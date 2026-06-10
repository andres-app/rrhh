<?php
// Vista/modulos/rrhh/teletrabajo.php

require_once ROOT_PATH . 'Controlador/CtrDirectorio.php';
require_once ROOT_PATH . 'Modelo/MdDirectorio.php';

if (file_exists(ROOT_PATH . 'Controlador/CtrTeletrabajo.php')) {
    require_once ROOT_PATH . 'Controlador/CtrTeletrabajo.php';
}

if (file_exists(ROOT_PATH . 'Modelo/MdTeletrabajo.php')) {
    require_once ROOT_PATH . 'Modelo/MdTeletrabajo.php';
}

if (!function_exists('rrhh_h')) {
    function rrhh_h($valor): string
    {
        return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('rrhh_fecha')) {
    function rrhh_fecha($fecha): string
    {
        if (empty($fecha) || $fecha === '0000-00-00') {
            return 'No registrado';
        }

        $ts = strtotime((string)$fecha);
        return $ts ? date('d/m/Y', $ts) : 'No registrado';
    }
}

if (!function_exists('rrhh_estado_remoto')) {
    function rrhh_estado_remoto($fechaInicio, $fechaFin = null, $estadoDb = null): array
    {
        $estadoDb = strtoupper(trim((string)$estadoDb));

        if ($estadoDb === 'ANULADO') {
            return [
                'estado' => 'ANULADO',
                'dias' => null,
                'clase' => 'bg-slate-100 text-slate-500 border-slate-200',
                'texto' => 'Anulado'
            ];
        }

        if (empty($fechaFin) || $fechaFin === '0000-00-00') {
            return [
                'estado' => 'SIN_FECHA',
                'dias' => null,
                'clase' => 'bg-slate-100 text-slate-500 border-slate-200',
                'texto' => 'Sin fecha'
            ];
        }

        $hoy = new DateTime('today');
        $inicio = DateTime::createFromFormat('Y-m-d', (string)$fechaInicio);
        $fin = DateTime::createFromFormat('Y-m-d', (string)$fechaFin);

        if (!$fin) {
            return [
                'estado' => 'SIN_FECHA',
                'dias' => null,
                'clase' => 'bg-slate-100 text-slate-500 border-slate-200',
                'texto' => 'Sin fecha'
            ];
        }

        if ($inicio && $hoy < $inicio) {
            $diasInicio = (int)$hoy->diff($inicio)->format('%r%a');

            return [
                'estado' => 'POR_INICIAR',
                'dias' => $diasInicio,
                'clase' => 'bg-blue-50 text-blue-700 border-blue-100',
                'texto' => 'Por iniciar'
            ];
        }

        if ($hoy > $fin) {
            $diasVencido = (int)$hoy->diff($fin)->format('%r%a');

            return [
                'estado' => 'VENCIDO',
                'dias' => $diasVencido,
                'clase' => 'bg-red-50 text-red-800 border-red-100',
                'texto' => 'Vencido'
            ];
        }

        $diasFin = (int)$hoy->diff($fin)->format('%r%a');

        if ($diasFin <= 15) {
            return [
                'estado' => 'POR_VENCER',
                'dias' => $diasFin,
                'clase' => 'bg-amber-50 text-amber-700 border-amber-100',
                'texto' => 'Por vencer'
            ];
        }

        return [
            'estado' => 'VIGENTE',
            'dias' => $diasFin,
            'clase' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            'texto' => 'Vigente'
        ];
    }
}

$rolSesion = strtolower(trim($_SESSION['user_role'] ?? ''));
$puedeGestionar = in_array($rolSesion, ['superadmin', 'admin', 'rrhh'], true);

$controladorDirectorio = new CtrDirectorio();
$controladorRemoto = class_exists('CtrTeletrabajo') ? new CtrTeletrabajo() : null;

$mensajeOperacion = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $puedeGestionar && $controladorRemoto) {
    if (isset($_POST['crear_acuerdo_remoto']) && method_exists($controladorRemoto, 'ctrCrearAcuerdo')) {
        $mensajeOperacion = $controladorRemoto->ctrCrearAcuerdo($_POST, null);
    }

    if (isset($_POST['crear_adenda_remoto']) && method_exists($controladorRemoto, 'ctrCrearAdenda')) {
        $mensajeOperacion = $controladorRemoto->ctrCrearAdenda($_POST, null);
    }

    if (isset($_POST['anular_documento_remoto']) && method_exists($controladorRemoto, 'ctrAnularDocumento')) {
        $mensajeOperacion = $controladorRemoto->ctrAnularDocumento((int)($_POST['documento_id'] ?? 0));
    }

    if (!empty($mensajeOperacion['success'])) {
        header('Location: ' . BASE_URL . '/rrhh/teletrabajo?remoto=ok');
        exit();
    }
}

$colaboradores = $controladorDirectorio->ctrMostrarDirectorio();
$colaboradores = is_array($colaboradores) ? $colaboradores : [];

$trabajosRemotos = [];

if ($controladorRemoto && method_exists($controladorRemoto, 'ctrListarTeletrabajo')) {
    $trabajosRemotos = $controladorRemoto->ctrListarTeletrabajo();
}

$trabajosRemotos = is_array($trabajosRemotos) ? $trabajosRemotos : [];

/*
 * Completar datos del colaborador desde CtrDirectorio.
 * Así el modelo no depende de columnas exactas de la tabla directorio.
 */
$mapaColaboradores = [];

foreach ($colaboradores as $c) {
    $idColaborador = (int)($c['id'] ?? 0);

    if ($idColaborador > 0) {
        $mapaColaboradores[$idColaborador] = $c;
    }
}

foreach ($trabajosRemotos as &$tr) {
    $colabId = (int)($tr['colab_id'] ?? 0);

    if ($colabId > 0 && isset($mapaColaboradores[$colabId])) {
        $c = $mapaColaboradores[$colabId];

        $tr['nombres_apellidos'] = $c['nombres_apellidos'] ?? $tr['nombres_apellidos'] ?? ('Colaborador #' . $colabId);
        $tr['colaborador'] = $c['nombres_apellidos'] ?? $tr['colaborador'] ?? ('Colaborador #' . $colabId);
        $tr['dni'] = $c['dni'] ?? $tr['dni'] ?? '';
        $tr['area'] = $c['area'] ?? $tr['area'] ?? '';
        $tr['puesto_cas'] = $c['puesto_cas'] ?? $tr['puesto_cas'] ?? '';
        $tr['puesto'] = $c['puesto_cas'] ?? $tr['puesto'] ?? '';
        $tr['situacion'] = $c['situacion'] ?? $tr['situacion'] ?? 'ACTIVO';
    }
}

unset($tr);

/*
 * Historial por acuerdo:
 * Arma el acuerdo principal y todas sus adendas para el modal de detalle.
 */
$historialRemotoJs = [];
$acuerdosConsultados = [];

