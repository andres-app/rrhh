<?php
// Activamos todos los errores para ver qué falla
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "<h2 style='color: green;'>1. El archivo test.php está funcionando...</h2>";

// Intentamos llamar a la vista directamente
$ruta_vista = __DIR__ . '/../Vista/modulos/colaborador/perfil.php';

if (file_exists($ruta_vista)) {
    echo "<h2 style='color: blue;'>2. ¡Encontré el archivo del perfil! Cargando diseño...</h2><hr>";
    require_once $ruta_vista;
} else {
    echo "<h2 style='color: red;'>ERROR: No encuentro el archivo en la ruta: $ruta_vista</h2>";
}
?>