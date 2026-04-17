<?php
require_once ROOT_PATH . 'Controlador/CtrPermisos.php';
require_once ROOT_PATH . 'Modelo/MdPermisos.php';

// ✅ Rol correcto desde sesión
$rolActual = strtolower(trim($_SESSION["user_role"] ?? ""));

$controlador = new CtrPermisos();
$modulos = MdPermisos::mdlMostrarModulos();
$rolesBase = MdPermisos::mdlMostrarRoles();

// ✅ FILTRO FINAL LIMPIO
$rolesAMostrar = [];

foreach ($rolesBase as $r) {

    $r_norm = strtolower(trim($r));

    // 🚫 ocultar siempre superadmin
    if ($r_norm === 'superadmin') continue;

    // 🚫 ocultar el mismo rol (incluye admin, rrhh, etc.)
    if ($r_norm === $rolActual) continue;

    $rolesAMostrar[] = $r;
}

$titulo_pagina = "Matriz de Permisos";
require_once ROOT_PATH . 'Vista/includes/header.php';
require_once ROOT_PATH . 'Vista/includes/sidebar.php';
?>

<main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50 text-slate-600">
    <header class="min-h-14 bg-white shadow-sm flex items-center px-6 justify-between border-b border-red-50">
        <div>
            <h1 class="text-base font-bold text-slate-800">Matriz de Roles y Permisos</h1>
            <p class="text-[9px] text-slate-400 uppercase tracking-widest">Configuración de Accesos</p>
        </div>
        <button form="formPermisos" type="submit" name="actualizar_permisos" class="bg-red-900 hover:bg-red-950 text-white px-4 py-1.5 rounded-lg text-xs font-bold transition-all shadow-md">
            Guardar Cambios
        </button>


    </header>

    <div class="p-4 flex-1 overflow-y-auto">
        <form id="formPermisos" method="POST">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden max-w-[1000px] mx-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-4 py-2 text-left text-[9px] font-bold text-slate-400 uppercase">Módulo</th>
                            <?php foreach ($rolesAMostrar as $rol): ?>
                                <th class="px-4 py-2 text-center border-l border-slate-100">
                                    <div class="text-red-900 text-[10px] font-black uppercase"><?= $rol ?></div>
                                    <div class="text-[8px] text-slate-300">VER | EDIT</div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-[12px]">
                        <?php foreach ($modulos as $m): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 py-2 font-medium text-slate-700 italic lowercase first-letter:uppercase">
                                    <?= $m['nombre'] ?>
                                </td>

                                <?php foreach ($rolesAMostrar as $rol):
                                    $p = MdPermisos::mdlObtenerPermiso($rol, $m['id']);
                                    $vCheck = ($p && $p['can_view']) ? 'checked' : '';
                                    $eCheck = ($p && $p['can_edit']) ? 'checked' : '';
                                ?>
                                    <td class="px-4 py-2 border-l border-slate-50">
                                        <div class="flex justify-center gap-5">
                                            <input type="checkbox" name="permiso[<?= $rol ?>][<?= $m['id'] ?>][ver]" <?= $vCheck ?>
                                                class="w-3.5 h-3.5 rounded border-slate-300 text-red-900 focus:ring-0">
                                            <input type="checkbox" name="permiso[<?= $rol ?>][<?= $m['id'] ?>][editar]" <?= $eCheck ?>
                                                class="w-3.5 h-3.5 rounded border-slate-300 text-slate-800 focus:ring-0">
                                        </div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php $controlador->ctrGuardarMatriz(); ?>
        </form>
    </div>
</main>