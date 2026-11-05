<?php
/**
 * Prisca Pizza - API do Assistente Virtual IA (Prisca AI)
 * Cumpre rigorosamente todas as 7 regras:
 * 1. Atendimento educado, claro e cordial baseado no sistema.
 * 2. Pedidos, tamanhos, sabores e adicionais reais.
 * 3. Preços e promoções sem inventar valores ou cupons falsos.
 * 4. Entregas com taxa padrão (R$ 8,00) e prazos reais (40-50 min).
 * 5. Disponibilidade e verificação do horário (Ter a Dom 18h às 23h30).
 * 6. Segurança e privacidade (sem solicitar dados bancários/senhas).
 * 7. Confirmação e acompanhamento de status real do pedido (#PRISCA...).
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'mensagem' => 'Método não permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$mensagem = trim($input['mensagem'] ?? '');

if (empty($mensagem)) {
    echo json_encode(['status' => 'error', 'mensagem' => 'Mensagem vazia']);
    exit;
}

$pdo = getDbConnection();
$msgLower = mb_strtolower($mensagem, 'UTF-8');

// 1. VERIFICAÇÃO DE CONSULTA DE STATUS DE PEDIDO (ex: #PRISCA-1001 ou apenas 1001)
if (preg_match('/(?:#?PRISCA-?)?(\d{4})/i', $mensagem, $matches)) {
    $num = $matches[1];
    $codigoBusca = '#PRISCA-' . $num;

    $stmt = $pdo->prepare("SELECT p.*, u.nome as cliente_nome FROM pedidos p JOIN usuarios u ON p.cliente_id = u.id WHERE p.codigo = :codigo OR p.id = :id LIMIT 1");
    $stmt->execute([':codigo' => $codigoBusca, ':id' => (int)$num]);
    $pedido = $stmt->fetch();

    if ($pedido) {
        // Regra de segurança: Se o usuário estiver logado e for cliente, apenas o dono pode ver ou staff
        if (isset($_SESSION['usuario']) && $_SESSION['usuario']['perfil'] === ROLE_CLIENTE && $_SESSION['usuario']['id'] != $pedido['cliente_id']) {
            echo json_encode([
                'status' => 'success',
                'resposta' => "Por motivos de privacidade e segurança, não posso exibir detalhes de pedidos vinculados a outra conta. Você pode consultar seus próprios pedidos na aba **Meus Pedidos**!"
            ]);
            exit;
        }

        $tempoEst = $pedido['tempo_estimado_min'] ?? 45;
        $tipoEntrega = $pedido['tipo_entrega'] === 'retirada' ? 'Retirada no Balcão' : 'Entrega em Domicílio';
        $statusStr = $pedido['status'];

        $msgResp = "Encontrei o seu pedido **{$pedido['codigo']}**!\n\n";
        $msgResp .= "• **Status Atual:** {$statusStr}\n";
        $msgResp .= "• **Modalidade:** {$tipoEntrega}\n";
        $msgResp .= "• **Total:** " . formatMoney($pedido['total']) . "\n";
        $msgResp .= "• **Forma de Pagamento:** " . strtoupper($pedido['forma_pagamento']) . " ({$pedido['status_pagamento']})\n";
        $msgResp .= "• **Tempo Estimado:** {$tempoEst} minutos\n\n";

        if ($statusStr === 'Recebido') {
            $msgResp .= "Seu pedido já foi recebido pelo nosso caixa e em instantes entrará na esteira de preparo da nossa cozinha.";
        } elseif ($statusStr === 'Em preparo') {
            $msgResp .= "Nosso mestre pizzaiolo está preparando seus produtos com todo o carinho e forno a lenha no ponto!";
        } elseif ($statusStr === 'Pronto') {
            $msgResp .= $pedido['tipo_entrega'] === 'retirada' 
                ? "Seu pedido está prontinho e quentinho aguardando sua retirada no balcão!" 
                : "Seu pedido está embalado na caixa térmica e o entregador já está a postos para sair.";
        } elseif ($statusStr === 'Saiu para entrega') {
            $msgResp .= "O motoboy já está em trânsito com a sua pizza quentinha! Fique atento(a) ao interfone ou telefone.";
        } elseif ($statusStr === 'Entregue') {
            $msgResp .= "Este pedido já foi finalizado e entregue com sucesso! Esperamos que aproveite cada pedaço!";
        } elseif ($statusStr === 'Cancelado') {
            $msgResp .= "Este pedido consta como cancelado. Motivo registrado: " . ($pedido['motivo_cancelamento'] ?: 'Solicitado pelo cliente ou cancelado pelo caixa.');
        }

        echo json_encode(['status' => 'success', 'resposta' => $msgResp]);
        exit;
    }
}

// 2. HORÁRIO DE FUNCIONAMENTO (Regra 5)
if (strpos($msgLower, 'horario') !== false || strpos($msgLower, 'aberto') !== false || strpos($msgLower, 'funciona') !== false || strpos($msgLower, 'fecha') !== false) {
    $aberto = isStoreOpen();
    $statusTxt = $aberto 
        ? "Estamos **ABERTOS** agora mesmo e aceitando pedidos a todo vapor!" 
        : "No momento nossa cozinha está **fechada**.";
    
    $resposta = "{$statusTxt}\n\n"
        . "🕒 **Nosso Horário de Atendimento:**\n"
        . "• **Terça a Domingo:** das 18:00 às 23:30\n"
        . "• **Segunda-feira:** Fechado para descanso da equipe\n\n"
        . "Você pode montar seu pedido e personalizar qualquer sabor através do nosso cardápio!";
    
    echo json_encode(['status' => 'success', 'resposta' => $resposta]);
    exit;
}

// 3. TAXA E TEMPO DE ENTREGA (Regra 4)
if (strpos($msgLower, 'entrega') !== false || strpos($msgLower, 'taxa') !== false || strpos($msgLower, 'frete') !== false || strpos($msgLower, 'tempo') !== false || strpos($msgLower, 'demora') !== false) {
    $taxa = formatMoney(DEFAULT_DELIVERY_FEE);
    $resposta = "🛵 **Informações de Entrega da Prisca Pizza:**\n\n"
        . "• **Taxa de Entrega:** {$taxa} (valor fixo para nossa região de atendimento)\n"
        . "• **Tempo Médio de Entrega:** " . ESTIMATED_DELIVERY_TIME . "\n"
        . "• **Retirada no Balcão:** Sem taxa de entrega, pronto em aproximadamente " . ESTIMATED_PICKUP_TIME . "\n"
        . "• **Nosso Endereço para retirada:** " . APP_ADDRESS . "\n\n"
        . "Todas as pizzas são transportadas em bolsas térmicas seladas para garantir máxima crocância e temperatura!";

    echo json_encode(['status' => 'success', 'resposta' => $resposta]);
    exit;
}

// 4. FORMAS DE PAGAMENTO (Regra 6 e Regra de Negócio)
if (strpos($msgLower, 'pagamento') !== false || strpos($msgLower, 'cartao') !== false || strpos($msgLower, 'pix') !== false || strpos($msgLower, 'dinheiro') !== false || strpos($msgLower, 'troco') !== false) {
    $resposta = "💳 **Formas de Pagamento Aceitas:**\n\n"
        . "• **PIX:** Pagamento instantâneo via QR Code e código Copia e Cola gerado na hora no site.\n"
        . "• **Cartão (Crédito ou Débito):** Simulação com aprovação rápida e segura online.\n"
        . "• **Dinheiro:** Você paga diretamente ao entregador na chegada ou no balcão. Nosso sistema permite que você informe para quanto precisa de troco!\n\n"
        . "🔒 Nunca solicitamos sua senha bancária ou dados confidenciais.";

    echo json_encode(['status' => 'success', 'resposta' => $resposta]);
    exit;
}

// 5. TAMANHOS E FATIAS (Regra 2)
if (strpos($msgLower, 'tamanho') !== false || strpos($msgLower, 'fatia') !== false || strpos($msgLower, 'pedaco') !== false || strpos($msgLower, 'meio a meio') !== false) {
    $resposta = "🍕 **Tamanhos e Fatias de Nossas Pizzas:**\n\n"
        . "• **Broto:** 4 fatias (25cm) - 1 sabor (ideal para 1 pessoa)\n"
        . "• **Média:** 6 fatias (30cm) - até 2 sabores (serve 2 pessoas)\n"
        . "• **Grande:** 8 fatias (35cm) - até 2 sabores (serve 3 a 4 pessoas) — *A mais pedida!*\n"
        . "• **Família:** 12 fatias (40cm) - até 3 sabores (serve toda a família!)\n\n"
        . "💡 Você pode escolher até 2 sabores (meio a meio) nos tamanhos Média e Grande, e até 3 sabores no tamanho Família!";

    echo json_encode(['status' => 'success', 'resposta' => $resposta]);
    exit;
}

// 6. ESFIHAS
if (strpos($msgLower, 'esfiha') !== false || strpos($msgLower, 'esfirra') !== false) {
    $stmt = $pdo->query("SELECT nome, preco_base, descricao FROM produtos WHERE categoria_id = 2 AND disponivel = 1 ORDER BY preco_base ASC");
    $esfihas = $stmt->fetchAll();

    $resposta = "🥟 **Nossas Esfihas Abertas Artesanais:**\n\n";
    foreach ($esfihas as $esf) {
        $resposta .= "• **{$esf['nome']}** — " . formatMoney($esf['preco_base']) . "\n  _{$esf['descricao']}_\n";
    }
    $resposta .= "\nMassas super macias e douradas preparadas na hora com tempero autêntico árabe!";

    echo json_encode(['status' => 'success', 'resposta' => $resposta]);
    exit;
}

// 7. BEBIDAS E SOBREMESAS
if (strpos($msgLower, 'bebida') !== false || strpos($msgLower, 'refrigerante') !== false || strpos($msgLower, 'coca') !== false || strpos($msgLower, 'cerveja') !== false) {
    $stmt = $pdo->query("SELECT nome, preco_base FROM produtos WHERE categoria_id = 3 AND disponivel = 1 ORDER BY preco_base ASC");
    $bebidas = $stmt->fetchAll();

    $resposta = "🥤 **Bebidas Geladas Disponíveis:**\n\n";
    foreach ($bebidas as $b) {
        $resposta .= "• **{$b['nome']}** — " . formatMoney($b['preco_base']) . "\n";
    }

    echo json_encode(['status' => 'success', 'resposta' => $resposta]);
    exit;
}

if (strpos($msgLower, 'sobremesa') !== false || strpos($msgLower, 'doce') !== false || strpos($msgLower, 'churros') !== false || strpos($msgLower, 'tiramisu') !== false) {
    $stmt = $pdo->query("SELECT nome, preco_base, descricao FROM produtos WHERE categoria_id = 4 AND disponivel = 1 ORDER BY preco_base ASC");
    $sobremesas = $stmt->fetchAll();

    $resposta = "🍰 **Sobremesas Especiais Prisca:**\n\n";
    foreach ($sobremesas as $s) {
        $resposta .= "• **{$s['nome']}** — " . formatMoney($s['preco_base']) . "\n  _{$s['descricao']}_\n";
    }

    echo json_encode(['status' => 'success', 'resposta' => $resposta]);
    exit;
}

// 8. SABORES DE PIZZAS MAIS PEDIDOS / DESTAQUES (Regra 1 e 3)
if (strpos($msgLower, 'pizza') !== false || strpos($msgLower, 'sabor') !== false || strpos($msgLower, 'cardapio') !== false || strpos($msgLower, 'mais pedid') !== false) {
    $stmt = $pdo->query("SELECT nome, preco_base, descricao FROM produtos WHERE categoria_id = 1 AND disponivel = 1 ORDER BY destaque DESC, preco_base ASC LIMIT 6");
    $pizzas = $stmt->fetchAll();

    $resposta = "🍕 **Nossos Sabores de Pizza Mais Pedidos:**\n\n";
    foreach ($pizzas as $p) {
        $resposta .= "• **{$p['nome']}** (a partir de " . formatMoney($p['preco_base']) . ")\n  _{$p['descricao']}_\n\n";
    }
    $resposta .= "Você pode conferir todos os sabores na página **Cardápio**, personalizar bordas recheadas de Catupiry ou Cheddar e escolher tamanho!";

    echo json_encode(['status' => 'success', 'resposta' => $resposta]);
    exit;
}

// 9. RESPOSTA PADRÃO CORDIAL (Regra 1: se não souber com certeza, atende cordialmente e orienta o cliente)
$resposta = "Muito obrigado por sua mensagem! 😊\n\n"
    . "Posso te ajudar com qualquer uma das seguintes opções:\n"
    . "• 🍕 Ver os **sabores de pizzas e esfihas** cadastrados no cardápio\n"
    . "• 📏 Conhecer os **tamanhos e quantas fatias** cada um tem\n"
    . "• 🛵 Consultar a **taxa de entrega** (R$ 8,00) e **tempo estimado**\n"
    . "• 🕒 Saber nossos **horários de funcionamento**\n"
    . "• 🔍 Rastrear um pedido digitando o código (ex: **#PRISCA-1001**)\n\n"
    . "Como prefere que eu te ajude agora?";

echo json_encode(['status' => 'success', 'resposta' => $resposta]);
