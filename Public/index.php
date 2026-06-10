<?php

declare(strict_types=1);

// Public/index.php

ob_start();

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| RUTAS BASE
|--------------------------------------------------------------------------
*/

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

if (!defined('BASE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    define('BASE_URL', $scheme . '://' . $host);
}

/*
|--------------------------------------------------------------------------
| CARGA DE CONFIGURACIÓN
|--------------------------------------------------------------------------
*/

$configFiles = [
    ROOT_PATH . 'config.php',
    ROOT_PATH . 'Config/config.php',
    ROOT_PATH . 'Configuracion/config.php',
    ROOT_PATH . 'Modelo/Conexion.php',
];

foreach ($configFiles as $configFile) {
    if (file_exists($configFile)) {
        require_once $configFile;
    }
}

/*
|--------------------------------------------------------------------------
| HELPERS DE SESIÓN Y PERMISOS
|--------------------------------------------------------------------------
*/

if (!function_exists('app_redirect')) {
    function app_redirect(string $path): void
    {
        $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $path = '/' . ltrim($path, '/');

        header('Location: ' . $base . $path);
        exit;
    }
}

if (!function_exists('user_is_logged')) {
    function user_is_logged(): bool
    {
        return !empty($_SESSION['user_id']) || !empty($_SESSION['usuario_id']);
    }
}

if (!function_exists('user_is_admin_role')) {
    function user_is_admin_role(): bool
    {
        $rol = strtolower(trim((string)($_SESSION['user_role'] ?? $_SESSION['rol'] ?? '')));

        return in_array($rol, ['superadmin', 'admin', 'rrhh'], true);
    }
}

if (!function_exists('current_user_role')) {
    function current_user_role(): string
    {
        return strtolower(trim((string)($_SESSION['user_role'] ?? $_SESSION['rol'] ?? '')));
    }
}

if (!function_exists('check_access')) {
    function check_access(string $rutaBase, string $rol): array
    {
        $rutaBase = trim($rutaBase);
        $rol = strtolower(trim($rol));

        if ($rutaBase === '') {
            return [
                'can_view' => 0,
                'can_edit' => 0,
            ];
        }

        /*
         * Fallback si no hay tabla de permisos disponible.
         */
        if (!class_exists('Conexion')) {
            return [
                'can_view' => in_array($rol, ['superadmin', 'admin', 'rrhh'], true) ? 1 : 0,
                'can_edit' => in_array($rol, ['superadmin', 'admin', 'rrhh'], true) ? 1 : 0,
            ];
        }

        try {
            $pdo = Conexion::conectar();

            if (!$pdo instanceof PDO) {
                throw new Exception('Conexión inválida.');
            }

            $sql = "
                SELECT
                    p.can_view,
                    p.can_edit
                FROM permisos p
                INNER JOIN modulos m ON m.id = p.modulo_id
                WHERE m.ruta_base = :ruta_base
                  AND LOWER(p.rol) = :rol
                LIMIT 1
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':ruta_base', $rutaBase, PDO::PARAM_STR);
            $stmt->bindValue(':rol', $rol, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return [
                    'can_view' => 0,
                    'can_edit' => 0,
                ];
            }

            return [
                'can_view' => (int)($row['can_view'] ?? 0),
                'can_edit' => (int)($row['can_edit'] ?? 0),
            ];
        } catch (Throwable $e) {
            error_log('check_access fallback error: ' . $e->getMessage());

            return [
                'can_view' => in_array($rol, ['superadmin', 'admin', 'rrhh'], true) ? 1 : 0,
                'can_edit' => in_array($rol, ['superadmin', 'admin', 'rrhh'], true) ? 1 : 0,
            ];
        }
    }
}

/*
|--------------------------------------------------------------------------
| NORMALIZAR URL
|--------------------------------------------------------------------------
*/

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestPath = $requestPath ?: '/';

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

if ($scriptDir !== '/' && $scriptDir !== '.' && str_starts_with($requestPath, $scriptDir)) {
    $requestPath = substr($requestPath, strlen($scriptDir));
}

