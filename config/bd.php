<?php
/**
 * Prisca Pizza - Conexão Segura com Banco de Dados via PDO
 * Suporta MySQL nativamente e inicialização automática caso o banco não exista.
 */

require_once __DIR__ . '/config.php';

function getDbConnection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host = DB_HOST;
    $port = DB_PORT;
    $dbname = DB_NAME;
    $user = DB_USER;
    $pass = DB_PASS;
    $charset = DB_CHARSET;

    try {
        // Tenta conectar ao MySQL apontando diretamente para o banco
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE utf8mb4_unicode_ci"
        ];

        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Caso o banco 'prisca_pizza' não exista ainda no MySQL, tenta criá-lo automaticamente
        if ($e->getCode() == 1049) {
            try {
                $dsnNoDb = "mysql:host={$host};port={$port};charset={$charset}";
                $rootPdo = new PDO($dsnNoDb, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

                // Reconecta agora ao banco criado
                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);

                // Executa a criação das tabelas e dados iniciais
                $schemaFile = __DIR__ . '/../database/schema.sql';
                $seedFile = __DIR__ . '/../database/seed.sql';
                if (file_exists($schemaFile)) {
                    $sql = file_get_contents($schemaFile);
                    $pdo->exec($sql);
                }
                if (file_exists($seedFile)) {
                    $sqlSeed = file_get_contents($seedFile);
                    $pdo->exec($sqlSeed);
                }
                return $pdo;
            } catch (Exception $innerEx) {
                // Falha ao criar no MySQL, tenta fallback local para SQLite
            }
        }

        // Fallback resiliente: SQLite local caso o MySQL não esteja rodando
        try {
            $sqlitePath = __DIR__ . '/../database/prisca_pizza.sqlite';
            $isNewDb = !file_exists($sqlitePath);
            $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            if ($isNewDb) {
                initSqliteDatabase($pdo);
            }
            return $pdo;
        } catch (Exception $sqliteEx) {
            // Se nenhum dos dois conectou, exibe tela de diagnóstico amigável
            die("
                <div style='font-family: sans-serif; max-width: 650px; margin: 40px auto; padding: 25px; border: 1px solid #f87171; background: #fef2f2; border-radius: 12px; color: #991b1b;'>
                    <h2 style='margin-top: 0;'>Erro de Conexão com o Banco de Dados</h2>
                    <p>Não foi possível conectar ao MySQL nem ao banco local.</p>
                    <p><strong>Detalhes:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                    <hr style='border: 0; border-top: 1px solid #fca5a5; margin: 15px 0;' />
                    <p>Verifique se o seu MySQL (XAMPP, WAMP ou Docker) está em execução na porta <strong>{$port}</strong> com o usuário <strong>{$user}</strong>.</p>
                    <p><a href='setup.php' style='display: inline-block; padding: 8px 16px; background: #dc2626; color: #fff; text-decoration: none; border-radius: 6px;'>Ir para o Assistente de Instalação (Setup)</a></p>
                </div>
            ");
        }
    }
}

/**
 * Inicializador de tabelas para SQLite (fallback)
 */
