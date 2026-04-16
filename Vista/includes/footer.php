<?php
// /Vista/includes/footer.php
?>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const btnMenu = document.getElementById("btn-menu");
        const btnClose = document.getElementById("btn-close-menu");
        const sidebar = document.getElementById("sidebar");
        const overlay = document.getElementById("sidebar-overlay");

        const toggleMenu = () => {
            // Deslizar menú
            sidebar.classList.toggle("-translate-x-full");
            
            // Lógica del overlay
            if (sidebar.classList.contains("-translate-x-full")) {
                overlay.classList.replace("opacity-100", "opacity-0");
                setTimeout(() => overlay.classList.add("hidden"), 300); // Esperar a que termine la transición
            } else {
                overlay.classList.remove("hidden");
                setTimeout(() => overlay.classList.replace("opacity-0", "opacity-100"), 10);
            }
        };

        // Eventos
        if(btnMenu) btnMenu.addEventListener("click", toggleMenu);
        if(btnClose) btnClose.addEventListener("click", toggleMenu);
        if(overlay) overlay.addEventListener("click", toggleMenu); // Cierra al tocar fuera del menú
    });
</script>

</body>
</html>