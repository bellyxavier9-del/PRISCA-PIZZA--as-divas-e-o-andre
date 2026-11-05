<?php
/**
 * Prisca Pizza - Cardápio Interativo com Customização Completa
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/bd.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDbConnection();

// Busca categorias
$categorias = $pdo->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY ordem ASC")->fetchAll();

// Busca todos os produtos ativos
$produtosStmt = $pdo->query("SELECT p.*, c.slug as categoria_slug, c.nome as categoria_nome FROM produtos p JOIN categorias c ON p.categoria_id = c.id WHERE p.disponivel = 1 ORDER BY p.categoria_id ASC, p.preco_base ASC");
$produtos = $produtosStmt->fetchAll();

// Busca tamanhos disponíveis
$tamanhosStmt = $pdo->query("SELECT * FROM tamanhos WHERE ativo = 1 ORDER BY multiplicador_preco ASC");
$tamanhos = $tamanhosStmt->fetchAll();

// Busca adicionais (bordas, massas, ingredientes)
$adicionaisStmt = $pdo->query("SELECT * FROM adicionais WHERE ativo = 1 ORDER BY tipo ASC, preco ASC");
$adicionais = $adicionaisStmt->fetchAll();

// Separa pizzas para seleção do meio a meio
$todasPizzas = array_filter($produtos, function($p) {
    return $p['categoria_slug'] === 'pizzas' && $p['permite_meio_a_meio'] == 1;
});

$pageTitle = 'Cardápio Completo - ' . APP_NAME;
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">

    <!-- Cabeçalho do Cardápio -->
    <div class="mb-8">
        <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Nosso Menu</span>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 font-serif mt-1">Cardápio Prisca Pizza</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-xl">
            Pizzas de fermentação artesanal, esfihas assadas na hora, bebidas geladas e sobremesas irresistíveis.
        </p>

        <!-- Filtros de Categorias e Barra de Pesquisa -->
        <div class="mt-6 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
            
            <!-- Tabs de Categoria -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 md:pb-0 scrollbar-none" id="category-tabs">
                <button type="button" class="cat-tab px-4 py-2.5 rounded-2xl text-xs font-bold uppercase tracking-wider bg-rose-600 text-white transition-all shrink-0 active shadow-md shadow-rose-600/25" data-filter="all">
                    Todos os Itens
                </button>
                <?php foreach ($categorias as $cat): ?>
                    <button type="button" class="cat-tab px-4 py-2.5 rounded-2xl text-xs font-bold uppercase tracking-wider bg-white text-slate-600 hover:text-rose-600 hover:bg-rose-50 border border-slate-200 transition-all shrink-0" data-filter="<?= e($cat['slug']) ?>">
                        <i class="fa-solid <?= e($cat['icone']) ?> mr-1.5"></i> <?= e($cat['nome']) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Campo de Busca em Tempo Real -->
            <div class="relative w-full md:w-72">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="search-input" placeholder="Buscar sabor ou ingrediente..." class="w-full pl-9 pr-4 py-2.5 text-xs rounded-2xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 shadow-sm">
            </div>

        </div>
    </div>

    <!-- Grid de Produtos -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" id="products-grid">
        <?php foreach ($produtos as $prod): ?>
            <div class="product-card bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 hover:shadow-md transition-all flex flex-col justify-between" 
                 data-category="<?= e($prod['categoria_slug']) ?>"
                 data-name="<?= e(mb_strtolower($prod['nome'], 'UTF-8')) ?>"
                 data-description="<?= e(mb_strtolower($prod['descricao'], 'UTF-8')) ?>">
                
                <div>
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700">
                            <?= e($prod['categoria_nome']) ?>
                        </span>
                        <?php if ($prod['permite_meio_a_meio']): ?>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/50">
                                1/2 a 1/2
                            </span>
                        <?php endif; ?>
                    </div>

                    <h3 class="text-lg font-bold text-slate-900 leading-snug">
                        <?= e($prod['nome']) ?>
                    </h3>

                    <p class="text-xs text-slate-500 mt-2 line-clamp-3 leading-relaxed">
                        <?= e($prod['descricao']) ?>
                    </p>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        <span class="block text-[10px] uppercase font-semibold text-slate-400">
                            <?= $prod['categoria_slug'] === 'pizzas' ? 'A partir de' : 'Valor' ?>
                        </span>
                        <span class="text-lg font-extrabold text-slate-900"><?= formatMoney($prod['preco_base']) ?></span>
                    </div>

                    <button type="button" 
                            class="btn-customize px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold uppercase tracking-wider transition-all shadow-md shadow-rose-600/20 active:scale-95"
                            data-product='<?= json_encode($prod, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                        <?= $prod['categoria_slug'] === 'pizzas' ? 'Personalizar' : 'Adicionar' ?>
                    </button>
                </div>

            </div>
        <?php endforeach; ?>
    </div>

    <!-- Mensagem de Nenhum Produto Encontrado na Busca -->
    <div id="no-products" class="hidden text-center py-16">
        <div class="w-16 h-16 rounded-3xl bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mx-auto mb-3">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>
        <h4 class="text-base font-bold text-slate-700">Nenhum item encontrado</h4>
        <p class="text-xs text-slate-400 mt-1">Tente buscar por outro sabor ou ingrediente.</p>
    </div>

</main>

<!-- =========================================================
     MODAL DE CUSTOMIZAÇÃO DO PRODUTO (PIZZAS, ESFIHAS, ETC)
     ========================================================= -->
<div id="custom-product-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm animate-in fade-in duration-200">
    <div class="relative w-full max-w-xl max-h-[90vh] flex flex-col rounded-3xl bg-white shadow-2xl overflow-hidden border border-slate-100">
        
        <!-- Cabeçalho do Modal -->
        <div class="p-5 sm:p-6 bg-slate-900 text-white flex items-start justify-between border-b border-slate-800">
            <div>
                <span id="modal-product-cat" class="text-[10px] uppercase font-bold tracking-wider text-rose-400">Categoria</span>
                <h3 id="modal-product-name" class="text-xl font-bold font-serif text-white mt-0.5">Nome do Produto</h3>
                <p id="modal-product-desc" class="text-xs text-slate-300 mt-1 line-clamp-2">Descrição detalhada com ingredientes.</p>
            </div>
            <button type="button" id="modal-close-btn" class="text-slate-400 hover:text-white p-2 rounded-xl hover:bg-white/10 transition-colors">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Conteúdo Rolável das Opções -->
        <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-6 text-xs text-slate-700">

            <!-- 1. Tamanho (Para Pizzas) -->
            <div id="size-section" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h5 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-arrows-left-right text-rose-600"></i> Escolha o Tamanho
                    </h5>
                    <span class="text-[10px] text-rose-600 font-semibold bg-rose-50 px-2 py-0.5 rounded">Obrigatório</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5" id="sizes-container">
                    <?php foreach ($tamanhos as $tam): ?>
                        <?php if ($tam['categoria_slug'] === 'pizzas'): ?>
                            <label class="size-option relative block p-3 rounded-2xl border border-slate-200 cursor-pointer hover:border-rose-400 transition-all text-center">
                                <input type="radio" name="selected_size" value="<?= e($tam['nome']) ?>" data-mult="<?= $tam['multiplicador_preco'] ?>" data-max-sabores="<?= $tam['max_sabores'] ?>" class="hidden" <?= $tam['nome'] === 'Grande' ? 'checked' : '' ?>>
                                <span class="block font-bold text-slate-900"><?= e($tam['nome']) ?></span>
                                <span class="block text-[10px] text-slate-400 mt-0.5"><?= e($tam['fatias']) ?></span>
                            </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 2. Meio a Meio (Segundo Sabor de Pizza) -->
            <div id="second-flavor-section" class="space-y-2 hidden">
                <div class="flex items-center justify-between">
                    <h5 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-half-stroke text-amber-500"></i> Dividir Sabor (1/2 a 1/2)?
                    </h5>
                    <span class="text-[10px] text-slate-400">Opcional</span>
                </div>
                <select id="second-flavor-select" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs bg-slate-50 focus:outline-none focus:ring-2 focus:ring-rose-500">
                    <option value="">Apenas 1 Sabor (Inteira)</option>
                    <?php foreach ($todasPizzas as $p2): ?>
                        <option value="<?= e($p2['nome']) ?>" data-price="<?= $p2['preco_base'] ?>">
                            Metade <?= e($p2['nome']) ?> (<?= formatMoney($p2['preco_base']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 3. Bordas Recheadas (Para Pizzas) -->
            <div id="crust-section" class="space-y-2.5">
                <div class="flex items-center justify-between">
                    <h5 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-ring text-amber-600"></i> Borda Recheada
                    </h5>
                    <span class="text-[10px] text-slate-400">Opcional</span>
                </div>
                <div class="space-y-1.5">
                    <?php foreach ($adicionais as $adi): ?>
                        <?php if ($adi['tipo'] === 'borda'): ?>
                            <label class="flex items-center justify-between p-2.5 rounded-xl border border-slate-100 hover:bg-slate-50 cursor-pointer transition-colors">
                                <div class="flex items-center gap-2.5">
                                    <input type="radio" name="selected_crust" value="<?= e($adi['nome']) ?>" data-price="<?= $adi['preco'] ?>" class="text-rose-600 focus:ring-rose-500" <?= $adi['preco'] == 0 ? 'checked' : '' ?>>
                                    <span class="font-medium text-slate-800"><?= e($adi['nome']) ?></span>
                                </div>
                                <span class="font-bold text-slate-900"><?= $adi['preco'] > 0 ? '+ ' . formatMoney($adi['preco']) : 'Grátis' ?></span>
                            </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 4. Ingredientes Extras e Adicionais -->
            <div id="extras-section" class="space-y-2.5">
                <div class="flex items-center justify-between">
                    <h5 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-plus text-rose-600"></i> Adicionais Extras
                    </h5>
                    <span class="text-[10px] text-slate-400">Opcional</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <?php foreach ($adicionais as $adi): ?>
                        <?php if ($adi['tipo'] === 'ingrediente'): ?>
                            <label class="flex items-center justify-between p-2.5 rounded-xl border border-slate-100 hover:bg-slate-50 cursor-pointer transition-colors">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" name="extra_ingredient" value="<?= e($adi['nome']) ?>" data-price="<?= $adi['preco'] ?>" class="rounded text-rose-600 focus:ring-rose-500">
                                    <span class="font-medium text-slate-700"><?= e($adi['nome']) ?></span>
                                </div>
                                <span class="font-bold text-slate-900 text-[11px]"><?= $adi['preco'] > 0 ? '+ ' . formatMoney($adi['preco']) : 'Grátis' ?></span>
                            </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 5. Observações Especiais do Cliente -->
            <div class="space-y-1.5">
                <label for="modal-observations" class="block font-bold uppercase tracking-wider text-slate-800 text-[11px]">
                    Observações para a Cozinha
                </label>
                <textarea id="modal-observations" rows="2" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none" placeholder="Ex: Sem cebola, massa bem assada, enviar sachês de azeite..."></textarea>
            </div>

        </div>

        <!-- Rodapé do Modal com Quantidade e Preço Total -->
        <div class="p-4 sm:p-5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-slate-500 uppercase">Qtd:</span>
                <div class="flex items-center bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                    <button type="button" id="qty-minus" class="w-8 h-8 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-colors font-bold">-</button>
                    <span id="qty-display" class="w-8 text-center font-bold text-xs">1</span>
                    <button type="button" id="qty-plus" class="w-8 h-8 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-colors font-bold">+</button>
                </div>
            </div>

            <button type="button" id="btn-add-to-cart" class="w-full sm:w-auto px-6 py-3 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-rose-600/30 transition-all flex items-center justify-center gap-3">
                <span>Adicionar à Sacola</span>
                <span class="w-1.5 h-1.5 rounded-full bg-white/40"></span>
                <span id="modal-total-display">R$ 0,00</span>
            </button>
        </div>

    </div>
</div>

<!-- Script de Interatividade do Cardápio -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Filtros de Categorias
    const tabs = document.querySelectorAll('.cat-tab');
    const cards = document.querySelectorAll('.product-card');
    const searchInput = document.getElementById('search-input');
    const noProducts = document.getElementById('no-products');

    let currentFilter = 'all';

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => {
                t.classList.remove('bg-rose-600', 'text-white', 'shadow-md', 'shadow-rose-600/25');
                t.classList.add('bg-white', 'text-slate-600', 'border', 'border-slate-200');
            });
            this.classList.remove('bg-white', 'text-slate-600', 'border', 'border-slate-200');
            this.classList.add('bg-rose-600', 'text-white', 'shadow-md', 'shadow-rose-600/25');

            currentFilter = this.getAttribute('data-filter');
            applyFilters();
        });
    });

    searchInput.addEventListener('input', applyFilters);

    function applyFilters() {
        const query = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;

        cards.forEach(card => {
            const cat = card.getAttribute('data-category');
            const name = card.getAttribute('data-name');
            const desc = card.getAttribute('data-description');

            const matchCat = (currentFilter === 'all' || cat === currentFilter);
            const matchSearch = (!query || name.includes(query) || desc.includes(query));

            if (matchCat && matchSearch) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        noProducts.classList.toggle('hidden', visibleCount > 0);
    }

    // 2. Lógica do Modal de Customização
    const modal = document.getElementById('custom-product-modal');
    const modalClose = document.getElementById('modal-close-btn');
    const modalName = document.getElementById('modal-product-name');
    const modalDesc = document.getElementById('modal-product-desc');
    const modalCat = document.getElementById('modal-product-cat');
    const sizeSection = document.getElementById('size-section');
    const secondFlavorSection = document.getElementById('second-flavor-section');
    const crustSection = document.getElementById('crust-section');
    const extrasSection = document.getElementById('extras-section');
    const observations = document.getElementById('modal-observations');
    const qtyDisplay = document.getElementById('qty-display');
    const qtyMinus = document.getElementById('qty-minus');
    const qtyPlus = document.getElementById('qty-plus');
    const totalDisplay = document.getElementById('modal-total-display');
    const btnAddToCart = document.getElementById('btn-add-to-cart');

    let currentProduct = null;
    let currentQty = 1;

    document.querySelectorAll('.btn-customize').forEach(btn => {
        btn.addEventListener('click', function() {
            const prod = JSON.parse(this.getAttribute('data-product'));
            openCustomizationModal(prod);
        });
    });

    function openCustomizationModal(prod) {
        currentProduct = prod;
        currentQty = 1;
        qtyDisplay.textContent = '1';
        observations.value = '';

        modalName.textContent = prod.nome;
        modalDesc.textContent = prod.descricao || 'Sem descrição cadastrada.';
        modalCat.textContent = prod.categoria_nome || 'Produto';

        const isPizza = (prod.categoria_slug === 'pizzas');

        sizeSection.style.display = isPizza ? 'block' : 'none';
        secondFlavorSection.style.display = (isPizza && prod.permite_meio_a_meio == 1) ? 'block' : 'none';
        crustSection.style.display = isPizza ? 'block' : 'none';
        extrasSection.style.display = isPizza ? 'block' : 'none';

        // Reseta seleções
        document.querySelectorAll('input[name="selected_size"]').forEach(r => {
            r.checked = (r.value === 'Grande');
            updateSizeCardStyle(r);
        });
        document.querySelectorAll('input[name="selected_crust"]').forEach(r => {
            r.checked = (parseFloat(r.getAttribute('data-price')) === 0);
        });
        document.querySelectorAll('input[name="extra_ingredient"]').forEach(c => c.checked = false);
        const secondFlavor = document.getElementById('second-flavor-select');
        if (secondFlavor) secondFlavor.value = '';

        calculateModalTotal();

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    modalClose.addEventListener('click', () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    });

    // Atualização de tamanho
    document.querySelectorAll('input[name="selected_size"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('input[name="selected_size"]').forEach(updateSizeCardStyle);
            calculateModalTotal();
        });
    });

    function updateSizeCardStyle(radio) {
        const card = radio.closest('.size-option');
        if (radio.checked) {
            card.classList.add('border-rose-600', 'bg-rose-50/50', 'ring-2', 'ring-rose-500');
            card.classList.remove('border-slate-200');
        } else {
            card.classList.remove('border-rose-600', 'bg-rose-50/50', 'ring-2', 'ring-rose-500');
            card.classList.add('border-slate-200');
        }
    }

    // Eventos de alteração para recalcular
    document.querySelectorAll('input[name="selected_crust"], input[name="extra_ingredient"]').forEach(el => {
        el.addEventListener('change', calculateModalTotal);
    });
    document.getElementById('second-flavor-select')?.addEventListener('change', calculateModalTotal);

    qtyMinus.addEventListener('click', () => {
        if (currentQty > 1) {
            currentQty--;
            qtyDisplay.textContent = currentQty;
            calculateModalTotal();
        }
    });

    qtyPlus.addEventListener('click', () => {
        currentQty++;
        qtyDisplay.textContent = currentQty;
        calculateModalTotal();
    });

    function calculateModalTotal() {
        if (!currentProduct) return 0;

        let basePrice = parseFloat(currentProduct.preco_base);
        const isPizza = (currentProduct.categoria_slug === 'pizzas');

        let multiplier = 1.0;
        if (isPizza) {
            const checkedSize = document.querySelector('input[name="selected_size"]:checked');
            if (checkedSize) {
                multiplier = parseFloat(checkedSize.getAttribute('data-mult') || 1.0);
            }

            // Se for meio a meio com sabor mais caro, usa o maior preço
            const secondFlavor = document.getElementById('second-flavor-select');
            if (secondFlavor && secondFlavor.value) {
                const opt = secondFlavor.options[secondFlavor.selectedIndex];
                const p2Price = parseFloat(opt.getAttribute('data-price') || 0);
                if (p2Price > basePrice) {
                    basePrice = p2Price;
                }
            }
        }

        let unitPrice = basePrice * multiplier;

        // Borda recheada
        if (isPizza) {
            const checkedCrust = document.querySelector('input[name="selected_crust"]:checked');
            if (checkedCrust) {
                unitPrice += parseFloat(checkedCrust.getAttribute('data-price') || 0);
            }
        }

        // Ingredientes extras
        document.querySelectorAll('input[name="extra_ingredient"]:checked').forEach(cb => {
            unitPrice += parseFloat(cb.getAttribute('data-price') || 0);
        });

        const total = unitPrice * currentQty;
        totalDisplay.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
        return { unitPrice, total };
    }

    // Adicionar à sacola
    btnAddToCart.addEventListener('click', function() {
        if (!currentProduct) return;

        const calc = calculateModalTotal();
        const isPizza = (currentProduct.categoria_slug === 'pizzas');

        let sizeName = isPizza ? (document.querySelector('input[name="selected_size"]:checked')?.value || 'Grande') : 'Padrão';
        let secondFlavor = isPizza ? (document.getElementById('second-flavor-select')?.value || null) : null;
        let adicionaisList = [];

        if (isPizza) {
            const crust = document.querySelector('input[name="selected_crust"]:checked');
            if (crust && parseFloat(crust.getAttribute('data-price')) > 0) {
                adicionaisList.push(crust.value);
            }
        }

        document.querySelectorAll('input[name="extra_ingredient"]:checked').forEach(cb => {
            adicionaisList.push(cb.value);
        });

        const itemParaSacola = {
            produto_id: currentProduct.id,
            nome: currentProduct.nome,
            categoria_slug: currentProduct.categoria_slug,
            tamanho_nome: sizeName,
            sabor_secundario_nome: secondFlavor,
            adicionais: adicionaisList,
            observacoes: observations.value.trim(),
            quantidade: currentQty,
            precoUnitario: calc.unitPrice,
            subtotal: calc.total
        };

        window.CartManager.addItem(itemParaSacola);

        modal.classList.add('hidden');
        modal.classList.remove('flex');
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
