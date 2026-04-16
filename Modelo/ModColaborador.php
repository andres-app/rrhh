<?php
// /Modelo/ModColaborador.php
require_once "Conexion.php";

class ModColaborador {

    // 1. Obtener los datos del perfil
    static public function mdlObtenerPerfil($usuario_id) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM perfiles WHERE usuario_id = :id");
        $stmt->bindParam(":id", $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC); 
    }

    // 2. Guardar la solicitud de actualización para que RRHH la revise
    static public function mdlSolicitarCambio($datos) {
        $stmt = Conexion::conectar()->prepare("INSERT INTO validaciones (colaborador_id, campo_modificado, valor_nuevo, documento_sustento, declaracion_jurada) VALUES (:colaborador_id, :campo, :valor, :sustento, :dj)");

        $stmt->bindParam(":colaborador_id", $datos['colaborador_id'], PDO::PARAM_INT);
        $stmt->bindParam(":campo", $datos['campo'], PDO::PARAM_STR);
        $stmt->bindParam(":valor", $datos['valor_nuevo'], PDO::PARAM_STR);
        $stmt->bindParam(":sustento", $datos['sustento'], PDO::PARAM_STR);
        $stmt->bindParam(":dj", $datos['dj'], PDO::PARAM_STR);

        if($stmt->execute()){
            return "ok";
        } else {
            return "error";
        }
    }
}