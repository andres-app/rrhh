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

function valorLimpio($valor, string $campo = ''): string
{
    if ($campo === 'sin_afp_afiliarme') {
        return !empty($valor) && (string)$valor !== '0' ? '1' : '';
    }

    if ($valor === null) {
        return '';
    }

    if (is_bool($valor)) {
        return $valor ? '1' : '0';
    }

    return trim((string)$valor);
}

function labelCampoSolicitud(string $campo): string
{
    $labels = [
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
        'onomastico_conyuge'   => 'Fecha nac. cónyuge',
        'dni_conyuge'          => 'DNI cónyuge',

        'contratos'            => 'Contratos',
        'formacion'            => 'Formación',
        'experiencia'          => 'Experiencia',
        'hijos'                => 'Familia',
        'idiomas'              => 'Idiomas',
        'pension'              => 'Sistema de pensiones',
        'bancario'             => 'Datos bancarios',
    ];

    return $labels[$campo] ?? ucwords(str_replace('_', ' ', $campo));
}

function labelSubCampoSolicitud(string $campo): string
{
    $labels = [
        'fecha_ingreso'          => 'Fecha ingreso',
        'fecha_cese'             => 'Fecha cese',
        'modalidad'              => 'Modalidad',

        'tipo_grado'             => 'Tipo / grado',
        'descripcion_carrera'    => 'Carrera / descripción',
        'institucion'            => 'Institución',
        'anio_realizacion'       => 'Año',
        'horas_lectivas'         => 'Horas lectivas',
        'especialidad'           => 'Especialidad',
        'grado_alcanzado'        => 'Grado alcanzado',

        'empresa_entidad'        => 'Entidad',
        'unidad_organica_area'   => 'Área',
        'cargo_puesto'           => 'Cargo',
        'fecha_inicio'           => 'Fecha inicio',
        'fecha_fin'              => 'Fecha fin',
        'actualmente_trabaja'    => 'Actualmente trabaja',
        'funciones_principales'  => 'Funciones',

        'nombre'                 => 'Nombre',
        'nombre_completo'        => 'Nombre',
        'parentesco'             => 'Parentesco',
        'fecha_nacimiento'       => 'Fecha nacimiento',
        'dni'                    => 'DNI',
        'dni_familiar'           => 'DNI',

        'idioma'                 => 'Idioma',
        'nivel'                  => 'Nivel',

        'sistema_pension'        => 'Sistema',
        'afp'                    => 'AFP',
        'cuspp'                  => 'CUSPP',
        'tipo_comision'          => 'Tipo comisión',
        'fecha_inscripcion'      => 'Fecha inscripción',
        'sin_afp_afiliarme'      => 'Sin AFP / Afiliarme',

        'banco_haberes'          => 'Banco',
        'numero_cuenta'          => 'Cuenta',
        'numero_cuenta_cci'      => 'CCI',
    ];

    return $labels[$campo] ?? ucwords(str_replace('_', ' ', $campo));
}

function normalizarFilaSolicitud(array $fila, array $campos): array
{
    $limpio = [];

    foreach ($campos as $campo) {
        $limpio[$campo] = valorLimpio($fila[$campo] ?? '', $campo);
    }

    return $limpio;
}

function filaVaciaSolicitud(array $fila): bool
{
    foreach ($fila as $valor) {
        if ($valor !== '') {
            return false;
        }
    }

    return true;
}

function normalizarListaSolicitud(array $lista, array $campos): array
{
    $resultado = [];

    foreach ($lista as $fila) {
        if (!is_array($fila)) {
            continue;
        }

        $item = normalizarFilaSolicitud($fila, $campos);

        if (!filaVaciaSolicitud($item)) {
            $resultado[] = $item;
        }
    }

    usort($resultado, function ($a, $b) {
        return strcmp(
            json_encode($a, JSON_UNESCAPED_UNICODE),
            json_encode($b, JSON_UNESCAPED_UNICODE)
        );
    });

    return $resultado;
}

function obtenerHijosDesdeFamiliaAnterior(array $datosAntes): array
{
    $familiaAntes = $datosAntes['familia'] ?? [];
    $hijosAntes = [];

    if (!is_array($familiaAntes)) {
        return [];
    }

    foreach ($familiaAntes as $familiar) {
        if (!is_array($familiar)) {
            continue;
        }

        $parentesco = strtoupper(valorLimpio($familiar['parentesco'] ?? ''));

        if (in_array($parentesco, ['HIJO', 'HIJA'], true)) {
            $hijosAntes[] = [
                'id'               => $familiar['id'] ?? '',
                'nombre'           => $familiar['nombre_completo'] ?? '',
                'parentesco'       => $familiar['parentesco'] ?? '',
                'fecha_nacimiento' => $familiar['fecha_nacimiento'] ?? '',
                'dni'              => $familiar['dni_familiar'] ?? '',
            ];
        }
    }

    return $hijosAntes;
}

function subCamposAsociativosCambiados(array $antes, array $despues, array $campos): array
{
    $cambiados = [];

    foreach ($campos as $campo) {
        $valorAntes = valorLimpio($antes[$campo] ?? '', $campo);
        $valorDespues = valorLimpio($despues[$campo] ?? '', $campo);

        if ($valorAntes !== $valorDespues) {
            $cambiados[] = labelSubCampoSolicitud($campo);
        }
    }

    return $cambiados;
}

