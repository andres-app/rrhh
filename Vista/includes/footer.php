<script>
    document.addEventListener("DOMContentLoaded", () => {
        const loader = document.getElementById('preloader-premium');

        // Función para mostrar
        const showLoader = () => loader && loader.classList.remove('loader-hidden');

        // 1. Mostrar al navegar (Enlaces internos)
        document.addEventListener("click", (e) => {
            const a = e.target.closest("a");
            if (a && a.hostname === window.location.hostname && 
                a.getAttribute("href") && 
                !a.getAttribute("href").startsWith("#") && 
                a.target !== "_blank") {
                showLoader();
            }
        });

        // 2. Mostrar al enviar formularios (Login, Guardar, etc.)
        document.addEventListener("submit", (e) => {
            if (!e.defaultPrevented) showLoader();
        });

        // --- TU LÓGICA DE MENU SIDEBAR ---
        const btnMenu = document.getElementById("btn-menu");
        const sidebar = document.getElementById("sidebar");
        const overlay = document.getElementById("sidebar-overlay");

        if (btnMenu && sidebar) {
            btnMenu.onclick = () => {
                sidebar.classList.toggle("-translate-x-full");
                if (overlay) overlay.classList.toggle("hidden");
            };
        }
        if (overlay) {
            overlay.onclick = () => {
                sidebar.classList.add("-translate-x-full");
                overlay.classList.add("hidden");
            };
        }
    });
</script>
</body>
</html>