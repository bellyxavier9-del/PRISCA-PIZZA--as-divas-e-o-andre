<?php
/**
 * Prisca Pizza - Carrinho de Compras e Checkout
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/bd.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$usuario = getLoggedUser();

$pageTitle = 'Minha Sacola & Finalizar Pedido - ' . APP_NAME;
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">

    <div class="mb-8">
        <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Checkout</span>
        <h1 class="text-3xl font-extrabold text-slate-900 font-serif mt-1">Minha Sacola de Pedidos</h1>
        <p class="text-xs text-slate-500 mt-1">Revise seus itens, escolha o tipo de entrega e a forma de pagamento simulada.</p>
    </div>

    <!-- Carrinho Vazio State -->
    <div id="cart-empty-state" class="hidden text-center py-16 bg-white rounded-3xl border border-slate-200/80 p-8 shadow-sm">
        <div class="w-20 h-20 rounded-3xl bg-rose-50 text-rose-600 text-3xl flex items-center justify-center mx-auto mb-4 border border-rose-100">
            <i class="fa-solid fa-bag-shopping"></i>
        </div>
        <h3 class="text-lg font-bold text-slate-900">Sua sacola está vazia</h3>
        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Que tal dar uma olhada em nossas deliciosas pizzas artesanais e esfihas feitas no capricho?</p>
        <a href="<?= BASE_URL ?>/cardapio.php" class="mt-6 inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-rose-600/30 transition-all">
            <i class="fa-solid fa-pizza-slice"></i> Ver Cardápio Agora
        </a>
    </div>

    <!-- Carrinho Preenchido -->
    <div id="cart-content" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Coluna Esquerda: Itens da Sacola -->
        <div class="lg:col-span-7 space-y-4">
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-list-check text-rose-600"></i> Itens Selecionados
                    </h3>
                    <button type="button" id="btn-clear-cart" class="text-xs text-slate-400 hover:text-rose-600 transition-colors">
                        Limpar sacola
                    </button>
                </div>

                <!-- Lista de Itens Renderizados via JS -->
                <div id="cart-items-list" class="space-y-4 divide-y divide-slate-100"></div>
            </div>

            <!-- Modalidade de Entrega: Entrega vs Retirada -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 space-y-4">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-truck-fast text-rose-600"></i> Como deseja receber?
                </h3>

                <div class="grid grid-cols-2 gap-3">
                    <label class="delivery-type-label block p-4 rounded-2xl border-2 border-rose-600 bg-rose-50/40 cursor-pointer transition-all text-center">
                        <input type="radio" name="tipo_entrega" value="entrega" checked class="hidden">
                        <i class="fa-solid fa-motorcycle text-xl text-rose-600 mb-1"></i>
                        <span class="block font-bold text-xs text-slate-900">Entrega em Casa</span>
                        <span class="block text-[10px] text-slate-500 mt-0.5">Taxa fixa <?= formatMoney(DEFAULT_DELIVERY_FEE) ?> (<?= ESTIMATED_DELIVERY_TIME ?>)</span>
                    </label>

                    <label class="delivery-type-label block p-4 rounded-2xl border-2 border-slate-200 cursor-pointer hover:border-slate-300 transition-all text-center">
                        <input type="radio" name="tipo_entrega" value="retirada" class="hidden">
                        <i class="fa-solid fa-store text-xl text-slate-600 mb-1"></i>
                        <span class="block font-bold text-xs text-slate-900">Retirada no Balcão</span>
                        <span class="block text-[10px] text-emerald-600 font-semibold mt-0.5">Sem taxa • Pronto em <?= ESTIMATED_PICKUP_TIME ?></span>
                    </label>
                </div>

                <!-- Campo de Endereço de Entrega (se entrega selecionada) -->
                <div id="address-container" class="space-y-1.5 pt-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Endereço de Entrega <span class="text-rose-600">*</span>
                    </label>
                    <textarea id="order-address" rows="2" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none" placeholder="Rua, número, complemento, bairro, ponto de referência..."><?= e($usuario['endereco'] ?? '') ?></textarea>
                </div>

                <!-- Campo de Endereço da Loja (se retirada selecionada) -->
                <div id="pickup-info" class="hidden p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600 space-y-1">
                    <p class="font-bold text-slate-800"><i class="fa-solid fa-location-dot text-rose-600 mr-1"></i> Local para retirada:</p>
                    <p><?= APP_ADDRESS ?></p>
                    <p class="text-[11px] text-slate-500">Apresente o número do seu pedido no balcão ao chegar.</p>
                </div>

                <!-- Telefone para Contato -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Telefone / WhatsApp para contato <span class="text-rose-600">*</span>
                    </label>
                    <input type="text" id="order-phone" value="<?= e($usuario['telefone'] ?? '') ?>" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none" placeholder="(11) 99999-9999">
                </div>

                <!-- Observações Gerais do Pedido -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Observações Gerais do Pedido (Opcional)
                    </label>
                    <input type="text" id="order-notes" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none" placeholder="Ex: Tocar o interfone 42, deixar na portaria...">
                </div>

            </div>
        </div>

        <!-- Coluna Direita: Pagamento e Resumo -->
        <div class="lg:col-span-5 space-y-4">
            
            <!-- Formas de Pagamento Simuladas (Regra de Negócio) -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 space-y-4">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-credit-card text-rose-600"></i> Forma de Pagamento
                </h3>

                <div class="space-y-2">
                    <!-- Opção PIX -->
                    <label class="payment-method-label flex items-start gap-3 p-3.5 rounded-2xl border-2 border-rose-600 bg-rose-50/30 cursor-pointer transition-all">
                        <input type="radio" name="forma_pagamento" value="pix" checked class="mt-1 text-rose-600 focus:ring-rose-500">
                        <div class="flex-1">
                            <span class="block font-bold text-xs text-slate-900 flex items-center gap-1.5">
                                <i class="fa-brands fa-pix text-emerald-600"></i> Pix Instantâneo
                            </span>
                            <span class="block text-[11px] text-slate-500 mt-0.5">Simulação de QR Code e código Copia e Cola.</span>
                        </div>
                    </label>

                    <!-- Opção Cartão -->
                    <label class="payment-method-label flex items-start gap-3 p-3.5 rounded-2xl border-2 border-slate-200 cursor-pointer hover:border-slate-300 transition-all">
                        <input type="radio" name="forma_pagamento" value="cartao" class="mt-1 text-rose-600 focus:ring-rose-500">
                        <div class="flex-1">
                            <span class="block font-bold text-xs text-slate-900 flex items-center gap-1.5">
                                <i class="fa-solid fa-credit-card text-sky-600"></i> Cartão de Crédito / Débito
                            </span>
                            <span class="block text-[11px] text-slate-500 mt-0.5">Simulação de aprovação imediata sem guardar dados bancários.</span>
                        </div>
                    </label>

                    <!-- Opção Dinheiro -->
                    <label class="payment-method-label flex items-start gap-3 p-3.5 rounded-2xl border-2 border-slate-200 cursor-pointer hover:border-slate-300 transition-all">
                        <input type="radio" name="forma_pagamento" value="dinheiro" class="mt-1 text-rose-600 focus:ring-rose-500">
                        <div class="flex-1">
                            <span class="block font-bold text-xs text-slate-900 flex items-center gap-1.5">
                                <i class="fa-solid fa-money-bill-wave text-amber-600"></i> Dinheiro
                            </span>
                            <span class="block text-[11px] text-slate-500 mt-0.5">Pagamento realizado na entrega ou no balcão.</span>
                        </div>
                    </label>
                </div>

                <!-- Campo de Troco (se dinheiro selecionado) -->
                <div id="troco-container" class="hidden p-3.5 rounded-2xl bg-amber-50/60 border border-amber-200/60 space-y-1.5">
                    <label class="block text-xs font-bold text-amber-900">Precisa de troco para quanto?</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">R$</span>
                        <input type="number" step="0.50" min="0" id="troco-input" placeholder="Ex: 50,00 ou 100,00 (Deixe em branco se não precisar)" class="w-full pl-9 pr-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>

            </div>

            <!-- Resumo Financeiro do Pedido -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 space-y-4">
                <h3 class="text-base font-bold text-slate-900">Resumo dos Valores</h3>

                <div class="space-y-2 text-xs text-slate-600 pb-3 border-b border-slate-100">
                    <div class="flex justify-between">
                        <span>Subtotal dos Itens:</span>
                        <span id="summary-subtotal" class="font-bold text-slate-800">R$ 0,00</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Taxa de Entrega:</span>
                        <span id="summary-delivery-fee" class="font-bold text-slate-800">R$ 8,00</span>
                    </div>
                    <div class="flex justify-between text-emerald-600 font-medium">
                        <span>Descontos:</span>
                        <span>R$ 0,00</span>
                    </div>
                </div>

                <div class="flex justify-between items-baseline pt-1">
                    <span class="text-sm font-bold text-slate-900">Total a Pagar:</span>
                    <span id="summary-total" class="text-2xl font-extrabold text-rose-600">R$ 0,00</span>
                </div>

                <!-- Botão de Finalização -->
                <?php if (isLoggedIn()): ?>
                    <button type="button" id="btn-submit-order" class="w-full py-4 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs uppercase tracking-wider shadow-xl shadow-rose-600/30 active:scale-98 transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-lock text-xs"></i> Finalizar Pedido Agora
                    </button>
                <?php else: ?>
                    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-center space-y-2">
                        <p class="text-xs font-bold text-rose-800">Faça login para concluir o pedido</p>
                        <p class="text-[11px] text-rose-600">Você precisa estar conectado para que possamos salvar seu histórico e endereço.</p>
                        <div class="grid grid-cols-2 gap-2 pt-2">
                            <a href="<?= BASE_URL ?>/login.php?redirect=carrinho.php" class="py-2 rounded-xl bg-slate-900 text-white text-xs font-bold uppercase tracking-wider">Entrar</a>
                            <a href="<?= BASE_URL ?>/cadastro.php" class="py-2 rounded-xl bg-rose-600 text-white text-xs font-bold uppercase tracking-wider">Cadastrar</a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="text-[11px] text-slate-400 text-center flex items-center justify-center gap-2 pt-2">
                    <i class="fa-solid fa-shield-halved text-emerald-500"></i>
                    <span>Ambiente protegido com sessão criptografada.</span>
                </div>
            </div>

        </div>

    </div>

</main>

<!-- Script de Interatividade do Carrinho e Finalização -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cartEmpty = document.getElementById('cart-empty-state');
    const cartContent = document.getElementById('cart-content');
    const itemsList = document.getElementById('cart-items-list');
    const summarySubtotal = document.getElementById('summary-subtotal');
    const summaryDeliveryFee = document.getElementById('summary-delivery-fee');
    const summaryTotal = document.getElementById('summary-total');
    const btnSubmit = document.getElementById('btn-submit-order');
    const btnClear = document.getElementById('btn-clear-cart');

    const addressContainer = document.getElementById('address-container');
    const pickupInfo = document.getElementById('pickup-info');
    const trocoContainer = document.getElementById('troco-container');

    window.renderCartPage = function() {
        const cart = window.CartManager.getCart();

        if (!cart || cart.length === 0) {
            cartEmpty.classList.remove('hidden');
            cartContent.classList.add('hidden');
            return;
        }

        cartEmpty.classList.add('hidden');
        cartContent.classList.remove('hidden');

        itemsList.innerHTML = '';

        cart.forEach((item, index) => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'pt-4 first:pt-0 flex flex-col sm:flex-row sm:items-center justify-between gap-3';

            let detailsHtml = '';
            if (item.tamanho_nome && item.categoria_slug === 'pizzas') {
                detailsHtml += `<span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 mr-1.5">${item.tamanho_nome}</span>`;
            }
            if (item.sabor_secundario_nome) {
                detailsHtml += `<span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 mr-1.5">1/2 ${item.sabor_secundario_nome}</span>`;
            }
            if (item.adicionais && item.adicionais.length > 0) {
                detailsHtml += `<span class="block text-[11px] text-slate-500 mt-1"><i class="fa-solid fa-plus text-[9px] text-rose-500"></i> ${item.adicionais.join(', ')}</span>`;
            }
            if (item.observacoes) {
                detailsHtml += `<span class="block text-[11px] text-slate-400 italic mt-0.5">Obs: ${item.observacoes}</span>`;
            }

            itemDiv.innerHTML = `
                <div class="flex-1">
                    <h4 class="text-sm font-bold text-slate-900 leading-snug">${item.nome}</h4>
                    <div class="mt-1">${detailsHtml}</div>
                    <span class="block text-xs font-extrabold text-slate-800 mt-1">${window.CartManager.formatCurrency(item.precoUnitario)} cada</span>
                </div>

                <div class="flex items-center justify-between sm:justify-end gap-4 shrink-0">
                    <div class="flex items-center bg-slate-100 rounded-xl overflow-hidden border border-slate-200">
                        <button type="button" class="w-7 h-7 flex items-center justify-center text-slate-600 hover:bg-slate-200 transition-colors font-bold" onclick="window.CartManager.updateQuantity(${index}, -1)">-</button>
                        <span class="w-7 text-center font-bold text-xs">${item.quantidade}</span>
                        <button type="button" class="w-7 h-7 flex items-center justify-center text-slate-600 hover:bg-slate-200 transition-colors font-bold" onclick="window.CartManager.updateQuantity(${index}, 1)">+</button>
                    </div>

                    <div class="text-right min-w-[70px]">
                        <span class="block text-sm font-extrabold text-slate-900">${window.CartManager.formatCurrency(item.subtotal || (item.precoUnitario * item.quantidade))}</span>
                        <button type="button" class="text-[11px] text-rose-600 hover:underline" onclick="window.CartManager.removeItem(${index})">Remover</button>
                    </div>
                </div>
            `;

            itemsList.appendChild(itemDiv);
        });

        updateTotals();
    };

    function updateTotals() {
        const subtotal = window.CartManager.getSubtotal();
        const tipoEntrega = document.querySelector('input[name="tipo_entrega"]:checked')?.value || 'entrega';
        const taxaEntrega = (tipoEntrega === 'entrega') ? 8.00 : 0.00;
        const total = subtotal + taxaEntrega;

        summarySubtotal.textContent = window.CartManager.formatCurrency(subtotal);
        summaryDeliveryFee.textContent = (taxaEntrega > 0) ? window.CartManager.formatCurrency(taxaEntrega) : 'Grátis (Retirada)';
        summaryTotal.textContent = window.CartManager.formatCurrency(total);
    }

    // Toggle Modalidade de Entrega
    document.querySelectorAll('input[name="tipo_entrega"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.delivery-type-label').forEach(label => {
                label.classList.remove('border-rose-600', 'bg-rose-50/40');
                label.classList.add('border-slate-200');
            });
            this.closest('.delivery-type-label').classList.remove('border-slate-200');
            this.closest('.delivery-type-label').classList.add('border-rose-600', 'bg-rose-50/40');

            if (this.value === 'entrega') {
                addressContainer.classList.remove('hidden');
                pickupInfo.classList.add('hidden');
            } else {
                addressContainer.classList.add('hidden');
                pickupInfo.classList.remove('hidden');
            }
            updateTotals();
        });
    });

    // Toggle Forma de Pagamento
    document.querySelectorAll('input[name="forma_pagamento"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.payment-method-label').forEach(label => {
                label.classList.remove('border-rose-600', 'bg-rose-50/30');
                label.classList.add('border-slate-200');
            });
            this.closest('.payment-method-label').classList.remove('border-slate-200');
            this.closest('.payment-method-label').classList.add('border-rose-600', 'bg-rose-50/30');

            trocoContainer.classList.toggle('hidden', this.value !== 'dinheiro');
        });
    });

    // Limpar Carrinho
    btnClear?.addEventListener('click', async function() {
        const confirmed = await window.appConfirm('Deseja realmente esvaziar todos os itens da sua sacola?', 'Esvaziar Sacola', true);
        if (confirmed) {
            window.CartManager.clearCart();
            window.renderCartPage();
            showToast('info', 'Sacola Vazia', 'Todos os itens foram removidos.');
        }
    });

    // Submissão do Pedido
    btnSubmit?.addEventListener('click', async function() {
        const cart = window.CartManager.getCart();
        if (!cart || cart.length === 0) {
            window.appAlert('Sua sacola de compras está vazia.');
            return;
        }

        const tipoEntrega = document.querySelector('input[name="tipo_entrega"]:checked')?.value || 'entrega';
        const endereco = document.getElementById('order-address')?.value.trim();
        const telefone = document.getElementById('order-phone')?.value.trim();
        const formaPagamento = document.querySelector('input[name="forma_pagamento"]:checked')?.value || 'pix';
        const trocoPara = document.getElementById('troco-input')?.value;
        const observacoes = document.getElementById('order-notes')?.value.trim();

        if (tipoEntrega === 'entrega' && !endereco) {
            window.appAlert('Por favor, informe o endereço completo para entrega.', 'Endereço Obrigatório');
            return;
        }

        if (!telefone) {
            window.appAlert('Por favor, informe seu telefone ou WhatsApp para contato.', 'Telefone Obrigatório');
            return;
        }

        const confirmed = await window.appConfirm(
            `Confirma o envio do pedido no valor de ${summaryTotal.textContent} via ${formaPagamento.toUpperCase()}?`,
            'Confirmar Pedido'
        );

        if (!confirmed) return;

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Processando seu pedido...';

        try {
            const response = await fetch(`${window.PRISCA.baseUrl}/api/checkout.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    tipo_entrega: tipoEntrega,
                    endereco_entrega: endereco,
                    telefone_contato: telefone,
                    forma_pagamento: formaPagamento,
                    troco_para: trocoPara,
                    observacoes: observacoes,
                    itens: cart
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                window.CartManager.clearCart();
                window.location.href = result.redirect_url;
            } else {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="fa-solid fa-lock text-xs mr-2"></i> Finalizar Pedido Agora';
                window.appAlert(result.mensagem || 'Erro ao processar pedido.', 'Atenção');
            }
        } catch (err) {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fa-solid fa-lock text-xs mr-2"></i> Finalizar Pedido Agora';
            window.appAlert('Ocorreu um erro de conexão com o servidor. Verifique e tente novamente.', 'Erro');
        }
    });

    window.renderCartPage();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
