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
                m.grado_militar,

                l.puesto_cas, 
                l.tipo_puesto,
                l.area, 
                l.correo_institucional,
                COALESCE(NULLIF(TRIM(l.situacion), ''), 'ACTIVO') AS situacion,

                COALESCE(
                    NULLIF(TRIM(l.modalidad_contrato), ''),
                    NULLIF(TRIM(ct.modalidad), '')
                ) AS modalidad_contrato

            FROM colab_maestro m

            LEFT JOIN (
                SELECT l1.*
                FROM colab_laboral l1
                INNER JOIN (
                    SELECT colab_id, MAX(id) AS max_id
                    FROM colab_laboral
                    GROUP BY colab_id
                ) ult ON ult.max_id = l1.id
            ) l ON l.colab_id = m.id

            LEFT JOIN (
                SELECT c1.*
                FROM colab_contratos c1
                INNER JOIN (
                    SELECT colab_id, MAX(id) AS max_id
                    FROM colab_contratos
                    GROUP BY colab_id
                ) uc ON uc.max_id = c1.id
            ) ct ON ct.colab_id = m.id

            ORDER BY m.nombres_apellidos ASC
        ");

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    private static function nullify($valor)
    {
        if ($valor === null) {
            return null;
        }

        $valor = trim((string)$valor);

        return $valor === '' ? null : $valor;
    }

    private static function valorSolicitudVacio($valor): bool
    {
        if ($valor === null || $valor === '' || $valor === []) {
            return true;
        }

        if (is_array($valor)) {
            return count($valor) === 0;
        }

        return false;
    }

    private static function normalizarSolicitudComparar($valor, string $campo = '')
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        if (!is_array($valor)) {
            $v = trim((string)$valor);

            if ($campo === 'sin_afp_afiliarme' && ($v === '0' || strtolower($v) === 'false')) {
                return '';
            }

            return $v;
        }

        if ($valor === []) {
            return [];
        }

        $esLista = array_keys($valor) === range(0, count($valor) - 1);

        $ignorar = [
            'id',
            'colab_id',
            'usuario_id',
            'edad',
            'n_hijos',
            'archivo_sustento',
            'nombre_archivo_original',
            'mime_archivo',
            'tamano_archivo',
            'estado_validacion',
            'created_at',
            'updated_at',
        ];

        if ($esLista) {
            $lista = [];

            foreach ($valor as $item) {
                $itemNormalizado = self::normalizarSolicitudComparar($item, $campo);

                if (!self::valorSolicitudVacio($itemNormalizado)) {
                    $lista[] = $itemNormalizado;
                }
            }

            usort($lista, function ($a, $b) {
                return strcmp(
                    json_encode($a, JSON_UNESCAPED_UNICODE),
                    json_encode($b, JSON_UNESCAPED_UNICODE)
                );
            });

            return $lista;
        }

        $salida = [];

        foreach ($valor as $k => $v) {
            if (in_array($k, $ignorar, true)) {
                continue;
            }

            if ($k === 'nombre_completo') $k = 'nombre';
            if ($k === 'dni_familiar') $k = 'dni';
            if ($k === 'modalidad_contrato') $k = 'mod_contrato';

            $normalizado = self::normalizarSolicitudComparar($v, (string)$k);

            if (!self::valorSolicitudVacio($normalizado)) {
                $salida[$k] = $normalizado;
            }
        }

        ksort($salida);

        return $salida;
    }

    private static function valorAnteriorSolicitud(array $antes, string $campo)
    {
        if ($campo === 'hijos') {
            $familia = $antes['familia'] ?? [];

            if (!is_array($familia)) {
                return [];
            }

            return array_values(array_filter($familia, function ($item) {
                $parentesco = strtoupper(trim($item['parentesco'] ?? ''));
                return in_array($parentesco, ['HIJO', 'HIJA'], true);
            }));
        }

        return $antes[$campo] ?? null;
    }

    private static function solicitudTieneCambiosReales(array $antes, array $despues): bool
    {
        $ignorar = [
            'id',
            'colab_id',
            'usuario_id',
            'edad',
            'n_hijos',
            'familia',
            'archivo_sustento',
            'nombre_archivo_original',
            'mime_archivo',
            'tamano_archivo',
            'created_at',
            'updated_at',
        ];

        foreach ($despues as $campo => $valorNuevo) {
            if (in_array($campo, $ignorar, true)) {
                continue;
            }

            $valorAnterior = self::valorAnteriorSolicitud($antes, (string)$campo);

            $anteriorNormalizado = self::normalizarSolicitudComparar($valorAnterior, (string)$campo);
            $nuevoNormalizado = self::normalizarSolicitudComparar($valorNuevo, (string)$campo);

            if (
                json_encode($anteriorNormalizado, JSON_UNESCAPED_UNICODE) !==
                json_encode($nuevoNormalizado, JSON_UNESCAPED_UNICODE)
            ) {
                return true;
            }
        }

        return false;
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

            // Total colaboradores
            $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total
            FROM colab_maestro
        ");
            $stmt->execute();
            $respuesta['total_colaboradores'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

            // Validaciones pendientes reales del flujo actual
            $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total
            FROM solicitudes_cambio
            WHERE estado = 'PENDIENTE'
        ");
            $stmt->execute();
            $respuesta['validaciones_pendientes'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

            // Contratos por vencer próximos 30 días
            $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total
            FROM colab_contratos c
            WHERE c.fecha_cese IS NOT NULL
              AND c.fecha_cese BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ");
            $stmt->execute();
            $respuesta['contratos_por_vencer'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

            // Modalidad tomando último registro laboral por colaborador
            $stmt = $pdo->prepare("
            SELECT 
                COALESCE(NULLIF(TRIM(l.modalidad_contrato), ''), 'SIN MODALIDAD') AS modalidad,
                COUNT(*) AS total
            FROM colab_laboral l
            INNER JOIN (
                SELECT colab_id, MAX(id) AS ultimo_id
                FROM colab_laboral
                GROUP BY colab_id
            ) ult ON ult.ultimo_id = l.id
            GROUP BY modalidad
            ORDER BY total DESC, modalidad ASC
        ");
            $stmt->execute();
            $respuesta['modalidades'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Próximos cumpleaños
            $stmt = $pdo->prepare("
            SELECT
                id,
                nombres_apellidos AS nombre,
                fecha_nacimiento,
                TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) + 1 AS edad_proxima,
                DATE_FORMAT(fecha_nacimiento, '%d') AS dia,
                DATE_FORMAT(fecha_nacimiento, '%m') AS mes_num,
                CASE MONTH(fecha_nacimiento)
                    WHEN 1 THEN 'ENE'
                    WHEN 2 THEN 'FEB'
                    WHEN 3 THEN 'MAR'
                    WHEN 4 THEN 'ABR'
                    WHEN 5 THEN 'MAY'
                    WHEN 6 THEN 'JUN'
                    WHEN 7 THEN 'JUL'
                    WHEN 8 THEN 'AGO'
                    WHEN 9 THEN 'SEP'
                    WHEN 10 THEN 'OCT'
                    WHEN 11 THEN 'NOV'
                    WHEN 12 THEN 'DIC'
                END AS mes_texto
            FROM colab_maestro
            WHERE fecha_nacimiento IS NOT NULL
            ORDER BY
                CASE
                    WHEN DATE_FORMAT(fecha_nacimiento, '%m-%d') >= DATE_FORMAT(CURDATE(), '%m-%d')
                    THEN DATE_FORMAT(fecha_nacimiento, '%m-%d')
                    ELSE CONCAT(YEAR(CURDATE()) + 1, '-', DATE_FORMAT(fecha_nacimiento, '%m-%d'))
                END ASC
            LIMIT 5
        ");
            $stmt->execute();
            $respuesta['cumpleanos'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return $respuesta;
        } catch (Throwable $e) {
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
    public static function mdlObtenerPerfilCompleto($colabId)
    {
        $pdo = Conexion::conectar();

        $colabId = (int)$colabId;

        if ($colabId <= 0) {
            return false;
        }

        $stmt = $pdo->prepare("
        SELECT 
            m.id,
            m.usuario_id,
            m.nombres_apellidos,
            m.dni,
            m.ruc,
            m.licencia_conducir,
            m.fecha_nacimiento,
            m.lugar_nacimiento,
            m.edad,
            m.sexo,
            m.estado_civil,
            m.grupo_sanguineo,
            m.talla,
            m.grado_militar,
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
        LEFT JOIN (
            SELECT l1.*
            FROM colab_laboral l1   
            INNER JOIN (
                SELECT colab_id, MAX(id) AS max_id
                FROM colab_laboral
                GROUP BY colab_id
            ) ult ON ult.max_id = l1.id
        ) l ON l.colab_id = m.id
        WHERE m.id = :id
        LIMIT 1
    ");

        $stmt->bindValue(':id', $colabId, PDO::PARAM_INT);
        $stmt->execute();

        $perfil = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$perfil) {
            return false;
        }

        $colabId = (int)$perfil['id'];

        // CONTRATOS Y ADENDAS
        $stmt2 = $pdo->prepare("
                SELECT 
                    id,
                    colab_id,
                    contrato_padre_id,
                    COALESCE(NULLIF(TRIM(tipo_registro), ''), 'CONTRATO') AS tipo_registro,
                    numero_documento,
                    fecha_documento,
                    fecha_ingreso,
                    fecha_cese,
                    modalidad,
                    motivo_adenda,
                    observacion
                FROM colab_contratos
                WHERE colab_id = :id
                ORDER BY 
                    COALESCE(contrato_padre_id, id) ASC,
                    CASE 
                        WHEN UPPER(COALESCE(NULLIF(TRIM(tipo_registro), ''), 'CONTRATO')) = 'CONTRATO' THEN 0
                        ELSE 1
                    END ASC,
                    fecha_ingreso ASC,
                    id ASC
            ");
        $stmt2->execute([':id' => $colabId]);
        $perfil['contratos'] = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];
        // FORMACIÓN
        $stmt3 = $pdo->prepare("
        SELECT *
        FROM colab_formacion
        WHERE colab_id = :id
        ORDER BY id ASC
    ");
        $stmt3->execute([':id' => $colabId]);
        $perfil['formacion'] = $stmt3->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // EXPERIENCIA
        $stmt4 = $pdo->prepare("
        SELECT *
        FROM colab_experiencia
        WHERE colab_id = :id
        ORDER BY fecha_inicio DESC, id DESC
    ");
        $stmt4->execute([':id' => $colabId]);
        $perfil['experiencia'] = $stmt4->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // FAMILIA
        $stmt5 = $pdo->prepare("
        SELECT *
        FROM colab_familia
        WHERE colab_id = :id
        ORDER BY 
            CASE parentesco
                WHEN 'CONYUGE' THEN 1
                WHEN 'HIJO' THEN 2
                WHEN 'HIJA' THEN 3
                ELSE 4
            END,
            id ASC
    ");
        $stmt5->execute([':id' => $colabId]);
        $familia = $stmt5->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $perfil['familia'] = $familia;

        $conyuge = array_values(array_filter($familia, function ($f) {
            return ($f['parentesco'] ?? '') === 'CONYUGE';
        }));

        $conyuge = $conyuge[0] ?? [];

        $perfil['conyuge'] = $conyuge['nombre_completo'] ?? null;
        $perfil['onomastico_conyuge'] = $conyuge['fecha_nacimiento'] ?? null;
        $perfil['dni_conyuge'] = $conyuge['dni_familiar'] ?? null;

        $stmt6 = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM colab_familia
        WHERE colab_id = :id
          AND parentesco IN ('HIJO','HIJA')
    ");
        $stmt6->execute([':id' => $colabId]);
        $perfil['n_hijos'] = (int)($stmt6->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // PENSIÓN
        $stmt7 = $pdo->prepare("
        SELECT *
        FROM colab_pension
        WHERE colab_id = :id
        LIMIT 1
    ");
        $stmt7->execute([':id' => $colabId]);
        $perfil['pension'] = $stmt7->fetch(PDO::FETCH_ASSOC) ?: [];

        // BANCARIO
        $stmt8 = $pdo->prepare("
        SELECT *
        FROM colab_bancario
        WHERE colab_id = :id
        LIMIT 1
    ");
        $stmt8->execute([':id' => $colabId]);
        $perfil['bancario'] = $stmt8->fetch(PDO::FETCH_ASSOC) ?: [];

        // IDIOMAS
        $stmt9 = $pdo->prepare("
        SELECT *
        FROM colab_idioma
        WHERE colab_id = :id
        ORDER BY id ASC
    ");
        $stmt9->execute([':id' => $colabId]);
        $perfil['idiomas'] = $stmt9->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $perfil;
    }

    public static function mdlObtenerPerfilPorUsuario(int $usuarioId)
    {
        $pdo = Conexion::conectar();

        $usuarioId = (int)$usuarioId;

        if ($usuarioId <= 0) {
            return false;
        }

        $stmt = $pdo->prepare("
        SELECT id
        FROM colab_maestro
        WHERE usuario_id = :usuario_id
        LIMIT 1
    ");

        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return false;
        }

        return self::mdlObtenerPerfilCompleto((int)$row['id']);
    }

    public static function mdlActualizarPerfil($datos)
    {
        $pdo = Conexion::conectar();

        $rolSesion = strtolower(trim($_SESSION['user_role'] ?? ''));
        $colabId = (int)($datos['id'] ?? 0);

        if ($colabId <= 0) {
            return [
                'success' => false,
                'mensaje' => 'ID de colaborador inválido'
            ];
        }

        $perfilExiste = self::mdlObtenerPerfilCompleto($colabId);

        if (!$perfilExiste) {
            return [
                'success' => false,
                'mensaje' => 'No se encontró el perfil del colaborador'
            ];
        }

        /*
     * Seguridad backend:
     * Si no es RRHH/Admin/Superadmin, no puede modificar datos sensibles.
     */
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

            // ─────────────────────────────────────────────
            // 1. TABLA MAESTRO
            // ─────────────────────────────────────────────
            $stmtActualPersonal = $pdo->prepare("
    SELECT 
        ruc,
        licencia_conducir,
        sexo,
        grado_militar
    FROM colab_maestro
    WHERE id = :id
    LIMIT 1
");
            $stmtActualPersonal->execute([':id' => $colabId]);
            $personalActual = $stmtActualPersonal->fetch(PDO::FETCH_ASSOC) ?: [];

            $sexoRecibido = array_key_exists('sexo', $datos)
                ? strtoupper(trim((string)$datos['sexo']))
                : null;

            $sexoFinal = array_key_exists('sexo', $datos)
                ? (in_array($sexoRecibido, ['M', 'F'], true) ? $sexoRecibido : null)
                : ($personalActual['sexo'] ?? null);

            $stmt = $pdo->prepare("
            UPDATE colab_maestro SET
                ruc                   = :ruc,
                licencia_conducir     = :licencia_conducir,
                fecha_nacimiento      = :fecha_nacimiento,
                lugar_nacimiento      = :lugar_nacimiento,
                sexo                  = :sexo,
                estado_civil          = :estado_civil,
                grupo_sanguineo       = :grupo_sanguineo,
                talla                 = :talla,
                grado_militar         = :grado_militar,
                celular               = :celular,
                correo_personal       = :correo_personal,
                direccion_residencia  = :direccion_residencia,
                distrito              = :distrito,
                actualizado_por       = :actualizado_por
            WHERE id = :id
        ");

            $stmt->execute([
                ':ruc'                  => array_key_exists('ruc', $datos)
                    ? self::nullify($datos['ruc'] ?? null)
                    : ($personalActual['ruc'] ?? null),

                ':licencia_conducir'    => array_key_exists('licencia_conducir', $datos)
                    ? self::nullify($datos['licencia_conducir'] ?? null)
                    : ($personalActual['licencia_conducir'] ?? null),

                ':fecha_nacimiento'     => !empty($datos['fecha_nacimiento']) ? $datos['fecha_nacimiento'] : null,
                ':lugar_nacimiento'     => self::nullify($datos['lugar_nacimiento'] ?? null),
                ':sexo'                 => $sexoFinal,
                ':estado_civil'         => self::nullify($datos['estado_civil'] ?? null),
                ':grupo_sanguineo'      => self::nullify($datos['grupo_sanguineo'] ?? null),
                ':talla'                => self::nullify($datos['talla'] ?? null),

                ':grado_militar'        => array_key_exists('grado_militar', $datos)
                    ? self::nullify($datos['grado_militar'] ?? null)
                    : ($personalActual['grado_militar'] ?? null),

                ':celular'              => self::nullify($datos['celular'] ?? null),
                ':correo_personal'      => self::nullify($datos['correo_personal'] ?? null),
                ':direccion_residencia' => self::nullify($datos['direccion_residencia'] ?? null),
                ':distrito'             => self::nullify($datos['distrito'] ?? null),
                ':actualizado_por'      => (int)($_SESSION['user_id'] ?? 0),
                ':id'                   => $colabId,
            ]);

            // Campos sensibles de maestro
            if (in_array($rolSesion, ['rrhh', 'admin', 'superadmin'], true)) {

                $stmtActualMaestro = $pdo->prepare("
                SELECT nombres_apellidos, dni
                FROM colab_maestro
                WHERE id = :id
                LIMIT 1
            ");
                $stmtActualMaestro->execute([':id' => $colabId]);
                $maestroActual = $stmtActualMaestro->fetch(PDO::FETCH_ASSOC) ?: [];

                $nombresFinal = array_key_exists('nombres_apellidos', $datos) && trim((string)$datos['nombres_apellidos']) !== ''
                    ? trim((string)$datos['nombres_apellidos'])
                    : ($maestroActual['nombres_apellidos'] ?? null);

                $dniFinal = array_key_exists('dni', $datos) && trim((string)$datos['dni']) !== ''
                    ? trim((string)$datos['dni'])
                    : ($maestroActual['dni'] ?? null);

                $stmtExtraMaestro = $pdo->prepare("
                UPDATE colab_maestro SET
                    nombres_apellidos = :nombres_apellidos,
                    dni               = :dni
                WHERE id = :id
            ");

                $stmtExtraMaestro->execute([
                    ':nombres_apellidos' => $nombresFinal,
                    ':dni'               => $dniFinal,
                    ':id'                => $colabId,
                ]);
            }

            // ─────────────────────────────────────────────
            // 2. TABLA LABORAL
            // ─────────────────────────────────────────────
            if (in_array($rolSesion, ['rrhh', 'admin', 'superadmin'], true)) {

                $stmtActualLaboral = $pdo->prepare("
                SELECT
                    id,
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
                ORDER BY id DESC
                LIMIT 1
            ");
                $stmtActualLaboral->execute([':id' => $colabId]);
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

                if (!empty($laboralActual['id'])) {
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
                        nsa_cip              = :nsa_cip,
                        actualizado_por      = :actualizado_por
                    WHERE id = :laboral_id
                    AND colab_id = :colab_id
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
                        ':laboral_id'           => (int)$laboralActual['id'],
                        ':colab_id'             => $colabId,
                        ':actualizado_por'      => (int)($_SESSION['user_id'] ?? 0),
                    ]);
                } else {
                    $stmtLaboral = $pdo->prepare("
                    INSERT INTO colab_laboral (
                        colab_id,
                        correo_institucional,
                        situacion,
                        sueldo,
                        modalidad_contrato,
                        puesto_cas,
                        tipo_puesto,
                        area,
                        procedencia,
                        nsa_cip
                    ) VALUES (
                        :colab_id,
                        :correo_institucional,
                        :situacion,
                        :sueldo,
                        :mod_contrato,
                        :puesto_cas,
                        :tipo_puesto,
                        :area,
                        :procedencia,
                        :nsa_cip
                    )
                ");

                    $stmtLaboral->execute([
                        ':colab_id'             => $colabId,
                        ':correo_institucional' => $correoInstitucional,
                        ':situacion'            => $situacion ?: 'ACTIVO',
                        ':sueldo'               => $sueldo,
                        ':mod_contrato'         => $modContrato,
                        ':puesto_cas'           => $puestoCas,
                        ':tipo_puesto'          => $tipoPuesto,
                        ':area'                 => $area,
                        ':procedencia'          => $procedencia,
                        ':nsa_cip'              => $nsaCip,
                    ]);
                }
            }

            // ─────────────────────────────────────────────
            // 3. CÓNYUGE
            // ─────────────────────────────────────────────
            $nombreConyuge = trim((string)($datos['conyuge'] ?? ''));
            $fechaConyuge  = !empty($datos['onomastico_conyuge']) ? $datos['onomastico_conyuge'] : null;
            $dniConyuge    = trim((string)($datos['dni_conyuge'] ?? ''));

            $chk = $pdo->prepare("
            SELECT id
            FROM colab_familia
            WHERE colab_id = :id
              AND parentesco = 'CONYUGE'
            LIMIT 1
        ");
            $chk->execute([':id' => $colabId]);
            $conyugeExistente = (int)($chk->fetchColumn() ?: 0);

            if ($conyugeExistente > 0) {
                if ($nombreConyuge === '' && $fechaConyuge === null && $dniConyuge === '') {
                    $pdo->prepare("
                    DELETE FROM colab_familia
                    WHERE id = :fid
                      AND colab_id = :colab_id
                ")->execute([
                        ':fid'      => $conyugeExistente,
                        ':colab_id' => $colabId,
                    ]);
                } else {
                    $pdo->prepare("
                    UPDATE colab_familia SET
                        nombre_completo  = :nombre,
                        fecha_nacimiento = :fecha,
                        dni_familiar     = :dni
                    WHERE id = :fid
                      AND colab_id = :colab_id
                ")->execute([
                        ':nombre'   => $nombreConyuge !== '' ? $nombreConyuge : null,
                        ':fecha'    => $fechaConyuge,
                        ':dni'      => $dniConyuge !== '' ? $dniConyuge : null,
                        ':fid'      => $conyugeExistente,
                        ':colab_id' => $colabId,
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
                ) VALUES (
                    :colab_id,
                    'CONYUGE',
                    :nombre,
                    :fecha,
                    :dni
                )
            ")->execute([
                    ':colab_id' => $colabId,
                    ':nombre'   => $nombreConyuge !== '' ? $nombreConyuge : null,
                    ':fecha'    => $fechaConyuge,
                    ':dni'      => $dniConyuge !== '' ? $dniConyuge : null,
                ]);
            }

            // ─────────────────────────────────────────────
            // 4. HIJOS
            // Solo sincroniza si llegaron registros.
            // Si viene [], no borra nada accidentalmente.
            // ─────────────────────────────────────────────
            $hijos = $datos['hijos'] ?? null;

            if (is_array($hijos) && count($hijos) > 0) {

                $stmtIds = $pdo->prepare("
                SELECT id
                FROM colab_familia
                WHERE colab_id = :id
                  AND parentesco IN ('HIJO','HIJA')
            ");
                $stmtIds->execute([':id' => $colabId]);

                $idsEnBD = array_map('intval', $stmtIds->fetchAll(PDO::FETCH_COLUMN));
                $idsRecibidos = [];

                foreach ($hijos as $hijo) {
                    $nombre = trim((string)($hijo['nombre'] ?? ''));

                    if ($nombre === '') {
                        continue;
                    }

                    $hijoId = (int)($hijo['id'] ?? 0);
                    $parentesco = in_array(($hijo['parentesco'] ?? ''), ['HIJO', 'HIJA'], true)
                        ? $hijo['parentesco']
                        : 'HIJO';

                    $fechaNac = !empty($hijo['fecha_nacimiento']) ? $hijo['fecha_nacimiento'] : null;
                    $dni = self::nullify($hijo['dni'] ?? null);

                    if ($hijoId > 0 && in_array($hijoId, $idsEnBD, true)) {
                        $pdo->prepare("
                        UPDATE colab_familia SET
                            nombre_completo  = :nombre,
                            parentesco       = :parentesco,
                            fecha_nacimiento = :fecha,
                            dni_familiar     = :dni
                        WHERE id = :fid
                          AND colab_id = :colab_id
                    ")->execute([
                            ':nombre'     => $nombre,
                            ':parentesco' => $parentesco,
                            ':fecha'      => $fechaNac,
                            ':dni'        => $dni,
                            ':fid'        => $hijoId,
                            ':colab_id'   => $colabId,
                        ]);

                        $idsRecibidos[] = $hijoId;
                    } else {
                        $ins = $pdo->prepare("
                        INSERT INTO colab_familia (
                            colab_id,
                            parentesco,
                            nombre_completo,
                            fecha_nacimiento,
                            dni_familiar
                        ) VALUES (
                            :colab_id,
                            :parentesco,
                            :nombre,
                            :fecha,
                            :dni
                        )
                    ");

                        $ins->execute([
                            ':colab_id'   => $colabId,
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
            }

            // ─────────────────────────────────────────────
            // 5. CONTRATOS
            // ─────────────────────────────────────────────
            $contratos = $datos['contratos'] ?? null;

            if (is_array($contratos) && count($contratos) > 0) {

                $stmtContratoIds = $pdo->prepare("
                SELECT id
                FROM colab_contratos
                WHERE colab_id = :id
            ");
                $stmtContratoIds->execute([':id' => $colabId]);

                $idsContratosEnBD = array_map('intval', $stmtContratoIds->fetchAll(PDO::FETCH_COLUMN));
                $idsContratosRecibidos = [];

                foreach ($contratos as $contrato) {
                    $contratoId   = (int)($contrato['id'] ?? 0);
                    $fechaIngreso = !empty($contrato['fecha_ingreso']) ? $contrato['fecha_ingreso'] : null;
                    $fechaCese    = !empty($contrato['fecha_cese']) ? $contrato['fecha_cese'] : null;
                    $modalidad    = trim((string)($contrato['modalidad'] ?? ''));

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
                            ':colab_id'      => $colabId,
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
                            ':colab_id'      => $colabId,
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
            }

            // ─────────────────────────────────────────────
            // 6. FORMACIÓN
            // ─────────────────────────────────────────────
            $formacion = $datos['formacion'] ?? null;

            if (is_array($formacion) && count($formacion) > 0) {

                $stmtFormIds = $pdo->prepare("
                SELECT id
                FROM colab_formacion
                WHERE colab_id = :id
            ");
                $stmtFormIds->execute([':id' => $colabId]);

                $idsFormEnBD = array_map('intval', $stmtFormIds->fetchAll(PDO::FETCH_COLUMN));
                $idsFormRecibidos = [];

                foreach ($formacion as $form) {
                    $formId = (int)($form['id'] ?? 0);

                    $tipoGrado = trim((string)($form['tipo_grado'] ?? ''));
                    $carrera = trim((string)($form['descripcion_carrera'] ?? ''));
                    $institucion = trim((string)($form['institucion'] ?? ''));

                    $anioRealizacion = isset($form['anio_realizacion']) && $form['anio_realizacion'] !== ''
                        ? (int)$form['anio_realizacion']
                        : null;

                    $horasLectivas = isset($form['horas_lectivas']) && $form['horas_lectivas'] !== ''
                        ? (int)$form['horas_lectivas']
                        : null;

                    $especialidad = trim((string)($form['especialidad'] ?? ''));
                    $gradoAlcanzado = trim((string)($form['grado_alcanzado'] ?? ''));

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
                            ':colab_id'            => $colabId,
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
                            ':colab_id'            => $colabId,
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
            }

            // ─────────────────────────────────────────────
            // 7. EXPERIENCIA
            // ─────────────────────────────────────────────
            $experiencia = $datos['experiencia'] ?? null;

            if (is_array($experiencia) && count($experiencia) > 0) {

                $stmtExpIds = $pdo->prepare("
                SELECT id
                FROM colab_experiencia
                WHERE colab_id = :id
            ");
                $stmtExpIds->execute([':id' => $colabId]);

                $idsExpEnBD = array_map('intval', $stmtExpIds->fetchAll(PDO::FETCH_COLUMN));
                $idsExpRecibidos = [];

                foreach ($experiencia as $exp) {
                    $expId = (int)($exp['id'] ?? 0);

                    $empresa = trim((string)($exp['empresa_entidad'] ?? ''));
                    $cargo = trim((string)($exp['cargo_puesto'] ?? ''));
                    $area = trim((string)($exp['unidad_organica_area'] ?? ''));
                    $funciones = trim((string)($exp['funciones_principales'] ?? ''));

                    $fechaInicio = !empty($exp['fecha_inicio']) ? $exp['fecha_inicio'] : null;
                    $fechaFin = !empty($exp['fecha_fin']) ? $exp['fecha_fin'] : null;
                    $actualmenteTrabaja = !empty($exp['actualmente_trabaja']) ? 1 : 0;

                    if ($actualmenteTrabaja === 1) {
                        $fechaFin = null;
                    }

                    if (
                        $empresa === '' &&
                        $cargo === '' &&
                        $area === '' &&
                        $funciones === '' &&
                        $fechaInicio === null &&
                        $fechaFin === null &&
                        $actualmenteTrabaja === 0
                    ) {
                        continue;
                    }

                    if ($expId > 0 && in_array($expId, $idsExpEnBD, true)) {
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
                        WHERE id = :eid
                          AND colab_id = :colab_id
                    ")->execute([
                            ':empresa'             => $empresa !== '' ? $empresa : null,
                            ':area'                => $area !== '' ? $area : null,
                            ':cargo'               => $cargo !== '' ? $cargo : null,
                            ':fecha_inicio'        => $fechaInicio,
                            ':fecha_fin'           => $fechaFin,
                            ':actualmente_trabaja' => $actualmenteTrabaja,
                            ':funciones'           => $funciones !== '' ? $funciones : null,
                            ':eid'                 => $expId,
                            ':colab_id'            => $colabId,
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
                            ':colab_id'            => $colabId,
                            ':empresa'             => $empresa !== '' ? $empresa : null,
                            ':area'                => $area !== '' ? $area : null,
                            ':cargo'               => $cargo !== '' ? $cargo : null,
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
            }

            // ─────────────────────────────────────────────
            // 8. PENSIÓN
            // ─────────────────────────────────────────────
            $pension = $datos['pension'] ?? null;

            if (is_array($pension)) {

                $tieneDatoPension =
                    !empty($pension['sistema_pension']) ||
                    !empty($pension['afp']) ||
                    !empty($pension['cuspp']) ||
                    !empty($pension['tipo_comision']) ||
                    !empty($pension['fecha_inscripcion']) ||
                    !empty($pension['sin_afp_afiliarme']);

                if ($tieneDatoPension) {
                    $stmtPen = $pdo->prepare("
                    SELECT id
                    FROM colab_pension
                    WHERE colab_id = :id
                    LIMIT 1
                ");
                    $stmtPen->execute([':id' => $colabId]);

                    $pensionId = (int)($stmtPen->fetchColumn() ?: 0);

                    $payloadPension = [
                        ':colab_id'          => $colabId,
                        ':sistema_pension'   => self::nullify($pension['sistema_pension'] ?? null),
                        ':afp'               => self::nullify($pension['afp'] ?? null),
                        ':cuspp'             => self::nullify($pension['cuspp'] ?? null),
                        ':tipo_comision'     => self::nullify($pension['tipo_comision'] ?? null),
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
            }

            // ─────────────────────────────────────────────
            // 9. BANCARIO
            // ─────────────────────────────────────────────
            $bancario = $datos['bancario'] ?? null;

            if (is_array($bancario)) {

                $tieneDatoBancario =
                    !empty($bancario['banco_haberes']) ||
                    !empty($bancario['numero_cuenta']) ||
                    !empty($bancario['numero_cuenta_cci']);

                if ($tieneDatoBancario) {
                    $stmtBan = $pdo->prepare("
                    SELECT id
                    FROM colab_bancario
                    WHERE colab_id = :id
                    LIMIT 1
                ");
                    $stmtBan->execute([':id' => $colabId]);

                    $bancarioId = (int)($stmtBan->fetchColumn() ?: 0);

                    $payloadBancario = [
                        ':colab_id'          => $colabId,
                        ':banco_haberes'     => self::nullify($bancario['banco_haberes'] ?? null),
                        ':numero_cuenta'     => self::nullify($bancario['numero_cuenta'] ?? null),
                        ':numero_cuenta_cci' => self::nullify($bancario['numero_cuenta_cci'] ?? null),
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
            }

            // ─────────────────────────────────────────────
            // 10. IDIOMAS
            // Solo sincroniza si llegaron registros.
            // Si viene [], no elimina idiomas por accidente.
            // ─────────────────────────────────────────────
            $idiomas = $datos['idiomas'] ?? null;

            if (is_array($idiomas) && count($idiomas) > 0) {

                $pdo->prepare("
                DELETE FROM colab_idioma
                WHERE colab_id = :id
            ")->execute([
                    ':id' => $colabId,
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
                        ':colab_id' => $colabId,
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
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return [
                'success' => false,
                'mensaje' => 'Error al actualizar el perfil: ' . $e->getMessage()
            ];
        }
    }

    private static function mdlGuardarArchivoSustentoTemporal(?array $archivo): ?array
    {
        if (empty($archivo) || empty($archivo['name'])) {
            return null;
        }

        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new Exception('No se pudo subir el archivo de sustento');
        }

        $maxBytes = 5 * 1024 * 1024; // 5 MB
        if (($archivo['size'] ?? 0) > $maxBytes) {
            throw new Exception('El archivo supera el tamaño máximo permitido de 5 MB');
        }

        $permitidos = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);

        if (!isset($permitidos[$mime])) {
            throw new Exception('Tipo de archivo no permitido');
        }

        $ext = $permitidos[$mime];

        $baseDir = __DIR__ . '/../Public/uploads/sustentos_cambios/';
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0775, true);
        }

        $nombreSeguro = 'sustento_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $rutaFisica   = $baseDir . $nombreSeguro;
        $rutaGuardar  = 'uploads/sustentos_cambios/' . $nombreSeguro;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaFisica)) {
            throw new Exception('No se pudo mover el archivo de sustento');
        }

        return [
            'ruta' => $rutaGuardar,
            'nombre_original' => $archivo['name'],
            'mime' => $mime,
            'tamano' => (int)$archivo['size'],
        ];
    }

    public static function mdlCrearSolicitudCambio(int $colabId, int $usuarioSolicitaId, array $datosNuevos, ?array $archivo = null): array
    {
        $db = Conexion::conectar();

        try {
            $db->beginTransaction();

            // Validar si ya tiene una pendiente
            $sqlExiste = "SELECT id 
                      FROM solicitudes_cambio 
                      WHERE colab_id = :colab_id 
                        AND tipo_seccion = 'perfil_completo'
                        AND estado = 'PENDIENTE'
                      LIMIT 1";
            $stmtExiste = $db->prepare($sqlExiste);
            $stmtExiste->execute(['colab_id' => $colabId]);

            if ($stmtExiste->fetch(PDO::FETCH_ASSOC)) {
                $db->rollBack();
                return [
                    'success' => false,
                    'mensaje' => 'Ya tienes una solicitud pendiente de validación'
                ];
            }

            $perfilActual = self::mdlObtenerPerfilCompleto($colabId);
            if (!$perfilActual) {
                $db->rollBack();
                return ['success' => false, 'mensaje' => 'No se encontró el perfil del colaborador'];
            }

            if (!self::solicitudTieneCambiosReales($perfilActual, $datosNuevos)) {
                $db->rollBack();
                return [
                    'success' => false,
                    'mensaje' => 'No se detectaron cambios reales para enviar a validación'
                ];
            }

            $archivoGuardado = self::mdlGuardarArchivoSustentoTemporal($archivo);

            $sql = "INSERT INTO solicitudes_cambio (
                    colab_id,
                    usuario_solicita_id,
                    tipo_seccion,
                    datos_json,
                    datos_anteriores_json,
                    archivo_sustento,
                    nombre_archivo_original,
                    mime_archivo,
                    tamano_archivo,
                    estado,
                    created_at
                ) VALUES (
                    :colab_id,
                    :usuario_solicita_id,
                    :tipo_seccion,
                    :datos_json,
                    :datos_anteriores_json,
                    :archivo_sustento,
                    :nombre_archivo_original,
                    :mime_archivo,
                    :tamano_archivo,
                    'PENDIENTE',
                    NOW()
                )";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                'colab_id' => $colabId,
                'usuario_solicita_id' => $usuarioSolicitaId,
                'tipo_seccion' => 'perfil_completo',
                'datos_json' => json_encode($datosNuevos, JSON_UNESCAPED_UNICODE),
                'datos_anteriores_json' => json_encode($perfilActual, JSON_UNESCAPED_UNICODE),
                'archivo_sustento' => $archivoGuardado['ruta'] ?? null,
                'nombre_archivo_original' => $archivoGuardado['nombre_original'] ?? null,
                'mime_archivo' => $archivoGuardado['mime'] ?? null,
                'tamano_archivo' => $archivoGuardado['tamano'] ?? null,
            ]);

            $db->commit();

            return [
                'success' => true,
                'modo' => 'solicitud',
                'mensaje' => 'Tu solicitud fue enviada y quedó pendiente de validación'
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return [
                'success' => false,
                'mensaje' => 'No se pudo registrar la solicitud: ' . $e->getMessage()
            ];
        }
    }

    public static function mdlListarSolicitudesCambio(?string $estado = null): array
    {
        $db = Conexion::conectar();

        $sql = "SELECT 
                s.*,
                cm.nombres_apellidos,
                cm.dni
            FROM solicitudes_cambio s
            INNER JOIN colab_maestro cm ON cm.id = s.colab_id
            WHERE 1=1";

        $params = [];

        if (!empty($estado)) {
            $sql .= " AND s.estado = :estado";
            $params['estado'] = strtoupper($estado);
        }

        $sql .= " ORDER BY 
                CASE s.estado
                    WHEN 'PENDIENTE' THEN 1
                    WHEN 'RECHAZADO' THEN 2
                    WHEN 'APROBADO' THEN 3
                    ELSE 4
                END,
                s.created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function mdlObtenerSolicitudCambio(int $id): ?array
    {
        $db = Conexion::conectar();

        $sql = "SELECT s.*, cm.nombres_apellidos, cm.dni
            FROM solicitudes_cambio s
            INNER JOIN colab_maestro cm ON cm.id = s.colab_id
            WHERE s.id = :id
            LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function mdlAprobarSolicitudCambio(int $solicitudId, int $validadorId): array
    {
        $db = Conexion::conectar();

        try {
            $db->beginTransaction();

            $solicitud = self::mdlObtenerSolicitudCambio($solicitudId);
            if (!$solicitud) {
                $db->rollBack();
                return ['success' => false, 'mensaje' => 'La solicitud no existe'];
            }

            if (($solicitud['estado'] ?? '') !== 'PENDIENTE') {
                $db->rollBack();
                return ['success' => false, 'mensaje' => 'La solicitud ya fue procesada'];
            }

            $datos = json_decode((string)$solicitud['datos_json'], true);
            if (!is_array($datos)) {
                $db->rollBack();
                return ['success' => false, 'mensaje' => 'La solicitud no contiene datos válidos'];
            }

            $resultado = self::mdlActualizarPerfil($datos);
            if (empty($resultado['success'])) {
                $db->rollBack();
                return [
                    'success' => false,
                    'mensaje' => $resultado['mensaje'] ?? 'No se pudo aplicar el cambio'
                ];
            }

            $sql = "UPDATE solicitudes_cambio
                SET estado = 'APROBADO',
                    validado_por = :validado_por,
                    revisado_por = :revisado_por,
                    fecha_validacion = NOW(),
                    observacion_rrhh = 'Solicitud aprobada'
                WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'validado_por' => $validadorId,
                'revisado_por' => $validadorId,
                'id' => $solicitudId
            ]);

            if (!empty($solicitud['archivo_sustento'])) {
                $rutaFisica = __DIR__ . '/../Public/' . ltrim($solicitud['archivo_sustento'], '/');
                if (is_file($rutaFisica)) {
                    @unlink($rutaFisica);
                }

                $stmtLimpia = $db->prepare("UPDATE solicitudes_cambio
                                        SET archivo_sustento = NULL,
                                            nombre_archivo_original = NULL,
                                            mime_archivo = NULL,
                                            tamano_archivo = NULL
                                        WHERE id = :id");
                $stmtLimpia->execute(['id' => $solicitudId]);
            }

            $db->commit();

            return [
                'success' => true,
                'mensaje' => 'Solicitud aprobada y cambios aplicados correctamente'
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            return [
                'success' => false,
                'mensaje' => 'Error al aprobar la solicitud: ' . $e->getMessage()
            ];
        }
    }

    public static function mdlRechazarSolicitudCambio(int $solicitudId, int $validadorId, string $motivo): array
    {
        $pdo = Conexion::conectar();

        try {
            $pdo->beginTransaction();

            $stmtSol = $pdo->prepare("
            SELECT archivo_sustento
            FROM solicitudes_cambio
            WHERE id = :id
              AND estado = 'PENDIENTE'
            LIMIT 1
        ");
            $stmtSol->execute([':id' => $solicitudId]);
            $solicitud = $stmtSol->fetch(PDO::FETCH_ASSOC);

            if (!$solicitud) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'mensaje' => 'La solicitud no existe o ya fue procesada'
                ];
            }

            $sql = "UPDATE solicitudes_cambio
                SET estado = 'RECHAZADO',
                    validado_por = :validado_por,
                    revisado_por = :revisado_por,
                    fecha_validacion = NOW(),
                    observacion_rrhh = :observacion_rrhh,
                    motivo_rechazo = :motivo_rechazo,
                    archivo_sustento = NULL,
                    nombre_archivo_original = NULL,
                    mime_archivo = NULL,
                    tamano_archivo = NULL
                WHERE id = :id
                  AND estado = 'PENDIENTE'";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':validado_por'     => $validadorId,
                ':revisado_por'     => $validadorId,
                ':observacion_rrhh' => $motivo,
                ':motivo_rechazo'   => $motivo,
                ':id'               => $solicitudId
            ]);

            if (!empty($solicitud['archivo_sustento'])) {
                $rutaFisica = __DIR__ . '/../Public/' . ltrim($solicitud['archivo_sustento'], '/');

                if (is_file($rutaFisica)) {
                    @unlink($rutaFisica);
                }
            }

            $pdo->commit();

            return [
                'success' => true,
                'mensaje' => 'Solicitud rechazada correctamente'
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return [
                'success' => false,
                'mensaje' => 'Error al rechazar la solicitud: ' . $e->getMessage()
            ];
        }
    }

    public static function mdlResumenSolicitudesPorColaborador(int $colabId): array
    {
        $db = Conexion::conectar();

        $sql = "SELECT 
                SUM(CASE WHEN estado = 'PENDIENTE' THEN 1 ELSE 0 END) AS pendientes,
                SUM(CASE WHEN estado = 'APROBADO' THEN 1 ELSE 0 END) AS aprobadas,
                SUM(CASE WHEN estado = 'RECHAZADO' THEN 1 ELSE 0 END) AS rechazadas
            FROM solicitudes_cambio
            WHERE colab_id = :colab_id";

        $stmt = $db->prepare($sql);
        $stmt->execute(['colab_id' => $colabId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'pendientes' => (int)($row['pendientes'] ?? 0),
            'aprobadas' => (int)($row['aprobadas'] ?? 0),
            'rechazadas' => (int)($row['rechazadas'] ?? 0),
        ];
    }

    public static function mdlListarSolicitudesPorColaborador(int $colabId): array
    {
        $db = Conexion::conectar();

        $sql = "SELECT 
                id,
                colab_id,
                usuario_solicita_id,
                tipo_seccion,
                datos_json,
                datos_anteriores_json,
                estado,
                observacion_rrhh,
                motivo_rechazo,
                validado_por,
                revisado_por,
                fecha_validacion,
                created_at
            FROM solicitudes_cambio
            WHERE colab_id = :colab_id
            ORDER BY created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':colab_id' => $colabId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function mdlCrearColaborador(array $datos): array
    {
        $pdo = Conexion::conectar();

        try {
            $pdo->beginTransaction();

            $dni = trim($datos['dni'] ?? '');

            // VALIDACIÓN BÁSICA
            if ($dni === '' || strlen($dni) !== 8) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'mensaje' => 'El DNI es obligatorio y debe tener 8 dígitos'
                ];
            }

            // VALIDAR DUPLICADO COLABORADOR
            $stmtExiste = $pdo->prepare("
            SELECT id FROM colab_maestro WHERE dni = :dni LIMIT 1
        ");
            $stmtExiste->execute([':dni' => $dni]);

            if ($stmtExiste->fetch()) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'mensaje' => 'Ya existe un colaborador con ese DNI'
                ];
            }

            // VALIDAR USUARIO
            $stmtUsuarioExiste = $pdo->prepare("
            SELECT id FROM usuarios WHERE username = :username LIMIT 1
        ");
            $stmtUsuarioExiste->execute([':username' => $dni]);

            if ($stmtUsuarioExiste->fetch()) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'mensaje' => 'Ya existe un usuario con ese DNI'
                ];
            }

            // CREAR USUARIO
            $passwordTemporal = password_hash($dni, PASSWORD_DEFAULT);

            $stmtUsuario = $pdo->prepare("
                    INSERT INTO usuarios (
                        username,
                        password,
                        rol,
                        estado,
                        cambiar_clave
                    ) VALUES (
                        :username,
                        :password,
                        :rol,
                        :estado,
                        :cambiar_clave
                    )
                ");

            $stmtUsuario->execute([
                ':username'       => $dni,
                ':password'       => $passwordTemporal,
                ':rol'            => 'colaborador',
                ':estado'         => 1,
                ':cambiar_clave'  => 1
            ]);

            $usuarioId = (int)$pdo->lastInsertId();

            // INSERT MAESTRO
            $stmtMaestro = $pdo->prepare("
            INSERT INTO colab_maestro (
                usuario_id, dni, ruc, licencia_conducir,
                nombres_apellidos, fecha_nacimiento, lugar_nacimiento,
                sexo, estado_civil, grupo_sanguineo, talla,
                celular, correo_personal, direccion_residencia,
                distrito, grado_militar
            ) VALUES (
                :usuario_id, :dni, :ruc, :licencia,
                :nombre, :fecha, :lugar,
                :sexo, :estado, :grupo, :talla,
                :celular, :correo, :direccion,
                :distrito, :grado
            )
        ");

            $stmtMaestro->execute([
                ':usuario_id' => $usuarioId,
                ':dni'        => $dni,
                ':ruc'        => self::nullify($datos['ruc'] ?? null),
                ':licencia'   => self::nullify($datos['licencia_conducir'] ?? null),
                ':nombre'     => trim($datos['nombres_apellidos'] ?? ''),
                ':fecha'  => !empty($datos['fecha_nacimiento']) ? $datos['fecha_nacimiento'] : null,
                ':lugar'      => self::nullify($datos['lugar_nacimiento'] ?? null),
                ':sexo'       => self::nullify($datos['sexo'] ?? null),
                ':estado'     => self::nullify($datos['estado_civil'] ?? null),
                ':grupo'      => self::nullify($datos['grupo_sanguineo'] ?? null),
                ':talla'      => self::nullify($datos['talla'] ?? null),
                ':celular'    => self::nullify($datos['celular'] ?? null),
                ':correo'     => self::nullify($datos['correo_personal'] ?? null),
                ':direccion'  => self::nullify($datos['direccion_residencia'] ?? null),
                ':distrito'   => self::nullify($datos['distrito'] ?? null),
                ':grado'      => self::nullify($datos['grado_militar'] ?? null),
            ]);

            $colabId = (int)$pdo->lastInsertId();

            // LABORAL
            $stmtLaboral = $pdo->prepare("
            INSERT INTO colab_laboral (
                colab_id, correo_institucional, situacion,
                sueldo, modalidad_contrato, puesto_cas,
                tipo_puesto, area, procedencia,
                fecha_ingreso, fecha_cese
            ) VALUES (
                :id, :correo, :situacion,
                :sueldo, :modalidad, :puesto,
                :tipo, :area, :procedencia,
                :ingreso, :cese
            )
        ");

            $stmtLaboral->execute([
                ':id'          => $colabId,
                ':correo'      => self::nullify($datos['correo_institucional'] ?? null),
                ':situacion'   => $datos['situacion'] ?? 'ACTIVO',
                ':sueldo'      => ($datos['sueldo'] ?? '') !== '' ? $datos['sueldo'] : null,
                ':modalidad'   => self::nullify($datos['modalidad_contrato'] ?? null),
                ':puesto'      => self::nullify($datos['puesto_cas'] ?? null),
                ':tipo'        => self::nullify($datos['tipo_puesto'] ?? null),
                ':area'        => self::nullify($datos['area'] ?? null),
                ':procedencia' => self::nullify($datos['procedencia'] ?? null),
                ':ingreso' => !empty($datos['fecha_ingreso']) ? $datos['fecha_ingreso'] : null,
                ':cese'   => !empty($datos['fecha_cese']) ? $datos['fecha_cese'] : null,
            ]);

            // PENSIÓN (solo si hay datos)
            if (!empty($datos['sistema_pension']) || !empty($datos['afp'])) {
                $pdo->prepare("
                INSERT INTO colab_pension (
                    colab_id, sistema_pension, afp, cuspp,
                    tipo_comision, fecha_inscripcion, sin_afp_afiliarme
                ) VALUES (
                    :id, :sistema, :afp, :cuspp,
                    :tipo, :fecha, :flag
                )
            ")->execute([
                    ':id'     => $colabId,
                    ':sistema' => self::nullify($datos['sistema_pension'] ?? null),
                    ':afp'    => self::nullify($datos['afp'] ?? null),
                    ':cuspp'  => self::nullify($datos['cuspp'] ?? null),
                    ':tipo'   => self::nullify($datos['tipo_comision'] ?? null),
                    ':fecha'  => !empty($datos['fecha_inscripcion']) ? $datos['fecha_inscripcion'] : null,
                    ':flag'   => isset($datos['sin_afp_afiliarme']) ? 1 : 0,
                ]);
            }

            // BANCO (solo si hay datos)
            if (!empty($datos['numero_cuenta']) || !empty($datos['banco_haberes'])) {
                $pdo->prepare("
                INSERT INTO colab_bancario (
                    colab_id, banco_haberes, numero_cuenta, numero_cuenta_cci
                ) VALUES (
                    :id, :banco, :cuenta, :cci
                )
            ")->execute([
                    ':id'     => $colabId,
                    ':banco'  => self::nullify($datos['banco_haberes'] ?? null),
                    ':cuenta' => self::nullify($datos['numero_cuenta'] ?? null),
                    ':cci'    => self::nullify($datos['numero_cuenta_cci'] ?? null),
                ]);
            }

            $pdo->commit();

            return [
                'success' => true,
                'mensaje' => 'Colaborador registrado correctamente',
                'id' => $colabId
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return [
                'success' => false,
                'mensaje' => 'Error al registrar colaborador: ' . $e->getMessage()
            ];
        }
    }

    public static function mdlMostrarDirectorioExcel()
    {
        try {
            $sql = "
            SELECT
                m.id,
                CAST(m.dni AS CHAR) AS dni,
                m.nombres_apellidos,
                CAST(m.ruc AS CHAR) AS ruc,
                CAST(m.licencia_conducir AS CHAR) AS licencia_conducir,
                m.fecha_nacimiento,
                m.lugar_nacimiento,
                CASE
                    WHEN m.fecha_nacimiento IS NOT NULL
                    THEN TIMESTAMPDIFF(YEAR, m.fecha_nacimiento, CURDATE())
                    ELSE NULL
                END AS edad,
                m.sexo,
                m.estado_civil,
                m.grupo_sanguineo,
                m.talla,
                m.grado_militar,
                CAST(m.celular AS CHAR) AS celular,
                m.correo_personal,
                m.direccion_residencia,
                m.distrito,

                l.nsa_cip,
                l.correo_institucional,
                COALESCE(NULLIF(TRIM(l.situacion), ''), 'ACTIVO') AS situacion,
                l.sueldo,
                COALESCE(
                    NULLIF(TRIM(l.modalidad_contrato), ''),
                    NULLIF(TRIM(ct.modalidad), '')
                ) AS modalidad_contrato,
                l.puesto_cas,
                l.tipo_puesto,
                l.area,
                l.procedencia,

                ct.fecha_ingreso,
                ct.fecha_cese,

                fam.n_hijos,
                fam.conyuge,
                fam.onomastico_conyuge,

                form.profesion,
                form.institucion,
                form.grado,
                form.curso_especializacion,

                p.sistema_pension,
                p.afp,
                CAST(p.cuspp AS CHAR) AS cuspp,
                p.tipo_comision,
                p.fecha_inscripcion,
                COALESCE(p.sin_afp_afiliarme, 0) AS sin_afp_afiliarme,

                b.banco_haberes,
                CAST(b.numero_cuenta AS CHAR) AS numero_cuenta,
                CAST(b.numero_cuenta_cci AS CHAR) AS numero_cuenta_cci

            FROM colab_maestro m

            LEFT JOIN (
                SELECT l1.*
                FROM colab_laboral l1
                INNER JOIN (
                    SELECT colab_id, MAX(id) AS max_id
                    FROM colab_laboral
                    GROUP BY colab_id
                ) ul ON ul.max_id = l1.id
            ) l ON l.colab_id = m.id

            LEFT JOIN (
                SELECT c1.*
                FROM colab_contratos c1
                INNER JOIN (
                    SELECT colab_id, MAX(id) AS max_id
                    FROM colab_contratos
                    GROUP BY colab_id
                ) uc ON uc.max_id = c1.id
            ) ct ON ct.colab_id = m.id

            LEFT JOIN (
                SELECT
                    f.colab_id,
                    SUM(CASE WHEN f.parentesco IN ('HIJO', 'HIJA') THEN 1 ELSE 0 END) AS n_hijos,
                    MAX(CASE WHEN f.parentesco = 'CONYUGE' THEN f.nombre_completo END) AS conyuge,
                    MAX(CASE WHEN f.parentesco = 'CONYUGE' THEN f.fecha_nacimiento END) AS onomastico_conyuge
                FROM colab_familia f
                GROUP BY f.colab_id
            ) fam ON fam.colab_id = m.id

            LEFT JOIN (
                SELECT
                    cf.colab_id,
                    GROUP_CONCAT(
                        NULLIF(TRIM(cf.descripcion_carrera), '')
                        ORDER BY cf.id ASC
                        SEPARATOR '\n'
                    ) AS profesion,
                    GROUP_CONCAT(
                        NULLIF(TRIM(cf.institucion), '')
                        ORDER BY cf.id ASC
                        SEPARATOR '\n'
                    ) AS institucion,
                    GROUP_CONCAT(
                        COALESCE(
                            NULLIF(TRIM(cf.grado_alcanzado), ''),
                            NULLIF(TRIM(cf.tipo_grado), '')
                        )
                        ORDER BY cf.id ASC
                        SEPARATOR '\n'
                    ) AS grado,
                    GROUP_CONCAT(
                        NULLIF(TRIM(cf.especialidad), '')
                        ORDER BY cf.id ASC
                        SEPARATOR '\n'
                    ) AS curso_especializacion
                FROM colab_formacion cf
                GROUP BY cf.colab_id
            ) form ON form.colab_id = m.id

            LEFT JOIN colab_pension  p ON p.colab_id = m.id
            LEFT JOIN colab_bancario b ON b.colab_id = m.id

            ORDER BY m.nombres_apellidos ASC
        ";

            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}
