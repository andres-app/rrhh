<?php
// Vista/modulos/rrhh/directorio.php

require_once ROOT_PATH . 'Controlador/CtrDirectorio.php';
require_once ROOT_PATH . 'Modelo/MdDirectorio.php';

$controlador = new CtrDirectorio();

function dirNormalizarTexto($valor): string
{
    $valor = trim((string)$valor);
    return function_exists('mb_strtolower')
        ? mb_strtolower($valor, 'UTF-8')
        : strtolower($valor);
}

function dirTipoPersonalExcel(array $row): string
{
    $modalidadPrincipal = strtoupper(trim((string)(
        $row['modalidad_contrato']
        ?? $row['mod_contrato']
        ?? $row['modalidad']
        ?? $row['tipo_personal']
        ?? ''
    )));

    if (in_array($modalidadPrincipal, ['CAS', 'MILITAR', 'PAC'], true)) {
        return $modalidadPrincipal;
    }

    $fuente = strtoupper(trim(implode(' ', array_filter([
        $row['modalidad_contrato'] ?? '',
        $row['mod_contrato'] ?? '',
        $row['modalidad'] ?? '',
        $row['tipo_personal'] ?? '',
        $row['tipo_puesto'] ?? '',
        $row['grado_militar'] ?? '',
        $row['procedencia'] ?? '',
        $row['puesto_cas'] ?? '',
    ]))));

    if (
        str_contains($fuente, 'MILITAR') ||
        str_contains($fuente, 'FFAA') ||
        str_contains($fuente, 'FAP') ||
        str_contains($fuente, 'EP') ||
        str_contains($fuente, 'MGP') ||
        str_contains($fuente, 'EJERCITO') ||
        str_contains($fuente, 'EJÉRCITO') ||
        str_contains($fuente, 'MARINA') ||
        str_contains($fuente, 'FUERZA AEREA') ||
        str_contains($fuente, 'FUERZA AÉREA')
    ) {
        return 'MILITAR';
    }

    if (str_contains($fuente, 'PAC')) {
        return 'PAC';
    }

    if (
        str_contains($fuente, 'CAS') ||
        str_contains($fuente, '1057') ||
        str_contains($fuente, 'D.L. 1057') ||
        str_contains($fuente, 'DL 1057')
    ) {
        return 'CAS';
    }

    return 'SIN TIPO';
}

function dirFiltrarExcel(array $rows): array
{
    $q = dirNormalizarTexto($_GET['q'] ?? '');
    $tipo = strtoupper(trim((string)($_GET['tipo_personal'] ?? '')));
    $situacion = strtoupper(trim((string)($_GET['situacion'] ?? '')));
    $area = dirNormalizarTexto($_GET['area'] ?? '');

    return array_values(array_filter($rows, function ($row) use ($q, $tipo, $situacion, $area) {
        $tipoPersonal = dirTipoPersonalExcel($row);

        $situacionRow = strtoupper(trim((string)($row['situacion'] ?? 'ACTIVO')));
        if ($situacionRow === '') {
            $situacionRow = 'ACTIVO';
        }

        $areaRow = dirNormalizarTexto($row['area'] ?? '');

        $searchData = dirNormalizarTexto(implode(' ', [
            $row['nombres_apellidos'] ?? '',
            $row['dni'] ?? '',
            $row['puesto_cas'] ?? '',
            $row['area'] ?? '',
            $row['correo_institucional'] ?? '',
            $row['celular'] ?? '',
            $situacionRow,
            $tipoPersonal,
        ]));

        if ($q !== '' && !str_contains($searchData, $q)) {
            return false;
        }

        if ($tipo !== '' && $tipoPersonal !== $tipo) {
            return false;
        }

        if ($situacion !== '' && $situacionRow !== $situacion) {
            return false;
        }

        if ($area !== '' && $areaRow !== $area) {
            return false;
        }

        return true;
    }));
}

