<?php
// /Modelo/MdDirectorio.php

require_once "Conexion.php";

class MdDirectorio
{

    /*=============================================
    MODELO PARA LA TABLA PRINCIPAL
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
    MODELO PARA EL PERFIL DETALLE
    =============================================*/
    public static function mdlObtenerPerfilCompleto($id)
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT 
                m.*, 
                l.sueldo, 
                l.nsa_cip, 
                l.correo_institucional, 
                l.puesto_cas, 
                l.tipo_puesto, 
                l.area, 
                l.procedencia, 
                l.modalidad_contrato AS mod_contrato, -- Alias para la vista
                l.situacion, 
                l.fecha_ingreso, 
                l.fecha_cese,
                (SELECT nombre_completo FROM colab_familia WHERE colab_id = m.id AND parentesco = 'CONYUGE' LIMIT 1) as conyuge,
                (SELECT fecha_nacimiento FROM colab_familia WHERE colab_id = m.id AND parentesco = 'CONYUGE' LIMIT 1) as onomastico_conyuge,
                (SELECT COUNT(*) FROM colab_familia WHERE colab_id = m.id AND parentesco LIKE 'HIJO%') as n_hijos
            FROM colab_maestro m
            INNER JOIN colab_laboral l ON m.id = l.colab_id
            WHERE m.id = :id
        ");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
