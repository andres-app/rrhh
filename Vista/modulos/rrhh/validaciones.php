<?php
// Vista/modulos/rrhh/validaciones.php
require_once __DIR__ . '/../../../Modelo/MdDirectorio.php';

$pendientes = MdDirectorio::mdlListarSolicitudesCambio('PENDIENTE');
$aprobadas  = MdDirectorio::mdlListarSolicitudesCambio('APROBADO');
$rechazadas = MdDirectorio::mdlListarSolicitudesCambio('RECHAZADO');

$solicitudes = array_merge($pendientes, $aprobadas, $rechazadas);

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

function valorLimpio($valor)
{
    if ($valor === null) return '';

    if (is_bool($valor)) {
        return $valor ? '1' : '0';
    }

    return trim((string)$valor);
}

function normalizarFila(array $fila, array $campos): array
{
    $limpio = [];

    foreach ($campos as $campo) {
        $limpio[$campo] = valorLimpio($fila[$campo] ?? '');
    }

    return $limpio;
}

function normalizarLista(array $lista, array $campos): array
{
    $resultado = [];

    foreach ($lista as $fila) {
        if (!is_array($fila)) continue;

        $item = normalizarFila($fila, $campos);

        $vacio = true;
        foreach ($item as $valor) {
            if ($valor !== '') {
                $vacio = false;
                break;
            }
        }

        if (!$vacio) {
            $resultado[] = $item;
        }
    }

    usort($resultado, function ($a, $b) {
        return json_encode($a, JSON_UNESCAPED_UNICODE) <=> json_encode($b, JSON_UNESCAPED_UNICODE);
    });

    return $resultado;
}

function bloqueCambio(array $antes, array $despues, string $campo, array $camposComparar): bool
{
    $listaAntes = $antes[$campo] ?? [];
    $listaDespues = $despues[$campo] ?? [];

    if (!is_array($listaAntes)) $listaAntes = [];
    if (!is_array($listaDespues)) $listaDespues = [];

    return normalizarLista($listaAntes, $camposComparar) !== normalizarLista($listaDespues, $camposComparar);
}

function resumenSolicitud($sol)
{
    $datosNuevos = json_decode((string)($sol['datos_json'] ?? ''), true) ?: [];
    $datosAntes  = json_decode((string)($sol['datos_anteriores_json'] ?? ''), true) ?: [];

    $resumen = [];

    $camposSimples = [
        'fecha_nacimiento'      => 'Fecha nacimiento',
        'lugar_nacimiento'     => 'Lugar nacimiento',
        'estado_civil'         => 'Estado civil',
        'grupo_sanguineo'      => 'Grupo sanguíneo',
        'talla'                => 'Talla',
        'direccion_residencia' => 'Dirección',
        'distrito'             => 'Distrito',
        'celular'              => 'Celular',
        'correo_personal'      => 'Correo personal',
        'conyuge'              => 'Cónyuge',
        'onomastico_conyuge'   => 'Onomástico cónyuge',
        'dni_conyuge'          => 'DNI cónyuge',
    ];

    foreach ($camposSimples as $campo => $label) {
        $antes = valorLimpio($datosAntes[$campo] ?? '');
        $despues = valorLimpio($datosNuevos[$campo] ?? '');

        if ($antes !== $despues) {
            $resumen[] = $label;
        }
    }

    if (bloqueCambio($datosAntes, $datosNuevos, 'contratos', [
        'fecha_ingreso',
        'fecha_cese',
        'modalidad'
    ])) {
        $resumen[] = 'Contratos';
    }

    if (bloqueCambio($datosAntes, $datosNuevos, 'formacion', [
        'tipo_grado',
        'descripcion_carrera',
        'institucion',
        'anio_realizacion',
        'horas_lectivas',
        'especialidad',
        'grado_alcanzado'
    ])) {
        $resumen[] = 'Formación';
    }

    if (bloqueCambio($datosAntes, $datosNuevos, 'experiencia', [
        'empresa_entidad',
        'unidad_organica_area',
        'cargo_puesto',
        'fecha_inicio',
        'fecha_fin',
        'actualmente_trabaja',
        'funciones_principales'
    ])) {
        $resumen[] = 'Experiencia';
    }

    /*
    IMPORTANTE:
    En BD viene como familia, pero desde el formulario viene como hijos.
    Por eso se comparan los hijos de familia contra hijos del JSON nuevo.
    */
    $familiaAntes = $datosAntes['familia'] ?? [];
    $hijosAntes = [];

    if (is_array($familiaAntes)) {
        foreach ($familiaAntes as $familiar) {
            if (!is_array($familiar)) continue;

            $parentesco = strtoupper(valorLimpio($familiar['parentesco'] ?? ''));

            if (in_array($parentesco, ['HIJO', 'HIJA'], true)) {
                $hijosAntes[] = [
                    'nombre' => $familiar['nombre_completo'] ?? '',
                    'parentesco' => $familiar['parentesco'] ?? '',
                    'fecha_nacimiento' => $familiar['fecha_nacimiento'] ?? '',
                    'dni' => $familiar['dni_familiar'] ?? '',
                ];
            }
        }
    }

    $datosAntesHijos = ['hijos' => $hijosAntes];

    if (bloqueCambio($datosAntesHijos, $datosNuevos, 'hijos', [
        'nombre',
        'parentesco',
        'fecha_nacimiento',
        'dni'
    ])) {
        $resumen[] = 'Familia';
    }

    $pensionAntes = is_array($datosAntes['pension'] ?? null) ? $datosAntes['pension'] : [];
    $pensionNueva = is_array($datosNuevos['pension'] ?? null) ? $datosNuevos['pension'] : [];

    if (normalizarFila($pensionAntes, [
        'sistema_pension',
        'afp',
        'cuspp',
        'tipo_comision',
        'fecha_inscripcion',
        'sin_afp_afiliarme'
    ]) !== normalizarFila($pensionNueva, [
        'sistema_pension',
        'afp',
        'cuspp',
        'tipo_comision',
        'fecha_inscripcion',
        'sin_afp_afiliarme'
    ])) {
        $resumen[] = 'Pensión';
    }

    $bancarioAntes = is_array($datosAntes['bancario'] ?? null) ? $datosAntes['bancario'] : [];
    $bancarioNuevo = is_array($datosNuevos['bancario'] ?? null) ? $datosNuevos['bancario'] : [];

    if (normalizarFila($bancarioAntes, [
        'banco_haberes',
        'numero_cuenta',
        'numero_cuenta_cci'
    ]) !== normalizarFila($bancarioNuevo, [
        'banco_haberes',
        'numero_cuenta',
        'numero_cuenta_cci'
    ])) {
        $resumen[] = 'Bancario';
    }

    if (bloqueCambio($datosAntes, $datosNuevos, 'idiomas', [
        'idioma',
        'nivel'
    ])) {
        $resumen[] = 'Idiomas';
    }

    return !empty($resumen)
        ? implode(', ', array_unique($resumen))
        : 'Sin cambios detectados';
}
?>

