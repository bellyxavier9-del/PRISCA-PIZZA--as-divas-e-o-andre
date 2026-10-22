<?php
/**
 * Prisca Pizza - API de Autenticação e Usuários
 * Requisitos:
 * - Senhas com hash bcrypt
 * - Limitação de tentativas de login contra ataques automatizados
 * - Recuperação de senha segura
 * - Registro em auditoria de logs
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../bd.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Leitura de JSON body se presente
$inputJSON = file_get_contents('php://input');
$inputData = json_decode($inputJSON, true) ?? [];
$params = !empty($inputData) ? $inputData : $_POST;

$clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

switch ($action) {
    case 'login':
        $email = trim($params['email'] ?? '');
        $password = $params['password'] ?? '';

        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Por favor, informe e-mail e senha.']);
            exit;
        }

        // 1. Verificação de Rate Limiting (Bloqueio após 5 tentativas em 15 minutos)
        // Requisito: "As tentativas de login devem ser limitadas para reduzir o risco de ataques automatizados."
        $stmtRate = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE (ip_address = ? OR email = ?) AND attempted_at > (NOW() - INTERVAL 15 MINUTE)");
        $stmtRate->execute([$clientIp, $email]);
        $attemptsCount = $stmtRate->fetchColumn();

        if ($attemptsCount >= 5) {
            logSystemAction($pdo, 'login_blocked_rate_limit', "Tentativa de login bloqueada por excesso de falhas para o e-mail {$email}", null, $email, 'desconhecido');
            echo json_encode([
                'success' => false, 
                'message' => 'Muitas tentativas incorretas. Por segurança, sua conta foi temporariamente bloqueada por 15 minutos.'
            ]);
            exit;
        }

        // 2. Busca usuário no banco
        $stmt = $pdo->prepare("SELECT id, name, email, password_hash, phone, address, neighborhood, city, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // 3. Verificação segura com password_verify
        if ($user && password_verify($password, $user['password_hash'])) {
            // Limpa tentativas de login anteriores bem-sucedidas
            $stmtClean = $pdo->prepare("DELETE FROM login_attempts WHERE email = ? OR ip_address = ?");
            $stmtClean->execute([$email, $clientIp]);

            // Regeneração de ID de sessão para prevenir Session Fixation
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'address' => $user['address'],
                'neighborhood' => $user['neighborhood'],
                'city' => $user['city'],
                'role' => $user['role']
            ];

            // Grava auditoria
            logSystemAction($pdo, 'login_success', "Login efetuado com sucesso via perfil {$user['role']}", $user['id'], $user['name'], $user['role']);

            // Define destino baseado no perfil
            $redirectUrl = 'cardapio.php';
            if ($user['role'] === 'atendente') $redirectUrl = 'painel-atendente.php';
            if ($user['role'] === 'pizzaiolo') $redirectUrl = 'painel-cozinha.php';
            if ($user['role'] === 'entregador') $redirectUrl = 'painel-entregador.php';
            if ($user['role'] === 'admin') $redirectUrl = 'painel-atendente.php';

            echo json_encode([
                'success' => true,
                'message' => "Bem-vindo(a), {$user['name']}!",
                'user' => $_SESSION['user'],
                'redirect' => $redirectUrl
            ]);
        } else {
            // Registra tentativa falha
            $stmtFail = $pdo->prepare("INSERT INTO login_attempts (ip_address, email) VALUES (?, ?)");
            $stmtFail->execute([$clientIp, $email]);

            logSystemAction($pdo, 'login_failed', "Tentativa falha de login com o e-mail {$email}", null, $email, 'desconhecido');

            $remaining = 5 - ($attemptsCount + 1);
            $msg = 'E-mail ou senha incorretos.';
            if ($remaining > 0 && $remaining <= 3) {
                $msg .= " Atenção: mais {$remaining} tentativa(s) incorreta(s) bloquearão o acesso temporariamente.";
            }

            echo json_encode(['success' => false, 'message' => $msg]);
        }
        break;

    case 'register':
        $name = trim($params['name'] ?? '');
        $email = trim($params['email'] ?? '');
        $password = $params['password'] ?? '';
        $phone = trim($params['phone'] ?? '');
        $address = trim($params['address'] ?? '');
        $neighborhood = trim($params['neighborhood'] ?? '');

        if (empty($name) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Nome, e-mail e senha são obrigatórios.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Por favor, informe um endereço de e-mail válido.']);
            exit;
        }

        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'A senha deve conter no mínimo 6 caracteres.']);
            exit;
        }

        // Verifica duplicidade de e-mail
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmtCheck->execute([$email]);
        if ($stmtCheck->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Este e-mail já está cadastrado no sistema.']);
            exit;
        }

        // Hash Bcrypt com salt seguro automático
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $stmtIns = $pdo->prepare("INSERT INTO users (name, email, password_hash, phone, address, neighborhood, role) VALUES (?, ?, ?, ?, ?, ?, 'cliente')");
        $stmtIns->execute([$name, $email, $passwordHash, $phone, $address, $neighborhood]);
        $newUserId = $pdo->lastInsertId();

        // Autentica o usuário recém-criado
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $newUserId,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'neighborhood' => $neighborhood,
            'city' => 'São Paulo',
            'role' => 'cliente'
        ];

        logSystemAction($pdo, 'register_success', "Novo cliente cadastrado: {$name} ({$email})", $newUserId, $name, 'cliente');

        echo json_encode([
            'success' => true,
            'message' => 'Conta criada com sucesso! Seja bem-vindo à Prisca Pizza.',
            'user' => $_SESSION['user'],
            'redirect' => 'cardapio.php'
        ]);
        break;

    case 'logout':
        if (isset($_SESSION['user']['id'])) {
            logSystemAction($pdo, 'logout', "Usuário efetuou logout", $_SESSION['user']['id'], $_SESSION['user']['name'], $_SESSION['user']['role']);
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $paramsSession = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $paramsSession["path"], $paramsSession["domain"],
                $paramsSession["secure"], $paramsSession["httponly"]
            );
        }
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Sessão encerrada com sucesso.', 'redirect' => 'login.php']);
        break;

    case 'update_profile':
        if (!isset($_SESSION['user']['id'])) {
            echo json_encode(['success' => false, 'message' => 'Você precisa estar logado para editar seu perfil.']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $name = trim($params['name'] ?? '');
        $phone = trim($params['phone'] ?? '');
        $address = trim($params['address'] ?? '');
        $neighborhood = trim($params['neighborhood'] ?? '');
        $currentPassword = $params['current_password'] ?? '';
        $newPassword = $params['new_password'] ?? '';

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'O nome não pode ficar em branco.']);
            exit;
        }

        // Se o usuário deseja alterar a senha
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                echo json_encode(['success' => false, 'message' => 'Para definir uma nova senha, informe a sua senha atual.']);
                exit;
            }

            $stmtUser = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmtUser->execute([$userId]);
            $currentHash = $stmtUser->fetchColumn();

            if (!password_verify($currentPassword, $currentHash)) {
                echo json_encode(['success' => false, 'message' => 'A senha atual informada está incorreta.']);
                exit;
            }

            if (strlen($newPassword) < 6) {
                echo json_encode(['success' => false, 'message' => 'A nova senha deve ter no mínimo 6 caracteres.']);
                exit;
            }

            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmtUp = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ?, neighborhood = ?, password_hash = ? WHERE id = ?");
            $stmtUp->execute([$name, $phone, $address, $neighborhood, $newHash, $userId]);
            logSystemAction($pdo, 'password_update', 'Usuário alterou sua senha com sucesso', $userId, $name, $_SESSION['user']['role']);
        } else {
            $stmtUp = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ?, neighborhood = ? WHERE id = ?");
            $stmtUp->execute([$name, $phone, $address, $neighborhood, $userId]);
            logSystemAction($pdo, 'profile_update', 'Usuário atualizou seus dados de perfil', $userId, $name, $_SESSION['user']['role']);
        }

        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['phone'] = $phone;
        $_SESSION['user']['address'] = $address;
        $_SESSION['user']['neighborhood'] = $neighborhood;

        echo json_encode(['success' => true, 'message' => 'Dados atualizados com sucesso!', 'user' => $_SESSION['user']]);
        break;

    case 'forgot_password':
        $email = trim($params['email'] ?? '');
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Informe o seu e-mail cadastrado.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Gera token criptograficamente seguro
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmtInsToken = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmtInsToken->execute([$user['id'], $token, $expiresAt]);

            logSystemAction($pdo, 'password_reset_request', "Solicitação de redefinição de senha para {$email}", $user['id'], $user['name'], 'cliente');

            $resetUrl = "redefinir-senha.php?token={$token}";

            echo json_encode([
                'success' => true,
                'message' => 'Instruções e link seguro de redefinição foram gerados com sucesso!',
                'reset_link' => $resetUrl,
                'token' => $token
            ]);
        } else {
            // Resposta genérica para evitar enumeração de usuários
            echo json_encode([
                'success' => true,
                'message' => 'Se o e-mail informado estiver cadastrado em nosso sistema, as instruções serão processadas com segurança.'
            ]);
        }
        break;

    case 'reset_password':
        $token = trim($params['token'] ?? '');
        $newPassword = $params['new_password'] ?? '';

        if (empty($token) || empty($newPassword)) {
            echo json_encode(['success' => false, 'message' => 'Token e nova senha são obrigatórios.']);
            exit;
        }

        if (strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'message' => 'A nova senha deve ter no mínimo 6 caracteres.']);
            exit;
        }

        $stmtToken = $pdo->prepare("SELECT pr.id, pr.user_id, u.name, u.email FROM password_resets pr JOIN users u ON u.id = pr.user_id WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()");
        $stmtToken->execute([$token]);
        $resetReq = $stmtToken->fetch();

        if (!$resetReq) {
            echo json_encode(['success' => false, 'message' => 'Link de redefinição inválido, expirado ou já utilizado.']);
            exit;
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmtUp = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmtUp->execute([$newHash, $resetReq['user_id']]);

        // Marca token como utilizado
        $stmtMark = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
        $stmtMark->execute([$resetReq['id']]);

        logSystemAction($pdo, 'password_reset_success', "Senha redefinida com sucesso via token para {$resetReq['email']}", $resetReq['user_id'], $resetReq['name'], 'cliente');

        echo json_encode([
            'success' => true, 
            'message' => 'Sua senha foi redefinida com sucesso! Você já pode efetuar login com sua nova credencial.',
            'redirect' => 'login.php'
        ]);
        break;

    case 'get_current_user':
        echo json_encode([
            'success' => true,
            'logged_in' => isset($_SESSION['user']['id']),
            'user' => $_SESSION['user'] ?? null
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Ação de autenticação não reconhecida.']);
        break;
}
