<?php
//Controlador/CtrPermisos.php
class CtrPermisos {

    public function ctrGuardarMatriz() {

        if (isset($_POST["actualizar_permisos"])) {
            
            $rolLogueado = strtolower(trim($_SESSION["user_role"] ?? ""));
            $rolesBase = MdPermisos::mdlMostrarRoles();
            $modulos = MdPermisos::mdlMostrarModulos();
            $exito = true;

            foreach ($rolesBase as $rol) {
                $rolNorm = strtolower(trim($rol));

                // 🛡️ Protección para no bloquearse a sí mismo
                if ($rolNorm === 'superadmin' || $rolNorm === $rolLogueado) {
                    continue; 
                }

                foreach ($modulos as $m) {
                    $id_mod = $m["id"];
                    $can_view = isset($_POST["permiso"][$rol][$id_mod]["ver"]) ? 1 : 0;
                    $can_edit = isset($_POST["permiso"][$rol][$id_mod]["editar"]) ? 1 : 0;

                    $datos = [
                        "rol"       => $rol,
                        "modulo_id" => (int)$id_mod,
                        "can_view"  => $can_view,
                        "can_edit"  => $can_edit
                    ];

                    if(!MdPermisos::mdlGuardarPermiso($datos)) {
                        $exito = false;
                    }
                }
            }

            // 🎨 Configuración de Toaster con Colores Suaves (Soft UI)
            if ($exito) {
                echo '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: "top-end",
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.onmouseenter = Swal.stopTimer;
                                toast.onmouseleave = Swal.resumeTimer;
                            }
                        });

                        Toast.fire({
                            icon: "success",
                            title: "¡Permisos actualizados!",
                            color: "#155724",          // Texto verde oscuro
                            background: "#d4edda",     // Fondo verde suave (Bootstrap success)
                            iconColor: "#28a745",      // Icono verde vibrante
                            customClass: {
                                popup: "border-0 shadow-sm rounded-lg"
                            }
                        });
                    });
                </script>';
            } else {
                echo '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: "top-end",
                            showConfirmButton: false,
                            timer: 4000
                        });

                        Toast.fire({
                            icon: "error",
                            title: "Hubo un problema al guardar",
                            color: "#721c24",          // Texto rojo oscuro
                            background: "#f8d7da",     // Fondo rojo suave (Bootstrap danger)
                            iconColor: "#dc3545"       // Icono rojo vibrante
                        });
                    });
                </script>';
            }
        }
    }
}