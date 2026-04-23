<?php
require_once __DIR__ . '/../../../Modelo/MdDirectorio.php';

$pendientes = MdDirectorio::mdlListarSolicitudesCambio('PENDIENTE');
$aprobadas  = MdDirectorio::mdlListarSolicitudesCambio('APROBADO');
$rechazadas = MdDirectorio::mdlListarSolicitudesCambio('RECHAZADO');

$titulo_pagina = "Validaciones | RRHH";
$menu_activo = "validaciones";

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

function badgeEstadoSolicitud($estado)
{
    return match ($estado) {
        'APROBADO' => 'bg-green-50 text-green-700 border-green-200',
        'RECHAZADO' => 'bg-red-50 text-red-700 border-red-200',
        default => 'bg-amber-50 text-amber-700 border-amber-200',
    };
}

function textoTipoSolicitud($tipo)
{
    return match ($tipo) {
        'perfil_completo' => 'Actualización de perfil',
        default => $tipo ?: 'Solicitud',
    };
}
?>

<main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">
    <header class="h-20 bg-white shadow-sm flex items-center px-8 justify-between z-10 border-b border-red-50">
        <div class="flex items-center">
            <div class="p-2 bg-orange-100 rounded-lg mr-4">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-800">Bandeja de Validaciones</h1>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">
                    Revisión de solicitudes de colaboradores
                </p>
            </div>
        </div>

        <div class="flex gap-2">
            <span class="bg-amber-50 text-amber-700 border border-amber-200 text-xs font-bold px-3 py-1 rounded-full">
                <?php echo count($pendientes); ?> Pendientes
            </span>
            <span class="bg-green-50 text-green-700 border border-green-200 text-xs font-bold px-3 py-1 rounded-full">
                <?php echo count($aprobadas); ?> Aprobadas
            </span>
            <span class="bg-red-50 text-red-700 border border-red-200 text-xs font-bold px-3 py-1 rounded-full">
                <?php echo count($rechazadas); ?> Rechazadas
            </span>
        </div>
    </header>

    <div class="p-8 flex-1 overflow-y-auto space-y-8">

        <section class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-black text-slate-800">Solicitudes pendientes</h2>
                    <p class="text-xs text-slate-400 mt-1">Aprueba o rechaza las solicitudes enviadas por colaboradores.</p>
                </div>
            </div>

            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[10px] tracking-widest font-bold">
                    <tr>
                        <th class="p-4">Colaborador</th>
                        <th class="p-4">Solicitud</th>
                        <th class="p-4">Fecha</th>
                        <th class="p-4">Sustento</th>
                        <th class="p-4 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($pendientes)): ?>
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-400 text-sm">
                                No hay solicitudes pendientes.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pendientes as $sol): ?>
                            <?php
                            $datosNuevos = json_decode((string)($sol['datos_json'] ?? ''), true);
                            $datosAntes  = json_decode((string)($sol['datos_anteriores_json'] ?? ''), true);
                            $resumen = [];

                            foreach (['direccion_residencia' => 'Dirección', 'distrito' => 'Distrito', 'celular' => 'Celular', 'correo_personal' => 'Correo personal', 'estado_civil' => 'Estado civil'] as $campo => $label) {
                                $antes = trim((string)($datosAntes[$campo] ?? ''));
                                $despues = trim((string)($datosNuevos[$campo] ?? ''));
                                if ($antes !== $despues) {
                                    $resumen[] = $label;
                                }
                            }

                            if (!empty($datosNuevos['hijos'])) $resumen[] = 'Familia';
                            if (!empty($datosNuevos['formacion'])) $resumen[] = 'Formación';
                            if (!empty($datosNuevos['experiencia'])) $resumen[] = 'Experiencia';
                            if (!empty($datosNuevos['pension'])) $resumen[] = 'Pensión';
                            if (!empty($datosNuevos['bancario'])) $resumen[] = 'Bancario';
                            if (!empty($datosNuevos['idiomas'])) $resumen[] = 'Idiomas';
                            ?>
                            <tr class="hover:bg-red-50/30 transition">
                                <td class="p-4">
                                    <div class="flex items-center">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($sol['nombres_apellidos'] ?? 'Colaborador'); ?>&background=880808&color=fff&size=32"
                                            class="w-9 h-9 rounded-xl mr-3 shadow-sm">
                                        <div>
                                            <p class="font-black text-slate-800"><?php echo htmlspecialchars($sol['nombres_apellidos'] ?? '—'); ?></p>
                                            <p class="text-[11px] text-slate-400">DNI: <?php echo htmlspecialchars($sol['dni'] ?? '—'); ?></p>
                                        </div>
                                    </div>
                                </td>

                                <td class="p-4">
                                    <p class="text-sm font-bold text-slate-700">
                                        <?php echo htmlspecialchars(textoTipoSolicitud($sol['tipo_seccion'] ?? '')); ?>
                                    </p>
                                    <p class="text-[11px] text-slate-400 mt-1">
                                        Cambios: <?php echo htmlspecialchars(!empty($resumen) ? implode(', ', array_unique($resumen)) : 'Perfil'); ?>
                                    </p>
                                </td>

                                <td class="p-4 text-sm text-slate-500">
                                    <?php echo !empty($sol['created_at']) ? date('d/m/Y H:i', strtotime($sol['created_at'])) : '—'; ?>
                                </td>

                                <td class="p-4">
                                    <?php if (!empty($sol['archivo_sustento'])): ?>
                                        <a href="<?php echo BASE_URL . '/' . htmlspecialchars($sol['archivo_sustento']); ?>" target="_blank"
                                            class="text-xs font-bold text-red-900 bg-red-50 border border-red-100 px-3 py-2 rounded-xl inline-flex">
                                            Ver sustento
                                        </a>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">Sin archivo</span>
                                    <?php endif; ?>
                                </td>

                                <td class="p-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button onclick="aprobarSolicitud(<?php echo (int)$sol['id']; ?>)"
                                            class="bg-green-600 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-green-700 transition">
                                            Aprobar
                                        </button>

                                        <button onclick="rechazarSolicitud(<?php echo (int)$sol['id']; ?>)"
                                            class="bg-red-900 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-[#4c0505] transition">
                                            Rechazar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-100">
                    <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Aprobadas recientes</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    <?php foreach (array_slice($aprobadas, 0, 5) as $sol): ?>
                        <div class="p-4 flex justify-between gap-4">
                            <div>
                                <p class="text-sm font-bold text-slate-700"><?php echo htmlspecialchars($sol['nombres_apellidos'] ?? '—'); ?></p>
                                <p class="text-xs text-slate-400"><?php echo !empty($sol['fecha_validacion']) ? date('d/m/Y H:i', strtotime($sol['fecha_validacion'])) : '—'; ?></p>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-lg border <?php echo badgeEstadoSolicitud('APROBADO'); ?>">
                                APROBADO
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($aprobadas)): ?>
                        <div class="p-6 text-center text-sm text-slate-400">Sin aprobadas.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-100">
                    <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Rechazadas recientes</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    <?php foreach (array_slice($rechazadas, 0, 5) as $sol): ?>
                        <div class="p-4">
                            <div class="flex justify-between gap-4">
                                <div>
                                    <p class="text-sm font-bold text-slate-700"><?php echo htmlspecialchars($sol['nombres_apellidos'] ?? '—'); ?></p>
                                    <p class="text-xs text-slate-400"><?php echo !empty($sol['fecha_validacion']) ? date('d/m/Y H:i', strtotime($sol['fecha_validacion'])) : '—'; ?></p>
                                </div>
                                <span class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-lg border <?php echo badgeEstadoSolicitud('RECHAZADO'); ?>">
                                    RECHAZADO
                                </span>
                            </div>
                            <?php if (!empty($sol['observacion_rrhh'])): ?>
                                <p class="mt-2 text-xs text-red-700 bg-red-50 border border-red-100 rounded-xl p-3">
                                    <?php echo htmlspecialchars($sol['observacion_rrhh']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($rechazadas)): ?>
                        <div class="p-6 text-center text-sm text-slate-400">Sin rechazadas.</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</main>

<script>
function aprobarSolicitud(id) {
    if (!confirm('¿Aprobar esta solicitud y aplicar los cambios al perfil?')) return;

    fetch('<?php echo BASE_URL; ?>/rrhh/validaciones/aprobar/' + id, {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(r => r.json())
    .then(res => {
        alert(res.mensaje || 'Solicitud procesada');
        if (res.success) location.reload();
    })
    .catch(() => alert('No se pudo procesar la aprobación'));
}

function rechazarSolicitud(id) {
    const motivo = prompt('Indica el motivo del rechazo:');
    if (motivo === null) return;

    if (!motivo.trim()) {
        alert('Debes indicar un motivo.');
        return;
    }

    fetch('<?php echo BASE_URL; ?>/rrhh/validaciones/rechazar/' + id, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({motivo: motivo.trim()})
    })
    .then(r => r.json())
    .then(res => {
        alert(res.mensaje || 'Solicitud procesada');
        if (res.success) location.reload();
    })
    .catch(() => alert('No se pudo procesar el rechazo'));
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>