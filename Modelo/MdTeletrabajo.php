<?php
// Modelo/MdTeletrabajo.php

if (!class_exists('Conexion')) {
    $posiblesRutas = [
        __DIR__ . '/Conexion.php',
        __DIR__ . '/conexion.php',
        dirname(__DIR__) . '/Modelo/Conexion.php',
        dirname(__DIR__) . '/Modelo/conexion.php',
    ];

    foreach ($posiblesRutas as $ruta) {
        if (file_exists($ruta)) {
            require_once $ruta;
            break;
        }
    }
}

class MdTeletrabajo
{
    private static $tabla = 'colab_trabajo_remoto';
    private static $ultimoError = '';

    public static function getUltimoError(): string
    {
        return self::$ultimoError;
    }

    private static function setError($e): void
    {
        self::$ultimoError = $e instanceof Throwable ? $e->getMessage() : (string)$e;
        error_log('MdTeletrabajo ERROR: ' . self::$ultimoError);
    }

    private static function conectar(): PDO
    {
        $pdo = Conexion::conectar();

        if (!$pdo instanceof PDO) {
            throw new Exception('La conexión no devolvió una instancia PDO válida.');
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("SET NAMES utf8mb4");

        return $pdo;
    }

    public static function mdlListarTeletrabajo(): array
    {
        try {
            self::$ultimoError = '';

            $pdo = self::conectar();

            /*
         * Muestra 1 documento por cada acuerdo principal.
         *
         * Prioridad:
         * 1. Documento vigente hoy.
         * 2. Si aún no inicia, muestra el próximo documento más cercano.
         * 3. Si ya vencieron todos, muestra el último vencido.
         *
         * Así NO muestra simplemente la última adenda por fecha_fin.
         */
            $sql = "
            SELECT
                tr.id,
                tr.colab_id,
                tr.documento_padre_id,
                COALESCE(tr.documento_padre_id, tr.id) AS acuerdo_id,
                tr.tipo_registro,
                tr.numero_documento,
                tr.fecha_documento,
                tr.fecha_inicio,
                tr.fecha_fin,
                tr.modalidad,
                tr.motivo,
                tr.observacion,
                tr.archivo_documento,
                tr.estado,
                tr.creado_por,
                tr.actualizado_por,
                tr.created_at,
                tr.updated_at,

                CONCAT('Colaborador #', tr.colab_id) AS nombres_apellidos,
                '' AS dni,
                '' AS area,
                '' AS puesto_cas,
                'ACTIVO' AS situacion,
                CONCAT('Colaborador #', tr.colab_id) AS colaborador,
                '' AS puesto,

                CASE
                    WHEN tr.estado = 'ANULADO' THEN 'ANULADO'
                    WHEN CURDATE() < tr.fecha_inicio THEN 'POR_INICIAR'
                    WHEN CURDATE() BETWEEN tr.fecha_inicio AND tr.fecha_fin 
                         AND DATEDIFF(tr.fecha_fin, CURDATE()) <= 15 THEN 'POR_VENCER'
                    WHEN CURDATE() BETWEEN tr.fecha_inicio AND tr.fecha_fin THEN 'VIGENTE'
                    WHEN CURDATE() > tr.fecha_fin THEN 'VENCIDO'
                    ELSE 'SIN_FECHA'
                END AS estado_calculado

            FROM `" . self::$tabla . "` tr

            WHERE tr.estado <> 'ANULADO'
            AND tr.id = (
                SELECT x.id
                FROM `" . self::$tabla . "` x
                WHERE COALESCE(x.documento_padre_id, x.id) = COALESCE(tr.documento_padre_id, tr.id)
                AND x.estado <> 'ANULADO'
                ORDER BY
                    CASE
                        WHEN CURDATE() BETWEEN x.fecha_inicio AND x.fecha_fin THEN 1
                        WHEN x.fecha_inicio > CURDATE() THEN 2
                        WHEN x.fecha_fin < CURDATE() THEN 3
                        ELSE 4
                    END ASC,

                    CASE
                        WHEN CURDATE() BETWEEN x.fecha_inicio AND x.fecha_fin THEN x.fecha_inicio
                    END DESC,

                    CASE
                        WHEN x.fecha_inicio > CURDATE() THEN x.fecha_inicio
                    END ASC,

                    CASE
                        WHEN x.fecha_fin < CURDATE() THEN x.fecha_fin
                    END DESC,

                    x.id DESC
                LIMIT 1
            )

            ORDER BY
                CASE
                    WHEN CURDATE() BETWEEN tr.fecha_inicio AND tr.fecha_fin 
                         AND DATEDIFF(tr.fecha_fin, CURDATE()) <= 15 THEN 1
                    WHEN CURDATE() BETWEEN tr.fecha_inicio AND tr.fecha_fin THEN 2
                    WHEN tr.fecha_inicio > CURDATE() THEN 3
                    WHEN tr.fecha_fin < CURDATE() THEN 4
                    ELSE 5
                END ASC,
                tr.fecha_inicio ASC,
                tr.id DESC
        ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            self::setError($e);
            return [];
        }
    }

    public static function mdlCrearAcuerdo(array $datos): bool
    {
        try {
            self::$ultimoError = '';

            $pdo = self::conectar();

            $sql = "
                INSERT INTO `" . self::$tabla . "` (
                    colab_id,
                    documento_padre_id,
                    tipo_registro,
                    numero_documento,
                    fecha_documento,
                    fecha_inicio,
                    fecha_fin,
                    modalidad,
                    motivo,
                    observacion,
                    archivo_documento,
                    estado,
                    creado_por,
                    created_at
                ) VALUES (
                    :colab_id,
                    NULL,
                    'ACUERDO',
                    :numero_documento,
                    :fecha_documento,
                    :fecha_inicio,
                    :fecha_fin,
                    'TRABAJO_REMOTO_TEMPORAL',
                    :motivo,
                    :observacion,
                    NULL,
                    'VIGENTE',
                    :creado_por,
                    NOW()
                )
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->bindValue(':colab_id', (int)$datos['colab_id'], PDO::PARAM_INT);
            self::bindNullableString($stmt, ':numero_documento', $datos['numero_documento'] ?? null);
            self::bindNullableString($stmt, ':fecha_documento', $datos['fecha_documento'] ?? null);
            self::bindNullableString($stmt, ':fecha_inicio', $datos['fecha_inicio'] ?? null);
            self::bindNullableString($stmt, ':fecha_fin', $datos['fecha_fin'] ?? null);
            self::bindNullableString($stmt, ':motivo', $datos['motivo'] ?? null);
            self::bindNullableString($stmt, ':observacion', $datos['observacion'] ?? null);
            self::bindNullableInt($stmt, ':creado_por', $datos['creado_por'] ?? null);

            $stmt->execute();

            return (int)$pdo->lastInsertId() > 0;
        } catch (Throwable $e) {
            self::setError($e);
            return false;
        }
    }

    public static function mdlCrearAdenda(array $datos): bool
    {
        try {
            self::$ultimoError = '';

            $pdo = self::conectar();

            $sql = "
                INSERT INTO `" . self::$tabla . "` (
                    colab_id,
                    documento_padre_id,
                    tipo_registro,
                    numero_documento,
                    fecha_documento,
                    fecha_inicio,
                    fecha_fin,
                    modalidad,
                    motivo,
                    observacion,
                    archivo_documento,
                    estado,
                    creado_por,
                    created_at
                ) VALUES (
                    :colab_id,
                    :documento_padre_id,
                    'ADENDA',
                    :numero_documento,
                    :fecha_documento,
                    :fecha_inicio,
                    :fecha_fin,
                    'TRABAJO_REMOTO_TEMPORAL',
                    :motivo,
                    :observacion,
                    NULL,
                    'VIGENTE',
                    :creado_por,
                    NOW()
                )
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->bindValue(':colab_id', (int)$datos['colab_id'], PDO::PARAM_INT);
            $stmt->bindValue(':documento_padre_id', (int)$datos['documento_padre_id'], PDO::PARAM_INT);
            self::bindNullableString($stmt, ':numero_documento', $datos['numero_documento'] ?? null);
            self::bindNullableString($stmt, ':fecha_documento', $datos['fecha_documento'] ?? null);
            self::bindNullableString($stmt, ':fecha_inicio', $datos['fecha_inicio'] ?? null);
            self::bindNullableString($stmt, ':fecha_fin', $datos['fecha_fin'] ?? null);
            self::bindNullableString($stmt, ':motivo', $datos['motivo'] ?? null);
            self::bindNullableString($stmt, ':observacion', $datos['observacion'] ?? null);
            self::bindNullableInt($stmt, ':creado_por', $datos['creado_por'] ?? null);

            $stmt->execute();

            return (int)$pdo->lastInsertId() > 0;
        } catch (Throwable $e) {
            self::setError($e);
            return false;
        }
    }

    public static function mdlObtenerDocumento(int $id): ?array
    {
        try {
            if ($id <= 0) {
                return null;
            }

            $pdo = self::conectar();

            $sql = "
                SELECT
                    tr.*,
                    COALESCE(tr.documento_padre_id, tr.id) AS acuerdo_id
                FROM `" . self::$tabla . "` tr
                WHERE tr.id = :id
                LIMIT 1
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        } catch (Throwable $e) {
            self::setError($e);
            return null;
        }
    }

    public static function mdlAnularDocumento(int $id, ?int $usuarioId = null): bool
    {
        try {
            if ($id <= 0) {
                return false;
            }

            $pdo = self::conectar();

            $documento = self::mdlObtenerDocumento($id);

            if (!$documento) {
                self::$ultimoError = 'El documento seleccionado no existe.';
                return false;
            }

            $tipoRegistro = strtoupper((string)($documento['tipo_registro'] ?? ''));

            if ($tipoRegistro === 'ACUERDO') {
                $sql = "
                    UPDATE `" . self::$tabla . "`
                    SET
                        estado = 'ANULADO',
                        actualizado_por = :actualizado_por,
                        updated_at = NOW()
                    WHERE id = :id
                       OR documento_padre_id = :id
                ";
            } else {
                $sql = "
                    UPDATE `" . self::$tabla . "`
                    SET
                        estado = 'ANULADO',
                        actualizado_por = :actualizado_por,
                        updated_at = NOW()
                    WHERE id = :id
                ";
            }

            $stmt = $pdo->prepare($sql);
            self::bindNullableInt($stmt, ':actualizado_por', $usuarioId);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (Throwable $e) {
            self::setError($e);
            return false;
        }
    }

    public static function mdlListarHistorialPorAcuerdo(int $acuerdoId): array
    {
        try {
            if ($acuerdoId <= 0) {
                return [];
            }

            $pdo = self::conectar();

            $sql = "
            SELECT
                tr.id,
                tr.colab_id,
                tr.documento_padre_id,
                COALESCE(tr.documento_padre_id, tr.id) AS acuerdo_id,
                tr.tipo_registro,
                tr.numero_documento,
                tr.fecha_documento,
                tr.fecha_inicio,
                tr.fecha_fin,
                tr.modalidad,
                tr.motivo,
                tr.observacion,
                tr.archivo_documento,
                tr.estado,
                tr.creado_por,
                tr.actualizado_por,
                tr.created_at,
                tr.updated_at,

                CASE
                    WHEN tr.estado = 'ANULADO' THEN 'ANULADO'
                    WHEN CURDATE() < tr.fecha_inicio THEN 'POR_INICIAR'
                    WHEN CURDATE() BETWEEN tr.fecha_inicio AND tr.fecha_fin 
                         AND DATEDIFF(tr.fecha_fin, CURDATE()) <= 15 THEN 'POR_VENCER'
                    WHEN CURDATE() BETWEEN tr.fecha_inicio AND tr.fecha_fin THEN 'VIGENTE'
                    WHEN CURDATE() > tr.fecha_fin THEN 'VENCIDO'
                    ELSE 'SIN_FECHA'
                END AS estado_calculado

            FROM `" . self::$tabla . "` tr
            WHERE tr.id = :acuerdo_id
               OR tr.documento_padre_id = :acuerdo_id
            ORDER BY
                CASE 
                    WHEN tr.tipo_registro = 'ACUERDO' THEN 1
                    ELSE 2
                END ASC,
                tr.fecha_inicio ASC,
                tr.id ASC
        ";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':acuerdo_id', $acuerdoId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            self::setError($e);
            return [];
        }
    }
    private static function bindNullableString(PDOStatement $stmt, string $param, $valor): void
    {
        if ($valor === null || trim((string)$valor) === '') {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
            return;
        }

        $stmt->bindValue($param, trim((string)$valor), PDO::PARAM_STR);
    }

    private static function bindNullableInt(PDOStatement $stmt, string $param, $valor): void
    {
        if ($valor === null || $valor === '' || (int)$valor <= 0) {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
            return;
        }

        $stmt->bindValue($param, (int)$valor, PDO::PARAM_INT);
    }

    public static function mdlObtenerTeletrabajoActualPorColaborador(int $colabId): ?array
    {
        try {
            if ($colabId <= 0) {
                return null;
            }

            $pdo = self::conectar();

            /*
         * Prioridad:
         * 1. Documento vigente hoy.
         * 2. Si todavía no inicia, el próximo.
         * 3. Si ya venció, el último vencido.
         */
            $sql = "
            SELECT
                tr.id,
                tr.colab_id,
                tr.documento_padre_id,
                COALESCE(tr.documento_padre_id, tr.id) AS acuerdo_id,
                tr.tipo_registro,
                tr.numero_documento,
                tr.fecha_documento,
                tr.fecha_inicio,
                tr.fecha_fin,
                tr.modalidad,
                tr.motivo,
                tr.observacion,
                tr.estado,

                CASE
                    WHEN tr.estado = 'ANULADO' THEN 'ANULADO'
                    WHEN CURDATE() < tr.fecha_inicio THEN 'POR_INICIAR'
                    WHEN CURDATE() BETWEEN tr.fecha_inicio AND tr.fecha_fin 
                         AND DATEDIFF(tr.fecha_fin, CURDATE()) <= 15 THEN 'POR_VENCER'
                    WHEN CURDATE() BETWEEN tr.fecha_inicio AND tr.fecha_fin THEN 'VIGENTE'
                    WHEN CURDATE() > tr.fecha_fin THEN 'VENCIDO'
                    ELSE 'SIN_FECHA'
                END AS estado_calculado

            FROM `" . self::$tabla . "` tr
            WHERE tr.colab_id = :colab_id
              AND tr.estado <> 'ANULADO'
            ORDER BY
                CASE
                    WHEN CURDATE() BETWEEN tr.fecha_inicio AND tr.fecha_fin THEN 1
                    WHEN tr.fecha_inicio > CURDATE() THEN 2
                    WHEN tr.fecha_fin < CURDATE() THEN 3
                    ELSE 4
                END ASC,
                CASE
                    WHEN CURDATE() BETWEEN tr.fecha_inicio AND tr.fecha_fin THEN tr.fecha_inicio
                END DESC,
                CASE
                    WHEN tr.fecha_inicio > CURDATE() THEN tr.fecha_inicio
                END ASC,
                CASE
                    WHEN tr.fecha_fin < CURDATE() THEN tr.fecha_fin
                END DESC,
                tr.id DESC
            LIMIT 1
        ";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':colab_id', $colabId, PDO::PARAM_INT);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        } catch (Throwable $e) {
            self::setError($e);
            return null;
        }
    }
}
