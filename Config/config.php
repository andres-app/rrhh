<?php
// /Config/config.php

// 1. DEFINICIÓN DE RUTA FÍSICA (Añade esta línea aquí)
// Esto detecta automáticamente la carpeta raíz del sistema
define('ROOT_PATH', dirname(__DIR__) . '/');

// URL base de tu proyecto
define('BASE_URL', 'https://rrhh.legrand.pe');

// Credenciales de Base de Datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'u274409976_rrhh');
define('DB_USER', 'u274409976_rrhh');
define('DB_PASS', 'Dev2804751$$$');

// Zona horaria
date_default_timezone_set('America/Lima');