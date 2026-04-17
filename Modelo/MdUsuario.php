<?php
// /Modelo/MdUsuario.php
require_once "Conexion.php";

class MdUsuario {

    static public function mdlMostrarUsuarios($tabla, $item, $valor) {
        if ($item != null) {
            try {
                $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = :$item");
                $stmt->bindParam(":".$item, $valor, PDO::PARAM_STR);
                $stmt->execute();
                return $stmt->fetch();
            } catch (Exception $e) {
                return "error";
            }
        } else {
            $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla");
            $stmt->execute();
            return $stmt->fetchAll();
        }
        $stmt = null;
    }
}