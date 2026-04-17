<?php
// /Controlador/CtrUsuario.php

class CtrUsuario {

    public function ctrLogin() {
        if (isset($_POST["login_username"])) {
            
            $tabla = "usuarios";
            $item = "username";
            $valor = $_POST["login_username"];

            // Llamada al modelo con nomenclatura Md
            $respuesta = MdUsuario::mdlMostrarUsuarios($tabla, $item, $valor);

            // Verificación segura
            if ($respuesta && $respuesta["username"] == $_POST["login_username"] && password_verify($_POST["login_password"], $respuesta["password"])) {
                
                if ($respuesta["estado"] == 1) {
                    // Seteo de variables de sesión
                    $_SESSION["validarSesion"] = "ok";
                    $_SESSION["user_id"] = $respuesta["id"];
                    $_SESSION["username"] = $respuesta["username"];
                    $_SESSION["user_role"] = $respuesta["rol"];

                    // Redirección al dashboard de RRHH
                    echo '<script>window.location = "rrhh/dashboard";</script>';
                } else {
                    echo '<div class="mt-4 bg-orange-50 border-l-4 border-orange-500 p-3 text-orange-700 text-sm">Esta cuenta está desactivada.</div>';
                }

            } else {
                echo '<div class="mt-4 bg-red-50 border-l-4 border-red-500 p-3 text-red-700 text-sm">Usuario o contraseña incorrectos.</div>';
            }
        }
    }
}