<main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">

    <header class="h-20 bg-white/90 backdrop-blur-xl flex items-center px-8 justify-between z-10 border-b border-slate-200">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-red-900 text-white flex items-center justify-center shadow-lg shadow-red-900/20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-800">Bandeja de Validaciones</h1>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">
                    Gestión de solicitudes de actualización de perfil
                </p>
            </div>
        </div>
    </header>

    <div class="p-8 flex-1 overflow-hidden flex flex-col gap-6">

        <section class="grid grid-cols-1 md:grid-cols-4 gap-4 shrink-0">

            <div class="bg-white rounded-3xl border border-slate-200 p-5 shadow-sm">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Total</p>
                <h2 class="text-3xl font-black text-slate-800 mt-2"><?php echo count($solicitudes); ?></h2>
            </div>

            <div class="bg-amber-50 rounded-3xl border border-amber-200 p-5 shadow-sm">
                <p class="text-xs font-bold text-amber-700 uppercase tracking-widest">Pendientes</p>
                <h2 class="text-3xl font-black text-amber-900 mt-2"><?php echo count($pendientes); ?></h2>
            </div>

            <div class="bg-green-50 rounded-3xl border border-green-200 p-5 shadow-sm">
                <p class="text-xs font-bold text-green-700 uppercase tracking-widest">Aprobadas</p>
                <h2 class="text-3xl font-black text-green-900 mt-2"><?php echo count($aprobadas); ?></h2>
            </div>

            <div class="bg-red-50 rounded-3xl border border-red-200 p-5 shadow-sm">
                <p class="text-xs font-bold text-red-700 uppercase tracking-widest">Rechazadas</p>
                <h2 class="text-3xl font-black text-red-900 mt-2"><?php echo count($rechazadas); ?></h2>
            </div>

        </section>

        <section class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden flex-1 flex flex-col">

            <div class="p-5 border-b border-slate-100 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4 shrink-0">

                <div>
                    <h2 class="text-lg font-black text-slate-800">Solicitudes registradas</h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Filtra, revisa y atiende solicitudes sin una lista infinita.
                    </p>
                </div>

                <div class="flex flex-col md:flex-row gap-3">

                    <div class="relative">
                        <input type="text" id="buscarSolicitud"
                            placeholder="Buscar colaborador, DNI o cambio..."
                            class="w-full md:w-80 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-red-900/20 focus:border-red-900">
                    </div>

                    <div class="flex gap-2">
                        <button onclick="filtrarEstado('TODOS', this)"
                            class="filtro-estado bg-red-900 text-white px-4 py-3 rounded-2xl text-xs font-black uppercase tracking-widest">
                            Todos
                        </button>
                        <button onclick="filtrarEstado('PENDIENTE', this)"
                            class="filtro-estado bg-slate-100 text-slate-600 px-4 py-3 rounded-2xl text-xs font-black uppercase tracking-widest">
                            Pendientes
                        </button>
                        <button onclick="filtrarEstado('APROBADO', this)"
                            class="filtro-estado bg-slate-100 text-slate-600 px-4 py-3 rounded-2xl text-xs font-black uppercase tracking-widest">
                            Aprobadas
                        </button>
                        <button onclick="filtrarEstado('RECHAZADO', this)"
                            class="filtro-estado bg-slate-100 text-slate-600 px-4 py-3 rounded-2xl text-xs font-black uppercase tracking-widest">
                            Rechazadas
                        </button>
                    </div>

                </div>
            </div>

            <div class="overflow-auto flex-1">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[10px] tracking-widest font-black sticky top-0 z-10">
                        <tr>
                            <th class="p-4">Colaborador</th>
                            <th class="p-4">Solicitud</th>
                            <th class="p-4">Estado</th>
                            <th class="p-4">Fecha</th>
                            <th class="p-4">Sustento</th>
                            <th class="p-4 text-center">Acción</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100" id="tablaSolicitudes">

                        <?php if (empty($solicitudes)): ?>
                            <tr>
                                <td colspan="6" class="p-10 text-center text-slate-400 text-sm">
                                    No hay solicitudes registradas.
                                </td>
                            </tr>
                        <?php else: ?>

                            <?php foreach ($solicitudes as $sol): ?>
                                <?php
                                $estado = $sol['estado'] ?? 'PENDIENTE';
                                $resumen = resumenSolicitud($sol);
                                $busqueda = strtolower(
                                    ($sol['nombres_apellidos'] ?? '') . ' ' .
                                        ($sol['dni'] ?? '') . ' ' .
                                        textoTipoSolicitud($sol['tipo_seccion'] ?? '') . ' ' .
                                        $resumen . ' ' .
                                        $estado
                                );
                                ?>

                                <tr class="fila-solicitud hover:bg-slate-50 transition"
                                    data-estado="<?php echo htmlspecialchars($estado); ?>"
                                    data-busqueda="<?php echo htmlspecialchars($busqueda); ?>">

                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($sol['nombres_apellidos'] ?? 'Colaborador'); ?>&background=7f1d1d&color=fff&size=64"
                                                class="w-10 h-10 rounded-2xl shadow-sm">

                                            <div>
                                                <p class="font-black text-slate-800 leading-tight">
                                                    <?php echo htmlspecialchars($sol['nombres_apellidos'] ?? '—'); ?>
                                                </p>
                                                <p class="text-[11px] text-slate-400 mt-1">
                                                    DNI: <?php echo htmlspecialchars($sol['dni'] ?? '—'); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="p-4">
                                        <p class="font-bold text-slate-700">
                                            <?php echo htmlspecialchars(textoTipoSolicitud($sol['tipo_seccion'] ?? '')); ?>
                                        </p>
                                        <p class="text-[11px] text-slate-400 mt-1 max-w-md truncate">
                                            Cambios: <?php echo htmlspecialchars($resumen); ?>
                                        </p>
                                    </td>

                                    <td class="p-4">
                                        <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1.5 rounded-xl border <?php echo badgeEstadoSolicitud($estado); ?>">
                                            <?php echo htmlspecialchars($estado); ?>
                                        </span>
                                    </td>

                                    <td class="p-4 text-slate-500 whitespace-nowrap">
                                        <?php echo !empty($sol['created_at']) ? date('d/m/Y H:i', strtotime($sol['created_at'])) : '—'; ?>
                                    </td>

                                    <td class="p-4">
                                        <?php if (!empty($sol['archivo_sustento'])): ?>
                                            <a href="<?php echo BASE_URL . '/' . htmlspecialchars($sol['archivo_sustento']); ?>" target="_blank"
                                                class="text-xs font-black text-red-900 bg-red-50 border border-red-100 px-3 py-2 rounded-xl inline-flex hover:bg-red-100 transition">
                                                Ver archivo
                                            </a>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400">Sin archivo</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-4 text-center">
                                        <?php if ($estado === 'PENDIENTE'): ?>
                                            <div class="flex justify-center gap-2">
                                                <button onclick="aprobarSolicitud(<?php echo (int)$sol['id']; ?>)"
                                                    class="bg-green-600 text-white px-4 py-2 rounded-xl text-xs font-black hover:bg-green-700 transition">
                                                    Aprobar
                                                </button>

                                                <button onclick="rechazarSolicitud(<?php echo (int)$sol['id']; ?>)"
                                                    class="bg-red-900 text-white px-4 py-2 rounded-xl text-xs font-black hover:bg-[#4c0505] transition">
                                                    Rechazar
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400 font-bold">Atendida</span>
                                        <?php endif; ?>
                                    </td>

                                </tr>
                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>
                </table>
            </div>

        </section>

    </div>
