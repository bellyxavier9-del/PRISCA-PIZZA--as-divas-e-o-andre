<?php
/**
 * Prisca Pizza - Roteador e Visão Geral dos Painéis Internos
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
$usuario = getLoggedUser();

// Redireciona diretamente para a função do colaborador
if ($usuario['perfil'] === ROLE_CAIXA) {
    header('Location: ' . BASE_URL . '/painel/caixa.php');
    exit;
} elseif ($usuario['perfil'] === ROLE_PIZZAIOLO) {
    header('Location: ' . BASE_URL . '/painel/cozinha.php');
    exit;
} elseif ($usuario['perfil'] === ROLE_ENTREGADOR) {
    header('Location: ' . BASE_URL . '/painel/entregas.php');
    exit;
}

// Se for Admin, exibe visão geral e links para todos os painéis
requireRole([ROLE_ADMIN]);
$pdo = getDbConnection();

// Contadores de Pedidos
$contadores = [
    'recebido' => $pdo->query("SELECT COUNT(*) FROM pedidos WHERE status = 'Recebido'")->fetchColumn(),
    'em_preparo' => $pdo->query("SELECT COUNT(*) FROM pedidos WHERE status = 'Em preparo'")->fetchColumn(),
    'pronto' => $pdo->query("SELECT COUNT(*) FROM pedidos WHERE status = 'Pronto'")->fetchColumn(),
    'saiu_entrega' => $pdo->query("SELECT COUNT(*) FROM pedidos WHERE status = 'Saiu para entrega'")->fetchColumn(),
    'entregue_hoje' => $pdo->query("SELECT COUNT(*) FROM pedidos WHERE status = 'Entregue'")->fetchColumn(),
];

$pageTitle = 'Painel Administrativo Geral - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">

    <div class="mb-8">
        <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Administração Geral</span>
        <h1 class="text-3xl font-extrabold text-slate-900 font-serif mt-1">Central Operacional Prisca Pizza</h1>
        <p class="text-xs text-slate-500 mt-1">Acompanhamento unificado de todos os setores e fluxos da pizzaria.</p>
    </div>

    <!-- Indicadores Numéricos -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-8">
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm text-center">
            <span class="text-2xl font-black text-amber-600 font-brand"><?= $contadores['recebido'] ?></span>
            <span class="block text-xs font-bold text-slate-700 mt-1">Novos (Recebido)</span>
        </div>
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm text-center">
            <span class="text-2xl font-black text-blue-600 font-brand"><?= $contadores['em_preparo'] ?></span>
            <span class="block text-xs font-bold text-slate-700 mt-1">Em Preparo</span>
        </div>
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm text-center">
            <span class="text-2xl font-black text-purple-600 font-brand"><?= $contadores['pronto'] ?></span>
            <span class="block text-xs font-bold text-slate-700 mt-1">Prontos</span>
        </div>
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm text-center">
            <span class="text-2xl font-black text-indigo-600 font-brand"><?= $contadores['saiu_entrega'] ?></span>
            <span class="block text-xs font-bold text-slate-700 mt-1">Em Trânsito</span>
        </div>
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm text-center col-span-2 sm:col-span-1">
            <span class="text-2xl font-black text-emerald-600 font-brand"><?= $contadores['entregue_hoje'] ?></span>
            <span class="block text-xs font-bold text-slate-700 mt-1">Entregues</span>
        </div>
    </div>

    <!-- Acesso aos Painéis Específicos -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Painel Caixa -->
        <a href="<?= BASE_URL ?>/painel/caixa.php" class="p-6 rounded-3xl bg-white border border-slate-200 hover:border-amber-400 hover:shadow-lg transition-all group">
            <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-cash-register"></i>
            </div>
            <h3 class="text-base font-bold text-slate-900 group-hover:text-amber-600 transition-colors">Painel do Caixa</h3>
            <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Conferência de pagamentos, encaminhamento para cozinha e conclusão de retiradas.</p>
        </a>

        <!-- Painel Cozinha -->
        <a href="<?= BASE_URL ?>/painel/cozinha.php" class="p-6 rounded-3xl bg-white border border-slate-200 hover:border-rose-400 hover:shadow-lg transition-all group">
            <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-kitchen-set"></i>
            </div>
            <h3 class="text-base font-bold text-slate-900 group-hover:text-rose-600 transition-colors">Cozinha (KDS)</h3>
            <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Display para os pizzaiolos com tickets, sabores, bordas e observações em tempo real.</p>
        </a>

        <!-- Painel Entregas -->
        <a href="<?= BASE_URL ?>/painel/entregas.php" class="p-6 rounded-3xl bg-white border border-slate-200 hover:border-blue-400 hover:shadow-lg transition-all group">
            <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-motorcycle"></i>
            </div>
            <h3 class="text-base font-bold text-slate-900 group-hover:text-blue-600 transition-colors">Painel de Entregas</h3>
            <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Visão dos entregadores para assumir pedidos prontos, rotas e confirmação de entrega.</p>
        </a>

        <!-- Auditoria e Logs -->
        <a href="<?= BASE_URL ?>/painel/auditoria.php" class="p-6 rounded-3xl bg-white border border-slate-200 hover:border-purple-400 hover:shadow-lg transition-all group">
            <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>
            <h3 class="text-base font-bold text-slate-900 group-hover:text-purple-600 transition-colors">Logs de Auditoria</h3>
            <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Rastreamento de todas as alterações feitas no sistema (quem, quando e o que mudou).</p>
        </a>

    </div>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
