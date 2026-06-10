<?php
// Vista/modulos/rrhh/licencias.php

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 3) . DIRECTORY_SEPARATOR);
}

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "/login");
    exit();
}

$rolSesion = strtolower(trim($_SESSION['user_role'] ?? ''));

if (!in_array($rolSesion, ['superadmin', 'admin', 'rrhh'], true)) {
    http_response_code(403);
    die('No tienes permiso para acceder a este módulo.');
}

$puedeGestionar = true;

require_once ROOT_PATH . 'Modelo/Conexion.php';
require_once ROOT_PATH . 'Modelo/MdDirectorio.php';
require_once ROOT_PATH . 'Controlador/CtrDirectorio.php';
require_once ROOT_PATH . 'Modelo/MdLicencias.php';
require_once ROOT_PATH . 'Controlador/CtrLicencias.php';

if (!function_exists('h')) {
    function h($valor): string
    {
        return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('fechaView')) {
    function fechaView($fecha): string
    {
        if (empty($fecha) || $fecha === '0000-00-00') {
            return '—';
        }

        try {
            return (new DateTime($fecha))->format('d/m/Y');
        } catch (Throwable $e) {
            return (string)$fecha;
        }
    }
}

if (!function_exists('estadoLicenciaView')) {
    function estadoLicenciaView($fechaInicio, $fechaFin, $estadoDb = null): array
    {
        $estadoDb = strtoupper(trim((string)$estadoDb));

        if ($estadoDb === 'ANULADO') {
            return [
                'estado' => 'ANULADO',
                'texto' => 'Anulada',
                'class' => 'bg-slate-100 text-slate-500 border-slate-200'
            ];
        }

        if (empty($fechaInicio) || empty($fechaFin)) {
            return [
                'estado' => 'SIN_FECHA',
                'texto' => 'Sin fecha',
                'class' => 'bg-slate-100 text-slate-500 border-slate-200'
            ];
        }

        try {
            $hoy = new DateTime('today');
            $inicio = new DateTime($fechaInicio);
            $fin = new DateTime($fechaFin);

            if ($hoy < $inicio) {
                return [
                    'estado' => 'POR_INICIAR',
                    'texto' => 'Por iniciar',
                    'class' => 'bg-blue-50 text-blue-700 border-blue-100'
                ];
            }

            if ($hoy > $fin) {
                return [
                    'estado' => 'VENCIDO',
                    'texto' => 'Vencida',
                    'class' => 'bg-red-50 text-red-700 border-red-100'
                ];
            }

            $dias = (int)$hoy->diff($fin)->format('%a');

            if ($dias <= 15) {
                return [
                    'estado' => 'POR_VENCER',
                    'texto' => 'Por vencer',
                    'class' => 'bg-amber-50 text-amber-700 border-amber-100'
                ];
            }

            return [
                'estado' => 'VIGENTE',
                'texto' => 'Vigente',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-100'
            ];
        } catch (Throwable $e) {
            return [
                'estado' => 'SIN_FECHA',
                'texto' => 'Sin fecha',
                'class' => 'bg-slate-100 text-slate-500 border-slate-200'
            ];
        }
    }
}

$ctrLicencias = new CtrLicencias();
$ctrDirectorio = new CtrDirectorio();

$mensajeOperacion = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $puedeGestionar) {
    if (isset($_POST['crear_licencia'])) {
        $mensajeOperacion = $ctrLicencias->ctrCrearLicencia($_POST, null);
    }

    if (isset($_POST['anular_licencia'])) {
        $mensajeOperacion = $ctrLicencias->ctrAnularLicencia((int)($_POST['licencia_id'] ?? 0));
    }

    if (!empty($mensajeOperacion['success'])) {
        header('Location: ' . BASE_URL . '/rrhh/licencias?ok=1');
        exit;
    }
}

$licencias = $ctrLicencias->ctrListarLicencias();
$licencias = is_array($licencias) ? $licencias : [];

$colaboradores = $ctrDirectorio->ctrMostrarDirectorio();
$colaboradores = is_array($colaboradores) ? $colaboradores : [];

$colaboradoresJs = [];
$colaboradoresMap = [];

