<?php
/**
 * Prisca Pizza - Assistente de Configuração, Diagnóstico e Testes
 * Facilita a instalação e testes rápidos com 1 clique para todos os 4 perfis
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/bd.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$mensagemAcao = null;
$tipoAcao = null;

// Execução de Ações pelo Assistente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'reinstalar') {
        try {
            $pdo = getDbConnection();
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

            if ($driver === 'mysql') {
                $schema = file_get_contents(__DIR__ . '/database/schema.sql');
                $seed = file_get_contents(__DIR__ . '/database/seed.sql');
                $pdo->exec($schema);
                $pdo->exec($seed);
            } else {
                initSqliteDatabase($pdo);
            }

            $mensagemAcao = "Banco de dados recriado e populado com sucesso!";
            $tipoAcao = "success";
        } catch (Exception $e) {
            $mensagemAcao = "Erro ao reinstalar: " . $e->getMessage();
            $tipoAcao = "error";
        }
    } elseif ($acao === 'login_rapido') {
        $perfilDesejado = $_POST['perfil'] ?? 'cliente';
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE perfil = :perfil LIMIT 1");
        $stmt->execute([':perfil' => $perfilDesejado]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['usuario'] = [
                'id' => $user['id'],
                'nome' => $user['nome'],
                'email' => $user['email'],
                'telefone' => $user['telefone'],
                'endereco' => $user['endereco'],
                'perfil' => $user['perfil']
            ];

            recordSuccessfulLogin($user['id'], $user['nome'], $user['perfil']);
            setToast('success', 'Login Rápido', "Conectado com sucesso como: {$user['nome']} ({$user['perfil']})");

            // Redireciona para o painel correspondente
            if ($user['perfil'] === ROLE_CAIXA) {
                header('Location: ' . BASE_URL . '/painel/caixa.php');
            } elseif ($user['perfil'] === ROLE_PIZZAIOLO) {
                header('Location: ' . BASE_URL . '/painel/cozinha.php');
            } elseif ($user['perfil'] === ROLE_ENTREGADOR) {
                header('Location: ' . BASE_URL . '/painel/entregas.php');
            } else {
                header('Location: ' . BASE_URL . '/cardapio.php');
            }
            exit;
        }
    }
}

// Diagnóstico de Conexão
$dbStatus = ['ok' => false, 'driver' => 'Desconhecido', 'info' => ''];
try {
    $pdo = getDbConnection();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM produtos");
    $produtosCount = $stmtCount->fetchColumn();
    $dbStatus = [
        'ok' => true,
        'driver' => strtoupper($driver),
        'info' => "Conectado via {$driver}. Total de produtos no cardápio: {$produtosCount}"
    ];
} catch (Exception $e) {
    $dbStatus['info'] = $e->getMessage();
}

$pageTitle = 'Setup & Diagnóstico - ' . APP_NAME;
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="max-w-5xl mx-auto px-4 sm:px-6 py-12 flex-1 w-full">
    
    <div class="mb-10 text-center">
        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-rose-100 text-rose-700 mb-2">Painel de Instalação e Testes</span>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight sm:text-4xl">Setup & Acesso Rápido para Avaliação</h1>
        <p class="mt-2 text-sm text-slate-600 max-w-2xl mx-auto">
            Utilize esta tela para verificar a integridade da base de dados e testar instantaneamente qualquer um dos 4 perfis de usuário com 1 clique.
        </p>
    </div>

    <?php if ($mensagemAcao): ?>
        <div class="mb-8 p-4 rounded-2xl border <?= $tipoAcao === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' ?> flex items-center gap-3">
            <i class="fa-solid <?= $tipoAcao === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-triangle-exclamation text-rose-600' ?> text-xl"></i>
            <span class="text-sm font-semibold"><?= e($mensagemAcao) ?></span>
        </div>
    <?php endif; ?>

    <!-- Status do Banco de Dados -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200 mb-10">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100">
            <div>
                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-database text-rose-600"></i> Status do Banco de Dados
                </h3>
                <p class="text-xs text-slate-500 mt-1">Conexão via PDO com suporte automático a MySQL e SQLite</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold <?= $dbStatus['ok'] ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' ?>">
                    <span class="w-2 h-2 rounded-full <?= $dbStatus['ok'] ? 'bg-emerald-500' : 'bg-rose-500' ?>"></span>
                    <?= $dbStatus['ok'] ? 'Operacional (' . $dbStatus['driver'] . ')' : 'Falha de Conexão' ?>
                </span>
            </div>
        </div>

        <div class="mt-4 text-xs text-slate-600 bg-slate-50 p-4 rounded-2xl border border-slate-200/60 font-mono">
            <?= e($dbStatus['info']) ?>
        </div>

        <form method="POST" class="mt-6 flex justify-end">
            <input type="hidden" name="acao" value="reinstalar">
            <button type="submit" onclick="return confirm('Isso recriará as tabelas e reinserirá todos os dados de teste. Deseja continuar?')" class="px-4 py-2.5 rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 text-xs font-bold uppercase tracking-wider transition-colors flex items-center gap-2">
                <i class="fa-solid fa-arrows-rotate"></i> Recarregar Schema e Dados Iniciais (Seed)
            </button>
        </form>
    </div>

    <!-- Login Rápido com 1 Clique para Demonstração dos Perfis -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-slate-900">Perfis de Usuário para Avaliação</h3>
            <span class="text-xs text-slate-500">Clique para logar instantaneamente</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

            <!-- Perfil 1: Cliente -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 flex flex-col justify-between hover:shadow-md transition-shadow">
                <div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center text-xl mb-4">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-rose-600 bg-rose-50 px-2.5 py-1 rounded-md">Perfil 1</span>
                    <h4 class="text-base font-bold text-slate-900 mt-2">Cliente</h4>
                    <p class="text-xs text-slate-500 mt-1">Monta pedidos, customiza pizzas, escolhe pagamentos e acompanha rastreio.</p>
                    <div class="mt-3 text-[11px] text-slate-400 bg-slate-50 p-2.5 rounded-xl font-mono">
                        cliente@priscapizza.com<br>senha123
                    </div>
                </div>
                <form method="POST" class="mt-5">
                    <input type="hidden" name="acao" value="login_rapido">
                    <input type="hidden" name="perfil" value="cliente">
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-rose-600 text-white font-bold text-xs uppercase tracking-wider transition-colors shadow">
                        Entrar como Cliente
                    </button>
                </form>
            </div>

            <!-- Perfil 2: Atendente de Caixa -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 flex flex-col justify-between hover:shadow-md transition-shadow">
                <div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl mb-4">
                        <i class="fa-solid fa-cash-register"></i>
                    </div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-amber-600 bg-amber-50 px-2.5 py-1 rounded-md">Perfil 2</span>
                    <h4 class="text-base font-bold text-slate-900 mt-2">Atendente de Caixa</h4>
                    <p class="text-xs text-slate-500 mt-1">Verifica pagamentos, encaminha para cozinha, conclui retiradas e cancela pedidos.</p>
                    <div class="mt-3 text-[11px] text-slate-400 bg-slate-50 p-2.5 rounded-xl font-mono">
                        caixa@priscapizza.com<br>senha123
                    </div>
                </div>
                <form method="POST" class="mt-5">
                    <input type="hidden" name="acao" value="login_rapido">
                    <input type="hidden" name="perfil" value="caixa">
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs uppercase tracking-wider transition-colors shadow">
                        Painel do Caixa
                    </button>
                </form>
            </div>

            <!-- Perfil 3: Pizzaiolo -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 flex flex-col justify-between hover:shadow-md transition-shadow">
                <div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center text-xl mb-4">
                        <i class="fa-solid fa-kitchen-set"></i>
                    </div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-rose-700 bg-rose-50 px-2.5 py-1 rounded-md">Perfil 3</span>
                    <h4 class="text-base font-bold text-slate-900 mt-2">Pizzaiolo (Cozinha)</h4>
                    <p class="text-xs text-slate-500 mt-1">Display de cozinha (KDS). Visualiza itens, sabores e observações. Sem dados bancários.</p>
                    <div class="mt-3 text-[11px] text-slate-400 bg-slate-50 p-2.5 rounded-xl font-mono">
                        pizzaiolo@priscapizza.com<br>senha123
                    </div>
                </div>
                <form method="POST" class="mt-5">
                    <input type="hidden" name="acao" value="login_rapido">
                    <input type="hidden" name="perfil" value="pizzaiolo">
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-rose-700 hover:bg-rose-800 text-white font-bold text-xs uppercase tracking-wider transition-colors shadow">
                        Painel Cozinha
                    </button>
                </form>
            </div>

            <!-- Perfil 4: Entregador -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 flex flex-col justify-between hover:shadow-md transition-shadow">
                <div>
                    <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl mb-4">
                        <i class="fa-solid fa-motorcycle"></i>
                    </div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-blue-600 bg-blue-50 px-2.5 py-1 rounded-md">Perfil 4</span>
                    <h4 class="text-base font-bold text-slate-900 mt-2">Entregador</h4>
                    <p class="text-xs text-slate-500 mt-1">Assume pedidos prontos ("Saiu para entrega"), vê endereço e telefone, e conclui como "Entregue".</p>
                    <div class="mt-3 text-[11px] text-slate-400 bg-slate-50 p-2.5 rounded-xl font-mono">
                        entregador@priscapizza.com<br>senha123
                    </div>
                </div>
                <form method="POST" class="mt-5">
                    <input type="hidden" name="acao" value="login_rapido">
                    <input type="hidden" name="perfil" value="entregador">
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs uppercase tracking-wider transition-colors shadow">
                        Painel Entregas
                    </button>
                </form>
            </div>

        </div>
    </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
