<?php
// /Public/index.php

declare(strict_types=1);

// Configuración de errores para desarrollo
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

// -------------------------------------------------------------------------
// 1. CARGAMOS LA CONFIGURACIÓN
// -------------------------------------------------------------------------
$ruta_config = __DIR__ . '/../Config/config.php';
if (file_exists($ruta_config)) {
    require_once $ruta_config;
}

// Fallback de seguridad
if (!defined('BASE_URL')) {
    define('BASE_URL', 'https://rrhh.legrand.pe');
}

/*
|--------------------------------------------------------------------------
| HELPERS (Funciones de utilidad)
|--------------------------------------------------------------------------
*/

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

function not_found(string $msg = '404 - Página no encontrada'): void
{
    http_response_code(404);
    echo "
    <div style='font-family:Arial,sans-serif;padding:24px;color:#0f172a;text-align:center;'>
        <h1 style='margin:0 0 10px;font-size:28px'>{$msg}</h1>
        <p>Verifica que la ruta o el archivo existan en la carpeta correspondiente.</p>
        <a href='" . BASE_URL . "/login' style='color:#4f46e5;text-decoration:none;font-weight:bold;'>Volver al inicio</a>
    </div>";
    exit;
}

function require_role(array $allowed_roles): void
{
    if (empty($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
        redirect(BASE_URL . '/login?error=no_access');
    }
}

function require_file(string $file): void
{
    if (!is_file($file)) {
        not_found("404 - No existe el archivo físico: {$file}");
    }
    require $file;
}

/*
|--------------------------------------------------------------------------
| ROUTER (Procesamiento de la URL)
|--------------------------------------------------------------------------
*/

$path   = trim((string)($_GET['url'] ?? 'login'), '/');
$parts  = $path === '' ? [] : explode('/', $path);
$module = $parts[0] ?? 'login';
$sub    = $parts[1] ?? null;

/*
|--------------------------------------------------------------------------
| RRHH ROUTES (/rrhh/...)
|--------------------------------------------------------------------------
*/
if ($module === 'rrhh') {
    // SEGURIDAD DESACTIVADA TEMPORALMENTE PARA VER EL DISEÑO
    // require_role(['rrhh', 'admin', 'superadmin']);

    $subRoute = (string)($sub ?? '');

    // RUTA VALIDACIONES
    if ($subRoute === 'validaciones') {
        require_file(__DIR__ . '/../Vista/modulos/rrhh/validaciones.php');
        exit;
    }

    // RUTA DIRECTORIO
    if ($subRoute === 'directorio') {
        require_file(__DIR__ . '/../Vista/modulos/rrhh/directorio.php');
        exit;
    }

    // RUTA VALIDACIONES
    if ($subRoute === 'validaciones') {
        require_file(__DIR__ . '/../Vista/modulos/rrhh/validaciones.php');
        exit;
    }

    // RUTA DASHBOARD
    if ($subRoute === 'dashboard') {
        require_file(__DIR__ . '/../Vista/modulos/rrhh/dashboard.php');
        exit;
    }

    not_found('404 - RRHH: Ruta no definida');
}
/*
|--------------------------------------------------------------------------
| PUBLIC & COLLABORATOR ROUTES
|--------------------------------------------------------------------------
*/
$routes = [

    'login' => static function (): void {
        // Llamamos directamente a la vista del login que creamos
        require_file(__DIR__ . '/../Vista/modulos/login.php');
    },

    'logout' => static function (): void {
        session_destroy();
        redirect(BASE_URL . '/login');
    },

    'perfil' => static function (): void {
        // SEGURIDAD DESACTIVADA TEMPORALMENTE PARA VER EL DISEÑO
        // require_role(['colaborador', 'rrhh', 'admin', 'superadmin']); 

        // Llamamos directamente a la vista del perfil
        require_file(__DIR__ . '/../Vista/modulos/colaborador/perfil.php');
    }
];

// Ejecución de la ruta encontrada
if (!isset($routes[$module])) {
    not_found("404 - El módulo '{$module}' no está definido en el sistema.");
}

$routes[$module]();
