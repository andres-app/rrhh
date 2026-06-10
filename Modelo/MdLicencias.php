<?php
// Modelo/MdLicencias.php

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

class MdLicencias
{
    private static $tabla = 'colab_licencias';
    private static $ultimoError = '';

    public static function getUltimoError(): string
    {
        return self::$ultimoError;
    }

    private static function setError($e): void
    {
        self::$ultimoError = $e instanceof Throwable ? $e->getMessage() : (string)$e;
        error_log('MdLicencias ERROR: ' . self::$ultimoError);
    }

    private static function conectar(): PDO
    {
        $pdo = Conexion::conectar();

        if (!$pdo instanceof PDO) {
            throw new Exception('La conexión no devolvió una instancia PDO válida.');
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("SET NAMES utf8mb4");

        self::asegurarTabla($pdo);

        return $pdo;
    }

    private static function asegurarTabla(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `" . self::$tabla . "` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `colab_id` INT(11) NOT NULL,
                `tipo_licencia` VARCHAR(80) NOT NULL,
                `numero_documento` VARCHAR(100) DEFAULT NULL,
                `fecha_documento` DATE DEFAULT NULL,
                `fecha_inicio` DATE NOT NULL,
                `fecha_fin` DATE NOT NULL,
                `dias_calendario` INT(11) DEFAULT NULL,
                `motivo` VARCHAR(255) DEFAULT NULL,
                `observacion` TEXT DEFAULT NULL,
                `archivo_documento` VARCHAR(255) DEFAULT NULL,
                `estado` VARCHAR(20) NOT NULL DEFAULT 'VIGENTE',
                `creado_por` INT(11) DEFAULT NULL,
                `actualizado_por` INT(11) DEFAULT NULL,
                `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_colab_id` (`colab_id`),
                KEY `idx_tipo_licencia` (`tipo_licencia`),
                KEY `idx_fecha_inicio` (`fecha_inicio`),
                KEY `idx_fecha_fin` (`fecha_fin`),
                KEY `idx_estado` (`estado`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public static function mdlListarLicencias(): array
    {
        try {
            self::$ultimoError = '';

            $pdo = self::conectar();

            $sql = "
                SELECT
                    l.id,
                    l.colab_id,
                    l.tipo_licencia,
                    l.numero_documento,
                    l.fecha_documento,
                    l.fecha_inicio,
                    l.fecha_fin,
                    l.dias_calendario,
                    l.motivo,
                    l.observacion,
                    l.archivo_documento,
                    l.estado,
                    l.creado_por,
                    l.actualizado_por,
                    l.created_at,
                    l.updated_at,

                    CONCAT('Colaborador #', l.colab_id) AS nombres_apellidos,
                    '' AS dni,
                    '' AS area,
                    '' AS puesto_cas,
                    'ACTIVO' AS situacion,
                    CONCAT('Colaborador #', l.colab_id) AS colaborador,
                    '' AS puesto,

                    CASE
                        WHEN l.estado = 'ANULADO' THEN 'ANULADO'
                        WHEN CURDATE() < l.fecha_inicio THEN 'POR_INICIAR'
                        WHEN CURDATE() BETWEEN l.fecha_inicio AND l.fecha_fin 
                             AND DATEDIFF(l.fecha_fin, CURDATE()) <= 15 THEN 'POR_VENCER'
                        WHEN CURDATE() BETWEEN l.fecha_inicio AND l.fecha_fin THEN 'VIGENTE'
                        WHEN CURDATE() > l.fecha_fin THEN 'VENCIDO'
                        ELSE 'SIN_FECHA'
                    END AS estado_calculado

                FROM `" . self::$tabla . "` l
                ORDER BY
                    CASE
                        WHEN l.estado = 'ANULADO' THEN 5
                        WHEN CURDATE() BETWEEN l.fecha_inicio AND l.fecha_fin 
                             AND DATEDIFF(l.fecha_fin, CURDATE()) <= 15 THEN 1
                        WHEN CURDATE() BETWEEN l.fecha_inicio AND l.fecha_fin THEN 2
                        WHEN l.fecha_inicio > CURDATE() THEN 3
                        WHEN l.fecha_fin < CURDATE() THEN 4
                        ELSE 6
                    END ASC,
                    l.fecha_inicio ASC,
                    l.id DESC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            self::setError($e);
            return [];
        }
    }

    public static function mdlCrearLicencia(array $datos): bool
    {
        try {
            self::$ultimoError = '';

            $pdo = self::conectar();

            $sql = "
                INSERT INTO `" . self::$tabla . "` (
                    colab_id,
                    tipo_licencia,
                    numero_documento,
                    fecha_documento,
                    fecha_inicio,
                    fecha_fin,
                    dias_calendario,
                    motivo,
                    observacion,
                    archivo_documento,
                    estado,
                    creado_por,
                    created_at
                ) VALUES (
                    :colab_id,
                    :tipo_licencia,
                    :numero_documento,
                    :fecha_documento,
                    :fecha_inicio,
                    :fecha_fin,
                    :dias_calendario,
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
            self::bindNullableString($stmt, ':tipo_licencia', $datos['tipo_licencia'] ?? null);
            self::bindNullableString($stmt, ':numero_documento', $datos['numero_documento'] ?? null);
            self::bindNullableString($stmt, ':fecha_documento', $datos['fecha_documento'] ?? null);
            self::bindNullableString($stmt, ':fecha_inicio', $datos['fecha_inicio'] ?? null);
            self::bindNullableString($stmt, ':fecha_fin', $datos['fecha_fin'] ?? null);
            self::bindNullableInt($stmt, ':dias_calendario', $datos['dias_calendario'] ?? null);
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

    public static function mdlObtenerLicencia(int $id): ?array
    {
        try {
            if ($id <= 0) {
                return null;
            }

            $pdo = self::conectar();

            $sql = "
                SELECT *
                FROM `" . self::$tabla . "`
                WHERE id = :id
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

    public static function mdlAnularLicencia(int $id, ?int $usuarioId = null): bool
    {
        try {
            if ($id <= 0) {
                return false;
            }

            $pdo = self::conectar();

            $sql = "
                UPDATE `" . self::$tabla . "`
                SET
                    estado = 'ANULADO',
                    actualizado_por = :actualizado_por,
                    updated_at = NOW()
                WHERE id = :id
                LIMIT 1
            ";

            $stmt = $pdo->prepare($sql);
            self::bindNullableInt($stmt, ':actualizado_por', $usuarioId);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (Throwable $e) {
            self::setError($e);
            return false;
        }
    }

    public static function mdlObtenerLicenciaActualPorColaborador(int $colabId): ?array
    {
        try {
            if ($colabId <= 0) {
                return null;
            }

            $pdo = self::conectar();

            $sql = "
                SELECT
                    l.*,
                    CASE
                        WHEN l.estado = 'ANULADO' THEN 'ANULADO'
                        WHEN CURDATE() < l.fecha_inicio THEN 'POR_INICIAR'
                        WHEN CURDATE() BETWEEN l.fecha_inicio AND l.fecha_fin 
                             AND DATEDIFF(l.fecha_fin, CURDATE()) <= 15 THEN 'POR_VENCER'
                        WHEN CURDATE() BETWEEN l.fecha_inicio AND l.fecha_fin THEN 'VIGENTE'
                        WHEN CURDATE() > l.fecha_fin THEN 'VENCIDO'
                        ELSE 'SIN_FECHA'
                    END AS estado_calculado
                FROM `" . self::$tabla . "` l
                WHERE l.colab_id = :colab_id
                  AND l.estado <> 'ANULADO'
                ORDER BY
                    CASE
                        WHEN CURDATE() BETWEEN l.fecha_inicio AND l.fecha_fin THEN 1
                        WHEN l.fecha_inicio > CURDATE() THEN 2
                        WHEN l.fecha_fin < CURDATE() THEN 3
                        ELSE 4
                    END ASC,
                    CASE
                        WHEN CURDATE() BETWEEN l.fecha_inicio AND l.fecha_fin THEN l.fecha_inicio
                    END DESC,
                    CASE
                        WHEN l.fecha_inicio > CURDATE() THEN l.fecha_inicio
                    END ASC,
                    CASE
                        WHEN l.fecha_fin < CURDATE() THEN l.fecha_fin
                    END DESC,
                    l.id DESC
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
}