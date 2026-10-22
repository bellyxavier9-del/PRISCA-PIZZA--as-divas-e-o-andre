/**
 * Prisca Pizza - Frontend JavaScript Core
 * Gerenciamento de Carrinho, Modais, Toasts, Polling e Chat IA
 */

// 1. GERENCIAMENTO DE CARRINHO (LocalStorage)
const CartManager = {
    KEY: 'prisca_pizza_cart',

    getItems() {
        try {
            const raw = localStorage.getItem(this.KEY);
            return raw ? JSON.parse(raw) : [];
        } catch (e) {
            console.error('Erro ao ler carrinho:', e);
            return [];
        }
    },

    saveItems(items) {
        localStorage.setItem(this.KEY, JSON.stringify(items));
        this.updateBadge();
        window.dispatchEvent(new CustomEvent('cartUpdated', { detail: items }));
    },

    addItem(item) {
        const items = this.getItems();
        // Gera um hash único baseado no produto e nas customizações
        const itemSignature = `${item.product_id}_${item.size || ''}_${item.flavor2_name || ''}_${item.crust || ''}_${(item.extras || []).sort().join('-')}_${item.observation || ''}`;
        
        const existingIndex = items.findIndex(i => i.signature === itemSignature);
        if (existingIndex > -1) {
            items[existingIndex].quantity += (item.quantity || 1);
            items[existingIndex].total_price = items[existingIndex].unit_price * items[existingIndex].quantity;
        } else {
            item.signature = itemSignature;
            item.quantity = item.quantity || 1;
            item.total_price = item.unit_price * item.quantity;
            items.push(item);
        }

        this.saveItems(items);
        if (typeof showToast === 'function') {
            showToast(`"${item.product_name}" adicionado ao carrinho! 🍕`, 'success');
        }
    },

    removeItem(index) {
        const items = this.getItems();
        if (index >= 0 && index < items.length) {
            const removed = items.splice(index, 1);
            this.saveItems(items);
            if (typeof showToast === 'function') {
                showToast(`"${removed[0].product_name}" removido do carrinho.`, 'info');
            }
        }
    },

    updateQuantity(index, newQty) {
        const items = this.getItems();
        if (index >= 0 && index < items.length) {
            if (newQty <= 0) {
                this.removeItem(index);
            } else {
                items[index].quantity = newQty;
                items[index].total_price = items[index].unit_price * newQty;
                this.saveItems(items);
            }
        }
    },

    clearCart() {
        localStorage.removeItem(this.KEY);
        this.updateBadge();
        window.dispatchEvent(new CustomEvent('cartUpdated', { detail: [] }));
    },

    getSubtotal() {
        const items = this.getItems();
        return items.reduce((sum, item) => sum + (Number(item.total_price) || 0), 0);
    },

    getItemCount() {
        const items = this.getItems();
        return items.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0);
    },

    updateBadge() {
        const count = this.getItemCount();
        const badges = document.querySelectorAll('.cart-badge');
        badges.forEach(b => {
            b.textContent = count;
            b.style.display = count > 0 ? 'inline-flex' : 'none';
        });
    }
};

// 2. HELPER DE POLLING SIMPLES PARA PAINÉIS
// Requisito: "Os painéis internos devem buscar novos pedidos e mudanças de status automaticamente a cada poucos segundos, de forma simples, sem exigir que a página seja recarregada."
class DashboardPoller {
    constructor(url, callback, intervalMs = 5000) {
        this.url = url;
        this.callback = callback;
        this.intervalMs = intervalMs;
        this.timer = null;
        this.isPolling = false;
    }

    start() {
        if (this.isPolling) return;
        this.isPolling = true;
        this.poll();
        this.timer = setInterval(() => this.poll(), this.intervalMs);
    }

    stop() {
        this.isPolling = false;
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }

