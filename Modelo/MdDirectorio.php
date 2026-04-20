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
                id,
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

        // 4. Toda la experiencia laboral
        $stmtExp = $pdo->prepare("
            SELECT
                id,
                empresa_entidad,
                unidad_organica_area,
                cargo_puesto,
                fecha_inicio,
                fecha_fin,
                actualmente_trabaja,
                funciones_principales,
                archivo_sustento,
                estado_validacion
            FROM colab_experiencia
            WHERE colab_id = :id
            ORDER BY fecha_inicio DESC, id DESC
        ");
        $stmtExp->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtExp->execute();
        $perfil['experiencia'] = $stmtExp->fetchAll(PDO::FETCH_ASSOC);

        // 5. Todos los familiares (cónyuge + hijos con datos completos)
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

        // 6. Conteo de hijos (HIJO e HIJA)
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

public static function mdlActualizarPerfil($datos)
{
    $pdo = Conexion::conectar();

    // ── CONTROL DE PERMISOS POR ROL (SEGURIDAD BACKEND) ──
    $rolSesion = strtolower(trim($_SESSION['user_role'] ?? ''));

    if (!in_array($rolSesion, ['rrhh', 'admin', 'superadmin'], true)) {
        unset($datos['nombres_apellidos']);
        unset($datos['dni']);
        unset($datos['correo_institucional']);
        unset($datos['situacion']);
        unset($datos['sueldo']);
        unset($datos['mod_contrato']);
        unset($datos['puesto_cas']);
    }

    try {
        $pdo->beginTransaction();

        // ── 1. Actualizar tabla maestro ──────────────────────────
        $stmt = $pdo->prepare("
            UPDATE colab_maestro SET
                fecha_nacimiento     = :fecha_nacimiento,
                lugar_nacimiento     = :lugar_nacimiento,
                estado_civil         = :estado_civil,
                grupo_sanguineo      = :grupo_sanguineo,
                talla                = :talla,
                celular              = :celular,
                correo_personal      = :correo_personal,
                direccion_residencia = :direccion_residencia,
                distrito             = :distrito
            WHERE id = :id
        ");

        $stmt->execute([
            ':fecha_nacimiento'     => !empty($datos['fecha_nacimiento']) ? $datos['fecha_nacimiento'] : null,
            ':lugar_nacimiento'     => $datos['lugar_nacimiento'] ?? null,
            ':estado_civil'         => $datos['estado_civil'] ?? null,
            ':grupo_sanguineo'      => $datos['grupo_sanguineo'] ?? null,
            ':talla'                => $datos['talla'] ?? null,
            ':celular'              => $datos['celular'] ?? null,
            ':correo_personal'      => $datos['correo_personal'] ?? null,
            ':direccion_residencia' => $datos['direccion_residencia'] ?? null,
            ':distrito'             => $datos['distrito'] ?? null,
            ':id'                   => (int)$datos['id'],
        ]);

        // ── 1.1 Actualizar campos sensibles en maestro (solo RRHH/Admin) ──
        if (in_array($rolSesion, ['rrhh', 'admin', 'superadmin'], true)) {
            $stmtExtraMaestro = $pdo->prepare("
                UPDATE colab_maestro SET
                    nombres_apellidos = :nombres_apellidos,
                    dni               = :dni
                WHERE id = :id
            ");

            $stmtExtraMaestro->execute([
                ':nombres_apellidos' => $datos['nombres_apellidos'] ?? null,
                ':dni'               => $datos['dni'] ?? null,
                ':id'                => (int)$datos['id'],
            ]);
        }

        // ── 1.2 Actualizar tabla laboral (registro más reciente) ──
        if (in_array($rolSesion, ['rrhh', 'admin', 'superadmin'], true)) {

            $stmtActualLaboral = $pdo->prepare("
                SELECT
                    correo_institucional,
                    situacion,
                    sueldo,
                    modalidad_contrato,
                    puesto_cas
                FROM colab_laboral
                WHERE colab_id = :id
                ORDER BY fecha_ingreso DESC
                LIMIT 1
            ");
            $stmtActualLaboral->execute([
                ':id' => (int)$datos['id'],
            ]);

            $laboralActual = $stmtActualLaboral->fetch(PDO::FETCH_ASSOC) ?: [];

            $correoInstitucional = array_key_exists('correo_institucional', $datos) && trim((string)$datos['correo_institucional']) !== ''
                ? trim((string)$datos['correo_institucional'])
                : ($laboralActual['correo_institucional'] ?? null);

            $situacion = array_key_exists('situacion', $datos) && trim((string)$datos['situacion']) !== ''
                ? trim((string)$datos['situacion'])
                : ($laboralActual['situacion'] ?? null);

            $sueldo = array_key_exists('sueldo', $datos) && trim((string)$datos['sueldo']) !== ''
                ? $datos['sueldo']
                : ($laboralActual['sueldo'] ?? null);

            $modContrato = array_key_exists('mod_contrato', $datos) && trim((string)$datos['mod_contrato']) !== ''
                ? trim((string)$datos['mod_contrato'])
                : ($laboralActual['modalidad_contrato'] ?? null);

            $puestoCas = array_key_exists('puesto_cas', $datos) && trim((string)$datos['puesto_cas']) !== ''
                ? trim((string)$datos['puesto_cas'])
                : ($laboralActual['puesto_cas'] ?? null);

            $stmtLaboral = $pdo->prepare("
                UPDATE colab_laboral
                SET
                    correo_institucional = :correo_institucional,
                    situacion            = :situacion,
                    sueldo               = :sueldo,
                    modalidad_contrato   = :mod_contrato,
                    puesto_cas           = :puesto_cas
                WHERE colab_id = :id
                ORDER BY fecha_ingreso DESC
                LIMIT 1
            ");

            $stmtLaboral->execute([
                ':correo_institucional' => $correoInstitucional,
                ':situacion'            => $situacion,
                ':sueldo'               => $sueldo,
                ':mod_contrato'         => $modContrato,
                ':puesto_cas'           => $puestoCas,
                ':id'                   => (int)$datos['id'],
            ]);
        }

        // ── 2. Actualizar / insertar cónyuge ─────────────────────
        $nombreConyuge = trim($datos['conyuge'] ?? '');
        $fechaConyuge  = !empty($datos['fecha_nac_conyuge']) ? $datos['fecha_nac_conyuge'] : null;

        $chk = $pdo->prepare("
            SELECT id
            FROM colab_familia
            WHERE colab_id = :id AND parentesco = 'CONYUGE'
            LIMIT 1
        ");
        $chk->execute([':id' => (int)$datos['id']]);
        $conyugeExistente = $chk->fetchColumn();

        if ($conyugeExistente) {
            if ($nombreConyuge === '') {
                $pdo->prepare("DELETE FROM colab_familia WHERE id = :fid")
                    ->execute([':fid' => $conyugeExistente]);
            } else {
                $pdo->prepare("
                    UPDATE colab_familia SET
                        nombre_completo  = :nombre,
                        fecha_nacimiento = :fecha
                    WHERE id = :fid
                ")->execute([
                    ':nombre' => $nombreConyuge,
                    ':fecha'  => $fechaConyuge,
                    ':fid'    => $conyugeExistente,
                ]);
            }
        } elseif ($nombreConyuge !== '') {
            $pdo->prepare("
                INSERT INTO colab_familia (colab_id, parentesco, nombre_completo, fecha_nacimiento)
                VALUES (:colab_id, 'CONYUGE', :nombre, :fecha)
            ")->execute([
                ':colab_id' => (int)$datos['id'],
                ':nombre'   => $nombreConyuge,
                ':fecha'    => $fechaConyuge,
            ]);
        }

        // ── 3. Sincronizar hijos ─────────────────────────────────
        $stmtIds = $pdo->prepare("
            SELECT id
            FROM colab_familia
            WHERE colab_id = :id AND parentesco IN ('HIJO','HIJA')
        ");
        $stmtIds->execute([':id' => (int)$datos['id']]);
        $idsEnBD = $stmtIds->fetchAll(PDO::FETCH_COLUMN);
        $idsRecibidos = [];

        $hijos = $datos['hijos'] ?? [];
        foreach ($hijos as $hijo) {
            $nombre = trim($hijo['nombre'] ?? '');
            if ($nombre === '') {
                continue;
            }

            $hijoId      = (int)($hijo['id'] ?? 0);
            $parentesco  = in_array(($hijo['parentesco'] ?? ''), ['HIJO', 'HIJA'], true) ? $hijo['parentesco'] : 'HIJO';
            $fechaNac    = !empty($hijo['fecha_nacimiento']) ? $hijo['fecha_nacimiento'] : null;
            $dni         = $hijo['dni'] ?? null;

            if ($hijoId > 0 && in_array($hijoId, $idsEnBD)) {
                $pdo->prepare("
                    UPDATE colab_familia SET
                        nombre_completo  = :nombre,
                        parentesco       = :parentesco,
                        fecha_nacimiento = :fecha,
                        dni_familiar     = :dni
                    WHERE id = :fid AND colab_id = :colab_id
                ")->execute([
                    ':nombre'     => $nombre,
                    ':parentesco' => $parentesco,
                    ':fecha'      => $fechaNac,
                    ':dni'        => $dni,
                    ':fid'        => $hijoId,
                    ':colab_id'   => (int)$datos['id'],
                ]);
                $idsRecibidos[] = $hijoId;
            } else {
                $ins = $pdo->prepare("
                    INSERT INTO colab_familia (colab_id, parentesco, nombre_completo, fecha_nacimiento, dni_familiar)
                    VALUES (:colab_id, :parentesco, :nombre, :fecha, :dni)
                ");
                $ins->execute([
                    ':colab_id'   => (int)$datos['id'],
                    ':parentesco' => $parentesco,
                    ':nombre'     => $nombre,
                    ':fecha'      => $fechaNac,
                    ':dni'        => $dni,
                ]);
                $idsRecibidos[] = (int)$pdo->lastInsertId();
            }
        }

        $aEliminar = array_diff($idsEnBD, $idsRecibidos);
        if (!empty($aEliminar)) {
            $placeholders = implode(',', array_fill(0, count($aEliminar), '?'));
            $pdo->prepare("DELETE FROM colab_familia WHERE id IN ($placeholders)")
                ->execute(array_values($aEliminar));
        }

        // ── 4. Sincronizar formación académica ───────────────────
        $stmtFormIds = $pdo->prepare("
            SELECT id
            FROM colab_formacion
            WHERE colab_id = :id
        ");
        $stmtFormIds->execute([':id' => (int)$datos['id']]);
        $idsFormEnBD = $stmtFormIds->fetchAll(PDO::FETCH_COLUMN);
        $idsFormRecibidos = [];

        $formacion = $datos['formacion'] ?? [];
        foreach ($formacion as $form) {
            $carrera     = trim($form['descripcion_carrera'] ?? '');
            $institucion = trim($form['institucion'] ?? '');

            if ($carrera === '' && $institucion === '') {
                continue;
            }

            $formId = (int)($form['id'] ?? 0);
            $tipoGrado = $form['tipo_grado'] ?? 'BACHILLER';

            if ($formId > 0 && in_array($formId, $idsFormEnBD)) {
                $pdo->prepare("
                    UPDATE colab_formacion SET
                        tipo_grado          = :tipo,
                        descripcion_carrera = :carrera,
                        institucion         = :institucion,
                        estado_validacion   = 'PENDIENTE'
                    WHERE id = :fid AND colab_id = :colab_id
                ")->execute([
                    ':tipo'        => $tipoGrado,
                    ':carrera'     => $carrera,
                    ':institucion' => $institucion,
                    ':fid'         => $formId,
                    ':colab_id'    => (int)$datos['id'],
                ]);
                $idsFormRecibidos[] = $formId;
            } else {
                $pdo->prepare("
                    INSERT INTO colab_formacion (colab_id, tipo_grado, descripcion_carrera, institucion, estado_validacion)
                    VALUES (:colab_id, :tipo, :carrera, :institucion, 'PENDIENTE')
                ")->execute([
                    ':colab_id'    => (int)$datos['id'],
                    ':tipo'        => $tipoGrado,
                    ':carrera'     => $carrera,
                    ':institucion' => $institucion,
                ]);
                $idsFormRecibidos[] = (int)$pdo->lastInsertId();
            }
        }

        $aEliminarForm = array_diff($idsFormEnBD, $idsFormRecibidos);
        if (!empty($aEliminarForm)) {
            $placeholders = implode(',', array_fill(0, count($aEliminarForm), '?'));
            $pdo->prepare("DELETE FROM colab_formacion WHERE id IN ($placeholders)")
                ->execute(array_values($aEliminarForm));
        }

        // ── 5. Sincronizar experiencia laboral ───────────────────
        $stmtExpIds = $pdo->prepare("
            SELECT id
            FROM colab_experiencia
            WHERE colab_id = :id
        ");
        $stmtExpIds->execute([':id' => (int)$datos['id']]);
        $idsExpEnBD = $stmtExpIds->fetchAll(PDO::FETCH_COLUMN);
        $idsExpRecibidos = [];

        $experiencia = $datos['experiencia'] ?? [];
        foreach ($experiencia as $exp) {
            $empresa  = trim($exp['empresa_entidad'] ?? '');
            $cargo    = trim($exp['cargo_puesto'] ?? '');
            $area     = trim($exp['unidad_organica_area'] ?? '');
            $funciones = trim($exp['funciones_principales'] ?? '');

            if ($empresa === '' && $cargo === '' && $area === '' && $funciones === '') {
                continue;
            }

            $expId = (int)($exp['id'] ?? 0);
            $fechaInicio = !empty($exp['fecha_inicio']) ? $exp['fecha_inicio'] : null;
            $fechaFin = !empty($exp['fecha_fin']) ? $exp['fecha_fin'] : null;
            $actualmenteTrabaja = !empty($exp['actualmente_trabaja']) ? 1 : 0;

            if ($actualmenteTrabaja === 1) {
                $fechaFin = null;
            }

            if ($expId > 0 && in_array($expId, $idsExpEnBD)) {
                $pdo->prepare("
                    UPDATE colab_experiencia SET
                        empresa_entidad       = :empresa,
                        unidad_organica_area  = :area,
                        cargo_puesto          = :cargo,
                        fecha_inicio          = :fecha_inicio,
                        fecha_fin             = :fecha_fin,
                        actualmente_trabaja   = :actualmente_trabaja,
                        funciones_principales = :funciones,
                        estado_validacion     = 'PENDIENTE'
                    WHERE id = :eid AND colab_id = :colab_id
                ")->execute([
                    ':empresa'             => $empresa,
                    ':area'                => $area !== '' ? $area : null,
                    ':cargo'               => $cargo,
                    ':fecha_inicio'        => $fechaInicio,
                    ':fecha_fin'           => $fechaFin,
                    ':actualmente_trabaja' => $actualmenteTrabaja,
                    ':funciones'           => $funciones !== '' ? $funciones : null,
                    ':eid'                 => $expId,
                    ':colab_id'            => (int)$datos['id'],
                ]);
                $idsExpRecibidos[] = $expId;
            } else {
                $pdo->prepare("
                    INSERT INTO colab_experiencia (
                        colab_id,
                        empresa_entidad,
                        unidad_organica_area,
                        cargo_puesto,
                        fecha_inicio,
                        fecha_fin,
                        actualmente_trabaja,
                        funciones_principales,
                        estado_validacion
                    ) VALUES (
                        :colab_id,
                        :empresa,
                        :area,
                        :cargo,
                        :fecha_inicio,
                        :fecha_fin,
                        :actualmente_trabaja,
                        :funciones,
                        'PENDIENTE'
                    )
                ")->execute([
                    ':colab_id'            => (int)$datos['id'],
                    ':empresa'             => $empresa,
                    ':area'                => $area !== '' ? $area : null,
                    ':cargo'               => $cargo,
                    ':fecha_inicio'        => $fechaInicio,
                    ':fecha_fin'           => $fechaFin,
                    ':actualmente_trabaja' => $actualmenteTrabaja,
                    ':funciones'           => $funciones !== '' ? $funciones : null,
                ]);
                $idsExpRecibidos[] = (int)$pdo->lastInsertId();
            }
        }

        $aEliminarExp = array_diff($idsExpEnBD, $idsExpRecibidos);
        if (!empty($aEliminarExp)) {
            $placeholders = implode(',', array_fill(0, count($aEliminarExp), '?'));
            $pdo->prepare("DELETE FROM colab_experiencia WHERE id IN ($placeholders)")
                ->execute(array_values($aEliminarExp));
        }

        $pdo->commit();

        return [
            'success' => true,
            'mensaje' => 'Perfil actualizado correctamente'
        ];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return [
            'success' => false,
            'mensaje' => 'Error al actualizar el perfil: ' . $e->getMessage()
        ];
    }
}
}
