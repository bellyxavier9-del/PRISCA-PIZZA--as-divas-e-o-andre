<?php
/**
 * Prisca Pizza - Painel da Cozinha (KDS - Kitchen Display System)
 * Perfil 3: Pizzaiolo - Visão focada em preparação culinária, sem dados bancários ou pessoais irrelevantes
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole([ROLE_PIZZAIOLO, ROLE_ADMIN]);
$usuario = getLoggedUser();

$pageTitle = 'Cozinha (KDS) - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">

    <!-- Cabeçalho do KDS Cozinha -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-rose-700 bg-rose-50 px-2.5 py-1 rounded-md">Perfil 3 — Cozinha & Pizzaiolo</span>
                <span class="inline-flex items-center gap-1.5 text-xs text-emerald-600 font-semibold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> KDS Ativo (6s)
                </span>
            </div>
            <h1 class="text-3xl font-extrabold text-slate-900 font-serif mt-1">Esteira de Produção & Forno</h1>
            <p class="text-xs text-slate-500 mt-0.5">Tickets de preparo com tamanhos, sabores meio a meio, bordas recheadas e observações.</p>
        </div>

        <div class="flex items-center gap-3">
            <span id="kitchen-count-badge" class="px-4 py-2 rounded-xl bg-slate-900 text-white font-extrabold text-xs uppercase tracking-wider">
                0 Pedidos na Fila
            </span>
        </div>
    </div>

    <!-- Grid de Comandas Culinárias -->
    <div id="kitchen-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="col-span-full text-center py-16 text-slate-400">
            <i class="fa-solid fa-spinner fa-spin text-3xl mb-3"></i>
            <p class="text-xs">Sincronizando com a esteira do forno...</p>
        </div>
    </div>

    <!-- Estado de Cozinha Livre -->
    <div id="kitchen-empty" class="hidden text-center py-20 bg-white rounded-3xl border border-slate-200/80 p-8">
        <div class="w-20 h-20 rounded-3xl bg-emerald-50 text-emerald-600 text-3xl flex items-center justify-center mx-auto mb-4 border border-emerald-100">
            <i class="fa-solid fa-fire-burner"></i>
        </div>
        <h3 class="text-lg font-bold text-slate-900">Forno livre e tudo em dia!</h3>
        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Nenhum pedido pendente na cozinha no momento. Novos pedidos enviados pelo caixa aparecerão aqui automaticamente.</p>
    </div>

</main>

<script src="<?= BASE_URL ?>/assets/js/panels.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const kitchenGrid = document.getElementById('kitchen-grid');
    const kitchenEmpty = document.getElementById('kitchen-empty');
    const countBadge = document.getElementById('kitchen-count-badge');

    window.renderOrders = function(pedidos) {
        if (!pedidos) return;

        // Na cozinha, exibe pedidos que estão "Em preparo", "Recebido" ou "Pronto"
        const kitchenOrders = pedidos.filter(p => p.status === 'Recebido' || p.status === 'Em preparo' || p.status === 'Pronto');

        countBadge.textContent = `${kitchenOrders.length} Pedido(s) na Cozinha`;

        if (kitchenOrders.length === 0) {
            kitchenGrid.innerHTML = '';
            kitchenEmpty.classList.remove('hidden');
            return;
        }

        kitchenEmpty.classList.add('hidden');
        kitchenGrid.innerHTML = '';

        kitchenOrders.forEach(p => {
            const card = document.createElement('div');
            const isInPrep = (p.status === 'Em preparo');
            const isReady = (p.status === 'Pronto');

            let headerBg = 'bg-slate-800 text-white';
            let statusBadge = '<span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-400 text-slate-900">Novo na Fila</span>';

            if (isInPrep) {
                headerBg = 'bg-blue-600 text-white';
                statusBadge = '<span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-white text-blue-700 animate-pulse">No Forno / Preparando</span>';
            } else if (isReady) {
                headerBg = 'bg-purple-700 text-white';
                statusBadge = '<span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-purple-100 text-purple-900">Pronto no Balcão</span>';
            }

            // Itens com formatação culinária limpa e em destaque
            let itemsListHtml = '';
            if (p.itens) {
                p.itens.forEach(it => {
                    let tags = [];
                    if (it.tamanho_nome) {
                        tags.push(`<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-800">${it.tamanho_nome}</span>`);
                    }
                    if (it.sabor_secundario_nome) {
                        tags.push(`<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">1/2 ${it.sabor_secundario_nome}</span>`);
                    }
                    if (it.adicionais_selecionados) {
                        tags.push(`<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-900 border border-rose-200">${it.adicionais_selecionados}</span>`);
                    }

                    itemsListHtml += `
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                            <div class="flex items-start gap-2.5">
                                <span class="w-6 h-6 rounded-lg bg-slate-900 text-white font-extrabold text-xs flex items-center justify-center shrink-0">
                                    ${it.quantidade}x
                                </span>
                                <div>
                                    <h4 class="text-sm font-black text-slate-900 leading-tight">${it.nome_produto}</h4>
                                    <div class="flex flex-wrap gap-1 mt-1.5">${tags.join('')}</div>
                                </div>
                            </div>
                            ${it.observacoes ? `
                                <div class="text-[11px] font-bold text-rose-700 bg-rose-50/80 p-2 rounded-xl border border-rose-200/60 flex items-start gap-1.5">
                                    <i class="fa-solid fa-triangle-exclamation text-rose-600 mt-0.5"></i>
                                    <span>OBS: ${it.observacoes}</span>
                                </div>
                            ` : ''}
                        </div>
                    `;
                });
            }

            // Tempo decorrido
            const minutos = Math.max(1, Math.round((new Date() - new Date(p.criado_em)) / 60000));

            // Botões de Ação do Pizzaiolo
            let buttonAction = '';
            if (p.status === 'Recebido') {
                buttonAction = `
                    <button type="button" class="btn-start-prep w-full py-3.5 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs uppercase tracking-wider shadow-md shadow-blue-600/30 transition-all flex items-center justify-center gap-2" data-id="${p.id}" data-code="${p.codigo}">
                        <i class="fa-solid fa-fire"></i> Iniciar Preparo
                    </button>
                `;
            } else if (p.status === 'Em preparo') {
                buttonAction = `
                    <button type="button" class="btn-finish-prep w-full py-3.5 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs uppercase tracking-wider shadow-md shadow-purple-600/30 transition-all flex items-center justify-center gap-2" data-id="${p.id}" data-code="${p.codigo}">
                        <i class="fa-solid fa-check"></i> Marcar como Pronto
                    </button>
                `;
            } else {
                buttonAction = `
                    <div class="w-full py-2.5 rounded-xl bg-purple-50 text-purple-700 border border-purple-200 text-center font-bold text-xs">
                        <i class="fa-solid fa-circle-check mr-1"></i> Pedido Pronto • Aguardando Entrega/Retirada
                    </div>
                `;
            }

            card.className = 'bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow';
            card.innerHTML = `
                <div>
                    <!-- Topo da Comanda -->
                    <div class="p-4 ${headerBg} flex items-center justify-between">
                        <div>
                            <span class="text-xs uppercase font-semibold text-white/80">Comanda</span>
                            <h3 class="text-lg font-black font-brand tracking-wider leading-tight">${p.codigo}</h3>
                        </div>
                        <div class="text-right">
                            ${statusBadge}
                            <span class="block text-[10px] text-white/80 mt-1"><i class="fa-regular fa-clock mr-1"></i> ${minutos} min na fila</span>
                        </div>
                    </div>

                    <!-- Corpo com Itens e Alérgenos/Obs -->
                    <div class="p-5 space-y-3">
                        <div class="flex items-center justify-between text-xs pb-2 border-b border-slate-100 text-slate-500">
                            <span>Tipo: <strong class="text-slate-800">${p.tipo_entrega === 'retirada' ? 'Retirada no Balcão' : 'Entrega em Casa'}</strong></span>
                            <span>Prazo: <strong class="text-slate-800">${p.tempo_estimado_min} min</strong></span>
                        </div>

                        ${p.observacoes ? `
                            <div class="p-2.5 rounded-xl bg-amber-50 text-amber-900 text-xs font-semibold border border-amber-200">
                                <strong>Obs Geral:</strong> ${p.observacoes}
                            </div>
                        ` : ''}

                        <div class="space-y-2.5 pt-1">
                            ${itemsListHtml}
                        </div>
                    </div>
                </div>

                <!-- Rodapé com Botão de Avanço de Status -->
                <div class="p-4 bg-slate-50 border-t border-slate-100">
                    ${buttonAction}
                </div>
            `;

            kitchenGrid.appendChild(card);
        });

        attachKitchenEvents();
    };

    function attachKitchenEvents() {
        // Iniciar preparo
        document.querySelectorAll('.btn-start-prep').forEach(btn => {
            btn.addEventListener('click', async function() {
                const id = this.getAttribute('data-id');
                const code = this.getAttribute('data-code');
                const ok = await window.PanelsManager.updateOrderStatus(id, 'Em preparo');
            });
        });

        // Marcar como pronto
        document.querySelectorAll('.btn-finish-prep').forEach(btn => {
            btn.addEventListener('click', async function() {
                const id = this.getAttribute('data-id');
                const code = this.getAttribute('data-code');
                const ok = await window.PanelsManager.updateOrderStatus(id, 'Pronto');
            });
        });
    }

    // Inicializa KDS com polling
    window.PanelsManager.init('cozinha');
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
