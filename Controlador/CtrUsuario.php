<?php
// /Controlador/CtrUsuario.php

class CtrUsuario
{

    /**
     * Maneja el proceso de inicio de sesión
     */
    public function ctrLogin()
    {
        if (isset($_POST["login_username"])) {

            // 1. Definición de variables
            $tabla = "usuarios";
            $item = "username";
            $valor = $_POST["login_username"];

            /**
             * 2. Llamada al Modelo
             * Nota: El modelo MdUsuario::mdlMostrarUsuarios ya debe tener 
             * el LEFT JOIN con la tabla colab_maestro.
             */
            $respuesta = MdUsuario::mdlMostrarUsuarios($tabla, $item, $valor);

            // 3. Verificación de credenciales con password_verify (por tus hashes $2y$10...)
            if (
                $respuesta && $respuesta["username"] == $_POST["login_username"] &&
                password_verify($_POST["login_password"], $respuesta["password"])
            ) {

                // 4. Verificación de si el usuario está activo
                if ($respuesta["estado"] == 1) {

                    // 5. Configuración de Variables de Sesión
                    $_SESSION["validarSesion"] = "ok";
                    $_SESSION["user_id"] = $respuesta["id"];
                    $_SESSION["username"] = $respuesta["username"];
                    $_SESSION["user_role"] = $respuesta["rol"]; // admin, superadmin, colaborador

                    /**
                     * 6. Lógica de Nombre Real
                     * Si existe el registro en colab_maestro usa el nombre real,
                     * de lo contrario usa el username (ej. para el usuario 'admin')
                     */
                    $_SESSION["nombre_completo"] = (!empty($respuesta["nombres_apellidos"]))
                        ? $respuesta["nombres_apellidos"]
                        : $respuesta["username"];

                    /**
                     * 7. Redirección Inteligente según Permisos
                     */
                    $rolLimpio = strtolower(trim($respuesta["rol"]));

                    if ($rolLimpio == "superadmin" || $rolLimpio == "admin" || $rolLimpio == "rrhh") {
                        // Ahora el rol 'rrhh' también es redirigido al panel administrativo
                        echo '<script>window.location = "rrhh/dashboard";</script>';
                    } else if ($rolLimpio == "colaborador") {
                        // Los colaboradores van a su perfil personal
                        echo '<script>window.location = "perfil";</script>';
                    } else {
                        // Si el rol no coincide con ninguno de los anteriores
                        echo '<script>window.location = "inicio";</script>';
                    }

                    exit(); // Detener ejecución tras redirección

                } else {
                    echo '<div class="mt-4 bg-orange-50 border-l-4 border-orange-500 p-3 text-orange-700 text-sm italic">
                            Esta cuenta está desactivada. Por favor, contacte con Recursos Humanos.
                          </div>';
                }
            } else {
                // Error de credenciales
                echo '<div class="mt-4 bg-red-50 border-l-4 border-red-500 p-3 text-red-700 text-sm font-bold shadow-sm">
                        Usuario o contraseña incorrectos.
                      </div>';
            }
        }
    }

    /**
     * Cerrar sesión de forma segura
     */
    static public function ctrLogout()
    {
        session_destroy();
        echo '<script>window.location = "login";</script>';
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

        $nuevoHash = password_hash($claveNueva, PASSWORD_DEFAULT);

        $ok = MdUsuario::mdlActualizarClavePerfil($usuarioId, $nuevoHash);

        if (!$ok) {
            return [
                'success' => false,
                'mensaje' => 'No se pudo actualizar la clave.'
            ];
        }

        return [
            'success' => true,
            'mensaje' => 'Clave actualizada correctamente.'
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