foreach ($colaboradores as $c) {
    $idColab = (int)($c['id'] ?? $c['colab_id'] ?? 0);

    if ($idColab <= 0) {
        continue;
    }

    $nombre = trim((string)($c['nombres_apellidos'] ?? $c['colaborador'] ?? ''));

    if ($nombre === '') {
        $nombre = trim(
            (string)($c['nombres'] ?? '') . ' ' .
                (string)($c['apellidos'] ?? '')
        );
    }

    $dni = trim((string)($c['dni'] ?? $c['documento'] ?? ''));
    $area = trim((string)($c['area'] ?? $c['dependencia'] ?? ''));
    $puesto = trim((string)($c['puesto_cas'] ?? $c['puesto'] ?? $c['cargo'] ?? ''));

    $itemColab = [
        'id' => $idColab,
        'nombre' => $nombre !== '' ? $nombre : 'Colaborador #' . $idColab,
        'dni' => $dni,
        'area' => $area,
        'puesto' => $puesto,
    ];

    $colaboradoresMap[$idColab] = $itemColab;
    $colaboradoresJs[] = $itemColab;
}

foreach ($licencias as $i => $licencia) {
    $idColab = (int)($licencia['colab_id'] ?? 0);

    if (isset($colaboradoresMap[$idColab])) {
        $licencias[$i]['nombres_apellidos'] = $colaboradoresMap[$idColab]['nombre'];
        $licencias[$i]['dni'] = $colaboradoresMap[$idColab]['dni'];
        $licencias[$i]['area'] = $colaboradoresMap[$idColab]['area'];
        $licencias[$i]['puesto_cas'] = $colaboradoresMap[$idColab]['puesto'];
        $licencias[$i]['puesto'] = $colaboradoresMap[$idColab]['puesto'];
    }
}

$totalVigentes = 0;
$totalVencidas = 0;
$totalPorIniciar = 0;
$totalPorVencer = 0;

foreach ($licencias as $item) {
    $estado = estadoLicenciaView($item['fecha_inicio'] ?? null, $item['fecha_fin'] ?? null, $item['estado'] ?? null);

    if ($estado['estado'] === 'VIGENTE') {
        $totalVigentes++;
    }

    if ($estado['estado'] === 'VENCIDO') {
        $totalVencidas++;
    }

    if ($estado['estado'] === 'POR_INICIAR') {
        $totalPorIniciar++;
    }

    if ($estado['estado'] === 'POR_VENCER') {
        $totalPorVencer++;
    }
}

$titulo_pagina = 'Licencias | RRHH';
$menu_activo = 'licencias';

require_once ROOT_PATH . 'Vista/includes/header.php';
require_once ROOT_PATH . 'Vista/includes/sidebar.php';
?>

