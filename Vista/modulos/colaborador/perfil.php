<?php
$titulo_pagina = "Mi Perfil | Portal del Colaborador";
$menu_activo = "perfil";

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="flex-1 flex flex-col h-screen overflow-y-auto bg-slate-50">
    <div class="bg-white rounded-3xl shadow-sm overflow-hidden m-8 border border-slate-200">
        <div class="h-40 bg-gradient-to-r from-[#4c0505] to-red-900"></div>
        
        <div class="px-8 pb-8 flex flex-col md:flex-row items-end -mt-16 space-y-4 md:space-y-0 md:space-x-6">
            <img src="https://ui-avatars.com/api/?name=Rafael+Abanto&size=160&background=880808&color=fff" 
                 class="w-32 h-32 rounded-2xl border-4 border-white shadow-xl bg-white object-cover">
            
            <div class="flex-1">
                <h1 class="text-3xl font-extrabold text-slate-800">Rafael Abanto Santacruz</h1>
                <p class="text-red-800 font-semibold flex items-center">
                    <span class="w-2 h-2 bg-red-600 rounded-full mr-2"></span>
                    Asistente en Servicios Generales | OGA
                </p>
            </div>
            
            <button class="bg-red-900 text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-red-900/20 transition-all hover:bg-[#4c0505] hover:-translate-y-1 active:scale-95">
                Actualizar Información
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 px-8 pb-8">
        <section class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm transition-all hover:shadow-md">
            <div class="flex items-center mb-6">
                <div class="p-2 bg-red-50 rounded-lg mr-3">
                    <svg class="w-5 h-5 text-red-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Información Personal</h3>
            </div>
            
            <div class="space-y-4">
                <div class="flex justify-between border-b border-slate-50 pb-3"> 
                    <span class="text-slate-500 text-sm">DNI</span> 
                    <span class="font-bold text-slate-800">19259548</span> 
                </div>
                <div class="flex justify-between border-b border-slate-50 pb-3"> 
                    <span class="text-slate-500 text-sm">Grupo Sanguíneo</span> 
                    <span class="text-red-700 font-bold bg-red-50 px-2 py-0.5 rounded-md">O+</span> 
                </div>
                <div class="flex justify-between"> 
                    <span class="text-slate-500 text-sm">Edad</span> 
                    <span class="font-bold text-slate-800">51 años</span> 
                </div>
            </div>
        </section>
        
        <section class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm transition-all hover:shadow-md">
            <div class="flex items-center mb-6">
                <div class="p-2 bg-red-50 rounded-lg mr-3">
                    <svg class="w-5 h-5 text-red-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Carga Familiar</h3>
            </div>

            <div class="space-y-4">
                <div class="flex justify-between border-b border-slate-50 pb-3"> 
                    <span class="text-slate-500 text-sm">Cónyuge</span> 
                    <span class="font-bold text-slate-800">Cecilia</span> 
                </div>
                <div class="flex justify-between"> 
                    <span class="text-slate-500 text-sm">Número de Hijos</span> 
                    <span class="font-bold text-slate-800">4</span> 
                </div>
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>