</main>

<script>
    let estadoActual = 'TODOS';

    function filtrarEstado(estado, boton) {
        estadoActual = estado;

        document.querySelectorAll('.filtro-estado').forEach(btn => {
            btn.className = 'filtro-estado bg-slate-100 text-slate-600 px-4 py-3 rounded-2xl text-xs font-black uppercase tracking-widest';
        });

        boton.className = 'filtro-estado bg-red-900 text-white px-4 py-3 rounded-2xl text-xs font-black uppercase tracking-widest';

        aplicarFiltros();
    }

    document.getElementById('buscarSolicitud').addEventListener('input', aplicarFiltros);

    function aplicarFiltros() {
        const texto = document.getElementById('buscarSolicitud').value.toLowerCase().trim();

        document.querySelectorAll('.fila-solicitud').forEach(fila => {
            const estado = fila.dataset.estado;
            const busqueda = fila.dataset.busqueda || '';

            const coincideEstado = estadoActual === 'TODOS' || estado === estadoActual;
            const coincideTexto = !texto || busqueda.includes(texto);

            fila.style.display = coincideEstado && coincideTexto ? '' : 'none';
        });
    }

    function aprobarSolicitud(id) {
        Swal.fire({
            title: '¿Aprobar solicitud?',
            text: 'Se aplicarán los cambios al perfil del colaborador.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, aprobar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#166534',
            cancelButtonColor: '#7f1d1d',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-3xl',
                confirmButton: 'rounded-xl px-5 py-2.5 font-bold',
                cancelButton: 'rounded-xl px-5 py-2.5 font-bold'
            }
        }).then((result) => {
            if (!result.isConfirmed) return;

            fetch('<?php echo BASE_URL; ?>/rrhh/validaciones/aprobar/' + id, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => r.json())
                .then(res => {
                    Swal.fire({
                        title: res.success ? 'Solicitud aprobada' : 'No se pudo aprobar',
                        text: res.mensaje || 'Solicitud procesada.',
                        icon: res.success ? 'success' : 'error',
                        confirmButtonColor: '#7f1d1d',
                        customClass: {
                            popup: 'rounded-3xl',
                            confirmButton: 'rounded-xl px-5 py-2.5 font-bold'
                        }
                    }).then(() => {
                        if (res.success) location.reload();
                    });
                })
                .catch(() => {
                    Swal.fire({
                        title: 'Error',
                        text: 'No se pudo procesar la aprobación.',
                        icon: 'error',
                        confirmButtonColor: '#7f1d1d'
                    });
                });
        });
    }

    function rechazarSolicitud(id) {
        Swal.fire({
            title: 'Rechazar solicitud',
            text: 'Indica el motivo del rechazo.',
            input: 'textarea',
            inputPlaceholder: 'Escribe el motivo...',
            inputAttributes: {
                maxlength: 500
            },
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Rechazar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#7f1d1d',
            cancelButtonColor: '#64748b',
            reverseButtons: true,
            inputValidator: (value) => {
                if (!value || !value.trim()) {
                    return 'Debes ingresar el motivo del rechazo.';
                }
            },
            customClass: {
                popup: 'rounded-3xl',
                input: 'rounded-2xl border-slate-200',
                confirmButton: 'rounded-xl px-5 py-2.5 font-bold',
                cancelButton: 'rounded-xl px-5 py-2.5 font-bold'
            }
        }).then((result) => {
            if (!result.isConfirmed) return;

            fetch('<?php echo BASE_URL; ?>/rrhh/validaciones/rechazar/' + id, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        motivo: result.value.trim()
                    })
                })
                .then(r => r.json())
                .then(res => {
                    Swal.fire({
                        title: res.success ? 'Solicitud rechazada' : 'No se pudo rechazar',
                        text: res.mensaje || 'Solicitud procesada.',
                        icon: res.success ? 'success' : 'error',
                        confirmButtonColor: '#7f1d1d',
                        customClass: {
                            popup: 'rounded-3xl',
                            confirmButton: 'rounded-xl px-5 py-2.5 font-bold'
                        }
                    }).then(() => {
                        if (res.success) location.reload();
                    });
                })
                .catch(() => {
                    Swal.fire({
                        title: 'Error',
                        text: 'No se pudo procesar el rechazo.',
                        icon: 'error',
                        confirmButtonColor: '#7f1d1d'
                    });
                });
        });
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>