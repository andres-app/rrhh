<?php
// /Controlador/CtrColaborador.php
require_once __DIR__ . '/../Modelo/MdDirectorio.php';

class CtrColaborador {

    public static function verPerfil() {
        if(!isset($_SESSION['user_id'])) {
            echo '<script>window.location = "login";</script>';
            return;
        }

        // Usamos el modelo que ya tienes definido y que funciona
        $idUsuario = $_SESSION['user_id'];
        
        // IMPORTANTE: En tu modelo MdDirectorio, el método pide el ID del colab.
        // Si el user_id coincide con el id de colab_maestro, esto funcionará directo:
        $datos = MdDirectorio::mdlObtenerPerfilCompleto($idUsuario);

        require_once __DIR__ . '/../Vista/modulos/colaborador/perfil.php';
    }
}