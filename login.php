<?php
/**
 * Prisca Pizza - Autenticação de Usuários
 * Proteção contra brute-force, bcrypt e atalhos rápidos para teste de perfis
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/bd.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$erro = null;
$redirect = $_GET['redirect'] ?? '';

// Se já está logado, redireciona para painel ou home
if (isLoggedIn()) {
    $role = $_SESSION['usuario']['perfil'];
    if ($role === ROLE_CAIXA) header('Location: ' . BASE_URL . '/painel/caixa.php');
    elseif ($role === ROLE_PIZZAIOLO) header('Location: ' . BASE_URL . '/painel/cozinha.php');
    elseif ($role === ROLE_ENTREGADOR) header('Location: ' . BASE_URL . '/painel/entregas.php');
    else header('Location: ' . BASE_URL . '/cardapio.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    // Verifica bloqueio por tentativas falhas (Regra de Segurança e Proteção)
    $rateLimit = checkLoginRateLimit($email);
    if ($rateLimit['bloqueado']) {
        $erro = "Esta conta está temporariamente bloqueada devido a excesso de tentativas incorretas. Tente novamente em {$rateLimit['minutos']} minutos.";
    } elseif (empty($email) || empty($senha)) {
        $erro = 'Preencha o e-mail e a senha para continuar.';
    } else {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['senha'])) {
            // Sucesso: reseta tentativas e inicia sessão
            recordSuccessfulLogin($user['id'], $user['nome'], $user['perfil']);

            $_SESSION['usuario'] = [
                'id' => $user['id'],
                'nome' => $user['nome'],
                'email' => $user['email'],
                'telefone' => $user['telefone'],
                'endereco' => $user['endereco'],
                'perfil' => $user['perfil']
            ];

            setToast('success', 'Bem-vindo(a)!', "Olá, {$user['nome']}! Você entrou com o perfil {$user['perfil']}.");

            if (!empty($redirect)) {
                header('Location: ' . BASE_URL . '/' . ltrim($redirect, '/'));
            } else {
                if ($user['perfil'] === ROLE_CAIXA) {
                    header('Location: ' . BASE_URL . '/painel/caixa.php');
                } elseif ($user['perfil'] === ROLE_PIZZAIOLO) {
                    header('Location: ' . BASE_URL . '/painel/cozinha.php');
                } elseif ($user['perfil'] === ROLE_ENTREGADOR) {
                    header('Location: ' . BASE_URL . '/painel/entregas.php');
                } else {
                    header('Location: ' . BASE_URL . '/cardapio.php');
                }
            }
            exit;
        } else {
            // Falha: computa tentativa
            recordFailedLogin($email);
            $erro = 'E-mail ou senha incorretos. Por favor, verifique.';
        }
    }
}

$pageTitle = 'Entrar - ' . APP_NAME;
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="max-w-md mx-auto px-4 sm:px-6 py-12 flex-1 w-full flex flex-col justify-center">

    <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-2xl bg-rose-600 text-white flex items-center justify-center text-2xl mx-auto mb-3 shadow-lg shadow-rose-600/30">
            <i class="fa-solid fa-pizza-slice"></i>
        </div>
        <h1 class="text-2xl font-extrabold text-slate-900 font-serif">Acesse sua Conta</h1>
        <p class="text-xs text-slate-500 mt-1">Conecte-se para pedir ou gerenciar a pizzaria</p>
    </div>

    <?php if ($erro): ?>
        <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-start gap-3">
            <i class="fa-solid fa-triangle-exclamation text-base mt-0.5 shrink-0"></i>
            <span><?= e($erro) ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/80">
        <form method="POST" class="space-y-4">

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">E-mail</label>
                <div class="relative">
                    <i class="fa-regular fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="email" name="email" required placeholder="seu@email.com" class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Senha</label>
                <div class="relative">
                    <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="password" name="senha" required placeholder="••••••••" class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none">
                </div>
            </div>

            <button type="submit" class="w-full py-3.5 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-rose-600/30 transition-all">
                Entrar na Conta
            </button>

            <div class="text-center pt-2">
                <span class="text-xs text-slate-500">Ainda não tem conta?</span>
                <a href="<?= BASE_URL ?>/cadastro.php" class="text-xs font-bold text-rose-600 hover:underline ml-1">Criar conta</a>
            </div>

        </form>
    </div>

    <!-- Seção de Acesso Rápido para Teste dos 4 Perfis de Avaliação -->
    <div class="mt-8 pt-6 border-t border-slate-200 text-center">
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-3">Atalhos de Avaliação (1 Clique)</p>
        <div class="grid grid-cols-2 gap-2 text-xs">
            <form method="POST" action="<?= BASE_URL ?>/setup.php">
                <input type="hidden" name="acao" value="login_rapido">
                <input type="hidden" name="perfil" value="cliente">
                <button type="submit" class="w-full p-2.5 rounded-xl border border-slate-200 hover:bg-rose-50 hover:text-rose-600 font-semibold transition-colors flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-user text-rose-500"></i> Cliente
                </button>
            </form>

            <form method="POST" action="<?= BASE_URL ?>/setup.php">
                <input type="hidden" name="acao" value="login_rapido">
                <input type="hidden" name="perfil" value="caixa">
                <button type="submit" class="w-full p-2.5 rounded-xl border border-slate-200 hover:bg-amber-50 hover:text-amber-600 font-semibold transition-colors flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-cash-register text-amber-500"></i> Caixa
                </button>
            </form>

            <form method="POST" action="<?= BASE_URL ?>/setup.php">
                <input type="hidden" name="acao" value="login_rapido">
                <input type="hidden" name="perfil" value="pizzaiolo">
                <button type="submit" class="w-full p-2.5 rounded-xl border border-slate-200 hover:bg-rose-50 hover:text-rose-700 font-semibold transition-colors flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-kitchen-set text-rose-600"></i> Pizzaiolo
                </button>
            </form>

            <form method="POST" action="<?= BASE_URL ?>/setup.php">
                <input type="hidden" name="acao" value="login_rapido">
                <input type="hidden" name="perfil" value="entregador">
                <button type="submit" class="w-full p-2.5 rounded-xl border border-slate-200 hover:bg-blue-50 hover:text-blue-600 font-semibold transition-colors flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-motorcycle text-blue-500"></i> Entregador
                </button>
            </form>
        </div>
    </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