$requestPath = trim($requestPath, '/');

$parts = $requestPath === ''
    ? []
    : array_values(array_filter(explode('/', $requestPath), fn($p) => $p !== ''));

$module = $parts[0] ?? 'rrhh';
$sub = $parts[1] ?? null;

$module = strtolower(trim($module));
$sub = $sub !== null ? strtolower(trim($sub)) : null;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS
|--------------------------------------------------------------------------
*/

$publicRoutes = [
    'login',
    'logout',
];

if (!in_array($module, $publicRoutes, true) && !user_is_logged()) {
    app_redirect('/login');
}

/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

if ($module === 'login') {
    $loginFiles = [
        ROOT_PATH . 'Vista/login.php',
        ROOT_PATH . 'Vista/modulos/login.php',
        ROOT_PATH . 'Vista/auth/login.php',
    ];

    foreach ($loginFiles as $loginFile) {
        if (file_exists($loginFile)) {
            require_once $loginFile;
            exit;
        }
    }

    http_response_code(404);
    echo '404 - Archivo de login no encontrado.';
    exit;
}

/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

if ($module === 'logout') {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool)$params['secure'],
            (bool)$params['httponly']
        );
    }

    session_destroy();

    app_redirect('/login');
}

/*
|--------------------------------------------------------------------------
| PERMISO PRINCIPAL
|--------------------------------------------------------------------------
*/

$rolSesion = current_user_role();
$modulePermission = $module;

/*
 * RRHH usa permisos por submódulo cuando corresponde.
 */
if ($module === 'rrhh') {
    $sub = $parts[1] ?? 'dashboard';

    /*
     * Cada submódulo RRHH usa su propio permiso si existe en la tabla modulos.
     */
    if (in_array($sub, ['contratos', 'licencias', 'teletrabajo', 'validaciones'], true)) {
        $modulePermission = $sub;
    } else {
        $modulePermission = 'rrhh';
    }
}

$permiso = check_access($modulePermission, $rolSesion);

$_SESSION['can_view'] = (int)($permiso['can_view'] ?? 0);
$_SESSION['can_edit'] = (int)($permiso['can_edit'] ?? 0);

/*
|--------------------------------------------------------------------------
| RUTAS AJAX DE PERFIL
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| RUTAS AJAX DE PERFIL
|--------------------------------------------------------------------------
*/

