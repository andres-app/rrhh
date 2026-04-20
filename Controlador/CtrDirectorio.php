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
        // 1. Validación básica
        if (!$body || empty($body['id'])) {
            return ['success' => false, 'mensaje' => 'Datos inválidos o incompletos'];
        }

        $idObjetivo = (int)$body['id'];
        $idSesion   = (int)($_SESSION['user_id'] ?? 0);
        $rolSesion  = strtolower(trim($_SESSION['user_role'] ?? ''));

        // 2. Roles con permiso total
        $rolesConAccesoTotal = ['rrhh', 'admin', 'superadmin'];

        $puedeEditar = false;

        // ✔ Puede editar su propio perfil
        if ($idObjetivo === $idSesion) {
            $puedeEditar = true;
        }

        // ✔ Puede editar si es RRHH/Admin
        if (in_array($rolSesion, $rolesConAccesoTotal, true)) {
            $puedeEditar = true;
        }

        if (!$puedeEditar) {
            return ['success' => false, 'mensaje' => 'No tienes permiso para editar este perfil'];
        }

        // 3. Guardar cambios
        $resultado = MdDirectorio::mdlActualizarPerfil($body);

        return $resultado;
    }
}
