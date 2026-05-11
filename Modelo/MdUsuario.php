<?php
// /Modelo/MdUsuario.php
require_once "Conexion.php";

class MdUsuario
{

    static public function mdlMostrarUsuarios($tabla, $item, $valor)
    {
        if ($item != null) {
            try {
                // Hacemos un JOIN para traer los nombres desde colab_maestro
                $stmt = Conexion::conectar()->prepare("
                SELECT u.*, c.nombres_apellidos 
                FROM $tabla u
                LEFT JOIN colab_maestro c ON u.id = c.usuario_id
                WHERE u.$item = :$item
            ");

                $stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);
                $stmt->execute();
                return $stmt->fetch();
            } catch (Exception $e) {
                return "error";
            }
        } else {
            // Para listar todos los usuarios (si lo necesitas)
            $stmt = Conexion::conectar()->prepare("SELECT u.*, c.nombres_apellidos FROM $tabla u LEFT JOIN colab_maestro c ON u.id = c.usuario_id");
            $stmt->execute();
            return $stmt->fetchAll();
        }
    }
    private static function mdlDetectarColumnaClave(PDO $pdo)
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
            return false;
        }

        $sql = "SELECT id, `$columnaClave` AS password_hash 
                FROM usuarios 
                WHERE id = :id 
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('Error al obtener usuario para clave: ' . $e->getMessage());
        return false;
    }
}

public static function mdlActualizarClavePerfil($usuarioId, $nuevoHash)
{
    try {
        $pdo = Conexion::conectar();

        $columnaClave = self::mdlDetectarColumnaClave($pdo);

        if (!$columnaClave) {
            return false;
        }

        $sql = "UPDATE usuarios 
                SET `$columnaClave` = :clave 
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':clave', $nuevoHash, PDO::PARAM_STR);
        $stmt->bindParam(':id', $usuarioId, PDO::PARAM_INT);

        return $stmt->execute();
    } catch (Throwable $e) {
        error_log('Error al actualizar clave: ' . $e->getMessage());
        return false;
    }
}
}
