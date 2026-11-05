/**
 * Prisca Pizza - Assistente Virtual Inteligente (Frontend)
 * Comunicação com a API de IA com base nas 7 Regras da Pizzaria
 */

document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('ai-widget-toggle');
    const closeBtn = document.getElementById('ai-chat-close');
    const chatWindow = document.getElementById('ai-chat-window');
    const chatForm = document.getElementById('ai-chat-form');
    const userInput = document.getElementById('ai-user-input');
    const messagesContainer = document.getElementById('ai-messages-container');
    const typingIndicator = document.getElementById('ai-typing-indicator');

    if (!toggleBtn || !chatWindow) return;

    // Abrir/Fechar janela
    toggleBtn.addEventListener('click', function() {
        chatWindow.classList.toggle('hidden');
        chatWindow.classList.toggle('flex');
        if (!chatWindow.classList.contains('hidden')) {
            userInput?.focus();
            scrollToBottom();
        }
    });

    closeBtn?.addEventListener('click', function() {
        chatWindow.classList.add('hidden');
        chatWindow.classList.remove('flex');
    });

    // Chips de sugestão rápida
    document.addEventListener('click', function(e) {
        const chip = e.target.closest('.ai-chip');
        if (chip) {
            const prompt = chip.getAttribute('data-prompt');
            if (prompt) {
                sendUserMessage(prompt);
            }
        }
    });

    // Envio do formulário
    chatForm?.addEventListener('submit', function(e) {
        e.preventDefault();
        const text = userInput.value.trim();
        if (!text) return;
        sendUserMessage(text);
        userInput.value = '';
    });

    function scrollToBottom() {
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }

    function appendUserMessage(text) {
        const div = document.createElement('div');
        div.className = 'flex items-start justify-end gap-2.5';
        div.innerHTML = `
            <div class="bg-rose-600 text-white p-3 rounded-2xl rounded-tr-sm shadow-sm max-w-[85%] text-xs leading-relaxed">
                <p>${escapeHtml(text)}</p>
            </div>
            <div class="w-7 h-7 rounded-xl bg-slate-800 text-white flex items-center justify-center text-[11px] shrink-0 font-bold">
                <i class="fa-solid fa-user"></i>
            </div>
        `;
        messagesContainer.appendChild(div);
        scrollToBottom();
    }

    function appendBotMessage(text) {
        const div = document.createElement('div');
        div.className = 'flex items-start gap-2.5 animate-in fade-in duration-200';
        div.innerHTML = `
            <div class="w-7 h-7 rounded-xl bg-rose-600 text-white flex items-center justify-center text-[11px] shrink-0">
                <i class="fa-solid fa-robot"></i>
            </div>
            <div class="bg-white p-3.5 rounded-2xl rounded-tl-sm shadow-sm border border-slate-100 max-w-[88%] text-slate-800 text-xs leading-relaxed space-y-2">
                ${formatBotResponse(text)}
            </div>
        `;
        messagesContainer.appendChild(div);
        scrollToBottom();
    }

    function escapeHtml(string) {
        return String(string)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatBotResponse(text) {
        // Converte quebras de linha e formata negritos simples
        let formatted = escapeHtml(text);
        formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        formatted = formatted.replace(/\n\n/g, '</p><p>');
        formatted = formatted.replace(/\n/g, '<br>');
        return `<p>${formatted}</p>`;
    }

    async function sendUserMessage(text) {
        appendUserMessage(text);

        // Exibe indicador de carregamento
        typingIndicator?.classList.remove('hidden');
        typingIndicator?.classList.add('flex');
        scrollToBottom();

        try {
            const response = await fetch(`${window.PRISCA.baseUrl}/api/ai_chat.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    mensagem: text,
                    csrf_token: window.PRISCA.csrfToken
                })
            });

            const data = await response.json();
            typingIndicator?.classList.add('hidden');
            typingIndicator?.classList.remove('flex');

            if (data.status === 'success') {
                appendBotMessage(data.resposta);
            } else {
                appendBotMessage(data.mensagem || 'Desculpe, ocorreu uma instabilidade momentânea. Por favor, tente novamente.');
            }
        } catch (err) {
            typingIndicator?.classList.add('hidden');
            typingIndicator?.classList.remove('flex');
            appendBotMessage('Não foi possível conectar com o servidor no momento. Por favor, verifique sua conexão.');
        }
    }
});
