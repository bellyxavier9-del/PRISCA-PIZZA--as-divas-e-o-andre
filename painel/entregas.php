<?php
/**
 * Prisca Pizza - Painel do Entregador
 * Perfil 4: Visualiza pedidos prontos, assume rota ("Saiu para entrega") e confirma entrega.
 * Proteção de privacidade: dados do cliente exibidos estritamente após assumir o pedido.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole([ROLE_ENTREGADOR, ROLE_ADMIN]);
$usuario = getLoggedUser();

$pageTitle = 'Painel do Entregador - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">

    <!-- Cabeçalho do Painel de Entregas -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-2.5 py-1 rounded-md">Perfil 4 — Entregador / Frota</span>
                <span class="inline-flex items-center gap-1.5 text-xs text-emerald-600 font-semibold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Polling Ativo (6s)
                </span>
            </div>
            <h1 class="text-3xl font-extrabold text-slate-900 font-serif mt-1">Central de Entregas</h1>
            <p class="text-xs text-slate-500 mt-0.5">Pegue pedidos prontos no balcão e confirme a entrega ao cliente no destino.</p>
        </div>

        <div class="flex items-center gap-3">
            <button type="button" id="btn-refresh-deliveries" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold uppercase tracking-wider shadow-sm transition-all flex items-center gap-2">
                <i class="fa-solid fa-arrows-rotate"></i> Atualizar
            </button>
        </div>
    </div>

    <!-- Seção 1: Minhas Entregas em Trânsito (Saiu para entrega) -->
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-motorcycle text-blue-600"></i> Minhas Entregas em Trânsito
            </h3>
            <span id="active-deliveries-count" class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                0 em andamento
            </span>
        </div>

        <div id="active-deliveries-list" class="space-y-4">
            <div class="text-center py-8 text-slate-400 bg-white rounded-3xl border border-slate-200/80 p-6">
                <i class="fa-solid fa-spinner fa-spin text-xl mb-2"></i>
                <p class="text-xs">Verificando suas rotas...</p>
            </div>
        </div>
    </div>

    <!-- Seção 2: Pedidos Prontos Aguardando Entregador -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-box-open text-purple-600"></i> Pedidos Prontos para Coleta no Balcão
            </h3>
            <span id="ready-deliveries-count" class="px-2.5 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-800">
                0 disponíveis
            </span>
        </div>

        <div id="ready-deliveries-list" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="col-span-full text-center py-8 text-slate-400 bg-white rounded-3xl border border-slate-200/80 p-6">
                <i class="fa-solid fa-spinner fa-spin text-xl mb-2"></i>
                <p class="text-xs">Buscando pizzas prontas na esteira...</p>
            </div>
        </div>
    </div>

</main>

<script src="<?= BASE_URL ?>/assets/js/panels.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const activeList = document.getElementById('active-deliveries-list');
    const readyList = document.getElementById('ready-deliveries-list');
    const activeCountBadge = document.getElementById('active-deliveries-count');
    const readyCountBadge = document.getElementById('ready-deliveries-count');
    const btnRefresh = document.getElementById('btn-refresh-deliveries');

    btnRefresh.addEventListener('click', () => {
        window.PanelsManager.fetchOrders();
        showToast('info', 'Atualizado', 'Buscando entregas...');
    });

    window.renderOrders = function(pedidos) {
        if (!pedidos) return;

        const currentUserId = <?= (int)$usuario['id'] ?>;

        // Entregas ativas deste entregador
        const activeOrders = pedidos.filter(p => p.status === 'Saiu para entrega' && p.entregador_id == currentUserId);
        // Pedidos prontos para serem coletados (ninguém pegou ainda)
        const readyOrders = pedidos.filter(p => p.status === 'Pronto');

        activeCountBadge.textContent = `${activeOrders.length} em andamento`;
        readyCountBadge.textContent = `${readyOrders.length} disponível(is)`;

        // 1. Renderiza entregas ativas
        if (activeOrders.length === 0) {
            activeList.innerHTML = `
                <div class="text-center py-8 bg-white rounded-3xl border border-slate-200/80 p-6 text-slate-500 text-xs">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-2xl mb-2"></i>
                    <p class="font-bold">Nenhuma entrega em trânsito no momento.</p>
                    <p class="text-slate-400 mt-0.5">Pegue um pedido pronto abaixo para iniciar sua rota.</p>
                </div>
            `;
        } else {
            activeList.innerHTML = '';
            activeOrders.forEach(p => {
                const card = document.createElement('div');
                card.className = 'bg-white rounded-3xl p-6 border-2 border-blue-400 shadow-md space-y-4';

                // Valor a cobrar
                let cobrancaHtml = '';
                if (p.forma_pagamento === 'dinheiro') {
                    const trocoTxt = p.troco_para ? `(Levar troco para R$ ${parseFloat(p.troco_para).toFixed(2)})` : '';
                    cobrancaHtml = `
                        <div class="p-3 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-bold flex items-center justify-between">
                            <span class="flex items-center gap-1.5"><i class="fa-solid fa-hand-holding-dollar text-amber-600"></i> RECEBER EM DINHEIRO:</span>
                            <span class="text-sm font-black">R$ ${parseFloat(p.total).toFixed(2).replace('.', ',')} ${trocoTxt}</span>
                        </div>
                    `;
                } else {
                    cobrancaHtml = `
                        <div class="p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-bold flex items-center justify-between">
                            <span class="flex items-center gap-1.5"><i class="fa-solid fa-check text-emerald-600"></i> JÁ PAGO ONLINE (${p.forma_pagamento.toUpperCase()})</span>
                            <span class="text-xs font-semibold text-emerald-700">Não cobrar nada do cliente</span>
                        </div>
                    `;
                }

                const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(p.endereco_entrega)}`;
                const phoneDigits = (p.telefone_contato || '').replace(/\D/g, '');
                const zapUrl = phoneDigits ? `https://wa.me/55${phoneDigits}` : '#';

                card.innerHTML = `
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-slate-100 gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-lg font-black font-brand text-slate-900">${p.codigo}</span>
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-blue-100 text-blue-800 animate-pulse">
                                Em Rota
                            </span>
                        </div>
                        <span class="text-xs text-slate-400">Total do Pedido: <strong class="text-slate-800">R$ ${parseFloat(p.total).toFixed(2).replace('.', ',')}</strong></span>
                    </div>

                    <!-- Dados Estritamente Exibidos para o Entregador Designado (Regra CORE de Privacidade) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-700">
                        <div class="space-y-1">
                            <p class="text-slate-400 font-bold uppercase text-[10px]">Cliente & Contato:</p>
                            <p class="font-extrabold text-sm text-slate-900">${p.cliente_nome}</p>
                            <p class="flex items-center gap-2 mt-1">
                                <i class="fa-solid fa-phone text-rose-500"></i> ${p.telefone_contato || 'Telefone não cadastrado'}
                                ${phoneDigits ? `<a href="${zapUrl}" target="_blank" class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-bold text-[10px] hover:bg-emerald-200 transition-colors"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>` : ''}
                            </p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-slate-400 font-bold uppercase text-[10px]">Endereço Completo de Destino:</p>
                            <p class="font-bold text-slate-800 leading-relaxed">${p.endereco_entrega}</p>
                            <a href="${mapsUrl}" target="_blank" class="inline-flex items-center gap-1.5 text-blue-600 hover:underline font-bold text-[11px] mt-1">
                                <i class="fa-solid fa-map-location-dot"></i> Abrir no Google Maps / GPS
                            </a>
                        </div>
                    </div>

                    ${cobrancaHtml}

                    <!-- Ação de Conclusão -->
                    <div class="pt-2 flex justify-end">
                        <button type="button" class="btn-complete-delivery w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-emerald-600/30 transition-all flex items-center justify-center gap-2" data-id="${p.id}" data-code="${p.codigo}">
                            <i class="fa-solid fa-house-chimney-check"></i> Confirmar Entrega Concluída
                        </button>
                    </div>
                `;

                activeList.appendChild(card);
            });
        }

        // 2. Renderiza pedidos prontos para pegar
        if (readyOrders.length === 0) {
            readyList.innerHTML = `
                <div class="col-span-full text-center py-8 bg-white rounded-3xl border border-slate-200/80 p-6 text-slate-400 text-xs">
                    <i class="fa-solid fa-check-circle text-xl mb-1"></i>
                    <p>Nenhum pedido pronto para entrega no balcão no momento.</p>
                </div>
            `;
        } else {
            readyList.innerHTML = '';
            readyOrders.forEach(p => {
                const card = document.createElement('div');
                card.className = 'bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow';

                const cobrancaTxt = (p.forma_pagamento === 'dinheiro') 
                    ? `Receber Dinheiro: R$ ${parseFloat(p.total).toFixed(2).replace('.', ',')}` 
                    : `Já Pago Online (${p.forma_pagamento.toUpperCase()})`;

                card.innerHTML = `
                    <div>
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
                            <span class="text-base font-black font-brand text-slate-900">${p.codigo}</span>
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-purple-100 text-purple-800">
                                Pronto no Balcão
                            </span>
                        </div>

                        <div class="text-xs text-slate-600 space-y-1.5">
                            <p><strong>Destino:</strong> ${p.endereco_entrega}</p>
                            <p class="text-slate-500"><strong>Pagamento:</strong> ${cobrancaTxt}</p>
                            ${p.observacoes ? `<p class="text-[11px] text-amber-700 italic">Obs: ${p.observacoes}</p>` : ''}
                        </div>
                    </div>

                    <div class="mt-5 pt-3 border-t border-slate-100">
                        <button type="button" class="btn-claim-order w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs uppercase tracking-wider shadow-md shadow-blue-600/20 transition-all flex items-center justify-center gap-2" data-id="${p.id}" data-code="${p.codigo}">
                            <i class="fa-solid fa-person-biking"></i> Pegar este Pedido & Iniciar Rota
                        </button>
                    </div>
                `;

                readyList.appendChild(card);
            });
        }

        attachDeliveryEvents();
    };

    function attachDeliveryEvents() {
        // Pegar pedido (Pronto -> Saiu para entrega)
        document.querySelectorAll('.btn-claim-order').forEach(btn => {
            btn.addEventListener('click', async function() {
                const id = this.getAttribute('data-id');
                const code = this.getAttribute('data-code');

                const confirmed = await window.appConfirm(`Você vai levar o pedido ${code} para entrega agora?`, 'Assumir Pedido');
                if (confirmed) {
                    window.PanelsManager.updateOrderStatus(id, 'Saiu para entrega');
                }
            });
        });

        // Concluir entrega (Saiu para entrega -> Entregue)
        document.querySelectorAll('.btn-complete-delivery').forEach(btn => {
            btn.addEventListener('click', async function() {
                const id = this.getAttribute('data-id');
                const code = this.getAttribute('data-code');

                const confirmed = await window.appConfirm(`Confirma que o pedido ${code} foi entregue com sucesso ao cliente?`, 'Concluir Entrega');
                if (confirmed) {
                    window.PanelsManager.updateOrderStatus(id, 'Entregue');
                }
            });
        });
    }

    // Inicializa painel com polling a cada 6s
    window.PanelsManager.init('entregador');
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
