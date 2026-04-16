<?php
$titulo_pagina = "Mi Perfil | Portal del Colaborador";
$menu_activo = "perfil";

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="flex-1 flex flex-col h-screen overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-sm overflow-hidden m-8 border border-slate-200">
        <div class="h-40 bg-gradient-to-r from-indigo-600 to-blue-500"></div>
        <div class="px-8 pb-8 flex flex-col md:flex-row items-end -mt-16 space-x-6">
            <img src="https://ui-avatars.com/api/?name=Rafael+Abanto&size=160" class="w-32 h-32 rounded-2xl border-4 border-white shadow-xl bg-white">
            <div class="flex-1">
                <h1 class="text-3xl font-extrabold text-slate-800">Rafael Abanto Santacruz</h1>
                <p class="text-indigo-600 font-semibold">Asistente en Servicios Generales | OGA</p>
            </div>
            <button class="bg-indigo-600 text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-indigo-200 transition-all hover:bg-indigo-700">
                Actualizar Información
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 px-8 pb-8">
        <section class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Información Personal</h3>
            <div class="space-y-3">
                <div class="flex justify-between border-b border-slate-50 pb-2"> <span class="text-slate-500">DNI</span> <span class="font-bold text-slate-800">19259548</span> </div>
                <div class="flex justify-between border-b border-slate-50 pb-2"> <span class="text-slate-500">Grupo Sanguíneo</span> <span class="text-red-600 font-bold">O+</span> </div>
                <div class="flex justify-between"> <span class="text-slate-500">Edad</span> <span class="font-bold text-slate-800">51 años</span> </div>
            </div>
        </section>
        
        <section class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Carga Familiar</h3>
            <div class="space-y-3">
                <div class="flex justify-between border-b border-slate-50 pb-2"> <span class="text-slate-500">Cónyuge</span> <span class="font-bold text-slate-800">Cecilia</span> </div>
                <div class="flex justify-between"> <span class="text-slate-500">Número de Hijos</span> <span class="font-bold text-slate-800">4</span> </div>
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>