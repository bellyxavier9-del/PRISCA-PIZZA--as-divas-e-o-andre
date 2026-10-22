<?php
/**
 * Prisca Pizza - API do Atendente de Caixa
 * Perfil 2 — Atendente de caixa
 * Requisitos:
 * - Acessar painel interno de gerenciamento de pedidos
 * - Visualizar novos pedidos, conferir informações e encaminhá-los para a cozinha
 * - Verificar status dos pagamentos
 * - Atualizar estados do pedido conforme permissões
 * - Organizar pedidos para retirada ou entrega
 * - Em pedidos para retirada, concluir quando o cliente retirar
 * - Não pode consultar senhas dos clientes
 * - Polling simples a cada poucos segundos
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Apenas atendente e admin podem acessar
checkAuth(['atendente', 'admin']);

$action = $_GET['action'] ?? $_POST['action'] ?? 'get_orders';
$inputJSON = file_get_contents('php://input');
$inputData = json_decode($inputJSON, true) ?? [];
$params = !empty($inputData) ? $inputData : $_POST;

switch ($action) {
    case 'get_orders':
        $filterStatus = $_GET['status'] ?? 'all';
        $filterType = $_GET['order_type'] ?? 'all';

        $query = "SELECT o.*, u.email as customer_email, u.name as registered_name,
                         del.name as delivery_person_name
                  FROM orders o
                  JOIN users u ON u.id = o.user_id
                  LEFT JOIN users del ON del.id = o.assigned_delivery_user_id
                  WHERE 1=1";
        $binds = [];

        if ($filterStatus !== 'all' && !empty($filterStatus)) {
            $query .= " AND o.status = ?";
            $binds[] = $filterStatus;
        }

        if ($filterType !== 'all' && !empty($filterType)) {
            $query .= " AND o.order_type = ?";
            $binds[] = $filterType;
        }

        $query .= " ORDER BY CASE 
                        WHEN o.status = 'Recebido' THEN 1
                        WHEN o.status = 'Em preparo' THEN 2
                        WHEN o.status = 'Pronto' THEN 3
                        WHEN o.status = 'Saiu para entrega' THEN 4
                        ELSE 5 
                    END, o.id DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($binds);
        $orders = $stmt->fetchAll();

        // Anexa itens a cada pedido
        foreach ($orders as &$ord) {
            $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmtItems->execute([$ord['id']]);
            $ord['items'] = $stmtItems->fetchAll();
        }

        // Estatísticas rápidas de contagem para os badges do painel
        $stmtStats = $pdo->query("
            SELECT 
                COUNT(CASE WHEN status = 'Recebido' THEN 1 END) as count_recebido,
                COUNT(CASE WHEN status = 'Em preparo' THEN 1 END) as count_preparo,
                COUNT(CASE WHEN status = 'Pronto' THEN 1 END) as count_pronto,
                COUNT(CASE WHEN status = 'Saiu para entrega' THEN 1 END) as count_saiu,
                COUNT(CASE WHEN status = 'Entregue' AND DATE(created_at) = CURRENT_DATE() THEN 1 END) as count_entregue_hoje,
                COUNT(CASE WHEN status = 'Cancelado' AND DATE(created_at) = CURRENT_DATE() THEN 1 END) as count_cancelado_hoje
            FROM orders
        ");
        $stats = $stmtStats->fetch();

        echo json_encode([
            'success' => true,
            'orders' => $orders,
            'stats' => $stats,
            'timestamp' => time()
        ]);
        break;

    case 'forward_to_kitchen':
        // Encaminhar pedido para cozinha: Recebido -> Em preparo
        $orderId = (int)($params['order_id'] ?? 0);
        $stmtCheck = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
        $stmtCheck->execute([$orderId]);
        $currStatus = $stmtCheck->fetchColumn();

        if (!$currStatus) {
            echo json_encode(['success' => false, 'message' => 'Pedido não encontrado.']);
            exit;
        }

        if ($currStatus !== 'Recebido') {
            echo json_encode(['success' => false, 'message' => "Pedido já está no status '{$currStatus}'. Apenas pedidos 'Recebido' podem ser encaminhados."]);
            exit;
        }

        $stmtUp = $pdo->prepare("UPDATE orders SET status = 'Em preparo' WHERE id = ?");
        $stmtUp->execute([$orderId]);

        logOrderStatusChange($pdo, $orderId, 'Recebido', 'Em preparo', 'Atendente conferiu e encaminhou para a cozinha');

        echo json_encode([
            'success' => true,
            'message' => "Pedido #{$orderId} encaminhado para a cozinha com sucesso!",
            'new_status' => 'Em preparo'
        ]);
        break;

    case 'confirm_pickup':
        // Concluir retirada no balcão: Pronto -> Entregue
        $orderId = (int)($params['order_id'] ?? 0);
        $stmtCheck = $pdo->prepare("SELECT status, order_type FROM orders WHERE id = ?");
        $stmtCheck->execute([$orderId]);
        $ord = $stmtCheck->fetch();

        if (!$ord) {
            echo json_encode(['success' => false, 'message' => 'Pedido não encontrado.']);
            exit;
        }

        if ($ord['order_type'] !== 'retirada') {
            echo json_encode(['success' => false, 'message' => 'Apenas pedidos de retirada no balcão podem ser concluídos pelo caixa.']);
            exit;
        }

        $stmtUp = $pdo->prepare("UPDATE orders SET status = 'Entregue', completed_at = NOW(), payment_status = 'aprovado' WHERE id = ?");
        $stmtUp->execute([$orderId]);

        logOrderStatusChange($pdo, $orderId, $ord['status'], 'Entregue', 'Cliente retirou o pedido no balcão (Concluído pelo Caixa)');

        echo json_encode([
            'success' => true,
            'message' => "Pedido de retirada #{$orderId} finalizado com sucesso!",
            'new_status' => 'Entregue'
        ]);
        break;

    case 'cancel_order':
        $orderId = (int)($params['order_id'] ?? 0);
        $reason = trim($params['reason'] ?? 'Cancelado pelo atendente de caixa');

        $stmtCheck = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
        $stmtCheck->execute([$orderId]);
        $currStatus = $stmtCheck->fetchColumn();

        if (!$currStatus) {
            echo json_encode(['success' => false, 'message' => 'Pedido não encontrado.']);
            exit;
        }

        if ($currStatus === 'Entregue') {
            echo json_encode(['success' => false, 'message' => 'Não é possível cancelar um pedido já entregue.']);
            exit;
        }

        $stmtUp = $pdo->prepare("UPDATE orders SET status = 'Cancelado' WHERE id = ?");
        $stmtUp->execute([$orderId]);

        logOrderStatusChange($pdo, $orderId, $currStatus, 'Cancelado', $reason);

        echo json_encode([
            'success' => true,
            'message' => "Pedido #{$orderId} foi cancelado.",
            'new_status' => 'Cancelado'
        ]);
        break;

    case 'update_payment_status':
        $orderId = (int)($params['order_id'] ?? 0);
        $newPaymentStatus = in_array($params['payment_status'] ?? '', ['aprovado', 'pendente', 'pago_na_entrega']) ? $params['payment_status'] : 'aprovado';

        $stmtUp = $pdo->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
        $stmtUp->execute([$newPaymentStatus, $orderId]);

        logSystemAction($pdo, 'payment_status_update', "Atendente alterou status do pagamento do pedido #{$orderId} para {$newPaymentStatus}");

        echo json_encode([
            'success' => true,
            'message' => "Status de pagamento do pedido #{$orderId} atualizado para '{$newPaymentStatus}'!",
            'payment_status' => $newPaymentStatus
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Ação do atendente não reconhecida.']);
        break;
}
