<?php
// /Modelo/MdDirectorio.php

require_once "Conexion.php";

class MdDirectorio
{

    /*=============================================
    MODELO PARA LA TABLA PRINCIPAL (DIRECTORIO)
    =============================================*/
    public static function mdlMostrarDirectorio()
    {
        try {
            $stmt = Conexion::conectar()->prepare("
                SELECT 
                    m.id, 
                    m.nombres_apellidos, 
                    m.dni, 
                    m.celular,
                    l.puesto_cas, 
                    l.area, 
                    l.correo_institucional,
                    l.situacion
                FROM colab_maestro m
                INNER JOIN colab_laboral l ON m.id = l.colab_id
                ORDER BY m.nombres_apellidos ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /*=============================================
    DATOS MAESTRO + LABORAL PRINCIPAL
    (Se toma el registro laboral más reciente como principal)
    =============================================*/
    public static function mdlObtenerPerfilCompleto($id)
    {
        $pdo = Conexion::conectar();

        // 1. Datos maestro + laboral más reciente como contexto principal
        $stmt = $pdo->prepare("
            SELECT 
                m.id,
                m.nombres_apellidos,
                m.dni,
                m.fecha_nacimiento,
                m.lugar_nacimiento,
                m.edad,
                m.sexo,
                m.estado_civil,
                m.grupo_sanguineo,
                m.talla,
                m.celular,
                m.correo_personal,
                m.direccion_residencia,
                m.distrito,
                l.sueldo,
                l.nsa_cip,
                l.correo_institucional,
                l.puesto_cas,
                l.tipo_puesto,
                l.area,
                l.procedencia,
                l.modalidad_contrato   AS mod_contrato,
                l.situacion
            FROM colab_maestro m
            INNER JOIN colab_laboral l ON m.id = l.colab_id
            WHERE m.id = :id
            ORDER BY l.fecha_ingreso DESC
            LIMIT 1
        ");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $perfil = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$perfil) return false;

        // 2. TODAS las fechas de ingreso / contratos (puede haber varios)
        $stmt2 = $pdo->prepare("
            SELECT 
                fecha_ingreso,
                fecha_cese,
                modalidad_contrato,
                puesto_cas,
                area,
                situacion
            FROM colab_laboral
            WHERE colab_id = :id
            ORDER BY fecha_ingreso ASC
        ");
        $stmt2->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt2->execute();
        $perfil['contratos'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // 3. TODA la formación académica (puede haber varios registros: grado, especialización, etc.)
        $stmt3 = $pdo->prepare("
            SELECT 
                tipo_grado,
                descripcion_carrera,
                institucion,
                estado_validacion
            FROM colab_formacion
            WHERE colab_id = :id
            ORDER BY id ASC
        ");
        $stmt3->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt3->execute();
        $perfil['formacion'] = $stmt3->fetchAll(PDO::FETCH_ASSOC);

        // 4. Todos los familiares (cónyuge + hijos con datos completos)
        $stmt4 = $pdo->prepare("
            SELECT 
                id,
                parentesco,
                nombre_completo,
                dni_familiar,
                fecha_nacimiento,
                archivo_sustento,
                estado_validacion
            FROM colab_familia
            WHERE colab_id = :id
            ORDER BY parentesco ASC, nombre_completo ASC
        ");
        $stmt4->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt4->execute();
        $familia = $stmt4->fetchAll(PDO::FETCH_ASSOC);
        $perfil['familia'] = $familia;

        // Separar cónyuge de hijos para acceso rápido
        $conyuge_row = array_filter($familia, fn($f) => $f['parentesco'] === 'CONYUGE');
        $conyuge_row = reset($conyuge_row);
        $perfil['conyuge']            = $conyuge_row['nombre_completo'] ?? null;
        $perfil['onomastico_conyuge'] = $conyuge_row['fecha_nacimiento'] ?? null;

        // 5. Conteo de hijos (HIJO e HIJA)
        $stmt5 = $pdo->prepare("
            SELECT COUNT(*) AS total
            FROM colab_familia
            WHERE colab_id = :id AND parentesco IN ('HIJO', 'HIJA')
        ");
        $stmt5->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt5->execute();
        $perfil['n_hijos'] = $stmt5->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        return $perfil;
    }
}