if ($module === 'perfil' && in_array($sub, ['actualizar', 'cambiar-clave'], true)) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');

    if (!function_exists('jsonPerfilResponse')) {
        function jsonPerfilResponse(array $data, int $status = 200): void
        {
            http_response_code($status);

            echo json_encode(
                $data,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            exit;
        }
    }

    if (!function_exists('perfilColumnasTabla')) {
        function perfilColumnasTabla(PDO $pdo, string $tabla): array
        {
            static $cache = [];

            if (isset($cache[$tabla])) {
                return $cache[$tabla];
            }

            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM `{$tabla}`");
                $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $cache[$tabla] = array_map(
                    fn($c) => (string)$c['Field'],
                    $cols ?: []
                );

                return $cache[$tabla];
            } catch (Throwable $e) {
                $cache[$tabla] = [];
                return [];
            }
        }
    }

    if (!function_exists('perfilTablaExiste')) {
        function perfilTablaExiste(PDO $pdo, string $tabla): bool
        {
            try {
                $stmt = $pdo->prepare("SHOW TABLES LIKE :tabla");
                $stmt->bindValue(':tabla', $tabla, PDO::PARAM_STR);
                $stmt->execute();

                return (bool)$stmt->fetchColumn();
            } catch (Throwable $e) {
                return false;
            }
        }
    }

    if (!function_exists('perfilPrimeraTabla')) {
        function perfilPrimeraTabla(PDO $pdo, array $tablas): ?string
        {
            foreach ($tablas as $tabla) {
                if (perfilTablaExiste($pdo, $tabla)) {
                    return $tabla;
                }
            }

            return null;
        }
    }

    if (!function_exists('perfilFkColaborador')) {
        function perfilFkColaborador(array $columnas): ?string
        {
            foreach (['colaborador_id', 'id_colaborador', 'colab_id'] as $fk) {
                if (in_array($fk, $columnas, true)) {
                    return $fk;
                }
            }

            return null;
        }
    }

    if (!function_exists('perfilNormalizarValor')) {
        function perfilNormalizarValor(string $campo, $valor)
        {
            if (is_array($valor) || is_object($valor)) {
                return null;
            }

            $valor = trim((string)($valor ?? ''));

            if ($valor === '' && str_contains($campo, 'fecha')) {
                return null;
            }

            return $valor;
        }
    }

    if (!function_exists('perfilFiltrarData')) {
        function perfilFiltrarData(array $data, array $columnas, array $mapa = []): array
        {
            $salida = [];

            foreach ($data as $campo => $valor) {
                if ($campo === 'id' || is_array($valor) || is_object($valor)) {
                    continue;
                }

                $campoReal = $mapa[$campo] ?? $campo;

                if (!in_array($campoReal, $columnas, true)) {
                    continue;
                }

                $salida[$campoReal] = perfilNormalizarValor($campoReal, $valor);
            }

            return $salida;
        }
    }

    if (!function_exists('perfilActualizarPorId')) {
        function perfilActualizarPorId(PDO $pdo, string $tabla, int $id, array $data, array $mapa = []): void
        {
            if ($id <= 0 || empty($data)) {
                return;
            }

            $columnas = perfilColumnasTabla($pdo, $tabla);
            $dataOk = perfilFiltrarData($data, $columnas, $mapa);

            if (empty($dataOk)) {
                return;
            }

            if (in_array('updated_at', $columnas, true)) {
                $dataOk['updated_at'] = date('Y-m-d H:i:s');
            }

            $sets = [];
            $params = [':id' => $id];

            foreach ($dataOk as $campo => $valor) {
                $sets[] = "`{$campo}` = :{$campo}";
                $params[":{$campo}"] = $valor;
            }

            $sql = "UPDATE `{$tabla}` SET " . implode(', ', $sets) . " WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }
    }

    if (!function_exists('perfilInsertarFila')) {
        function perfilInsertarFila(PDO $pdo, string $tabla, int $idColaborador, array $data, array $mapa = []): void
        {
            $columnas = perfilColumnasTabla($pdo, $tabla);
            $fk = perfilFkColaborador($columnas);

            if (!$fk) {
                return;
            }

            $dataOk = perfilFiltrarData($data, $columnas, $mapa);
            $dataOk[$fk] = $idColaborador;

            if (in_array('created_at', $columnas, true)) {
                $dataOk['created_at'] = date('Y-m-d H:i:s');
            }

            if (in_array('updated_at', $columnas, true)) {
                $dataOk['updated_at'] = date('Y-m-d H:i:s');
            }

            if (empty($dataOk)) {
                return;
            }

            $campos = array_keys($dataOk);
            $placeholders = array_map(fn($c) => ':' . $c, $campos);

            $sql = "
                INSERT INTO `{$tabla}` (`" . implode('`, `', $campos) . "`)
                VALUES (" . implode(', ', $placeholders) . ")
            ";

            $stmt = $pdo->prepare($sql);

            foreach ($dataOk as $campo => $valor) {
                $stmt->bindValue(':' . $campo, $valor);
            }

            $stmt->execute();
        }
    }

    if (!function_exists('perfilActualizarOInsertarLista')) {
        function perfilActualizarOInsertarLista(PDO $pdo, string $tabla, int $idColaborador, array $items, array $mapa = []): void
        {
            $columnas = perfilColumnasTabla($pdo, $tabla);
            $fk = perfilFkColaborador($columnas);

            if (!$fk) {
                return;
            }

            $stmt = $pdo->prepare("SELECT id FROM `{$tabla}` WHERE `{$fk}` = :id_colaborador");
            $stmt->bindValue(':id_colaborador', $idColaborador, PDO::PARAM_INT);
            $stmt->execute();

            $idsExistentes = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
            $idsUsados = [];

            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $idItem = (int)($item['id'] ?? 0);

                if ($idItem > 0 && in_array($idItem, $idsExistentes, true)) {
                    $idsUsados[] = $idItem;
                    perfilActualizarPorId($pdo, $tabla, $idItem, $item, $mapa);
                } else {
                    perfilInsertarFila($pdo, $tabla, $idColaborador, $item, $mapa);
                }
            }

            $idsEliminar = array_diff($idsExistentes, $idsUsados);

            if (!empty($idsEliminar)) {
                $placeholders = implode(',', array_fill(0, count($idsEliminar), '?'));

                $sql = "DELETE FROM `{$tabla}` WHERE `{$fk}` = ? AND id IN ({$placeholders})";
                $stmt = $pdo->prepare($sql);

                $params = array_merge([$idColaborador], array_values($idsEliminar));
                $stmt->execute($params);
            }
        }
    }

    if (!function_exists('perfilReemplazarLista')) {
        function perfilReemplazarLista(PDO $pdo, string $tabla, int $idColaborador, array $items, array $mapa = []): void
        {
            $columnas = perfilColumnasTabla($pdo, $tabla);
            $fk = perfilFkColaborador($columnas);

            if (!$fk) {
                return;
            }

            $stmt = $pdo->prepare("DELETE FROM `{$tabla}` WHERE `{$fk}` = :id_colaborador");
            $stmt->bindValue(':id_colaborador', $idColaborador, PDO::PARAM_INT);
            $stmt->execute();

            foreach ($items as $item) {
                if (is_array($item)) {
                    perfilInsertarFila($pdo, $tabla, $idColaborador, $item, $mapa);
                }
            }
        }
    }

    if (!function_exists('perfilUpsertUnico')) {
        function perfilUpsertUnico(PDO $pdo, array $tablas, int $idColaborador, array $data): void
        {
            $tabla = perfilPrimeraTabla($pdo, $tablas);

            if (!$tabla) {
                return;
            }

            $columnas = perfilColumnasTabla($pdo, $tabla);
            $fk = perfilFkColaborador($columnas);

            if (!$fk) {
                return;
            }

            $stmt = $pdo->prepare("SELECT id FROM `{$tabla}` WHERE `{$fk}` = :id_colaborador LIMIT 1");
            $stmt->bindValue(':id_colaborador', $idColaborador, PDO::PARAM_INT);
            $stmt->execute();

            $idRegistro = (int)($stmt->fetchColumn() ?: 0);

            if ($idRegistro > 0) {
                perfilActualizarPorId($pdo, $tabla, $idRegistro, $data);
            } else {
                perfilInsertarFila($pdo, $tabla, $idColaborador, $data);
            }
        }
    }

    if (!function_exists('perfilGuardarArchivoSustento')) {
        function perfilGuardarArchivoSustento(array $file): ?string
        {
            if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                return null;
            }

            if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
                throw new RuntimeException('El sustento no debe superar los 5 MB.');
            }

            $nombreOriginal = (string)($file['name'] ?? '');
            $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

            $permitidos = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];

            if (!in_array($ext, $permitidos, true)) {
                throw new RuntimeException('Formato de sustento no permitido.');
            }

            $dirRelativo = 'Uploads/sustentos_perfil/';
            $dirAbsoluto = ROOT_PATH . $dirRelativo;

            if (!is_dir($dirAbsoluto)) {
                mkdir($dirAbsoluto, 0775, true);
            }

            $nombreSeguro = 'sustento_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destino = $dirAbsoluto . $nombreSeguro;

            if (!move_uploaded_file($file['tmp_name'], $destino)) {
                throw new RuntimeException('No se pudo guardar el archivo de sustento.');
            }

            return $dirRelativo . $nombreSeguro;
        }
    }

    try {
        if (!user_is_logged()) {
            jsonPerfilResponse([
                'success' => false,
                'mensaje' => 'Sesión expirada. Vuelve a iniciar sesión.'
            ], 401);
        }

        if (!class_exists('Conexion')) {
            require_once ROOT_PATH . 'Modelo/Conexion.php';
        }

        $pdo = Conexion::conectar();

        if (!$pdo instanceof PDO) {
            jsonPerfilResponse([
                'success' => false,
                'mensaje' => 'No se pudo conectar a la base de datos.'
            ], 500);
        }

        /*
        |--------------------------------------------------------------------------
        | /perfil/actualizar
        |--------------------------------------------------------------------------
        */
        if ($sub === 'actualizar') {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonPerfilResponse([
                    'success' => false,
                    'mensaje' => 'Método no permitido.'
                ], 405);
            }

            $payloadRaw = $_POST['payload'] ?? '';

            if ($payloadRaw === '') {
                jsonPerfilResponse([
                    'success' => false,
                    'mensaje' => 'No se recibió información para actualizar.'
                ], 400);
            }

            $payload = json_decode($payloadRaw, true);

            if (!is_array($payload)) {
                jsonPerfilResponse([
                    'success' => false,
                    'mensaje' => 'El formato de la información enviada no es válido.'
                ], 400);
            }

            if (!class_exists('MdDirectorio')) {
                require_once ROOT_PATH . 'Modelo/MdDirectorio.php';
            }

            if (!class_exists('CtrDirectorio')) {
                require_once ROOT_PATH . 'Controlador/CtrDirectorio.php';
            }

            $rolAjax = strtolower(trim($_SESSION['user_role'] ?? ''));
            $esAdminAjax = in_array($rolAjax, ['superadmin', 'admin', 'rrhh'], true);

            if ($esAdminAjax) {
                $idColaborador = (int)($payload['id_colaborador'] ?? $payload['id'] ?? 0);

                if ($idColaborador <= 0) {
                    jsonPerfilResponse([
                        'success' => false,
                        'mensaje' => 'No se pudo identificar al colaborador.'
                    ], 400);
                }

                $payload['id'] = $idColaborador;
                $payload['id_colaborador'] = $idColaborador;
            }

            $archivoSustento = $_FILES['archivo_sustento'] ?? null;

            $controladorPerfil = new CtrDirectorio();
            $respuesta = $controladorPerfil->ctrActualizarPerfil($payload, $archivoSustento);

            if (!empty($respuesta['success'])) {
                $idRedirect = (int)($payload['id'] ?? $payload['id_colaborador'] ?? 0);

                if ($esAdminAjax && $idRedirect > 0) {
                    $respuesta['redirect'] = BASE_URL . '/perfil/' . $idRedirect;
                } else {
                    $respuesta['redirect'] = BASE_URL . '/perfil';
                }
            }

            jsonPerfilResponse($respuesta, !empty($respuesta['success']) ? 200 : 400);
        }
        /*
        |--------------------------------------------------------------------------
        | /perfil/cambiar-clave
        |--------------------------------------------------------------------------
        */
        if ($sub === 'cambiar-clave') {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonPerfilResponse([
                    'success' => false,
                    'mensaje' => 'Método no permitido.'
                ], 405);
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (!is_array($input)) {
                jsonPerfilResponse([
                    'success' => false,
                    'mensaje' => 'Solicitud inválida.'
                ], 400);
            }

            $claveActual = trim((string)($input['clave_actual'] ?? ''));
            $claveNueva = trim((string)($input['clave_nueva'] ?? ''));
            $claveConfirmar = trim((string)($input['clave_confirmar'] ?? ''));

            if ($claveActual === '' || $claveNueva === '' || $claveConfirmar === '') {
                jsonPerfilResponse([
                    'success' => false,
                    'mensaje' => 'Completa todos los campos.'
                ], 400);
            }

            if ($claveNueva !== $claveConfirmar) {
                jsonPerfilResponse([
                    'success' => false,
                    'mensaje' => 'La confirmación no coincide.'
                ], 400);
            }

            if (strlen($claveNueva) < 8) {
                jsonPerfilResponse([
                    'success' => false,
                    'mensaje' => 'La nueva clave debe tener mínimo 8 caracteres.'
                ], 400);
            }

            if ($claveActual === $claveNueva) {
                jsonPerfilResponse([
                    'success' => false,
                    'mensaje' => 'La nueva clave debe ser diferente a la actual.'
                ], 400);
            }

            $usuarioId = (int)($_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? 0);

            if ($usuarioId <= 0) {
                jsonPerfilResponse([
                    'success' => false,
                    'mensaje' => 'Sesión inválida.'
                ], 401);
            }

            $columnasUsuarios = perfilColumnasTabla($pdo, 'usuarios');

            $campoClave = null;

            foreach (['password', 'clave', 'contrasena', 'password_hash'] as $campoTmp) {
                if (in_array($campoTmp, $columnasUsuarios, true)) {
                    $campoClave = $campoTmp;
                    break;
                }
            }

            if (!$campoClave) {
                jsonPerfilResponse([
                    'success' => false,
                    'mensaje' => 'No se encontró el campo de clave en la tabla usuarios.'
                ], 500);
            }

            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id LIMIT 1");
            $stmt->bindValue(':id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();

            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                jsonPerfilResponse([
                    'success' => false,
                    'mensaje' => 'Usuario no encontrado.'
                ], 404);
            }

            $hashActual = (string)($usuario[$campoClave] ?? '');

            $claveOk = password_verify($claveActual, $hashActual) || hash_equals($hashActual, $claveActual);

            if (!$claveOk) {
                jsonPerfilResponse([
                    'success' => false,
                    'mensaje' => 'La clave actual no es correcta.'
                ], 400);
            }

            $nuevoHash = password_hash($claveNueva, PASSWORD_DEFAULT);

            $sets = ["`{$campoClave}` = :clave"];

            if (in_array('cambiar_clave', $columnasUsuarios, true)) {
                $sets[] = "`cambiar_clave` = 0";
            }

            if (in_array('updated_at', $columnasUsuarios, true)) {
                $sets[] = "`updated_at` = :updated_at";
            }

            $sql = "UPDATE usuarios SET " . implode(', ', $sets) . " WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':clave', $nuevoHash);
            $stmt->bindValue(':id', $usuarioId, PDO::PARAM_INT);

            if (in_array('updated_at', $columnasUsuarios, true)) {
                $stmt->bindValue(':updated_at', date('Y-m-d H:i:s'));
            }

            $stmt->execute();

            $_SESSION['cambiar_clave'] = 0;

            jsonPerfilResponse([
                'success' => true,
                'mensaje' => 'Clave actualizada correctamente.',
                'redirect' => BASE_URL . '/perfil'
            ]);
        }

        jsonPerfilResponse([
            'success' => false,
            'mensaje' => 'Ruta AJAX no reconocida.'
        ], 404);
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Error AJAX perfil: ' . $e->getMessage());

        jsonPerfilResponse([
            'success' => false,
            'mensaje' => 'Error interno: ' . $e->getMessage()
        ], 500);
    }
}

