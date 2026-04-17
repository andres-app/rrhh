<?php
// /Controlador/CtrUsuario.php

class CtrUsuario {

    public function ctrLogin() {
        if (isset($_POST["login_username"])) {
            
            $tabla = "usuarios";
            $item = "username";
            $valor = $_POST["login_username"];

            $respuesta = MdUsuario::mdlMostrarUsuarios($tabla, $item, $valor);

            if ($respuesta && $respuesta["username"] == $_POST["login_username"] && password_verify($_POST["login_password"], $respuesta["password"])) {
                
                if ($respuesta["estado"] == 1) {
                    // Seteo de variables de sesión
                    $_SESSION["validarSesion"] = "ok";
                    $_SESSION["user_id"] = $respuesta["id"];
                    $_SESSION["username"] = $respuesta["username"];
                    $_SESSION["user_role"] = $respuesta["rol"];

                    // --- REDIRECCIÓN CORREGIDA ---
                    // Verificamos el rol antes de decidir a dónde enviarlo
                    if ($respuesta["rol"] == "superadmin" || $respuesta["rol"] == "admin") {
                        // Admins van al Dashboard de RRHH
                        echo '<script>window.location = "rrhh/dashboard";</script>';
                    } else {
                        // Colaboradores van directamente a su Perfil (donde sí tienen permiso)
                        echo '<script>window.location = "perfil";</script>';
                    }
                    exit; // Importante para detener la ejecución del script

                } else {
                    echo '<div class="mt-4 bg-orange-50 border-l-4 border-orange-500 p-3 text-orange-700 text-sm">Esta cuenta está desactivada.</div>';
                }

            } else {
                echo '<div class="mt-4 bg-red-50 border-l-4 border-red-500 p-3 text-red-700 text-sm">Usuario o contraseña incorrectos.</div>';
            }
        }
    }
}