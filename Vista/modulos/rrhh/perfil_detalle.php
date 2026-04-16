<?php
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

$titulo_pagina = "Perfil: " . $data['nombres_apellidos'];
require_once ROOT_PATH . 'Vista/includes/header.php';
require_once ROOT_PATH . 'Vista/includes/sidebar.php';
?>

<main class="flex-1 bg-slate-50 overflow-y-auto pb-20">
    <div class="h-48 bg-gradient-to-r from-slate-900 to-indigo-900 shadow-lg"></div>

    <div class="max-w-6xl mx-auto px-6">
        <div class="relative -mt-24">
            <div class="bg-white rounded-3xl shadow-xl p-8 border border-slate-200 mb-8">
                <div class="flex flex-col md:flex-row items-center gap-8">
                    <div class="h-40 w-40 rounded-3xl bg-indigo-600 flex items-center justify-center text-6xl font-black text-white shadow-2xl ring-8 ring-white">
                        <?php echo substr($data['nombres_apellidos'], 0, 1); ?>
                    </div>
                    
                    <div class="flex-1 text-center md:text-left">
                        <div class="flex flex-col md:flex-row md:items-center gap-4 mb-2">
                            <h1 class="text-4xl font-black text-slate-800 tracking-tight"><?php echo $data['nombres_apellidos']; ?></h1>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?php echo ($data['situacion'] == 'Baja') ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'; ?>">
                                ● <?php echo $data['situacion'] ?? 'Activo'; ?>
                            </span>
                        </div>
                        <div class="flex flex-wrap justify-center md:justify-start gap-3">
                            <span class="bg-indigo-50 text-indigo-700 px-4 py-1.5 rounded-xl text-xs font-black uppercase tracking-widest border border-indigo-100">
                                <?php echo $data['puesto_cas']; ?>
                            </span>
                            <span class="bg-slate-50 text-slate-500 px-4 py-1.5 rounded-xl text-xs font-black uppercase tracking-widest border border-slate-200">
                                <?php echo $data['area']; ?>
                            </span>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button class="bg-slate-800 text-white px-6 py-3 rounded-2xl font-bold shadow-lg hover:bg-slate-700 transition">Editar Perfil</button>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 mt-10 pt-10 border-t border-slate-100">
                    <div class="p-4 bg-slate-50 rounded-2xl">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-1">DNI</p>
                        <p class="font-bold text-slate-700"><?php echo $data['dni']; ?></p>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-2xl">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-1">NSA - CIP</p>
                        <p class="font-bold text-slate-700"><?php echo $data['nsa_cip'] ?: '---'; ?></p>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-2xl">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Sueldo</p>
                        <p class="font-bold text-indigo-600">S/ <?php echo number_format($data['sueldo'], 2); ?></p>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-2xl">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Ingreso</p>
                        <p class="font-bold text-slate-700"><?php echo $data['fecha_ingreso']; ?></p>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-2xl">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Edad</p>
                        <p class="font-bold text-slate-700"><?php echo $data['edad']; ?> años</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2 space-y-8">
                    
                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
                        <h3 class="text-xl font-black text-slate-800 mb-6 flex items-center gap-2">
                            <span class="w-1.5 h-6 bg-indigo-600 rounded-full"></span>
                            Información Personal Detallada
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-6">
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-slate-400 font-medium">Lugar de Nacimiento</span>
                                <span class="text-slate-700 font-bold"><?php echo $data['lugar_nacimiento']; ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-slate-400 font-medium">Fecha Nacimiento</span>
                                <span class="text-slate-700 font-bold"><?php echo $data['fecha_nacimiento']; ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-slate-400 font-medium">Estado Civil</span>
                                <span class="text-slate-700 font-bold"><?php echo $data['estado_civil']; ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-slate-400 font-medium">Sexo</span>
                                <span class="text-slate-700 font-bold"><?php echo ($data['sexo'] == 'M') ? 'Masculino' : 'Femenino'; ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-slate-400 font-medium">Grupo Sanguíneo</span>
                                <span class="text-slate-700 font-bold"><?php echo $data['grupo_sanguineo']; ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-slate-400 font-medium">Talla</span>
                                <span class="text-slate-700 font-bold"><?php echo $data['talla']; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
                        <h3 class="text-xl font-black text-slate-800 mb-6 flex items-center gap-2">
                            <span class="w-1.5 h-6 bg-blue-500 rounded-full"></span>
                            Contacto y Domicilio
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-start gap-4 p-4 bg-slate-50 rounded-2xl">
                                <div class="p-3 bg-white rounded-xl shadow-sm">📍</div>
                                <div>
                                    <p class="text-xs font-black text-slate-400 uppercase">Dirección de Residencia</p>
                                    <p class="font-bold text-slate-700"><?php echo $data['direccion_residencia']; ?></p>
                                    <p class="text-sm text-slate-500"><?php echo $data['distrito']; ?></p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl">
                                    <div class="p-3 bg-white rounded-xl shadow-sm">📞</div>
                                    <div>
                                        <p class="text-xs font-black text-slate-400 uppercase">Celular</p>
                                        <p class="font-bold text-slate-700"><?php echo $data['celular']; ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl">
                                    <div class="p-3 bg-white rounded-xl shadow-sm">✉️</div>
                                    <div>
                                        <p class="text-xs font-black text-slate-400 uppercase">Correo Institucional</p>
                                        <p class="font-bold text-slate-700"><?php echo $data['correo_institucional']; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-8">
                    
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                        <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                            <span class="w-1.5 h-5 bg-pink-500 rounded-full"></span>
                            Cónyuge y Familia
                        </h3>
                        <?php if($data['conyuge']): ?>
                            <div class="p-4 bg-pink-50 rounded-2xl border border-pink-100 mb-4">
                                <p class="text-[10px] font-black text-pink-400 uppercase mb-1">Cónyuge</p>
                                <p class="font-bold text-pink-900"><?php echo $data['conyuge']; ?></p>
                                <p class="text-xs text-pink-600">Onomástico: <?php echo $data['onomastico_conyuge'] ?: 'No registrado'; ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl">
                            <span class="text-sm font-bold text-slate-500">Número de Hijos</span>
                            <span class="h-8 w-8 bg-white rounded-lg flex items-center justify-center font-black text-indigo-600 shadow-sm border">
                                <?php echo $data['n_hijos'] ?? '0'; ?>
                            </span>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                        <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                            <span class="w-1.5 h-5 bg-orange-500 rounded-full"></span>
                            Datos CAS
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase">Procedencia</p>
                                <p class="text-sm font-bold text-slate-700"><?php echo $data['procedencia']; ?></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase">Mod. Contrato</p>
                                <p class="text-sm font-bold text-slate-700"><?php echo $data['mod_contrato']; ?></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase">Tipo de Puesto</p>
                                <p class="text-sm font-bold text-slate-700"><?php echo $data['tipo_puesto']; ?></p>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</main>

<?php require_once ROOT_PATH . 'Vista/includes/footer.php'; ?>