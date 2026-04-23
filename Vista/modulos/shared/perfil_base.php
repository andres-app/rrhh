<?php

$rolSesion = strtolower($_SESSION['user_role'] ?? '');
$esEditable = in_array($rolSesion, ['admin', 'rrhh', 'superadmin']);
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
                        <?php echo mb_substr($data['nombres_apellidos'] ?? 'C', 0, 1); ?>
                    </div>

                    <!-- Nombre y badges -->
                    <div class="flex-1 text-center md:text-left">
                        <h1 class="text-3xl font-black text-slate-800 tracking-tight mb-2">
                            <?php echo htmlspecialchars($data['nombres_apellidos'] ?? 'Colaborador'); ?>
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
                        <button onclick="abrirModal()" class="bg-red-900 text-white px-6 py-3 rounded-2xl font-bold hover:bg-[#310404] transition-all shadow-lg shadow-red-900/20 active:scale-95">
                            <svg class="w-4 h-4 inline-block mr-1 -mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                            Editar Perfil
                        </button>
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
                            <div class="bg-white p-8 rounded-[32px] shadow-sm border border-slate-200/80">
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
                            <div class="bg-white p-8 rounded-[32px] shadow-sm border border-slate-200/80">
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
                            <div class="bg-white p-8 rounded-[32px] shadow-sm border border-slate-200/80">
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
                                                <div class="w-8 h-8 rounded-xl bg-red-900 text-white flex items-center justify-center text-xs font-black shrink-0">
                                                    <?php echo $i + 1; ?>
                                                </div>

                                                <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                                                    <div>
                                                        <p class="text-slate-400 font-bold uppercase tracking-wider mb-0.5">Ingreso</p>
                                                        <p class="font-black text-slate-700">
                                                            <?php echo !empty($contrato['fecha_ingreso']) ? formatFecha($contrato['fecha_ingreso']) : '—'; ?>
                                                        </p>
                                                    </div>

                                                    <div>
                                                        <p class="text-slate-400 font-bold uppercase tracking-wider mb-0.5">Cese</p>
                                                        <p class="font-black text-slate-700">
                                                            <?php echo !empty($contrato['fecha_cese']) ? formatFecha($contrato['fecha_cese']) : 'Vigente'; ?>
                                                        </p>
                                                    </div>

                                                    <div>
                                                        <p class="text-slate-400 font-bold uppercase tracking-wider mb-0.5">Modalidad</p>
                                                        <p class="font-black text-slate-700">
                                                            <?php echo htmlspecialchars($contrato['modalidad'] ?? '—'); ?>
                                                        </p>
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
                                        <?php echo htmlspecialchars(($data['conyuge'] ?? '') ?: 'No registrado'); ?>
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
                            <?php if ($esEditable): ?>
                                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                                    <h3 class="text-md font-black text-slate-800 mb-4 flex items-center gap-2">
                                        <span class="w-1.5 h-4 bg-slate-800 rounded-full"></span>
                                        Datos Laborales
                                    </h3>
                                    <div class="space-y-4 text-sm">
                                        <div class="flex justify-between items-center bg-slate-50 p-3 rounded-xl border border-slate-100">
                                            <span class="text-slate-400 font-medium">Sueldo</span>

                                            <div class="flex items-center gap-2">
                                                <!-- Monto -->
                                                <span id="sueldo-texto" class="font-black text-red-900 text-base tracking-widest">
                                                    *****
                                                </span>

                                                <!-- Botón ojito -->
                                                <button type="button" onclick="toggleSueldo()" class="text-slate-400 hover:text-red-900 transition">
                                                    <svg id="icono-ojo" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path id="ojo-abierto" stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path id="ojo-linea" stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-.274.847-.66 1.647-1.143 2.379M15 12a3 3 0 00-6 0" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Guardamos el valor real -->
                                        <input type="hidden" id="sueldo-real" value="<?php echo !empty($data['sueldo']) ? number_format($data['sueldo'], 2) : '0.00'; ?>">
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
                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 xl:col-span-2">
                            <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                <span class="w-1.5 h-5 bg-red-800 rounded-full"></span>
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

                                            <div class="flex-1 grid grid-cols-1 lg:grid-cols-3 gap-3 text-xs">
                                                <div>
                                                    <p class="text-slate-400 font-bold uppercase tracking-wider mb-0.5">Ingreso</p>
                                                    <p class="font-black text-slate-700">
                                                        <?php echo !empty($contrato['fecha_ingreso']) ? formatFecha($contrato['fecha_ingreso']) : '—'; ?>
                                                    </p>
                                                </div>

                                                <div>
                                                    <p class="text-slate-400 font-bold uppercase tracking-wider mb-0.5">Cese</p>
                                                    <p class="font-black text-slate-700">
                                                        <?php echo !empty($contrato['fecha_cese']) ? formatFecha($contrato['fecha_cese']) : 'Vigente'; ?>
                                                    </p>
                                                </div>

                                                <div>
                                                    <p class="text-slate-400 font-bold uppercase tracking-wider mb-0.5">Modalidad</p>
                                                    <p class="font-black text-slate-700">
                                                        <?php echo htmlspecialchars($contrato['modalidad'] ?? '—'); ?>
                                                    </p>
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
                    <div class="bg-white p-8 rounded-[32px] shadow-sm border border-slate-200/80">
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
                            <div class="relative pl-8 border-l border-red-100/70 space-y-6">
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
                                        <div class="absolute -left-[41px] top-7 w-5 h-5 rounded-full <?php echo $dotColor; ?> border-4 border-white shadow-md"></div>

                                        <div class="bg-white border border-slate-200/80 rounded-3xl p-5 md:p-6 shadow-sm hover:shadow-md transition-all">
                                            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                                <div class="min-w-0">
                                                    <?php if (!empty($item['tipo_grado'])): ?>
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.18em] border mb-3 <?php echo $badgeColor; ?>">
                                                            <?php echo htmlspecialchars($item['tipo_grado']); ?>
                                                        </span>
                                                    <?php endif; ?>

                                                    <h4 class="text-lg md:text-xl font-black text-slate-800 leading-tight">
                                                        <?php echo htmlspecialchars($item['descripcion_carrera'] ?? 'No registrado'); ?>
                                                    </h4>

                                                    <p class="text-sm text-slate-500 mt-2">
                                                        <?php echo htmlspecialchars($item['institucion'] ?? 'Institución no registrada'); ?>
                                                    </p>
                                                </div>

                                                <?php if (!empty($item['estado_validacion']) && $item['estado_validacion'] !== 'PENDIENTE'): ?>
                                                    <span class="inline-flex items-center self-start px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest <?php echo $item['estado_validacion'] === 'APROBADO'
                                                                                                                                                                                ? 'bg-green-50 text-green-700 border border-green-100'
                                                                                                                                                                                : 'bg-red-50 text-red-700 border border-red-100'; ?>">
                                                        <?php echo htmlspecialchars($item['estado_validacion']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="mt-5 flex flex-wrap gap-2.5">
                                                <?php if (!empty($item['anio_realizacion'])): ?>
                                                    <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-2xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-700">
                                                        <span class="text-slate-400 font-bold">Año</span>
                                                        <span class="text-slate-800"><?php echo htmlspecialchars($item['anio_realizacion']); ?></span>
                                                    </span>
                                                <?php endif; ?>

                                                <?php if (!empty($item['horas_lectivas'])): ?>
                                                    <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-2xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-700">
                                                        <span class="text-slate-400 font-bold">Horas</span>
                                                        <span class="text-slate-800"><?php echo htmlspecialchars($item['horas_lectivas']); ?></span>
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <?php if (!empty($item['especialidad']) || !empty($item['grado_alcanzado'])): ?>
                                                <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-3">
                                                    <?php if (!empty($item['especialidad'])): ?>
                                                        <div class="rounded-2xl bg-slate-50 border border-slate-200 px-4 py-3">
                                                            <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-1">Especialidad</p>
                                                            <p class="text-sm font-semibold text-slate-800 leading-relaxed">
                                                                <?php echo htmlspecialchars($item['especialidad']); ?>
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($item['grado_alcanzado'])): ?>
                                                        <div class="rounded-2xl bg-slate-50 border border-slate-200 px-4 py-3">
                                                            <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-1">Grado alcanzado</p>
                                                            <p class="text-sm font-semibold text-slate-800 leading-relaxed">
                                                                <?php echo htmlspecialchars($item['grado_alcanzado']); ?>
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
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
                                    value="<?php echo htmlspecialchars($perfil['correo_institucional'] ?? ''); ?>">
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
                                    <input type="number" name="sueldo" class="field-input"
                                        value="<?php echo htmlspecialchars($perfil['sueldo'] ?? ''); ?>">
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Contrato</label>
                                    <input type="text" name="mod_contrato" class="field-input"
                                        value="<?php echo htmlspecialchars($perfil['mod_contrato'] ?? ''); ?>">
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
                                        <?php foreach (['ACTIVO', 'INACTIVO', 'SUSPENDIDO', 'CESADO'] as $opt): ?>
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
                        <!-- BLOQUE 4: CONTRATOS -->
                        <div class="bg-white border border-slate-200 rounded-3xl p-6 lg:p-8 shadow-sm xl:col-span-2">
                            <div class="flex items-center justify-between mb-4 gap-3">
                                <div class="flex-1">
                                    <p class="section-title">Contratos</p>
                                    <div class="section-divider"></div>
                                </div>

                                <button type="button" onclick="agregarContrato()"
                                    class="inline-flex items-center gap-1.5 text-xs font-bold text-red-900 bg-red-50 hover:bg-red-100 border border-red-200 px-3 py-1.5 rounded-xl transition-all shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Agregar Contrato
                                </button>
                            </div>

                            <div id="lista-contratos" class="space-y-3">
                                <?php if (empty($contratos)): ?>
                                    <div id="sin-contratos" class="text-center py-5 text-slate-400 text-xs border border-dashed border-slate-200 rounded-2xl bg-slate-50/70">
                                        No hay contratos registrados. Haz clic en "Agregar Contrato".
                                    </div>
                                <?php else: ?>
                                    <?php foreach (array_values($contratos) as $ci => $contrato): ?>
                                        <div class="contrato-row bg-white border border-slate-200 rounded-xl p-4 relative transition-all" data-index="<?php echo $ci; ?>">

                                            <div class="item-resumen flex items-center justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-bold text-slate-800 val-fecha-ingreso">
                                                        <?php echo !empty($contrato['fecha_ingreso']) ? formatFecha($contrato['fecha_ingreso']) : 'Sin fecha de ingreso'; ?>
                                                    </p>
                                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                                        Cese:
                                                        <span class="val-fecha-cese">
                                                            <?php echo !empty($contrato['fecha_cese']) ? formatFecha($contrato['fecha_cese']) : 'Vigente'; ?>
                                                        </span>
                                                        • Modalidad:
                                                        <span class="val-modalidad">
                                                            <?php echo htmlspecialchars($contrato['modalidad'] ?? '—'); ?>
                                                        </span>
                                                    </p>
                                                </div>

                                                <button type="button" onclick="toggleFila(this, true)"
                                                    class="text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors border border-red-100 shrink-0">
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
                                                    <div class="field-group">
                                                        <label class="field-label">Fecha de Ingreso</label>
                                                        <input type="date"
                                                            name="contratos[<?php echo $ci; ?>][fecha_ingreso]"
                                                            class="field-input input-fecha-ingreso"
                                                            value="<?php echo htmlspecialchars($contrato['fecha_ingreso'] ?? ''); ?>">
                                                    </div>

                                                    <div class="field-group">
                                                        <label class="field-label">Fecha de Cese</label>
                                                        <input type="date"
                                                            name="contratos[<?php echo $ci; ?>][fecha_cese]"
                                                            class="field-input input-fecha-cese"
                                                            value="<?php echo htmlspecialchars($contrato['fecha_cese'] ?? ''); ?>">
                                                    </div>

                                                    <div class="field-group span-full">
                                                        <label class="field-label">Modalidad</label>
                                                        <input type="text"
                                                            name="contratos[<?php echo $ci; ?>][modalidad]"
                                                            class="field-input input-modalidad"
                                                            value="<?php echo htmlspecialchars($contrato['modalidad'] ?? ''); ?>"
                                                            placeholder="Ej: CAS">
                                                    </div>

                                                    <input type="hidden" name="contratos[<?php echo $ci; ?>][id]" value="<?php echo $contrato['id'] ?? ''; ?>">
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
    <div class="modal-footer sticky bottom-0 z-20 px-6 lg:px-8 py-5 border-t border-slate-100 flex items-center justify-between">
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
                const fechaIngreso = row.querySelector('.input-fecha-ingreso')?.value || '';
                const fechaCese = row.querySelector('.input-fecha-cese')?.value || '';
                const modalidad = row.querySelector('.input-modalidad')?.value?.trim() || '—';

                const elIngreso = row.querySelector('.val-fecha-ingreso');
                const elCese = row.querySelector('.val-fecha-cese');
                const elModalidad = row.querySelector('.val-modalidad');

                if (elIngreso) elIngreso.textContent = fechaIngreso ? formatearFecha(fechaIngreso) : 'Sin fecha de ingreso';
                if (elCese) elCese.textContent = fechaCese ? formatearFecha(fechaCese) : 'Vigente';
                if (elModalidad) elModalidad.textContent = modalidad;
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
        <div class="contrato-row bg-white border border-slate-200 rounded-xl p-4 relative animate-in" data-index="${idx}">
            <div class="item-resumen hidden flex items-center justify-between gap-3">
                <div>
                    <p class="val-fecha-ingreso text-sm font-bold text-slate-800">Sin fecha de ingreso</p>
                    <p class="text-xs text-slate-500">
                        Cese: <span class="val-fecha-cese">Vigente</span> •
                        Modalidad: <span class="val-modalidad">—</span>
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
                        <label class="field-label">Fecha de Ingreso</label>
                        <input type="date" name="contratos[${idx}][fecha_ingreso]" class="field-input input-fecha-ingreso">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Fecha de Cese</label>
                        <input type="date" name="contratos[${idx}][fecha_cese]" class="field-input input-fecha-cese">
                    </div>

                    <div class="field-group span-full">
                        <label class="field-label">Modalidad</label>
                        <input type="text" name="contratos[${idx}][modalidad]" class="field-input input-modalidad" placeholder="Ej: CAS">
                    </div>

                    <input type="hidden" name="contratos[${idx}][id]" value="">
                </div>

                <div class="mt-3 text-right">
                    <button type="button" onclick="toggleFila(this, false)" class="text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors bg-slate-100 px-3 py-1.5 rounded-lg">✓ Listo</button>
                </div>
            </div>
        </div>`;
        document.getElementById('lista-contratos').insertAdjacentHTML('beforeend', html);
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

    let pasoActual = 1;
    const totalPasos = 4;

    const gruposPasos = {
        1: [1],
        2: [3, 4],
        3: [6, 7],
        4: [9]
    };


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

        irPaso(1);
    }

    function cerrarModal() {
        const m = document.getElementById('modal-perfil');
        if (!m) return;

        m.classList.remove('modal-open');
        setTimeout(() => {
            if (m) m.classList.add('hidden');
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

        const iguales = (a, b) => JSON.stringify(a) === JSON.stringify(b);

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
        btn.textContent = 'Guardando…';
        btn.disabled = true;

        const campos = {};

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
                campos[el.name] = el.type === 'checkbox' ? (el.checked ? 1 : 0) : (el.value ?? '');
            }
        });

        campos['id'] = <?php echo (int)($perfil['id'] ?? 0); ?>;

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

            if (item.nombre || item.fecha_nacimiento || item.dni || item.id) {
                hijos.push(item);
            }
        });

        campos['hijos'] = hijos;

        const contratos = [];
        document.querySelectorAll('.contrato-row').forEach(row => {
            const idx = row.dataset.index;
            const item = {
                id: row.querySelector(`[name="contratos[${idx}][id]"]`)?.value || '',
                fecha_ingreso: row.querySelector(`[name="contratos[${idx}][fecha_ingreso]"]`)?.value || '',
                fecha_cese: row.querySelector(`[name="contratos[${idx}][fecha_cese]"]`)?.value || '',
                modalidad: row.querySelector(`[name="contratos[${idx}][modalidad]"]`)?.value?.trim() || ''
            };

            if (item.id || item.fecha_ingreso || item.fecha_cese || item.modalidad) {
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

        campos['pension'] = {
            sistema_pension: document.querySelector('[name="pension[sistema_pension]"]')?.value || '',
            afp: document.querySelector('[name="pension[afp]"]')?.value || '',
            cuspp: document.querySelector('[name="pension[cuspp]"]')?.value || '',
            tipo_comision: document.querySelector('[name="pension[tipo_comision]"]')?.value || '',
            fecha_inscripcion: document.querySelector('[name="pension[fecha_inscripcion]"]')?.value || '',
            sin_afp_afiliarme: document.querySelector('[name="pension[sin_afp_afiliarme]"]')?.checked ? 1 : 0
        };

        campos['bancario'] = {
            banco_haberes: document.querySelector('[name="bancario[banco_haberes]"]')?.value?.trim() || '',
            numero_cuenta: document.querySelector('[name="bancario[numero_cuenta]"]')?.value?.trim() || '',
            numero_cuenta_cci: document.querySelector('[name="bancario[numero_cuenta_cci]"]')?.value?.trim() || ''
        };

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
                const cleanJson = raw.trim();
                const res = JSON.parse(cleanJson);

                if (res.success) {
                    cerrarModal();
                    location.reload();
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
</script>

<?php require_once ROOT_PATH . 'Vista/includes/footer.php'; ?>