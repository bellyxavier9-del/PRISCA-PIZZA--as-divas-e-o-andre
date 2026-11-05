/**
 * Prisca Pizza - Gerenciador do Carrinho e Customização de Pedidos
 */

const CART_KEY = 'prisca_pizza_cart_v1';

window.CartManager = {
    getCart: function() {
        try {
            return JSON.parse(localStorage.getItem(CART_KEY)) || [];
        } catch (e) {
            return [];
        }
    },

    saveCart: function(cart) {
        localStorage.setItem(CART_KEY, JSON.stringify(cart));
        this.updateBadges();
    },

    addItem: function(item) {
        const cart = this.getCart();
        cart.push(item);
        this.saveCart(cart);
        if (typeof showToast === 'function') {
            showToast('success', 'Item Adicionado!', `${item.nome} foi adicionado à sua sacola.`);
        }
    },

    removeItem: async function(index) {
        if (typeof window.appConfirm === 'function') {
            const confirmed = await window.appConfirm('Tem certeza que deseja remover este item da sacola?', 'Remover Item', true);
            if (!confirmed) return;
        }

        const cart = this.getCart();
        if (cart[index]) {
            const removed = cart.splice(index, 1);
            this.saveCart(cart);
            if (typeof showToast === 'function') {
                showToast('info', 'Item Removido', `${removed[0]?.nome || 'Item'} removido com sucesso.`);
            }
            if (typeof window.renderCartPage === 'function') {
                window.renderCartPage();
            }
        }
    },

    updateQuantity: function(index, delta) {
        const cart = this.getCart();
        if (!cart[index]) return;

        cart[index].quantidade = (cart[index].quantidade || 1) + delta;
        if (cart[index].quantidade <= 0) {
            this.removeItem(index);
            return;
        }

        cart[index].subtotal = cart[index].quantidade * cart[index].precoUnitario;
        this.saveCart(cart);

        if (typeof window.renderCartPage === 'function') {
            window.renderCartPage();
        }
    },

    clearCart: function() {
        localStorage.removeItem(CART_KEY);
        this.updateBadges();
    },

    getCount: function() {
        const cart = this.getCart();
        return cart.reduce((total, item) => total + (item.quantidade || 1), 0);
    },

    getSubtotal: function() {
        const cart = this.getCart();
        return cart.reduce((total, item) => total + (item.subtotal || (item.precoUnitario * item.quantidade)), 0);
    },

    updateBadges: function() {
        const count = this.getCount();
        const badge = document.getElementById('cart-badge');
        const mobileBadge = document.getElementById('mobile-cart-badge');

        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }
        if (mobileBadge) {
            mobileBadge.textContent = count;
            mobileBadge.style.display = count > 0 ? 'flex' : 'none';
        }
    },

    formatCurrency: function(val) {
        return 'R$ ' + parseFloat(val || 0).toFixed(2).replace('.', ',');
    }
};

// Inicializa os badges ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    window.CartManager.updateBadges();
});
