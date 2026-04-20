<?php
// /Vista/includes/sidebar.php

/**
 * Función auxiliar para verificar permisos en la vista.
 * Nota: 'check_access' debe estar disponible (ya la definiste en index.php)
 */
function tieneAcceso($modulo)
{
    if (!isset($_SESSION['user_role'])) return false;

    // El Superadmin suele tener acceso a todo por defecto
    if ($_SESSION['user_role'] === 'superadmin') return true;

    // Consultamos la función que ya creaste en tu Router (index.php)
    $permisos = check_access($modulo, $_SESSION['user_role']);
    return (bool)$permisos['can_view'];
}

// Pre-calculamos accesos para no repetir llamadas
$accesoRRHH = tieneAcceso('rrhh');
$accesoConfig = tieneAcceso('configuracion');
$accesoPerfil = tieneAcceso('perfil');
$accesoDocs = tieneAcceso('documentos');
?>

<div class="md:hidden flex items-center justify-between bg-[#1a0505] p-4 shrink-0 z-20 border-b border-red-950/50">
    <div class="flex items-center">
        <div class="w-8 h-8 bg-red-800 rounded-lg flex items-center justify-center mr-3 shadow-lg shadow-red-900/40">
            <span class="text-white font-bold text-xl leading-none">P</span>
        </div>
        <span class="text-xl font-bold text-white tracking-wide">RRHH<span class="text-red-400">Panel</span></span>
    </div>
    <button id="btn-menu" class="text-red-200 hover:text-white focus:outline-none">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>
</div>

<div id="sidebar-overlay" class="fixed inset-0 bg-black/60 z-30 hidden backdrop-blur-sm transition-opacity opacity-0"></div>

<aside id="sidebar" class="w-64 bg-[#1a0505] text-red-100/70 flex flex-col z-40 border-r border-red-950 shadow-xl fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out h-full">

    <div class="h-20 flex items-center justify-between px-6 border-b border-red-950 shrink-0">
        <div class="flex items-center">
            <div class="w-8 h-8 bg-red-800 rounded-lg flex items-center justify-center mr-3 shadow-lg shadow-red-900/50">
                <span class="text-white font-bold text-xl leading-none">P</span>
            </div>
            <span class="text-xl font-bold text-white tracking-wide">RRHH<span class="text-red-500">Panel</span></span>
        </div>
        <button id="btn-close-menu" class="md:hidden text-red-300 hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto py-6 custom-scrollbar">

        <?php if ($accesoPerfil || $accesoDocs): ?>
            <div class="px-6 mb-2 text-[10px] font-bold text-red-400/50 uppercase tracking-widest">Mi Espacio</div>

            <?php if ($accesoPerfil): ?>
                <a href="<?= BASE_URL ?>/perfil" class="flex items-center px-6 py-3 transition-all duration-200 <?= ($menu_activo == 'perfil') ? 'bg-red-900/30 text-red-400 border-l-4 border-red-600' : 'hover:bg-red-900/20 hover:text-white border-l-4 border-transparent' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="font-medium text-sm">Mi Perfil</span>
                </a>
            <?php endif; ?>

            <?php if ($accesoDocs): ?>
                <a href="<?= BASE_URL ?>/documentos" class="flex items-center px-6 py-3 transition-all duration-200 <?= ($menu_activo == 'documentos') ? 'bg-red-900/30 text-red-400 border-l-4 border-red-600' : 'hover:bg-red-900/20 hover:text-white border-l-4 border-transparent' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="font-medium text-sm">Mis Documentos</span>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($accesoRRHH): ?>
            <div class="px-6 mt-8 mb-2 text-[10px] font-bold text-red-400/50 uppercase tracking-widest">Administración</div>

            <a href="<?= BASE_URL ?>/rrhh/dashboard" class="flex items-center px-6 py-3 transition-all duration-200 <?= ($menu_activo == 'dashboard') ? 'bg-red-900/30 text-red-400 border-l-4 border-red-600' : 'hover:bg-red-900/20 hover:text-white border-l-4 border-transparent' ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v10a2 2 0 01-2 2h-2a2 2 0 01-2-2v-10z"></path>
                </svg>
                <span class="font-medium text-sm">Dashboard</span>
            </a>

            <a href="<?= BASE_URL ?>/rrhh/validaciones" class="flex items-center px-6 py-3 transition-all duration-200 <?= ($menu_activo == 'validaciones') ? 'bg-red-900/30 text-red-400 border-l-4 border-red-600' : 'hover:bg-red-900/20 hover:text-white border-l-4 border-transparent' ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="font-medium text-sm flex-1">Validaciones</span>
            </a>

            <a href="<?= BASE_URL ?>/rrhh/directorio" class="flex items-center px-6 py-3 transition-all duration-200 <?= ($menu_activo == 'directorio') ? 'bg-red-900/30 text-red-400 border-l-4 border-red-600' : 'hover:bg-red-900/20 hover:text-white border-l-4 border-transparent' ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <span class="font-medium text-sm">Directorio</span>
            </a>
        <?php endif; ?>

        <?php if ($accesoConfig): ?>
            <div class="px-6 mt-8 mb-2 text-[10px] font-bold text-red-400/50 uppercase tracking-widest">Configuración</div>
            <a href="<?= BASE_URL ?>/configuracion" class="flex items-center px-6 py-3 transition-all duration-200 <?= ($menu_activo == 'configuracion') ? 'bg-red-900/30 text-red-400 border-l-4 border-red-600' : 'hover:bg-red-900/20 hover:text-white border-l-4 border-transparent' ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="font-medium text-sm">Permisos</span>
            </a>
        <?php endif; ?>

    </nav>

    <a href="<?= BASE_URL ?>/logout" class="flex items-center p-2 rounded-xl hover:bg-red-900/30 transition cursor-pointer group">
        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username'] ?? 'Usuario') ?>&background=880808&color=fff"
            class="w-9 h-9 rounded-full shadow-md border border-red-900 group-hover:border-red-500 transition-colors">

        <div class="ml-3 overflow-hidden flex-1">
            <p class="text-sm font-bold text-white truncate">
                <?= $_SESSION['nombre_completo'] ?>
            </p>
            <p class="text-[10px] text-red-400/60 uppercase font-medium">
                <?= $_SESSION['user_role'] ?>
            </p>
            <span class="text-xs text-red-400/70 hover:text-red-400 transition block">
                Cerrar sesión
            </span>
        </div>

        <svg class="w-4 h-4 text-red-700 group-hover:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
        </svg>
    </a>
