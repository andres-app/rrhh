<?php
declare(strict_types=1);

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "/login");
    exit();
}

$rolSesion = strtolower(trim($_SESSION['user_role'] ?? ''));

// Seguridad adicional en vista
if (!in_array($rolSesion, ['superadmin', 'admin', 'rrhh'], true)) {
    http_response_code(403);
    die('No tienes permiso para acceder a este módulo.');
}

$titulo_pagina = "Contratos por vencer | RRHH";
$menu_activo = "contratos";

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../../Modelo/Conexion.php';

if (!function_exists('formatearFechaContrato')) {
    function formatearFechaContrato(?string $fecha): string
    {
        if (empty($fecha) || $fecha === '0000-00-00') {
            return '—';
        }

        $ts = strtotime($fecha);
        if (!$ts) {
            return '—';
        }

        return date('d/m/Y', $ts);
    }
}

if (!function_exists('diasRestantesContrato')) {
    function diasRestantesContrato(?string $fechaCese): ?int
    {
        if (empty($fechaCese) || $fechaCese === '0000-00-00') {
            return null;
        }

        $hoy = new DateTime(date('Y-m-d'));
        $fin = new DateTime($fechaCese);

        return (int)$hoy->diff($fin)->format('%r%a');
    }
}

try {
    $pdo = Conexion::conectar();

    // Último contrato vigente por colaborador que vence en los próximos 30 días
    $sql = "
        SELECT
            c.id,
            c.colab_id,
            m.nombres_apellidos,
            m.dni,
            c.modalidad,
            c.fecha_ingreso,
            c.fecha_cese,
            DATEDIFF(c.fecha_cese, CURDATE()) AS dias_restantes
        FROM colab_contratos c
        INNER JOIN colab_maestro m
            ON m.id = c.colab_id
        WHERE c.fecha_ingreso IS NOT NULL
          AND c.fecha_ingreso <= CURDATE()
          AND c.fecha_cese IS NOT NULL
          AND c.fecha_cese >= CURDATE()
          AND c.fecha_cese <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
          AND NOT EXISTS (
              SELECT 1
              FROM colab_contratos c2
              WHERE c2.colab_id = c.colab_id
                AND c2.fecha_ingreso IS NOT NULL
                AND c2.fecha_ingreso <= CURDATE()
                AND (c2.fecha_cese IS NULL OR c2.fecha_cese >= CURDATE())
                AND (
                    COALESCE(c2.fecha_cese, '9999-12-31') > COALESCE(c.fecha_cese, '9999-12-31')
                    OR (
                        COALESCE(c2.fecha_cese, '9999-12-31') = COALESCE(c.fecha_cese, '9999-12-31')
                        AND c2.fecha_ingreso > c.fecha_ingreso
                    )
                    OR (
                        COALESCE(c2.fecha_cese, '9999-12-31') = COALESCE(c.fecha_cese, '9999-12-31')
                        AND c2.fecha_ingreso = c.fecha_ingreso
                        AND c2.id > c.id
                    )
                )
          )
        ORDER BY c.fecha_cese ASC, m.nombres_apellidos ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $contratos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $total = count($contratos);
    $vence7 = 0;
    $vence15 = 0;
    $vence30 = 0;

    foreach ($contratos as $row) {
        $dias = isset($row['dias_restantes']) ? (int)$row['dias_restantes'] : null;

        if ($dias !== null && $dias <= 7) {
            $vence7++;
        }
        if ($dias !== null && $dias <= 15) {
            $vence15++;
        }
        if ($dias !== null && $dias <= 30) {
            $vence30++;
        }
    }
} catch (Throwable $e) {
    $contratos = [];
    $total = 0;
    $vence7 = 0;
    $vence15 = 0;
    $vence30 = 0;
    $errorVista = $e->getMessage();
}
?>

