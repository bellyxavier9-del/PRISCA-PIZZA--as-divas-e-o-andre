<?php
/**
 * Prisca Pizza - Rodapé, Scripts Globais e Modais
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

$activeToast = getToast();
?>
    <!-- Rodapé Principal -->
    <footer class="mt-auto bg-slate-900 text-slate-300 pt-16 pb-28 md:pb-12 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
                
                <!-- Coluna 1: Sobre & Marca -->
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-rose-600 flex items-center justify-center text-white text-xl shadow-lg shadow-rose-600/30">
                            <i class="fa-solid fa-pizza-slice"></i>
                        </div>
                        <span class="font-brand text-2xl font-bold tracking-tight text-white">
                            PRISCA <span class="text-rose-500">PIZZA</span>
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Pizzas e esfihas artesanais assadas na perfeição. Tradição italiana com ingredientes nobres e fermentação natural de 48 horas.
                    </p>
                    <div class="flex items-center gap-3 text-sm text-slate-400 pt-2">
                        <a href="#" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-rose-600 hover:text-white flex items-center justify-center transition-colors"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-rose-600 hover:text-white flex items-center justify-center transition-colors"><i class="fa-brands fa-facebook"></i></a>
                        <a href="https://wa.me/5511988887777" target="_blank" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-emerald-600 hover:text-white flex items-center justify-center transition-colors"><i class="fa-brands fa-whatsapp"></i></a>
                    </div>
                </div>

                <!-- Coluna 2: Horários & Funcionamento -->
                <div class="space-y-3">
                    <h5 class="text-xs font-bold uppercase tracking-widest text-slate-100">Horário de Fornada</h5>
                    <ul class="text-xs space-y-2 text-slate-400">
                        <li class="flex justify-between pb-1 border-b border-slate-800">
                            <span>Segunda-feira:</span>
                            <span class="text-rose-400 font-semibold">Fechado</span>
                        </li>
                        <li class="flex justify-between pb-1 border-b border-slate-800">
                            <span>Terça a Quinta:</span>
                            <span class="text-slate-200">18:00 às 23:30</span>
                        </li>
                        <li class="flex justify-between pb-1 border-b border-slate-800">
                            <span>Sexta e Sábado:</span>
                            <span class="text-slate-200">18:00 às 00:00</span>
                        </li>
                        <li class="flex justify-between">
                            <span>Domingo:</span>
                            <span class="text-slate-200">18:00 às 23:30</span>
                        </li>
                    </ul>
                </div>

                <!-- Coluna 3: Localização & Contato -->
                <div class="space-y-3">
                    <h5 class="text-xs font-bold uppercase tracking-widest text-slate-100">Nossa Pizzaria</h5>
                    <ul class="text-xs space-y-2 text-slate-400">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-location-dot text-rose-500 mt-0.5"></i>
                            <span><?= APP_ADDRESS ?></span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-phone text-rose-500"></i>
                            <span><?= APP_PHONE ?></span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-brands fa-whatsapp text-emerald-500"></i>
                            <span><?= APP_WHATSAPP ?></span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-motorcycle text-amber-500"></i>
                            <span>Entrega média: <?= ESTIMATED_DELIVERY_TIME ?></span>
                        </li>
                    </ul>
                </div>

                <!-- Coluna 4: Segurança & Pagamentos -->
                <div class="space-y-3">
                    <h5 class="text-xs font-bold uppercase tracking-widest text-slate-100">Pagamento & Segurança</h5>
                    <p class="text-xs text-slate-400">
                        Simulação segura e transparente com Pix, Cartões de Crédito/Débito e Dinheiro na entrega ou balcão.
                    </p>
                    <div class="flex flex-wrap gap-2 pt-2">
                        <span class="px-2.5 py-1 rounded-lg bg-slate-800 text-[11px] font-semibold text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5">
                            <i class="fa-brands fa-pix"></i> Pix Instantâneo
                        </span>
                        <span class="px-2.5 py-1 rounded-lg bg-slate-800 text-[11px] font-semibold text-sky-400 border border-sky-500/20 flex items-center gap-1.5">
                            <i class="fa-solid fa-credit-card"></i> Cartões
                        </span>
                        <span class="px-2.5 py-1 rounded-lg bg-slate-800 text-[11px] font-semibold text-amber-400 border border-amber-500/20 flex items-center gap-1.5">
                            <i class="fa-solid fa-money-bill-wave"></i> Dinheiro
                        </span>
                    </div>
                    <div class="pt-2 text-[11px] text-slate-500 flex items-center gap-1.5">
                        <i class="fa-solid fa-lock text-emerald-500"></i>
                        <span>Senhas protegidas com bcrypt e sessões criptografadas.</span>
                    </div>
                </div>

            </div>

            <div class="mt-12 pt-8 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <p>&copy; <?= date('Y') ?> Prisca Pizza Artesanal. Todos os direitos reservados.</p>
                <div class="flex items-center gap-4 text-[11px]">
                    <a href="<?= BASE_URL ?>/setup.php" class="hover:text-rose-400 transition-colors">Setup & Diagnóstico</a>
                    <span>•</span>
                    <a href="<?= BASE_URL ?>/cardapio.php" class="hover:text-rose-400 transition-colors">Cardápio</a>
                    <span>•</span>
                    <a href="<?= BASE_URL ?>/login.php" class="hover:text-rose-400 transition-colors">Acesso Equipe</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Barra de Navegação Inferior Fixa para Mobile (Mobile First) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 px-4 py-2 flex justify-around items-center text-center shadow-lg">
        <a href="<?= BASE_URL ?>/index.php" class="flex flex-col items-center text-xs font-semibold <?= $currentPage === 'index.php' ? 'text-rose-600' : 'text-slate-500' ?>">
            <i class="fa-solid fa-house text-base mb-0.5"></i>
            <span>Início</span>
        </a>
        <a href="<?= BASE_URL ?>/cardapio.php" class="flex flex-col items-center text-xs font-semibold <?= $currentPage === 'cardapio.php' ? 'text-rose-600' : 'text-slate-500' ?>">
            <i class="fa-solid fa-pizza-slice text-base mb-0.5"></i>
            <span>Cardápio</span>
        </a>
        <a href="<?= BASE_URL ?>/carrinho.php" class="relative flex flex-col items-center text-xs font-semibold <?= $currentPage === 'carrinho.php' ? 'text-rose-600' : 'text-slate-500' ?>">
            <div class="relative">
                <i class="fa-solid fa-bag-shopping text-base mb-0.5"></i>
                <span id="mobile-cart-badge" class="absolute -top-1.5 -right-3 min-w-[16px] h-4 px-1 rounded-full bg-rose-600 text-white text-[9px] font-extrabold flex items-center justify-center">0</span>
            </div>
            <span>Sacola</span>
        </a>
        <a href="<?= BASE_URL ?>/meus_pedidos.php" class="flex flex-col items-center text-xs font-semibold <?= $currentPage === 'meus_pedidos.php' ? 'text-rose-600' : 'text-slate-500' ?>">
            <i class="fa-solid fa-clock-rotate-left text-base mb-0.5"></i>
            <span>Pedidos</span>
        </a>
        <a href="<?= isLoggedIn() ? BASE_URL . '/perfil.php' : BASE_URL . '/login.php' ?>" class="flex flex-col items-center text-xs font-semibold <?= in_array($currentPage, ['perfil.php', 'login.php']) ? 'text-rose-600' : 'text-slate-500' ?>">
            <i class="fa-solid fa-user text-base mb-0.5"></i>
            <span><?= isLoggedIn() ? 'Conta' : 'Entrar' ?></span>
        </a>
    </nav>

    <!-- Componente Modal Unificado (Regra: Tratar mensagens nativas em MODAL) -->
    <?php include __DIR__ . '/modal.php'; ?>

    <!-- Assistente Virtual IA Prisca (Regra: Regras da IA — Site de Pizzaria) -->
    <?php include __DIR__ . '/ai_widget.php'; ?>

    <!-- Variáveis de ambiente Javascript -->
    <script>
        window.PRISCA = {
            baseUrl: '<?= BASE_URL ?>',
            csrfToken: '<?= generateCsrfToken() ?>',
            isLoggedIn: <?= isLoggedIn() ? 'true' : 'false' ?>,
            userRole: '<?= $_SESSION['usuario']['perfil'] ?? 'visitante' ?>'
        };
    </script>

    <!-- Scripts do Sistema -->
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/cart.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/ai_chat.js"></script>

    <!-- Disparo de Toast da Sessão PHP se houver -->
    <?php if ($activeToast): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof showToast === 'function') {
                showToast(
                    <?= json_encode($activeToast['tipo']) ?>,
                    <?= json_encode($activeToast['titulo']) ?>,
                    <?= json_encode($activeToast['mensagem']) ?>
                );
            }
        });
    </script>
    <?php endif; ?>

</body>
</html>
