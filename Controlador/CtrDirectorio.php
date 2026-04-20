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

    public function ctrActualizarPerfil()
    {
        // Solo aceptar AJAX con JSON
        if (
            ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' ||
            ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest'
        ) {
            http_response_code(403);
            echo json_encode(['success' => false, 'mensaje' => 'Acceso no permitido']);
            return;
        }

        // Leer body JSON
        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body || empty($body['id'])) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos inválidos']);
            return;
        }

        // Verificar que el usuario solo edite su propio perfil
        $idSesion = $_SESSION['user_id'] ?? null;
        if ((int)$body['id'] !== (int)$idSesion) {
            echo json_encode(['success' => false, 'mensaje' => 'Sin permiso']);
            return;
        }

        $resultado = MdDirectorio::mdlActualizarPerfil($body);

        header('Content-Type: application/json');
        echo json_encode($resultado);
    }
}
