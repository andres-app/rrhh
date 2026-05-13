<?php
//Vista/modulos/colaborador/misvalidaciones.php
require_once ROOT_PATH . 'Modelo/MdDirectorio.php';

$userIdSesion = (int)($_SESSION['user_id'] ?? 0);

$perfilSesion = MdDirectorio::mdlObtenerPerfilPorUsuario($userIdSesion);

if (!$perfilSesion || empty($perfilSesion['id'])) {
    echo "No se encontró el perfil asociado a este usuario.";
    exit;
}

$colabId = (int)$perfilSesion['id'];

$_SESSION['colab_id'] = $colabId;

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
        'fecha_inscripcion' => 'Fecha inscripción',
        'sin_afp_afiliarme' => 'Sin AFP / Afiliarme',
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

function valorDetalleLegible($valor, string $campo = ''): string
{
    if ($campo === 'sin_afp_afiliarme') {
        return !empty($valor) && (string)$valor !== '0' ? 'Sí' : 'No';
    }

    if ($valor === null || $valor === '' || $valor === []) {
        return 'Sin registro';
    }

    if (is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
        return date('d/m/Y', strtotime($valor));
    }

    if (is_array($valor)) {
        return valorPlano($valor);
    }

    return (string)$valor;
}

function normalizarSubCampoDetalle($valor, string $campo = ''): string
{
    if ($campo === 'sin_afp_afiliarme') {
        return !empty($valor) && (string)$valor !== '0' ? '1' : '';
    }

    if ($valor === null || $valor === '') {
        return '';
    }

    if (is_array($valor)) {
        return json_encode(normalizarParaComparar($valor, $campo), JSON_UNESCAPED_UNICODE);
    }

    return trim((string)$valor);
}

function obtenerSubCambiosAsociativos($antes, $despues): array
{
    $antes = is_array($antes) ? $antes : [];
    $despues = is_array($despues) ? $despues : [];

    $ignorar = [
        'id',
        'colab_id',
        'usuario_id',
        'created_at',
        'updated_at',
        'estado_validacion',
    ];

    $keys = array_unique(array_merge(array_keys($antes), array_keys($despues)));
    $subcambios = [];

    foreach ($keys as $key) {
        if (in_array($key, $ignorar, true)) {
            continue;
        }

        $valorAntes = $antes[$key] ?? null;
        $valorDespues = $despues[$key] ?? null;

        $antesNorm = normalizarSubCampoDetalle($valorAntes, (string)$key);
        $despuesNorm = normalizarSubCampoDetalle($valorDespues, (string)$key);

        if ($antesNorm !== $despuesNorm) {
            $subcambios[] = [
                'campo' => (string)$key,
                'campo_label' => labelSubCampo((string)$key),
                'antes' => $valorAntes,
                'despues' => $valorDespues,
            ];
        }
    }

    return $subcambios;
}

function renderDetalleCambio(array $c): string
{
    if (empty($c['subcambios']) || !is_array($c['subcambios'])) {
        return '
            <div class="grid grid-cols-1 lg:grid-cols-2">
                <div class="p-5 bg-slate-50 border-b lg:border-b-0 lg:border-r border-slate-200">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">
                        Antes
                    </p>
                    ' . renderDetalleValor($c['antes']) . '
                </div>

                <div class="p-5 bg-white">
                    <p class="text-[10px] font-black uppercase tracking-widest text-red-900 mb-3">
                        Después
                    </p>
                    ' . renderDetalleValor($c['despues']) . '
                </div>
            </div>
        ';
    }

    $html = '<div class="p-5 bg-white space-y-3">';

    foreach ($c['subcambios'] as $sub) {
        $html .= '
            <div class="rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                    <p class="text-xs font-black uppercase tracking-widest text-slate-700">
                        ' . e($sub['campo_label']) . '
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2">
                    <div class="p-4 bg-slate-50 border-b md:border-b-0 md:border-r border-slate-200">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">
                            Antes
                        </p>
                        <p class="text-sm font-bold text-slate-700">
                            ' . e(valorDetalleLegible($sub['antes'], $sub['campo'])) . '
                        </p>
                    </div>

                    <div class="p-4 bg-white">
                        <p class="text-[10px] font-black uppercase tracking-widest text-red-900 mb-2">
                            Después
                        </p>
                        <p class="text-sm font-bold text-slate-900">
                            ' . e(valorDetalleLegible($sub['despues'], $sub['campo'])) . '
                        </p>
                    </div>
                </div>
            </div>
        ';
    }

    $html .= '</div>';

    return $html;
}

