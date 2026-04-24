<?php
require_once ROOT_PATH . 'Controlador/CtrDirectorio.php';
require_once ROOT_PATH . 'Modelo/MdDirectorio.php';

$controlador = new CtrDirectorio();
$empleados = $controlador->ctrMostrarDirectorio();

if (!is_array($empleados)) {
    $empleados = [];
}

$titulo_pagina = "Directorio de Personal - RRHH";
$menu_activo = "directorio";

require_once ROOT_PATH . 'Vista/includes/header.php';
require_once ROOT_PATH . 'Vista/includes/sidebar.php';

$totalEmpleados = count($empleados);
?>

<main class="flex-1 flex flex-col h-screen overflow-hidden bg-[#f8fafc]">

    <header class="bg-white/90 backdrop-blur-xl border-b border-slate-200/80 z-20">
        <div class="px-4 md:px-8 py-5">
            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-5">

                <div>
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-red-900 text-white flex items-center justify-center shadow-lg shadow-red-900/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m8-4a4 4 0 10-8 0 4 4 0 008 0z" />
                            </svg>
                        </div>

                        <div>
                            <h1 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">
                                Directorio de Personal
                            </h1>
                            <p class="text-xs md:text-sm text-slate-500 font-medium mt-0.5">
                                Gestión y consulta rápida de colaboradores
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row gap-3 w-full xl:w-auto xl:min-w-[720px]">

                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>

                        <input type="text"
                            id="searchInput"
                            autocomplete="off"
                            class="block w-full pl-12 pr-12 py-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-sm font-semibold text-slate-700 placeholder-slate-400 outline-none focus:bg-white focus:border-red-900 focus:ring-4 focus:ring-red-900/10 transition-all"
                            placeholder="Buscar por nombre, DNI, puesto, área, correo o celular...">

                        <button type="button"
                            id="clearSearch"
                            class="hidden absolute inset-y-0 right-0 pr-4 items-center text-slate-400 hover:text-red-900 transition"
                            title="Limpiar búsqueda">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <button class="bg-red-900 hover:bg-[#4c0505] text-white px-5 py-3.5 rounded-2xl font-black shadow-xl shadow-red-900/20 transition-all flex items-center justify-center active:scale-95">
                        <span class="mr-2 text-xl leading-none">+</span>
                        <span class="whitespace-nowrap text-sm">Nuevo Empleado</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div class="p-4 md:p-8 flex-1 overflow-y-auto">

        <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/60 border border-slate-200 overflow-hidden">

            <div class="px-5 md:px-6 py-4 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h2 class="text-sm font-black text-slate-800 uppercase tracking-wide">
                        Lista de colaboradores
                    </h2>
                    <p class="text-xs text-slate-400 font-medium">
                        Mostrando
                        <span id="rangeInfo" class="font-black text-red-900">0</span>
                        de
                        <span id="resultCount" class="font-black text-slate-700"><?php echo $totalEmpleados; ?></span>
                        colaboradores
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest">
                        Ver
                    </label>

                    <select id="pageSize"
                        class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-black text-slate-700 outline-none focus:border-red-900 focus:ring-4 focus:ring-red-900/10">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>

                    <span class="hidden md:inline-flex px-3 py-1 rounded-full bg-red-50 text-red-900 text-[10px] font-black uppercase tracking-widest border border-red-100">
                        RRHH
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table id="directorioTable" class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Colaborador</th>
                            <th class="hidden sm:table-cell px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Puesto / Área</th>
                            <th class="hidden lg:table-cell px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Contacto</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Estado</th>
                            <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        <?php foreach ($empleados as $row): ?>
                            <?php
                            $nombre = $row['nombres_apellidos'] ?? '';
                            $dni = $row['dni'] ?? '';
                            $puesto = $row['puesto_cas'] ?? '';
                            $area = $row['area'] ?? '';
                            $correo = $row['correo_institucional'] ?? '';
                            $celular = $row['celular'] ?? '';
                            $situacion = $row['situacion'] ?? 'Activo';

                            $searchData = mb_strtolower(trim($nombre . ' ' . $dni . ' ' . $puesto . ' ' . $area . ' ' . $correo . ' ' . $celular . ' ' . $situacion), 'UTF-8');
                            $isBaja = mb_strtolower($situacion, 'UTF-8') === 'baja';

                            $color = $isBaja
                                ? 'bg-slate-100 text-slate-500 border-slate-200'
                                : 'bg-green-50 text-green-700 border-green-100';
                            ?>

                            <tr class="directorio-row hover:bg-red-50/30 transition-all group"
                                data-search="<?php echo htmlspecialchars($searchData, ENT_QUOTES, 'UTF-8'); ?>">

                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex h-11 w-11 rounded-2xl bg-gradient-to-br from-red-900 to-[#4c0505] flex-shrink-0 items-center justify-center text-white font-black mr-3 shadow-lg shadow-red-900/20 group-hover:scale-105 group-hover:rotate-3 transition-all">
                                            <?php echo htmlspecialchars(mb_substr($nombre ?: 'C', 0, 1, 'UTF-8')); ?>
                                        </div>

                                        <div class="overflow-hidden">
                                            <div class="text-sm font-black text-slate-800 truncate max-w-[170px] md:max-w-[320px]">
                                                <?php echo htmlspecialchars($nombre ?: 'Sin nombre'); ?>
                                            </div>

                                            <div class="text-[11px] text-slate-400 font-bold tracking-wide">
                                                DNI: <?php echo htmlspecialchars($dni ?: 'No registrado'); ?>
                                            </div>

                                            <div class="sm:hidden text-[10px] text-red-800 font-black mt-1 uppercase truncate max-w-[180px]">
                                                <?php echo htmlspecialchars($puesto ?: 'Sin puesto'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="hidden sm:table-cell px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-slate-700 font-black">
                                        <?php echo htmlspecialchars($puesto ?: 'Sin puesto'); ?>
                                    </div>
                                    <div class="text-xs text-slate-400 font-semibold">
                                        <?php echo htmlspecialchars($area ?: 'Sin área'); ?>
                                    </div>
                                </td>

                                <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-xs text-slate-600 font-semibold flex items-center">
                                            <span class="w-1.5 h-1.5 bg-red-400 rounded-full mr-2"></span>
                                            <?php echo htmlspecialchars($correo ?: 'Sin correo'); ?>
                                        </span>

                                        <span class="text-[11px] text-slate-400 font-semibold flex items-center">
                                            <span class="w-1.5 h-1.5 bg-slate-300 rounded-full mr-2"></span>
                                            <?php echo htmlspecialchars($celular ?: 'Sin celular'); ?>
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider border <?php echo $color; ?>">
                                        <?php echo htmlspecialchars($situacion ?: 'Activo'); ?>
                                    </span>
                                </td>

                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <a href="perfil/<?php echo (int)$row['id']; ?>"
                                            class="p-2.5 bg-red-50 text-red-900 rounded-xl hover:bg-red-900 hover:text-white transition-all shadow-sm border border-red-100"
                                            title="Ver Perfil">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <tr id="emptyState" class="hidden">
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="mx-auto w-16 h-16 rounded-3xl bg-red-50 text-red-900 flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>

                                <h3 class="text-lg font-black text-slate-800">
                                    No se encontraron resultados
                                </h3>

                                <p class="text-sm text-slate-400 mt-1">
                                    Intenta buscar por nombre, DNI, puesto, área, correo o celular.
                                </p>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

            <div class="px-5 md:px-6 py-4 border-t border-slate-100 bg-slate-50/70 flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                <div class="text-xs font-bold text-slate-400">
                    Página <span id="currentPageLabel" class="text-slate-700 font-black">1</span>
                    de <span id="totalPagesLabel" class="text-slate-700 font-black">1</span>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button type="button"
                        id="prevPage"
                        class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-black text-slate-600 hover:bg-red-900 hover:text-white hover:border-red-900 transition disabled:opacity-40 disabled:hover:bg-white disabled:hover:text-slate-600 disabled:hover:border-slate-200">
                        Anterior
                    </button>

                    <div id="paginationNumbers" class="flex items-center gap-1"></div>

                    <button type="button"
                        id="nextPage"
                        class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-black text-slate-600 hover:bg-red-900 hover:text-white hover:border-red-900 transition disabled:opacity-40 disabled:hover:bg-white disabled:hover:text-slate-600 disabled:hover:border-slate-200">
                        Siguiente
                    </button>
                </div>

            </div>

        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('searchInput');
    const clearBtn = document.getElementById('clearSearch');
    const rows = Array.from(document.querySelectorAll('.directorio-row'));
    const emptyState = document.getElementById('emptyState');
    const resultCount = document.getElementById('resultCount');
    const rangeInfo = document.getElementById('rangeInfo');
    const pageSizeSelect = document.getElementById('pageSize');
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');
    const paginationNumbers = document.getElementById('paginationNumbers');
    const currentPageLabel = document.getElementById('currentPageLabel');
    const totalPagesLabel = document.getElementById('totalPagesLabel');

    let currentPage = 1;
    let filteredRows = rows;

    function normalizeText(text) {
        return (text || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function getPageSize() {
        return parseInt(pageSizeSelect.value, 10) || 25;
    }

    function applyFilter() {
        const query = normalizeText(input.value);

        filteredRows = rows.filter(row => {
            const data = normalizeText(row.dataset.search);
            return query === '' || data.includes(query);
        });

        currentPage = 1;
        renderTable();

        clearBtn.classList.toggle('hidden', query.length === 0);
        clearBtn.classList.toggle('flex', query.length > 0);
    }

    function renderTable() {
        const pageSize = getPageSize();
        const totalResults = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(totalResults / pageSize));

        if (currentPage > totalPages) {
            currentPage = totalPages;
        }

        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;
        const visibleRows = filteredRows.slice(start, end);

        rows.forEach(row => row.classList.add('hidden'));
        visibleRows.forEach(row => row.classList.remove('hidden'));

        const showingFrom = totalResults === 0 ? 0 : start + 1;
        const showingTo = Math.min(end, totalResults);

        if (resultCount) resultCount.textContent = totalResults;
        if (rangeInfo) rangeInfo.textContent = totalResults === 0 ? '0' : `${showingFrom}-${showingTo}`;
        if (emptyState) emptyState.classList.toggle('hidden', totalResults !== 0);

        currentPageLabel.textContent = totalResults === 0 ? '0' : currentPage;
        totalPagesLabel.textContent = totalResults === 0 ? '0' : totalPages;

        prevBtn.disabled = currentPage <= 1;
        nextBtn.disabled = currentPage >= totalPages || totalResults === 0;

        renderPaginationNumbers(totalPages, totalResults);
    }

    function renderPaginationNumbers(totalPages, totalResults) {
        paginationNumbers.innerHTML = '';

        if (totalResults === 0) return;

        const maxButtons = 5;
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, startPage + maxButtons - 1);

        if (endPage - startPage < maxButtons - 1) {
            startPage = Math.max(1, endPage - maxButtons + 1);
        }

        for (let page = startPage; page <= endPage; page++) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = page;

            btn.className = page === currentPage
                ? 'w-9 h-9 rounded-xl bg-red-900 text-white text-xs font-black shadow-lg shadow-red-900/20'
                : 'w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-600 text-xs font-black hover:bg-red-50 hover:text-red-900 transition';

            btn.addEventListener('click', function () {
                currentPage = page;
                renderTable();
            });

            paginationNumbers.appendChild(btn);
        }
    }

    input.addEventListener('input', applyFilter);

    clearBtn.addEventListener('click', function () {
        input.value = '';
        input.focus();
        applyFilter();
    });

    pageSizeSelect.addEventListener('change', function () {
        currentPage = 1;
        renderTable();
    });

    prevBtn.addEventListener('click', function () {
        if (currentPage > 1) {
            currentPage--;
            renderTable();
        }
    });

    nextBtn.addEventListener('click', function () {
        const totalPages = Math.ceil(filteredRows.length / getPageSize());

        if (currentPage < totalPages) {
            currentPage++;
            renderTable();
        }
    });

    applyFilter();
});
</script>