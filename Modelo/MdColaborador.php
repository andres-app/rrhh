<?php
require_once "Conexion.php";

class ModColaborador {
    // Obtener todos los datos del colaborador por su ID de usuario
    static public function mdlObtenerPerfil($idUsuario) {
        try {
            $stmt = Conexion::conectar()->prepare("
                SELECT * FROM colab_maestro 
                WHERE usuario_id = :id 
                LIMIT 1
            ");
            $stmt->bindParam(":id", $idUsuario, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }

    // Registrar una solicitud de cambio (para que RRHH la apruebe)
    static public function mdlSolicitarCambio($datos) {
        $stmt = Conexion::conectar()->prepare("
            INSERT INTO solicitudes_cambio (colaborador_id, campo, valor_nuevo, ruta_sustento, ruta_dj, estado) 
            VALUES (:id, :campo, :valor, :sustento, :dj, 'pendiente')
        ");
        $stmt->bindParam(":id", $datos["colaborador_id"], PDO::PARAM_INT);
        $stmt->bindParam(":campo", $datos["campo"], PDO::PARAM_STR);
        $stmt->bindParam(":valor", $datos["valor_nuevo"], PDO::PARAM_STR);
        $stmt->bindParam(":sustento", $datos["sustento"], PDO::PARAM_STR);
        $stmt->bindParam(":dj", $datos["dj"], PDO::PARAM_STR);

        if($stmt->execute()) { return "ok"; } else { return "error"; }
    }
}