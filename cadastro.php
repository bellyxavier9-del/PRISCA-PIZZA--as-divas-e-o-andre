<?php
/**
 * Prisca Pizza - Cadastro de Novos Clientes
 * Regra: Perfil 1 — Cliente: Pode criar uma conta, fazer login e editar seus próprios dados.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/bd.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$erro = null;

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/cardapio.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmaSenha = $_POST['confirma_senha'] ?? '';

    if (empty($nome) || empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos obrigatórios (*).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um endereço de e-mail válido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve conter no mínimo 6 caracteres.';
    } elseif ($senha !== $confirmaSenha) {
        $erro = 'A confirmação de senha não confere.';
    } else {
        $pdo = getDbConnection();

        // Verifica se e-mail já existe
        $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email LIMIT 1");
        $stmtCheck->execute([':email' => $email]);
        if ($stmtCheck->fetch()) {
            $erro = 'Já existe uma conta cadastrada com este e-mail. Faça login.';
        } else {
            try {
                $hash = password_hash($senha, PASSWORD_BCRYPT);

                $stmtInsert = $pdo->prepare("
                    INSERT INTO usuarios (nome, email, senha, telefone, endereco, perfil)
                    VALUES (:nome, :email, :senha, :telefone, :endereco, 'cliente')
                ");
                $stmtInsert->execute([
                    ':nome' => $nome,
                    ':email' => $email,
                    ':senha' => $hash,
                    ':telefone' => $telefone,
                    ':endereco' => $endereco
                ]);

                $novoId = $pdo->lastInsertId();

                // Autentica automaticamente
                $_SESSION['usuario'] = [
                    'id' => $novoId,
                    'nome' => $nome,
                    'email' => $email,
                    'telefone' => $telefone,
                    'endereco' => $endereco,
                    'perfil' => 'cliente'
                ];

                registrarAuditoria(null, 'usuario_cadastrado', null, null, "Novo cliente {$nome} ({$email}) cadastrado com sucesso.");
                setToast('success', 'Conta Criada!', "Seja bem-vindo(a) à Prisca Pizza, {$nome}!");
                header('Location: ' . BASE_URL . '/cardapio.php');
                exit;
            } catch (Exception $e) {
                $erro = 'Erro ao criar conta: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Criar Conta - ' . APP_NAME;
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="max-w-lg mx-auto px-4 sm:px-6 py-10 flex-1 w-full flex flex-col justify-center">

    <div class="text-center mb-8">
        <h1 class="text-2xl font-extrabold text-slate-900 font-serif">Criar sua Conta</h1>
        <p class="text-xs text-slate-500 mt-1">Cadastre-se para pedir pizzas com rapidez e acompanhar a entrega</p>
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
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                    Nome Completo <span class="text-rose-600">*</span>
                </label>
                <input type="text" name="nome" required placeholder="Ex: João da Silva" value="<?= e($_POST['nome'] ?? '') ?>" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        E-mail <span class="text-rose-600">*</span>
                    </label>
                    <input type="email" name="email" required placeholder="seu@email.com" value="<?= e($_POST['email'] ?? '') ?>" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Telefone / WhatsApp
                    </label>
                    <input type="text" name="telefone" placeholder="(11) 98765-4321" value="<?= e($_POST['telefone'] ?? '') ?>" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                    Endereço de Entrega Principal
                </label>
                <textarea name="endereco" rows="2" placeholder="Rua, número, complemento, bairro, cidade, CEP..." class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none"><?= e($_POST['endereco'] ?? '') ?></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Senha <span class="text-rose-600">*</span>
                    </label>
                    <input type="password" name="senha" required placeholder="Mínimo 6 dígitos" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Confirmar Senha <span class="text-rose-600">*</span>
                    </label>
                    <input type="password" name="confirma_senha" required placeholder="Repita a senha" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none">
                </div>
            </div>

            <button type="submit" class="w-full mt-4 py-3.5 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-rose-600/30 transition-all">
                Criar Minha Conta
            </button>

            <div class="text-center pt-2">
                <span class="text-xs text-slate-500">Já tem uma conta?</span>
                <a href="<?= BASE_URL ?>/login.php" class="text-xs font-bold text-rose-600 hover:underline ml-1">Fazer login</a>
            </div>

        </form>
    </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