foreach ($trabajosRemotos as $rowHistorial) {
    $acuerdoIdHistorial = (int)(
        $rowHistorial['acuerdo_id']
        ?? $rowHistorial['documento_padre_id']
        ?? $rowHistorial['id']
        ?? 0
    );

    if ($acuerdoIdHistorial <= 0 || isset($acuerdosConsultados[$acuerdoIdHistorial])) {
        continue;
    }

    $acuerdosConsultados[$acuerdoIdHistorial] = true;

    $historial = [];

    if ($controladorRemoto && method_exists($controladorRemoto, 'ctrHistorialTeletrabajo')) {
        $historial = $controladorRemoto->ctrHistorialTeletrabajo($acuerdoIdHistorial);
    } elseif (class_exists('MdTeletrabajo') && method_exists('MdTeletrabajo', 'mdlListarHistorialPorAcuerdo')) {
        $historial = MdTeletrabajo::mdlListarHistorialPorAcuerdo($acuerdoIdHistorial);
    }

    $historialRemotoJs[$acuerdoIdHistorial] = [];

    foreach ($historial as $h) {
        $colabIdH = (int)($h['colab_id'] ?? 0);
        $colaboradorH = $mapaColaboradores[$colabIdH] ?? [];

        $estadoH = rrhh_estado_remoto(
            $h['fecha_inicio'] ?? null,
            $h['fecha_fin'] ?? null,
            $h['estado'] ?? null
        );

        $historialRemotoJs[$acuerdoIdHistorial][] = [
            'id' => (int)($h['id'] ?? 0),
            'acuerdo_id' => (int)($h['acuerdo_id'] ?? $acuerdoIdHistorial),
            'documento_padre_id' => $h['documento_padre_id'] ?? null,
            'tipo_registro' => strtoupper(trim((string)($h['tipo_registro'] ?? 'ACUERDO'))),
            'numero_documento' => (string)($h['numero_documento'] ?? ''),
            'fecha_documento' => $h['fecha_documento'] ?? null,
            'fecha_inicio' => $h['fecha_inicio'] ?? null,
            'fecha_fin' => $h['fecha_fin'] ?? null,
            'motivo' => (string)($h['motivo'] ?? ''),
            'observacion' => (string)($h['observacion'] ?? ''),
            'estado' => $estadoH['estado'],
            'estado_texto' => $estadoH['texto'],
            'dias' => $estadoH['dias'],
            'nombre' => (string)($colaboradorH['nombres_apellidos'] ?? ''),
            'dni' => (string)($colaboradorH['dni'] ?? ''),
            'area' => (string)($colaboradorH['area'] ?? ''),
            'puesto' => (string)($colaboradorH['puesto_cas'] ?? ''),
        ];
    }
}

$areasFiltro = [];
$totalVigentes = 0;
$totalPorVencer = 0;
$totalVencidos = 0;
$totalAnulados = 0;

foreach ($trabajosRemotos as $item) {
    $area = trim((string)($item['area'] ?? ''));
    if ($area !== '') {
        $areasFiltro[] = $area;
    }

    $estadoInfo = rrhh_estado_remoto($item['fecha_inicio'] ?? null, $item['fecha_fin'] ?? null, $item['estado'] ?? null);

    if ($estadoInfo['estado'] === 'VIGENTE') {
        $totalVigentes++;
    } elseif ($estadoInfo['estado'] === 'POR_VENCER') {
        $totalPorVencer++;
    } elseif ($estadoInfo['estado'] === 'VENCIDO') {
        $totalVencidos++;
    } elseif ($estadoInfo['estado'] === 'ANULADO') {
        $totalAnulados++;
    }
}

$areasFiltro = array_values(array_unique($areasFiltro));
sort($areasFiltro, SORT_NATURAL | SORT_FLAG_CASE);

$colaboradoresJs = [];

foreach ($colaboradores as $c) {
    $situacion = strtoupper(trim((string)($c['situacion'] ?? 'ACTIVO')));

    if ($situacion === '' || $situacion === 'BAJA' || $situacion === 'INACTIVO') {
        $situacion = $situacion === '' ? 'ACTIVO' : 'CESADO';
    }

    $colaboradoresJs[] = [
        'id' => (int)($c['id'] ?? 0),
        'nombre' => (string)($c['nombres_apellidos'] ?? ''),
        'dni' => (string)($c['dni'] ?? ''),
        'area' => (string)($c['area'] ?? ''),
        'puesto' => (string)($c['puesto_cas'] ?? ''),
        'situacion' => $situacion,
    ];
}

$titulo_pagina = 'Trabajo Remoto Temporal - RRHH';
$menu_activo = 'teletrabajo';

require_once ROOT_PATH . 'Vista/includes/header.php';
require_once ROOT_PATH . 'Vista/includes/sidebar.php';
?>

