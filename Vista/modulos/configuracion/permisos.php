<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once ROOT_PATH . 'Controlador/CtrPermisos.php';
require_once ROOT_PATH . 'Modelo/MdPermisos.php';

$rolActual = strtolower(trim($_SESSION["user_role"] ?? ""));
$controlador = new CtrPermisos();
$controlador->ctrGuardarMatriz();

$modulos = MdPermisos::mdlMostrarModulos();
$rolesBase = MdPermisos::mdlMostrarRoles();
$permisosCargados = MdPermisos::mdlObtenerPermisosAsociativos();

$rolesAMostrar = [];
foreach ($rolesBase as $r) {
    $r_norm = strtolower(trim($r));
    if ($r_norm === 'superadmin' || $r_norm === $rolActual) continue;
    $rolesAMostrar[] = $r;
}

$titulo_pagina = "Matriz Premium";
require_once ROOT_PATH . 'Vista/includes/header.php';
require_once ROOT_PATH . 'Vista/includes/sidebar.php';
?>

<style>
    /* Switch Premium */
    .switch {
        position: relative;
        display: inline-block;
        width: 42px;
        height: 24px;
    }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #e2e8f0;
        transition: .4s;
        border-radius: 34px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 18px; width: 18px;
        left: 3px; bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    /* Colores por tipo */
    input:checked + .slider-red { background-color: #991b1b; }
    input:checked + .slider-slate { background-color: #1e293b; }
    input:checked + .slider:before { transform: translateX(18px); }

    /* Estilo de fila */
    .tr-permiso:hover { background-color: #f8fafc !important; }
    .tr-permiso:hover .mod-icon { background-color: white; color: #991b1b; shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
</style>

<main class="flex-1 flex flex-col h-screen bg-[#f1f5f9] overflow-hidden">
    <header class="h-20 bg-white/80 backdrop-blur-md flex items-center justify-between px-10 border-b border-slate-200 shrink-0">
        <div class="flex items-center gap-5">
            <div class="w-12 h-12 bg-slate-900 rounded-2xl flex items-center justify-center shadow-lg shadow-slate-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Privilegios del Sistema</h1>
            </div>
        </div>

        <button form="formPermisos" type="submit" name="actualizar_permisos" 
                class="bg-slate-900 hover:bg-red-900 text-white px-10 py-3 rounded-2xl text-sm font-black transition-all duration-500 shadow-xl shadow-slate-200 hover:shadow-red-200 flex items-center gap-3 active:scale-95">
            GUARDAR CAMBIOS
        </button>
    </header>

    <div class="p-8 flex-1 overflow-hidden">
        <form id="formPermisos" method="POST" class="h-full flex flex-col">
            <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/60 border border-slate-100 flex-1 overflow-hidden flex flex-col">
                <div class="overflow-auto flex-1 custom-scrollbar">
                    <table class="w-full border-separate border-spacing-0">
                        <thead>
                            <tr>
                                <th class="sticky top-0 z-30 bg-white/95 backdrop-blur px-10 py-8 text-left border-b border-slate-100">
                                    <span class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">Módulos</span>
                                </th>
                                <?php foreach ($rolesAMostrar as $rol): ?>
                                    <th class="sticky top-0 z-30 bg-white/95 backdrop-blur px-6 py-8 text-center border-b border-slate-100 border-l border-slate-50">
                                        <div class="text-slate-800 text-xs font-black uppercase tracking-widest mb-3"><?= $rol ?></div>
                                        <div class="flex justify-center gap-8 text-[9px] font-black text-slate-300 uppercase">
                                            <span>Lectura</span>
                                            <span>Escritura</span>
                                        </div>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach ($modulos as $m): ?>
                                <tr class="tr-permiso transition-colors bg-white">
                                    <td class="px-10 py-6">
                                        <div class="flex items-center gap-4">
                                            <div class="mod-icon w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 transition-all duration-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                            </div>
                                            <span class="font-bold text-slate-700 text-sm tracking-tight"><?= str_replace("_", " ", $m['nombre']) ?></span>
                                        </div>
                                    </td>

                                    <?php foreach ($rolesAMostrar as $rol): 
                                        $vCheck = (isset($permisosCargados[$rol][$m['id']]) && $permisosCargados[$rol][$m['id']]['ver']) ? 'checked' : '';
                                        $eCheck = (isset($permisosCargados[$rol][$m['id']]) && $permisosCargados[$rol][$m['id']]['editar']) ? 'checked' : '';
                                    ?>
                                        <td class="px-6 py-6 border-l border-slate-50/50">
                                            <div class="flex justify-center items-center gap-8">
                                                <label class="switch">
                                                    <input type="checkbox" name="permiso[<?= $rol ?>][<?= $m['id'] ?>][ver]" <?= $vCheck ?>>
                                                    <span class="slider slider-red"></span>
                                                </label>

                                                <label class="switch">
                                                    <input type="checkbox" name="permiso[<?= $rol ?>][<?= $m['id'] ?>][editar]" <?= $eCheck ?>>
                                                    <span class="slider slider-slate"></span>
                                                </label>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="px-10 py-6 bg-slate-50/50 border-t border-slate-100 flex justify-between items-center">
                    <div class="flex items-center gap-8">
                        <div class="flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full bg-red-800"></span>
                            <span class="text-[10px] font-black text-slate-500 uppercase">Acceso Visual</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full bg-slate-800"></span>
                            <span class="text-[10px] font-black text-slate-500 uppercase">Capacidad de Edición</span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>