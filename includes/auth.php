<?php
/**
 * Prisca Pizza - Controle de Sessão, Autenticação e Permissões de Acesso
 * Implementação segura com proteção contra brute-force e verificação server-side
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/functions.php';

/**
 * Verifica se há um usuário autenticado na sessão
 */
function isLoggedIn() {
    return !empty($_SESSION['usuario']['id']);
}

/**
 * Retorna os dados do usuário autenticado
 */
function getLoggedUser() {
    return $_SESSION['usuario'] ?? null;
}

/**
 * Verifica se o usuário autenticado possui o perfil informado
 * 
 * @param string|array $roles Perfil único ou array de perfis permitidos
 * @return bool
 */
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }

    $userRole = $_SESSION['usuario']['perfil'] ?? null;
    if (is_array($roles)) {
        return in_array($userRole, $roles);
    }
    return $userRole === $roles;
}

/**
 * Exige que o usuário esteja logado. Caso contrário, redireciona para login.
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setToast('warning', 'Acesso Restrito', 'Faça login para continuar.');
        $currentUrl = urlencode($_SERVER['REQUEST_URI']);
        header('Location: ' . BASE_URL . '/login.php?redirect=' . $currentUrl);
        exit;
    }
}

/**
 * Exige que o usuário possua um dos perfis autorizados (Validação no servidor - Regra CORE)
 * 
 * @param string|array $roles Perfis permitidos
 */
function requireRole($roles) {
    requireLogin();

    if (!hasRole($roles)) {
        // Registra tentativa de violação de acesso na auditoria
        registrarAuditoria(
            null,
            'acesso_negado',
            null,
            null,
            'Tentativa de acesso não autorizado à rota: ' . ($_SERVER['REQUEST_URI'] ?? 'desconhecida')
        );

        setToast('error', 'Acesso Negado', 'Você não tem permissão para acessar esta página.');
        
        // Redireciona para o painel apropriado do usuário ou página inicial
        $userRole = $_SESSION['usuario']['perfil'] ?? 'cliente';
        if ($userRole === ROLE_CAIXA) {
            header('Location: ' . BASE_URL . '/painel/caixa.php');
        } elseif ($userRole === ROLE_PIZZAIOLO) {
            header('Location: ' . BASE_URL . '/painel/cozinha.php');
        } elseif ($userRole === ROLE_ENTREGADOR) {
            header('Location: ' . BASE_URL . '/painel/entregas.php');
        } else {
            header('Location: ' . BASE_URL . '/index.php');
        }
        exit;
    }
}

/**
 * Verifica se a conta está bloqueada por excesso de tentativas de login
 * 
 * @param string $email
 * @return array ['bloqueado' => bool, 'minutos_restantes' => int]
 */
function checkLoginRateLimit($email) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT tentativas_login, bloqueado_ate FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => trim($email)]);
    $user = $stmt->fetch();

    if ($user && !empty($user['bloqueado_ate'])) {
        $bloqueadoAte = new DateTime($user['bloqueado_ate']);
        $agora = new DateTime();

        if ($agora < $bloqueadoAte) {
            $diff = $agora->diff($bloqueadoAte);
            $minutosRestantes = ($diff->i) + 1;
            return ['bloqueado' => true, 'minutos' => $minutosRestantes];
        } else {
            // Tempo expirou, desbloqueia automaticamente
            $update = $pdo->prepare("UPDATE usuarios SET tentativas_login = 0, bloqueado_ate = NULL WHERE email = :email");
            $update->execute([':email' => trim($email)]);
        }
    }

    return ['bloqueado' => false, 'minutos' => 0];
}

/**
 * Registra falha de login e aplica bloqueio temporário após 5 tentativas
 */
function recordFailedLogin($email) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT id, tentativas_login FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => trim($email)]);
    $user = $stmt->fetch();

    if ($user) {
        $novasTentativas = (int)$user['tentativas_login'] + 1;
        $bloqueadoAte = null;

        // Se atingir 5 tentativas, bloqueia por 15 minutos
        if ($novasTentativas >= 5) {
            $bloqueadoAte = (new DateTime('+15 minutes'))->format('Y-m-d H:i:s');
        }

        $update = $pdo->prepare("UPDATE usuarios SET tentativas_login = :tentativas, bloqueado_ate = :bloqueado WHERE id = :id");
        $update->execute([
            ':tentativas' => $novasTentativas,
            ':bloqueado' => $bloqueadoAte,
            ':id' => $user['id']
        ]);

        // Registra na auditoria
        registrarAuditoria(
            null,
            'login_falha',
            null,
            null,
            "Tentativa de login falha para o e-mail: {$email} (Tentativa {$novasTentativas}/5)"
        );
    } else {
        // Tentativa com e-mail não existente
        registrarAuditoria(
            null,
            'login_falha',
            null,
            null,
            "Tentativa de login com e-mail não cadastrado: {$email}"
        );
    }
}

/**
 * Reseta o contador de tentativas e registra sucesso de autenticação
 */
function recordSuccessfulLogin($userId, $userNome, $userPerfil) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("UPDATE usuarios SET tentativas_login = 0, bloqueado_ate = NULL WHERE id = :id");
    $stmt->execute([':id' => $userId]);

    // Registra na auditoria
    registrarAuditoria(
        null,
        'login_sucesso',
        null,
        null,
        "Usuário {$userNome} ({$userPerfil}) realizou login com sucesso."
    );
}
