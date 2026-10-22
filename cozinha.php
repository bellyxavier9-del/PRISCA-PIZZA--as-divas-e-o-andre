<?php
/**
 * Prisca Pizza - API do Pizzaiolo / Cozinha
 * Perfil 3 — Pizzaiolo
 * Requisitos:
 * - Deve acessar um painel específico para a cozinha
 * - Pode visualizar os pedidos encaminhados para preparo
 * - Deve visualizar produtos, tamanhos, sabores, adicionais e observações relevantes
 * - Pode atualizar o pedido para “Em preparo” e “Pronto”
 * - NÃO deve acessar dados de pagamento desnecessários ou informações pessoais que não sejam relevantes para a preparação
 * - Atualização automática a cada poucos segundos
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Apenas pizzaiolo e admin
checkAuth(['pizzaiolo', 'admin']);

$action = $_GET['action'] ?? $_POST['action'] ?? 'get_kitchen_orders';
$inputJSON = file_get_contents('php://input');
$inputData = json_decode($inputJSON, true) ?? [];
$params = !empty($inputData) ? $inputData : $_POST;

switch ($action) {
    case 'get_kitchen_orders':
        // Apenas dados de cozinha: sem cartão, sem pix, sem endereço, sem telefone
        $stmt = $pdo->query("
            SELECT 
                o.id,
                o.order_type,
                o.status,
                o.created_at,
                o.observation,
                o.estimated_minutes
            FROM orders o
            WHERE o.status IN ('Recebido', 'Em preparo', 'Pronto')
            ORDER BY 
                CASE 
                    WHEN o.status = 'Em preparo' THEN 1
                    WHEN o.status = 'Recebido' THEN 2
                    WHEN o.status = 'Pronto' THEN 3
                    ELSE 4
                END,
                o.id ASC
        ");
        $orders = $stmt->fetchAll();

        foreach ($orders as &$ord) {
            // Busca apenas os itens com seus detalhes culinários
            $stmtItems = $pdo->prepare("
                SELECT 
                    id, product_name, size, flavor2_name, crust, extras, observation, quantity
                FROM order_items
                WHERE order_id = ?
            ");
            $stmtItems->execute([$ord['id']]);
            $ord['items'] = $stmtItems->fetchAll();
        }

        echo json_encode([
            'success' => true,
            'orders' => $orders,
            'timestamp' => time()
        ]);
        break;

    case 'update_status':
        $orderId = (int)($params['order_id'] ?? 0);
        $targetStatus = $params['status'] ?? ''; // 'Em preparo' ou 'Pronto'

        if (!in_array($targetStatus, ['Em preparo', 'Pronto'])) {
            echo json_encode(['success' => false, 'message' => 'Status inválido para a cozinha.']);
            exit;
        }

        $stmtCheck = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
        $stmtCheck->execute([$orderId]);
        $currStatus = $stmtCheck->fetchColumn();

        if (!$currStatus) {
            echo json_encode(['success' => false, 'message' => 'Pedido não encontrado.']);
            exit;
        }

        // Atualiza status
        $stmtUp = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmtUp->execute([$targetStatus, $orderId]);

        $comment = ($targetStatus === 'Em preparo') 
            ? 'Pizzaiolo iniciou o preparo dos itens no forno a lenha' 
            : 'Pizzaiolo finalizou o preparo. Pedido pronto!';

        logOrderStatusChange($pdo, $orderId, $currStatus, $targetStatus, $comment);

        echo json_encode([
            'success' => true,
            'message' => "Pedido #{$orderId} atualizado para '{$targetStatus}' com sucesso!",
            'new_status' => $targetStatus
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Ação de cozinha não reconhecida.']);
        break;
}
