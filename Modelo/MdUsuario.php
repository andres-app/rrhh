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
}
