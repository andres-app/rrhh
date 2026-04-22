<?php
// /Controlador/CtrDirectorio.php

class CtrDirectorio
{
    /*=============================================
    MOSTRAR DIRECTORIO
    =============================================*/
    public function ctrMostrarDirectorio()
    {
        return MdDirectorio::mdlMostrarDirectorio();
    }

    /*=============================================
    DASHBOARD DINÁMICO
    =============================================*/
    public function ctrMostrarDashboard()
    {
        return MdDirectorio::mdlObtenerResumenDashboard();
    }

    /*=============================================
    VER PERFIL INDIVIDUAL
    =============================================*/
    public function ctrVerPerfil($id)
    {
        if (!$id || !is_numeric($id)) {
            return false;
        }

        return MdDirectorio::mdlObtenerPerfilCompleto((int)$id);
    }

    public function ctrActualizarPerfil($body)
    {
        if (!$body || empty($body['id'])) {
            return ['success' => false, 'mensaje' => 'Datos inválidos o incompletos'];
        }

        $idObjetivo = (int)$body['id'];
        $idSesion   = (int)($_SESSION['user_id'] ?? 0);
        $rolSesion  = strtolower(trim($_SESSION['user_role'] ?? ''));

        $rolesConAccesoTotal = ['rrhh', 'admin', 'superadmin'];

        $puedeEditar = false;

        if ($idObjetivo === $idSesion) {
            $puedeEditar = true;
        }

        if (in_array($rolSesion, $rolesConAccesoTotal, true)) {
            $puedeEditar = true;
        }

        if (!$puedeEditar) {
            return ['success' => false, 'mensaje' => 'No tienes permiso para editar este perfil'];
        }

        return MdDirectorio::mdlActualizarPerfil($body);
    }
}