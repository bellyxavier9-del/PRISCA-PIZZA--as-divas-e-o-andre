<?php
/**
 * Prisca Pizza - Página Inicial (Landing Page)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/bd.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDbConnection();

// Busca produtos em destaque
$stmtDestaques = $pdo->query("SELECT p.*, c.nome as categoria_nome FROM produtos p JOIN categorias c ON p.categoria_id = c.id WHERE p.destaque = 1 AND p.disponivel = 1 LIMIT 6");
$produtosDestaque = $stmtDestaques->fetchAll();

// Busca categorias
$stmtCategorias = $pdo->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY ordem ASC");
$categorias = $stmtCategorias->fetchAll();

$pageTitle = APP_NAME . ' - Pizzas e Esfihas Artesanais';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<!-- Hero Section -->
<section class="relative bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950 text-white overflow-hidden py-16 sm:py-24">
    <!-- Decorações de fundo -->
    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#f43f5e_1px,transparent_1px)] [background-size:16px_16px]"></div>
    <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full bg-rose-600/20 blur-3xl pointer-events-none"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            
            <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-rose-500/15 border border-rose-500/30 text-rose-400 text-xs font-bold uppercase tracking-wider">
                    <i class="fa-solid fa-fire text-rose-500 animate-pulse"></i> Forno a Lenha & Fermentação Lenta 48h
                </div>

                <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight font-serif leading-[1.1]">
                    A autêntica tradição da <span class="text-rose-500 italic">pizza italiana</span> na sua mesa.
                </h1>

                <p class="text-slate-300 text-base sm:text-lg max-w-xl mx-auto lg:mx-0 font-light leading-relaxed">
                    Massas leves de digestão perfeita, molho artesanal de tomates pelati e os queijos mais nobres. Peça agora e acompanhe cada etapa do seu pedido em tempo real!
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-2">
                    <a href="<?= BASE_URL ?>/cardapio.php" class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm uppercase tracking-wider shadow-xl shadow-rose-600/30 hover:scale-105 active:scale-95 transition-all text-center flex items-center justify-center gap-2 group">
                        <i class="fa-solid fa-pizza-slice group-hover:rotate-12 transition-transform"></i> Pedir Agora no Cardápio
                    </a>

                    <a href="#como-funciona" class="w-full sm:w-auto px-6 py-4 rounded-2xl bg-white/10 hover:bg-white/15 text-slate-200 font-semibold text-sm transition-colors text-center border border-white/10">
                        Como Funciona
                    </a>
                </div>

                <!-- Selos de Confiança -->
                <div class="pt-6 grid grid-cols-3 gap-4 border-t border-slate-800/80 text-left">
                    <div>
                        <span class="block text-xl sm:text-2xl font-bold text-rose-400 font-brand">40-50 min</span>
                        <span class="text-xs text-slate-400">Entrega rápida na caixa térmica</span>
                    </div>
                    <div>
                        <span class="block text-xl sm:text-2xl font-bold text-amber-400 font-brand">4.9 / 5.0</span>
                        <span class="text-xs text-slate-400">Mais de 5.000 clientes felizes</span>
                    </div>
                    <div>
                        <span class="block text-xl sm:text-2xl font-bold text-emerald-400 font-brand">100% Seguro</span>
                        <span class="text-xs text-slate-400">Pix, cartões e dinheiro</span>
                    </div>
                </div>
            </div>

            <!-- Imagem / Destaque Visual Hero -->
            <div class="lg:col-span-5 flex justify-center">
                <div class="relative w-full max-w-md">
                    <div class="w-full aspect-square rounded-full bg-gradient-to-tr from-rose-600/30 to-amber-500/20 absolute -inset-2 blur-2xl"></div>
                    <div class="relative rounded-3xl bg-slate-800/80 p-6 border border-slate-700/60 shadow-2xl backdrop-blur-md">
                        <div class="rounded-2xl overflow-hidden aspect-video relative flex items-center justify-center bg-gradient-to-br from-amber-900/60 to-rose-950/80 border border-white/10 text-center p-6">
                            <div>
                                <i class="fa-solid fa-pizza-slice text-6xl text-amber-400 mb-3 animate-bounce"></i>
                                <h3 class="text-xl font-bold text-white font-serif">Pizzas & Esfihas Gourmet</h3>
                                <p class="text-xs text-amber-200 mt-1">Calabresa • Margherita • Frango com Catupiry</p>
                            </div>
                        </div>

                        <!-- Mini Card Flutuante de Rastreio -->
                        <div class="mt-4 p-4 rounded-2xl bg-slate-900/90 border border-slate-700/80 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-lg shrink-0">
                                <i class="fa-solid fa-motorcycle animate-pulse"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-bold text-white">Rastreamento Transparente</p>
                                <p class="text-[11px] text-slate-400">Recebido → Cozinha → Pronto → Entrega</p>
                            </div>
                            <span class="px-2 py-1 rounded bg-emerald-500/20 text-emerald-400 text-[10px] font-bold">Ao Vivo</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Categorias Rápidas -->
<section class="py-12 bg-white border-b border-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-xl mx-auto mb-8">
            <h2 class="text-2xl font-extrabold text-slate-900 font-serif">O que você quer saborear hoje?</h2>
            <p class="text-xs text-slate-500 mt-1">Selecione uma categoria para explorar nosso cardápio artesanal</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <?php foreach ($categorias as $cat): ?>
                <a href="<?= BASE_URL ?>/cardapio.php?categoria=<?= e($cat['slug']) ?>" 
                   class="group p-5 rounded-3xl bg-slate-50 hover:bg-rose-50 border border-slate-200/80 hover:border-rose-200 transition-all text-center flex flex-col items-center">
                    <div class="w-14 h-14 rounded-2xl bg-white shadow-sm group-hover:scale-110 group-hover:bg-rose-600 group-hover:text-white text-rose-600 flex items-center justify-center text-2xl transition-all mb-3 border border-slate-100">
                        <i class="fa-solid <?= e($cat['icone'] ?? 'fa-utensils') ?>"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800 group-hover:text-rose-700 transition-colors"><?= e($cat['nome']) ?></h3>
                    <p class="text-[11px] text-slate-400 mt-0.5 line-clamp-1"><?= e($cat['descricao']) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Produtos em Destaque -->
<section class="py-16 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-10 gap-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Mais Pedidas</span>
                <h2 class="text-3xl font-extrabold text-slate-900 font-serif mt-1">Nossos Destaques Imperdíveis</h2>
            </div>
            <a href="<?= BASE_URL ?>/cardapio.php" class="text-xs font-bold uppercase tracking-wider text-rose-600 hover:text-rose-700 flex items-center gap-1.5 transition-colors">
                Ver cardápio completo <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($produtosDestaque as $prod): ?>
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/70 hover:shadow-md transition-all flex flex-col justify-between group">
                    <div>
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-100">
                                <?= e($prod['categoria_nome']) ?>
                            </span>
                            <span class="text-xs font-bold text-amber-500 flex items-center gap-1">
                                <i class="fa-solid fa-star"></i> 4.9
                            </span>
                        </div>

                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-rose-600 transition-colors leading-snug">
                            <?= e($prod['nome']) ?>
                        </h3>

                        <p class="text-xs text-slate-500 mt-2 line-clamp-3 leading-relaxed">
                            <?= e($prod['descricao']) ?>
                        </p>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                        <div>
                            <span class="block text-[10px] uppercase font-semibold text-slate-400">A partir de</span>
                            <span class="text-lg font-extrabold text-slate-900"><?= formatMoney($prod['preco_base']) ?></span>
                        </div>

                        <a href="<?= BASE_URL ?>/cardapio.php?produto_id=<?= $prod['id'] ?>" 
                           class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-rose-600 text-white text-xs font-bold uppercase tracking-wider transition-colors shadow">
                            Personalizar
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Como Funciona Section -->
<section id="como-funciona" class="py-16 bg-white border-t border-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-xl mx-auto mb-12">
            <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Simples e Rápido</span>
            <h2 class="text-3xl font-extrabold text-slate-900 font-serif mt-1">Como Funciona o Pedido</h2>
            <p class="text-xs text-slate-500 mt-1">Do seu celular direto para o forno e até sua porta</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="text-center p-4">
                <div class="w-16 h-16 rounded-3xl bg-rose-50 text-rose-600 text-2xl font-bold flex items-center justify-center mx-auto mb-4 border border-rose-100 shadow-sm">
                    1
                </div>
                <h4 class="text-sm font-bold text-slate-900">Escolha o Sabor</h4>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Personalize tamanhos, meio a meio, bordas recheadas de Catupiry ou Cheddar e adicionais.</p>
            </div>

            <div class="text-center p-4">
                <div class="w-16 h-16 rounded-3xl bg-amber-50 text-amber-600 text-2xl font-bold flex items-center justify-center mx-auto mb-4 border border-amber-100 shadow-sm">
                    2
                </div>
                <h4 class="text-sm font-bold text-slate-900">Pagamento Simulado</h4>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Pague por Pix com QR Code, Cartão ou selecione Dinheiro com troco na entrega.</p>
            </div>

            <div class="text-center p-4">
                <div class="w-16 h-16 rounded-3xl bg-blue-50 text-blue-600 text-2xl font-bold flex items-center justify-center mx-auto mb-4 border border-blue-100 shadow-sm">
                    3
                </div>
                <h4 class="text-sm font-bold text-slate-900">Forno & Cozinha</h4>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">O caixa confere e encaminha o pedido para a cozinha. Nosso pizzaiolo prepara com perfeição.</p>
            </div>

            <div class="text-center p-4">
                <div class="w-16 h-16 rounded-3xl bg-emerald-50 text-emerald-600 text-2xl font-bold flex items-center justify-center mx-auto mb-4 border border-emerald-100 shadow-sm">
                    4
                </div>
                <h4 class="text-sm font-bold text-slate-900">Entrega Quentinha</h4>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">O motoboy assume o pedido e leva direto até sua casa ou você retira no balcão!</p>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
