<?php
/**
 * Prisca Pizza - Seed Database Script
 */
require_once __DIR__ . '/../bd.php';

echo "=== Iniciando Configuração e Carga Inicial do Banco de Dados ===\n";

// 1. Executa o schema.sql
$schemaSql = file_get_contents(__DIR__ . '/schema.sql');
try {
    $pdo->exec($schemaSql);
    echo "[OK] Schema executado com sucesso!\n";
} catch (PDOException $e) {
    die("[ERRO] Falha ao executar schema: " . $e->getMessage() . "\n");
}

// 2. Insere Configurações Iniciais
$settings = [
    'opening_hour' => '18:00',
    'closing_hour' => '23:30',
    'store_open_override' => '1', // 1 = Força Aberto para testes
    'delivery_fee_default' => '7.00',
    'delivery_time_min' => '30',
    'delivery_time_max' => '50',
    'store_phone' => '(11) 3456-7890',
    'store_whatsapp' => '(11) 98765-4321',
    'store_address' => 'Rua das Pizzas, 100 - Bairro Bela Vista, São Paulo - SP',
    'pix_key' => 'financeiro@priscapizza.com.br',
    'pix_beneficiary' => 'Prisca Pizza Ltda'
];

$stmtSetting = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
foreach ($settings as $k => $v) {
    $stmtSetting->execute([$k, $v]);
}
echo "[OK] Configurações inseridas!\n";

// 3. Insere Usuários com hash bcrypt (PASSWORD_BCRYPT)
// Requisito: "Tratar senhas de usuários com hash bcrypt", "Cada usuário deve ter seu próprio acesso e perfil"
$users = [
    [
        'name' => 'Mariana Atendente',
        'email' => 'caixa@priscapizza.com',
        'password' => 'caixa123',
        'phone' => '(11) 98111-2233',
        'address' => 'Av. Paulista, 1000 - Caixa',
        'role' => 'atendente'
    ],
    [
        'name' => 'André Pizzaiolo',
        'email' => 'cozinha@priscapizza.com',
        'password' => 'cozinha123',
        'phone' => '(11) 98222-3344',
        'address' => 'Av. Paulista, 1000 - Cozinha',
        'role' => 'pizzaiolo'
    ],
    [
        'name' => 'Carlos Entregador',
        'email' => 'entregador@priscapizza.com',
        'password' => 'entrega123',
        'phone' => '(11) 98333-4455',
        'address' => 'Rua das Entregas, 50, SP',
        'role' => 'entregador'
    ],
    [
        'name' => 'Belly Xavier',
        'email' => 'cliente@email.com',
        'password' => 'cliente123',
        'phone' => '(11) 98765-4321',
        'address' => 'Rua das Flores, 123 - Apto 42',
        'neighborhood' => 'Centro',
        'city' => 'São Paulo',
        'role' => 'cliente'
    ],
    [
        'name' => 'Gerente Geral',
        'email' => 'admin@priscapizza.com',
        'password' => 'admin123',
        'phone' => '(11) 99999-8888',
        'address' => 'Av. Paulista, 1000 - Diretoria',
        'role' => 'admin'
    ]
];

