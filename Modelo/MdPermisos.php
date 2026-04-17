<?php
// /Modelo/MdPermisos.php
require_once "Conexion.php";

class MdPermisos {

    // Mostrar todos los módulos disponibles
    static public function mdlMostrarModulos() {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM modulos ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Obtener permisos específicos de un rol y módulo
    static public function mdlObtenerPermiso($rol, $moduloId) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM permisos WHERE rol = :rol AND modulo_id = :id");
        $stmt->execute(["rol" => $rol, "id" => $moduloId]);
        return $stmt->fetch();
    }

    // Guardar o actualizar permiso
    static public function mdlGuardarPermiso($datos) {
        try {
            $db = Conexion::conectar();
            // Esta consulta intenta insertar, pero si el par (rol, modulo_id) ya existe, 
            // solo actualiza los valores de can_view y can_edit.
            $stmt = $db->prepare("INSERT INTO permisos (rol, modulo_id, can_view, can_edit) 
                                  VALUES (:rol, :id, :v, :e) 
                                  ON DUPLICATE KEY UPDATE can_view = :v, can_edit = :e");
            
            return $stmt->execute([
                "rol" => $datos["rol"],
                "id"  => $datos["modulo_id"],
                "v"   => $datos["can_view"],
                "e"   => $datos["can_edit"]
            ]);
        } catch (Exception $e) {
            return false;
        }
    }
}