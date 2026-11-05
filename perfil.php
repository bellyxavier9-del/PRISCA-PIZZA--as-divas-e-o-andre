<?php
/**
 * Prisca Pizza - Edição de Dados Pessoais do Cliente
 * Regra: Perfil 1 — Cliente: Pode criar uma conta, fazer login e editar seus próprios dados.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/bd.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();
$usuario = getLoggedUser();
$pdo = getDbConnection();

$erro = null;
$sucesso = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $senhaAtual = $_POST['senha_atual'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmaSenha = $_POST['confirma_senha'] ?? '';

    if (empty($nome)) {
        $erro = 'O nome não pode ficar em branco.';
    } else {
        try {
            // Se informou nova senha, valida senha atual e hashing
            $updatePassword = false;
            $senhaHash = null;

            if (!empty($novaSenha)) {
                if (strlen($novaSenha) < 6) {
                    $erro = 'A nova senha deve ter no mínimo 6 caracteres.';
                } elseif ($novaSenha !== $confirmaSenha) {
                    $erro = 'A confirmação de senha não confere.';
                } else {
                    // Verifica senha atual no banco
                    $stmtPass = $pdo->prepare("SELECT senha FROM usuarios WHERE id = :id");
                    $stmtPass->execute([':id' => $usuario['id']]);
                    $userDb = $stmtPass->fetch();

                    if (!password_verify($senhaAtual, $userDb['senha'])) {
                        $erro = 'A senha atual informada está incorreta.';
                    } else {
                        $updatePassword = true;
                        $senhaHash = password_hash($novaSenha, PASSWORD_BCRYPT);
                    }
                }
            }

            if (!$erro) {
                if ($updatePassword) {
                    $stmtUpdate = $pdo->prepare("UPDATE usuarios SET nome = :nome, telefone = :telefone, endereco = :endereco, senha = :senha WHERE id = :id");
                    $stmtUpdate->execute([
                        ':nome' => $nome,
                        ':telefone' => $telefone,
                        ':endereco' => $endereco,
                        ':senha' => $senhaHash,
                        ':id' => $usuario['id']
                    ]);
                } else {
                    $stmtUpdate = $pdo->prepare("UPDATE usuarios SET nome = :nome, telefone = :telefone, endereco = :endereco WHERE id = :id");
                    $stmtUpdate->execute([
                        ':nome' => $nome,
                        ':telefone' => $telefone,
                        ':endereco' => $endereco,
                        ':id' => $usuario['id']
                    ]);
                }

                // Atualiza sessão
                $_SESSION['usuario']['nome'] = $nome;
                $_SESSION['usuario']['telefone'] = $telefone;
                $_SESSION['usuario']['endereco'] = $endereco;
                $usuario = $_SESSION['usuario'];

                registrarAuditoria(null, 'perfil_atualizado', null, null, "Usuário {$nome} atualizou seus dados cadastrais.");
                setToast('success', 'Perfil Atualizado', 'Seus dados foram atualizados com sucesso!');
                $sucesso = 'Dados salvos com sucesso!';
            }
        } catch (Exception $e) {
            $erro = 'Erro ao salvar alterações: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Meus Dados - ' . APP_NAME;
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">

    <div class="mb-8">
        <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Minha Conta</span>
        <h1 class="text-3xl font-extrabold text-slate-900 font-serif mt-1">Meus Dados & Endereço</h1>
        <p class="text-xs text-slate-500 mt-1">Mantenha seu endereço de entrega e telefone sempre atualizados.</p>
    </div>

    <?php if ($erro): ?>
        <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-base shrink-0"></i>
            <span><?= e($erro) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-base shrink-0"></i>
            <span><?= e($sucesso) ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/80">
        <form method="POST" class="space-y-6">

            <!-- E-mail (Somente leitura para integridade) -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">E-mail Cadastrado</label>
                <input type="email" value="<?= e($usuario['email']) ?>" disabled class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-100 text-slate-500 cursor-not-allowed">
                <span class="block text-[10px] text-slate-400 mt-1">O e-mail é o seu identificador único no sistema.</span>
            </div>

            <!-- Nome Completo -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nome Completo</label>
                <input type="text" name="nome" value="<?= e($usuario['nome']) ?>" required class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none">
            </div>

            <!-- Telefone / WhatsApp -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Telefone / WhatsApp</label>
                <input type="text" name="telefone" value="<?= e($usuario['telefone'] ?? '') ?>" placeholder="(11) 98765-4321" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none">
            </div>

            <!-- Endereço de Entrega Principal -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Endereço Principal de Entrega</label>
                <textarea name="endereco" rows="2" placeholder="Rua, número, complemento, bairro, cidade, CEP..." class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none"><?= e($usuario['endereco'] ?? '') ?></textarea>
            </div>

            <!-- Alteração de Senha Segura -->
            <div class="pt-6 border-t border-slate-100 space-y-4">
                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="fa-solid fa-lock text-rose-600"></i> Alterar Senha (Opcional)
                </h4>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Senha Atual</label>
                        <input type="password" name="senha_atual" placeholder="••••••••" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Nova Senha</label>
                        <input type="password" name="nova_senha" placeholder="Mínimo 6 dígitos" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Confirmar Nova Senha</label>
                        <input type="password" name="confirma_senha" placeholder="Repita a senha" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 outline-none">
                    </div>
                </div>
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit" class="px-6 py-3 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-rose-600/30 transition-all">
                    Salvar Alterações
                </button>
            </div>

        </form>
    </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
