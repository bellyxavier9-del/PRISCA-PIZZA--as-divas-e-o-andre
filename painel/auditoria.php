<?php
/**
 * Prisca Pizza - Página de Histórico de Auditoria e Logs
 * Regra CORE: Mantém sempre os logs de qualquer alteração feita por qualquer usuário para auditorias futuras.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole([ROLE_CAIXA, ROLE_ADMIN]);
$usuario = getLoggedUser();
$pdo = getDbConnection();

// Filtros
$acaoFiltro = trim($_GET['acao'] ?? '');
$perfilFiltro = trim($_GET['perfil'] ?? '');
$buscaCodigo = trim($_GET['codigo'] ?? '');

$sql = "SELECT l.*, p.codigo as pedido_codigo 
        FROM logs_auditoria l 
        LEFT JOIN pedidos p ON l.pedido_id = p.id 
        WHERE 1=1";
$params = [];

if (!empty($acaoFiltro)) {
    $sql .= " AND l.acao = :acao";
    $params[':acao'] = $acaoFiltro;
}
if (!empty($perfilFiltro)) {
    $sql .= " AND l.usuario_perfil = :perfil";
    $params[':perfil'] = $perfilFiltro;
}
if (!empty($buscaCodigo)) {
    $sql .= " AND (p.codigo LIKE :cod OR l.detalhes LIKE :det)";
    $params[':cod'] = "%{$buscaCodigo}%";
    $params[':det'] = "%{$buscaCodigo}%";
}

$sql .= " ORDER BY l.criado_em DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$pageTitle = 'Auditoria & Logs do Sistema - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-purple-600 bg-purple-50 px-2.5 py-1 rounded-md">Regra de Negócio CORE</span>
            <h1 class="text-3xl font-extrabold text-slate-900 font-serif mt-1">Histórico de Auditoria e Logs</h1>
            <p class="text-xs text-slate-500 mt-0.5">Rastreamento de todas as alterações, transições de status e acessos no sistema.</p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold uppercase tracking-wider shadow-sm transition-all flex items-center gap-2">
                <i class="fa-solid fa-print"></i> Imprimir Relatório
            </button>
        </div>
    </div>

    <!-- Barra de Filtros de Auditoria -->
    <div class="bg-white p-4 rounded-3xl border border-slate-200/80 shadow-sm mb-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Ação</label>
                <select name="acao" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">Todas as ações</option>
                    <option value="status_mudou" <?= $acaoFiltro === 'status_mudou' ? 'selected' : '' ?>>Mudança de Status</option>
                    <option value="pedido_criado" <?= $acaoFiltro === 'pedido_criado' ? 'selected' : '' ?>>Pedido Criado</option>
                    <option value="login_sucesso" <?= $acaoFiltro === 'login_sucesso' ? 'selected' : '' ?>>Login com Sucesso</option>
                    <option value="login_falha" <?= $acaoFiltro === 'login_falha' ? 'selected' : '' ?>>Falha de Login</option>
                    <option value="acesso_negado" <?= $acaoFiltro === 'acesso_negado' ? 'selected' : '' ?>>Tentativa de Violação</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Perfil do Usuário</label>
                <select name="perfil" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">Todos os perfis</option>
                    <option value="cliente" <?= $perfilFiltro === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                    <option value="caixa" <?= $perfilFiltro === 'caixa' ? 'selected' : '' ?>>Atendente de Caixa</option>
                    <option value="pizzaiolo" <?= $perfilFiltro === 'pizzaiolo' ? 'selected' : '' ?>>Pizzaiolo</option>
                    <option value="entregador" <?= $perfilFiltro === 'entregador' ? 'selected' : '' ?>>Entregador</option>
                    <option value="admin" <?= $perfilFiltro === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Buscar por Pedido / Texto</label>
                <input type="text" name="codigo" value="<?= e($buscaCodigo) ?>" placeholder="Ex: PRISCA-1001..." class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs uppercase tracking-wider transition-colors shadow">
                    Filtrar
                </button>
                <a href="<?= BASE_URL ?>/painel/auditoria.php" class="p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs text-center" title="Limpar Filtros">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Tabela de Auditoria -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 uppercase tracking-wider text-[10px]">
                        <th class="py-3.5 px-4 font-bold">Data & Hora</th>
                        <th class="py-3.5 px-4 font-bold">Usuário / Perfil</th>
                        <th class="py-3.5 px-4 font-bold">Ação</th>
                        <th class="py-3.5 px-4 font-bold">Pedido</th>
                        <th class="py-3.5 px-4 font-bold">Transição de Status</th>
                        <th class="py-3.5 px-4 font-bold">Detalhes / IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-12 text-slate-400">
                                Nenhum registro de log encontrado para os critérios selecionados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3.5 px-4 whitespace-nowrap text-slate-500">
                                    <span class="font-bold text-slate-800"><?= date('d/m/Y', strtotime($log['criado_em'])) ?></span>
                                    <span class="block text-[10px]"><?= date('H:i:s', strtotime($log['criado_em'])) ?></span>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-slate-900 block"><?= e($log['usuario_nome']) ?></span>
                                    <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold uppercase bg-slate-100 text-slate-700 mt-0.5">
                                        <?= e($log['usuario_perfil']) ?>
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded text-[10px] uppercase">
                                        <?= e($log['acao']) ?>
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap font-brand font-bold text-slate-900">
                                    <?= e($log['pedido_codigo'] ?: '-') ?>
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <?php if ($log['status_anterior'] || $log['status_novo']): ?>
                                        <div class="flex items-center gap-1.5 text-[11px]">
                                            <span class="text-slate-400"><?= e($log['status_anterior'] ?: 'Início') ?></span>
                                            <i class="fa-solid fa-arrow-right text-[9px] text-slate-400"></i>
                                            <span class="font-bold text-slate-800"><?= e($log['status_novo']) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>

                                <td class="py-3.5 px-4 max-w-xs">
                                    <p class="text-slate-600 truncate hover:text-clip hover:whitespace-normal" title="<?= e($log['detalhes']) ?>">
                                        <?= e($log['detalhes'] ?: '-') ?>
                                    </p>
                                    <span class="block text-[10px] text-slate-400 font-mono mt-0.5">IP: <?= e($log['ip']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
