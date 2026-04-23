<?php

// En cada vista protegida
if (!isset($_SESSION["validarSesion"]) || $_SESSION["validarSesion"] != "ok") {
    header("Location: " . BASE_URL . "/login");
    exit();
}

$titulo_pagina = "Dashboard | Panel de Control RRHH";
$menu_activo = "dashboard";

// Agregamos un ../ extra para retroceder dos niveles
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../../Controlador/CtrDirectorio.php';
require_once __DIR__ . '/../../../Modelo/MdDirectorio.php';

$ctrDirectorio = new CtrDirectorio();
$dashboard = $ctrDirectorio->ctrMostrarDashboard();

$totalColaboradores     = (int)($dashboard['total_colaboradores'] ?? 0);
$validacionesPendientes = (int)($dashboard['validaciones_pendientes'] ?? 0);
$contratosPorVencer     = (int)($dashboard['contratos_por_vencer'] ?? 0);
$modalidades            = $dashboard['modalidades'] ?? [];
$cumpleanos             = $dashboard['cumpleanos'] ?? [];

$totalModalidades = array_sum(array_map(fn($m) => (int)($m['total'] ?? 0), $modalidades));

if (!function_exists('formatearMesCortoDashboard')) {
    function formatearMesCortoDashboard(?string $fecha): string
    {
        if (empty($fecha)) return '--';

        $timestamp = strtotime($fecha);
        if (!$timestamp) return '--';

        $meses = [
            'Jan' => 'Ene',
            'Feb' => 'Feb',
            'Mar' => 'Mar',
            'Apr' => 'Abr',
            'May' => 'May',
            'Jun' => 'Jun',
            'Jul' => 'Jul',
            'Aug' => 'Ago',
            'Sep' => 'Sep',
            'Oct' => 'Oct',
            'Nov' => 'Nov',
            'Dec' => 'Dic',
        ];

        $mes = date('M', $timestamp);
        return $meses[$mes] ?? $mes;
    }
}
?>

<main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">
    <header class="h-20 bg-white shadow-sm flex items-center px-8 justify-between z-10 border-b border-red-50">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">
                Hola, <?= explode(' ', $_SESSION["nombre_completo"])[0] ?> 👋
            </h1>

            <p class="text-sm text-slate-500">
                Aquí tienes el resumen de hoy, <?= date('d/m/Y') ?> | <?= date('H:i') ?>
            </p>
        </div>

        <div class="flex items-center space-x-4">
            <div class="hidden md:flex relative">

            </div>
            <button class="relative p-2 text-slate-400 hover:text-red-800 transition">
                <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-600 rounded-full border-2 border-white"></span>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
            </button>
        </div>
    </header>

    <div class="p-8 flex-1 overflow-y-auto">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:border-red-200 transition-all group">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-[#4c0505] rounded-2xl shadow-lg shadow-red-900/20 group-hover:bg-red-800 transition-colors">
                        <svg class="w-6 h-6 text-red-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <span class="bg-green-100 text-green-700 text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wider">Actualizado</span>
                </div>
                <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest">Total Colaboradores</h3>
                <p class="text-3xl font-black text-slate-800 mt-1"><?= number_format($totalColaboradores) ?></p>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:border-red-200 transition-all group cursor-pointer" onclick="window.location.href='<?= BASE_URL ?>/rrhh/validaciones'">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-orange-600 rounded-2xl shadow-lg shadow-orange-900/20 group-hover:bg-orange-500 transition-colors">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <span class="bg-orange-100 text-orange-700 text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wider">Acción requerida</span>
                </div>
                <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest">Validaciones Pendientes</h3>
                <p class="text-3xl font-black text-slate-800 mt-1"><?= number_format($validacionesPendientes) ?></p>
            </div>

            <div
                class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:border-red-200 transition-all group cursor-pointer"
                onclick="window.location.href='<?= BASE_URL ?>/rrhh/contratos'">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-red-700 rounded-2xl shadow-lg shadow-red-900/20 group-hover:bg-red-600 transition-colors">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <span class="bg-red-100 text-red-700 text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wider">
                        Próximos 30 días
                    </span>
                </div>

                <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest">
                    Contratos por Vencer
                </h3>

                <p class="text-3xl font-black text-slate-800 mt-1">
                    <?= number_format($contratosPorVencer) ?>
                </p>
            </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-bold text-slate-800">Distribución por Modalidad</h2>
                        <button class="text-red-800 text-sm font-bold hover:text-red-600 transition">Ver reporte completo</button>
                    </div>

                    <div class="space-y-6">
                        <?php if (!empty($modalidades)): ?>
                            <?php foreach ($modalidades as $index => $modalidad): ?>
                                <?php
                                $nombre = trim((string)($modalidad['modalidad'] ?? ''));
                                $nombre = $nombre !== '' ? $nombre : 'SIN MODALIDAD';

                                $total = (int)($modalidad['total'] ?? 0);
                                $porcentaje = $totalModalidades > 0 ? round(($total / $totalModalidades) * 100, 2) : 0;

                                if ($index === 0) {
                                    $textoColor = 'text-red-800';
                                    $barraColor = 'bg-[#600505]';
                                } elseif ($index === 1) {
                                    $textoColor = 'text-red-500';
                                    $barraColor = 'bg-red-400';
                                } else {
                                    $textoColor = 'text-slate-600';
                                    $barraColor = 'bg-slate-400';
                                }
                                ?>
                                <div>
                                    <div class="flex justify-between text-sm font-medium mb-2">
                                        <span class="text-slate-700"><?= htmlspecialchars($nombre) ?></span>
                                        <span class="<?= $textoColor ?> font-bold"><?= number_format($total) ?> Colaboradores</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-3">
                                        <div class="<?= $barraColor ?> h-3 rounded-full" style="width: <?= $porcentaje ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-sm text-slate-500">No hay datos de modalidad disponibles.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-800 mb-6 flex items-center">
                        <span class="text-2xl mr-2">🎂</span> Próximos Cumpleaños
                    </h2>

                    <div class="space-y-4">
                        <?php if (!empty($cumpleanos)): ?>
                            <?php foreach ($cumpleanos as $cumple): ?>
                                <?php
                                $fecha  = $cumple['fecha_nacimiento'] ?? null;
                                $mes    = formatearMesCortoDashboard($fecha);
                                $dia    = (!empty($fecha) && strtotime($fecha)) ? date('d', strtotime($fecha)) : '--';
                                $nombre = trim((string)($cumple['nombre'] ?? 'Sin nombre'));
                                $detalle = trim((string)($cumple['detalle'] ?? 'Cumpleaños registrado'));
                                ?>
                                <div class="flex items-center p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <div class="w-12 h-12 bg-slate-200 text-slate-600 rounded-xl flex flex-col items-center justify-center font-bold">
                                        <span class="text-[10px] uppercase"><?= htmlspecialchars($mes) ?></span>
                                        <span class="text-lg leading-none"><?= htmlspecialchars($dia) ?></span>
                                    </div>
                                    <div class="ml-4 min-w-0">
                                        <p class="font-bold text-slate-800 text-sm truncate"><?= htmlspecialchars($nombre) ?></p>
                                        <p class="text-xs text-slate-500 truncate"><?= htmlspecialchars($detalle) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-sm text-slate-500">No hay cumpleaños próximos registrados.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>