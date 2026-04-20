<?php
//Vista/modulos/rrhh/perfil_detalle.php
if (!defined('ROOT_PATH')) {
    require_once __DIR__ . '/../../../Config/config.php';
}

require_once ROOT_PATH . 'Controlador/CtrDirectorio.php';
require_once ROOT_PATH . 'Modelo/MdDirectorio.php';

$url_params = explode('/', trim($_GET['url'], '/'));
$id_colaborador = $url_params[2] ?? null;

if (!$id_colaborador) {
    header("Location: " . BASE_URL . "/rrhh/directorio");
    exit;
}

$controlador = new CtrDirectorio();
$data = $controlador->ctrVerPerfil($id_colaborador);

if (!$data) {
    echo "<div style='padding:50px; text-align:center;'><h1>404 - No encontrado</h1></div>";
    exit;
}

// ──────────────────────────────────────────────────────────────
// HELPERS PHP
// ──────────────────────────────────────────────────────────────

/**
 * Calcula la edad exacta a partir de una fecha de nacimiento (Y-m-d).
 * Si el campo está vacío devuelve '—'.
 */
function calcularEdad(?string $fechaNac): string
{
    if (empty($fechaNac)) return '—';
    try {
        $nac   = new DateTime($fechaNac);
        $hoy   = new DateTime();
        $diff  = $hoy->diff($nac);
        return $diff->y . ' años';
    } catch (Exception $e) {
        return '—';
    }
}

/**
 * Formatea una fecha Y-m-d a d/m/Y para mostrar.
 */
function formatFecha(?string $fecha): string
{
    if (empty($fecha)) return '—';
    try {
        return (new DateTime($fecha))->format('d/m/Y');
    } catch (Exception $e) {
        return $fecha;
    }
}

$titulo_pagina = "Perfil: " . $data['nombres_apellidos'];
require_once ROOT_PATH . 'Vista/includes/header.php';
require_once ROOT_PATH . 'Vista/includes/sidebar.php';