<main class="flex-1 flex flex-col h-screen overflow-hidden bg-[#f8fafc]">

    <header class="bg-white/90 backdrop-blur-xl border-b border-slate-200/80 z-20">
        <div class="px-4 md:px-8 py-5">
            <div class="flex flex-col 2xl:flex-row 2xl:items-center 2xl:justify-between gap-5">

                <div class="flex items-start gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-red-900 text-white flex items-center justify-center shadow-lg shadow-red-900/20 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>

                    <div>
                        <h1 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">
                            Teletrabajo temporal
                        </h1>
                        <p class="text-xs md:text-sm text-slate-500 font-medium mt-0.5">
                            Control de acuerdos, adendas, vigencia y alertas de vencimiento.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row gap-3 w-full 2xl:w-auto 2xl:min-w-[720px]">
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
                            placeholder="Buscar colaborador, DNI, documento, área o motivo...">

                        <button type="button"
                            id="clearSearch"
                            class="hidden absolute inset-y-0 right-0 pr-4 items-center text-slate-400 hover:text-red-900 transition"
                            title="Limpiar búsqueda">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <?php if ($puedeGestionar): ?>
                        <button type="button" onclick="abrirDrawerAcuerdo()"
                            class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-red-900 text-white text-sm font-bold shadow-lg shadow-red-900/20 hover:bg-red-800 transition-all">
                            <span class="text-lg leading-none">+</span>
                            Nuevo acuerdo
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <section class="p-4 md:p-8 flex-1 overflow-y-auto">

        <?php if (!$controladorRemoto): ?>
            <?php if ($controladorRemoto && method_exists('MdTeletrabajo', 'getUltimoError') && MdTeletrabajo::getUltimoError() !== ''): ?>
                <div class="mb-5 rounded-3xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 font-bold">
                    Error al listar teletrabajo: <?= rrhh_h(MdTeletrabajo::getUltimoError()) ?>
                </div>
            <?php endif; ?>
            <div class="mb-5 rounded-3xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 font-semibold">
                Falta conectar el controlador <strong>CtrTeletrabajo</strong>. La vista ya está lista, pero todavía no puede guardar ni listar acuerdos reales.
            </div>
        <?php endif; ?>

        <?php if (!empty($mensajeOperacion) && empty($mensajeOperacion['success'])): ?>
            <div class="mb-5 rounded-3xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800 font-bold">
                <?= rrhh_h($mensajeOperacion['mensaje'] ?? 'No se pudo completar la operación') ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
            <article class="bg-white rounded-[1.6rem] border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Vigentes</p>
                <div class="mt-3 flex items-end justify-between gap-3">
                    <span class="text-3xl font-black text-slate-900"><?= (int)$totalVigentes ?></span>
                    <span class="px-3 py-1 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100 text-[10px] font-black uppercase">Activo</span>
                </div>
            </article>

            <article class="bg-white rounded-[1.6rem] border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Por vencer</p>
                <div class="mt-3 flex items-end justify-between gap-3">
                    <span class="text-3xl font-black text-slate-900"><?= (int)$totalPorVencer ?></span>
                    <span class="px-3 py-1 rounded-xl bg-amber-50 text-amber-700 border border-amber-100 text-[10px] font-black uppercase">15 días</span>
                </div>
            </article>

            <article class="bg-white rounded-[1.6rem] border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Vencidos</p>
                <div class="mt-3 flex items-end justify-between gap-3">
                    <span class="text-3xl font-black text-slate-900"><?= (int)$totalVencidos ?></span>
                    <span class="px-3 py-1 rounded-xl bg-red-50 text-red-800 border border-red-100 text-[10px] font-black uppercase">Atención</span>
                </div>
            </article>

            <article class="bg-white rounded-[1.6rem] border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total registros</p>
                <div class="mt-3 flex items-end justify-between gap-3">
                    <span class="text-3xl font-black text-slate-900"><?= count($trabajosRemotos) ?></span>
                    <span class="px-3 py-1 rounded-xl bg-slate-50 text-slate-500 border border-slate-200 text-[10px] font-black uppercase">Remoto</span>
                </div>
            </article>
        </div>

        <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/60 border border-slate-200 overflow-hidden">

            <div class="px-5 md:px-6 py-5 border-b border-slate-100 bg-white">
                <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                    <div class="min-w-[220px]">
                        <h2 class="text-sm font-black text-slate-800 uppercase tracking-wide">
                            Personal con teletrabajo temporal
                        </h2>
                        <p class="text-xs text-slate-400 font-semibold mt-1">
                            <span id="rangeInfo" class="font-black text-red-900">0</span>
                            de
                            <span id="resultCount" class="font-black text-slate-700"><?= count($trabajosRemotos) ?></span>
                            registros
                        </p>
                    </div>

                    <div class="flex flex-col lg:flex-row lg:items-center gap-2 w-full xl:w-auto">
                        <select id="filterEstado"
                            class="w-full lg:w-[160px] rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-black text-slate-700 outline-none focus:bg-white focus:border-red-900 focus:ring-4 focus:ring-red-900/10 transition-all">
                            <option value="" selected>Estado: todos</option>
                            <option value="POR_INICIAR">Por iniciar</option>
                            <option value="VIGENTE">Vigentes</option>
                            <option value="POR_VENCER">Por vencer</option>
                            <option value="VENCIDO">Vencidos</option>
                            <option value="ANULADO">Anulados</option>
                        </select>

                        <select id="filterArea"
                            class="w-full lg:w-[190px] rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-black text-slate-700 outline-none focus:bg-white focus:border-red-900 focus:ring-4 focus:ring-red-900/10 transition-all">
                            <option value="">Área: todas</option>
                            <?php foreach ($areasFiltro as $areaFiltro): ?>
                                <option value="<?= rrhh_h(mb_strtolower(trim($areaFiltro), 'UTF-8')) ?>">
                                    <?= rrhh_h($areaFiltro) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select id="filterSituacion"
                            class="w-full lg:w-[165px] rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-black text-slate-700 outline-none focus:bg-white focus:border-red-900 focus:ring-4 focus:ring-red-900/10 transition-all">
                            <option value="">Situación: todos</option>
                            <option value="ACTIVO">Activos</option>
                            <option value="CESADO">Cesados</option>
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
                <table id="remotoTable" class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Colaborador</th>
                            <th class="hidden md:table-cell px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Área / Puesto</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Documento actual</th>
                            <th class="hidden lg:table-cell px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Vigencia</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Estado</th>
                            <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        <?php foreach ($trabajosRemotos as $row): ?>
                            <?php
                            $estadoInfo = rrhh_estado_remoto($row['fecha_inicio'] ?? null, $row['fecha_fin'] ?? null, $row['estado'] ?? null);
                            $nombre = trim((string)($row['nombres_apellidos'] ?? $row['colaborador'] ?? ''));
                            $dni = trim((string)($row['dni'] ?? ''));
                            $area = trim((string)($row['area'] ?? ''));
                            $puesto = trim((string)($row['puesto_cas'] ?? $row['puesto'] ?? ''));
                            $situacion = strtoupper(trim((string)($row['situacion'] ?? 'ACTIVO')));

                            if ($situacion === '' || $situacion === 'BAJA' || $situacion === 'INACTIVO') {
                                $situacion = $situacion === '' ? 'ACTIVO' : 'CESADO';
                            }

                            $documentoId = (int)($row['id'] ?? 0);
                            $acuerdoId = (int)($row['acuerdo_id'] ?? $row['documento_padre_id'] ?? $row['id'] ?? 0);
                            $numeroDocumento = trim((string)($row['numero_documento'] ?? ''));
                            $tipoRegistro = strtoupper(trim((string)($row['tipo_registro'] ?? 'ACUERDO')));
                            $motivo = trim((string)($row['motivo'] ?? $row['motivo_adenda'] ?? ''));
                            $archivo = trim((string)($row['archivo_documento'] ?? ''));
                            $areaFiltroValor = mb_strtolower($area, 'UTF-8');

                            $searchData = mb_strtolower(trim(
                                $nombre . ' ' .
                                    $dni . ' ' .
                                    $area . ' ' .
                                    $puesto . ' ' .
                                    $numeroDocumento . ' ' .
                                    $tipoRegistro . ' ' .
                                    $motivo . ' ' .
                                    $situacion . ' ' .
                                    $estadoInfo['estado']
                            ), 'UTF-8');

                            $detallePayload = [
                                'documento_id' => $documentoId,
                                'acuerdo_id' => $acuerdoId,
                                'colab_id' => (int)($row['colab_id'] ?? 0),
                                'nombre' => $nombre,
                                'dni' => $dni,
                                'area' => $area,
                                'puesto' => $puesto,
                                'situacion' => $situacion,
                                'tipo_registro' => $tipoRegistro,
                                'numero_documento' => $numeroDocumento,
                                'fecha_documento' => $row['fecha_documento'] ?? null,
                                'fecha_inicio' => $row['fecha_inicio'] ?? null,
                                'fecha_fin' => $row['fecha_fin'] ?? null,
                                'motivo' => $motivo,
                                'observacion' => $row['observacion'] ?? '',
                                'estado' => $estadoInfo['estado'],
                                'dias' => $estadoInfo['dias'],
                            ];
                            ?>

                            <tr class="remoto-row transition-all group border-b border-slate-100/80"
                                data-search="<?= rrhh_h($searchData) ?>"
                                data-estado="<?= rrhh_h($estadoInfo['estado']) ?>"
                                data-area="<?= rrhh_h($areaFiltroValor) ?>"
                                data-situacion="<?= rrhh_h($situacion) ?>">

                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex h-11 w-11 rounded-2xl bg-gradient-to-br from-red-900 to-[#4c0505] flex-shrink-0 items-center justify-center text-white font-black mr-3 shadow-lg shadow-red-900/20 group-hover:scale-105 group-hover:rotate-3 transition-all">
                                            <?= rrhh_h(mb_substr($nombre ?: 'C', 0, 1, 'UTF-8')) ?>
                                        </div>

                                        <div class="overflow-hidden">
                                            <div class="text-sm font-black text-slate-800 truncate max-w-[170px] md:max-w-[300px]">
                                                <?= rrhh_h($nombre ?: 'Sin nombre') ?>
                                            </div>
                                            <div class="text-[11px] text-slate-400 font-bold tracking-wide">
                                                DNI: <?= rrhh_h($dni ?: 'No registrado') ?>
                                            </div>
                                            <div class="md:hidden mt-1 text-[10px] text-slate-500 font-bold truncate max-w-[180px]">
                                                <?= rrhh_h($area ?: 'Sin área') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-slate-700 font-black">
                                        <?= rrhh_h($area ?: 'Sin área') ?>
                                    </div>
                                    <div class="text-xs text-slate-400 font-semibold">
                                        <?= rrhh_h($puesto ?: 'Sin puesto') ?>
                                    </div>
                                    <div class="mt-2">
                                        <span class="inline-flex px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider border <?= $situacion === 'CESADO' ? 'bg-slate-100 text-slate-500 border-slate-200' : 'bg-green-50 text-green-700 border-green-100' ?>">
                                            <?= rrhh_h($situacion) ?>
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-black text-slate-800">
                                            <?= rrhh_h($numeroDocumento ?: 'Sin número') ?>
                                        </span>
                                        <span class="text-[11px] text-slate-400 font-bold uppercase tracking-wide">
                                            <?= rrhh_h($tipoRegistro) ?> · <?= rrhh_h($motivo ?: 'Trabajo remoto temporal') ?>
                                        </span>
                                        <span class="lg:hidden mt-1 text-[11px] text-slate-500 font-semibold">
                                            <?= rrhh_fecha($row['fecha_inicio'] ?? null) ?> - <?= rrhh_fecha($row['fecha_fin'] ?? null) ?>
                                        </span>
                                    </div>
                                </td>

                                <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap">
                                    <div class="text-xs text-slate-500 font-bold">
                                        Inicio: <span class="text-slate-800"><?= rrhh_fecha($row['fecha_inicio'] ?? null) ?></span>
                                    </div>
                                    <div class="text-xs text-slate-500 font-bold mt-1">
                                        Fin: <span class="text-slate-800"><?= rrhh_fecha($row['fecha_fin'] ?? null) ?></span>
                                    </div>
                                </td>

                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider border <?= rrhh_h($estadoInfo['clase']) ?>">
                                        <?= rrhh_h($estadoInfo['texto']) ?>
                                    </span>

                                    <div class="text-[11px] text-slate-400 font-bold mt-1">
                                        <?php if ($estadoInfo['dias'] !== null): ?>
                                            <?php
                                            if ($estadoInfo['estado'] === 'POR_INICIAR') {
                                                echo 'Inicia en ' . abs((int)$estadoInfo['dias']) . ' día(s)';
                                            } elseif ($estadoInfo['dias'] < 0) {
                                                echo 'Venció hace ' . abs((int)$estadoInfo['dias']) . ' día(s)';
                                            } else {
                                                echo 'Faltan ' . abs((int)$estadoInfo['dias']) . ' día(s)';
                                            }
                                            ?>
                                        <?php else: ?>
                                            Sin conteo
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <button type="button"
                                            onclick='abrirModalDetalle(<?= json_encode($detallePayload, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                            class="p-2.5 bg-slate-50 text-slate-600 rounded-xl hover:bg-slate-900 hover:text-white transition-all shadow-sm border border-slate-200"
                                            title="Ver detalle">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>

                                        <?php if ($archivo !== ''): ?>
                                            <a href="<?= BASE_URL . '/' . ltrim(rrhh_h($archivo), '/') ?>" target="_blank"
                                                class="p-2.5 bg-indigo-50 text-indigo-700 rounded-xl hover:bg-indigo-700 hover:text-white transition-all shadow-sm border border-indigo-100"
                                                title="Ver documento">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($puedeGestionar && $estadoInfo['estado'] !== 'ANULADO'): ?>
                                            <button type="button"
                                                onclick='abrirDrawerAdenda(<?= json_encode($detallePayload, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                                class="p-2.5 bg-red-50 text-red-900 rounded-xl hover:bg-red-900 hover:text-white transition-all shadow-sm border border-red-100"
                                                title="Agregar adenda">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <tr id="emptyState" class="hidden">
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="mx-auto w-16 h-16 rounded-3xl bg-red-50 text-red-900 flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-black text-slate-800">No se encontraron resultados</h3>
                                <p class="text-sm text-slate-400 mt-1">Ajusta la búsqueda, el estado, el área o la situación seleccionada.</p>
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
                    <button type="button" id="prevPage"
                        class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-black text-slate-600 hover:bg-red-900 hover:text-white hover:border-red-900 transition disabled:opacity-40 disabled:hover:bg-white disabled:hover:text-slate-600 disabled:hover:border-slate-200">
                        Anterior
                    </button>

                    <div id="paginationNumbers" class="flex items-center gap-1"></div>

                    <button type="button" id="nextPage"
                        class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-black text-slate-600 hover:bg-red-900 hover:text-white hover:border-red-900 transition disabled:opacity-40 disabled:hover:bg-white disabled:hover:text-slate-600 disabled:hover:border-slate-200">
                        Siguiente
                    </button>
                </div>
            </div>
        </div>
    </section>

    <style>
        #remotoTable {
            border-collapse: separate;
            border-spacing: 0;
        }

        #remotoTable thead th {
            position: sticky;
            top: 0;
            z-index: 5;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        #remotoTable tbody tr.remoto-row td {
            transition: background-color .18s ease, box-shadow .18s ease;
            border-bottom: 1px solid rgba(226, 232, 240, 0.75);
        }

        #remotoTable tbody tr.row-clear td {
            background: #ffffff;
        }

        #remotoTable tbody tr.row-soft td {
            background: #f8fafc;
        }

        #remotoTable tbody tr.remoto-row:hover td {
            background: #fff7f7;
            box-shadow: none;
        }

        #remotoTable tbody tr.remoto-row:hover td:first-child {
            box-shadow: inset 3px 0 0 #7f1d1d;
        }

        #remotoTable tbody tr.remoto-row td:first-child {
            border-top-left-radius: 0.75rem;
            border-bottom-left-radius: 0.75rem;
        }

        #remotoTable tbody tr.remoto-row td:last-child {
            border-top-right-radius: 0.75rem;
            border-bottom-right-radius: 0.75rem;
        }

        .drawer-remoto input:not([type="file"]),
        .drawer-remoto select,
        .drawer-remoto textarea {
            width: 100%;
            margin-top: 0.25rem;
            border-radius: 0.9rem;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            padding: 0.75rem 0.9rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #0f172a;
            outline: none;
            transition: all .18s ease;
        }

        .drawer-remoto input[type="file"] {
            width: 100%;
            margin-top: 0.25rem;
            border-radius: 0.9rem;
            border: 1px dashed #cbd5e1;
            background: #f8fafc;
            padding: 0.85rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: #475569;
        }

        .drawer-remoto input:focus,
        .drawer-remoto select:focus,
        .drawer-remoto textarea:focus {
            border-color: #7f1d1d;
            box-shadow: 0 0 0 3px rgba(127, 29, 29, 0.12);
        }

        .drawer-remoto label {
            display: block;
            margin-bottom: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
        }

        .drawer-remoto-open .drawer-backdrop {
            opacity: 1;
        }

        .drawer-remoto-open .drawer-panel {
            transform: translateX(0);
        }

        body.remoto-drawer-open {
            overflow: hidden;
        }

        body.remoto-drawer-open aside:not(.drawer-panel) {
            filter: brightness(0.86) saturate(0.9);
            pointer-events: none;
            transition: filter .25s ease;
        }

        .colab-option:hover {
            background: #fff7f7;
        }
    </style>

    <div id="drawerAcuerdo" class="drawer-remoto fixed inset-0 z-[9999] hidden">
        <div class="drawer-backdrop absolute inset-0 bg-slate-950/30 backdrop-blur-[1px] opacity-0 transition-opacity duration-300 ease-out" onclick="cerrarDrawerAcuerdo()"></div>

        <aside class="drawer-panel absolute top-0 right-0 h-full left-0 lg:left-64 bg-white shadow-2xl border-l border-slate-200 flex flex-col transition-all duration-300 ease-out translate-x-full">
            <form method="POST" class="h-full flex flex-col">
                <input type="hidden" name="crear_acuerdo_remoto" value="1">
                <input type="hidden" name="colab_id" id="acuerdoColabId">

                <div class="px-8 py-5 border-b border-slate-100 bg-white flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black tracking-[0.2em] text-red-900 uppercase">Trabajo remoto temporal</p>
                        <h2 class="text-2xl font-black text-slate-800 mt-1">Nuevo acuerdo principal</h2>
                        <p class="text-sm text-slate-500 mt-1">Selecciona el colaborador y registra la vigencia del documento principal.</p>
                    </div>

                    <button type="button" onclick="cerrarDrawerAcuerdo()"
                        class="w-10 h-10 rounded-2xl bg-slate-50 border border-slate-200 text-slate-500 hover:text-red-900">
                        ✕
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-8 space-y-6 bg-gradient-to-br from-slate-50 via-white to-red-50/30">
                    <section class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm p-6">
                        <h3 class="text-sm font-black text-red-900 uppercase tracking-widest mb-5">Colaborador</h3>

                        <div class="relative">
                            <label>Buscar personal *</label>
                            <input type="text" id="colaboradorSearchInput" autocomplete="off" placeholder="Escribe nombre, DNI, área o puesto..." required>

                            <div id="colaboradorResults" class="hidden absolute z-50 mt-2 w-full max-h-72 overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-200/70"></div>

                            <p class="text-xs text-slate-400 font-semibold mt-2">
                                Las opciones se muestran recién al hacer clic o escribir en el campo.
                            </p>
                        </div>
                    </section>

                    <section class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm p-6">
                        <h3 class="text-sm font-black text-red-900 uppercase tracking-widest mb-5">Documento principal</h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label>N° de documento *</label>
                                <input name="numero_documento" required placeholder="Ej. ACUERDO N° 001-2026">
                            </div>

                            <div>
                                <label>Fecha de documento</label>
                                <input type="date" name="fecha_documento">
                            </div>

                            <div>
                                <label>Motivo</label>
                                <select name="motivo">
                                    <option value="Trabajo remoto temporal">Trabajo remoto temporal</option>
                                    <option value="Salud">Salud</option>
                                    <option value="Necesidad institucional">Necesidad institucional</option>
                                    <option value="Caso excepcional">Caso excepcional</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>

                            <div>
                                <label>Fecha inicio *</label>
                                <input type="date" name="fecha_inicio" required>
                            </div>

                            <div>
                                <label>Fecha fin *</label>
                                <input type="date" name="fecha_fin" required>
                            </div>

                            <div class="md:col-span-3">
                                <label>Observación</label>
                                <textarea name="observacion" rows="3" placeholder="Detalle breve del acuerdo, condición o comentario de RRHH..."></textarea>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="px-8 py-5 border-t border-slate-100 bg-white flex justify-end gap-3">
                    <button type="button" onclick="cerrarDrawerAcuerdo()"
                        class="px-5 py-3 rounded-2xl border border-slate-200 text-slate-600 text-sm font-bold hover:bg-slate-50">
                        Cancelar
                    </button>

                    <button type="submit"
                        class="px-6 py-3 rounded-2xl bg-red-900 text-white text-sm font-black shadow-lg shadow-red-900/20 hover:bg-red-800">
                        Guardar acuerdo
                    </button>
                </div>
            </form>
        </aside>
    </div>

    <div id="drawerAdenda" class="drawer-remoto fixed inset-0 z-[9999] hidden">
        <div class="drawer-backdrop absolute inset-0 bg-slate-950/30 backdrop-blur-[1px] opacity-0 transition-opacity duration-300 ease-out" onclick="cerrarDrawerAdenda()"></div>

        <aside class="drawer-panel absolute top-0 right-0 h-full left-0 lg:left-64 bg-white shadow-2xl border-l border-slate-200 flex flex-col transition-all duration-300 ease-out translate-x-full">
            <form method="POST" class="h-full flex flex-col">
                <input type="hidden" name="crear_adenda_remoto" value="1">
                <input type="hidden" name="documento_padre_id" id="adendaPadreId">
                <input type="hidden" name="colab_id" id="adendaColabId">

                <div class="px-8 py-5 border-b border-slate-100 bg-white flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black tracking-[0.2em] text-red-900 uppercase">Ampliación de vigencia</p>
                        <h2 class="text-2xl font-black text-slate-800 mt-1">Nueva adenda</h2>
                        <p id="adendaResumen" class="text-sm text-slate-500 mt-1">Documento seleccionado</p>
                    </div>

                    <button type="button" onclick="cerrarDrawerAdenda()"
                        class="w-10 h-10 rounded-2xl bg-slate-50 border border-slate-200 text-slate-500 hover:text-red-900">
                        ✕
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-8 space-y-6 bg-gradient-to-br from-slate-50 via-white to-red-50/30">
                    <section class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm p-6">
                        <h3 class="text-sm font-black text-red-900 uppercase tracking-widest mb-5">Datos de la adenda</h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label>N° de adenda *</label>
                                <input name="numero_documento" required placeholder="Ej. ADENDA N° 001-2026">
                            </div>

                            <div>
                                <label>Fecha de documento</label>
                                <input type="date" name="fecha_documento">
                            </div>

                            <div>
                                <label>Motivo</label>
                                <select name="motivo">
                                    <option value="Ampliación de trabajo remoto temporal">Ampliación</option>
                                    <option value="Regularización">Regularización</option>
                                    <option value="Renovación">Renovación</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>

                            <div>
                                <label>Nueva fecha inicio *</label>
                                <input type="date" name="fecha_inicio" id="adendaFechaInicio" required>
                            </div>

                            <div>
                                <label>Nueva fecha fin *</label>
                                <input type="date" name="fecha_fin" required>
                            </div>

                            <div class="md:col-span-3">
                                <label>Observación</label>
                                <textarea name="observacion" rows="3" placeholder="Detalle de la ampliación o comentario de RRHH..."></textarea>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="px-8 py-5 border-t border-slate-100 bg-white flex justify-end gap-3">
                    <button type="button" onclick="cerrarDrawerAdenda()"
                        class="px-5 py-3 rounded-2xl border border-slate-200 text-slate-600 text-sm font-bold hover:bg-slate-50">
                        Cancelar
                    </button>

                    <button type="submit"
                        class="px-6 py-3 rounded-2xl bg-red-900 text-white text-sm font-black shadow-lg shadow-red-900/20 hover:bg-red-800">
                        Guardar adenda
                    </button>
                </div>
            </form>
        </aside>
    </div>

    <div id="modalDetalle" class="fixed inset-0 z-[9999] hidden">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-[3px]" onclick="cerrarModalDetalle()"></div>

        <section class="absolute inset-x-3 top-3 bottom-3 md:inset-x-8 md:top-6 md:bottom-6 xl:inset-x-20 bg-white rounded-[2rem] shadow-2xl border border-slate-200 overflow-hidden flex flex-col">

            <header class="px-5 md:px-7 py-5 border-b border-slate-100 bg-white flex items-start justify-between gap-4 shrink-0">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="w-12 h-12 rounded-2xl bg-red-900 text-white flex items-center justify-center shadow-lg shadow-red-900/20 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <p class="text-[10px] font-black tracking-[0.22em] text-red-900 uppercase">
                            Expediente de teletrabajo
                        </p>
                        <h3 id="detalleTitulo" class="text-xl md:text-2xl font-black text-slate-900 mt-1 truncate">
                            Trabajo remoto temporal
                        </h3>
                        <p id="detalleSubtitulo" class="text-sm text-slate-500 font-semibold mt-1 truncate">
                            Información del documento
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <?php if ($puedeGestionar): ?>
                        <button type="button" id="btnAdendaDesdeDetalle"
                            class="hidden md:inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-red-900 text-white text-xs font-black shadow-lg shadow-red-900/20 hover:bg-red-800 transition">
                            <span class="text-base leading-none">+</span>
                            Nueva adenda
                        </button>
                    <?php endif; ?>

                    <button type="button" onclick="cerrarModalDetalle()"
                        class="w-10 h-10 rounded-2xl bg-slate-50 border border-slate-200 text-slate-500 hover:text-red-900 hover:bg-red-50 transition">
                        ✕
                    </button>
                </div>
            </header>

            <div class="flex-1 min-h-0 bg-gradient-to-br from-slate-50 via-white to-red-50/20 overflow-hidden">
                <div class="h-full grid grid-cols-1 lg:grid-cols-[360px_minmax(0,1fr)]">

                    <!-- RESUMEN DOCUMENTO ACTUAL -->
                    <aside class="border-b lg:border-b-0 lg:border-r border-slate-200 bg-white/85 backdrop-blur-sm overflow-y-auto">
                        <div class="p-5 md:p-6 space-y-4">

                            <div class="rounded-[1.5rem] border border-red-100 bg-red-50/70 p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-red-900">
                                        Documento mostrado
                                    </p>
                                    <span id="detalleDocumentoBadge"
                                        class="px-2.5 py-1 rounded-xl bg-white border border-red-100 text-[10px] font-black text-red-900 uppercase">
                                        Actual
                                    </span>
                                </div>

                                <h4 id="detalleDocumento" class="text-base font-black text-slate-900 mt-3 leading-snug">
                                    -
                                </h4>

                                <p id="detalleEstado" class="mt-3 inline-flex px-3 py-1.5 rounded-xl border text-[10px] font-black uppercase">
                                    -
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-3">
                                <div class="rounded-2xl bg-white border border-slate-200 p-4">
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Vigencia actual</p>
                                    <p id="detalleVigencia" class="text-sm font-black text-slate-800 mt-1">-</p>
                                </div>

                                <div class="rounded-2xl bg-white border border-slate-200 p-4">
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Área / Puesto</p>
                                    <p id="detalleArea" class="text-sm font-black text-slate-800 mt-1">-</p>
                                </div>

                                <div class="rounded-2xl bg-white border border-slate-200 p-4">
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Observación</p>
                                    <p id="detalleObservacion" class="text-sm font-semibold text-slate-600 mt-1 leading-relaxed">-</p>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <!-- HISTORIAL ESCALABLE -->
                    <section class="min-h-0 flex flex-col">
                        <div class="px-5 md:px-6 py-4 border-b border-slate-200 bg-white/90 backdrop-blur-xl shrink-0">
                            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="text-base font-black text-slate-900">
                                            Historial del acuerdo
                                        </h4>

                                        <span id="historialTotal"
                                            class="px-3 py-1 rounded-xl bg-slate-50 border border-slate-200 text-[10px] font-black text-slate-500 uppercase">
                                            0 registros
                                        </span>
                                    </div>

                                    <p id="historialResumen" class="text-xs text-slate-400 font-semibold mt-1">
                                        Acuerdo principal y adendas ordenadas cronológicamente.
                                    </p>
                                </div>

                                <div class="flex flex-col md:flex-row gap-2 w-full xl:w-auto">
                                    <div class="relative w-full xl:w-[320px]">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </span>

                                        <input type="text" id="historialSearchInput"
                                            class="w-full pl-9 pr-3 py-2.5 rounded-2xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-700 outline-none focus:bg-white focus:border-red-900 focus:ring-4 focus:ring-red-900/10"
                                            placeholder="Buscar documento, motivo u observación...">
                                    </div>

                                    <select id="historialFilterTipo"
                                        class="w-full md:w-[170px] rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-black text-slate-700 outline-none focus:bg-white focus:border-red-900 focus:ring-4 focus:ring-red-900/10">
                                        <option value="">Todos</option>
                                        <option value="ACUERDO">Acuerdo</option>
                                        <option value="ADENDA">Adendas</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="historialDocumentos" class="flex-1 min-h-0 overflow-y-auto p-5 md:p-6 space-y-3">
                            <!-- Se pinta desde JS -->
                        </div>
                    </section>
                </div>
            </div>
        </section>
    </div>
