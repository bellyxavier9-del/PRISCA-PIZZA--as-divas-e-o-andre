<?php
/**
 * Prisca Pizza - Verificação de Permissões e Perfis
 * Requisito: "As permissões devem ser verificadas no servidor, não apenas na interface do site."
 * "Usuários não autorizados não podem acessar páginas ou informações internas alterando endereços ou parâmetros."
 */

require_once __DIR__ . '/../bd.php';

function checkAuth($allowedRoles = []) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Se o usuário não estiver logado
    if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
        if (isAjaxRequest()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Sessão expirada ou não autenticada. Faça login.']);
            exit;
        }
        $_SESSION['flash_error'] = 'Você precisa estar conectado para acessar esta página.';
        header('Location: login.php');
        exit;
    }

    $userRole = $_SESSION['user']['role'] ?? 'cliente';

    // Se foram especificadas roles permitidas
    if (!empty($allowedRoles)) {
        // Admin sempre tem acesso total
        if ($userRole === 'admin') {
            return true;
        }

        if (!in_array($userRole, $allowedRoles)) {
            if (isAjaxRequest()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Acesso negado: perfil sem permissão para esta ação.']);
                exit;
            }
            $_SESSION['flash_error'] = 'Acesso não autorizado para o seu perfil de usuário.';
            
            // Redireciona para o painel correspondente ao perfil
            switch ($userRole) {
                case 'atendente':
                    header('Location: painel-atendente.php');
                    break;
                case 'pizzaiolo':
                    header('Location: painel-cozinha.php');
                    break;
                case 'entregador':
                    header('Location: painel-entregador.php');
                    break;
                default:
                    header('Location: cardapio.php');
                    break;
            }
            exit;
        }
    }

    return true;
}

function isAjaxRequest() {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
        || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
        || (isset($_GET['format']) && $_GET['format'] === 'json');
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}
