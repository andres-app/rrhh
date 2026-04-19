<?php
//Modelo/MdPermisos.php
require_once "Conexion.php";

class MdPermisos {

    static public function mdlMostrarRoles() {
        $stmt = Conexion::conectar()->prepare("SELECT DISTINCT rol FROM permisos ORDER BY rol ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    static public function mdlMostrarModulos() {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM modulos ORDER BY id ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    static public function mdlObtenerPermisosAsociativos() {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM permisos");
        $stmt->execute();
        $resultados = $stmt->fetchAll();
        
        $matriz = [];
        foreach ($resultados as $fila) {
            $matriz[$fila['rol']][$fila['modulo_id']] = [
                'ver' => $fila['can_view'],
                'editar' => $fila['can_edit']
            ];
        }
        return $matriz;
    }

    static public function mdlGuardarPermiso($datos) {
        try {
            $db = Conexion::conectar();
            $stmt = $db->prepare("INSERT INTO permisos (rol, modulo_id, can_view, can_edit) 
                                  VALUES (:rol, :id, :v, :e) 
                                  ON DUPLICATE KEY UPDATE can_view = :v, can_edit = :e");
            return $stmt->execute([
                "rol" => $datos["rol"],
                "id"  => $datos["modulo_id"],
                "v"   => $datos["can_view"],
                "e"   => $datos["can_edit"]
            ]);
        } catch (Exception $e) { return false; }
    }
}