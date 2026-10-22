<!-- Prisca Pizza - Modal Dialog Component (Replaces native alert/confirm/prompt) -->
<!-- Requisito: "Sempre trate as mensagens de caixa de mensagens nativas do navegador em um MODAL" -->

<div id="app-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-200">
    <div id="app-modal-card" class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden transform scale-95 transition-transform duration-200 border border-amber-100">
        <!-- Header -->
        <div id="app-modal-header" class="px-6 py-4 bg-gradient-to-r from-red-600 to-amber-600 text-white flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i id="app-modal-icon" class="fa-solid fa-circle-info text-xl"></i>
                <h3 id="app-modal-title" class="font-bold text-lg">Aviso</h3>
            </div>
            <button type="button" onclick="closeAppModal(null)" class="text-white/80 hover:text-white transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6">
            <p id="app-modal-body" class="text-slate-700 text-base leading-relaxed whitespace-pre-line"></p>
            
            <!-- Prompt Input (se aplicável) -->
            <div id="app-modal-input-container" class="mt-4 hidden">
                <input type="text" id="app-modal-input" class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:outline-none text-slate-800" placeholder="Digite aqui...">
            </div>
        </div>

        <!-- Footer -->
        <div id="app-modal-footer" class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-end space-x-3">
            <button type="button" id="app-modal-btn-cancel" onclick="closeAppModal(false)" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-200 transition font-medium text-sm hidden">
                Cancelar
            </button>
            <button type="button" id="app-modal-btn-confirm" onclick="closeAppModal(true)" class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white transition font-medium text-sm shadow-md">
                OK
            </button>
        </div>
    </div>
</div>

<script>
let modalResolve = null;

function showAppModal({ title = 'Aviso', message = '', type = 'info', isConfirm = false, isPrompt = false, defaultValue = '' }) {
    return new Promise((resolve) => {
        modalResolve = resolve;

        const modal = document.getElementById('app-modal');
        const card = document.getElementById('app-modal-card');
        const titleEl = document.getElementById('app-modal-title');
        const bodyEl = document.getElementById('app-modal-body');
        const iconEl = document.getElementById('app-modal-icon');
        const cancelBtn = document.getElementById('app-modal-btn-cancel');
        const confirmBtn = document.getElementById('app-modal-btn-confirm');
        const inputContainer = document.getElementById('app-modal-input-container');
        const inputEl = document.getElementById('app-modal-input');

        titleEl.textContent = title;
        bodyEl.textContent = message;

        // Ícones e cores
        if (type === 'danger' || type === 'error') {
            iconEl.className = 'fa-solid fa-triangle-exclamation text-xl';
            confirmBtn.className = 'px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white transition font-medium text-sm shadow-md';
        } else if (type === 'success') {
            iconEl.className = 'fa-solid fa-circle-check text-xl';
            confirmBtn.className = 'px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white transition font-medium text-sm shadow-md';
        } else {
            iconEl.className = 'fa-solid fa-circle-info text-xl';
            confirmBtn.className = 'px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white transition font-medium text-sm shadow-md';
        }

        // Modos Confirm / Prompt / Alert
        if (isConfirm || isPrompt) {
            cancelBtn.classList.remove('hidden');
            confirmBtn.textContent = isPrompt ? 'Confirmar' : 'Sim, continuar';
        } else {
            cancelBtn.classList.add('hidden');
            confirmBtn.textContent = 'OK';
        }

        if (isPrompt) {
            inputContainer.classList.remove('hidden');
            inputEl.value = defaultValue;
            setTimeout(() => inputEl.focus(), 150);
        } else {
            inputContainer.classList.add('hidden');
        }

        // Abre modal
        modal.classList.remove('opacity-0', 'pointer-events-none');
        modal.classList.add('opacity-100', 'pointer-events-auto');
        card.classList.remove('scale-95');
        card.classList.add('scale-100');
    });
}

function closeAppModal(result) {
    const modal = document.getElementById('app-modal');
    const card = document.getElementById('app-modal-card');
    const inputContainer = document.getElementById('app-modal-input-container');
    const inputEl = document.getElementById('app-modal-input');

    let finalResult = result;
    if (result === true && !inputContainer.classList.contains('hidden')) {
        finalResult = inputEl.value;
    }

    modal.classList.remove('opacity-100', 'pointer-events-auto');
    modal.classList.add('opacity-0', 'pointer-events-none');
    card.classList.remove('scale-100');
    card.classList.add('scale-95');

    if (modalResolve) {
        modalResolve(finalResult);
        modalResolve = null;
    }
}

// Substitutos seguros globais para alert e confirm nativos
window.appAlert = function(message, title = 'Aviso') {
    return showAppModal({ title, message, type: 'info', isConfirm: false });
};

window.appConfirm = function(message, title = 'Confirmação', type = 'warning') {
    return showAppModal({ title, message, type, isConfirm: true });
};

window.appPrompt = function(message, defaultValue = '', title = 'Informação') {
    return showAppModal({ title, message, type: 'info', isPrompt: true, defaultValue });
};
</script>
