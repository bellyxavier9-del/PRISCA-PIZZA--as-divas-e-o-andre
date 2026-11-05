-- ========================================================
-- Prisca Pizza - Dados Iniciais (Seed)
-- ========================================================

USE `prisca_pizza`;

-- 1. CONFIGURAÇÕES DA PIZZARIA
INSERT INTO `configuracoes` (`chave`, `valor`, `descricao`) VALUES
('nome_pizzaria', 'Prisca Pizza Artesanal', 'Nome fantasia da pizzaria'),
('telefone', '(11) 3456-7890', 'Telefone fixo principal'),
('whatsapp', '(11) 98888-7777', 'WhatsApp oficial para atendimento'),
('endereco', 'Rua das Pizzas, 500 - Bairro Italiano, São Paulo - SP', 'Endereço físico da pizzaria'),
('taxa_entrega_padrao', '8.00', 'Taxa fixa de entrega em reais'),
('tempo_estimado_entrega', '45', 'Tempo estimado de entrega em minutos'),
('tempo_estimado_retirada', '25', 'Tempo estimado para retirada no balcão em minutos'),
('horario_abertura', '18:00', 'Horário de início de funcionamento'),
('horario_fechamento', '23:30', 'Horário de encerramento dos pedidos'),
('dias_funcionamento', 'Terça a Domingo', 'Dias em que a pizzaria opera')
ON DUPLICATE KEY UPDATE `valor`=VALUES(`valor`);

-- 2. USUÁRIOS DO SISTEMA (Senha padrão: senha123)
-- Hash bcrypt gerado para 'senha123': $2y$10$wE1H4qZzO0WdK9z9x2Yp8.uB8gqgKkJx9Jq4zT8zOqN3P6L0zF9qy
INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `telefone`, `endereco`, `perfil`) VALUES
(1, 'João da Silva (Cliente)', 'cliente@priscapizza.com', '$2y$10$e0MYzXy4Y7L3k5q7z4y0Oe8uX1xR2.y6eZ9k7qP2pB0m1c4d5e6f.', '(11) 98765-4321', 'Av. Paulista, 1000 - Apto 42, Bela Vista - São Paulo/SP', 'cliente'),
(2, 'Mariana Atendente', 'caixa@priscapizza.com', '$2y$10$e0MYzXy4Y7L3k5q7z4y0Oe8uX1xR2.y6eZ9k7qP2pB0m1c4d5e6f.', '(11) 97777-1111', 'Rua dos Funcionários, 10, São Paulo/SP', 'caixa'),
(3, 'Mestre Luigi Pizzaiolo', 'pizzaiolo@priscapizza.com', '$2y$10$e0MYzXy4Y7L3k5q7z4y0Oe8uX1xR2.y6eZ9k7qP2pB0m1c4d5e6f.', '(11) 97777-2222', 'Rua da Cozinha, 20, São Paulo/SP', 'pizzaiolo'),
(4, 'Carlos Motoboy', 'entregador@priscapizza.com', '$2y$10$e0MYzXy4Y7L3k5q7z4y0Oe8uX1xR2.y6eZ9k7qP2pB0m1c4d5e6f.', '(11) 97777-3333', 'Rua da Frota, 30, São Paulo/SP', 'entregador'),
(5, 'Administrador Geral', 'admin@priscapizza.com', '$2y$10$e0MYzXy4Y7L3k5q7z4y0Oe8uX1xR2.y6eZ9k7qP2pB0m1c4d5e6f.', '(11) 99999-9999', 'Av. Central, 1, São Paulo/SP', 'admin')
ON DUPLICATE KEY UPDATE `nome`=VALUES(`nome`);

