<?php
/**
 * Prisca Pizza - API de Histórico e Auditoria
 * Requisito CORE:
 * "O sistema deve ter uma página de históricos e manter sempre os logs de qualquer alteração feita por qualquer usuário, para auditorias futuras."
 * "Cada mudança de status deve registrar quem fez, quando fez e qual era o status anterior."
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Apenas atendente e admin podem auditar
checkAuth(['atendente', 'admin']);

$type = $_GET['type'] ?? 'all'; // 'orders', 'system', 'all'
$orderId = !empty($_GET['order_id']) ? (int)$_GET['order_id'] : null;
$limit = min(100, max(10, (int)($_GET['limit'] ?? 50)));

$response = ['success' => true];

// 1. Logs de Mudanças de Status de Pedidos
if ($type === 'orders' || $type === 'all') {
    $sql = "SELECT osl.*, o.customer_name, o.total, o.order_type
            FROM order_status_logs osl
            JOIN orders o ON o.id = osl.order_id
            WHERE 1=1";
    $binds = [];

    if ($orderId) {
        $sql .= " AND osl.order_id = ?";
        $binds[] = $orderId;
    }

    $sql .= " ORDER BY osl.id DESC LIMIT " . $limit;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($binds);
    $response['order_status_logs'] = $stmt->fetchAll();
}

// 2. Logs Globais de Auditoria do Sistema
if ($type === 'system' || $type === 'all') {
    $sql = "SELECT * FROM system_logs WHERE 1=1";
    $binds = [];

    if (!empty($_GET['action_filter'])) {
        $sql .= " AND action = ?";
        $binds[] = $_GET['action_filter'];
    }

    $sql .= " ORDER BY id DESC LIMIT " . $limit;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($binds);
    $response['system_logs'] = $stmt->fetchAll();
}

echo json_encode($response);
