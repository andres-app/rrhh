<?php
// 1. IMPORTACIÓN SEGURA (Ya no usas ../../../)
require_once ROOT_PATH . 'Controlador/CtrDirectorio.php';
require_once ROOT_PATH . 'Modelo/MdDirectorio.php';

// 2. Instancia del controlador
$controlador = new CtrDirectorio();
$empleados = $controlador->ctrMostrarDirectorio();

// 3. Variables de diseño
$titulo_pagina = "Directorio de Personal - RRHH";
$menu_activo = "directorio";

require_once ROOT_PATH . 'Vista/includes/header.php';
require_once ROOT_PATH . 'Vista/includes/sidebar.php';
?>

<main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">

    <header class="h-20 bg-white shadow-sm flex items-center px-8 justify-between z-10">
        <h1 class="text-2xl font-bold text-slate-800">Directorio de Personal</h1>

        <div class="flex-1 max-w-md mx-8">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" id="searchInput" onkeyup="filterTable()"
                    class="block w-full pl-10 pr-3 py-2 border border-slate-200 rounded-xl leading-5 bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition"
                    placeholder="Buscar por nombre o DNI...">
            </div>
        </div>

        <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-bold shadow-md transition flex items-center">
            <span class="mr-2">+</span> Nuevo Empleado
        </button>
    </header>

    <div class="p-8 flex-1 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Colaborador</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Puesto / Área</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Contacto</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <?php foreach ($empleados as $row): ?>
                        <tr class="hover:bg-slate-50/80 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold mr-3">
                                        <?php echo substr($row['nombres_apellidos'], 0, 1); ?>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-slate-900"><?php echo $row['nombres_apellidos']; ?></div>
                                        <div class="text-xs text-slate-500">DNI: <?php echo $row['dni']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-slate-700 font-medium"><?php echo $row['puesto_cas']; ?></div>
                                <div class="text-xs text-slate-400"><?php echo $row['area']; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                <div class="flex flex-col">
                                    <span class="flex items-center mb-1">
                                        <svg class="w-3.5 h-3.5 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <?php echo $row['correo_institucional']; ?>
                                    </span>
                                    <span class="flex items-center">
                                        <svg class="w-3.5 h-3.5 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        <?php echo $row['celular']; ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php
                                $color = $row['situacion'] === 'Baja' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700';
                                ?>
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold <?php echo $color; ?>">
                                    <?php echo $row['situacion'] ?? 'Activo'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="perfil/<?php echo $row['id']; ?>" class="p-2 hover:bg-indigo-50 text-indigo-600 rounded-lg transition" title="Ver Perfil">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <button class="p-2 hover:bg-slate-100 text-slate-600 rounded-lg transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<script>
    function filterTable() {
        const input = document.getElementById("searchInput");
        const filter = input.value.toUpperCase();
        const table = document.querySelector("table");
        const tr = table.getElementsByTagName("tr");

        for (let i = 1; i < tr.length; i++) { // Empezamos en 1 para saltar el header
            const tdName = tr[i].getElementsByTagName("td")[0]; // Columna Nombre/DNI
            const tdPuesto = tr[i].getElementsByTagName("td")[1]; // Columna Puesto

            if (tdName || tdPuesto) {
                const txtValueName = tdName.textContent || tdName.innerText;
                const txtValuePuesto = tdPuesto.textContent || tdPuesto.innerText;

                if (txtValueName.toUpperCase().indexOf(filter) > -1 ||
                    txtValuePuesto.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>