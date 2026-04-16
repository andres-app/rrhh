<?php
$titulo_pagina = "Validaciones Pendientes | RRHH";
$menu_activo = "validaciones";

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="flex-1 flex flex-col h-screen overflow-hidden">
    <header class="h-20 bg-white shadow-sm flex items-center px-8 justify-between z-10">
        <h1 class="text-2xl font-bold text-slate-800">Bandeja de Validaciones</h1>
    </header>

    <div class="p-8 flex-1 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-xs">
                    <tr>
                        <th class="p-4">Colaborador</th>
                        <th class="p-4">Cambio</th>
                        <th class="p-4 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr>
                        <td class="p-4"> <p class="font-bold">Rafael Abanto S.</p> </td>
                        <td class="p-4 text-sm">Dirección Domiciliaria</td>
                        <td class="p-4 text-center">
                            <button class="bg-indigo-50 text-indigo-700 px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-100 transition">Revisar</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>