<!DOCTYPE html>
<!-- Vista/includes/header.php -->
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title><?= $titulo_pagina ?? 'Sistema de RRHH' ?></title>
    <style>
        html,
        body {
            width: 100%;
            height: 100%;
            overflow: hidden !important;
        }

        body.swal2-shown,
        body.swal2-height-auto {
            height: 100vh !important;
            overflow: hidden !important;
            padding-right: 0 !important;
        }

        .swal2-container {
            padding-right: 0 !important;
        }

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
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 3px solid transparent;
            border-top-color: #6366f1;
            /* Indigo */
            animation: spin-orbit 1s cubic-bezier(0.5, 0, 0.5, 1) infinite;
        }

        .orbit:nth-child(2) {
            width: 80%;
            height: 80%;
            top: 10%;
            left: 10%;
            border-top-color: #a855f7;
            /* Purple */
            animation-duration: 1.5s;
            animation-direction: reverse;
        }

        .orbit:nth-child(3) {
            width: 60%;
            height: 60%;
            top: 20%;
            left: 20%;
            border-top-color: #f43f5e;
            /* Rose */
            animation-duration: 0.8s;
        }

        @keyframes spin-orbit {
            0% {
                transform: rotate(0deg) scale(1);
            }

            50% {
                transform: rotate(180deg) scale(1.1);
            }

            100% {
                transform: rotate(360deg) scale(1);
            }
        }

        #preloader-premium {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(6px);
            transition: opacity 0.35s ease, visibility 0.35s ease;

            /* seguridad: se oculta solo aunque falle JS */
            animation: ocultarPreloaderSeguro 2.8s ease forwards;
        }

        .loader-hidden {
            opacity: 0 !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }

        @keyframes ocultarPreloaderSeguro {

            0%,
            75% {
                opacity: 1;
                visibility: visible;
            }

            100% {
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
            }
        }
    </style>
</head>

<body class="bg-slate-50 font-sans antialiased text-slate-900 flex flex-col md:flex-row min-h-screen overflow-hidden">

<div id="preloader-premium">
    <div class="loader-container">
        <div class="orbit"></div>
        <div class="orbit"></div>
        <div class="orbit"></div>
    </div>
</div>

<script>
    (function() {
        function cerrarPreloader() {
            const loader = document.getElementById('preloader-premium');

            if (!loader) {
                return;
            }

            loader.classList.add('loader-hidden');

            setTimeout(function() {
                if (loader && loader.parentNode) {
                    loader.parentNode.removeChild(loader);
                }
            }, 450);
        }

        window.addEventListener('load', cerrarPreloader);

        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(cerrarPreloader, 600);
        });

        setTimeout(cerrarPreloader, 2500);

        if (document.readyState === 'interactive' || document.readyState === 'complete') {
            setTimeout(cerrarPreloader, 300);
        }
    })();
</script>