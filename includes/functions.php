<?php
/**
 * Prisca Pizza - Funções Utilitárias e de Auditoria
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/bd.php';

/**
 * Formata valor para moeda brasileira (R$)
 */
function formatMoney($val) {
    return 'R$ ' . number_format((float)$val, 2, ',', '.');
}

/**
 * Sanitiza strings para saída segura em HTML
 */
function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/**
 * Registra log de auditoria no banco de dados (Regra de Negócio CORE)
 *
 * @param int|null $pedidoId
 * @param string $acao (ex: 'status_mudou', 'pedido_criado', 'login_sucesso', 'login_falha')
 * @param string|null $statusAnterior
 * @param string|null $statusNovo
 * @param string|null $detalhes
 * @return bool
 */
function registrarAuditoria($pedidoId, $acao, $statusAnterior = null, $statusNovo = null, $detalhes = null) {
    try {
        $pdo = getDbConnection();

        $usuarioId = $_SESSION['usuario']['id'] ?? null;
        $usuarioNome = $_SESSION['usuario']['nome'] ?? 'Sistema / Visitante';
        $usuarioPerfil = $_SESSION['usuario']['perfil'] ?? 'publico';

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }

        $sql = "INSERT INTO logs_auditoria (pedido_id, usuario_id, usuario_nome, usuario_perfil, acao, status_anterior, status_novo, detalhes, ip, criado_em)
                VALUES (:pedido_id, :usuario_id, :usuario_nome, :usuario_perfil, :acao, :status_anterior, :status_novo, :detalhes, :ip, NOW())";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':pedido_id' => $pedidoId,
            ':usuario_id' => $usuarioId,
            ':usuario_nome' => $usuarioNome,
            ':usuario_perfil' => $usuarioPerfil,
            ':acao' => $acao,
            ':status_anterior' => $statusAnterior,
            ':status_novo' => $statusNovo,
            ':detalhes' => $detalhes,
            ':ip' => substr($ip, 0, 45)
        ]);
    } catch (Exception $e) {
        error_log("Erro ao registrar auditoria: " . $e->getMessage());
        return false;
    }
}

/**
 * Define uma notificação Toast na sessão
 *
 * @param string $tipo ('success', 'error', 'info', 'warning')
 * @param string $titulo
 * @param string $mensagem
 */
function setToast($tipo, $titulo, $mensagem) {
    $_SESSION['toast'] = [
        'tipo' => $tipo,
        'titulo' => $titulo,
        'mensagem' => $mensagem
    ];
}

/**
 * Resgata e consome a notificação Toast ativa
 */
function getToast() {
    if (isset($_SESSION['toast'])) {
        $toast = $_SESSION['toast'];
        unset($_SESSION['toast']);
        return $toast;
    }
    return null;
}

/**
 * Gera token CSRF para proteção de formulários
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida o token CSRF enviado
 */
function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Gera badge estilizado do Tailwind para o status do pedido
 */
function getStatusBadge($status) {
    switch ($status) {
        case STATUS_RECEBIDO:
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/15 text-amber-600 border border-amber-500/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Recebido
                    </span>';
        case STATUS_EM_PREPARO:
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-500/15 text-blue-600 border border-blue-500/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span> Em preparo
                    </span>';
        case STATUS_PRONTO:
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-purple-500/15 text-purple-600 border border-purple-500/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span> Pronto
                    </span>';
        case STATUS_SAIU_ENTREGA:
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/15 text-indigo-600 border border-indigo-500/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-bounce"></span> Saiu para entrega
                    </span>';
        case STATUS_ENTREGUE:
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/15 text-emerald-600 border border-emerald-500/30">
                        <i class="fa-solid fa-check text-[10px]"></i> Entregue
                    </span>';
        case STATUS_CANCELADO:
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/15 text-rose-600 border border-rose-500/30">
                        <i class="fa-solid fa-xmark text-[10px]"></i> Cancelado
                    </span>';
        default:
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">' . e($status) . '</span>';
    }
}

/**
 * Retorna o progresso percentual baseado no status do pedido
 */
function getStatusProgressPercentage($status) {
    switch ($status) {
        case STATUS_RECEBIDO:
            return 20;
        case STATUS_EM_PREPARO:
            return 45;
        case STATUS_PRONTO:
            return 70;
        case STATUS_SAIU_ENTREGA:
            return 90;
        case STATUS_ENTREGUE:
            return 100;
        case STATUS_CANCELADO:
            return 0;
        default:
            return 0;
    }
}
