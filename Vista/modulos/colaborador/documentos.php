<?php
$titulo_pagina = "Mis Documentos | Portal del Colaborador";
$menu_activo = "documentos";

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">
    
    <header class="h-20 bg-white shadow-sm flex items-center px-8 justify-between z-10 border-b border-red-50">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Mis Documentos</h1>
            <p class="text-sm text-slate-500">Visualiza y descarga tus boletas de pago y contratos.</p>
        </div>
    </header>

    <div class="p-8 flex-1 overflow-y-auto">
        
        <div class="max-w-5xl mx-auto">
            <div class="flex space-x-1 bg-slate-200/50 p-1 rounded-xl w-max mb-6">
                <button id="tab-boletas" onclick="switchTab('boletas')" class="px-6 py-2.5 rounded-lg text-sm font-bold transition-all bg-white shadow-sm text-red-900">
                    Boletas de Pago
                </button>
                <button id="tab-contratos" onclick="switchTab('contratos')" class="px-6 py-2.5 rounded-lg text-sm font-bold transition-all text-slate-600 hover:text-red-900 hover:bg-red-50">
                    Contratos y Adendas
                </button>
            </div>

            <div id="content-boletas" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden block">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[10px] tracking-widest">
                        <tr>
                            <th class="p-4 font-bold">Documento</th>
                            <th class="p-4 font-bold hidden md:table-cell text-center">Periodo</th>
                            <th class="p-4 font-bold hidden md:table-cell">Neto Pagado</th>
                            <th class="p-4 font-bold text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr class="hover:bg-red-50/30 transition">
                            <td class="p-4 flex items-center">
                                <div class="p-2.5 bg-red-900 rounded-lg mr-4 shadow-md shadow-red-900/20">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800">Boleta de Pago - Marzo 2026</p>
                                    <p class="text-xs text-slate-500 md:hidden font-medium text-red-700">S/ 3,114.19</p>
                                </div>
                            </td>
                            <td class="p-4 hidden md:table-cell text-center">
                                <span class="bg-red-50 text-red-900 px-3 py-1 rounded-full text-xs font-bold border border-red-100">Marzo 2026</span>
                            </td>
                            <td class="p-4 hidden md:table-cell font-bold text-slate-700">S/ 3,114.19</td>
                            <td class="p-4 text-right">
                                <button class="bg-white border border-slate-300 text-slate-700 hover:bg-red-900 hover:text-white hover:border-red-900 px-4 py-2 rounded-lg text-sm font-bold shadow-sm transition-all flex items-center inline-flex group">
                                    <svg class="w-4 h-4 mr-2 text-red-800 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    Descargar
                                </button>
                            </td>
                        </tr>
                        <tr class="hover:bg-red-50/30 transition">
                            <td class="p-4 flex items-center">
                                <div class="p-2.5 bg-red-900 rounded-lg mr-4 shadow-md shadow-red-900/20">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800">Boleta de Pago - Febrero 2026</p>
                                    <p class="text-xs text-slate-500 md:hidden font-medium text-red-700">S/ 3,114.19</p>
                                </div>
                            </td>
                            <td class="p-4 hidden md:table-cell text-center">
                                <span class="bg-slate-100 text-slate-600 px-3 py-1 rounded-full text-xs font-bold border border-slate-200">Febrero 2026</span>
                            </td>
                            <td class="p-4 hidden md:table-cell font-bold text-slate-700">S/ 3,114.19</td>
                            <td class="p-4 text-right">
                                <button class="bg-white border border-slate-300 text-slate-700 hover:bg-red-900 hover:text-white hover:border-red-900 px-4 py-2 rounded-lg text-sm font-bold shadow-sm transition-all flex items-center inline-flex group">
                                    <svg class="w-4 h-4 mr-2 text-red-800 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    Descargar
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="content-contratos" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hidden">
                <div class="p-8 text-center border-b border-slate-100 bg-red-50/20">
                    <div class="w-16 h-16 bg-red-900 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-red-900/30 rotate-3">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">Régimen Actual: <span class="text-red-900">CAS</span></h3>
                    <p class="text-slate-500 text-sm">Fecha de ingreso: 16 de Diciembre de 2016</p>
                </div>
                
                <div class="p-4 divide-y divide-slate-100">
                    <div class="flex items-center justify-between p-4 hover:bg-red-50/40 transition rounded-xl group">
                        <div class="flex items-center">
                            <div class="p-2.5 bg-slate-100 text-slate-500 rounded-lg mr-4 group-hover:bg-red-100 group-hover:text-red-900 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="font-bold text-slate-800">Adenda de Renovación 2026</p>
                                <p class="text-xs text-slate-500">Vigencia: 01 Ene 2026 - 31 Dic 2026</p>
                            </div>
                        </div>
                        <button class="text-red-900 hover:text-white font-bold text-sm bg-red-50 hover:bg-red-900 px-5 py-2 rounded-lg transition-all">Ver Documento</button>
                    </div>
                    
                    <div class="flex items-center justify-between p-4 hover:bg-red-50/40 transition rounded-xl group">
                        <div class="flex items-center">
                            <div class="p-2.5 bg-slate-100 text-slate-500 rounded-lg mr-4 group-hover:bg-red-100 group-hover:text-red-900 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="font-bold text-slate-800">Contrato Original N° 042-2016</p>
                                <p class="text-xs text-slate-500">Firma: 16 Dic 2016</p>
                            </div>
                        </div>
                        <button class="text-red-900 hover:text-white font-bold text-sm bg-red-50 hover:bg-red-900 px-5 py-2 rounded-lg transition-all">Ver Documento</button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<script>
    function switchTab(tab) {
        document.getElementById('content-boletas').classList.add('hidden');
        document.getElementById('content-contratos').classList.add('hidden');
        
        document.getElementById('tab-boletas').className = "px-6 py-2.5 rounded-lg text-sm font-bold transition-all text-slate-600 hover:text-red-900 hover:bg-red-50";
        document.getElementById('tab-contratos').className = "px-6 py-2.5 rounded-lg text-sm font-bold transition-all text-slate-600 hover:text-red-900 hover:bg-red-50";
        
        document.getElementById('content-' + tab).classList.remove('hidden');
        document.getElementById('tab-' + tab).className = "px-6 py-2.5 rounded-lg text-sm font-bold transition-all bg-white shadow-sm text-red-900 border border-red-100/50";
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>