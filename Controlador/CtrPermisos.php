<?php
// /Controlador/CtrPermisos.php

class CtrPermisos {

    public function ctrGuardarMatriz() {
        if (isset($_POST["actualizar_permisos"])) {
            
            $rolLogueado = $_SESSION["rol"] ?? $_SESSION["perfil"] ?? null;
            $rolesBase = MdPermisos::mdlMostrarRoles();
            $modulos = MdPermisos::mdlMostrarModulos();
            $exito = true;

            foreach ($rolesBase as $rol) {
                
                // PROTECCIÓN: Si el usuario no es superadmin, no puede tocar al superadmin ni a sí mismo
                if ($rolLogueado !== 'superadmin') {
                    if ($rol === 'superadmin' || $rol === $rolLogueado) {
                        continue; // Ignorar estos registros en el guardado
                    }
                }

                foreach ($modulos as $m) {
                    $id_mod = $m["id"];
                    
                    // Solo procesamos los roles que permitimos en el bucle superior
                    $can_view = isset($_POST["permiso"][$rol][$id_mod]["ver"]) ? 1 : 0;
                    $can_edit = isset($_POST["permiso"][$rol][$id_mod]["editar"]) ? 1 : 0;

                    $datos = [
                        "rol" => $rol,
                        "modulo_id" => (int)$id_mod,
                        "can_view" => $can_view,
                        "can_edit" => $can_edit
                    ];

                    if(!MdPermisos::mdlGuardarPermiso($datos)) {
                        $exito = false;
                    }
                }
            }

            if ($exito) {
                echo '<script>
                    alert("¡Permisos actualizados correctamente!");
                    window.location.href = "permisos";
                </script>';
                exit;
            }
        }
    }
}