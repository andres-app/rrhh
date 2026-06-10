<?php
// Vista/modulos/rrhh/perfil_detalle.php

if (!defined('ROOT_PATH')) {
    require_once __DIR__ . '/../../../Config/config.php';
}

require_once ROOT_PATH . 'Controlador/CtrDirectorio.php';
require_once ROOT_PATH . 'Modelo/MdDirectorio.php';

$rolSesion = strtolower(trim($_SESSION['user_role'] ?? ''));
$esAdmin = in_array($rolSesion, ['superadmin', 'admin', 'rrhh'], true);

if (!$esAdmin) {
    http_response_code(403);
    echo '403 - No tienes permiso para ver este perfil.';
    exit;
}

if (!function_exists('calcularEdad')) {
    function calcularEdad(?string $fechaNac): string
    {
        if (empty($fechaNac)) return '—';

        try {
            $nac = new DateTime($fechaNac);
            $hoy = new DateTime();
            return $hoy->diff($nac)->y . ' años';
        } catch (Throwable $e) {
            return '—';
        }
    }
}

if (!function_exists('formatFecha')) {
    function formatFecha(?string $fecha): string
    {
        if (empty($fecha)) return '—';

        try {
            return (new DateTime($fecha))->format('d/m/Y');
        } catch (Throwable $e) {
            return $fecha;
        }
    }
}

$url_params = $parts ?? explode('/', trim($_GET['url'] ?? '', '/'));

$id_colaborador = 0;

/*
|--------------------------------------------------------------------------
| Compatible con:
| /perfil/ID
| /rrhh/perfil/ID
|--------------------------------------------------------------------------
*/
if (($url_params[0] ?? '') === 'perfil') {
    $id_colaborador = (int)($url_params[1] ?? 0);
} elseif (($url_params[0] ?? '') === 'rrhh' && ($url_params[1] ?? '') === 'perfil') {
    $id_colaborador = (int)($url_params[2] ?? 0);
}

if ($id_colaborador <= 0) {
    header('Location: ' . BASE_URL . '/rrhh/directorio');
    exit;
}

$controlador = new CtrDirectorio();
$data = $controlador->ctrVerPerfil($id_colaborador);

if (!$data) {
    echo "<div style='padding:50px; text-align:center;'><h1>404 - No encontrado</h1></div>";
    exit;
}

$menu_activo = 'perfil';

require_once ROOT_PATH . 'Vista/modulos/shared/perfil_base.php';