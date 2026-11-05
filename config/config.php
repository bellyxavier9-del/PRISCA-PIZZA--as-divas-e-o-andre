<?php
/**
 * Prisca Pizza - Configurações Gerais do Sistema
 */

if (session_status() === PHP_SESSION_NONE) {
    // Configurações de cookies de sessão seguras
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Configurações de Fuso Horário e Idioma
date_default_timezone_set('America/Sao_Paulo');
setlocale(LC_ALL, 'pt_BR.utf-8', 'pt_BR', 'portuguese');

// Definições Gerais da Pizzaria
define('APP_NAME', 'Prisca Pizza');
define('APP_TAGLINE', 'Tradição, Sabor e Paixão em Cada Fatia');
define('APP_PHONE', '(11) 3456-7890');
define('APP_WHATSAPP', '(11) 98888-7777');
define('APP_ADDRESS', 'Rua das Pizzas, 500 - Bairro Italiano, São Paulo - SP');
define('DEFAULT_DELIVERY_FEE', 8.00);
define('ESTIMATED_DELIVERY_TIME', '40 - 50 min');
define('ESTIMATED_PICKUP_TIME', '20 - 30 min');

// Horário de Funcionamento
define('OPENING_HOUR', 18); // 18:00
define('CLOSING_HOUR', 23); // 23:30 (tratado na função)
define('CLOSING_MINUTE', 30);

// Configurações do Banco de Dados MySQL
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'prisca_pizza');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

// Caminho Base Dinâmico
$script_name = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$base_url = rtrim($script_name, '/');
if ($base_url === '') {
    $base_url = '';
}
define('BASE_URL', $base_url);

// Perfis de Usuário Permitidos
define('ROLE_CLIENTE', 'cliente');
define('ROLE_CAIXA', 'caixa');
define('ROLE_PIZZAIOLO', 'pizzaiolo');
define('ROLE_ENTREGADOR', 'entregador');
define('ROLE_ADMIN', 'admin');

// Status Válidos dos Pedidos (Fluxo oficial)
define('STATUS_RECEBIDO', 'Recebido');
define('STATUS_EM_PREPARO', 'Em preparo');
define('STATUS_PRONTO', 'Pronto');
define('STATUS_SAIU_ENTREGA', 'Saiu para entrega');
define('STATUS_ENTREGUE', 'Entregue');
define('STATUS_CANCELADO', 'Cancelado');

/**
 * Verifica se a pizzaria está aberta no momento
 */
function isStoreOpen() {
    $now = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
    $dayOfWeek = (int)$now->format('w'); // 0 = Domingo, 1 = Segunda, ..., 6 = Sábado
    $hour = (int)$now->format('G');
    $minute = (int)$now->format('i');

    // Fechado às segundas-feiras
    if ($dayOfWeek === 1) {
        return false;
    }

    $timeInMinutes = ($hour * 60) + $minute;
    $openInMinutes = (OPENING_HOUR * 60);
    $closeInMinutes = (CLOSING_HOUR * 60) + CLOSING_MINUTE;

    return ($timeInMinutes >= $openInMinutes && $timeInMinutes <= $closeInMinutes);
}

/**
 * Retorna mensagem de status de funcionamento
 */
function getStoreStatusMessage() {
    if (isStoreOpen()) {
        return ['open' => true, 'text' => 'Pizzaria Aberta • Entregas a todo vapor!', 'color' => 'emerald'];
    }
    return ['open' => false, 'text' => 'Pizzaria Fechada • Abrimos hoje às 18:00 (Ter a Dom)', 'color' => 'rose'];
}