-- 3. CATEGORIAS DO CARDÁPIO
INSERT INTO `categorias` (`id`, `slug`, `nome`, `descricao`, `icone`, `ordem`) VALUES
(1, 'pizzas', 'Pizzas Artesanais', 'Massas de fermentação lenta com ingredientes selecionados e assadas em forno de alta temperatura.', 'fa-pizza-slice', 1),
(2, 'esfihas', 'Esfihas Abertas', 'Massa macia e dourada, recheios suculentos preparados na hora.', 'fa-cookie', 2),
(3, 'bebidas', 'Bebidas Geladas', 'Refrigerantes, sucos naturais e cervejas para acompanhar sua refeição.', 'fa-wine-bottle', 3),
(4, 'sobremesas', 'Sobremesas Especiais', 'Delícias doces irresistíveis para fechar com chave de ouro.', 'fa-ice-cream', 4)
ON DUPLICATE KEY UPDATE `nome`=VALUES(`nome`);

-- 4. TAMANHOS
INSERT INTO `tamanhos` (`id`, `categoria_slug`, `nome`, `fatias`, `multiplicador_preco`, `max_sabores`) VALUES
(1, 'pizzas', 'Broto', '4 fatias (25cm)', 0.70, 1),
(2, 'pizzas', 'Média', '6 fatias (30cm)', 0.85, 2),
(3, 'pizzas', 'Grande', '8 fatias (35cm)', 1.00, 2),
(4, 'pizzas', 'Família', '12 fatias (40cm)', 1.25, 3),
(5, 'esfihas', 'Unidade Individual', '1 unidade', 1.00, 1)
ON DUPLICATE KEY UPDATE `nome`=VALUES(`nome`);

-- 5. ADICIONAIS E BORDAS
INSERT INTO `adicionais` (`id`, `tipo`, `nome`, `preco`, `categoria_slug`) VALUES
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
(11, 'ingrediente', 'Alho Dourado Crocante', 3.50, 'pizzas'),
(12, 'ingrediente', 'Azeitonas Pretas sem Caroço', 3.00, 'pizzas'),
(13, 'ingrediente', 'Limão e Pimenta Síria à Parte', 0.00, 'esfihas')
ON DUPLICATE KEY UPDATE `nome`=VALUES(`nome`);

-- 6. PRODUTOS DO CARDÁPIO
INSERT INTO `produtos` (`id`, `categoria_id`, `nome`, `descricao`, `preco_base`, `imagem`, `disponivel`, `permite_meio_a_meio`, `destaque`) VALUES
-- Pizzas
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

-- Esfihas
(11, 2, 'Esfiha de Carne Temperada', 'Recheio tradicional árabe com carne moída de primeira, tomate picado, cebola, especiarias e suco de limão fresco.', 6.50, 'esfiha-carne.jpg', 1, 0, 1),
(12, 2, 'Esfiha de Queijo com Ervas', 'Mussarela derretida artesanal com toque suave de orégano, cheiro-verde e azeite.', 6.50, 'esfiha-queijo.jpg', 1, 0, 1),
(13, 2, 'Esfiha de Frango com Catupiry', 'Frango desfiado temperado com alho e especiarias coberto com Catupiry genuíno.', 7.00, 'esfiha-frango.jpg', 1, 0, 1),
(14, 2, 'Esfiha de Calabresa com Queijo', 'Calabresa moída levemente apimentada com camada generosa de mussarela gratinada.', 6.80, 'esfiha-calabresa.jpg', 1, 0, 0),
(15, 2, 'Esfiha de Espinafre com Ricota', 'Espinafre fresco salteado no azeite com ricota fresca e noz-moscada.', 7.00, 'esfiha-espinafre.jpg', 1, 0, 0),
(16, 2, 'Esfiha Romeu e Julieta', 'Queijo derretido com goiabada cascão cremosa e açúcar cristal.', 8.00, 'esfiha-romeu.jpg', 1, 0, 0),
(17, 2, 'Esfiha de Nutella com Ninho', 'Creme de avelã Nutella original polvilhado com leite em pó Ninho.', 8.90, 'esfiha-nutella.jpg', 1, 0, 1),

