<?php

// En cada vista protegida
if (!isset($_SESSION["validarSesion"]) || $_SESSION["validarSesion"] != "ok") {
    header("Location: " . BASE_URL . "/login");
    exit();
}

$titulo_pagina = "Dashboard | Panel de Control RRHH";
$menu_activo = "dashboard";

// Agregamos un ../ extra para retroceder dos niveles
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">

    <header class="h-20 bg-white shadow-sm flex items-center px-8 justify-between z-10 border-b border-red-50">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">
                Hola, <?= explode(' ', $_SESSION["nombre_completo"])[0] ?> 👋
            </h1>

            <p class="text-sm text-slate-500">
                Aquí tienes el resumen de hoy, <?= date('d/m/Y') ?> | <?= date('H:i') ?>
            </p>
        </div>

        <div class="flex items-center space-x-4">
            <div class="hidden md:flex relative">
                <input type="text" placeholder="Búsqueda rápida..." class="bg-slate-100 border-none rounded-full px-4 py-2 text-sm focus:ring-2 focus:ring-red-700 outline-none w-64 transition-all">
            </div>
            <button class="relative p-2 text-slate-400 hover:text-red-800 transition">
                <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-600 rounded-full border-2 border-white"></span>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
            </button>
        </div>
    </header>

    <div class="p-8 flex-1 overflow-y-auto">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:border-red-200 transition-all group">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-[#4c0505] rounded-2xl shadow-lg shadow-red-900/20 group-hover:bg-red-800 transition-colors">
                        <svg class="w-6 h-6 text-red-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <span class="bg-green-100 text-green-700 text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wider">+2% este mes</span>
                </div>
                <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest">Total Colaboradores</h3>
                <p class="text-3xl font-black text-slate-800 mt-1">142</p>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:border-red-200 transition-all group cursor-pointer" onclick="window.location.href='<?= BASE_URL ?>/rrhh/validaciones'">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-orange-600 rounded-2xl shadow-lg shadow-orange-900/20 group-hover:bg-orange-500 transition-colors">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <span class="bg-orange-100 text-orange-700 text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wider">Acción requerida</span>
                </div>
                <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest">Validaciones Pendientes</h3>
                <p class="text-3xl font-black text-slate-800 mt-1">5</p>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:border-red-200 transition-all group">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-red-700 rounded-2xl shadow-lg shadow-red-900/20 group-hover:bg-red-600 transition-colors">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <span class="bg-red-100 text-red-700 text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wider">Próximos 30 días</span>
                </div>
                <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest">Contratos por Vencer</h3>
                <p class="text-3xl font-black text-slate-800 mt-1">8</p>
            </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-bold text-slate-800">Distribución por Modalidad</h2>
                        <button class="text-red-800 text-sm font-bold hover:text-red-600 transition">Ver reporte completo</button>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <div class="flex justify-between text-sm font-medium mb-2">
                                <span class="text-slate-700">Contrato CAS</span>
                                <span class="text-red-800 font-bold">110 Colaboradores</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-3">
                                <div class="bg-[#600505] h-3 rounded-full" style="width: 75%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm font-medium mb-2">
                                <span class="text-slate-700">Ley 728</span>
                                <span class="text-red-400 font-bold">32 Colaboradores</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-3">
                                <div class="bg-red-400 h-3 rounded-full" style="width: 25%"></div>
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
                        <div class="flex items-center p-3 bg-red-50/30 rounded-xl border border-red-100/50">
                            <div class="w-12 h-12 bg-red-800 text-white rounded-xl flex flex-col items-center justify-center font-bold shadow-md">
                                <span class="text-[10px] opacity-80 uppercase">Ago</span>
                                <span class="text-lg leading-none">13</span>
                            </div>
                            <div class="ml-4">
                                <p class="font-bold text-slate-800 text-sm">Cecilia (Cónyuge)</p>
                                <p class="text-xs text-slate-500">Esposa de Rafael Abanto</p>
                            </div>
                        </div>

                        <div class="flex items-center p-3 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="w-12 h-12 bg-slate-200 text-slate-600 rounded-xl flex flex-col items-center justify-center font-bold">
                                <span class="text-[10px] uppercase">May</span>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>