$stmtUser = $pdo->prepare("INSERT INTO users (name, email, password_hash, phone, address, neighborhood, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
foreach ($users as $u) {
    $hash = password_hash($u['password'], PASSWORD_BCRYPT);
    $neighborhood = $u['neighborhood'] ?? 'Bela Vista';
    $stmtUser->execute([$u['name'], $u['email'], $hash, $u['phone'], $u['address'], $neighborhood, $u['role']]);
}
echo "[OK] Usuários criados com Bcrypt!\n";

// 4. Insere Categorias
$categories = [
    [
        'name' => 'Pizzas',
        'slug' => 'pizzas',
        'description' => 'Tradicionais e especiais feitas no forno a lenha com massa de fermentação lenta e molho artesanal.',
        'icon' => 'fa-pizza-slice',
        'order_index' => 1
    ],
    [
        'name' => 'Esfihas',
        'slug' => 'esfihas',
        'description' => 'Esfihas abertas e fechadas quentinhas com massas leves e recheios generosos.',
        'icon' => 'fa-bread-slice',
        'order_index' => 2
    ],
    [
        'name' => 'Bebidas',
        'slug' => 'bebidas',
        'description' => 'Refrigerantes gelados, sucos naturais e cervejas especiais para acompanhar.',
        'icon' => 'fa-wine-bottle',
        'order_index' => 3
    ],
    [
        'name' => 'Sobremesas',
        'slug' => 'sobremesas',
        'description' => 'Pizzas doces irresistíveis, petit gâteau e sobremesas tradicionais para fechar com chave de ouro.',
        'icon' => 'fa-ice-cream',
        'order_index' => 4
    ]
];

$stmtCat = $pdo->prepare("INSERT INTO categories (name, slug, description, icon, order_index) VALUES (?, ?, ?, ?, ?)");
$catMap = [];
foreach ($categories as $c) {
    $stmtCat->execute([$c['name'], $c['slug'], $c['description'], $c['icon'], $c['order_index']]);
    $catMap[$c['slug']] = $pdo->lastInsertId();
}
echo "[OK] Categorias criadas!\n";

// 5. Insere Opções de Customização (Tamanhos, Bordas e Adicionais)
$options = [
    // Tamanhos de Pizza
    ['category_slug' => 'pizzas', 'type' => 'tamanho', 'name' => 'Média (6 fatias)', 'price_modifier' => 0.00, 'slices' => 6],
    ['category_slug' => 'pizzas', 'type' => 'tamanho', 'name' => 'Grande (8 fatias)', 'price_modifier' => 10.00, 'slices' => 8],
    ['category_slug' => 'pizzas', 'type' => 'tamanho', 'name' => 'Gigante (12 fatias)', 'price_modifier' => 18.00, 'slices' => 12],
    ['category_slug' => 'pizzas', 'type' => 'tamanho', 'name' => 'Broto Individual (4 fatias)', 'price_modifier' => -8.00, 'slices' => 4],

    // Bordas de Pizza
    ['category_slug' => 'pizzas', 'type' => 'borda', 'name' => 'Borda Tradicional (Massa crocante)', 'price_modifier' => 0.00, 'slices' => null],
    ['category_slug' => 'pizzas', 'type' => 'borda', 'name' => 'Borda Recheada Catupiry Original', 'price_modifier' => 9.00, 'slices' => null],
    ['category_slug' => 'pizzas', 'type' => 'borda', 'name' => 'Borda Recheada Cheddar Cremoso', 'price_modifier' => 9.00, 'slices' => null],
    ['category_slug' => 'pizzas', 'type' => 'borda', 'name' => 'Borda Vulcão de Catupiry', 'price_modifier' => 14.00, 'slices' => null],
    ['category_slug' => 'pizzas', 'type' => 'borda', 'name' => 'Borda Doce Chocolate ao Leite', 'price_modifier' => 10.00, 'slices' => null],

    // Adicionais para Pizza
    ['category_slug' => 'pizzas', 'type' => 'adicional', 'name' => 'Bacon Crocante Extra', 'price_modifier' => 6.00, 'slices' => null],
    ['category_slug' => 'pizzas', 'type' => 'adicional', 'name' => 'Parmesão Ralado na Hora', 'price_modifier' => 5.00, 'slices' => null],
    ['category_slug' => 'pizzas', 'type' => 'adicional', 'name' => 'Alho Dourado Crocante', 'price_modifier' => 4.00, 'slices' => null],
    ['category_slug' => 'pizzas', 'type' => 'adicional', 'name' => 'Catupiry Extra no Recheio', 'price_modifier' => 7.00, 'slices' => null],
    ['category_slug' => 'pizzas', 'type' => 'adicional', 'name' => 'Champignon Fresco Fatiado', 'price_modifier' => 7.00, 'slices' => null]
];

$stmtOpt = $pdo->prepare("INSERT INTO product_options (category_slug, type, name, price_modifier, slices) VALUES (?, ?, ?, ?, ?)");
foreach ($options as $opt) {
    $stmtOpt->execute([$opt['category_slug'], $opt['type'], $opt['name'], $opt['price_modifier'], $opt['slices']]);
}
echo "[OK] Opções de customização inseridas!\n";

// 6. Insere Produtos
$products = [
    // PIZZAS
    [
        'category_id' => $catMap['pizzas'],
        'name' => 'Calabresa Especial',
        'description' => 'Molho de tomate artesanal, mussarela derretida, calabresa defumada fatiada, cebola roxa fresca, azeitonas pretas chilenas e orégano.',
        'ingredients' => 'Molho de tomate caseiro, mussarela, calabresa artesanal, cebola roxa, azeitonas pretas, orégano',
        'base_price' => 48.00,
        'image_url' => 'https://images.unsplash.com/photo-1628840042765-356cda07504e?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 1
    ],
    [
        'category_id' => $catMap['pizzas'],
        'name' => 'Quatro Queijos Nobre',
        'description' => 'Combinação harmônica de queijo mussarela especial, requeijão Catupiry original cremoso, provolone defumado e parmesão maturado ralado.',
        'ingredients' => 'Molho de tomate, mussarela, catupiry original, provolone, parmesão, orégano',
        'base_price' => 56.00,
        'image_url' => 'https://images.unsplash.com/photo-1573821663912-569905455b1c?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 1
    ],
    [
        'category_id' => $catMap['pizzas'],
        'name' => 'Frango com Catupiry',
        'description' => 'Peito de frango cozido e desfiado com temperos frescos da horta, coberto generosamente com Catupiry autêntico e azeitonas.',
        'ingredients' => 'Molho de tomate, peito de frango temperado desfiado, catupiry genuíno, azeitonas, orégano',
        'base_price' => 52.00,
        'image_url' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 1
    ],
    [
        'category_id' => $catMap['pizzas'],
        'name' => 'Portuguesa Tradicional',
        'description' => 'Presunto cozido em cubos finos, ovos cozidos frescos, cebola em rodelas, ervilhas selecionadas, coberta com mussarela e azeitonas.',
        'ingredients' => 'Molho de tomate, mussarela, presunto picado, ovos cozidos, cebola fresca, ervilha verde, azeitonas, orégano',
        'base_price' => 54.00,
        'image_url' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 1
    ],
    [
        'category_id' => $catMap['pizzas'],
        'name' => 'Margherita Italiana',
        'description' => 'A clássica napolitana: molho de tomate San Marzano, fatias de mussarela especial, rodelas de tomate caqui maduro, folhas frescas de manjericão e fio de azeite extra virgem.',
        'ingredients' => 'Molho de tomate italiano, mussarela, rodelas de tomate caqui, manjericão fresco, azeite extra virgem',
        'base_price' => 50.00,
        'image_url' => 'https://images.unsplash.com/photo-1604382354936-07c5d9983bd3?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 1
    ],
    [
        'category_id' => $catMap['pizzas'],
        'name' => 'Prisca Sensação (Especial da Casa)',
        'description' => 'A receita secreta das divas e do André: lombo canadense defumado, champignon paris fresco, bacon crocante em cubos, catupiry cremoso e toque de alecrim.',
        'ingredients' => 'Molho artesanal, mussarela, lombo defumado, champignon fresco, bacon crocante, catupiry, alecrim',
        'base_price' => 62.00,
        'image_url' => 'https://images.unsplash.com/photo-1594007654729-407eedc4be65?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 1
    ],
    [
        'category_id' => $catMap['pizzas'],
        'name' => 'Pepperoni Supremo',
        'description' => 'Massa artesanal fina, generosa camada de mussarela de búfala e fatias selecionadas de pepperoni levemente picante tostado no forno a lenha.',
        'ingredients' => 'Molho de tomate rústico, mussarela de búfala, fatias generosas de pepperoni premium, orégano',
        'base_price' => 58.00,
        'image_url' => 'https://images.unsplash.com/photo-1534308983496-4fabb1a015ee?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 1
    ],
    [
        'category_id' => $catMap['pizzas'],
        'name' => 'Vegana Primavera',
        'description' => 'Massa 100% vegetal, molho de tomate natural, queijo vegano derretido à base de castanha de caju, abobrinha grelhada, tomate seco, palmito pupunha e folhas de rúcula.',
        'ingredients' => 'Molho de tomate, queijo vegetal de castanha, abobrinha grelhada, tomate seco, palmito, rúcula fresca',
        'base_price' => 55.00,
        'image_url' => 'https://images.unsplash.com/photo-1588315029754-2dd089d39a1a?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 1
    ],

    // ESFIHAS
    [
        'category_id' => $catMap['esfihas'],
        'name' => 'Esfiha Aberta de Carne',
        'description' => 'Massa macia e aerada, recheada com carne bovina moída fresca de primeira, cebola roxa bem picada, tomate e o clássico tempero árabe com limão tahiti.',
        'ingredients' => 'Massa artesanal, carne bovina moída, cebola, tomate, limão, pimenta síria, sal',
        'base_price' => 5.50,
        'image_url' => 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 0
    ],
    [
        'category_id' => $catMap['esfihas'],
        'name' => 'Esfiha Aberta de Queijo',
        'description' => 'Queijo mussarela e minas padrão ralados e temperados com salsinha fresca, orégano e leve toque de azeite, assada na hora.',
        'ingredients' => 'Massa artesanal, blend de mussarela e queijo minas, salsinha fresca, orégano',
        'base_price' => 5.50,
        'image_url' => 'https://images.unsplash.com/photo-1608198093002-ad4e005484ec?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 0
    ],
    [
        'category_id' => $catMap['esfihas'],
        'name' => 'Esfiha de Frango com Catupiry',
        'description' => 'Recheio suculento de peito de frango finamente temperado com alho e cebola dourados, coroado com Catupiry genuíno.',
        'ingredients' => 'Massa leve, peito de frango desfiado, catupiry original, especiarias',
        'base_price' => 6.00,
        'image_url' => 'https://images.unsplash.com/photo-1541745537411-b8046dc6d66c?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 0
    ],
    [
        'category_id' => $catMap['esfihas'],
        'name' => 'Esfiha de Calabresa com Queijo',
        'description' => 'Calabresa moída levemente apimentada e refogada com cebola, coberta com mussarela derretida.',
        'ingredients' => 'Calabresa moída, cebola dourada, mussarela derretida, orégano',
        'base_price' => 5.50,
        'image_url' => 'https://images.unsplash.com/photo-1589182373726-e4f658ab50f0?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 0
    ],
    [
        'category_id' => $catMap['esfihas'],
        'name' => 'Esfiha Doce de Chocolate com Morango',
        'description' => 'Esfiha aberta com creme aveludado de chocolate meio amargo e pedaços frescos de morango selecionado.',
        'ingredients' => 'Massa leve, chocolate nobre derretido, morangos frescos fatiados',
        'base_price' => 7.50,
        'image_url' => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 0
    ],

    // BEBIDAS
    [
        'category_id' => $catMap['bebidas'],
        'name' => 'Coca-Cola Original 2L',
        'description' => 'Garrafa PET 2 Litros gelada.',
        'ingredients' => 'Refrigerante de cola',
        'base_price' => 14.00,
        'image_url' => 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 0
    ],
    [
        'category_id' => $catMap['bebidas'],
        'name' => 'Coca-Cola Sem Açúcar 2L',
        'description' => 'Garrafa PET 2 Litros geladinha, zero calorias.',
        'ingredients' => 'Refrigerante de cola sem açúcar',
        'base_price' => 14.00,
        'image_url' => 'https://images.unsplash.com/photo-1554866585-cd94860890b7?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 0
    ],
    [
        'category_id' => $catMap['bebidas'],
        'name' => 'Guaraná Antarctica 2L',
        'description' => 'Garrafa PET 2 Litros trincando de gelada.',
        'ingredients' => 'Refrigerante de guaraná da Amazônia',
        'base_price' => 13.00,
        'image_url' => 'https://images.unsplash.com/photo-1629203851122-3726ecdf080e?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 0
    ],
    [
        'category_id' => $catMap['bebidas'],
        'name' => 'Suco Natural de Laranja 500ml',
        'description' => 'Suco espremido da fruta 100% natural, sem adição de açúcar ou conservantes.',
        'ingredients' => 'Laranja pera fresca selecionada',
        'base_price' => 9.00,
        'image_url' => 'https://images.unsplash.com/photo-1613478223719-2ab802602423?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 0
    ],
    [
        'category_id' => $catMap['bebidas'],
        'name' => 'Cerveja Heineken Long Neck 330ml',
        'description' => 'Cerveja puro malte Premium geladíssima (proibida venda para menores de 18 anos).',
        'ingredients' => 'Água, malte, lúpulo',
        'base_price' => 11.00,
        'image_url' => 'https://images.unsplash.com/photo-1608270110368-f99a5382877a?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 0
    ],
    [
        'category_id' => $catMap['bebidas'],
        'name' => 'Água Mineral com Gás 500ml',
        'description' => 'Garrafa 500ml gelada com gás.',
        'ingredients' => 'Água mineral natural gaseificada',
        'base_price' => 5.00,
        'image_url' => 'https://images.unsplash.com/photo-1548839140-29a749e1bc4e?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 0
    ],

    // SOBREMESAS
    [
        'category_id' => $catMap['sobremesas'],
        'name' => 'Pizza Broto de Nutella com Ninho',
        'description' => 'Massa crocante, banhada com farto creme de avelã Nutella original Ferrero e polvilhada generosamente com leite Ninho.',
        'ingredients' => 'Massa doce crocante, Nutella original, Leite Ninho integral em pó',
        'base_price' => 39.00,
        'image_url' => 'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 0
    ],
    [
        'category_id' => $catMap['sobremesas'],
        'name' => 'Pizza Broto Romeu e Julieta',
        'description' => 'A combinação perfeita brasileira: goiabada cremosa cascão derretida com generosa cobertura de queijo minas padrão.',
        'ingredients' => 'Massa doce artesanal, goiabada cascão cremosa, queijo minas padrão especial',
        'base_price' => 34.00,
        'image_url' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 0
    ],
    [
        'category_id' => $catMap['sobremesas'],
        'name' => 'Petit Gâteau com Sorvete de Baunilha',
        'description' => 'Bolinho quente de chocolate meio amargo com centro líquido escorrendo, servido com bola de sorvete de baunilha e calda de fudge.',
        'ingredients' => 'Bolo de chocolate belga, sorvete artesanal de baunilha, calda de chocolate',
        'base_price' => 24.00,
        'image_url' => 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 0
    ],
    [
        'category_id' => $catMap['sobremesas'],
        'name' => 'Pudim Tradicional de Leite Condensado',
        'description' => 'Pudim lisinho, sem furinhos, extremamente cremoso com calda de caramelo dourado da vovó (porção individual generosa).',
        'ingredients' => 'Leite condensado moça, ovos caipiras, leite integral, calda de açúcar caramelizado',
        'base_price' => 16.00,
        'image_url' => 'https://images.unsplash.com/photo-1541783245831-57d6fb0926d3?w=600&auto=format&fit=crop&q=80',
        'allow_half_half' => 0
    ]
];

$stmtProd = $pdo->prepare("INSERT INTO products (category_id, name, description, ingredients, base_price, image_url, allow_half_half) VALUES (?, ?, ?, ?, ?, ?, ?)");
foreach ($products as $p) {
    $stmtProd->execute([$p['category_id'], $p['name'], $p['description'], $p['ingredients'], $p['base_price'], $p['image_url'], $p['allow_half_half']]);
}
echo "[OK] Produtos inseridos com sucesso!\n";

// 7. Insere Pedidos de Demonstração para testar todos os 4 painéis de imediato!
// Pedido 1: Recebido (para Caixa encaminhar para Cozinha)
// Pedido 2: Em preparo (para Pizzaiolo atualizar para Pronto)
// Pedido 3: Pronto (para Entregador pegar e sair para entrega)
// Pedido 4: Saiu para entrega (para Entregador marcar como Entregue)
// Pedido 5: Entregue (concluído)

$demoOrders = [
    [
        'user_id' => 4, // Belly Xavier
        'order_type' => 'entrega',
        'customer_name' => 'Belly Xavier',
        'customer_phone' => '(11) 98765-4321',
        'delivery_address' => 'Rua das Flores, 123 - Apto 42, Centro, São Paulo',
        'delivery_neighborhood' => 'Centro',
        'subtotal' => 67.00,
        'delivery_fee' => 7.00,
        'discount' => 0.00,
        'total' => 74.00,
        'payment_method' => 'pix',
        'payment_status' => 'aprovado',
        'pix_code' => '00020126580014br.gov.bcb.pix0136financeiro@priscapizza.com.br520400005303986540574.005802BR5916PRISCA PIZZA LTDA6009SAO PAULO62070503***6304ABCD',
        'status' => 'Recebido',
        'observation' => 'Por favor tocar o interfone do apto 42.',
        'items' => [
            [
                'product_id' => 1,
                'name' => 'Calabresa Especial',
                'size' => 'Grande (8 fatias)',
                'crust' => 'Borda Recheada Catupiry Original',
                'extras' => 'Alho Dourado Crocante',
                'obs' => 'Cebola bem fininha',
                'qty' => 1,
                'unit_price' => 67.00,
                'total_price' => 67.00
            ]
        ]
    ],
    [
        'user_id' => 4,
        'order_type' => 'entrega',
        'customer_name' => 'Lucas Ferreira',
        'customer_phone' => '(11) 97777-6666',
        'delivery_address' => 'Av. Brigadeiro Luís Antônio, 2500 - Bela Vista',
        'delivery_neighborhood' => 'Bela Vista',
        'subtotal' => 70.00,
        'delivery_fee' => 7.00,
        'discount' => 0.00,
        'total' => 77.00,
        'payment_method' => 'cartao',
        'payment_status' => 'aprovado',
        'status' => 'Em preparo',
        'observation' => 'Sem orégano se possível.',
        'items' => [
            [
                'product_id' => 2,
                'name' => 'Quatro Queijos Nobre',
                'size' => 'Grande (8 fatias)',
                'crust' => 'Borda Tradicional',
                'extras' => 'Bacon Crocante Extra',
                'obs' => 'Massa bem assadinha',
                'qty' => 1,
                'unit_price' => 70.00,
                'total_price' => 70.00
            ]
        ]
    ],
    [
        'user_id' => 4,
        'order_type' => 'entrega',
        'customer_name' => 'Fernanda Lima',
        'customer_phone' => '(11) 96666-5555',
        'delivery_address' => 'Rua Augusta, 1400 - Consolação',
        'delivery_neighborhood' => 'Consolação',
        'subtotal' => 52.00,
        'delivery_fee' => 7.00,
        'discount' => 0.00,
        'total' => 59.00,
        'payment_method' => 'dinheiro',
        'payment_status' => 'pago_na_entrega',
        'change_for' => 100.00,
        'status' => 'Pronto',
        'observation' => 'Levar troco para R$ 100,00',
        'items' => [
            [
                'product_id' => 3,
                'name' => 'Frango com Catupiry',
                'size' => 'Média (6 fatias)',
                'crust' => 'Borda Tradicional',
                'extras' => null,
                'obs' => '',
                'qty' => 1,
                'unit_price' => 52.00,
                'total_price' => 52.00
            ]
        ]
    ],
    [
        'user_id' => 4,
        'order_type' => 'entrega',
        'customer_name' => 'Juliana Paes',
        'customer_phone' => '(11) 95555-4444',
        'delivery_address' => 'Rua Frei Caneca, 800 - Bela Vista',
        'delivery_neighborhood' => 'Bela Vista',
        'subtotal' => 64.00,
        'delivery_fee' => 7.00,
        'discount' => 0.00,
        'total' => 71.00,
        'payment_method' => 'cartao',
        'payment_status' => 'aprovado',
        'status' => 'Saiu para entrega',
        'assigned_delivery_user_id' => 3, // Carlos Entregador
        'observation' => 'Deixar na portaria com o porteiro Silva.',
        'items' => [
            [
                'product_id' => 6,
                'name' => 'Prisca Sensação (Especial da Casa)',
                'size' => 'Média (6 fatias)',
                'crust' => 'Borda Tradicional',
                'extras' => null,
                'obs' => '',
                'qty' => 1,
                'unit_price' => 62.00,
                'total_price' => 62.00
            ]
        ]
    ],
    [
        'user_id' => 4,
        'order_type' => 'retirada',
        'customer_name' => 'Roberto Carlos',
        'customer_phone' => '(11) 94444-3333',
        'delivery_address' => 'Retirada no balcão da pizzaria',
        'subtotal' => 48.00,
        'delivery_fee' => 0.00,
        'discount' => 0.00,
        'total' => 48.00,
        'payment_method' => 'pix',
        'payment_status' => 'aprovado',
        'status' => 'Entregue',
        'observation' => 'Cliente retirou no balcão.',
        'items' => [
            [
                'product_id' => 1,
                'name' => 'Calabresa Especial',
                'size' => 'Média (6 fatias)',
                'crust' => 'Borda Tradicional',
                'extras' => null,
                'obs' => '',
                'qty' => 1,
                'unit_price' => 48.00,
                'total_price' => 48.00
            ]
        ]
    ]
];

$stmtOrder = $pdo->prepare("INSERT INTO orders (user_id, order_type, customer_name, customer_phone, delivery_address, delivery_neighborhood, subtotal, delivery_fee, discount, total, payment_method, payment_status, pix_code, change_for, status, assigned_delivery_user_id, observation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, size, crust, extras, observation, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmtLog = $pdo->prepare("INSERT INTO order_status_logs (order_id, user_name, user_role, previous_status, new_status, comment) VALUES (?, ?, ?, ?, ?, ?)");

foreach ($demoOrders as $ord) {
    $stmtOrder->execute([
        $ord['user_id'],
        $ord['order_type'],
        $ord['customer_name'],
        $ord['customer_phone'],
        $ord['delivery_address'],
        $ord['delivery_neighborhood'] ?? '',
        $ord['subtotal'],
        $ord['delivery_fee'],
        $ord['discount'],
        $ord['total'],
        $ord['payment_method'],
        $ord['payment_status'],
        $ord['pix_code'] ?? null,
        $ord['change_for'] ?? null,
        $ord['status'],
        $ord['assigned_delivery_user_id'] ?? null,
        $ord['observation']
    ]);
    $orderId = $pdo->lastInsertId();

    foreach ($ord['items'] as $it) {
        $stmtItem->execute([
            $orderId,
            $it['product_id'],
            $it['name'],
            $it['size'],
            $it['crust'],
            $it['extras'],
            $it['obs'],
            $it['qty'],
            $it['unit_price'],
            $it['total_price']
        ]);
    }

    // Registra log do pedido
    $stmtLog->execute([$orderId, 'Sistema', 'sistema', 'Criado', $ord['status'], 'Pedido de demonstração inicial']);
}

echo "[OK] Pedidos de demonstração criados para teste dos painéis!\n";
echo "=== Base de Dados Configurada com Sucesso! ===\n";
