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
        // 1. Validación de datos (Lógica del Controlador)
        if (!$body || empty($body['id'])) {
            return ['success' => false, 'mensaje' => 'Datos inválidos o incompletos'];
        }

        // 2. Validación de seguridad (El usuario solo edita su propio ID)
        $idSesion = $_SESSION['user_id'] ?? null;
        if ((int)$body['id'] !== (int)$idSesion) {
            return ['success' => false, 'mensaje' => 'No tienes permiso para editar este perfil'];
        }

        // 3. Llamada al Modelo
        $resultado = MdDirectorio::mdlActualizarPerfil($body);

        // 4. Retornamos el array de respuesta al Router
        return $resultado;
    }
}