<?php
/**
 * Prisca Pizza - Assistente Virtual Inteligente (Prisca AI)
 * Atende às "Regras da IA — Site de Pizzaria"
 */
?>
<!-- Botão Flutuante da IA Assistente -->
<div class="fixed bottom-20 md:bottom-6 right-6 z-40">
    <button type="button" 
            id="ai-widget-toggle" 
            class="group relative flex items-center gap-3 px-4 py-3 rounded-full bg-gradient-to-r from-rose-600 via-rose-700 to-prisca-800 text-white shadow-2xl shadow-rose-600/40 hover:scale-105 active:scale-95 transition-all duration-300 border border-white/20">
        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-sm relative">
            <i class="fa-solid fa-robot"></i>
            <span class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-emerald-400 border-2 border-white animate-pulse"></span>
        </div>
        <div class="text-left hidden sm:block">
            <p class="text-xs font-bold leading-tight flex items-center gap-1.5">
                Prisca AI <span class="text-[9px] px-1.5 py-0.2 rounded bg-white/25 uppercase font-extrabold">Online</span>
            </p>
            <p class="text-[10px] text-rose-100 font-medium">Tire dúvidas sobre o cardápio e pedidos</p>
        </div>
        <span class="sm:hidden text-xs font-bold">Assistente IA</span>
    </button>
</div>

<!-- Janela de Chat da Prisca AI -->
<div id="ai-chat-window" 
     class="fixed bottom-20 md:bottom-24 right-4 sm:right-6 z-50 hidden w-[92vw] sm:w-[400px] h-[550px] max-h-[80vh] flex-col rounded-3xl bg-white shadow-2xl border border-slate-200 overflow-hidden transition-all duration-300 animate-in fade-in slide-in-from-bottom-5">
    
    <!-- Cabeçalho do Chat -->
    <div class="px-5 py-4 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white flex items-center justify-between border-b border-slate-700/50">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-rose-600 text-white flex items-center justify-center shadow-md shadow-rose-600/30 text-base">
                <i class="fa-solid fa-pizza-slice"></i>
            </div>
            <div>
                <h4 class="text-sm font-bold flex items-center gap-1.5">
                    Assistente Prisca Pizza
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                </h4>
                <p class="text-[11px] text-slate-300">Inteligência especializada em nosso cardápio</p>
            </div>
        </div>
        <button type="button" id="ai-chat-close" class="text-slate-400 hover:text-white p-1.5 rounded-xl hover:bg-white/10 transition-colors">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>
    </div>

    <!-- Regras e Aviso de Segurança -->
    <div class="bg-amber-500/10 border-b border-amber-500/20 px-4 py-1.5 text-[11px] text-amber-700 flex items-center gap-2">
        <i class="fa-solid fa-shield-halved text-amber-600"></i>
        <span>Atendimento oficial com base estrita nos dados cadastrados.</span>
    </div>

    <!-- Mensagens (Scrollable) -->
    <div id="ai-messages-container" class="flex-1 p-4 overflow-y-auto space-y-3.5 bg-slate-50/70 text-xs">
        <!-- Boas-vindas inicial -->
        <div class="flex items-start gap-2.5">
            <div class="w-7 h-7 rounded-xl bg-rose-600 text-white flex items-center justify-center text-[11px] shrink-0">
                <i class="fa-solid fa-robot"></i>
            </div>
            <div class="bg-white p-3.5 rounded-2xl rounded-tl-sm shadow-sm border border-slate-100 max-w-[85%] text-slate-800 space-y-2">
                <p>Olá! Seja muito bem-vindo(a) à <strong>Prisca Pizza</strong>! 🍕</p>
                <p>Eu sou a sua assistente virtual. Como posso te ajudar hoje? Posso tirar dúvidas sobre os sabores de pizzas e esfihas, tamanhos, adicionais, tempo de entrega ou consultar o status de um pedido seu!</p>
            </div>
        </div>

        <!-- Sugestões Rápidas -->
        <div id="ai-quick-chips" class="pt-2 flex flex-wrap gap-1.5">
            <button type="button" class="ai-chip px-2.5 py-1.5 rounded-xl bg-white hover:bg-rose-50 text-slate-700 hover:text-rose-600 border border-slate-200 text-[11px] font-medium transition-colors text-left" data-prompt="Quais os sabores de pizzas mais pedidos?">
                🍕 Sabores de pizza mais pedidos
            </button>
            <button type="button" class="ai-chip px-2.5 py-1.5 rounded-xl bg-white hover:bg-rose-50 text-slate-700 hover:text-rose-600 border border-slate-200 text-[11px] font-medium transition-colors text-left" data-prompt="Quais os tamanhos e fatias de pizza disponíveis?">
                📏 Tamanhos e quantas fatias
            </button>
            <button type="button" class="ai-chip px-2.5 py-1.5 rounded-xl bg-white hover:bg-rose-50 text-slate-700 hover:text-rose-600 border border-slate-200 text-[11px] font-medium transition-colors text-left" data-prompt="Qual o horário de funcionamento e valor da taxa de entrega?">
                🛵 Horário e taxa de entrega
            </button>
            <button type="button" class="ai-chip px-2.5 py-1.5 rounded-xl bg-white hover:bg-rose-50 text-slate-700 hover:text-rose-600 border border-slate-200 text-[11px] font-medium transition-colors text-left" data-prompt="Quais as formas de pagamento aceitas?">
                💳 Formas de pagamento
            </button>
        </div>
    </div>

    <!-- Indicador de Digitação -->
    <div id="ai-typing-indicator" class="hidden px-5 py-2 bg-slate-50 text-[11px] text-slate-500 items-center gap-2 border-t border-slate-100">
        <span class="flex gap-1">
            <span class="w-1.5 h-1.5 rounded-full bg-rose-600 animate-bounce"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-rose-600 animate-bounce [animation-delay:0.2s]"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-rose-600 animate-bounce [animation-delay:0.4s]"></span>
        </span>
        <span>Prisca AI está consultando o sistema...</span>
    </div>

    <!-- Formulário de Input do Chat -->
    <form id="ai-chat-form" class="p-3 bg-white border-t border-slate-200 flex items-center gap-2">
        <input type="text" 
               id="ai-user-input" 
               placeholder="Pergunte sobre sabores, status (#PRISCA...)..." 
               class="flex-1 px-4 py-2.5 text-xs bg-slate-100 rounded-2xl border border-transparent focus:border-rose-400 focus:bg-white focus:outline-none transition-all placeholder-slate-400">
        <button type="submit" 
                class="w-10 h-10 rounded-2xl bg-rose-600 text-white flex items-center justify-center hover:bg-rose-700 transition-colors shadow-md shadow-rose-600/30 shrink-0">
            <i class="fa-solid fa-paper-plane text-xs"></i>
        </button>
    </form>
</div>
