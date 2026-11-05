<?php
/**
 * Prisca Pizza - Conexão Segura com Banco de Dados via PDO
 * Conforme Requisitos Globais:
 * - Use sempre PDO para conexão e queries no MySQL para evitar SQL Injections
 * - Separe os arquivos de forma lógica: um arquivo para conexão com a base (bd.php)
 */

if (session_status() === PHP_SESSION_NONE) {
    // Configurações seguras de sessão nativa
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

$db_host = '127.0.0.1';
$db_port = '3306';
$db_name = 'prisca_pizza';
$db_user = 'root';
$db_pass = '';
$db_charset = 'utf8mb4';

$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset={$db_charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // Fallback: se o banco ainda não foi selecionado, tenta conectar ao servidor sem banco para poder criar
    try {
        $dsnFallback = "mysql:host={$db_host};port={$db_port};charset={$db_charset}";
        $pdo = new PDO($dsnFallback, $db_user, $db_pass, $options);
    } catch (PDOException $e2) {
        die(json_encode([
            'success' => false,
            'message' => 'Erro de conexão com o banco de dados: ' . $e2->getMessage()
        ]));
    }
}

/**
 * Função global de registro de log no sistema para auditorias futuras
 * Requisito: "manter sempre os logs de qualquer alteração feita por qualquer usuário, para auditorias futuras."
 */
function logSystemAction($pdo, $action, $details = '', $userId = null, $userName = null, $userRole = null) {
    try {
        if ($userId === null && isset($_SESSION['user']['id'])) {
            $userId = $_SESSION['user']['id'];
            $userName = $_SESSION['user']['name'] ?? 'Usuário';
            $userRole = $_SESSION['user']['role'] ?? 'cliente';
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, user_name, user_role, action, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $userName, $userRole, $action, $details, $ip]);
    } catch (Exception $e) {
        // Falha silenciosa em logs para não interromper fluxo principal
        error_log("Falha ao gravar system_log: " . $e->getMessage());
    }
}

/**
 * Função para registrar alteração de status do pedido
 * Requisito: "Cada mudança de status deve registrar quem fez, quando fez e qual era o status anterior."
 */
function logOrderStatusChange($pdo, $orderId, $prevStatus, $newStatus, $comment = '') {
    try {
        $userId = $_SESSION['user']['id'] ?? null;
        $userName = $_SESSION['user']['name'] ?? 'Sistema';
        $userRole = $_SESSION['user']['role'] ?? 'sistema';

        $stmt = $pdo->prepare("INSERT INTO order_status_logs (order_id, user_id, user_name, user_role, previous_status, new_status, comment) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$orderId, $userId, $userName, $userRole, $prevStatus, $newStatus, $comment]);

        // Também grava no log geral de auditoria
        logSystemAction($pdo, 'order_status_change', "Pedido #{$orderId}: de '{$prevStatus}' para '{$newStatus}'. Obs: {$comment}", $userId, $userName, $userRole);
    } catch (Exception $e) {
        error_log("Falha ao gravar order_status_log: " . $e->getMessage());
    }
}

/**
 * Retorna uma configuração do banco
 */
function getSetting($pdo, $key, $default = null) {
    try {
        $stmt = $pdo->prepare("SELECT `value` FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return ($val !== false) ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}
