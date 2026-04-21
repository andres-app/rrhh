<?php
$titulo_pagina = "Dashboard | Panel de Control RRHH";
$menu_activo = "dashboard";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">
    
    <header class="h-20 bg-white shadow-sm flex items-center px-8 justify-between z-10">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Hola, Administrador 👋</h1>
            <p class="text-sm text-slate-500">Aquí tienes el resumen de hoy, <?= date('d/m/Y') ?></p>
        </div>
        <div class="flex items-center space-x-4">
            <div class="hidden md:flex relative">
                <input type="text" placeholder="Búsqueda rápida..." class="bg-slate-100 border-none rounded-full px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none w-64">
            </div>
            <button class="relative p-2 text-slate-400 hover:text-indigo-600 transition">
                <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white"></span>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
            </button>
        </div>
    </header>

    <div class="p-8 flex-1 overflow-y-auto">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow group">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-indigo-50 rounded-2xl group-hover:bg-indigo-600 transition-colors">
                        <span class="text-2xl group-hover:text-white transition-colors">👥</span>
                    </div>
                    <span class="bg-green-100 text-green-700 text-xs font-bold px-2 py-1 rounded-full">+2% este mes</span>
                </div>
                <h3 class="text-slate-400 text-sm font-bold uppercase tracking-wider">Total Colaboradores</h3>
                <p class="text-3xl font-black text-slate-800 mt-1">142</p>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow group cursor-pointer" onclick="window.location.href='<?= BASE_URL ?>/rrhh/validaciones'">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-orange-50 rounded-2xl group-hover:bg-orange-500 transition-colors">
                        <span class="text-2xl group-hover:text-white transition-colors">⚠️</span>
                    </div>
                    <span class="bg-orange-100 text-orange-700 text-xs font-bold px-2 py-1 rounded-full">Requieren acción</span>
                </div>
                <h3 class="text-slate-400 text-sm font-bold uppercase tracking-wider">Validaciones Pendientes</h3>
                <p class="text-3xl font-black text-slate-800 mt-1">5</p>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow group">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-red-50 rounded-2xl group-hover:bg-red-500 transition-colors">
                        <span class="text-2xl group-hover:text-white transition-colors">📄</span>
                    </div>
                    <span class="bg-red-100 text-red-700 text-xs font-bold px-2 py-1 rounded-full">Próximos 30 días</span>
                </div>
                <h3 class="text-slate-400 text-sm font-bold uppercase tracking-wider">Contratos por Vencer</h3>
                <p class="text-3xl font-black text-slate-800 mt-1">8</p>
            </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-bold text-slate-800">Distribución por Modalidad</h2>
                        <button class="text-indigo-600 text-sm font-bold hover:underline">Ver reporte completo</button>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-sm font-medium mb-1">
                                <span class="text-slate-700">Contrato CAS</span>
                                <span class="text-indigo-600 font-bold">110 Colaboradores</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-3">
                                <div class="bg-indigo-500 h-3 rounded-full" style="width: 75%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm font-medium mb-1">
                                <span class="text-slate-700">Ley 728</span>
                                <span class="text-blue-500 font-bold">32 Colaboradores</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-3">
                                <div class="bg-blue-400 h-3 rounded-full" style="width: 25%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-800 mb-6 flex items-center">
                        <span class="text-2xl mr-2">🎂</span> Próximos Cumpleaños
                    </h2>
                    <div class="space-y-4">
                        <div class="flex items-center p-3 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="w-12 h-12 bg-pink-100 text-pink-600 rounded-xl flex flex-col items-center justify-center font-bold shadow-inner">
                                <span class="text-xs">AGO</span>
                                <span class="text-lg leading-none">13</span>
                            </div>
                            <div class="ml-4">
                                <p class="font-bold text-slate-800 text-sm">Cecilia (Cónyuge)</p>
                                <p class="text-xs text-slate-500">Esposa de Rafael Abanto</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center p-3 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex flex-col items-center justify-center font-bold shadow-inner">
                                <span class="text-xs">MAY</span>
                                <span class="text-lg leading-none">12</span>
                            </div>
                            <div class="ml-4">
                                <p class="font-bold text-slate-800 text-sm">Luis O. Aguilar</p>
                                <p class="text-xs text-slate-500">Cumple 75 años</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>