<?php
// /Modelo/MdUsuario.php

require_once __DIR__ . "/Conexion.php";

class MdUsuario
{
    public static function mdlMostrarUsuarios($tabla, $item, $valor)
    {
        try {
            $pdo = Conexion::conectar();

            $tablaPermitida = 'usuarios';
            $columnasPermitidas = [
                'id',
                'username',
                'rol',
                'estado'
            ];

            if ($tabla !== $tablaPermitida) {
                return false;
            }

            if ($item !== null && !in_array($item, $columnasPermitidas, true)) {
                return false;
            }

            $columnaClave = self::mdlDetectarColumnaClave($pdo);

            if (!$columnaClave) {
                error_log('No se encontró columna de clave en tabla usuarios.');
                return false;
            }

            if ($item !== null) {
                $sql = "
                    SELECT 
                        u.*,
                        u.`$columnaClave` AS password,
                        c.nombres_apellidos
                    FROM usuarios u
                    LEFT JOIN colab_maestro c ON u.id = c.usuario_id
                    WHERE u.`$item` = :valor
                    LIMIT 1
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(":valor", $valor, PDO::PARAM_STR);
                $stmt->execute();

                return $stmt->fetch(PDO::FETCH_ASSOC);
            }

            $sql = "
                SELECT 
                    u.*,
                    u.`$columnaClave` AS password,
                    c.nombres_apellidos
                FROM usuarios u
                LEFT JOIN colab_maestro c ON u.id = c.usuario_id
                ORDER BY u.id DESC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Error en MdUsuario::mdlMostrarUsuarios: ' . $e->getMessage());
            return false;
        }
    }

    private static function mdlDetectarColumnaClave(PDO $pdo): ?string
    {
        $posiblesColumnas = [
            'password',
            'clave',
            'contrasena',
            'contraseña',
            'password_hash'
        ];

        foreach ($posiblesColumnas as $columna) {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM usuarios LIKE :columna");
            $stmt->bindParam(':columna', $columna, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                return $columna;
            }
        }

        return null;
    }

    public static function mdlObtenerUsuarioParaClave($usuarioId)
    {
        try {
            $pdo = Conexion::conectar();

            $columnaClave = self::mdlDetectarColumnaClave($pdo);

            if (!$columnaClave) {
                error_log('No se encontró columna de clave en tabla usuarios.');
                return false;
            }

            $sql = "
                SELECT 
                    id,
                    username,
                    `$columnaClave` AS password_hash,
                    cambiar_clave
                FROM usuarios 
                WHERE id = :id 
                LIMIT 1
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Error en MdUsuario::mdlObtenerUsuarioParaClave: ' . $e->getMessage());
            return false;
        }
    }

    public static function mdlActualizarClavePerfil($usuarioId, $nuevoHash)
    {
        try {
            $pdo = Conexion::conectar();

            $columnaClave = self::mdlDetectarColumnaClave($pdo);

            if (!$columnaClave) {
                error_log('No se encontró columna de clave en tabla usuarios.');
                return false;
            }

            $sql = "
                UPDATE usuarios 
                SET 
                    `$columnaClave` = :clave,
                    cambiar_clave = 0
                WHERE id = :id
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':clave', $nuevoHash, PDO::PARAM_STR);
            $stmt->bindParam(':id', $usuarioId, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (Throwable $e) {
            error_log('Error en MdUsuario::mdlActualizarClavePerfil: ' . $e->getMessage());
            return false;
        }
    }

    public static function mdlCrearUsuario($username, $passwordHash, $rol)
{
    try {
        $pdo = Conexion::conectar();

        $columnaClave = self::mdlDetectarColumnaClave($pdo);

        if (!$columnaClave) {
            return false;
        }

        $sql = "
            INSERT INTO usuarios 
                (username, `$columnaClave`, rol, estado, cambiar_clave)
            VALUES 
                (:username, :password, :rol, 1, 1)
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':password', $passwordHash, PDO::PARAM_STR);
        $stmt->bindParam(':rol', $rol, PDO::PARAM_STR);

        return $stmt->execute();
    } catch (Throwable $e) {
        error_log('Error en MdUsuario::mdlCrearUsuario: ' . $e->getMessage());
        return false;
    }
}

public static function mdlEditarUsuario($id, $username, $rol, $passwordHash = null)
{
    try {
        $pdo = Conexion::conectar();

        $columnaClave = self::mdlDetectarColumnaClave($pdo);

        if (!$columnaClave) {
            return false;
        }

        if ($passwordHash !== null && $passwordHash !== '') {
            $sql = "
                UPDATE usuarios
                SET 
                    username = :username,
                    rol = :rol,
                    `$columnaClave` = :password
                WHERE id = :id
            ";
        } else {
            $sql = "
                UPDATE usuarios
                SET 
                    username = :username,
                    rol = :rol
                WHERE id = :id
            ";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':rol', $rol, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($passwordHash !== null && $passwordHash !== '') {
            $stmt->bindParam(':password', $passwordHash, PDO::PARAM_STR);
        }

        return $stmt->execute();
    } catch (Throwable $e) {
        error_log('Error en MdUsuario::mdlEditarUsuario: ' . $e->getMessage());
        return false;
    }
}

public static function mdlCambiarEstadoUsuario($id, $estado)
{
    try {
        $pdo = Conexion::conectar();

        $sql = "
            UPDATE usuarios
            SET estado = :estado
            WHERE id = :id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    } catch (Throwable $e) {
        error_log('Error en MdUsuario::mdlCambiarEstadoUsuario: ' . $e->getMessage());
        return false;
    }
}
}