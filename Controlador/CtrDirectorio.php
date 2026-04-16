<?php
// /Controladores/CtrDirectorio.php

class CtrDirectorio {

    public function ctrMostrarDirectorio() {
        $respuesta = MdDirectorio::mdlMostrarDirectorio();
        return $respuesta;
    }

    // Para la vista de perfil individual (tipo Facebook)
    public function ctrVerPerfil($id) {
        return MdDirectorio::mdlObtenerPerfilCompleto($id);
    }
}