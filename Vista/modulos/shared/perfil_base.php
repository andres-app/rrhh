    <?php
    //Vista/modulos/shared/perfil_base.php

    if (!function_exists('calcularEdad')) {
        function calcularEdad(?string $fechaNac): string
        {
            if (empty($fechaNac)) {
                return '—';
            }

            try {
                $nac = new DateTime($fechaNac);
                $hoy = new DateTime();

                return $hoy->diff($nac)->y . ' años';
            } catch (Throwable $e) {
                return '—';
            }
        }
    }

    if (!function_exists('formatFecha')) {
        function formatFecha(?string $fecha): string
        {
            if (empty($fecha)) {
                return '—';
            }

            try {
                return (new DateTime($fecha))->format('d/m/Y');
            } catch (Throwable $e) {
                return $fecha;
            }
        }
    }
    if (!function_exists('htmlPerfil')) {
        function htmlPerfil($valor): string
        {
            return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
        }
    }

    if (!function_exists('organizarContratosConAdendas')) {
        function organizarContratosConAdendas(array $contratos): array
        {
            $mapa = [];

            foreach ($contratos as $contrato) {
                $id = (int)($contrato['id'] ?? 0);

                if ($id <= 0) {
                    continue;
                }

                $contrato['_adendas'] = [];
                $mapa[$id] = $contrato;
            }

            foreach ($mapa as $id => $contrato) {
                $tipo = strtoupper(trim($contrato['tipo_registro'] ?? 'CONTRATO'));
                $padreId = (int)($contrato['contrato_padre_id'] ?? 0);

                if ($tipo === 'ADENDA' && $padreId > 0 && isset($mapa[$padreId])) {
                    $mapa[$padreId]['_adendas'][] = $contrato;
                }
            }

            $padres = [];

            foreach ($mapa as $id => $contrato) {
                $tipo = strtoupper(trim($contrato['tipo_registro'] ?? 'CONTRATO'));
                $padreId = (int)($contrato['contrato_padre_id'] ?? 0);

                // Si es adenda correctamente vinculada, no se muestra como padre.
                if ($tipo === 'ADENDA' && $padreId > 0 && isset($mapa[$padreId])) {
                    continue;
                }

                // Contratos normales y adendas huérfanas quedan como periodos independientes.
                $padres[] = $contrato;
            }

            foreach ($padres as &$padre) {
                usort($padre['_adendas'], function ($a, $b) {
                    $fa = (string)($a['fecha_ingreso'] ?? $a['fecha_documento'] ?? '');
                    $fb = (string)($b['fecha_ingreso'] ?? $b['fecha_documento'] ?? '');

                    return strcmp($fa, $fb) ?: ((int)($a['id'] ?? 0) <=> (int)($b['id'] ?? 0));
                });
            }

            unset($padre);

            // Último periodo laboral primero.
            usort($padres, function ($a, $b) {
                $fa = (string)($a['fecha_ingreso'] ?? $a['fecha_documento'] ?? '');
                $fb = (string)($b['fecha_ingreso'] ?? $b['fecha_documento'] ?? '');

                return strcmp($fb, $fa) ?: ((int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0));
            });

            return $padres;
        }
    }

    if (!function_exists('obtenerVigenciaFinalContrato')) {
        function obtenerVigenciaFinalContrato(array $contrato): ?string
        {
            $fechaFinal = $contrato['fecha_cese'] ?? null;

            foreach (($contrato['_adendas'] ?? []) as $adenda) {
                $fechaCeseAdenda = $adenda['fecha_cese'] ?? null;

                if (!empty($fechaCeseAdenda) && (empty($fechaFinal) || $fechaCeseAdenda > $fechaFinal)) {
                    $fechaFinal = $fechaCeseAdenda;
                }
            }

            return $fechaFinal ?: null;
        }
    }

    if (!function_exists('obtenerInicioPeriodoContrato')) {
        function obtenerInicioPeriodoContrato(array $contrato): ?string
        {
            if (!empty($contrato['fecha_ingreso'])) {
                return $contrato['fecha_ingreso'];
            }

            foreach (($contrato['_adendas'] ?? []) as $adenda) {
                if (!empty($adenda['fecha_ingreso'])) {
                    return $adenda['fecha_ingreso'];
                }
            }

            return $contrato['fecha_documento'] ?? null;
        }
    }

    if (!function_exists('obtenerEstadoVisualContrato')) {
        function obtenerEstadoVisualContrato(array $contrato): array
        {
            $inicio = obtenerInicioPeriodoContrato($contrato);
            $final = obtenerVigenciaFinalContrato($contrato);

            try {
                $hoy = new DateTime('today');

                if (!empty($inicio)) {
                    $fechaInicio = new DateTime($inicio);

                    if ($fechaInicio > $hoy) {
                        return [
                            'clave' => 'programado',
                            'texto' => 'Programado',
                            'clase' => 'bg-blue-50 text-blue-700 border-blue-100'
                        ];
                    }
                }

                if (empty($final)) {
                    return [
                        'clave' => 'vigente',
                        'texto' => 'Vigente',
                        'clase' => 'bg-emerald-50 text-emerald-700 border-emerald-100'
                    ];
                }

                $fechaFinal = new DateTime($final);

                if ($fechaFinal >= $hoy) {
                    return [
                        'clave' => 'vigente',
                        'texto' => 'Vigente',
                        'clase' => 'bg-emerald-50 text-emerald-700 border-emerald-100'
                    ];
                }

                return [
                    'clave' => 'cerrado',
                    'texto' => 'Cerrado',
                    'clase' => 'bg-slate-100 text-slate-600 border-slate-200'
                ];
            } catch (Throwable $e) {
                return [
                    'clave' => 'sin-validar',
                    'texto' => 'Sin validar',
                    'clase' => 'bg-amber-50 text-amber-700 border-amber-100'
                ];
            }
        }
    }

    if (!function_exists('renderModalDetalleContrato')) {
        function renderModalDetalleContrato(array $contrato, int $numero, string $instancia = ''): void
        {
            $sufijo = $instancia !== '' ? '-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $instancia) : '';
            $modalId = 'modal-detalle-contrato-' . (int)($contrato['id'] ?? $numero) . $sufijo;

            $adendas = $contrato['_adendas'] ?? [];
            $vigenciaFinal = obtenerVigenciaFinalContrato($contrato);
            $estado = obtenerEstadoVisualContrato($contrato);
    ?>

            <div id="<?php echo $modalId; ?>" class="fixed inset-0 z-[90] hidden" role="dialog" aria-modal="true">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                    onclick="cerrarModalDetalleContrato('<?php echo $modalId; ?>')"></div>

                <div class="absolute inset-0 flex items-center justify-center p-4">
                    <div class="w-full max-w-5xl max-h-[92vh] bg-white rounded-[32px] shadow-2xl border border-slate-200 overflow-hidden flex flex-col">

                        <div class="bg-gradient-to-r from-[#310404] to-red-900 px-6 lg:px-8 py-5 flex items-start justify-between gap-4">
                            <div>
                                <p class="text-red-200 text-[10px] font-black uppercase tracking-[0.22em] mb-1">
                                    Detalle del periodo contractual
                                </p>

                                <h2 class="text-white text-xl font-black leading-tight">
                                    Periodo <?php echo $numero; ?> ·
                                    <?php echo htmlPerfil($contrato['numero_documento'] ?: 'Contrato sin número/documento'); ?>
                                </h2>

                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border bg-white/10 text-white border-white/20">
                                        <?php echo htmlPerfil($contrato['modalidad'] ?? 'Sin modalidad'); ?>
                                    </span>

                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border bg-white/10 text-white border-white/20">
                                        <?php echo count($adendas); ?> adenda(s)
                                    </span>
                                </div>
                            </div>

                            <button type="button"
                                onclick="cerrarModalDetalleContrato('<?php echo $modalId; ?>')"
                                class="text-red-100 hover:text-white transition-colors">
                                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="flex-1 overflow-y-auto bg-slate-50 px-6 lg:px-8 py-6 space-y-6">

                            <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
                                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-5">
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-red-800">
                                            Contrato padre
                                        </p>
                                        <p class="text-lg font-black text-slate-800 mt-1">
                                            <?php echo htmlPerfil($contrato['numero_documento'] ?: 'Sin número/documento'); ?>
                                        </p>
                                    </div>

                                    <span class="inline-flex self-start px-3 py-1.5 rounded-full border text-[10px] font-black uppercase tracking-widest <?php echo $estado['clase']; ?>">
                                        <?php echo $estado['texto']; ?>
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                    <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4">
                                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Fecha documento</p>
                                        <p class="text-sm font-black text-slate-700">
                                            <?php echo !empty($contrato['fecha_documento']) ? formatFecha($contrato['fecha_documento']) : '—'; ?>
                                        </p>
                                    </div>

                                    <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4">
                                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Ingreso</p>
                                        <p class="text-sm font-black text-slate-700">
                                            <?php echo !empty($contrato['fecha_ingreso']) ? formatFecha($contrato['fecha_ingreso']) : '—'; ?>
                                        </p>
                                    </div>

                                    <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4">
                                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Cese inicial</p>
                                        <p class="text-sm font-black text-slate-700">
                                            <?php echo !empty($contrato['fecha_cese']) ? formatFecha($contrato['fecha_cese']) : 'Vigente'; ?>
                                        </p>
                                    </div>

                                    <div class="rounded-2xl bg-red-50 border border-red-100 p-4">
                                        <p class="text-[10px] font-black uppercase tracking-widest text-red-400 mb-1">Vigencia final</p>
                                        <p class="text-sm font-black text-red-900">
                                            <?php echo !empty($vigenciaFinal) ? formatFecha($vigenciaFinal) : 'Vigente'; ?>
                                        </p>
                                    </div>
                                </div>

                                <?php if (!empty($contrato['observacion'])): ?>
                                    <div class="mt-4 rounded-2xl bg-slate-50 border border-slate-200 p-4">
                                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                                            Observación
                                        </p>
                                        <p class="text-sm font-semibold text-slate-700 leading-relaxed">
                                            <?php echo nl2br(htmlPerfil($contrato['observacion'])); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
                                <div class="flex items-center justify-between gap-3 mb-5">
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">
                                            Adendas vinculadas
                                        </p>
                                        <h3 class="text-lg font-black text-slate-800">
                                            Historial de ampliaciones
                                        </h3>
                                    </div>

                                    <span class="bg-slate-100 text-slate-600 text-xs font-black px-3 py-1.5 rounded-xl">
                                        <?php echo count($adendas); ?> registro(s)
                                    </span>
                                </div>

                                <?php if (empty($adendas)): ?>
                                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 py-8 text-center">
                                        <p class="text-sm text-slate-400 font-semibold">
                                            Este periodo contractual no tiene adendas registradas.
                                        </p>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-3">
                                        <?php foreach ($adendas as $i => $adenda): ?>
                                            <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4">
                                                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-red-50 text-red-900 border border-red-100 mb-2">
                                                            Adenda <?php echo $i + 1; ?>
                                                        </span>

                                                        <p class="text-sm font-black text-slate-800 break-words">
                                                            <?php echo htmlPerfil($adenda['numero_documento'] ?: 'Adenda sin número/documento'); ?>
                                                        </p>

                                                        <?php if (!empty($adenda['motivo_adenda'])): ?>
                                                            <p class="text-xs text-slate-500 font-semibold mt-1">
                                                                <?php echo htmlPerfil($adenda['motivo_adenda']); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="grid grid-cols-2 gap-2 w-full lg:w-[260px] shrink-0">
                                                        <div class="rounded-xl bg-white border border-slate-200 px-3 py-2">
                                                            <p class="text-[9px] font-black uppercase text-slate-400">Desde</p>
                                                            <p class="text-xs font-black text-slate-700">
                                                                <?php echo !empty($adenda['fecha_ingreso']) ? formatFecha($adenda['fecha_ingreso']) : '—'; ?>
                                                            </p>
                                                        </div>

                                                        <div class="rounded-xl bg-white border border-slate-200 px-3 py-2">
                                                            <p class="text-[9px] font-black uppercase text-slate-400">Hasta</p>
                                                            <p class="text-xs font-black text-slate-700">
                                                                <?php echo !empty($adenda['fecha_cese']) ? formatFecha($adenda['fecha_cese']) : '—'; ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php if (!empty($adenda['observacion'])): ?>
                                                    <div class="mt-3 rounded-xl bg-white border border-slate-200 p-3">
                                                        <p class="text-xs font-semibold text-slate-700 leading-relaxed">
                                                            <?php echo nl2br(htmlPerfil($adenda['observacion'])); ?>
                                                        </p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="px-6 lg:px-8 py-5 border-t border-slate-200 bg-white flex justify-end">
                            <button type="button"
                                onclick="cerrarModalDetalleContrato('<?php echo $modalId; ?>')"
                                class="px-5 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-bold hover:bg-slate-800 transition-all">
                                Cerrar
                            </button>
                        </div>

                    </div>
                </div>
            </div>

        <?php
        }
    }

    if (!function_exists('renderHistorialContratos')) {
        function renderHistorialContratos(array $contratos): void
        {
            static $instanciaHistorial = 0;
            $instanciaHistorial++;

            $instancia = 'hc' . $instanciaHistorial;

            $contratosOrganizados = organizarContratosConAdendas($contratos);

            $totalPeriodos = count($contratosOrganizados);

            $totalAdendas = count(array_filter($contratos, function ($c) {
                return strtoupper(trim($c['tipo_registro'] ?? 'CONTRATO')) === 'ADENDA';
            }));

            $totalVigentes = 0;

            foreach ($contratosOrganizados as $periodoTmp) {
                $vigenciaTmp = obtenerVigenciaFinalContrato($periodoTmp);

                try {
                    if (empty($vigenciaTmp) || new DateTime($vigenciaTmp) >= new DateTime('today')) {
                        $totalVigentes++;
                    }
                } catch (Throwable $e) {
                    // Evita romper la vista si una fecha viene mal.
                }
            }
        ?>

            <div class="bg-white p-5 lg:p-7 rounded-[32px] shadow-sm border border-slate-200/80 overflow-hidden">

                <!-- CABECERA PREMIUM -->
                <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                            <span class="w-1.5 h-5 bg-red-800 rounded-full"></span>
                            Historial de Contratos
                        </h3>

                        <p class="text-xs text-slate-400 font-semibold mt-1">
                            Resumen ejecutivo por periodo contractual.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-2 rounded-2xl bg-slate-50 border border-slate-200 px-3 py-2">
                            <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-600">
                                <?php echo $totalAdendas; ?> adenda(s)
                            </span>
                        </span>

                        <span class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 border border-emerald-100 px-3 py-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="text-[10px] font-black uppercase tracking-widest text-emerald-700">
                                <?php echo $totalVigentes; ?> vigente(s)
                            </span>
                        </span>
                    </div>
                </div>

                <?php if (empty($contratosOrganizados)): ?>

                    <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 py-10 text-center">
                        <p class="text-slate-400 text-sm font-semibold">
                            No hay contratos registrados.
                        </p>
                    </div>

                <?php else: ?>

                    <div class="space-y-4">
                        <?php foreach ($contratosOrganizados as $i => $contrato): ?>
                            <?php
                            $numero = $i + 1;
                            $adendas = $contrato['_adendas'] ?? [];
                            $cantidadAdendas = count($adendas);
                            $vigenciaFinal = obtenerVigenciaFinalContrato($contrato);

                            $ultimaAdenda = null;

                            if (!empty($adendas)) {
                                $adendasOrdenadas = $adendas;

                                usort($adendasOrdenadas, function ($a, $b) {
                                    $fa = (string)($a['fecha_cese'] ?? $a['fecha_ingreso'] ?? $a['fecha_documento'] ?? '');
                                    $fb = (string)($b['fecha_cese'] ?? $b['fecha_ingreso'] ?? $b['fecha_documento'] ?? '');

                                    return strcmp($fb, $fa);
                                });

                                $ultimaAdenda = $adendasOrdenadas[0] ?? null;
                            }

                            $modalId = 'modal-detalle-contrato-' . (int)($contrato['id'] ?? $numero) . '-' . $instancia;

                            $estadoTexto = 'Cerrado';
                            $estadoClase = 'bg-slate-100 text-slate-600 border-slate-200';
                            $estadoDot = 'bg-slate-400';

                            try {
                                if (empty($vigenciaFinal) || new DateTime($vigenciaFinal) >= new DateTime('today')) {
                                    $estadoTexto = 'Vigente';
                                    $estadoClase = 'bg-emerald-50 text-emerald-700 border-emerald-100';
                                    $estadoDot = 'bg-emerald-500';
                                }
                            } catch (Throwable $e) {
                                $estadoTexto = 'Sin validar';
                                $estadoClase = 'bg-amber-50 text-amber-700 border-amber-100';
                                $estadoDot = 'bg-amber-500';
                            }
                            ?>

                            <!-- TARJETA PREMIUM COMPACTA -->
                            <div class="group relative overflow-hidden rounded-[30px] border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-white shadow-sm hover:shadow-lg hover:border-red-100 transition-all duration-300">

                                <!-- línea decorativa superior -->
                                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-red-900 via-red-700 to-slate-200"></div>

                                <div class="p-5 lg:p-6">
                                    <div class="flex flex-col xl:flex-row xl:items-center gap-5">

                                        <!-- Número -->
                                        <div class="flex xl:block items-center gap-3 shrink-0">
                                            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-red-900 to-[#310404] text-white flex items-center justify-center text-sm font-black shadow-md shadow-red-900/20">
                                                <?php echo $numero; ?>
                                            </div>
                                        </div>

                                        <!-- Contenido principal -->
                                        <div class="flex-1 min-w-0">

                                            <!-- Badges -->
                                            <div class="flex flex-wrap items-center gap-2 mb-3">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border <?php echo $estadoClase; ?>">
                                                    <span class="w-1.5 h-1.5 rounded-full <?php echo $estadoDot; ?>"></span>
                                                    <?php echo $estadoTexto; ?>
                                                </span>

                                                <?php if (!empty($contrato['modalidad'])): ?>
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-white text-slate-700 border border-slate-200">
                                                        <?php echo htmlPerfil($contrato['modalidad']); ?>
                                                    </span>
                                                <?php endif; ?>

                                                <?php if ($cantidadAdendas > 0): ?>
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-900 text-white">
                                                        <?php echo $cantidadAdendas; ?> adenda(s)
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Título -->
                                            <p class="text-base lg:text-lg font-black text-slate-900 leading-tight break-words">
                                                <?php echo htmlPerfil($contrato['numero_documento'] ?: 'Contrato sin número/documento'); ?>
                                            </p>

                                            <!-- Fechas limpias -->
                                            <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 max-w-3xl">

                                                <div class="rounded-2xl bg-white/80 border border-slate-200 px-4 py-3">
                                                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">
                                                        Contrato original
                                                    </p>
                                                    <p class="text-xs font-black text-slate-700">
                                                        <?php echo !empty($contrato['fecha_ingreso']) ? formatFecha($contrato['fecha_ingreso']) : 'Sin ingreso'; ?>
                                                        —
                                                        <?php echo !empty($contrato['fecha_cese']) ? formatFecha($contrato['fecha_cese']) : 'Vigente'; ?>
                                                    </p>
                                                </div>

                                                <div class="rounded-2xl bg-red-50/80 border border-red-100 px-4 py-3">
                                                    <p class="text-[9px] font-black uppercase tracking-widest text-red-400 mb-1">
                                                        Vigencia actual
                                                    </p>
                                                    <p class="text-xs font-black text-red-900">
                                                        <?php echo !empty($vigenciaFinal) ? 'Hasta ' . formatFecha($vigenciaFinal) : 'Vigente'; ?>
                                                    </p>
                                                </div>

                                            </div>

                                            <?php if (!empty($ultimaAdenda)): ?>
                                                <div class="mt-3 inline-flex max-w-full items-center gap-2 rounded-2xl bg-white border border-slate-200 px-3 py-2">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-700 shrink-0"></span>
                                                    <p class="text-[11px] font-semibold text-slate-500 truncate">
                                                        Última adenda:
                                                        <span class="font-black text-slate-700">
                                                            <?php echo htmlPerfil($ultimaAdenda['numero_documento'] ?: 'Sin número'); ?>
                                                        </span>
                                                    </p>
                                                </div>
                                            <?php else: ?>
                                                <div class="mt-3 inline-flex items-center gap-2 rounded-2xl bg-slate-100 border border-slate-200 px-3 py-2">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                                    <p class="text-[11px] font-bold text-slate-500">
                                                        Sin adendas vinculadas
                                                    </p>
                                                </div>
                                            <?php endif; ?>

                                        </div>

                                        <!-- Acción -->
                                        <div class="shrink-0">
                                            <button type="button"
                                                onclick="abrirModalDetalleContrato('<?php echo $modalId; ?>')"
                                                class="w-full xl:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-slate-900 text-white text-xs font-black hover:bg-red-900 transition-all shadow-md shadow-slate-900/10 active:scale-95">
                                                Ver detalle
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <?php renderModalDetalleContrato($contrato, $numero, $instancia); ?>

                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>
            </div>

    <?php
        }
    }
    $rolSesion = strtolower($_SESSION['user_role'] ?? '');
    $esEditable = in_array($rolSesion, ['admin', 'rrhh', 'superadmin'], true);
    // Refuerzo: sincroniza cambiar_clave desde BD antes de pintar el perfil
    if (!empty($_SESSION['user_id'])) {
        require_once ROOT_PATH . 'Modelo/MdUsuario.php';

        $usuarioActualClave = MdUsuario::mdlMostrarUsuarios(
            'usuarios',
            'id',
            (string)$_SESSION['user_id']
        );

        if (is_array($usuarioActualClave)) {
            $_SESSION['cambiar_clave'] = (int)($usuarioActualClave['cambiar_clave'] ?? 0);
        }
    }

    $debeCambiarClave = ((int)($_SESSION['cambiar_clave'] ?? 0) === 1);
    //Vista/modulos/shared/perfil_base.php
    $titulo_pagina = "Perfil: " . ($data['nombres_apellidos'] ?? 'Colaborador');
    require_once ROOT_PATH . 'Vista/includes/header.php';
    require_once ROOT_PATH . 'Vista/includes/sidebar.php';

    // Arrays que vienen del modelo
    $contratos   = $data['contratos'] ?? [];
    $formacion   = $data['formacion'] ?? [];
    $experiencia = $data['experiencia'] ?? [];
    $familia     = $data['familia'] ?? [];
    $idiomas     = $data['idiomas'] ?? [];
    $pension     = $data['pension'] ?? [];
    $bancario    = $data['bancario'] ?? [];

    $teletrabajoActual = null;

    if (file_exists(ROOT_PATH . 'Controlador/CtrTeletrabajo.php')) {
        require_once ROOT_PATH . 'Controlador/CtrTeletrabajo.php';

        if (class_exists('CtrTeletrabajo')) {
            $ctrTeletrabajoPerfil = new CtrTeletrabajo();

            if (method_exists($ctrTeletrabajoPerfil, 'ctrObtenerTeletrabajoActualPorColaborador')) {
                $teletrabajoActual = $ctrTeletrabajoPerfil->ctrObtenerTeletrabajoActualPorColaborador((int)($data['id'] ?? 0));
            }
        }
    }

    $estadoTeletrabajo = strtoupper(trim((string)($teletrabajoActual['estado_calculado'] ?? '')));

    $tieneTeletrabajoActivo = in_array($estadoTeletrabajo, ['VIGENTE', 'POR_VENCER', 'POR_INICIAR'], true);

    $hijos = array_values(array_filter($familia, fn($f) => in_array(($f['parentesco'] ?? ''), ['HIJO', 'HIJA'], true)));

    // Compatibilidad con el modal copiado desde colaborador
    $perfil = [];

    if (isset($data) && is_array($data)) {
        $perfil = $data;
    }

    if (!empty($data["datos_json"])) {
        $jsonPerfil = json_decode($data["datos_json"], true);

        if (is_array($jsonPerfil)) {
            $perfil = array_merge($perfil, $jsonPerfil);
        }
    }

    // Reasignar arrays reales del modelo para que NO se vacíen los inputs dinámicos
    $perfil['contratos']   = $contratos;
    $perfil['formacion']   = $formacion;
    $perfil['experiencia'] = $experiencia;
    $perfil['familia']     = $familia;
    $perfil['idiomas']     = $idiomas;
    $perfil['pension']     = $pension;
    $perfil['bancario']    = $bancario;

    $experienciaOrdenada = $perfil['experiencia'] ?? [];

    usort($experienciaOrdenada, function ($a, $b) {
        $aActual = !empty($a['actualmente_trabaja']);
        $bActual = !empty($b['actualmente_trabaja']);

        // Primero los trabajos actuales
        if ($aActual !== $bActual) {
            return $aActual ? -1 : 1;
        }

        // Luego ordenar por fecha final o fecha inicio más reciente
        $fechaA = $aActual
            ? ($a['fecha_inicio'] ?? '')
            : ($a['fecha_fin'] ?? $a['fecha_inicio'] ?? '');

        $fechaB = $bActual
            ? ($b['fecha_inicio'] ?? '')
            : ($b['fecha_fin'] ?? $b['fecha_inicio'] ?? '');

        return strcmp($fechaB, $fechaA);
    });

    // Reemplaza la experiencia original por la ordenada para usarla en vista y modal
    $perfil['experiencia'] = $experienciaOrdenada;

    $resumenSolicitudes = MdDirectorio::mdlResumenSolicitudesPorColaborador((int)($perfil['id'] ?? 0));
    ?>

    <main class="flex-1 bg-slate-50 overflow-y-auto pb-20">
        <div class="h-48 bg-gradient-to-r from-[#310404] via-[#4c0505] to-red-900 shadow-lg"></div>

        <div class="max-w-6xl mx-auto px-6">
            <div class="relative -mt-24">

                <!-- ============================================================
                        TARJETA CABECERA: Avatar + Nombre + Tabs
                    ============================================================ -->
                <div class="bg-white rounded-3xl shadow-xl p-8 border border-slate-200 mb-6">
                    <div class="flex flex-col md:flex-row items-center gap-8">

                        <!-- Avatar -->
                        <div class="relative shrink-0">
                            <div class="h-36 w-36 rounded-[2rem] bg-gradient-to-br from-[#310404] to-red-900 flex items-center justify-center text-5xl font-black text-white shadow-2xl ring-8 ring-white">
                                <?php echo mb_substr($data['nombres_apellidos'] ?? 'C', 0, 1); ?>
                            </div>

                            <?php if (!empty($data['situacion'])): ?>
                                <span class="absolute -bottom-2 left-1/2 -translate-x-1/2 bg-green-50 text-green-700 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border border-green-200 shadow-sm">
                                    <?php echo htmlspecialchars($data['situacion']); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Datos principales -->
                        <div class="flex-1 text-center md:text-left min-w-0">
                            <p class="text-xs font-black uppercase tracking-[0.25em] text-red-900/60 mb-2">
                                Perfil del colaborador
                            </p>

                            <h1 class="text-3xl lg:text-4xl font-black text-slate-800 tracking-tight leading-tight truncate">
                                <?php echo htmlspecialchars($data['nombres_apellidos'] ?? 'Colaborador'); ?>
                            </h1>

                            <div class="mt-4 flex flex-wrap justify-center md:justify-start gap-2">
                                <span class="bg-red-50 text-red-900 px-3 py-1.5 rounded-xl text-xs font-bold uppercase tracking-widest border border-red-100">
                                    <?php echo htmlspecialchars($data['puesto_cas'] ?? 'Sin puesto'); ?>
                                </span>

                                <span class="bg-slate-50 text-slate-600 px-3 py-1.5 rounded-xl text-xs font-bold uppercase tracking-widest border border-slate-200">
                                    <?php echo htmlspecialchars($data['area'] ?? 'Sin área'); ?>
                                </span>

                                <?php if ($tieneTeletrabajoActivo): ?>
                                    <span class="bg-blue-50 text-blue-700 px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-widest border border-blue-100">
                                        Remoto temporal
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Acción -->
                        <div class="shrink-0 flex flex-col sm:flex-row gap-3">
                            <button onclick="abrirModalClave()"
                                class="inline-flex items-center justify-center gap-2 bg-slate-900 text-white px-6 py-3 rounded-2xl font-bold hover:bg-slate-800 transition-all shadow-lg shadow-slate-900/20 active:scale-95">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V7a4.5 4.5 0 00-9 0v3.5m-.75 0h10.5A1.75 1.75 0 0119 12.25v6A1.75 1.75 0 0117.25 20H6.75A1.75 1.75 0 015 18.25v-6a1.75 1.75 0 011.75-1.75z" />
                                </svg>
                                Cambiar Clave
                            </button>

                            <button onclick="abrirModal()"
                                class="inline-flex items-center justify-center gap-2 bg-red-900 text-white px-6 py-3 rounded-2xl font-bold hover:bg-[#310404] transition-all shadow-lg shadow-red-900/20 active:scale-95">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                Editar Perfil
                            </button>
                        </div>
                    </div>

                    <!-- Tabs de navegación -->
                    <div class="flex items-center gap-8 mt-10 mb-7 border-t border-slate-100 pt-2 overflow-x-auto no-scrollbar">
                        <button onclick="switchTab('resumen')" id="btn-resumen" class="tab-btn tab-active px-2 py-4 text-sm font-bold whitespace-nowrap">
                            RESUMEN
                        </button>

                        <button onclick="switchTab('informacion')" id="btn-informacion" class="tab-btn tab-idle px-2 py-4 text-sm font-bold whitespace-nowrap">
                            INFORMACIÓN
                        </button>

                        <button onclick="switchTab('laboral')" id="btn-laboral" class="tab-btn tab-idle px-2 py-4 text-sm font-bold whitespace-nowrap">
                            LABORAL
                        </button>

                        <button onclick="switchTab('formacion')" id="btn-formacion" class="tab-btn tab-idle px-2 py-4 text-sm font-bold whitespace-nowrap">
                            FORMACIÓN
                        </button>

                        <button onclick="switchTab('experiencia')" id="btn-experiencia" class="tab-btn tab-idle px-2 py-4 text-sm font-bold whitespace-nowrap">
                            EXPERIENCIA
                        </button>
                    </div>


                    <!-- ============================================================
    TAB 1: RESUMEN PERFIL
============================================================ -->
                    <div id="tab-resumen" class="tab-content block animate-fadeIn">

                        <?php
                        $contratosResumen = organizarContratosConAdendas($contratos);
                        $contratoActualResumen = $contratosResumen[0] ?? null;

                        $inicioContratoResumen = $contratoActualResumen ? obtenerInicioPeriodoContrato($contratoActualResumen) : null;
                        $finContratoResumen = $contratoActualResumen ? obtenerVigenciaFinalContrato($contratoActualResumen) : null;
                        $estadoContratoResumen = $contratoActualResumen ? obtenerEstadoVisualContrato($contratoActualResumen) : null;
                        $totalAdendasResumen = $contratoActualResumen ? count($contratoActualResumen['_adendas'] ?? []) : 0;

                        $sexo = $data['sexo'] ?? '';
                        $sexoTexto = $sexo === 'M' ? 'Masculino' : ($sexo === 'F' ? 'Femenino' : '—');

                        $situacion = strtoupper(trim((string)($data['situacion'] ?? '')));
                        $situacionColor = match ($situacion) {
                            'ACTIVO' => 'bg-green-50 text-green-700 border-green-100',
                            default  => 'bg-red-50 text-red-700 border-red-100',
                        };
                        ?>

                        <div class="space-y-6">

                            <!-- DATOS PRINCIPALES + FAMILIA -->
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                                <!-- Datos personales -->
                                <div class="lg:col-span-2 bg-white p-7 rounded-[32px] shadow-sm border border-slate-200/80">
                                    <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                        <span class="w-1.5 h-5 bg-red-800 rounded-full"></span>
                                        Datos Personales
                                    </h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">

                                        <div class="space-y-4">
                                            <div class="flex justify-between gap-4 border-b border-slate-50 pb-2">
                                                <span class="text-slate-400 font-medium">DNI</span>
                                                <span class="font-black text-red-950 text-right">
                                                    <?php echo htmlspecialchars($data['dni'] ?? '—'); ?>
                                                </span>
                                            </div>

                                            <div class="flex justify-between gap-4 border-b border-slate-50 pb-2">
                                                <span class="text-slate-400 font-medium">Fecha Nac.</span>
                                                <span class="font-bold text-slate-700 text-right">
                                                    <?php echo formatFecha($data['fecha_nacimiento'] ?? null); ?>
                                                </span>
                                            </div>

                                            <div class="flex justify-between gap-4 border-b border-slate-50 pb-2">
                                                <span class="text-slate-400 font-medium">Lugar Nac.</span>
                                                <span class="font-bold text-slate-700 text-right">
                                                    <?php echo htmlspecialchars($data['lugar_nacimiento'] ?? '—'); ?>
                                                </span>
                                            </div>

                                            <div class="flex justify-between gap-4 border-b border-slate-50 pb-2">
                                                <span class="text-slate-400 font-medium">Situación</span>
                                                <span class="inline-flex px-3 py-1 rounded-xl border text-[10px] font-black uppercase tracking-widest <?php echo $situacionColor; ?>">
                                                    <?php echo htmlspecialchars($data['situacion'] ?? '—'); ?>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <div class="flex justify-between gap-4 border-b border-slate-50 pb-2">
                                                <span class="text-slate-400 font-medium">Edad</span>
                                                <span class="font-black text-red-950 text-right">
                                                    <?php echo calcularEdad($data['fecha_nacimiento'] ?? null); ?>
                                                </span>
                                            </div>

                                            <div class="flex justify-between gap-4 border-b border-slate-50 pb-2">
                                                <span class="text-slate-400 font-medium">Estado Civil</span>
                                                <span class="font-bold text-slate-700 text-right">
                                                    <?php echo htmlspecialchars($data['estado_civil'] ?? '—'); ?>
                                                </span>
                                            </div>

                                            <div class="flex justify-between gap-4 border-b border-slate-50 pb-2">
                                                <span class="text-slate-400 font-medium">Sexo</span>
                                                <span class="font-bold text-slate-700 text-right">
                                                    <?php echo $sexoTexto; ?>
                                                </span>
                                            </div>

                                            <div class="flex justify-between gap-4 border-b border-slate-50 pb-2">
                                                <span class="text-slate-400 font-medium">Grupo Sanguíneo</span>
                                                <span class="font-black text-red-950 text-right">
                                                    <?php echo htmlspecialchars($data['grupo_sanguineo'] ?? '—'); ?>
                                                </span>
                                            </div>

                                            <div class="flex justify-between gap-4 border-b border-slate-50 pb-2">
                                                <span class="text-slate-400 font-medium">Talla</span>
                                                <span class="font-bold text-slate-700 text-right">
                                                    <?php echo htmlspecialchars($data['talla'] ?? '—'); ?>
                                                </span>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <!-- Familia -->
                                <div class="bg-white p-7 rounded-[32px] shadow-sm border border-slate-200/80">
                                    <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                        <span class="w-1.5 h-5 bg-red-800 rounded-full"></span>
                                        Familia
                                    </h3>

                                    <div class="space-y-4 text-sm">

                                        <div class="p-4 bg-red-50 rounded-2xl border border-red-100">
                                            <p class="text-[10px] font-black text-red-500 uppercase tracking-widest mb-1">
                                                Cónyuge
                                            </p>

                                            <p class="font-black text-red-950 leading-tight">
                                                <?php echo htmlspecialchars(($data['conyuge'] ?? '') ?: 'No registrado'); ?>
                                            </p>

                                            <p class="text-xs font-bold text-red-700 mt-2">
                                                <?php echo !empty($data['onomastico_conyuge']) ? formatFecha($data['onomastico_conyuge']) : 'Fecha no registrada'; ?>
                                            </p>
                                        </div>

                                        <div class="flex justify-between items-center p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                            <span class="text-slate-500 font-bold">Hijos registrados</span>
                                            <span class="text-red-950 text-2xl font-black">
                                                <?php echo (int)($data['n_hijos'] ?? 0); ?>
                                            </span>
                                        </div>

                                        <div class="flex justify-between items-center p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                            <span class="text-slate-500 font-bold">DNI cónyuge</span>
                                            <span class="text-slate-700 font-black">
                                                <?php echo htmlspecialchars($data['dni_conyuge'] ?? '—'); ?>
                                            </span>
                                        </div>

                                    </div>
                                </div>

                            </div>

                            <!-- CONTACTO + LABORAL -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                                <!-- Contacto -->
                                <div class="bg-white p-7 rounded-[32px] shadow-sm border border-slate-200/80">
                                    <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                        <span class="w-1.5 h-5 bg-red-800 rounded-full"></span>
                                        Contacto y Domicilio
                                    </h3>

                                    <div class="space-y-4 text-sm">

                                        <div class="p-5 bg-slate-50 rounded-2xl border border-slate-100">
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">
                                                Dirección
                                            </p>

                                            <p class="font-bold text-slate-700 leading-tight">
                                                <?php echo htmlspecialchars($data['direccion_residencia'] ?? 'No registrada'); ?>
                                            </p>

                                            <p class="text-xs text-red-900 font-black mt-2">
                                                <?php echo htmlspecialchars($data['distrito'] ?? '—'); ?>
                                            </p>
                                        </div>

                                        <div class="flex justify-between gap-4 border-b border-slate-50 pb-2">
                                            <span class="text-slate-400 font-medium">Celular</span>
                                            <span class="font-black text-red-950 text-right">
                                                <?php echo htmlspecialchars($data['celular'] ?? '—'); ?>
                                            </span>
                                        </div>

                                        <div class="flex justify-between gap-4 border-b border-slate-50 pb-2">
                                            <span class="text-slate-400 font-medium">Correo Personal</span>
                                            <span class="font-bold text-slate-700 text-right break-all">
                                                <?php echo htmlspecialchars($data['correo_personal'] ?? '—'); ?>
                                            </span>
                                        </div>

                                        <div class="flex justify-between gap-4">
                                            <span class="text-slate-400 font-medium">Correo Inst.</span>
                                            <span class="font-black text-red-950 text-right break-all">
                                                <?php echo htmlspecialchars($data['correo_institucional'] ?? '—'); ?>
                                            </span>
                                        </div>

                                    </div>
                                </div>

                                <!-- Datos laborales -->
                                <div class="bg-white p-7 rounded-[32px] shadow-sm border border-slate-200/80">
                                    <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                        <span class="w-1.5 h-5 bg-red-800 rounded-full"></span>
                                        Datos Laborales
                                    </h3>

                                    <div class="space-y-4 text-sm">

                                        <?php if ($esEditable): ?>
                                            <div class="flex justify-between items-center bg-red-50 p-3 rounded-xl border border-red-100">
                                                <span class="text-red-500 font-bold">Sueldo</span>

                                                <div class="flex items-center gap-2">
                                                    <span id="sueldo-texto" class="font-black text-red-950 text-base tracking-widest">
                                                        *****
                                                    </span>

                                                    <button type="button" onclick="toggleSueldo()" class="text-red-400 hover:text-red-900 transition">
                                                        <svg id="icono-ojo" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path id="ojo-abierto" stroke-linecap="round" stroke-linejoin="round"
                                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path id="ojo-linea" stroke-linecap="round" stroke-linejoin="round"
                                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-.274.847-.66 1.647-1.143 2.379M15 12a3 3 0 00-6 0" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>

                                            <input type="hidden" id="sueldo-real"
                                                value="<?php echo !empty($data['sueldo']) ? number_format($data['sueldo'], 2) : '0.00'; ?>">
                                        <?php endif; ?>

                                        <div class="flex justify-between gap-4 border-b border-slate-50 pb-2">
                                            <span class="text-slate-400 font-medium">Puesto CAS</span>
                                            <span class="font-black text-red-950 text-right">
                                                <?php echo htmlspecialchars($data['puesto_cas'] ?? '—'); ?>
                                            </span>
                                        </div>

                                        <div class="flex justify-between gap-4 border-b border-slate-50 pb-2">
                                            <span class="text-slate-400 font-medium">Área</span>
                                            <span class="font-bold text-slate-700 text-right">
                                                <?php echo htmlspecialchars($data['area'] ?? '—'); ?>
                                            </span>
                                        </div>

                                        <div class="flex justify-between gap-4 border-b border-slate-50 pb-2">
                                            <span class="text-slate-400 font-medium">Contrato</span>
                                            <span class="font-black text-red-950 text-right">
                                                <?php echo htmlspecialchars($data['mod_contrato'] ?? '—'); ?>
                                            </span>
                                        </div>

                                        <div class="flex justify-between gap-4 border-b border-slate-50 pb-2">
                                            <span class="text-slate-400 font-medium">Tipo Puesto</span>
                                            <span class="font-bold text-slate-700 text-right">
                                                <?php echo htmlspecialchars($data['tipo_puesto'] ?? '—'); ?>
                                            </span>
                                        </div>

                                        <div class="flex justify-between gap-4 border-b border-slate-50 pb-2">
                                            <span class="text-slate-400 font-medium">Procedencia</span>
                                            <span class="font-bold text-slate-700 text-right">
                                                <?php echo htmlspecialchars($data['procedencia'] ?? '—'); ?>
                                            </span>
                                        </div>

                                        <div class="flex justify-between gap-4">
                                            <span class="text-slate-400 font-medium">NSA / CIP</span>
                                            <span class="font-bold text-slate-700 text-right">
                                                <?php echo htmlspecialchars($data['nsa_cip'] ?? '—'); ?>
                                            </span>
                                        </div>

                                    </div>
                                </div>

                            </div>

                            <!-- CONTRATO ACTUAL -->
                            <div class="bg-white p-7 rounded-[32px] shadow-sm border border-slate-200/80">
                                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
                                    <div>
                                        <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                                            <span class="w-1.5 h-5 bg-red-800 rounded-full"></span>
                                            Contrato Vigente o Más Reciente
                                        </h3>

                                        <p class="text-xs text-slate-400 font-semibold mt-1">
                                            Resumen compacto del último periodo contractual.
                                        </p>
                                    </div>

                                    <?php if ($estadoContratoResumen): ?>
                                        <span class="inline-flex self-start px-3 py-1.5 rounded-xl bg-red-950 text-white text-[10px] font-black uppercase tracking-widest shadow-sm">
                                            <?php echo htmlspecialchars($estadoContratoResumen['texto']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if (empty($contratoActualResumen)): ?>

                                    <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 py-10 text-center">
                                        <p class="text-slate-400 text-sm font-semibold">
                                            No hay contratos registrados.
                                        </p>
                                    </div>

                                <?php else: ?>

                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">

                                        <div class="md:col-span-2 rounded-2xl bg-red-50 border border-red-100 p-4">
                                            <p class="text-[10px] font-black uppercase tracking-widest text-red-500 mb-1">
                                                Documento contractual
                                            </p>

                                            <p class="font-black text-red-950 leading-tight">
                                                <?php echo htmlPerfil($contratoActualResumen['numero_documento'] ?: 'Contrato sin número/documento'); ?>
                                            </p>
                                        </div>

                                        <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                                                Inicio
                                            </p>

                                            <p class="font-black text-slate-700">
                                                <?php echo !empty($inicioContratoResumen) ? formatFecha($inicioContratoResumen) : '—'; ?>
                                            </p>
                                        </div>

                                        <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                                                Fin
                                            </p>

                                            <p class="font-black text-red-950">
                                                <?php echo !empty($finContratoResumen) ? formatFecha($finContratoResumen) : 'Vigente'; ?>
                                            </p>
                                        </div>

                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-[11px] font-bold text-slate-600">
                                            Modalidad:
                                            <strong class="ml-1 text-red-950">
                                                <?php echo htmlPerfil($contratoActualResumen['modalidad'] ?? '—'); ?>
                                            </strong>
                                        </span>

                                        <span class="inline-flex items-center px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-[11px] font-bold text-slate-600">
                                            <?php echo $totalAdendasResumen; ?> adenda(s)
                                        </span>

                                        <?php if ($tieneTeletrabajoActivo): ?>
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-xl bg-blue-50 border border-blue-100 text-[11px] font-black text-blue-700">
                                                Remoto temporal
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                <?php endif; ?>
                            </div>

                        </div>
                    </div>

                    <!-- ============================================================
                    TAB 2: INFORMACIÓN
                    ============================================================ -->
                    <div id="tab-informacion" class="tab-content hidden animate-fadeIn">
                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

                            <!-- Datos personales -->
                            <div class="bg-white p-8 rounded-[32px] shadow-sm border border-slate-200/80">
                                <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                    <span class="w-1.5 h-5 bg-red-800 rounded-full"></span>
                                    Datos Personales
                                </h3>

                                <div class="space-y-4 text-sm">
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">DNI</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['dni'] ?? '—'); ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Edad</span>
                                        <span class="font-bold text-slate-700"><?php echo calcularEdad($data['fecha_nacimiento'] ?? null); ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Fecha Nacimiento</span>
                                        <span class="font-bold text-slate-700"><?php echo formatFecha($data['fecha_nacimiento'] ?? null); ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Lugar Nacimiento</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['lugar_nacimiento'] ?? '—'); ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Estado Civil</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['estado_civil'] ?? '—'); ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Sexo</span>
                                        <span class="font-bold text-slate-700">
                                            <?php
                                            $sexo = $data['sexo'] ?? '';
                                            echo $sexo === 'M' ? 'Masculino' : ($sexo === 'F' ? 'Femenino' : '—');
                                            ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Grupo Sanguíneo</span>
                                        <span class="font-bold text-red-700"><?php echo htmlspecialchars($data['grupo_sanguineo'] ?? '—'); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-400 font-medium">Talla</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['talla'] ?? '—'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Contacto -->
                            <div class="bg-white p-8 rounded-[32px] shadow-sm border border-slate-200/80">
                                <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                    <span class="w-1.5 h-5 bg-red-800 rounded-full"></span>
                                    Contacto y Domicilio
                                </h3>

                                <div class="space-y-4 text-sm">
                                    <div class="border-b border-slate-50 pb-3">
                                        <p class="text-slate-400 font-medium mb-1">Dirección</p>
                                        <p class="font-bold text-slate-700"><?php echo htmlspecialchars($data['direccion_residencia'] ?? 'No registrada'); ?></p>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Distrito</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['distrito'] ?? '—'); ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Celular</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['celular'] ?? '—'); ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2 gap-4">
                                        <span class="text-slate-400 font-medium">Correo Personal</span>
                                        <span class="font-bold text-slate-700 text-right break-all"><?php echo htmlspecialchars($data['correo_personal'] ?? '—'); ?></span>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <span class="text-slate-400 font-medium">Correo Institucional</span>
                                        <span class="font-bold text-red-900 text-right break-all"><?php echo htmlspecialchars($data['correo_institucional'] ?? '—'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Cónyuge -->
                            <div class="bg-white p-8 rounded-[32px] shadow-sm border border-slate-200/80">
                                <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                    <span class="w-1.5 h-5 bg-red-600 rounded-full"></span>
                                    Cónyuge
                                </h3>

                                <div class="space-y-4 text-sm">
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Nombre</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['conyuge'] ?? 'No registrado'); ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Fecha Nacimiento</span>
                                        <span class="font-bold text-slate-700"><?php echo !empty($data['onomastico_conyuge']) ? formatFecha($data['onomastico_conyuge']) : '—'; ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-400 font-medium">DNI</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['dni_conyuge'] ?? '—'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Hijos -->
                            <div class="bg-white p-8 rounded-[32px] shadow-sm border border-slate-200/80">
                                <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                    <span class="w-1.5 h-5 bg-red-800 rounded-full"></span>
                                    Hijos
                                    <span class="ml-auto bg-slate-100 text-slate-500 text-xs font-bold px-2 py-1 rounded-lg">
                                        <?php echo count($hijos); ?> registro(s)
                                    </span>
                                </h3>

                                <?php if (empty($hijos)): ?>
                                    <p class="text-slate-400 text-sm text-center py-6">No hay hijos registrados.</p>
                                <?php else: ?>
                                    <div class="space-y-3">
                                        <?php foreach ($hijos as $i => $hijo): ?>
                                            <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50">
                                                <div class="flex items-start justify-between gap-4">
                                                    <div>
                                                        <p class="font-black text-slate-800"><?php echo htmlspecialchars($hijo['nombre_completo'] ?? 'Sin nombre'); ?></p>
                                                        <p class="text-xs text-slate-500 mt-1">
                                                            <?php echo htmlspecialchars($hijo['parentesco'] ?? 'HIJO'); ?>
                                                        </p>
                                                    </div>
                                                    <span class="text-xs font-bold text-red-900 bg-red-50 border border-red-100 px-2 py-1 rounded-lg">
                                                        <?php echo htmlspecialchars($hijo['dni_familiar'] ?? '—'); ?>
                                                    </span>
                                                </div>

                                                <div class="mt-3 text-xs text-slate-500">
                                                    Fecha Nacimiento:
                                                    <span class="font-bold text-slate-700">
                                                        <?php echo !empty($hijo['fecha_nacimiento']) ? formatFecha($hijo['fecha_nacimiento']) : '—'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>

                    <!-- ============================================================
                    TAB 3: LABORAL
                     ============================================================ -->
                    <div id="tab-laboral" class="tab-content hidden animate-fadeIn">
                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

                            <!-- Resumen laboral -->
                            <div class="bg-white p-8 rounded-[32px] shadow-sm border border-slate-200/80">
                                <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                    <span class="w-1.5 h-5 bg-red-800 rounded-full"></span>
                                    Datos Laborales
                                </h3>

                                <div class="space-y-4 text-sm">
                                    <div class="flex justify-between items-center bg-slate-50 p-3 rounded-xl border border-slate-100">
                                        <span class="text-slate-400 font-medium">Sueldo</span>
                                        <span class="font-black text-red-900 text-base">
                                            S/ <?php echo !empty($data['sueldo']) ? number_format((float)$data['sueldo'], 2) : '0.00'; ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Puesto CAS</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['puesto_cas'] ?? '—'); ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Área</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['area'] ?? '—'); ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Contrato</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['mod_contrato'] ?? '—'); ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Condición temporal</span>

                                        <?php if ($tieneTeletrabajoActivo): ?>
                                            <span class="font-black text-blue-700">Trabajo remoto temporal</span>
                                        <?php else: ?>
                                            <span class="font-bold text-slate-400">No registra</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($teletrabajoActual)): ?>
                                        <div class="flex justify-between border-b border-slate-50 pb-2">
                                            <span class="text-slate-400 font-medium">Vigencia remoto</span>
                                            <span class="font-bold text-slate-700">
                                                <?php echo formatFecha($teletrabajoActual['fecha_inicio'] ?? null); ?>
                                                -
                                                <?php echo formatFecha($teletrabajoActual['fecha_fin'] ?? null); ?>
                                            </span>
                                        </div>

                                        <div class="flex justify-between border-b border-slate-50 pb-2">
                                            <span class="text-slate-400 font-medium">Documento remoto</span>
                                            <span class="font-bold text-slate-700 text-right">
                                                <?php echo htmlspecialchars($teletrabajoActual['numero_documento'] ?? '—'); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Tipo Puesto</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['tipo_puesto'] ?? '—'); ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Procedencia</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['procedencia'] ?? '—'); ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">NSA / CIP</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['nsa_cip'] ?? '—'); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-400 font-medium">Situación</span>
                                        <span class="font-black <?php echo strtoupper($data['situacion'] ?? '') === 'ACTIVO' ? 'text-green-700' : 'text-red-700'; ?>">
                                            <?php echo htmlspecialchars($data['situacion'] ?? '—'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Pensión -->
                            <div class="bg-white p-8 rounded-[32px] shadow-sm border border-slate-200/80">
                                <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                    <span class="w-1.5 h-5 bg-red-800 rounded-full"></span>
                                    Sistema de Pensiones
                                </h3>

                                <div class="space-y-4 text-sm">
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Sistema</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($pension['sistema_pension'] ?? '—'); ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">AFP</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($pension['afp'] ?? '—'); ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">CUSPP</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($pension['cuspp'] ?? '—'); ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Tipo Comisión</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($pension['tipo_comision'] ?? '—'); ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Fecha Inscripción</span>
                                        <span class="font-bold text-slate-700"><?php echo !empty($pension['fecha_inscripcion']) ? formatFecha($pension['fecha_inscripcion']) : '—'; ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-400 font-medium">Sin AFP / Afiliarme</span>
                                        <span class="font-bold text-slate-700"><?php echo !empty($pension['sin_afp_afiliarme']) ? 'Sí' : 'No'; ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Bancario -->
                            <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 xl:col-span-2">
                                <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                    <span class="w-1.5 h-5 bg-red-800 rounded-full"></span>
                                    Datos Bancarios
                                </h3>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                    <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4">
                                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Banco Haberes</p>
                                        <p class="font-bold text-slate-700 break-words"><?php echo htmlspecialchars($bancario['banco_haberes'] ?? '—'); ?></p>
                                    </div>
                                    <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4">
                                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Número de Cuenta</p>
                                        <p class="font-bold text-slate-700 break-words"><?php echo htmlspecialchars($bancario['numero_cuenta'] ?? '—'); ?></p>
                                    </div>
                                    <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4">
                                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">CCI</p>
                                        <p class="font-bold text-slate-700 break-words"><?php echo htmlspecialchars($bancario['numero_cuenta_cci'] ?? '—'); ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Contratos -->
                            <div class="xl:col-span-2">
                                <?php renderHistorialContratos($contratos); ?>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================
    TAB 4: FORMACIÓN ACADÉMICA
============================================================ -->
                    <div id="tab-formacion" class="tab-content hidden animate-fadeIn">
                        <div class="bg-white p-8 rounded-[32px] shadow-sm border border-slate-200/80">

                            <h3 class="text-xl font-black text-slate-800 mb-8 flex items-center gap-2">
                                <span class="w-1.5 h-6 bg-red-900 rounded-full"></span>
                                Formación Académica

                                <?php if (!empty($formacion)): ?>
                                    <span class="ml-auto bg-red-50 text-red-800 text-xs font-bold px-2 py-1 rounded-lg border border-red-100">
                                        <?php echo count($formacion); ?> registro(s)
                                    </span>
                                <?php endif; ?>
                            </h3>

                            <?php if (empty($formacion)): ?>

                                <div class="text-center py-16">
                                    <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl">
                                        🎓
                                    </div>

                                    <h4 class="text-lg font-black text-slate-700 mb-2">
                                        Sin formación registrada
                                    </h4>

                                    <p class="text-slate-400 max-w-sm mx-auto text-sm">
                                        Aún no se ha registrado información académica para este colaborador.
                                    </p>
                                </div>

                            <?php else: ?>

                                <div class="relative pl-8 border-l-2 border-red-100 space-y-5">

                                    <?php foreach ($formacion as $i => $item): ?>
                                        <?php
                                        $tipo = strtoupper(trim((string)($item['tipo_grado'] ?? '')));

                                        $dotColor = match (true) {
                                            str_contains($tipo, 'DOCTOR')    => 'bg-slate-900',
                                            str_contains($tipo, 'MAESTR')    => 'bg-red-950',
                                            str_contains($tipo, 'TÍTULO')    => 'bg-red-900',
                                            str_contains($tipo, 'TITULO')    => 'bg-red-900',
                                            str_contains($tipo, 'BACHILLER') => 'bg-red-800',
                                            str_contains($tipo, 'TECN')      => 'bg-slate-700',
                                            str_contains($tipo, 'ESPECIAL')  => 'bg-slate-800',
                                            default                          => 'bg-red-900',
                                        };

                                        $badgeColor = match (true) {
                                            str_contains($tipo, 'DOCTOR')    => 'bg-slate-100 text-slate-800 border-slate-200',
                                            str_contains($tipo, 'MAESTR')    => 'bg-red-50 text-red-950 border-red-100',
                                            str_contains($tipo, 'TÍTULO')    => 'bg-red-50 text-red-900 border-red-100',
                                            str_contains($tipo, 'TITULO')    => 'bg-red-50 text-red-900 border-red-100',
                                            str_contains($tipo, 'BACHILLER') => 'bg-red-50 text-red-800 border-red-100',
                                            str_contains($tipo, 'TECN')      => 'bg-slate-100 text-slate-700 border-slate-200',
                                            str_contains($tipo, 'ESPECIAL')  => 'bg-slate-100 text-slate-800 border-slate-200',
                                            default                          => 'bg-red-50 text-red-900 border-red-100',
                                        };

                                        $estadoValidacion = strtoupper(trim((string)($item['estado_validacion'] ?? '')));
                                        $idFormacion = 'formacion-detalle-' . $i;
                                        ?>

                                        <div class="relative">
                                            <div class="absolute -left-[41px] top-5 w-5 h-5 rounded-full <?php echo $dotColor; ?> border-4 border-white shadow-sm"></div>

                                            <div class="bg-slate-50 border border-slate-200 rounded-2xl overflow-hidden">

                                                <!-- Cabecera compacta -->
                                                <div class="p-5">
                                                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                                                        <div class="min-w-0">

                                                            <?php if (!empty($item['tipo_grado'])): ?>
                                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border mb-2 <?php echo $badgeColor; ?>">
                                                                    <?php echo htmlspecialchars($item['tipo_grado']); ?>
                                                                </span>
                                                            <?php endif; ?>

                                                            <h4 class="text-base font-black text-slate-800 leading-tight">
                                                                <?php echo htmlspecialchars($item['descripcion_carrera'] ?? 'No registrado'); ?>
                                                            </h4>

                                                            <p class="text-sm font-bold text-red-900 mt-1">
                                                                <?php echo htmlspecialchars($item['institucion'] ?? 'Institución no registrada'); ?>
                                                            </p>

                                                            <div class="mt-2 flex flex-wrap gap-2">
                                                                <?php if (!empty($item['anio_realizacion'])): ?>
                                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-[11px] font-bold text-slate-600">
                                                                        Año:
                                                                        <strong class="ml-1 text-slate-800">
                                                                            <?php echo htmlspecialchars($item['anio_realizacion']); ?>
                                                                        </strong>
                                                                    </span>
                                                                <?php endif; ?>

                                                                <?php if (!empty($item['horas_lectivas'])): ?>
                                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-[11px] font-bold text-slate-600">
                                                                        Horas:
                                                                        <strong class="ml-1 text-slate-800">
                                                                            <?php echo htmlspecialchars($item['horas_lectivas']); ?>
                                                                        </strong>
                                                                    </span>
                                                                <?php endif; ?>

                                                                <?php if (!empty($item['grado_alcanzado'])): ?>
                                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-red-50 border border-red-100 text-[11px] font-black text-red-900">
                                                                        <?php echo htmlspecialchars($item['grado_alcanzado']); ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>

                                                        <div class="flex flex-wrap items-center gap-2 shrink-0">

                                                            <?php if (!empty($estadoValidacion) && $estadoValidacion !== 'PENDIENTE'): ?>
                                                                <span class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-lg border
                                                <?php echo $estadoValidacion === 'APROBADO'
                                                                    ? 'bg-green-50 text-green-700 border-green-200'
                                                                    : 'bg-red-50 text-red-700 border-red-200'; ?>">
                                                                    <?php echo htmlspecialchars($estadoValidacion); ?>
                                                                </span>
                                                            <?php endif; ?>

                                                            <button
                                                                type="button"
                                                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-100 transition-all"
                                                                onclick="toggleFormacionDetalle('<?php echo $idFormacion; ?>', this)">
                                                                <span class="form-toggle-text">Ver más</span>

                                                                <svg class="form-toggle-icon w-4 h-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                                </svg>
                                                            </button>

                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Detalle desplegable -->
                                                <div id="<?php echo $idFormacion; ?>" class="hidden border-t border-slate-200 bg-white">
                                                    <div class="p-5">

                                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                                                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                                                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                                                                    Tipo de grado
                                                                </p>

                                                                <p class="text-sm font-bold text-slate-700">
                                                                    <?php echo htmlspecialchars($item['tipo_grado'] ?? '—'); ?>
                                                                </p>
                                                            </div>

                                                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                                                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                                                                    Año de realización
                                                                </p>

                                                                <p class="text-sm font-bold text-slate-700">
                                                                    <?php echo htmlspecialchars($item['anio_realizacion'] ?? '—'); ?>
                                                                </p>
                                                            </div>

                                                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                                                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                                                                    Horas lectivas
                                                                </p>

                                                                <p class="text-sm font-bold text-slate-700">
                                                                    <?php echo htmlspecialchars($item['horas_lectivas'] ?? '—'); ?>
                                                                </p>
                                                            </div>

                                                        </div>

                                                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">

                                                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                                                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                                                                    Institución
                                                                </p>

                                                                <p class="text-sm font-bold text-slate-700 leading-relaxed">
                                                                    <?php echo htmlspecialchars($item['institucion'] ?? '—'); ?>
                                                                </p>
                                                            </div>

                                                            <div class="bg-red-50 border border-red-100 rounded-xl p-4">
                                                                <p class="text-[10px] font-black uppercase tracking-widest text-red-400 mb-1">
                                                                    Grado alcanzado
                                                                </p>

                                                                <p class="text-sm font-black text-red-950 leading-relaxed">
                                                                    <?php echo htmlspecialchars($item['grado_alcanzado'] ?? '—'); ?>
                                                                </p>
                                                            </div>

                                                        </div>

                                                        <?php if (!empty($item['especialidad'])): ?>
                                                            <div class="mt-4">
                                                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">
                                                                    Especialidad
                                                                </p>

                                                                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm text-slate-700 leading-relaxed font-semibold">
                                                                    <?php echo nl2br(htmlspecialchars($item['especialidad'])); ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>

                                                    </div>
                                                </div>

                                            </div>
                                        </div>

                                    <?php endforeach; ?>

                                </div>

                            <?php endif; ?>

                            <!-- IDIOMAS COMPACTOS -->
                            <?php $idiomas = $perfil['idiomas'] ?? []; ?>

                            <?php if (!empty($idiomas)): ?>
                                <div class="mt-10 pt-8 border-t border-slate-200">
                                    <h3 class="text-xl font-black text-slate-800 mb-6 flex items-center gap-2">
                                        <span class="w-1.5 h-6 bg-red-800 rounded-full"></span>
                                        Idiomas

                                        <span class="ml-auto bg-slate-50 text-slate-700 text-xs font-bold px-2 py-1 rounded-lg border border-slate-200">
                                            <?php echo count($idiomas); ?> registro(s)
                                        </span>
                                    </h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <?php foreach ($idiomas as $idioma): ?>
                                            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5">
                                                <div class="flex items-center justify-between gap-4">
                                                    <div>
                                                        <p class="text-base font-black text-slate-800">
                                                            <?php echo htmlspecialchars($idioma['idioma'] ?? 'Sin idioma'); ?>
                                                        </p>

                                                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mt-1">
                                                            Nivel
                                                        </p>
                                                    </div>

                                                    <span class="inline-flex px-3 py-1.5 rounded-xl bg-red-50 border border-red-100 text-[11px] font-black text-red-900">
                                                        <?php echo htmlspecialchars($idioma['nivel'] ?? 'BASICO'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>

                    <!-- ============================================================
                        TAB 3: EXPERIENCIA LABORAL
                    ============================================================ -->
                    <div id="tab-experiencia" class="tab-content hidden animate-fadeIn">
                        <div class="bg-white p-8 rounded-[32px] shadow-sm border border-slate-200/80">
                            <h3 class="text-xl font-black text-slate-800 mb-8 flex items-center gap-2">
                                <span class="w-1.5 h-6 bg-red-900 rounded-full"></span>
                                Experiencia Laboral
                                <?php if (!empty($perfil['experiencia'])): ?>
                                    <span class="ml-auto bg-red-50 text-red-800 text-xs font-bold px-2 py-1 rounded-lg border border-red-100">
                                        <?php echo count($perfil['experiencia']); ?> registro(s)
                                    </span>
                                <?php endif; ?>
                            </h3>

                            <?php if (empty($perfil['experiencia'])): ?>
                                <div class="text-center py-16">
                                    <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl">💼</div>
                                    <h4 class="text-lg font-black text-slate-700 mb-2">Sin experiencia registrada</h4>
                                    <p class="text-slate-400 max-w-sm mx-auto text-sm">
                                        Aún no se ha registrado experiencia laboral para este colaborador.
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="relative pl-8 border-l-2 border-red-100 space-y-5">
                                    <?php foreach ($perfil['experiencia'] as $i => $item): ?>
                                        <?php
                                        $inicio = !empty($item['fecha_inicio']) ? new DateTime($item['fecha_inicio']) : null;
                                        $fin = !empty($item['fecha_fin'])
                                            ? new DateTime($item['fecha_fin'])
                                            : (($item['actualmente_trabaja'] ?? 0) ? new DateTime() : null);

                                        $tiempoServicio = '—';
                                        if ($inicio && $fin && $inicio <= $fin) {
                                            $diff = $inicio->diff($fin);
                                            $partes = [];
                                            if ($diff->y > 0) $partes[] = $diff->y . ' año' . ($diff->y > 1 ? 's' : '');
                                            if ($diff->m > 0) $partes[] = $diff->m . ' mes' . ($diff->m > 1 ? 'es' : '');
                                            if ($diff->d > 0) $partes[] = $diff->d . ' día' . ($diff->d > 1 ? 's' : '');
                                            $tiempoServicio = !empty($partes) ? implode(', ', $partes) : '0 días';
                                        }

                                        $idExp = 'exp-detalle-' . $i;
                                        ?>
                                        <div class="relative">
                                            <div class="absolute -left-[41px] top-5 w-5 h-5 rounded-full bg-red-900 border-4 border-white shadow-sm"></div>

                                            <div class="bg-slate-50 border border-slate-200 rounded-2xl overflow-hidden">
                                                <!-- Cabecera compacta -->
                                                <div class="p-5">
                                                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                                        <div class="min-w-0">
                                                            <h4 class="text-base font-black text-slate-800 leading-tight">
                                                                <?php echo htmlspecialchars($item['cargo_puesto'] ?? 'Sin cargo'); ?>
                                                            </h4>

                                                            <p class="text-sm font-bold text-red-900 mt-1">
                                                                <?php echo htmlspecialchars($item['empresa_entidad'] ?? 'Sin empresa'); ?>
                                                            </p>

                                                            <p class="text-xs text-slate-500 mt-1">
                                                                <?php echo !empty($item['fecha_inicio']) ? formatFecha($item['fecha_inicio']) : '—'; ?>
                                                                —
                                                                <?php echo !empty($item['actualmente_trabaja']) ? 'Actualidad' : (!empty($item['fecha_fin']) ? formatFecha($item['fecha_fin']) : '—'); ?>
                                                            </p>

                                                            <?php if (!empty($item['unidad_organica_area'])): ?>
                                                                <p class="text-xs text-slate-400 mt-1">
                                                                    <?php echo htmlspecialchars($item['unidad_organica_area']); ?>
                                                                </p>
                                                            <?php endif; ?>
                                                        </div>

                                                        <div class="flex flex-wrap items-center gap-2 shrink-0">

                                                            <?php if (!empty($item['actualmente_trabaja'])): ?>
                                                                <span class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-lg border bg-blue-50 text-blue-700 border-blue-200">
                                                                    Actual
                                                                </span>
                                                            <?php endif; ?>

                                                            <button
                                                                type="button"
                                                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-100 transition-all"
                                                                onclick="toggleExperienciaDetalle('<?php echo $idExp; ?>', this)">
                                                                <span class="exp-toggle-text">Ver más</span>
                                                                <svg class="exp-toggle-icon w-4 h-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Detalle desplegable -->
                                                <div id="<?php echo $idExp; ?>" class="hidden border-t border-slate-200 bg-white">
                                                    <div class="p-5">
                                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                                                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Fecha Inicio</p>
                                                                <p class="text-sm font-bold text-slate-700">
                                                                    <?php echo !empty($item['fecha_inicio']) ? formatFecha($item['fecha_inicio']) : '—'; ?>
                                                                </p>
                                                            </div>

                                                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                                                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Fecha Fin</p>
                                                                <p class="text-sm font-bold text-slate-700">
                                                                    <?php
                                                                    echo !empty($item['actualmente_trabaja'])
                                                                        ? 'Actualidad'
                                                                        : (!empty($item['fecha_fin']) ? formatFecha($item['fecha_fin']) : '—');
                                                                    ?>
                                                                </p>
                                                            </div>

                                                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                                                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Tiempo de Servicio</p>
                                                                <p class="text-sm font-bold text-slate-700">
                                                                    <?php echo htmlspecialchars($tiempoServicio); ?>
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <?php if (!empty($item['funciones_principales'])): ?>
                                                            <div class="mt-4">
                                                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Funciones Principales</p>
                                                                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm text-slate-700 leading-relaxed">
                                                                    <?php echo nl2br(htmlspecialchars($item['funciones_principales'])); ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div><!-- /.relative.-mt-24 -->
            </div><!-- /.max-w-6xl -->
    </main>


    <div id="modal-perfil" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">

        <!-- Backdrop -->
        <div class="modal-backdrop absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="cerrarModal()"></div>

        <!-- Panel -->
        <div class="modal-panel absolute inset-0 w-screen h-screen bg-white shadow-2xl flex flex-col">

            <!-- Header del modal -->
            <div class="sticky top-0 z-20 flex items-center justify-between px-6 lg:px-8 py-5 border-b border-slate-100 bg-gradient-to-r from-[#310404] to-red-900">
                <div>
                    <h2 class="text-white font-black text-lg">Modificar Perfil del Colaborador</h2>
                    <p class="text-red-200 text-xs mt-0.5">
                        Sección <span id="paso-actual">1</span> de <span id="paso-total">4</span>
                    </p>
                </div>
                <button onclick="cerrarModal()" class="text-red-200 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Barra de progreso + steps compactos -->
            <div class="sticky top-[82px] z-10 px-6 lg:px-8 pt-4 pb-4 border-b border-slate-100 bg-white">
                <?php
                $steps = [
                    ['num' => 1, 'label' => 'Personal',   'desc' => 'Datos personales y contacto'],
                    ['num' => 2, 'label' => 'Familia',    'desc' => 'Familia, pensión y banco'],
                    ['num' => 3, 'label' => 'Trayectoria', 'desc' => 'Formación, idiomas y experiencia'],
                    ['num' => 4, 'label' => 'Confirmar',  'desc' => 'Resumen final'],
                ];
                ?>

                <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
                    <?php foreach ($steps as $step): ?>
                        <button
                            type="button"
                            onclick="irPaso(<?php echo $step['num']; ?>)"
                            class="step-indicator flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-left transition-all duration-300 hover:border-red-200 hover:bg-red-50/40"
                            data-step="<?php echo $step['num']; ?>">

                            <div class="step-circle w-10 h-10 rounded-2xl border-2 flex items-center justify-center text-xs font-black shrink-0 transition-all duration-300
                        <?php echo $step['num'] === 1 ? 'bg-red-900 border-red-900 text-white' : 'bg-white border-slate-200 text-slate-400'; ?>">
                                <span class="step-num"><?php echo $step['num']; ?></span>
                                <svg class="step-check w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>

                            <div class="min-w-0">
                                <p class="text-xs font-black uppercase tracking-widest transition-colors
                            <?php echo $step['num'] === 1 ? 'text-red-900' : 'text-slate-400'; ?>"
                                    id="step-label-<?php echo $step['num']; ?>">
                                    <?php echo $step['label']; ?>
                                </p>
                                <p class="text-[11px] text-slate-500 mt-1 leading-tight">
                                    <?php echo $step['desc']; ?>
                                </p>
                            </div>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Modal Cuerpo scrollable del formulario de Pasos -->
            <div class="modal-body flex-1 overflow-y-auto px-6 lg:px-10 py-7 lg:py-10">

                <!-- ── PASO 1: Datos Personales ── -->
                <div id="form-step-1" class="form-step">
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

                        <!-- BLOQUE 1: INFORMACIÓN PERSONAL -->
                        <div class="bg-white border border-slate-200 rounded-3xl p-6 lg:p-8 shadow-sm space-y-4">
                            <div class="mb-4">
                                <p class="section-title">Información Personal</p>
                                <div class="section-divider"></div>
                            </div>

                            <div class="form-grid-2">
                                <div class="field-group span-full">
                                    <label class="field-label">Nombres y Apellidos</label>
                                    <input
                                        type="text"
                                        name="nombres_apellidos"
                                        class="field-input"
                                        value="<?php echo htmlspecialchars($perfil['nombres_apellidos'] ?? ''); ?>"
                                        title="Este campo solo puede ser modificado por RRHH"
                                        <?php echo !$esEditable ? 'readonly' : ''; ?>>
                                    <p class="text-[10px] text-slate-400 mt-1">Solo RRHH puede modificar el nombre.</p>
                                </div>

                                <div class="field-group">
                                    <label class="field-label">DNI</label>
                                    <input
                                        type="text"
                                        name="dni"
                                        class="field-input"
                                        maxlength="8"
                                        value="<?php echo htmlspecialchars($perfil['dni'] ?? ''); ?>"
                                        <?php echo !$esEditable ? 'readonly' : ''; ?>>
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Fecha de Nacimiento</label>
                                    <input
                                        type="date"
                                        name="fecha_nacimiento"
                                        class="field-input"
                                        value="<?php echo htmlspecialchars($perfil['fecha_nacimiento'] ?? ''); ?>">
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Lugar de Nacimiento</label>
                                    <input
                                        type="text"
                                        name="lugar_nacimiento"
                                        class="field-input"
                                        value="<?php echo htmlspecialchars($perfil['lugar_nacimiento'] ?? ''); ?>">
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Estado Civil</label>
                                    <select name="estado_civil" class="field-input">
                                        <option value="">Seleccionar</option>
                                        <?php foreach (['Soltero/a', 'Casado/a', 'Divorciado/a', 'Viudo/a', 'Conviviente'] as $opt):
                                            $sel = ($perfil['estado_civil'] ?? '') === $opt ? 'selected' : ''; ?>
                                            <option value="<?php echo $opt; ?>" <?php echo $sel; ?>>
                                                <?php echo $opt; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Grupo Sanguíneo</label>
                                    <select name="grupo_sanguineo" class="field-input">
                                        <option value="">Seleccionar</option>
                                        <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $g):
                                            $sel = ($perfil['grupo_sanguineo'] ?? '') === $g ? 'selected' : ''; ?>
                                            <option value="<?php echo $g; ?>" <?php echo $sel; ?>>
                                                <?php echo $g; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Talla</label>
                                    <input
                                        type="text"
                                        name="talla"
                                        class="field-input"
                                        placeholder="Ej: 1.70"
                                        value="<?php echo htmlspecialchars($perfil['talla'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- BLOQUE 2: CONTACTO Y DOMICILIO -->
                        <div class="bg-white border border-slate-200 rounded-3xl p-6 lg:p-8 shadow-sm space-y-4">
                            <div class="mb-4">
                                <p class="section-title">Contacto y Domicilio</p>
                                <div class="section-divider"></div>
                            </div>
                            <div class="form-grid-2">
                                <div class="field-group span-full">
                                    <label class="field-label">Dirección de Residencia</label>
                                    <input type="text" name="direccion_residencia" class="field-input"
                                        value="<?php echo htmlspecialchars($perfil['direccion_residencia'] ?? ''); ?>">
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Distrito</label>
                                    <input type="text" name="distrito" class="field-input"
                                        value="<?php echo htmlspecialchars($perfil['distrito'] ?? ''); ?>">
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Celular</label>
                                    <input type="tel" name="celular" class="field-input" maxlength="9"
                                        value="<?php echo htmlspecialchars($perfil['celular'] ?? ''); ?>">
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Correo Personal</label>
                                    <input type="email" name="correo_personal" class="field-input"
                                        value="<?php echo htmlspecialchars($perfil['correo_personal'] ?? ''); ?>">
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Correo Institucional</label>
                                    <input type="email" name="correo_institucional" class="field-input"
                                        value="<?php echo htmlspecialchars($perfil['correo_institucional'] ?? ''); ?>"
                                        <?php echo !$esEditable ? 'readonly' : ''; ?>>
                                </div>
                            </div>
                        </div>

                        <?php if ($esEditable): ?>
                            <!-- BLOQUE 3: DATOS LABORALES -->
                            <div class="bg-white border border-slate-200 rounded-3xl p-6 lg:p-8 shadow-sm xl:col-span-2">
                                <div class="mb-4">
                                    <p class="section-title">Datos Laborales</p>
                                    <div class="section-divider"></div>
                                </div>

                                <div class="form-grid-2">

                                    <div class="field-group">
                                        <label class="field-label">Sueldo</label>
                                        <input type="number" step="0.01" min="0" name="sueldo" class="field-input" value="<?php echo htmlspecialchars($perfil['sueldo'] ?? ''); ?>">
                                            value="<?php echo htmlspecialchars($perfil['sueldo'] ?? ''); ?>">
                                    </div>

                                    <div class="field-group">
                                        <label class="field-label">Contrato</label>
                                        <select name="mod_contrato" class="field-input">
                                            <option value="">Seleccionar</option>
                                            <?php
                                            $contratoActual = strtoupper(trim($perfil['mod_contrato'] ?? ''));
                                            foreach (['CAS', 'MILITAR', 'PAC'] as $opt):
                                            ?>
                                                <option value="<?php echo $opt; ?>" <?php echo ($contratoActual === $opt) ? 'selected' : ''; ?>>
                                                    <?php echo $opt; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="field-group">
                                        <label class="field-label">Puesto CAS</label>
                                        <input type="text" name="puesto_cas" class="field-input"
                                            value="<?php echo htmlspecialchars($perfil['puesto_cas'] ?? ''); ?>">
                                    </div>

                                    <div class="field-group">
                                        <label class="field-label">Tipo Puesto</label>
                                        <input type="text" name="tipo_puesto" class="field-input"
                                            value="<?php echo htmlspecialchars($perfil['tipo_puesto'] ?? ''); ?>">
                                    </div>

                                    <div class="field-group">
                                        <label class="field-label">Área</label>
                                        <input type="text" name="area" class="field-input"
                                            value="<?php echo htmlspecialchars($perfil['area'] ?? ''); ?>">
                                    </div>

                                    <div class="field-group">
                                        <label class="field-label">Procedencia</label>
                                        <input type="text" name="procedencia" class="field-input"
                                            value="<?php echo htmlspecialchars($perfil['procedencia'] ?? ''); ?>">
                                    </div>

                                    <div class="field-group">
                                        <label class="field-label">NSA / CIP</label>
                                        <input type="text" name="nsa_cip" class="field-input"
                                            value="<?php echo htmlspecialchars($perfil['nsa_cip'] ?? ''); ?>">
                                    </div>

                                    <div class="field-group">
                                        <label class="field-label">Situación</label>
                                        <select name="situacion" class="field-input">
                                            <option value="">Seleccionar</option>
                                            <?php foreach (['ACTIVO', 'CESADO'] as $opt): ?>
                                                <option value="<?php echo $opt; ?>"
                                                    <?php echo (($perfil['situacion'] ?? '') === $opt) ? 'selected' : ''; ?>>
                                                    <?php echo $opt; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($esEditable): ?>
                            <!-- BLOQUE 4: CONTRATOS Y ADENDAS -->
                            <div class="bg-white border border-slate-200 rounded-3xl p-6 lg:p-8 shadow-sm xl:col-span-2">
                                <div class="flex items-center justify-between mb-4 gap-3">
                                    <div class="flex-1">
                                        <p class="section-title">Contratos y Adendas</p>
                                        <div class="section-divider"></div>
                                        <p class="text-[11px] text-slate-400 font-semibold mt-1">
                                            Registra contratos padre y agrega adendas vinculadas al contrato correspondiente.
                                        </p>
                                    </div>

                                    <button type="button" onclick="agregarContrato()"
                                        class="inline-flex items-center gap-1.5 text-xs font-bold text-red-900 bg-red-50 hover:bg-red-100 border border-red-200 px-3 py-1.5 rounded-xl transition-all shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Agregar Contrato
                                    </button>
                                </div>

                                <div id="lista-contratos" class="space-y-4">
                                    <?php
                                    $contratosOrganizadosEditar = organizarContratosConAdendas($contratos);
                                    $contratoIndexEdit = 0;
                                    ?>

                                    <?php if (empty($contratosOrganizadosEditar)): ?>
                                        <div id="sin-contratos" class="text-center py-5 text-slate-400 text-xs border border-dashed border-slate-200 rounded-2xl bg-slate-50/70">
                                            No hay contratos registrados. Haz clic en "Agregar Contrato".
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($contratosOrganizadosEditar as $contrato): ?>
                                            <?php
                                            $ci = $contratoIndexEdit++;
                                            $idContratoPadre = (int)($contrato['id'] ?? 0);
                                            $numeroContrato = trim((string)($contrato['numero_documento'] ?? ''));
                                            $modalidadContrato = strtoupper(trim((string)($contrato['modalidad'] ?? '')));
                                            $adendasContrato = $contrato['_adendas'] ?? [];
                                            ?>

                                            <div class="contrato-row bg-white border border-slate-200 rounded-2xl p-4 relative transition-all"
                                                data-index="<?php echo $ci; ?>"
                                                data-tipo-registro="CONTRATO">

                                                <div class="item-resumen flex items-center justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-red-50 text-red-900 border border-red-100">
                                                                Contrato padre
                                                            </span>

                                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600 border border-slate-200">
                                                                <?php echo count($adendasContrato); ?> adenda(s)
                                                            </span>
                                                        </div>

                                                        <p class="text-sm font-black text-slate-800 val-numero-documento">
                                                            <?php echo htmlPerfil($numeroContrato !== '' ? $numeroContrato : 'Contrato sin número'); ?>
                                                        </p>

                                                        <p class="text-[11px] text-slate-500 mt-0.5">
                                                            Inicio:
                                                            <span class="val-fecha-ingreso">
                                                                <?php echo !empty($contrato['fecha_ingreso']) ? formatFecha($contrato['fecha_ingreso']) : 'Sin fecha'; ?>
                                                            </span>
                                                            • Fin:
                                                            <span class="val-fecha-cese">
                                                                <?php echo !empty($contrato['fecha_cese']) ? formatFecha($contrato['fecha_cese']) : 'Vigente'; ?>
                                                            </span>
                                                            • Modalidad:
                                                            <span class="val-modalidad">
                                                                <?php echo htmlPerfil($modalidadContrato !== '' ? $modalidadContrato : '—'); ?>
                                                            </span>
                                                        </p>
                                                    </div>

                                                    <div class="flex flex-wrap justify-end gap-2 shrink-0">
                                                        <?php if ($idContratoPadre > 0): ?>
                                                            <button type="button"
                                                                onclick="agregarAdenda('<?php echo $idContratoPadre; ?>', '<?php echo htmlPerfil($numeroContrato !== '' ? $numeroContrato : 'Contrato padre'); ?>', '<?php echo htmlPerfil($modalidadContrato); ?>')"
                                                                class="text-slate-700 bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors border border-slate-200">
                                                                + Adenda
                                                            </button>
                                                        <?php endif; ?>

                                                        <button type="button" onclick="toggleFila(this, true)"
                                                            class="text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors border border-red-100">
                                                            Editar
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="item-form hidden mt-3 pt-4 border-t border-slate-200 relative animate-in">
                                                    <button type="button" onclick="eliminarFila(this)"
                                                        class="absolute top-0 right-0 text-slate-300 hover:text-red-500 transition-colors p-1"
                                                        title="Eliminar">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>

                                                    <div class="form-grid-2 pr-6">
                                                        <div class="field-group span-full">
                                                            <label class="field-label">N° de contrato</label>
                                                            <input type="text"
                                                                name="contratos[<?php echo $ci; ?>][numero_documento]"
                                                                class="field-input input-numero-documento"
                                                                value="<?php echo htmlPerfil($contrato['numero_documento'] ?? ''); ?>"
                                                                placeholder="Ej. CONTRATO N° 001-2026">
                                                        </div>

                                                        <div class="field-group">
                                                            <label class="field-label">Fecha documento</label>
                                                            <input type="date"
                                                                name="contratos[<?php echo $ci; ?>][fecha_documento]"
                                                                class="field-input input-fecha-documento"
                                                                value="<?php echo htmlPerfil($contrato['fecha_documento'] ?? ''); ?>">
                                                        </div>

                                                        <div class="field-group">
                                                            <label class="field-label">Modalidad</label>
                                                            <select name="contratos[<?php echo $ci; ?>][modalidad]" class="field-input input-modalidad">
                                                                <option value="">Seleccionar</option>
                                                                <?php foreach (['CAS', 'MILITAR', 'PAC'] as $opt): ?>
                                                                    <option value="<?php echo $opt; ?>" <?php echo ($modalidadContrato === $opt) ? 'selected' : ''; ?>>
                                                                        <?php echo $opt; ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>

                                                        <div class="field-group">
                                                            <label class="field-label">Inicio contrato</label>
                                                            <input type="date"
                                                                name="contratos[<?php echo $ci; ?>][fecha_ingreso]"
                                                                class="field-input input-fecha-ingreso"
                                                                value="<?php echo htmlPerfil($contrato['fecha_ingreso'] ?? ''); ?>">
                                                        </div>

                                                        <div class="field-group">
                                                            <label class="field-label">Fin contrato</label>
                                                            <input type="date"
                                                                name="contratos[<?php echo $ci; ?>][fecha_cese]"
                                                                class="field-input input-fecha-cese"
                                                                value="<?php echo htmlPerfil($contrato['fecha_cese'] ?? ''); ?>">
                                                        </div>

                                                        <div class="field-group span-full">
                                                            <label class="field-label">Observación</label>
                                                            <textarea name="contratos[<?php echo $ci; ?>][observacion]"
                                                                class="field-input input-observacion"
                                                                rows="2"><?php echo htmlPerfil($contrato['observacion'] ?? ''); ?></textarea>
                                                        </div>

                                                        <input type="hidden" name="contratos[<?php echo $ci; ?>][id]" value="<?php echo htmlPerfil($contrato['id'] ?? ''); ?>">
                                                        <input type="hidden" name="contratos[<?php echo $ci; ?>][tipo_registro]" value="CONTRATO">
                                                        <input type="hidden" name="contratos[<?php echo $ci; ?>][contrato_padre_id]" value="">
                                                        <input type="hidden" name="contratos[<?php echo $ci; ?>][motivo_adenda]" value="">
                                                    </div>

                                                    <div class="mt-3 text-right">
                                                        <button type="button" onclick="toggleFila(this, false)"
                                                            class="text-xs font-bold text-slate-600 hover:text-slate-900 transition-colors bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-lg">
                                                            ✓ Listo
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <?php foreach ($adendasContrato as $adenda): ?>
                                                <?php
                                                $ai = $contratoIndexEdit++;
                                                $modalidadAdenda = strtoupper(trim((string)($adenda['modalidad'] ?? $modalidadContrato)));
                                                ?>

                                                <div class="contrato-row adenda-row bg-slate-50 border border-slate-200 rounded-2xl p-4 relative transition-all ml-0 md:ml-8"
                                                    data-index="<?php echo $ai; ?>"
                                                    data-tipo-registro="ADENDA">

                                                    <div class="item-resumen flex items-center justify-between gap-3">
                                                        <div class="min-w-0">
                                                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-900 text-white border border-slate-900">
                                                                    Adenda
                                                                </span>

                                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-white text-slate-600 border border-slate-200">
                                                                    Padre: <?php echo htmlPerfil($numeroContrato !== '' ? $numeroContrato : 'Contrato'); ?>
                                                                </span>
                                                            </div>

                                                            <p class="text-sm font-black text-slate-800 val-numero-documento">
                                                                <?php echo htmlPerfil($adenda['numero_documento'] ?: 'Adenda sin número'); ?>
                                                            </p>

                                                            <p class="text-[11px] text-slate-500 mt-0.5">
                                                                Inicio:
                                                                <span class="val-fecha-ingreso">
                                                                    <?php echo !empty($adenda['fecha_ingreso']) ? formatFecha($adenda['fecha_ingreso']) : 'Sin fecha'; ?>
                                                                </span>
                                                                • Fin:
                                                                <span class="val-fecha-cese">
                                                                    <?php echo !empty($adenda['fecha_cese']) ? formatFecha($adenda['fecha_cese']) : '—'; ?>
                                                                </span>
                                                                • Motivo:
                                                                <span class="val-motivo-adenda">
                                                                    <?php echo htmlPerfil($adenda['motivo_adenda'] ?? '—'); ?>
                                                                </span>
                                                            </p>
                                                        </div>

                                                        <button type="button" onclick="toggleFila(this, true)"
                                                            class="text-red-900 bg-white hover:bg-red-50 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors border border-red-100 shrink-0">
                                                            Editar
                                                        </button>
                                                    </div>

                                                    <div class="item-form hidden mt-3 pt-4 border-t border-slate-200 relative animate-in">
                                                        <button type="button" onclick="eliminarFila(this)"
                                                            class="absolute top-0 right-0 text-slate-300 hover:text-red-500 transition-colors p-1"
                                                            title="Eliminar">
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>

                                                        <div class="form-grid-2 pr-6">
                                                            <div class="field-group span-full">
                                                                <label class="field-label">N° de adenda</label>
                                                                <input type="text"
                                                                    name="contratos[<?php echo $ai; ?>][numero_documento]"
                                                                    class="field-input input-numero-documento"
                                                                    value="<?php echo htmlPerfil($adenda['numero_documento'] ?? ''); ?>"
                                                                    placeholder="Ej. ADENDA N° 001-2026">
                                                            </div>

                                                            <div class="field-group">
                                                                <label class="field-label">Fecha documento</label>
                                                                <input type="date"
                                                                    name="contratos[<?php echo $ai; ?>][fecha_documento]"
                                                                    class="field-input input-fecha-documento"
                                                                    value="<?php echo htmlPerfil($adenda['fecha_documento'] ?? ''); ?>">
                                                            </div>

                                                            <div class="field-group">
                                                                <label class="field-label">Modalidad</label>
                                                                <select name="contratos[<?php echo $ai; ?>][modalidad]" class="field-input input-modalidad">
                                                                    <option value="">Heredar contrato padre</option>
                                                                    <?php foreach (['CAS', 'MILITAR', 'PAC'] as $opt): ?>
                                                                        <option value="<?php echo $opt; ?>" <?php echo ($modalidadAdenda === $opt) ? 'selected' : ''; ?>>
                                                                            <?php echo $opt; ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>

                                                            <div class="field-group">
                                                                <label class="field-label">Inicio adenda</label>
                                                                <input type="date"
                                                                    name="contratos[<?php echo $ai; ?>][fecha_ingreso]"
                                                                    class="field-input input-fecha-ingreso"
                                                                    value="<?php echo htmlPerfil($adenda['fecha_ingreso'] ?? ''); ?>">
                                                            </div>

                                                            <div class="field-group">
                                                                <label class="field-label">Fin adenda</label>
                                                                <input type="date"
                                                                    name="contratos[<?php echo $ai; ?>][fecha_cese]"
                                                                    class="field-input input-fecha-cese"
                                                                    value="<?php echo htmlPerfil($adenda['fecha_cese'] ?? ''); ?>">
                                                            </div>

                                                            <div class="field-group span-full">
                                                                <label class="field-label">Motivo de adenda</label>
                                                                <input type="text"
                                                                    name="contratos[<?php echo $ai; ?>][motivo_adenda]"
                                                                    class="field-input input-motivo-adenda"
                                                                    value="<?php echo htmlPerfil($adenda['motivo_adenda'] ?? ''); ?>"
                                                                    placeholder="Ej. Ampliación de plazo, renovación, modificación contractual...">
                                                            </div>

                                                            <div class="field-group span-full">
                                                                <label class="field-label">Observación</label>
                                                                <textarea name="contratos[<?php echo $ai; ?>][observacion]"
                                                                    class="field-input input-observacion"
                                                                    rows="2"><?php echo htmlPerfil($adenda['observacion'] ?? ''); ?></textarea>
                                                            </div>

                                                            <input type="hidden" name="contratos[<?php echo $ai; ?>][id]" value="<?php echo htmlPerfil($adenda['id'] ?? ''); ?>">
                                                            <input type="hidden" name="contratos[<?php echo $ai; ?>][tipo_registro]" value="ADENDA">
                                                            <input type="hidden" name="contratos[<?php echo $ai; ?>][contrato_padre_id]" value="<?php echo $idContratoPadre; ?>">
                                                        </div>

                                                        <div class="mt-3 text-right">
                                                            <button type="button" onclick="toggleFila(this, false)"
                                                                class="text-xs font-bold text-slate-600 hover:text-slate-900 transition-colors bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-lg">
                                                                ✓ Listo
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ── PASO 3: Familia ── -->
                <div id="form-step-3" class="form-step hidden">
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

                        <!-- CÓNYUGE -->
                        <div class="bg-white border border-slate-200 rounded-3xl p-6 lg:p-8 shadow-sm">
                            <div class="mb-4">
                                <p class="section-title">Cónyuge</p>
                                <div class="section-divider"></div>
                            </div>

                            <div class="form-grid-2">
                                <div class="field-group span-full">
                                    <label class="field-label">Nombre Completo</label>
                                    <input type="text" name="conyuge" class="field-input"
                                        value="<?php echo htmlspecialchars($perfil['conyuge'] ?? ''); ?>"
                                        placeholder="Dejar vacío si no aplica">
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Fecha de Nacimiento</label>
                                    <input type="date" name="onomastico_conyuge" class="field-input"
                                        value="<?php echo htmlspecialchars($perfil['onomastico_conyuge'] ?? ''); ?>">
                                </div>

                                <div class="field-group">
                                    <label class="field-label">DNI Cónyuge</label>
                                    <input type="text" name="dni_conyuge" class="field-input" maxlength="8"
                                        value="<?php echo htmlspecialchars($perfil['dni_conyuge'] ?? ''); ?>"
                                        placeholder="Opcional">
                                </div>
                            </div>
                        </div>

                        <!-- HIJOS -->
                        <div class="bg-white border border-slate-200 rounded-3xl p-6 lg:p-8 shadow-sm">
                            <div class="mb-4">
                                <p class="section-title">Hijos</p>
                                <div class="section-divider"></div>
                            </div>

                            <div class="flex justify-end mb-4">
                                <button type="button" onclick="agregarHijo()"
                                    class="inline-flex items-center gap-1.5 text-xs font-bold text-red-900 bg-red-50 hover:bg-red-100 border border-red-200 px-3 py-1.5 rounded-xl transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Agregar hijo
                                </button>
                            </div>

                            <div id="lista-hijos" class="space-y-3">
                                <?php
                                $hijos = array_filter($perfil['familia'] ?? [], fn($f) => in_array(($f['parentesco'] ?? ''), ['HIJO', 'HIJA'], true));
                                if (empty($hijos)): ?>
                                    <div id="sin-hijos" class="text-center py-5 text-slate-400 text-xs border border-dashed border-slate-200 rounded-2xl bg-slate-50/70">
                                        No hay hijos registrados. Haz clic en "Agregar hijo" para añadir.
                                    </div>
                                    <?php else:
                                    foreach (array_values($hijos) as $hi => $hijo): ?>
                                        <div class="hijo-row bg-slate-50 border border-slate-200 rounded-2xl p-4 relative transition-all" data-index="<?php echo $hi; ?>">

                                            <div class="item-resumen flex items-center justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-bold text-slate-800 val-nombre truncate">
                                                        <?php echo htmlspecialchars($hijo['nombre_completo'] ?? ''); ?>
                                                    </p>
                                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                                        <span class="val-parentesco"><?php echo htmlspecialchars($hijo['parentesco'] ?? 'HIJO'); ?></span>
                                                        • DNI: <span class="val-dni"><?php echo htmlspecialchars($hijo['dni_familiar'] ?: '—'); ?></span>
                                                    </p>
                                                </div>

                                                <button type="button" onclick="toggleFila(this, true)"
                                                    class="text-red-900 bg-white hover:bg-red-50 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors border border-red-100 shrink-0">
                                                    Editar
                                                </button>
                                            </div>

                                            <div class="item-form hidden mt-3 pt-4 border-t border-slate-200 relative animate-in">
                                                <button type="button" onclick="eliminarFila(this)"
                                                    class="absolute top-0 right-0 text-slate-300 hover:text-red-500 transition-colors p-1"
                                                    title="Eliminar">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>

                                                <div class="form-grid-2 pr-6">
                                                    <div class="field-group span-full">
                                                        <label class="field-label">Nombre Completo</label>
                                                        <input type="text" name="hijos[<?php echo $hi; ?>][nombre]" class="field-input input-nombre"
                                                            value="<?php echo htmlspecialchars($hijo['nombre_completo'] ?? ''); ?>">
                                                    </div>

                                                    <div class="field-group">
                                                        <label class="field-label">Parentesco</label>
                                                        <select name="hijos[<?php echo $hi; ?>][parentesco]" class="field-input input-parentesco">
                                                            <option value="HIJO" <?php echo ($hijo['parentesco'] === 'HIJO') ? 'selected' : ''; ?>>Hijo</option>
                                                            <option value="HIJA" <?php echo ($hijo['parentesco'] === 'HIJA') ? 'selected' : ''; ?>>Hija</option>
                                                        </select>
                                                    </div>

                                                    <div class="field-group">
                                                        <label class="field-label">Fecha Nacimiento</label>
                                                        <input type="date" name="hijos[<?php echo $hi; ?>][fecha_nacimiento]" class="field-input input-fecha"
                                                            value="<?php echo htmlspecialchars($hijo['fecha_nacimiento'] ?? ''); ?>">
                                                    </div>

                                                    <div class="field-group">
                                                        <label class="field-label">DNI</label>
                                                        <input type="text" name="hijos[<?php echo $hi; ?>][dni]" class="field-input input-dni" maxlength="8"
                                                            value="<?php echo htmlspecialchars($hijo['dni_familiar'] ?? ''); ?>">
                                                    </div>

                                                    <input type="hidden" name="hijos[<?php echo $hi; ?>][id]" value="<?php echo $hijo['id'] ?? ''; ?>">
                                                </div>

                                                <div class="mt-3 text-right">
                                                    <button type="button" onclick="toggleFila(this, false)"
                                                        class="text-xs font-bold text-slate-600 hover:text-slate-900 transition-colors bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-lg">
                                                        ✓ Listo
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                <?php endforeach;
                                endif; ?>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ── PASO 4: Pensiones ── -->
                <div id="form-step-4" class="form-step hidden">
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

                        <!-- SISTEMA DE PENSIONES -->
                        <div class="bg-white border border-slate-200 rounded-3xl p-6 lg:p-8 shadow-sm">
                            <div class="mb-4">
                                <p class="section-title">Sistema de Pensiones</p>
                                <div class="section-divider"></div>
                            </div>

                            <div class="form-grid-2">
                                <div class="field-group span-full">
                                    <label class="field-label">Sistema de Pensiones</label>
                                    <select name="pension[sistema_pension]" class="field-input">
                                        <option value="">Seleccionar</option>
                                        <?php foreach (['CNP', 'D.L 20520', 'CAJA MILITAR', 'OTROS'] as $opt): ?>
                                            <option value="<?php echo $opt; ?>" <?php echo (($perfil['pension']['sistema_pension'] ?? '') === $opt) ? 'selected' : ''; ?>>
                                                <?php echo $opt; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="field-group">
                                    <label class="field-label">AFP</label>
                                    <select name="pension[afp]" class="field-input">
                                        <option value="">Seleccionar</option>
                                        <?php foreach (['PRIMA', 'INTEGRA', 'PROFUTURO', 'HABITAT', 'OTRO'] as $opt): ?>
                                            <option value="<?php echo $opt; ?>" <?php echo (($perfil['pension']['afp'] ?? '') === $opt) ? 'selected' : ''; ?>>
                                                <?php echo $opt; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Número de CUSPP</label>
                                    <input type="text" name="pension[cuspp]" class="field-input"
                                        value="<?php echo htmlspecialchars($perfil['pension']['cuspp'] ?? ''); ?>">
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Tipo de Comisión</label>
                                    <select name="pension[tipo_comision]" class="field-input">
                                        <option value="">Seleccionar</option>
                                        <?php foreach (['FLUJO', 'MIXTA', 'OTRO'] as $opt): ?>
                                            <option value="<?php echo $opt; ?>" <?php echo (($perfil['pension']['tipo_comision'] ?? '') === $opt) ? 'selected' : ''; ?>>
                                                <?php echo $opt; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Fecha de Inscripción</label>
                                    <input type="date" name="pension[fecha_inscripcion]" class="field-input"
                                        value="<?php echo htmlspecialchars($perfil['pension']['fecha_inscripcion'] ?? ''); ?>">
                                </div>

                                <div class="field-group span-full">
                                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                                        <input type="checkbox" name="pension[sin_afp_afiliarme]" value="1"
                                            <?php echo !empty($perfil['pension']['sin_afp_afiliarme']) ? 'checked' : ''; ?>>
                                        No tengo AFP (deseo afiliarme)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- DATOS BANCARIOS -->
                        <div class="bg-white border border-slate-200 rounded-3xl p-6 lg:p-8 shadow-sm">
                            <div class="mb-4">
                                <p class="section-title">Datos Bancarios</p>
                                <div class="section-divider"></div>
                            </div>

                            <div class="form-grid-2">
                                <div class="field-group span-full">
                                    <label class="field-label">Nombre Banco Haberes</label>
                                    <input type="text" name="bancario[banco_haberes]" class="field-input"
                                        value="<?php echo htmlspecialchars($perfil['bancario']['banco_haberes'] ?? ''); ?>">
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Número de Cuenta</label>
                                    <input type="text" name="bancario[numero_cuenta]" class="field-input"
                                        value="<?php echo htmlspecialchars($perfil['bancario']['numero_cuenta'] ?? ''); ?>">
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Número de Cuenta CCI</label>
                                    <input type="text" name="bancario[numero_cuenta_cci]" class="field-input"
                                        value="<?php echo htmlspecialchars($perfil['bancario']['numero_cuenta_cci'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ── PASO 6: Académico ── -->
                <div id="form-step-6" class="form-step hidden">
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

                        <!-- BLOQUE 1: FORMACIÓN ACADÉMICA -->
                        <div class="bg-white border border-slate-200 rounded-3xl p-6 lg:p-8 shadow-sm">
                            <div class="flex items-center justify-between mb-4 gap-3">
                                <div class="flex-1">
                                    <p class="section-title">Formación Académica</p>
                                    <div class="section-divider"></div>
                                </div>

                                <button type="button" onclick="agregarFormacion()"
                                    class="inline-flex items-center gap-1.5 text-xs font-bold text-red-900 bg-red-50 hover:bg-red-100 border border-red-200 px-3 py-1.5 rounded-xl transition-all shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Agregar Estudio
                                </button>
                            </div>

                            <div id="lista-formacion" class="space-y-3">
                                <?php if (empty($formacion)): ?>
                                    <div id="sin-formacion" class="text-center py-5 text-slate-400 text-xs">
                                        No hay estudios registrados. Haz clic en "Agregar Estudio".
                                    </div>
                                <?php else: ?>
                                    <?php foreach (array_values($formacion) as $fi => $form): ?>
                                        <div class="formacion-row bg-white border border-slate-200 rounded-xl p-4 relative transition-all" data-index="<?php echo $fi; ?>">

                                            <div class="item-resumen flex items-center justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-bold text-slate-800 val-carrera truncate">
                                                        <?php echo htmlspecialchars($form['descripcion_carrera'] ?? 'Sin carrera'); ?>
                                                    </p>
                                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                                        <span class="val-grado"><?php echo htmlspecialchars($form['tipo_grado'] ?? 'BACHILLER'); ?></span>
                                                        • <span class="val-inst"><?php echo htmlspecialchars($form['institucion'] ?: 'Sin institución'); ?></span>
                                                    </p>
                                                </div>

                                                <button type="button" onclick="toggleFila(this, true)" class="text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors border border-red-100 shrink-0">
                                                    Editar
                                                </button>
                                            </div>

                                            <div class="item-form hidden mt-2 pt-3 border-t border-slate-100 relative animate-in">
                                                <button type="button" onclick="eliminarFila(this)" class="absolute top-0 right-0 text-slate-300 hover:text-red-500 transition-colors p-1" title="Eliminar">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>

                                                <div class="form-grid-2 pr-6">
                                                    <div class="field-group">
                                                        <label class="field-label">Tipo de Grado</label>
                                                        <select name="formacion[<?php echo $fi; ?>][tipo_grado]" class="field-input input-grado">
                                                            <?php foreach (['SECUNDARIA', 'TÉCNICO', 'BACHILLER', 'TÍTULO PROFESIONAL', 'MAESTRÍA', 'DOCTORADO', 'ESPECIALIZACIÓN'] as $t): ?>
                                                                <option value="<?php echo $t; ?>" <?php echo (strtoupper($form['tipo_grado'] ?? '') === $t) ? 'selected' : ''; ?>>
                                                                    <?php echo $t; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                    <div class="field-group">
                                                        <label class="field-label">Carrera / Especialidad</label>
                                                        <input type="text"
                                                            name="formacion[<?php echo $fi; ?>][descripcion_carrera]"
                                                            class="field-input input-carrera"
                                                            value="<?php echo htmlspecialchars($form['descripcion_carrera'] ?? ''); ?>">
                                                    </div>

                                                    <div class="field-group span-full">
                                                        <label class="field-label">Institución</label>
                                                        <input type="text"
                                                            name="formacion[<?php echo $fi; ?>][institucion]"
                                                            class="field-input input-inst"
                                                            value="<?php echo htmlspecialchars($form['institucion'] ?? ''); ?>">
                                                    </div>

                                                    <div class="field-group">
                                                        <label class="field-label">Año de Realización</label>
                                                        <input type="number"
                                                            name="formacion[<?php echo $fi; ?>][anio_realizacion]"
                                                            class="field-input"
                                                            min="1900"
                                                            max="2100"
                                                            step="1"
                                                            value="<?php echo htmlspecialchars($form['anio_realizacion'] ?? ''); ?>"
                                                            placeholder="Ej: 2020">
                                                    </div>
                                                    <div class="field-group">
                                                        <label class="field-label">Horas Lectivas</label>
                                                        <input type="number"
                                                            name="formacion[<?php echo $fi; ?>][horas_lectivas]"
                                                            class="field-input"
                                                            min="0"
                                                            step="1"
                                                            value="<?php echo htmlspecialchars($form['horas_lectivas'] ?? ''); ?>"
                                                            placeholder="Ej: 120">
                                                    </div>

                                                    <div class="field-group">
                                                        <label class="field-label">Especialidad</label>
                                                        <input type="text"
                                                            name="formacion[<?php echo $fi; ?>][especialidad]"
                                                            class="field-input"
                                                            value="<?php echo htmlspecialchars($form['especialidad'] ?? ''); ?>"
                                                            placeholder="Ej: Gestión Pública">
                                                    </div>

                                                    <div class="field-group">
                                                        <label class="field-label">Grado Alcanzado</label>
                                                        <input type="text"
                                                            name="formacion[<?php echo $fi; ?>][grado_alcanzado]"
                                                            class="field-input"
                                                            value="<?php echo htmlspecialchars($form['grado_alcanzado'] ?? ''); ?>"
                                                            placeholder="Ej: Egresado / Titulado / Concluido">
                                                    </div>

                                                    <input type="hidden" name="formacion[<?php echo $fi; ?>][id]" value="<?php echo $form['id'] ?? ''; ?>">
                                                </div>

                                                <div class="mt-3 text-right">
                                                    <button type="button" onclick="toggleFila(this, false)" class="text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors bg-slate-100 px-3 py-1.5 rounded-lg">
                                                        ✓ Listo
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- BLOQUE 2: IDIOMAS -->
                        <div class="bg-white border border-slate-200 rounded-3xl p-6 lg:p-8 shadow-sm">
                            <div class="flex items-center justify-between mb-4 gap-3">
                                <div class="flex-1">
                                    <p class="section-title">Idiomas</p>
                                    <div class="section-divider"></div>
                                </div>

                                <button type="button" onclick="agregarIdioma()"
                                    class="inline-flex items-center gap-1.5 text-xs font-bold text-red-900 bg-red-50 hover:bg-red-100 border border-red-200 px-3 py-1.5 rounded-xl transition-all shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Agregar Idioma
                                </button>
                            </div>

                            <div id="lista-idiomas" class="space-y-3">
                                <?php $idiomas = $perfil['idiomas'] ?? []; ?>
                                <?php if (empty($idiomas)): ?>
                                    <div id="sin-idiomas" class="text-center py-5 text-slate-400 text-xs">
                                        No hay idiomas registrados. Haz clic en "Agregar Idioma".
                                    </div>
                                <?php else: ?>
                                    <?php foreach (array_values($idiomas) as $ii => $idioma): ?>
                                        <div class="idioma-row bg-white border border-slate-200 rounded-xl p-4 relative transition-all" data-index="<?php echo $ii; ?>">

                                            <div class="item-resumen flex items-center justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-bold text-slate-800 val-idioma truncate">
                                                        <?php echo htmlspecialchars($idioma['idioma'] ?? 'Sin idioma'); ?>
                                                    </p>
                                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                                        Nivel: <span class="val-nivel"><?php echo htmlspecialchars($idioma['nivel'] ?? 'BASICO'); ?></span>
                                                    </p>
                                                </div>

                                                <button type="button" onclick="toggleFila(this, true)" class="text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors border border-red-100 shrink-0">
                                                    Editar
                                                </button>
                                            </div>

                                            <div class="item-form hidden mt-2 pt-3 border-t border-slate-100 relative animate-in">
                                                <button type="button" onclick="eliminarFila(this)" class="absolute top-0 right-0 text-slate-300 hover:text-red-500 transition-colors p-1" title="Eliminar">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>

                                                <div class="form-grid-2 pr-6">
                                                    <div class="field-group">
                                                        <label class="field-label">Idioma</label>
                                                        <input type="text" name="idiomas[<?php echo $ii; ?>][idioma]" class="field-input input-idioma"
                                                            value="<?php echo htmlspecialchars($idioma['idioma'] ?? ''); ?>">
                                                    </div>

                                                    <div class="field-group">
                                                        <label class="field-label">Nivel</label>
                                                        <select name="idiomas[<?php echo $ii; ?>][nivel]" class="field-input input-nivel">
                                                            <?php foreach (['BASICO', 'INTERMEDIO', 'AVANZADO'] as $nivel): ?>
                                                                <option value="<?php echo $nivel; ?>" <?php echo (($idioma['nivel'] ?? 'BASICO') === $nivel) ? 'selected' : ''; ?>>
                                                                    <?php echo $nivel; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="mt-3 text-right">
                                                    <button type="button" onclick="toggleFila(this, false)" class="text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors bg-slate-100 px-3 py-1.5 rounded-lg">
                                                        ✓ Listo
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ── PASO 7: Experiencia Laboral ── -->
                <div id="form-step-7" class="form-step hidden">
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

                        <!-- BLOQUE 1: EXPERIENCIA LABORAL -->
                        <div class="bg-white border border-slate-200 rounded-3xl p-6 lg:p-8 shadow-sm xl:col-span-2">
                            <div class="flex items-center justify-between mb-4 gap-3">
                                <div class="flex-1">
                                    <p class="section-title">Experiencia Laboral</p>
                                    <div class="section-divider"></div>
                                </div>

                                <button type="button" onclick="agregarExperiencia()"
                                    class="inline-flex items-center gap-1.5 text-xs font-bold text-red-900 bg-red-50 hover:bg-red-100 border border-red-200 px-3 py-1.5 rounded-xl transition-all shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Agregar Experiencia
                                </button>
                            </div>

                            <div id="lista-experiencia" class="space-y-3">
                                <?php if (empty($perfil['experiencia'])): ?>
                                    <div id="sin-experiencia" class="text-center py-5 text-slate-400 text-xs">
                                        No hay experiencia registrada. Haz clic en "Agregar Experiencia".
                                    </div>
                                <?php else: ?>
                                    <?php foreach (array_values($perfil['experiencia']) as $ei => $exp): ?>
                                        <div class="experiencia-row bg-white border border-slate-200 rounded-xl p-4 relative transition-all" data-index="<?php echo $ei; ?>">

                                            <div class="item-resumen flex items-center justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-bold text-slate-800 val-cargo truncate">
                                                        <?php echo htmlspecialchars($exp['cargo_puesto'] ?? 'Sin cargo'); ?>
                                                    </p>
                                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                                        <span class="val-empresa"><?php echo htmlspecialchars($exp['empresa_entidad'] ?? 'Sin empresa'); ?></span>
                                                        •
                                                        <span class="val-fechas">
                                                            <?php echo !empty($exp['fecha_inicio']) ? formatFecha($exp['fecha_inicio']) : '—'; ?>
                                                            -
                                                            <?php echo !empty($exp['actualmente_trabaja']) ? 'Actualidad' : (!empty($exp['fecha_fin']) ? formatFecha($exp['fecha_fin']) : '—'); ?>
                                                        </span>
                                                    </p>
                                                </div>

                                                <button type="button" onclick="toggleFila(this, true)" class="text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors border border-red-100 shrink-0">
                                                    Editar
                                                </button>
                                            </div>

                                            <div class="item-form hidden mt-2 pt-3 border-t border-slate-100 relative animate-in">
                                                <button type="button" onclick="eliminarFila(this)" class="absolute top-0 right-0 text-slate-300 hover:text-red-500 transition-colors p-1" title="Eliminar">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>

                                                <div class="form-grid-2 pr-6">
                                                    <div class="field-group span-full">
                                                        <label class="field-label">Empresa / Entidad</label>
                                                        <input type="text" name="experiencia[<?php echo $ei; ?>][empresa_entidad]" class="field-input input-empresa"
                                                            value="<?php echo htmlspecialchars($exp['empresa_entidad'] ?? ''); ?>">
                                                    </div>

                                                    <div class="field-group span-full">
                                                        <label class="field-label">Unidad Orgánica / Área</label>
                                                        <input type="text" name="experiencia[<?php echo $ei; ?>][unidad_organica_area]" class="field-input input-area"
                                                            value="<?php echo htmlspecialchars($exp['unidad_organica_area'] ?? ''); ?>">
                                                    </div>

                                                    <div class="field-group span-full">
                                                        <label class="field-label">Cargo / Puesto</label>
                                                        <input type="text" name="experiencia[<?php echo $ei; ?>][cargo_puesto]" class="field-input input-cargo"
                                                            value="<?php echo htmlspecialchars($exp['cargo_puesto'] ?? ''); ?>">
                                                    </div>

                                                    <div class="field-group">
                                                        <label class="field-label">Fecha Inicio</label>
                                                        <input type="date" name="experiencia[<?php echo $ei; ?>][fecha_inicio]" class="field-input input-inicio"
                                                            value="<?php echo htmlspecialchars($exp['fecha_inicio'] ?? ''); ?>">
                                                    </div>

                                                    <div class="field-group">
                                                        <label class="field-label">Fecha Fin</label>
                                                        <input type="date" name="experiencia[<?php echo $ei; ?>][fecha_fin]" class="field-input input-fin"
                                                            value="<?php echo htmlspecialchars($exp['fecha_fin'] ?? ''); ?>">
                                                    </div>

                                                    <div class="field-group span-full">
                                                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                                                            <input type="checkbox" name="experiencia[<?php echo $ei; ?>][actualmente_trabaja]" class="input-actual" value="1"
                                                                <?php echo !empty($exp['actualmente_trabaja']) ? 'checked' : ''; ?>>
                                                            Actualmente trabaja aquí
                                                        </label>
                                                    </div>

                                                    <div class="field-group span-full">
                                                        <label class="field-label">Funciones Principales</label>
                                                        <textarea name="experiencia[<?php echo $ei; ?>][funciones_principales]" class="field-input input-funciones" rows="4"><?php echo htmlspecialchars($exp['funciones_principales'] ?? ''); ?></textarea>
                                                    </div>

                                                    <input type="hidden" name="experiencia[<?php echo $ei; ?>][id]" value="<?php echo $exp['id'] ?? ''; ?>">
                                                </div>

                                                <div class="mt-3 text-right">
                                                    <button type="button" onclick="toggleFila(this, false)" class="text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors bg-slate-100 px-3 py-1.5 rounded-lg">
                                                        ✓ Listo
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ── PASO 9: Resumen Cambios ── -->
                <div id="form-step-9" class="form-step hidden">
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-6">Resumen de Cambios</p>

                    <div class="bg-green-50 border border-green-200 rounded-2xl p-5 mb-6 flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-black text-green-800">
                                <?php echo $rolSesion === 'colaborador' ? 'Solicitud lista para enviar' : 'Todo listo para guardar'; ?>
                            </p>
                            <p class="text-xs text-green-700 mt-0.5">
                                <?php if ($rolSesion === 'colaborador'): ?>
                                    Al confirmar, tus cambios quedarán pendientes de validación por RRHH.
                                <?php else: ?>
                                    Al confirmar, los cambios se guardarán directamente en el sistema.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <div id="resumen-cambios" class="space-y-2 text-sm">
                        <!-- Se rellena dinámicamente con JS -->
                    </div>

                    <?php if ($rolSesion === 'colaborador'): ?>
                        <div class="mt-6 bg-white border border-slate-200 rounded-2xl p-5">
                            <p class="text-xs font-black uppercase tracking-widest text-slate-500 mb-3">
                                Sustento de solicitud
                            </p>

                            <div class="space-y-3">
                                <p class="text-sm text-slate-600">
                                    Adjunta una foto o archivo como sustento del cambio. Este archivo se eliminará automáticamente cuando la solicitud sea aprobada.
                                </p>

                                <input
                                    type="file"
                                    id="archivo_sustento"
                                    accept=".jpg,.jpeg,.png,.pdf,.webp"
                                    class="block w-full text-sm text-slate-700 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-red-50 file:text-red-900 hover:file:bg-red-100">

                                <p class="text-[11px] text-slate-400">
                                    Permitido: JPG, PNG, WEBP o PDF. Máximo 5 MB.
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div><!-- /overflow-y-auto -->

        <!-- Footer con navegación del wizard -->
        <div class="modal-footer sticky bottom-0 z-20 px-6 lg:px-8 py-5 border-t border-slate-100 flex items-center justify-between">

            <!-- BOTÓN CERRAR -->
            <button onclick="cerrarModal()"
                class="px-5 py-2.5 rounded-xl border border-red-200 text-red-700 text-sm font-bold hover:bg-red-50 transition-all">
                ✕ Cerrar
            </button>

            <!-- NAVEGACIÓN -->
            <div class="flex items-center gap-3 ml-auto">
                <button id="btn-anterior" onclick="pasoAnterior()"
                    class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-bold hover:bg-slate-100 transition-all hidden">
                    ← Anterior
                </button>

                <button id="btn-siguiente" onclick="pasoSiguiente()"
                    class="px-6 py-2.5 rounded-xl bg-red-900 text-white text-sm font-bold hover:bg-[#310404] transition-all shadow-md">
                    Siguiente →
                </button>

                <button id="btn-guardar" onclick="guardarPerfil()"
                    class="px-6 py-2.5 rounded-xl bg-green-600 text-white text-sm font-bold hover:bg-green-700 transition-all shadow-md hidden">
                    ✓ Guardar Cambios
                </button>
            </div>

        </div>

    </div><!-- /panel -->
    </div><!-- /modal -->

    <!-- Modal Cambiar Clave -->
    <div id="modal-clave" class="fixed inset-0 z-[70] <?php echo $debeCambiarClave ? '' : 'hidden'; ?>" role="dialog" aria-modal="true">

        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"
            onclick="<?php echo $debeCambiarClave ? '' : 'cerrarModalClave()'; ?>">
        </div>

        <div class="absolute inset-0 flex items-center justify-center px-4">
            <div class="relative w-full max-w-md bg-white rounded-[32px] shadow-2xl border border-slate-200 overflow-hidden animate-in">

                <div class="bg-gradient-to-r from-[#310404] to-red-900 px-7 py-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-red-200 text-xs font-black uppercase tracking-[0.22em] mb-1">
                                Seguridad
                            </p>
                            <h2 class="text-white text-xl font-black">
                                Cambiar clave de acceso
                            </h2>
                        </div>

                        <?php if (!$debeCambiarClave): ?>
                            <button type="button" onclick="cerrarModalClave()" class="text-red-200 hover:text-white transition">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="p-7 space-y-5">
                    <div class="bg-red-50 border border-red-100 rounded-2xl p-4">
                        <p class="text-sm text-red-900 font-semibold leading-relaxed">
                            Para proteger tu cuenta, ingresa tu clave actual y luego define una nueva clave.
                        </p>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Clave actual</label>
                        <div class="relative">
                            <input type="password" id="clave_actual" class="field-input pr-12" autocomplete="current-password">
                            <button type="button" onclick="toggleClaveInput('clave_actual')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-red-900">
                                👁
                            </button>
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Nueva clave</label>
                        <div class="relative">
                            <input type="password" id="clave_nueva" class="field-input pr-12" autocomplete="new-password" minlength="8">
                            <button type="button" onclick="toggleClaveInput('clave_nueva')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-red-900">
                                👁
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-400 font-semibold">
                            Mínimo 8 caracteres. Recomendado: letras, números y símbolos.
                        </p>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Confirmar nueva clave</label>
                        <div class="relative">
                            <input type="password" id="clave_confirmar" class="field-input pr-12" autocomplete="new-password" minlength="8">
                            <button type="button" onclick="toggleClaveInput('clave_confirmar')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-red-900">
                                👁
                            </button>
                        </div>
                    </div>
                </div>

                <div class="px-7 py-5 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                    <?php if (!$debeCambiarClave): ?>
                        <button type="button" onclick="cerrarModalClave()"
                            class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-bold hover:bg-slate-100 transition-all">
                            Cancelar
                        </button>
                    <?php endif; ?>

                    <button type="button" id="btn-guardar-clave" onclick="guardarClavePerfil()"
                        class="px-6 py-2.5 rounded-xl bg-red-900 text-white text-sm font-bold hover:bg-[#310404] transition-all shadow-md">
                        Guardar clave
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- Toast de confirmación -->
    <div id="toast" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[9999] hidden pointer-events-none">
        <div class="bg-slate-800 text-white px-6 py-3 rounded-2xl shadow-2xl text-sm font-bold flex items-center gap-2">
            <span id="toast-icon">✓</span>
            <span id="toast-msg">Cambios guardados correctamente</span>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════
            ESTILOS
        ═══════════════════════════════════════════════ -->
    <style>
        /* ===============================
        TABS
        =============================== */
        .tab-btn {
            border-bottom: 3px solid transparent;
        }

        .tab-active {
            color: #7f1d1d;
            border-bottom-color: #7f1d1d;
        }

        .tab-idle {
            color: #94a3b8;
        }

        .tab-idle:hover {
            color: #475569;
        }

        /* ===============================
        ANIMACIONES
        =============================== */
        @keyframes fadeSlideIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-in {
            animation: fadeSlideIn .25s ease-out forwards;
        }

        /* ===============================
        CAMPOS
        =============================== */
        .field-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
        }

        .field-label {
            font-size: 11px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .field-input {
            width: 100%;
            min-height: 44px;
            padding: 11px 14px;
            border: 1.5px solid #dbe3ee;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            background: #f8fafc;
            transition: border-color .2s, background .2s, box-shadow .2s;
            outline: none;
            box-sizing: border-box;
        }

        .field-input:focus {
            border-color: #7f1d1d;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(127, 29, 29, .08);
        }

        .field-input[readonly] {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
        }

        .field-input::placeholder {
            color: #cbd5e1;
        }

        textarea.field-input {
            min-height: 110px;
            resize: vertical;
        }

        /* ===============================
        SCROLLBAR
        =============================== */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* ===============================
        MODAL BASE
        =============================== */
        #modal-perfil .modal-backdrop {
            transition: opacity .35s ease;
            opacity: 0;
        }

        #modal-perfil .modal-panel {
            transition: transform .35s cubic-bezier(.4, 0, .2, 1), opacity .35s ease;
            transform: translateY(16px);
            opacity: 0;
        }

        #modal-perfil.modal-open .modal-backdrop {
            opacity: 1;
        }

        #modal-perfil.modal-open .modal-panel {
            transform: translateY(0);
            opacity: 1;
        }

        /* ===============================
        HEADER / BODY / FOOTER MODAL
        =============================== */
        #modal-perfil .modal-progress {
            border-bottom: 1px solid #e2e8f0;
            background: #fff;
        }

        #modal-perfil .modal-body {
            padding-top: 28px;
            padding-bottom: 40px;
            background: linear-gradient(to bottom, #f8fafc 0%, #f1f5f9 100%);
        }

        #modal-perfil .modal-footer {
            box-shadow: 0 -8px 30px rgba(15, 23, 42, .06);
            background: #f8fafc;
        }

        /* ===============================
        STEPPER
        =============================== */
        #modal-perfil .step-indicator {
            min-height: 76px;
            border-radius: 22px;
            padding: 16px 18px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
        }

        #modal-perfil .step-indicator .step-circle {
            width: 34px;
            height: 34px;
            border-radius: 9999px;
            font-size: 11px;
            flex-shrink: 0;
        }

        /* ===============================
        STEP WRAPPER
        =============================== */
        #modal-perfil .form-step {
            margin-bottom: 20px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 28px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .05);
        }

        #modal-perfil .form-step.hidden {
            display: none !important;
        }

        #modal-perfil .form-step.block {
            display: block !important;
        }

        #modal-perfil .form-step>p.text-xs,
        #modal-perfil .form-step .step-title {
            margin-bottom: 16px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .12em;
            color: #94a3b8;
            text-transform: uppercase;
        }

        /* Limpia wrappers internos repetidos */
        #modal-perfil #form-step-1>.bg-white.border,
        #modal-perfil #form-step-3>.bg-slate-50.border,
        #modal-perfil #form-step-4>.bg-slate-50.border,
        #modal-perfil #form-step-5>.bg-slate-50.border {
            background: transparent !important;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            padding: 0 !important;
        }

        /* ===============================
        GRID DEL MODAL
        =============================== */
        #modal-perfil .form-grid-2 {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 16px;
        }

        @media (min-width: 768px) {
            #modal-perfil .form-grid-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        #modal-perfil .span-full {
            grid-column: span 1 / span 1;
        }

        @media (min-width: 768px) {
            #modal-perfil .span-full {
                grid-column: span 2 / span 2;
            }
        }

        /* ===============================
        BLOQUES INTERNOS
        =============================== */
        #modal-perfil .step-card,
        #modal-perfil .bg-slate-50.border {
            border-radius: 22px;
            padding: 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        /* ===============================
        FILAS DINÁMICAS
        =============================== */
        #modal-perfil .hijo-row,
        #modal-perfil .formacion-row,
        #modal-perfil .idioma-row,
        #modal-perfil .experiencia-row {
            border-radius: 18px;
            padding: 18px;
            background: #fff;
            border: 1px solid #e2e8f0;
        }

        /* ===============================
        RESUMEN FINAL
        =============================== */
        .resumen-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 12px 14px;
            background: #f8fafc;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
        }

        .resumen-item .r-label {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .resumen-item .r-val {
            font-size: 13px;
            font-weight: 800;
            color: #1e293b;
        }

        /* ===============================
        EXPERIENCIA DETALLE
        =============================== */
        .exp-toggle-icon.rotate-180 {
            transform: rotate(180deg);
        }

        /* ===============================
        MOBILE
        =============================== */
        @media (max-width: 640px) {
            #modal-perfil .step-indicator {
                min-height: auto;
                padding: 12px 14px;
            }

            #modal-perfil .step-indicator p:last-child {
                display: none;
            }

            #modal-perfil .form-step {
                padding: 18px;
                border-radius: 22px;
            }

            #modal-perfil .step-card,
            #modal-perfil .bg-slate-50.border {
                padding: 16px;
                border-radius: 18px;
            }

            .resumen-item {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        /* ===== TITULOS PREMIUM DE BLOQUES ===== */
        .section-title {
            position: relative;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #64748b;
            padding-left: 14px;
        }

        /* línea roja lateral */
        .section-title::before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 20px;
            border-radius: 4px;
            background: linear-gradient(180deg, #7f1d1d, #b91c1c);
        }

        /* opcional: línea sutil debajo */
        .section-divider {
            height: 2px;
            background: linear-gradient(to right, #7f1d1d 0%, #b91c1c 35%, rgba(185, 28, 28, 0.18) 70%, transparent 100%);
            margin-top: 8px;
            margin-bottom: 18px;
            border-radius: 999px;
        }
    </style>

    <!-- ═══════════════════════════════════════════════
            JAVASCRIPT
        ═══════════════════════════════════════════════ -->
    <script>
        function abrirModalDetalleContrato(id) {
            const modal = document.getElementById(id);
            if (!modal) return;

            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function cerrarModalDetalleContrato(id) {
            const modal = document.getElementById(id);
            if (!modal) return;

            modal.classList.add('hidden');

            const hayModalAbierto = document.querySelector('[id^="modal-detalle-contrato-"]:not(.hidden)');
            if (!hayModalAbierto) {
                document.body.classList.remove('overflow-hidden');
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key !== 'Escape') return;

            document.querySelectorAll('[id^="modal-detalle-contrato-"]').forEach(function(modal) {
                modal.classList.add('hidden');
            });

            document.body.classList.remove('overflow-hidden');
        });
        // ── MAGIA DE ABRIR/CERRAR FILAS ────────────────────────
        function toggleFila(btn, editar) {
            const row = btn.closest('.hijo-row, .formacion-row, .experiencia-row, .idioma-row, .contrato-row');
            if (!row) return;

            const resumen = row.querySelector('.item-resumen');
            const form = row.querySelector('.item-form');

            if (!resumen || !form) return;

            if (editar) {
                resumen.classList.add('hidden');
                resumen.classList.remove('flex');
                form.classList.remove('hidden');
            } else {
                resumen.classList.remove('hidden');
                resumen.classList.add('flex');
                form.classList.add('hidden');

                if (row.classList.contains('hijo-row')) {
                    const nombre = row.querySelector('.input-nombre')?.value?.trim() || 'Sin nombre';
                    const parentesco = row.querySelector('.input-parentesco')?.value || 'HIJO';
                    const dni = row.querySelector('.input-dni')?.value?.trim() || '—';

                    const elNombre = row.querySelector('.val-nombre');
                    const elParentesco = row.querySelector('.val-parentesco');
                    const elDni = row.querySelector('.val-dni');

                    if (elNombre) elNombre.textContent = nombre;
                    if (elParentesco) elParentesco.textContent = parentesco;
                    if (elDni) elDni.textContent = dni;
                } else if (row.classList.contains('formacion-row')) {
                    const carrera = row.querySelector('.input-carrera')?.value?.trim() || 'Sin carrera';
                    const grado = row.querySelector('.input-grado')?.value || 'BACHILLER';
                    const inst = row.querySelector('.input-inst')?.value?.trim() || 'Sin institución';

                    const elCarrera = row.querySelector('.val-carrera');
                    const elGrado = row.querySelector('.val-grado');
                    const elInst = row.querySelector('.val-inst');

                    if (elCarrera) elCarrera.textContent = carrera;
                    if (elGrado) elGrado.textContent = grado;
                    if (elInst) elInst.textContent = inst;
                } else if (row.classList.contains('experiencia-row')) {
                    const cargo = row.querySelector('.input-cargo')?.value?.trim() || 'Sin cargo';
                    const empresa = row.querySelector('.input-empresa')?.value?.trim() || 'Sin empresa';
                    const fechaInicio = row.querySelector('.input-inicio')?.value || '';
                    const fechaFin = row.querySelector('.input-fin')?.value || '';
                    const actual = row.querySelector('.input-actual')?.checked;

                    let textoFechas = 'Sin fechas';
                    if (fechaInicio) {
                        const ini = formatearFecha(fechaInicio);
                        if (actual) {
                            textoFechas = `${ini} - Actualidad`;
                        } else if (fechaFin) {
                            textoFechas = `${ini} - ${formatearFecha(fechaFin)}`;
                        } else {
                            textoFechas = ini;
                        }
                    }

                    const elCargo = row.querySelector('.val-cargo');
                    const elEmpresa = row.querySelector('.val-empresa');
                    const elFechas = row.querySelector('.val-fechas');

                    if (elCargo) elCargo.textContent = cargo;
                    if (elEmpresa) elEmpresa.textContent = empresa;
                    if (elFechas) elFechas.textContent = textoFechas;
                } else if (row.classList.contains('idioma-row')) {
                    const idioma = row.querySelector('.input-idioma')?.value?.trim() || 'Sin idioma';
                    const nivel = row.querySelector('.input-nivel')?.value || 'BASICO';

                    const elIdioma = row.querySelector('.val-idioma');
                    const elNivel = row.querySelector('.val-nivel');

                    if (elIdioma) elIdioma.textContent = idioma;
                    if (elNivel) elNivel.textContent = nivel;
                } else if (row.classList.contains('contrato-row')) {
                    const numeroDocumento = row.querySelector('.input-numero-documento')?.value?.trim() || '';
                    const fechaIngreso = row.querySelector('.input-fecha-ingreso')?.value || '';
                    const fechaCese = row.querySelector('.input-fecha-cese')?.value || '';
                    const modalidad = row.querySelector('.input-modalidad')?.value?.trim() || '—';
                    const motivoAdenda = row.querySelector('.input-motivo-adenda')?.value?.trim() || '—';
                    const esAdenda = (row.dataset.tipoRegistro || '').toUpperCase() === 'ADENDA';

                    const elNumero = row.querySelector('.val-numero-documento');
                    const elIngreso = row.querySelector('.val-fecha-ingreso');
                    const elCese = row.querySelector('.val-fecha-cese');
                    const elModalidad = row.querySelector('.val-modalidad');
                    const elMotivo = row.querySelector('.val-motivo-adenda');

                    if (elNumero) {
                        elNumero.textContent = numeroDocumento || (esAdenda ? 'Adenda sin número' : 'Contrato sin número');
                    }

                    if (elIngreso) {
                        elIngreso.textContent = fechaIngreso ? formatearFecha(fechaIngreso) : 'Sin fecha';
                    }

                    if (elCese) {
                        elCese.textContent = fechaCese ? formatearFecha(fechaCese) : (esAdenda ? '—' : 'Vigente');
                    }

                    if (elModalidad) {
                        elModalidad.textContent = modalidad;
                    }

                    if (elMotivo) {
                        elMotivo.textContent = motivoAdenda;
                    }
                }
            }
        }

        function formatearFecha(fecha) {
            if (!fecha) return '';
            const [y, m, d] = fecha.split('-');
            return `${d}/${m}/${y}`;
        }


        function eliminarFila(btn) {
            const row = btn.closest('.hijo-row') || btn.closest('.formacion-row') || btn.closest('.idioma-row') || btn.closest('.experiencia-row') || btn.closest('.contrato-row');
            if (!row) return;

            const isIdioma = row.classList.contains('idioma-row');
            const isHijo = row.classList.contains('hijo-row');
            const isFormacion = row.classList.contains('formacion-row');
            const isExperiencia = row.classList.contains('experiencia-row');
            const isContrato = row.classList.contains('contrato-row');

            row.style.opacity = '0';
            row.style.transform = 'translateY(-6px)';
            row.style.transition = 'all .2s ease';

            setTimeout(() => {
                row.remove();

                if (isHijo) {
                    const lista = document.getElementById('lista-hijos');
                    if (lista && !lista.querySelector('.hijo-row')) {
                        lista.innerHTML = '<div id="sin-hijos" class="text-center py-5 text-slate-400 text-xs">No hay hijos registrados. Haz clic en "Agregar hijo" para añadir.</div>';
                    }
                }

                if (isIdioma) {
                    const lista = document.getElementById('lista-idiomas');
                    if (lista && !lista.querySelector('.idioma-row')) {
                        lista.innerHTML = '<div id="sin-idiomas" class="text-center py-5 text-slate-400 text-xs">No hay idiomas registrados. Haz clic en "Agregar Idioma".</div>';
                    }
                }

                if (isFormacion) {
                    const lista = document.getElementById('lista-formacion');
                    if (lista && !lista.querySelector('.formacion-row')) {
                        lista.innerHTML = '<div id="sin-formacion" class="text-center py-5 text-slate-400 text-xs">No hay estudios registrados. Haz clic en "Agregar Estudio".</div>';
                    }
                }

                if (isExperiencia) {
                    const lista = document.getElementById('lista-experiencia');
                    if (lista && !lista.querySelector('.experiencia-row')) {
                        lista.innerHTML = '<div id="sin-experiencia" class="text-center py-5 text-slate-400 text-xs">No hay experiencia registrada. Haz clic en "Agregar Experiencia".</div>';
                    }
                }

                if (isContrato) {
                    const lista = document.getElementById('lista-contratos');
                    if (lista && !lista.querySelector('.contrato-row')) {
                        lista.innerHTML = '<div id="sin-contratos" class="text-center py-5 text-slate-400 text-xs border border-dashed border-slate-200 rounded-2xl bg-slate-50/70">No hay contratos registrados. Haz clic en "Agregar Contrato".</div>';
                    }
                }
            }, 200);
        }

        // ── HIJOS DINÁMICOS ───────────────────────────────
        let hijoIdx = <?php echo max(count(array_filter($perfil['familia'] ?? [], fn($f) => in_array($f['parentesco'], ['HIJO', 'HIJA']))), 0); ?>;
        let contratoIdx = document.querySelectorAll('.contrato-row').length;

        function agregarContrato() {
            const sin = document.getElementById('sin-contratos');
            if (sin) sin.remove();

            const idx = contratoIdx++;

            const html = `
            <div class="contrato-row bg-white border border-slate-200 rounded-2xl p-4 relative animate-in" data-index="${idx}" data-tipo-registro="CONTRATO">
                <div class="item-resumen hidden flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-red-50 text-red-900 border border-red-100">
                                Contrato padre
                            </span>
                        </div>

                        <p class="val-numero-documento text-sm font-black text-slate-800">Contrato sin número</p>

                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Inicio: <span class="val-fecha-ingreso">Sin fecha</span> •
                            Fin: <span class="val-fecha-cese">Vigente</span> •
                            Modalidad: <span class="val-modalidad">—</span>
                        </p>
                    </div>

                    <button type="button" onclick="toggleFila(this, true)"
                        class="text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg text-xs font-bold border border-red-100">
                        Editar
                    </button>
                </div>

                <div class="item-form mt-1 pt-1 relative">
                    <button type="button" onclick="eliminarFila(this)"
                        class="absolute -top-2 right-0 text-slate-300 hover:text-red-500 transition-colors p-1">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    <div class="form-grid-2 pr-6">
                        <div class="field-group span-full">
                            <label class="field-label">N° de contrato</label>
                            <input type="text" name="contratos[${idx}][numero_documento]" class="field-input input-numero-documento" placeholder="Ej. CONTRATO N° 001-2026">
                        </div>

                        <div class="field-group">
                            <label class="field-label">Fecha documento</label>
                            <input type="date" name="contratos[${idx}][fecha_documento]" class="field-input input-fecha-documento">
                        </div>

                        <div class="field-group">
                            <label class="field-label">Modalidad</label>
                            <select name="contratos[${idx}][modalidad]" class="field-input input-modalidad">
                                <option value="">Seleccionar</option>
                                <option value="CAS">CAS</option>
                                <option value="MILITAR">MILITAR</option>
                                <option value="PAC">PAC</option>
                            </select>
                        </div>

                        <div class="field-group">
                            <label class="field-label">Inicio contrato</label>
                            <input type="date" name="contratos[${idx}][fecha_ingreso]" class="field-input input-fecha-ingreso">
                        </div>

                        <div class="field-group">
                            <label class="field-label">Fin contrato</label>
                            <input type="date" name="contratos[${idx}][fecha_cese]" class="field-input input-fecha-cese">
                        </div>

                        <div class="field-group span-full">
                            <label class="field-label">Observación</label>
                            <textarea name="contratos[${idx}][observacion]" class="field-input input-observacion" rows="2"></textarea>
                        </div>

                        <input type="hidden" name="contratos[${idx}][id]" value="">
                        <input type="hidden" name="contratos[${idx}][tipo_registro]" value="CONTRATO">
                        <input type="hidden" name="contratos[${idx}][contrato_padre_id]" value="">
                        <input type="hidden" name="contratos[${idx}][motivo_adenda]" value="">
                    </div>

                    <div class="mt-3 text-right">
                        <button type="button" onclick="toggleFila(this, false)"
                            class="text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors bg-slate-100 px-3 py-1.5 rounded-lg">
                            ✓ Listo
                        </button>
                    </div>
                </div>
            </div>`;

            document.getElementById('lista-contratos').insertAdjacentHTML('beforeend', html);
        }

        function agregarAdenda(contratoPadreId, contratoLabel = 'Contrato padre', modalidadPadre = '') {
            const lista = document.getElementById('lista-contratos');
            if (!lista) return;

            const idx = contratoIdx++;

            const modalidadSeleccionada = (modalidadPadre || '').toUpperCase();

            const html = `
        <div class="contrato-row adenda-row bg-slate-50 border border-slate-200 rounded-2xl p-4 relative animate-in ml-0 md:ml-8"
            data-index="${idx}"
            data-tipo-registro="ADENDA">

            <div class="item-resumen hidden flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-900 text-white border border-slate-900">
                            Adenda
                        </span>

                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-white text-slate-600 border border-slate-200">
                            Padre: ${contratoLabel}
                        </span>
                    </div>

                    <p class="val-numero-documento text-sm font-black text-slate-800">Adenda sin número</p>

                    <p class="text-[11px] text-slate-500 mt-0.5">
                        Inicio: <span class="val-fecha-ingreso">Sin fecha</span> •
                        Fin: <span class="val-fecha-cese">—</span> •
                        Motivo: <span class="val-motivo-adenda">—</span>
                    </p>
                </div>

                <button type="button" onclick="toggleFila(this, true)"
                    class="text-red-900 bg-white hover:bg-red-50 px-3 py-1.5 rounded-lg text-xs font-bold border border-red-100">
                    Editar
                </button>
            </div>

            <div class="item-form mt-1 pt-1 relative">
                <button type="button" onclick="eliminarFila(this)"
                    class="absolute -top-2 right-0 text-slate-300 hover:text-red-500 transition-colors p-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <div class="form-grid-2 pr-6">
                    <div class="field-group span-full">
                        <label class="field-label">N° de adenda</label>
                        <input type="text" name="contratos[${idx}][numero_documento]" class="field-input input-numero-documento" placeholder="Ej. ADENDA N° 001-2026">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Fecha documento</label>
                        <input type="date" name="contratos[${idx}][fecha_documento]" class="field-input input-fecha-documento">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Modalidad</label>
                        <select name="contratos[${idx}][modalidad]" class="field-input input-modalidad">
                            <option value="">Heredar contrato padre</option>
                            <option value="CAS" ${modalidadSeleccionada === 'CAS' ? 'selected' : ''}>CAS</option>
                            <option value="MILITAR" ${modalidadSeleccionada === 'MILITAR' ? 'selected' : ''}>MILITAR</option>
                            <option value="PAC" ${modalidadSeleccionada === 'PAC' ? 'selected' : ''}>PAC</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Inicio adenda</label>
                        <input type="date" name="contratos[${idx}][fecha_ingreso]" class="field-input input-fecha-ingreso">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Fin adenda</label>
                        <input type="date" name="contratos[${idx}][fecha_cese]" class="field-input input-fecha-cese">
                    </div>

                    <div class="field-group span-full">
                        <label class="field-label">Motivo de adenda</label>
                        <input type="text" name="contratos[${idx}][motivo_adenda]" class="field-input input-motivo-adenda" placeholder="Ej. Ampliación de plazo, renovación, modificación contractual...">
                    </div>

                    <div class="field-group span-full">
                        <label class="field-label">Observación</label>
                        <textarea name="contratos[${idx}][observacion]" class="field-input input-observacion" rows="2"></textarea>
                    </div>

                    <input type="hidden" name="contratos[${idx}][id]" value="">
                    <input type="hidden" name="contratos[${idx}][tipo_registro]" value="ADENDA">
                    <input type="hidden" name="contratos[${idx}][contrato_padre_id]" value="${contratoPadreId}">
                </div>

                <div class="mt-3 text-right">
                    <button type="button" onclick="toggleFila(this, false)"
                        class="text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors bg-slate-100 px-3 py-1.5 rounded-lg">
                        ✓ Listo
                    </button>
                </div>
            </div>
        </div>`;

            lista.insertAdjacentHTML('beforeend', html);
        }

        function agregarHijo() {
            const sinHijos = document.getElementById('sin-hijos');
            if (sinHijos) sinHijos.remove();

            const idx = hijoIdx++;
            const html = `
            <div class="hijo-row bg-white border border-slate-200 rounded-xl p-4 relative animate-in" data-index="${idx}">
                <div class="item-resumen hidden items-center justify-between">
                    <div>
                        <p class="text-sm font-bold text-slate-800 val-nombre">Nuevo Hijo</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            <span class="val-parentesco">HIJO</span> • DNI: <span class="val-dni">—</span>
                        </p>
                    </div>
                    <button type="button" onclick="toggleFila(this, true)" class="text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg text-xs font-bold border border-red-100">Editar</button>
                </div>

                <div class="item-form mt-1 pt-1 relative">
                    <button type="button" onclick="eliminarFila(this)" class="absolute -top-2 right-0 text-slate-300 hover:text-red-500 transition-colors p-1">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <div class="grid grid-cols-2 gap-3 pr-6">
                        <div class="field-group col-span-2">
                            <label class="field-label">Nombre Completo</label>
                            <input type="text" name="hijos[${idx}][nombre]" class="field-input input-nombre" placeholder="Nombre y apellidos">
                        </div>
                        <div class="field-group">
                            <label class="field-label">Parentesco</label>
                            <select name="hijos[${idx}][parentesco]" class="field-input input-parentesco">
                                <option value="HIJO">Hijo</option>
                                <option value="HIJA">Hija</option>
                            </select>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Fecha Nacimiento</label>
                            <input type="date" name="hijos[${idx}][fecha_nacimiento]" class="field-input input-fecha">
                        </div>
                        <div class="field-group">
                            <label class="field-label">DNI</label>
                            <input type="text" name="hijos[${idx}][dni]" class="field-input input-dni" maxlength="8" placeholder="Opcional">
                        </div>
                        <input type="hidden" name="hijos[${idx}][id]" value="">
                    </div>
                    <div class="mt-3 text-right">
                        <button type="button" onclick="toggleFila(this, false)" class="text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors bg-slate-100 px-3 py-1.5 rounded-lg">✓ Listo</button>
                    </div>
                </div>
            </div>`;
            document.getElementById('lista-hijos').insertAdjacentHTML('beforeend', html);
        }

        // ── FORMACIÓN Y EXPERIENCIA DINÁMICA ────────────────────────────
        let formIdx = <?php echo count($formacion ?? []); ?>;
        let expIdx = <?php echo count($perfil['experiencia'] ?? []); ?>;
        let idiomaIdx = <?php echo count($perfil['idiomas'] ?? []); ?>;

        function agregarFormacion() {
            const sinForm = document.getElementById('sin-formacion');
            if (sinForm) sinForm.remove();

            const idx = formIdx++;
            const html = `
        <div class="formacion-row bg-white border border-slate-200 rounded-xl p-4 relative animate-in" data-index="${idx}">
            <div class="item-resumen hidden items-center justify-between">
                <div>
                    <p class="text-sm font-bold text-slate-800 val-carrera">Nuevo Estudio</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        <span class="val-grado">BACHILLER</span> • <span class="val-inst">—</span>
                    </p>
                </div>
                <button type="button" onclick="toggleFila(this, true)" class="text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg text-xs font-bold border border-red-100">Editar</button>
            </div>

            <div class="item-form mt-1 pt-1 relative">
                <button type="button" onclick="eliminarFila(this)" class="absolute -top-2 right-0 text-slate-300 hover:text-red-500 transition-colors p-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <div class="form-grid-2 pr-6">
                    <div class="field-group">
                        <label class="field-label">Tipo de Grado</label>
                        <select name="formacion[${idx}][tipo_grado]" class="field-input input-grado">
                            <option value="SECUNDARIA">Secundaria</option>
                            <option value="TÉCNICO">Técnico</option>
                            <option value="BACHILLER">Bachiller</option>
                            <option value="TÍTULO PROFESIONAL">Título Profesional</option>
                            <option value="MAESTRÍA">Maestría</option>
                            <option value="DOCTORADO">Doctorado</option>
                            <option value="ESPECIALIZACIÓN">Especialización</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Carrera / Especialidad</label>
                        <input type="text" name="formacion[${idx}][descripcion_carrera]" class="field-input input-carrera" placeholder="Ej: Ing. Sistemas">
                    </div>

                    <div class="field-group span-full">
                        <label class="field-label">Institución</label>
                        <input type="text" name="formacion[${idx}][institucion]" class="field-input input-inst" placeholder="Ej: Universidad Nacional...">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Año de Realización</label>
                        <input type="number" name="formacion[${idx}][anio_realizacion]" class="field-input" min="1900" max="2100" step="1" placeholder="Ej: 2020">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Horas Lectivas</label>
                        <input type="number" name="formacion[${idx}][horas_lectivas]" class="field-input" min="0" step="1" placeholder="Ej: 120">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Especialidad</label>
                        <input type="text" name="formacion[${idx}][especialidad]" class="field-input" placeholder="Ej: Gestión Pública">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Grado Alcanzado</label>
                        <input type="text" name="formacion[${idx}][grado_alcanzado]" class="field-input" placeholder="Ej: Egresado / Titulado / Concluido">
                    </div>

                    <input type="hidden" name="formacion[${idx}][id]" value="">
                </div>

                <div class="mt-3 text-right">
                    <button type="button" onclick="toggleFila(this, false)" class="text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors bg-slate-100 px-3 py-1.5 rounded-lg">✓ Listo</button>
                </div>
            </div>
        </div>`;
            document.getElementById('lista-formacion').insertAdjacentHTML('beforeend', html);
        }


        function agregarIdioma() {
            const sinIdiomas = document.getElementById('sin-idiomas');
            if (sinIdiomas) sinIdiomas.remove();

            const idx = idiomaIdx++;
            const html = `
                <div class="idioma-row bg-white border border-slate-200 rounded-xl p-4 relative animate-in" data-index="${idx}">
                    <div class="item-resumen hidden items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-slate-800 val-idioma">Nuevo idioma</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Nivel: <span class="val-nivel">BASICO</span>
                            </p>
                        </div>
                        <button type="button" onclick="toggleFila(this, true)" class="text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg text-xs font-bold border border-red-100">Editar</button>
                    </div>

                    <div class="item-form mt-1 pt-1 relative">
                        <button type="button" onclick="eliminarFila(this)" class="absolute -top-2 right-0 text-slate-300 hover:text-red-500 transition-colors p-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        <div class="grid grid-cols-2 gap-3 pr-6">
                            <div class="field-group">
                                <label class="field-label">Idioma</label>
                                <input type="text" name="idiomas[${idx}][idioma]" class="field-input input-idioma" placeholder="Ej: Inglés">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Nivel</label>
                                <select name="idiomas[${idx}][nivel]" class="field-input input-nivel">
                                    <option value="BASICO">Básico</option>
                                    <option value="INTERMEDIO">Intermedio</option>
                                    <option value="AVANZADO">Avanzado</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3 text-right">
                            <button type="button" onclick="toggleFila(this, false)" class="text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors bg-slate-100 px-3 py-1.5 rounded-lg">✓ Listo</button>
                        </div>
                    </div>
                </div>`;
            document.getElementById('lista-idiomas').insertAdjacentHTML('beforeend', html);
        }

        function agregarExperiencia() {
            const sinExp = document.getElementById('sin-experiencia');
            if (sinExp) sinExp.remove();

            const idx = expIdx++;
            const html = `
            <div class="experiencia-row bg-white border border-slate-200 rounded-xl p-4 relative animate-in" data-index="${idx}">
                <div class="item-resumen hidden items-center justify-between">
                    <div>
                        <p class="text-sm font-bold text-slate-800 val-cargo">Nuevo cargo</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            <span class="val-empresa">Nueva empresa</span> • <span class="val-fechas">—</span>
                        </p>
                    </div>
                    <button type="button" onclick="toggleFila(this, true)" class="text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg text-xs font-bold border border-red-100">Editar</button>
                </div>

                <div class="item-form mt-1 pt-1 relative">
                    <button type="button" onclick="eliminarFila(this)" class="absolute -top-2 right-0 text-slate-300 hover:text-red-500 transition-colors p-1">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    <div class="grid grid-cols-2 gap-3 pr-6">
                        <div class="field-group col-span-2">
                            <label class="field-label">Empresa / Entidad</label>
                            <input type="text" name="experiencia[${idx}][empresa_entidad]" class="field-input input-empresa" placeholder="Empresa o entidad">
                        </div>

                        <div class="field-group col-span-2">
                            <label class="field-label">Unidad Orgánica / Área</label>
                            <input type="text" name="experiencia[${idx}][unidad_organica_area]" class="field-input input-area" placeholder="Área o unidad orgánica">
                        </div>

                        <div class="field-group col-span-2">
                            <label class="field-label">Cargo / Puesto</label>
                            <input type="text" name="experiencia[${idx}][cargo_puesto]" class="field-input input-cargo" placeholder="Cargo o puesto">
                        </div>

                        <div class="field-group">
                            <label class="field-label">Fecha Inicio</label>
                            <input type="date" name="experiencia[${idx}][fecha_inicio]" class="field-input input-inicio">
                        </div>

                        <div class="field-group">
                            <label class="field-label">Fecha Fin</label>
                            <input type="date" name="experiencia[${idx}][fecha_fin]" class="field-input input-fin">
                        </div>

                        <div class="field-group col-span-2">
                            <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <input type="checkbox" name="experiencia[${idx}][actualmente_trabaja]" class="input-actual" value="1">
                                Actualmente trabaja aquí
                            </label>
                        </div>

                        <div class="field-group col-span-2">
                            <label class="field-label">Funciones Principales</label>
                            <textarea name="experiencia[${idx}][funciones_principales]" class="field-input input-funciones" rows="4" placeholder="Describe las funciones principales"></textarea>
                        </div>

                        <input type="hidden" name="experiencia[${idx}][id]" value="">
                    </div>

                    <div class="mt-3 text-right">
                        <button type="button" onclick="toggleFila(this, false)" class="text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors bg-slate-100 px-3 py-1.5 rounded-lg">✓ Listo</button>
                    </div>
                </div>
            </div>`;
            document.getElementById('lista-experiencia').insertAdjacentHTML('beforeend', html);
        }

        // ── TABS PRINCIPALES ──────────────────────────────
        function switchTab(id) {
            document.querySelectorAll('.tab-content').forEach(p => {
                p.classList.add('hidden');
                p.classList.remove('block');
            });

            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('tab-active');
                b.classList.add('tab-idle');
            });

            const panel = document.getElementById('tab-' + id);
            if (panel) {
                panel.classList.remove('hidden');
                panel.classList.add('block');
            }

            const btn = document.getElementById('btn-' + id);
            if (btn) {
                btn.classList.add('tab-active');
                btn.classList.remove('tab-idle');
            }
        }

        // ── MODAL & WIZARD ────────────────────────────────
        const valoresOriginales = {};

        function esVacioComparacion(valor) {
            if (valor === null || valor === undefined) return true;
            if (typeof valor === 'string') return valor.trim() === '';
            if (Array.isArray(valor)) return valor.length === 0;
            if (typeof valor === 'object') return Object.keys(valor).length === 0;
            return false;
        }

        function normalizarComparacion(valor, campo = '') {
            if (valor === null || valor === undefined) return '';

            if (typeof valor !== 'object') {
                let v = String(valor).trim();

                if (campo === 'sin_afp_afiliarme' && (v === '0' || v === 'false')) {
                    return '';
                }

                return v;
            }

            if (Array.isArray(valor)) {
                return valor
                    .map(item => normalizarComparacion(item, campo))
                    .filter(item => !esVacioComparacion(item))
                    .sort((a, b) => JSON.stringify(a).localeCompare(JSON.stringify(b)));
            }

            const ignorar = [
                'id',
                'colab_id',
                'usuario_id',
                'edad',
                'n_hijos',
                'estado_validacion',
                'archivo_sustento',
                'created_at',
                'updated_at'
            ];

            const salida = {};

            Object.keys(valor).sort().forEach(k => {
                if (ignorar.includes(k)) return;

                let key = k;

                if (key === 'nombre_completo') key = 'nombre';
                if (key === 'dni_familiar') key = 'dni';
                if (key === 'modalidad_contrato') key = 'mod_contrato';

                const normalizado = normalizarComparacion(valor[k], key);

                if (!esVacioComparacion(normalizado)) {
                    salida[key] = normalizado;
                }
            });

            return salida;
        }

        function sonIguales(a, b) {
            return JSON.stringify(normalizarComparacion(a)) === JSON.stringify(normalizarComparacion(b));
        }

        function obtenerPensionActual() {
            return {
                sistema_pension: document.querySelector('[name="pension[sistema_pension]"]')?.value || '',
                afp: document.querySelector('[name="pension[afp]"]')?.value || '',
                cuspp: document.querySelector('[name="pension[cuspp]"]')?.value?.trim() || '',
                tipo_comision: document.querySelector('[name="pension[tipo_comision]"]')?.value || '',
                fecha_inscripcion: document.querySelector('[name="pension[fecha_inscripcion]"]')?.value || '',
                sin_afp_afiliarme: document.querySelector('[name="pension[sin_afp_afiliarme]"]')?.checked ? 1 : 0
            };
        }

        function obtenerBancarioActual() {
            return {
                banco_haberes: document.querySelector('[name="bancario[banco_haberes]"]')?.value?.trim() || '',
                numero_cuenta: document.querySelector('[name="bancario[numero_cuenta]"]')?.value?.trim() || '',
                numero_cuenta_cci: document.querySelector('[name="bancario[numero_cuenta_cci]"]')?.value?.trim() || ''
            };
        }

        let pasoActual = 1;
        const totalPasos = 4;

        const gruposPasos = {
            1: [1],
            2: [3, 4],
            3: [6, 7],
            4: [9]
        };

        function leerContratoRow(row, index = 0) {
            const idx = row.dataset.index ?? index;

            return {
                idx: String(idx),
                id: row.querySelector(`[name="contratos[${idx}][id]"]`)?.value || '',
                tipo_registro: row.querySelector(`[name="contratos[${idx}][tipo_registro]"]`)?.value || 'CONTRATO',
                contrato_padre_id: row.querySelector(`[name="contratos[${idx}][contrato_padre_id]"]`)?.value || '',
                numero_documento: row.querySelector(`[name="contratos[${idx}][numero_documento]"]`)?.value?.trim() || '',
                fecha_documento: row.querySelector(`[name="contratos[${idx}][fecha_documento]"]`)?.value || '',
                fecha_ingreso: row.querySelector(`[name="contratos[${idx}][fecha_ingreso]"]`)?.value || '',
                fecha_cese: row.querySelector(`[name="contratos[${idx}][fecha_cese]"]`)?.value || '',
                modalidad: row.querySelector(`[name="contratos[${idx}][modalidad]"]`)?.value?.trim() || '',
                motivo_adenda: row.querySelector(`[name="contratos[${idx}][motivo_adenda]"]`)?.value?.trim() || '',
                observacion: row.querySelector(`[name="contratos[${idx}][observacion]"]`)?.value?.trim() || ''
            };
        }

        function contratoEstaVacio(item) {
            return !item.id &&
                !item.numero_documento &&
                !item.fecha_documento &&
                !item.fecha_ingreso &&
                !item.fecha_cese &&
                !item.modalidad &&
                !item.motivo_adenda &&
                !item.observacion;
        }


        function abrirModal() {
            Object.keys(valoresOriginales).forEach(key => delete valoresOriginales[key]);

            const m = document.getElementById('modal-perfil');
            if (!m) return;

            m.classList.remove('hidden');
            requestAnimationFrame(() => m.classList.add('modal-open'));

            document.querySelectorAll('.form-step [name]').forEach(el => {
                if (
                    !el.readOnly &&
                    !el.name.includes('hijos') &&
                    !el.name.includes('formacion') &&
                    !el.name.includes('experiencia') &&
                    !el.name.includes('idiomas[') &&
                    !el.name.includes('contratos[') &&
                    !el.name.startsWith('pension[') &&
                    !el.name.startsWith('bancario[')
                ) {
                    valoresOriginales[el.name] = el.type === 'checkbox' ?
                        (el.checked ? 1 : 0) :
                        (el.value ?? '').trim();
                }
            });

            valoresOriginales.hijos = [];
            document.querySelectorAll('.hijo-row').forEach((row, index) => {
                valoresOriginales.hijos.push({
                    idx: String(row.dataset.index ?? index),
                    id: row.querySelector('[name*="[id]"]')?.value || '',
                    nombre: row.querySelector('[name*="[nombre]"]')?.value?.trim() || '',
                    parentesco: row.querySelector('[name*="[parentesco]"]')?.value || 'HIJO',
                    fecha_nacimiento: row.querySelector('[name*="[fecha_nacimiento]"]')?.value || '',
                    dni: row.querySelector('[name*="[dni]"]')?.value?.trim() || ''
                });
            });

            valoresOriginales.formacion = [];
            document.querySelectorAll('.formacion-row').forEach((row, index) => {
                valoresOriginales.formacion.push({
                    idx: String(row.dataset.index ?? index),
                    id: row.querySelector('[name*="[id]"]')?.value || '',
                    tipo_grado: row.querySelector('[name*="[tipo_grado]"]')?.value || '',
                    descripcion_carrera: row.querySelector('[name*="[descripcion_carrera]"]')?.value?.trim() || '',
                    institucion: row.querySelector('[name*="[institucion]"]')?.value?.trim() || '',
                    anio_realizacion: row.querySelector('[name*="[anio_realizacion]"]')?.value || '',
                    horas_lectivas: row.querySelector('[name*="[horas_lectivas]"]')?.value || '',
                    especialidad: row.querySelector('[name*="[especialidad]"]')?.value?.trim() || '',
                    grado_alcanzado: row.querySelector('[name*="[grado_alcanzado]"]')?.value?.trim() || ''
                });
            });

            valoresOriginales.experiencia = [];
            document.querySelectorAll('.experiencia-row').forEach((row, index) => {
                valoresOriginales.experiencia.push({
                    idx: String(row.dataset.index ?? index),
                    id: row.querySelector('[name*="[id]"]')?.value || '',
                    empresa_entidad: row.querySelector('[name*="[empresa_entidad]"]')?.value?.trim() || '',
                    unidad_organica_area: row.querySelector('[name*="[unidad_organica_area]"]')?.value?.trim() || '',
                    cargo_puesto: row.querySelector('[name*="[cargo_puesto]"]')?.value?.trim() || '',
                    fecha_inicio: row.querySelector('[name*="[fecha_inicio]"]')?.value || '',
                    fecha_fin: row.querySelector('[name*="[fecha_fin]"]')?.value || '',
                    actualmente_trabaja: row.querySelector('[name*="[actualmente_trabaja]"]')?.checked ? 1 : 0,
                    funciones_principales: row.querySelector('[name*="[funciones_principales]"]')?.value?.trim() || ''
                });
            });

            valoresOriginales.idiomas = [];
            document.querySelectorAll('.idioma-row').forEach((row, index) => {
                valoresOriginales.idiomas.push({
                    idx: String(row.dataset.index ?? index),
                    idioma: row.querySelector('[name*="[idioma]"]')?.value?.trim() || '',
                    nivel: row.querySelector('[name*="[nivel]"]')?.value || 'BASICO'
                });
            });

            valoresOriginales.contratos = [];
            document.querySelectorAll('.contrato-row').forEach((row, index) => {
                valoresOriginales.contratos.push({
                    idx: String(row.dataset.index ?? index),
                    id: row.querySelector('[name*="[id]"]')?.value || '',
                    fecha_ingreso: row.querySelector('[name*="[fecha_ingreso]"]')?.value || '',
                    fecha_cese: row.querySelector('[name*="[fecha_cese]"]')?.value || '',
                    modalidad: row.querySelector('[name*="[modalidad]"]')?.value?.trim() || ''
                });
            });

            valoresOriginales.pension = {

                sistema_pension: document.querySelector('[name="pension[sistema_pension]"]')?.value || '',
                afp: document.querySelector('[name="pension[afp]"]')?.value || '',
                cuspp: document.querySelector('[name="pension[cuspp]"]')?.value?.trim() || '',
                tipo_comision: document.querySelector('[name="pension[tipo_comision]"]')?.value || '',
                fecha_inscripcion: document.querySelector('[name="pension[fecha_inscripcion]"]')?.value || '',
                sin_afp_afiliarme: document.querySelector('[name="pension[sin_afp_afiliarme]"]')?.checked ? 1 : 0
            };

            valoresOriginales.bancario = {
                banco_haberes: document.querySelector('[name="bancario[banco_haberes]"]')?.value?.trim() || '',
                numero_cuenta: document.querySelector('[name="bancario[numero_cuenta]"]')?.value?.trim() || '',
                numero_cuenta_cci: document.querySelector('[name="bancario[numero_cuenta_cci]"]')?.value?.trim() || ''
            };

            valoresOriginales.pension = obtenerPensionActual();
            valoresOriginales.bancario = obtenerBancarioActual();

            irPaso(1);
        }

        function cerrarModal(confirmar = true) {
            if (confirmar && !confirm('¿Deseas salir sin guardar los cambios?')) {
                return;
            }

            const m = document.getElementById('modal-perfil');

            if (!m) {
                return;
            }

            m.classList.remove('modal-open');

            setTimeout(() => {
                m.classList.add('hidden');
            }, 350);
        }

        function irPaso(n) {
            document.querySelectorAll('.form-step').forEach(s => {
                s.classList.add('hidden');
                s.classList.remove('block');
            });

            (gruposPasos[n] || []).forEach(realStep => {
                const bloque = document.getElementById('form-step-' + realStep);
                if (bloque) {
                    bloque.classList.remove('hidden');
                    bloque.classList.add('block');
                }
            });

            for (let i = 1; i <= totalPasos; i++) {
                const indicator = document.querySelector(`.step-indicator[data-step="${i}"]`);
                const circle = indicator?.querySelector('.step-circle');
                const numEl = indicator?.querySelector('.step-num');
                const chkEl = indicator?.querySelector('.step-check');
                const label = document.getElementById('step-label-' + i);

                if (!circle) continue;

                indicator.classList.remove('border-red-200', 'bg-red-50', 'border-green-200', 'bg-green-50');
                circle.classList.remove(
                    'bg-red-900', 'border-red-900', 'text-white',
                    'bg-green-500', 'border-green-500',
                    'bg-white', 'border-slate-200', 'text-slate-400'
                );
                label?.classList.remove('text-red-900', 'text-green-600', 'text-slate-400');

                if (i < n) {
                    indicator.classList.add('border-green-200', 'bg-green-50');
                    circle.classList.add('bg-green-500', 'border-green-500', 'text-white');
                    numEl?.classList.add('hidden');
                    chkEl?.classList.remove('hidden');
                    label?.classList.add('text-green-600');
                } else if (i === n) {
                    indicator.classList.add('border-red-200', 'bg-red-50');
                    circle.classList.add('bg-red-900', 'border-red-900', 'text-white');
                    numEl?.classList.remove('hidden');
                    chkEl?.classList.add('hidden');
                    label?.classList.add('text-red-900');
                } else {
                    circle.classList.add('bg-white', 'border-slate-200', 'text-slate-400');
                    numEl?.classList.remove('hidden');
                    chkEl?.classList.add('hidden');
                    label?.classList.add('text-slate-400');
                }
            }

            const msActual = document.getElementById('paso-actual');
            const msTotal = document.getElementById('paso-total');
            if (msActual) msActual.textContent = n;
            if (msTotal) msTotal.textContent = totalPasos;

            const btnAnt = document.getElementById('btn-anterior');
            const btnSig = document.getElementById('btn-siguiente');
            const btnGuar = document.getElementById('btn-guardar');

            btnAnt.classList.toggle('hidden', n === 1);
            btnSig.classList.toggle('hidden', n === totalPasos);
            btnGuar.classList.toggle('hidden', n !== totalPasos);

            if (n === totalPasos) construirResumen();

            pasoActual = n;

            const contenedorScroll = document.querySelector('#modal-perfil .flex-1.overflow-y-auto');
            if (contenedorScroll) contenedorScroll.scrollTop = 0;
        }

        function pasoSiguiente() {
            if (pasoActual < totalPasos) irPaso(pasoActual + 1);
        }

        function pasoAnterior() {
            if (pasoActual > 1) irPaso(pasoActual - 1);
        }

        function construirResumen() {
            const container = document.getElementById('resumen-cambios');
            if (!container) return;

            const cambios = [];

            const safe = (v) => {
                if (v === null || v === undefined || String(v).trim() === '') return '—';
                return String(v);
            };

            const iguales = sonIguales;

            const labelsCampos = {
                nombres_apellidos: 'Nombres y Apellidos',
                dni: 'DNI',
                fecha_nacimiento: 'Fecha de Nacimiento',
                lugar_nacimiento: 'Lugar de Nacimiento',
                estado_civil: 'Estado Civil',
                grupo_sanguineo: 'Grupo Sanguíneo',
                talla: 'Talla',
                direccion_residencia: 'Dirección',
                distrito: 'Distrito',
                celular: 'Celular',
                correo_personal: 'Correo Personal',
                correo_institucional: 'Correo Institucional',

                sueldo: 'Sueldo',
                mod_contrato: 'Contrato',
                puesto_cas: 'Puesto CAS',
                tipo_puesto: 'Tipo de Puesto',
                area: 'Área',
                procedencia: 'Procedencia',
                nsa_cip: 'NSA / CIP',
                situacion: 'Situación',

                conyuge: 'Cónyuge',
                onomastico_conyuge: 'Fecha Nac. Cónyuge',
                dni_conyuge: 'DNI Cónyuge'
            };

            document.querySelectorAll('.form-step [name]').forEach(el => {
                if (
                    el.readOnly ||
                    el.name.includes('hijos') ||
                    el.name.includes('formacion') ||
                    el.name.includes('experiencia') ||
                    el.name.includes('idiomas[') ||
                    el.name.includes('contratos[') ||
                    el.name.startsWith('pension[') ||
                    el.name.startsWith('bancario[')
                ) return;

                if (!labelsCampos[el.name]) return;

                const original = valoresOriginales[el.name] ?? '';
                const actual = el.type === 'checkbox' ? (el.checked ? 1 : 0) : ((el.value ?? '').trim());

                if (String(original) !== String(actual)) {
                    cambios.push(`
                    <div class="resumen-item">
                        <span class="r-label">${labelsCampos[el.name]}</span>
                        <div class="text-right">
                            <p class="text-[11px] text-slate-400 line-through">${safe(original)}</p>
                            <p class="r-val text-green-700">${safe(actual)}</p>
                        </div>
                    </div>
                `);
                }
            });

            // HIJOS
            const hijosActuales = [];
            document.querySelectorAll('.hijo-row').forEach((row, index) => {
                hijosActuales.push({
                    idx: String(row.dataset.index ?? index),
                    id: row.querySelector('[name*="[id]"]')?.value || '',
                    nombre: row.querySelector('[name*="[nombre]"]')?.value?.trim() || '',
                    parentesco: row.querySelector('[name*="[parentesco]"]')?.value || 'HIJO',
                    fecha_nacimiento: row.querySelector('[name*="[fecha_nacimiento]"]')?.value || '',
                    dni: row.querySelector('[name*="[dni]"]')?.value?.trim() || ''
                });
            });

            const hijosOriginales = valoresOriginales.hijos || [];

            hijosActuales.forEach(actual => {
                const original = hijosOriginales.find(x => x.idx === actual.idx);

                const actualVacio = !actual.id &&
                    !actual.nombre &&
                    !actual.parentesco &&
                    !actual.fecha_nacimiento &&
                    !actual.dni;

                if (actualVacio) return;

                if (!original) {
                    cambios.push(`
                    <div class="resumen-item border-l-4 border-emerald-500">
                        <span class="r-label text-emerald-600">Nuevo Hijo</span>
                        <span class="r-val">${safe(actual.nombre)} (${safe(actual.parentesco)})</span>
                    </div>
                `);
                    return;
                }

                const originalCmp = {
                    nombre: original.nombre,
                    parentesco: original.parentesco,
                    fecha_nacimiento: original.fecha_nacimiento,
                    dni: original.dni
                };

                const actualCmp = {
                    nombre: actual.nombre,
                    parentesco: actual.parentesco,
                    fecha_nacimiento: actual.fecha_nacimiento,
                    dni: actual.dni
                };

                if (!iguales(originalCmp, actualCmp)) {
                    cambios.push(`
                    <div class="resumen-item border-l-4 border-amber-500">
                        <span class="r-label text-amber-600">Hijo Editado</span>
                        <span class="r-val">${safe(actual.nombre)} (${safe(actual.parentesco)})</span>
                    </div>
                `);
                }
            });

            hijosOriginales.forEach(original => {
                const existe = hijosActuales.find(x => x.idx === original.idx);
                if (!existe) {
                    cambios.push(`
                    <div class="resumen-item border-l-4 border-red-500">
                        <span class="r-label text-red-600">Hijo Eliminado</span>
                        <span class="r-val">${safe(original.nombre)} (${safe(original.parentesco)})</span>
                    </div>
                `);
                }
            });

            // FORMACIÓN
            const formacionActual = [];
            document.querySelectorAll('.formacion-row').forEach((row, index) => {
                formacionActual.push({
                    idx: String(row.dataset.index ?? index),
                    id: row.querySelector('[name*="[id]"]')?.value || '',
                    tipo_grado: row.querySelector('[name*="[tipo_grado]"]')?.value || '',
                    descripcion_carrera: row.querySelector('[name*="[descripcion_carrera]"]')?.value?.trim() || '',
                    institucion: row.querySelector('[name*="[institucion]"]')?.value?.trim() || '',
                    anio_realizacion: row.querySelector('[name*="[anio_realizacion]"]')?.value || '',
                    horas_lectivas: row.querySelector('[name*="[horas_lectivas]"]')?.value || '',
                    especialidad: row.querySelector('[name*="[especialidad]"]')?.value?.trim() || '',
                    grado_alcanzado: row.querySelector('[name*="[grado_alcanzado]"]')?.value?.trim() || ''
                });
            });

            const formacionOriginal = valoresOriginales.formacion || [];

            formacionActual.forEach(actual => {
                const original = formacionOriginal.find(x => x.idx === actual.idx);

                const actualVacio = !actual.id &&
                    !actual.tipo_grado &&
                    !actual.descripcion_carrera &&
                    !actual.institucion &&
                    !actual.anio_realizacion &&
                    !actual.horas_lectivas &&
                    !actual.especialidad &&
                    !actual.grado_alcanzado;

                if (actualVacio) return;

                if (!original) {
                    cambios.push(`
                    <div class="resumen-item border-l-4 border-emerald-500">
                        <span class="r-label text-emerald-600">Nuevo Estudio</span>
                        <span class="r-val">${safe(actual.descripcion_carrera)} - ${safe(actual.institucion)}</span>
                    </div>
                `);
                    return;
                }

                const originalCmp = {
                    tipo_grado: original.tipo_grado,
                    descripcion_carrera: original.descripcion_carrera,
                    institucion: original.institucion,
                    anio_realizacion: original.anio_realizacion,
                    horas_lectivas: original.horas_lectivas,
                    especialidad: original.especialidad,
                    grado_alcanzado: original.grado_alcanzado
                };

                const actualCmp = {
                    tipo_grado: actual.tipo_grado,
                    descripcion_carrera: actual.descripcion_carrera,
                    institucion: actual.institucion,
                    anio_realizacion: actual.anio_realizacion,
                    horas_lectivas: actual.horas_lectivas,
                    especialidad: actual.especialidad,
                    grado_alcanzado: actual.grado_alcanzado
                };

                if (!iguales(originalCmp, actualCmp)) {
                    cambios.push(`
                    <div class="resumen-item border-l-4 border-amber-500">
                        <span class="r-label text-amber-600">Estudio Editado</span>
                        <span class="r-val">${safe(actual.descripcion_carrera)} - ${safe(actual.institucion)}</span>
                    </div>
                `);
                }
            });

            formacionOriginal.forEach(original => {
                const existe = formacionActual.find(x => x.idx === original.idx);
                if (!existe) {
                    cambios.push(`
                    <div class="resumen-item border-l-4 border-red-500">
                        <span class="r-label text-red-600">Estudio Eliminado</span>
                        <span class="r-val">${safe(original.descripcion_carrera)} - ${safe(original.institucion)}</span>
                    </div>
                `);
                }
            });

            // EXPERIENCIA
            const experienciaActual = [];
            document.querySelectorAll('.experiencia-row').forEach((row, index) => {
                experienciaActual.push({
                    idx: String(row.dataset.index ?? index),
                    id: row.querySelector('[name*="[id]"]')?.value || '',
                    empresa_entidad: row.querySelector('[name*="[empresa_entidad]"]')?.value?.trim() || '',
                    unidad_organica_area: row.querySelector('[name*="[unidad_organica_area]"]')?.value?.trim() || '',
                    cargo_puesto: row.querySelector('[name*="[cargo_puesto]"]')?.value?.trim() || '',
                    fecha_inicio: row.querySelector('[name*="[fecha_inicio]"]')?.value || '',
                    fecha_fin: row.querySelector('[name*="[fecha_fin]"]')?.value || '',
                    actualmente_trabaja: row.querySelector('[name*="[actualmente_trabaja]"]')?.checked ? 1 : 0,
                    funciones_principales: row.querySelector('[name*="[funciones_principales]"]')?.value?.trim() || ''
                });
            });

            const experienciaOriginal = valoresOriginales.experiencia || [];

            experienciaActual.forEach(actual => {
                const original = experienciaOriginal.find(x => x.idx === actual.idx);

                const actualVacio = !actual.id &&
                    !actual.empresa_entidad &&
                    !actual.unidad_organica_area &&
                    !actual.cargo_puesto &&
                    !actual.fecha_inicio &&
                    !actual.fecha_fin &&
                    !actual.actualmente_trabaja &&
                    !actual.funciones_principales;

                if (actualVacio) return;

                if (!original) {
                    cambios.push(`
                    <div class="resumen-item border-l-4 border-emerald-500">
                        <span class="r-label text-emerald-600">Nueva Experiencia</span>
                        <span class="r-val">${safe(actual.cargo_puesto)} - ${safe(actual.empresa_entidad)}</span>
                    </div>
                `);
                    return;
                }

                const originalCmp = {
                    empresa_entidad: original.empresa_entidad,
                    unidad_organica_area: original.unidad_organica_area,
                    cargo_puesto: original.cargo_puesto,
                    fecha_inicio: original.fecha_inicio,
                    fecha_fin: original.fecha_fin,
                    actualmente_trabaja: original.actualmente_trabaja,
                    funciones_principales: original.funciones_principales
                };

                const actualCmp = {
                    empresa_entidad: actual.empresa_entidad,
                    unidad_organica_area: actual.unidad_organica_area,
                    cargo_puesto: actual.cargo_puesto,
                    fecha_inicio: actual.fecha_inicio,
                    fecha_fin: actual.fecha_fin,
                    actualmente_trabaja: actual.actualmente_trabaja,
                    funciones_principales: actual.funciones_principales
                };

                if (!iguales(originalCmp, actualCmp)) {
                    cambios.push(`
                    <div class="resumen-item border-l-4 border-amber-500">
                        <span class="r-label text-amber-600">Experiencia Editada</span>
                        <span class="r-val">${safe(actual.cargo_puesto)} - ${safe(actual.empresa_entidad)}</span>
                    </div>
                `);
                }
            });

            experienciaOriginal.forEach(original => {
                const existe = experienciaActual.find(x => x.idx === original.idx);
                if (!existe) {
                    cambios.push(`
                    <div class="resumen-item border-l-4 border-red-500">
                        <span class="r-label text-red-600">Experiencia Eliminada</span>
                        <span class="r-val">${safe(original.cargo_puesto)} - ${safe(original.empresa_entidad)}</span>
                    </div>
                `);
                }
            });

            // IDIOMAS
            const idiomasActuales = [];
            document.querySelectorAll('.idioma-row').forEach((row, index) => {
                idiomasActuales.push({
                    idx: String(row.dataset.index ?? index),
                    idioma: row.querySelector('[name*="[idioma]"]')?.value?.trim() || '',
                    nivel: row.querySelector('[name*="[nivel]"]')?.value || 'BASICO'
                });
            });

            const idiomasOriginales = valoresOriginales.idiomas || [];

            idiomasActuales.forEach(actual => {
                const original = idiomasOriginales.find(x => x.idx === actual.idx);

                const actualVacio = !actual.idioma && !actual.nivel;
                if (actualVacio) return;

                if (!original) {
                    cambios.push(`
                    <div class="resumen-item border-l-4 border-emerald-500">
                        <span class="r-label text-emerald-600">Nuevo Idioma</span>
                        <span class="r-val">${safe(actual.idioma)} - ${safe(actual.nivel)}</span>
                    </div>
                `);
                    return;
                }

                const originalCmp = {
                    idioma: original.idioma,
                    nivel: original.nivel
                };

                const actualCmp = {
                    idioma: actual.idioma,
                    nivel: actual.nivel
                };

                if (!iguales(originalCmp, actualCmp)) {
                    cambios.push(`
                    <div class="resumen-item border-l-4 border-amber-500">
                        <span class="r-label text-amber-600">Idioma Editado</span>
                        <span class="r-val">${safe(actual.idioma)} - ${safe(actual.nivel)}</span>
                    </div>
                `);
                }
            });

            idiomasOriginales.forEach(original => {
                const existe = idiomasActuales.find(x => x.idx === original.idx);
                if (!existe) {
                    cambios.push(`
                    <div class="resumen-item border-l-4 border-red-500">
                        <span class="r-label text-red-600">Idioma Eliminado</span>
                        <span class="r-val">${safe(original.idioma)} - ${safe(original.nivel)}</span>
                    </div>
                `);
                }
            });

            // CONTRATOS
            const contratosActuales = [];
            document.querySelectorAll('.contrato-row').forEach((row, index) => {
                contratosActuales.push({
                    idx: String(row.dataset.index ?? index),
                    id: row.querySelector('[name*="[id]"]')?.value || '',
                    fecha_ingreso: row.querySelector('[name*="[fecha_ingreso]"]')?.value || '',
                    fecha_cese: row.querySelector('[name*="[fecha_cese]"]')?.value || '',
                    modalidad: row.querySelector('[name*="[modalidad]"]')?.value?.trim() || ''
                });
            });

            const contratosOriginales = valoresOriginales.contratos || [];

            contratosActuales.forEach(actual => {
                const original = contratosOriginales.find(x => x.idx === actual.idx);

                const actualVacio = !actual.id &&
                    !actual.fecha_ingreso &&
                    !actual.fecha_cese &&
                    !actual.modalidad;

                if (actualVacio) return;

                if (!original) {
                    cambios.push(`
                    <div class="resumen-item border-l-4 border-emerald-500">
                        <span class="r-label text-emerald-600">Nuevo Contrato</span>
                        <span class="r-val">${safe(actual.modalidad)} • ${safe(actual.fecha_ingreso || 'Sin ingreso')}</span>
                    </div>
                `);
                    return;
                }

                const originalCmp = {
                    fecha_ingreso: original.fecha_ingreso,
                    fecha_cese: original.fecha_cese,
                    modalidad: original.modalidad
                };

                const actualCmp = {
                    fecha_ingreso: actual.fecha_ingreso,
                    fecha_cese: actual.fecha_cese,
                    modalidad: actual.modalidad
                };

                if (!iguales(originalCmp, actualCmp)) {
                    cambios.push(`
                    <div class="resumen-item border-l-4 border-amber-500">
                        <span class="r-label text-amber-600">Contrato Editado</span>
                        <span class="r-val">${safe(actual.modalidad)} • ${safe(actual.fecha_ingreso || 'Sin ingreso')}</span>
                    </div>
                `);
                }
            });

            contratosOriginales.forEach(original => {
                const existe = contratosActuales.find(x => x.idx === original.idx);
                if (!existe) {
                    cambios.push(`
                    <div class="resumen-item border-l-4 border-red-500">
                        <span class="r-label text-red-600">Contrato Eliminado</span>
                        <span class="r-val">${safe(original.modalidad)} • ${safe(original.fecha_ingreso || 'Sin ingreso')}</span>
                    </div>
                `);
                }
            });
            // PENSIÓN
            const pensionActual = {
                sistema_pension: document.querySelector('[name="pension[sistema_pension]"]')?.value || '',
                afp: document.querySelector('[name="pension[afp]"]')?.value || '',
                cuspp: document.querySelector('[name="pension[cuspp]"]')?.value?.trim() || '',
                tipo_comision: document.querySelector('[name="pension[tipo_comision]"]')?.value || '',
                fecha_inscripcion: document.querySelector('[name="pension[fecha_inscripcion]"]')?.value || '',
                sin_afp_afiliarme: document.querySelector('[name="pension[sin_afp_afiliarme]"]')?.checked ? 1 : 0
            };

            if (!iguales(valoresOriginales.pension || {}, pensionActual)) {
                cambios.push(`
                <div class="resumen-item border-l-4 border-violet-500">
                    <span class="r-label text-violet-600">Pensión</span>
                    <span class="r-val">Actualizada</span>
                </div>
            `);
            }

            // BANCARIO
            const bancarioActual = {
                banco_haberes: document.querySelector('[name="bancario[banco_haberes]"]')?.value?.trim() || '',
                numero_cuenta: document.querySelector('[name="bancario[numero_cuenta]"]')?.value?.trim() || '',
                numero_cuenta_cci: document.querySelector('[name="bancario[numero_cuenta_cci]"]')?.value?.trim() || ''
            };

            if (!iguales(valoresOriginales.bancario || {}, bancarioActual)) {
                cambios.push(`
                <div class="resumen-item border-l-4 border-rose-500">
                    <span class="r-label text-rose-600">Datos Bancarios</span>
                    <span class="r-val">Actualizados</span>
                </div>
            `);
            }

            container.innerHTML = cambios.length ?
                `<p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-3">${cambios.length} cambio(s) detectado(s)</p><div class="space-y-2">${cambios.join('')}</div>` :
                `<div class="text-center py-8 text-slate-400 text-sm">No realizaste ningún cambio.</div>`;
        }

        // ── GUARDAR VÍA AJAX ──────────────────────────────

        function guardarPerfil() {
            const btn = document.getElementById('btn-guardar');
            const rolSesion = "<?php echo $rolSesion; ?>";

            if (!btn) return;

            if (rolSesion === 'colaborador') {
                const sustentoInput = document.getElementById('archivo_sustento');

                if (!sustentoInput || !sustentoInput.files || sustentoInput.files.length === 0) {
                    mostrarToast(
                        '⚠',
                        'Recuerda que cada cambio requiere un sustento en foto o PDF.',
                        'bg-red-800'
                    );

                    if (sustentoInput) {
                        sustentoInput.classList.add('ring-2', 'ring-red-500', 'rounded-xl');
                        sustentoInput.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });

                        setTimeout(() => {
                            sustentoInput.classList.remove('ring-2', 'ring-red-500');
                        }, 3500);
                    }

                    return;
                }
            }

            btn.textContent = 'Guardando…';
            btn.disabled = true;

            const campos = {};

            campos['id'] = <?php echo (int)($perfil['id'] ?? $data['id'] ?? 0); ?>;
            campos['id_colaborador'] = <?php echo (int)($perfil['id'] ?? $data['id'] ?? 0); ?>;

            document.querySelectorAll('.form-step [name]').forEach(el => {
                if (
                    !el.readOnly &&
                    !el.name.includes('hijos') &&
                    !el.name.includes('formacion') &&
                    !el.name.includes('experiencia') &&
                    !el.name.includes('idiomas[') &&
                    !el.name.includes('contratos[') &&
                    !el.name.startsWith('pension[') &&
                    !el.name.startsWith('bancario[') &&
                    el.type !== 'file'
                ) {
                    campos[el.name] = el.type === 'checkbox' ? (el.checked ? 1 : 0) : (el.value ?? '');
                }
            });

            const hijos = [];

            document.querySelectorAll('.hijo-row').forEach(row => {
                const idx = row.dataset.index;

                const item = {
                    id: row.querySelector(`[name="hijos[${idx}][id]"]`)?.value || '',
                    nombre: row.querySelector(`[name="hijos[${idx}][nombre]"]`)?.value?.trim() || '',
                    parentesco: row.querySelector(`[name="hijos[${idx}][parentesco]"]`)?.value || 'HIJO',
                    fecha_nacimiento: row.querySelector(`[name="hijos[${idx}][fecha_nacimiento]"]`)?.value || '',
                    dni: row.querySelector(`[name="hijos[${idx}][dni]"]`)?.value?.trim() || ''
                };

                if (item.id || item.nombre || item.fecha_nacimiento || item.dni) {
                    hijos.push(item);
                }
            });

            campos['hijos'] = hijos;

            const contratos = [];

            document.querySelectorAll('.contrato-row').forEach((row, index) => {
                const item = typeof leerContratoRow === 'function' ?
                    leerContratoRow(row, index) :
                    null;

                if (item && (typeof contratoEstaVacio !== 'function' || !contratoEstaVacio(item))) {
                    contratos.push(item);
                }
            });

            campos['contratos'] = contratos;

            const formacion = [];

            document.querySelectorAll('.formacion-row').forEach(row => {
                const idx = row.dataset.index;

                const item = {
                    id: row.querySelector(`[name="formacion[${idx}][id]"]`)?.value || '',
                    tipo_grado: row.querySelector(`[name="formacion[${idx}][tipo_grado]"]`)?.value || '',
                    descripcion_carrera: row.querySelector(`[name="formacion[${idx}][descripcion_carrera]"]`)?.value?.trim() || '',
                    institucion: row.querySelector(`[name="formacion[${idx}][institucion]"]`)?.value?.trim() || '',
                    anio_realizacion: row.querySelector(`[name="formacion[${idx}][anio_realizacion]"]`)?.value || '',
                    horas_lectivas: row.querySelector(`[name="formacion[${idx}][horas_lectivas]"]`)?.value || '',
                    especialidad: row.querySelector(`[name="formacion[${idx}][especialidad]"]`)?.value?.trim() || '',
                    grado_alcanzado: row.querySelector(`[name="formacion[${idx}][grado_alcanzado]"]`)?.value?.trim() || ''
                };

                if (
                    item.id ||
                    item.tipo_grado ||
                    item.descripcion_carrera ||
                    item.institucion ||
                    item.anio_realizacion ||
                    item.horas_lectivas ||
                    item.especialidad ||
                    item.grado_alcanzado
                ) {
                    formacion.push(item);
                }
            });

            campos['formacion'] = formacion;

            const experiencia = [];

            document.querySelectorAll('.experiencia-row').forEach(row => {
                const idx = row.dataset.index;

                const item = {
                    id: row.querySelector(`[name="experiencia[${idx}][id]"]`)?.value || '',
                    empresa_entidad: row.querySelector(`[name="experiencia[${idx}][empresa_entidad]"]`)?.value?.trim() || '',
                    unidad_organica_area: row.querySelector(`[name="experiencia[${idx}][unidad_organica_area]"]`)?.value?.trim() || '',
                    cargo_puesto: row.querySelector(`[name="experiencia[${idx}][cargo_puesto]"]`)?.value?.trim() || '',
                    fecha_inicio: row.querySelector(`[name="experiencia[${idx}][fecha_inicio]"]`)?.value || '',
                    fecha_fin: row.querySelector(`[name="experiencia[${idx}][fecha_fin]"]`)?.value || '',
                    actualmente_trabaja: row.querySelector(`[name="experiencia[${idx}][actualmente_trabaja]"]`)?.checked ? 1 : 0,
                    funciones_principales: row.querySelector(`[name="experiencia[${idx}][funciones_principales]"]`)?.value?.trim() || ''
                };

                if (
                    item.id ||
                    item.empresa_entidad ||
                    item.unidad_organica_area ||
                    item.cargo_puesto ||
                    item.fecha_inicio ||
                    item.fecha_fin ||
                    item.actualmente_trabaja ||
                    item.funciones_principales
                ) {
                    experiencia.push(item);
                }
            });

            campos['experiencia'] = experiencia;

            const idiomas = [];

            document.querySelectorAll('.idioma-row').forEach(row => {
                const idx = row.dataset.index;

                const idioma = row.querySelector(`[name="idiomas[${idx}][idioma]"]`)?.value?.trim() || '';
                const nivel = row.querySelector(`[name="idiomas[${idx}][nivel]"]`)?.value || 'BASICO';

                if (idioma) {
                    idiomas.push({
                        idioma,
                        nivel
                    });
                }
            });

            campos['idiomas'] = idiomas;

            if (typeof obtenerPensionActual === 'function') {
                campos['pension'] = obtenerPensionActual();
            }

            if (typeof obtenerBancarioActual === 'function') {
                campos['bancario'] = obtenerBancarioActual();
            }

            const formData = new FormData();
            formData.append('payload', JSON.stringify(campos));

            const sustentoInput = document.getElementById('archivo_sustento');

            if (sustentoInput && sustentoInput.files && sustentoInput.files[0]) {
                formData.append('archivo_sustento', sustentoInput.files[0]);
            }

            fetch('<?php echo BASE_URL; ?>/perfil/actualizar', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(async response => {
                    const raw = await response.text();

                    let res;

                    try {
                        res = JSON.parse(raw.trim());
                    } catch (e) {
                        console.error('Respuesta no JSON del servidor:', raw);
                        throw new Error(raw || 'La respuesta del servidor no fue válida.');
                    }

                    if (!response.ok || !res.success) {
                        throw new Error(res.mensaje || 'No se pudo guardar la información.');
                    }

                    return res;
                })
                .then(res => {
                    cerrarModal(false);
                    mostrarToast('✓', res.mensaje || 'Perfil actualizado correctamente.', 'bg-green-700');

                    setTimeout(() => {
                        window.location.href = res.redirect || window.location.href;
                    }, 900);
                })
                .catch(err => {
                    console.error(err);

                    mostrarToast(
                        '✗',
                        err.message && err.message.length < 180 ?
                        err.message :
                        'No se pudo guardar. Revisa el log del servidor.',
                        'bg-red-800'
                    );
                })
                .finally(() => {
                    btn.textContent = '✓ Guardar Cambios';
                    btn.disabled = false;
                });
        }


        document.addEventListener('DOMContentLoaded', () => {

            const inputFile = document.querySelector('input[name="sustento"]');
            const btnGuardar = document.getElementById('btn-guardar-perfil');

            if (!inputFile || !btnGuardar) return;

            function validarArchivo() {
                btnGuardar.disabled = inputFile.files.length === 0;
                btnGuardar.classList.toggle('opacity-50', btnGuardar.disabled);
                btnGuardar.classList.toggle('cursor-not-allowed', btnGuardar.disabled);
            }

            validarArchivo();
            inputFile.addEventListener('change', validarArchivo);
        });

        // ── TOAST ─────────────────────────────────────────
        function mostrarToast(icon, msg, bgClass) {
            const t = document.getElementById('toast');
            const div = t.querySelector('div');
            document.getElementById('toast-icon').textContent = icon;
            document.getElementById('toast-msg').textContent = msg;
            div.className = `${bgClass} text-white px-6 py-3 rounded-2xl shadow-2xl text-sm font-bold flex items-center gap-2`;
            t.classList.remove('hidden');
            setTimeout(() => t.classList.add('hidden'), 3000);
        }

        function toggleExperienciaDetalle(id, btn) {
            const panel = document.getElementById(id);
            if (!panel) return;

            const text = btn.querySelector('.exp-toggle-text');
            const icon = btn.querySelector('.exp-toggle-icon');

            const abierto = !panel.classList.contains('hidden');

            if (abierto) {
                panel.classList.add('hidden');
                if (text) text.textContent = 'Ver más';
                if (icon) icon.classList.remove('rotate-180');
            } else {
                panel.classList.remove('hidden');
                if (text) text.textContent = 'Ver menos';
                if (icon) icon.classList.add('rotate-180');
            }
        }

        document.addEventListener('keydown', e => {
            if (e.key !== 'Escape') return;

            const modalClave = document.getElementById('modal-clave');
            const modalClaveAbierto = modalClave && !modalClave.classList.contains('hidden');

            if (modalClaveAbierto) {
                if (DEBE_CAMBIAR_CLAVE) {
                    mostrarToast('⚠', 'Debes cambiar tu clave para continuar.', 'bg-red-800');
                    return;
                }

                cerrarModalClave();
                return;
            }

            const modalPerfil = document.getElementById('modal-perfil');
            const modalPerfilAbierto = modalPerfil && !modalPerfil.classList.contains('hidden');

            if (modalPerfilAbierto) {
                cerrarModal();
            }
        });

        document.querySelectorAll('.idioma-row').forEach(row => {

            const idioma = row.querySelector('.input-idioma')?.value || 'Sin idioma';
            const nivel = row.querySelector('.input-nivel')?.value || 'BASICO';

            if (row.querySelector('.val-idioma')) {
                row.querySelector('.val-idioma').textContent = idioma;
            }

            if (row.querySelector('.val-nivel')) {
                row.querySelector('.val-nivel').textContent = nivel;
            }

        });

        let sueldoVisible = false;

        function toggleSueldo() {
            const texto = document.getElementById('sueldo-texto');
            const real = document.getElementById('sueldo-real').value;

            if (!sueldoVisible) {
                texto.textContent = 'S/ ' + real;
            } else {
                texto.textContent = '*****';
            }

            sueldoVisible = !sueldoVisible;
        }

        const DEBE_CAMBIAR_CLAVE = <?php echo $debeCambiarClave ? 'true' : 'false'; ?>;

        function abrirModalClave() {
            const modal = document.getElementById('modal-clave');
            if (!modal) return;

            document.getElementById('clave_actual').value = '';
            document.getElementById('clave_nueva').value = '';
            document.getElementById('clave_confirmar').value = '';

            modal.classList.remove('hidden');

            setTimeout(() => {
                document.getElementById('clave_actual')?.focus();
            }, 100);
        }

        function cerrarModalClave() {
            if (DEBE_CAMBIAR_CLAVE) {
                mostrarToast('⚠', 'Debes cambiar tu clave para continuar.', 'bg-red-800');
                return;
            }

            const modal = document.getElementById('modal-clave');
            if (!modal) return;

            modal.classList.add('hidden');
        }

        function toggleClaveInput(id) {
            const input = document.getElementById(id);
            if (!input) return;

            input.type = input.type === 'password' ? 'text' : 'password';
        }

        function guardarClavePerfil() {
            const btn = document.getElementById('btn-guardar-clave');

            const claveActual = document.getElementById('clave_actual')?.value?.trim() || '';
            const claveNueva = document.getElementById('clave_nueva')?.value?.trim() || '';
            const claveConfirmar = document.getElementById('clave_confirmar')?.value?.trim() || '';

            if (!claveActual || !claveNueva || !claveConfirmar) {
                mostrarToast('⚠', 'Completa todos los campos.', 'bg-red-800');
                return;
            }

            if (claveNueva.length < 8) {
                mostrarToast('⚠', 'La nueva clave debe tener mínimo 8 caracteres.', 'bg-red-800');
                return;
            }

            if (claveNueva !== claveConfirmar) {
                mostrarToast('⚠', 'La confirmación no coincide con la nueva clave.', 'bg-red-800');
                return;
            }

            if (claveActual === claveNueva) {
                mostrarToast('⚠', 'La nueva clave debe ser diferente a la actual.', 'bg-red-800');
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Guardando…';

            fetch('<?php echo BASE_URL; ?>/perfil/cambiar-clave', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        clave_actual: claveActual,
                        clave_nueva: claveNueva,
                        clave_confirmar: claveConfirmar
                    })
                })
                .then(r => r.text())
                .then(raw => {
                    let res;

                    try {
                        res = JSON.parse(raw.trim());
                    } catch (e) {
                        console.error(raw);
                        throw new Error('Respuesta inválida del servidor');
                    }

                    if (res.success) {
                        mostrarToast('✓', res.mensaje || 'Clave actualizada correctamente.', 'bg-green-700');

                        setTimeout(() => {
                            window.location.href = res.redirect || '<?php echo BASE_URL; ?>/perfil';
                        }, 900);
                    } else {
                        mostrarToast('✗', res.mensaje || 'No se pudo cambiar la clave.', 'bg-red-800');
                    }
                })
                .catch(err => {
                    console.error(err);
                    mostrarToast('✗', 'La respuesta del servidor no fue válida.', 'bg-red-800');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'Guardar clave';
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (DEBE_CAMBIAR_CLAVE) {
                abrirModalClave();
            }
        });

        function toggleFormacionDetalle(id, btn) {
            const detalle = document.getElementById(id);
            if (!detalle) return;

            const texto = btn.querySelector('.form-toggle-text');
            const icono = btn.querySelector('.form-toggle-icon');

            const estaOculto = detalle.classList.contains('hidden');

            if (estaOculto) {
                detalle.classList.remove('hidden');

                if (texto) texto.textContent = 'Ver menos';
                if (icono) icono.classList.add('rotate-180');
            } else {
                detalle.classList.add('hidden');

                if (texto) texto.textContent = 'Ver más';
                if (icono) icono.classList.remove('rotate-180');
            }
        }
    </script>

    <?php require_once ROOT_PATH . 'Vista/includes/footer.php'; ?>