function subCamposListaCambiados(array $antes, array $despues, array $campos): array
{
    $listaAntes = normalizarListaSolicitud($antes, $campos);
    $listaDespues = normalizarListaSolicitud($despues, $campos);

    if ($listaAntes === $listaDespues) {
        return [];
    }

    $cambiados = [];

    $max = max(count($listaAntes), count($listaDespues));

    for ($i = 0; $i < $max; $i++) {
        $filaAntes = $listaAntes[$i] ?? [];
        $filaDespues = $listaDespues[$i] ?? [];

        foreach ($campos as $campo) {
            $valorAntes = $filaAntes[$campo] ?? '';
            $valorDespues = $filaDespues[$campo] ?? '';

            if ($valorAntes !== $valorDespues) {
                $cambiados[] = labelSubCampoSolicitud($campo);
            }
        }
    }

    return array_values(array_unique($cambiados));
}

function agregarResumenCambio(array &$resumen, string $bloque, array $subcampos = []): void
{
    $label = labelCampoSolicitud($bloque);

    if (!empty($subcampos)) {
        $resumen[] = $label . ': ' . implode(', ', array_values(array_unique($subcampos)));
        return;
    }

    $resumen[] = $label;
}

function resumenSolicitud($sol)
{
    $datosNuevos = json_decode((string)($sol['datos_json'] ?? ''), true) ?: [];
    $datosAntes  = json_decode((string)($sol['datos_anteriores_json'] ?? ''), true) ?: [];

    $resumen = [];

    $camposSimples = [
        'fecha_nacimiento',
        'lugar_nacimiento',
        'estado_civil',
        'grupo_sanguineo',
        'talla',
        'direccion_residencia',
        'distrito',
        'celular',
        'correo_personal',
        'conyuge',
        'onomastico_conyuge',
        'dni_conyuge',
    ];

    foreach ($camposSimples as $campo) {
        $antes = valorLimpio($datosAntes[$campo] ?? '', $campo);
        $despues = valorLimpio($datosNuevos[$campo] ?? '', $campo);

        if ($antes !== $despues) {
            $resumen[] = labelCampoSolicitud($campo);
        }
    }

    $bloquesLista = [
        'contratos' => [
            'fecha_ingreso',
            'fecha_cese',
            'modalidad',
        ],
        'formacion' => [
            'tipo_grado',
            'descripcion_carrera',
            'institucion',
            'anio_realizacion',
            'horas_lectivas',
            'especialidad',
            'grado_alcanzado',
        ],
        'experiencia' => [
            'empresa_entidad',
            'unidad_organica_area',
            'cargo_puesto',
            'fecha_inicio',
            'fecha_fin',
            'actualmente_trabaja',
            'funciones_principales',
        ],
        'idiomas' => [
            'idioma',
            'nivel',
        ],
    ];

    foreach ($bloquesLista as $bloque => $campos) {
        $antes = is_array($datosAntes[$bloque] ?? null) ? $datosAntes[$bloque] : [];
        $despues = is_array($datosNuevos[$bloque] ?? null) ? $datosNuevos[$bloque] : [];

        $subcambios = subCamposListaCambiados($antes, $despues, $campos);

        if (!empty($subcambios)) {
            agregarResumenCambio($resumen, $bloque, $subcambios);
        }
    }

    $hijosAntes = obtenerHijosDesdeFamiliaAnterior($datosAntes);
    $hijosDespues = is_array($datosNuevos['hijos'] ?? null) ? $datosNuevos['hijos'] : [];

    $subcambiosHijos = subCamposListaCambiados($hijosAntes, $hijosDespues, [
        'nombre',
        'parentesco',
        'fecha_nacimiento',
        'dni',
    ]);

    if (!empty($subcambiosHijos)) {
        agregarResumenCambio($resumen, 'hijos', $subcambiosHijos);
    }

    $pensionAntes = is_array($datosAntes['pension'] ?? null) ? $datosAntes['pension'] : [];
    $pensionNueva = is_array($datosNuevos['pension'] ?? null) ? $datosNuevos['pension'] : [];

    $subcambiosPension = subCamposAsociativosCambiados($pensionAntes, $pensionNueva, [
        'sistema_pension',
        'afp',
        'cuspp',
        'tipo_comision',
        'fecha_inscripcion',
        'sin_afp_afiliarme',
    ]);

    if (!empty($subcambiosPension)) {
        agregarResumenCambio($resumen, 'pension', $subcambiosPension);
    }

    $bancarioAntes = is_array($datosAntes['bancario'] ?? null) ? $datosAntes['bancario'] : [];
    $bancarioNuevo = is_array($datosNuevos['bancario'] ?? null) ? $datosNuevos['bancario'] : [];

    $subcambiosBancario = subCamposAsociativosCambiados($bancarioAntes, $bancarioNuevo, [
        'banco_haberes',
        'numero_cuenta',
        'numero_cuenta_cci',
    ]);

    if (!empty($subcambiosBancario)) {
        agregarResumenCambio($resumen, 'bancario', $subcambiosBancario);
    }

    return !empty($resumen)
        ? implode(', ', array_values(array_unique($resumen)))
        : 'Sin cambios detectados';
}

