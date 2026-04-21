<?php
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

$hijos = array_values(array_filter($familia, fn($f) => in_array(($f['parentesco'] ?? ''), ['HIJO', 'HIJA'], true)));

// Compatibilidad con el modal copiado desde colaborador
$perfil = $data;
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
                        <?php if ($esAdmin): ?>
                            <button onclick="abrirModal()" class="bg-red-900 text-white px-6 py-3 rounded-2xl font-bold hover:bg-[#310404] transition-all shadow-lg shadow-red-900/20 active:scale-95">
                                <svg class="w-4 h-4 inline-block mr-1 -mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                Editar Perfil
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tabs de navegación -->
                <div class="flex items-center gap-8 mt-10 border-t border-slate-100 pt-2 overflow-x-auto no-scrollbar">
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
                                                <div class="flex-1 grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
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
                            <?php if ($esAdmin): ?>
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
                                        <div class="flex justify-between items-center bg-slate-50 p-3 rounded-xl border border-slate-100">
                                            <span class="text-slate-400 font-medium">Situación</span>
                                            <?php
                                            $sit = $data['situacion'] ?? '';
                                            $sitColor = match (strtoupper($sit)) {
                                                'ACTIVO'  => 'text-green-700',
                                                default   => 'text-red-700',
                                            };
                                            ?>
                                            <span class="font-black <?php echo $sitColor; ?>"><?php echo htmlspecialchars($sit ?: '—'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div><!-- /columna lateral -->
                    </div>
                </div>

                <!-- ============================================================
        TAB 2: INFORMACIÓN
    ============================================================ -->
                <div id="tab-informacion" class="tab-content hidden animate-fadeIn">
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

                        <!-- Datos personales -->
                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
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
                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
                            <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                <span class="w-1.5 h-5 bg-slate-800 rounded-full"></span>
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
                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
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
                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
                            <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                <span class="w-1.5 h-5 bg-slate-800 rounded-full"></span>
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
                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
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
                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
                            <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                <span class="w-1.5 h-5 bg-slate-800 rounded-full"></span>
                                Sistema de Pensiones
                            </h3>

                            <div class="space-y-4 text-sm">
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-slate-400 font-medium">Sistema</span>
                                    <span class="font-bold text-slate-700"><?php echo htmlspecialchars($pension['sistema_pension'] ?? '—'); ?></span>
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-2 gap-4">
                                    <span class="text-slate-400 font-medium">Detalle Sistema</span>
                                    <span class="font-bold text-slate-700 text-right"><?php echo htmlspecialchars($pension['sistema_pension_detalle'] ?? '—'); ?></span>
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-slate-400 font-medium">AFP</span>
                                    <span class="font-bold text-slate-700"><?php echo htmlspecialchars($pension['afp'] ?? '—'); ?></span>
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-2 gap-4">
                                    <span class="text-slate-400 font-medium">Detalle AFP</span>
                                    <span class="font-bold text-slate-700 text-right"><?php echo htmlspecialchars($pension['afp_detalle'] ?? '—'); ?></span>
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
                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 xl:col-span-2">
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
                                        <div class="flex flex-col lg:flex-row lg:items-center gap-3 p-4 rounded-2xl border border-slate-100 bg-slate-50">
                                            <div class="w-8 h-8 rounded-xl bg-red-900 text-white flex items-center justify-center text-xs font-black shrink-0">
                                                <?php echo $i + 1; ?>
                                            </div>

                                            <div class="flex-1 grid grid-cols-2 lg:grid-cols-6 gap-3 text-xs">
                                                <div>
                                                    <p class="text-slate-400 font-bold uppercase tracking-wider mb-0.5">Ingreso</p>
                                                    <p class="font-black text-slate-700"><?php echo formatFecha($contrato['fecha_ingreso'] ?? null); ?></p>
                                                </div>
                                                <div>
                                                    <p class="text-slate-400 font-bold uppercase tracking-wider mb-0.5">Cese</p>
                                                    <p class="font-black text-slate-700"><?php echo formatFecha($contrato['fecha_cese'] ?? null); ?></p>
                                                </div>
                                                <div>
                                                    <p class="text-slate-400 font-bold uppercase tracking-wider mb-0.5">Modalidad</p>
                                                    <p class="font-black text-slate-700"><?php echo htmlspecialchars($contrato['modalidad_contrato'] ?? '—'); ?></p>
                                                </div>
                                                <div>
                                                    <p class="text-slate-400 font-bold uppercase tracking-wider mb-0.5">Puesto</p>
                                                    <p class="font-black text-slate-700"><?php echo htmlspecialchars($contrato['puesto_cas'] ?? '—'); ?></p>
                                                </div>
                                                <div>
                                                    <p class="text-slate-400 font-bold uppercase tracking-wider mb-0.5">Área</p>
                                                    <p class="font-black text-slate-700"><?php echo htmlspecialchars($contrato['area'] ?? '—'); ?></p>
                                                </div>
                                                <div>
                                                    <p class="text-slate-400 font-bold uppercase tracking-wider mb-0.5">Situación</p>
                                                    <p class="font-black text-slate-700"><?php echo htmlspecialchars($contrato['situacion'] ?? '—'); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

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
                            <div class="text-center py-16">
                                <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl">🎓</div>
                                <h4 class="text-lg font-black text-slate-700 mb-2">Sin formación registrada</h4>
                                <p class="text-slate-400 max-w-sm mx-auto text-sm">
                                    Aún no se ha registrado información académica para este colaborador.
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="relative pl-8 border-l-2 border-red-100 space-y-8">
                                <?php foreach ($formacion as $idx => $item):
                                    $tipo = strtoupper($item['tipo_grado'] ?? '');
                                    $dotColor = match (true) {
                                        str_contains($tipo, 'ESPECIALI') => 'bg-amber-500',
                                        str_contains($tipo, 'MAESTR')    => 'bg-purple-700',
                                        str_contains($tipo, 'DOCTOR')    => 'bg-slate-800',
                                        str_contains($tipo, 'BACHILLER') => 'bg-blue-600',
                                        str_contains($tipo, 'TECNI')     => 'bg-teal-600',
                                        default                          => 'bg-red-900',
                                    };
                                    $badgeColor = match (true) {
                                        str_contains($tipo, 'ESPECIALI') => 'bg-amber-50 text-amber-800 border-amber-100',
                                        str_contains($tipo, 'MAESTR')    => 'bg-purple-50 text-purple-800 border-purple-100',
                                        str_contains($tipo, 'DOCTOR')    => 'bg-slate-100 text-slate-800 border-slate-200',
                                        str_contains($tipo, 'BACHILLER') => 'bg-blue-50 text-blue-800 border-blue-100',
                                        str_contains($tipo, 'TECNI')     => 'bg-teal-50 text-teal-800 border-teal-100',
                                        default                          => 'bg-red-50 text-red-900 border-red-100',
                                    };
                                ?>
                                    <div class="relative">
                                        <div class="absolute -left-[41px] top-1 w-5 h-5 rounded-full <?php echo $dotColor; ?> border-4 border-white shadow-sm"></div>

                                        <?php if (!empty($item['tipo_grado'])): ?>
                                            <span class="inline-block text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded-md border mb-2 <?php echo $badgeColor; ?>">
                                                <?php echo htmlspecialchars($item['tipo_grado']); ?>
                                            </span>
                                        <?php endif; ?>

                                        <h4 class="text-lg font-bold text-slate-800 mb-1">
                                            <?php echo htmlspecialchars($item['descripcion_carrera'] ?? 'No registrado'); ?>
                                        </h4>

                                        <p class="text-slate-500 italic text-sm">
                                            <?php echo htmlspecialchars($item['institucion'] ?? 'Institución no registrada'); ?>
                                        </p>

                                        <?php if (!empty($item['estado_validacion']) && $item['estado_validacion'] !== 'PENDIENTE'): ?>
                                            <span class="inline-block mt-2 text-[10px] font-bold uppercase px-2 py-0.5 rounded <?php echo $item['estado_validacion'] === 'APROBADO'
                                                                                                                                    ? 'bg-green-50 text-green-700'
                                                                                                                                    : 'bg-red-50 text-red-700'; ?>">
                                                <?php echo htmlspecialchars($item['estado_validacion']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php $idiomas = $perfil['idiomas'] ?? []; ?>
                        <?php if (!empty($idiomas)): ?>
                            <div class="mt-10 pt-8 border-t border-slate-200">
                                <h3 class="text-xl font-black text-slate-800 mb-6 flex items-center gap-2">
                                    <span class="w-1.5 h-6 bg-slate-800 rounded-full"></span>
                                    Idiomas
                                    <span class="ml-auto bg-slate-50 text-slate-700 text-xs font-bold px-2 py-1 rounded-lg border border-slate-200">
                                        <?php echo count($idiomas); ?> registro(s)
                                    </span>
                                </h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <?php foreach ($idiomas as $idioma): ?>
                                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5">
                                            <p class="text-lg font-black text-slate-800 mb-2">
                                                <?php echo htmlspecialchars($idioma['idioma'] ?? 'Sin idioma'); ?>
                                            </p>
                                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Nivel</p>
                                            <p class="text-sm font-black text-red-800 mt-1">
                                                <?php echo htmlspecialchars($idioma['nivel'] ?? 'BASICO'); ?>
                                            </p>
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
                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
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

                                    $badgeEstado = match ($item['estado_validacion'] ?? 'PENDIENTE') {
                                        'APROBADO' => 'bg-green-50 text-green-700 border-green-200',
                                        'RECHAZADO' => 'bg-red-50 text-red-700 border-red-200',
                                        default => 'bg-amber-50 text-amber-700 border-amber-200',
                                    };

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
                                                        <span class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-lg border <?php echo $badgeEstado; ?>">
                                                            <?php echo htmlspecialchars($item['estado_validacion'] ?? 'PENDIENTE'); ?>
                                                        </span>

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

<?php if ($esAdmin): ?>
    <div id="modal-perfil" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">

        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="cerrarModal()"></div>

        <!-- Panel -->
        <div class="absolute inset-0 w-screen h-screen bg-white shadow-2xl flex flex-col">

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
            <div class="flex-1 overflow-y-auto px-6 lg:px-10 py-7 lg:py-10 bg-gradient-to-b from-slate-50 to-slate-100/70">

                <!-- ── PASO 1: Datos Personales ── -->
                <div id="form-step-1" class="form-step">
                    <div class="bg-white border border-slate-200 rounded-3xl p-6 lg:p-8 shadow-sm space-y-4">
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-4">Información Personal</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="field-group col-span-2">
                                <label class="field-label">Nombres y Apellidos</label>
                                <input type="text" name="nombres_apellidos" class="field-input"
                                    value="<?php echo htmlspecialchars($perfil['nombres_apellidos'] ?? ''); ?>"
                                    title="Este campo solo puede ser modificado por RRHH">
                                <p class="text-[10px] text-slate-400 mt-1">Solo RRHH puede modificar el nombre.</p>
                            </div>
                            <div class="field-group">
                                <label class="field-label">DNI</label>
                                <input type="text" name="dni" class="field-input" maxlength="8"
                                    value="<?php echo htmlspecialchars($perfil['dni'] ?? ''); ?>">
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
                </div>

                <!-- ── PASO 2: Contacto ── -->
                <div id="form-step-2" class="form-step space-y-4 hidden">
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-4">Contacto y Domicilio</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                value="<?php echo htmlspecialchars($perfil['correo_institucional'] ?? ''); ?>">
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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                    value="<?php echo htmlspecialchars($perfil['dni_conyuge'] ?? ''); ?>"
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
                                    <div class="hijo-row bg-white border border-slate-200 rounded-xl p-4 relative transition-all" data-index="<?php echo $hi; ?>">

                                        <div class="item-resumen flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-bold text-slate-800 val-nombre"><?php echo htmlspecialchars($hijo['nombre_completo'] ?? ''); ?></p>
                                                <p class="text-[11px] text-slate-500 mt-0.5">
                                                    <span class="val-parentesco"><?php echo htmlspecialchars($hijo['parentesco'] ?? 'HIJO'); ?></span>
                                                    • DNI: <span class="val-dni"><?php echo htmlspecialchars($hijo['dni_familiar'] ?: '—'); ?></span>
                                                </p>
                                            </div>
                                            <button type="button" onclick="toggleFila(this, true)" class="text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors border border-red-100">
                                                Editar
                                            </button>
                                        </div>

                                        <div class="item-form hidden mt-2 pt-3 border-t border-slate-100 relative animate-in">
                                            <button type="button" onclick="eliminarFila(this)" class="absolute top-0 right-0 text-slate-300 hover:text-red-500 transition-colors p-1" title="Eliminar">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                            <div class="grid grid-cols-2 gap-3 pr-6">
                                                <div class="field-group col-span-2">
                                                    <label class="field-label">Nombre Completo</label>
                                                    <input type="text" name="hijos[<?php echo $hi; ?>][nombre]" class="field-input input-nombre" value="<?php echo htmlspecialchars($hijo['nombre_completo'] ?? ''); ?>">
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
                                                    <input type="date" name="hijos[<?php echo $hi; ?>][fecha_nacimiento]" class="field-input input-fecha" value="<?php echo htmlspecialchars($hijo['fecha_nacimiento'] ?? ''); ?>">
                                                </div>
                                                <div class="field-group">
                                                    <label class="field-label">DNI</label>
                                                    <input type="text" name="hijos[<?php echo $hi; ?>][dni]" class="field-input input-dni" maxlength="8" value="<?php echo htmlspecialchars($hijo['dni_familiar'] ?? ''); ?>">
                                                </div>
                                                <input type="hidden" name="hijos[<?php echo $hi; ?>][id]" value="<?php echo $hijo['id'] ?? ''; ?>">
                                            </div>
                                            <div class="mt-3 text-right">
                                                <button type="button" onclick="toggleFila(this, false)" class="text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors bg-slate-100 px-3 py-1.5 rounded-lg">
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

                <!-- ── PASO 4: Pensiones ── -->
                <div id="form-step-4" class="form-step space-y-5 hidden">
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-4">Sistema de Pensiones</p>

                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="field-group col-span-2">
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

                            <div class="field-group col-span-2">
                                <label class="field-label">Detalle sistema (si es otros)</label>
                                <input type="text" name="pension[sistema_pension_detalle]" class="field-input"
                                    value="<?php echo htmlspecialchars($perfil['pension']['sistema_pension_detalle'] ?? ''); ?>">
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
                                <label class="field-label">Detalle AFP (si es otro)</label>
                                <input type="text" name="pension[afp_detalle]" class="field-input"
                                    value="<?php echo htmlspecialchars($perfil['pension']['afp_detalle'] ?? ''); ?>">
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
                                    <?php foreach (['MIXTA', 'FLUJO'] as $opt): ?>
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

                            <div class="field-group col-span-2">
                                <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                                    <input type="checkbox" name="pension[sin_afp_afiliarme]" value="1"
                                        <?php echo !empty($perfil['pension']['sin_afp_afiliarme']) ? 'checked' : ''; ?>>
                                    No tengo AFP (deseo afiliarme)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── PASO 5: Bancarios ── -->
                <div id="form-step-5" class="form-step space-y-5 hidden">
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-4">Datos Bancarios</p>

                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="field-group col-span-2">
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

                <!-- ── PASO 6: Academico ── -->
                <div id="form-step-6" class="form-step space-y-5 hidden">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Formación Académica</p>
                        <button type="button" onclick="agregarFormacion()"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-red-900 bg-red-50 hover:bg-red-100 border border-red-200 px-3 py-1.5 rounded-xl transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Agregar Estudio
                        </button>
                    </div>

                    <div id="lista-formacion" class="space-y-3">
                        <?php
                        if (empty($formacion)): ?>
                            <div id="sin-formacion" class="text-center py-5 text-slate-400 text-xs">
                                No hay estudios registrados. Haz clic en "Agregar Estudio".
                            </div>
                            <?php else:
                            foreach (array_values($formacion) as $fi => $form): ?>
                                <div class="formacion-row bg-white border border-slate-200 rounded-xl p-4 relative transition-all" data-index="<?php echo $fi; ?>">

                                    <div class="item-resumen flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-bold text-slate-800 val-carrera"><?php echo htmlspecialchars($form['descripcion_carrera'] ?? 'Sin carrera'); ?></p>
                                            <p class="text-[11px] text-slate-500 mt-0.5">
                                                <span class="val-grado"><?php echo htmlspecialchars($form['tipo_grado'] ?? 'BACHILLER'); ?></span>
                                                • <span class="val-inst"><?php echo htmlspecialchars($form['institucion'] ?: 'Sin institución'); ?></span>
                                            </p>
                                        </div>
                                        <button type="button" onclick="toggleFila(this, true)" class="text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors border border-red-100">
                                            Editar
                                        </button>
                                    </div>

                                    <div class="item-form hidden mt-2 pt-3 border-t border-slate-100 relative animate-in">
                                        <button type="button" onclick="eliminarFila(this)" class="absolute top-0 right-0 text-slate-300 hover:text-red-500 transition-colors p-1" title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                        <div class="grid grid-cols-2 gap-3 pr-6">
                                            <div class="field-group">
                                                <label class="field-label">Tipo de Grado</label>
                                                <select name="formacion[<?php echo $fi; ?>][tipo_grado]" class="field-input input-grado">
                                                    <?php foreach (['SECUNDARIA', 'TÉCNICO', 'BACHILLER', 'TÍTULO PROFESIONAL', 'MAESTRÍA', 'DOCTORADO', 'ESPECIALIZACIÓN'] as $t): ?>
                                                        <option value="<?php echo $t; ?>" <?php echo (strtoupper($form['tipo_grado'] ?? '') === $t) ? 'selected' : ''; ?>><?php echo $t; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="field-group">
                                                <label class="field-label">Carrera / Especialidad</label>
                                                <input type="text" name="formacion[<?php echo $fi; ?>][descripcion_carrera]" class="field-input input-carrera" value="<?php echo htmlspecialchars($form['descripcion_carrera'] ?? ''); ?>">
                                            </div>
                                            <div class="field-group col-span-2">
                                                <label class="field-label">Institución</label>
                                                <input type="text" name="formacion[<?php echo $fi; ?>][institucion]" class="field-input input-inst" value="<?php echo htmlspecialchars($form['institucion'] ?? ''); ?>">
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
                        <?php endforeach;
                        endif; ?>
                    </div>
                </div>

                <!-- ── PASO 7: Idiomas ── -->
                <div id="form-step-7" class="form-step space-y-5 hidden">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Idiomas</p>
                        <button type="button" onclick="agregarIdioma()"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-red-900 bg-red-50 hover:bg-red-100 border border-red-200 px-3 py-1.5 rounded-xl transition-all">
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
                                    <div class="item-resumen flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-bold text-slate-800 val-idioma"><?php echo htmlspecialchars($idioma['idioma'] ?? 'Sin idioma'); ?></p>
                                            <p class="text-[11px] text-slate-500 mt-0.5">
                                                Nivel: <span class="val-nivel"><?php echo htmlspecialchars($idioma['nivel'] ?? 'BASICO'); ?></span>
                                            </p>
                                        </div>
                                        <button type="button" onclick="toggleFila(this, true)" class="text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors border border-red-100">
                                            Editar
                                        </button>
                                    </div>

                                    <div class="item-form hidden mt-2 pt-3 border-t border-slate-100 relative animate-in">
                                        <button type="button" onclick="eliminarFila(this)" class="absolute top-0 right-0 text-slate-300 hover:text-red-500 transition-colors p-1" title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>

                                        <div class="grid grid-cols-2 gap-3 pr-6">
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

                <!-- ── PASO 8: Exp. Laboral ── -->
                <div id="form-step-8" class="form-step space-y-5 hidden">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Experiencia Laboral</p>
                        <button type="button" onclick="agregarExperiencia()"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-red-900 bg-red-50 hover:bg-red-100 border border-red-200 px-3 py-1.5 rounded-xl transition-all">
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

                                    <div class="item-resumen flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-bold text-slate-800 val-cargo"><?php echo htmlspecialchars($exp['cargo_puesto'] ?? 'Sin cargo'); ?></p>
                                            <p class="text-[11px] text-slate-500 mt-0.5">
                                                <span class="val-empresa"><?php echo htmlspecialchars($exp['empresa_entidad'] ?? 'Sin empresa'); ?></span>
                                                • <span class="val-fechas">
                                                    <?php echo !empty($exp['fecha_inicio']) ? formatFecha($exp['fecha_inicio']) : '—'; ?>
                                                    -
                                                    <?php echo !empty($exp['actualmente_trabaja']) ? 'Actualidad' : (!empty($exp['fecha_fin']) ? formatFecha($exp['fecha_fin']) : '—'); ?>
                                                </span>
                                            </p>
                                        </div>
                                        <button type="button" onclick="toggleFila(this, true)" class="text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors border border-red-100">
                                            Editar
                                        </button>
                                    </div>

                                    <div class="item-form hidden mt-2 pt-3 border-t border-slate-100 relative animate-in">
                                        <button type="button" onclick="eliminarFila(this)" class="absolute top-0 right-0 text-slate-300 hover:text-red-500 transition-colors p-1" title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>

                                        <div class="grid grid-cols-2 gap-3 pr-6">
                                            <div class="field-group col-span-2">
                                                <label class="field-label">Empresa / Entidad</label>
                                                <input type="text" name="experiencia[<?php echo $ei; ?>][empresa_entidad]" class="field-input input-empresa" value="<?php echo htmlspecialchars($exp['empresa_entidad'] ?? ''); ?>">
                                            </div>

                                            <div class="field-group col-span-2">
                                                <label class="field-label">Unidad Orgánica / Área</label>
                                                <input type="text" name="experiencia[<?php echo $ei; ?>][unidad_organica_area]" class="field-input input-area" value="<?php echo htmlspecialchars($exp['unidad_organica_area'] ?? ''); ?>">
                                            </div>

                                            <div class="field-group col-span-2">
                                                <label class="field-label">Cargo / Puesto</label>
                                                <input type="text" name="experiencia[<?php echo $ei; ?>][cargo_puesto]" class="field-input input-cargo" value="<?php echo htmlspecialchars($exp['cargo_puesto'] ?? ''); ?>">
                                            </div>

                                            <div class="field-group">
                                                <label class="field-label">Fecha Inicio</label>
                                                <input type="date" name="experiencia[<?php echo $ei; ?>][fecha_inicio]" class="field-input input-inicio" value="<?php echo htmlspecialchars($exp['fecha_inicio'] ?? ''); ?>">
                                            </div>

                                            <div class="field-group">
                                                <label class="field-label">Fecha Fin</label>
                                                <input type="date" name="experiencia[<?php echo $ei; ?>][fecha_fin]" class="field-input input-fin" value="<?php echo htmlspecialchars($exp['fecha_fin'] ?? ''); ?>">
                                            </div>

                                            <div class="field-group col-span-2">
                                                <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                                                    <input type="checkbox" name="experiencia[<?php echo $ei; ?>][actualmente_trabaja]" class="input-actual" value="1"
                                                        <?php echo !empty($exp['actualmente_trabaja']) ? 'checked' : ''; ?>>
                                                    Actualmente trabaja aquí
                                                </label>
                                            </div>

                                            <div class="field-group col-span-2">
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
            </div>
        </div><!-- /overflow-y-auto -->

        <!-- Footer con navegación del wizard -->
        <div class="sticky bottom-0 z-20 px-6 lg:px-8 py-5 border-t border-slate-100 flex items-center justify-between bg-slate-50">
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
<?php endif; ?>

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

    /* Form fields */
    .field-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
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
        min-height: 46px;
        padding: 12px 14px;
        border: 1.5px solid #dbe3ee;
        border-radius: 14px;
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        background: #f8fafc;
        transition: border-color .2s, background .2s, box-shadow .2s;
        outline: none;
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

    /* Scrollbar */
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    /* Modal pantalla completa */
    #modal-perfil .absolute.inset-0:last-child {
        transition: transform .35s cubic-bezier(.4, 0, .2, 1), opacity .35s ease;
        transform: translateY(16px);
        opacity: 0;
    }

    #modal-perfil.modal-open .absolute.inset-0:last-child {
        transform: translateY(0);
        opacity: 1;
    }

    #modal-perfil>.absolute.inset-0:first-child {
        transition: opacity .35s ease;
        opacity: 0;
    }

    #modal-perfil.modal-open>.absolute.inset-0:first-child {
        opacity: 1;
    }

    /* Stepper superior */
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

    /* Área scroll */
    #modal-perfil .flex-1.overflow-y-auto {
        padding-top: 28px;
        padding-bottom: 40px;
        background: linear-gradient(to bottom, #f8fafc 0%, #f1f5f9 100%);
    }

    /* ===== CADA STEP REAL COMO BLOQUE BLANCO ===== */
    #modal-perfil .form-step {
        margin-bottom: 24px;
    }

    #modal-perfil .form-step.hidden {
        display: none !important;
    }

    #modal-perfil .form-step.block {
        display: block !important;
    }

    /* PASO 1 */
    #modal-perfil #form-step-1 {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 28px;
        padding: 24px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .05);
    }

    #modal-perfil #form-step-1>.bg-white.border {
        background: transparent !important;
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    /* PASO 2 */
    #modal-perfil #form-step-2 {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 28px;
        padding: 24px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .05);
    }

    /* PASO 3 */
    #modal-perfil #form-step-3 {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 28px;
        padding: 24px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .05);
    }

    #modal-perfil #form-step-3>.bg-slate-50.border {
        background: transparent !important;
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    /* PASO 4 */
    #modal-perfil #form-step-4 {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 28px;
        padding: 24px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .05);
    }

    #modal-perfil #form-step-4>.bg-slate-50.border {
        background: transparent !important;
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    /* PASO 5 */
    #modal-perfil #form-step-5 {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 28px;
        padding: 24px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .05);
    }

    #modal-perfil #form-step-5>.bg-slate-50.border {
        background: transparent !important;
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    /* PASO 6 */
    #modal-perfil #form-step-6 {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 28px;
        padding: 24px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .05);
    }

    /* PASO 7 */
    #modal-perfil #form-step-7 {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 28px;
        padding: 24px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .05);
    }

    /* PASO 8 */
    #modal-perfil #form-step-8 {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 28px;
        padding: 24px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .05);
    }

    /* PASO 9 */
    #modal-perfil #form-step-9 {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 28px;
        padding: 24px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .05);
    }

    /* títulos de cada bloque */
    #modal-perfil .form-step>p.text-xs,
    #modal-perfil .form-step .bg-white.border>p.text-xs {
        margin-bottom: 16px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .12em;
        color: #94a3b8;
        text-transform: uppercase;
    }

    /* bloques internos grises */
    #modal-perfil .bg-slate-50.border {
        border-radius: 22px;
        padding: 20px;
    }

    /* filas dinámicas */
    #modal-perfil .hijo-row,
    #modal-perfil .formacion-row,
    #modal-perfil .idioma-row,
    #modal-perfil .experiencia-row {
        border-radius: 18px;
        padding: 18px;
    }

    /* Resumen paso final */
    .resumen-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
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

    /* Footer */
    #modal-perfil .sticky.bottom-0 {
        box-shadow: 0 -8px 30px rgba(15, 23, 42, .06);
    }

    @media (max-width: 640px) {
        #modal-perfil .step-indicator {
            min-height: auto;
            padding: 12px 14px;
        }

        #modal-perfil .step-indicator p:last-child {
            display: none;
        }

        #modal-perfil #form-step-1,
        #modal-perfil #form-step-2,
        #modal-perfil #form-step-3,
        #modal-perfil #form-step-4,
        #modal-perfil #form-step-5,
        #modal-perfil #form-step-6,
        #modal-perfil #form-step-7,
        #modal-perfil #form-step-8,
        #modal-perfil #form-step-9 {
            padding: 18px;
            border-radius: 22px;
        }

        #modal-perfil .bg-slate-50.border {
            padding: 16px;
            border-radius: 18px;
        }
    }
