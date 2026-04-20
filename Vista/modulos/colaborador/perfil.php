<?php
// Vista/modulos/colaborador/perfil.php
if (!defined('ROOT_PATH')) {
    require_once __DIR__ . '/../../../Config/config.php';
}

require_once ROOT_PATH . 'Controlador/CtrDirectorio.php';
require_once ROOT_PATH . 'Modelo/MdDirectorio.php';

$rolSesion = strtolower(trim($_SESSION['user_role'] ?? ''));
$esAdmin = in_array($rolSesion, ['superadmin', 'admin', 'rrhh'], true);

function calcularEdad(?string $fechaNac): string
{
    if (empty($fechaNac)) return '—';
    try {
        $nac = new DateTime($fechaNac);
        $hoy = new DateTime();
        return $hoy->diff($nac)->y . ' años';
    } catch (Exception $e) {
        return '—';
    }
}

function formatFecha(?string $fecha): string
{
    if (empty($fecha)) return '—';
    try {
        return (new DateTime($fecha))->format('d/m/Y');
    } catch (Exception $e) {
        return $fecha;
    }
}

$id_colaborador = (int)($_SESSION['user_id'] ?? 0);

if ($id_colaborador <= 0) {
    header('Location: ' . BASE_URL . '/login');
    exit;
}

$controlador = new CtrDirectorio();
$data = $controlador->ctrVerPerfil($id_colaborador);

if (!$data) {
    echo "<div style='padding:50px; text-align:center;'><h1>404 - No encontrado</h1></div>";
    exit;
}

require_once ROOT_PATH . 'Vista/modulos/shared/perfil_base.php';