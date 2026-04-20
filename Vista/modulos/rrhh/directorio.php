<?php
require_once ROOT_PATH . 'Controlador/CtrDirectorio.php';
require_once ROOT_PATH . 'Modelo/MdDirectorio.php';

$controlador = new CtrDirectorio();
$empleados = $controlador->ctrMostrarDirectorio();

$titulo_pagina = "Directorio de Personal - RRHH";
$menu_activo = "directorio";

require_once ROOT_PATH . 'Vista/includes/header.php';
require_once ROOT_PATH . 'Vista/includes/sidebar.php';
?>

<main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">

    <header class="min-h-20 bg-white shadow-sm flex flex-col md:flex-row items-center px-4 md:px-8 py-4 md:py-0 justify-between z-10 gap-4 border-b border-red-50">
        <h1 class="text-xl md:text-2xl font-bold text-slate-800 text-center md:text-left">Directorio de Personal</h1>

        <div class="w-full md:flex-1 md:max-w-md md:mx-8">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" id="searchInput" onkeyup="filterTable()"
                    class="block w-full pl-10 pr-3 py-2 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-900/20 focus:border-red-900 sm:text-sm transition"
                    placeholder="Buscar por nombre o DNI...">
            </div>
        </div>

        <button class="w-full md:w-auto bg-red-900 hover:bg-[#4c0505] text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-red-900/20 transition-all flex items-center justify-center active:scale-95">
            <span class="mr-2 text-xl leading-none">+</span> <span class="whitespace-nowrap text-sm">Nuevo Empleado</span>
        </button>
    </header>

    <div class="p-4 md:p-8 flex-1 overflow-y-auto">

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Colaborador</th>
                        <th class="hidden sm:table-cell px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Puesto / Área</th>
                        <th class="hidden lg:table-cell px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Contacto</th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Estado</th>
                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <?php foreach ($empleados as $row): ?>
                        <tr class="hover:bg-red-50/30 transition-colors group">
                            <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="hidden xs:flex h-10 w-10 rounded-xl bg-red-900 flex-shrink-0 items-center justify-center text-white font-bold mr-3 shadow-md shadow-red-900/20 group-hover:rotate-3 transition-transform">
                                        <?php echo substr($row['nombres_apellidos'], 0, 1); ?>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="text-sm font-bold text-slate-800 truncate max-w-[150px] md:max-w-none">
                                            <?php echo $row['nombres_apellidos']; ?>
                                        </div>
                                        <div class="text-[11px] text-slate-400 font-medium tracking-wide">DNI: <?php echo $row['dni']; ?></div>
                                        <div class="sm:hidden text-[10px] text-red-700 font-bold mt-1 uppercase"><?php echo $row['puesto_cas']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden sm:table-cell px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-slate-700 font-bold"><?php echo $row['puesto_cas']; ?></div>
                                <div class="text-xs text-slate-400 font-medium"><?php echo $row['area']; ?></div>
                            </td>
                            <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col space-y-0.5">
                                    <span class="text-xs text-slate-600 font-medium flex items-center">
                                        <span class="w-1 h-1 bg-red-400 rounded-full mr-2"></span>
                                        <?php echo $row['correo_institucional']; ?>
                                    </span>
                                    <span class="text-[11px] text-slate-400 flex items-center">
                                        <span class="w-1 h-1 bg-slate-300 rounded-full mr-2"></span>
                                        <?php echo $row['celular']; ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm">
                                <?php 
                                $isBaja = (isset($row['situacion']) && $row['situacion'] === 'Baja');
                                $color = $isBaja ? 'bg-slate-100 text-slate-500 border-slate-200' : 'bg-green-50 text-green-700 border-green-100'; 
                                ?>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider border <?php echo $color; ?>">
                                    <?php echo $row['situacion'] ?? 'Activo'; ?>
                                </span>
                            </td>
                            <td class="px-4 md:px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <a href="perfil/<?php echo $row['id']; ?>"
                                        class="p-2 bg-red-50 text-red-900 rounded-lg hover:bg-red-900 hover:text-white transition-all shadow-sm border border-red-100"
                                        title="Ver Perfil">
                                        <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>