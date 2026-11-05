<?php
/**
 * Prisca Pizza - Processamento e Finalização de Pedido (Checkout)
 * Simula pagamentos (Pix fictício, Cartão simulado, Dinheiro com troco)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'mensagem' => 'Método inválido']);
    exit;
}

// Cliente deve estar autenticado para realizar pedidos (Regra)
if (!isLoggedIn()) {
    echo json_encode([
        'status' => 'error', 
        'requer_login' => true,
        'mensagem' => 'Para finalizar seu pedido, você precisa estar conectado à sua conta.'
    ]);
    exit;
}

$pdo = getDbConnection();
$usuario = getLoggedUser();

$input = json_decode(file_get_contents('php://input'), true);

$tipoEntrega = $input['tipo_entrega'] ?? 'entrega';
$enderecoEntrega = trim($input['endereco_entrega'] ?? '');
$telefoneContato = trim($input['telefone_contato'] ?? '');
$formaPagamento = $input['forma_pagamento'] ?? 'pix';
$trocoPara = !empty($input['troco_para']) ? (float)$input['troco_para'] : null;
$observacoesGerais = trim($input['observacoes'] ?? '');
$itens = $input['itens'] ?? [];

// Validações básicas
if (empty($itens) || !is_array($itens)) {
    echo json_encode(['status' => 'error', 'mensagem' => 'Sua sacola de compras está vazia.']);
    exit;
}

if ($tipoEntrega === 'entrega' && empty($enderecoEntrega)) {
    echo json_encode(['status' => 'error', 'mensagem' => 'Informe o endereço completo de entrega.']);
    exit;
}

if (empty($telefoneContato)) {
    echo json_encode(['status' => 'error', 'mensagem' => 'Informe um telefone para contato.']);
    exit;
}

// Cálculo do Subtotal e Itens
$subtotal = 0.0;
$itensProcessados = [];

foreach ($itens as $item) {
    $produtoId = (int)($item['produto_id'] ?? 0);
    $quantidade = max(1, (int)($item['quantidade'] ?? 1));
    $tamanhoNome = $item['tamanho_nome'] ?? 'Padrão';
    $saborSecundarioNome = $item['sabor_secundario_nome'] ?? null;
    $adicionais = $item['adicionais'] ?? [];
    $observacoes = trim($item['observacoes'] ?? '');
    $precoUnitario = (float)($item['preco_unitario'] ?? 0.0);

    // Valida no banco para garantir consistência
    $stmtProd = $pdo->prepare("SELECT nome, preco_base, disponivel FROM produtos WHERE id = :id");
    $stmtProd->execute([':id' => $produtoId]);
    $prodDb = $stmtProd->fetch();

    if (!$prodDb) {
        continue;
    }

    if (!$prodDb['disponivel']) {
        echo json_encode(['status' => 'error', 'mensagem' => "O produto '{$prodDb['nome']}' não está disponível no momento."]);
        exit;
    }

    $subtotalItem = $precoUnitario * $quantidade;
    $subtotal += $subtotalItem;

    $itensProcessados[] = [
        'produto_id' => $produtoId,
        'nome_produto' => $prodDb['nome'],
        'tamanho_nome' => $tamanhoNome,
        'sabor_secundario_nome' => $saborSecundarioNome,
        'adicionais_selecionados' => is_array($adicionais) ? implode(', ', $adicionais) : $adicionais,
        'observacoes' => $observacoes,
        'quantidade' => $quantidade,
        'preco_unitario' => $precoUnitario,
        'subtotal_item' => $subtotalItem
    ];
}

if (empty($itensProcessados)) {
    echo json_encode(['status' => 'error', 'mensagem' => 'Nenhum produto válido encontrado no pedido.']);
    exit;
}

$taxaEntrega = ($tipoEntrega === 'entrega') ? DEFAULT_DELIVERY_FEE : 0.00;
$total = $subtotal + $taxaEntrega;

// Tratamento e Simulação de Pagamento (Regra: simular Pix fictício, Cartão com aprovação imediata, Dinheiro na entrega)
$statusPagamento = 'pendente';
$pixCodigo = null;
$pixQrCode = null;

if ($formaPagamento === 'pix') {
    // Código PIX Copia e Cola fictício no formato padrão BR Code
    $valorPixFormatado = number_format($total, 2, '.', '');
    $pixCodigo = "00020126580014BR.GOV.BCB.PIX0136priscapizza-pix-ficticio-bcb-br520400005303986540" . strlen($valorPixFormatado) . $valorPixFormatado . "5802BR5912Prisca Pizza6009Sao Paulo62070503***6304ABCD";
    
    // QR Code visual dinâmico via QuickChart API
    $pixQrCode = "https://quickchart.io/qr?text=" . urlencode($pixCodigo) . "&size=250&margin=1";
    $statusPagamento = 'pago'; // Simula aprovação imediata para melhor experiência de teste
} elseif ($formaPagamento === 'cartao') {
    $statusPagamento = 'pago'; // Simulação de aprovação sem reter dados do cartão
} elseif ($formaPagamento === 'dinheiro') {
    $statusPagamento = 'a_receber'; // Será recebido na entrega pelo entregador ou no balcão
}

// Gera código legível do pedido (#PRISCA-XXXX)
$codigoPedido = '#PRISCA-' . rand(1100, 9990);

$tempoEstimadoMin = ($tipoEntrega === 'entrega') ? 45 : 25;

try {
    $pdo->beginTransaction();

    $stmtInsertPedido = $pdo->prepare("
        INSERT INTO pedidos (
            codigo, cliente_id, tipo_entrega, endereco_entrega, telefone_contato,
            subtotal, taxa_entrega, total, forma_pagamento, status_pagamento,
            troco_para, pix_codigo, pix_qrcode, status, tempo_estimado_min, observacoes
        ) VALUES (
            :codigo, :cliente_id, :tipo_entrega, :endereco_entrega, :telefone_contato,
            :subtotal, :taxa_entrega, :total, :forma_pagamento, :status_pagamento,
            :troco_para, :pix_codigo, :pix_qrcode, 'Recebido', :tempo_estimado_min, :observacoes
        )
    ");

    $stmtInsertPedido->execute([
        ':codigo' => $codigoPedido,
        ':cliente_id' => $usuario['id'],
        ':tipo_entrega' => $tipoEntrega,
        ':endereco_entrega' => ($tipoEntrega === 'entrega') ? $enderecoEntrega : 'Retirada no Balcão Prisca Pizza',
        ':telefone_contato' => $telefoneContato,
        ':subtotal' => $subtotal,
        ':taxa_entrega' => $taxaEntrega,
        ':total' => $total,
        ':forma_pagamento' => $formaPagamento,
        ':status_pagamento' => $statusPagamento,
        ':troco_para' => $trocoPara,
        ':pix_codigo' => $pixCodigo,
        ':pix_qrcode' => $pixQrCode,
        ':tempo_estimado_min' => $tempoEstimadoMin,
        ':observacoes' => $observacoesGerais
    ]);

    $pedidoId = $pdo->lastInsertId();

    // Insere os itens
    $stmtItem = $pdo->prepare("
        INSERT INTO itens_pedido (
            pedido_id, produto_id, nome_produto, tamanho_nome, sabor_secundario_nome,
            adicionais_selecionados, observacoes, quantidade, preco_unitario, subtotal_item
        ) VALUES (
            :pedido_id, :produto_id, :nome_produto, :tamanho_nome, :sabor_secundario_nome,
            :adicionais_selecionados, :observacoes, :quantidade, :preco_unitario, :subtotal_item
        )
    ");

    foreach ($itensProcessados as $item) {
        $stmtItem->execute([
            ':pedido_id' => $pedidoId,
            ':produto_id' => $item['produto_id'],
            ':nome_produto' => $item['nome_produto'],
            ':tamanho_nome' => $item['tamanho_nome'],
            ':sabor_secundario_nome' => $item['sabor_secundario_nome'],
            ':adicionais_selecionados' => $item['adicionais_selecionados'],
            ':observacoes' => $item['observacoes'],
            ':quantidade' => $item['quantidade'],
            ':preco_unitario' => $item['preco_unitario'],
            ':subtotal_item' => $item['subtotal_item']
        ]);
    }

    // Registra na Auditoria (Regra CORE)
    registrarAuditoria(
        $pedidoId,
        'pedido_criado',
        null,
        STATUS_RECEBIDO,
        "Pedido criado pelo cliente {$usuario['nome']}. Total: " . formatMoney($total) . ". Pagamento: {$formaPagamento} ({$statusPagamento})."
    );

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'mensagem' => 'Pedido realizado com sucesso!',
        'codigo' => $codigoPedido,
        'pedido_id' => $pedidoId,
        'redirect_url' => BASE_URL . '/rastreio.php?codigo=' . urlencode($codigoPedido)
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro ao salvar pedido: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'mensagem' => 'Erro interno ao salvar pedido. Tente novamente.']);
}