function dirValorExcel($valor): string
{
    $valor = (string)($valor ?? '');

    if (preg_match('/^[=+\-@]/', $valor)) {
        $valor = "'" . $valor;
    }

    return nl2br(htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
}

function dirDescargarExcel(array $rows): void
{
    $columnas = [
        'ID' => 'id',
        'DNI' => 'dni',
        'Nombres y apellidos' => 'nombres_apellidos',
        'Tipo de personal' => '_tipo_personal',
        'Situación' => 'situacion',
        'RUC' => 'ruc',
        'Licencia de conducir' => 'licencia_conducir',
        'Fecha de nacimiento' => 'fecha_nacimiento',
        'Lugar de nacimiento' => 'lugar_nacimiento',
        'Edad' => 'edad',
        'Sexo' => 'sexo',
        'Estado civil' => 'estado_civil',
        'Grupo sanguíneo' => 'grupo_sanguineo',
        'Talla' => 'talla',
        'Grado militar' => 'grado_militar',
        'Celular' => 'celular',
        'Correo personal' => 'correo_personal',
        'Dirección' => 'direccion_residencia',
        'Distrito' => 'distrito',
        'NSA / CIP' => 'nsa_cip',
        'Correo institucional' => 'correo_institucional',
        'Sueldo' => 'sueldo',
        'Modalidad contrato' => 'modalidad_contrato',
        'Puesto CAS' => 'puesto_cas',
        'Tipo puesto' => 'tipo_puesto',
        'Área' => 'area',
        'Procedencia' => 'procedencia',
        'Fecha ingreso' => 'fecha_ingreso',
        'Fecha cese' => 'fecha_cese',
        'N° hijos' => 'n_hijos',
        'Cónyuge' => 'conyuge',
        'Onomástico cónyuge' => 'onomastico_conyuge',
        'Profesión' => 'profesion',
        'Institución' => 'institucion',
        'Grado' => 'grado',
        'Curso / especialización' => 'curso_especializacion',
        'Sistema pensión' => 'sistema_pension',
        'AFP' => 'afp',
        'CUSPP' => 'cuspp',
        'Tipo comisión' => 'tipo_comision',
        'Fecha inscripción AFP' => 'fecha_inscripcion',
        'Sin AFP / por afiliarme' => 'sin_afp_afiliarme',
        'Banco haberes' => 'banco_haberes',
        'Número de cuenta' => 'numero_cuenta',
        'CCI' => 'numero_cuenta_cci',
    ];

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $filename = 'directorio_personal_' . date('Ymd_His') . '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF";
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '</head>';
    echo '<body>';
    echo '<table border="1">';
    echo '<thead><tr>';

    foreach ($columnas as $titulo => $campo) {
        echo '<th style="background:#7f1d1d;color:#ffffff;font-weight:bold;">' . dirValorExcel($titulo) . '</th>';
    }

    echo '</tr></thead>';
    echo '<tbody>';

    foreach ($rows as $row) {
        echo '<tr>';

        foreach ($columnas as $campo) {
            $valor = $campo === '_tipo_personal'
                ? dirTipoPersonalExcel($row)
                : ($row[$campo] ?? '');

            echo '<td style="mso-number-format:\'\@\'; vertical-align:top;">' . dirValorExcel($valor) . '</td>';
        }

        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</body>';
    echo '</html>';
    exit;
}

$uriActual = strtok($_SERVER['REQUEST_URI'] ?? '', '?');

$esExportExcel =
    (isset($_GET['export']) && $_GET['export'] === 'excel') ||
    str_ends_with($uriActual, '/rrhh/directorio/xlsx') ||
    str_ends_with($uriActual, '/directorio/xlsx') ||
    str_ends_with($uriActual, '/xlsx');

if ($esExportExcel) {
    $rolSesion = strtolower(trim($_SESSION['user_role'] ?? ''));

    if (!in_array($rolSesion, ['superadmin', 'admin', 'rrhh'], true)) {
        http_response_code(403);
        exit('No tienes permisos para exportar el directorio.');
    }

    $rowsExcel = $controlador->ctrMostrarDirectorioExcel();

    if (!is_array($rowsExcel)) {
        $rowsExcel = [];
    }

    $rowsExcel = dirFiltrarExcel($rowsExcel);
    dirDescargarExcel($rowsExcel);
}

$mensajeRegistro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_colaborador'])) {
    $mensajeRegistro = $controlador->ctrCrearColaborador($_POST);

    if (!empty($mensajeRegistro['success'])) {
        header("Location: " . BASE_URL . "/rrhh/directorio?creado=ok");
        exit();
    }
}

$empleados = $controlador->ctrMostrarDirectorio();

if (!is_array($empleados)) {
    $empleados = [];
}

$titulo_pagina = "Directorio de Personal - RRHH";
$menu_activo = "directorio";

require_once ROOT_PATH . 'Vista/includes/header.php';
require_once ROOT_PATH . 'Vista/includes/sidebar.php';

$totalEmpleados = count($empleados);

$areasFiltro = [];
$totalActivos = 0;

foreach ($empleados as $emp) {
    $areaTmp = trim((string)($emp['area'] ?? ''));

    if ($areaTmp !== '') {
        $areasFiltro[] = $areaTmp;
    }

    $situacionTmp = strtoupper(trim((string)($emp['situacion'] ?? 'ACTIVO')));

    if ($situacionTmp === '' || $situacionTmp === 'ACTIVO') {
        $totalActivos++;
    }
}

$areasFiltro = array_values(array_unique($areasFiltro));
sort($areasFiltro, SORT_NATURAL | SORT_FLAG_CASE);
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

                    <?php if (in_array(strtolower($_SESSION['user_role'] ?? ''), ['superadmin', 'admin', 'rrhh'], true)): ?>
                        <a href="<?= BASE_URL ?>/rrhh/directorio?export=excel"
                            id="btnExportExcel"
                            class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-emerald-600 text-white text-sm font-bold shadow-lg shadow-emerald-600/20 hover:bg-emerald-700 transition-all">
                            Exportar Excel
                        </a>
                        <button onclick="abrirDrawerNuevoColaborador()"
                            class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-red-900 text-white text-sm font-bold shadow-lg shadow-red-900/20 hover:bg-red-800 transition-all">
                            <span class="text-lg">+</span>
                            Nuevo colaborador
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <div class="p-4 md:p-8 flex-1 overflow-y-auto">

        <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/60 border border-slate-200 overflow-hidden">

            <div class="px-5 md:px-6 py-5 border-b border-slate-100 bg-white">
                <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">

                    <!-- Título compacto -->
                    <div class="min-w-[220px]">
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-black text-slate-800 uppercase tracking-wide">
                                Colaboradores
                            </h2>
                        </div>

                        <p class="text-xs text-slate-400 font-semibold mt-1">
                            <span id="rangeInfo" class="font-black text-red-900">0</span>
                            de
                            <span id="resultCount" class="font-black text-slate-700"><?php echo $totalActivos; ?></span>
                            registros
                        </p>
                    </div>

                    <!-- Filtros compactos -->
                    <div class="flex flex-col lg:flex-row lg:items-center gap-2 w-full xl:w-auto">

                        <select id="filterTipoPersonal"
                            class="w-full lg:w-[145px] rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-black text-slate-700 outline-none focus:bg-white focus:border-red-900 focus:ring-4 focus:ring-red-900/10 transition-all">
                            <option value="">Tipo: todos</option>
                            <option value="CAS">CAS</option>
                            <option value="MILITAR">MILITAR</option>
                            <option value="PAC">PAC</option>
                        </select>

                        <select id="filterSituacion"
                            class="w-full lg:w-[150px] rounded-2xl border border-green-200 bg-green-50 px-4 py-2.5 text-xs font-black text-green-700 outline-none focus:bg-white focus:border-green-700 focus:ring-4 focus:ring-green-700/10 transition-all">
                            <option value="">Situación: todos</option>
                            <option value="ACTIVO" selected>Activos</option>
                            <option value="CESADO">Cesados</option>
                        </select>

                        <select id="filterArea"
                            class="w-full lg:w-[180px] rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-black text-slate-700 outline-none focus:bg-white focus:border-red-900 focus:ring-4 focus:ring-red-900/10 transition-all">
                            <option value="">Área: todas</option>
                            <?php foreach ($areasFiltro as $areaFiltro): ?>
                                <option value="<?php echo htmlspecialchars(mb_strtolower(trim($areaFiltro), 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($areaFiltro); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select id="pageSize"
                            class="w-full lg:w-[105px] rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-700 outline-none focus:border-red-900 focus:ring-4 focus:ring-red-900/10">
                            <option value="10">Ver 10</option>
                            <option value="25" selected>Ver 25</option>
                            <option value="50">Ver 50</option>
                            <option value="100">Ver 100</option>
                        </select>

                        <button type="button" id="btnClearFilters"
                            class="inline-flex items-center justify-center px-4 py-2.5 rounded-2xl border border-slate-200 bg-white text-xs font-black text-slate-500 hover:bg-red-50 hover:text-red-900 hover:border-red-100 transition-all">
                            Limpiar
                        </button>

                    </div>

                </div>
            </div>

            <div class="overflow-x-auto">
                <table id="directorioTable" class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Colaborador</th>
                            <th class="hidden sm:table-cell px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Puesto / Área / Tipo</th>
                            <th class="hidden lg:table-cell px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Contacto</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Situación</th>
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

                            $situacionRaw = strtoupper(trim((string)($row['situacion'] ?? 'ACTIVO')));
                            $situacion = $situacionRaw !== '' ? $situacionRaw : 'ACTIVO';

                            if (in_array($situacion, ['BAJA', 'INACTIVO'], true)) {
                                $situacion = 'CESADO';
                            }

                            $modalidadPrincipal = strtoupper(trim((string)(
                                $row['modalidad_contrato']
                                ?? $row['mod_contrato']
                                ?? $row['modalidad']
                                ?? $row['tipo_personal']
                                ?? ''
                            )));

                            $tipoPersonal = 'SIN TIPO';

                            if (in_array($modalidadPrincipal, ['CAS', 'MILITAR', 'PAC'], true)) {
                                $tipoPersonal = $modalidadPrincipal;
                            } else {
                                $tipoPersonalFuente = strtoupper(trim(implode(' ', array_filter([
                                    $row['modalidad_contrato'] ?? '',
                                    $row['mod_contrato'] ?? '',
                                    $row['modalidad'] ?? '',
                                    $row['tipo_personal'] ?? '',
                                    $row['tipo_puesto'] ?? '',
                                    $row['grado_militar'] ?? '',
                                    $row['procedencia'] ?? '',
                                    $row['puesto_cas'] ?? '',
                                ]))));

                                if (
                                    str_contains($tipoPersonalFuente, 'MILITAR') ||
                                    str_contains($tipoPersonalFuente, 'FFAA') ||
                                    str_contains($tipoPersonalFuente, 'FAP') ||
                                    str_contains($tipoPersonalFuente, 'EP') ||
                                    str_contains($tipoPersonalFuente, 'MGP') ||
                                    str_contains($tipoPersonalFuente, 'EJERCITO') ||
                                    str_contains($tipoPersonalFuente, 'EJÉRCITO') ||
                                    str_contains($tipoPersonalFuente, 'MARINA') ||
                                    str_contains($tipoPersonalFuente, 'FUERZA AEREA') ||
                                    str_contains($tipoPersonalFuente, 'FUERZA AÉREA')
                                ) {
                                    $tipoPersonal = 'MILITAR';
                                } elseif (
                                    str_contains($tipoPersonalFuente, 'PAC')
                                ) {
                                    $tipoPersonal = 'PAC';
                                } elseif (
                                    str_contains($tipoPersonalFuente, 'CAS') ||
                                    str_contains($tipoPersonalFuente, '1057') ||
                                    str_contains($tipoPersonalFuente, 'D.L. 1057') ||
                                    str_contains($tipoPersonalFuente, 'DL 1057')
                                ) {
                                    $tipoPersonal = 'CAS';
                                }
                            }

                            $areaFiltroValor = mb_strtolower(trim($area), 'UTF-8');

                            $searchData = mb_strtolower(trim(
                                $nombre . ' ' .
                                    $dni . ' ' .
                                    $puesto . ' ' .
                                    $area . ' ' .
                                    $correo . ' ' .
                                    $celular . ' ' .
                                    $situacion . ' ' .
                                    $tipoPersonal
                            ), 'UTF-8');

                            $isCesado = $situacion === 'CESADO';

                            $colorSituacion = $isCesado
                                ? 'bg-slate-100 text-slate-500 border-slate-200'
                                : 'bg-green-50 text-green-700 border-green-100';

                            $tipoColor = match ($tipoPersonal) {
                                'CAS'     => 'bg-red-50 text-red-900 border-red-100',
                                'MILITAR' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                                'PAC'     => 'bg-amber-50 text-amber-700 border-amber-100',
                                default   => 'bg-slate-50 text-slate-500 border-slate-200',
                            };
                            ?>

                            <tr class="directorio-row transition-all group border-b border-slate-100/80"
                                data-search="<?php echo htmlspecialchars($searchData, ENT_QUOTES, 'UTF-8'); ?>"
                                data-tipo-personal="<?php echo htmlspecialchars($tipoPersonal, ENT_QUOTES, 'UTF-8'); ?>"
                                data-situacion="<?php echo htmlspecialchars($situacion, ENT_QUOTES, 'UTF-8'); ?>"
                                data-area="<?php echo htmlspecialchars($areaFiltroValor, ENT_QUOTES, 'UTF-8'); ?>">

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

                                            <div class="sm:hidden mt-1 flex flex-wrap items-center gap-1.5">
                                                <span class="inline-flex px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-wider border <?php echo $tipoColor; ?>">
                                                    <?php echo htmlspecialchars($tipoPersonal); ?>
                                                </span>
                                                <span class="text-[10px] text-red-800 font-black uppercase truncate max-w-[150px]">
                                                    <?php echo htmlspecialchars($puesto ?: 'Sin puesto'); ?>
                                                </span>
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

                                    <div class="mt-2">
                                        <span class="inline-flex px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider border <?php echo $tipoColor; ?>">
                                            <?php echo htmlspecialchars($tipoPersonal); ?>
                                        </span>
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
                                    <span class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider border <?php echo $colorSituacion; ?>">
                                        <?php echo htmlspecialchars($situacion); ?>
                                    </span>
                                </td>

                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <a href="<?php echo BASE_URL; ?>/perfil/<?php echo (int)$row['id']; ?>"
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
                                    Ajusta la búsqueda, el tipo de personal, la situación o el área seleccionada.
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

    <style>
        /* ===== TABLA PREMIUM TIPO DATATABLE ===== */

        #directorioTable {
            border-collapse: separate;
            border-spacing: 0;
        }

        #directorioTable thead th {
            position: sticky;
            top: 0;
            z-index: 5;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        #directorioTable tbody tr.directorio-row td {
            transition: background-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }

        /* Fila blanca */
        #directorioTable tbody tr.row-clear td {
            background: #ffffff;
        }

        /* Fila sombreada suave */
        #directorioTable tbody tr.row-soft td {
            background: #f8fafc;
        }

        /* Hover premium */
        /* Hover premium sin líneas verticales entre columnas */
        #directorioTable tbody tr.directorio-row:hover td {
            background: #fff7f7;
            box-shadow: none;
        }

        /* Línea guinda solo al inicio de la fila */
        #directorioTable tbody tr.directorio-row:hover td:first-child {
            box-shadow: inset 3px 0 0 #7f1d1d;
            color: #7f1d1d;
        }

        /* Separación visual elegante entre registros */
        #directorioTable tbody tr.directorio-row td {
            border-bottom: 1px solid rgba(226, 232, 240, 0.75);
        }

        /* Redondeo visual suave en hover */
        #directorioTable tbody tr.directorio-row td:first-child {
            border-top-left-radius: 0.75rem;
            border-bottom-left-radius: 0.75rem;
        }

        #directorioTable tbody tr.directorio-row td:last-child {
            border-top-right-radius: 0.75rem;
            border-bottom-right-radius: 0.75rem;
        }

        /* LABELS — más livianos y modernos */
        #drawerNuevoColaborador label {
            display: block;
            margin-bottom: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
            /* antes 900 */
            color: #64748b;
            letter-spacing: 0.02em;
        }

        /* INPUTS / SELECTS — tipografía limpia */
        #drawerNuevoColaborador input:not([type="checkbox"]),
        #drawerNuevoColaborador select {
            width: 100%;
            margin-top: 0.25rem;
            border-radius: 0.9rem;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            padding: 0.75rem 0.9rem;
            font-size: 0.875rem;
            font-weight: 400;
            /* antes 700 */
            color: #0f172a;
            outline: none;
            transition: all .18s ease;
        }

        /* HOVER sutil */
        #drawerNuevoColaborador input:not([type="checkbox"]):hover,
        #drawerNuevoColaborador select:hover {
            border-color: #cbd5e1;
        }

        /* FOCUS estilo moderno tipo SaaS */
        #drawerNuevoColaborador input:not([type="checkbox"]):focus,
        #drawerNuevoColaborador select:focus {
            border-color: #7f1d1d;
            box-shadow: 0 0 0 3px rgba(127, 29, 29, 0.12);
        }

        /* PLACEHOLDER ligero */
        #drawerNuevoColaborador input::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        /* SELECT limpio */
        #drawerNuevoColaborador select {
            appearance: none;
            background-image:
                linear-gradient(45deg, transparent 50%, #94a3b8 50%),
                linear-gradient(135deg, #94a3b8 50%, transparent 50%);
            background-position:
                calc(100% - 16px) 50%,
                calc(100% - 12px) 50%;
            background-size: 4px 4px;
            background-repeat: no-repeat;
            padding-right: 2.5rem;
        }

        /* CHECKBOX minimal */
        #drawerNuevoColaborador input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
            border-radius: .25rem;
            accent-color: #7f1d1d;
            cursor: pointer;
        }

        /* SECCIONES más limpias */
        #drawerNuevoColaborador section {
            background: #ffffff;
            border-radius: 1.25rem;
            border: 1px solid #f1f5f9;
            padding: 1.5rem;
        }

        /* TÍTULOS más modernos */
        #drawerNuevoColaborador section h3 {
            font-size: 0.8rem;
            font-weight: 600;
            color: #7f1d1d;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        /* ===== DRAWER NUEVO COLABORADOR — OVERLAY PREMIUM ===== */
        #drawerNuevoColaborador {
            isolation: isolate;
        }

        #drawerNuevoColaborador .drawer-backdrop {
            z-index: 10;
            pointer-events: auto;
        }

        #drawerNuevoColaborador .drawer-panel {
            z-index: 20;
        }

        #drawerNuevoColaborador.drawer-open .drawer-backdrop {
            opacity: 1;
        }

        #drawerNuevoColaborador.drawer-open .drawer-panel {
            transform: translateX(0);
        }

        /* Bloquea scroll general cuando el drawer está abierto */
        body.drawer-nuevo-open {
            overflow: hidden;
        }

        /* Oscurece visualmente el sidebar, pero más suave */
        body.drawer-nuevo-open aside:not(.drawer-panel) {
            filter: brightness(0.85) saturate(0.85);
            opacity: 0.98;
            pointer-events: none;
            transition: filter .25s ease, opacity .25s ease;
        }

        /* Capa sutil encima del sidebar */
        body.drawer-nuevo-open aside:not(.drawer-panel)::after {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.12);
            pointer-events: none;
        }
    </style>

    <div id="drawerNuevoColaborador" class="fixed inset-0 z-[9999] hidden">

        <!-- Overlay sobre toda la pantalla, incluido el sidebar -->
        <div class="drawer-backdrop absolute inset-0 bg-slate-950/30 backdrop-blur-[1px] opacity-0 transition-opacity duration-300 ease-out"
            onclick="cerrarDrawerNuevoColaborador()"></div>

        <!-- Panel del formulario -->
        <aside class="drawer-panel absolute top-0 right-0 h-full left-0 lg:left-64 bg-white shadow-2xl border-l border-slate-200 flex flex-col transition-all duration-300 ease-out translate-x-full">

            <form method="POST" class="h-full flex flex-col">

                <input type="hidden" name="crear_colaborador" value="1">

                <div class="px-8 py-5 border-b border-slate-100 bg-white flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black tracking-[0.2em] text-red-900 uppercase">Registro RRHH</p>
                        <h2 class="text-2xl font-black text-slate-800 mt-1">Nuevo colaborador</h2>
                        <p class="text-sm text-slate-500 mt-1">Completa la información integral del colaborador.</p>
                    </div>

                    <button type="button" onclick="cerrarDrawerNuevoColaborador()"
                        class="w-10 h-10 rounded-2xl bg-slate-50 border border-slate-200 text-slate-500 hover:text-red-900">
                        ✕
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-8 space-y-6 bg-gradient-to-br from-slate-50 via-white to-red-50/30">

                    <?php if (!empty($mensajeRegistro) && empty($mensajeRegistro['success'])): ?>
                        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-sm font-bold">
                            <?= htmlspecialchars($mensajeRegistro['mensaje']) ?>
                        </div>
                    <?php endif; ?>

                    <section class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm p-6">
                        <h3 class="text-sm font-black text-red-900 uppercase tracking-widest mb-5">Datos personales</h3>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-xs font-bold text-slate-500">DNI *</label>
                                <input name="dni" maxlength="8" required class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>

                            <div class="md:col-span-2">
                                <label class="text-xs font-bold text-slate-500">Nombres y apellidos *</label>
                                <input name="nombres_apellidos" required class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">RUC</label>
                                <input name="ruc" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Licencia conducir</label>
                                <input name="licencia_conducir" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Fecha nacimiento</label>
                                <input type="date" name="fecha_nacimiento" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Lugar nacimiento</label>
                                <input name="lugar_nacimiento" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Sexo</label>
                                <select name="sexo" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                    <option value="">Seleccionar</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Femenino</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Estado civil</label>
                                <select name="estado_civil"
                                    class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                    <option value="">Seleccionar</option>
                                    <?php foreach (['Soltero/a', 'Casado/a', 'Divorciado/a', 'Viudo/a', 'Conviviente'] as $opt): ?>
                                        <option value="<?= $opt ?>"><?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Grupo sanguíneo</label>
                                <select name="grupo_sanguineo"
                                    class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                    <option value="">Seleccionar</option>
                                    <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $g): ?>
                                        <option value="<?= $g ?>"><?= $g ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Talla</label>
                                <input name="talla" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Grado militar</label>
                                <input name="grado_militar" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>
                        </div>
                    </section>

                    <section class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm p-6">
                        <h3 class="text-sm font-black text-red-900 uppercase tracking-widest mb-5">Contacto y domicilio</h3>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-xs font-bold text-slate-500">Celular</label>
                                <input name="celular" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Correo personal</label>
                                <input type="email" name="correo_personal" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Distrito</label>
                                <input name="distrito" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>

                            <div class="md:col-span-4">
                                <label class="text-xs font-bold text-slate-500">Dirección residencia</label>
                                <input name="direccion_residencia" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>
                        </div>
                    </section>

                    <section class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm p-6">
                        <h3 class="text-sm font-black text-red-900 uppercase tracking-widest mb-5">Datos laborales</h3>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-xs font-bold text-slate-500">Correo institucional</label>
                                <input type="email" name="correo_institucional" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Situación</label>
                                <select name="situacion" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                    <option value="ACTIVO">ACTIVO</option>
                                    <option value="CESADO">CESADO</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Sueldo</label>
                                <input type="number" step="0.01" name="sueldo" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Modalidad contrato</label>
                                <select name="modalidad_contrato"
                                    class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 focus:border-red-900 focus:ring-red-900/20">
                                    <option value="">Seleccionar</option>
                                    <option value="CAS">CAS</option>
                                    <option value="MILITAR">MILITAR</option>
                                    <option value="PAC">PAC</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Puesto CAS</label>
                                <input name="puesto_cas" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Tipo puesto</label>
                                <input name="tipo_puesto" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Área</label>
                                <input name="area" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Procedencia</label>
                                <input name="procedencia" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Fecha ingreso</label>
                                <input type="date" name="fecha_ingreso" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Fecha cese</label>
                                <input type="date" name="fecha_cese" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>
                        </div>
                    </section>

                    <section class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm p-6">
                        <h3 class="text-sm font-black text-red-900 uppercase tracking-widest mb-5">Datos pensionarios</h3>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-xs font-bold text-slate-500">Sistema pensión</label>
                                <select name="sistema_pension"
                                    class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                    <option value="">Seleccionar</option>
                                    <?php foreach (['CNP', 'D.L 20520', 'CAJA MILITAR', 'OTROS'] as $opt): ?>
                                        <option value="<?= $opt ?>"><?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">AFP</label>
                                <select name="afp"
                                    class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                    <option value="">Seleccionar</option>
                                    <?php foreach (['PRIMA', 'INTEGRA', 'PROFUTURO', 'HABITAT', 'OTRO'] as $opt): ?>
                                        <option value="<?= $opt ?>"><?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">CUSPP</label>
                                <input name="cuspp"
                                    class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Tipo comisión</label>
                                <select name="tipo_comision"
                                    class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                    <option value="">Seleccionar</option>
                                    <?php foreach (['FLUJO', 'MIXTA', 'OTRO'] as $opt): ?>
                                        <option value="<?= $opt ?>"><?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Fecha inscripción</label>
                                <input type="date" name="fecha_inscripcion" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>

                            <label class="flex items-center gap-3 mt-7 text-sm font-bold text-slate-600">
                                <input type="checkbox" name="sin_afp_afiliarme" value="1" class="rounded border-slate-300 text-red-900">
                                Sin AFP / por afiliar
                            </label>
                        </div>
                    </section>

                    <section class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm p-6">
                        <h3 class="text-sm font-black text-red-900 uppercase tracking-widest mb-5">Datos bancarios</h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="text-xs font-bold text-slate-500">Banco haberes</label>
                                <input name="banco_haberes" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">Número de cuenta</label>
                                <input name="numero_cuenta" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-500">CCI</label>
                                <input name="numero_cuenta_cci" class="mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>
                        </div>
                    </section>

                </div>

                <div class="px-8 py-5 border-t border-slate-100 bg-white flex justify-end gap-3">
                    <button type="button" onclick="cerrarDrawerNuevoColaborador()"
                        class="px-5 py-3 rounded-2xl border border-slate-200 text-slate-600 text-sm font-bold hover:bg-slate-50">
                        Cancelar
                    </button>

                    <button type="submit"
                        class="px-6 py-3 rounded-2xl bg-red-900 text-white text-sm font-black shadow-lg shadow-red-900/20 hover:bg-red-800">
                        Guardar colaborador
                    </button>
                </div>

            </form>
        </aside>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('searchInput');
            const clearBtn = document.getElementById('clearSearch');

            const filterTipoPersonal = document.getElementById('filterTipoPersonal');
            const filterSituacion = document.getElementById('filterSituacion');
            const filterArea = document.getElementById('filterArea');
            const btnClearFilters = document.getElementById('btnClearFilters');
            const activeFilterSummary = document.getElementById('activeFilterSummary');

            const pageSizeSelect = document.getElementById('pageSize');
            const rows = Array.from(document.querySelectorAll('.directorio-row'));
            const emptyState = document.getElementById('emptyState');

            const resultCount = document.getElementById('resultCount');
            const rangeInfo = document.getElementById('rangeInfo');
            const currentPageLabel = document.getElementById('currentPageLabel');
            const totalPagesLabel = document.getElementById('totalPagesLabel');

            const prevBtn = document.getElementById('prevPage');
            const nextBtn = document.getElementById('nextPage');
            const paginationNumbers = document.getElementById('paginationNumbers');

            const btnExportExcel = document.getElementById('btnExportExcel');

            let filteredRows = [...rows];
            let currentPage = 1;

            const DEFAULT_SITUACION = 'ACTIVO';

            function normalizar(valor) {
                return (valor || '').toString().trim().toLowerCase();
            }

            function obtenerPageSize() {
                const size = parseInt(pageSizeSelect?.value || '25', 10);
                return Number.isNaN(size) ? 25 : size;
            }

            function obtenerFiltros() {
                return {
                    q: normalizar(input?.value || ''),
                    tipo: (filterTipoPersonal?.value || '').trim().toUpperCase(),
                    situacion: (filterSituacion?.value || '').trim().toUpperCase(),
                    area: normalizar(filterArea?.value || '')
                };
            }

            function aplicarFiltro() {
                const filtros = obtenerFiltros();

                if (clearBtn) {
                    clearBtn.classList.toggle('hidden', filtros.q.length === 0);
                    clearBtn.classList.toggle('flex', filtros.q.length > 0);
                }

                filteredRows = rows.filter(row => {
                    const search = row.dataset.search || '';
                    const tipo = (row.dataset.tipoPersonal || '').toUpperCase();
                    const situacion = (row.dataset.situacion || '').toUpperCase();
                    const area = normalizar(row.dataset.area || '');

                    const coincideBusqueda = !filtros.q || search.includes(filtros.q);
                    const coincideTipo = !filtros.tipo || tipo === filtros.tipo;
                    const coincideSituacion = !filtros.situacion || situacion === filtros.situacion;
                    const coincideArea = !filtros.area || area === filtros.area;

                    return coincideBusqueda && coincideTipo && coincideSituacion && coincideArea;
                });

                currentPage = 1;
                actualizarResumenFiltros();
                renderTable();
            }

            function actualizarResumenFiltros() {
                if (!activeFilterSummary) return;

                const filtros = obtenerFiltros();
                const partes = [];

                if (filtros.q) {
                    partes.push(`Búsqueda: "${input.value.trim()}"`);
                }

                if (filtros.tipo) {
                    partes.push(`Tipo: ${filtros.tipo}`);
                }

                if (filtros.situacion) {
                    partes.push(`Situación: ${filtros.situacion}`);
                } else {
                    partes.push('Situación: todos');
                }

                if (filterArea && filterArea.value) {
                    const areaTexto = filterArea.options[filterArea.selectedIndex]?.textContent?.trim() || 'Área seleccionada';
                    partes.push(`Área: ${areaTexto}`);
                }

                activeFilterSummary.textContent = partes.length ?
                    partes.join(' • ') :
                    'Vista inicial: personal activo.';
            }

            function renderTable() {
                const pageSize = obtenerPageSize();
                const totalResults = filteredRows.length;
                const totalPages = Math.max(1, Math.ceil(totalResults / pageSize));

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }

                const start = (currentPage - 1) * pageSize;
                const end = Math.min(start + pageSize, totalResults);

                rows.forEach(row => {
                    row.classList.add('hidden');
                });

                filteredRows.slice(start, end).forEach((row, index) => {
                    row.classList.remove('hidden');

                    row.classList.remove('row-clear', 'row-soft');

                    if (index % 2 === 0) {
                        row.classList.add('row-clear');
                    } else {
                        row.classList.add('row-soft');
                    }
                });

                if (emptyState) {
                    emptyState.classList.toggle('hidden', totalResults !== 0);
                }

                if (resultCount) {
                    resultCount.textContent = totalResults;
                }

                if (rangeInfo) {
                    rangeInfo.textContent = totalResults === 0 ? '0' : `${start + 1}-${end}`;
                }

                if (currentPageLabel) {
                    currentPageLabel.textContent = totalResults === 0 ? '0' : currentPage;
                }

                if (totalPagesLabel) {
                    totalPagesLabel.textContent = totalResults === 0 ? '0' : totalPages;
                }

                if (prevBtn) {
                    prevBtn.disabled = currentPage <= 1 || totalResults === 0;
                }

                if (nextBtn) {
                    nextBtn.disabled = currentPage >= totalPages || totalResults === 0;
                }

                renderPaginationNumbers(totalPages, totalResults);
            }

            function renderPaginationNumbers(totalPages, totalResults) {
                if (!paginationNumbers) return;

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

                    btn.className = page === currentPage ?
                        'w-9 h-9 rounded-xl bg-red-900 text-white text-xs font-black shadow-lg shadow-red-900/20' :
                        'w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-600 text-xs font-black hover:bg-red-50 hover:text-red-900 transition';

                    btn.addEventListener('click', function() {
                        currentPage = page;
                        renderTable();
                    });

                    paginationNumbers.appendChild(btn);
                }
            }

            function limpiarFiltrosADefault() {
                if (input) input.value = '';
                if (filterTipoPersonal) filterTipoPersonal.value = '';
                if (filterSituacion) filterSituacion.value = DEFAULT_SITUACION;
                if (filterArea) filterArea.value = '';

                aplicarFiltro();

                if (input) input.focus();
            }

            input?.addEventListener('input', aplicarFiltro);
            filterTipoPersonal?.addEventListener('change', aplicarFiltro);
            filterSituacion?.addEventListener('change', aplicarFiltro);
            filterArea?.addEventListener('change', aplicarFiltro);

            clearBtn?.addEventListener('click', function() {
                if (!input) return;

                input.value = '';
                input.focus();
                aplicarFiltro();
            });

            btnClearFilters?.addEventListener('click', limpiarFiltrosADefault);

            pageSizeSelect?.addEventListener('change', function() {
                currentPage = 1;
                renderTable();
            });

            prevBtn?.addEventListener('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    renderTable();
                }
            });

            nextBtn?.addEventListener('click', function() {
                const totalPages = Math.ceil(filteredRows.length / obtenerPageSize());

                if (currentPage < totalPages) {
                    currentPage++;
                    renderTable();
                }
            });

            if (btnExportExcel) {
                btnExportExcel.addEventListener('click', function(e) {
                    e.preventDefault();

                    const filtros = obtenerFiltros();
                    const params = new URLSearchParams();

                    params.set('export', 'excel');

                    if (input?.value?.trim()) {
                        params.set('q', input.value.trim());
                    }

                    if (filtros.tipo) {
                        params.set('tipo_personal', filtros.tipo);
                    }

                    if (filtros.situacion) {
                        params.set('situacion', filtros.situacion);
                    }

                    if (filtros.area) {
                        params.set('area', filtros.area);
                    }

                    window.location.href = `<?= BASE_URL ?>/rrhh/directorio?${params.toString()}`;
                });
            }
            aplicarFiltro();
        });

        function abrirDrawerNuevoColaborador() {
            const drawer = document.getElementById('drawerNuevoColaborador');
            if (!drawer) return;

            drawer.classList.remove('hidden');
            document.body.classList.add('drawer-nuevo-open');

            requestAnimationFrame(() => {
                drawer.classList.add('drawer-open');
            });
        }

        function cerrarDrawerNuevoColaborador() {
            const drawer = document.getElementById('drawerNuevoColaborador');
            if (!drawer) return;

            drawer.classList.remove('drawer-open');

            setTimeout(() => {
                drawer.classList.add('hidden');
                document.body.classList.remove('drawer-nuevo-open');
            }, 300);
        }
    </script>