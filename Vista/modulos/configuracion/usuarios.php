<?php
// Vista/modulos/configuracion/usuarios.php

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 3) . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'Controlador/CtrUsuario.php';

$titulo_pagina = "Usuarios | Configuración";
$menu_activo = "usuarios";

require_once ROOT_PATH . 'Vista/includes/header.php';
require_once ROOT_PATH . 'Vista/includes/sidebar.php';

$ctr = new CtrUsuario();

$ctr->ctrCrearUsuario();
$ctr->ctrEditarUsuario();
$ctr->ctrEstadoUsuario();

$usuarios = $ctr->ctrListarUsuarios();

if (!is_array($usuarios)) {
    $usuarios = [];
}

if (!function_exists('usuarios_h')) {
    function usuarios_h($valor): string
    {
        return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('usuarios_minuscula')) {
    function usuarios_minuscula($valor): string
    {
        $valor = trim((string)$valor);

        if (function_exists('mb_strtolower')) {
            return mb_strtolower($valor, 'UTF-8');
        }

        return strtolower($valor);
    }
}

if (!function_exists('usuarios_mayuscula')) {
    function usuarios_mayuscula($valor): string
    {
        $valor = trim((string)$valor);

        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($valor, 'UTF-8');
        }

        return strtoupper($valor);
    }
}

if (!function_exists('usuarios_rol_etiqueta')) {
    function usuarios_rol_etiqueta($rol): string
    {
        $rol = usuarios_minuscula($rol);

        switch ($rol) {
            case 'superadmin':
                return 'Superadmin';
            case 'admin':
                return 'Admin';
            case 'rrhh':
                return 'RRHH';
            case 'colaborador':
                return 'Colaborador';
            default:
                return ucfirst($rol);
        }
    }
}

if (!function_exists('usuarios_rol_clase')) {
    function usuarios_rol_clase($rol): string
    {
        $rol = usuarios_minuscula($rol);

        switch ($rol) {
            case 'superadmin':
                return 'bg-purple-50 text-purple-700 ring-purple-200';
            case 'admin':
                return 'bg-[#7A0C19]/10 text-[#7A0C19] ring-[#7A0C19]/20';
            case 'rrhh':
                return 'bg-blue-50 text-blue-700 ring-blue-200';
            case 'colaborador':
                return 'bg-slate-100 text-slate-700 ring-slate-200';
            default:
                return 'bg-gray-100 text-gray-700 ring-gray-200';
        }
    }
}

if (!function_exists('usuarios_iniciales')) {
    function usuarios_iniciales($nombre, $username): string
    {
        $base = trim((string)($nombre ?: $username));

        if ($base === '') {
            return 'U';
        }

        $partes = preg_split('/\s+/', $base);
        $iniciales = '';

        foreach ($partes as $p) {
            if ($p === '') {
                continue;
            }

            if (function_exists('mb_substr')) {
                $iniciales .= mb_substr($p, 0, 1, 'UTF-8');
            } else {
                $iniciales .= substr($p, 0, 1);
            }

            if (strlen($iniciales) >= 2) {
                break;
            }
        }

        return usuarios_mayuscula($iniciales);
    }
}
?>

