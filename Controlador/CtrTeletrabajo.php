<?php
// Controlador/CtrTeletrabajo.php

if (!class_exists('MdTeletrabajo')) {
    require_once dirname(__DIR__) . '/Modelo/MdTeletrabajo.php';
}

class CtrTeletrabajo
{
    public function ctrListarTeletrabajo(): array
    {
        return MdTeletrabajo::mdlListarTeletrabajo();
    }

    public function ctrHistorialTeletrabajo(int $acuerdoId): array
    {
        if ($acuerdoId <= 0) {
            return [];
        }

        return MdTeletrabajo::mdlListarHistorialPorAcuerdo($acuerdoId);
    }

    public function ctrCrearAcuerdo(array $datos, ?array $archivo = null): array
    {
        /*
         * IMPORTANTE:
         * El parámetro $archivo se conserva solo por compatibilidad con la vista,
         * pero NO se usa, NO se sube y NO se guarda ningún documento.
         */

        $validacion = $this->validarDocumento($datos, false);

        if (!$validacion['success']) {
            return $validacion;
        }

        $respuesta = MdTeletrabajo::mdlCrearAcuerdo($validacion['data']);

        if ($respuesta) {
            return [
                'success' => true,
                'mensaje' => 'Acuerdo de trabajo remoto registrado correctamente.'
            ];
        }

        $errorBD = method_exists('MdTeletrabajo', 'getUltimoError')
            ? MdTeletrabajo::getUltimoError()
            : '';

        return [
            'success' => false,
            'mensaje' => 'No se pudo registrar el acuerdo de trabajo remoto.' . ($errorBD ? ' Error BD: ' . $errorBD : '')
        ];
    }

    public function ctrCrearAdenda(array $datos, ?array $archivo = null): array
    {
        /*
         * IMPORTANTE:
         * El parámetro $archivo se conserva solo por compatibilidad con la vista,
         * pero NO se usa, NO se sube y NO se guarda ningún documento.
         */

        $documentoPadreId = (int)($datos['documento_padre_id'] ?? 0);

        if ($documentoPadreId <= 0) {
            return [
                'success' => false,
                'mensaje' => 'No se encontró el acuerdo principal para registrar la adenda.'
            ];
        }

        $documentoPadre = MdTeletrabajo::mdlObtenerDocumento($documentoPadreId);

        if (!$documentoPadre) {
            return [
                'success' => false,
                'mensaje' => 'El acuerdo principal seleccionado no existe.'
            ];
        }

        if (strtoupper((string)($documentoPadre['estado'] ?? '')) === 'ANULADO') {
            return [
                'success' => false,
                'mensaje' => 'No se puede agregar una adenda a un documento anulado.'
            ];
        }

        /*
         * Si el usuario eligió una adenda como documento padre,
         * se obtiene siempre el acuerdo principal real.
         */
        $tipoRegistroPadre = strtoupper((string)($documentoPadre['tipo_registro'] ?? ''));

        $acuerdoId = $tipoRegistroPadre === 'ACUERDO'
            ? (int)$documentoPadre['id']
            : (int)($documentoPadre['documento_padre_id'] ?? 0);

        if ($acuerdoId <= 0) {
            return [
                'success' => false,
                'mensaje' => 'No se pudo identificar el acuerdo principal.'
            ];
        }

        /*
         * La adenda siempre pertenece al mismo colaborador del acuerdo principal.
         */
        $datos['colab_id'] = (int)$documentoPadre['colab_id'];
        $datos['documento_padre_id'] = $acuerdoId;

        $validacion = $this->validarDocumento($datos, true);

        if (!$validacion['success']) {
            return $validacion;
        }

        $respuesta = MdTeletrabajo::mdlCrearAdenda($validacion['data']);

        if ($respuesta) {
            return [
                'success' => true,
                'mensaje' => 'Adenda de trabajo remoto registrada correctamente.'
            ];
        }

        $errorBD = method_exists('MdTeletrabajo', 'getUltimoError')
            ? MdTeletrabajo::getUltimoError()
            : '';

        return [
            'success' => false,
            'mensaje' => 'No se pudo registrar la adenda de trabajo remoto.' . ($errorBD ? ' Error BD: ' . $errorBD : '')
        ];
    }

    public function ctrAnularDocumento(int $documentoId): array
    {
        if ($documentoId <= 0) {
            return [
                'success' => false,
                'mensaje' => 'Documento no válido.'
            ];
        }

        $documento = MdTeletrabajo::mdlObtenerDocumento($documentoId);

        if (!$documento) {
            return [
                'success' => false,
                'mensaje' => 'El documento seleccionado no existe.'
            ];
        }

        if (strtoupper((string)($documento['estado'] ?? '')) === 'ANULADO') {
            return [
                'success' => false,
                'mensaje' => 'El documento ya se encuentra anulado.'
            ];
        }

        $usuarioId = $this->obtenerUsuarioSesion();

        $respuesta = MdTeletrabajo::mdlAnularDocumento($documentoId, $usuarioId);

        if ($respuesta) {
            return [
                'success' => true,
                'mensaje' => 'Documento anulado correctamente.'
            ];
        }

        $errorBD = method_exists('MdTeletrabajo', 'getUltimoError')
            ? MdTeletrabajo::getUltimoError()
            : '';

        return [
            'success' => false,
            'mensaje' => 'No se pudo anular el documento.' . ($errorBD ? ' Error BD: ' . $errorBD : '')
        ];
    }

