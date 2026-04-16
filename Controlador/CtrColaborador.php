<?php
// /Controlador/CtrColaborador.php
require_once __DIR__ . '/../Modelo/ModColaborador.php';

class CtrColaborador {

    // Mostrar la vista del perfil
    static public function verPerfil() {
        // Asumimos que el ID del usuario está en la sesión tras el login
        $usuario_id = $_SESSION['user_id']; 
        
        // Pedimos los datos al modelo
        $datosPerfil = ModColaborador::mdlObtenerPerfil($usuario_id);

        // Incluimos la vista (el HTML con Tailwind que hicimos antes)
        // La vista podrá usar la variable $datosPerfil
        require_once __DIR__ . '/../Vista/modulos/colaborador/perfil.php';
    }

    // Procesar el formulario de "Actualizar Dato"
    static public function procesarActualizacion() {
        if(isset($_POST['campo_a_modificar'])) {
            
            $colaborador_id = $_SESSION['user_id'];
            $directorio_uploads = __DIR__ . '/../Public/uploads/sustentos/';

            // Lógica básica para subir el PDF/Imagen del sustento
            $ruta_sustento = "";
            if(isset($_FILES['archivo_sustento']) && $_FILES['archivo_sustento']['tmp_name'] != ""){
                $nombre_archivo = $colaborador_id . "_" . time() . "_" . $_FILES['archivo_sustento']['name'];
                $ruta_destino = $directorio_uploads . $nombre_archivo;
                
                if(move_uploaded_file($_FILES['archivo_sustento']['tmp_name'], $ruta_destino)){
                    $ruta_sustento = 'uploads/sustentos/' . $nombre_archivo; // Ruta relativa para la BD
                }
            }

            // Aquí harías lo mismo para $_FILES['declaracion_jurada']
            $ruta_dj = "uploads/dj/dj_firmada_ejemplo.pdf"; // Simulado por ahora

            // Preparamos el array para el modelo
            $datos = array(
                "colaborador_id" => $colaborador_id,
                "campo" => $_POST['campo_a_modificar'], // ej: 'N° de Hijos'
                "valor_nuevo" => $_POST['nuevo_valor'], // ej: '5'
                "sustento" => $ruta_sustento,
                "dj" => $ruta_dj
            );

            // Enviamos al modelo
            $respuesta = ModColaborador::mdlSolicitarCambio($datos);

            if($respuesta == "ok"){
                echo '<script>
                    alert("Solicitud enviada a RRHH correctamente.");
                    window.location = "'.BASE_URL.'/perfil";
                </script>';
            }
        }
    }
}