/*
|--------------------------------------------------------------------------
| ROUTER
|--------------------------------------------------------------------------
*/

$file = null;

switch ($module) {
    /*
    |--------------------------------------------------------------------------
    | RRHH
    |--------------------------------------------------------------------------
    | Rutas:
    | /rrhh
    | /rrhh/dashboard
    | /rrhh/directorio
    | /rrhh/validaciones
    | /rrhh/contratos
    | /rrhh/licencias
    | /rrhh/teletrabajo
    |--------------------------------------------------------------------------
    */
    case 'rrhh':
        $sub = $sub ?? 'dashboard';

        if (!user_is_admin_role()) {
            http_response_code(403);
            echo '403 - No tienes permiso para acceder a este módulo.';
            exit;
        }

        $subPermitidos = [
            'dashboard',
            'directorio',
            'validaciones',
            'contratos',
            'licencias',
            'teletrabajo',
        ];

        if (!in_array($sub, $subPermitidos, true)) {
            http_response_code(404);
            echo '404 - Submódulo RRHH no encontrado.';
            exit;
        }

        if (empty($permiso['can_view'])) {
            http_response_code(403);
            echo '403 - No tienes permiso para ver este módulo.';
            exit;
        }

        $file = ROOT_PATH . "Vista/modulos/rrhh/{$sub}.php";
        break;

    /*
|--------------------------------------------------------------------------
| PERFIL
|--------------------------------------------------------------------------
| Rutas:
| /perfil       -> Mi perfil del usuario logueado
| /perfil/ID    -> Perfil detalle de colaborador para RRHH/Admin
|--------------------------------------------------------------------------
*/
    case 'perfil':
        $idPerfilUrl = (int)($parts[1] ?? 0);

        /*
     * /perfil/ID
     * Detalle de colaborador para RRHH/Admin.
     */
        if ($idPerfilUrl > 0) {
            if (!user_is_admin_role()) {
                http_response_code(403);
                echo '403 - No tienes permiso para ver este perfil.';
                exit;
            }

            $file = ROOT_PATH . 'Vista/modulos/rrhh/perfil_detalle.php';
            break;
        }

        /*
     * /perfil
     * Mi perfil del usuario logueado.
     */
        $file = ROOT_PATH . 'Vista/modulos/colaborador/perfil.php';
        break;

    /*
    |--------------------------------------------------------------------------
    | DOCUMENTOS
    |--------------------------------------------------------------------------
    */
    case 'documentos':
        $file = ROOT_PATH . 'Vista/modulos/documentos.php';

        if (!file_exists($file)) {
            $file = ROOT_PATH . 'Vista/modulos/documentos/index.php';
        }

        break;

    /*
    |--------------------------------------------------------------------------
    | CONFIGURACIÓN
    |--------------------------------------------------------------------------
    */
    case 'configuracion':
        if (!user_is_admin_role()) {
            http_response_code(403);
            echo '403 - No tienes permiso para acceder a configuración.';
            exit;
        }

        $subConfig = $sub ?? 'permisos';

        $configPermitidos = [
            'permisos',
            'usuarios',
            'modulos',
        ];

        if (!in_array($subConfig, $configPermitidos, true)) {
            http_response_code(404);
            echo '404 - Submódulo de configuración no encontrado.';
            exit;
        }

        $file = ROOT_PATH . "Vista/modulos/configuracion/{$subConfig}.php";
        break;

    /*
    |--------------------------------------------------------------------------
    | MIS VALIDACIONES
    |--------------------------------------------------------------------------
    */
    case 'misvalidaciones':
        $file = ROOT_PATH . 'Vista/modulos/misvalidaciones.php';

        if (!file_exists($file)) {
            $file = ROOT_PATH . 'Vista/modulos/rrhh/misvalidaciones.php';
        }

        break;

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD DIRECTO
    |--------------------------------------------------------------------------
    */
    case 'dashboard':
        $file = ROOT_PATH . 'Vista/modulos/dashboard.php';

        if (!file_exists($file)) {
            $file = ROOT_PATH . 'Vista/modulos/rrhh/dashboard.php';
        }

        break;

    /*
    |--------------------------------------------------------------------------
    | RAÍZ
    |--------------------------------------------------------------------------
    */
    case '':
        app_redirect('/rrhh/dashboard');
        break;

    default:
        /*
         * Carga genérica para módulos existentes.
         * Ejemplo: /algomodulo -> Vista/modulos/algomodulo.php
         */
        $genericFile = ROOT_PATH . "Vista/modulos/{$module}.php";

        if (file_exists($genericFile)) {
            if (empty($permiso['can_view']) && !user_is_admin_role()) {
                http_response_code(403);
                echo '403 - No tienes permiso para ver este módulo.';
                exit;
            }

            $file = $genericFile;
            break;
        }

        http_response_code(404);
        echo '404 - Módulo no encontrado.';
        exit;
}

/*
|--------------------------------------------------------------------------
| CARGAR VISTA
|--------------------------------------------------------------------------
*/

if (!$file || !file_exists($file)) {
    http_response_code(404);
    echo '404 - El archivo del módulo no existe.';

    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo '<br><small>' . htmlspecialchars((string)$file, ENT_QUOTES, 'UTF-8') . '</small>';
    }

    exit;
}

require_once $file;

ob_end_flush();
