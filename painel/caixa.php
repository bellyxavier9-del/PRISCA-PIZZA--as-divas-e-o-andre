<?php
/**
 * Prisca Pizza - Painel do Atendente de Caixa
 * Perfil 2: Conferência de pedidos, status de pagamento, despacho para cozinha e retiradas
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole([ROLE_CAIXA, ROLE_ADMIN]);
$usuario = getLoggedUser();

$pageTitle = 'Painel do Caixa - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">

    <!-- Cabeçalho do Painel com Indicador de Conexão em Tempo Real -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-600 bg-amber-50 px-2.5 py-1 rounded-md">Perfil 2 — Atendimento & Caixa</span>
                <span class="inline-flex items-center gap-1.5 text-xs text-emerald-600 font-semibold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Polling Ativo (6s)
                </span>
            </div>
            <h1 class="text-3xl font-extrabold text-slate-900 font-serif mt-1">Gerenciamento de Pedidos</h1>
            <p class="text-xs text-slate-500 mt-0.5">Confira novos pedidos, verifique pagamentos e encaminhe para a esteira da cozinha.</p>
        </div>

        <div class="flex items-center gap-3">
            <button type="button" id="btn-refresh" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold uppercase tracking-wider shadow-sm transition-all flex items-center gap-2">
                <i class="fa-solid fa-arrows-rotate"></i> Atualizar Agora
            </button>
        </div>
    </div>

    <!-- Barra de Filtros Rápidos -->
    <div class="bg-white p-4 rounded-3xl border border-slate-200/80 shadow-sm mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <!-- Filtro por Status -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none" id="status-filters">
            <button type="button" class="btn-filter-status px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-900 text-white transition-all shrink-0 active" data-status="">
                Todos
            </button>
            <button type="button" class="btn-filter-status px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all shrink-0" data-status="Recebido">
                Novos (Recebido)
            </button>
            <button type="button" class="btn-filter-status px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all shrink-0" data-status="Em preparo">
                Na Cozinha
            </button>
            <button type="button" class="btn-filter-status px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all shrink-0" data-status="Pronto">
                Prontos
            </button>
            <button type="button" class="btn-filter-status px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all shrink-0" data-status="Saiu para entrega">
                Em Trânsito
            </button>
            <button type="button" class="btn-filter-status px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all shrink-0" data-status="Entregue">
                Entregues
            </button>
        </div>

        <!-- Filtro por Modalidade (Entrega vs Retirada) -->
        <div class="flex items-center gap-2 shrink-0">
            <span class="text-xs font-semibold text-slate-500">Modalidade:</span>
            <select id="filter-type" class="px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-slate-50 font-semibold focus:outline-none focus:ring-2 focus:ring-rose-500">
                <option value="">Todas</option>
                <option value="entrega">Entrega em Domicílio</option>
                <option value="retirada">Retirada no Balcão</option>
            </select>
        </div>

    </div>

    <!-- Lista de Pedidos em Cards Dinâmicos -->
    <div id="orders-container" class="space-y-4">
        <div class="text-center py-12 text-slate-400">
            <i class="fa-solid fa-spinner fa-spin text-2xl mb-2"></i>
            <p class="text-xs">Carregando pedidos em tempo real...</p>
        </div>
    </div>

    <!-- Estado Vazio -->
    <div id="orders-empty" class="hidden text-center py-16 bg-white rounded-3xl border border-slate-200/80 p-8">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mx-auto mb-3">
            <i class="fa-solid fa-inbox"></i>
        </div>
        <h4 class="text-sm font-bold text-slate-700">Nenhum pedido encontrado</h4>
        <p class="text-xs text-slate-400 mt-1">Nenhum pedido corresponde ao filtro selecionado no momento.</p>
    </div>

</main>

<!-- Ticket de Impressão Térmica Oculto (CSS @media print) -->
<div id="printable-order-ticket" class="hidden"></div>

<script src="<?= BASE_URL ?>/assets/js/panels.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ordersContainer = document.getElementById('orders-container');
    const ordersEmpty = document.getElementById('orders-empty');
    const statusButtons = document.querySelectorAll('.btn-filter-status');
    const typeFilter = document.getElementById('filter-type');
    const btnRefresh = document.getElementById('btn-refresh');

    let filterStatus = '';
    let filterType = '';

    statusButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            statusButtons.forEach(b => {
                b.classList.remove('bg-slate-900', 'text-white');
                b.classList.add('bg-slate-100', 'text-slate-700');
            });
            this.classList.remove('bg-slate-100', 'text-slate-700');
            this.classList.add('bg-slate-900', 'text-white');

            filterStatus = this.getAttribute('data-status');
            window.PanelsManager.fetchOrders();
        });
    });

    typeFilter.addEventListener('change', function() {
        filterType = this.value;
        window.PanelsManager.fetchOrders();
    });

    btnRefresh.addEventListener('click', function() {
        window.PanelsManager.fetchOrders();
        showToast('info', 'Atualizado', 'Buscando novos pedidos...');
    });

    // Renderização dos Pedidos
    window.renderOrders = function(pedidos) {
        if (!pedidos) return;

        // Aplica filtros no cliente para fluidez imediata
        const filtered = pedidos.filter(p => {
            const matchStatus = (!filterStatus || p.status === filterStatus);
            const matchType = (!filterType || p.tipo_entrega === filterType);
            return matchStatus && matchType;
        });

        if (filtered.length === 0) {
            ordersContainer.innerHTML = '';
            ordersEmpty.classList.remove('hidden');
            return;
        }

        ordersEmpty.classList.add('hidden');
        ordersContainer.innerHTML = '';

        filtered.forEach(p => {
            const card = document.createElement('div');
            const isNew = (p.status === 'Recebido');
            card.className = `bg-white rounded-3xl p-6 border shadow-sm transition-all duration-300 ${isNew ? 'border-amber-400 bg-amber-50/20 order-pulse-new' : 'border-slate-200/80 hover:shadow-md'}`;

            // Badge de Pagamento
            let paymentBadge = '';
            if (p.status_pagamento === 'pago') {
                paymentBadge = `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase bg-emerald-100 text-emerald-700">
                    <i class="fa-solid fa-check"></i> ${p.forma_pagamento.toUpperCase()} Pago
                </span>`;
            } else if (p.status_pagamento === 'a_receber') {
                const trocoTxt = p.troco_para ? `(Troco p/ R$ ${parseFloat(p.troco_para).toFixed(2)})` : '(Sem troco)';
                paymentBadge = `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase bg-amber-100 text-amber-800">
                    <i class="fa-solid fa-money-bill-wave"></i> Dinheiro a Receber ${trocoTxt}
                </span>`;
            } else {
                paymentBadge = `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase bg-rose-100 text-rose-700">
                    <i class="fa-solid fa-clock"></i> Pagamento Pendente
                </span>`;
            }

            // Itens do Pedido
            let itemsHtml = '';
            if (p.itens && p.itens.length > 0) {
                p.itens.forEach(it => {
                    let extraDesc = [];
                    if (it.tamanho_nome) extraDesc.push(it.tamanho_nome);
                    if (it.sabor_secundario_nome) extraDesc.push(`1/2 ${it.sabor_secundario_nome}`);
                    if (it.adicionais_selecionados) extraDesc.push(`+ ${it.adicionais_selecionados}`);

                    itemsHtml += `
                        <div class="py-1.5 flex justify-between text-xs text-slate-700">
                            <div>
                                <span class="font-bold text-slate-900">${it.quantidade}x ${it.nome_produto}</span>
                                ${extraDesc.length > 0 ? `<span class="block text-[11px] text-slate-500">${extraDesc.join(' • ')}</span>` : ''}
                                ${it.observacoes ? `<span class="block text-[10px] text-amber-600 italic">Obs: ${it.observacoes}</span>` : ''}
                            </div>
                        </div>
                    `;
                });
            }

            // Botões de Ação por Estado
            let actionsHtml = '';

            // Se "Recebido": pode despachar para a cozinha
            if (p.status === 'Recebido') {
                actionsHtml += `
                    <button type="button" class="btn-forward-kitchen px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs uppercase tracking-wider transition-all shadow-md shadow-rose-600/20 flex items-center gap-1.5" data-id="${p.id}" data-code="${p.codigo}">
                        <i class="fa-solid fa-kitchen-set"></i> Despachar para Cozinha
                    </button>
                `;
            }

            // Se for para "Retirada" e estiver "Pronto": o Caixa pode concluir
            if (p.tipo_entrega === 'retirada' && p.status === 'Pronto') {
                actionsHtml += `
                    <button type="button" class="btn-conclude-pickup px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider transition-all shadow-md shadow-emerald-600/20 flex items-center gap-1.5" data-id="${p.id}" data-code="${p.codigo}">
                        <i class="fa-solid fa-check-double"></i> Concluir Retirada no Balcão
                    </button>
                `;
            }

            // Botão Cancelar (apenas se não estiver entregue ou cancelado)
            if (p.status !== 'Entregue' && p.status !== 'Cancelado') {
                actionsHtml += `
                    <button type="button" class="btn-cancel-order px-3 py-2.5 rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold text-xs uppercase tracking-wider transition-colors" data-id="${p.id}" data-code="${p.codigo}">
                        Cancelar
                    </button>
                `;
            }

            // Botão Imprimir Ticket
            actionsHtml += `
                <button type="button" class="btn-print-ticket p-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors" title="Imprimir Comanda Térmica" data-id="${p.id}">
                    <i class="fa-solid fa-print"></i>
                </button>
            `;

            card.innerHTML = `
                <div class="flex flex-col lg:flex-row lg:items-center justify-between pb-4 border-b border-slate-100 gap-3">
                    <div class="flex items-center gap-3">
                        <span class="text-lg font-black text-slate-900 font-brand">${p.codigo}</span>
                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider ${p.tipo_entrega === 'retirada' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'}">
                            <i class="fa-solid ${p.tipo_entrega === 'retirada' ? 'fa-store' : 'fa-motorcycle'} mr-1"></i>
                            ${p.tipo_entrega === 'retirada' ? 'Retirada no Balcão' : 'Entrega em Casa'}
                        </span>
                        ${paymentBadge}
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-xs font-semibold text-slate-500">${new Date(p.criado_em).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                        <span class="px-3 py-1 rounded-full text-xs font-bold ${getStatusColorClass(p.status)}">
                            ${p.status}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 py-4">
                    <!-- Detalhes do Cliente e Endereço -->
                    <div class="md:col-span-5 text-xs text-slate-600 space-y-1.5 border-b md:border-b-0 md:border-r border-slate-100 pb-4 md:pb-0 pr-4">
                        <p><strong>Cliente:</strong> ${p.cliente_nome}</p>
                        <p><strong>Telefone:</strong> ${p.telefone_contato || p.cliente_telefone || 'Não informado'}</p>
                        <p><strong>Endereço:</strong> ${p.endereco_entrega}</p>
                        ${p.entregador_nome ? `<p class="text-blue-600 font-semibold"><strong>Entregador:</strong> ${p.entregador_nome}</p>` : ''}
                        ${p.observacoes ? `<p class="text-amber-700 bg-amber-50 p-2 rounded-xl mt-2"><strong>Obs Geral:</strong> ${p.observacoes}</p>` : ''}
                    </div>

                    <!-- Itens -->
                    <div class="md:col-span-7 space-y-2">
                        <div class="divide-y divide-slate-100">${itemsHtml}</div>
                        <div class="pt-2 border-t border-slate-100 flex justify-between items-baseline font-bold text-xs">
                            <span class="text-slate-500">Total do Pedido:</span>
                            <span class="text-base text-slate-900 font-extrabold">R$ ${parseFloat(p.total).toFixed(2).replace('.', ',')}</span>
                        </div>
                    </div>
                </div>

                <!-- Barra de Ações -->
                <div class="pt-4 border-t border-slate-100 flex flex-wrap items-center justify-end gap-2">
                    ${actionsHtml}
                </div>
            `;

            ordersContainer.appendChild(card);
        });

        attachActionEvents(filtered);
    };

    function getStatusColorClass(status) {
        switch (status) {
            case 'Recebido': return 'bg-amber-100 text-amber-800';
            case 'Em preparo': return 'bg-blue-100 text-blue-800';
            case 'Pronto': return 'bg-purple-100 text-purple-800';
            case 'Saiu para entrega': return 'bg-indigo-100 text-indigo-800';
            case 'Entregue': return 'bg-emerald-100 text-emerald-800';
            case 'Cancelado': return 'bg-rose-100 text-rose-800';
            default: return 'bg-slate-100 text-slate-700';
        }
    }

    function attachActionEvents(pedidos) {
        // Encaminhar para cozinha
        document.querySelectorAll('.btn-forward-kitchen').forEach(btn => {
            btn.addEventListener('click', async function() {
                const id = this.getAttribute('data-id');
                const code = this.getAttribute('data-code');

                const confirmed = await window.appConfirm(`Confirma o encaminhamento do pedido ${code} para preparo na cozinha?`, 'Encaminhar para a Cozinha');
                if (confirmed) {
                    window.PanelsManager.updateOrderStatus(id, 'Em preparo');
                }
            });
        });

        // Concluir retirada no balcão
        document.querySelectorAll('.btn-conclude-pickup').forEach(btn => {
            btn.addEventListener('click', async function() {
                const id = this.getAttribute('data-id');
                const code = this.getAttribute('data-code');

                const confirmed = await window.appConfirm(`O cliente retirou o pedido ${code} no balcão?`, 'Concluir Retirada');
                if (confirmed) {
                    window.PanelsManager.updateOrderStatus(id, 'Entregue');
                }
            });
        });

        // Cancelar pedido
        document.querySelectorAll('.btn-cancel-order').forEach(btn => {
            btn.addEventListener('click', async function() {
                const id = this.getAttribute('data-id');
                const code = this.getAttribute('data-code');

                const modalResult = await window.showModal({
                    title: `Cancelar Pedido ${code}`,
                    message: 'Tem certeza que deseja cancelar este pedido? Esta ação será registrada nos logs de auditoria.',
                    type: 'danger',
                    confirmText: 'Confirmar Cancelamento',
                    showInput: true,
                    inputLabel: 'Informe o motivo do cancelamento:',
                    inputPlaceholder: 'Ex: Cliente solicitou cancelamento, endereço fora da área...'
                });

                if (modalResult && modalResult.confirmed) {
                    window.PanelsManager.updateOrderStatus(id, 'Cancelado', modalResult.value);
                }
            });
        });

        // Imprimir comanda
        document.querySelectorAll('.btn-print-ticket').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = parseInt(this.getAttribute('data-id'));
                const p = pedidos.find(item => item.id == id);
                if (p) printThermalTicket(p);
            });
        });
    }

    function printThermalTicket(p) {
        const ticket = document.getElementById('printable-order-ticket');
        if (!ticket) return;

        let itemsText = '';
        if (p.itens) {
            p.itens.forEach(i => {
                itemsText += `<div style="margin-bottom: 4px;"><strong>${i.quantidade}x ${i.nome_produto}</strong> (${i.tamanho_nome || ''})<br>${i.sabor_secundario_nome ? 'Metade: ' + i.sabor_secundario_nome + '<br>' : ''}${i.adicionais_selecionados ? '+ ' + i.adicionais_selecionados + '<br>' : ''}${i.observacoes ? 'Obs: ' + i.observacoes : ''}</div>`;
            });
        }

        ticket.innerHTML = `
            <div style="text-align: center; border-bottom: 1px dashed #000; padding-bottom: 8px; margin-bottom: 8px;">
                <h2 style="margin: 0; font-size: 16px;">PRISCA PIZZA ARTESANAL</h2>
                <p style="margin: 2px 0; font-size: 11px;">Rua das Pizzas, 500 - Tel: (11) 3456-7890</p>
                <h3 style="margin: 6px 0 0 0; font-size: 18px;">${p.codigo}</h3>
                <p style="margin: 0; font-size: 11px;">${new Date(p.criado_em).toLocaleString()}</p>
            </div>
            <div style="border-bottom: 1px dashed #000; padding-bottom: 6px; margin-bottom: 6px; font-size: 12px;">
                <strong>CLIENTE:</strong> ${p.cliente_nome}<br>
                <strong>FONE:</strong> ${p.telefone_contato || p.cliente_telefone || ''}<br>
                <strong>TIPO:</strong> ${p.tipo_entrega.toUpperCase()}<br>
                <strong>ENDEREÇO:</strong> ${p.endereco_entrega}
            </div>
            <div style="border-bottom: 1px dashed #000; padding-bottom: 6px; margin-bottom: 6px; font-size: 12px;">
                <div style="font-weight: bold; margin-bottom: 4px;">ITENS:</div>
                ${itemsText}
            </div>
            <div style="font-size: 13px; text-align: right;">
                <strong>TOTAL: R$ ${parseFloat(p.total).toFixed(2).replace('.', ',')}</strong><br>
                <span style="font-size: 11px;">PAGAMENTO: ${p.forma_pagamento.toUpperCase()} (${p.status_pagamento.toUpperCase()})</span>
            </div>
        `;

        ticket.classList.remove('hidden');
        window.print();
        ticket.classList.add('hidden');
    }

    // Inicializa o gerenciador com polling a cada 6s
    window.PanelsManager.init('caixa');
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