function initSqliteDatabase(PDO $pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            senha TEXT NOT NULL,
            telefone TEXT,
            endereco TEXT,
            perfil TEXT NOT NULL DEFAULT 'cliente',
            tentativas_login INTEGER DEFAULT 0,
            bloqueado_ate TEXT,
            criado_em TEXT DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS categorias (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug TEXT NOT NULL UNIQUE,
            nome TEXT NOT NULL,
            descricao TEXT,
            icone TEXT DEFAULT 'pizza-slice',
            ordem INTEGER DEFAULT 0,
            ativo INTEGER DEFAULT 1
        );

        CREATE TABLE IF NOT EXISTS produtos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            categoria_id INTEGER NOT NULL,
            nome TEXT NOT NULL,
            descricao TEXT,
            preco_base REAL NOT NULL,
            imagem TEXT,
            disponivel INTEGER DEFAULT 1,
            permite_meio_a_meio INTEGER DEFAULT 0,
            destaque INTEGER DEFAULT 0,
            criado_em TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS tamanhos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            categoria_slug TEXT NOT NULL,
            nome TEXT NOT NULL,
            fatias TEXT,
            multiplicador_preco REAL DEFAULT 1.0,
            max_sabores INTEGER DEFAULT 1,
            ativo INTEGER DEFAULT 1
        );

        CREATE TABLE IF NOT EXISTS adicionais (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tipo TEXT NOT NULL DEFAULT 'ingrediente',
            nome TEXT NOT NULL,
            preco REAL NOT NULL DEFAULT 0.0,
            categoria_slug TEXT DEFAULT 'pizzas',
            ativo INTEGER DEFAULT 1
        );

        CREATE TABLE IF NOT EXISTS pedidos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            codigo TEXT NOT NULL UNIQUE,
            cliente_id INTEGER NOT NULL,
            entregador_id INTEGER,
            tipo_entrega TEXT NOT NULL DEFAULT 'entrega',
            endereco_entrega TEXT,
            telefone_contato TEXT,
            subtotal REAL NOT NULL,
            taxa_entrega REAL NOT NULL DEFAULT 0.0,
            desconto REAL NOT NULL DEFAULT 0.0,
            total REAL NOT NULL,
            forma_pagamento TEXT NOT NULL,
            status_pagamento TEXT NOT NULL DEFAULT 'pendente',
            troco_para REAL,
            pix_codigo TEXT,
            pix_qrcode TEXT,
            status TEXT NOT NULL DEFAULT 'Recebido',
            tempo_estimado_min INTEGER DEFAULT 45,
            observacoes TEXT,
            motivo_cancelamento TEXT,
            criado_em TEXT DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS itens_pedido (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            pedido_id INTEGER NOT NULL,
            produto_id INTEGER NOT NULL,
            nome_produto TEXT NOT NULL,
            tamanho_nome TEXT,
            sabor_secundario_nome TEXT,
            adicionais_selecionados TEXT,
            observacoes TEXT,
            quantidade INTEGER NOT NULL DEFAULT 1,
            preco_unitario REAL NOT NULL,
            subtotal_item REAL NOT NULL
        );

        CREATE TABLE IF NOT EXISTS logs_auditoria (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            pedido_id INTEGER,
            usuario_id INTEGER,
            usuario_nome TEXT NOT NULL,
            usuario_perfil TEXT NOT NULL,
            acao TEXT NOT NULL,
            status_anterior TEXT,
            status_novo TEXT,
            detalhes TEXT,
            ip TEXT,
            criado_em TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS configuracoes (
            chave TEXT PRIMARY KEY,
            valor TEXT NOT NULL,
            descricao TEXT
        );
    ");

    // Injeta seed no SQLite
    $hash = password_hash('senha123', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO usuarios (id, nome, email, senha, telefone, endereco, perfil) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([1, 'João da Silva (Cliente)', 'cliente@priscapizza.com', $hash, '(11) 98765-4321', 'Av. Paulista, 1000 - Apto 42, Bela Vista - São Paulo/SP', 'cliente']);
    $stmt->execute([2, 'Mariana Atendente', 'caixa@priscapizza.com', $hash, '(11) 97777-1111', 'Rua dos Funcionários, 10, São Paulo/SP', 'caixa']);
    $stmt->execute([3, 'Mestre Luigi Pizzaiolo', 'pizzaiolo@priscapizza.com', $hash, '(11) 97777-2222', 'Rua da Cozinha, 20, São Paulo/SP', 'pizzaiolo']);
    $stmt->execute([4, 'Carlos Motoboy', 'entregador@priscapizza.com', $hash, '(11) 97777-3333', 'Rua da Frota, 30, São Paulo/SP', 'entregador']);
    $stmt->execute([5, 'Administrador Geral', 'admin@priscapizza.com', $hash, '(11) 99999-9999', 'Av. Central, 1, São Paulo/SP', 'admin']);

    // Configurações
    $pdo->exec("
        INSERT OR IGNORE INTO configuracoes (chave, valor, descricao) VALUES
        ('nome_pizzaria', 'Prisca Pizza Artesanal', 'Nome fantasia da pizzaria'),
        ('telefone', '(11) 3456-7890', 'Telefone fixo'),
        ('whatsapp', '(11) 98888-7777', 'WhatsApp'),
        ('taxa_entrega_padrao', '8.00', 'Taxa de entrega'),
        ('tempo_estimado_entrega', '45', 'Tempo estimado de entrega em minutos');
    ");

    // Categorias
    $pdo->exec("
        INSERT OR IGNORE INTO categorias (id, slug, nome, descricao, icone, ordem) VALUES
        (1, 'pizzas', 'Pizzas Artesanais', 'Massas de fermentação lenta com ingredientes nobres.', 'fa-pizza-slice', 1),
        (2, 'esfihas', 'Esfihas Abertas', 'Massa macia e dourada com recheio fresco.', 'fa-cookie', 2),
        (3, 'bebidas', 'Bebidas Geladas', 'Refrigerantes e sucos naturais.', 'fa-wine-bottle', 3),
        (4, 'sobremesas', 'Sobremesas Especiais', 'Delícias doces irresistíveis.', 'fa-ice-cream', 4);
    ");

    // Tamanhos
    $pdo->exec("
        INSERT OR IGNORE INTO tamanhos (id, categoria_slug, nome, fatias, multiplicador_preco, max_sabores) VALUES
        (1, 'pizzas', 'Broto', '4 fatias (25cm)', 0.70, 1),
        (2, 'pizzas', 'Média', '6 fatias (30cm)', 0.85, 2),
        (3, 'pizzas', 'Grande', '8 fatias (35cm)', 1.00, 2),
        (4, 'pizzas', 'Família', '12 fatias (40cm)', 1.25, 3),
        (5, 'esfihas', 'Unidade Individual', '1 unidade', 1.00, 1);
    ");

    // Adicionais
    $pdo->exec("
        INSERT OR IGNORE INTO adicionais (id, tipo, nome, preco, categoria_slug) VALUES
        (1, 'borda', 'Borda Recheada com Catupiry Original', 9.90, 'pizzas'),
        (2, 'borda', 'Borda Recheada com Cheddar Cremoso', 9.90, 'pizzas'),
        (3, 'borda', 'Borda Vulcão de Chocolate ao Leite', 12.90, 'pizzas'),
        (4, 'borda', 'Sem Borda Recheada (Tradicional)', 0.00, 'pizzas'),
        (5, 'massa', 'Massa Tradicional de Fermentação Natural', 0.00, 'pizzas'),
        (6, 'massa', 'Massa Fina Italiana Crocante', 0.00, 'pizzas'),
        (7, 'massa', 'Massa Pan Alta e Fofinha', 5.00, 'pizzas'),
        (8, 'ingrediente', 'Bacon Crocante Extra', 6.50, 'pizzas'),
        (9, 'ingrediente', 'Queijo Mussarela Extra', 6.00, 'pizzas'),
        (10, 'ingrediente', 'Cebola Caramelizada', 4.00, 'pizzas'),
        (11, 'ingrediente', 'Alho Dourado Crocante', 3.50, 'pizzas');
    ");

    // Produtos
    $pdo->exec("
        INSERT OR IGNORE INTO produtos (id, categoria_id, nome, descricao, preco_base, imagem, disponivel, permite_meio_a_meio, destaque) VALUES
        (1, 1, 'Calabresa Artesanal', 'Molho de tomate pelati especial, mussarela fatiada, generosa camada de calabresa defumada artesanal, cebola roxa e orégano.', 52.00, 'pizza-calabresa.jpg', 1, 1, 1),
        (2, 1, 'Margherita della Nonna', 'Molho pelati italiano, mussarela de búfala fresca, rodelas de tomate maduro, manjericão fresco e azeite extravirgem.', 54.00, 'pizza-margherita.jpg', 1, 1, 1),
        (3, 1, 'Frango com Catupiry', 'Peito de frango desfiado suavemente temperado, milho verde e autêntico Catupiry cremoso.', 56.00, 'pizza-frango-catupiry.jpg', 1, 1, 1),
        (4, 1, 'Quatro Queijos Real', 'Combinação nobre de mussarela, provolone curado, gorgonzola aerado e requeijão cremoso tipo catupiry.', 58.00, 'pizza-quatro-queijos.jpg', 1, 1, 1),
        (5, 1, 'Portuguesa Suprema', 'Molho de tomate, presunto cozido magro, ovos picados, cebola, ervilha fresca, palmito pupunha macio e azeitonas pretas.', 55.00, 'pizza-portuguesa.jpg', 1, 1, 0),
        (6, 1, 'Pepperoni Supremo', 'Molho especial da casa, mussarela, generosa cobertura de pepperoni premium levemente picante e orégano.', 59.00, 'pizza-pepperoni.jpg', 1, 1, 1),
        (7, 1, 'Carne Seca com Requeijão', 'Carne seca artesanal desfiada puxada na manteiga de garrafa, cebola roxa, requeijão cremoso e salsinha.', 62.00, 'pizza-carne-seca.jpg', 1, 1, 0),
        (8, 1, 'Vegetariana Rústica', 'Molho fresco, abobrinha marinada em azeite e ervas, berinjela grelhada, tomate seco, rúcula fresca e queijo mussarela.', 53.00, 'pizza-vegetariana.jpg', 1, 1, 0),
        (9, 1, 'Lombo com Cream Cheese', 'Fatias de lombo canadense defumado, cream cheese Philadelphia original, cebola e gergelim tostado.', 60.00, 'pizza-lombo.jpg', 1, 1, 0),
        (10, 1, 'Brigadeiro com Morangos', 'Massa macia, ganache de chocolate ao leite nobre, granulado gourmet de chocolate belga e morangos frescos.', 48.00, 'pizza-doce-brigadeiro.jpg', 1, 0, 1),
        (11, 2, 'Esfiha de Carne Temperada', 'Recheio tradicional árabe com carne moída de primeira, tomate picado, cebola, especiarias e suco de limão fresco.', 6.50, 'esfiha-carne.jpg', 1, 0, 1),
        (12, 2, 'Esfiha de Queijo com Ervas', 'Mussarela derretida artesanal com toque suave de orégano, cheiro-verde e azeite.', 6.50, 'esfiha-queijo.jpg', 1, 0, 1),
        (13, 2, 'Esfiha de Frango com Catupiry', 'Frango desfiado temperado com alho e especiarias coberto com Catupiry genuíno.', 7.00, 'esfiha-frango.jpg', 1, 0, 1),
        (14, 2, 'Esfiha de Calabresa com Queijo', 'Calabresa moída levemente apimentada com camada generosa de mussarela gratinada.', 6.80, 'esfiha-calabresa.jpg', 1, 0, 0),
        (15, 2, 'Esfiha de Nutella com Ninho', 'Creme de avelã Nutella original polvilhado com leite em pó Ninho.', 8.90, 'esfiha-nutella.jpg', 1, 0, 1),
        (18, 3, 'Coca-Cola Original 2 Litros', 'Garrafa pet de 2 litros bem gelada.', 14.00, 'coca-2l.jpg', 1, 0, 1),
        (19, 3, 'Guaraná Antarctica 2 Litros', 'Garrafa pet de 2 litros bem gelada.', 13.00, 'guarana-2l.jpg', 1, 0, 0),
        (20, 3, 'Coca-Cola Lata 350ml', 'Lata 350ml trincando de gelada.', 6.50, 'coca-lata.jpg', 1, 0, 0),
        (22, 3, 'Suco Natural de Laranja 500ml', 'Suco 100% natural espremido da fruta na hora, sem conservantes.', 9.00, 'suco-laranja.jpg', 1, 0, 1),
        (26, 4, 'Petit Gâteau com Sorvete', 'Bolinho de chocolate meio amargo com recheio cremoso quente, acompanhado de bola de sorvete de baunilha.', 22.00, 'petit-gateau.jpg', 1, 0, 1),
        (27, 4, 'Tiramisù Tradicional Prisca', 'Receita autêntica italiana com biscoitos savoiardi e mascarpone.', 19.00, 'tiramisu.jpg', 1, 0, 1);
    ");

    // Injeta pedidos iniciais para teste
    $pdo->exec("
        INSERT OR IGNORE INTO pedidos (id, codigo, cliente_id, entregador_id, tipo_entrega, endereco_entrega, telefone_contato, subtotal, taxa_entrega, desconto, total, forma_pagamento, status_pagamento, troco_para, status, tempo_estimado_min, observacoes) VALUES
        (1, '#PRISCA-1001', 1, NULL, 'entrega', 'Av. Paulista, 1000 - Apto 42, Bela Vista - São Paulo/SP', '(11) 98765-4321', 63.90, 8.00, 0.00, 71.90, 'pix', 'pago', NULL, 'Recebido', 45, 'Por favor, caprichar no manjericão da pizza.'),
        (2, '#PRISCA-1002', 1, NULL, 'entrega', 'Av. Paulista, 1000 - Apto 42, Bela Vista - São Paulo/SP', '(11) 98765-4321', 65.90, 8.00, 0.00, 73.90, 'cartao', 'pago', NULL, 'Em preparo', 35, 'Sem cebola no meio a meio.'),
        (3, '#PRISCA-1003', 1, 4, 'entrega', 'Av. Brigadeiro Luís Antônio, 2500 - Cerqueira César, SP', '(11) 98765-4321', 52.00, 8.00, 0.00, 60.00, 'dinheiro', 'a_receber', 100.00, 'Saiu para entrega', 15, 'Troco para R$ 100,00. Tocar interfone 31.'),
        (4, '#PRISCA-1004', 1, NULL, 'retirada', 'Retirada no Balcão Prisca Pizza', '(11) 98765-4321', 39.00, 0.00, 0.00, 39.00, 'pix', 'pago', NULL, 'Pronto', 10, 'Cliente a caminho do balcão.');

        INSERT OR IGNORE INTO itens_pedido (id, pedido_id, produto_id, nome_produto, tamanho_nome, sabor_secundario_nome, adicionais_selecionados, observacoes, quantidade, preco_unitario, subtotal_item) VALUES
        (1, 1, 2, 'Margherita della Nonna', 'Grande (8 fatias)', NULL, 'Borda Recheada com Catupiry Original (+R$ 9.90)', 'Bastante manjericão', 1, 63.90, 63.90),
        (2, 2, 1, 'Calabresa Artesanal', 'Grande (8 fatias)', 'Frango com Catupiry', 'Borda Recheada com Cheddar Cremoso (+R$ 9.90)', 'Metade sem cebola', 1, 65.90, 65.90),
        (3, 3, 6, 'Pepperoni Supremo', 'Média (6 fatias)', NULL, 'Sem Borda Recheada', 'Bem assada', 1, 52.00, 52.00),
        (4, 4, 11, 'Esfiha de Carne Temperada', 'Unidade', NULL, 'Limão à parte', '', 6, 6.50, 39.00);

        INSERT OR IGNORE INTO logs_auditoria (id, pedido_id, usuario_id, usuario_nome, usuario_perfil, acao, status_anterior, status_novo, detalhes, ip) VALUES
        (1, 1, 1, 'João da Silva (Cliente)', 'cliente', 'pedido_criado', NULL, 'Recebido', 'Pedido #PRISCA-1001 criado com pagamento Pix aprovado.', '127.0.0.1'),
        (2, 2, 1, 'João da Silva (Cliente)', 'cliente', 'pedido_criado', NULL, 'Recebido', 'Pedido #PRISCA-1002 criado com Cartão de Crédito aprovado.', '127.0.0.1'),
        (3, 2, 2, 'Mariana Atendente', 'caixa', 'status_mudou', 'Recebido', 'Em preparo', 'Atendente conferiu e encaminhou para a cozinha.', '127.0.0.1'),
        (4, 3, 3, 'Mestre Luigi Pizzaiolo', 'pizzaiolo', 'status_mudou', 'Em preparo', 'Pronto', 'Cozinha finalizou o preparo das pizzas.', '127.0.0.1'),
        (5, 3, 4, 'Carlos Motoboy', 'entregador', 'status_mudou', 'Pronto', 'Saiu para entrega', 'Entregador assumiu a entrega do pedido com troco para R$ 100,00.', '127.0.0.1');
    ");
}
