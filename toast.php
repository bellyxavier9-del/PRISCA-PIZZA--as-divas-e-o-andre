<!-- Prisca Pizza - Toast Notification System (Tailwind CSS) -->
<div id="toast-container" class="fixed top-5 right-5 z-50 flex flex-col space-y-3 pointer-events-none max-w-sm w-full px-4"></div>

<script>
/**
 * Toast Notification Function
 * Requisito: "Retorne sempre as mensagens de erros de forma claras na interface para o usuário (TOAST)"
 */
function showToast(message, type = 'info', duration = 4000) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'pointer-events-auto flex items-start p-4 rounded-xl shadow-2xl transition-all duration-300 transform translate-x-full opacity-0 text-white font-medium text-sm space-x-3 ';

    let bgClass = 'bg-slate-800';
    let iconClass = 'fa-info-circle text-blue-400';

    if (type === 'success') {
        bgClass = 'bg-emerald-700 border-l-4 border-emerald-400';
        iconClass = 'fa-check-circle text-emerald-200';
    } else if (type === 'error' || type === 'danger') {
        bgClass = 'bg-red-700 border-l-4 border-red-400';
        iconClass = 'fa-circle-exclamation text-red-200';
    } else if (type === 'warning') {
        bgClass = 'bg-amber-700 border-l-4 border-amber-400';
        iconClass = 'fa-triangle-exclamation text-amber-200';
    } else {
        bgClass = 'bg-slate-800 border-l-4 border-blue-400';
    }

    toast.className += bgClass;
    toast.innerHTML = `
        <div class="pt-0.5 text-lg">
            <i class="fa-solid ${iconClass}"></i>
        </div>
        <div class="flex-1 break-words leading-snug">
            ${message}
        </div>
        <button type="button" class="text-white/70 hover:text-white transition ml-2 -mr-1" onclick="this.parentElement.remove()">
            <i class="fa-solid fa-xmark"></i>
        </button>
    `;

    container.appendChild(toast);

    // Animação de entrada
    requestAnimationFrame(() => {
        toast.classList.remove('translate-x-full', 'opacity-0');
        toast.classList.add('translate-x-0', 'opacity-100');
    });

    // Auto fechar
    setTimeout(() => {
        toast.classList.remove('translate-x-0', 'opacity-100');
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// Verifica se há flash message em PHP
document.addEventListener('DOMContentLoaded', () => {
    <?php if (isset($_SESSION['flash_success'])): ?>
        showToast(<?= json_encode($_SESSION['flash_success']) ?>, 'success');
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        showToast(<?= json_encode($_SESSION['flash_error']) ?>, 'error');
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
});
</script>
