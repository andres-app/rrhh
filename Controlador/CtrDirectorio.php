<?php
// /Controlador/CtrDirectorio.php

class CtrDirectorio {

    /*=============================================
    MOSTRAR DIRECTORIO (El método que falta)
    =============================================*/
    public function ctrMostrarDirectorio() {
        
        // Llamamos al modelo para obtener los datos
        $respuesta = MdDirectorio::mdlMostrarDirectorio();
        return $respuesta;
        
    }

    /*=============================================
    VER PERFIL INDIVIDUAL
    =============================================*/
    public function ctrVerPerfil($id) {
        if (!$id || !is_numeric($id)) return false;

        $respuesta = MdDirectorio::mdlObtenerPerfilCompleto($id);
        return $respuesta;
    }
}