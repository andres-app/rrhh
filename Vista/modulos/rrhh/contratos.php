<?php
// Vista/modulos/rrhh/contratos.php
declare(strict_types=1);

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "/login");
    exit();
}

$rolSesion = strtolower(trim($_SESSION['user_role'] ?? ''));

if (!in_array($rolSesion, ['superadmin', 'admin', 'rrhh'], true)) {
    http_response_code(403);
    die('No tienes permiso para acceder a este módulo.');
}

$titulo_pagina = "Contratos y Adendas | RRHH";
$menu_activo = "contratos";

require_once __DIR__ . '/../../../Modelo/Conexion.php';

if (!function_exists('eContrato')) {
    function eContrato($valor): string
    {
        return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

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

        try {
            $hoy = new DateTime(date('Y-m-d'));
            $fin = new DateTime($fechaCese);

            return (int)$hoy->diff($fin)->format('%r%a');
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('estadoVigenciaContrato')) {
    function estadoVigenciaContrato(?string $fechaFinal): array
    {
        if (empty($fechaFinal) || $fechaFinal === '0000-00-00') {
            return [
                'clave' => 'vigente',
                'texto' => 'Vigente',
                'detalle' => 'Sin fecha de cese',
                'clase' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                'dot' => 'bg-emerald-500',
            ];
        }

        $dias = diasRestantesContrato($fechaFinal);

        if ($dias === null) {
            return [
                'clave' => 'sin_validar',
                'texto' => 'Sin validar',
                'detalle' => 'Fecha no válida',
                'clase' => 'bg-amber-50 text-amber-700 border-amber-100',
                'dot' => 'bg-amber-500',
            ];
        }

        if ($dias < 0) {
            return [
                'clave' => 'vencido',
                'texto' => 'Vencido',
                'detalle' => abs($dias) . ' día(s) vencido',
                'clase' => 'bg-slate-100 text-slate-600 border-slate-200',
                'dot' => 'bg-slate-400',
            ];
        }

        if ($dias <= 30) {
            return [
                'clave' => 'por_vencer',
                'texto' => 'Por vencer',
                'detalle' => $dias . ' día(s) restante(s)',
                'clase' => 'bg-amber-50 text-amber-700 border-amber-100',
                'dot' => 'bg-amber-500',
            ];
        }

        return [
            'clave' => 'vigente',
            'texto' => 'Vigente',
            'detalle' => $dias . ' día(s) restante(s)',
            'clase' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            'dot' => 'bg-emerald-500',
        ];
    }
}

if (!function_exists('tipoRegistroContrato')) {
    function tipoRegistroContrato(array $row): string
    {
        return strtoupper(trim((string)($row['tipo_registro'] ?? 'CONTRATO'))) ?: 'CONTRATO';
    }
}

if (!function_exists('esAdendaContrato')) {
    function esAdendaContrato(array $row): bool
    {
        return tipoRegistroContrato($row) === 'ADENDA';
    }
}

if (!function_exists('fechaOrdenContrato')) {
    function fechaOrdenContrato(?string $fecha, string $fallback = '0000-00-00'): string
    {
        if (empty($fecha) || $fecha === '0000-00-00') {
            return $fallback;
        }

        return $fecha;
    }
}

if (!function_exists('obtenerVigenciaFinalGrupo')) {
    function obtenerVigenciaFinalGrupo(array $contrato): ?string
    {
        $fechas = [];

        $fechaContrato = $contrato['fecha_cese'] ?? null;

        if (!empty($fechaContrato) && $fechaContrato !== '0000-00-00') {
            $fechas[] = $fechaContrato;
        }

        foreach (($contrato['_adendas'] ?? []) as $adenda) {
            $fechaAdenda = $adenda['fecha_cese'] ?? null;

            if (!empty($fechaAdenda) && $fechaAdenda !== '0000-00-00') {
                $fechas[] = $fechaAdenda;
            }
        }

        if (empty($fechas)) {
            return null;
        }

        rsort($fechas);

        return $fechas[0];
    }
}

if (!function_exists('ordenarAdendasHistorial')) {
    function ordenarAdendasHistorial(array $adendas): array
    {
        usort($adendas, function (array $a, array $b): int {
            $fa = fechaOrdenContrato($a['fecha_ingreso'] ?? null);
            $fb = fechaOrdenContrato($b['fecha_ingreso'] ?? null);

            if ($fa === $fb) {
                return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
            }

            return strcmp($fa, $fb);
        });

        return $adendas;
    }
}

if (!function_exists('obtenerUltimaAdendaGrupo')) {
    function obtenerUltimaAdendaGrupo(array $contrato): ?array
    {
        $adendas = $contrato['_adendas'] ?? [];

        if (empty($adendas)) {
            return null;
        }

        usort($adendas, function (array $a, array $b): int {
            $fa = fechaOrdenContrato($a['fecha_cese'] ?? null, '9999-12-31');
            $fb = fechaOrdenContrato($b['fecha_cese'] ?? null, '9999-12-31');

            if ($fa === $fb) {
                $ia = (int)($a['id'] ?? 0);
                $ib = (int)($b['id'] ?? 0);

                return $ib <=> $ia;
            }

            return strcmp($fb, $fa);
        });

        return $adendas[0] ?? null;
    }
}

if (!function_exists('textoBusquedaRegistroContrato')) {
    function textoBusquedaRegistroContrato(array $row): string
    {
        return implode(' ', [
            $row['nombres_apellidos'] ?? '',
            $row['dni'] ?? '',
            $row['numero_documento'] ?? '',
            $row['numero_documento_padre'] ?? '',
            $row['modalidad'] ?? '',
            $row['motivo_adenda'] ?? '',
            $row['observacion'] ?? '',
            $row['puesto_cas'] ?? '',
            $row['area'] ?? '',
            $row['situacion_colaborador'] ?? '',
            $row['tipo_registro'] ?? '',
        ]);
    }
}

if (!function_exists('textoBusquedaGrupoContrato')) {
    function textoBusquedaGrupoContrato(array $contrato): string
    {
        $partes = [
            textoBusquedaRegistroContrato($contrato),
        ];

        foreach (($contrato['_adendas'] ?? []) as $adenda) {
            $partes[] = textoBusquedaRegistroContrato($adenda);
        }

        return strtolower(implode(' ', $partes));
    }
}

if (!function_exists('limpiarTextoContrato')) {
    function limpiarTextoContrato($valor, int $max = 255): string
    {
        $texto = trim((string)($valor ?? ''));
        $texto = preg_replace('/\s+/', ' ', $texto) ?? '';

        if (mb_strlen($texto) > $max) {
            $texto = mb_substr($texto, 0, $max);
        }

        return $texto;
    }
}

if (!function_exists('limpiarFechaContratoInput')) {
    function limpiarFechaContratoInput($valor): ?string
    {
        $fecha = trim((string)($valor ?? ''));

        if ($fecha === '') {
            return null;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return null;
        }

        return $fecha;
    }
}

if (!function_exists('redireccionarContratos')) {
    function redireccionarContratos(string $tipo, string $mensaje): void
    {
        $_SESSION['flash_contrato'] = [
            'tipo' => $tipo,
            'mensaje' => $mensaje,
        ];

        $url = strtok($_SERVER['REQUEST_URI'] ?? '', '?');

        if (!$url) {
            $url = BASE_URL . '/rrhh/contratos';
        }

        header("Location: " . $url);
        exit();
    }
}

if (empty($_SESSION['csrf_contrato'])) {
    $_SESSION['csrf_contrato'] = bin2hex(random_bytes(32));
}

$csrfContrato = $_SESSION['csrf_contrato'];

try {
    $pdo = Conexion::conectar();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $accionContrato = trim((string)($_POST['accion_contrato'] ?? ''));
        $csrfPost = (string)($_POST['csrf_contrato'] ?? '');

        if (!hash_equals($csrfContrato, $csrfPost)) {
            redireccionarContratos('error', 'La sesión del formulario expiró. Vuelve a intentarlo.');
        }

        if ($accionContrato === 'crear_contrato') {
            $colabId = (int)($_POST['colab_id'] ?? 0);
            $numeroDocumento = limpiarTextoContrato($_POST['numero_documento'] ?? '', 150);
            $fechaDocumento = limpiarFechaContratoInput($_POST['fecha_documento'] ?? '');
            $fechaIngreso = limpiarFechaContratoInput($_POST['fecha_ingreso'] ?? '');
            $fechaCese = limpiarFechaContratoInput($_POST['fecha_cese'] ?? '');
            $modalidad = limpiarTextoContrato($_POST['modalidad'] ?? '', 100);
            $observacion = limpiarTextoContrato($_POST['observacion'] ?? '', 500);

            $errores = [];

            if ($colabId <= 0) {
                $errores[] = 'Selecciona un colaborador.';
            }

            if ($numeroDocumento === '') {
                $errores[] = 'Ingresa el número de contrato.';
            }

            if ($fechaIngreso === null) {
                $errores[] = 'Ingresa una fecha de inicio válida.';
            }

            if (!empty($errores)) {
                redireccionarContratos('error', implode(' ', $errores));
            }

            $stmtValidarColab = $pdo->prepare("
                SELECT id
                FROM colab_maestro
                WHERE id = :id
                LIMIT 1
            ");
            $stmtValidarColab->execute([':id' => $colabId]);

            if (!$stmtValidarColab->fetch(PDO::FETCH_ASSOC)) {
                redireccionarContratos('error', 'El colaborador seleccionado no existe.');
            }

            $stmtInsert = $pdo->prepare("
                INSERT INTO colab_contratos (
                    colab_id,
                    contrato_padre_id,
                    tipo_registro,
                    numero_documento,
                    fecha_documento,
                    fecha_ingreso,
                    fecha_cese,
                    modalidad,
                    motivo_adenda,
                    observacion
                ) VALUES (
                    :colab_id,
                    NULL,
                    'CONTRATO',
                    :numero_documento,
                    :fecha_documento,
                    :fecha_ingreso,
                    :fecha_cese,
                    :modalidad,
                    NULL,
                    :observacion
                )
            ");

            $stmtInsert->execute([
                ':colab_id' => $colabId,
                ':numero_documento' => $numeroDocumento,
                ':fecha_documento' => $fechaDocumento,
                ':fecha_ingreso' => $fechaIngreso,
                ':fecha_cese' => $fechaCese,
                ':modalidad' => $modalidad !== '' ? $modalidad : null,
                ':observacion' => $observacion !== '' ? $observacion : null,
            ]);

            redireccionarContratos('exito', 'Contrato registrado correctamente.');
        }

        if ($accionContrato === 'crear_adenda') {
            $contratoPadreId = (int)($_POST['contrato_padre_id'] ?? 0);
            $numeroDocumento = limpiarTextoContrato($_POST['numero_documento'] ?? '', 150);
            $fechaDocumento = limpiarFechaContratoInput($_POST['fecha_documento'] ?? '');
            $fechaIngreso = limpiarFechaContratoInput($_POST['fecha_ingreso'] ?? '');
            $fechaCese = limpiarFechaContratoInput($_POST['fecha_cese'] ?? '');
            $modalidad = limpiarTextoContrato($_POST['modalidad'] ?? '', 100);
            $motivoAdenda = limpiarTextoContrato($_POST['motivo_adenda'] ?? '', 255);
            $observacion = limpiarTextoContrato($_POST['observacion'] ?? '', 500);

            $errores = [];

            if ($contratoPadreId <= 0) {
                $errores[] = 'No se identificó el contrato principal.';
            }

            if ($numeroDocumento === '') {
                $errores[] = 'Ingresa el número de adenda.';
            }

            if ($fechaIngreso === null) {
                $errores[] = 'Ingresa la fecha de inicio de la adenda.';
            }

            if ($fechaCese === null) {
                $errores[] = 'Ingresa la fecha final de la adenda.';
            }

            if ($motivoAdenda === '') {
                $errores[] = 'Ingresa el motivo de la adenda.';
            }

            if (!empty($errores)) {
                redireccionarContratos('error', implode(' ', $errores));
            }

            $stmtPadre = $pdo->prepare("
                SELECT
                    id,
                    colab_id,
                    modalidad,
                    numero_documento
                FROM colab_contratos
                WHERE id = :id
                  AND UPPER(COALESCE(NULLIF(TRIM(tipo_registro), ''), 'CONTRATO')) = 'CONTRATO'
                LIMIT 1
            ");
            $stmtPadre->execute([':id' => $contratoPadreId]);
            $contratoPadre = $stmtPadre->fetch(PDO::FETCH_ASSOC);

            if (!$contratoPadre) {
                redireccionarContratos('error', 'El contrato principal no existe o no puede recibir adendas.');
            }

            $modalidadFinal = $modalidad !== ''
                ? $modalidad
                : limpiarTextoContrato($contratoPadre['modalidad'] ?? '', 100);

            $stmtInsert = $pdo->prepare("
                INSERT INTO colab_contratos (
                    colab_id,
                    contrato_padre_id,
                    tipo_registro,
                    numero_documento,
                    fecha_documento,
                    fecha_ingreso,
                    fecha_cese,
                    modalidad,
                    motivo_adenda,
                    observacion
                ) VALUES (
                    :colab_id,
                    :contrato_padre_id,
                    'ADENDA',
                    :numero_documento,
                    :fecha_documento,
                    :fecha_ingreso,
                    :fecha_cese,
                    :modalidad,
                    :motivo_adenda,
                    :observacion
                )
            ");

            $stmtInsert->execute([
                ':colab_id' => (int)$contratoPadre['colab_id'],
                ':contrato_padre_id' => $contratoPadreId,
                ':numero_documento' => $numeroDocumento,
                ':fecha_documento' => $fechaDocumento,
                ':fecha_ingreso' => $fechaIngreso,
                ':fecha_cese' => $fechaCese,
                ':modalidad' => $modalidadFinal !== '' ? $modalidadFinal : null,
                ':motivo_adenda' => $motivoAdenda,
                ':observacion' => $observacion !== '' ? $observacion : null,
            ]);

            redireccionarContratos('exito', 'Adenda registrada correctamente.');
        }
    }
} catch (Throwable $e) {
    redireccionarContratos('error', 'No se pudo guardar la información: ' . $e->getMessage());
}

$flashContrato = $_SESSION['flash_contrato'] ?? null;
unset($_SESSION['flash_contrato']);

$registros = [];
$porId = [];
$idsRaiz = [];
$contratosAgrupados = [];
$colaboradores = [];
$modalidades = [];

$totalGrupos = 0;
$errorVista = null;

try {
    $pdo = Conexion::conectar();

    $stmtColaboradores = $pdo->prepare("
        SELECT
            m.id,
            m.nombres_apellidos,
            m.dni,
            COALESCE(NULLIF(TRIM(l.situacion), ''), 'ACTIVO') AS situacion_colaborador
        FROM colab_maestro m
        LEFT JOIN (
            SELECT l1.*
            FROM colab_laboral l1
            INNER JOIN (
                SELECT colab_id, MAX(id) AS max_id
                FROM colab_laboral
                GROUP BY colab_id
            ) ult ON ult.max_id = l1.id
        ) l ON l.colab_id = m.id
        ORDER BY
            CASE
                WHEN UPPER(COALESCE(NULLIF(TRIM(l.situacion), ''), 'ACTIVO')) = 'CESADO' THEN 1
                ELSE 0
            END ASC,
            m.nombres_apellidos ASC
    ");
    $stmtColaboradores->execute();
    $colaboradores = $stmtColaboradores->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $sql = "
        SELECT
            c.id,
            c.colab_id,
            c.contrato_padre_id,
            COALESCE(NULLIF(TRIM(c.tipo_registro), ''), 'CONTRATO') AS tipo_registro,
            c.numero_documento,
            c.fecha_documento,
            c.fecha_ingreso,
            c.fecha_cese,
            c.modalidad,
            c.motivo_adenda,
            c.observacion,

            m.nombres_apellidos,
            m.dni,

            COALESCE(NULLIF(TRIM(l.situacion), ''), 'ACTIVO') AS situacion_colaborador,
            l.puesto_cas,
            l.area,

            padre.numero_documento AS numero_documento_padre,
            padre.fecha_ingreso AS fecha_ingreso_padre,
            padre.fecha_cese AS fecha_cese_padre,

            (
                SELECT COUNT(*)
                FROM colab_contratos a
                WHERE a.contrato_padre_id = c.id
                  AND UPPER(COALESCE(NULLIF(TRIM(a.tipo_registro), ''), 'CONTRATO')) = 'ADENDA'
            ) AS total_adendas_hijas,

            (
                SELECT MAX(x.fecha_cese)
                FROM colab_contratos x
                WHERE x.fecha_cese IS NOT NULL
                  AND (
                        x.id = c.id
                        OR x.contrato_padre_id = c.id
                  )
            ) AS vigencia_final_periodo,

            (
                SELECT a.numero_documento
                FROM colab_contratos a
                WHERE a.contrato_padre_id = c.id
                  AND UPPER(COALESCE(NULLIF(TRIM(a.tipo_registro), ''), 'CONTRATO')) = 'ADENDA'
                ORDER BY
                    COALESCE(a.fecha_cese, '9999-12-31') DESC,
                    COALESCE(a.fecha_ingreso, '0000-00-00') DESC,
                    a.id DESC
                LIMIT 1
            ) AS ultima_adenda_documento,

            (
                SELECT a.fecha_cese
                FROM colab_contratos a
                WHERE a.contrato_padre_id = c.id
                  AND UPPER(COALESCE(NULLIF(TRIM(a.tipo_registro), ''), 'CONTRATO')) = 'ADENDA'
                ORDER BY
                    COALESCE(a.fecha_cese, '9999-12-31') DESC,
                    COALESCE(a.fecha_ingreso, '0000-00-00') DESC,
                    a.id DESC
                LIMIT 1
            ) AS ultima_adenda_cese

        FROM colab_contratos c

        INNER JOIN colab_maestro m
            ON m.id = c.colab_id

        LEFT JOIN (
            SELECT l1.*
            FROM colab_laboral l1
            INNER JOIN (
                SELECT colab_id, MAX(id) AS max_id
                FROM colab_laboral
                GROUP BY colab_id
            ) ult ON ult.max_id = l1.id
        ) l ON l.colab_id = m.id

        LEFT JOIN colab_contratos padre
            ON padre.id = c.contrato_padre_id

        ORDER BY
            m.nombres_apellidos ASC,
            COALESCE(padre.fecha_ingreso, c.fecha_ingreso, c.fecha_documento) DESC,
            COALESCE(c.contrato_padre_id, c.id) DESC,
            CASE
                WHEN UPPER(COALESCE(NULLIF(TRIM(c.tipo_registro), ''), 'CONTRATO')) = 'CONTRATO' THEN 0
                ELSE 1
            END ASC,
            c.fecha_ingreso ASC,
            c.id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($registros as $row) {
        $row['_adendas'] = [];
        $id = (int)($row['id'] ?? 0);

        if ($id <= 0) {
            continue;
        }

        $porId[$id] = $row;

        $tipo = tipoRegistroContrato($row);

        $modalidad = strtoupper(trim((string)($row['modalidad'] ?? '')));

        if ($modalidad !== '') {
            $modalidades[$modalidad] = $modalidad;
        }
    }

    foreach (array_keys($porId) as $id) {
        $tipo = tipoRegistroContrato($porId[$id]);
        $padreId = (int)($porId[$id]['contrato_padre_id'] ?? 0);

        if ($tipo === 'ADENDA' && $padreId > 0 && isset($porId[$padreId])) {
            $porId[$padreId]['_adendas'][] = $porId[$id];
        } else {
            $idsRaiz[] = $id;
        }
    }

    foreach ($idsRaiz as $id) {
        if (!isset($porId[$id])) {
            continue;
        }

        $porId[$id]['_adendas'] = ordenarAdendasHistorial($porId[$id]['_adendas'] ?? []);
        $contratosAgrupados[] = $porId[$id];
    }

    foreach ($contratosAgrupados as $grupo) {
        $vigenciaFinal = esAdendaContrato($grupo)
            ? ($grupo['fecha_cese'] ?? null)
            : obtenerVigenciaFinalGrupo($grupo);

        $estado = estadoVigenciaContrato($vigenciaFinal);

    }

    ksort($modalidades);

    $totalGrupos = count($contratosAgrupados);
} catch (Throwable $e) {
    $errorVista = $e->getMessage();
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

?>

<main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">

    <header class="h-20 bg-white shadow-sm flex items-center px-8 justify-between z-10 border-b border-red-50">
        <div>
            <h1 class="text-2xl font-black text-slate-800">Contratos y adendas</h1>
            <p class="text-sm text-slate-500">
                Vista jerárquica: contrato principal, vigencia actual e historial de adendas.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button type="button"
                data-open-modal="modalNuevoContrato"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-red-900 text-white text-sm font-black hover:bg-slate-900 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5" />
                </svg>
                Nuevo contrato
            </button>

            <a href="<?= BASE_URL ?>/rrhh/dashboard"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl border border-slate-200 bg-white text-slate-700 text-sm font-semibold hover:border-red-200 hover:text-red-800 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Volver
            </a>
        </div>
    </header>

    <section class="flex-1 overflow-y-auto p-8 space-y-7">

        <?php if (!empty($flashContrato)): ?>
            <?php
            $flashTipo = (string)($flashContrato['tipo'] ?? 'exito');
            $flashClase = $flashTipo === 'error'
                ? 'bg-red-50 border-red-200 text-red-700'
                : 'bg-emerald-50 border-emerald-200 text-emerald-700';
            ?>
            <div class="<?= $flashClase ?> border px-5 py-4 rounded-2xl font-semibold flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-white/70 flex items-center justify-center shrink-0">
                    <?php if ($flashTipo === 'error'): ?>
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                    <?php else: ?>
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    <?php endif; ?>
                </div>

                <div>
                    <p class="text-sm font-black">
                        <?= $flashTipo === 'error' ? 'No se pudo guardar' : 'Registro guardado' ?>
                    </p>
                    <p class="text-sm font-semibold opacity-90">
                        <?= eContrato($flashContrato['mensaje'] ?? '') ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorVista)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl font-semibold">
                Error al cargar contratos: <?= eContrato($errorVista) ?>
            </div>
        <?php endif; ?>

        <!-- PANEL PRINCIPAL -->
        <div class="bg-white border border-slate-200 rounded-[32px] shadow-sm overflow-hidden">

            <!-- FILTROS -->
            <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-br from-white via-slate-50 to-white">
                <div class="mb-5">
                    <h2 class="text-lg font-black text-slate-800">Listado jerárquico</h2>
                    <p class="text-sm text-slate-500 mt-0.5">
                        Cada contrato muestra su vigencia real y permite abrir sus adendas solo cuando sea necesario.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-3">

                    <div class="xl:col-span-2 relative">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z" />
                        </svg>

                        <input
                            type="text"
                            id="filtroTexto"
                            placeholder="Buscar nombre, DNI, contrato o adenda..."
                            class="w-full rounded-2xl border border-slate-200 bg-white pl-11 pr-4 py-3 text-sm font-semibold text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-4 focus:ring-red-100 focus:border-red-300">
                    </div>

                    <select id="filtroSituacion"
                        class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 focus:outline-none focus:ring-4 focus:ring-red-100 focus:border-red-300">
                        <option value="">Todos: activo/cesado</option>
                        <option value="activo">Solo activos</option>
                        <option value="cesado">Solo cesados</option>
                    </select>

                    <select id="filtroTipo"
                        class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 focus:outline-none focus:ring-4 focus:ring-red-100 focus:border-red-300">
                        <option value="">Agrupación: todos</option>
                        <option value="contrato">Contratos principales</option>
                        <option value="adenda">Con adendas</option>
                        <option value="sin_adenda">Sin adendas</option>
                    </select>

                    <select id="filtroEstado"
                        class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 focus:outline-none focus:ring-4 focus:ring-red-100 focus:border-red-300">
                        <option value="">Vigencia: todas</option>
                        <option value="vigente">Vigentes</option>
                        <option value="por_vencer">Por vencer</option>
                        <option value="vencido">Vencidos</option>
                        <option value="sin_validar">Sin validar</option>
                    </select>

                    <select id="filtroModalidad"
                        class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 focus:outline-none focus:ring-4 focus:ring-red-100 focus:border-red-300">
                        <option value="">Modalidad: todas</option>
                        <?php foreach ($modalidades as $modalidad): ?>
                            <option value="<?= eContrato(strtolower($modalidad)) ?>">
                                <?= eContrato($modalidad) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                </div>

                <div class="mt-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <label for="pageSizeContratos" class="text-xs font-black uppercase tracking-widest text-slate-400">
                            Mostrar
                        </label>

                        <select id="pageSizeContratos"
                            class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 focus:outline-none focus:ring-4 focus:ring-red-100 focus:border-red-300">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="todos">Todos</option>
                        </select>

                        <span class="text-xs font-bold text-slate-400">registros por página</span>
                    </div>

                    <button type="button" id="btnLimpiarFiltros"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-2xl bg-slate-100 text-slate-600 text-xs font-black hover:bg-slate-200 transition-all">
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <?php if (empty($contratosAgrupados)): ?>

                <div class="p-12 text-center">
                    <div class="mx-auto w-16 h-16 rounded-3xl bg-slate-100 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>

                    <h3 class="text-base font-black text-slate-700">No hay contratos registrados</h3>
                    <p class="text-sm text-slate-500 mt-1">Puedes registrar el primer contrato desde el botón superior.</p>

                    <button type="button"
                        data-open-modal="modalNuevoContrato"
                        class="mt-5 inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-red-900 text-white text-sm font-black hover:bg-slate-900 transition-all shadow-sm">
                        Registrar contrato
                    </button>
                </div>

            <?php else: ?>

                <div id="sinResultadosFiltro" class="hidden p-10 text-center border-b border-slate-100">
                    <div class="mx-auto w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16l2.879-2.879m0 0a3 3 0 104.242-4.242 3 3 0 00-4.242 4.242zM21 21l-4.35-4.35"></path>
                        </svg>
                    </div>

                    <h3 class="text-base font-black text-slate-700">Sin resultados</h3>
                    <p class="text-sm text-slate-500 mt-1">No hay contratos que coincidan con los filtros aplicados.</p>
                </div>

                <div class="px-6 py-4">
                    <div class="space-y-3" id="tablaContratos">
                        <?php foreach ($contratosAgrupados as $row): ?>
                            <?php
                            $idContrato = (int)($row['id'] ?? 0);
                            $idColab = (int)($row['colab_id'] ?? 0);
                            $nombre = trim((string)($row['nombres_apellidos'] ?? ''));
                            $dni = trim((string)($row['dni'] ?? ''));

                            $tipo = tipoRegistroContrato($row);
                            $esAdendaRaiz = esAdendaContrato($row);

                            $adendas = $row['_adendas'] ?? [];
                            $totalAdendasGrupo = count($adendas);
                            $tieneAdendas = $totalAdendasGrupo > 0;
                            $ultimaAdenda = obtenerUltimaAdendaGrupo($row);

                            $modalidad = strtoupper(trim((string)($row['modalidad'] ?? '')));
                            $situacion = strtoupper(trim((string)($row['situacion_colaborador'] ?? 'ACTIVO')));

                            $vigenciaFinal = $esAdendaRaiz
                                ? ($row['fecha_cese'] ?? null)
                                : obtenerVigenciaFinalGrupo($row);

                            $estado = estadoVigenciaContrato($vigenciaFinal);

                            $tipoFiltro = $esAdendaRaiz ? 'adenda' : 'contrato';
                            $situacionFiltro = $situacion === 'CESADO' ? 'cesado' : 'activo';
                            $modalidadFiltro = strtolower($modalidad);

                            $textoBusqueda = textoBusquedaGrupoContrato($row);

                            $situacionClase = $situacion === 'CESADO'
                                ? 'bg-red-50 text-red-700 border-red-100'
                                : 'bg-emerald-50 text-emerald-700 border-emerald-100';

                            $documentoPrincipal = $row['numero_documento']
                                ?: ($esAdendaRaiz ? 'Adenda sin número' : 'Contrato sin número');

                            $fechaInicio = formatearFechaContrato($row['fecha_ingreso'] ?? null);
                            $fechaCeseOriginal = formatearFechaContrato($row['fecha_cese'] ?? null);
                            $vigenciaFinalTexto = formatearFechaContrato($vigenciaFinal);

                            if ($vigenciaFinalTexto === '—') {
                                $vigenciaFinalTexto = 'Vigente / sin cese';
                            }

                            $tipoChipClase = $esAdendaRaiz
                                ? 'bg-slate-900 text-white border-slate-900'
                                : 'bg-red-50 text-red-800 border-red-100';
                            ?>

                            <div
                                class="fila-contrato group rounded-[28px] border border-slate-200 bg-white px-5 py-4 hover:shadow-md hover:border-red-200 transition-all"
                                data-search="<?= eContrato($textoBusqueda) ?>"
                                data-situacion="<?= eContrato($situacionFiltro) ?>"
                                data-tipo="<?= eContrato($tipoFiltro) ?>"
                                data-estado="<?= eContrato($estado['clave']) ?>"
                                data-modalidad="<?= eContrato($modalidadFiltro) ?>"
                                data-tiene-adendas="<?= $tieneAdendas ? '1' : '0' ?>">

                                <div class="grid grid-cols-1 2xl:grid-cols-[1.45fr_1.75fr_1.35fr_auto] gap-5 items-center">

                                    <!-- COLABORADOR -->
                                    <div class="min-w-0">
                                        <div class="flex items-start gap-3">
                                            <div class="w-11 h-11 rounded-2xl bg-slate-900 text-white flex items-center justify-center text-sm font-black shrink-0 shadow-sm">
                                                <?= eContrato(mb_substr($nombre !== '' ? $nombre : 'C', 0, 1)) ?>
                                            </div>

                                            <div class="min-w-0">
                                                <p class="text-sm font-black text-slate-800 leading-tight truncate">
                                                    <?= eContrato($nombre !== '' ? $nombre : 'Sin nombre') ?>
                                                </p>

                                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                                    <span class="text-[10px] font-black text-slate-500 bg-white border border-slate-200 px-2 py-1 rounded-lg">
                                                        DNI: <?= eContrato($dni !== '' ? $dni : '—') ?>
                                                    </span>

                                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg border text-[10px] font-black <?= $situacionClase ?>">
                                                        <span class="w-1.5 h-1.5 rounded-full <?= $situacion === 'CESADO' ? 'bg-red-500' : 'bg-emerald-500' ?>"></span>
                                                        <?= eContrato($situacion ?: 'ACTIVO') ?>
                                                    </span>
                                                </div>

                                                <?php if (!empty($row['area'])): ?>
                                                    <p class="text-[11px] text-slate-400 font-semibold mt-1 truncate">
                                                        <?= eContrato($row['area']) ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- CONTRATO PRINCIPAL -->
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border <?= $tipoChipClase ?>">
                                                <?= $esAdendaRaiz ? 'Adenda sin padre' : 'Contrato principal' ?>
                                            </span>

                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-white text-slate-700 border border-slate-200">
                                                <?= eContrato($modalidad !== '' ? $modalidad : 'SIN MODALIDAD') ?>
                                            </span>
                                            <?php if ($tieneAdendas): ?>
                                                <button type="button"
                                                    data-toggle-adendas
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-900 text-white hover:bg-red-900 transition-all">
                                                    <?= $totalAdendasGrupo ?> adenda(s)
                                                    <svg class="icon-toggle w-3.5 h-3.5 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-50 text-slate-500 border border-slate-200">
                                                    Sin adendas
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <p class="text-sm font-black text-red-900 leading-tight truncate">
                                            <?= eContrato($documentoPrincipal) ?>
                                        </p>

                                        <p class="text-[11px] text-slate-400 font-semibold mt-1">
                                            Fecha doc.:
                                            <span class="text-slate-600 font-bold">
                                                <?= eContrato(formatearFechaContrato($row['fecha_documento'] ?? null)) ?>
                                            </span>
                                        </p>

                                        <?php if ($ultimaAdenda): ?>
                                            <p class="text-[11px] text-slate-500 font-semibold mt-1 truncate">
                                                Última adenda:
                                                <span class="text-slate-800 font-black">
                                                    <?= eContrato($ultimaAdenda['numero_documento'] ?: 'Adenda sin número') ?>
                                                </span>
                                            </p>
                                        <?php elseif ($esAdendaRaiz && !empty($row['numero_documento_padre'])): ?>
                                            <p class="text-[11px] text-slate-500 font-semibold mt-1 truncate">
                                                Contrato padre:
                                                <span class="text-slate-800 font-black">
                                                    <?= eContrato($row['numero_documento_padre']) ?>
                                                </span>
                                            </p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- VIGENCIA ACTUAL -->
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                                            <?= $esAdendaRaiz ? 'Periodo de adenda' : 'Periodo inicial' ?>
                                        </p>

                                        <p class="text-xs font-black text-slate-700">
                                            <?= eContrato($fechaInicio) ?> — <?= eContrato($fechaCeseOriginal) ?>
                                        </p>

                                        <?php if (!$esAdendaRaiz): ?>
                                            <p class="text-[11px] text-slate-500 font-semibold mt-1">
                                                Vigencia actual:
                                                <span class="text-red-900 font-black">
                                                    <?= eContrato($vigenciaFinalTexto) ?>
                                                </span>
                                            </p>
                                        <?php endif; ?>

                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border <?= $estado['clase'] ?>">
                                                <span class="w-1.5 h-1.5 rounded-full <?= $estado['dot'] ?>"></span>
                                                <?= eContrato($estado['texto']) ?>
                                            </span>

                                            <span class="text-[11px] text-slate-400 font-semibold">
                                                <?= eContrato($estado['detalle']) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- ACCIONES MINIMALISTAS -->
                                    <div class="relative flex justify-end">
                                        <button type="button"
                                            data-open-action-menu
                                            class="w-11 h-11 inline-flex items-center justify-center rounded-2xl bg-slate-900 text-white hover:bg-red-900 transition-all shadow-sm"
                                            title="Acciones">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.7">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75h.01M12 12h.01M12 17.25h.01" />
                                            </svg>
                                        </button>

                                        <div data-action-menu
                                            class="hidden absolute right-0 top-13 z-40 w-56 rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-200/70 p-2">

                                            <?php if (!$esAdendaRaiz): ?>
                                                <button type="button"
                                                    data-open-adenda
                                                    data-contrato-id="<?= $idContrato ?>"
                                                    data-contrato-numero="<?= eContrato($documentoPrincipal) ?>"
                                                    data-contrato-colaborador="<?= eContrato($nombre !== '' ? $nombre : 'Sin nombre') ?>"
                                                    data-contrato-modalidad="<?= eContrato($modalidad) ?>"
                                                    data-contrato-vigencia="<?= eContrato($vigenciaFinalTexto) ?>"
                                                    class="w-full flex items-center gap-3 px-3 py-3 rounded-2xl text-left text-xs font-black text-slate-700 hover:bg-red-50 hover:text-red-900 transition-all">
                                                    <span class="w-8 h-8 rounded-xl bg-red-50 text-red-900 flex items-center justify-center">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.7">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5" />
                                                        </svg>
                                                    </span>
                                                    Agregar adenda
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($tieneAdendas): ?>
                                                <button type="button"
                                                    data-toggle-adendas
                                                    class="w-full flex items-center gap-3 px-3 py-3 rounded-2xl text-left text-xs font-black text-slate-700 hover:bg-slate-100 transition-all">
                                                    <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.7">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8M8 12h8M8 17h5" />
                                                        </svg>
                                                    </span>
                                                    Ver historial
                                                </button>
                                            <?php endif; ?>

                                            <a href="<?= BASE_URL ?>/rrhh/perfil/<?= $idColab ?>"
                                                class="w-full flex items-center gap-3 px-3 py-3 rounded-2xl text-left text-xs font-black text-slate-700 hover:bg-slate-100 transition-all">
                                                <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.7">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0" />
                                                    </svg>
                                                </span>
                                                Ver perfil
                                            </a>
                                        </div>
                                    </div>

                                </div>

                                <?php if ($tieneAdendas): ?>
                                    <div class="detalle-adendas hidden mt-4 pt-4 border-t border-slate-100">
                                        <div class="flex items-center justify-between gap-3 mb-3">
                                            <div>
                                                <p class="text-xs font-black text-slate-800">Historial de adendas</p>
                                                <p class="text-[11px] text-slate-400 font-semibold">
                                                    Línea de tiempo vinculada al contrato principal.
                                                </p>
                                            </div>

                                            <span class="hidden md:inline-flex items-center px-3 py-1.5 rounded-full bg-slate-50 border border-slate-200 text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                                <?= $totalAdendasGrupo ?> registro(s)
                                            </span>
                                        </div>

                                        <div class="space-y-2">
                                            <?php foreach ($adendas as $indexAdenda => $adenda): ?>
                                                <?php
                                                $numeroAdenda = $adenda['numero_documento'] ?: 'Adenda sin número';
                                                $estadoAdenda = estadoVigenciaContrato($adenda['fecha_cese'] ?? null);
                                                $textoAdenda = strtolower(textoBusquedaRegistroContrato($adenda));
                                                $motivoAdenda = trim((string)($adenda['motivo_adenda'] ?? ''));
                                                ?>

                                                <div
                                                    class="item-adenda rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3"
                                                    data-child-search="<?= eContrato($textoAdenda) ?>">

                                                    <div class="grid grid-cols-1 xl:grid-cols-[1.4fr_1fr_1fr] gap-3 items-center">

                                                        <div class="min-w-0">
                                                            <div class="flex items-center gap-2 mb-1">
                                                                <span class="w-6 h-6 rounded-xl bg-white border border-slate-200 text-slate-500 flex items-center justify-center text-[10px] font-black">
                                                                    <?= $indexAdenda + 1 ?>
                                                                </span>

                                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-900 text-white">
                                                                    Adenda
                                                                </span>
                                                            </div>

                                                            <p class="text-xs font-black text-slate-800 truncate">
                                                                <?= eContrato($numeroAdenda) ?>
                                                            </p>

                                                            <p class="text-[11px] text-slate-400 font-semibold mt-0.5">
                                                                Fecha doc.:
                                                                <span class="text-slate-600 font-bold">
                                                                    <?= eContrato(formatearFechaContrato($adenda['fecha_documento'] ?? null)) ?>
                                                                </span>
                                                            </p>
                                                        </div>

                                                        <div>
                                                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                                                                Periodo modificado
                                                            </p>

                                                            <p class="text-xs font-black text-slate-700">
                                                                <?= eContrato(formatearFechaContrato($adenda['fecha_ingreso'] ?? null)) ?>
                                                                —
                                                                <?= eContrato(formatearFechaContrato($adenda['fecha_cese'] ?? null)) ?>
                                                            </p>

                                                            <p class="text-[11px] text-slate-400 font-semibold mt-1 truncate">
                                                                <?= eContrato($motivoAdenda !== '' ? $motivoAdenda : 'Sin motivo registrado') ?>
                                                            </p>
                                                        </div>

                                                        <div class="xl:text-right">
                                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border <?= $estadoAdenda['clase'] ?>">
                                                                <span class="w-1.5 h-1.5 rounded-full <?= $estadoAdenda['dot'] ?>"></span>
                                                                <?= eContrato($estadoAdenda['texto']) ?>
                                                            </span>

                                                            <p class="text-[11px] text-slate-400 font-semibold mt-1">
                                                                <?= eContrato($estadoAdenda['detalle']) ?>
                                                            </p>
                                                        </div>

                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div id="paginacionContratos"
                        class="mt-5 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4 border-t border-slate-100 pt-4">
                        <div id="infoPaginacionContratos" class="text-xs font-black text-slate-500">
                            Mostrando 0 a 0 de 0 registros
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" id="btnPaginaAnterior"
                                class="px-3 py-2 rounded-2xl border border-slate-200 bg-white text-xs font-black text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed">
                                Anterior
                            </button>

                            <div id="numerosPaginacionContratos" class="flex flex-wrap items-center gap-1"></div>

                            <button type="button" id="btnPaginaSiguiente"
                                class="px-3 py-2 rounded-2xl border border-slate-200 bg-white text-xs font-black text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed">
                                Siguiente
                            </button>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

        </div>

    </section>
</main>

<!-- MODAL NUEVO CONTRATO -->
<div id="modalNuevoContrato"
    data-modal-backdrop
    class="modal-contrato hidden fixed inset-0 z-[100] bg-slate-950/55 backdrop-blur-sm px-4 py-6 overflow-y-auto">

    <div class="min-h-full flex items-center justify-center">
        <div class="w-full max-w-3xl bg-white rounded-[32px] shadow-2xl border border-white overflow-hidden">

            <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-br from-white via-red-50 to-white flex items-start justify-between gap-4">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.25em] text-red-700 mb-1">
                        Registro RR. HH.
                    </p>
                    <h3 class="text-xl font-black text-slate-900">Nuevo contrato</h3>
                    <p class="text-sm text-slate-500 mt-1">
                        Registra el contrato inicial de un colaborador.
                    </p>
                </div>

                <button type="button"
                    data-close-modal
                    class="w-10 h-10 rounded-2xl bg-white border border-slate-200 text-slate-500 hover:text-red-900 hover:border-red-200 transition-all flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="formNuevoContrato" method="POST" class="p-6 space-y-5">
                <input type="hidden" name="csrf_contrato" value="<?= eContrato($csrfContrato) ?>">
                <input type="hidden" name="accion_contrato" value="crear_contrato">

                <div class="relative" id="comboColaboradorContrato">
                    <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">
                        Colaborador
                    </label>

                    <input type="hidden" name="colab_id" id="nuevoContratoColabId">

                    <div class="relative">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z" />
                        </svg>

                        <input type="text"
                            id="buscadorColaboradorContrato"
                            autocomplete="off"
                            placeholder="Buscar por nombre o DNI..."
                            class="w-full rounded-2xl border border-slate-200 bg-white pl-11 pr-12 py-3 text-sm font-bold text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-4 focus:ring-red-100 focus:border-red-300"
                            <?= empty($colaboradores) ? 'disabled' : '' ?>>

                        <button type="button"
                            id="limpiarColaboradorContrato"
                            class="hidden absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 rounded-xl bg-slate-100 text-slate-500 hover:bg-red-50 hover:text-red-900 transition-all flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <p id="colaboradorSeleccionadoLabel"
                        class="hidden mt-2 text-[11px] font-black text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-2xl px-3 py-2">
                    </p>

                    <p id="errorColaboradorContrato"
                        class="hidden mt-2 text-[11px] font-black text-red-700 bg-red-50 border border-red-100 rounded-2xl px-3 py-2">
                        Selecciona un colaborador de la lista.
                    </p>

                    <div id="dropdownColaboradorContrato"
                        class="hidden absolute left-0 right-0 top-full mt-2 z-[150] rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-300/70 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                                Resultados
                            </p>
                        </div>

                        <div id="listaColaboradoresContrato" class="max-h-72 overflow-y-auto p-2 space-y-1">
                            <?php foreach ($colaboradores as $colaborador): ?>
                                <?php
                                $idOption = (int)($colaborador['id'] ?? 0);
                                $nombreOption = trim((string)($colaborador['nombres_apellidos'] ?? 'Sin nombre'));
                                $dniOption = trim((string)($colaborador['dni'] ?? ''));
                                $situacionOption = strtoupper(trim((string)($colaborador['situacion_colaborador'] ?? 'ACTIVO')));
                                $textoBusquedaOption = strtolower($nombreOption . ' ' . $dniOption . ' ' . $situacionOption);
                                $esCesadoOption = $situacionOption === 'CESADO';
                                ?>

                                <button type="button"
                                    data-colab-option
                                    data-id="<?= $idOption ?>"
                                    data-nombre="<?= eContrato($nombreOption) ?>"
                                    data-dni="<?= eContrato($dniOption) ?>"
                                    data-situacion="<?= eContrato($situacionOption) ?>"
                                    data-search="<?= eContrato($textoBusquedaOption) ?>"
                                    class="w-full text-left rounded-2xl px-3 py-3 hover:bg-red-50 transition-all group">

                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-2xl bg-slate-900 text-white flex items-center justify-center text-xs font-black shrink-0">
                                            <?= eContrato(mb_substr($nombreOption !== '' ? $nombreOption : 'C', 0, 1)) ?>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-black text-slate-800 truncate group-hover:text-red-900">
                                                <?= eContrato($nombreOption) ?>
                                            </p>

                                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                                <span class="text-[10px] font-black text-slate-500 bg-white border border-slate-200 px-2 py-1 rounded-lg">
                                                    DNI: <?= eContrato($dniOption !== '' ? $dniOption : '—') ?>
                                                </span>

                                                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg border text-[10px] font-black <?= $esCesadoOption ? 'bg-red-50 text-red-700 border-red-100' : 'bg-emerald-50 text-emerald-700 border-emerald-100' ?>">
                                                    <span class="w-1.5 h-1.5 rounded-full <?= $esCesadoOption ? 'bg-red-500' : 'bg-emerald-500' ?>"></span>
                                                    <?= eContrato($situacionOption ?: 'ACTIVO') ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </button>
                            <?php endforeach; ?>

                            <div id="sinResultadosColaboradorContrato"
                                class="hidden px-4 py-6 text-center">
                                <p class="text-sm font-black text-slate-600">Sin resultados</p>
                                <p class="text-xs text-slate-400 mt-1">No se encontró personal con ese criterio.</p>
                            </div>
                        </div>
                    </div>

                    <?php if (empty($colaboradores)): ?>
                        <p class="text-xs text-red-600 font-semibold mt-2">
                            No hay colaboradores disponibles para asociar contratos.
                        </p>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">
                            N° de contrato
                        </label>
                        <input type="text" name="numero_documento" required placeholder="Ej. CONTRATO N° 001-2026"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-4 focus:ring-red-100 focus:border-red-300">
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">
                            Modalidad
                        </label>
                        <input type="text" name="modalidad" list="listaModalidadesContrato" placeholder="CAS, MILITAR, PAC..."
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-4 focus:ring-red-100 focus:border-red-300">
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">
                            Fecha documento
                        </label>
                        <input type="date" name="fecha_documento"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 focus:outline-none focus:ring-4 focus:ring-red-100 focus:border-red-300">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">
                            Inicio contrato
                        </label>
                        <input type="date" name="fecha_ingreso" required
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 focus:outline-none focus:ring-4 focus:ring-red-100 focus:border-red-300">
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">
                            Fin de Contrato
                        </label>
                        <input type="date" name="fecha_cese"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 focus:outline-none focus:ring-4 focus:ring-red-100 focus:border-red-300">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">
                        Observación
                    </label>
                    <textarea name="observacion" rows="3" placeholder="Detalle opcional del contrato..."
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-4 focus:ring-red-100 focus:border-red-300"></textarea>
                </div>

                <div class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-2">
                    <button type="button" data-close-modal
                        class="inline-flex items-center justify-center px-5 py-3 rounded-2xl bg-slate-100 text-slate-600 text-sm font-black hover:bg-slate-200 transition-all">
                        Cancelar
                    </button>

                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-red-900 text-white text-sm font-black hover:bg-slate-900 transition-all shadow-sm"
                        <?= empty($colaboradores) ? 'disabled' : '' ?>>
                        Guardar contrato
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL NUEVA ADENDA -->
<div id="modalNuevaAdenda"
    data-modal-backdrop
    class="modal-contrato hidden fixed inset-0 z-[100] bg-slate-950/55 backdrop-blur-sm px-4 py-6 overflow-y-auto">

    <div class="min-h-full flex items-center justify-center">
        <div class="w-full max-w-3xl bg-white rounded-[32px] shadow-2xl border border-white overflow-hidden">

            <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-br from-white via-slate-50 to-white flex items-start justify-between gap-4">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.25em] text-slate-500 mb-1">
                        Historial contractual
                    </p>
                    <h3 class="text-xl font-black text-slate-900">Agregar adenda</h3>
                    <p class="text-sm text-slate-500 mt-1">
                        La adenda se vinculará al contrato principal seleccionado.
                    </p>
                </div>

                <button type="button"
                    data-close-modal
                    class="w-10 h-10 rounded-2xl bg-white border border-slate-200 text-slate-500 hover:text-red-900 hover:border-red-200 transition-all flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" class="p-6 space-y-5">
                <input type="hidden" name="csrf_contrato" value="<?= eContrato($csrfContrato) ?>">
                <input type="hidden" name="accion_contrato" value="crear_adenda">
                <input type="hidden" name="contrato_padre_id" id="adendaContratoPadreId">

                <div class="rounded-3xl bg-slate-50 border border-slate-200 p-4">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">
                        Contrato seleccionado
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Colaborador</p>
                            <p id="adendaContratoColaborador" class="text-sm font-black text-slate-800 truncate">—</p>
                        </div>

                        <div>
                            <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Contrato</p>
                            <p id="adendaContratoNumero" class="text-sm font-black text-red-900 truncate">—</p>
                        </div>

                        <div>
                            <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Vigencia actual</p>
                            <p id="adendaContratoVigencia" class="text-sm font-black text-slate-800 truncate">—</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">
                            N° de adenda
                        </label>
                        <input type="text" name="numero_documento" required placeholder="Ej. ADENDA N° 001-2026"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-4 focus:ring-red-100 focus:border-red-300">
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">
                            Modalidad
                        </label>
                        <input type="text" name="modalidad" id="adendaModalidad" list="listaModalidadesContrato" placeholder="Se hereda si se deja vacío"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-4 focus:ring-red-100 focus:border-red-300">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">
                        Motivo de adenda
                    </label>
                    <input type="text" name="motivo_adenda" required placeholder="Ej. Ampliación de plazo, renovación, modificación contractual..."
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-4 focus:ring-red-100 focus:border-red-300">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">
                            Fecha documento
                        </label>
                        <input type="date" name="fecha_documento"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 focus:outline-none focus:ring-4 focus:ring-red-100 focus:border-red-300">
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">
                            Inicio adenda
                        </label>
                        <input type="date" name="fecha_ingreso" required
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 focus:outline-none focus:ring-4 focus:ring-red-100 focus:border-red-300">
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">
                            Fin adenda
                        </label>
                        <input type="date" name="fecha_cese" required
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 focus:outline-none focus:ring-4 focus:ring-red-100 focus:border-red-300">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">
                        Observación
                    </label>
                    <textarea name="observacion" rows="3" placeholder="Detalle opcional de la adenda..."
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-4 focus:ring-red-100 focus:border-red-300"></textarea>
                </div>

                <div class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-2">
                    <button type="button" data-close-modal
                        class="inline-flex items-center justify-center px-5 py-3 rounded-2xl bg-slate-100 text-slate-600 text-sm font-black hover:bg-slate-200 transition-all">
                        Cancelar
                    </button>

                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-red-900 text-white text-sm font-black hover:bg-slate-900 transition-all shadow-sm">
                        Guardar adenda
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$modalidadesDatalist = [];

foreach ($modalidades as $modalidad) {
    $modalidadLimpia = strtoupper(trim((string)$modalidad));

    if ($modalidadLimpia !== '') {
        $modalidadesDatalist[$modalidadLimpia] = $modalidadLimpia;
    }
}

foreach (['CAS', 'MILITAR', 'PAC'] as $modalidadBase) {
    $modalidadesDatalist[$modalidadBase] = $modalidadBase;
}

ksort($modalidadesDatalist);
?>

<datalist id="listaModalidadesContrato">
    <?php foreach ($modalidadesDatalist as $modalidad): ?>
        <option value="<?= eContrato($modalidad) ?>"></option>
    <?php endforeach; ?>
</datalist>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const inputTexto = document.getElementById('filtroTexto');
        const filtroSituacion = document.getElementById('filtroSituacion');
        const filtroTipo = document.getElementById('filtroTipo');
        const filtroEstado = document.getElementById('filtroEstado');
        const filtroModalidad = document.getElementById('filtroModalidad');
        const btnLimpiar = document.getElementById('btnLimpiarFiltros');
        const filas = Array.from(document.querySelectorAll('.fila-contrato'));
        const sinResultados = document.getElementById('sinResultadosFiltro');
        const pageSizeContratos = document.getElementById('pageSizeContratos');
        const paginacionContratos = document.getElementById('paginacionContratos');
        const infoPaginacionContratos = document.getElementById('infoPaginacionContratos');
        const numerosPaginacionContratos = document.getElementById('numerosPaginacionContratos');
        const btnPaginaAnterior = document.getElementById('btnPaginaAnterior');
        const btnPaginaSiguiente = document.getElementById('btnPaginaSiguiente');

        let paginaActualContratos = 1;
        let filasFiltradasContratos = [];

        function normalizar(texto) {
            return (texto || '')
                .toString()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim();
        }

        function abrirCerrarHistorial(fila, abrir) {
            const detalle = fila.querySelector('.detalle-adendas');
            const textoToggle = fila.querySelector('.texto-toggle');
            const iconToggle = fila.querySelector('.icon-toggle');

            if (!detalle) {
                return;
            }

            detalle.classList.toggle('hidden', !abrir);

            if (textoToggle) {
                textoToggle.textContent = abrir ? 'Ocultar historial' : 'Ver historial';
            }

            cerrarMenusAcciones();

            if (iconToggle) {
                iconToggle.classList.toggle('rotate-180', abrir);
            }

            fila.dataset.historialAbierto = abrir ? '1' : '0';
        }

        document.querySelectorAll('[data-toggle-adendas]').forEach(btn => {
            btn.addEventListener('click', function() {
                const fila = btn.closest('.fila-contrato');

                if (!fila) {
                    return;
                }

                const estaAbierto = fila.dataset.historialAbierto === '1';
                abrirCerrarHistorial(fila, !estaAbierto);
            });
        });

        function cumpleFiltroTipo(fila, tipo) {
            const tipoFila = fila.dataset.tipo || '';
            const tieneAdendas = fila.dataset.tieneAdendas === '1';

            if (tipo === '') {
                return true;
            }

            if (tipo === 'contrato') {
                return tipoFila === 'contrato';
            }

            if (tipo === 'adenda') {
                return tieneAdendas;
            }

            if (tipo === 'sin_adenda') {
                return !tieneAdendas;
            }

            return true;
        }

        function obtenerPageSizeContratos() {
            if (!pageSizeContratos) {
                return 10;
            }

            if (pageSizeContratos.value === 'todos') {
                return Math.max(filasFiltradasContratos.length, 1);
            }

            const valor = parseInt(pageSizeContratos.value, 10);

            return Number.isFinite(valor) && valor > 0 ? valor : 10;
        }

        function crearBotonPagina(numero, activo) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = numero.toLocaleString('es-PE');
            btn.className = activo
                ? 'min-w-9 px-3 py-2 rounded-2xl bg-red-900 text-white text-xs font-black border border-red-900'
                : 'min-w-9 px-3 py-2 rounded-2xl bg-white text-slate-600 text-xs font-black border border-slate-200 hover:bg-slate-100';

            btn.addEventListener('click', function() {
                paginaActualContratos = numero;
                renderizarPaginaContratos();
            });

            return btn;
        }

        function crearPuntosPaginacion() {
            const span = document.createElement('span');
            span.textContent = '...';
            span.className = 'px-2 py-2 text-xs font-black text-slate-400';

            return span;
        }

        function renderizarNumerosPaginacion(totalPaginas) {
            if (!numerosPaginacionContratos) {
                return;
            }

            numerosPaginacionContratos.innerHTML = '';

            if (totalPaginas <= 1) {
                return;
            }

            const paginas = new Set([1, totalPaginas]);

            for (let i = paginaActualContratos - 1; i <= paginaActualContratos + 1; i++) {
                if (i >= 1 && i <= totalPaginas) {
                    paginas.add(i);
                }
            }

            const ordenadas = Array.from(paginas).sort((a, b) => a - b);
            let anterior = 0;

            ordenadas.forEach(numero => {
                if (anterior > 0 && numero - anterior > 1) {
                    numerosPaginacionContratos.appendChild(crearPuntosPaginacion());
                }

                numerosPaginacionContratos.appendChild(crearBotonPagina(numero, numero === paginaActualContratos));
                anterior = numero;
            });
        }

        function renderizarPaginaContratos() {
            const total = filasFiltradasContratos.length;
            const pageSize = obtenerPageSizeContratos();
            const totalPaginas = Math.max(Math.ceil(total / pageSize), 1);

            if (paginaActualContratos > totalPaginas) {
                paginaActualContratos = totalPaginas;
            }

            if (paginaActualContratos < 1) {
                paginaActualContratos = 1;
            }

            const inicio = (paginaActualContratos - 1) * pageSize;
            const fin = inicio + pageSize;
            const filasPagina = new Set(filasFiltradasContratos.slice(inicio, fin));

            filas.forEach(fila => {
                const debeMostrar = filasPagina.has(fila);
                fila.style.display = debeMostrar ? '' : 'none';
            });

            if (sinResultados) {
                sinResultados.classList.toggle('hidden', total !== 0);
            }

            if (paginacionContratos) {
                paginacionContratos.classList.toggle('hidden', total === 0);
            }

            if (infoPaginacionContratos) {
                const desde = total === 0 ? 0 : inicio + 1;
                const hasta = Math.min(fin, total);
                infoPaginacionContratos.textContent = `Mostrando ${desde.toLocaleString('es-PE')} a ${hasta.toLocaleString('es-PE')} de ${total.toLocaleString('es-PE')} registro(s)`;
            }

            if (btnPaginaAnterior) {
                btnPaginaAnterior.disabled = paginaActualContratos <= 1;
            }

            if (btnPaginaSiguiente) {
                btnPaginaSiguiente.disabled = paginaActualContratos >= totalPaginas;
            }

            renderizarNumerosPaginacion(totalPaginas);
            cerrarMenusAcciones();
        }

        function aplicarFiltros(resetPagina = true) {
            const q = normalizar(inputTexto ? inputTexto.value : '');
            const situacion = filtroSituacion ? filtroSituacion.value : '';
            const tipo = filtroTipo ? filtroTipo.value : '';
            const estado = filtroEstado ? filtroEstado.value : '';
            const modalidad = filtroModalidad ? filtroModalidad.value : '';

            filasFiltradasContratos = [];

            filas.forEach(fila => {
                const texto = normalizar(fila.dataset.search || '');
                const cumpleTexto = q === '' || texto.includes(q);
                const cumpleSituacion = situacion === '' || fila.dataset.situacion === situacion;
                const cumpleTipo = cumpleFiltroTipo(fila, tipo);
                const cumpleEstado = estado === '' || fila.dataset.estado === estado;
                const cumpleModalidad = modalidad === '' || fila.dataset.modalidad === modalidad;

                const visible = cumpleTexto && cumpleSituacion && cumpleTipo && cumpleEstado && cumpleModalidad;

                if (visible) {
                    filasFiltradasContratos.push(fila);

                    const adendas = Array.from(fila.querySelectorAll('.item-adenda'));
                    const hayCoincidenciaAdenda = q !== '' && adendas.some(adenda => {
                        return normalizar(adenda.dataset.childSearch || '').includes(q);
                    });

                    if (hayCoincidenciaAdenda || tipo === 'adenda') {
                        abrirCerrarHistorial(fila, true);
                    }
                }
            });

            if (resetPagina) {
                paginaActualContratos = 1;
            }

            renderizarPaginaContratos();
        }

        [inputTexto, filtroSituacion, filtroTipo, filtroEstado, filtroModalidad].forEach(el => {
            if (el) {
                el.addEventListener('input', function() {
                    aplicarFiltros(true);
                });
                el.addEventListener('change', function() {
                    aplicarFiltros(true);
                });
            }
        });

        if (pageSizeContratos) {
            pageSizeContratos.addEventListener('change', function() {
                aplicarFiltros(true);
            });
        }

        if (btnPaginaAnterior) {
            btnPaginaAnterior.addEventListener('click', function() {
                if (paginaActualContratos > 1) {
                    paginaActualContratos--;
                    renderizarPaginaContratos();
                }
            });
        }

        if (btnPaginaSiguiente) {
            btnPaginaSiguiente.addEventListener('click', function() {
                const pageSize = obtenerPageSizeContratos();
                const totalPaginas = Math.max(Math.ceil(filasFiltradasContratos.length / pageSize), 1);

                if (paginaActualContratos < totalPaginas) {
                    paginaActualContratos++;
                    renderizarPaginaContratos();
                }
            });
        }

        if (btnLimpiar) {
            btnLimpiar.addEventListener('click', function() {
                if (inputTexto) inputTexto.value = '';
                if (filtroSituacion) filtroSituacion.value = '';
                if (filtroTipo) filtroTipo.value = '';
                if (filtroEstado) filtroEstado.value = '';
                if (filtroModalidad) filtroModalidad.value = '';

                filas.forEach(fila => abrirCerrarHistorial(fila, false));

                aplicarFiltros(true);
            });
        }

        /* BUSCADOR TIPO SELECT2 SIN LIBRERÍA */
        const comboColaboradorContrato = document.getElementById('comboColaboradorContrato');
        const buscadorColaboradorContrato = document.getElementById('buscadorColaboradorContrato');
        const dropdownColaboradorContrato = document.getElementById('dropdownColaboradorContrato');
        const nuevoContratoColabId = document.getElementById('nuevoContratoColabId');
        const limpiarColaboradorContrato = document.getElementById('limpiarColaboradorContrato');
        const colaboradorSeleccionadoLabel = document.getElementById('colaboradorSeleccionadoLabel');
        const errorColaboradorContrato = document.getElementById('errorColaboradorContrato');
        const sinResultadosColaboradorContrato = document.getElementById('sinResultadosColaboradorContrato');
        const formNuevoContrato = document.getElementById('formNuevoContrato');
        const opcionesColaboradorContrato = Array.from(document.querySelectorAll('[data-colab-option]'));

        function abrirDropdownColaborador() {
            if (dropdownColaboradorContrato && opcionesColaboradorContrato.length > 0) {
                dropdownColaboradorContrato.classList.remove('hidden');
            }
        }

        function cerrarDropdownColaborador() {
            if (dropdownColaboradorContrato) {
                dropdownColaboradorContrato.classList.add('hidden');
            }
        }

        function limpiarSeleccionColaborador() {
            if (nuevoContratoColabId) {
                nuevoContratoColabId.value = '';
            }

            if (buscadorColaboradorContrato) {
                buscadorColaboradorContrato.value = '';
                buscadorColaboradorContrato.focus();
            }

            if (colaboradorSeleccionadoLabel) {
                colaboradorSeleccionadoLabel.textContent = '';
                colaboradorSeleccionadoLabel.classList.add('hidden');
            }

            if (limpiarColaboradorContrato) {
                limpiarColaboradorContrato.classList.add('hidden');
            }

            if (errorColaboradorContrato) {
                errorColaboradorContrato.classList.add('hidden');
            }

            opcionesColaboradorContrato.forEach(opcion => {
                opcion.classList.remove('hidden');
            });

            if (sinResultadosColaboradorContrato) {
                sinResultadosColaboradorContrato.classList.add('hidden');
            }

            abrirDropdownColaborador();
        }

        function seleccionarColaboradorContrato(opcion) {
            const id = opcion.dataset.id || '';
            const nombre = opcion.dataset.nombre || '';
            const dni = opcion.dataset.dni || '';
            const situacion = opcion.dataset.situacion || 'ACTIVO';

            if (nuevoContratoColabId) {
                nuevoContratoColabId.value = id;
            }

            if (buscadorColaboradorContrato) {
                buscadorColaboradorContrato.value = dni ? `${nombre} — DNI: ${dni}` : nombre;
            }

            if (colaboradorSeleccionadoLabel) {
                colaboradorSeleccionadoLabel.textContent = `Seleccionado: ${nombre}${dni ? ' — DNI: ' + dni : ''} — ${situacion}`;
                colaboradorSeleccionadoLabel.classList.remove('hidden');
            }

            if (limpiarColaboradorContrato) {
                limpiarColaboradorContrato.classList.remove('hidden');
            }

            if (errorColaboradorContrato) {
                errorColaboradorContrato.classList.add('hidden');
            }

            cerrarDropdownColaborador();
        }

        function filtrarColaboradoresContrato() {
            const q = normalizar(buscadorColaboradorContrato ? buscadorColaboradorContrato.value : '');
            let visibles = 0;

            opcionesColaboradorContrato.forEach(opcion => {
                const texto = normalizar(opcion.dataset.search || '');
                const visible = q === '' || texto.includes(q);

                opcion.classList.toggle('hidden', !visible);

                if (visible) {
                    visibles++;
                }
            });

            if (sinResultadosColaboradorContrato) {
                sinResultadosColaboradorContrato.classList.toggle('hidden', visibles !== 0);
            }
        }

        if (buscadorColaboradorContrato) {
            buscadorColaboradorContrato.addEventListener('click', function(e) {
                e.stopPropagation();
                abrirDropdownColaborador();
                filtrarColaboradoresContrato();
            });

            buscadorColaboradorContrato.addEventListener('input', function() {
                if (nuevoContratoColabId) {
                    nuevoContratoColabId.value = '';
                }

                if (colaboradorSeleccionadoLabel) {
                    colaboradorSeleccionadoLabel.textContent = '';
                    colaboradorSeleccionadoLabel.classList.add('hidden');
                }

                if (limpiarColaboradorContrato) {
                    limpiarColaboradorContrato.classList.add('hidden');
                }

                if (errorColaboradorContrato) {
                    errorColaboradorContrato.classList.add('hidden');
                }

                abrirDropdownColaborador();
                filtrarColaboradoresContrato();
            });
        }

        opcionesColaboradorContrato.forEach(opcion => {
            opcion.addEventListener('click', function(e) {
                e.stopPropagation();
                seleccionarColaboradorContrato(opcion);
            });
        });

        if (limpiarColaboradorContrato) {
            limpiarColaboradorContrato.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                limpiarSeleccionColaborador();
            });
        }

        document.addEventListener('click', function(e) {
            if (comboColaboradorContrato && !comboColaboradorContrato.contains(e.target)) {
                cerrarDropdownColaborador();
            }
        });

        if (formNuevoContrato) {
            formNuevoContrato.addEventListener('submit', function(e) {
                if (!nuevoContratoColabId || nuevoContratoColabId.value === '') {
                    e.preventDefault();

                    if (errorColaboradorContrato) {
                        errorColaboradorContrato.classList.remove('hidden');
                    }

                    if (buscadorColaboradorContrato) {
                        buscadorColaboradorContrato.focus();
                    }

                    abrirDropdownColaborador();
                }
            });
        }

        function abrirModal(modal) {
            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');

            const primerCampo = modal.querySelector('input:not([type="hidden"]), select, textarea');

            if (primerCampo) {
                setTimeout(() => primerCampo.focus(), 80);
            }
        }

        function cerrarModal(modal) {
            if (!modal) {
                return;
            }

            modal.classList.add('hidden');

            const abiertos = document.querySelectorAll('.modal-contrato:not(.hidden)');

            if (abiertos.length === 0) {
                document.body.classList.remove('overflow-hidden');
            }
        }

        document.querySelectorAll('[data-open-modal]').forEach(btn => {
            btn.addEventListener('click', function() {
                const modalId = btn.dataset.openModal;
                const modal = document.getElementById(modalId);
                abrirModal(modal);
            });
        });

        document.querySelectorAll('[data-close-modal]').forEach(btn => {
            btn.addEventListener('click', function() {
                cerrarModal(btn.closest('.modal-contrato'));
            });
        });

        document.querySelectorAll('[data-modal-backdrop]').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    cerrarModal(modal);
                }
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-contrato:not(.hidden)').forEach(modal => cerrarModal(modal));
            }
        });

        const modalNuevaAdenda = document.getElementById('modalNuevaAdenda');
        const adendaContratoPadreId = document.getElementById('adendaContratoPadreId');
        const adendaContratoColaborador = document.getElementById('adendaContratoColaborador');
        const adendaContratoNumero = document.getElementById('adendaContratoNumero');
        const adendaContratoVigencia = document.getElementById('adendaContratoVigencia');
        const adendaModalidad = document.getElementById('adendaModalidad');

        document.querySelectorAll('[data-open-adenda]').forEach(btn => {
            btn.addEventListener('click', function() {
                const contratoId = btn.dataset.contratoId || '';
                const contratoNumero = btn.dataset.contratoNumero || '—';
                const contratoColaborador = btn.dataset.contratoColaborador || '—';
                const contratoVigencia = btn.dataset.contratoVigencia || '—';
                const contratoModalidad = btn.dataset.contratoModalidad || '';

                if (adendaContratoPadreId) {
                    adendaContratoPadreId.value = contratoId;
                }

                if (adendaContratoColaborador) {
                    adendaContratoColaborador.textContent = contratoColaborador;
                }

                if (adendaContratoNumero) {
                    adendaContratoNumero.textContent = contratoNumero;
                }

                if (adendaContratoVigencia) {
                    adendaContratoVigencia.textContent = contratoVigencia;
                }

                if (adendaModalidad) {
                    adendaModalidad.value = contratoModalidad;
                }

                abrirModal(modalNuevaAdenda);
            });
        });

        function cerrarMenusAcciones() {
            document.querySelectorAll('[data-action-menu]').forEach(menu => {
                menu.classList.add('hidden');
            });
        }

        document.querySelectorAll('[data-open-action-menu]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();

                const contenedor = btn.closest('.relative');
                const menu = contenedor ? contenedor.querySelector('[data-action-menu]') : null;

                if (!menu) {
                    return;
                }

                const estabaAbierto = !menu.classList.contains('hidden');

                cerrarMenusAcciones();

                if (!estabaAbierto) {
                    menu.classList.remove('hidden');
                }
            });
        });

        document.querySelectorAll('[data-action-menu]').forEach(menu => {
            menu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });

        document.addEventListener('click', function() {
            cerrarMenusAcciones();
        });

        aplicarFiltros(true);
    });
</script>