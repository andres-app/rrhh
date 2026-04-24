<?php
require_once ROOT_PATH . 'Modelo/MdDirectorio.php';

$colabId = (int)($_SESSION['colab_id'] ?? $_SESSION['user_id'] ?? 0);
$solicitudes = MdDirectorio::mdlListarSolicitudesPorColaborador($colabId);

$titulo_pagina = "Mis Validaciones - RRHH";
$menu_activo = "misvalidaciones";

require_once ROOT_PATH . 'Vista/includes/header.php';
require_once ROOT_PATH . 'Vista/includes/sidebar.php';

function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function estadoBadge($estado): string
{
    return match ($estado) {
        'APROBADO' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'RECHAZADO' => 'bg-red-50 text-red-700 border-red-100',
        default => 'bg-amber-50 text-amber-700 border-amber-100',
    };
}

function estadoIcono($estado): string
{
    return match ($estado) {
        'APROBADO' => '✓',
        'RECHAZADO' => '×',
        default => '•',
    };
}

function labelCampo($campo): string
{
    $labels = [
        'hijos' => 'Hijos',
        'conyuge' => 'Cónyuge',
        'formacion' => 'Formación académica',
        'experiencia' => 'Experiencia laboral',
        'contratos' => 'Contratos',
        'pension' => 'Sistema de pensiones',
        'bancario' => 'Datos bancarios',
        'nombres_apellidos' => 'Nombres y apellidos',
        'dni' => 'DNI',
        'celular' => 'Celular',
        'correo_personal' => 'Correo personal',
        'direccion_residencia' => 'Dirección',
        'distrito' => 'Distrito',
        'sueldo' => 'Sueldo',
        'correo_institucional' => 'Correo institucional',
        'situacion' => 'Situación',
        'puesto_cas' => 'Puesto CAS',
        'modalidad_contrato' => 'Modalidad de contrato',
        'mod_contrato' => 'Modalidad de contrato',
    ];

    return $labels[$campo] ?? ucwords(str_replace('_', ' ', $campo));
}

function labelSubCampo($campo): string
{
    $labels = [
        'nombre' => 'Nombre',
        'nombre_completo' => 'Nombre completo',
        'parentesco' => 'Parentesco',
        'fecha_nacimiento' => 'Fecha nacimiento',
        'dni' => 'DNI',
        'dni_familiar' => 'DNI',
        'tipo_grado' => 'Tipo / grado',
        'descripcion_carrera' => 'Descripción',
        'institucion' => 'Institución',
        'empresa_entidad' => 'Entidad',
        'unidad_organica_area' => 'Área',
        'cargo_puesto' => 'Cargo',
        'fecha_inicio' => 'Inicio',
        'fecha_fin' => 'Fin',
        'funciones_principales' => 'Funciones',
        'sistema_pension' => 'Sistema',
        'afp' => 'AFP',
        'cuspp' => 'CUSPP',
        'tipo_comision' => 'Comisión',
        'banco_haberes' => 'Banco',
        'numero_cuenta' => 'Cuenta',
        'numero_cuenta_cci' => 'CCI',
    ];

    return $labels[$campo] ?? ucwords(str_replace('_', ' ', $campo));
}

function valorPlano($valor): string
{
    if ($valor === null || $valor === '' || $valor === []) {
        return 'Sin registro';
    }

    if (!is_array($valor)) {
        return (string)$valor;
    }

    $partes = [];

    foreach ($valor as $item) {
        if (is_array($item)) {
            $titulo = $item['nombre_completo']
                ?? $item['nombre']
                ?? $item['descripcion_carrera']
                ?? $item['cargo_puesto']
                ?? $item['empresa_entidad']
                ?? 'Registro';

            $partes[] = $titulo;
        }
    }

    return !empty($partes) ? implode(' / ', $partes) : 'Registro actualizado';
}

function renderDetalleValor($valor): string
{
    if ($valor === null || $valor === '' || $valor === []) {
        return '<span class="text-slate-400 italic">Sin registro</span>';
    }

    if (!is_array($valor)) {
        return '<span class="text-slate-700 font-semibold">' . e($valor) . '</span>';
    }

    $html = '<div class="space-y-3">';

    foreach ($valor as $item) {
        if (!is_array($item)) {
            $html .= '<p class="text-xs font-semibold text-slate-700">' . e($item) . '</p>';
            continue;
        }

        $titulo = $item['nombre_completo']
            ?? $item['nombre']
            ?? $item['descripcion_carrera']
            ?? $item['cargo_puesto']
            ?? $item['empresa_entidad']
            ?? 'Registro';

        $html .= '<div class="rounded-xl bg-white border border-slate-200 p-4">';
        $html .= '<p class="text-sm font-black text-slate-800 mb-3">' . e($titulo) . '</p>';
        $html .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-2">';

        foreach ($item as $k => $v) {
            if ($k === 'id' || $v === null || $v === '') continue;

            $html .= '
                <div class="rounded-lg bg-slate-50 border border-slate-100 px-3 py-2">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">' . e(labelSubCampo($k)) . '</p>
                    <p class="text-xs font-bold text-slate-700 mt-1">' . e($v) . '</p>
                </div>
            ';
        }

        $html .= '</div></div>';
    }

    $html .= '</div>';
    return $html;
}