function rrhhValE($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function rrhhValLabelCampo(string $campo): string
{
    $labels = [
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
        'onomastico_conyuge'   => 'Fecha nac. cónyuge',
        'dni_conyuge'          => 'DNI cónyuge',
        'pension'              => 'Sistema de pensiones',
        'bancario'             => 'Datos bancarios',
        'contratos'            => 'Contratos',
        'formacion'            => 'Formación',
        'experiencia'          => 'Experiencia',
        'hijos'                => 'Familia',
        'idiomas'              => 'Idiomas',
    ];

    return $labels[$campo] ?? ucwords(str_replace('_', ' ', $campo));
}

function rrhhValLabelSubCampo(string $campo): string
{
    $labels = [
        'sistema_pension'       => 'Sistema',
        'afp'                   => 'AFP',
        'cuspp'                 => 'CUSPP',
        'tipo_comision'         => 'Tipo comisión',
        'fecha_inscripcion'     => 'Fecha inscripción',
        'sin_afp_afiliarme'     => 'Sin AFP / Afiliarme',

        'banco_haberes'         => 'Banco',
        'numero_cuenta'         => 'Número de cuenta',
        'numero_cuenta_cci'     => 'CCI',

        'fecha_ingreso'         => 'Fecha ingreso',
        'fecha_cese'            => 'Fecha cese',
        'modalidad'             => 'Modalidad',

        'tipo_grado'            => 'Tipo / grado',
        'descripcion_carrera'   => 'Carrera / descripción',
        'institucion'           => 'Institución',
        'anio_realizacion'      => 'Año',
        'horas_lectivas'        => 'Horas lectivas',
        'especialidad'          => 'Especialidad',
        'grado_alcanzado'       => 'Grado alcanzado',

        'empresa_entidad'       => 'Entidad',
        'unidad_organica_area'  => 'Área',
        'cargo_puesto'          => 'Cargo',
        'fecha_inicio'          => 'Fecha inicio',
        'fecha_fin'             => 'Fecha fin',
        'actualmente_trabaja'   => 'Actualmente trabaja',
        'funciones_principales' => 'Funciones',

        'nombre'                => 'Nombre',
        'nombre_completo'       => 'Nombre',
        'parentesco'            => 'Parentesco',
        'fecha_nacimiento'      => 'Fecha nacimiento',
        'dni'                   => 'DNI',
        'dni_familiar'          => 'DNI',

        'idioma'                => 'Idioma',
        'nivel'                 => 'Nivel',
    ];

    return $labels[$campo] ?? ucwords(str_replace('_', ' ', $campo));
}

function rrhhValNormalizar($valor, string $campo = ''): string
{
    if ($campo === 'sin_afp_afiliarme' || $campo === 'actualmente_trabaja') {
        return !empty($valor) && (string)$valor !== '0' ? '1' : '';
    }

    if ($valor === null) {
        return '';
    }

    if (is_bool($valor)) {
        return $valor ? '1' : '';
    }

    if (is_array($valor)) {
        return json_encode($valor, JSON_UNESCAPED_UNICODE);
    }

    return trim((string)$valor);
}

function rrhhValLegible($valor, string $campo = ''): string
{
    if ($campo === 'sin_afp_afiliarme' || $campo === 'actualmente_trabaja') {
        return !empty($valor) && (string)$valor !== '0' ? 'Sí' : 'No';
    }

    if ($valor === null || $valor === '' || $valor === []) {
        return 'Sin registro';
    }

    if (is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
        return date('d/m/Y', strtotime($valor));
    }

    if (is_array($valor)) {
        return json_encode($valor, JSON_UNESCAPED_UNICODE);
    }

    return (string)$valor;
}

function rrhhValObtenerValorAnterior(array $antes, string $campo)
{
    if ($campo === 'hijos') {
        $familia = $antes['familia'] ?? [];

        if (!is_array($familia)) {
            return [];
        }

        $hijos = [];

        foreach ($familia as $item) {
            if (!is_array($item)) continue;

            $parentesco = strtoupper(trim($item['parentesco'] ?? ''));

            if (in_array($parentesco, ['HIJO', 'HIJA'], true)) {
                $hijos[] = [
                    'id'               => $item['id'] ?? '',
                    'nombre'           => $item['nombre_completo'] ?? '',
                    'parentesco'       => $item['parentesco'] ?? '',
                    'fecha_nacimiento' => $item['fecha_nacimiento'] ?? '',
                    'dni'              => $item['dni_familiar'] ?? '',
                ];
            }
        }

        return $hijos;
    }

    return $antes[$campo] ?? null;
}

function rrhhValSubCambiosAsociativos($antes, $despues, array $campos): array
{
    $antes = is_array($antes) ? $antes : [];
    $despues = is_array($despues) ? $despues : [];

    $cambios = [];

    foreach ($campos as $campo) {
        $valorAntes = $antes[$campo] ?? null;
        $valorDespues = $despues[$campo] ?? null;

        if (rrhhValNormalizar($valorAntes, $campo) !== rrhhValNormalizar($valorDespues, $campo)) {
            $cambios[] = [
                'bloque' => '',
                'campo' => $campo,
                'campo_label' => rrhhValLabelSubCampo($campo),
                'antes' => $valorAntes,
                'despues' => $valorDespues,
            ];
        }
    }

    return $cambios;
}

function rrhhValPrepararLista($lista, array $campos): array
{
    if (!is_array($lista)) {
        return [];
    }

    $salida = [];

    foreach (array_values($lista) as $idx => $item) {
        if (!is_array($item)) continue;

        $id = trim((string)($item['id'] ?? ''));
        $key = $id !== '' ? 'id_' . $id : 'idx_' . $idx;

        $fila = [
            '_key' => $key,
            '_titulo' => 'Registro ' . ($idx + 1),
        ];

        foreach ($campos as $campo) {
            $fila[$campo] = $item[$campo] ?? null;
        }

        $salida[$key] = $fila;
    }

    return $salida;
}

function rrhhValSubCambiosLista($antes, $despues, array $campos): array
{
    $listaAntes = rrhhValPrepararLista($antes, $campos);
    $listaDespues = rrhhValPrepararLista($despues, $campos);

    $keys = array_unique(array_merge(array_keys($listaAntes), array_keys($listaDespues)));
    $cambios = [];

    foreach ($keys as $index => $key) {
        $filaAntes = $listaAntes[$key] ?? [];
        $filaDespues = $listaDespues[$key] ?? [];

        $titulo = $filaDespues['_titulo'] ?? $filaAntes['_titulo'] ?? ('Registro ' . ($index + 1));

        foreach ($campos as $campo) {
            $valorAntes = $filaAntes[$campo] ?? null;
            $valorDespues = $filaDespues[$campo] ?? null;

            if (rrhhValNormalizar($valorAntes, $campo) !== rrhhValNormalizar($valorDespues, $campo)) {
                $cambios[] = [
                    'bloque' => $titulo,
                    'campo' => $campo,
                    'campo_label' => rrhhValLabelSubCampo($campo),
                    'antes' => $valorAntes,
                    'despues' => $valorDespues,
                ];
            }
        }
    }

    return $cambios;
}

function rrhhValObtenerCambiosDetalle(array $sol): array
{
    $despues = json_decode((string)($sol['datos_json'] ?? ''), true) ?: [];
    $antes = json_decode((string)($sol['datos_anteriores_json'] ?? ''), true) ?: [];

    $cambios = [];

    $camposSimples = [
        'fecha_nacimiento',
        'lugar_nacimiento',
        'estado_civil',
        'grupo_sanguineo',
        'talla',
        'direccion_residencia',
        'distrito',
        'celular',
        'correo_personal',
        'conyuge',
        'onomastico_conyuge',
        'dni_conyuge',
    ];

    foreach ($camposSimples as $campo) {
        if (!array_key_exists($campo, $despues)) {
            continue;
        }

        $valorAntes = rrhhValObtenerValorAnterior($antes, $campo);
        $valorDespues = $despues[$campo] ?? null;

        if (rrhhValNormalizar($valorAntes, $campo) !== rrhhValNormalizar($valorDespues, $campo)) {
            $cambios[] = [
                'seccion' => rrhhValLabelCampo($campo),
                'detalle' => '',
                'campo' => $campo,
                'campo_label' => rrhhValLabelCampo($campo),
                'antes' => $valorAntes,
                'despues' => $valorDespues,
            ];
        }
    }

    $bloquesAsociativos = [
        'pension' => [
            'sistema_pension',
            'afp',
            'cuspp',
            'tipo_comision',
            'fecha_inscripcion',
            'sin_afp_afiliarme',
        ],
        'bancario' => [
            'banco_haberes',
            'numero_cuenta',
            'numero_cuenta_cci',
        ],
    ];

    foreach ($bloquesAsociativos as $bloque => $campos) {
        if (!array_key_exists($bloque, $despues)) {
            continue;
        }

        $subcambios = rrhhValSubCambiosAsociativos(
            rrhhValObtenerValorAnterior($antes, $bloque),
            $despues[$bloque] ?? [],
            $campos
        );

        foreach ($subcambios as $sub) {
            $cambios[] = [
                'seccion' => rrhhValLabelCampo($bloque),
                'detalle' => '',
                'campo' => $sub['campo'],
                'campo_label' => $sub['campo_label'],
                'antes' => $sub['antes'],
                'despues' => $sub['despues'],
            ];
        }
    }

    $bloquesLista = [
        'contratos' => [
            'fecha_ingreso',
            'fecha_cese',
            'modalidad',
        ],
        'formacion' => [
            'tipo_grado',
            'descripcion_carrera',
            'institucion',
            'anio_realizacion',
            'horas_lectivas',
            'especialidad',
            'grado_alcanzado',
        ],
        'experiencia' => [
            'empresa_entidad',
            'unidad_organica_area',
            'cargo_puesto',
            'fecha_inicio',
            'fecha_fin',
            'actualmente_trabaja',
            'funciones_principales',
        ],
        'hijos' => [
            'nombre',
            'parentesco',
            'fecha_nacimiento',
            'dni',
        ],
        'idiomas' => [
            'idioma',
            'nivel',
        ],
    ];

    foreach ($bloquesLista as $bloque => $campos) {
        if (!array_key_exists($bloque, $despues)) {
            continue;
        }

        $subcambios = rrhhValSubCambiosLista(
            rrhhValObtenerValorAnterior($antes, $bloque),
            $despues[$bloque] ?? [],
            $campos
        );

        foreach ($subcambios as $sub) {
            $cambios[] = [
                'seccion' => rrhhValLabelCampo($bloque),
                'detalle' => $sub['bloque'],
                'campo' => $sub['campo'],
                'campo_label' => $sub['campo_label'],
                'antes' => $sub['antes'],
                'despues' => $sub['despues'],
            ];
        }
    }

    return $cambios;
}

function rrhhValRenderDetalleCambios(array $cambios): string
{
    if (empty($cambios)) {
        return '
            <div class="p-8 text-center text-slate-400">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center mb-3 font-black">—</div>
                <p class="text-sm font-bold">No se detectaron cambios visibles.</p>
            </div>
        ';
    }

    $html = '<div class="space-y-4">';

    foreach ($cambios as $c) {
        $detalle = !empty($c['detalle'])
            ? '<span class="ml-2 text-[10px] font-black text-slate-400 bg-slate-100 px-2 py-1 rounded-lg">' . rrhhValE($c['detalle']) . '</span>'
            : '';

        $html .= '
            <div class="rounded-3xl border border-slate-200 overflow-hidden bg-white shadow-sm">
                <div class="px-5 py-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-red-900">
                            ' . rrhhValE($c['seccion']) . '
                        </p>
                        <p class="text-sm font-black text-slate-800 mt-1">
                            ' . rrhhValE($c['campo_label']) . $detalle . '
                        </p>
                    </div>

                    <span class="text-[10px] font-black uppercase tracking-widest bg-red-50 text-red-900 border border-red-100 rounded-xl px-3 py-1">
                        Cambio detectado
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2">
                    <div class="p-5 bg-slate-50 border-b md:border-b-0 md:border-r border-slate-200">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">
                            Antes
                        </p>
                        <p class="text-sm font-bold text-slate-700 break-words">
                            ' . rrhhValE(rrhhValLegible($c['antes'], $c['campo'])) . '
                        </p>
                    </div>

                    <div class="p-5 bg-white">
                        <p class="text-[10px] font-black uppercase tracking-widest text-red-900 mb-2">
                            Después
                        </p>
                        <p class="text-sm font-black text-slate-900 break-words">
                            ' . rrhhValE(rrhhValLegible($c['despues'], $c['campo'])) . '
                        </p>
                    </div>
                </div>
            </div>
        ';
    }

    $html .= '</div>';

    return $html;
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

                    <select id="pageSize"
                        class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs font-black text-slate-600 uppercase tracking-widest outline-none focus:ring-2 focus:ring-red-900/20 focus:border-red-900">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>

                    <div class="flex gap-2">
                        <button onclick="filtrarEstado('TODOS', this)"
                            class="filtro-estado bg-slate-100 text-slate-600 px-4 py-3 rounded-2xl text-xs font-black uppercase tracking-widest">
                            Todos
                        </button>

                        <button onclick="filtrarEstado('PENDIENTE', this)"
                            class="filtro-estado bg-red-900 text-white px-4 py-3 rounded-2xl text-xs font-black uppercase tracking-widest">
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
                                $estado = strtoupper(trim((string)($sol['estado'] ?? 'PENDIENTE')));
                                $modalId = 'modal-validacion-' . (int)($sol['id'] ?? 0);

                                $fechaSolicitud = !empty($sol['created_at'])
                                    ? date('d/m/Y H:i', strtotime($sol['created_at']))
                                    : 'Sin fecha';

                                $colaborador = $sol['nombres_apellidos']
                                    ?? $sol['colaborador']
                                    ?? $sol['nombre_colaborador']
                                    ?? 'Colaborador';

                                $dni = $sol['dni'] ?? '—';

                                $tipo = textoTipoSolicitud($sol['tipo_solicitud'] ?? 'perfil_completo');
                                $resumen = resumenSolicitud($sol);

                                $busqueda = mb_strtolower(
                                    trim($colaborador . ' ' . $dni . ' ' . $tipo . ' ' . $estado . ' ' . $resumen),
                                    'UTF-8'
                                );

                                $archivoSustento = $sol['archivo_sustento'] ?? '';
                                ?>

                                <tr class="fila-solicitud hover:bg-red-50/30 transition-all"
                                    data-estado="<?php echo rrhhValE($estado); ?>"
                                    data-busqueda="<?php echo rrhhValE($busqueda); ?>">

                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-11 h-11 rounded-2xl bg-red-900 text-white flex items-center justify-center font-black shadow-sm">
                                                <?php echo rrhhValE(mb_substr($colaborador, 0, 1, 'UTF-8')); ?>
                                            </div>

                                            <div class="min-w-0">
                                                <p class="font-black text-slate-800 truncate">
                                                    <?php echo rrhhValE($colaborador); ?>
                                                </p>
                                                <p class="text-xs text-slate-400 font-bold">
                                                    DNI: <?php echo rrhhValE($dni); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="p-4">
                                        <p class="font-black text-slate-700">
                                            <?php echo rrhhValE($tipo); ?>
                                        </p>
                                        <p class="text-xs text-slate-400 mt-1 line-clamp-2 max-w-xl">
                                            <?php echo rrhhValE($resumen); ?>
                                        </p>
                                    </td>

                                    <td class="p-4">
                                        <span class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest px-3 py-1.5 rounded-xl border <?php echo badgeEstadoSolicitud($estado); ?>">
                                            <?php echo rrhhValE($estado); ?>
                                        </span>
                                    </td>

                                    <td class="p-4">
                                        <p class="text-sm font-bold text-slate-700">
                                            <?php echo rrhhValE($fechaSolicitud); ?>
                                        </p>
                                    </td>

                                    <td class="p-4">
                                        <?php if (!empty($archivoSustento)): ?>
                                            <a href="<?php echo BASE_URL . '/' . ltrim($archivoSustento, '/'); ?>"
                                                target="_blank"
                                                class="inline-flex items-center justify-center px-3 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-black hover:bg-red-900 hover:text-white transition">
                                                Ver archivo
                                            </a>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400 font-bold">Sin sustento</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-4 text-center">
                                        <button type="button"
                                            onclick="abrirDetalleValidacion('<?php echo $modalId; ?>')"
                                            class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-red-900 text-white text-xs font-black hover:bg-[#310404] transition shadow-sm">
                                            Ver detalle
                                        </button>
                                    </td>

                                </tr>
                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>
                </table>
            </div>

            <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/70 flex flex-col md:flex-row md:items-center md:justify-between gap-4 shrink-0">

                <div class="text-xs font-bold text-slate-400">
                    Mostrando
                    <span id="rangeInfo" class="text-red-900 font-black">0</span>
                    de
                    <span id="resultCount" class="text-slate-700 font-black"><?php echo count($solicitudes); ?></span>
                    solicitudes
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

        </section>

    </div>
</main>

<?php foreach ($solicitudes as $sol): ?>
    <?php
    $estado = strtoupper(trim((string)($sol['estado'] ?? 'PENDIENTE')));
    $modalId = 'modal-validacion-' . (int)($sol['id'] ?? 0);

    $fechaSolicitud = !empty($sol['created_at'])
        ? date('d/m/Y H:i', strtotime($sol['created_at']))
        : 'Sin fecha';

    $fechaRevision = !empty($sol['fecha_validacion'])
        ? date('d/m/Y H:i', strtotime($sol['fecha_validacion']))
        : '—';

    $cambios = rrhhValObtenerCambiosDetalle($sol);

    $resumenModal = [];

    foreach ($cambios as $c) {
        $texto = trim(
            (string)($c['seccion'] ?? 'Cambio') .
                ' - ' .
                (string)($c['campo_label'] ?? 'Campo')
        );

        if (!empty($c['detalle'])) {
            $texto .= ' (' . $c['detalle'] . ')';
        }

        $resumenModal[] = $texto;
    }

    $resumenModalTexto = !empty($resumenModal)
        ? implode(', ', array_values(array_unique($resumenModal)))
        : 'Sin cambios visibles';
    ?>

    <div id="<?php echo $modalId; ?>"
        class="fixed inset-0 z-[90] hidden"
        role="dialog"
        aria-modal="true">

        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"
            onclick="cerrarDetalleValidacion('<?php echo $modalId; ?>')"></div>

        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="relative w-full max-w-5xl max-h-[92vh] bg-white rounded-[32px] shadow-2xl border border-slate-200 overflow-hidden flex flex-col">

                <div class="px-7 py-6 bg-gradient-to-r from-[#310404] to-red-900 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-red-200 text-[10px] font-black uppercase tracking-[0.24em] mb-1">
                            Detalle de validación
                        </p>

                        <h2 class="text-white text-2xl font-black leading-tight">
                            Solicitud #<?php echo (int)($sol['id'] ?? 0); ?>
                        </h2>

                        <p class="text-red-100 text-sm font-semibold mt-2">
                            Revisión de los cambios solicitados en el perfil del colaborador
                        </p>
                    </div>

                    <button type="button"
                        onclick="cerrarDetalleValidacion('<?php echo $modalId; ?>')"
                        class="w-10 h-10 rounded-2xl bg-white/10 text-white hover:bg-white/20 transition flex items-center justify-center font-black">
                        ✕
                    </button>
                </div>

                <div class="px-7 py-4 border-b border-slate-100 bg-slate-50">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

                        <div class="bg-white border border-slate-200 rounded-2xl p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                                Estado
                            </p>

                            <span class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest px-3 py-1.5 rounded-xl border <?php echo badgeEstadoSolicitud($estado); ?>">
                                <?php echo rrhhValE($estado); ?>
                            </span>
                        </div>

                        <div class="bg-white border border-slate-200 rounded-2xl p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                                Fecha de solicitud
                            </p>

                            <p class="text-sm font-black text-slate-800">
                                <?php echo rrhhValE($fechaSolicitud); ?>
                            </p>
                        </div>

                        <div class="bg-white border border-slate-200 rounded-2xl p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                                Fecha de revisión
                            </p>

                            <p class="text-sm font-black text-slate-800">
                                <?php echo rrhhValE($fechaRevision); ?>
                            </p>
                        </div>

                    </div>

                    <div class="mt-4 bg-white border border-slate-200 rounded-2xl p-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                            Resumen de cambios
                        </p>

                        <p class="text-sm font-bold text-slate-700 leading-relaxed">
                            <?php echo rrhhValE($resumenModalTexto); ?>
                        </p>
                    </div>

                    <?php if (!empty($sol['observacion_rrhh']) || !empty($sol['motivo_rechazo'])): ?>
                        <div class="mt-4 rounded-2xl border border-red-100 bg-red-50 p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-red-700 mb-1">
                                Observación de RR. HH.
                            </p>

                            <p class="text-sm font-semibold text-red-800 leading-relaxed">
                                <?php echo rrhhValE($sol['motivo_rechazo'] ?: $sol['observacion_rrhh']); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flex-1 overflow-y-auto px-7 py-6 bg-slate-50">
                    <?php echo rrhhValRenderDetalleCambios($cambios); ?>
                </div>

                <div class="px-7 py-5 bg-white border-t border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="text-xs text-slate-400 font-bold">
                        Esta vista muestra lo enviado para validación y la respuesta de RR. HH. cuando corresponda.
                    </div>

                    <div class="flex items-center gap-2">
                        <?php if ($estado === 'PENDIENTE'): ?>
                            <button type="button"
                                onclick="aprobarSolicitud(<?php echo (int)($sol['id'] ?? 0); ?>)"
                                class="px-5 py-2.5 rounded-xl bg-green-600 text-white text-sm font-black hover:bg-green-700 transition">
                                Aprobar
                            </button>

                            <button type="button"
                                onclick="rechazarSolicitud(<?php echo (int)($sol['id'] ?? 0); ?>)"
                                class="px-5 py-2.5 rounded-xl bg-red-900 text-white text-sm font-black hover:bg-[#4c0505] transition">
                                Rechazar
                            </button>
                        <?php endif; ?>

                        <button type="button"
                            onclick="cerrarDetalleValidacion('<?php echo $modalId; ?>')"
                            class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-black hover:bg-slate-100 transition">
                            Cerrar
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let estadoActual = 'PENDIENTE';
    let paginaActual = 1;
    let filasFiltradas = [];

    const BASE_URL_RRHH = <?php echo json_encode(rtrim(BASE_URL, '/')); ?>;

    function abrirDetalleValidacion(id) {
        const modal = document.getElementById(id);

        if (!modal) {
            console.error('No existe el modal con ID:', id);
            alert('No se encontró el detalle de esta solicitud.');
            return;
        }

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function cerrarDetalleValidacion(id) {
        const modal = document.getElementById(id);

        if (!modal) return;

        modal.classList.add('hidden');

        const abierto = document.querySelector('[id^="modal-validacion-"]:not(.hidden)');

        if (!abierto) {
            document.body.classList.remove('overflow-hidden');
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;

        document.querySelectorAll('[id^="modal-validacion-"]').forEach(modal => {
            modal.classList.add('hidden');
        });

        document.body.classList.remove('overflow-hidden');
    });

    const inputBuscar = document.getElementById('buscarSolicitud');
    const pageSizeSelect = document.getElementById('pageSize');
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');
    const paginationNumbers = document.getElementById('paginationNumbers');
    const rangeInfo = document.getElementById('rangeInfo');
    const resultCount = document.getElementById('resultCount');

    function normalizarTexto(texto) {
        return (texto || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function obtenerFilas() {
        return Array.from(document.querySelectorAll('.fila-solicitud'));
    }

    function obtenerPageSize() {
        return parseInt(pageSizeSelect?.value || 10, 10);
    }

    function filtrarEstado(estado, boton) {
        estadoActual = estado;
        paginaActual = 1;

        document.querySelectorAll('.filtro-estado').forEach(btn => {
            btn.className = 'filtro-estado bg-slate-100 text-slate-600 px-4 py-3 rounded-2xl text-xs font-black uppercase tracking-widest';
        });

        if (boton) {
            boton.className = 'filtro-estado bg-red-900 text-white px-4 py-3 rounded-2xl text-xs font-black uppercase tracking-widest';
        }

        aplicarFiltros();
    }

    function aplicarFiltros() {
        const texto = normalizarTexto(inputBuscar?.value || '');
        const filas = obtenerFilas();

        filasFiltradas = filas.filter(fila => {
            const estado = fila.dataset.estado || '';
            const busqueda = normalizarTexto(fila.dataset.busqueda || '');

            const coincideEstado = estadoActual === 'TODOS' || estado === estadoActual;
            const coincideTexto = texto === '' || busqueda.includes(texto);

            return coincideEstado && coincideTexto;
        });

        renderizarTabla();
    }

    function renderizarTabla() {
        const filas = obtenerFilas();
        const pageSize = obtenerPageSize();
        const total = filasFiltradas.length;
        const totalPaginas = Math.max(1, Math.ceil(total / pageSize));

        if (paginaActual > totalPaginas) {
            paginaActual = totalPaginas;
        }

        const inicio = (paginaActual - 1) * pageSize;
        const fin = inicio + pageSize;
        const visibles = filasFiltradas.slice(inicio, fin);

        filas.forEach(fila => fila.style.display = 'none');
        visibles.forEach(fila => fila.style.display = '');

        const desde = total === 0 ? 0 : inicio + 1;
        const hasta = Math.min(fin, total);

        if (rangeInfo) {
            rangeInfo.textContent = total === 0 ? '0' : `${desde}-${hasta}`;
        }

        if (resultCount) {
            resultCount.textContent = total;
        }

        if (prevBtn) {
            prevBtn.disabled = paginaActual <= 1 || total === 0;
        }

        if (nextBtn) {
            nextBtn.disabled = paginaActual >= totalPaginas || total === 0;
        }

        renderizarNumeros(totalPaginas, total);
    }

    function renderizarNumeros(totalPaginas, total) {
        if (!paginationNumbers) return;

        paginationNumbers.innerHTML = '';

        if (total === 0) return;

        const maxBotones = 5;
        let inicio = Math.max(1, paginaActual - 2);
        let fin = Math.min(totalPaginas, inicio + maxBotones - 1);

        if (fin - inicio < maxBotones - 1) {
            inicio = Math.max(1, fin - maxBotones + 1);
        }

        for (let page = inicio; page <= fin; page++) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = page;

            btn.className = page === paginaActual ?
                'w-9 h-9 rounded-xl bg-red-900 text-white text-xs font-black shadow-lg shadow-red-900/20' :
                'w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-600 text-xs font-black hover:bg-red-50 hover:text-red-900 transition';

            btn.addEventListener('click', () => {
                paginaActual = page;
                renderizarTabla();
            });

            paginationNumbers.appendChild(btn);
        }
    }

    inputBuscar?.addEventListener('input', function() {
        paginaActual = 1;
        aplicarFiltros();
    });

    pageSizeSelect?.addEventListener('change', function() {
        paginaActual = 1;
        aplicarFiltros();
    });

    prevBtn?.addEventListener('click', function() {
        if (paginaActual > 1) {
            paginaActual--;
            renderizarTabla();
        }
    });

    nextBtn?.addEventListener('click', function() {
        const totalPaginas = Math.ceil(filasFiltradas.length / obtenerPageSize());

        if (paginaActual < totalPaginas) {
            paginaActual++;
            renderizarTabla();
        }
    });

    aplicarFiltros();

    function swalDisponible() {
        return typeof Swal !== 'undefined' && typeof Swal.fire === 'function';
    }

    function alertaBasica(titulo, texto, tipo = 'info') {
        if (swalDisponible()) {
            return Swal.fire({
                title: titulo,
                text: texto,
                icon: tipo,
                confirmButtonColor: '#7f1d1d',
                customClass: {
                    popup: 'rounded-3xl',
                    confirmButton: 'rounded-xl px-5 py-2.5 font-bold'
                }
            });
        }

        alert(titulo + '\n' + texto);
        return Promise.resolve();
    }

    async function postValidacion(url, payload) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        });

        const raw = await response.text();

        try {
            return JSON.parse(raw.trim());
        } catch (e) {
            console.error('Respuesta no JSON:', raw);
            return {
                success: false,
                mensaje: 'El servidor no devolvió una respuesta válida.'
            };
        }
    }

    function aprobarSolicitud(id) {
        if (!id || id <= 0) {
            alertaBasica('Error', 'ID de solicitud inválido.', 'error');
            return;
        }

        const confirmar = swalDisponible() ?
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
            }) :
            Promise.resolve({
                isConfirmed: confirm('¿Aprobar esta solicitud? Se aplicarán los cambios al perfil.')
            });

        confirmar.then(async result => {
            if (!result.isConfirmed) return;

            const res = await postValidacion(BASE_URL_RRHH + '/rrhh/validaciones/aprobar/' + encodeURIComponent(id), {});

            await alertaBasica(
                res.success ? 'Solicitud aprobada' : 'No se pudo aprobar',
                res.mensaje || 'Solicitud procesada.',
                res.success ? 'success' : 'error'
            );

            if (res.success) {
                location.reload();
            }
        }).catch(error => {
            console.error(error);
            alertaBasica('Error', 'No se pudo procesar la aprobación.', 'error');
        });
    }

    function rechazarSolicitud(id) {
        if (!id || id <= 0) {
            alertaBasica('Error', 'ID de solicitud inválido.', 'error');
            return;
        }

        const pedirMotivo = swalDisponible() ?
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
                inputValidator: value => {
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
            }) :
            Promise.resolve({
                isConfirmed: true,
                value: prompt('Escribe el motivo del rechazo:')
            });

        pedirMotivo.then(async result => {
            if (!result.isConfirmed) return;

            const motivo = (result.value || '').trim();

            if (!motivo) {
                alertaBasica('Falta motivo', 'Debes ingresar el motivo del rechazo.', 'warning');
                return;
            }

            const res = await postValidacion(BASE_URL_RRHH + '/rrhh/validaciones/rechazar/' + encodeURIComponent(id), {
                motivo: motivo
            });

            await alertaBasica(
                res.success ? 'Solicitud rechazada' : 'No se pudo rechazar',
                res.mensaje || 'Solicitud procesada.',
                res.success ? 'success' : 'error'
            );

            if (res.success) {
                location.reload();
            }
        }).catch(error => {
            console.error(error);
            alertaBasica('Error', 'No se pudo procesar el rechazo.', 'error');
        });
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>