// Arrays que vienen del modelo
$contratos = $data['contratos'] ?? [];   // múltiples registros de colab_laboral
$formacion = $data['formacion'] ?? [];   // múltiples registros de colab_formacion
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

                    <!-- Avatar inicial -->
                    <div class="h-36 w-36 rounded-3xl bg-red-900 flex items-center justify-center text-5xl font-black text-white shadow-2xl ring-8 ring-white shrink-0">
                        <?php echo mb_substr($data['nombres_apellidos'], 0, 1); ?>
                    </div>

                    <!-- Nombre y badges -->
                    <div class="flex-1 text-center md:text-left">
                        <h1 class="text-3xl font-black text-slate-800 tracking-tight mb-2">
                            <?php echo htmlspecialchars($data['nombres_apellidos']); ?>
                        </h1>
                        <div class="flex flex-wrap justify-center md:justify-start gap-2">
                            <span class="bg-red-50 text-red-900 px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-widest border border-red-100">
                                <?php echo htmlspecialchars($data['puesto_cas'] ?? 'Sin puesto'); ?>
                            </span>
                            <span class="bg-slate-50 text-slate-500 px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-widest border border-slate-200">
                                <?php echo htmlspecialchars($data['area'] ?? 'Sin área'); ?>
                            </span>
                            <?php if (!empty($data['situacion'])): ?>
                            <span class="bg-green-50 text-green-700 px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-widest border border-green-100">
                                <?php echo htmlspecialchars($data['situacion']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Botón Editar -->
                    <div class="flex gap-2">
                        <button class="bg-red-900 text-white px-6 py-3 rounded-2xl font-bold hover:bg-[#310404] transition-all shadow-lg shadow-red-900/20 active:scale-95">
                            Editar Perfil
                        </button>
                    </div>
                </div>

                <!-- Tabs de navegación -->
                <div class="flex items-center gap-8 mt-10 border-t border-slate-100 pt-2 overflow-x-auto no-scrollbar">
                    <button onclick="showTab('resumen')" class="tab-btn active-tab px-2 py-4 text-sm font-bold transition-all relative whitespace-nowrap" id="btn-resumen">
                        RESUMEN PERFIL
                    </button>
                    <button onclick="showTab('formacion')" class="tab-btn inactive-tab px-2 py-4 text-sm font-bold transition-all relative whitespace-nowrap" id="btn-formacion">
                        FORMACIÓN ACADÉMICA
                    </button>
                    <button onclick="showTab('experiencia')" class="tab-btn inactive-tab px-2 py-4 text-sm font-bold transition-all relative whitespace-nowrap" id="btn-experiencia">
                        EXPERIENCIA LABORAL
                    </button>
                </div>
            </div>

            <!-- ============================================================
                 TAB 1: RESUMEN PERFIL
            ============================================================ -->
            <div id="tab-resumen" class="tab-content block animate-fadeIn">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                    <!-- Columna principal (2/3) -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- Datos Personales -->
                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
                            <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                <span class="w-1.5 h-5 bg-red-800 rounded-full"></span>
                                Datos Personales
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4 text-sm">
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">DNI</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['dni'] ?? '—'); ?></span>
                                    </div>
                                    <!-- CORRECCIÓN 1: Edad calculada desde fecha_nacimiento -->
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Edad</span>
                                        <span class="font-bold text-slate-700"><?php echo calcularEdad($data['fecha_nacimiento'] ?? null); ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Fecha Nac.</span>
                                        <span class="font-bold text-slate-700"><?php echo formatFecha($data['fecha_nacimiento'] ?? null); ?></span>
                                    </div>
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Lugar Nac.</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['lugar_nacimiento'] ?? '—'); ?></span>
                                    </div>
                                </div>
                                <div class="space-y-4 text-sm">
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
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400 font-medium">Talla</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['talla'] ?? '—'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contacto y Domicilio -->
                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
                            <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                <span class="w-1.5 h-5 bg-slate-800 rounded-full"></span>
                                Contacto y Domicilio
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="p-5 bg-slate-50 rounded-2xl border border-slate-100">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Dirección</p>
                                    <p class="font-bold text-slate-700 leading-tight mb-1">
                                        <?php echo htmlspecialchars($data['direccion_residencia'] ?? 'No registrada'); ?>
                                    </p>
                                    <p class="text-xs text-red-800 font-bold">
                                        <?php echo htmlspecialchars($data['distrito'] ?? ''); ?>
                                    </p>
                                </div>
                                <div class="space-y-2 text-sm">
                                    <div class="p-3 bg-slate-50 rounded-xl flex justify-between items-center border border-slate-100">
                                        <span class="text-[10px] font-bold text-slate-400">CELULAR</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['celular'] ?? '—'); ?></span>
                                    </div>
                                    <div class="p-3 bg-slate-50 rounded-xl flex justify-between items-center border border-slate-100">
                                        <span class="text-[10px] font-bold text-slate-400">CORREO PERS.</span>
                                        <span class="font-bold text-slate-600 text-[11px]"><?php echo htmlspecialchars($data['correo_personal'] ?? '—'); ?></span>
                                    </div>
                                    <div class="p-3 bg-slate-50 rounded-xl flex justify-between items-center border border-slate-100">
                                        <span class="text-[10px] font-bold text-slate-400">CORREO INST.</span>
                                        <span class="font-bold text-red-900 text-[11px]"><?php echo htmlspecialchars($data['correo_institucional'] ?? '—'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CORRECCIÓN 5: Historial de Contratos / Fechas de Ingreso (múltiples) -->
                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
                            <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                <span class="w-1.5 h-5 bg-slate-800 rounded-full"></span>
                                Historial de Contratos
                                <span class="ml-auto bg-slate-100 text-slate-500 text-xs font-bold px-2 py-1 rounded-lg">
                                    <?php echo count($contratos); ?> registro(s)
                                </span>
                            </h3>

                            <?php if (empty($contratos)): ?>
                                <p class="text-slate-400 text-sm text-center py-6">No hay contratos registrados.</p>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($contratos as $i => $contrato): ?>
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-4 rounded-2xl border border-slate-100 bg-slate-50">
                                        <!-- Número de contrato -->
                                        <div class="w-8 h-8 rounded-xl bg-red-900 text-white flex items-center justify-center text-xs font-black shrink-0">
                                            <?php echo $i + 1; ?>
                                        </div>
                                        <!-- Fechas -->
                                        <div class="flex-1 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                            <div>
                                                <p class="text-slate-400 font-bold uppercase tracking-wider mb-0.5">Ingreso</p>
                                                <p class="font-black text-slate-700"><?php echo formatFecha($contrato['fecha_ingreso']); ?></p>
                                            </div>
                                            <div>
                                                <p class="text-slate-400 font-bold uppercase tracking-wider mb-0.5">Cese</p>
                                                <p class="font-black text-slate-700"><?php echo formatFecha($contrato['fecha_cese']); ?></p>
                                            </div>
                                            <div>
                                                <p class="text-slate-400 font-bold uppercase tracking-wider mb-0.5">Modalidad</p>
                                                <p class="font-black text-slate-700"><?php echo htmlspecialchars($contrato['modalidad_contrato'] ?? '—'); ?></p>
                                            </div>
                                            <div>
                                                <p class="text-slate-400 font-bold uppercase tracking-wider mb-0.5">Estado</p>
                                                <?php
                                                    $sit = $contrato['situacion'] ?? '';
                                                    $sitColor = match(strtoupper($sit)) {
                                                        'ACTIVO'   => 'text-green-700',
                                                        'INACTIVO' => 'text-red-600',
                                                        default    => 'text-slate-500',
                                                    };
                                                ?>
                                                <p class="font-black <?php echo $sitColor; ?>"><?php echo htmlspecialchars($sit ?: '—'); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div><!-- /columna principal -->

                    <!-- Columna lateral (1/3) -->
                    <div class="space-y-6">

                        <!-- CORRECCIÓN 2 y 3: Familia con n_hijos y fecha nacimiento cónyuge -->
                        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                            <h3 class="text-md font-black text-slate-800 mb-4 flex items-center gap-2">
                                <span class="w-1.5 h-4 bg-red-600 rounded-full"></span>
                                Familia
                            </h3>

                            <!-- Cónyuge -->
                            <div class="p-4 bg-red-50 rounded-2xl mb-3 border border-red-100">
                                <p class="text-[10px] font-bold text-red-400 uppercase tracking-tighter mb-1">Cónyuge</p>
                                <p class="font-black text-red-950 mb-1">
                                    <?php echo htmlspecialchars($data['conyuge'] ?: 'No registrado'); ?>
                                </p>
                                <!-- CORRECCIÓN 3: Fecha de nacimiento cónyuge -->
                                <?php if (!empty($data['onomastico_conyuge'])): ?>
                                <div class="flex items-center gap-1 mt-1">
                                    <span class="text-[10px] text-red-400 font-bold uppercase tracking-tighter">Fecha nac.:</span>
                                    <span class="text-[11px] text-red-700 font-bold"><?php echo formatFecha($data['onomastico_conyuge']); ?></span>
                                </div>
                                <?php else: ?>
                                <p class="text-[10px] text-red-300 italic mt-1">Fecha de nacimiento no registrada</p>
                                <?php endif; ?>
                            </div>

                            <!-- CORRECCIÓN 2: Número de hijos (COUNT real desde BD) -->
                            <div class="flex justify-between items-center p-4 bg-slate-50 rounded-2xl text-sm font-bold border border-slate-100">
                                <span class="text-slate-500">Hijos registrados</span>
                                <span class="text-red-900 text-xl font-black"><?php echo (int)($data['n_hijos'] ?? 0); ?></span>
                            </div>
                        </div>

                        <!-- Datos Laborales (resumen del contrato más reciente) -->
                        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                            <h3 class="text-md font-black text-slate-800 mb-4 flex items-center gap-2">
                                <span class="w-1.5 h-4 bg-slate-800 rounded-full"></span>
                                Datos Laborales
                            </h3>
                            <div class="space-y-4 text-sm">
                                <div class="flex justify-between items-center bg-slate-50 p-3 rounded-xl border border-slate-100">
                                    <span class="text-slate-400 font-medium">Sueldo</span>
                                    <span class="font-black text-red-900 text-base">
                                        S/ <?php echo !empty($data['sueldo']) ? number_format($data['sueldo'], 2) : '0.00'; ?>
                                    </span>
                                </div>
                                <div class="flex justify-between px-2">
                                    <span class="text-slate-400">Contrato</span>
                                    <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['mod_contrato'] ?? '—'); ?></span>
                                </div>
                                <div class="flex justify-between px-2">
                                    <span class="text-slate-400">Tipo Puesto</span>
                                    <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['tipo_puesto'] ?? '—'); ?></span>
                                </div>
                                <div class="flex justify-between px-2">
                                    <span class="text-slate-400">Procedencia</span>
                                    <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['procedencia'] ?? '—'); ?></span>
                                </div>
                                <div class="flex justify-between px-2">
                                    <span class="text-slate-400">NSA / CIP</span>
                                    <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['nsa_cip'] ?? '—'); ?></span>
                                </div>
                            </div>
                        </div>

                    </div><!-- /columna lateral -->
                </div>
            </div>

            <!-- ============================================================
                 TAB 2: FORMACIÓN ACADÉMICA
                 CORRECCIÓN 4: Muestra TODOS los registros de colab_formacion
                 (grado, especialización, otros) en timeline
            ============================================================ -->
            <div id="tab-formacion" class="tab-content hidden animate-fadeIn">
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
                    <h3 class="text-xl font-black text-slate-800 mb-8 flex items-center gap-2">
                        <span class="w-1.5 h-6 bg-red-900 rounded-full"></span>
                        Historial Académico
                        <?php if (!empty($formacion)): ?>
                        <span class="ml-auto bg-red-50 text-red-800 text-xs font-bold px-2 py-1 rounded-lg border border-red-100">
                            <?php echo count($formacion); ?> registro(s)
                        </span>
                        <?php endif; ?>
                    </h3>

                    <?php if (empty($formacion)): ?>
                        <!-- Estado vacío -->
                        <div class="text-center py-16">
                            <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl">🎓</div>
                            <h4 class="text-lg font-black text-slate-700 mb-2">Sin formación registrada</h4>
                            <p class="text-slate-400 max-w-sm mx-auto text-sm">
                                Aún no se ha registrado información académica para este colaborador.
                            </p>
                        </div>
                    <?php else: ?>
                        <!-- Timeline de formación: un ítem por cada fila de colab_formacion -->
                        <div class="relative pl-8 border-l-2 border-red-100 space-y-8">
                            <?php foreach ($formacion as $idx => $item):
                                // Colores del punto según tipo de grado
                                $tipo = strtoupper($item['tipo_grado'] ?? '');
                                $dotColor = match(true) {
                                    str_contains($tipo, 'ESPECIALI') => 'bg-amber-500',
                                    str_contains($tipo, 'MAESTR')    => 'bg-purple-700',
                                    str_contains($tipo, 'DOCTOR')    => 'bg-slate-800',
                                    str_contains($tipo, 'BACHILLER') => 'bg-blue-600',
                                    str_contains($tipo, 'TECNI')     => 'bg-teal-600',
                                    default                          => 'bg-red-900',
                                };
                                $badgeColor = match(true) {
                                    str_contains($tipo, 'ESPECIALI') => 'bg-amber-50 text-amber-800 border-amber-100',
                                    str_contains($tipo, 'MAESTR')    => 'bg-purple-50 text-purple-800 border-purple-100',
                                    str_contains($tipo, 'DOCTOR')    => 'bg-slate-100 text-slate-800 border-slate-200',
                                    str_contains($tipo, 'BACHILLER') => 'bg-blue-50 text-blue-800 border-blue-100',
                                    str_contains($tipo, 'TECNI')     => 'bg-teal-50 text-teal-800 border-teal-100',
                                    default                          => 'bg-red-50 text-red-900 border-red-100',
                                };
                            ?>
                            <div class="relative">
                                <!-- Punto en la línea de tiempo -->
                                <div class="absolute -left-[41px] top-1 w-5 h-5 rounded-full <?php echo $dotColor; ?> border-4 border-white shadow-sm"></div>

                                <!-- Badge de tipo de grado -->
                                <?php if (!empty($item['tipo_grado'])): ?>
                                <span class="inline-block text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded-md border mb-2 <?php echo $badgeColor; ?>">
                                    <?php echo htmlspecialchars($item['tipo_grado']); ?>
                                </span>
                                <?php endif; ?>

                                <!-- Descripción / carrera -->
                                <h4 class="text-lg font-bold text-slate-800 mb-1">
                                    <?php echo htmlspecialchars($item['descripcion_carrera'] ?? 'No registrado'); ?>
                                </h4>

                                <!-- Institución -->
                                <p class="text-slate-500 italic text-sm">
                                    <?php echo htmlspecialchars($item['institucion'] ?? 'Institución no registrada'); ?>
                                </p>

                                <!-- Estado de validación -->
                                <?php if (!empty($item['estado_validacion']) && $item['estado_validacion'] !== 'PENDIENTE'): ?>
                                <span class="inline-block mt-2 text-[10px] font-bold uppercase px-2 py-0.5 rounded
                                    <?php echo $item['estado_validacion'] === 'APROBADO'
                                        ? 'bg-green-50 text-green-700'
                                        : 'bg-red-50 text-red-700'; ?>">
                                    <?php echo htmlspecialchars($item['estado_validacion']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ============================================================
                 TAB 3: EXPERIENCIA LABORAL
            ============================================================ -->
            <div id="tab-experiencia" class="tab-content hidden animate-fadeIn">
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 text-center py-20">
                    <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl">💼</div>
                    <h3 class="text-xl font-black text-slate-800">Experiencia Laboral</h3>
                    <p class="text-slate-500 max-w-sm mx-auto">
                        Esta sección se carga dinámicamente desde la trayectoria profesional registrada en el sistema.
                    </p>
                </div>
            </div>

        </div><!-- /.relative.-mt-24 -->
    </div><!-- /.max-w-6xl -->
</main>

<style>
    .active-tab { color: #7f1d1d; }
    .active-tab::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0;
        width: 100%; height: 4px;
        background: #7f1d1d;
        border-radius: 10px 10px 0 0;
    }
    .inactive-tab { color: #94a3b8; }
    .inactive-tab:hover { color: #475569; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeIn { animation: fadeIn 0.3s ease-out forwards; }

    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<script>
function showTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
        content.classList.remove('block');
    });
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active-tab');
        btn.classList.add('inactive-tab');
    });
    document.getElementById('tab-' + tabId).classList.remove('hidden');
    document.getElementById('tab-' + tabId).classList.add('block');
    document.getElementById('btn-' + tabId).classList.add('active-tab');
    document.getElementById('btn-' + tabId).classList.remove('inactive-tab');
}
</script>

<?php require_once ROOT_PATH . 'Vista/includes/footer.php'; ?>