<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Iniciar Sesión - RRHH</title>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-900 h-screen flex">

    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-[#310404] via-[#4c0505] to-red-900 items-center justify-center relative overflow-hidden">
        <div class="absolute top-0 left-0 w-96 h-96 bg-white opacity-5 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-red-500 opacity-10 rounded-full translate-x-1/3 translate-y-1/3"></div>
        
        <div class="relative z-10 p-12 text-center">
            <div class="w-20 h-20 bg-white rounded-2xl mx-auto mb-8 flex items-center justify-center shadow-2xl shadow-black/50">
                <span class="text-3xl font-black text-red-900">HR</span>
            </div>
            <h1 class="text-4xl font-extrabold text-white mb-4 tracking-tight">Portal del Colaborador</h1>
            <p class="text-red-200/80 text-lg max-w-md mx-auto font-medium">Gestiona tu información personal y mantén tu perfil actualizado.</p>
        </div>
    </div>

    <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-white">
        <div class="w-full max-w-md">
            
            <h2 class="text-3xl font-bold text-slate-800 mb-2 tracking-tight">Bienvenido de nuevo</h2>
            <p class="text-slate-500 mb-8">Ingresa tus credenciales para acceder.</p>

            <?php if(isset($_GET['error']) && $_GET['error'] == 'no_access'): ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-600 p-4 rounded-r-lg">
                    <p class="text-sm text-red-800 font-bold">Debes iniciar sesión para acceder.</p>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wide">Usuario (DNI)</label>
                    <input type="text" name="login_username" required 
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-red-900/20 focus:border-red-900 transition-all outline-none" 
                           placeholder="Ingresa tu número de DNI">
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-bold text-slate-700 uppercase tracking-wide">Contraseña</label>
                    </div>
                    <input type="password" name="login_password" required 
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-red-900/20 focus:border-red-900 transition-all outline-none" 
                           placeholder="••••••••">
                </div>

                <button type="submit" class="w-full bg-red-900 text-white font-bold rounded-xl py-4 px-4 hover:bg-[#310404] shadow-xl shadow-red-900/20 transition-all active:scale-[0.98] tracking-wide">
                    Ingresar al Sistema
                </button>

                <?php
                    // EJECUCIÓN DEL CONTROLADOR
                    $login = new CtrUsuario();
                    $login->ctrLogin();
                ?>
            </form>

            <p class="mt-8 text-center text-xs text-slate-400 font-medium uppercase tracking-[0.2em]">
                &copy; <?= date('Y') ?> Recursos Humanos
            </p>
        </div>
    </div>

</body>
</html>