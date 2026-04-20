<?php
// /Controlador/CtrDirectorio.php

class CtrDirectorio
{

    /*=============================================
    MOSTRAR DIRECTORIO (El método que falta)
    =============================================*/
    public function ctrMostrarDirectorio()
    {

        // Llamamos al modelo para obtener los datos
        $respuesta = MdDirectorio::mdlMostrarDirectorio();
        return $respuesta;
    }

    /*=============================================
    VER PERFIL INDIVIDUAL
    =============================================*/
    public function ctrVerPerfil($id)
    {
        if (!$id || !is_numeric($id)) return false;

        $respuesta = MdDirectorio::mdlObtenerPerfilCompleto($id);
        return $respuesta;
    }

public function ctrActualizarPerfil($body)
    {
        // 1. Ya no validamos acá porque el router (index.php) ya lo hizo.
        // 2. Solo llamamos al modelo para procesar los datos.
        $resultado = MdDirectorio::mdlActualizarPerfil($body);

        // 3. RETORNAMOS el array. 
        // ¡IMPORTANTE! No uses "echo" ni "header" aquí, 
        // porque el echo ya se hace en el index.php
        return $resultado;
    }
}
