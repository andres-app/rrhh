<?php
//Public/index.php
declare(strict_types=1);
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../Config/config.php';
require_once __DIR__ . '/../Modelo/Conexion.php';

// Modelos
require_once __DIR__ . '/../Modelo/MdUsuario.php';
require_once __DIR__ . '/../Modelo/MdPermisos.php';

// Controladores
require_once __DIR__ . '/../Controlador/CtrUsuario.php';
require_once __DIR__ . '/../Controlador/CtrPermisos.php';

/* HELPERS */
function redirect(string $to)
{
    header('Location: ' . $to);
    exit;
}

function check_access(string $module_path, string $role): array
{
    $db = Conexion::conectar();
    $stmt = $db->prepare("SELECT p.can_view, p.can_edit 
                          FROM permisos p 
                          INNER JOIN modulos m ON p.modulo_id = m.id 
                          WHERE m.ruta_base = :module AND p.rol = :rol LIMIT 1");
    $stmt->execute(['module' => $module_path, 'rol' => $role]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['can_view' => 0, 'can_edit' => 0];
}

/* ROUTER LOGIC */
$path   = trim((string)($_GET['url'] ?? 'login'), '/');
$parts  = explode('/', $path);
$module = $parts[0] ?: 'login';

// ── REDIRECCIÓN INTELIGENTE POR ROL (ANTES DE PERMISOS) ──
if (!empty($_SESSION['user_id'])) {

    $rol = strtolower($_SESSION['user_role'] ?? '');

    // Si es colaborador y entra a RRHH → lo mandas a su perfil
    if ($rol === 'colaborador' && $module === 'rrhh') {
        redirect(BASE_URL . '/perfil');
    }
}

// ── Rutas públicas ────────────────────────────────────────────
if ($module === 'login' || $module === 'logout') {
    if ($module === 'logout') {
        session_destroy();
        redirect(BASE_URL . '/login');
    }
    require_once __DIR__ . '/../Vista/modulos/login.php';
    exit;
}

// ── Validación de sesión ──────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    redirect(BASE_URL . '/login');
}


// ══════════════════════════════════════════════════════════════
// RUTAS AJAX 
// ══════════════════════════════════════════════════════════════

if ($module === 'perfil' && ($parts[1] ?? '') === 'actualizar') {

    require_once __DIR__ . '/../Modelo/MdDirectorio.php';
    require_once __DIR__ . '/../Controlador/CtrDirectorio.php';

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');

    try {
        $ctrl = new CtrDirectorio();

        $body = [];
        if (!empty($_POST['payload'])) {
            $body = json_decode((string)$_POST['payload'], true);
        }

        if (!is_array($body)) {
            echo json_encode([
                'success' => false,
                'mensaje' => 'Payload inválido o vacío'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $archivo = $_FILES['archivo_sustento'] ?? null;

        $respuesta = $ctrl->ctrActualizarPerfil($body, $archivo);

        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        echo json_encode([
            'success' => false,
            'mensaje' => 'Error interno: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if ($module === 'rrhh' && ($parts[1] ?? '') === 'validaciones' && ($parts[2] ?? '') === 'aprobar') {
    require_once __DIR__ . '/../Modelo/MdDirectorio.php';
    require_once __DIR__ . '/../Controlador/CtrDirectorio.php';

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');

    try {
        $idSolicitud = (int)($parts[3] ?? 0);
        $ctrl = new CtrDirectorio();
        $respuesta = $ctrl->ctrAprobarSolicitudCambio($idSolicitud);

        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        echo json_encode([
            'success' => false,
            'mensaje' => 'Error interno: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if ($module === 'rrhh' && ($parts[1] ?? '') === 'validaciones' && ($parts[2] ?? '') === 'rechazar') {
    require_once __DIR__ . '/../Modelo/MdDirectorio.php';
    require_once __DIR__ . '/../Controlador/CtrDirectorio.php';

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');

    try {
        $idSolicitud = (int)($parts[3] ?? 0);
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true);
        $motivo = trim((string)($body['motivo'] ?? ''));

        $ctrl = new CtrDirectorio();
        $respuesta = $ctrl->ctrRechazarSolicitudCambio($idSolicitud, $motivo);

        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        echo json_encode([
            'success' => false,
            'mensaje' => 'Error interno: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ── Permisos ──────────────────────────────────────────────────
$user_role = $_SESSION['user_role'];
$permisos  = check_access($module, $user_role);

if (!$permisos['can_view']) {
    die("No tienes permiso para acceder a este módulo.");
}

$_SESSION['current_module_can_edit'] = (bool)$permisos['can_edit'];

/* CARGA DE MÓDULOS */
switch ($module) {
    case 'documentos':
        $file = __DIR__ . "/../Vista/modulos/colaborador/documentos.php";
        break;

    case 'misvalidaciones':
        $file = __DIR__ . "/../Vista/modulos/colaborador/misvalidaciones.php";
        break;

    case 'rrhh':
        $sub = $parts[1] ?? 'dashboard';

        // Restricción específica para submódulo contratos
        if ($sub === 'contratos') {
            $rolActual = strtolower(trim($_SESSION['user_role'] ?? ''));

            if (!in_array($rolActual, ['superadmin', 'admin', 'rrhh'], true)) {
                die("No tienes permiso para acceder a este módulo.");
            }
        }

        $file = __DIR__ . "/../Vista/modulos/rrhh/{$sub}.php";

        if ($sub === 'perfil') {
            $id_colaborador = $parts[2] ?? null;
            $file = __DIR__ . "/../Vista/modulos/rrhh/perfil_detalle.php";
        }
        break;

    case 'configuracion':
        $file = __DIR__ . "/../Vista/modulos/configuracion/permisos.php";
        break;

    case 'perfil':
        $file = __DIR__ . "/../Vista/modulos/colaborador/perfil.php";
        break;

    default:
        $file = __DIR__ . "/../Vista/modulos/{$module}.php";
        break;
}

if (file_exists($file)) {
    require_once $file;
} else {
    echo "404 - El archivo del módulo no existe.";
}
