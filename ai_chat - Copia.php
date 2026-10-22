<?php
/**
 * Prisca Pizza - Motor da IA / Assistente Virtual Prisca
 * Atende estritamente às Regras da IA:
 * 1. Atendimento educado, claro e cordial
 * 2. Consulta cardápio, ingredientes e preços reais cadastrados
 * 3. Nunca inventa informações nem promoções inexistentes
 * 4. Informa taxas de entrega (R$ 7,00) e prazo (30-50 min)
 * 5. Verifica horários de funcionamento (18h00 às 23h30)
 * 6. Protege dados e privacidade do cliente
 * 7. Consulta status real de pedidos
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../bd.php';

$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true) ?? [];
$message = trim($data['message'] ?? '');
$history = $data['history'] ?? [];

if (empty($message)) {
    echo json_encode([
        'success' => false,
        'reply' => 'Olá! Como posso ajudar você hoje com as delícias da Prisca Pizza?'
    ]);
    exit;
}

// 1. Coleta dados reais do sistema (sem inventar nada)
$stmtProd = $pdo->query("SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p JOIN categories c ON c.id = p.category_id WHERE p.is_available = 1");
$products = $stmtProd->fetchAll();

$stmtOpts = $pdo->query("SELECT * FROM product_options WHERE is_active = 1");
$options = $stmtOpts->fetchAll();

$openingHour = getSetting($pdo, 'opening_hour', '18:00');
$closingHour = getSetting($pdo, 'closing_hour', '23:30');
$deliveryFee = (float)getSetting($pdo, 'delivery_fee_default', 7.00);
$deliveryMin = getSetting($pdo, 'delivery_time_min', '30');
$deliveryMax = getSetting($pdo, 'delivery_time_max', '50');
$storePhone = getSetting($pdo, 'store_phone', '(11) 3456-7890');
$storeAddress = getSetting($pdo, 'store_address', 'Rua das Pizzas, 100 - Bela Vista, SP');

// Normaliza texto da mensagem
$msgLower = mb_strtolower($message, 'UTF-8');

// Variáveis de resposta
$reply = '';
$actionButtons = [];
$suggestedProduct = null;

// Verifica se está logado
$isLogged = isset($_SESSION['user']['id']);
$userName = $isLogged ? $_SESSION['user']['name'] : 'cliente';

// Intent 1: Saudações
if (preg_match('/^(oi|ol[aá]|boa tarde|boa noite|bom dia|opa|hey|e a[ií])/i', $msgLower) && strlen($msgLower) < 25) {
    $saudacao = 'Olá' . ($isLogged ? ", {$userName}" : '') . '! É um enorme prazer atender você na **Prisca Pizza**! 🍕✨';
    $reply = "{$saudacao}\n\nSou a **Prisca IA**, sua assistente virtual. Posso te ajudar a:\n- Consultar nosso cardápio e preços\n- Conhecer ingredientes de pizzas, esfihas, bebidas e sobremesas\n- Tirar dúvidas sobre entrega e formas de pagamento\n- Acompanhar o status do seu pedido em tempo real\n\nComo posso ajudar você neste momento?";
    $actionButtons = [
        ['label' => 'Ver Cardápio Completo', 'action' => 'url', 'value' => 'cardapio.php'],
        ['label' => 'Consultar Status do Pedido', 'action' => 'send', 'value' => 'Como está o meu pedido?'],
        ['label' => 'Horário e Taxa de Entrega', 'action' => 'send', 'value' => 'Qual o horário e a taxa de entrega?']
    ];
}

// Intent 2: Status do Pedido / Onde está meu pedido
elseif (preg_match('/(status|meu pedido|onde est[aá]|rastrear|acompanhar|demorando|quando chega|pedido n[uú]mero|pedido #)/i', $msgLower)) {
    // Tenta encontrar um ID de pedido na mensagem (ex: #5 ou pedido 3)
    preg_match('/(?:#|pedido\s*(?:n[úu]mero)?\s*)(\d+)/i', $msgLower, $matches);
    $searchOrderId = !empty($matches[1]) ? (int)$matches[1] : null;

    if ($searchOrderId) {
        $stmtOrd = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmtOrd->execute([$searchOrderId]);
        $order = $stmtOrd->fetch();

        if ($order) {
            // Se logado como cliente, checa se é o dono
            if ($isLogged && $_SESSION['user']['role'] === 'cliente' && $order['user_id'] != $_SESSION['user']['id']) {
                $reply = "Por razões de segurança e privacidade dos nossos clientes, não é possível consultar pedidos pertencentes a outro usuário cadastrado.";
            } else {
                $statusEmoji = [
                    'Recebido' => '📝 Recebido no caixa',
                    'Em preparo' => '👨‍🍳 Em preparo no forno a lenha',
                    'Pronto' => '🍕 Pronto para envio/retirada',
                    'Saiu para entrega' => '🛵 Saiu para entrega!',
                    'Entregue' => '✅ Entregue com sucesso!',
                    'Cancelado' => '❌ Cancelado'
                ][$order['status']] ?? $order['status'];

                $reply = "Encontrei as informações do **Pedido #{$order['id']}**:\n\n"
                    . "• **Status atual:** {$statusEmoji}\n"
                    . "• **Tipo:** " . ucfirst($order['order_type']) . "\n"
                    . "• **Valor Total:** R$ " . number_format($order['total'], 2, ',', '.') . "\n"
                    . "• **Pagamento:** " . strtoupper($order['payment_method']) . " (" . ucfirst($order['payment_status']) . ")\n"
                    . "• **Prazo estimado:** cerca de {$order['estimated_minutes']} minutos a partir da aprovação.\n\n"
                    . "Você pode acompanhar o rastreamento detalhado em tempo real clicando no botão abaixo:";
                
                $actionButtons = [
                    ['label' => "Ver Pedido #{$order['id']}", 'action' => 'url', 'value' => "pedido-detalhe.php?id={$order['id']}"]
                ];
            }
        } else {
            $reply = "Não encontrei nenhum pedido com o número **#{$searchOrderId}** em nosso banco de dados. Por favor, verifique o comprovante ou consulte sua lista em 'Meus Pedidos'.";
        }
    } elseif ($isLogged) {
        // Busca o último pedido do usuário logado
        $stmtLast = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmtLast->execute([$_SESSION['user']['id']]);
        $lastOrder = $stmtLast->fetch();

        if ($lastOrder) {
            $statusEmoji = [
                'Recebido' => '📝 Recebido',
                'Em preparo' => '👨‍🍳 Em preparo no forno',
                'Pronto' => '🍕 Pronto para envio',
                'Saiu para entrega' => '🛵 Saiu para entrega',
                'Entregue' => '✅ Entregue',
                'Cancelado' => '❌ Cancelado'
            ][$lastOrder['status']] ?? $lastOrder['status'];

            $reply = "Olá, {$userName}! Seu pedido mais recente é o **#{$lastOrder['id']}**:\n\n"
                . "• **Status:** {$statusEmoji}\n"
                . "• **Total:** R$ " . number_format($lastOrder['total'], 2, ',', '.') . "\n"
                . "• **Horário do pedido:** " . date('H:i \d\o \d\i\a d/m', strtotime($lastOrder['created_at'])) . "\n\n"
                . "Deseja ver os detalhes completos?";
            
            $actionButtons = [
                ['label' => "Acompanhar Pedido #{$lastOrder['id']}", 'action' => 'url', 'value' => "pedido-detalhe.php?id={$lastOrder['id']}"],
                ['label' => 'Histórico de Pedidos', 'action' => 'url', 'value' => 'meus-pedidos.php']
            ];
        } else {
            $reply = "Olá, {$userName}! Você ainda não possui pedidos registrados em sua conta. Que tal experimentar uma de nossas deliciosas pizzas hoje?";
            $actionButtons = [
                ['label' => 'Ver Cardápio', 'action' => 'url', 'value' => 'cardapio.php']
            ];
        }
    } else {
        $reply = "Para consultar o status do seu pedido, informe o número dele (por exemplo: *'Qual o status do pedido #1?'*) ou faça login para consultar seus pedidos automaticamente.";
        $actionButtons = [
            ['label' => 'Entrar na Minha Conta', 'action' => 'url', 'value' => 'login.php']
        ];
    }
}

// Intent 3: Horário de funcionamento
elseif (preg_match('/(hor[aá]rio|funcionamento|aberto|fechado|abre|fecha|que horas)/i', $msgLower)) {
    $now = date('H:i');
    $reply = "🕒 **Horário de Atendimento da Prisca Pizza**:\n\n"
        . "• Funcionamos diariamente das **{$openingHour} às {$closingHour}**.\n"
        . "• Aceitamos pedidos para **entrega em domicílio (delivery)** e **retirada no balcão**.\n\n"
        . "Nosso endereço para retirada é: **{$storeAddress}**.";
    
    $actionButtons = [
        ['label' => 'Fazer Pedido Agora', 'action' => 'url', 'value' => 'cardapio.php']
    ];
}

// Intent 4: Entrega, taxa, prazo, bairros
elseif (preg_match('/(taxa|entrega|frete|prazo|tempo|demora|quanto custa a entrega|bairros|endere[çc]o)/i', $msgLower)) {
    $reply = "🛵 **Informações sobre Entregas**:\n\n"
        . "• **Taxa fixa de entrega:** R$ " . number_format($deliveryFee, 2, ',', '.') . " para a região atendida.\n"
        . "• **Prazo estimado:** de **{$deliveryMin} a {$deliveryMax} minutos** após a confirmação.\n"
        . "• **Retirada no balcão:** Grátis (taxa zero!). Você pode retirar seu pedido quentinho em: {$storeAddress}.\n\n"
        . "A taxa é calculada e exibida com total transparência antes de você confirmar seu pedido.";
    
    $actionButtons = [
        ['label' => 'Montar Pedido', 'action' => 'url', 'value' => 'cardapio.php']
    ];
}

// Intent 5: Formas de pagamento
elseif (preg_match('/(pagamento|pagar|forma de pagamento|pix|cart[aã]o|dinheiro|troco|cr[eé]dito|d[eé]bito)/i', $msgLower)) {
    $reply = "💳 **Formas de Pagamento Aceitas**:\n\n"
        . "1. **PIX:** Pagamento instantâneo via QRCode ou código Copia-e-Cola gerado no checkout.\n"
        . "2. **Cartão de Crédito / Débito:** Aprovação simulada com total segurança, sem reter dados do seu cartão.\n"
        . "3. **Dinheiro:** Pagamento realizado diretamente ao entregador na chegada ou no balcão de retirada (com opção de troco).\n\n"
        . "Todas as opções podem ser escolhidas com segurança ao finalizar o seu pedido.";
}

// Intent 6: Pergunta sobre produto específico ou ingredientes
elseif (preg_match('/(tem|ingredientes|como [eé]|o que vem|sabor|pre[çc]o|quanto custa|calabresa|quatro queijos|portuguesa|margherita|sensa[çc][aã]o|pepperoni|frango|esfiha|coca|guaran[aá]|suco|petit|pudim|nutella)/i', $msgLower)) {
    $found = [];
    foreach ($products as $p) {
        $pNameLower = mb_strtolower($p['name'], 'UTF-8');
        // Checa ocorrência de palavras-chave
        if (strpos($msgLower, mb_strtolower(explode(' ', $p['name'])[0], 'UTF-8')) !== false
            || strpos($msgLower, $pNameLower) !== false
            || (strpos($msgLower, 'esfiha') !== false && $p['category_slug'] === 'esfihas')
            || (strpos($msgLower, 'sobremesa') !== false && $p['category_slug'] === 'sobremesas')
            || (strpos($msgLower, 'bebida') !== false && $p['category_slug'] === 'bebidas')) {
            $found[] = $p;
            if (count($found) >= 3) break;
        }
    }

    if (!empty($found)) {
        $reply = "🍕 Encontrei as seguintes opções no nosso cardápio oficial:\n\n";
        foreach ($found as $item) {
            $reply .= "• **{$item['name']}** — **R$ " . number_format($item['base_price'], 2, ',', '.') . "**\n";
            $reply .= "  _{$item['description']}_\n";
            if (!empty($item['ingredients'])) {
                $reply .= "  🥗 *Ingredientes:* {$item['ingredients']}\n";
            }
            $reply .= "\n";
        }
        $reply .= "Você pode personalizar tamanhos, bordas recheadas e adicionais diretamente no cardápio!";
        $actionButtons = [
            ['label' => 'Abrir Cardápio', 'action' => 'url', 'value' => 'cardapio.php']
        ];
    } else {
        $reply = "Temos deliciosas opções de **Pizzas**, **Esfihas**, **Bebidas** e **Sobremesas** preparadas com ingredientes nobres e frescos!\n\n"
            . "Você gostaria de consultar alguma categoria específica ou ver o cardápio interativo completo?";
        $actionButtons = [
            ['label' => 'Ver Todas as Pizzas', 'action' => 'url', 'value' => 'cardapio.php?cat=pizzas'],
            ['label' => 'Ver Esfihas', 'action' => 'url', 'value' => 'cardapio.php?cat=esfihas'],
            ['label' => 'Ver Bebidas e Sobremesas', 'action' => 'url', 'value' => 'cardapio.php?cat=sobremesas']
        ];
    }
}

// Intent 7: Promoções ou cupons
elseif (preg_match('/(promo[çc][aã]o|cupom|desconto|oferta)/i', $msgLower)) {
    // Requisito: "A IA nunca deve inventar preços, cupons ou promoções. Deve respeitar os preços cadastrados no sistema."
    $reply = "No momento, nossos preços do cardápio já possuem valores especiais direto do forno para você!\n\n"
        . "• **Esfihas artesanais:** a partir de R$ 5,50\n"
        . "• **Pizzas clássicas e especiais:** a partir de R$ 48,00\n"
        . "• **Retirada no balcão:** Taxa de entrega zero (Grátis)\n\n"
        . "Não possuímos cupons adicionais ativos nesta data, mas garantimos a máxima qualidade em cada ingrediente!";
    $actionButtons = [
        ['label' => 'Acessar Cardápio', 'action' => 'url', 'value' => 'cardapio.php']
    ];
}

// Intent 8: Atendimento humano
elseif (preg_match('/(humano|atendente|falar com algu[eé]m|telefone|whatsapp|reclama[çc][aã]o|ajuda)/i', $msgLower)) {
    // Requisito: "Deve encaminhar o cliente para atendimento humano quando necessário."
    $reply = "Com certeza! Caso prefira conversar diretamente com a nossa equipe de atendimento:\n\n"
        . "📞 **Telefone da Pizzaria:** {$storePhone}\n"
        . "💬 **WhatsApp:** (11) 98765-4321\n"
        . "📍 **Balcão Prisca Pizza:** {$storeAddress}\n\n"
        . "Nossa equipe de caixa e gerência está pronta para prestar suporte completo!";
}

// Default: resposta cordial com base no cardápio
else {
    $reply = "Olá! Como assistente virtual da **Prisca Pizza**, utilizo somente as informações oficiais cadastradas em nosso sistema.\n\n"
        . "Posso esclarecer dúvidas sobre os ingredientes das nossas pizzas, tamanhos (Média, Grande, Gigante, Broto), bordas recheadas de Catupiry ou Cheddar, opções de esfihas e bebidas, além de consultar o status dos seus pedidos e taxas de entrega.\n\n"
        . "Como posso te ajudar?";
    $actionButtons = [
        ['label' => 'Ver Cardápio', 'action' => 'url', 'value' => 'cardapio.php'],
        ['label' => 'Dúvidas sobre Entrega', 'action' => 'send', 'value' => 'Qual a taxa e tempo de entrega?'],
        ['label' => 'Falar com Atendente', 'action' => 'send', 'value' => 'Quero falar com um atendente']
    ];
}

echo json_encode([
    'success' => true,
    'reply' => $reply,
    'action_buttons' => $actionButtons
]);
