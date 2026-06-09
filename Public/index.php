<?php
// Public/index.php
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

/* ============================================================
   HELPERS
============================================================ */

function redirect(string $to)
{
    header('Location: ' . $to);
    exit;
}

function home_by_role(string $role): string
{
    $role = strtolower(trim($role));

    if (in_array($role, ['superadmin', 'admin', 'rrhh'], true)) {
        return BASE_URL . '/rrhh/dashboard';
    }

    return BASE_URL . '/perfil';
}

function check_access(string $module_path, string $role): array
{
    $db = Conexion::conectar();

    $stmt = $db->prepare("
        SELECT p.can_view, p.can_edit 
        FROM permisos p 
        INNER JOIN modulos m ON p.modulo_id = m.id 
        WHERE m.ruta_base = :module 
          AND p.rol = :rol 
        LIMIT 1
    ");

    $stmt->execute([
        ':module' => $module_path,
        ':rol'    => $role
    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'can_view' => 0,
        'can_edit' => 0
    ];
}

function user_is_admin_role(): bool
{
    $rol = strtolower(trim($_SESSION['user_role'] ?? ''));

    return in_array($rol, ['superadmin', 'admin', 'rrhh'], true);
}

/* ============================================================
   ROUTER BASE
============================================================ */

$path   = trim((string)($_GET['url'] ?? 'login'), '/');
$parts  = $path !== '' ? explode('/', $path) : [];
$module = $parts[0] ?? 'login';

if ($module === '') {
    $module = 'login';
}

/* ============================================================
   RUTAS PÚBLICAS
============================================================ */

if ($module === 'login' || $module === 'logout') {

    if ($module === 'logout') {
        session_destroy();
        redirect(BASE_URL . '/login');
    }

    if (!empty($_SESSION['user_id'])) {
        redirect(home_by_role($_SESSION['user_role'] ?? ''));
    }

    require_once __DIR__ . '/../Vista/modulos/login.php';
    exit;
}

/* ============================================================
   VALIDACIÓN DE SESIÓN
============================================================ */

if (empty($_SESSION['user_id'])) {
    redirect(BASE_URL . '/login');
}

/* ============================================================
   SINCRONIZAR CAMBIAR_CLAVE DESDE BD
============================================================ */

$usuarioActual = MdUsuario::mdlMostrarUsuarios(
    'usuarios',
    'id',
    (string)$_SESSION['user_id']
);

$_SESSION['cambiar_clave'] = (int)($usuarioActual['cambiar_clave'] ?? 0);

/* ============================================================
   BLOQUEO POR PRIMER CAMBIO DE CLAVE
   IMPORTANTE:
   - Solo bloqueamos rutas críticas si el usuario es colaborador.
   - Para admin/rrhh/superadmin no bloqueamos Directorio, Validaciones
     ni Contratos desde aquí, porque eso debe manejarlo permisos.
============================================================ */

if ((int)($_SESSION['cambiar_clave'] ?? 0) === 1) {

    $rolActual = strtolower(trim($_SESSION['user_role'] ?? ''));

    if ($rolActual === 'colaborador') {

        $rutasPermitidasCambioClave = [
            'perfil',
            'perfil/cambiar-clave',
            'logout'
        ];

        if (!in_array($path, $rutasPermitidasCambioClave, true)) {
            redirect(BASE_URL . '/perfil');
        }
    }
}

/* ============================================================
   RESTRICCIÓN GENERAL:
   COLABORADOR NO ENTRA A RRHH
============================================================ */

$rolSesion = strtolower(trim($_SESSION['user_role'] ?? ''));

if ($rolSesion === 'colaborador' && $module === 'rrhh') {
    redirect(BASE_URL . '/perfil');
}

/* ============================================================
   RUTAS AJAX
============================================================ */

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

if ($module === 'perfil' && ($parts[1] ?? '') === 'cambiar-clave') {

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');

    try {
        $respuesta = CtrUsuario::ctrCambiarClavePerfil();

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

    if (!user_is_admin_role()) {
        echo json_encode([
            'success' => false,
            'mensaje' => 'No tienes permiso para aprobar solicitudes'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

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

    if (!user_is_admin_role()) {
        echo json_encode([
            'success' => false,
            'mensaje' => 'No tienes permiso para rechazar solicitudes'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

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

/* ============================================================
   EXPORTAR EXCEL DIRECTORIO
   URL: /rrhh/directorio/xlsx
============================================================ */

if ($module === 'rrhh' && ($parts[1] ?? '') === 'directorio' && ($parts[2] ?? '') === 'xlsx') {

    if (!user_is_admin_role()) {
        die("No tienes permiso para exportar este reporte.");
    }

    require_once __DIR__ . '/../Modelo/MdDirectorio.php';
    require_once __DIR__ . '/../Controlador/CtrDirectorio.php';
    require_once __DIR__ . '/../Vista/modulos/rrhh/RptExcelDirectorioXlsx.php';
    exit;
}

/* ============================================================
   PERFIL PROPIO
   URL: /perfil
============================================================ */

if ($module === 'perfil' && empty($parts[1])) {

    require_once __DIR__ . '/../Modelo/MdDirectorio.php';
    require_once __DIR__ . '/../Controlador/CtrDirectorio.php';

    $ctrl = new CtrDirectorio();
    $data = $ctrl->ctrVerPerfil(null);

    if (!$data) {

        if (user_is_admin_role()) {
            redirect(BASE_URL . '/rrhh/dashboard');
        }

        echo "404 - No se encontró el perfil asociado a este usuario.";
        exit;
    }

    require_once __DIR__ . '/../Vista/modulos/shared/perfil_base.php';
    exit;
}

/* ============================================================
   PERFIL DETALLE RRHH
   URLS SOPORTADAS:
   - /rrhh/perfil/{id}
   - /rrhh/perfil_detalle/{id}
============================================================ */

if (
    $module === 'rrhh' &&
    in_array(($parts[1] ?? ''), ['perfil', 'perfil_detalle'], true)
) {

    if (!user_is_admin_role()) {
        die("No tienes permiso para acceder a este módulo.");
    }

    require_once __DIR__ . '/../Modelo/MdDirectorio.php';
    require_once __DIR__ . '/../Controlador/CtrDirectorio.php';

    $idColaborador = (int)($parts[2] ?? 0);

    if ($idColaborador <= 0) {
        redirect(BASE_URL . '/rrhh/directorio');
    }

    $ctrl = new CtrDirectorio();
    $data = $ctrl->ctrVerPerfil($idColaborador);

    if (!$data) {
        echo "404 - No se encontró el perfil del colaborador.";
        exit;
    }

    require_once __DIR__ . '/../Vista/modulos/shared/perfil_base.php';
    exit;
}

/* ============================================================
   PERMISOS
   AQUÍ ESTABA EL PROBLEMA:
   Antes validabas solo $module.
   Ahora validamos la ruta real.
============================================================ */

/* ============================================================
   PERMISOS
============================================================ */

$user_role = strtolower(trim($_SESSION['user_role'] ?? ''));

$modulePermission = $module;

if ($module === 'rrhh') {
    $sub = $parts[1] ?? 'dashboard';

    /*
     * Permisos por submódulo RRHH:
     * - contratos usa permiso contratos
     * - teletrabajo también usará permiso contratos
     *   porque pertenece a gestión documental/laboral.
     * - los demás usan permiso rrhh
     */
    if (in_array($sub, ['contratos', 'teletrabajo'], true)) {
        $modulePermission = 'contratos';
    } else {
        $modulePermission = 'rrhh';
    }
}

if ($module === 'configuracion') {
    $modulePermission = 'configuracion';
}

$permisos = check_access($modulePermission, $user_role);

if (!$permisos['can_view']) {
    die("No tienes permiso para acceder a este módulo: " . htmlspecialchars($modulePermission));
}

$_SESSION['current_module_can_edit'] = (bool)$permisos['can_edit'];


/* ============================================================
   CARGA DE MÓDULOS
============================================================ */

$file = '';

switch ($module) {

    case 'documentos':
        $file = __DIR__ . "/../Vista/modulos/colaborador/documentos.php";
        break;

    case 'misvalidaciones':
        $file = __DIR__ . "/../Vista/modulos/colaborador/misvalidaciones.php";
        break;

    case 'rrhh':
        $sub = $parts[1] ?? 'dashboard';

        if (!user_is_admin_role()) {
            die("No tienes permiso para acceder a este módulo.");
        }

        $subPermitidos = [
            'dashboard',
            'directorio',
            'validaciones',
            'contratos',
            'teletrabajo'
        ];

        if (!in_array($sub, $subPermitidos, true)) {
            echo "404 - Submódulo RRHH no encontrado.";
            exit;
        }

        $file = __DIR__ . "/../Vista/modulos/rrhh/{$sub}.php";
        break;

    case 'configuracion':
        $file = __DIR__ . "/../Vista/modulos/configuracion/permisos.php";
        break;

    default:
        $file = __DIR__ . "/../Vista/modulos/{$module}.php";
        break;
}

if ($file && file_exists($file)) {
    require_once $file;
    exit;
}

echo "404 - El archivo del módulo no existe.";
exit;