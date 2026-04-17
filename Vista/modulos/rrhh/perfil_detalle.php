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
            <div class="bg-white rounded-3xl shadow-xl p-8 border border-slate-200 mb-6">
                <div class="flex flex-col md:flex-row items-center gap-8">
                    <div class="h-36 w-36 rounded-3xl bg-indigo-600 flex items-center justify-center text-5xl font-black text-white shadow-2xl ring-8 ring-white shrink-0">
                        <?php echo substr($data['nombres_apellidos'], 0, 1); ?>
                    </div>
                    
                    <div class="flex-1 text-center md:text-left">
                        <h1 class="text-3xl font-black text-slate-800 tracking-tight mb-2"><?php echo $data['nombres_apellidos']; ?></h1>
                        <div class="flex flex-wrap justify-center md:justify-start gap-2">
                            <span class="bg-indigo-50 text-indigo-700 px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-widest border border-indigo-100">
                                <?php echo $data['puesto_cas']; ?>
                            </span>
                            <span class="bg-slate-50 text-slate-500 px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-widest border border-slate-200">
                                <?php echo $data['area']; ?>
                            </span>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button class="bg-slate-800 text-white px-6 py-3 rounded-2xl font-bold hover:bg-slate-700 transition shadow-lg">Editar</button>
                    </div>
                </div>

                <div class="flex items-center gap-8 mt-10 border-t border-slate-100 pt-2">
                    <button onclick="showTab('resumen')" class="tab-btn active-tab px-2 py-4 text-sm font-bold transition-all relative" id="btn-resumen">
                        RESUMEN PERFIL
                    </button>
                    <button onclick="showTab('formacion')" class="tab-btn inactive-tab px-2 py-4 text-sm font-bold transition-all relative" id="btn-formacion">
                        FORMACIÓN ACADÉMICA
                    </button>
                    <button onclick="showTab('experiencia')" class="tab-btn inactive-tab px-2 py-4 text-sm font-bold transition-all relative" id="btn-experiencia">
                        EXPERIENCIA LABORAL
                    </button>
                </div>
            </div>

            <div id="tab-resumen" class="tab-content block animate-fadeIn">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
                            <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                <span class="w-1.5 h-5 bg-indigo-600 rounded-full"></span> Datos Personales
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div class="flex justify-between border-b pb-2"><span class="text-slate-400">DNI</span><span class="font-bold"><?php echo $data['dni']; ?></span></div>
                                    <div class="flex justify-between border-b pb-2"><span class="text-slate-400">Edad</span><span class="font-bold"><?php echo $data['edad']; ?> años</span></div>
                                    <div class="flex justify-between border-b pb-2"><span class="text-slate-400">Estado Civil</span><span class="font-bold"><?php echo $data['estado_civil']; ?></span></div>
                                </div>
                                <div class="space-y-4">
                                    <div class="flex justify-between border-b pb-2"><span class="text-slate-400">Grupo Sanguíneo</span><span class="font-bold"><?php echo $data['grupo_sanguineo']; ?></span></div>
                                    <div class="flex justify-between border-b pb-2"><span class="text-slate-400">Talla</span><span class="font-bold"><?php echo $data['talla']; ?></span></div>
                                    <div class="flex justify-between border-b pb-2"><span class="text-slate-400">Fecha Nac.</span><span class="font-bold"><?php echo $data['fecha_nacimiento']; ?></span></div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
                            <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                <span class="w-1.5 h-5 bg-blue-500 rounded-full"></span> Contacto y Domicilio
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="p-4 bg-slate-50 rounded-2xl">
                                    <p class="text-[10px] font-black text-slate-400 uppercase">Dirección</p>
                                    <p class="font-bold text-slate-700 leading-tight"><?php echo $data['direccion_residencia']; ?></p>
                                    <p class="text-xs text-slate-500"><?php echo $data['distrito']; ?></p>
                                </div>
                                <div class="space-y-2">
                                    <div class="p-3 bg-slate-50 rounded-xl flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-400">CELULAR</span>
                                        <span class="font-bold text-slate-700"><?php echo $data['celular']; ?></span>
                                    </div>
                                    <div class="p-3 bg-slate-50 rounded-xl flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-400">CORREO INST.</span>
                                        <span class="font-bold text-slate-700 text-xs"><?php echo $data['correo_institucional']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                            <h3 class="text-md font-black text-slate-800 mb-4 flex items-center gap-2">
                                <span class="w-1.5 h-4 bg-pink-500 rounded-full"></span> Familia
                            </h3>
                            <div class="p-4 bg-pink-50 rounded-2xl mb-3">
                                <p class="text-[10px] font-bold text-pink-400 uppercase">Cónyuge</p>
                                <p class="font-black text-pink-900"><?php echo $data['conyuge'] ?: 'No registrado'; ?></p>
                            </div>
                            <div class="flex justify-between items-center p-4 bg-slate-50 rounded-2xl text-sm font-bold">
                                <span class="text-slate-500">Hijos registrados</span>
                                <span class="text-indigo-600 text-lg"><?php echo $data['n_hijos'] ?? '0'; ?></span>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                            <h3 class="text-md font-black text-slate-800 mb-4 flex items-center gap-2">
                                <span class="w-1.5 h-4 bg-orange-500 rounded-full"></span> Datos CAS
                            </h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between"><span class="text-slate-400">Sueldo</span><span class="font-bold text-indigo-600">S/ <?php echo number_format($data['sueldo'], 2); ?></span></div>
                                <div class="flex justify-between"><span class="text-slate-400">Ingreso</span><span class="font-bold"><?php echo $data['fecha_ingreso']; ?></span></div>
                                <div class="flex justify-between"><span class="text-slate-400">Contrato</span><span class="font-bold"><?php echo $data['mod_contrato']; ?></span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="tab-formacion" class="tab-content hidden animate-fadeIn">
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
                    <h3 class="text-xl font-black text-slate-800 mb-6">Historial Académico</h3>
                    <div class="space-y-6">
                        <div class="relative pl-8 border-l-2 border-indigo-100 space-y-8">
                            <div class="relative">
                                <div class="absolute -left-[41px] top-0 w-5 h-5 rounded-full bg-indigo-600 border-4 border-white shadow-sm"></div>
                                <p class="text-xs font-black text-indigo-600 uppercase">Grado Principal</p>
                                <h4 class="text-lg font-bold text-slate-800"><?php echo $data['profesion'] ?? 'Verificar en Base de Datos'; ?></h4>
                                <p class="text-slate-500 italic"><?php echo $data['institucion'] ?? 'Institución no registrada'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="tab-experiencia" class="tab-content hidden animate-fadeIn">
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 text-center py-20">
                    <div class="text-5xl mb-4">💼</div>
                    <h3 class="text-xl font-black text-slate-800">Experiencia Laboral</h3>
                    <p class="text-slate-500">Esta sección se carga desde la tabla de trayectoria profesional.</p>
                </div>
            </div>

        </div>
    </div>
</main>

<style>
    .active-tab { color: #4f46e5; }
    .active-tab::after { content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 3px; background: #4f46e5; border-radius: 10px; }
    .inactive-tab { color: #94a3b8; }
    .inactive-tab:hover { color: #64748b; }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeIn { animation: fadeIn 0.3s ease-out forwards; }
</style>

<script>
function showTab(tabId) {
    // Ocultar todos los contenidos
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
        content.classList.remove('block');
    });

    // Quitar estilos activos de todos los botones
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active-tab');
        btn.classList.add('inactive-tab');
    });

    // Mostrar el seleccionado
    const selectedTab = document.getElementById('tab-' + tabId);
    selectedTab.classList.remove('hidden');
    selectedTab.classList.add('block');

    // Activar botón
    const selectedBtn = document.getElementById('btn-' + tabId);
    selectedBtn.classList.add('active-tab');
    selectedBtn.classList.remove('inactive-tab');
}
</script>

<?php require_once ROOT_PATH . 'Vista/includes/footer.php'; ?>