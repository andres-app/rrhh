<?php
// /Controlador/CtrDirectorio.php

class CtrDirectorio
{
    public function ctrMostrarDirectorio()
    {
        return MdDirectorio::mdlMostrarDirectorio();
    }

    public function ctrMostrarDirectorioExcel()
    {
        return MdDirectorio::mdlMostrarDirectorioExcel();
    }

    public function ctrMostrarDashboard()
    {
        return MdDirectorio::mdlObtenerResumenDashboard();
    }

    public function ctrVerPerfil($id = null)
    {
        $rolSesion = strtolower(trim($_SESSION['user_role'] ?? ''));
        $userId    = (int)($_SESSION['user_id'] ?? 0);

        if ($userId <= 0) {
            return false;
        }

        /*
     * CASO 1:
     * /perfil
     * Cualquier usuario logueado puede ver SU PROPIO perfil,
     * siempre que tenga un registro asociado en colab_maestro.usuario_id.
     */
        if ($id === null || $id === '' || $id === false) {
            return MdDirectorio::mdlObtenerPerfilPorUsuario($userId);
        }

        /*
     * CASO 2:
     * /rrhh/perfil_detalle/{id}
     * Solo RRHH/Admin/Superadmin pueden ver perfiles de otros colaboradores.
     */
        if (!in_array($rolSesion, ['superadmin', 'admin', 'rrhh'], true)) {
            return false;
        }

        if (!is_numeric($id) || (int)$id <= 0) {
            return false;
        }

        return MdDirectorio::mdlObtenerPerfilCompleto((int)$id);
    }

    public function ctrActualizarPerfil(array $body, ?array $archivo = null): array
    {
        if (!$body) {
            return [
                'success' => false,
                'mensaje' => 'Datos inválidos o incompletos'
            ];
        }

        $rolSesion = strtolower(trim($_SESSION['user_role'] ?? ''));
        $userId    = (int)($_SESSION['user_id'] ?? 0);

        if ($userId <= 0) {
            return [
                'success' => false,
                'mensaje' => 'Sesión inválida'
            ];
        }

        /*
         * COLABORADOR:
         * Ignoramos el ID que venga del payload por seguridad.
         * El perfil objetivo siempre será el vinculado al usuario logueado.
         */
        if ($rolSesion === 'colaborador') {

            $perfilSesion = MdDirectorio::mdlObtenerPerfilPorUsuario($userId);

            if (!$perfilSesion || empty($perfilSesion['id'])) {
                return [
                    'success' => false,
                    'mensaje' => 'No se encontró el perfil del colaborador'
                ];
            }

            $idObjetivo = (int)$perfilSesion['id'];

            $body['id'] = $idObjetivo;

            if (!$archivo || empty($archivo['tmp_name'])) {
                return [
                    'success' => false,
                    'mensaje' => 'Debe adjuntar un sustento (imagen o PDF) para continuar'
                ];
            }

            return MdDirectorio::mdlCrearSolicitudCambio($idObjetivo, $userId, $body, $archivo);
        }

        /*
         * ADMIN / RRHH / SUPERADMIN:
         * El ID del payload sí es válido porque corresponde a colab_maestro.id.
         */
        if (in_array($rolSesion, ['superadmin', 'admin', 'rrhh'], true)) {

            if (empty($body['id']) || (int)$body['id'] <= 0) {
                return [
                    'success' => false,
                    'mensaje' => 'Colaborador inválido'
                ];
            }

            $perfil = MdDirectorio::mdlObtenerPerfilCompleto((int)$body['id']);

            if (!$perfil) {
                return [
                    'success' => false,
                    'mensaje' => 'No se encontró el perfil del colaborador'
                ];
            }

            return MdDirectorio::mdlActualizarPerfil($body);
        }

        return [
            'success' => false,
            'mensaje' => 'No tienes permisos para realizar esta acción'
        ];
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

    public function ctrCrearColaborador(array $body): array
    {
        $rolSesion = strtolower(trim($_SESSION['user_role'] ?? ''));

        if (!in_array($rolSesion, ['superadmin', 'admin', 'rrhh'], true)) {
            return [
                'success' => false,
                'mensaje' => 'No tienes permisos para crear colaboradores'
            ];
        }

        if (empty(trim($body['dni'] ?? '')) || empty(trim($body['nombres_apellidos'] ?? ''))) {
            return [
                'success' => false,
                'mensaje' => 'DNI y nombres completos son obligatorios'
            ];
        }

        return MdDirectorio::mdlCrearColaborador($body);
    }
}
