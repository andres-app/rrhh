<?php
// /Controlador/CtrPermisos.php

class CtrPermisos {

    public function ctrGuardarMatriz() {
        if (isset($_POST["actualizar_permisos"])) {
            
            $matriz = $_POST["permiso"]; 
            $exito = true;

            foreach ($matriz as $rol => $modulos) {
                foreach ($modulos as $moduloId => $acciones) {
                    
                    $datos = [
                        "rol" => $rol,
                        "modulo_id" => (int)$moduloId,
                        "can_view" => isset($acciones["ver"]) ? 1 : 0,
                        "can_edit" => isset($acciones["editar"]) ? 1 : 0
                    ];

                    // Llamada al modelo corregido
                    if (!MdPermisos::mdlGuardarPermiso($datos)) {
                        $exito = false;
                    }
                }
            }

            if ($exito) {
                // Usamos un script que detenga la ejecución y redireccione
                echo '<script>
                    alert("¡Permisos actualizados con éxito!");
                    window.location.href = "'.BASE_URL.'/configuracion/permisos"; 
                </script>';
                exit; // IMPORTANTE para detener el flujo
            }
        }
    }
}