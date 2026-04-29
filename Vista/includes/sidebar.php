<?php
// /Vista/includes/sidebar.php
function tieneAcceso($modulo)
{
    if (!isset($_SESSION['user_role'])) return false;

    $rol = strtolower(trim($_SESSION['user_role']));
    $modulo = strtolower(trim($modulo));

    if ($modulo === 'misvalidaciones' && $rol !== 'colaborador') {
        return false;
    }

    if ($rol === 'superadmin') return true;

    $permisos = check_access($modulo, $_SESSION['user_role']);
    return !empty($permisos['can_view']);
}

// Accesos
$accesoRRHH = tieneAcceso('rrhh');
$accesoConfig = tieneAcceso('configuracion');
$accesoPerfil = tieneAcceso('perfil');
$accesoDocs = tieneAcceso('documentos');
$accesoMisValidaciones = tieneAcceso('misvalidaciones');
$accesoContratos = tieneAcceso('contratos');

// COMPONENTE ITEM PREMIUM
function itemSidebar($ruta, $icono, $texto, $activo)
{
    $base = "flex items-center gap-3 px-4 py-3 mx-3 rounded-2xl transition-all duration-200 group";

    $estado = $activo
        ? "bg-gradient-to-r from-[#7A0C19] to-[#a0142a] text-white shadow-lg shadow-red-900/30"
        : "text-red-100/70 hover:bg-white/10 backdrop-blur-sm shadow-inner hover:text-white";

    return "
    <a href='{$ruta}' class='{$base} {$estado}'>
        <div class='w-9 h-9 flex items-center justify-center rounded-xl bg-white/10 backdrop-blur-sm shadow-inner group-hover:bg-white/10 transition'>
            {$icono}
        </div>
        <span class='text-sm font-semibold tracking-wide'>{$texto}</span>
    </a>";
}
?>

<!-- MOBILE HEADER -->
<div class="md:hidden flex items-center justify-between bg-gradient-to-r from-[#1a0505] to-[#0d0202] p-4 border-b border-red-950/50">
    <div class="flex items-center">
        <div class="w-10 h-10 bg-gradient-to-br from-[#7A0C19] to-[#a0142a] rounded-xl flex items-center justify-center shadow-lg">
            <span class="text-white font-bold text-lg">P</span>
        </div>
        <span class="ml-3 text-lg font-bold text-white">RRHH<span class="text-red-400">Panel</span></span>
    </div>
    <button id="btn-menu" class="text-white">
        ☰
    </button>
</div>

<div id="sidebar-overlay" class="fixed inset-0 bg-black/60 hidden z-30"></div>

