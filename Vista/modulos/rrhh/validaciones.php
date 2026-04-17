<?php
$titulo_pagina = "Validaciones Pendientes | RRHH";
$menu_activo = "validaciones";

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">
    <header class="h-20 bg-white shadow-sm flex items-center px-8 justify-between z-10 border-b border-red-50">
        <div class="flex items-center">
            <div class="p-2 bg-orange-100 rounded-lg mr-4">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-800">Bandeja de Validaciones</h1>
        </div>
        <span class="bg-red-100 text-red-800 text-xs font-bold px-3 py-1 rounded-full">5 Pendientes</span>
    </header>

    <div class="p-8 flex-1 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[10px] tracking-widest font-bold">
                    <tr>
                        <th class="p-4">Colaborador</th>
                        <th class="p-4">Tipo de Solicitud</th>
                        <th class="p-4">Fecha Envío</th>
                        <th class="p-4 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr class="hover:bg-red-50/30 transition group">
                        <td class="p-4">
                            <div class="flex items-center">
                                <img src="https://ui-avatars.com/api/?name=Rafael+Abanto&background=880808&color=fff&size=32" class="w-8 h-8 rounded-lg mr-3 shadow-sm">
                                <p class="font-bold text-slate-800">Rafael Abanto S.</p>
                            </div>
                        </td>
                        <td class="p-4">
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-slate-700">Dirección Domiciliaria</span>
                                <span class="text-[10px] text-slate-400">Actualización de datos personales</span>
                            </div>
                        </td>
                        <td class="p-4 text-sm text-slate-500">
                            17/04/2026
                        </td>
                        <td class="p-4 text-center">
                            <button class="bg-red-900 text-white px-5 py-2 rounded-xl text-xs font-bold hover:bg-[#4c0505] transition-all shadow-md shadow-red-900/20 active:scale-95 flex items-center mx-auto group-hover:shadow-red-900/40">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                Revisar Solicitud
                            </button>
                        </td>
                    </tr>
                    </tbody>
            </table>
        </div>
        
        <div class="mt-6 flex justify-between items-center px-4">
            <p class="text-xs text-slate-500 font-medium">Mostrando 1 de 5 validaciones pendientes</p>
            <div class="flex space-x-2">
                <button class="p-2 rounded-lg bg-white border border-slate-200 text-slate-400 cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <button class="p-2 rounded-lg bg-white border border-slate-200 text-red-900 hover:bg-red-50 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>