function valorNormalizadoVacio($valor): bool
{
    if ($valor === null) return true;
    if ($valor === '') return true;
    if ($valor === []) return true;

    if (is_array($valor)) {
        return count($valor) === 0;
    }

    return false;
}

function normalizarParaComparar($valor, string $campo = '')
{
    if ($valor === null || $valor === '') {
        return '';
    }

    if (!is_array($valor)) {
        $v = trim((string)$valor);

        if ($campo === 'sin_afp_afiliarme' && ($v === '0' || strtolower($v) === 'false')) {
            return '';
        }

        return $v;
    }

    if ($valor === []) {
        return [];
    }

    $esLista = array_keys($valor) === range(0, count($valor) - 1);

    $ignorarInternos = [
        'id',
        'colab_id',
        'usuario_id',
        'edad',
        'n_hijos',
        'archivo_sustento',
        'nombre_archivo_original',
        'mime_archivo',
        'tamano_archivo',
        'estado_validacion',
        'created_at',
        'updated_at',
    ];

    if ($esLista) {
        $normalizadoLista = [];

        foreach ($valor as $item) {
            $itemNormalizado = normalizarParaComparar($item, $campo);

            if (!valorNormalizadoVacio($itemNormalizado)) {
                $normalizadoLista[] = $itemNormalizado;
            }
        }

        usort($normalizadoLista, function ($a, $b) {
            return strcmp(
                json_encode($a, JSON_UNESCAPED_UNICODE),
                json_encode($b, JSON_UNESCAPED_UNICODE)
            );
        });

        return $normalizadoLista;
    }

    $normalizado = [];

    foreach ($valor as $k => $v) {
        if (in_array($k, $ignorarInternos, true)) {
            continue;
        }

        if ($k === 'nombre_completo') $k = 'nombre';
        if ($k === 'dni_familiar') $k = 'dni';
        if ($k === 'modalidad_contrato') $k = 'mod_contrato';

        $valorNormalizado = normalizarParaComparar($v, (string)$k);

        if (!valorNormalizadoVacio($valorNormalizado)) {
            $normalizado[$k] = $valorNormalizado;
        }
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

        if (
            json_encode($anteriorNormalizado, JSON_UNESCAPED_UNICODE) !==
            json_encode($nuevoNormalizado, JSON_UNESCAPED_UNICODE)
        ) {
            $subcambios = [];

            if (in_array($campo, ['pension', 'bancario'], true)) {
                $subcambios = obtenerSubCambiosAsociativos($valorAnterior, $valorNuevo);
            }

            $cambios[] = [
                'campo_key' => $campo,
                'campo' => labelCampo($campo),
                'subcampos' => array_map(
                    fn($item) => $item['campo_label'],
                    $subcambios
                ),
                'subcambios' => $subcambios,
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

                <input type="text" id="searchInput"
                    class="block w-full pl-10 pr-10 py-2 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-900/20 focus:border-red-900 sm:text-sm transition"
                    placeholder="Buscar por estado, fecha o campo...">

                <button type="button" id="clearSearch"
                    class="hidden absolute inset-y-0 right-0 pr-3 items-center text-slate-400 hover:text-red-900 transition">
                    ×
                </button>
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

            <div class="px-5 md:px-6 py-4 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-sm font-black text-slate-800 uppercase tracking-wide">
                        Historial de solicitudes
                    </h2>
                    <p class="text-xs text-slate-400 font-medium">
                        Mostrando
                        <span id="rangeInfo" class="font-black text-red-900">0</span>
                        de
                        <span id="resultCount" class="font-black text-slate-700"><?php echo count($solicitudes); ?></span>
                        solicitudes
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest">
                        Ver
                    </label>

                    <select id="pageSize"
                        class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-black text-slate-700 outline-none focus:border-red-900 focus:ring-4 focus:ring-red-900/10">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>

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
                            <tr id="noDataRow">
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
                            $fechaSolicitud = !empty($sol['created_at']) ? date('d/m/Y H:i', strtotime($sol['created_at'])) : 'Sin fecha';

                            $searchText = 'Solicitud #' . (int)$sol['id'] . ' ' . $fechaSolicitud . ' ' . $estado . ' ' . ($sol['observacion_rrhh'] ?? '');

                            foreach ($cambios as $c) {
                                $searchText .= ' ' . ($c['campo'] ?? '');
                            }
                            ?>

                            <tr class="validacion-row hover:bg-red-50/30 transition-colors group"
                                data-search="<?php echo e(mb_strtolower($searchText, 'UTF-8')); ?>">
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
                                                <?php echo e($fechaSolicitud); ?>
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
                                                    <?php
                                                    $textoCambio = $c['campo'];

                                                    if (!empty($c['subcampos']) && is_array($c['subcampos'])) {
                                                        $textoCambio .= ': ' . implode(', ', $c['subcampos']);
                                                    }

                                                    echo e($textoCambio);
                                                    ?>
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
                        <?php endforeach; ?>

                        <tr id="emptyState" class="hidden">
                            <td colspan="5" class="px-6 py-14 text-center">
                                <div class="mx-auto w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center text-red-900 font-black mb-3">
                                    ?
                                </div>
                                <p class="text-sm font-black text-slate-700">No se encontraron resultados.</p>
                                <p class="text-xs text-slate-400 mt-1">Intenta buscar por estado, fecha, número de solicitud o campo modificado.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-5 md:px-6 py-4 border-t border-slate-100 bg-slate-50/70 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="text-xs font-bold text-slate-400">
                    Página
                    <span id="currentPageLabel" class="text-slate-700 font-black">1</span>
                    de
                    <span id="totalPagesLabel" class="text-slate-700 font-black">1</span>
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
    </div>
</main>
<?php foreach ($solicitudes as $sol): ?>
    <?php
    $nuevos = json_decode($sol['datos_json'] ?? '{}', true);
    $anteriores = json_decode($sol['datos_anteriores_json'] ?? '{}', true);

    $nuevos = is_array($nuevos) ? $nuevos : [];
    $anteriores = is_array($anteriores) ? $anteriores : [];

    $cambios = obtenerCambios($anteriores, $nuevos);

    $estado = $sol['estado'] ?? 'PENDIENTE';
    $modalId = 'modalSolicitud' . (int)$sol['id'];

    $fechaSolicitud = !empty($sol['created_at'])
        ? date('d/m/Y H:i', strtotime($sol['created_at']))
        : 'Sin fecha';

    $fechaRevision = !empty($sol['fecha_validacion'])
        ? date('d/m/Y H:i', strtotime($sol['fecha_validacion']))
        : '—';

    $resumenModal = [];

    foreach ($cambios as $c) {
        $texto = $c['campo'] ?? 'Campo modificado';

        if (!empty($c['subcampos']) && is_array($c['subcampos'])) {
            $texto .= ': ' . implode(', ', $c['subcampos']);
        }

        $resumenModal[] = $texto;
    }

    $resumenModalTexto = !empty($resumenModal)
        ? implode(', ', array_unique($resumenModal))
        : 'Sin cambios visibles';
    ?>

    <div id="<?php echo $modalId; ?>" class="fixed inset-0 z-[90] hidden" role="dialog" aria-modal="true">

        <!-- FONDO -->
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"
            onclick="cerrarModal('<?php echo $modalId; ?>')"></div>

        <!-- CONTENEDOR -->
        <div class="absolute inset-0 flex items-center justify-center p-4">

            <div class="relative w-full max-w-5xl max-h-[92vh] bg-white rounded-[32px] shadow-2xl border border-slate-200 overflow-hidden flex flex-col">

                <!-- HEADER -->
                <div class="px-7 py-6 bg-gradient-to-r from-[#310404] to-red-900 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-red-200 text-[10px] font-black uppercase tracking-[0.24em] mb-1">
                            Detalle de validación
                        </p>

                        <h2 class="text-white text-2xl font-black leading-tight">
                            Solicitud #<?php echo (int)$sol['id']; ?>
                        </h2>

                        <p class="text-red-100 text-sm font-semibold mt-2">
                            Revisión de los cambios solicitados en tu perfil
                        </p>
                    </div>

                    <button type="button"
                        onclick="cerrarModal('<?php echo $modalId; ?>')"
                        class="w-10 h-10 rounded-2xl bg-white/10 text-white hover:bg-white/20 transition flex items-center justify-center font-black">
                        ✕
                    </button>
                </div>

                <!-- RESUMEN SUPERIOR -->
                <div class="px-7 py-4 border-b border-slate-100 bg-slate-50">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

                        <div class="bg-white border border-slate-200 rounded-2xl p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                                Estado
                            </p>

                            <span class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest px-3 py-1.5 rounded-xl border <?php echo estadoBadge($estado); ?>">
                                <span><?php echo estadoIcono($estado); ?></span>
                                <?php echo e($estado); ?>
                            </span>
                        </div>

                        <div class="bg-white border border-slate-200 rounded-2xl p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                                Fecha de solicitud
                            </p>

                            <p class="text-sm font-black text-slate-800">
                                <?php echo e($fechaSolicitud); ?>
                            </p>
                        </div>

                        <div class="bg-white border border-slate-200 rounded-2xl p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                                Fecha de revisión
                            </p>

                            <p class="text-sm font-black text-slate-800">
                                <?php echo e($fechaRevision); ?>
                            </p>
                        </div>

                    </div>

                    <div class="mt-4 bg-white border border-slate-200 rounded-2xl p-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                            Resumen de cambios
                        </p>

                        <p class="text-sm font-bold text-slate-700 leading-relaxed">
                            <?php echo e($resumenModalTexto); ?>
                        </p>
                    </div>

                    <?php if (!empty($sol['observacion_rrhh']) || !empty($sol['motivo_rechazo'])): ?>
                        <div class="mt-4 rounded-2xl border border-red-100 bg-red-50 p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-red-700 mb-1">
                                Observación de RR. HH.
                            </p>

                            <p class="text-sm font-semibold text-red-800 leading-relaxed">
                                <?php echo e($sol['motivo_rechazo'] ?: $sol['observacion_rrhh']); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                </div>

                <!-- CUERPO -->
                <div class="flex-1 overflow-y-auto px-7 py-6 bg-slate-50">

                    <?php if (empty($cambios)): ?>

                        <div class="p-8 text-center text-slate-400 bg-white border border-slate-200 rounded-3xl">
                            <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center mb-3 font-black">
                                —
                            </div>

                            <p class="text-sm font-bold">
                                No se detectaron cambios visibles.
                            </p>
                        </div>

                    <?php else: ?>

                        <div class="space-y-4">
                            <?php foreach ($cambios as $c): ?>

                                <div class="rounded-3xl border border-slate-200 overflow-hidden bg-white shadow-sm">

                                    <div class="px-5 py-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-red-900">
                                                Campo modificado
                                            </p>

                                            <p class="text-sm font-black text-slate-800 mt-1">
                                                <?php echo e($c['campo'] ?? 'Cambio'); ?>

                                                <?php if (!empty($c['subcampos']) && is_array($c['subcampos'])): ?>
                                                    <span class="ml-2 text-[10px] font-black text-slate-500 bg-slate-100 px-2 py-1 rounded-lg">
                                                        <?php echo e(implode(', ', $c['subcampos'])); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </p>
                                        </div>

                                        <span class="text-[10px] font-black uppercase tracking-widest bg-red-50 text-red-900 border border-red-100 rounded-xl px-3 py-1">
                                            Cambio detectado
                                        </span>
                                    </div>

                                    <?php if (function_exists('renderDetalleCambio')): ?>

                                        <?php echo renderDetalleCambio($c); ?>

                                    <?php else: ?>

                                        <div class="grid grid-cols-1 md:grid-cols-2">
                                            <div class="p-5 bg-slate-50 border-b md:border-b-0 md:border-r border-slate-200">
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

                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>
                        </div>

                    <?php endif; ?>

                </div>

                <!-- FOOTER -->
                <div class="px-7 py-5 bg-white border-t border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="text-xs text-slate-400 font-bold">
                        Esta vista muestra lo enviado para validación y la respuesta de RR. HH. cuando corresponda.
                    </div>

                    <button type="button"
                        onclick="cerrarModal('<?php echo $modalId; ?>')"
                        class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-black hover:bg-slate-100 transition">
                        Cerrar
                    </button>
                </div>

            </div>

        </div>
    </div>

<?php endforeach; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('searchInput');
        const clearBtn = document.getElementById('clearSearch');
        const rows = Array.from(document.querySelectorAll('.validacion-row'));
        const emptyState = document.getElementById('emptyState');
        const noDataRow = document.getElementById('noDataRow');
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
            return parseInt(pageSizeSelect.value, 10) || 10;
        }

        function applyFilter() {
            const query = normalizeText(input.value);

            filteredRows = rows.filter(row => {
                const data = normalizeText(row.dataset.search);
                return query === '' || data.includes(query);
            });

            currentPage = 1;

            clearBtn.classList.toggle('hidden', query.length === 0);
            clearBtn.classList.toggle('flex', query.length > 0);

            renderTable();
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

            if (emptyState) {
                emptyState.classList.toggle('hidden', totalResults !== 0 || rows.length === 0);
            }

            if (noDataRow) {
                noDataRow.classList.toggle('hidden', rows.length !== 0);
            }

            currentPageLabel.textContent = totalResults === 0 ? '0' : currentPage;
            totalPagesLabel.textContent = totalResults === 0 ? '0' : totalPages;

            prevBtn.disabled = currentPage <= 1 || totalResults === 0;
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

        input.addEventListener('input', applyFilter);

        clearBtn.addEventListener('click', function() {
            input.value = '';
            input.focus();
            applyFilter();
        });

        pageSizeSelect.addEventListener('change', function() {
            currentPage = 1;
            renderTable();
        });

        prevBtn.addEventListener('click', function() {
            if (currentPage > 1) {
                currentPage--;
                renderTable();
            }
        });

        nextBtn.addEventListener('click', function() {
            const totalPages = Math.ceil(filteredRows.length / getPageSize());

            if (currentPage < totalPages) {
                currentPage++;
                renderTable();
            }
        });

        applyFilter();
    });

    function abrirModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function cerrarModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        modal.classList.add('hidden');

        const hayModalAbierto = document.querySelector('[id^="modalSolicitud"]:not(.hidden)');

        if (!hayModalAbierto) {
            document.body.classList.remove('overflow-hidden');
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;

        document.querySelectorAll('[id^="modalSolicitud"]').forEach(modal => {
            if (!modal.classList.contains('hidden')) {
                modal.classList.add('hidden');
            }
        });

        document.body.classList.remove('overflow-hidden');
    });
</script>