<aside id="sidebar"
    class="w-64 bg-gradient-to-b from-[#1a0505] via-[#140303] to-[#0d0202] text-white flex flex-col fixed md:relative h-full z-40 transform -translate-x-full md:translate-x-0 transition">

    <!-- HEADER -->
    <div class="h-20 flex items-center px-6 border-b border-white/5">
        <div class="w-10 h-10 bg-gradient-to-br from-[#7A0C19] to-[#a0142a] rounded-xl flex items-center justify-center shadow-lg">
            <span class="text-white font-bold">P</span>
        </div>
        <span class="ml-3 text-lg font-bold">RRHH<span class="text-red-400">Panel</span></span>
    </div>

    <!-- NAV -->
    <nav class="flex-1 overflow-y-auto py-6 custom-scrollbar">

        <!-- MI ESPACIO -->
        <?php if ($accesoPerfil || $accesoDocs || $accesoMisValidaciones): ?>
            <div class="text-[10px] font-bold text-white/30 uppercase tracking-widest px-6 mb-3">
                Mi Espacio
            </div>

            <?php if ($accesoPerfil): ?>
                <?= itemSidebar(BASE_URL . "/perfil",
                    '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"> <path d="M12 12c2.7 0 5-2.69 5-6s-2.3-6-5-6-5 2.69-5 6 2.3 6 5 6zm0 2c-3.33 0-10 1.67-10 5v3h20v-3c0-3.33-6.67-5-10-5z"/> </svg>',
                    "Mi Perfil",
                    $menu_activo == 'perfil'
                ) ?>
            <?php endif; ?>

            <?php if ($accesoMisValidaciones): ?>
                <?= itemSidebar(BASE_URL . "/misvalidaciones",
                    '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"> <path d="M9 12l2 2 4-4m1-7H8a2 2 0 00-2 2v14l6-3 6 3V5a2 2 0 00-2-2z"/> </svg>',
                    "Mis Validaciones",
                    $menu_activo == 'misvalidaciones'
                ) ?>
            <?php endif; ?>

            <?php if ($accesoDocs): ?>
                <?= itemSidebar(BASE_URL . "/documentos",
                    '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"> <path d="M14 2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2V8l-6-6z"/> </svg>',
                    "Documentos",
                    $menu_activo == 'documentos'
                ) ?>
            <?php endif; ?>
        <?php endif; ?>


        <!-- ADMIN -->
        <?php if ($accesoRRHH || $accesoContratos): ?>
            <div class="text-[10px] font-bold text-white/30 uppercase tracking-widest px-6 mt-8 mb-3">
                Administración
            </div>

            <?= itemSidebar(BASE_URL . "/rrhh/dashboard",
                '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"> <path d="M3 13h8V3H3v10zm10 8h8V3h-8v18zM3 21h8v-6H3v6z"/> </svg>',
                "Dashboard",
                $menu_activo == 'dashboard'
            ) ?>

            <?= itemSidebar(BASE_URL . "/rrhh/validaciones",
                '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"> <path d="M9 12l2 2 4-4m1-7H8a2 2 0 00-2 2v14l6-3 6 3V5a2 2 0 00-2-2z"/> </svg>',
                "Validaciones",
                $menu_activo == 'validaciones'
            ) ?>

            <?= itemSidebar(BASE_URL . "/rrhh/directorio",
                '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"> <path d="M16 11c1.657 0 3-1.79 3-4s-1.343-4-3-4-3 1.79-3 4 1.343 4 3 4zM8 11c1.657 0 3-1.79 3-4S9.657 3 8 3 5 4.79 5 7s1.343 4 3 4zm0 2c-2.67 0-8 1.34-8 4v3h16v-3c0-2.66-5.33-4-8-4zm8 0c-.29 0-.62.02-.97.05 1.37.98 2.97 2.44 2.97 3.95v3h6v-3c0-2.66-5.33-4-8-4z"/> </svg>',
                "Directorio",
                $menu_activo == 'directorio'
            ) ?>
        <?php endif; ?>

        <?php if ($accesoContratos): ?>
            <?= itemSidebar(BASE_URL . "/rrhh/contratos",
                '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"> <path d="M6 2a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2V4a2 2 0 00-2-2H6z"/> </svg>',
                "Contratos",
                $menu_activo == 'contratos'
            ) ?>
        <?php endif; ?>

        <!-- CONFIG -->
        <?php if ($accesoConfig): ?>
            <div class="text-[10px] font-bold text-white/30 uppercase tracking-widest px-6 mt-8 mb-3">
                Configuración
            </div>

            <?= itemSidebar(BASE_URL . "/configuracion",
                '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"> <path d="M19.14 12.94a7.49 7.49 0 000-1.88l2.03-1.58a.5.5 0 00.12-.65l-1.92-3.32a.5.5 0 00-.6-.22l-2.39.96a7.28 7.28 0 00-1.63-.94l-.36-2.54A.5.5 0 0013.9 1h-3.8a.5.5 0 00-.5.42l-.36 2.54c-.58.23-1.13.54-1.63.94l-2.39-.96a.5.5 0 00-.6.22L2.7 7.48a.5.5 0 00.12.65l2.03 1.58a7.49 7.49 0 000 1.88L2.82 13.17a.5.5 0 00-.12.65l1.92 3.32c.14.24.43.34.68.22l2.39-.96c.5.4 1.05.72 1.63.94l.36 2.54c.04.24.25.42.5.42h3.8c.25 0 .46-.18.5-.42l.36-2.54c.58-.23 1.13-.54 1.63-.94l2.39.96c.25.12.54.02.68-.22l1.92-3.32a.5.5 0 00-.12-.65l-2.03-1.58zM12 15a3 3 0 110-6 3 3 0 010 6z"/> </svg>',
                "Permisos",
                $menu_activo == 'configuracion'
            ) ?>
        <?php endif; ?>

    </nav>

    <!-- USER -->
    <a href="<?= BASE_URL ?>/logout" class="mx-3 mb-4 p-3 rounded-2xl bg-white/10 backdrop-blur-sm shadow-inner hover:bg-white/10 transition flex items-center gap-3">

        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username'] ?? 'Usuario') ?>&background=7A0C19&color=fff"
            class="w-10 h-10 rounded-xl">

        <div class="flex-1 overflow-hidden">
            <p class="text-sm font-bold truncate"><?= $_SESSION['nombre_completo'] ?></p>
            <p class="text-[10px] text-white/40 uppercase"><?= $_SESSION['user_role'] ?></p>
        </div>

        <span class="text-white/40">→</span>
    </a>

</aside>

<style>
.custom-scrollbar::-webkit-scrollbar { display: none; }
.custom-scrollbar { scrollbar-width: none; }
</style>

<script>
(function(){
    const btnMenu = document.getElementById("btn-menu");
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("sidebar-overlay");

    if(btnMenu){
        btnMenu.onclick = () => {
            sidebar.classList.remove("-translate-x-full");
            overlay.classList.remove("hidden");
        }
    }

    if(overlay){
        overlay.onclick = () => {
            sidebar.classList.add("-translate-x-full");
            overlay.classList.add("hidden");
        }
    }
})();
</script>