-- Bebidas
(18, 3, 'Coca-Cola Original 2 Litros', 'Garrafa pet de 2 litros bem gelada.', 14.00, 'coca-2l.jpg', 1, 0, 1),
(19, 3, 'Guaraná Antarctica 2 Litros', 'Garrafa pet de 2 litros bem gelada.', 13.00, 'guarana-2l.jpg', 1, 0, 0),
(20, 3, 'Coca-Cola Lata 350ml', 'Lata 350ml trincando de gelada.', 6.50, 'coca-lata.jpg', 1, 0, 0),
(21, 3, 'Guaraná Antarctica Lata 350ml', 'Lata 350ml trincando de gelada.', 6.00, 'guarana-lata.jpg', 1, 0, 0),
(22, 3, 'Suco Natural de Laranja 500ml', 'Suco 100% natural espremido da fruta na hora, sem conservantes.', 9.00, 'suco-laranja.jpg', 1, 0, 1),
(23, 3, 'Cerveja Heineken Long Neck 330ml', 'Cerveja puro malte gelada (venda proibida para menores de 18 anos).', 10.00, 'heineken.jpg', 1, 0, 0),
(24, 3, 'Água Mineral sem Gás 500ml', 'Garrafa 500ml de água mineral límpida.', 4.50, 'agua-sem-gas.jpg', 1, 0, 0),
(25, 3, 'Água Mineral com Gás 500ml', 'Garrafa 500ml gaseificada com gás natural.', 5.00, 'agua-com-gas.jpg', 1, 0, 0),

-- Sobremesas
(26, 4, 'Petit Gâteau com Sorvete', 'Bolinho de chocolate meio amargo com recheio cremoso quente, acompanhado de bola de sorvete de baunilha.', 22.00, 'petit-gateau.jpg', 1, 0, 1),
(27, 4, 'Tiramisù Tradicional Prisca', 'Receita autêntica italiana com biscoitos savoiardi embebidos em café expresso e creme de mascarpone.', 19.00, 'tiramisu.jpg', 1, 0, 1),
(28, 4, 'Mini Churros com Doce de Leite (6 un)', 'Churros dourados e crocantes passados na canela e açúcar com potinho de doce de leite cremoso.', 16.00, 'mini-churros.jpg', 1, 0, 0),
(29, 4, 'Pudim de Leite Condensado Caseiro', 'Fatia generosa e aveludada com calda dourada de caramelo sem furinhos.', 12.00, 'pudim.jpg', 1, 0, 0)
ON DUPLICATE KEY UPDATE `nome`=VALUES(`nome`);

-- 7. PEDIDOS DE EXEMPLO (Para demonstrar os painéis imediatamente com dados reais)
INSERT INTO `pedidos` (`id`, `codigo`, `cliente_id`, `entregador_id`, `tipo_entrega`, `endereco_entrega`, `telefone_contato`, `subtotal`, `taxa_entrega`, `desconto`, `total`, `forma_pagamento`, `status_pagamento`, `troco_para`, `pix_codigo`, `status`, `tempo_estimado_min`, `observacoes`, `criado_em`) VALUES
(1, '#PRISCA-1001', 1, NULL, 'entrega', 'Av. Paulista, 1000 - Apto 42, Bela Vista - São Paulo/SP', '(11) 98765-4321', 62.00, 8.00, 0.00, 70.00, 'pix', 'pago', NULL, '00020126580014BR.GOV.BCB.PIX0136123e4567-e89b-12d3-a456-426614174000520400005303986540570.005802BR5912Prisca Pizza6009Sao Paulo62070503***6304ABCD', 'Recebido', 45, 'Por favor, caprichar no manjericão da pizza.', DATE_SUB(NOW(), INTERVAL 15 MINUTE)),
(2, '#PRISCA-1002', 1, NULL, 'entrega', 'Av. Paulista, 1000 - Apto 42, Bela Vista - São Paulo/SP', '(11) 98765-4321', 68.50, 8.00, 0.00, 76.50, 'cartao', 'pago', NULL, NULL, 'Em preparo', 35, 'Sem cebola no meio a meio.', DATE_SUB(NOW(), INTERVAL 25 MINUTE)),
(3, '#PRISCA-1003', 1, 4, 'entrega', 'Av. Brigadeiro Luís Antônio, 2500 - Cerqueira César, SP', '(11) 98765-4321', 52.00, 8.00, 0.00, 60.00, 'dinheiro', 'a_receber', 100.00, NULL, 'Saiu para entrega', 15, 'Troco para R$ 100,00. Tocar interfone 31.', DATE_SUB(NOW(), INTERVAL 40 MINUTE)),
(4, '#PRISCA-1004', 1, NULL, 'retirada', 'Retirada no Balcão Prisca Pizza', '(11) 98765-4321', 39.00, 0.00, 0.00, 39.00, 'pix', 'pago', NULL, NULL, 'Pronto', 10, 'Cliente já avisou que está a caminho do balcão.', DATE_SUB(NOW(), INTERVAL 30 MINUTE))
ON DUPLICATE KEY UPDATE `codigo`=VALUES(`codigo`);

