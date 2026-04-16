<?php
// /Vista/includes/sidebar.php
?>

<div class="md:hidden flex items-center justify-between bg-slate-900 p-4 shrink-0 z-20 border-b border-slate-800">
    <div class="flex items-center">
        <div class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center mr-3 shadow-lg shadow-indigo-500/30">
            <span class="text-white font-bold text-xl leading-none">H</span>
        </div>
        <span class="text-xl font-bold text-white tracking-wide">HR<span class="text-indigo-400">Panel</span></span>
    </div>
    <button id="btn-menu" class="text-slate-300 hover:text-white focus:outline-none">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>
</div>

<div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/60 z-30 hidden backdrop-blur-sm transition-opacity opacity-0"></div>

<aside id="sidebar" class="w-64 bg-slate-900 text-slate-300 flex flex-col z-40 border-r border-slate-800 shadow-xl fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out h-full">
    
    <div class="h-20 flex items-center justify-between px-6 border-b border-slate-800 shrink-0">
        <div class="flex items-center">
            <div class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center mr-3 shadow-lg shadow-indigo-500/30">
                <span class="text-white font-bold text-xl leading-none">H</span>
            </div>
            <span class="text-xl font-bold text-white tracking-wide">HR<span class="text-indigo-400">Panel</span></span>
        </div>
        <button id="btn-close-menu" class="md:hidden text-slate-400 hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
    
    <nav class="flex-1 overflow-y-auto py-6">
        <div class="px-6 mb-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Mi Espacio</div>
        
        <a href="<?= BASE_URL ?>/perfil" class="flex items-center px-6 py-3 transition-all duration-200 <?= ($menu_activo == 'perfil') ? 'bg-indigo-500/10 text-indigo-400 border-l-4 border-indigo-500' : 'hover:bg-slate-800 hover:text-white border-l-4 border-transparent text-slate-400' ?>">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            <span class="font-medium text-sm">Mi Perfil</span>
        </a>
        
        <a href="<?= BASE_URL ?>/documentos" class="flex items-center px-6 py-3 transition-all duration-200 <?= ($menu_activo == 'documentos') ? 'bg-indigo-500/10 text-indigo-400 border-l-4 border-indigo-500' : 'hover:bg-slate-800 hover:text-white border-l-4 border-transparent text-slate-400' ?>">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <span class="font-medium text-sm">Mis Documentos</span>
        </a>

        <div class="px-6 mt-8 mb-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Administración</div>
        
        <a href="<?= BASE_URL ?>/rrhh/dashboard" class="flex items-center px-6 py-3 transition-all duration-200 <?= ($menu_activo == 'dashboard') ? 'bg-indigo-500/10 text-indigo-400 border-l-4 border-indigo-500' : 'hover:bg-slate-800 hover:text-white border-l-4 border-transparent text-slate-400' ?>">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v10a2 2 0 01-2 2h-2a2 2 0 01-2-2v-10z"></path></svg>
            <span class="font-medium text-sm">Dashboard</span>
        </a>

        <a href="<?= BASE_URL ?>/rrhh/validaciones" class="flex items-center px-6 py-3 transition-all duration-200 <?= ($menu_activo == 'validaciones') ? 'bg-indigo-500/10 text-indigo-400 border-l-4 border-indigo-500' : 'hover:bg-slate-800 hover:text-white border-l-4 border-transparent text-slate-400' ?>">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="font-medium text-sm flex-1">Validaciones</span>
            <span class="bg-indigo-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm">5</span>
        </a>
        
        <a href="<?= BASE_URL ?>/rrhh/directorio" class="flex items-center px-6 py-3 transition-all duration-200 <?= ($menu_activo == 'directorio') ? 'bg-indigo-500/10 text-indigo-400 border-l-4 border-indigo-500' : 'hover:bg-slate-800 hover:text-white border-l-4 border-transparent text-slate-400' ?>">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            <span class="font-medium text-sm">Directorio</span>
        </a>
    </nav>

    <div class="p-4 bg-slate-900/80 border-t border-slate-800 shrink-0">
        <div class="flex items-center p-2 rounded-xl hover:bg-slate-800 transition cursor-pointer group">
            <img src="https://ui-avatars.com/api/?name=Rafael+Abanto&background=4f46e5&color=fff" class="w-9 h-9 rounded-full shadow-md border border-slate-700 group-hover:border-indigo-500 transition-colors">
            <div class="ml-3 overflow-hidden flex-1">
                <p class="text-sm font-bold text-white truncate">Rafael Abanto</p>
                <a href="<?= BASE_URL ?>/logout" class="text-xs text-slate-400 group-hover:text-indigo-400 transition truncate block">Cerrar sesión</a>
            </div>
            <svg class="w-4 h-4 text-slate-500 group-hover:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
        </div>
    </div>
</aside>