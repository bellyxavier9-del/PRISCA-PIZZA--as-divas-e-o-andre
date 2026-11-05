<?php
/**
 * Prisca Pizza - API de Histórico de Auditoria e Logs
 * Exclusivo para perfis autorizados (Caixa e Admin)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
if (!hasRole([ROLE_CAIXA, ROLE_ADMIN])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'mensagem' => 'Acesso negado à auditoria']);
    exit;
}

$pdo = getDbConnection();

$limite = min(100, max(10, (int)($_GET['limite'] ?? 50)));
$acaoFiltro = trim($_GET['acao'] ?? '');
$perfilFiltro = trim($_GET['perfil'] ?? '');
$pedidoFiltro = trim($_GET['pedido_codigo'] ?? '');

$sql = "SELECT l.*, p.codigo as pedido_codigo 
        FROM logs_auditoria l 
        LEFT JOIN pedidos p ON l.pedido_id = p.id 
        WHERE 1=1";
$params = [];

if (!empty($acaoFiltro)) {
    $sql .= " AND l.acao = :acao";
    $params[':acao'] = $acaoFiltro;
}
if (!empty($perfilFiltro)) {
    $sql .= " AND l.usuario_perfil = :perfil";
    $params[':perfil'] = $perfilFiltro;
}
if (!empty($pedidoFiltro)) {
    $sql .= " AND p.codigo LIKE :codigo";
    $params[':codigo'] = '%' . $pedidoFiltro . '%';
}

$sql .= " ORDER BY l.criado_em DESC LIMIT " . (int)$limite;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

echo json_encode([
    'status' => 'success',
    'total' => count($logs),
    'logs' => $logs
]);