function normalizarParaComparar($valor, string $campo = '')
{
    if ($valor === null) return '';
    if ($valor === '') return '';

    if (!is_array($valor)) {
        return trim((string)$valor);
    }

    $ignorarInternos = [
        'id',
        'archivo_sustento',
        'estado_validacion',
        'created_at',
        'updated_at',
    ];

    $normalizado = [];

    foreach ($valor as $k => $v) {
        if (in_array($k, $ignorarInternos, true)) {
            continue;
        }

        // Homologar nombres de campos familia/hijos
        if ($k === 'nombre_completo') $k = 'nombre';
        if ($k === 'dni_familiar') $k = 'dni';

        if ($v === null) $v = '';
        if (is_numeric($v)) $v = (string)$v;

        $normalizado[$k] = is_array($v)
            ? normalizarParaComparar($v, $campo)
            : trim((string)$v);
    }

    ksort($normalizado);
    return $normalizado;
}

function obtenerValorAnteriorCampo(array $antes, string $campo)
{
    // En BD anterior, hijos vienen dentro de familia
    if ($campo === 'hijos') {
        $familia = $antes['familia'] ?? [];

        if (!is_array($familia)) {
            return [];
        }

        return array_values(array_filter($familia, function ($item) {
            $parentesco = strtoupper(trim($item['parentesco'] ?? ''));
            return in_array($parentesco, ['HIJO', 'HIJA'], true);
        }));
    }

    return $antes[$campo] ?? null;
}

function obtenerCambios($antes, $despues): array
{
    $ignorar = [
        'id',
        'colab_id',
        'usuario_id',
        'edad',
        'n_hijos',
        'familia',
        'archivo_sustento',
        'nombre_archivo_original',
        'mime_archivo',
        'tamano_archivo',
        'created_at',
        'updated_at',
    ];

    $cambios = [];

    foreach ($despues as $campo => $valorNuevo) {
        if (in_array($campo, $ignorar, true)) {
            continue;
        }

        $valorAnterior = obtenerValorAnteriorCampo($antes, $campo);

        $anteriorNormalizado = normalizarParaComparar($valorAnterior, $campo);
        $nuevoNormalizado = normalizarParaComparar($valorNuevo, $campo);

        if (json_encode($anteriorNormalizado, JSON_UNESCAPED_UNICODE) !== json_encode($nuevoNormalizado, JSON_UNESCAPED_UNICODE)) {
            $cambios[] = [
                'campo' => labelCampo($campo),
                'antes' => $valorAnterior,
                'despues' => $valorNuevo,
            ];
        }
    }

    return $cambios;
}
?>

