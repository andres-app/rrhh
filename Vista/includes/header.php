<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title><?= $titulo_pagina ?? 'Sistema de RRHH' ?></title>
    <style>
        /* Contenedor Glassmorphism */
        #preloader-premium {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(6px);
            transition: opacity 0.4s ease;
        }

        /* Diseño de Órbitas Impactante */
        .loader-container {
            position: relative;
            width: 100px;
            height: 100px;
        }

        .orbit {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            border-radius: 50%;
            border: 3px solid transparent;
            border-top-color: #6366f1; /* Indigo */
            animation: spin-orbit 1s cubic-bezier(0.5, 0, 0.5, 1) infinite;
        }

        .orbit:nth-child(2) {
            width: 80%; height: 80%;
            top: 10%; left: 10%;
            border-top-color: #a855f7; /* Purple */
            animation-duration: 1.5s;
            animation-direction: reverse;
        }

        .orbit:nth-child(3) {
            width: 60%; height: 60%;
            top: 20%; left: 20%;
            border-top-color: #f43f5e; /* Rose */
            animation-duration: 0.8s;
        }

        @keyframes spin-orbit {
            0% { transform: rotate(0deg) scale(1); }
            50% { transform: rotate(180deg) scale(1.1); }
            100% { transform: rotate(360deg) scale(1); }
        }

        .loader-hidden { opacity: 0 !important; pointer-events: none !important; }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-900 flex flex-col md:flex-row h-screen overflow-hidden">

    <div id="preloader-premium">
        <div class="loader-container">
            <div class="orbit"></div>
            <div class="orbit"></div>
            <div class="orbit"></div>
        </div>
        <script>
            (function() {
                const loader = document.getElementById('preloader-premium');
                const close = () => { if(loader) loader.classList.add('loader-hidden'); };
                // Se apaga sí o sí a los 2.5 segundos para evitar bloqueos
                setTimeout(close, 2500);
                window.addEventListener('load', close);
                if (document.readyState === 'complete') close();
            })();
        </script>
    </div>