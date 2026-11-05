<?php
/**
 * Prisca Pizza - Cabeçalho HTML e Carregamento de Recursos
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? APP_NAME . ' - ' . APP_TAGLINE;
$storeStatus = getStoreStatusMessage();
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-50 text-slate-800 antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= e($pageTitle) ?></title>
    
    <!-- Meta SEO & Theme -->
    <meta name="description" content="Pizzas e esfihas artesanais de fermentação natural com ingredientes nobres. Peça online na Prisca Pizza com entrega rápida.">
    <meta name="theme-color" content="#b91c1c">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Tailwind CSS (via CDN oficial) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        prisca: {
                            50: '#fff1f2',
                            100: '#ffe4e6',
                            200: '#fecdd3',
                            500: '#f43f5e',
                            600: '#e11d48',
                            700: '#be123c',
                            800: '#9f1239',
                            900: '#881337',
                            dark: '#1c1917',
                            gold: '#f59e0b'
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        serif: ['"Playfair Display"', 'serif'],
                        brand: ['"Cinzel"', 'serif']
                    }
                }
            }
        }
    </script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/custom.css">
</head>
<body class="flex flex-col min-h-screen bg-slate-50 selection:bg-rose-500 selection:text-white">

    <!-- Container Global para Toasts (Regra: Retornar mensagens claras em TOAST) -->
    <div id="toast-container" class="fixed top-5 right-5 z-[9999] flex flex-col gap-2 pointer-events-none max-w-sm w-full px-4 sm:px-0"></div>

    <!-- Barra de aviso de funcionamento (Topo) -->
    <div class="bg-slate-900 text-xs py-1.5 px-4 text-slate-300 border-b border-slate-800">
        <div class="max-w-7xl mx-auto flex flex-wrap justify-between items-center gap-2">
            <div class="flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full <?= $storeStatus['open'] ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' ?>"></span>
                <span class="font-medium text-slate-200"><?= $storeStatus['text'] ?></span>
            </div>
            <div class="hidden sm:flex items-center gap-4 text-[11px] text-slate-400">
                <span><i class="fa-solid fa-phone text-rose-500 mr-1"></i> <?= APP_PHONE ?></span>
                <span><i class="fa-brands fa-whatsapp text-emerald-500 mr-1"></i> <?= APP_WHATSAPP ?></span>
                <span><i class="fa-solid fa-clock text-amber-500 mr-1"></i> Ter a Dom: 18h às 23h30</span>
            </div>
        </div>
    </div>
