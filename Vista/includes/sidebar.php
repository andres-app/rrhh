<?php
// /Vista/includes/sidebar.php
?>
<aside class="w-64 bg-indigo-900 text-white flex flex-col hidden md:flex z-20">
    <div class="h-20 flex items-center justify-center border-b border-indigo-800">
        <span class="text-2xl font-black tracking-wider">HR<span class="text-indigo-400">Panel</span></span>
    </div>
    
    <nav class="flex-1 p-4 space-y-2">
        <a href="<?= BASE_URL ?>/perfil" class="flex items-center px-4 py-3 rounded-xl transition <?= ($menu_activo == 'perfil') ? 'bg-indigo-800 font-bold shadow-inner' : 'text-indigo-200 hover:bg-indigo-800' ?>">
            <span class="mr-3">👤</span> Mi Perfil
        </a>
        
        <a href="<?= BASE_URL ?>/rrhh/validaciones" class="flex items-center px-4 py-3 rounded-xl transition <?= ($menu_activo == 'validaciones') ? 'bg-indigo-800 font-bold shadow-inner' : 'text-indigo-200 hover:bg-indigo-800' ?>">
            <span class="mr-3">✅</span> Validaciones
        </a>
        
        <a href="<?= BASE_URL ?>/rrhh/directorio" class="flex items-center px-4 py-3 rounded-xl transition <?= ($menu_activo == 'directorio') ? 'bg-indigo-800 font-bold shadow-inner' : 'text-indigo-200 hover:bg-indigo-800' ?>">
            <span class="mr-3">👥</span> Directorio
        </a>
    </nav>

    <div class="p-4 border-t border-indigo-800">
        <div class="flex items-center">
            <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center font-bold">R</div>
            <div class="ml-3 text-sm">
                <p class="font-bold">Rafael Abanto</p>
                <a href="<?= BASE_URL ?>/logout" class="text-indigo-300 text-xs hover:text-white transition">Cerrar Sesión</a>
            </div>
        </div>
    </div>
</aside>