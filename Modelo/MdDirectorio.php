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
RESUMEN DASHBOARD DINÁMICO
=============================================*/
    public static function mdlObtenerResumenDashboard()
    {
        try {
            $pdo = Conexion::conectar();

            $respuesta = [
                'total_colaboradores'      => 0,
                'validaciones_pendientes'  => 0,
                'contratos_por_vencer'     => 0,
                'modalidades'              => [],
                'cumpleanos'               => [],
            ];

            // Total de colaboradores únicos
            $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total
            FROM colab_maestro
        ");
            $stmt->execute();
            $respuesta['total_colaboradores'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

            // Validaciones pendientes
            $stmt = $pdo->prepare("
            SELECT
                COALESCE((
                    SELECT COUNT(*) FROM colab_formacion WHERE estado_validacion = 'PENDIENTE'
                ), 0)
                +
                COALESCE((
                    SELECT COUNT(*) FROM colab_experiencia WHERE estado_validacion = 'PENDIENTE'
                ), 0)
                +
                COALESCE((
                    SELECT COUNT(*) FROM colab_familia WHERE estado_validacion = 'PENDIENTE'
                ), 0)
                AS total
        ");
            $stmt->execute();
            $respuesta['validaciones_pendientes'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

            // Contratos por vencer en próximos 30 días
            $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total
            FROM colab_laboral
            WHERE fecha_cese IS NOT NULL
              AND fecha_cese >= CURDATE()
              AND fecha_cese <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ");
            $stmt->execute();
            $respuesta['contratos_por_vencer'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

            // Distribución por modalidad
            $stmt = $pdo->prepare("
            SELECT
                COALESCE(NULLIF(TRIM(modalidad_contrato), ''), 'SIN MODALIDAD') AS modalidad,
                COUNT(*) AS total
            FROM colab_laboral
            GROUP BY modalidad
            ORDER BY total DESC, modalidad ASC
        ");
            $stmt->execute();
            $respuesta['modalidades'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Próximos cumpleaños de colaboradores
            $stmt = $pdo->prepare("
            SELECT
                m.id,
                m.nombres_apellidos AS nombre,
                m.fecha_nacimiento
            FROM colab_maestro m
            WHERE m.fecha_nacimiento IS NOT NULL
            ORDER BY
                CASE
                    WHEN DATE_FORMAT(m.fecha_nacimiento, '%m-%d') >= DATE_FORMAT(CURDATE(), '%m-%d')
                    THEN DATE_FORMAT(m.fecha_nacimiento, '%m-%d')
                    ELSE DATE_FORMAT(DATE_ADD(m.fecha_nacimiento, INTERVAL 1 YEAR), '%m-%d')
                END ASC
            LIMIT 5
        ");
            $stmt->execute();
            $respuesta['cumpleanos'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return $respuesta;
        } catch (Exception $e) {
            return [
                'total_colaboradores'      => 0,
                'validaciones_pendientes'  => 0,
                'contratos_por_vencer'     => 0,
                'modalidades'              => [],
                'cumpleanos'               => [],
            ];
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
                l.modalidad_contrato AS mod_contrato,
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

        // 2. Historial de contratos
        $stmt2 = $pdo->prepare("
            SELECT 
                id,
                colab_id,
                fecha_ingreso,
                fecha_cese,
                modalidad
            FROM colab_contratos
            WHERE colab_id = :id
            ORDER BY fecha_ingreso ASC, id ASC
        ");
        $stmt2->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt2->execute();
        $perfil['contratos'] = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 3. Formación académica
        $stmt3 = $pdo->prepare("
            SELECT 
                id,
                tipo_grado,
                descripcion_carrera,
                institucion,
                anio_realizacion,
                horas_lectivas,
                especialidad,
                grado_alcanzado,
                estado_validacion
            FROM colab_formacion
            WHERE colab_id = :id
            ORDER BY id ASC
        ");
        $stmt3->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt3->execute();
        $perfil['formacion'] = $stmt3->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 4. Experiencia laboral
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
        $perfil['experiencia'] = $stmtExp->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 5. Familia
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
        $familia = $stmt4->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $perfil['familia'] = $familia;

        $conyuge_row = array_filter($familia, fn($f) => ($f['parentesco'] ?? '') === 'CONYUGE');
        $conyuge_row = reset($conyuge_row);

        $perfil['conyuge']            = $conyuge_row['nombre_completo'] ?? null;
        $perfil['onomastico_conyuge'] = $conyuge_row['fecha_nacimiento'] ?? null;
        $perfil['dni_conyuge']        = $conyuge_row['dni_familiar'] ?? null;

        // 6. Conteo de hijos
        $stmt5 = $pdo->prepare("
            SELECT COUNT(*) AS total
            FROM colab_familia
            WHERE colab_id = :id
              AND parentesco IN ('HIJO', 'HIJA')
        ");
        $stmt5->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt5->execute();
        $perfil['n_hijos'] = (int)($stmt5->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // 7. Pensión
        $stmt6 = $pdo->prepare("
            SELECT
                id,
                sistema_pension,
                afp,
                cuspp,
                tipo_comision,
                fecha_inscripcion,
                sin_afp_afiliarme
            FROM colab_pension
            WHERE colab_id = :id
            LIMIT 1
        ");
        $stmt6->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt6->execute();
        $perfil['pension'] = $stmt6->fetch(PDO::FETCH_ASSOC) ?: [];

        // 8. Bancario
        $stmt7 = $pdo->prepare("
            SELECT
                id,
                banco_haberes,
                numero_cuenta,
                numero_cuenta_cci
            FROM colab_bancario
            WHERE colab_id = :id
            LIMIT 1
        ");
        $stmt7->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt7->execute();
        $perfil['bancario'] = $stmt7->fetch(PDO::FETCH_ASSOC) ?: [];

        // 9. Idiomas
        $stmt8 = $pdo->prepare("
            SELECT
                id,
                idioma,
                nivel
            FROM colab_idioma
            WHERE colab_id = :id
            ORDER BY id ASC
        ");
        $stmt8->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt8->execute();
        $perfil['idiomas'] = $stmt8->fetchAll(PDO::FETCH_ASSOC) ?: [];

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
            unset($datos['tipo_puesto']);
            unset($datos['area']);
            unset($datos['procedencia']);
            unset($datos['nsa_cip']);
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
                        puesto_cas,
                        tipo_puesto,
                        area,
                        procedencia,
                        nsa_cip
                    FROM colab_laboral
                    WHERE colab_id = :id
                    ORDER BY fecha_ingreso DESC, id DESC
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

                $tipoPuesto = array_key_exists('tipo_puesto', $datos) && trim((string)$datos['tipo_puesto']) !== ''
                    ? trim((string)$datos['tipo_puesto'])
                    : ($laboralActual['tipo_puesto'] ?? null);

                $area = array_key_exists('area', $datos) && trim((string)$datos['area']) !== ''
                    ? trim((string)$datos['area'])
                    : ($laboralActual['area'] ?? null);

                $procedencia = array_key_exists('procedencia', $datos) && trim((string)$datos['procedencia']) !== ''
                    ? trim((string)$datos['procedencia'])
                    : ($laboralActual['procedencia'] ?? null);

                $nsaCip = array_key_exists('nsa_cip', $datos) && trim((string)$datos['nsa_cip']) !== ''
                    ? trim((string)$datos['nsa_cip'])
                    : ($laboralActual['nsa_cip'] ?? null);

                $stmtLaboral = $pdo->prepare("
                        UPDATE colab_laboral
                        SET
                            correo_institucional = :correo_institucional,
                            situacion            = :situacion,
                            sueldo               = :sueldo,
                            modalidad_contrato   = :mod_contrato,
                            puesto_cas           = :puesto_cas,
                            tipo_puesto          = :tipo_puesto,
                            area                 = :area,
                            procedencia          = :procedencia,
                            nsa_cip              = :nsa_cip
                        WHERE colab_id = :id
                        ORDER BY fecha_ingreso DESC, id DESC
                        LIMIT 1
                    ");

                $stmtLaboral->execute([
                    ':correo_institucional' => $correoInstitucional,
                    ':situacion'            => $situacion,
                    ':sueldo'               => $sueldo,
                    ':mod_contrato'         => $modContrato,
                    ':puesto_cas'           => $puestoCas,
                    ':tipo_puesto'          => $tipoPuesto,
                    ':area'                 => $area,
                    ':procedencia'          => $procedencia,
                    ':nsa_cip'              => $nsaCip,
                    ':id'                   => (int)$datos['id'],
                ]);
            }
            // ── 2. Actualizar / insertar cónyuge ─────────────────────
            $nombreConyuge = trim($datos['conyuge'] ?? '');
            $fechaConyuge  = !empty($datos['onomastico_conyuge']) ? $datos['onomastico_conyuge'] : null;
            $dniConyuge    = trim($datos['dni_conyuge'] ?? '');

            $chk = $pdo->prepare("
                SELECT id
                FROM colab_familia
                WHERE colab_id = :id AND parentesco = 'CONYUGE'
                LIMIT 1
            ");
            $chk->execute([':id' => (int)$datos['id']]);
            $conyugeExistente = $chk->fetchColumn();

            if ($conyugeExistente) {
                if ($nombreConyuge === '' && $fechaConyuge === null && $dniConyuge === '') {
                    $pdo->prepare("
                        DELETE FROM colab_familia
                        WHERE id = :fid
                    ")->execute([
                        ':fid' => $conyugeExistente
                    ]);
                } else {
                    $pdo->prepare("
                        UPDATE colab_familia SET
                            nombre_completo  = :nombre,
                            fecha_nacimiento = :fecha,
                            dni_familiar     = :dni
                        WHERE id = :fid
                    ")->execute([
                        ':nombre' => $nombreConyuge !== '' ? $nombreConyuge : null,
                        ':fecha'  => $fechaConyuge,
                        ':dni'    => $dniConyuge !== '' ? $dniConyuge : null,
                        ':fid'    => $conyugeExistente,
                    ]);
                }
            } elseif ($nombreConyuge !== '' || $fechaConyuge !== null || $dniConyuge !== '') {
                $pdo->prepare("
                    INSERT INTO colab_familia (
                        colab_id,
                        parentesco,
                        nombre_completo,
                        fecha_nacimiento,
                        dni_familiar
                    )
                    VALUES (
                        :colab_id,
                        'CONYUGE',
                        :nombre,
                        :fecha,
                        :dni
                    )
                ")->execute([
                    ':colab_id' => (int)$datos['id'],
                    ':nombre'   => $nombreConyuge !== '' ? $nombreConyuge : null,
                    ':fecha'    => $fechaConyuge,
                    ':dni'      => $dniConyuge !== '' ? $dniConyuge : null,
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

            // ── 3.1. Sincronizar contratos ─────────────────────────────
            $stmtContratoIds = $pdo->prepare("
                SELECT id
                FROM colab_contratos
                WHERE colab_id = :id
            ");
            $stmtContratoIds->execute([
                ':id' => (int)$datos['id']
            ]);
            $idsContratosEnBD = array_map('intval', $stmtContratoIds->fetchAll(PDO::FETCH_COLUMN));
            $idsContratosRecibidos = [];

            $contratos = $datos['contratos'] ?? [];

            foreach ($contratos as $contrato) {
                $contratoId   = (int)($contrato['id'] ?? 0);
                $fechaIngreso = !empty($contrato['fecha_ingreso']) ? $contrato['fecha_ingreso'] : null;
                $fechaCese    = !empty($contrato['fecha_cese']) ? $contrato['fecha_cese'] : null;
                $modalidad    = trim((string)($contrato['modalidad'] ?? ''));

                // Si la fila viene vacía, no se procesa
                if ($fechaIngreso === null && $fechaCese === null && $modalidad === '') {
                    continue;
                }

                if ($contratoId > 0 && in_array($contratoId, $idsContratosEnBD, true)) {
                    $stmtUpdateContrato = $pdo->prepare("
                        UPDATE colab_contratos SET
                            fecha_ingreso = :fecha_ingreso,
                            fecha_cese    = :fecha_cese,
                            modalidad     = :modalidad
                        WHERE id = :id
                          AND colab_id = :colab_id
                    ");

                    $stmtUpdateContrato->execute([
                        ':fecha_ingreso' => $fechaIngreso,
                        ':fecha_cese'    => $fechaCese,
                        ':modalidad'     => $modalidad !== '' ? $modalidad : null,
                        ':id'            => $contratoId,
                        ':colab_id'      => (int)$datos['id'],
                    ]);

                    $idsContratosRecibidos[] = $contratoId;
                } else {
                    $stmtInsertContrato = $pdo->prepare("
                        INSERT INTO colab_contratos (
                            colab_id,
                            fecha_ingreso,
                            fecha_cese,
                            modalidad
                        ) VALUES (
                            :colab_id,
                            :fecha_ingreso,
                            :fecha_cese,
                            :modalidad
                        )
                    ");

                    $stmtInsertContrato->execute([
                        ':colab_id'      => (int)$datos['id'],
                        ':fecha_ingreso' => $fechaIngreso,
                        ':fecha_cese'    => $fechaCese,
                        ':modalidad'     => $modalidad !== '' ? $modalidad : null,
                    ]);

                    $idsContratosRecibidos[] = (int)$pdo->lastInsertId();
                }
            }

            $aEliminarContratos = array_diff($idsContratosEnBD, $idsContratosRecibidos);

            if (!empty($aEliminarContratos)) {
                $placeholders = implode(',', array_fill(0, count($aEliminarContratos), '?'));
                $stmtDeleteContratos = $pdo->prepare("
                    DELETE FROM colab_contratos
                    WHERE id IN ($placeholders)
                ");
                $stmtDeleteContratos->execute(array_values($aEliminarContratos));
            }

            // ── 4. Sincronizar formación académica ───────────────────
            $stmtFormIds = $pdo->prepare("
                SELECT id
                FROM colab_formacion
                WHERE colab_id = :id
            ");
            $stmtFormIds->execute([':id' => (int)$datos['id']]);
            $idsFormEnBD = array_map('intval', $stmtFormIds->fetchAll(PDO::FETCH_COLUMN));
            $idsFormRecibidos = [];

            $formacion = $datos['formacion'] ?? [];

            foreach ($formacion as $form) {
                $formId           = (int)($form['id'] ?? 0);
                $tipoGrado        = trim($form['tipo_grado'] ?? 'BACHILLER');
                $carrera          = trim($form['descripcion_carrera'] ?? '');
                $institucion      = trim($form['institucion'] ?? '');
                $anioRealizacion  = isset($form['anio_realizacion']) && $form['anio_realizacion'] !== ''
                    ? (int)$form['anio_realizacion']
                    : null;
                $horasLectivas    = isset($form['horas_lectivas']) && $form['horas_lectivas'] !== ''
                    ? (int)$form['horas_lectivas']
                    : null;
                $especialidad     = trim($form['especialidad'] ?? '');
                $gradoAlcanzado   = trim($form['grado_alcanzado'] ?? '');

                // Si la fila viene totalmente vacía, no se procesa
                if (
                    $tipoGrado === '' &&
                    $carrera === '' &&
                    $institucion === '' &&
                    $anioRealizacion === null &&
                    $horasLectivas === null &&
                    $especialidad === '' &&
                    $gradoAlcanzado === ''
                ) {
                    continue;
                }

                if ($formId > 0 && in_array($formId, $idsFormEnBD, true)) {
                    $stmtUpdateForm = $pdo->prepare("
                        UPDATE colab_formacion SET
                            tipo_grado          = :tipo_grado,
                            descripcion_carrera = :descripcion_carrera,
                            institucion         = :institucion,
                            anio_realizacion    = :anio_realizacion,
                            horas_lectivas      = :horas_lectivas,
                            especialidad        = :especialidad,
                            grado_alcanzado     = :grado_alcanzado,
                            estado_validacion   = 'PENDIENTE'
                        WHERE id = :id
                          AND colab_id = :colab_id
                    ");

                    $stmtUpdateForm->execute([
                        ':tipo_grado'          => $tipoGrado !== '' ? $tipoGrado : null,
                        ':descripcion_carrera' => $carrera !== '' ? $carrera : null,
                        ':institucion'         => $institucion !== '' ? $institucion : null,
                        ':anio_realizacion'    => $anioRealizacion,
                        ':horas_lectivas'      => $horasLectivas,
                        ':especialidad'        => $especialidad !== '' ? $especialidad : null,
                        ':grado_alcanzado'     => $gradoAlcanzado !== '' ? $gradoAlcanzado : null,
                        ':id'                  => $formId,
                        ':colab_id'            => (int)$datos['id'],
                    ]);

                    $idsFormRecibidos[] = $formId;
                } else {
                    $stmtInsertForm = $pdo->prepare("
                        INSERT INTO colab_formacion (
                            colab_id,
                            tipo_grado,
                            descripcion_carrera,
                            institucion,
                            anio_realizacion,
                            horas_lectivas,
                            especialidad,
                            grado_alcanzado,
                            estado_validacion
                        ) VALUES (
                            :colab_id,
                            :tipo_grado,
                            :descripcion_carrera,
                            :institucion,
                            :anio_realizacion,
                            :horas_lectivas,
                            :especialidad,
                            :grado_alcanzado,
                            'PENDIENTE'
                        )
                    ");

                    $stmtInsertForm->execute([
                        ':colab_id'            => (int)$datos['id'],
                        ':tipo_grado'          => $tipoGrado !== '' ? $tipoGrado : null,
                        ':descripcion_carrera' => $carrera !== '' ? $carrera : null,
                        ':institucion'         => $institucion !== '' ? $institucion : null,
                        ':anio_realizacion'    => $anioRealizacion,
                        ':horas_lectivas'      => $horasLectivas,
                        ':especialidad'        => $especialidad !== '' ? $especialidad : null,
                        ':grado_alcanzado'     => $gradoAlcanzado !== '' ? $gradoAlcanzado : null,
                    ]);

                    $idsFormRecibidos[] = (int)$pdo->lastInsertId();
                }
            }

            $aEliminarForm = array_diff($idsFormEnBD, $idsFormRecibidos);

            if (!empty($aEliminarForm)) {
                $placeholders = implode(',', array_fill(0, count($aEliminarForm), '?'));
                $stmtDeleteForm = $pdo->prepare("
                    DELETE FROM colab_formacion
                    WHERE id IN ($placeholders)
                ");
                $stmtDeleteForm->execute(array_values($aEliminarForm));
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

            // ── 6. Sincronizar pensión ───────────────────────────
            $pension = $datos['pension'] ?? null;

            if (is_array($pension)) {

                $stmtPen = $pdo->prepare("
        SELECT id
        FROM colab_pension
        WHERE colab_id = :id
        LIMIT 1
        ");
                $stmtPen->execute([
                    ':id' => (int)$datos['id'],
                ]);

                $pensionId = (int)($stmtPen->fetchColumn() ?: 0);

                $payloadPension = [
                    ':colab_id'          => (int)$datos['id'],
                    ':sistema_pension'   => !empty($pension['sistema_pension']) ? $pension['sistema_pension'] : null,
                    ':afp'               => !empty($pension['afp']) ? $pension['afp'] : null,
                    ':cuspp'             => !empty($pension['cuspp']) ? trim((string)$pension['cuspp']) : null,
                    ':tipo_comision'     => !empty($pension['tipo_comision']) ? $pension['tipo_comision'] : null,
                    ':fecha_inscripcion' => !empty($pension['fecha_inscripcion']) ? $pension['fecha_inscripcion'] : null,
                    ':sin_afp_afiliarme' => !empty($pension['sin_afp_afiliarme']) ? 1 : 0,
                ];

                if ($pensionId > 0) {
                    $pdo->prepare("
            UPDATE colab_pension SET
                sistema_pension   = :sistema_pension,
                afp               = :afp,
                cuspp             = :cuspp,
                tipo_comision     = :tipo_comision,
                fecha_inscripcion = :fecha_inscripcion,
                sin_afp_afiliarme = :sin_afp_afiliarme
            WHERE colab_id = :colab_id
        ")->execute($payloadPension);
                } else {
                    $pdo->prepare("
            INSERT INTO colab_pension (
                colab_id,
                sistema_pension,
                afp,
                cuspp,
                tipo_comision,
                fecha_inscripcion,
                sin_afp_afiliarme
            ) VALUES (
                :colab_id,
                :sistema_pension,
                :afp,
                :cuspp,
                :tipo_comision,
                :fecha_inscripcion,
                :sin_afp_afiliarme
            )
        ")->execute($payloadPension);
                }
            }
            // ── 7. Sincronizar datos bancarios ───────────────────
            $bancario = $datos['bancario'] ?? null;

            if (is_array($bancario)) {

                $stmtBan = $pdo->prepare("
        SELECT id
        FROM colab_bancario
        WHERE colab_id = :id
        LIMIT 1
     ");
                $stmtBan->execute([
                    ':id' => (int)$datos['id'],
                ]);

                $bancarioId = (int)($stmtBan->fetchColumn() ?: 0);

                $payloadBancario = [
                    ':colab_id'          => (int)$datos['id'],
                    ':banco_haberes'     => !empty($bancario['banco_haberes']) ? trim((string)$bancario['banco_haberes']) : null,
                    ':numero_cuenta'     => !empty($bancario['numero_cuenta']) ? trim((string)$bancario['numero_cuenta']) : null,
                    ':numero_cuenta_cci' => !empty($bancario['numero_cuenta_cci']) ? trim((string)$bancario['numero_cuenta_cci']) : null,
                ];

                if ($bancarioId > 0) {
                    $pdo->prepare("
            UPDATE colab_bancario SET
                banco_haberes     = :banco_haberes,
                numero_cuenta     = :numero_cuenta,
                numero_cuenta_cci = :numero_cuenta_cci
            WHERE colab_id = :colab_id
        ")->execute($payloadBancario);
                } else {
                    $pdo->prepare("
            INSERT INTO colab_bancario (
                colab_id,
                banco_haberes,
                numero_cuenta,
                numero_cuenta_cci
            ) VALUES (
                :colab_id,
                :banco_haberes,
                :numero_cuenta,
                :numero_cuenta_cci
            )
        ")->execute($payloadBancario);
                }
            }

            // ── 8. Sincronizar idiomas ───────────────────────────
            $idiomas = $datos['idiomas'] ?? null;

            if (is_array($idiomas)) {

                $pdo->prepare("
        DELETE FROM colab_idioma
        WHERE colab_id = :id
        ")->execute([
                    ':id' => (int)$datos['id'],
                ]);

                foreach ($idiomas as $idioma) {
                    $nombreIdioma = trim((string)($idioma['idioma'] ?? ''));
                    $nivelIdioma  = strtoupper(trim((string)($idioma['nivel'] ?? 'BASICO')));

                    if ($nombreIdioma === '') {
                        continue;
                    }

                    if (!in_array($nivelIdioma, ['BASICO', 'INTERMEDIO', 'AVANZADO'], true)) {
                        $nivelIdioma = 'BASICO';
                    }

                    $pdo->prepare("
            INSERT INTO colab_idioma (
                colab_id,
                idioma,
                nivel
            ) VALUES (
                :colab_id,
                :idioma,
                :nivel
            )
        ")->execute([
                        ':colab_id' => (int)$datos['id'],
                        ':idioma'   => $nombreIdioma,
                        ':nivel'    => $nivelIdioma,
                    ]);
                }
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