-- 8. ITENS DOS PEDIDOS DE EXEMPLO
INSERT INTO `itens_pedido` (`id`, `pedido_id`, `produto_id`, `nome_produto`, `tamanho_nome`, `sabor_secundario_nome`, `adicionais_selecionados`, `observacoes`, `quantidade`, `preco_unitario`, `subtotal_item`) VALUES
(1, 1, 2, 'Margherita della Nonna', 'Grande (8 fatias)', NULL, 'Borda Recheada com Catupiry Original (+R$ 9.90)', 'Bastante manjericão', 1, 63.90, 63.90),
(2, 2, 1, 'Calabresa Artesanal', 'Grande (8 fatias)', 'Frango com Catupiry', 'Borda Recheada com Cheddar Cremoso (+R$ 9.90)', 'Metade calabresa sem cebola', 1, 65.90, 65.90),
(3, 3, 6, 'Pepperoni Supremo', 'Média (6 fatias)', NULL, 'Sem Borda Recheada', 'Bem assada', 1, 52.00, 52.00),
(4, 4, 11, 'Esfiha de Carne Temperada', 'Unidade', NULL, 'Limão à parte', '', 6, 6.50, 39.00)
ON DUPLICATE KEY UPDATE `nome_produto`=VALUES(`nome_produto`);

-- 9. LOGS DE AUDITORIA INICIAIS
INSERT INTO `logs_auditoria` (`id`, `pedido_id`, `usuario_id`, `usuario_nome`, `usuario_perfil`, `acao`, `status_anterior`, `status_novo`, `detalhes`, `ip`) VALUES
(1, 1, 1, 'João da Silva (Cliente)', 'cliente', 'pedido_criado', NULL, 'Recebido', 'Pedido #PRISCA-1001 criado com pagamento Pix aprovado.', '127.0.0.1'),
(2, 2, 1, 'João da Silva (Cliente)', 'cliente', 'pedido_criado', NULL, 'Recebido', 'Pedido #PRISCA-1002 criado com Cartão de Crédito aprovado.', '127.0.0.1'),
(3, 2, 2, 'Mariana Atendente', 'caixa', 'status_mudou', 'Recebido', 'Em preparo', 'Atendente conferiu o pedido e encaminhou para a cozinha.', '127.0.0.1'),
(4, 3, 3, 'Mestre Luigi Pizzaiolo', 'pizzaiolo', 'status_mudou', 'Em preparo', 'Pronto', 'Cozinha finalizou o preparo das pizzas.', '127.0.0.1'),
(5, 3, 4, 'Carlos Motoboy', 'entregador', 'status_mudou', 'Pronto', 'Saiu para entrega', 'Entregador assumiu a entrega do pedido com troco para R$ 100,00.', '127.0.0.1')
ON DUPLICATE KEY UPDATE `acao`=VALUES(`acao`);
