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
    | /perfil
    | /perfil/ID
    |--------------------------------------------------------------------------
    */
    case 'perfil':
        $file = ROOT_PATH . 'Vista/modulos/perfil.php';

        if (!file_exists($file)) {
            $file = ROOT_PATH . 'Vista/modulos/rrhh/perfil.php';
        }

        if (!file_exists($file)) {
            $file = ROOT_PATH . 'Vista/modulos/rrhh/perfil_detalle.php';
        }

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