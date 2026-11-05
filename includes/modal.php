<?php
/**
 * Prisca Pizza - Componente Unificado de Modal Personalizado
 * Atende à regra: 'Sempre trate as mensagens de caixa de mensagens nativas do navegador em um MODAL'
 */
?>
<!-- Estrutura do Modal Global -->
<div id="app-modal" class="fixed inset-0 z-[9998] hidden items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm animate-in fade-in duration-200">
    <div id="app-modal-dialog" class="relative w-full max-w-md transform rounded-3xl bg-white p-6 text-left shadow-2xl transition-all scale-95 opacity-0 duration-200 border border-slate-100">
        
        <!-- Botão Fechar no canto superior -->
        <button type="button" id="app-modal-close" class="absolute top-5 right-5 text-slate-400 hover:text-slate-600 transition-colors p-1 rounded-full hover:bg-slate-100">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>

        <!-- Cabeçalho com Ícone Temático -->
        <div class="flex items-start gap-4">
            <div id="app-modal-icon-container" class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 text-xl">
                <i id="app-modal-icon" class="fa-solid fa-circle-info"></i>
            </div>
            <div class="pt-1">
                <h3 id="app-modal-title" class="text-lg font-bold text-slate-900 leading-tight">Título da Mensagem</h3>
                <p id="app-modal-message" class="mt-2 text-sm text-slate-600 leading-relaxed">Mensagem descritiva detalhada para o usuário.</p>
            </div>
        </div>

        <!-- Área de Input opcional (usado como prompt, ex: motivo de cancelamento) -->
        <div id="app-modal-input-container" class="mt-4 hidden">
            <label id="app-modal-input-label" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Informe os detalhes:</label>
            <textarea id="app-modal-input" rows="3" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none placeholder-slate-400" placeholder="Digite aqui..."></textarea>
        </div>

        <!-- Botões de Ação -->
        <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
            <button type="button" id="app-modal-btn-cancel" class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                Cancelar
            </button>
            <button type="button" id="app-modal-btn-confirm" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-rose-600 text-sm font-semibold text-white hover:bg-rose-700 shadow-md shadow-rose-600/25 transition-all">
                Confirmar
            </button>
        </div>
    </div>
</div>
