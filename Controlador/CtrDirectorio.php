<?php
// /Controlador/CtrDirectorio.php

class CtrDirectorio
{
    public function ctrMostrarDirectorio()
    {
        return MdDirectorio::mdlMostrarDirectorio();
    }

    public function ctrMostrarDashboard()
    {
        return MdDirectorio::mdlObtenerResumenDashboard();
    }

    public function ctrVerPerfil($id)
    {
        if (!$id || !is_numeric($id)) {
            return false;
        }

        return MdDirectorio::mdlObtenerPerfilCompleto((int)$id);
    }

    public function ctrActualizarPerfil(array $body, ?array $archivo = null): array
    {
        if (!$body || empty($body['id'])) {
            return ['success' => false, 'mensaje' => 'Datos inválidos o incompletos'];
        }

        $idObjetivo = (int)$body['id'];
        $rolSesion  = strtolower(trim($_SESSION['user_role'] ?? ''));
        $userId     = (int)($_SESSION['user_id'] ?? 0);

        if ($idObjetivo <= 0 || $userId <= 0) {
            return ['success' => false, 'mensaje' => 'Sesión o colaborador inválido'];
        }

        if ($rolSesion === 'colaborador') {
            return MdDirectorio::mdlCrearSolicitudCambio($idObjetivo, $userId, $body, $archivo);
        }

        if (in_array($rolSesion, ['superadmin', 'admin', 'rrhh'], true)) {
            return MdDirectorio::mdlActualizarPerfil($body);
        }

        return ['success' => false, 'mensaje' => 'No tienes permisos para realizar esta acción'];
    }

    public function ctrAprobarSolicitudCambio(int $solicitudId): array
    {
        $rolSesion = strtolower(trim($_SESSION['user_role'] ?? ''));
        $userId    = (int)($_SESSION['user_id'] ?? 0);

        if (!in_array($rolSesion, ['superadmin', 'admin', 'rrhh'], true)) {
            return [
                'success' => false,
                'mensaje' => 'No tienes permiso para aprobar solicitudes'
            ];
        }

        if ($solicitudId <= 0) {
            return [
                'success' => false,
                'mensaje' => 'Solicitud inválida'
            ];
        }

        return MdDirectorio::mdlAprobarSolicitudCambio($solicitudId, $userId);
    }

    public function ctrRechazarSolicitudCambio(int $solicitudId, string $motivo): array
    {
        $rolSesion = strtolower(trim($_SESSION['user_role'] ?? ''));
        $userId    = (int)($_SESSION['user_id'] ?? 0);

        if (!in_array($rolSesion, ['superadmin', 'admin', 'rrhh'], true)) {
            return [
                'success' => false,
                'mensaje' => 'No tienes permiso para rechazar solicitudes'
            ];
        }

        if ($solicitudId <= 0) {
            return [
                'success' => false,
                'mensaje' => 'Solicitud inválida'
            ];
        }

        $motivo = trim($motivo);
        if ($motivo === '') {
            return [
                'success' => false,
                'mensaje' => 'Debes ingresar el motivo del rechazo'
            ];
        }

        return MdDirectorio::mdlRechazarSolicitudCambio($solicitudId, $userId, $motivo);
    }
}