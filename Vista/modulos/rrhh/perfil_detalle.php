<?php
// /Vista/modulos/rrhh/perfil_detalle.php

// 1. Carga de Configuración y Rutas (Ajustado a Controlador/ y Modelo/)
if (!defined('ROOT_PATH')) {
    require_once __DIR__ . '/../../../Config/config.php';
}

require_once ROOT_PATH . 'Controlador/CtrDirectorio.php';
require_once ROOT_PATH . 'Modelo/MdDirectorio.php';

// 2. Captura del ID desde la URL (rrhh/perfil/1)
$url_params = explode('/', trim($_GET['url'], '/'));
$id_colaborador = $url_params[2] ?? null; 

if (!$id_colaborador) {
    header("Location: " . BASE_URL . "/rrhh/directorio");
    exit;
}

// 3. Uso del Controlador
$controlador = new CtrDirectorio();
$data = $controlador->ctrVerPerfil($id_colaborador);

// 4. Verificación de Datos (Evita el error de "offset on value of type bool")
if (!$data) {
    echo "<div style='padding:50px; text-align:center; font-family:sans-serif;'>
            <h1 style='color:#ef4444;'>404 - Colaborador no encontrado</h1>
            <p>El ID <b>{$id_colaborador}</b> no existe en el Directorio.</p>
            <a href='".BASE_URL."/rrhh/directorio' style='color:#4f46e5; font-weight:bold;'>Volver al Directorio</a>
          </div>";
    exit;
}

$titulo_pagina = "Perfil: " . $data['nombres_apellidos'];
require_once ROOT_PATH . 'Vista/includes/header.php';
require_once ROOT_PATH . 'Vista/includes/sidebar.php';
?>

<main class="flex-1 bg-slate-50 overflow-y-auto">
    <div class="h-40 bg-gradient-to-r from-slate-800 to-indigo-900 shadow-lg"></div>

    <div class="max-w-5xl mx-auto px-6">
        <div class="relative -mt-20">
            <div class="bg-white rounded-3xl shadow-xl p-8 border border-slate-200">
                <div class="flex flex-col md:flex-row items-center gap-8">
                    <div class="h-32 w-32 rounded-3xl bg-indigo-600 flex items-center justify-center text-5xl font-black text-white shadow-2xl ring-8 ring-white">
                        <?php echo substr($data['nombres_apellidos'], 0, 1); ?>
                    </div>
                    
                    <div class="flex-1 text-center md:text-left">
                        <h1 class="text-4xl font-black text-slate-800 tracking-tight"><?php echo $data['nombres_apellidos']; ?></h1>
                        <div class="flex flex-wrap justify-center md:justify-start gap-3 mt-3">
                            <span class="bg-indigo-100 text-indigo-700 px-4 py-1.5 rounded-xl text-xs font-black uppercase tracking-widest border border-indigo-200">
                                <?php echo $data['puesto_cas']; ?>
                            </span>
                            <span class="bg-slate-100 text-slate-500 px-4 py-1.5 rounded-xl text-xs font-black uppercase tracking-widest border border-slate-200">
                                <?php echo $data['area']; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-10 pt-10 border-t border-slate-100">
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-1">DNI / Documento</p>
                        <p class="text-lg font-bold text-slate-700"><?php echo $data['dni']; ?></p>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-1">NSA - CIP</p>
                        <p class="text-lg font-bold text-slate-700"><?php echo $data['nsa_cip'] ?: '---'; ?></p>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Grupo Sanguíneo</p>
                        <p class="text-lg font-bold text-slate-700"><?php echo $data['grupo_sanguineo'] ?: 'N/A'; ?></p>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Talla</p>
                        <p class="text-lg font-bold text-slate-700"><?php echo $data['talla'] ?: 'S/T'; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once ROOT_PATH . 'Vista/includes/footer.php'; ?>