<main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">

    <header class="min-h-20 bg-white shadow-sm flex flex-col md:flex-row items-center px-4 md:px-8 py-4 md:py-0 justify-between z-10 gap-4 border-b border-red-50">
        <h1 class="text-xl md:text-2xl font-bold text-slate-800 text-center md:text-left">
            Mis Validaciones
        </h1>

        <div class="w-full md:flex-1 md:max-w-md md:mx-8">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" id="searchInput" onkeyup="filterTable()"
                    class="block w-full pl-10 pr-3 py-2 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-900/20 focus:border-red-900 sm:text-sm transition"
                    placeholder="Buscar por estado, fecha o campo...">
            </div>
        </div>

        <div class="w-full md:w-auto bg-red-900 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-red-900/20 flex items-center justify-center">
            <span class="whitespace-nowrap text-sm">
                <?php echo count($solicitudes); ?> Solicitud(es)
            </span>
        </div>
    </header>

    <div class="p-4 md:p-8 flex-1 overflow-y-auto">

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

            <div class="overflow-x-auto">
                <table id="tablaValidaciones" class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Solicitud</th>
                            <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Cambios solicitados</th>
                            <th class="hidden lg:table-cell px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Observación</th>
                            <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Estado</th>
                            <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        <?php if (empty($solicitudes)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="mx-auto w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 font-black mb-3">
                                        —
                                    </div>
                                    <p class="text-sm font-bold text-slate-500">No tienes solicitudes registradas.</p>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($solicitudes as $sol): ?>
                            <?php
                            $nuevos = json_decode($sol['datos_json'] ?? '{}', true);
                            $anteriores = json_decode($sol['datos_anteriores_json'] ?? '{}', true);

                            $nuevos = is_array($nuevos) ? $nuevos : [];
                            $anteriores = is_array($anteriores) ? $anteriores : [];

                            $cambios = obtenerCambios($anteriores, $nuevos);
                            $estado = $sol['estado'] ?? 'PENDIENTE';
                            $modalId = 'modalSolicitud' . (int)$sol['id'];
                            ?>

                            <tr class="hover:bg-red-50/30 transition-colors group">
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-xl flex-shrink-0 flex items-center justify-center font-black mr-3 border shadow-sm <?php echo estadoBadge($estado); ?>">
                                            <?php echo estadoIcono($estado); ?>
                                        </div>

                                        <div>
                                            <div class="text-sm font-bold text-slate-800">
                                                Solicitud #<?php echo (int)$sol['id']; ?>
                                            </div>
                                            <div class="text-[11px] text-slate-400 font-medium tracking-wide">
                                                <?php echo !empty($sol['created_at']) ? date('d/m/Y H:i', strtotime($sol['created_at'])) : 'Sin fecha'; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1.5 max-w-xl">
                                        <?php if (empty($cambios)): ?>
                                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-400 border border-slate-200">
                                                Sin cambios visibles
                                            </span>
                                        <?php else: ?>
                                            <?php foreach (array_slice($cambios, 0, 4) as $c): ?>
                                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-red-50 text-red-900 border border-red-100">
                                                    <?php echo e($c['campo']); ?>
                                                </span>
                                            <?php endforeach; ?>

                                            <?php if (count($cambios) > 4): ?>
                                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-500 border border-slate-200">
                                                    +<?php echo count($cambios) - 4; ?> más
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td class="hidden lg:table-cell px-6 py-4">
                                    <div class="text-xs text-slate-500 font-medium max-w-xs truncate">
                                        <?php echo !empty($sol['observacion_rrhh']) ? e($sol['observacion_rrhh']) : '—'; ?>
                                    </div>
                                </td>

                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider border <?php echo estadoBadge($estado); ?>">
                                        <?php echo e($estado); ?>
                                    </span>
                                </td>

                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button type="button"
                                        onclick="abrirModal('<?php echo $modalId; ?>')"
                                        class="p-2 bg-red-50 text-red-900 rounded-lg hover:bg-red-900 hover:text-white transition-all shadow-sm border border-red-100"
                                        title="Ver detalle">
                                        <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>

                            <div id="<?php echo $modalId; ?>" class="fixed inset-0 z-50 hidden">
                                <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="cerrarModal('<?php echo $modalId; ?>')"></div>

                                <div class="absolute inset-x-4 top-6 bottom-6 md:inset-x-auto md:right-8 md:w-[900px] bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col">
                                    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-white">
                                        <div>
                                            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-400">
                                                Detalle de validación
                                            </p>
                                            <h2 class="text-xl font-black text-slate-900">
                                                Solicitud #<?php echo (int)$sol['id']; ?>
                                            </h2>
                                        </div>

                                        <button type="button" onclick="cerrarModal('<?php echo $modalId; ?>')"
                                            class="w-10 h-10 rounded-xl bg-slate-100 text-slate-500 hover:bg-red-900 hover:text-white transition font-black">
                                            ×
                                        </button>
                                    </div>

                                    <div class="p-6 overflow-y-auto flex-1 bg-slate-50">
                                        <?php if (!empty($sol['observacion_rrhh'])): ?>
                                            <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 p-4">
                                                <p class="text-[10px] font-black uppercase tracking-widest text-red-700">
                                                    Observación de RR. HH.
                                                </p>
                                                <p class="text-sm font-semibold text-red-800 mt-1">
                                                    <?php echo e($sol['observacion_rrhh']); ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>

                                        <div class="space-y-4">
                                            <?php foreach ($cambios as $c): ?>
                                                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                                                    <div class="px-5 py-3 border-b border-slate-100 bg-white">
                                                        <p class="text-sm font-black text-slate-900">
                                                            <?php echo e($c['campo']); ?>
                                                        </p>
                                                    </div>

                                                    <div class="grid grid-cols-1 lg:grid-cols-2">
                                                        <div class="p-5 bg-slate-50 border-b lg:border-b-0 lg:border-r border-slate-200">
                                                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">
                                                                Antes
                                                            </p>
                                                            <?php echo renderDetalleValor($c['antes']); ?>
                                                        </div>

                                                        <div class="p-5 bg-white">
                                                            <p class="text-[10px] font-black uppercase tracking-widest text-red-900 mb-3">
                                                                Después
                                                            </p>
                                                            <?php echo renderDetalleValor($c['despues']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</main>

<script>
    function filterTable() {
        const input = document.getElementById("searchInput");
        const filter = input.value.toLowerCase();
        const table = document.getElementById("tablaValidaciones");
        const trs = table.getElementsByTagName("tr");

        for (let i = 1; i < trs.length; i++) {
            const txt = trs[i].innerText.toLowerCase();
            trs[i].style.display = txt.includes(filter) ? "" : "none";
        }
    }

    function abrirModal(id) {
        document.getElementById(id)?.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function cerrarModal(id) {
        document.getElementById(id)?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
</script>