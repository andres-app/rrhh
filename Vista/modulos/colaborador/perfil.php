<?php
// 1. Asegurar que el modelo esté cargado
require_once __DIR__ . '/../../../Modelo/MdDirectorio.php';

// 2. Si $datos no viene del controlador, lo obtenemos aquí mismo
if (!isset($datos) || empty($datos)) {
    $idUsuario = $_SESSION['user_id'] ?? null;
    if ($idUsuario) {
        $datos = MdDirectorio::mdlObtenerPerfilCompleto($idUsuario);
    }
}

// 3. Si sigue siendo null, inicializamos un array vacío para evitar los Warnings
$perfil = $datos ? $datos : [
    'nombres_apellidos' => 'Usuario no encontrado',
    'puesto_cas' => '---',
    'area' => '---',
    'dni' => '---',
    'correo_institucional' => '---',
    'celular' => '---',
    'n_hijos' => '0'
];

$titulo_pagina = "Mi Perfil | Portal del Colaborador";
$menu_activo = "perfil";

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="flex-1 flex flex-col h-screen overflow-y-auto bg-slate-50">
    <div class="bg-white rounded-3xl shadow-sm overflow-hidden m-8 border border-slate-200">
        <div class="h-40 bg-gradient-to-r from-[#4c0505] to-red-900"></div>
        
        <div class="px-8 pb-8 flex flex-col md:flex-row items-end -mt-16 space-y-4 md:space-y-0 md:space-x-6">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($perfil['nombres_apellidos']); ?>&size=160&background=880808&color=fff" 
                 class="w-32 h-32 rounded-2xl border-4 border-white shadow-xl bg-white object-cover">
            
            <div class="flex-1">
                <h1 class="text-3xl font-extrabold text-slate-800"><?php echo $perfil['nombres_apellidos']; ?></h1>
                <p class="text-red-800 font-semibold flex items-center">
                    <span class="w-2 h-2 bg-red-600 rounded-full mr-2"></span>
                    <?php echo $perfil['puesto_cas']; ?> | <?php echo $perfil['area']; ?>
                </p>
            </div>
            
            <button class="bg-red-900 text-white px-6 py-3 rounded-2xl font-bold shadow-lg hover:bg-[#4c0505] transition-all">
                Actualizar Información
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 px-8 pb-8">
        <section class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em] mb-6">Información Personal</h3>
            <div class="space-y-4">
                <div class="flex justify-between border-b border-slate-50 pb-3"> 
                    <span class="text-slate-500 text-sm">DNI</span> 
                    <span class="font-bold text-slate-800"><?php echo $perfil['dni']; ?></span> 
                </div>
                <div class="flex justify-between border-b border-slate-50 pb-3"> 
                    <span class="text-slate-500 text-sm">Correo</span> 
                    <span class="font-bold text-slate-800 text-sm"><?php echo $perfil['correo_institucional']; ?></span> 
                </div>
                <div class="flex justify-between"> 
                    <span class="text-slate-500 text-sm">Celular</span> 
                    <span class="font-bold text-slate-800"><?php echo $perfil['celular']; ?></span> 
                </div>
            </div>
        </section>
        
        <section class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em] mb-6">Carga Familiar</h3>
            <div class="space-y-4">
                <div class="flex justify-between border-b border-slate-50 pb-3"> 
                    <span class="text-slate-500 text-sm">Cónyuge</span> 
                    <span class="font-bold text-slate-800"><?php echo $perfil['conyuge'] ?? 'No registrado'; ?></span> 
                </div>
                <div class="flex justify-between"> 
                    <span class="text-slate-500 text-sm">Número de Hijos</span> 
                    <span class="font-bold text-slate-800"><?php echo $perfil['n_hijos']; ?></span> 
                </div>
            </div>
        </section>
    </div>
</main>