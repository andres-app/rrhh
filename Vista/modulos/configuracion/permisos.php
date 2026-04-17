<?php
//Vista/modulos/configuracion/permisos.php
require_once ROOT_PATH . 'Controlador/CtrPermisos.php';
require_once ROOT_PATH . 'Modelo/MdPermisos.php';

$controlador = new CtrPermisos();
$modulos = MdPermisos::mdlMostrarModulos();
$roles = ['superadmin', 'admin', 'colaborador'];

$titulo_pagina = "Gestión de Permisos - RRHH";
$menu_activo = "configuracion";

require_once ROOT_PATH . 'Vista/includes/header.php';
require_once ROOT_PATH . 'Vista/includes/sidebar.php';
?>

<main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">

    <header class="min-h-20 bg-white shadow-sm flex flex-col md:flex-row items-center px-4 md:px-8 py-4 md:py-0 justify-between z-10 gap-4 border-b border-red-50">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-800 text-center md:text-left">Matriz de Roles y Permisos</h1>
            <p class="text-[11px] text-slate-400 font-medium uppercase tracking-wider">Configuración de accesos del sistema</p>
        </div>

        <div class="flex gap-3">
            <button onclick="location.reload()" class="p-2.5 bg-slate-50 text-slate-500 rounded-xl hover:bg-slate-100 transition-all border border-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            
            <button form="formPermisos" type="submit" name="actualizar_permisos" class="bg-red-900 hover:bg-[#4c0505] text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-red-900/20 transition-all flex items-center justify-center active:scale-95">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>
                <span class="whitespace-nowrap text-sm">Guardar Cambios</span>
            </button>
        </div>
    </header>

    <div class="p-4 md:p-8 flex-1 overflow-y-auto">
        
        <form id="formPermisos" method="POST">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Módulo del Sistema</th>
                            <?php foreach ($roles as $rol): ?>
                                <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] border-l border-slate-100">
                                    <div class="text-red-900 mb-1"><?= $rol ?></div>
                                    <div class="flex justify-center gap-4 text-[8px] text-slate-300">
                                        <span>VER</span> | <span>EDITAR</span>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <?php foreach ($modulos as $m): ?>
                            <tr class="hover:bg-red-50/20 transition-colors group">
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 mr-3 group-hover:bg-red-900 group-hover:text-white transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                                        </div>
                                        <div class="text-sm font-bold text-slate-700"><?= $m['nombre'] ?></div>
                                    </div>
                                </td>

                                <?php foreach ($roles as $rol): 
                                    $p = MdPermisos::mdlObtenerPermiso($rol, $m['id']);
                                    $vChecked = ($p && $p['can_view']) ? 'checked' : '';
                                    $eChecked = ($p && $p['can_edit']) ? 'checked' : '';
                                ?>
                                    <td class="px-6 py-5 whitespace-nowrap border-l border-slate-50">
                                        <div class="flex justify-center items-center gap-8">
                                            <input type="checkbox" name="permiso[<?= $rol ?>][<?= $m['id'] ?>][ver]" <?= $vChecked ?> 
                                                   class="w-5 h-5 rounded border-slate-300 text-red-900 focus:ring-red-900 transition-all cursor-pointer">
                                            
                                            <input type="checkbox" name="permiso[<?= $rol ?>][<?= $m['id'] ?>][editar]" <?= $eChecked ?> 
                                                   class="w-5 h-5 rounded border-slate-300 text-slate-800 focus:ring-slate-800 transition-all cursor-pointer">
                                        </div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php
                // Ejecución de la lógica de guardado
                $controlador->ctrGuardarMatriz();
            ?>
        </form>

        <p class="mt-6 text-[10px] text-slate-400 font-medium uppercase tracking-widest text-center">
            &copy; <?= date('Y') ?> - Control de Accesos Legrand
        </p>
    </div>
</main>