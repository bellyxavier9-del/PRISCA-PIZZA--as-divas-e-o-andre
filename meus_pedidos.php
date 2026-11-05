<?php
/**
 * Prisca Pizza - Histórico de Pedidos do Cliente
 * Acesso exclusivo aos próprios pedidos (Regra: Perfil 1 — Cliente)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/bd.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();
$usuario = getLoggedUser();
$pdo = getDbConnection();

// Busca pedidos do cliente logado
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE cliente_id = :id ORDER BY criado_em DESC");
$stmt->execute([':id' => $usuario['id']]);
$pedidos = $stmt->fetchAll();

$pageTitle = 'Meus Pedidos - ' . APP_NAME;
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">

    <div class="mb-8">
        <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Histórico de Compras</span>
        <h1 class="text-3xl font-extrabold text-slate-900 font-serif mt-1">Meus Pedidos</h1>
        <p class="text-xs text-slate-500 mt-1">Acompanhe seus pedidos em andamento ou veja o histórico das suas pizzas anteriores.</p>
    </div>

    <?php if (empty($pedidos)): ?>
        <div class="text-center py-16 bg-white rounded-3xl border border-slate-200/80 p-8 shadow-sm">
            <div class="w-20 h-20 rounded-3xl bg-slate-100 text-slate-400 text-3xl flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900">Nenhum pedido realizado ainda</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Que tal pedir sua primeira pizza artesanal da Prisca Pizza hoje mesmo?</p>
            <a href="<?= BASE_URL ?>/cardapio.php" class="mt-6 inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-rose-600/30 transition-all">
                <i class="fa-solid fa-pizza-slice"></i> Ver Cardápio
            </a>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($pedidos as $ped): ?>
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 hover:shadow-md transition-shadow flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-3">
                            <span class="text-base font-extrabold text-slate-900 font-brand"><?= e($ped['codigo']) ?></span>
                            <?= getStatusBadge($ped['status']) ?>
                        </div>
                        <p class="text-xs text-slate-500">
                            Realizado em <?= date('d/m/Y \à\s H:i', strtotime($ped['criado_em'])) ?> • 
                            Modalidade: <strong class="text-slate-700"><?= $ped['tipo_entrega'] === 'retirada' ? 'Retirada no Balcão' : 'Entrega em Casa' ?></strong>
                        </p>
                        <p class="text-xs text-slate-600">
                            Pagamento: <span class="uppercase font-semibold"><?= e($ped['forma_pagamento']) ?></span> 
                            (<?= e($ped['status_pagamento']) ?>) • 
                            Total: <strong class="text-slate-900"><?= formatMoney($ped['total']) ?></strong>
                        </p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <a href="<?= BASE_URL ?>/rastreio.php?codigo=<?= urlencode($ped['codigo']) ?>" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-rose-600 text-white font-bold text-xs uppercase tracking-wider transition-colors shadow text-center flex items-center justify-center gap-2">
                            <i class="fa-solid fa-location-crosshairs text-xs"></i> Acompanhar
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
