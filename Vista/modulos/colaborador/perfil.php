<?php
// Vista/modulos/colaborador/perfil.php
require_once __DIR__ . '/../../../Modelo/MdDirectorio.php';

if (!isset($datos) || empty($datos)) {
    $idUsuario = $_SESSION['user_id'] ?? null;
    if ($idUsuario) {
        $datos = MdDirectorio::mdlObtenerPerfilCompleto($idUsuario);
    }
}

$perfil = $datos ?: [
    'nombres_apellidos'  => 'Usuario no encontrado',
    'puesto_cas'         => '---',
    'area'               => '---',
    'dni'                => '---',
    'correo_institucional' => '---',
    'celular'            => '---',
    'n_hijos'            => '0',
    'fecha_nacimiento'   => null,
];

function calcularEdad(?string $f): string
{
    if (!$f) return '—';
    try {
        return (new DateTime())->diff(new DateTime($f))->y . ' años';
    } catch (Exception $e) {
        return '—';
    }
}
function fmt(?string $f): string
{
    if (!$f) return '—';
    try {
        return (new DateTime($f))->format('d/m/Y');
    } catch (Exception $e) {
        return $f;
    }
}

$titulo_pagina  = "Mi Perfil | Portal del Colaborador";
$menu_activo    = "perfil";
$contratos      = $perfil['contratos'] ?? [];
$formacion      = $perfil['formacion'] ?? [];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="flex-1 bg-slate-50 overflow-y-auto pb-24">

    <!-- ═══════════════════════════════════════════════
         HERO BANNER + AVATAR
    ═══════════════════════════════════════════════ -->
    <div class="relative">
        <div class="h-44 bg-gradient-to-r from-[#310404] via-[#4c0505] to-red-900 shadow-lg"></div>

        <div class="max-w-5xl mx-auto px-6">
            <div class="relative -mt-16 flex flex-col md:flex-row items-end gap-6 pb-0">

                <!-- Avatar -->
                <div class="relative shrink-0">
                    <div class="w-32 h-32 rounded-3xl bg-red-900 border-4 border-white shadow-2xl flex items-center justify-center text-5xl font-black text-white select-none">
                        <?php echo mb_substr($perfil['nombres_apellidos'], 0, 1); ?>
                    </div>
                    <?php if (!empty($perfil['situacion'])): ?>
                        <span class="absolute -bottom-2 -right-2 text-[10px] font-black uppercase px-2 py-0.5 rounded-full border-2 border-white
                        <?php echo strtoupper($perfil['situacion']) === 'ACTIVO' ? 'bg-green-500 text-white' : 'bg-red-600 text-white'; ?>">
                            <?php echo htmlspecialchars($perfil['situacion']); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Nombre y badges -->
                <div class="flex-1 pb-4">
                    <h1 class="text-2xl md:text-3xl font-black text-white tracking-tight leading-tight">
                        <?php echo htmlspecialchars($perfil['nombres_apellidos']); ?>
                    </h1>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <span class="bg-red-50 text-red-900 border border-red-100 px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-widest">
                            <?php echo htmlspecialchars($perfil['puesto_cas'] ?? '—'); ?>
                        </span>
                        <span class="bg-slate-100 text-slate-600 border border-slate-200 px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-widest">
                            <?php echo htmlspecialchars($perfil['area'] ?? '—'); ?>
                        </span>
                    </div>
                </div>

                <!-- Botón Actualizar -->
                <div class="pb-4">
                    <button onclick="abrirModal()"
                        class="inline-flex items-center gap-2 bg-red-900 hover:bg-[#310404] text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-red-900/25 transition-all active:scale-95">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.25 2.25 0 113.182 3.182L7.5 19.213l-4 1 1-4 12.362-12.726z" />
                        </svg>
                        Actualizar Información
                    </button>
                </div>
            </div>

            <!-- ── TABS ── -->
            <div class="bg-white rounded-t-3xl border border-b-0 border-slate-200 mt-4 px-6 flex gap-0 overflow-x-auto no-scrollbar">
                <?php
                $tabs = [
                    ['id' => 'personal',   'label' => 'Datos Personales'],
                    ['id' => 'contacto',   'label' => 'Contacto'],
                    ['id' => 'laboral',    'label' => 'Datos Laborales'],
                    ['id' => 'familia',    'label' => 'Familia'],
                    ['id' => 'formacion',  'label' => 'Formación'],
                ];
                foreach ($tabs as $i => $tab): ?>
                    <button onclick="switchTab('<?php echo $tab['id']; ?>')"
                        id="btn-<?php echo $tab['id']; ?>"
                        class="tab-btn <?php echo $i === 0 ? 'tab-active' : 'tab-idle'; ?> whitespace-nowrap px-5 py-4 text-xs font-black uppercase tracking-widest relative transition-colors">
                        <?php echo $tab['label']; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════
         CONTENIDO DE TABS
    ═══════════════════════════════════════════════ -->
    <div class="max-w-5xl mx-auto px-6">
        <div class="bg-white rounded-b-3xl rounded-tr-3xl border border-slate-200 shadow-sm p-8 min-h-[320px]">

            <!-- ─── TAB: DATOS PERSONALES ─── -->
            <div id="tab-personal" class="tab-panel block animate-in">
                <h3 class="section-title">Datos Personales</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                    <?php
                    $sexo = $perfil['sexo'] ?? '';
                    $campos = [
                        ['DNI',             htmlspecialchars($perfil['dni'] ?? '—')],
                        ['Fecha de Nac.',   fmt($perfil['fecha_nacimiento'] ?? null)],
                        ['Edad',            calcularEdad($perfil['fecha_nacimiento'] ?? null)],
                        ['Lugar de Nac.',   htmlspecialchars($perfil['lugar_nacimiento'] ?? '—')],
                        ['Sexo',            $sexo === 'M' ? 'Masculino' : ($sexo === 'F' ? 'Femenina' : '—')],
                        ['Estado Civil',    htmlspecialchars($perfil['estado_civil'] ?? '—')],
                        ['Grupo Sanguíneo', htmlspecialchars($perfil['grupo_sanguineo'] ?? '—')],
                        ['Talla',           htmlspecialchars($perfil['talla'] ?? '—')],
                    ];
                    foreach ($campos as [$label, $val]): ?>
                        <div class="info-card">
                            <p class="info-label"><?php echo $label; ?></p>
                            <p class="info-value"><?php echo $val; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ─── TAB: CONTACTO ─── -->
            <div id="tab-contacto" class="tab-panel hidden animate-in">
                <h3 class="section-title">Contacto y Domicilio</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6">
                    <div class="info-card col-span-full sm:col-span-2">
                        <p class="info-label">Dirección de Residencia</p>
                        <p class="info-value"><?php echo htmlspecialchars($perfil['direccion_residencia'] ?? '—'); ?></p>
                        <?php if (!empty($perfil['distrito'])): ?>
                            <p class="text-xs text-red-800 font-bold mt-1"><?php echo htmlspecialchars($perfil['distrito']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php
                    $contactos = [
                        ['Celular',          htmlspecialchars($perfil['celular'] ?? '—')],
                        ['Correo Personal',  htmlspecialchars($perfil['correo_personal'] ?? '—')],
                        ['Correo Inst.',     htmlspecialchars($perfil['correo_institucional'] ?? '—')],
                    ];
                    foreach ($contactos as [$label, $val]): ?>
                        <div class="info-card">
                            <p class="info-label"><?php echo $label; ?></p>
                            <p class="info-value text-sm"><?php echo $val; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ─── TAB: DATOS LABORALES ─── -->
            <div id="tab-laboral" class="tab-panel hidden animate-in">
                <h3 class="section-title">Datos Laborales</h3>

                <!-- Resumen del contrato actual -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mt-6 mb-8">
                    <?php
                    $sit = $perfil['situacion'] ?? '';
                    $sitClass = strtoupper($sit) === 'ACTIVO' ? 'text-green-700' : 'text-red-700';
                    $laboral = [
                        ['Sueldo',       'S/ ' . number_format($perfil['sueldo'] ?? 0, 2), 'font-black text-red-900 text-lg'],
                        ['Situación',    htmlspecialchars($sit ?: '—'), 'font-black ' . $sitClass],
                        ['Contrato',     htmlspecialchars($perfil['mod_contrato'] ?? '—'), ''],
                        ['Tipo Puesto',  htmlspecialchars($perfil['tipo_puesto'] ?? '—'), ''],
                        ['Procedencia',  htmlspecialchars($perfil['procedencia'] ?? '—'), ''],
                        ['NSA / CIP',    htmlspecialchars($perfil['nsa_cip'] ?? '—'), ''],
                    ];
                    foreach ($laboral as [$label, $val, $extra]): ?>
                        <div class="info-card">
                            <p class="info-label"><?php echo $label; ?></p>
                            <p class="info-value <?php echo $extra; ?>"><?php echo $val; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Historial de contratos -->
                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                    Historial de Contratos
                    <span class="bg-slate-100 text-slate-500 px-2 py-0.5 rounded-md font-bold"><?php echo count($contratos); ?></span>
                </h4>
                <?php if (empty($contratos)): ?>
                    <p class="text-slate-400 text-sm py-4 text-center">Sin contratos registrados.</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($contratos as $i => $c): ?>
                            <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100 text-xs">
                                <div class="w-7 h-7 rounded-xl bg-red-900 text-white flex items-center justify-center font-black shrink-0 text-[11px]">
                                    <?php echo $i + 1; ?>
                                </div>
                                <div class="grid grid-cols-3 gap-4 flex-1">
                                    <div>
                                        <p class="text-slate-400 font-bold uppercase tracking-wider mb-0.5">Ingreso</p>
                                        <p class="font-black text-slate-700"><?php echo fmt($c['fecha_ingreso']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-slate-400 font-bold uppercase tracking-wider mb-0.5">Cese</p>
                                        <p class="font-black text-slate-700"><?php echo fmt($c['fecha_cese']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-slate-400 font-bold uppercase tracking-wider mb-0.5">Modalidad</p>
                                        <p class="font-black text-slate-700"><?php echo htmlspecialchars($c['modalidad_contrato'] ?? '—'); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ─── TAB: FAMILIA ─── -->
            <div id="tab-familia" class="tab-panel hidden animate-in">
                <h3 class="section-title">Carga Familiar</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6">
                    <!-- Cónyuge -->
                    <div class="bg-red-50 border border-red-100 rounded-2xl p-6">
                        <p class="info-label text-red-400">Cónyuge</p>
                        <p class="text-base font-black text-red-950 mt-1"><?php echo htmlspecialchars($perfil['conyuge'] ?: 'No registrado'); ?></p>
                        <?php if (!empty($perfil['onomastico_conyuge'])): ?>
                            <p class="text-xs text-red-500 font-bold mt-2">
                                Fecha nac.: <?php echo fmt($perfil['onomastico_conyuge']); ?>
                            </p>
                        <?php else: ?>
                            <p class="text-xs text-red-300 italic mt-2">Fecha de nacimiento no registrada</p>
                        <?php endif; ?>
                    </div>
                    <!-- Hijos -->
                    <div class="bg-slate-50 border border-slate-100 rounded-2xl p-6 flex items-center justify-between">
                        <div>
                            <p class="info-label">Hijos Registrados</p>
                            <p class="text-4xl font-black text-red-900 mt-1"><?php echo (int)($perfil['n_hijos'] ?? 0); ?></p>
                        </div>
                        <div class="w-14 h-14 rounded-2xl bg-red-100 flex items-center justify-center text-2xl">👨‍👩‍👧‍👦</div>
                    </div>
                </div>
            </div>

            <!-- ─── TAB: FORMACIÓN ─── -->
            <div id="tab-formacion" class="tab-panel hidden animate-in">
                <h3 class="section-title flex items-center gap-3">
                    Formación Académica
                    <?php if (!empty($formacion)): ?>
                        <span class="text-xs font-bold bg-red-50 text-red-800 border border-red-100 px-2 py-0.5 rounded-lg">
                            <?php echo count($formacion); ?> registro(s)
                        </span>
                    <?php endif; ?>
                </h3>

                <?php if (empty($formacion)): ?>
                    <div class="text-center py-16">
                        <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">🎓</div>
                        <p class="text-slate-500 text-sm">Sin formación registrada aún.</p>
                    </div>
                <?php else: ?>
                    <div class="mt-6 relative pl-7 border-l-2 border-red-100 space-y-7">
                        <?php foreach ($formacion as $item):
                            $tipo = strtoupper($item['tipo_grado'] ?? '');
                            [$dot, $badge] = match (true) {
                                str_contains($tipo, 'ESPECIALI') => ['bg-amber-500', 'bg-amber-50 text-amber-800 border-amber-200'],
                                str_contains($tipo, 'MAESTR')    => ['bg-purple-600', 'bg-purple-50 text-purple-800 border-purple-200'],
                                str_contains($tipo, 'DOCTOR')    => ['bg-slate-800', 'bg-slate-100 text-slate-800 border-slate-300'],
                                str_contains($tipo, 'BACHILLER') => ['bg-blue-600', 'bg-blue-50 text-blue-800 border-blue-200'],
                                str_contains($tipo, 'TECNI')     => ['bg-teal-600', 'bg-teal-50 text-teal-800 border-teal-200'],
                                default                         => ['bg-red-900', 'bg-red-50 text-red-900 border-red-200'],
                            };
                        ?>
                            <div class="relative">
                                <div class="absolute -left-[37px] top-1 w-4 h-4 rounded-full <?php echo $dot; ?> border-4 border-white shadow-sm"></div>
                                <?php if (!empty($item['tipo_grado'])): ?>
                                    <span class="inline-block text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded border mb-1.5 <?php echo $badge; ?>">
                                        <?php echo htmlspecialchars($item['tipo_grado']); ?>
                                    </span>
                                <?php endif; ?>
                                <h4 class="text-base font-bold text-slate-800"><?php echo htmlspecialchars($item['descripcion_carrera'] ?? 'No registrado'); ?></h4>
                                <p class="text-sm text-slate-500 italic"><?php echo htmlspecialchars($item['institucion'] ?? 'Institución no registrada'); ?></p>
                                <?php if (!empty($item['estado_validacion']) && $item['estado_validacion'] !== 'PENDIENTE'): ?>
                                    <span class="inline-block mt-1.5 text-[10px] font-bold uppercase px-2 py-0.5 rounded
                            <?php echo $item['estado_validacion'] === 'APROBADO' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'; ?>">
                                        <?php echo htmlspecialchars($item['estado_validacion']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div><!-- /bg-white tab container -->
    </div><!-- /max-w -->

</main>

<!-- ═══════════════════════════════════════════════════════════════
     MODAL: ACTUALIZAR INFORMACIÓN — FORMULARIO POR PASOS
═══════════════════════════════════════════════════════════════ -->
<div id="modal-perfil" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">

    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="cerrarModal()"></div>

    <!-- Panel -->
    <div class="absolute inset-y-0 right-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col">

        <!-- Header del modal -->
        <div class="flex items-center justify-between px-8 py-5 border-b border-slate-100 bg-gradient-to-r from-[#310404] to-red-900">
            <div>
                <h2 class="text-white font-black text-lg">Actualizar Mi Perfil</h2>
                <p class="text-red-200 text-xs mt-0.5">Paso <span id="paso-actual">1</span> de <span id="paso-total">4</span></p>
            </div>
            <button onclick="cerrarModal()" class="text-red-200 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Barra de progreso + steps -->
        <div class="px-8 pt-5 pb-0 border-b border-slate-100">
            <!-- Indicadores de paso -->
            <div class="flex items-center gap-0 mb-4">
                <?php
                $steps = [
                    ['num' => 1, 'label' => 'Personal'],
                    ['num' => 2, 'label' => 'Contacto'],
                    ['num' => 3, 'label' => 'Familia'],
                    ['num' => 4, 'label' => 'Confirmar'],
                ];
                $total = count($steps);
                foreach ($steps as $idx => $step):
                    $isLast = $idx === $total - 1;
                ?>
                    <div class="flex items-center <?php echo $isLast ? '' : 'flex-1'; ?>">
                        <div class="step-indicator flex flex-col items-center" data-step="<?php echo $step['num']; ?>">
                            <div class="step-circle w-8 h-8 rounded-full border-2 flex items-center justify-center text-xs font-black transition-all duration-300
                            <?php echo $step['num'] === 1 ? 'bg-red-900 border-red-900 text-white' : 'bg-white border-slate-200 text-slate-400'; ?>">
                                <span class="step-num"><?php echo $step['num']; ?></span>
                                <svg class="step-check w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <p class="text-[10px] font-bold mt-1 transition-colors
                            <?php echo $step['num'] === 1 ? 'text-red-900' : 'text-slate-400'; ?>"
                                id="step-label-<?php echo $step['num']; ?>">
                                <?php echo $step['label']; ?>
                            </p>
                        </div>
                        <?php if (!$isLast): ?>
                            <div class="step-line flex-1 h-0.5 mx-2 mb-4 rounded-full bg-slate-200 transition-all duration-500"
                                id="line-<?php echo $step['num']; ?>"></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Cuerpo scrollable del formulario -->
        <div class="flex-1 overflow-y-auto px-8 py-6">

            <!-- ── PASO 1: Datos Personales ── -->
            <div id="form-step-1" class="form-step space-y-4">
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-4">Información Personal</p>
                <div class="grid grid-cols-2 gap-4">
                    <div class="field-group col-span-2">
                        <label class="field-label">Nombres y Apellidos</label>
                        <input type="text" name="nombres_apellidos" class="field-input"
                            value="<?php echo htmlspecialchars($perfil['nombres_apellidos'] ?? ''); ?>" readonly
                            title="Este campo solo puede ser modificado por RRHH">
                        <p class="text-[10px] text-slate-400 mt-1">Solo RRHH puede modificar el nombre.</p>
                    </div>
                    <div class="field-group">
                        <label class="field-label">DNI</label>
                        <input type="text" name="dni" class="field-input" maxlength="8"
                            value="<?php echo htmlspecialchars($perfil['dni'] ?? ''); ?>" readonly>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Fecha de Nacimiento</label>
                        <input type="date" name="fecha_nacimiento" class="field-input"
                            value="<?php echo htmlspecialchars($perfil['fecha_nacimiento'] ?? ''); ?>">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Lugar de Nacimiento</label>
                        <input type="text" name="lugar_nacimiento" class="field-input"
                            value="<?php echo htmlspecialchars($perfil['lugar_nacimiento'] ?? ''); ?>">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Estado Civil</label>
                        <select name="estado_civil" class="field-input">
                            <?php foreach (['Soltero/a', 'Casado/a', 'Divorciado/a', 'Viudo/a', 'Conviviente'] as $opt):
                                $sel = ($perfil['estado_civil'] ?? '') === $opt ? 'selected' : ''; ?>
                                <option value="<?php echo $opt; ?>" <?php echo $sel; ?>><?php echo $opt; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Grupo Sanguíneo</label>
                        <select name="grupo_sanguineo" class="field-input">
                            <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $g):
                                $sel = ($perfil['grupo_sanguineo'] ?? '') === $g ? 'selected' : ''; ?>
                                <option value="<?php echo $g; ?>" <?php echo $sel; ?>><?php echo $g; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Talla</label>
                        <input type="text" name="talla" class="field-input" placeholder="Ej: 1.70"
                            value="<?php echo htmlspecialchars($perfil['talla'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- ── PASO 2: Contacto ── -->
            <div id="form-step-2" class="form-step space-y-4 hidden">
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-4">Contacto y Domicilio</p>
                <div class="grid grid-cols-2 gap-4">
                    <div class="field-group col-span-2">
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
                    <div class="field-group col-span-2">
                        <label class="field-label">Correo Personal</label>
                        <input type="email" name="correo_personal" class="field-input"
                            value="<?php echo htmlspecialchars($perfil['correo_personal'] ?? ''); ?>">
                    </div>
                    <div class="field-group col-span-2">
                        <label class="field-label">Correo Institucional</label>
                        <input type="email" name="correo_institucional" class="field-input"
                            value="<?php echo htmlspecialchars($perfil['correo_institucional'] ?? ''); ?>" readonly>
                        <p class="text-[10px] text-slate-400 mt-1">Gestionado por RRHH.</p>
                    </div>
                </div>
            </div>

            <!-- ── PASO 3: Familia ── -->
            <div id="form-step-3" class="form-step space-y-5 hidden">
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Carga Familiar</p>

                <!-- Cónyuge -->
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 space-y-4">
                    <p class="text-[11px] font-black text-slate-500 uppercase tracking-widest">Cónyuge</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="field-group col-span-2">
                            <label class="field-label">Nombre Completo</label>
                            <input type="text" name="conyuge" class="field-input"
                                value="<?php echo htmlspecialchars($perfil['conyuge'] ?? ''); ?>"
                                placeholder="Dejar vacío si no aplica">
                        </div>
                        <div class="field-group">
                            <label class="field-label">Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nac_conyuge" class="field-input"
                                value="<?php echo htmlspecialchars($perfil['onomastico_conyuge'] ?? ''); ?>">
                        </div>
                        <div class="field-group">
                            <label class="field-label">DNI Cónyuge</label>
                            <input type="text" name="dni_conyuge" class="field-input" maxlength="8"
                                placeholder="Opcional">
                        </div>
                    </div>
                </div>

                <!-- Hijos -->
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-[11px] font-black text-slate-500 uppercase tracking-widest">Hijos</p>
                        <button type="button" onclick="agregarHijo()"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-red-900 bg-red-50 hover:bg-red-100 border border-red-200 px-3 py-1.5 rounded-xl transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Agregar hijo
                        </button>
                    </div>

                    <!-- Lista dinámica de hijos -->
                    <div id="lista-hijos" class="space-y-3">
                        <?php
                        // Cargar hijos existentes desde BD
                        $hijos = array_filter($perfil['familia'] ?? [], fn($f) => in_array($f['parentesco'], ['HIJO', 'HIJA']));
                        if (empty($hijos)): ?>
                            <div id="sin-hijos" class="text-center py-5 text-slate-400 text-xs">
                                No hay hijos registrados. Haz clic en "Agregar hijo" para añadir.
                            </div>
                            <?php else:
                            foreach (array_values($hijos) as $hi => $hijo): ?>
                                <div class="hijo-row bg-white border border-slate-200 rounded-xl p-4 relative" data-index="<?php echo $hi; ?>">
                                    <button type="button" onclick="eliminarHijo(this)"
                                        class="absolute top-3 right-3 text-slate-300 hover:text-red-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <div class="grid grid-cols-2 gap-3 pr-6">
                                        <div class="field-group col-span-2">
                                            <label class="field-label">Nombre Completo</label>
                                            <input type="text" name="hijos[<?php echo $hi; ?>][nombre]" class="field-input"
                                                value="<?php echo htmlspecialchars($hijo['nombre_completo'] ?? ''); ?>">
                                        </div>
                                        <div class="field-group">
                                            <label class="field-label">Parentesco</label>
                                            <select name="hijos[<?php echo $hi; ?>][parentesco]" class="field-input">
                                                <option value="HIJO" <?php echo ($hijo['parentesco'] === 'HIJO')  ? 'selected' : ''; ?>>Hijo</option>
                                                <option value="HIJA" <?php echo ($hijo['parentesco'] === 'HIJA')  ? 'selected' : ''; ?>>Hija</option>
                                            </select>
                                        </div>
                                        <div class="field-group">
                                            <label class="field-label">Fecha de Nacimiento</label>
                                            <input type="date" name="hijos[<?php echo $hi; ?>][fecha_nacimiento]" class="field-input"
                                                value="<?php echo htmlspecialchars($hijo['fecha_nacimiento'] ?? ''); ?>">
                                        </div>
                                        <div class="field-group">
                                            <label class="field-label">DNI</label>
                                            <input type="text" name="hijos[<?php echo $hi; ?>][dni]" class="field-input" maxlength="8"
                                                value="<?php echo htmlspecialchars($hijo['dni_familiar'] ?? ''); ?>">
                                        </div>
                                        <input type="hidden" name="hijos[<?php echo $hi; ?>][id]" value="<?php echo $hijo['id'] ?? ''; ?>">
                                    </div>
                                </div>
                        <?php endforeach;
                        endif; ?>
                    </div>
                </div>
            </div>

            <!-- ── PASO 4: Confirmación ── -->
            <div id="form-step-4" class="form-step hidden">
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-6">Resumen de Cambios</p>

                <div class="bg-green-50 border border-green-200 rounded-2xl p-5 mb-6 flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-black text-green-800">Todo listo para guardar</p>
                        <p class="text-xs text-green-700 mt-0.5">
                            Revisa el resumen a continuación. Al confirmar, los cambios se guardarán en el sistema.
                        </p>
                    </div>
                </div>

                <div id="resumen-cambios" class="space-y-2 text-sm">
                    <!-- Se rellena dinámicamente con JS -->
                </div>
            </div>

        </div><!-- /overflow-y-auto -->

        <!-- Footer con navegación del wizard -->
        <div class="px-8 py-5 border-t border-slate-100 flex items-center justify-between bg-slate-50">
            <button id="btn-anterior" onclick="pasoAnterior()"
                class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-bold hover:bg-slate-100 transition-all hidden">
                ← Anterior
            </button>
            <div class="flex-1"></div>
            <button id="btn-siguiente" onclick="pasoSiguiente()"
                class="px-6 py-2.5 rounded-xl bg-red-900 text-white text-sm font-bold hover:bg-[#310404] transition-all shadow-md shadow-red-900/20 active:scale-95">
                Siguiente →
            </button>
            <button id="btn-guardar" onclick="guardarPerfil()"
                class="px-6 py-2.5 rounded-xl bg-green-600 text-white text-sm font-bold hover:bg-green-700 transition-all shadow-md hidden active:scale-95">
                ✓ Guardar Cambios
            </button>
        </div>

    </div><!-- /panel -->
</div><!-- /modal -->

<!-- Toast de confirmación -->
<div id="toast" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[60] hidden">
    <div class="bg-slate-800 text-white px-6 py-3 rounded-2xl shadow-2xl text-sm font-bold flex items-center gap-2">
        <span id="toast-icon">✓</span>
        <span id="toast-msg">Cambios guardados correctamente</span>
    </div>
</div>

<!-- ═══════════════════════════════════════════════
     ESTILOS
═══════════════════════════════════════════════ -->
<style>
    /* Tabs */
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

    /* Animación entrada */
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

    /* Cards de información */
    .info-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 16px 20px;
    }

    .info-label {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .1em;
        color: #94a3b8;
        margin-bottom: 4px;
    }

    .info-value {
        font-weight: 700;
        color: #1e293b;
    }

    /* Section title */
    .section-title {
        font-size: 1rem;
        font-weight: 900;
        color: #1e293b;
        letter-spacing: -.01em;
    }

    /* Form fields */
    .field-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .field-label {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    .field-input {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        background: #f8fafc;
        transition: border-color .2s, background .2s;
        outline: none;
    }

    .field-input:focus {
        border-color: #7f1d1d;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(127, 29, 29, .08);
    }

    .field-input[readonly] {
        background: #f1f5f9;
        color: #94a3b8;
        cursor: not-allowed;
    }

    .field-input::placeholder {
        color: #cbd5e1;
    }

    /* Scrollbar */
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    /* Modal deslizante */
    #modal-perfil .absolute.inset-y-0 {
        transition: transform .35s cubic-bezier(.4, 0, .2, 1);
        transform: translateX(100%);
    }

    #modal-perfil.modal-open .absolute.inset-y-0 {
        transform: translateX(0);
    }

    #modal-perfil .absolute.inset-0 {
        transition: opacity .35s;
        opacity: 0;
    }

    #modal-perfil.modal-open .absolute.inset-0 {
        opacity: 1;
    }

    /* Resumen paso 4 */
    .resumen-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 14px;
        background: #f8fafc;
        border-radius: 12px;
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
</style>

