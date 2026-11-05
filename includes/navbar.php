<?php
/**
 * Prisca Pizza - Barra de Navegação Responsiva
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';

$usuarioAtual = getLoggedUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200/80 shadow-sm transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">

            <!-- Logomarca -->
            <a href="<?= BASE_URL ?>/index.php" class="flex items-center gap-3 group">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-rose-600 to-prisca-800 flex items-center justify-center text-white shadow-lg shadow-rose-600/25 group-hover:scale-105 transition-transform duration-300">
                    <i class="fa-solid fa-pizza-slice text-2xl group-hover:rotate-12 transition-transform duration-300"></i>
                </div>
                <div>
                    <span class="font-brand text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-1.5">
                        PRISCA <span class="text-rose-600">PIZZA</span>
                    </span>
                    <span class="block text-[10px] tracking-widest uppercase font-semibold text-slate-400">Artesanal & Forno à Lenha</span>
                </div>
            </a>

            <!-- Links Desktop -->
            <nav class="hidden md:flex items-center gap-1">
                <a href="<?= BASE_URL ?>/index.php" 
                   class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors <?= $currentPage === 'index.php' ? 'text-rose-600 bg-rose-50' : 'text-slate-600 hover:text-rose-600 hover:bg-slate-50' ?>">
                   <i class="fa-solid fa-house mr-1.5 text-xs opacity-70"></i> Início
                </a>

                <a href="<?= BASE_URL ?>/cardapio.php" 
                   class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors <?= $currentPage === 'cardapio.php' ? 'text-rose-600 bg-rose-50' : 'text-slate-600 hover:text-rose-600 hover:bg-slate-50' ?>">
                   <i class="fa-solid fa-book-open mr-1.5 text-xs opacity-70"></i> Cardápio
                </a>

                <?php if (isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>/meus_pedidos.php" 
                       class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors <?= $currentPage === 'meus_pedidos.php' ? 'text-rose-600 bg-rose-50' : 'text-slate-600 hover:text-rose-600 hover:bg-slate-50' ?>">
                       <i class="fa-solid fa-clock-rotate-left mr-1.5 text-xs opacity-70"></i> Meus Pedidos
                    </a>
                <?php endif; ?>

                <!-- Links Rápidos de Painéis Internos por Perfil -->
                <?php if ($usuarioAtual): ?>
                    <?php if ($usuarioAtual['perfil'] === ROLE_CAIXA || $usuarioAtual['perfil'] === ROLE_ADMIN): ?>
                        <a href="<?= BASE_URL ?>/painel/caixa.php" 
                           class="px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider bg-amber-500/10 text-amber-600 border border-amber-500/20 hover:bg-amber-500 hover:text-white transition-all">
                           <i class="fa-solid fa-cash-register mr-1"></i> Painel Caixa
                        </a>
                    <?php endif; ?>

                    <?php if ($usuarioAtual['perfil'] === ROLE_PIZZAIOLO || $usuarioAtual['perfil'] === ROLE_ADMIN): ?>
                        <a href="<?= BASE_URL ?>/painel/cozinha.php" 
                           class="px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider bg-rose-500/10 text-rose-600 border border-rose-500/20 hover:bg-rose-500 hover:text-white transition-all">
                           <i class="fa-solid fa-kitchen-set mr-1"></i> Cozinha (KDS)
                        </a>
                    <?php endif; ?>

                    <?php if ($usuarioAtual['perfil'] === ROLE_ENTREGADOR || $usuarioAtual['perfil'] === ROLE_ADMIN): ?>
                        <a href="<?= BASE_URL ?>/painel/entregas.php" 
                           class="px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider bg-blue-500/10 text-blue-600 border border-blue-500/20 hover:bg-blue-500 hover:text-white transition-all">
                           <i class="fa-solid fa-motorcycle mr-1"></i> Entregas
                        </a>
                    <?php endif; ?>

                    <?php if ($usuarioAtual['perfil'] === ROLE_ADMIN || $usuarioAtual['perfil'] === ROLE_CAIXA): ?>
                        <a href="<?= BASE_URL ?>/painel/auditoria.php" 
                           class="px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider bg-purple-500/10 text-purple-600 border border-purple-500/20 hover:bg-purple-500 hover:text-white transition-all" title="Auditoria e Histórico de Logs">
                           <i class="fa-solid fa-clipboard-list mr-1"></i> Auditoria
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>

            <!-- Ações Direitas (Carrinho + Perfil / Login) -->
            <div class="flex items-center gap-3">

                <!-- Botão Carrinho -->
                <a href="<?= BASE_URL ?>/carrinho.php" 
                   class="relative p-2.5 sm:px-4 sm:py-2.5 rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-700 hover:text-rose-600 transition-colors flex items-center gap-2 group" 
                   title="Ver Carrinho">
                    <i class="fa-solid fa-bag-shopping text-lg group-hover:scale-110 transition-transform"></i>
                    <span class="hidden sm:inline text-xs font-bold uppercase tracking-wider">Sacola</span>
                    <span id="cart-badge" class="min-w-[20px] h-5 px-1.5 rounded-full bg-rose-600 text-white text-[11px] font-extrabold flex items-center justify-center shadow-md">0</span>
                </a>

                <?php if (isLoggedIn()): ?>
                    <!-- Dropdown de Usuário Logado -->
                    <div class="relative" id="user-menu-dropdown-wrapper">
                        <button type="button" 
                                id="user-menu-btn"
                                class="flex items-center gap-2 p-1.5 pr-3 rounded-xl hover:bg-slate-100 transition-colors text-left border border-slate-200">
                            <div class="w-8 h-8 rounded-lg bg-rose-600 text-white font-bold flex items-center justify-center text-xs shadow-sm">
                                <?= strtoupper(substr($usuarioAtual['nome'], 0, 1)) ?>
                            </div>
                            <div class="hidden lg:block">
                                <span class="block text-xs font-bold text-slate-800 leading-tight truncate max-w-[120px]"><?= e($usuarioAtual['nome']) ?></span>
                                <span class="block text-[10px] uppercase font-semibold text-rose-600"><?= e($usuarioAtual['perfil']) ?></span>
                            </div>
                            <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
                        </button>

                        <!-- Menu suspenso -->
                        <div id="user-dropdown-menu" 
                             class="hidden absolute right-0 mt-2 w-56 rounded-2xl bg-white shadow-xl border border-slate-100 py-2 z-50 animate-in fade-in slide-in-from-top-2 duration-200">
                            <div class="px-4 py-2 border-b border-slate-100">
                                <p class="text-xs font-semibold text-slate-400">Conectado como</p>
                                <p class="text-sm font-bold text-slate-800 truncate"><?= e($usuarioAtual['nome']) ?></p>
                                <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-rose-100 text-rose-700">
                                    Perfil: <?= e($usuarioAtual['perfil']) ?>
                                </span>
                            </div>

                            <a href="<?= BASE_URL ?>/perfil.php" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-rose-600 transition-colors">
                                <i class="fa-regular fa-user text-slate-400"></i> Meus Dados & Endereço
                            </a>

                            <a href="<?= BASE_URL ?>/meus_pedidos.php" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-rose-600 transition-colors">
                                <i class="fa-solid fa-receipt text-slate-400"></i> Histórico de Pedidos
                            </a>

                            <?php if ($usuarioAtual['perfil'] !== ROLE_CLIENTE): ?>
                                <div class="border-t border-slate-100 my-1"></div>
                                <div class="px-4 py-1 text-[10px] font-bold uppercase text-slate-400 tracking-wider">Painéis Internos</div>
                                <?php if ($usuarioAtual['perfil'] === ROLE_CAIXA || $usuarioAtual['perfil'] === ROLE_ADMIN): ?>
                                    <a href="<?= BASE_URL ?>/painel/caixa.php" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-amber-600 hover:bg-amber-50">
                                        <i class="fa-solid fa-cash-register"></i> Painel do Caixa
                                    </a>
                                <?php endif; ?>
                                <?php if ($usuarioAtual['perfil'] === ROLE_PIZZAIOLO || $usuarioAtual['perfil'] === ROLE_ADMIN): ?>
                                    <a href="<?= BASE_URL ?>/painel/cozinha.php" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">
                                        <i class="fa-solid fa-kitchen-set"></i> Cozinha (KDS)
                                    </a>
                                <?php endif; ?>
                                <?php if ($usuarioAtual['perfil'] === ROLE_ENTREGADOR || $usuarioAtual['perfil'] === ROLE_ADMIN): ?>
                                    <a href="<?= BASE_URL ?>/painel/entregas.php" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-blue-600 hover:bg-blue-50">
                                        <i class="fa-solid fa-motorcycle"></i> Painel de Entregas
                                    </a>
                                <?php endif; ?>
                                <a href="<?= BASE_URL ?>/painel/auditoria.php" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-purple-600 hover:bg-purple-50">
                                    <i class="fa-solid fa-clipboard-list"></i> Auditoria de Logs
                                </a>
                            <?php endif; ?>

                            <div class="border-t border-slate-100 my-1"></div>
                            <a href="<?= BASE_URL ?>/logout.php" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-colors">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i> Sair da Conta
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php" 
                       class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider text-slate-700 hover:text-rose-600 hover:bg-slate-100 transition-colors">
                       Entrar
                    </a>
                    <a href="<?= BASE_URL ?>/cadastro.php" 
                       class="hidden sm:inline-flex px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider bg-rose-600 text-white hover:bg-rose-700 shadow-md shadow-rose-600/25 transition-all">
                       Cadastrar
                    </a>
                <?php endif; ?>

                <!-- Botão Menu Mobile -->
                <button type="button" 
                        id="mobile-menu-toggle"
                        class="md:hidden p-2 rounded-xl text-slate-700 hover:bg-slate-100 transition-colors"
                        aria-label="Abrir menu móvel">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Gaveta Menu Mobile -->
    <div id="mobile-menu-drawer" class="hidden md:hidden border-t border-slate-200 bg-white px-4 pt-3 pb-6 space-y-2 shadow-lg">
        <a href="<?= BASE_URL ?>/index.php" class="block px-3 py-2.5 rounded-xl font-semibold text-sm <?= $currentPage === 'index.php' ? 'text-rose-600 bg-rose-50' : 'text-slate-700' ?>">
            <i class="fa-solid fa-house mr-2 text-rose-500"></i> Início
        </a>
        <a href="<?= BASE_URL ?>/cardapio.php" class="block px-3 py-2.5 rounded-xl font-semibold text-sm <?= $currentPage === 'cardapio.php' ? 'text-rose-600 bg-rose-50' : 'text-slate-700' ?>">
            <i class="fa-solid fa-book-open mr-2 text-rose-500"></i> Cardápio Completo
        </a>
        <a href="<?= BASE_URL ?>/carrinho.php" class="block px-3 py-2.5 rounded-xl font-semibold text-sm <?= $currentPage === 'carrinho.php' ? 'text-rose-600 bg-rose-50' : 'text-slate-700' ?>">
            <i class="fa-solid fa-bag-shopping mr-2 text-rose-500"></i> Meu Carrinho
        </a>
        <?php if (isLoggedIn()): ?>
            <a href="<?= BASE_URL ?>/meus_pedidos.php" class="block px-3 py-2.5 rounded-xl font-semibold text-sm <?= $currentPage === 'meus_pedidos.php' ? 'text-rose-600 bg-rose-50' : 'text-slate-700' ?>">
                <i class="fa-solid fa-receipt mr-2 text-rose-500"></i> Meus Pedidos
            </a>
            <a href="<?= BASE_URL ?>/perfil.php" class="block px-3 py-2.5 rounded-xl font-semibold text-sm text-slate-700">
                <i class="fa-solid fa-user-gear mr-2 text-rose-500"></i> Meus Dados & Endereço
            </a>
            <?php if ($usuarioAtual['perfil'] !== ROLE_CLIENTE): ?>
                <div class="pt-2 border-t border-slate-100">
                    <p class="text-[11px] font-bold uppercase text-slate-400 px-3 pb-1">Painéis Internos</p>
                    <?php if ($usuarioAtual['perfil'] === ROLE_CAIXA || $usuarioAtual['perfil'] === ROLE_ADMIN): ?>
                        <a href="<?= BASE_URL ?>/painel/caixa.php" class="block px-3 py-2 rounded-lg font-bold text-xs text-amber-600">
                            <i class="fa-solid fa-cash-register mr-2"></i> Painel do Caixa
                        </a>
                    <?php endif; ?>
                    <?php if ($usuarioAtual['perfil'] === ROLE_PIZZAIOLO || $usuarioAtual['perfil'] === ROLE_ADMIN): ?>
                        <a href="<?= BASE_URL ?>/painel/cozinha.php" class="block px-3 py-2 rounded-lg font-bold text-xs text-rose-600">
                            <i class="fa-solid fa-kitchen-set mr-2"></i> Cozinha (KDS)
                        </a>
                    <?php endif; ?>
                    <?php if ($usuarioAtual['perfil'] === ROLE_ENTREGADOR || $usuarioAtual['perfil'] === ROLE_ADMIN): ?>
                        <a href="<?= BASE_URL ?>/painel/entregas.php" class="block px-3 py-2 rounded-lg font-bold text-xs text-blue-600">
                            <i class="fa-solid fa-motorcycle mr-2"></i> Painel de Entregas
                        </a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/painel/auditoria.php" class="block px-3 py-2 rounded-lg font-bold text-xs text-purple-600">
                        <i class="fa-solid fa-clipboard-list mr-2"></i> Auditoria de Logs
                    </a>
                </div>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/logout.php" class="block px-3 py-2 rounded-xl font-semibold text-sm text-rose-600 pt-2 border-t border-slate-100">
                <i class="fa-solid fa-arrow-right-from-bracket mr-2"></i> Sair da Conta
            </a>
        <?php else: ?>
            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100">
                <a href="<?= BASE_URL ?>/login.php" class="text-center py-2.5 rounded-xl border border-slate-200 font-bold text-xs uppercase text-slate-700">Entrar</a>
                <a href="<?= BASE_URL ?>/cadastro.php" class="text-center py-2.5 rounded-xl bg-rose-600 text-white font-bold text-xs uppercase shadow">Cadastrar</a>
            </div>
        <?php endif; ?>
    </div>
</header>
