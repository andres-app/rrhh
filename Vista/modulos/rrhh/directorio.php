<?php
// 1. Configuramos las variables para el Header y Sidebar
$titulo_pagina = "Directorio de Personal - RRHH";
$menu_activo = "directorio";

// 2. Traemos la cabecera y el menú lateral
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
    
    <header class="h-20 bg-white shadow-sm flex items-center px-8 justify-between z-10">
        <h1 class="text-2xl font-bold text-slate-800">Directorio de Personal</h1>
        <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-bold shadow-md transition flex items-center">
            <span class="mr-2">+</span> Nuevo Empleado
        </button>
    </header>

    <div class="p-8 flex-1 overflow-y-auto">
        <p>Tabla de empleados aquí...</p>
    </div>

</main>

<?php 
// 4. Cerramos el HTML
require_once __DIR__ . '/../../includes/footer.php'; 
?>