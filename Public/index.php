<?php
//Public/index.php
declare(strict_types=1);
ob_start();
ini_set('display_errors', '1');
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
// RUTAS AJAX — van ANTES del check_access para no bloquearse
// ══════════════════════════════════════════════════════════════
if ($module === 'perfil' && ($parts[1] ?? '') === 'actualizar') {
    require_once __DIR__ . '/../Modelo/MdDirectorio.php';
    require_once __DIR__ . '/../Controlador/CtrDirectorio.php';

    // Limpiamos cualquier salida previa para que no corrompa el JSON
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    $body = json_decode(file_get_contents('php://input'), true);

    if (!$body || empty($body['id'])) {
        echo json_encode(['success' => false, 'mensaje' => 'Datos inválidos']);
        exit;
    }

    if ((int)$body['id'] !== (int)$_SESSION['user_id']) {
        echo json_encode(['success' => false, 'mensaje' => 'Sin permiso']);
        exit;
    }

    $ctrl = new CtrDirectorio();
    // Ejecutamos el método. El echo se hace AQUÍ, no dentro del controlador.
    $respuesta = $ctrl->ctrActualizarPerfil($body);
    echo json_encode($respuesta);
    exit;
}
// ══════════════════════════════════════════════════════════════

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

    case 'rrhh':
        $sub = $parts[1] ?? 'dashboard';
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