<main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">
    <header class="h-20 bg-white shadow-sm flex items-center px-8 justify-between z-10 border-b border-red-50">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Contratos por vencer</h1>
            <p class="text-sm text-slate-500">
                Último contrato vigente por colaborador con vencimiento dentro de los próximos 30 días
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="<?= BASE_URL ?>/rrhh/dashboard"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl border border-slate-200 bg-white text-slate-700 text-sm font-semibold hover:border-red-200 hover:text-red-800 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Volver
            </a>
        </div>
    </header>

    <section class="flex-1 overflow-y-auto p-8 space-y-8">
        <?php if (!empty($errorVista)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl">
                Error al cargar contratos: <?= htmlspecialchars($errorVista) ?>
            </div>
        <?php endif; ?>

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Total</p>
                <p class="text-3xl font-black text-slate-800"><?= number_format($total) ?></p>
                <p class="text-sm text-slate-500 mt-2">Contratos por vencer</p>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-red-100 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Urgente</p>
                <p class="text-3xl font-black text-red-700"><?= number_format($vence7) ?></p>
                <p class="text-sm text-slate-500 mt-2">Vencen en 7 días o menos</p>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-amber-100 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Seguimiento</p>
                <p class="text-3xl font-black text-amber-600"><?= number_format($vence15) ?></p>
                <p class="text-sm text-slate-500 mt-2">Vencen en 15 días o menos</p>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Ventana total</p>
                <p class="text-3xl font-black text-slate-800"><?= number_format($vence30) ?></p>
                <p class="text-sm text-slate-500 mt-2">Vencen en 30 días o menos</p>
            </div>
        </div>

        <!-- Tabla -->
        <div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Detalle de contratos</h2>
                    <p class="text-sm text-slate-500">Se muestra solo el último contrato vigente por colaborador</p>
                </div>

                <div class="relative w-full lg:w-80">
                    <input
                        type="text"
                        id="filtroContratos"
                        placeholder="Buscar por nombre, DNI o modalidad..."
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-300"
                    >
                </div>
            </div>

            <?php if (empty($contratos)): ?>
                <div class="p-10 text-center">
                    <div class="mx-auto w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-700">No hay contratos por vencer</h3>
                    <p class="text-sm text-slate-500 mt-1">No se encontraron registros dentro de los próximos 30 días.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm" id="tablaContratos">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-slate-500 uppercase tracking-wider text-[11px]">
                                <th class="px-6 py-4 font-bold">Colaborador</th>
                                <th class="px-6 py-4 font-bold">DNI</th>
                                <th class="px-6 py-4 font-bold">Modalidad</th>
                                <th class="px-6 py-4 font-bold">Fecha ingreso</th>
                                <th class="px-6 py-4 font-bold">Fecha cese</th>
                                <th class="px-6 py-4 font-bold">Días restantes</th>
                                <th class="px-6 py-4 font-bold text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($contratos as $row): ?>
                                <?php
                                    $idColab = (int)($row['colab_id'] ?? 0);
                                    $nombre  = trim((string)($row['nombres_apellidos'] ?? ''));
                                    $dni     = trim((string)($row['dni'] ?? ''));
                                    $modalidad = trim((string)($row['modalidad'] ?? ''));
                                    $dias    = isset($row['dias_restantes']) ? (int)$row['dias_restantes'] : null;

                                    $badgeClase = 'bg-slate-100 text-slate-700';
                                    if ($dias !== null && $dias <= 7) {
                                        $badgeClase = 'bg-red-100 text-red-700';
                                    } elseif ($dias !== null && $dias <= 15) {
                                        $badgeClase = 'bg-amber-100 text-amber-700';
                                    } elseif ($dias !== null && $dias <= 30) {
                                        $badgeClase = 'bg-blue-100 text-blue-700';
                                    }
                                ?>
                                <tr class="hover:bg-slate-50/80 transition fila-contrato">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-800"><?= htmlspecialchars($nombre) ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($dni !== '' ? $dni : '—') ?></td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?= $modalidad !== '' ? 'bg-slate-100 text-slate-700' : 'bg-slate-100 text-slate-400' ?>">
                                            <?= htmlspecialchars($modalidad !== '' ? $modalidad : 'SIN MODALIDAD') ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars(formatearFechaContrato($row['fecha_ingreso'] ?? null)) ?></td>
                                    <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars(formatearFechaContrato($row['fecha_cese'] ?? null)) ?></td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?= $badgeClase ?>">
                                            <?= $dias !== null ? $dias . ' día(s)' : '—' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <a href="<?= BASE_URL ?>/rrhh/perfil/<?= $idColab ?>"
                                           class="inline-flex items-center justify-center px-4 py-2 rounded-2xl bg-red-900 text-white text-xs font-bold hover:bg-red-800 transition-all">
                                            Ver perfil
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('filtroContratos');
    const filas = document.querySelectorAll('.fila-contrato');

    if (!input) return;

    input.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();

        filas.forEach(fila => {
            const texto = fila.innerText.toLowerCase();
            fila.style.display = texto.includes(q) ? '' : 'none';
        });
    });
});
</script>