</main>

<script>
    const COLABORADORES_REMOTO = <?= json_encode($colaboradoresJs, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const HISTORIAL_REMOTO = <?= json_encode($historialRemotoJs ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    document.addEventListener('DOMContentLoaded', function() {
        iniciarTablaRemoto();
        iniciarBuscadorColaboradores();
    });

    function normalizarRemoto(valor) {
        return (valor || '').toString().trim().toLowerCase();
    }

    function iniciarTablaRemoto() {
        const input = document.getElementById('searchInput');
        const clearBtn = document.getElementById('clearSearch');
        const filterEstado = document.getElementById('filterEstado');
        const filterArea = document.getElementById('filterArea');
        const filterSituacion = document.getElementById('filterSituacion');
        const btnClearFilters = document.getElementById('btnClearFilters');
        const pageSizeSelect = document.getElementById('pageSize');
        const rows = Array.from(document.querySelectorAll('.remoto-row'));
        const emptyState = document.getElementById('emptyState');
        const resultCount = document.getElementById('resultCount');
        const rangeInfo = document.getElementById('rangeInfo');
        const currentPageLabel = document.getElementById('currentPageLabel');
        const totalPagesLabel = document.getElementById('totalPagesLabel');
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        const paginationNumbers = document.getElementById('paginationNumbers');

        let filteredRows = [...rows];
        let currentPage = 1;

        function obtenerPageSize() {
            const size = parseInt(pageSizeSelect?.value || '25', 10);
            return Number.isNaN(size) ? 25 : size;
        }

        function obtenerFiltros() {
            return {
                q: normalizarRemoto(input?.value || ''),
                estado: (filterEstado?.value || '').trim().toUpperCase(),
                area: normalizarRemoto(filterArea?.value || ''),
                situacion: (filterSituacion?.value || '').trim().toUpperCase()
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
                const estado = (row.dataset.estado || '').toUpperCase();
                const area = normalizarRemoto(row.dataset.area || '');
                const situacion = (row.dataset.situacion || '').toUpperCase();

                const coincideBusqueda = !filtros.q || search.includes(filtros.q);
                const coincideEstado = !filtros.estado || estado === filtros.estado;
                const coincideArea = !filtros.area || area === filtros.area;
                const coincideSituacion = !filtros.situacion || situacion === filtros.situacion;

                return coincideBusqueda && coincideEstado && coincideArea && coincideSituacion;
            });

            currentPage = 1;
            renderTable();
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

            rows.forEach(row => row.classList.add('hidden'));

            filteredRows.slice(start, end).forEach((row, index) => {
                row.classList.remove('hidden');
                row.classList.remove('row-clear', 'row-soft');
                row.classList.add(index % 2 === 0 ? 'row-clear' : 'row-soft');
            });

            emptyState?.classList.toggle('hidden', totalResults !== 0);

            if (resultCount) resultCount.textContent = totalResults;
            if (rangeInfo) rangeInfo.textContent = totalResults === 0 ? '0' : `${start + 1}-${end}`;
            if (currentPageLabel) currentPageLabel.textContent = totalResults === 0 ? '0' : currentPage;
            if (totalPagesLabel) totalPagesLabel.textContent = totalResults === 0 ? '0' : totalPages;

            if (prevBtn) prevBtn.disabled = currentPage <= 1 || totalResults === 0;
            if (nextBtn) nextBtn.disabled = currentPage >= totalPages || totalResults === 0;

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

        input?.addEventListener('input', aplicarFiltro);
        filterEstado?.addEventListener('change', aplicarFiltro);
        filterArea?.addEventListener('change', aplicarFiltro);
        filterSituacion?.addEventListener('change', aplicarFiltro);
        pageSizeSelect?.addEventListener('change', function() {
            currentPage = 1;
            renderTable();
        });

        clearBtn?.addEventListener('click', function() {
            if (!input) return;
            input.value = '';
            input.focus();
            aplicarFiltro();
        });

        btnClearFilters?.addEventListener('click', function() {
            if (input) input.value = '';
            if (filterEstado) filterEstado.value = '';
            if (filterArea) filterArea.value = '';
            if (filterSituacion) filterSituacion.value = '';
            aplicarFiltro();
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

        aplicarFiltro();
    }

    function iniciarBuscadorColaboradores() {
        const input = document.getElementById('colaboradorSearchInput');
        const hidden = document.getElementById('acuerdoColabId');
        const results = document.getElementById('colaboradorResults');

        if (!input || !hidden || !results) return;

        let touched = false;

        function renderResults() {
            if (!touched) return;

            const q = normalizarRemoto(input.value);

            const filtrados = COLABORADORES_REMOTO
                .filter(c => {
                    const texto = normalizarRemoto(`${c.nombre} ${c.dni} ${c.area} ${c.puesto} ${c.situacion}`);
                    return !q || texto.includes(q);
                })
                .slice(0, 12);

            results.innerHTML = '';

            if (filtrados.length === 0) {
                results.innerHTML = '<div class="px-4 py-4 text-sm font-semibold text-slate-400">No se encontraron colaboradores.</div>';
                results.classList.remove('hidden');
                return;
            }

            filtrados.forEach(c => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'colab-option w-full text-left px-4 py-3 border-b border-slate-100 last:border-b-0 transition';
                btn.innerHTML = `
                    <div class="text-sm font-black text-slate-800">${escapeHtml(c.nombre || 'Sin nombre')}</div>
                    <div class="text-[11px] font-bold text-slate-400 mt-0.5">DNI: ${escapeHtml(c.dni || 'No registrado')} · ${escapeHtml(c.area || 'Sin área')} · ${escapeHtml(c.situacion || 'ACTIVO')}</div>
                `;

                btn.addEventListener('click', function() {
                    hidden.value = c.id;
                    input.value = `${c.nombre} · DNI ${c.dni}`;
                    results.classList.add('hidden');
                });

                results.appendChild(btn);
            });

            results.classList.remove('hidden');
        }

        input.addEventListener('focus', function() {
            touched = true;
            renderResults();
        });

        input.addEventListener('input', function() {
            hidden.value = '';
            touched = true;
            renderResults();
        });

        document.addEventListener('click', function(e) {
            if (!results.contains(e.target) && e.target !== input) {
                results.classList.add('hidden');
            }
        });
    }

    function abrirDrawerAcuerdo() {
        const drawer = document.getElementById('drawerAcuerdo');
        if (!drawer) return;

        const input = document.getElementById('colaboradorSearchInput');
        const hidden = document.getElementById('acuerdoColabId');
        const results = document.getElementById('colaboradorResults');

        if (input) input.value = '';
        if (hidden) hidden.value = '';
        if (results) results.classList.add('hidden');

        drawer.classList.remove('hidden');
        document.body.classList.add('remoto-drawer-open');

        requestAnimationFrame(() => {
            drawer.classList.add('drawer-remoto-open');
        });
    }

    function cerrarDrawerAcuerdo() {
        const drawer = document.getElementById('drawerAcuerdo');
        if (!drawer) return;

        drawer.classList.remove('drawer-remoto-open');

        setTimeout(() => {
            drawer.classList.add('hidden');
            document.body.classList.remove('remoto-drawer-open');
        }, 300);
    }

    function abrirDrawerAdenda(data) {
        const drawer = document.getElementById('drawerAdenda');
        if (!drawer) return;

        document.getElementById('adendaPadreId').value = data.acuerdo_id || data.documento_id || '';
        document.getElementById('adendaColabId').value = data.colab_id || '';
        document.getElementById('adendaResumen').textContent = `${data.nombre || 'Colaborador'} · Documento actual: ${data.numero_documento || 'Sin número'}`;

        const inicio = document.getElementById('adendaFechaInicio');
        if (inicio && data.fecha_fin) {
            const siguiente = sumarDias(data.fecha_fin, 1);
            inicio.value = siguiente;
        }

        drawer.classList.remove('hidden');
        document.body.classList.add('remoto-drawer-open');

        requestAnimationFrame(() => {
            drawer.classList.add('drawer-remoto-open');
        });
    }

    function cerrarDrawerAdenda() {
        const drawer = document.getElementById('drawerAdenda');
        if (!drawer) return;

        drawer.classList.remove('drawer-remoto-open');

        setTimeout(() => {
            drawer.classList.add('hidden');
            document.body.classList.remove('remoto-drawer-open');
        }, 300);
    }

    let HISTORIAL_MODAL_REMOTO = [];
    let DOCUMENTO_ACTUAL_MODAL_REMOTO_ID = 0;
    let DETALLE_ACTUAL_REMOTO = null;

    function abrirModalDetalle(data) {
        const modal = document.getElementById('modalDetalle');
        if (!modal) return;

        DETALLE_ACTUAL_REMOTO = data || {};

        document.getElementById('detalleTitulo').textContent = data.nombre || 'Trabajo remoto temporal';
        document.getElementById('detalleSubtitulo').textContent = `DNI: ${data.dni || 'No registrado'} · ${data.situacion || 'ACTIVO'}`;

        document.getElementById('detalleDocumento').textContent =
            `${data.tipo_registro || 'DOCUMENTO'} · ${data.numero_documento || 'Sin número'} · ${formatearFecha(data.fecha_documento)}`;

        document.getElementById('detalleVigencia').textContent =
            `${formatearFecha(data.fecha_inicio)} - ${formatearFecha(data.fecha_fin)}`;

        document.getElementById('detalleArea').textContent =
            `${data.area || 'Sin área'} · ${data.puesto || 'Sin puesto'}`;

        document.getElementById('detalleObservacion').textContent =
            data.observacion || data.motivo || 'Sin observación registrada.';

        const estadoEl = document.getElementById('detalleEstado');
        if (estadoEl) {
            estadoEl.className = `mt-3 inline-flex px-3 py-1.5 rounded-xl border text-[10px] font-black uppercase ${obtenerClaseEstadoHistorial(data.estado)}`;
            estadoEl.textContent = `${textoEstadoHistorial(data.estado)}${data.dias !== null && data.dias !== undefined ? ' · ' + Math.abs(parseInt(data.dias || 0, 10)) + ' día(s)' : ''}`;
        }

        const badge = document.getElementById('detalleDocumentoBadge');
        if (badge) {
            badge.textContent = data.tipo_registro === 'ADENDA' ? 'Adenda vigente' : 'Acuerdo vigente';
        }

        const btnAdenda = document.getElementById('btnAdendaDesdeDetalle');
        if (btnAdenda) {
            btnAdenda.onclick = function() {
                abrirDrawerAdenda(DETALLE_ACTUAL_REMOTO);
            };
        }

        const search = document.getElementById('historialSearchInput');
        const tipo = document.getElementById('historialFilterTipo');

        if (search) search.value = '';
        if (tipo) tipo.value = '';

        renderHistorialRemoto(data.acuerdo_id || data.documento_id || 0, data.documento_id || 0);

        modal.classList.remove('hidden');
    }

    function renderHistorialRemoto(acuerdoId, documentoActualId) {
        const historial = HISTORIAL_REMOTO[String(acuerdoId)] || HISTORIAL_REMOTO[acuerdoId] || [];

        HISTORIAL_MODAL_REMOTO = Array.isArray(historial) ? historial : [];
        DOCUMENTO_ACTUAL_MODAL_REMOTO_ID = parseInt(documentoActualId || 0, 10);

        actualizarResumenHistorial(HISTORIAL_MODAL_REMOTO, DOCUMENTO_ACTUAL_MODAL_REMOTO_ID);
        conectarFiltrosHistorial();
        pintarHistorialFiltrado();
    }

    function conectarFiltrosHistorial() {
        const search = document.getElementById('historialSearchInput');
        const tipo = document.getElementById('historialFilterTipo');

        if (search) {
            search.oninput = pintarHistorialFiltrado;
        }

        if (tipo) {
            tipo.onchange = pintarHistorialFiltrado;
        }
    }

    function pintarHistorialFiltrado() {
        const contenedor = document.getElementById('historialDocumentos');
        if (!contenedor) return;

        const search = document.getElementById('historialSearchInput');
        const tipo = document.getElementById('historialFilterTipo');

        const q = normalizarRemoto(search?.value || '');
        const tipoFiltro = (tipo?.value || '').toUpperCase();

        const filtrado = HISTORIAL_MODAL_REMOTO.filter(item => {
            const tipoItem = (item.tipo_registro || '').toUpperCase();

            const texto = normalizarRemoto([
                item.tipo_registro,
                item.numero_documento,
                item.fecha_documento,
                item.fecha_inicio,
                item.fecha_fin,
                item.motivo,
                item.observacion,
                item.estado_texto,
                item.estado
            ].join(' '));

            const coincideTexto = !q || texto.includes(q);
            const coincideTipo = !tipoFiltro || tipoItem === tipoFiltro;

            return coincideTexto && coincideTipo;
        });

        contenedor.innerHTML = '';

        if (!HISTORIAL_MODAL_REMOTO.length) {
            contenedor.innerHTML = `
            <div class="h-full min-h-[320px] flex items-center justify-center">
                <div class="text-center max-w-sm">
                    <div class="mx-auto w-16 h-16 rounded-3xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h4 class="text-base font-black text-slate-800">Sin historial registrado</h4>
                    <p class="text-sm text-slate-400 font-semibold mt-1">Este acuerdo todavía no tiene documentos asociados.</p>
                </div>
            </div>
        `;
            return;
        }

        if (!filtrado.length) {
            contenedor.innerHTML = `
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-6 text-center">
                <p class="text-sm font-black text-slate-700">No hay coincidencias</p>
                <p class="text-xs font-semibold text-slate-400 mt-1">Prueba limpiando la búsqueda o el filtro.</p>
            </div>
        `;
            return;
        }

        filtrado.forEach((item, index) => {
            contenedor.appendChild(crearCardHistorial(item, index, filtrado.length));
        });
    }

    function crearCardHistorial(item, index, totalFiltrado) {
        const esActual = parseInt(item.id || 0, 10) === DOCUMENTO_ACTUAL_MODAL_REMOTO_ID;
        const esAcuerdo = (item.tipo_registro || '').toUpperCase() === 'ACUERDO';

        const claseEstado = obtenerClaseEstadoHistorial(item.estado);
        const claseTipo = esAcuerdo ?
            'bg-red-900 text-white border-red-900' :
            'bg-slate-100 text-slate-700 border-slate-200';

        const textoDias = obtenerTextoDiasHistorial(item);

        const card = document.createElement('article');

        card.className = `
        relative rounded-[1.35rem] border px-4 py-4 transition-all
        ${esActual ? 'border-red-200 bg-red-50/60 shadow-sm' : 'border-slate-200 bg-white hover:bg-slate-50'}
    `;

        card.innerHTML = `
        <div class="flex gap-4">
            <div class="flex flex-col items-center shrink-0">
                <div class="w-10 h-10 rounded-2xl ${esAcuerdo ? 'bg-red-900 text-white' : 'bg-slate-100 text-slate-600'} flex items-center justify-center text-xs font-black border ${esAcuerdo ? 'border-red-900' : 'border-slate-200'}">
                    ${esAcuerdo ? 'A' : index}
                </div>

                ${index < totalFiltrado - 1 ? '<div class="w-px flex-1 min-h-[44px] bg-slate-200 mt-2"></div>' : ''}
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2.5 py-1 rounded-xl border text-[10px] font-black uppercase ${claseTipo}">
                                ${escapeHtml(item.tipo_registro || 'DOCUMENTO')}
                            </span>

                            ${esActual ? `
                                <span class="px-2.5 py-1 rounded-xl border border-red-100 bg-red-100 text-red-800 text-[10px] font-black uppercase">
                                    Documento mostrado
                                </span>
                            ` : ''}

                            <span class="px-2.5 py-1 rounded-xl border text-[10px] font-black uppercase ${claseEstado}">
                                ${escapeHtml(item.estado_texto || textoEstadoHistorial(item.estado))}
                            </span>
                        </div>

                        <h4 class="mt-2 text-sm md:text-base font-black text-slate-900 leading-snug">
                            ${escapeHtml(item.numero_documento || 'Sin número')}
                        </h4>

                        <p class="mt-1 text-xs font-semibold text-slate-400">
                            ${escapeHtml(item.motivo || 'Trabajo remoto temporal')}
                        </p>
                    </div>

                    <div class="xl:text-right shrink-0">
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">
                            Vigencia
                        </p>
                        <p class="text-xs font-black text-slate-800 mt-1">
                            ${formatearFecha(item.fecha_inicio)} - ${formatearFecha(item.fecha_fin)}
                        </p>
                        <p class="text-[11px] font-bold text-slate-400 mt-1">
                            ${escapeHtml(textoDias)}
                        </p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-2">
                    <div class="rounded-xl bg-slate-50 border border-slate-100 px-3 py-2">
                        <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-400">Fecha doc.</p>
                        <p class="text-xs font-black text-slate-700 mt-0.5">${formatearFecha(item.fecha_documento)}</p>
                    </div>

                    <div class="rounded-xl bg-slate-50 border border-slate-100 px-3 py-2">
                        <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-400">Inicio</p>
                        <p class="text-xs font-black text-slate-700 mt-0.5">${formatearFecha(item.fecha_inicio)}</p>
                    </div>

                    <div class="rounded-xl bg-slate-50 border border-slate-100 px-3 py-2">
                        <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-400">Fin</p>
                        <p class="text-xs font-black text-slate-700 mt-0.5">${formatearFecha(item.fecha_fin)}</p>
                    </div>
                </div>

                ${item.observacion ? `
                    <details class="mt-3 group">
                        <summary class="cursor-pointer select-none text-xs font-black text-red-900 hover:text-red-700">
                            Ver observación
                        </summary>
                        <div class="mt-2 rounded-xl bg-white border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-500 leading-relaxed">
                            ${escapeHtml(item.observacion)}
                        </div>
                    </details>
                ` : ''}
            </div>
        </div>
    `;

        return card;
    }

    function actualizarResumenHistorial(historial, documentoActualId) {
        const total = historial.length;
        const adendas = historial.filter(item => (item.tipo_registro || '').toUpperCase() === 'ADENDA').length;
        const actual = historial.find(item => parseInt(item.id || 0, 10) === parseInt(documentoActualId || 0, 10));

        const totalEl = document.getElementById('historialTotal');
        const resumenEl = document.getElementById('historialResumen');
        const totalMini = document.getElementById('historialTotalMini');
        const adendasMini = document.getElementById('historialAdendasMini');
        const actualMini = document.getElementById('historialActualMini');
        const coberturaEl = document.getElementById('historialCobertura');

        if (totalEl) {
            totalEl.textContent = `${total} registro${total === 1 ? '' : 's'}`;
        }

        if (resumenEl) {
            resumenEl.textContent = total > 0 ?
                `1 acuerdo principal y ${adendas} adenda${adendas === 1 ? '' : 's'} registrada${adendas === 1 ? '' : 's'}.` :
                'Acuerdo principal y adendas ordenadas cronológicamente.';
        }

        if (totalMini) totalMini.textContent = total;
        if (adendasMini) adendasMini.textContent = adendas;
        if (actualMini) actualMini.textContent = actual ? (actual.tipo_registro === 'ADENDA' ? 'AD' : 'AC') : '-';

        if (coberturaEl) {
            const fechasInicio = historial.map(i => i.fecha_inicio).filter(Boolean).sort();
            const fechasFin = historial.map(i => i.fecha_fin).filter(Boolean).sort();

            const inicio = fechasInicio.length ? fechasInicio[0] : null;
            const fin = fechasFin.length ? fechasFin[fechasFin.length - 1] : null;

            coberturaEl.textContent = inicio && fin ?
                `${formatearFecha(inicio)} - ${formatearFecha(fin)}` :
                'Sin fechas registradas';
        }
    }

    function obtenerClaseEstadoHistorial(estado) {
        estado = (estado || '').toUpperCase();

        if (estado === 'VIGENTE') {
            return 'bg-emerald-50 text-emerald-700 border-emerald-100';
        }

        if (estado === 'POR_VENCER') {
            return 'bg-amber-50 text-amber-700 border-amber-100';
        }

        if (estado === 'POR_INICIAR') {
            return 'bg-blue-50 text-blue-700 border-blue-100';
        }

        if (estado === 'VENCIDO') {
            return 'bg-red-50 text-red-800 border-red-100';
        }

        if (estado === 'ANULADO') {
            return 'bg-slate-100 text-slate-500 border-slate-200';
        }

        return 'bg-slate-50 text-slate-500 border-slate-200';
    }

    function textoEstadoHistorial(estado) {
        estado = (estado || '').toUpperCase();

        const mapa = {
            'VIGENTE': 'Vigente',
            'POR_VENCER': 'Por vencer',
            'POR_INICIAR': 'Por iniciar',
            'VENCIDO': 'Vencido',
            'ANULADO': 'Anulado',
            'SIN_FECHA': 'Sin fecha'
        };

        return mapa[estado] || 'Sin estado';
    }

    function obtenerTextoDiasHistorial(item) {
        const estado = (item.estado || '').toUpperCase();
        const dias = parseInt(item.dias || 0, 10);

        if (item.dias === null || item.dias === undefined || Number.isNaN(dias)) {
            return 'Sin conteo';
        }

        if (estado === 'POR_INICIAR') {
            return `Inicia en ${Math.abs(dias)} día(s)`;
        }

        if (estado === 'VENCIDO') {
            return `Venció hace ${Math.abs(dias)} día(s)`;
        }

        return `Faltan ${Math.abs(dias)} día(s)`;
    }

    function cerrarModalDetalle() {
        document.getElementById('modalDetalle')?.classList.add('hidden');
    }

    function sumarDias(fecha, dias) {
        const partes = (fecha || '').split('-').map(Number);
        if (partes.length !== 3) return '';

        const d = new Date(partes[0], partes[1] - 1, partes[2]);
        d.setDate(d.getDate() + dias);

        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');

        return `${y}-${m}-${day}`;
    }

    function formatearFecha(fecha) {
        if (!fecha) return 'No registrado';
        const partes = fecha.split('-');
        if (partes.length !== 3) return fecha;
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }

    function escapeHtml(str) {
        return (str || '').toString()
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
</script>