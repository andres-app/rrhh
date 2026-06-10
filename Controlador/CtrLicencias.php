<?php
// Controlador/CtrLicencias.php

if (!class_exists('MdLicencias')) {
    require_once dirname(__DIR__) . '/Modelo/MdLicencias.php';
}

class CtrLicencias
{
    public function ctrListarLicencias(): array
    {
        return MdLicencias::mdlListarLicencias();
    }

    public function ctrCrearLicencia(array $datos, ?array $archivo = null): array
    {
        /*
         * El parámetro $archivo se conserva por compatibilidad,
         * pero esta versión NO sube ni guarda documentos.
         */

        $validacion = $this->validarLicencia($datos);

        if (!$validacion['success']) {
            return $validacion;
        }

        $respuesta = MdLicencias::mdlCrearLicencia($validacion['data']);

        if ($respuesta) {
            return [
                'success' => true,
                'mensaje' => 'Licencia registrada correctamente.'
            ];
        }

        $errorBD = method_exists('MdLicencias', 'getUltimoError')
            ? MdLicencias::getUltimoError()
            : '';

        return [
            'success' => false,
            'mensaje' => 'No se pudo registrar la licencia.' . ($errorBD ? ' Error BD: ' . $errorBD : '')
        ];
    }

    public function ctrAnularLicencia(int $licenciaId): array
    {
        if ($licenciaId <= 0) {
            return [
                'success' => false,
                'mensaje' => 'Licencia no válida.'
            ];
        }

        $licencia = MdLicencias::mdlObtenerLicencia($licenciaId);

        if (!$licencia) {
            return [
                'success' => false,
                'mensaje' => 'La licencia seleccionada no existe.'
            ];
        }

        if (strtoupper((string)($licencia['estado'] ?? '')) === 'ANULADO') {
            return [
                'success' => false,
                'mensaje' => 'La licencia ya se encuentra anulada.'
            ];
        }

        $usuarioId = $this->obtenerUsuarioSesion();

        $respuesta = MdLicencias::mdlAnularLicencia($licenciaId, $usuarioId);

        if ($respuesta) {
            return [
                'success' => true,
                'mensaje' => 'Licencia anulada correctamente.'
            ];
        }

        $errorBD = method_exists('MdLicencias', 'getUltimoError')
            ? MdLicencias::getUltimoError()
            : '';

        return [
            'success' => false,
            'mensaje' => 'No se pudo anular la licencia.' . ($errorBD ? ' Error BD: ' . $errorBD : '')
        ];
    }

    public function ctrObtenerLicenciaActualPorColaborador(int $colabId): ?array
    {
        if ($colabId <= 0) {
            return null;
        }

        return MdLicencias::mdlObtenerLicenciaActualPorColaborador($colabId);
    }

    private function validarLicencia(array $datos): array
    {
        $colabId = (int)($datos['colab_id'] ?? 0);

        $tipoLicencia = $this->limpiarTexto($datos['tipo_licencia'] ?? '', 80);
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

        if ($tipoLicencia === '') {
            return [
                'success' => false,
                'mensaje' => 'Debes seleccionar el tipo de licencia.'
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

        if ($numeroDocumento === '') {
            $numeroDocumento = 'S/N';
        }

        if ($motivo === '') {
            $motivo = $tipoLicencia;
        }

        $diasCalendario = $this->calcularDiasCalendario($fechaInicio, $fechaFin);

        return [
            'success' => true,
            'data' => [
                'colab_id' => $colabId,
                'tipo_licencia' => $tipoLicencia,
                'numero_documento' => $numeroDocumento,
                'fecha_documento' => $fechaDocumento,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'dias_calendario' => $diasCalendario,
                'motivo' => $motivo,
                'observacion' => $observacion,
                'archivo_documento' => null,
                'estado' => 'VIGENTE',
                'creado_por' => $this->obtenerUsuarioSesion()
            ]
        ];
    }

    private function calcularDiasCalendario(string $inicio, string $fin): int
    {
        try {
            $fechaInicio = new DateTime($inicio);
            $fechaFin = new DateTime($fin);

            return ((int)$fechaInicio->diff($fechaFin)->format('%a')) + 1;
        } catch (Throwable $e) {
            return 0;
        }
    }

    private function normalizarFecha($fecha): ?string
    {
        $fecha = trim((string)($fecha ?? ''));

        if ($fecha === '') {
            return null;
        }

        $date = DateTime::createFromFormat('Y-m-d', $fecha);

        if ($date && $date->format('Y-m-d') === $fecha) {
            return $fecha;
        }

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
}