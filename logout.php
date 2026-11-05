<?php
/**
 * Prisca Pizza - Encerramento de Sessão (Logout Seguro)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/bd.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    $nome = $_SESSION['usuario']['nome'] ?? 'Usuário';
    registrarAuditoria(null, 'logout', null, null, "Usuário {$nome} encerrou a sessão.");
}

// Limpa todas as variáveis de sessão
$_SESSION = [];

// Remove cookie de sessão do navegador se existir
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Reinicia sessão apenas para exibir o toast informativo
session_start();
setToast('info', 'Sessão Encerrada', 'Você saiu com segurança da sua conta.');

header('Location: ' . BASE_URL . '/index.php');
exit;
