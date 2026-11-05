/**
 * Prisca Pizza - Core JavaScript
 * Sistema de Toasts e Modais personalizados (substitui caixas de diálogo nativas)
 */

// -------------------------------------------------------------
// 1. SISTEMA GLOBAL DE NOTIFICAÇÕES (TOAST)
// -------------------------------------------------------------
window.showToast = function(tipo, titulo, mensagem) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'pointer-events-auto flex items-start gap-3 p-4 rounded-2xl bg-white shadow-2xl border toast-enter transition-all duration-300 max-w-sm w-full';

    let iconHtml = '';
    let borderClass = 'border-slate-100';

    switch (tipo) {
        case 'success':
            iconHtml = '<div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0"><i class="fa-solid fa-circle-check"></i></div>';
            borderClass = 'border-emerald-200';
            break;
        case 'error':
            iconHtml = '<div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0"><i class="fa-solid fa-circle-xmark"></i></div>';
            borderClass = 'border-rose-200';
            break;
        case 'warning':
            iconHtml = '<div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0"><i class="fa-solid fa-triangle-exclamation"></i></div>';
            borderClass = 'border-amber-200';
            break;
        default:
            iconHtml = '<div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0"><i class="fa-solid fa-circle-info"></i></div>';
            borderClass = 'border-blue-200';
            break;
    }

    toast.classList.add(borderClass);

    toast.innerHTML = `
        ${iconHtml}
        <div class="flex-1 pt-0.5">
            <h5 class="text-xs font-bold text-slate-900 leading-tight">${titulo || 'Aviso'}</h5>
            <p class="text-xs text-slate-600 mt-1 leading-relaxed">${mensagem || ''}</p>
        </div>
        <button type="button" class="text-slate-400 hover:text-slate-600 transition-colors p-1" onclick="this.parentElement.remove()">
            <i class="fa-solid fa-xmark text-xs"></i>
        </button>
    `;

    container.appendChild(toast);

    // Auto-remover após 4.5 segundos
    setTimeout(() => {
        if (toast.parentElement) {
            toast.classList.remove('toast-enter');
            toast.classList.add('toast-exit');
            setTimeout(() => toast.remove(), 250);
        }
    }, 4500);
};

