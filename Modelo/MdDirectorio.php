<?php
// /Modelos/MdDirectorio.php

require_once "Conexion.php";

class MdDirectorio {

    public static function mdlMostrarDirectorio() {
        $stmt = Conexion::conectar()->prepare("
            SELECT 
                m.id, 
                m.nombres_apellidos, 
                m.dni, 
                m.celular,
                l.puesto_cas, 
                l.area, 
                l.correo_institucional,
                l.situacion
            FROM colab_maestro m
            INNER JOIN colab_laboral l ON m.id = l.colab_id
            ORDER BY m.nombres_apellidos ASC
        ");

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlObtenerPerfilCompleto($colabId) {
        // Método para traer TODO lo de un colaborador (Maestro + Laboral)
        $stmt = Conexion::conectar()->prepare("
            SELECT m.*, l.* FROM colab_maestro m 
            JOIN colab_laboral l ON m.id = l.colab_id 
            WHERE m.id = :id
        ");
        $stmt->bindParam(":id", $colabId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}