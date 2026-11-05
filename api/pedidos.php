<?php
/**
 * Prisca Pizza - API de Pedidos e Atualização em Tempo Real (Polling)
 * Validação rigorosa de permissões no servidor (Regra CORE de Segurança)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDbConnection();
$method = $_SERVER['REQUEST_METHOD'];

// -------------------------------------------------------------
// GET: CONSULTAS DE PEDIDOS (COM FILTROS POR PERFIL)
// -------------------------------------------------------------
if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    // 1. Consulta pública/cliente de um pedido específico por código
    if ($action === 'track') {
        $codigo = trim($_GET['codigo'] ?? '');
        if (empty($codigo)) {
            echo json_encode(['status' => 'error', 'mensagem' => 'Código do pedido não informado']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE codigo = :codigo LIMIT 1");
        $stmt->execute([':codigo' => $codigo]);
        $pedido = $stmt->fetch();

        if (!$pedido) {
            echo json_encode(['status' => 'error', 'mensagem' => 'Pedido não encontrado']);
            exit;
        }

        // Se for cliente logado, valida se o pedido pertence a ele
        if (isLoggedIn() && hasRole(ROLE_CLIENTE) && $pedido['cliente_id'] != $_SESSION['usuario']['id']) {
            echo json_encode(['status' => 'error', 'mensagem' => 'Acesso não autorizado a este pedido']);
            exit;
        }

        // Busca itens do pedido
        $stmtItens = $pdo->prepare("SELECT * FROM itens_pedido WHERE pedido_id = :id");
        $stmtItens->execute([':id' => $pedido['id']]);
        $itens = $stmtItens->fetchAll();

        // Busca histórico de auditoria
        $stmtLogs = $pdo->prepare("SELECT acao, status_anterior, status_novo, detalhes, criado_em, usuario_perfil FROM logs_auditoria WHERE pedido_id = :id ORDER BY criado_em ASC");
        $stmtLogs->execute([':id' => $pedido['id']]);
        $logs = $stmtLogs->fetchAll();

        echo json_encode([
            'status' => 'success',
            'pedido' => $pedido,
            'itens' => $itens,
            'historico' => $logs
        ]);
        exit;
    }

    // 2. Consulta de painéis internos (Exige login e perfis específicos)
    requireLogin();
    $usuario = getLoggedUser();

    // Painel do Atendente de Caixa
    if ($usuario['perfil'] === ROLE_CAIXA || $usuario['perfil'] === ROLE_ADMIN) {
        $statusFiltro = $_GET['status'] ?? null;
        $tipoFiltro = $_GET['tipo'] ?? null;

        $sql = "SELECT p.*, u.nome as cliente_nome, u.telefone as cliente_telefone, e.nome as entregador_nome 
                FROM pedidos p 
                JOIN usuarios u ON p.cliente_id = u.id 
                LEFT JOIN usuarios e ON p.entregador_id = e.id 
                WHERE 1=1";
        $params = [];

        if (!empty($statusFiltro)) {
            $sql .= " AND p.status = :status";
            $params[':status'] = $statusFiltro;
        }
        if (!empty($tipoFiltro)) {
            $sql .= " AND p.tipo_entrega = :tipo";
            $params[':tipo'] = $tipoFiltro;
        }

        $sql .= " ORDER BY CASE 
                    WHEN p.status = 'Recebido' THEN 1 
                    WHEN p.status = 'Em preparo' THEN 2 
                    WHEN p.status = 'Pronto' THEN 3 
                    WHEN p.status = 'Saiu para entrega' THEN 4 
                    ELSE 5 END, p.criado_em DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $pedidos = $stmt->fetchAll();

        // Carrega itens resumidos para cada pedido
        foreach ($pedidos as &$ped) {
            $stmtItens = $pdo->prepare("SELECT nome_produto, tamanho_nome, sabor_secundario_nome, quantidade, adicionais_selecionados, observacoes FROM itens_pedido WHERE pedido_id = :id");
            $stmtItens->execute([':id' => $ped['id']]);
            $ped['itens'] = $stmtItens->fetchAll();
        }

        echo json_encode(['status' => 'success', 'pedidos' => $pedidos]);
        exit;
    }

    // Painel do Pizzaiolo (KDS - Somente pedidos em preparo/cozinha, SEM dados financeiros ou pessoais irrelevantes)
    if ($usuario['perfil'] === ROLE_PIZZAIOLO) {
        $sql = "SELECT p.id, p.codigo, p.status, p.tipo_entrega, p.tempo_estimado_min, p.observacoes, p.criado_em 
                FROM pedidos p 
                WHERE p.status IN ('Recebido', 'Em preparo', 'Pronto') 
                ORDER BY CASE WHEN p.status = 'Em preparo' THEN 1 WHEN p.status = 'Recebido' THEN 2 ELSE 3 END, p.criado_em ASC";
        
        $stmt = $pdo->query($sql);
        $pedidos = $stmt->fetchAll();

        foreach ($pedidos as &$ped) {
            $stmtItens = $pdo->prepare("SELECT nome_produto, tamanho_nome, sabor_secundario_nome, quantidade, adicionais_selecionados, observacoes FROM itens_pedido WHERE pedido_id = :id");
            $stmtItens->execute([':id' => $ped['id']]);
            $ped['itens'] = $stmtItens->fetchAll();
        }

        echo json_encode(['status' => 'success', 'pedidos' => $pedidos]);
        exit;
    }

    // Painel do Entregador (Visualiza prontos para pegar + seus pedidos em trânsito)
    if ($usuario['perfil'] === ROLE_ENTREGADOR) {
        $sql = "SELECT p.id, p.codigo, p.status, p.tipo_entrega, p.endereco_entrega, p.telefone_contato, 
                       p.forma_pagamento, p.status_pagamento, p.total, p.troco_para, p.observacoes, p.criado_em,
                       u.nome as cliente_nome, p.entregador_id
                FROM pedidos p 
                JOIN usuarios u ON p.cliente_id = u.id 
                WHERE p.tipo_entrega = 'entrega' 
                  AND (p.status = 'Pronto' OR (p.status = 'Saiu para entrega' AND p.entregador_id = :entregador_id))
                ORDER BY CASE WHEN p.status = 'Saiu para entrega' THEN 1 ELSE 2 END, p.criado_em ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':entregador_id' => $usuario['id']]);
        $pedidos = $stmt->fetchAll();

        echo json_encode(['status' => 'success', 'pedidos' => $pedidos]);
        exit;
    }

    // Perfil Cliente (Apenas os próprios pedidos)
    if ($usuario['perfil'] === ROLE_CLIENTE) {
        $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE cliente_id = :cliente_id ORDER BY criado_em DESC");
        $stmt->execute([':cliente_id' => $usuario['id']]);
        $pedidos = $stmt->fetchAll();

        echo json_encode(['status' => 'success', 'pedidos' => $pedidos]);
        exit;
    }

    echo json_encode(['status' => 'error', 'mensagem' => 'Perfil sem permissão']);
    exit;
}

// -------------------------------------------------------------
// POST: ATUALIZAÇÕES DE STATUS (COM AUDITORIA OBRIGATÓRIA)
// -------------------------------------------------------------
if ($method === 'POST') {
    requireLogin();
    $usuario = getLoggedUser();

    $input = json_decode(file_get_contents('php://input'), true);
    $pedidoId = (int)($input['pedido_id'] ?? 0);
    $novoStatus = trim($input['novo_status'] ?? '');
    $motivo = trim($input['motivo'] ?? '');

    if (!$pedidoId || empty($novoStatus)) {
        echo json_encode(['status' => 'error', 'mensagem' => 'Dados incompletos']);
        exit;
    }

    // Busca o pedido atual
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $pedidoId]);
    $pedido = $stmt->fetch();

    if (!$pedido) {
        echo json_encode(['status' => 'error', 'mensagem' => 'Pedido não encontrado']);
        exit;
    }

    $statusAnterior = $pedido['status'];

    // Validações de Transição Permitidas por Perfil (Segurança Server-side)
    $autorizado = false;
    $extraUpdates = [];
    $extraParams = [];

    // Perfil: Caixa ou Admin
    if ($usuario['perfil'] === ROLE_CAIXA || $usuario['perfil'] === ROLE_ADMIN) {
        if ($novoStatus === STATUS_EM_PREPARO && $statusAnterior === STATUS_RECEBIDO) {
            $autorizado = true; // Encaminhar para cozinha
        } elseif ($novoStatus === STATUS_ENTREGUE && $statusAnterior === STATUS_PRONTO && $pedido['tipo_entrega'] === 'retirada') {
            $autorizado = true; // Conclusão de retirada no balcão
        } elseif ($novoStatus === STATUS_CANCELADO) {
            $autorizado = true; // Cancelamento pelo caixa
            $extraUpdates[] = "motivo_cancelamento = :motivo";
            $extraParams[':motivo'] = $motivo ?: 'Cancelado pelo atendente de caixa.';
        }
    }

    // Perfil: Pizzaiolo
    if ($usuario['perfil'] === ROLE_PIZZAIOLO) {
        if ($novoStatus === STATUS_EM_PREPARO && $statusAnterior === STATUS_RECEBIDO) {
            $autorizado = true; // Iniciar preparo
        } elseif ($novoStatus === STATUS_PRONTO && ($statusAnterior === STATUS_EM_PREPARO || $statusAnterior === STATUS_RECEBIDO)) {
            $autorizado = true; // Marcar como pronto
        }
    }

    // Perfil: Entregador
    if ($usuario['perfil'] === ROLE_ENTREGADOR) {
        if ($novoStatus === STATUS_SAIU_ENTREGA && $statusAnterior === STATUS_PRONTO) {
            $autorizado = true; // Pegar pedido para entrega
            $extraUpdates[] = "entregador_id = :entregador_id";
            $extraParams[':entregador_id'] = $usuario['id'];
        } elseif ($novoStatus === STATUS_ENTREGUE && $statusAnterior === STATUS_SAIU_ENTREGA && $pedido['entregador_id'] == $usuario['id']) {
            $autorizado = true; // Marcar como entregue
            // Se o pagamento era dinheiro na entrega, atualiza status_pagamento para 'pago'
            if ($pedido['forma_pagamento'] === 'dinheiro') {
                $extraUpdates[] = "status_pagamento = 'pago'";
            }
        }
    }

    if (!$autorizado) {
        echo json_encode([
            'status' => 'error', 
            'mensagem' => "Transição de status '{$statusAnterior}' para '{$novoStatus}' não é autorizada para o perfil {$usuario['perfil']}."
        ]);
        exit;
    }

    // Executa a atualização
    $sql = "UPDATE pedidos SET status = :novo_status";
    if (!empty($extraUpdates)) {
        $sql .= ", " . implode(", ", $extraUpdates);
    }
    $sql .= " WHERE id = :id";

    $params = array_merge([':novo_status' => $novoStatus, ':id' => $pedidoId], $extraParams);
    $updateStmt = $pdo->prepare($sql);
    $updateStmt->execute($params);

    // Registra na Auditoria (Regra CORE: Quem fez, quando fez, status anterior e novo)
    $detalhesAuditoria = "Status alterado de '{$statusAnterior}' para '{$novoStatus}' por {$usuario['nome']} ({$usuario['perfil']}).";
    if (!empty($motivo)) {
        $detalhesAuditoria .= " Motivo: {$motivo}";
    }

    registrarAuditoria(
        $pedidoId,
        'status_mudou',
        $statusAnterior,
        $novoStatus,
        $detalhesAuditoria
    );

    echo json_encode([
        'status' => 'success',
        'mensagem' => "Pedido {$pedido['codigo']} atualizado para {$novoStatus} com sucesso!",
        'novo_status' => $novoStatus
    ]);
    exit;
}