    private function validarDocumento(array $datos, bool $esAdenda): array
    {
        $colabId = (int)($datos['colab_id'] ?? 0);
        $documentoPadreId = (int)($datos['documento_padre_id'] ?? 0);

        /*
         * En tu tabla colab_trabajo_remoto:
         * numero_documento = varchar(100)
         */
        $numeroDocumento = $this->limpiarTexto($datos['numero_documento'] ?? '', 100);

        $fechaDocumento = $this->normalizarFecha($datos['fecha_documento'] ?? null);
        $fechaInicio = $this->normalizarFecha($datos['fecha_inicio'] ?? null);
        $fechaFin = $this->normalizarFecha($datos['fecha_fin'] ?? null);

        $motivo = $this->limpiarTexto($datos['motivo'] ?? '', 255);
        $observacion = $this->limpiarTextoLargo($datos['observacion'] ?? '');

        if ($colabId <= 0) {
            return [
                'success' => false,
                'mensaje' => 'Debes seleccionar un colaborador.'
            ];
        }

        if ($esAdenda && $documentoPadreId <= 0) {
            return [
                'success' => false,
                'mensaje' => 'Debes seleccionar el acuerdo principal.'
            ];
        }

        if ($numeroDocumento === '') {
            return [
                'success' => false,
                'mensaje' => 'Debes ingresar el número de documento.'
            ];
        }

        if (!$fechaInicio) {
            return [
                'success' => false,
                'mensaje' => 'Debes ingresar una fecha de inicio válida.'
            ];
        }

        if (!$fechaFin) {
            return [
                'success' => false,
                'mensaje' => 'Debes ingresar una fecha de fin válida.'
            ];
        }

        if (strtotime($fechaFin) < strtotime($fechaInicio)) {
            return [
                'success' => false,
                'mensaje' => 'La fecha fin no puede ser menor que la fecha de inicio.'
            ];
        }

        if ($motivo === '') {
            $motivo = $esAdenda
                ? 'Ampliación de trabajo remoto temporal'
                : 'Trabajo remoto temporal';
        }

        return [
            'success' => true,
            'data' => [
                'colab_id' => $colabId,
                'documento_padre_id' => $esAdenda ? $documentoPadreId : null,
                'tipo_registro' => $esAdenda ? 'ADENDA' : 'ACUERDO',
                'numero_documento' => $numeroDocumento,
                'fecha_documento' => $fechaDocumento,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'modalidad' => 'TRABAJO_REMOTO_TEMPORAL',
                'motivo' => $motivo,
                'observacion' => $observacion,
                'archivo_documento' => null,
                'estado' => 'VIGENTE',
                'creado_por' => $this->obtenerUsuarioSesion()
            ]
        ];
    }

    private function normalizarFecha($fecha): ?string
    {
        $fecha = trim((string)($fecha ?? ''));

        if ($fecha === '') {
            return null;
        }

        /*
         * El input type="date" normalmente envía formato Y-m-d.
         */
        $date = DateTime::createFromFormat('Y-m-d', $fecha);

        if ($date && $date->format('Y-m-d') === $fecha) {
            return $fecha;
        }

        /*
         * Soporte opcional si algún campo llega como dd/mm/yyyy.
         */
        $date = DateTime::createFromFormat('d/m/Y', $fecha);

        if ($date) {
            return $date->format('Y-m-d');
        }

        return null;
    }

    private function limpiarTexto($valor, int $limite = 255): string
    {
        $valor = trim(strip_tags((string)($valor ?? '')));
        $valor = preg_replace('/\s+/', ' ', $valor);

        if (function_exists('mb_substr')) {
            return mb_substr($valor, 0, $limite, 'UTF-8');
        }

        return substr($valor, 0, $limite);
    }

    private function limpiarTextoLargo($valor): string
    {
        $valor = trim(strip_tags((string)($valor ?? '')));
        $valor = preg_replace("/\r\n|\r|\n/", "\n", $valor);
        $valor = preg_replace('/[ \t]+/', ' ', $valor);

        if (function_exists('mb_substr')) {
            return mb_substr($valor, 0, 5000, 'UTF-8');
        }

        return substr($valor, 0, 5000);
    }

    private function obtenerUsuarioSesion(): ?int
    {
        $usuarioId = (int)(
            $_SESSION['user_id']
            ?? $_SESSION['id_usuario']
            ?? $_SESSION['usuario_id']
            ?? 0
        );

        return $usuarioId > 0 ? $usuarioId : null;
    }

    public function ctrObtenerTeletrabajoActualPorColaborador(int $colabId): ?array
{
    if ($colabId <= 0) {
        return null;
    }

    return MdTeletrabajo::mdlObtenerTeletrabajoActualPorColaborador($colabId);
}
}
