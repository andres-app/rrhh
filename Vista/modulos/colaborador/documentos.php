<?php
$titulo_pagina = "Mis Documentos | Portal del Colaborador";
$menu_activo = "documentos"; // Para que se pinte en el sidebar

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">
    
    <header class="h-20 bg-white shadow-sm flex items-center px-8 justify-between z-10">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Mis Documentos</h1>
            <p class="text-sm text-slate-500">Visualiza y descarga tus boletas de pago y contratos.</p>
        </div>
    </header>

    <div class="p-8 flex-1 overflow-y-auto">
        
        <div class="max-w-5xl mx-auto">
            <div class="flex space-x-1 bg-slate-200/50 p-1 rounded-xl w-max mb-6">
                <button id="tab-boletas" onclick="switchTab('boletas')" class="px-6 py-2.5 rounded-lg text-sm font-bold transition-all bg-white shadow-sm text-indigo-700">
                    Boletas de Pago
                </button>
                <button id="tab-contratos" onclick="switchTab('contratos')" class="px-6 py-2.5 rounded-lg text-sm font-bold transition-all text-slate-600 hover:text-slate-800 hover:bg-slate-200">
                    Contratos y Adendas
                </button>
            </div>

            <div id="content-boletas" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden block">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-xs tracking-wider">
                        <tr>
                            <th class="p-4 font-bold">Documento</th>
                            <th class="p-4 font-bold hidden md:table-cell">Periodo</th>
                            <th class="p-4 font-bold hidden md:table-cell">Neto Pagado</th>
                            <th class="p-4 font-bold text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-4 flex items-center">
                                <span class="text-3xl mr-4">📄</span>
                                <div>
                                    <p class="font-bold text-slate-800">Boleta de Pago - Marzo 2026</p>
                                    <p class="text-xs text-slate-500 md:hidden">S/ 3,114.19</p>
                                </div>
                            </td>
                            <td class="p-4 hidden md:table-cell"><span class="bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full text-xs font-bold border border-indigo-100">Marzo 2026</span></td>
                            <td class="p-4 hidden md:table-cell font-bold text-slate-700">S/ 3,114.19</td>
                            <td class="p-4 text-right">
                                <button class="bg-white border border-slate-300 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 px-4 py-2 rounded-lg text-sm font-bold shadow-sm transition flex items-center inline-flex">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    Descargar
                                </button>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-4 flex items-center">
                                <span class="text-3xl mr-4">📄</span>
                                <div>
                                    <p class="font-bold text-slate-800">Boleta de Pago - Febrero 2026</p>
                                    <p class="text-xs text-slate-500 md:hidden">S/ 3,114.19</p>
                                </div>
                            </td>
                            <td class="p-4 hidden md:table-cell"><span class="bg-slate-100 text-slate-600 px-3 py-1 rounded-full text-xs font-bold">Febrero 2026</span></td>
                            <td class="p-4 hidden md:table-cell font-bold text-slate-700">S/ 3,114.19</td>
                            <td class="p-4 text-right">
                                <button class="bg-white border border-slate-300 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 px-4 py-2 rounded-lg text-sm font-bold shadow-sm transition flex items-center inline-flex">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    Descargar
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="content-contratos" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hidden">
                <div class="p-8 text-center border-b border-slate-100">
                    <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl">✍️</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">Régimen Actual: CAS</h3>
                    <p class="text-slate-500 text-sm">Fecha de ingreso: 16 de Diciembre de 2016</p>
                </div>
                <div class="p-4 divide-y divide-slate-100">
                    <div class="flex items-center justify-between p-4 hover:bg-slate-50 transition rounded-xl">
                        <div class="flex items-center">
                            <span class="text-3xl mr-4">📑</span>
                            <div>
                                <p class="font-bold text-slate-800">Adenda de Renovación 2026</p>
                                <p class="text-xs text-slate-500">Vigencia: 01 Ene 2026 - 31 Dic 2026</p>
                            </div>
                        </div>
                        <button class="text-indigo-600 hover:text-indigo-800 font-bold text-sm bg-indigo-50 px-4 py-2 rounded-lg transition">Ver Documento</button>
                    </div>
                    <div class="flex items-center justify-between p-4 hover:bg-slate-50 transition rounded-xl">
                        <div class="flex items-center">
                            <span class="text-3xl mr-4">📑</span>
                            <div>
                                <p class="font-bold text-slate-800">Contrato Original N° 042-2016</p>
                                <p class="text-xs text-slate-500">Firma: 16 Dic 2016</p>
                            </div>
                        </div>
                        <button class="text-indigo-600 hover:text-indigo-800 font-bold text-sm bg-indigo-50 px-4 py-2 rounded-lg transition">Ver Documento</button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<script>
    function switchTab(tab) {
        // Ocultar todo
        document.getElementById('content-boletas').classList.add('hidden');
        document.getElementById('content-contratos').classList.add('hidden');
        
        // Resetear botones
        document.getElementById('tab-boletas').className = "px-6 py-2.5 rounded-lg text-sm font-bold transition-all text-slate-600 hover:text-slate-800 hover:bg-slate-200";
        document.getElementById('tab-contratos').className = "px-6 py-2.5 rounded-lg text-sm font-bold transition-all text-slate-600 hover:text-slate-800 hover:bg-slate-200";
        
        // Mostrar seleccionado
        document.getElementById('content-' + tab).classList.remove('hidden');
        document.getElementById('tab-' + tab).className = "px-6 py-2.5 rounded-lg text-sm font-bold transition-all bg-white shadow-sm text-indigo-700";
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>