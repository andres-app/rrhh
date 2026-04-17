<?php
// /Controlador/CtrUsuario.php

class CtrUsuario {

    /**
     * Maneja el proceso de inicio de sesión
     */
    public function ctrLogin() {
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
            if ($respuesta && $respuesta["username"] == $_POST["login_username"] && 
                password_verify($_POST["login_password"], $respuesta["password"])) {
                
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
                     * Esto evita que el Colaborador caiga en rrhh/dashboard (donde no tiene acceso)
                     */
                    if ($respuesta["rol"] == "superadmin" || $respuesta["rol"] == "admin") {
                        // Perfiles administrativos van al panel de control
                        echo '<script>window.location = "rrhh/dashboard";</script>';
                    } 
                    else if ($respuesta["rol"] == "colaborador") {
                        // Colaboradores van directamente a su perfil personal
                        echo '<script>window.location = "perfil";</script>';
                    } 
                    else {
                        // Ruta por defecto para otros roles
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
    static public function ctrLogout() {
        session_destroy();
        echo '<script>window.location = "login";</script>';
    }
}