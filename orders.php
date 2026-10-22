<?php
/**
 * Prisca Pizza - API de Pedidos do Cliente
 * Requisitos:
 * - O cliente deve ter cadastro e estar conectado para fazer pedido
 * - Resumo do pedido e valor total antes do pagamento
 * - Formas de pagamento: pix (código fictício), cartão (simulado), dinheiro (na entrega/retirada)
 * - Fluxo de status: Recebido -> Em preparo -> Pronto -> Saiu para entrega -> Entregue
 * - Auditoria: cada mudança registra quem, quando e status anterior
 * - Cliente só visualiza dados dos próprios pedidos
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth_check.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

$inputJSON = file_get_contents('php://input');
$inputData = json_decode($inputJSON, true) ?? [];
$params = !empty($inputData) ? $inputData : $_POST;

switch ($action) {
    case 'create':
        // 1. Cliente deve estar conectado
        // Requisito: "Para fazer um pedido, o cliente deve ter cadastro e estar conectado ao sistema."
        if (!isset($_SESSION['user']['id'])) {
            echo json_encode([
                'success' => false, 
                'need_login' => true,
                'message' => 'Para finalizar seu pedido, faça login ou cadastre-se na Prisca Pizza.'
            ]);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $orderType = in_array($params['order_type'] ?? '', ['retirada', 'entrega']) ? $params['order_type'] : 'entrega';
        $customerName = trim($params['customer_name'] ?? $_SESSION['user']['name']);
        $customerPhone = trim($params['customer_phone'] ?? $_SESSION['user']['phone'] ?? '');
        $deliveryAddress = trim($params['delivery_address'] ?? $_SESSION['user']['address'] ?? '');
        $deliveryNeighborhood = trim($params['delivery_neighborhood'] ?? $_SESSION['user']['neighborhood'] ?? '');
        $paymentMethod = in_array($params['payment_method'] ?? '', ['pix', 'cartao', 'dinheiro']) ? $params['payment_method'] : 'pix';
        $changeFor = !empty($params['change_for']) ? (float)$params['change_for'] : null;
        $observation = trim($params['observation'] ?? '');
        $items = $params['items'] ?? [];

        if (empty($items) || !is_array($items)) {
            echo json_encode(['success' => false, 'message' => 'Seu carrinho está vazio. Adicione produtos antes de finalizar.']);
            exit;
        }

        if (empty($customerName) || empty($customerPhone)) {
            echo json_encode(['success' => false, 'message' => 'Nome e telefone são obrigatórios para contato sobre a entrega.']);
            exit;
        }

        if ($orderType === 'entrega' && empty($deliveryAddress)) {
            echo json_encode(['success' => false, 'message' => 'Informe o endereço completo para realizarmos a entrega.']);
            exit;
        }

        // 2. Calcula e valida valores com segurança no servidor
        $subtotal = 0.00;
        $validatedItems = [];

        foreach ($items as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            $qty = max(1, (int)($item['quantity'] ?? 1));
            
            // Busca produto no banco
            $stmtProd = $pdo->prepare("SELECT p.*, c.slug as category_slug FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id = ? AND p.is_available = 1");
            $stmtProd->execute([$productId]);
            $product = $stmtProd->fetch();

            if (!$product) {
                continue;
            }

            $unitPrice = (float)$product['base_price'];
            $sizeName = trim($item['size'] ?? '');
            $flavor2Name = trim($item['flavor2_name'] ?? '');
            $crustName = trim($item['crust'] ?? '');
            $extras = is_array($item['extras'] ?? null) ? implode(', ', $item['extras']) : trim($item['extras'] ?? '');
            $itemObs = trim($item['observation'] ?? '');

            // Ajuste por tamanho
            if (!empty($sizeName)) {
                $stmtOpt = $pdo->prepare("SELECT price_modifier FROM product_options WHERE type = 'tamanho' AND name = ?");
                $stmtOpt->execute([$sizeName]);
                $mod = $stmtOpt->fetchColumn();
                if ($mod !== false) {
                    $unitPrice += (float)$mod;
                }
            }

            // Ajuste se for meio a meio com sabor de maior valor
            if (!empty($flavor2Name)) {
                $stmtF2 = $pdo->prepare("SELECT base_price FROM products WHERE name = ?");
                $stmtF2->execute([$flavor2Name]);
                $f2Price = $stmtF2->fetchColumn();
                if ($f2Price !== false && (float)$f2Price > (float)$product['base_price']) {
                    // Pela regra de pizzaria tradicional, prevalece o sabor de maior valor
                    $diff = (float)$f2Price - (float)$product['base_price'];
                    $unitPrice += $diff;
                }
            }

            // Ajuste por borda
            if (!empty($crustName)) {
                $stmtCrust = $pdo->prepare("SELECT price_modifier FROM product_options WHERE type = 'borda' AND name = ?");
                $stmtCrust->execute([$crustName]);
                $modCrust = $stmtCrust->fetchColumn();
                if ($modCrust !== false) {
                    $unitPrice += (float)$modCrust;
                }
            }

            // Ajuste por adicionais
            if (!empty($item['extras']) && is_array($item['extras'])) {
                foreach ($item['extras'] as $extraItem) {
                    $stmtEx = $pdo->prepare("SELECT price_modifier FROM product_options WHERE type = 'adicional' AND name = ?");
                    $stmtEx->execute([$extraItem]);
                    $modEx = $stmtEx->fetchColumn();
                    if ($modEx !== false) {
                        $unitPrice += (float)$modEx;
                    }
                }
            }

            $totalItemPrice = $unitPrice * $qty;
            $subtotal += $totalItemPrice;

            $validatedItems[] = [
                'product_id' => $product['id'],
                'product_name' => $product['name'],
                'size' => $sizeName,
                'flavor2_name' => $flavor2Name,
                'crust' => $crustName,
                'extras' => $extras,
                'observation' => $itemObs,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'total_price' => $totalItemPrice
            ];
        }

        if (empty($validatedItems)) {
            echo json_encode(['success' => false, 'message' => 'Nenhum item válido foi processado no carrinho.']);
            exit;
        }

        // Taxa de entrega
        $deliveryFee = ($orderType === 'entrega') ? (float)getSetting($pdo, 'delivery_fee_default', 7.00) : 0.00;
        $total = $subtotal + $deliveryFee;

        // Simulação de status do pagamento
        // Requisito: "No pix, o sistema deve gerar um código pix fictício para o cliente 'pagar'"
        // "No cartão, simular aprovação. No dinheiro, na entrega/retirada."
        $pixCode = null;
        $paymentStatus = 'pendente';

        if ($paymentMethod === 'pix') {
            $paymentStatus = 'pendente';
            $pixKey = getSetting($pdo, 'pix_key', 'financeiro@priscapizza.com.br');
            $pixCode = "00020126580014br.gov.bcb.pix0136{$pixKey}5204000053039865405" . number_format($total, 2, '.', '') . "5802BR5916PRISCA PIZZA6009SAO PAULO62070503***6304" . strtoupper(substr(md5(uniqid()), 0, 4));
        } elseif ($paymentMethod === 'cartao') {
            // Simulação de aprovação imediata do cartão
            $paymentStatus = 'aprovado';
        } elseif ($paymentMethod === 'dinheiro') {
            $paymentStatus = 'pago_na_entrega';
        }

        // 3. Salva pedido dentro de transação segura
        $pdo->beginTransaction();
        try {
            $stmtOrder = $pdo->prepare("
                INSERT INTO orders (
                    user_id, order_type, customer_name, customer_phone, delivery_address, delivery_neighborhood,
                    subtotal, delivery_fee, total, payment_method, payment_status, pix_code, change_for,
                    status, estimated_minutes, observation
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Recebido', 45, ?)
            ");
            $stmtOrder->execute([
                $userId,
                $orderType,
                $customerName,
                $customerPhone,
                $deliveryAddress,
                $deliveryNeighborhood,
                $subtotal,
                $deliveryFee,
                $total,
                $paymentMethod,
                $paymentStatus,
                $pixCode,
                $changeFor,
                $observation
            ]);
            $orderId = $pdo->lastInsertId();

            // Salva itens
            $stmtItem = $pdo->prepare("
                INSERT INTO order_items (
                    order_id, product_id, product_name, size, flavor2_name, crust, extras, observation,
                    quantity, unit_price, total_price
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($validatedItems as $vi) {
                $stmtItem->execute([
                    $orderId,
                    $vi['product_id'],
                    $vi['product_name'],
                    $vi['size'],
                    $vi['flavor2_name'],
                    $vi['crust'],
                    $vi['extras'],
                    $vi['observation'],
                    $vi['quantity'],
                    $vi['unit_price'],
                    $vi['total_price']
                ]);
            }

            // Registra histórico de status
            logOrderStatusChange($pdo, $orderId, 'Novo Pedido', 'Recebido', "Pedido realizado pelo cliente via site. Pagamento: {$paymentMethod}");

            // Registra auditoria do sistema
            logSystemAction($pdo, 'order_created', "Pedido #{$orderId} criado com valor R$ " . number_format($total, 2, ',', '.'), $userId, $customerName, 'cliente');

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => "Pedido #{$orderId} realizado com sucesso!",
                'order_id' => $orderId,
                'total' => $total,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'pix_code' => $pixCode,
                'redirect' => "pedido-detalhe.php?id={$orderId}"
            ]);

        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Falha ao processar pedido: ' . $e->getMessage()]);
        }
        break;

    case 'my_orders':
        // Requisito: "Não pode visualizar, alterar ou cancelar pedidos de outros clientes."
        if (!isset($_SESSION['user']['id'])) {
            echo json_encode(['success' => false, 'message' => 'Faça login para consultar seus pedidos.']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $orders = $stmt->fetchAll();

        // Anexa itens a cada pedido
        foreach ($orders as &$ord) {
            $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmtItems->execute([$ord['id']]);
            $ord['items'] = $stmtItems->fetchAll();
        }

        echo json_encode(['success' => true, 'orders' => $orders]);
        break;

    case 'get_order':
        $orderId = (int)($_GET['id'] ?? 0);
        if ($orderId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de pedido inválido.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Pedido não encontrado.']);
            exit;
        }

        // Validação de segurança de acesso: cliente só vê o próprio pedido
        $currentUser = currentUser();
        if (!$currentUser) {
            echo json_encode(['success' => false, 'message' => 'Acesso não autorizado. Faça login.']);
            exit;
        }

        if ($currentUser['role'] === 'cliente' && $order['user_id'] != $currentUser['id']) {
            echo json_encode(['success' => false, 'message' => 'Acesso negado: você não tem permissão para visualizar este pedido.']);
            exit;
        }

        // Busca itens do pedido
        $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmtItems->execute([$orderId]);
        $order['items'] = $stmtItems->fetchAll();

        // Busca histórico de status do pedido
        $stmtLogs = $pdo->prepare("SELECT * FROM order_status_logs WHERE order_id = ? ORDER BY created_at ASC");
        $stmtLogs->execute([$orderId]);
        $order['status_history'] = $stmtLogs->fetchAll();

        echo json_encode(['success' => true, 'order' => $order]);
        break;

    case 'simulate_pix_payment':
        // Simulação do cliente confirmando pagamento Pix
        $orderId = (int)($params['order_id'] ?? 0);
        if ($orderId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Pedido não encontrado.']);
            exit;
        }

        // Atualiza status do pagamento para aprovado
        $stmtUp = $pdo->prepare("UPDATE orders SET payment_status = 'aprovado' WHERE id = ?");
        $stmtUp->execute([$orderId]);

        logSystemAction($pdo, 'pix_payment_approved', "Pagamento Pix simulado e aprovado com sucesso para o pedido #{$orderId}");

        echo json_encode([
            'success' => true,
            'message' => 'Pagamento Pix simulado e confirmado com sucesso!'
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Ação de pedidos não reconhecida.']);
        break;
}
