<?php
/**
 * Prisca Pizza - API do Entregador
 * Perfil 4 — Entregador
 * Requisitos:
 * - Deve acessar um painel específico para entregas
 * - Pode visualizar os pedidos prontos para entrega
 * - Pode pegar um pedido e atualizá-lo para “Saiu para entrega”
 * - Deve visualizar nome, endereço e telefone do cliente APENAS dos pedidos que está entregando
 * - Deve visualizar forma de pagamento apenas para saber se há valor a receber na entrega
 * - Pode atualizar o pedido para “Entregue” ao concluir a entrega
 * - NÃO pode consultar senhas, pedidos de outros entregadores nem acessar funções administrativas
 * - Atualização automática a cada poucos segundos
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Apenas entregador e admin
checkAuth(['entregador', 'admin']);

$driverId = $_SESSION['user']['id'];
$isAdmin = ($_SESSION['user']['role'] === 'admin');

$action = $_GET['action'] ?? $_POST['action'] ?? 'get_delivery_orders';
$inputJSON = file_get_contents('php://input');
$inputData = json_decode($inputJSON, true) ?? [];
$params = !empty($inputData) ? $inputData : $_POST;

switch ($action) {
    case 'get_delivery_orders':
        // 1. Pedidos disponíveis na pizzaria (Prontos para entrega e ainda não assumidos por nenhum motorista)
        $stmtAvailable = $pdo->prepare("
            SELECT 
                o.id,
                o.status,
                o.order_type,
                o.delivery_neighborhood,
                o.created_at,
                o.total,
                o.payment_method
            FROM orders o
            WHERE o.order_type = 'entrega' 
              AND o.status = 'Pronto' 
              AND o.assigned_delivery_user_id IS NULL
            ORDER BY o.id ASC
        ");
        $stmtAvailable->execute();
        $availableOrders = $stmtAvailable->fetchAll();

        // 2. Pedidos em andamento deste entregador específico (Saiu para entrega)
        // Requisito: "Deve visualizar o nome, o endereço e o telefone do cliente apenas dos pedidos que está entregando"
        // "Deve visualizar a forma de pagamento apenas para saber se há valor a receber na entrega"
        $queryMy = "
            SELECT 
                o.id,
                o.status,
                o.customer_name,
                o.customer_phone,
                o.delivery_address,
                o.delivery_neighborhood,
                o.total,
                o.payment_method,
                o.payment_status,
                o.change_for,
                o.observation,
                o.created_at
            FROM orders o
            WHERE o.order_type = 'entrega'
              AND o.status = 'Saiu para entrega'
        ";
        if (!$isAdmin) {
            $queryMy .= " AND o.assigned_delivery_user_id = ?";
            $stmtMy = $pdo->prepare($queryMy . " ORDER BY o.id ASC");
            $stmtMy->execute([$driverId]);
        } else {
            $stmtMy = $pdo->query($queryMy . " ORDER BY o.id ASC");
        }
        $myActiveOrders = $stmtMy->fetchAll();

        // 3. Histórico recente de pedidos entregues por este entregador hoje
        $queryCompleted = "
            SELECT 
                o.id,
                o.customer_name,
                o.delivery_neighborhood,
                o.total,
                o.payment_method,
                o.completed_at
            FROM orders o
            WHERE o.order_type = 'entrega'
              AND o.status = 'Entregue'
              AND DATE(o.completed_at) = CURRENT_DATE()
        ";
        if (!$isAdmin) {
            $queryCompleted .= " AND o.assigned_delivery_user_id = ? ORDER BY o.completed_at DESC LIMIT 10";
            $stmtComp = $pdo->prepare($queryCompleted);
            $stmtComp->execute([$driverId]);
        } else {
            $stmtComp = $pdo->query($queryCompleted . " ORDER BY o.completed_at DESC LIMIT 10");
        }
        $completedToday = $stmtComp->fetchAll();

        echo json_encode([
            'success' => true,
            'available_orders' => $availableOrders,
            'my_active_orders' => $myActiveOrders,
            'completed_today' => $completedToday,
            'timestamp' => time()
        ]);
        break;

    case 'accept_delivery':
        // Entregador assume o pedido e muda para 'Saiu para entrega'
        $orderId = (int)($params['order_id'] ?? 0);

        $stmtCheck = $pdo->prepare("SELECT status, assigned_delivery_user_id FROM orders WHERE id = ?");
        $stmtCheck->execute([$orderId]);
        $ord = $stmtCheck->fetch();

        if (!$ord) {
            echo json_encode(['success' => false, 'message' => 'Pedido não encontrado.']);
            exit;
        }

        if ($ord['status'] !== 'Pronto') {
            echo json_encode(['success' => false, 'message' => "Este pedido não está disponível para entrega (Status atual: {$ord['status']})."]);
            exit;
        }

        if (!empty($ord['assigned_delivery_user_id']) && $ord['assigned_delivery_user_id'] != $driverId) {
            echo json_encode(['success' => false, 'message' => 'Este pedido já foi assumido por outro entregador.']);
            exit;
        }

        $stmtUp = $pdo->prepare("UPDATE orders SET status = 'Saiu para entrega', assigned_delivery_user_id = ? WHERE id = ?");
        $stmtUp->execute([$driverId, $orderId]);

        logOrderStatusChange($pdo, $orderId, 'Pronto', 'Saiu para entrega', "Entregador {$_SESSION['user']['name']} assumiu a rota de entrega.");

        echo json_encode([
            'success' => true,
            'message' => "Pedido #{$orderId} coletado! Boa rota e dirija com segurança.",
            'new_status' => 'Saiu para entrega'
        ]);
        break;

    case 'complete_delivery':
        // Concluir entrega: 'Saiu para entrega' -> 'Entregue'
        $orderId = (int)($params['order_id'] ?? 0);

        $stmtCheck = $pdo->prepare("SELECT status, assigned_delivery_user_id, payment_method FROM orders WHERE id = ?");
        $stmtCheck->execute([$orderId]);
        $ord = $stmtCheck->fetch();

        if (!$ord) {
            echo json_encode(['success' => false, 'message' => 'Pedido não encontrado.']);
            exit;
        }

        if (!$isAdmin && $ord['assigned_delivery_user_id'] != $driverId) {
            echo json_encode(['success' => false, 'message' => 'Você não tem permissão para concluir pedidos de outro entregador.']);
            exit;
        }

        // Se o pagamento foi em dinheiro na entrega, marca como pago
        $updateSql = "UPDATE orders SET status = 'Entregue', completed_at = NOW()";
        if ($ord['payment_method'] === 'dinheiro') {
            $updateSql .= ", payment_status = 'aprovado'";
        }
        $updateSql .= " WHERE id = ?";

        $stmtUp = $pdo->prepare($updateSql);
        $stmtUp->execute([$orderId]);

        logOrderStatusChange($pdo, $orderId, 'Saiu para entrega', 'Entregue', "Entrega concluída com sucesso pelo entregador {$_SESSION['user']['name']}.");

        echo json_encode([
            'success' => true,
            'message' => "Entrega do Pedido #{$orderId} confirmada com sucesso!",
            'new_status' => 'Entregue'
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Ação do entregador não reconhecida.']);
        break;
}
