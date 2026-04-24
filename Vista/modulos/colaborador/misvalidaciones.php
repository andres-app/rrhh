<?php
//Vista/modulos/colaborador/misvalidaciones.php
require_once __DIR__ . '/../../../Modelo/MdDirectorio.php';

$colabId = (int)($_SESSION['user_id'] ?? 0);
$solicitudes = MdDirectorio::mdlListarSolicitudesPorColaborador($colabId);

$titulo_pagina = "Mis Validaciones | Colaborador";
$menu_activo = "misvalidaciones";

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

function badgeEstado($estado)
{
    return match ($estado) {
        'APROBADO' => 'bg-green-50 text-green-700 border-green-200',
        'RECHAZADO' => 'bg-red-50 text-red-700 border-red-200',
        default => 'bg-amber-50 text-amber-700 border-amber-200',
    };
}
?>

<main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">

    <header class="h-20 bg-white shadow-sm flex items-center px-8 justify-between border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-black text-slate-800">Mis Solicitudes</h1>
            <p class="text-xs text-slate-400 uppercase tracking-widest mt-1">
                Seguimiento de tus cambios enviados
            </p>
        </div>
    </header>

    <div class="p-8 flex-1 overflow-y-auto">

        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">

            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[10px] tracking-widest font-bold">
                    <tr>
                        <th class="p-4">Solicitud</th>
                        <th class="p-4">Fecha envío</th>
                        <th class="p-4">Estado</th>
                        <th class="p-4">Observación</th>
                        <th class="p-4">Sustento</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    <?php if (empty($solicitudes)): ?>
                        <tr>
                            <td colspan="5" class="p-10 text-center text-slate-400 text-sm">
                                No tienes solicitudes registradas.
                            </td>
                        </tr>
                    <?php else: ?>

                        <?php foreach ($solicitudes as $sol): ?>

                            <tr class="hover:bg-slate-50 transition">

                                <!-- Tipo -->
                                <td class="p-4">
                                    <p class="text-sm font-bold text-slate-700">
                                        <?php echo htmlspecialchars($sol['tipo_seccion'] ?? 'Solicitud'); ?>
                                    </p>
                                </td>

                                <!-- Fecha -->
                                <td class="p-4 text-sm text-slate-500">
                                    <?php echo !empty($sol['created_at'])
                                        ? date('d/m/Y H:i', strtotime($sol['created_at']))
                                        : '—'; ?>
                                </td>

                                <!-- Estado -->
                                <td class="p-4">
                                    <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-lg border <?php echo badgeEstado($sol['estado']); ?>">
                                        <?php echo htmlspecialchars($sol['estado']); ?>
                                    </span>
                                </td>

                                <!-- Observación -->
                                <td class="p-4">
                                    <?php if ($sol['estado'] === 'RECHAZADO' && !empty($sol['observacion_rrhh'])): ?>
                                        <p class="text-xs text-red-700 bg-red-50 border border-red-100 rounded-xl p-3">
                                            <?php echo htmlspecialchars($sol['observacion_rrhh']); ?>
                                        </p>
                                    <?php elseif ($sol['estado'] === 'APROBADO'): ?>
                                        <span class="text-xs text-green-600 font-semibold">Aprobado</span>
                                    <?php else: ?>
                                        <span class="text-xs text-amber-600 font-semibold">En revisión</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Sustento -->
                                <td class="p-4">
                                    <?php if (!empty($sol['archivo_sustento'])): ?>
                                        <a href="<?php echo BASE_URL . '/' . htmlspecialchars($sol['archivo_sustento']); ?>"
                                           target="_blank"
                                           class="text-xs font-bold text-red-900 bg-red-50 border border-red-100 px-3 py-2 rounded-xl inline-flex">
                                            Ver archivo
                                        </a>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">—</span>
                                    <?php endif; ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>
            </table>

        </div>

    </div>

</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>