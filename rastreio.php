<?php
/**
 * Prisca Pizza - Acompanhamento do Pedido em Tempo Real (Rastreio)
 * Auto-refresh via AJAX com stepper visual e detalhes do pagamento
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/bd.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$codigo = trim($_GET['codigo'] ?? '');
if (empty($codigo)) {
    header('Location: ' . BASE_URL . '/meus_pedidos.php');
    exit;
}

$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE codigo = :codigo LIMIT 1");
$stmt->execute([':codigo' => $codigo]);
$pedido = $stmt->fetch();

if (!$pedido) {
    setToast('error', 'Pedido Não Encontrado', 'O código de pedido informado não existe.');
    header('Location: ' . BASE_URL . '/meus_pedidos.php');
    exit;
}

// Se cliente logado, garante que não acesse pedido de outro cliente
if (isLoggedIn() && hasRole(ROLE_CLIENTE) && $pedido['cliente_id'] != $_SESSION['usuario']['id']) {
    setToast('error', 'Acesso Negado', 'Você só pode visualizar seus próprios pedidos.');
    header('Location: ' . BASE_URL . '/meus_pedidos.php');
    exit;
}

// Busca itens
$stmtItens = $pdo->prepare("SELECT * FROM itens_pedido WHERE pedido_id = :id");
$stmtItens->execute([':id' => $pedido['id']]);
$itens = $stmtItens->fetchAll();

// Busca logs de auditoria
$stmtLogs = $pdo->prepare("SELECT * FROM logs_auditoria WHERE pedido_id = :id ORDER BY criado_em ASC");
$stmtLogs->execute([':id' => $pedido['id']]);
$logs = $stmtLogs->fetchAll();

$pageTitle = 'Rastreio do Pedido ' . $pedido['codigo'] . ' - ' . APP_NAME;
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">

    <!-- Cabeçalho do Rastreio -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/80 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100">
            <div>
                <span class="text-[10px] uppercase font-bold tracking-wider text-rose-600 bg-rose-50 px-2.5 py-1 rounded-md">
                    Pedido <?= e($pedido['tipo_entrega'] === 'retirada' ? 'Para Retirada' : 'Para Entrega') ?>
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-brand mt-1 flex items-center gap-2">
                    <span id="tracker-code"><?= e($pedido['codigo']) ?></span>
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">Realizado em <?= date('d/m/Y \à\s H:i', strtotime($pedido['criado_em'])) ?></p>
            </div>

            <div class="text-left sm:text-right">
                <span class="block text-xs text-slate-400 font-medium">Status Atual</span>
                <div id="tracker-status-badge" class="mt-1">
                    <?= getStatusBadge($pedido['status']) ?>
                </div>
            </div>
        </div>

        <!-- Indicador de Atualização em Tempo Real -->
        <div class="flex items-center justify-between pt-4 text-xs text-slate-500">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Atualização automática a cada 5s</span>
            </div>
            <div class="flex items-center gap-1.5 font-bold text-slate-800">
                <i class="fa-solid fa-clock text-rose-600"></i>
                <span>Previsão: <?= $pedido['tempo_estimado_min'] ?> minutos</span>
            </div>
        </div>

        <!-- STEPPER VISUAL DO FLUXO DO PEDIDO (Regra: Recebido -> Em preparo -> Pronto -> Saiu para entrega -> Entregue) -->
        <div class="mt-8 pt-6 border-t border-slate-100" id="stepper-wrapper">
            <?php if ($pedido['status'] === STATUS_CANCELADO): ?>
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                    <div class="flex items-center gap-2 font-bold text-sm">
                        <i class="fa-solid fa-triangle-exclamation text-rose-600"></i> Este pedido foi cancelado.
                    </div>
                    <p class="mt-1"><strong>Motivo:</strong> <?= e($pedido['motivo_cancelamento'] ?: 'Cancelamento solicitado.') ?></p>
                </div>
            <?php else: ?>
                <div class="relative">
                    <!-- Linha de progresso no fundo -->
                    <div class="absolute top-1/2 left-0 right-0 h-1 bg-slate-200 -translate-y-1/2 z-0 hidden sm:block"></div>
                    <div id="stepper-progress-bar" class="absolute top-1/2 left-0 h-1 bg-rose-600 -translate-y-1/2 z-0 hidden sm:block transition-all duration-500" style="width: <?= getStatusProgressPercentage($pedido['status']) ?>%;"></div>

                    <!-- Passos -->
                    <div class="relative z-10 grid grid-cols-2 sm:grid-cols-5 gap-4 text-center">
                        
                        <!-- Passo 1: Recebido -->
                        <div class="step-node flex flex-col items-center" data-step="Recebido">
                            <div class="step-icon w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs transition-all shadow-md <?= in_array($pedido['status'], ['Recebido', 'Em preparo', 'Pronto', 'Saiu para entrega', 'Entregue']) ? 'bg-rose-600 text-white' : 'bg-slate-200 text-slate-500' ?>">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <span class="step-label text-xs font-bold text-slate-800 mt-2">Recebido</span>
                            <span class="text-[10px] text-slate-400">Pelo Caixa</span>
                        </div>

                        <!-- Passo 2: Em preparo -->
                        <div class="step-node flex flex-col items-center" data-step="Em preparo">
                            <div class="step-icon w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs transition-all shadow-md <?= in_array($pedido['status'], ['Em preparo', 'Pronto', 'Saiu para entrega', 'Entregue']) ? 'bg-rose-600 text-white' : 'bg-slate-200 text-slate-500' ?>">
                                <i class="fa-solid fa-fire"></i>
                            </div>
                            <span class="step-label text-xs font-bold text-slate-800 mt-2">Em preparo</span>
                            <span class="text-[10px] text-slate-400">Na Cozinha</span>
                        </div>

                        <!-- Passo 3: Pronto -->
                        <div class="step-node flex flex-col items-center" data-step="Pronto">
                            <div class="step-icon w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs transition-all shadow-md <?= in_array($pedido['status'], ['Pronto', 'Saiu para entrega', 'Entregue']) ? 'bg-rose-600 text-white' : 'bg-slate-200 text-slate-500' ?>">
                                <i class="fa-solid fa-pizza-slice"></i>
                            </div>
                            <span class="step-label text-xs font-bold text-slate-800 mt-2">Pronto</span>
                            <span class="text-[10px] text-slate-400"><?= $pedido['tipo_entrega'] === 'retirada' ? 'Para Retirar' : 'Embalado' ?></span>
                        </div>

                        <!-- Passo 4: Saiu para entrega -->
                        <div class="step-node flex flex-col items-center" data-step="Saiu para entrega">
                            <div class="step-icon w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs transition-all shadow-md <?= in_array($pedido['status'], ['Saiu para entrega', 'Entregue']) ? 'bg-rose-600 text-white' : 'bg-slate-200 text-slate-500' ?>">
                                <i class="fa-solid fa-motorcycle"></i>
                            </div>
                            <span class="step-label text-xs font-bold text-slate-800 mt-2">Em trânsito</span>
                            <span class="text-[10px] text-slate-400">Com Motoboy</span>
                        </div>

                        <!-- Passo 5: Entregue -->
                        <div class="step-node flex flex-col items-center col-span-2 sm:col-span-1" data-step="Entregue">
                            <div class="step-icon w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs transition-all shadow-md <?= $pedido['status'] === 'Entregue' ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500' ?>">
                                <i class="fa-solid fa-house-chimney"></i>
                            </div>
                            <span class="step-label text-xs font-bold text-slate-800 mt-2">Entregue</span>
                            <span class="text-[10px] text-slate-400">Concluído</span>
                        </div>

                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Seção de Pagamento Simulado e QR Code Pix -->
    <?php if ($pedido['forma_pagamento'] === 'pix' && !empty($pedido['pix_codigo'])): ?>
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 mb-6">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                <i class="fa-brands fa-pix text-emerald-600 text-xl"></i>
                <h3 class="text-base font-bold text-slate-900">Pagamento Pix Simulado</h3>
                <span class="ml-auto px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800">
                    <?= e($pedido['status_pagamento']) ?>
                </span>
            </div>

            <div class="mt-4 flex flex-col sm:flex-row items-center gap-6">
                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 shrink-0">
                    <img src="<?= e($pedido['pix_qrcode']) ?>" alt="QR Code Pix" class="w-36 h-36 object-contain rounded-xl">
                </div>
                <div class="space-y-3 flex-1 text-center sm:text-left">
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Abra o aplicativo do seu banco, escolha <strong>Pix Copia e Cola</strong> ou aponte a câmera para o QR Code acima.
                    </p>
                    <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-[11px] font-mono text-slate-700 break-all select-all">
                        <?= e($pedido['pix_codigo']) ?>
                    </div>
                    <button type="button" onclick="navigator.clipboard.writeText('<?= addslashes($pedido['pix_codigo']) ?>'); showToast('success', 'Código Copiado!', 'O código Pix Copia e Cola foi copiado para sua área de transferência.')" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-rose-600 text-white font-bold text-xs uppercase tracking-wider transition-colors inline-flex items-center gap-2">
                        <i class="fa-regular fa-copy"></i> Copiar Código Pix
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Detalhes dos Produtos e Valores -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        
        <!-- Itens do Pedido -->
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-receipt text-rose-600"></i> Produtos do Pedido
            </h3>
            
            <div class="space-y-3 divide-y divide-slate-100 text-xs" id="tracker-items-list">
                <?php foreach ($itens as $item): ?>
                    <div class="pt-3 first:pt-0 flex justify-between items-start gap-2">
                        <div>
                            <span class="font-bold text-slate-800"><?= $item['quantidade'] ?>x <?= e($item['nome_produto']) ?></span>
                            <?php if ($item['tamanho_nome']): ?>
                                <span class="block text-[10px] text-slate-500"><?= e($item['tamanho_nome']) ?></span>
                            <?php endif; ?>
                            <?php if ($item['sabor_secundario_nome']): ?>
                                <span class="block text-[10px] text-amber-600 font-medium">Metade <?= e($item['sabor_secundario_nome']) ?></span>
                            <?php endif; ?>
                            <?php if ($item['adicionais_selecionados']): ?>
                                <span class="block text-[10px] text-slate-400">+ <?= e($item['adicionais_selecionados']) ?></span>
                            <?php endif; ?>
                            <?php if ($item['observacoes']): ?>
                                <span class="block text-[10px] text-slate-400 italic">Obs: <?= e($item['observacoes']) ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="font-extrabold text-slate-900 shrink-0"><?= formatMoney($item['subtotal_item']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 space-y-1.5 text-xs text-slate-600">
                <div class="flex justify-between">
                    <span>Subtotal:</span>
                    <span class="font-bold text-slate-800"><?= formatMoney($pedido['subtotal']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span>Taxa de Entrega:</span>
                    <span class="font-bold text-slate-800"><?= formatMoney($pedido['taxa_entrega']) ?></span>
                </div>
                <div class="flex justify-between text-sm font-extrabold text-rose-600 pt-1">
                    <span>Total:</span>
                    <span><?= formatMoney($pedido['total']) ?></span>
                </div>
            </div>
        </div>

        <!-- Dados de Entrega e Auditoria do Pedido -->
        <div class="space-y-6">
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 text-xs space-y-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
                    <i class="fa-solid fa-location-dot text-rose-600"></i> Informações de Destino
                </h3>
                <p><strong>Destino:</strong> <?= e($pedido['endereco_entrega']) ?></p>
                <p><strong>Telefone de Contato:</strong> <?= e($pedido['telefone_contato']) ?></p>
                <p><strong>Pagamento:</strong> <?= strtoupper($pedido['forma_pagamento']) ?> (<?= e($pedido['status_pagamento']) ?>)</p>
                <?php if ($pedido['troco_para']): ?>
                    <p class="text-amber-700 font-semibold"><strong>Troco para:</strong> <?= formatMoney($pedido['troco_para']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Histórico de Auditoria do Pedido (Transparência total) -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 text-xs">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
                    <i class="fa-solid fa-history text-rose-600"></i> Histórico de Atualizações
                </h3>
                <div class="mt-3 space-y-2.5 max-h-48 overflow-y-auto" id="tracker-history-list">
                    <?php foreach ($logs as $log): ?>
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-[11px] leading-relaxed">
                            <div class="flex justify-between font-bold text-slate-800">
                                <span><?= e($log['status_novo'] ?: $log['acao']) ?></span>
                                <span class="text-slate-400 font-normal"><?= date('H:i:s', strtotime($log['criado_em'])) ?></span>
                            </div>
                            <p class="text-slate-500 mt-0.5"><?= e($log['detalhes']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>

</main>

<!-- Polling em Tempo Real (Atualização a cada 5 segundos) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const orderCode = '<?= addslashes($pedido['codigo']) ?>';
    let currentStatus = '<?= addslashes($pedido['status']) ?>';

    async function pollOrderStatus() {
        try {
            const response = await fetch(`${window.PRISCA.baseUrl}/api/pedidos.php?action=track&codigo=${encodeURIComponent(orderCode)}`);
            const data = await response.json();

            if (data.status === 'success' && data.pedido) {
                const newStatus = data.pedido.status;

                if (newStatus !== currentStatus) {
                    currentStatus = newStatus;
                    showToast('info', 'Status Atualizado!', `Seu pedido agora está: ${newStatus}`);
                    setTimeout(() => window.location.reload(), 1200);
                }
            }
        } catch (e) {
            // Silencioso em caso de erro transitório de rede
        }
    }

    // Executa polling a cada 5 segundos
    setInterval(pollOrderStatus, 5000);
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
