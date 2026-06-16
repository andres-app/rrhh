<?php
// Vista/modulos/configuracion/usuarios.php
$titulo_pagina = "Usuarios | Configuración";
$menu_activo = "usuarios";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$ctr = new CtrUsuario();
$usuarios = $ctr->ctrListarUsuarios();
$ctr->ctrCrearUsuario();
$ctr->ctrEditarUsuario();
$ctr->ctrEstadoUsuario();
?>

<main class="flex-1 p-8 bg-slate-50">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-slate-800">Gestión de Usuarios</h1>

        <button onclick="modalNuevo()" class="bg-[#7A0C19] text-white px-4 py-2 rounded-xl shadow hover:opacity-90">
            + Nuevo
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-100 text-slate-600 uppercase text-xs">
                <tr>
                    <th class="p-3 text-left">Usuario</th>
                    <th>Nombre</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr class="border-t hover:bg-slate-50">
                        <td class="p-3"><?= $u["username"] ?></td>
                        <td><?= $u["nombres_apellidos"] ?? '-' ?></td>
                        <td><?= $u["rol"] ?></td>

                        <td>
                            <?php if ($u["estado"]): ?>
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded-lg">Activo</span>
                            <?php else: ?>
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded-lg">Inactivo</span>
                            <?php endif; ?>
                        </td>

                        <td class="text-center space-x-2">
                            <button onclick="editar(<?= htmlspecialchars(json_encode($u)) ?>)" class="text-blue-600">Editar</button>

                            <?php if ($u["estado"]): ?>
                                <a href="usuarios?estado_id=<?= $u["id"] ?>&estado_val=0" class="text-red-600">Desactivar</a>
                            <?php else: ?>
                                <a href="usuarios?estado_id=<?= $u["id"] ?>&estado_val=1" class="text-green-600">Activar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- MODAL -->
<div id="modal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-[400px] shadow-xl">

        <form method="POST">
            <input type="hidden" name="editar_id" id="edit_id">

            <h2 class="text-lg font-bold mb-4">Usuario</h2>

            <input type="text" name="nuevo_usuario" id="user" placeholder="Usuario"
                class="w-full border p-2 rounded mb-3">

            <input type="password" name="nuevo_password" id="pass"
                placeholder="Contraseña"
                class="w-full border p-2 rounded mb-3">

            <select name="nuevo_rol" id="rol" class="w-full border p-2 rounded mb-4">
                <option value="admin">Admin</option>
                <option value="rrhh">RRHH</option>
                <option value="colaborador">Colaborador</option>
            </select>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="cerrar()" class="px-3 py-1 bg-gray-200 rounded">Cancelar</button>
                <button class="px-3 py-1 bg-[#7A0C19] text-white rounded">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
function modalNuevo() {
    document.getElementById("modal").classList.remove("hidden");
}

function cerrar() {
    document.getElementById("modal").classList.add("hidden");
}

function editar(u) {
    modalNuevo();

    document.getElementById("edit_id").value = u.id;
    document.getElementById("user").value = u.username;
    document.getElementById("rol").value = u.rol;

    // Cambia nombres para edición
    document.querySelector('[name="nuevo_usuario"]').name = "editar_usuario";
    document.querySelector('[name="nuevo_rol"]').name = "editar_rol";
}
</script>