<?php
// /Modelo/Conexion.php
require_once __DIR__ . '/../Config/config.php';

class Conexion {
    static public function conectar() {
        try {
            $link = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
            $link->exec("set names utf8");
            // Que PDO lance excepciones si hay errores (muy útil para depurar)
            $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $link;
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
}