    async poll() {
        try {
            const res = await fetch(this.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (res.status === 401 || res.status === 403) {
                console.warn('Sessão expirada no polling. Redirecionando...');
                window.location.href = 'login.php';
                return;
            }
            const data = await res.json();
            if (data.success && typeof this.callback === 'function') {
                this.callback(data);
            }
        } catch (err) {
            console.warn('Erro ao atualizar pedidos no painel:', err);
        }
    }
}

// 3. WIDGET DO CHATBOT IA PRISCA
const PriscaAIWidget = {
    isOpen: false,

    init() {
        const toggleBtn = document.getElementById('ai-chat-toggle');
        const closeBtn = document.getElementById('ai-chat-close');
        const sendBtn = document.getElementById('ai-chat-send');
        const input = document.getElementById('ai-chat-input');
        const container = document.getElementById('ai-chat-window');

        if (!toggleBtn || !container) return;

        toggleBtn.addEventListener('click', () => this.toggle());
        if (closeBtn) closeBtn.addEventListener('click', () => this.close());

        if (sendBtn && input) {
            sendBtn.addEventListener('click', () => this.sendMessage());
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        }
    },

    toggle() {
        this.isOpen ? this.close() : this.open();
    },

    open() {
        const container = document.getElementById('ai-chat-window');
        const input = document.getElementById('ai-chat-input');
        if (!container) return;

        container.classList.remove('hidden');
        requestAnimationFrame(() => {
            container.classList.remove('scale-90', 'opacity-0');
            container.classList.add('scale-100', 'opacity-100');
        });
        this.isOpen = true;
        if (input) setTimeout(() => input.focus(), 200);
    },

    close() {
        const container = document.getElementById('ai-chat-window');
        if (!container) return;

        container.classList.remove('scale-100', 'opacity-100');
        container.classList.add('scale-90', 'opacity-0');
        setTimeout(() => container.classList.add('hidden'), 200);
        this.isOpen = false;
    },

    async sendMessage(customText = null) {
        const input = document.getElementById('ai-chat-input');
        const messagesBox = document.getElementById('ai-chat-messages');
        const text = customText || (input ? input.value.trim() : '');

        if (!text) return;
        if (input && !customText) input.value = '';

        // Exibe mensagem do usuário
        this.appendMessage('user', text);

        // Indicador de digitação
        const typingId = this.showTypingIndicator();

        try {
            const res = await fetch('api/ai_chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text })
            });
            const data = await res.json();
            this.removeTypingIndicator(typingId);

            if (data.reply) {
                this.appendMessage('bot', data.reply, data.action_buttons);
            } else {
                this.appendMessage('bot', 'Desculpe, tive uma oscilação momentânea. Como posso te ajudar?');
            }
        } catch (err) {
            this.removeTypingIndicator(typingId);
            this.appendMessage('bot', 'No momento não foi possível conectar à assistente. Por favor, tente novamente em instantes.');
        }
    },

    appendMessage(sender, text, actionButtons = []) {
        const box = document.getElementById('ai-chat-messages');
        if (!box) return;

        const row = document.createElement('div');
        row.className = `flex items-start space-x-2 my-3 ${sender === 'user' ? 'justify-end' : 'justify-start'}`;

        // Converte quebras de linha e negrito simples (* ou **) para HTML seguro
        let formatted = text
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/\n/g, '<br>');

        if (sender === 'user') {
            row.innerHTML = `
                <div class="bg-red-600 text-white rounded-2xl rounded-tr-none px-4 py-2.5 max-w-[85%] text-sm shadow-md leading-relaxed break-words">
                    ${formatted}
                </div>
            `;
        } else {
            let buttonsHtml = '';
            if (actionButtons && actionButtons.length > 0) {
                buttonsHtml = '<div class="mt-3 flex flex-wrap gap-1.5 pt-2 border-t border-red-100">';
                actionButtons.forEach(btn => {
                    if (btn.action === 'url') {
                        buttonsHtml += `<a href="${btn.value}" class="text-xs bg-red-100 hover:bg-red-200 text-red-800 font-medium px-2.5 py-1.5 rounded-lg transition inline-flex items-center space-x-1"><i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> <span>${btn.label}</span></a>`;
                    } else {
                        buttonsHtml += `<button type="button" onclick="PriscaAIWidget.sendMessage('${btn.value.replace(/'/g, "\\'")}')" class="text-xs bg-amber-100 hover:bg-amber-200 text-amber-900 font-medium px-2.5 py-1.5 rounded-lg transition text-left">${btn.label}</button>`;
                    }
                });
                buttonsHtml += '</div>';
            }

            row.innerHTML = `
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-red-600 to-amber-500 text-white flex items-center justify-center text-xs shrink-0 shadow">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <div class="bg-white text-slate-800 border border-amber-200/60 rounded-2xl rounded-tl-none px-4 py-2.5 max-w-[85%] text-sm shadow-sm leading-relaxed break-words">
                    ${formatted}
                    ${buttonsHtml}
                </div>
            `;
        }

        box.appendChild(row);
        box.scrollTop = box.scrollHeight;
    },

    showTypingIndicator() {
        const box = document.getElementById('ai-chat-messages');
        if (!box) return null;

        const id = 'typing-' + Date.now();
        const row = document.createElement('div');
        row.id = id;
        row.className = 'flex items-center space-x-2 my-2 text-slate-400 text-xs';
        row.innerHTML = `
            <div class="w-8 h-8 rounded-full bg-amber-500/20 text-amber-700 flex items-center justify-center text-xs shrink-0">
                <i class="fa-solid fa-robot animate-pulse"></i>
            </div>
            <div class="bg-slate-100 rounded-xl px-3 py-2 text-slate-500 italic flex items-center space-x-1">
                <span>Prisca IA digitando</span>
                <span class="animate-bounce">.</span>
                <span class="animate-bounce delay-100">.</span>
                <span class="animate-bounce delay-200">.</span>
            </div>
        `;
        box.appendChild(row);
        box.scrollTop = box.scrollHeight;
        return id;
    },

    removeTypingIndicator(id) {
        if (!id) return;
        const el = document.getElementById(id);
        if (el) el.remove();
    }
};

// Inicialização ao carregar o DOM
document.addEventListener('DOMContentLoaded', () => {
    CartManager.updateBadge();
    PriscaAIWidget.init();
});
