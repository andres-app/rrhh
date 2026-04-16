<?php
require_once ROOT_PATH . 'Controlador/CtrDirectorio.php';
require_once ROOT_PATH . 'Modelo/MdDirectorio.php';

$controlador = new CtrDirectorio();
$empleados = $controlador->ctrMostrarDirectorio();

$titulo_pagina = "Directorio de Personal - RRHH";
$menu_activo = "directorio";

require_once ROOT_PATH . 'Vista/includes/header.php';
require_once ROOT_PATH . 'Vista/includes/sidebar.php';
?>

<main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">

    <header class="min-h-20 bg-white shadow-sm flex flex-col md:flex-row items-center px-4 md:px-8 py-4 md:py-0 justify-between z-10 gap-4">
        <h1 class="text-xl md:text-2xl font-bold text-slate-800 text-center md:text-left">Directorio de Personal</h1>

        <div class="w-full md:flex-1 md:max-w-md md:mx-8">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" id="searchInput" onkeyup="filterTable()"
                    class="block w-full pl-10 pr-3 py-2 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition"
                    placeholder="Buscar por nombre o DNI...">
            </div>
        </div>

        <button class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-bold shadow-md transition flex items-center justify-center">
            <span class="mr-2">+</span> <span class="whitespace-nowrap">Nuevo Empleado</span>
        </button>
    </header>

    <div class="p-4 md:p-8 flex-1 overflow-y-auto">

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Colaborador</th>
                        <th class="hidden sm:table-cell px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Puesto / Área</th>
                        <th class="hidden lg:table-cell px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Contacto</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <?php foreach ($empleados as $row): ?>
                        <tr class="hover:bg-slate-50/80 transition-colors group">
                            <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="hidden xs:flex h-10 w-10 rounded-full bg-indigo-100 flex-shrink-0 items-center justify-center text-indigo-700 font-bold mr-3">
                                        <?php echo substr($row['nombres_apellidos'], 0, 1); ?>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="text-sm font-bold text-slate-900 truncate max-w-[150px] md:max-w-none">
                                            <?php echo $row['nombres_apellidos']; ?>
                                        </div>
                                        <div class="text-xs text-slate-500">DNI: <?php echo $row['dni']; ?></div>
                                        <div class="sm:hidden text-[10px] text-indigo-600 font-medium"><?php echo $row['puesto_cas']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden sm:table-cell px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-slate-700 font-medium"><?php echo $row['puesto_cas']; ?></div>
                                <div class="text-xs text-slate-400"><?php echo $row['area']; ?></div>
                            </td>
                            <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                <div class="flex flex-col">
                                    <span class="flex items-center mb-1 text-xs">
                                        <?php echo $row['correo_institucional']; ?>
                                    </span>
                                    <span class="flex items-center text-xs">
                                        <?php echo $row['celular']; ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm">
                                <?php $color = $row['situacion'] === 'Baja' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>
                                <span class="px-2 py-0.5 rounded-full text-[10px] md:text-xs font-bold <?php echo $color; ?>">
                                    <?php echo $row['situacion'] ?? 'Activo'; ?>
                                </span>
                            </td>
                            <td class="px-4 md:px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-1 md:gap-3">
                                    <a href="perfil/<?php echo $row['id']; ?>"
                                        class="p-2 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-600 hover:text-white transition shadow-sm"
                                        title="Ver Perfil">
                                        <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <button class="p-2 bg-slate-50 text-slate-600 rounded-lg hover:bg-slate-800 hover:text-white transition shadow-sm">
                                        <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
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