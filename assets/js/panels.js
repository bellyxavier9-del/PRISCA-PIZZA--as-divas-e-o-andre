/**
 * Prisca Pizza - Gerenciador dos Painéis Internos e Polling em Tempo Real
 * Atende às regras dos Perfis 2 (Caixa), 3 (Pizzaiolo) e 4 (Entregador)
 */

window.PanelsManager = {
    pollingInterval: null,
    audioAlert: null,

    init: function(panelType) {
        this.panelType = panelType;
        this.startPolling();

        // Inicializa som sutil de novo pedido usando Web Audio API
        this.initAudio();
    },

    initAudio: function() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                this.audioCtx = new AudioContext();
            }
        } catch (e) {
            // Audio context pode ser bloqueado até o primeiro clique do usuário
        }
    },

    playChime: function() {
        if (!this.audioCtx) return;
        try {
            if (this.audioCtx.state === 'suspended') {
                this.audioCtx.resume();
            }
            const osc = this.audioCtx.createOscillator();
            const gain = this.audioCtx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, this.audioCtx.currentTime); // D5
            osc.frequency.exponentialRampToValueAtTime(880, this.audioCtx.currentTime + 0.15); // A5
            gain.gain.setValueAtTime(0.2, this.audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, this.audioCtx.currentTime + 0.3);
            osc.connect(gain);
            gain.connect(this.audioCtx.destination);
            osc.start();
            osc.stop(this.audioCtx.currentTime + 0.35);
        } catch (e) {}
    },

    startPolling: function() {
        if (this.pollingInterval) clearInterval(this.pollingInterval);
        this.fetchOrders();
        // Polling a cada 6 segundos
        this.pollingInterval = setInterval(() => this.fetchOrders(), 6000);
    },

    fetchOrders: async function() {
        try {
            const response = await fetch(`${window.PRISCA.baseUrl}/api/pedidos.php?action=list`);
            const data = await response.json();

            if (data.status === 'success') {
                if (typeof window.renderOrders === 'function') {
                    window.renderOrders(data.pedidos);
                }
            }
        } catch (e) {
            // Erro transitório de rede
        }
    },

    updateOrderStatus: async function(pedidoId, novoStatus, motivo = '') {
        try {
            const response = await fetch(`${window.PRISCA.baseUrl}/api/pedidos.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    pedido_id: pedidoId,
                    novo_status: novoStatus,
                    motivo: motivo
                })
            });

            const data = await response.json();
            if (data.status === 'success') {
                if (typeof showToast === 'function') {
                    showToast('success', 'Status Alterado', data.mensagem);
                }
                this.fetchOrders();
                return true;
            } else {
                if (typeof window.appAlert === 'function') {
                    window.appAlert(data.mensagem || 'Não foi possível alterar o status.', 'Atenção');
                }
                return false;
            }
        } catch (e) {
            if (typeof window.appAlert === 'function') {
                window.appAlert('Erro ao comunicar com o servidor.', 'Erro');
            }
            return false;
        }
    }
};
