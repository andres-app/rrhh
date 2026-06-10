<?php
// /Controlador/CtrUsuario.php

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

if (!class_exists('MdUsuario')) {
    $rutaMdUsuario = ROOT_PATH . 'Modelo/MdUsuario.php';

    if (!file_exists($rutaMdUsuario)) {
        throw new RuntimeException('No se encontró Modelo/MdUsuario.php en: ' . $rutaMdUsuario);
    }

    require_once $rutaMdUsuario;
}

class CtrUsuario
{
    /**
     * Maneja el proceso de inicio de sesión.
     */
    public function ctrLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (!isset($_POST["login_username"])) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tabla = "usuarios";
        $item = "username";
        $valor = trim((string)($_POST["login_username"] ?? ''));
        $claveIngresada = (string)($_POST["login_password"] ?? '');

        if ($valor === '' || $claveIngresada === '') {
            echo '<div class="mt-4 bg-red-50 border-l-4 border-red-500 p-3 text-red-700 text-sm font-bold shadow-sm">
                    Ingresa usuario y contraseña.
                  </div>';
            return;
        }

        $respuesta = MdUsuario::mdlMostrarUsuarios($tabla, $item, $valor);

        if (!$respuesta || !is_array($respuesta)) {
            echo '<div class="mt-4 bg-red-50 border-l-4 border-red-500 p-3 text-red-700 text-sm font-bold shadow-sm">
                    Usuario o contraseña incorrectos.
                  </div>';
            return;
        }

        $usernameBD = (string)($respuesta["username"] ?? '');

        $hashBD = (string)(
            $respuesta["password"]
            ?? $respuesta["password_hash"]
            ?? $respuesta["clave"]
            ?? $respuesta["contrasena"]
            ?? $respuesta["contraseña"]
            ?? ''
        );

        if ($usernameBD !== $valor || $hashBD === '' || !password_verify($claveIngresada, $hashBD)) {
            echo '<div class="mt-4 bg-red-50 border-l-4 border-red-500 p-3 text-red-700 text-sm font-bold shadow-sm">
                    Usuario o contraseña incorrectos.
                  </div>';
            return;
        }

        if ((int)($respuesta["estado"] ?? 0) !== 1) {
            echo '<div class="mt-4 bg-orange-50 border-l-4 border-orange-500 p-3 text-orange-700 text-sm italic">
                    Esta cuenta está desactivada. Por favor, contacte con Recursos Humanos.
                  </div>';
            return;
        }

        $_SESSION["validarSesion"] = "ok";
        $_SESSION["user_id"] = (int)($respuesta["id"] ?? 0);
        $_SESSION["username"] = $usernameBD;
        $_SESSION["user_role"] = (string)($respuesta["rol"] ?? '');
        $_SESSION["cambiar_clave"] = (int)($respuesta["cambiar_clave"] ?? 0);

        $_SESSION["nombre_completo"] = !empty($respuesta["nombres_apellidos"])
            ? $respuesta["nombres_apellidos"]
            : $usernameBD;

        $rolLimpio = strtolower(trim((string)($respuesta["rol"] ?? '')));

        if ($_SESSION["cambiar_clave"] === 1) {
            echo '<script>window.location = "perfil";</script>';
            exit();
        }

        if (in_array($rolLimpio, ["superadmin", "admin", "rrhh"], true)) {
            echo '<script>window.location = "rrhh/dashboard";</script>';
            exit();
        }

        if ($rolLimpio === "colaborador") {
            echo '<script>window.location = "perfil";</script>';
            exit();
        }

        echo '<script>window.location = "inicio";</script>';
        exit();
    }

    /**
     * Cerrar sesión de forma segura.
     */
    public static function ctrLogout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        echo '<script>window.location = "login";</script>';
        exit();
    }

    public static function ctrCambiarClavePerfil(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            $usuarioId = (int)($_SESSION['user_id'] ?? 0);

            if ($usuarioId <= 0) {
                return [
                    'success' => false,
                    'mensaje' => 'Sesión no válida. Vuelve a iniciar sesión.'
                ];
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (!is_array($input)) {
                return [
                    'success' => false,
                    'mensaje' => 'Solicitud inválida.'
                ];
            }

            $claveActual = trim((string)($input['clave_actual'] ?? ''));
            $claveNueva = trim((string)($input['clave_nueva'] ?? ''));
            $claveConfirmar = trim((string)($input['clave_confirmar'] ?? ''));

            if ($claveActual === '' || $claveNueva === '' || $claveConfirmar === '') {
                return [
                    'success' => false,
                    'mensaje' => 'Completa todos los campos.'
                ];
            }

            if (strlen($claveNueva) < 8) {
                return [
                    'success' => false,
                    'mensaje' => 'La nueva clave debe tener mínimo 8 caracteres.'
                ];
            }

            if ($claveNueva !== $claveConfirmar) {
                return [
                    'success' => false,
                    'mensaje' => 'La confirmación no coincide con la nueva clave.'
                ];
            }

            if ($claveActual === $claveNueva) {
                return [
                    'success' => false,
                    'mensaje' => 'La nueva clave debe ser diferente a la actual.'
                ];
            }

            $usuario = MdUsuario::mdlObtenerUsuarioParaClave($usuarioId);

            if (!$usuario || empty($usuario['password_hash'])) {
                return [
                    'success' => false,
                    'mensaje' => 'No se encontró la clave del usuario.'
                ];
            }

            if (!password_verify($claveActual, $usuario['password_hash'])) {
                return [
                    'success' => false,
                    'mensaje' => 'La clave actual no es correcta.'
                ];
            }

            if ($claveNueva === (string)($usuario['username'] ?? '')) {
                return [
                    'success' => false,
                    'mensaje' => 'La nueva clave no puede ser igual al DNI.'
                ];
            }

            $nuevoHash = password_hash($claveNueva, PASSWORD_DEFAULT);

            $ok = MdUsuario::mdlActualizarClavePerfil($usuarioId, $nuevoHash);

            if (!$ok) {
                return [
                    'success' => false,
                    'mensaje' => 'No se pudo actualizar la clave.'
                ];
            }

            $_SESSION['cambiar_clave'] = 0;

            $baseUrl = defined('BASE_URL') ? BASE_URL : '';

            return [
                'success' => true,
                'mensaje' => 'Clave actualizada correctamente.',
                'redirect' => $baseUrl . '/perfil'
            ];
        } catch (Throwable $e) {
            error_log('Error al cambiar clave: ' . $e->getMessage());

            return [
                'success' => false,
                'mensaje' => 'Error interno al cambiar la clave.'
            ];
        }
    }
}