</aside>

<style>
    /* Ocultar scrollbar para Chrome, Safari y Opera */
    .custom-scrollbar::-webkit-scrollbar {
        display: none;
    }
    /* Ocultar scrollbar para IE, Edge y Firefox */
    .custom-scrollbar {
        -ms-overflow-style: none; /* IE and Edge */
        scrollbar-width: none; /* Firefox */
    }
</style>

<script>
    // Usamos una función autoejecutable para que no choque con otras variables de tu sistema
    (function() {
        function iniciarMenu() {
            const btnMenu = document.getElementById("btn-menu");
            const btnCloseMenu = document.getElementById("btn-close-menu");
            const sidebar = document.getElementById("sidebar");
            const overlay = document.getElementById("sidebar-overlay");

            // Si por alguna razón el sidebar no existe en esta vista, no hacemos nada para evitar errores
            if (!sidebar) return; 

            // 1. Abrir Menú
            if (btnMenu) {
                // Usamos onclick tradicional que es más resistente a recargas de AJAX o includes de PHP
                btnMenu.onclick = function(e) {
                    e.preventDefault();
                    sidebar.classList.remove("-translate-x-full");
                    if (overlay) overlay.classList.remove("hidden");
                };
            }

            // 2. Cerrar Menú con la 'X'
            if (btnCloseMenu) {
                btnCloseMenu.onclick = function(e) {
                    e.preventDefault();
                    sidebar.classList.add("-translate-x-full");
                    if (overlay) overlay.classList.add("hidden");
                };
            }

            // 3. Cerrar Menú tocando el fondo oscuro
            if (overlay) {
                overlay.onclick = function() {
                    sidebar.classList.add("-translate-x-full");
                    overlay.classList.add("hidden");
                };
            }
        }

        // Verificamos si la página ya cargó. Si sí, lo ejecutamos ya. Si no, esperamos.
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", iniciarMenu);
        } else {
            iniciarMenu();
        }
    })();
</script>