// -------------------------------------------------------------
// 2. SISTEMA GLOBAL DE MODAL (Substitui confirm() e alert() nativos)
// -------------------------------------------------------------
window.showModal = function(options) {
    return new Promise((resolve) => {
        const modal = document.getElementById('app-modal');
        const dialog = document.getElementById('app-modal-dialog');
        const titleEl = document.getElementById('app-modal-title');
        const messageEl = document.getElementById('app-modal-message');
        const iconContainer = document.getElementById('app-modal-icon-container');
        const iconEl = document.getElementById('app-modal-icon');
        const btnCancel = document.getElementById('app-modal-btn-cancel');
        const btnConfirm = document.getElementById('app-modal-btn-confirm');
        const btnClose = document.getElementById('app-modal-close');
        const inputContainer = document.getElementById('app-modal-input-container');
        const inputField = document.getElementById('app-modal-input');
        const inputLabel = document.getElementById('app-modal-input-label');

        if (!modal) {
            // Fallback se elemento não estiver na tela
            resolve(window.confirm(options.message || options.title));
            return;
        }

        const config = Object.assign({
            title: 'Confirmação',
            message: 'Deseja prosseguir com esta ação?',
            type: 'confirm', // 'confirm', 'alert', 'prompt', 'danger', 'success'
            confirmText: 'Confirmar',
            cancelText: 'Cancelar',
            showInput: false,
            inputLabel: 'Observações / Motivo:',
            inputPlaceholder: 'Digite aqui...'
        }, options);

        titleEl.textContent = config.title;
        messageEl.textContent = config.message;
        btnConfirm.textContent = config.confirmText;
        btnCancel.textContent = config.cancelText;

        // Configura ícone e cores conforme o tipo
        iconContainer.className = 'w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 text-xl';
        btnConfirm.className = 'w-full sm:w-auto px-5 py-2.5 rounded-xl text-sm font-semibold text-white shadow-md transition-all ';

        if (config.type === 'danger') {
            iconContainer.classList.add('bg-rose-100', 'text-rose-600');
            iconEl.className = 'fa-solid fa-triangle-exclamation';
            btnConfirm.classList.add('bg-rose-600', 'hover:bg-rose-700', 'shadow-rose-600/25');
        } else if (config.type === 'success') {
            iconContainer.classList.add('bg-emerald-100', 'text-emerald-600');
            iconEl.className = 'fa-solid fa-circle-check';
            btnConfirm.classList.add('bg-emerald-600', 'hover:bg-emerald-700', 'shadow-emerald-600/25');
        } else if (config.type === 'alert') {
            iconContainer.classList.add('bg-blue-100', 'text-blue-600');
            iconEl.className = 'fa-solid fa-circle-info';
            btnConfirm.classList.add('bg-blue-600', 'hover:bg-blue-700', 'shadow-blue-600/25');
            btnCancel.classList.add('hidden');
        } else {
            iconContainer.classList.add('bg-amber-100', 'text-amber-600');
            iconEl.className = 'fa-solid fa-question';
            btnConfirm.classList.add('bg-rose-600', 'hover:bg-rose-700', 'shadow-rose-600/25');
            btnCancel.classList.remove('hidden');
        }

        if (config.showInput) {
            inputContainer.classList.remove('hidden');
            inputLabel.textContent = config.inputLabel;
            inputField.placeholder = config.inputPlaceholder;
            inputField.value = '';
            setTimeout(() => inputField.focus(), 200);
        } else {
            inputContainer.classList.add('hidden');
        }

        // Abre o modal com transição suave
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            dialog.classList.remove('scale-95', 'opacity-0');
            dialog.classList.add('scale-100', 'opacity-100');
        }, 10);

        function closeModal(result) {
            dialog.classList.remove('scale-100', 'opacity-100');
            dialog.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                btnCancel.classList.remove('hidden');
                resolve(result);
            }, 180);
        }

        btnConfirm.onclick = () => {
            if (config.showInput) {
                closeModal({ confirmed: true, value: inputField.value.trim() });
            } else {
                closeModal(true);
            }
        };

        btnCancel.onclick = () => closeModal(config.showInput ? { confirmed: false, value: '' } : false);
        btnClose.onclick = () => closeModal(config.showInput ? { confirmed: false, value: '' } : false);
    });
};

// -------------------------------------------------------------
// 3. OVERRIDE AMIGÁVEL DOS NATIVOS ALERT E CONFIRM
// -------------------------------------------------------------
window.appAlert = function(mensagem, titulo = 'Aviso') {
    return window.showModal({
        title: titulo,
        message: mensagem,
        type: 'alert',
        confirmText: 'Entendido'
    });
};

window.appConfirm = function(mensagem, titulo = 'Confirmação', isDanger = false) {
    return window.showModal({
        title: titulo,
        message: mensagem,
        type: isDanger ? 'danger' : 'confirm',
        confirmText: isDanger ? 'Sim, Confirmar' : 'Confirmar',
        cancelText: 'Cancelar'
    });
};

// -------------------------------------------------------------
// 4. INTERATIVIDADE DA BARRA SUPERIOR E MENUS
// -------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function() {
    // Menu dropdown do usuário logado
    const userMenuBtn = document.getElementById('user-menu-btn');
    const userDropdown = document.getElementById('user-dropdown-menu');

    if (userMenuBtn && userDropdown) {
        userMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('hidden');
        });

        document.addEventListener('click', function(e) {
            if (!userDropdown.contains(e.target) && !userMenuBtn.contains(e.target)) {
                userDropdown.classList.add('hidden');
            }
        });
    }

    // Menu móvel gaveta
    const mobileToggle = document.getElementById('mobile-menu-toggle');
    const mobileDrawer = document.getElementById('mobile-menu-drawer');

    if (mobileToggle && mobileDrawer) {
        mobileToggle.addEventListener('click', function() {
            mobileDrawer.classList.toggle('hidden');
        });
    }
});