<!-- ═══════════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════════ -->
<script>
    // ── HIJOS DINÁMICOS ───────────────────────────────
    let hijoIdx = <?php echo max(count(array_filter($perfil['familia'] ?? [], fn($f) => in_array($f['parentesco'], ['HIJO', 'HIJA']))), 0); ?>;

    function agregarHijo() {
        const sinHijos = document.getElementById('sin-hijos');
        if (sinHijos) sinHijos.remove();

        const idx = hijoIdx++;
        const html = `
    <div class="hijo-row bg-white border border-slate-200 rounded-xl p-4 relative animate-in" data-index="${idx}">
        <button type="button" onclick="eliminarHijo(this)"
            class="absolute top-3 right-3 text-slate-300 hover:text-red-500 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        <div class="grid grid-cols-2 gap-3 pr-6">
            <div class="field-group col-span-2">
                <label class="field-label">Nombre Completo</label>
                <input type="text" name="hijos[${idx}][nombre]" class="field-input" placeholder="Nombre y apellidos">
            </div>
            <div class="field-group">
                <label class="field-label">Parentesco</label>
                <select name="hijos[${idx}][parentesco]" class="field-input">
                    <option value="HIJO">Hijo</option>
                    <option value="HIJA">Hija</option>
                </select>
            </div>
            <div class="field-group">
                <label class="field-label">Fecha de Nacimiento</label>
                <input type="date" name="hijos[${idx}][fecha_nacimiento]" class="field-input">
            </div>
            <div class="field-group">
                <label class="field-label">DNI</label>
                <input type="text" name="hijos[${idx}][dni]" class="field-input" maxlength="8" placeholder="Opcional">
            </div>
            <input type="hidden" name="hijos[${idx}][id]" value="">
        </div>
    </div>`;
        document.getElementById('lista-hijos').insertAdjacentHTML('beforeend', html);
    }

    function eliminarHijo(btn) {
        const row = btn.closest('.hijo-row');
        row.style.opacity = '0';
        row.style.transform = 'translateY(-6px)';
        row.style.transition = 'all .2s ease';
        setTimeout(() => {
            row.remove();
            const lista = document.getElementById('lista-hijos');
            if (!lista.querySelector('.hijo-row')) {
                lista.innerHTML = '<div id="sin-hijos" class="text-center py-5 text-slate-400 text-xs">No hay hijos registrados. Haz clic en "Agregar hijo" para añadir.</div>';
            }
        }, 200);
    }

    // ── TABS PRINCIPALES ──────────────────────────────
    function switchTab(id) {
        document.querySelectorAll('.tab-panel').forEach(p => {
            p.classList.add('hidden');
            p.classList.remove('block');
        });
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('tab-active');
            b.classList.add('tab-idle');
        });
        const panel = document.getElementById('tab-' + id);
        panel.classList.remove('hidden');
        panel.classList.add('block');
        panel.classList.remove('animate-in');
        void panel.offsetWidth;
        panel.classList.add('animate-in');

        const btn = document.getElementById('btn-' + id);
        btn.classList.add('tab-active');
        btn.classList.remove('tab-idle');
    }

    // ── MODAL ─────────────────────────────────────────
    const valoresOriginales = {};

    function abrirModal() {
        const m = document.getElementById('modal-perfil');
        m.classList.remove('hidden');
        requestAnimationFrame(() => m.classList.add('modal-open'));

        // Capturar valores originales ANTES de que el usuario edite
        document.querySelectorAll('.form-step [name]').forEach(el => {
            if (!el.readOnly) {
                valoresOriginales[el.name] = el.value;
            }
        });

        irPaso(1);
    }

    function cerrarModal() {
        const m = document.getElementById('modal-perfil');
        m.classList.remove('modal-open');
        setTimeout(() => m.classList.add('hidden'), 350);
    }

    // ── WIZARD DE PASOS ───────────────────────────────
    let pasoActual = 1;
    const totalPasos = 4;

    function irPaso(n) {
        document.querySelectorAll('.form-step').forEach(s => s.classList.add('hidden'));
        document.getElementById('form-step-' + n).classList.remove('hidden');

        for (let i = 1; i <= totalPasos; i++) {
            const circle = document.querySelector(`.step-indicator[data-step="${i}"] .step-circle`);
            const numEl = document.querySelector(`.step-indicator[data-step="${i}"] .step-num`);
            const chkEl = document.querySelector(`.step-indicator[data-step="${i}"] .step-check`);
            const label = document.getElementById('step-label-' + i);
            const line = document.getElementById('line-' + i);

            circle.classList.remove('bg-red-900', 'border-red-900', 'text-white', 'bg-green-500', 'border-green-500', 'bg-white', 'border-slate-200', 'text-slate-400');
            label && label.classList.remove('text-red-900', 'text-green-600', 'text-slate-400');

            if (i < n) {
                circle.classList.add('bg-green-500', 'border-green-500', 'text-white');
                numEl && numEl.classList.add('hidden');
                chkEl && chkEl.classList.remove('hidden');
                label && label.classList.add('text-green-600');
                if (line) line.style.background = '#22c55e';
            } else if (i === n) {
                circle.classList.add('bg-red-900', 'border-red-900', 'text-white');
                numEl && numEl.classList.remove('hidden');
                chkEl && chkEl.classList.add('hidden');
                label && label.classList.add('text-red-900');
                if (line) line.style.background = '#e2e8f0';
            } else {
                circle.classList.add('bg-white', 'border-slate-200', 'text-slate-400');
                numEl && numEl.classList.remove('hidden');
                chkEl && chkEl.classList.add('hidden');
                label && label.classList.add('text-slate-400');
                if (line) line.style.background = '#e2e8f0';
            }
        }

        document.getElementById('paso-actual').textContent = n;
        document.getElementById('paso-total').textContent = totalPasos;

        const btnAnt = document.getElementById('btn-anterior');
        const btnSig = document.getElementById('btn-siguiente');
        const btnGuar = document.getElementById('btn-guardar');

        btnAnt.classList.toggle('hidden', n === 1);
        btnSig.classList.toggle('hidden', n === totalPasos);
        btnGuar.classList.toggle('hidden', n !== totalPasos);

        if (n === totalPasos) construirResumen();

        pasoActual = n;
    }

    function pasoSiguiente() {
        if (pasoActual < totalPasos) irPaso(pasoActual + 1);
    }

    function pasoAnterior() {
        if (pasoActual > 1) irPaso(pasoActual - 1);
    }

    // ── RESUMEN EN PASO 4 ─────────────────────────────
    const labelesCampos = {
        fecha_nacimiento: 'Fecha de Nacimiento',
        lugar_nacimiento: 'Lugar de Nacimiento',
        estado_civil: 'Estado Civil',
        grupo_sanguineo: 'Grupo Sanguíneo',
        talla: 'Talla',
        direccion_residencia: 'Dirección',
        distrito: 'Distrito',
        celular: 'Celular',
        correo_personal: 'Correo Personal',
        conyuge: 'Cónyuge',
        fecha_nac_conyuge: 'Fecha Nac. Cónyuge',
    };

    function construirResumen() {
        const container = document.getElementById('resumen-cambios');
        const cambios = [];

        document.querySelectorAll('.form-step [name]').forEach(el => {
            if (el.readOnly) return;
            if (!labelesCampos[el.name]) return;

            const original = valoresOriginales[el.name] ?? '';
            const actual = el.value ?? '';

            if (original !== actual) {
                cambios.push(`
                <div class="resumen-item">
                    <span class="r-label">${labelesCampos[el.name]}</span>
                    <div class="text-right">
                        <p class="text-[11px] text-slate-400 line-through">${original || '—'}</p>
                        <p class="r-val text-green-700">${actual || '—'}</p>
                    </div>
                </div>`);
            }
        });

        // Hijos nuevos o modificados
        document.querySelectorAll('.hijo-row').forEach((row, i) => {
            const nombre = row.querySelector('[name*="nombre"]')?.value ?? '';
            if (nombre) {
                cambios.push(`
                <div class="resumen-item">
                    <span class="r-label">Hijo ${i + 1}</span>
                    <span class="r-val">${nombre}</span>
                </div>`);
            }
        });

        if (cambios.length === 0) {
            container.innerHTML = `
            <div class="text-center py-8 text-slate-400 text-sm">
                No realizaste ningún cambio.
            </div>`;
        } else {
            container.innerHTML = `
            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-3">
                ${cambios.length} campo(s) modificado(s)
            </p>
            ${cambios.join('')}`;
        }
    }

    // ── GUARDAR VÍA AJAX ──────────────────────────────
    function guardarPerfil() {
        const btn = document.getElementById('btn-guardar');
        btn.textContent = 'Guardando…';
        btn.disabled = true;

        const campos = {};
        document.querySelectorAll('.form-step [name]').forEach(el => {
            if (!el.readOnly) campos[el.name] = el.value;
        });
        campos['id'] = <?php echo (int)($perfil['id'] ?? 0); ?>;

        // Capturar datos de hijos
        const hijos = [];
        document.querySelectorAll('.hijo-row').forEach(row => {
            const idx = row.dataset.index;
            hijos.push({
                id: row.querySelector(`[name="hijos[${idx}][id]"]`)?.value || '',
                nombre: row.querySelector(`[name="hijos[${idx}][nombre]"]`)?.value || '',
                parentesco: row.querySelector(`[name="hijos[${idx}][parentesco]"]`)?.value || 'HIJO',
                fecha_nacimiento: row.querySelector(`[name="hijos[${idx}][fecha_nacimiento]"]`)?.value || '',
                dni: row.querySelector(`[name="hijos[${idx}][dni]"]`)?.value || ''
            });
        });
        campos['hijos'] = hijos;

        fetch('<?php echo BASE_URL; ?>/perfil/actualizar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(campos)
            })
            .then(r => r.text()) // Leemos como texto primero para limpiar
            .then(raw => {
                const cleanJson = raw.trim(); // <--- ESTO ELIMINA EL ERROR DE CARACTERES
                const res = JSON.parse(cleanJson);

                if (res.success) {
                    cerrarModal();
                    mostrarToast('✓', 'Perfil actualizado correctamente', 'bg-slate-800');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    mostrarToast('✗', res.mensaje || 'Error al guardar', 'bg-red-800');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                mostrarToast('✗', 'Error de conexión', 'bg-red-800');
            })
            .finally(() => {
                btn.textContent = '✓ Guardar Cambios';
                btn.disabled = false;
            });
    }

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

    // Cerrar modal con ESC
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') cerrarModal();
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>