<main class="flex-1 h-screen overflow-hidden bg-slate-50">
    <section class="h-full p-4 lg:p-6 flex flex-col">

        <!-- CABECERA -->
        <div class="mb-4 rounded-[1.6rem] bg-gradient-to-r from-[#5E0712] via-[#7A0C19] to-[#9F1239] shadow-lg shadow-[#7A0C19]/15 overflow-hidden shrink-0">
            <div class="relative p-5 lg:p-6">
                <div class="absolute -right-20 -top-24 w-72 h-72 rounded-full bg-white/10"></div>
                <div class="absolute right-44 -bottom-28 w-72 h-72 rounded-full bg-white/10"></div>

                <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 text-white/90 text-[11px] font-black uppercase tracking-wide mb-3">
                            Configuración
                        </div>

                        <h1 class="text-2xl lg:text-3xl font-black text-white tracking-tight">
                            Gestión de usuarios
                        </h1>

                        <p class="text-white/75 mt-1 max-w-2xl text-sm">
                            Administra las cuentas de acceso, roles y estado de usuarios del sistema.
                        </p>
                    </div>

                    <button type="button"
                        onclick="modalNuevo()"
                        class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-white text-[#7A0C19] font-black shadow-lg hover:scale-[1.02] active:scale-[0.98] transition">
                        <span class="text-xl leading-none">+</span>
                        Nuevo usuario
                    </button>
                </div>
            </div>
        </div>

        <!-- PANEL PRINCIPAL -->
        <div class="bg-white rounded-[1.6rem] border border-slate-200 shadow-sm overflow-hidden flex-1 min-h-0 flex flex-col">

            <!-- FILTROS -->
            <div class="p-4 lg:p-5 border-b border-slate-200 bg-white shrink-0">
                <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-black text-slate-900">
                            Listado de usuarios
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Busca, filtra y administra los usuarios registrados.
                        </p>
                    </div>

                    <div class="flex flex-col md:flex-row gap-3 w-full xl:w-auto">
                        <div class="relative w-full md:w-80">
                            <input id="buscarUsuario"
                                type="text"
                                placeholder="Buscar usuario, nombre o rol..."
                                class="w-full pl-11 pr-4 py-3 rounded-2xl border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-[#7A0C19] focus:ring-4 focus:ring-[#7A0C19]/10 transition">

                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 6.05 6.05a7.5 7.5 0 0 0 10.6 10.6Z" />
                                </svg>
                            </span>
                        </div>

                        <select id="filtroRol"
                            class="px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50 text-sm font-bold outline-none focus:bg-white focus:border-[#7A0C19] focus:ring-4 focus:ring-[#7A0C19]/10 transition">
                            <option value="">Todos los roles</option>
                            <option value="superadmin">Superadmin</option>
                            <option value="admin">Admin</option>
                            <option value="rrhh">RRHH</option>
                            <option value="colaborador">Colaborador</option>
                        </select>

                        <select id="filtroEstado"
                            class="px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50 text-sm font-bold outline-none focus:bg-white focus:border-[#7A0C19] focus:ring-4 focus:ring-[#7A0C19]/10 transition">
                            <option value="">Todos los estados</option>
                            <option value="1">Activos</option>
                            <option value="0">Inactivos</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- TABLA CON SCROLL INTERNO -->
            <div id="contenedorTablaUsuarios" class="flex-1 min-h-0 overflow-auto">
                <table class="w-full min-w-[960px] text-sm">
                    <thead class="sticky top-0 z-20">
                        <tr class="bg-slate-100 text-slate-500 uppercase text-xs tracking-wide border-b border-slate-200">
                            <th class="px-6 py-4 text-left font-black">Usuario</th>
                            <th class="px-6 py-4 text-left font-black">Nombre completo</th>
                            <th class="px-6 py-4 text-left font-black">Rol</th>
                            <th class="px-6 py-4 text-left font-black">Estado</th>
                            <th class="px-6 py-4 text-center font-black">Acciones</th>
                        </tr>
                    </thead>

                    <tbody id="tablaUsuarios" class="divide-y divide-slate-100">
                        <?php if (empty($usuarios)): ?>
                            <tr id="filaVacia">
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <div class="mx-auto w-16 h-16 rounded-3xl bg-slate-100 flex items-center justify-center text-slate-400 mb-4">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a8.25 8.25 0 0 1 15 0" />
                                        </svg>
                                    </div>
                                    <p class="text-slate-900 font-black">No hay usuarios registrados</p>
                                    <p class="text-slate-500 text-sm mt-1">Crea el primer usuario del sistema.</p>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($usuarios as $u): ?>
                            <?php
                            $id = (int)($u["id"] ?? 0);
                            $username = (string)($u["username"] ?? '');
                            $nombre = (string)($u["nombres_apellidos"] ?? '');
                            $rol = usuarios_minuscula($u["rol"] ?? '');
                            $estado = (int)($u["estado"] ?? 0);

                            $jsonEditar = json_encode([
                                "id" => $id,
                                "username" => $username,
                                "rol" => $rol,
                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

                            $textoBuscar = usuarios_minuscula(
                                $username . ' ' .
                                $nombre . ' ' .
                                $rol . ' ' .
                                usuarios_rol_etiqueta($rol)
                            );
                            ?>

                            <tr class="usuario-row hover:bg-slate-50 transition"
                                data-search="<?= usuarios_h($textoBuscar) ?>"
                                data-rol="<?= usuarios_h($rol) ?>"
                                data-estado="<?= $estado ?>">

                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-[#7A0C19] to-[#9F1239] text-white flex items-center justify-center font-black shadow-sm">
                                            <?= usuarios_h(usuarios_iniciales($nombre, $username)) ?>
                                        </div>

                                        <div>
                                            <p class="font-black text-slate-900">
                                                <?= usuarios_h($username) ?>
                                            </p>
                                            <p class="text-xs text-slate-400">
                                                ID: <?= $id ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <?php if ($nombre !== ''): ?>
                                        <p class="font-semibold text-slate-700">
                                            <?= usuarios_h($nombre) ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="font-semibold text-slate-400">
                                            Sin vincular a colaborador
                                        </p>
                                    <?php endif; ?>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-black ring-1 <?= usuarios_rol_clase($rol) ?>">
                                        <?= usuarios_h(usuarios_rol_etiqueta($rol)) ?>
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <?php if ($estado === 1): ?>
                                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-black ring-1 ring-emerald-200">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-50 text-red-700 text-xs font-black ring-1 ring-red-200">
                                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                            Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button"
                                            onclick='editar(<?= $jsonEditar ?>)'
                                            class="inline-flex items-center justify-center px-3 py-2 rounded-xl bg-blue-50 text-blue-700 text-xs font-black hover:bg-blue-100 transition">
                                            Editar
                                        </button>

                                        <?php if ($estado === 1): ?>
                                            <a href="<?= BASE_URL ?>/configuracion/usuarios?estado_id=<?= $id ?>&estado_val=0"
                                                onclick="return confirm('¿Deseas desactivar este usuario?')"
                                                class="inline-flex items-center justify-center px-3 py-2 rounded-xl bg-red-50 text-red-700 text-xs font-black hover:bg-red-100 transition">
                                                Desactivar
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>/configuracion/usuarios?estado_id=<?= $id ?>&estado_val=1"
                                                onclick="return confirm('¿Deseas activar este usuario?')"
                                                class="inline-flex items-center justify-center px-3 py-2 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-black hover:bg-emerald-100 transition">
                                                Activar
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <tr id="sinResultados" class="hidden">
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="mx-auto w-16 h-16 rounded-3xl bg-slate-100 flex items-center justify-center text-slate-400 mb-4">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3Z" />
                                    </svg>
                                </div>
                                <p class="font-black text-slate-800">No se encontraron resultados</p>
                                <p class="text-sm text-slate-500 mt-1">Prueba con otro usuario, nombre, rol o estado.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINACIÓN FIJA ABAJO -->
            <div class="px-4 lg:px-5 py-3 border-t border-slate-200 bg-slate-50 shrink-0 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">
                <div class="text-sm text-slate-500">
                    Mostrando
                    <span id="pagDesde" class="font-black text-slate-900">0</span>
                    -
                    <span id="pagHasta" class="font-black text-slate-900">0</span>
                    de
                    <span id="pagTotal" class="font-black text-slate-900">0</span>
                    usuarios
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <select id="porPagina"
                        class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm font-bold outline-none focus:border-[#7A0C19]">
                        <option value="5">5 por página</option>
                        <option value="10" selected>10 por página</option>
                        <option value="20">20 por página</option>
                        <option value="50">50 por página</option>
                        <option value="99999">Todos</option>
                    </select>

                    <button type="button" id="btnPrimera"
                        class="px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-sm font-black hover:bg-slate-100 transition">
                        «
                    </button>

                    <button type="button" id="btnAnterior"
                        class="px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-sm font-black hover:bg-slate-100 transition">
                        Anterior
                    </button>

                    <span class="px-3 py-2 text-sm font-black text-slate-700">
                        Página <span id="paginaActual">1</span> de <span id="totalPaginas">1</span>
                    </span>

                    <button type="button" id="btnSiguiente"
                        class="px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-sm font-black hover:bg-slate-100 transition">
                        Siguiente
                    </button>

                    <button type="button" id="btnUltima"
                        class="px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-sm font-black hover:bg-slate-100 transition">
                        »
                    </button>
                </div>
            </div>

        </div>
    </section>
</main>

<!-- MODAL -->
<div id="modal" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" onclick="cerrar()"></div>

    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-lg bg-white rounded-[2rem] shadow-2xl overflow-hidden">

            <div class="bg-gradient-to-r from-[#5E0712] to-[#9F1239] px-6 py-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 id="modalTitulo" class="text-xl font-black text-white">
                            Nuevo usuario
                        </h2>
                        <p id="modalSubtitulo" class="text-sm text-white/75 mt-1">
                            Registra una cuenta de acceso al sistema.
                        </p>
                    </div>

                    <button type="button"
                        onclick="cerrar()"
                        class="w-10 h-10 rounded-2xl bg-white/15 text-white text-2xl leading-none hover:bg-white/25 transition">
                        ×
                    </button>
                </div>
            </div>

            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="editar_id" id="edit_id">

                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wide mb-2">
                        Usuario
                    </label>
                    <input type="text"
                        name="nuevo_usuario"
                        id="user"
                        placeholder="Ejemplo: 12345678"
                        autocomplete="off"
                        required
                        class="w-full border border-slate-200 bg-slate-50 px-4 py-3 rounded-2xl outline-none focus:bg-white focus:border-[#7A0C19] focus:ring-4 focus:ring-[#7A0C19]/10 transition">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wide mb-2">
                        Contraseña
                    </label>
                    <input type="password"
                        name="nuevo_password"
                        id="pass"
                        placeholder="Contraseña"
                        autocomplete="new-password"
                        required
                        class="w-full border border-slate-200 bg-slate-50 px-4 py-3 rounded-2xl outline-none focus:bg-white focus:border-[#7A0C19] focus:ring-4 focus:ring-[#7A0C19]/10 transition">

                    <p id="passHelp" class="text-xs text-slate-400 mt-2">
                        Para usuarios nuevos, la contraseña es obligatoria.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wide mb-2">
                        Rol
                    </label>

                    <select name="nuevo_rol"
                        id="rol"
                        required
                        class="w-full border border-slate-200 bg-slate-50 px-4 py-3 rounded-2xl outline-none focus:bg-white focus:border-[#7A0C19] focus:ring-4 focus:ring-[#7A0C19]/10 transition">
                        <option value="superadmin">Superadmin</option>
                        <option value="admin" selected>Admin</option>
                        <option value="rrhh">RRHH</option>
                        <option value="colaborador">Colaborador</option>
                    </select>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button"
                        onclick="cerrar()"
                        class="px-5 py-3 rounded-2xl bg-slate-100 text-slate-700 font-black hover:bg-slate-200 transition">
                        Cancelar
                    </button>

                    <button type="submit"
                        class="px-5 py-3 rounded-2xl bg-[#7A0C19] text-white font-black shadow-lg shadow-[#7A0C19]/20 hover:bg-[#5E0712] transition">
                        Guardar usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let paginaUsuarios = 1;

function modalNuevo() {
    const modal = document.getElementById("modal");
    const editId = document.getElementById("edit_id");
    const user = document.getElementById("user");
    const pass = document.getElementById("pass");
    const rol = document.getElementById("rol");

    document.getElementById("modalTitulo").textContent = "Nuevo usuario";
    document.getElementById("modalSubtitulo").textContent = "Registra una cuenta de acceso al sistema.";
    document.getElementById("passHelp").textContent = "Para usuarios nuevos, la contraseña es obligatoria.";

    modal.classList.remove("hidden");

    editId.value = "";
    user.value = "";
    pass.value = "";
    rol.value = "admin";

    user.name = "nuevo_usuario";
    pass.name = "nuevo_password";
    rol.name = "nuevo_rol";

    user.required = true;
    pass.required = true;
    rol.required = true;

    pass.placeholder = "Contraseña";

    setTimeout(() => user.focus(), 100);
}

function cerrar() {
    document.getElementById("modal").classList.add("hidden");
}

function editar(u) {
    const modal = document.getElementById("modal");
    const editId = document.getElementById("edit_id");
    const user = document.getElementById("user");
    const pass = document.getElementById("pass");
    const rol = document.getElementById("rol");

    document.getElementById("modalTitulo").textContent = "Editar usuario";
    document.getElementById("modalSubtitulo").textContent = "Actualiza el rol o cambia la contraseña si es necesario.";
    document.getElementById("passHelp").textContent = "Déjalo vacío si no deseas cambiar la contraseña.";

    modal.classList.remove("hidden");

    editId.value = u.id || "";
    user.value = u.username || "";
    pass.value = "";
    rol.value = u.rol || "colaborador";

    user.name = "editar_usuario";
    pass.name = "editar_password";
    rol.name = "editar_rol";

    user.required = true;
    pass.required = false;
    rol.required = true;

    pass.placeholder = "Nueva contraseña opcional";

    setTimeout(() => user.focus(), 100);
}

function obtenerFilasFiltradas() {
    const q = (document.getElementById("buscarUsuario")?.value || "").toLowerCase().trim();
    const rol = document.getElementById("filtroRol")?.value || "";
    const estado = document.getElementById("filtroEstado")?.value || "";

    const filas = Array.from(document.querySelectorAll(".usuario-row"));

    return filas.filter(fila => {
        const texto = fila.dataset.search || "";
        const filaRol = fila.dataset.rol || "";
        const filaEstado = fila.dataset.estado || "";

        const coincideTexto = q === "" || texto.includes(q);
        const coincideRol = rol === "" || filaRol === rol;
        const coincideEstado = estado === "" || filaEstado === estado;

        return coincideTexto && coincideRol && coincideEstado;
    });
}

function actualizarBoton(id, deshabilitado) {
    const btn = document.getElementById(id);

    if (!btn) {
        return;
    }

    btn.disabled = deshabilitado;
    btn.classList.toggle("opacity-40", deshabilitado);
    btn.classList.toggle("cursor-not-allowed", deshabilitado);
}

function filtrarUsuarios(resetPagina = true) {
    const filas = Array.from(document.querySelectorAll(".usuario-row"));
    const filasFiltradas = obtenerFilasFiltradas();
    const sinResultados = document.getElementById("sinResultados");
    const filaVacia = document.getElementById("filaVacia");

    const porPagina = parseInt(document.getElementById("porPagina")?.value || "10", 10);
    const total = filasFiltradas.length;
    const totalPaginas = Math.max(1, Math.ceil(total / porPagina));

    if (resetPagina) {
        paginaUsuarios = 1;
    }

    if (paginaUsuarios > totalPaginas) {
        paginaUsuarios = totalPaginas;
    }

    const inicio = (paginaUsuarios - 1) * porPagina;
    const fin = inicio + porPagina;

    filas.forEach(fila => fila.classList.add("hidden"));

    filasFiltradas.slice(inicio, fin).forEach(fila => {
        fila.classList.remove("hidden");
    });

    if (filaVacia) {
        filaVacia.classList.toggle("hidden", filas.length > 0);
    }

    if (sinResultados) {
        sinResultados.classList.toggle("hidden", total > 0 || filas.length === 0);
    }

    const desde = total === 0 ? 0 : inicio + 1;
    const hasta = Math.min(fin, total);

    document.getElementById("pagDesde").textContent = desde;
    document.getElementById("pagHasta").textContent = hasta;
    document.getElementById("pagTotal").textContent = total;
    document.getElementById("paginaActual").textContent = paginaUsuarios;
    document.getElementById("totalPaginas").textContent = totalPaginas;

    actualizarBoton("btnPrimera", paginaUsuarios <= 1);
    actualizarBoton("btnAnterior", paginaUsuarios <= 1);
    actualizarBoton("btnSiguiente", paginaUsuarios >= totalPaginas);
    actualizarBoton("btnUltima", paginaUsuarios >= totalPaginas);

    const contenedor = document.getElementById("contenedorTablaUsuarios");

    if (contenedor) {
        contenedor.scrollTop = 0;
    }
}

document.getElementById("buscarUsuario")?.addEventListener("input", function() {
    filtrarUsuarios(true);
});

document.getElementById("filtroRol")?.addEventListener("change", function() {
    filtrarUsuarios(true);
});

document.getElementById("filtroEstado")?.addEventListener("change", function() {
    filtrarUsuarios(true);
});

document.getElementById("porPagina")?.addEventListener("change", function() {
    filtrarUsuarios(true);
});

document.getElementById("btnPrimera")?.addEventListener("click", function() {
    paginaUsuarios = 1;
    filtrarUsuarios(false);
});

document.getElementById("btnAnterior")?.addEventListener("click", function() {
    if (paginaUsuarios > 1) {
        paginaUsuarios--;
        filtrarUsuarios(false);
    }
});

document.getElementById("btnSiguiente")?.addEventListener("click", function() {
    const porPagina = parseInt(document.getElementById("porPagina")?.value || "10", 10);
    const total = obtenerFilasFiltradas().length;
    const totalPaginas = Math.max(1, Math.ceil(total / porPagina));

    if (paginaUsuarios < totalPaginas) {
        paginaUsuarios++;
        filtrarUsuarios(false);
    }
});

document.getElementById("btnUltima")?.addEventListener("click", function() {
    const porPagina = parseInt(document.getElementById("porPagina")?.value || "10", 10);
    const total = obtenerFilasFiltradas().length;

    paginaUsuarios = Math.max(1, Math.ceil(total / porPagina));
    filtrarUsuarios(false);
});

document.addEventListener("keydown", function(e) {
    if (e.key === "Escape") {
        cerrar();
    }
});

filtrarUsuarios(true);
</script>