<main class="flex-1 bg-slate-50 min-h-screen overflow-y-auto pb-12">

    <div class="px-6 lg:px-8 py-6 bg-white border-b border-slate-200">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900">Licencias</h1>
                <p class="text-sm text-slate-500 font-semibold mt-1">
                    Registro y seguimiento de licencias del personal.
                </p>
            </div>

            <?php if ($puedeGestionar): ?>
                <button type="button" onclick="abrirModalLicencia()"
                    class="px-5 py-3 rounded-2xl bg-red-900 text-white text-sm font-black hover:bg-[#310404] transition">
                    + Nueva licencia
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="px-6 lg:px-8 py-6 space-y-5">

        <?php if (!empty($_GET['ok'])): ?>
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-5 py-4 text-sm font-bold text-emerald-800">
                Operación realizada correctamente.
            </div>
        <?php endif; ?>

        <?php if (!empty($mensajeOperacion) && empty($mensajeOperacion['success'])): ?>
            <div class="rounded-2xl bg-red-50 border border-red-200 px-5 py-4 text-sm font-bold text-red-800">
                <?= h($mensajeOperacion['mensaje'] ?? 'No se pudo completar la operación.') ?>
            </div>
        <?php endif; ?>

        <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
            <div class="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total</p>
                <p class="text-3xl font-black text-slate-900 mt-3"><?= count($licencias) ?></p>
            </div>

            <div class="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Vigentes</p>
                <p class="text-3xl font-black text-emerald-700 mt-3"><?= $totalVigentes ?></p>
            </div>

            <div class="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Por iniciar</p>
                <p class="text-3xl font-black text-blue-700 mt-3"><?= $totalPorIniciar ?></p>
            </div>

            <div class="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Por vencer</p>
                <p class="text-3xl font-black text-amber-700 mt-3"><?= $totalPorVencer ?></p>
            </div>

            <div class="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Vencidas</p>
                <p class="text-3xl font-black text-red-700 mt-3"><?= $totalVencidas ?></p>
            </div>
        </section>

        <section class="bg-white border border-slate-200 rounded-[2rem] shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 space-y-4">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-black uppercase tracking-widest text-slate-800">
                            Listado de licencias
                        </h2>
                        <p id="contadorLicencias" class="text-xs text-slate-400 font-semibold mt-1">
                            <?= count($licencias) ?> registro(s)
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
                    <div class="lg:col-span-6">
                        <input type="text" id="buscarLicencias"
                            class="w-full rounded-2xl border border-slate-200 bg-[#fffaf7] px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:bg-white focus:border-red-900"
                            placeholder="Buscar por colaborador, DNI, tipo, documento o motivo...">
                    </div>

                    <div class="lg:col-span-3">
                        <select id="filtroEstadoLicencias"
                            class="w-full rounded-2xl border border-slate-200 bg-[#fffaf7] px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:bg-white focus:border-red-900">
                            <option value="">Todos los estados</option>
                            <option value="VIGENTE">Vigentes</option>
                            <option value="POR_INICIAR">Por iniciar</option>
                            <option value="POR_VENCER">Por vencer</option>
                            <option value="VENCIDO">Vencidas</option>
                            <option value="ANULADO">Anuladas</option>
                        </select>
                    </div>

                    <div class="lg:col-span-3">
                        <select id="filasPorPaginaLicencias"
                            class="w-full rounded-2xl border border-slate-200 bg-[#fffaf7] px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:bg-white focus:border-red-900">
                            <option value="10">10 filas por página</option>
                            <option value="25">25 filas por página</option>
                            <option value="50">50 filas por página</option>
                            <option value="100">100 filas por página</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-widest text-slate-400">Colaborador</th>
                            <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-widest text-slate-400">Tipo</th>
                            <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-widest text-slate-400">Documento</th>
                            <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-widest text-slate-400">Vigencia</th>
                            <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-widest text-slate-400">Estado</th>
                            <th class="px-5 py-4 text-right text-[10px] font-black uppercase tracking-widest text-slate-400">Acción</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($licencias)): ?>
                            <tr>
                                <td colspan="6" class="px-5 py-16 text-center">
                                    <p class="text-base font-black text-slate-700">No hay licencias registradas.</p>
                                    <p class="text-sm font-semibold text-slate-400 mt-1">Registra una nueva licencia para empezar.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($licencias as $row): ?>
                                <?php
                                $estado = estadoLicenciaView($row['fecha_inicio'] ?? null, $row['fecha_fin'] ?? null, $row['estado'] ?? null);

                                $detalleLicencia = [
                                    'id' => (int)($row['id'] ?? 0),
                                    'colaborador' => (string)($row['nombres_apellidos'] ?? 'Colaborador'),
                                    'dni' => (string)($row['dni'] ?? ''),
                                    'area' => (string)($row['area'] ?? ''),
                                    'puesto' => (string)($row['puesto_cas'] ?? $row['puesto'] ?? ''),
                                    'tipo' => (string)($row['tipo_licencia'] ?? ''),
                                    'documento' => (string)($row['numero_documento'] ?? 'S/N'),
                                    'fecha_documento' => fechaView($row['fecha_documento'] ?? null),
                                    'fecha_inicio' => fechaView($row['fecha_inicio'] ?? null),
                                    'fecha_fin' => fechaView($row['fecha_fin'] ?? null),
                                    'dias' => (int)($row['dias_calendario'] ?? 0),
                                    'motivo' => (string)($row['motivo'] ?? ''),
                                    'observacion' => (string)($row['observacion'] ?? ''),
                                    'estado' => (string)($estado['texto'] ?? ''),
                                ];

                                $search = strtolower(trim(implode(' ', [
                                    $row['nombres_apellidos'] ?? '',
                                    $row['dni'] ?? '',
                                    $row['tipo_licencia'] ?? '',
                                    $row['numero_documento'] ?? '',
                                    $row['motivo'] ?? '',
                                ])));
                                ?>

                                <tr class="licencia-row hover:bg-[#fffaf7] transition" data-search="<?= h($search) ?>" data-estado="<?= h($estado['estado'] ?? '') ?>">
                                    <td class="px-5 py-4">
                                        <p class="text-sm font-black text-slate-800">
                                            <?= h($row['nombres_apellidos'] ?? 'Colaborador') ?>
                                        </p>
                                        <p class="text-xs font-semibold text-slate-400 mt-1">
                                            DNI: <?= h($row['dni'] ?? '—') ?>
                                        </p>
                                        <p class="text-xs font-semibold text-slate-400">
                                            <?= h($row['area'] ?? 'Sin área') ?>
                                        </p>
                                    </td>

                                    <td class="px-5 py-4">
                                        <span class="inline-flex px-3 py-1 rounded-xl bg-red-50 text-red-900 border border-red-100 text-[10px] font-black uppercase">
                                            <?= h($row['tipo_licencia'] ?? 'Licencia') ?>
                                        </span>
                                    </td>

                                    <td class="px-5 py-4">
                                        <p class="text-sm font-black text-slate-800">
                                            <?= h($row['numero_documento'] ?? 'S/N') ?>
                                        </p>
                                        <p class="text-xs text-slate-400 font-semibold">
                                            <?= fechaView($row['fecha_documento'] ?? null) ?>
                                        </p>
                                    </td>

                                    <td class="px-5 py-4">
                                        <p class="text-sm font-black text-slate-800">
                                            <?= fechaView($row['fecha_inicio'] ?? null) ?> -
                                            <?= fechaView($row['fecha_fin'] ?? null) ?>
                                        </p>
                                        <p class="text-xs text-slate-400 font-semibold">
                                            <?= (int)($row['dias_calendario'] ?? 0) ?> día(s)
                                        </p>
                                    </td>

                                    <td class="px-5 py-4">
                                        <span class="inline-flex px-3 py-1.5 rounded-xl border text-[10px] font-black uppercase <?= h($estado['class']) ?>">
                                            <?= h($estado['texto']) ?>
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button"
                                                title="Ver detalle"
                                                data-detalle="<?= h(json_encode($detalleLicencia, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>"
                                                onclick="abrirDetalleLicencia(this)"
                                                class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-slate-100 text-slate-700 border border-slate-200 hover:bg-red-50 hover:text-red-900 hover:border-red-100 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </button>

                                            <?php if ($puedeGestionar && strtoupper((string)($row['estado'] ?? '')) !== 'ANULADO'): ?>
                                                <form method="POST" onsubmit="return confirm('¿Seguro que deseas anular esta licencia?');">
                                                    <input type="hidden" name="licencia_id" value="<?= (int)($row['id'] ?? 0) ?>">
                                                    <button type="submit" name="anular_licencia" value="1"
                                                        class="px-3 py-2 rounded-xl bg-red-50 text-red-700 border border-red-100 text-xs font-black hover:bg-red-100">
                                                        Anular
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <tr id="sinResultadosLicencias" class="hidden">
                            <td colspan="6" class="px-5 py-16 text-center">
                                <div class="mx-auto w-14 h-14 rounded-3xl bg-red-50 border border-red-100 flex items-center justify-center text-red-900 mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2m0 0A7.5 7.5 0 105.2 5.2a7.5 7.5 0 0010.6 10.6z" />
                                    </svg>
                                </div>
                                <p class="text-base font-black text-slate-700">No se encontraron licencias.</p>
                                <p class="text-sm font-semibold text-slate-400 mt-1">Prueba cambiando la búsqueda o el filtro.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-4 border-t border-slate-100 bg-[#fffaf7] flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <p id="infoPaginacionLicencias" class="text-xs font-bold text-slate-500">
                    Mostrando registros.
                </p>

                <div class="flex items-center gap-2">
                    <button type="button" id="btnPrevLicencias"
                        class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-black text-slate-700 hover:bg-red-50 disabled:opacity-40 disabled:cursor-not-allowed">
                        Anterior
                    </button>

                    <span id="paginaActualLicencias"
                        class="min-w-10 text-center px-3 py-2 rounded-xl bg-red-900 text-white text-xs font-black">
                        1
                    </span>

                    <button type="button" id="btnNextLicencias"
                        class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-black text-slate-700 hover:bg-red-50 disabled:opacity-40 disabled:cursor-not-allowed">
                        Siguiente
                    </button>
                </div>
            </div>
        </section>
    </div>
</main>

<!-- MODAL NUEVA LICENCIA -->
<div id="modalLicencia" class="fixed inset-0 z-[9999] hidden">
    <div class="absolute inset-0 bg-[#180506]/60 backdrop-blur-sm" onclick="cerrarModalLicencia()"></div>

    <div class="relative z-10 min-h-[100dvh] w-full flex items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-4xl max-h-[92dvh] rounded-[2rem] bg-[#fffaf7] border border-red-100 shadow-2xl overflow-hidden flex flex-col">

            <div class="relative px-6 py-5 bg-gradient-to-r from-[#3b060b] via-[#7a0f16] to-[#9f1d23] text-white">
                <div class="absolute right-6 top-5">
                    <button type="button" onclick="cerrarModalLicencia()"
                        class="w-10 h-10 rounded-2xl bg-white/10 hover:bg-white/20 flex items-center justify-center font-black">
                        ✕
                    </button>
                </div>

                <div class="flex items-start gap-4 pr-14">
                    <div class="w-14 h-14 rounded-3xl bg-white/15 border border-white/20 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                        </svg>
                    </div>

                    <div>
                        <p class="text-red-100 text-[10px] font-black uppercase tracking-widest">
                            RRHH · Licencias
                        </p>
                        <h3 class="text-2xl font-black mt-1">Nueva licencia</h3>
                        <p class="text-sm text-red-50 font-semibold mt-1">
                            Registra una licencia del personal con sus fechas, documento y motivo.
                        </p>
                    </div>
                </div>
            </div>

            <form method="POST" class="flex-1 min-h-0 flex flex-col">
                <div class="flex-1 min-h-0 overflow-y-auto p-5 sm:p-6 space-y-5">

                    <!-- BLOQUE COLABORADOR -->
                    <div class="rounded-[1.5rem] bg-white border border-red-100 shadow-sm p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-2xl bg-red-50 text-red-900 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 20.25a7.5 7.5 0 0115 0" />
                                </svg>
                            </div>

                            <div>
                                <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest">
                                    Datos del colaborador
                                </h4>
                                <p class="text-xs font-semibold text-slate-400">
                                    Busca por nombre o DNI y selecciona al trabajador.
                                </p>
                            </div>
                        </div>

                        <div class="relative">
                            <label class="block text-xs font-black uppercase tracking-widest text-red-900/60 mb-2">
                                Colaborador
                            </label>

                            <input type="hidden" name="colab_id" id="lic_colab_id">

                            <input type="text" id="lic_buscar_colaborador"
                                class="w-full rounded-2xl border border-red-100 bg-[#fffaf7] px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:bg-white focus:border-red-900 focus:ring-4 focus:ring-red-100"
                                placeholder="Buscar por nombre o DNI..." autocomplete="off">

                            <div id="lic_resultados"
                                class="hidden absolute z-30 mt-2 w-full max-h-72 overflow-y-auto rounded-2xl border border-red-100 bg-white shadow-2xl">
                            </div>
                        </div>
                    </div>

                    <!-- BLOQUE TIPO Y DOCUMENTO -->
                    <div class="rounded-[1.5rem] bg-white border border-red-100 shadow-sm p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-2xl bg-orange-50 text-red-900 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75h6L18 8.25v12H7.5a1.5 1.5 0 01-1.5-1.5V5.25a1.5 1.5 0 011.5-1.5z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 3.75v4.5H18" />
                                </svg>
                            </div>

                            <div>
                                <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest">
                                    Documento de licencia
                                </h4>
                                <p class="text-xs font-semibold text-slate-400">
                                    Completa el tipo, número y fecha del documento.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-3">
                                <label class="block text-xs font-black uppercase tracking-widest text-red-900/60 mb-2">
                                    Tipo de licencia
                                </label>

                                <select name="tipo_licencia" required
                                    class="w-full rounded-2xl border border-red-100 bg-[#fffaf7] px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:bg-white focus:border-red-900 focus:ring-4 focus:ring-red-100">
                                    <option value="">Seleccionar</option>
                                    <option value="LICENCIA CON GOCE">Licencia con goce</option>
                                    <option value="LICENCIA SIN GOCE">Licencia sin goce</option>
                                    <option value="DESCANSO MÉDICO">Descanso médico</option>
                                    <option value="MATERNIDAD">Maternidad</option>
                                    <option value="PATERNIDAD">Paternidad</option>
                                    <option value="ONOMÁSTICO">Onomástico</option>
                                    <option value="CAPACITACIÓN">Capacitación</option>
                                    <option value="COMISIÓN DE SERVICIO">Comisión de servicio</option>
                                    <option value="OTROS">Otros</option>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-black uppercase tracking-widest text-red-900/60 mb-2">
                                    N° documento
                                </label>
                                <input type="text" name="numero_documento"
                                    class="w-full rounded-2xl border border-red-100 bg-[#fffaf7] px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:bg-white focus:border-red-900 focus:ring-4 focus:ring-red-100"
                                    placeholder="Ej. LIC-001-2026">
                            </div>

                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest text-red-900/60 mb-2">
                                    Fecha documento
                                </label>
                                <input type="date" name="fecha_documento"
                                    class="w-full rounded-2xl border border-red-100 bg-[#fffaf7] px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:bg-white focus:border-red-900 focus:ring-4 focus:ring-red-100">
                            </div>
                        </div>
                    </div>

                    <!-- BLOQUE PERIODO -->
                    <div class="rounded-[1.5rem] bg-white border border-red-100 shadow-sm p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-2xl bg-red-50 text-red-900 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 8.25h16.5M5.25 5.25h13.5A1.5 1.5 0 0120.25 6.75v12A1.5 1.5 0 0118.75 20.25H5.25a1.5 1.5 0 01-1.5-1.5v-12A1.5 1.5 0 015.25 5.25z" />
                                </svg>
                            </div>

                            <div>
                                <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest">
                                    Periodo y sustento
                                </h4>
                                <p class="text-xs font-semibold text-slate-400">
                                    Define la vigencia de la licencia y registra el motivo.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest text-red-900/60 mb-2">
                                    Inicio
                                </label>
                                <input type="date" name="fecha_inicio" required
                                    class="w-full rounded-2xl border border-red-100 bg-[#fffaf7] px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:bg-white focus:border-red-900 focus:ring-4 focus:ring-red-100">
                            </div>

                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest text-red-900/60 mb-2">
                                    Fin
                                </label>
                                <input type="date" name="fecha_fin" required
                                    class="w-full rounded-2xl border border-red-100 bg-[#fffaf7] px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:bg-white focus:border-red-900 focus:ring-4 focus:ring-red-100">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-black uppercase tracking-widest text-red-900/60 mb-2">
                                    Motivo
                                </label>
                                <input type="text" name="motivo"
                                    class="w-full rounded-2xl border border-red-100 bg-[#fffaf7] px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:bg-white focus:border-red-900 focus:ring-4 focus:ring-red-100"
                                    placeholder="Ej. Licencia por paternidad, descanso médico, comisión de servicio...">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-black uppercase tracking-widest text-red-900/60 mb-2">
                                    Observación
                                </label>
                                <textarea name="observacion" rows="4"
                                    class="w-full rounded-2xl border border-red-100 bg-[#fffaf7] px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:bg-white focus:border-red-900 focus:ring-4 focus:ring-red-100 resize-none"
                                    placeholder="Agrega una observación adicional si corresponde."></textarea>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="shrink-0 px-6 py-4 border-t border-red-100 bg-white flex flex-col sm:flex-row sm:justify-end gap-3">
                    <button type="button" onclick="cerrarModalLicencia()"
                        class="px-6 py-3 rounded-2xl bg-white border border-slate-200 text-sm font-black text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>

                    <button type="submit" name="crear_licencia" value="1"
                        class="px-6 py-3 rounded-2xl bg-red-900 text-white text-sm font-black hover:bg-[#310404] shadow-lg shadow-red-900/20">
                        Guardar licencia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL DETALLE LICENCIA -->
<div id="modalDetalleLicencia" class="fixed inset-0 z-[10000] hidden">
    <div class="absolute inset-0 bg-[#180506]/60 backdrop-blur-sm" onclick="cerrarDetalleLicencia()"></div>

    <div class="relative z-10 min-h-[100dvh] w-full flex items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-3xl max-h-[92dvh] rounded-[2rem] bg-[#fffaf7] border border-red-100 shadow-2xl overflow-hidden flex flex-col">

            <div class="relative px-6 py-5 bg-gradient-to-r from-[#3b060b] via-[#7a0f16] to-[#9f1d23] text-white">
                <div class="absolute right-6 top-5">
                    <button type="button" onclick="cerrarDetalleLicencia()"
                        class="w-10 h-10 rounded-2xl bg-white/10 hover:bg-white/20 flex items-center justify-center font-black">
                        ✕
                    </button>
                </div>

                <div class="flex items-start gap-4 pr-14">
                    <div class="w-14 h-14 rounded-3xl bg-white/15 border border-white/20 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>

                    <div>
                        <p class="text-red-100 text-[10px] font-black uppercase tracking-widest">RRHH · Licencias</p>
                        <h3 class="text-2xl font-black mt-1">Detalle de licencia</h3>
                        <p id="detalleLicenciaTitulo" class="text-sm text-red-50 font-bold mt-1"></p>
                        <p id="detalleLicenciaSubtitulo" class="text-xs text-red-100 font-semibold mt-1"></p>
                    </div>
                </div>
            </div>

            <div id="detalleLicenciaContenido"
                class="flex-1 min-h-0 overflow-y-auto p-5 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-3">
            </div>

            <div class="shrink-0 px-6 py-4 border-t border-red-100 bg-white flex justify-end">
                <button type="button" onclick="cerrarDetalleLicencia()"
                    class="px-6 py-3 rounded-2xl bg-red-900 text-white text-sm font-black hover:bg-[#310404]">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const COLABORADORES_LIC = <?= json_encode($colaboradoresJs, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function abrirModalLicencia() {
        const modal = document.getElementById('modalLicencia');
        const inputColaborador = document.getElementById('lic_buscar_colaborador');

        modal?.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        setTimeout(() => {
            inputColaborador?.focus();
        }, 150);
    }

    function cerrarModalLicencia() {
        const modal = document.getElementById('modalLicencia');
        const resultados = document.getElementById('lic_resultados');

        modal?.classList.add('hidden');
        resultados?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function normalizarLic(texto) {
        return (texto || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
    }

    function escapeLic(texto) {
        return (texto || '').toString()
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function campoDetalleLic(label, valor, completo = false) {
        const clase = completo ? 'md:col-span-2' : '';

        return `
        <div class="${clase} rounded-2xl bg-white border border-red-100 p-4 shadow-sm">
            <p class="text-[10px] font-black uppercase tracking-widest text-red-900/60">${escapeLic(label)}</p>
            <p class="text-sm font-black text-slate-800 mt-1 whitespace-pre-line leading-relaxed">${escapeLic(valor || '—')}</p>
        </div>
    `;
    }

    function abrirDetalleLicencia(btn) {
        const modal = document.getElementById('modalDetalleLicencia');
        const contenedor = document.getElementById('detalleLicenciaContenido');
        const titulo = document.getElementById('detalleLicenciaTitulo');
        const subtitulo = document.getElementById('detalleLicenciaSubtitulo');

        if (!modal || !contenedor) {
            return;
        }

        let data = {};

        try {
            data = JSON.parse(btn.getAttribute('data-detalle') || '{}');
        } catch (e) {
            data = {};
        }

        if (titulo) {
            titulo.textContent = data.colaborador || 'Colaborador';
        }

        if (subtitulo) {
            subtitulo.textContent = `DNI: ${data.dni || '—'} · ${data.area || 'Sin área'}`;
        }

        contenedor.innerHTML = `
            <div class="md:col-span-2 rounded-[1.5rem] bg-gradient-to-r from-red-50 to-orange-50 border border-red-100 p-5">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-red-900/60">
                            Colaborador
                        </p>

                        <p class="text-lg font-black text-slate-900 mt-1">
                            ${escapeLic(data.colaborador || 'Colaborador')}
                        </p>

                        <p class="text-xs font-bold text-slate-500 mt-1">
                            DNI: ${escapeLic(data.dni || '—')} · ${escapeLic(data.area || 'Sin área')}
                        </p>

                        <p class="text-xs font-bold text-slate-500 mt-1">
                            Puesto: ${escapeLic(data.puesto || '—')}
                        </p>
                    </div>

                    <div class="inline-flex w-fit px-4 py-2 rounded-2xl bg-white border border-red-100 text-red-900 text-xs font-black uppercase shadow-sm">
                        ${escapeLic(data.estado || '—')}
                    </div>
                </div>
            </div>

    ${campoDetalleLic('Tipo de licencia', data.tipo)}
    ${campoDetalleLic('N° documento', data.documento)}
    ${campoDetalleLic('Fecha documento', data.fecha_documento)}
    ${campoDetalleLic('Inicio', data.fecha_inicio)}
    ${campoDetalleLic('Fin', data.fecha_fin)}
    ${campoDetalleLic('Días calendario', data.dias ? `${data.dias} día(s)` : '—')}
    ${campoDetalleLic('Motivo', data.motivo, true)}
    ${campoDetalleLic('Observación', data.observacion, true)}
`;
        modal.classList.remove('hidden');
    }

    function cerrarDetalleLicencia() {
        document.getElementById('modalDetalleLicencia')?.classList.add('hidden');
    }

    const inputBuscarColab = document.getElementById('lic_buscar_colaborador');
    const inputColabId = document.getElementById('lic_colab_id');
    const boxResultados = document.getElementById('lic_resultados');

    if (inputBuscarColab && inputColabId && boxResultados) {
        inputBuscarColab.addEventListener('input', function() {
            const q = normalizarLic(this.value);
            inputColabId.value = '';
            boxResultados.innerHTML = '';

            if (q.length < 2) {
                boxResultados.classList.add('hidden');
                return;
            }

            const resultados = COLABORADORES_LIC.filter(c => {
                return normalizarLic(`${c.nombre} ${c.dni} ${c.area} ${c.puesto}`).includes(q);
            }).slice(0, 10);

            if (!resultados.length) {
                boxResultados.innerHTML = '<div class="px-4 py-3 text-sm font-bold text-slate-400">No se encontraron colaboradores.</div>';
                boxResultados.classList.remove('hidden');
                return;
            }

            resultados.forEach(c => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'w-full text-left px-4 py-3 hover:bg-red-50 border-b border-slate-100';
                btn.innerHTML = `
                <p class="text-sm font-black text-slate-800">${escapeLic(c.nombre)}</p>
                <p class="text-xs font-semibold text-slate-400">DNI: ${escapeLic(c.dni || '—')} · ${escapeLic(c.area || '')}</p>
            `;

                btn.onclick = function() {
                    inputColabId.value = c.id;
                    inputBuscarColab.value = `${c.nombre} · DNI ${c.dni || '—'}`;
                    boxResultados.classList.add('hidden');
                };

                boxResultados.appendChild(btn);
            });

            boxResultados.classList.remove('hidden');
        });
    }

    const inputBuscarLicencias = document.getElementById('buscarLicencias');
    const filtroEstadoLicencias = document.getElementById('filtroEstadoLicencias');
    const filasPorPaginaLicencias = document.getElementById('filasPorPaginaLicencias');
    const contadorLicencias = document.getElementById('contadorLicencias');
    const infoPaginacionLicencias = document.getElementById('infoPaginacionLicencias');
    const paginaActualLicencias = document.getElementById('paginaActualLicencias');
    const btnPrevLicencias = document.getElementById('btnPrevLicencias');
    const btnNextLicencias = document.getElementById('btnNextLicencias');
    const sinResultadosLicencias = document.getElementById('sinResultadosLicencias');

    let paginaLicencias = 1;

    function renderLicencias(reset = false) {
        if (reset) {
            paginaLicencias = 1;
        }

        const rows = Array.from(document.querySelectorAll('.licencia-row'));
        const q = normalizarLic(inputBuscarLicencias?.value || '');
        const estado = filtroEstadoLicencias?.value || '';
        const porPagina = parseInt(filasPorPaginaLicencias?.value || '10', 10);

        const filtradas = rows.filter(row => {
            const texto = normalizarLic(row.dataset.search || '');
            const estadoRow = row.dataset.estado || '';

            const coincideTexto = !q || texto.includes(q);
            const coincideEstado = !estado || estadoRow === estado;

            return coincideTexto && coincideEstado;
        });

        const total = filtradas.length;
        const totalPaginas = Math.max(1, Math.ceil(total / porPagina));

        if (paginaLicencias > totalPaginas) {
            paginaLicencias = totalPaginas;
        }

        rows.forEach(row => row.classList.add('hidden'));

        const inicio = (paginaLicencias - 1) * porPagina;
        const fin = inicio + porPagina;

        filtradas.slice(inicio, fin).forEach(row => {
            row.classList.remove('hidden');
        });

        if (sinResultadosLicencias) {
            sinResultadosLicencias.classList.toggle('hidden', total !== 0 || rows.length === 0);
        }

        if (contadorLicencias) {
            contadorLicencias.textContent = `${total} registro(s) encontrado(s)`;
        }

        if (infoPaginacionLicencias) {
            if (total === 0) {
                infoPaginacionLicencias.textContent = 'No hay registros para mostrar.';
            } else {
                infoPaginacionLicencias.textContent = `Mostrando ${inicio + 1} - ${Math.min(fin, total)} de ${total} registro(s).`;
            }
        }

        if (paginaActualLicencias) {
            paginaActualLicencias.textContent = `${paginaLicencias} / ${totalPaginas}`;
        }

        if (btnPrevLicencias) {
            btnPrevLicencias.disabled = paginaLicencias <= 1;
        }

        if (btnNextLicencias) {
            btnNextLicencias.disabled = paginaLicencias >= totalPaginas;
        }
    }

    inputBuscarLicencias?.addEventListener('input', () => renderLicencias(true));
    filtroEstadoLicencias?.addEventListener('change', () => renderLicencias(true));
    filasPorPaginaLicencias?.addEventListener('change', () => renderLicencias(true));

    btnPrevLicencias?.addEventListener('click', () => {
        if (paginaLicencias > 1) {
            paginaLicencias--;
            renderLicencias();
        }
    });

    btnNextLicencias?.addEventListener('click', () => {
        paginaLicencias++;
        renderLicencias();
    });

    renderLicencias(true);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModalLicencia();
            cerrarDetalleLicencia();
        }
    });
</script>