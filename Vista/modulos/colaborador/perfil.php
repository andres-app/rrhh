<?php
// Vista/modulos/colaborador/perfil.php

if (!defined('ROOT_PATH')) {
    require_once __DIR__ . '/../../../Config/config.php';
}

require_once ROOT_PATH . 'Modelo/Conexion.php';
require_once ROOT_PATH . 'Controlador/CtrDirectorio.php';
require_once ROOT_PATH . 'Modelo/MdDirectorio.php';

$rolSesion = strtolower(trim($_SESSION['user_role'] ?? ''));
$esAdmin = in_array($rolSesion, ['superadmin', 'admin', 'rrhh'], true);

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

/*
|--------------------------------------------------------------------------
| ID REAL DEL USUARIO LOGUEADO
|--------------------------------------------------------------------------
| Este ID pertenece a la tabla usuarios.
*/
$usuario_id = (int)($_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? 0);

if ($usuario_id <= 0) {
    header('Location: ' . BASE_URL . '/login');
    exit;
}

/*
|--------------------------------------------------------------------------
| BUSCAR EL ID REAL DEL COLABORADOR
|--------------------------------------------------------------------------
| usuarios.id = colab_maestro.usuario_id
| ctrVerPerfil() debe recibir colab_maestro.id
*/
try {
    $pdo = Conexion::conectar();

    $stmt = $pdo->prepare("
        SELECT id
        FROM colab_maestro
        WHERE usuario_id = :usuario_id
        LIMIT 1
    ");

    $stmt->bindValue(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();

    $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);
    $id_colaborador = (int)($colaborador['id'] ?? 0);
} catch (Throwable $e) {
    error_log('Error obteniendo colaborador por usuario_id: ' . $e->getMessage());
    $id_colaborador = 0;
}

if ($id_colaborador <= 0) {
    echo "<div style='padding:50px; text-align:center;'>
            <h1>404 - Perfil no encontrado</h1>
            <p>No existe un colaborador vinculado a este usuario.</p>
          </div>";
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