<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Iniciar Sesión - RRHH</title>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-900 h-screen flex">

    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-indigo-900 via-indigo-800 to-blue-900 items-center justify-center relative overflow-hidden">
        <div class="absolute top-0 left-0 w-96 h-96 bg-white opacity-5 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-blue-500 opacity-10 rounded-full translate-x-1/3 translate-y-1/3"></div>
        
        <div class="relative z-10 p-12 text-center">
            <div class="w-20 h-20 bg-white rounded-2xl mx-auto mb-8 flex items-center justify-center shadow-2xl shadow-indigo-900/50">
                <span class="text-3xl font-black text-indigo-600">HR</span>
            </div>
            <h1 class="text-4xl font-extrabold text-white mb-4">Portal del Colaborador</h1>
            <p class="text-indigo-200 text-lg max-w-md mx-auto">Gestiona tu información personal, revisa tus documentos y mantén tu perfil profesional actualizado en un solo lugar.</p>
        </div>
    </div>

    <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-white">
        <div class="w-full max-w-md">
            
            <div class="lg:hidden w-16 h-16 bg-indigo-600 rounded-2xl mb-8 flex items-center justify-center shadow-lg">
                <span class="text-2xl font-black text-white">HR</span>
            </div>

            <h2 class="text-3xl font-bold text-slate-800 mb-2">Bienvenido de nuevo</h2>
            <p class="text-slate-500 mb-8">Ingresa tus credenciales para acceder a tu cuenta.</p>

            <?php if(isset($_GET['error']) && $_GET['error'] == 'no_access'): ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                    <p class="text-sm text-red-700 font-medium">Debes iniciar sesión para acceder a esa página.</p>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>/login" method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Usuario (DNI)</label>
                    <input type="text" name="dni" required 
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors outline-none" 
                           placeholder="Ingresa tu número de DNI">
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-bold text-slate-700">Contraseña</label>
                        <a href="#" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">¿Olvidaste tu contraseña?</a>
                    </div>
                    <input type="password" name="password" required 
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors outline-none" 
                           placeholder="••••••••">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" id="remember" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                    <label for="remember" class="ml-2 text-sm text-slate-600 cursor-pointer">Recordar mis datos</label>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white font-bold rounded-xl py-3 px-4 hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all active:scale-[0.98]">
                    Ingresar al Sistema
                </button>
            </form>

        </div>
    </div>

</body>
</html>