<?php
// Modelo/MdPermisos.php

require_once __DIR__ . "/Conexion.php";

class MdPermisos
{
    static public function mdlMostrarRoles()
    {
        try {
            $stmt = Conexion::conectar()->prepare("
                SELECT DISTINCT rol 
                FROM permisos 
                ORDER BY rol ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        } catch (Exception $e) {
            return [];
        }
    }

    static public function mdlMostrarModulos()
    {
        try {
            $stmt = Conexion::conectar()->prepare("
                SELECT * 
                FROM modulos 
                ORDER BY id ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    static public function mdlObtenerPermisosAsociativos()
    {
        try {
            $stmt = Conexion::conectar()->prepare("
                SELECT * 
                FROM permisos
            ");
            $stmt->execute();
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $matriz = [];

            foreach ($resultados as $fila) {
                $rol = strtolower(trim($fila['rol']));
                $moduloId = (int)$fila['modulo_id'];

                $matriz[$rol][$moduloId] = [
                    'ver'    => (int)$fila['can_view'],
                    'editar' => (int)$fila['can_edit']
                ];
            }

            return $matriz;

        } catch (Exception $e) {
            return [];
        }
    }

    static public function mdlGuardarPermiso($datos)
    {
        try {
            $db = Conexion::conectar();

            $stmt = $db->prepare("
                INSERT INTO permisos 
                    (rol, modulo_id, can_view, can_edit) 
                VALUES 
                    (:rol, :modulo_id, :can_view, :can_edit) 
                ON DUPLICATE KEY UPDATE 
                    can_view = VALUES(can_view), 
                    can_edit = VALUES(can_edit)
            ");

            return $stmt->execute([
                "rol"       => strtolower(trim($datos["rol"])),
                "modulo_id" => (int)$datos["modulo_id"],
                "can_view"  => (int)$datos["can_view"],
                "can_edit"  => (int)$datos["can_edit"]
            ]);

        } catch (Exception $e) {
            return false;
        }
    }

    static public function mdlTienePermisoModulo($rol, $rutaBase)
    {
        try {
            $rol = strtolower(trim($rol));
            $rutaBase = strtolower(trim($rutaBase));

            if ($rol === '' || $rutaBase === '') {
                return false;
            }

            if ($rol === 'superadmin') {
                return true;
            }

            $db = Conexion::conectar();

            $stmt = $db->prepare("
                SELECT 
                    p.can_view
                FROM permisos p
                INNER JOIN modulos m 
                    ON m.id = p.modulo_id
                WHERE LOWER(TRIM(p.rol)) = :rol
                AND LOWER(TRIM(m.ruta_base)) = :ruta_base
                LIMIT 1
            ");

            $stmt->execute([
                "rol"       => $rol,
                "ruta_base" => $rutaBase
            ]);

            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            return $resultado && (int)$resultado["can_view"] === 1;

        } catch (Exception $e) {
            return false;
        }
    }

    static public function mdlPuedeEditarModulo($rol, $rutaBase)
    {
        try {
            $rol = strtolower(trim($rol));
            $rutaBase = strtolower(trim($rutaBase));

            if ($rol === '' || $rutaBase === '') {
                return false;
            }

            if ($rol === 'superadmin') {
                return true;
            }

            $db = Conexion::conectar();

            $stmt = $db->prepare("
                SELECT 
                    p.can_edit
                FROM permisos p
                INNER JOIN modulos m 
                    ON m.id = p.modulo_id
                WHERE LOWER(TRIM(p.rol)) = :rol
                AND LOWER(TRIM(m.ruta_base)) = :ruta_base
                LIMIT 1
            ");

            $stmt->execute([
                "rol"       => $rol,
                "ruta_base" => $rutaBase
            ]);

            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            return $resultado && (int)$resultado["can_edit"] === 1;

        } catch (Exception $e) {
            return false;
        }
    }
}