</style>

<!-- ═══════════════════════════════════════════════
        JAVASCRIPT
    ═══════════════════════════════════════════════ -->
<script>
    // ── MAGIA DE ABRIR/CERRAR FILAS ────────────────────────
    function toggleFila(btn, editar) {
        const row = btn.closest('.hijo-row, .formacion-row, .experiencia-row, .idioma-row');

        if (!row) return;

        const resumen = row.querySelector('.item-resumen');
        const form = row.querySelector('.item-form');

        if (editar) {
            resumen.classList.add('hidden');
            form.classList.remove('hidden');
        } else {
            resumen.classList.remove('hidden');
            form.classList.add('hidden');

            // HIJOS
            if (row.classList.contains('hijo-row')) {
                row.querySelector('.val-nombre').textContent =
                    row.querySelector('.input-nombre')?.value || 'Sin nombre';

                row.querySelector('.val-parentesco').textContent =
                    row.querySelector('.input-parentesco')?.value || 'HIJO';

                row.querySelector('.val-dni').textContent =
                    row.querySelector('.input-dni')?.value || '—';
            }

            // FORMACIÓN
            else if (row.classList.contains('formacion-row')) {
                row.querySelector('.val-carrera').textContent =
                    row.querySelector('.input-carrera')?.value || 'Sin carrera';

                row.querySelector('.val-grado').textContent =
                    row.querySelector('.input-grado')?.value || 'BACHILLER';

                row.querySelector('.val-inst').textContent =
                    row.querySelector('.input-inst')?.value || 'Sin institución';
            }

            // EXPERIENCIA
            else if (row.classList.contains('experiencia-row')) {
                row.querySelector('.val-cargo').textContent =
                    row.querySelector('.input-cargo')?.value || 'Sin cargo';

                row.querySelector('.val-empresa').textContent =
                    row.querySelector('.input-empresa')?.value || 'Sin empresa';
            }

            // 🔥 ESTE ES TU CASO (IDIOMAS)
            else if (row.classList.contains('idioma-row')) {
                row.querySelector('.val-idioma').textContent =
                    row.querySelector('.input-idioma')?.value || 'Sin idioma';

                row.querySelector('.val-nivel').textContent =
                    row.querySelector('.input-nivel')?.value || 'BASICO';
            }
        }
    }

    function eliminarFila(btn) {
        const row = btn.closest('.hijo-row') || btn.closest('.formacion-row') || btn.closest('.idioma-row') || btn.closest('.experiencia-row');
        const isIdioma = row.classList.contains('idioma-row');
        if (!row) return;

        const isHijo = row.classList.contains('hijo-row');
        const isFormacion = row.classList.contains('formacion-row');
        const isExperiencia = row.classList.contains('experiencia-row');

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
        }, 200);
    }

    // ── HIJOS DINÁMICOS ───────────────────────────────
    let hijoIdx = <?php echo max(count(array_filter($perfil['familia'] ?? [], fn($f) => in_array($f['parentesco'], ['HIJO', 'HIJA']))), 0); ?>;

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
                <div class="grid grid-cols-2 gap-3 pr-6">
                    <div class="field-group">
                        <label class="field-label">Tipo de Grado</label>
                        <select name="formacion[${idx}][tipo_grado]" class="field-input input-grado">
                            <option value="BACHILLER">Bachiller</option>
                            <option value="TÍTULO PROFESIONAL">Título Profesional</option>
                            <option value="TÉCNICO">Técnico</option>
                            <option value="MAESTRÍA">Maestría</option>
                            <option value="DOCTORADO">Doctorado</option>
                            <option value="ESPECIALIZACIÓN">Especialización</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Carrera / Especialidad</label>
                        <input type="text" name="formacion[${idx}][descripcion_carrera]" class="field-input input-carrera" placeholder="Ej: Ing. Sistemas">
                    </div>
                    <div class="field-group col-span-2">
                        <label class="field-label">Institución</label>
                        <input type="text" name="formacion[${idx}][institucion]" class="field-input input-inst" placeholder="Ej: Universidad Nacional...">
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
    let pasoActual = 1;
    const totalPasos = 4;

    // Cada macro-paso agrupa varios pasos reales del formulario
    const gruposPasos = {
        1: [1, 2], // Personal + Contacto
        2: [3, 4, 5], // Familia + Pensión + Banco
        3: [6, 7, 8], // Formación + Idiomas + Experiencia
        4: [9] // Confirmación
    };

    function abrirModal() {
        const m = document.getElementById('modal-perfil');
        m.classList.remove('hidden');
        requestAnimationFrame(() => m.classList.add('modal-open'));

        document.querySelectorAll('.form-step [name]').forEach(el => {
            if (
                !el.readOnly &&
                !el.name.includes('hijos') &&
                !el.name.includes('formacion') &&
                !el.name.includes('experiencia') &&
                !el.name.includes('idiomas[')
            ) {
                if (el.type === 'checkbox') {
                    valoresOriginales[el.name] = el.checked ? 1 : 0;
                } else {
                    valoresOriginales[el.name] = el.value;
                }
            }
        });

        valoresOriginales['hijos'] = [];
        document.querySelectorAll('.hijo-row').forEach(row => {
            const id = row.querySelector('[name*="[id]"]')?.value;
            if (id) {
                valoresOriginales['hijos'].push({
                    id: id,
                    nombre: row.querySelector('[name*="[nombre]"]')?.value,
                    dni: row.querySelector('[name*="[dni]"]')?.value,
                    fecha: row.querySelector('[name*="[fecha_nacimiento]"]')?.value
                });
            }
        });

        valoresOriginales['formacion'] = [];
        document.querySelectorAll('.formacion-row').forEach(row => {
            const id = row.querySelector('[name*="[id]"]')?.value;
            if (id) {
                valoresOriginales['formacion'].push({
                    id: id,
                    carrera: row.querySelector('[name*="[descripcion_carrera]"]')?.value
                });
            }
        });

        valoresOriginales['experiencia'] = [];
        document.querySelectorAll('.experiencia-row').forEach(row => {
            const id = row.querySelector('[name*="[id]"]')?.value;
            if (id) {
                valoresOriginales['experiencia'].push({
                    id: id,
                    empresa_entidad: row.querySelector('[name*="[empresa_entidad]"]')?.value || '',
                    cargo_puesto: row.querySelector('[name*="[cargo_puesto]"]')?.value || ''
                });
            }
        });

        irPaso(1);
    }

    function cerrarModal() {
        const m = document.getElementById('modal-perfil');
        m.classList.remove('modal-open');
        setTimeout(() => m.classList.add('hidden'), 350);
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

    // ── RESUMEN EN ÚLTIMO PASO ────────────────────────
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

        // ── Campos simples ─────────────────────────
        document.querySelectorAll('.form-step [name]').forEach(el => {
            if (
                el.readOnly ||
                el.name.includes('hijos') ||
                el.name.includes('formacion') ||
                el.name.includes('experiencia')
            ) return;

            if (!labelesCampos[el.name]) return;

            const original = valoresOriginales[el.name] ?? '';
            const actual = el.type === 'checkbox' ? (el.checked ? 1 : 0) : (el.value ?? '');

            if (String(original) !== String(actual)) {
                cambios.push(`
                <div class="resumen-item">
                    <span class="r-label">${labelesCampos[el.name]}</span>
                    <div class="text-right">
                        <p class="text-[11px] text-slate-400 line-through">${original || '—'}</p>
                        <p class="r-val text-green-700">${actual || '—'}</p>
                    </div>
                </div>
            `);
            }
        });

        // ── HIJOS: nuevos / editados ─────────────────────────
        const hijosActualesIds = [];

        document.querySelectorAll('.hijo-row').forEach(row => {
            const idActual = row.querySelector('[name*="[id]"]')?.value || '';
            const nombreActual = row.querySelector('[name*="[nombre]"]')?.value ?? '';
            if (!nombreActual) return;

            if (idActual) hijosActualesIds.push(String(idActual));

            const original = valoresOriginales['hijos'].find(h => String(h.id) === String(idActual));

            if (!original) {
                cambios.push(`
                <div class="resumen-item border-l-4 border-green-500">
                    <span class="r-label text-green-600">Nuevo Hijo</span>
                    <span class="r-val">${nombreActual}</span>
                </div>
            `);
            } else if (original.nombre !== nombreActual) {
                cambios.push(`
                <div class="resumen-item border-l-4 border-amber-500">
                    <span class="r-label text-amber-600">Hijo Editado</span>
                    <span class="r-val">${nombreActual}</span>
                </div>
            `);
            }
        });

        // ── HIJOS: eliminados ─────────────────────────
        (valoresOriginales['hijos'] || []).forEach(hijoOriginal => {
            if (!hijosActualesIds.includes(String(hijoOriginal.id))) {
                cambios.push(`
                <div class="resumen-item border-l-4 border-red-500">
                    <span class="r-label text-red-600">Hijo Eliminado</span>
                    <span class="r-val">${hijoOriginal.nombre || '—'}</span>
                </div>
            `);
            }
        });

        // ── FORMACIÓN: nuevos / editados ─────────────────────────
        const formacionActualIds = [];

        document.querySelectorAll('.formacion-row').forEach(row => {
            const idActual = row.querySelector('[name*="[id]"]')?.value || '';
            const carreraActual = row.querySelector('[name*="[descripcion_carrera]"]')?.value ?? '';
            if (!carreraActual) return;

            if (idActual) formacionActualIds.push(String(idActual));

            const original = valoresOriginales['formacion'].find(f => String(f.id) === String(idActual));

            if (!original) {
                cambios.push(`
                <div class="resumen-item border-l-4 border-green-500">
                    <span class="r-label text-green-600">Nuevo Estudio</span>
                    <span class="r-val">${carreraActual}</span>
                </div>
            `);
            } else if (original.carrera !== carreraActual) {
                cambios.push(`
                <div class="resumen-item border-l-4 border-amber-500">
                    <span class="r-label text-amber-600">Estudio Editado</span>
                    <span class="r-val">${carreraActual}</span>
                </div>
            `);
            }
        });

        // ── FORMACIÓN: eliminados ─────────────────────────
        (valoresOriginales['formacion'] || []).forEach(formOriginal => {
            if (!formacionActualIds.includes(String(formOriginal.id))) {
                cambios.push(`
                <div class="resumen-item border-l-4 border-red-500">
                    <span class="r-label text-red-600">Estudio Eliminado</span>
                    <span class="r-val">${formOriginal.carrera || '—'}</span>
                </div>
            `);
            }
        });

        // ── EXPERIENCIA: nuevos / editados ─────────────────────────
        const experienciaActualIds = [];

        document.querySelectorAll('.experiencia-row').forEach(row => {
            const idActual = row.querySelector('[name*="[id]"]')?.value || '';
            const empresaActual = row.querySelector('[name*="[empresa_entidad]"]')?.value ?? '';
            const cargoActual = row.querySelector('[name*="[cargo_puesto]"]')?.value ?? '';

            if (!empresaActual && !cargoActual) return;

            if (idActual) experienciaActualIds.push(String(idActual));

            const original = valoresOriginales['experiencia'].find(e => String(e.id) === String(idActual));
            const textoActual = `${cargoActual || 'Sin cargo'} - ${empresaActual || 'Sin empresa'}`;

            if (!original) {
                cambios.push(`
                <div class="resumen-item border-l-4 border-green-500">
                    <span class="r-label text-green-600">Nueva Experiencia</span>
                    <span class="r-val">${textoActual}</span>
                </div>
            `);
            } else if (
                original.empresa_entidad !== empresaActual ||
                original.cargo_puesto !== cargoActual
            ) {
                cambios.push(`
                <div class="resumen-item border-l-4 border-amber-500">
                    <span class="r-label text-amber-600">Experiencia Editada</span>
                    <span class="r-val">${textoActual}</span>
                </div>
            `);
            }
        });

        // ── EXPERIENCIA: eliminados ─────────────────────────
        (valoresOriginales['experiencia'] || []).forEach(expOriginal => {
            if (!experienciaActualIds.includes(String(expOriginal.id))) {
                const textoEliminado = `${expOriginal.cargo_puesto || 'Sin cargo'} - ${expOriginal.empresa_entidad || 'Sin empresa'}`;

                cambios.push(`
                <div class="resumen-item border-l-4 border-red-500">
                    <span class="r-label text-red-600">Experiencia Eliminada</span>
                    <span class="r-val">${textoEliminado}</span>
                </div>
            `);
            }
        });

        // ── Render final ─────────────────────────
        if (cambios.length === 0) {
            container.innerHTML = `<div class="text-center py-8 text-slate-400 text-sm">No realizaste ningún cambio.</div>`;
        } else {
            container.innerHTML = `
            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-3">${cambios.length} cambio(s) detectado(s)</p>
            <div class="space-y-2">${cambios.join('')}</div>
        `;
        }
    }

    // ── GUARDAR VÍA AJAX ──────────────────────────────
    function guardarPerfil() {
        const btn = document.getElementById('btn-guardar');
        btn.textContent = 'Guardando…';
        btn.disabled = true;

        const campos = {};

        // Campos simples
        document.querySelectorAll('.form-step [name]').forEach(el => {
            if (
                !el.readOnly &&
                !el.name.includes('hijos') &&
                !el.name.includes('formacion') &&
                !el.name.includes('experiencia') &&
                !el.name.includes('idiomas[') &&
                !el.name.startsWith('pension[') &&
                !el.name.startsWith('bancario[')
            ) {
                if (el.type === 'checkbox') {
                    campos[el.name] = el.checked ? 1 : 0;
                } else {
                    campos[el.name] = el.value;
                }
            }
        });

        campos['id'] = <?php echo (int)($perfil['id'] ?? 0); ?>;

        // Hijos
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

        // Formación
        const formacion = [];
        document.querySelectorAll('.formacion-row').forEach(row => {
            const idx = row.dataset.index;
            formacion.push({
                id: row.querySelector(`[name="formacion[${idx}][id]"]`)?.value || '',
                tipo_grado: row.querySelector(`[name="formacion[${idx}][tipo_grado]"]`)?.value || '',
                descripcion_carrera: row.querySelector(`[name="formacion[${idx}][descripcion_carrera]"]`)?.value || '',
                institucion: row.querySelector(`[name="formacion[${idx}][institucion]"]`)?.value || '',
                anio_realizacion: row.querySelector(`[name="formacion[${idx}][anio_realizacion]"]`)?.value || '',
                horas_lectivas: row.querySelector(`[name="formacion[${idx}][horas_lectivas]"]`)?.value || '',
                especialidad: row.querySelector(`[name="formacion[${idx}][especialidad]"]`)?.value || '',
                grado_alcanzado: row.querySelector(`[name="formacion[${idx}][grado_alcanzado]"]`)?.value || ''
            });
        });
        campos['formacion'] = formacion;

        // Experiencia
        const experiencia = [];
        document.querySelectorAll('.experiencia-row').forEach(row => {
            const idx = row.dataset.index;
            experiencia.push({
                id: row.querySelector(`[name="experiencia[${idx}][id]"]`)?.value || '',
                empresa_entidad: row.querySelector(`[name="experiencia[${idx}][empresa_entidad]"]`)?.value || '',
                unidad_organica_area: row.querySelector(`[name="experiencia[${idx}][unidad_organica_area]"]`)?.value || '',
                cargo_puesto: row.querySelector(`[name="experiencia[${idx}][cargo_puesto]"]`)?.value || '',
                fecha_inicio: row.querySelector(`[name="experiencia[${idx}][fecha_inicio]"]`)?.value || '',
                fecha_fin: row.querySelector(`[name="experiencia[${idx}][fecha_fin]"]`)?.value || '',
                actualmente_trabaja: row.querySelector(`[name="experiencia[${idx}][actualmente_trabaja]"]`)?.checked ? 1 : 0,
                funciones_principales: row.querySelector(`[name="experiencia[${idx}][funciones_principales]"]`)?.value || ''
            });
        });
        campos['experiencia'] = experiencia;

        // Pensión
        campos['pension'] = {
            sistema_pension: document.querySelector('[name="pension[sistema_pension]"]')?.value || '',
            sistema_pension_detalle: document.querySelector('[name="pension[sistema_pension_detalle]"]')?.value || '',
            afp: document.querySelector('[name="pension[afp]"]')?.value || '',
            afp_detalle: document.querySelector('[name="pension[afp_detalle]"]')?.value || '',
            cuspp: document.querySelector('[name="pension[cuspp]"]')?.value || '',
            tipo_comision: document.querySelector('[name="pension[tipo_comision]"]')?.value || '',
            fecha_inscripcion: document.querySelector('[name="pension[fecha_inscripcion]"]')?.value || '',
            sin_afp_afiliarme: document.querySelector('[name="pension[sin_afp_afiliarme]"]')?.checked ? 1 : 0
        };

        // Bancario
        campos['bancario'] = {
            banco_haberes: document.querySelector('[name="bancario[banco_haberes]"]')?.value || '',
            numero_cuenta: document.querySelector('[name="bancario[numero_cuenta]"]')?.value || '',
            numero_cuenta_cci: document.querySelector('[name="bancario[numero_cuenta_cci]"]')?.value || ''
        };

        // Idiomas
        const idiomas = [];
        document.querySelectorAll('.idioma-row').forEach(row => {
            const idx = row.dataset.index;
            const idioma = row.querySelector(`[name="idiomas[${idx}][idioma]"]`)?.value || '';
            const nivel = row.querySelector(`[name="idiomas[${idx}][nivel]"]`)?.value || 'BASICO';

            if (idioma.trim() !== '') {
                idiomas.push({
                    idioma: idioma,
                    nivel: nivel
                });
            }
        });
        campos['idiomas'] = idiomas;

        console.log('DATOS A ENVIAR:', campos);

        fetch('<?php echo BASE_URL; ?>/perfil/actualizar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(campos)
            })
            .then(response => response.text())
            .then(raw => {
                console.log('RESPUESTA RAW DEL SERVIDOR:', raw);

                const cleanJson = raw.trim();
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
                console.error('Error real:', err);
                mostrarToast('✗', 'La respuesta del servidor no fue válida', 'bg-red-800');
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
        if (e.key === 'Escape') cerrarModal();
    });

    document.querySelectorAll('.idioma-row').forEach(row => {

        const idioma = row.querySelector('.input-idioma')?.value || 'Sin idioma';
        const nivel = row.querySelector('.input-nivel')?.value || 'BÁSICO';

        if (row.querySelector('.val-idioma')) {
            row.querySelector('.val-idioma').textContent = idioma;
        }

        if (row.querySelector('.val-nivel')) {
            row.querySelector('.val-nivel').textContent = nivel;
        }

    });
</script>

<?php require_once ROOT_PATH . 'Vista/includes/footer.php'; ?>