<?php
/**
 * API - Dados do painel inicial (indicadores conforme o perfil do usuário)
 *   GET api/dashboard.php
 */
require_once __DIR__ . '/_bootstrap.php';
api_exigir_login();

$uid = usuario_id();

function valor(string $sql, array $params = [])
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function linhas(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

$cards = [
    ['titulo' => 'Tarefas pendentes', 'icone' => '📚', 'link' => 'tarefas.php',
     'valor' => valor("SELECT COUNT(*) FROM tarefas WHERE usuario_id = ? AND status <> 'concluida'", [$uid])],
    ['titulo' => 'Tarefas atrasadas', 'icone' => '⏰', 'link' => 'tarefas.php',
     'valor' => valor("SELECT COUNT(*) FROM tarefas WHERE usuario_id = ? AND status <> 'concluida' AND data_entrega < CURDATE()", [$uid])],
    ['titulo' => 'Minhas reservas futuras', 'icone' => '🏫', 'link' => 'reservas.php',
     'valor' => valor("SELECT COUNT(*) FROM reservas WHERE usuario_id = ? AND data >= CURDATE() AND status IN ('pendente','aprovada')", [$uid])],
    ['titulo' => 'Meus chamados abertos', 'icone' => '🛠️', 'link' => 'chamados.php',
     'valor' => valor("SELECT COUNT(*) FROM chamados WHERE usuario_id = ? AND status NOT IN ('resolvido','fechado')", [$uid])],
];

if (tem_perfil('admin')) {
    $cards[] = ['titulo' => 'Reservas aguardando aprovação', 'icone' => '✅', 'link' => 'reservas.php',
        'valor' => valor("SELECT COUNT(*) FROM reservas WHERE status = 'pendente'")];
    $cards[] = ['titulo' => 'Pedidos em aberto na cantina', 'icone' => '🍔', 'link' => 'cantina-admin.php',
        'valor' => valor("SELECT COUNT(*) FROM pedidos WHERE status IN ('pendente','preparando','pronto')")];
    $cards[] = ['titulo' => 'Vendas de hoje (R$)', 'icone' => '💰', 'link' => 'relatorios.php?tipo=cantina',
        'valor' => number_format((float) valor("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE DATE(criado_em) = CURDATE() AND status <> 'cancelado'"), 2, ',', '.')];
    $cards[] = ['titulo' => 'Usuários ativos', 'icone' => '👥', 'link' => 'usuarios.php',
        'valor' => valor('SELECT COUNT(*) FROM usuarios WHERE ativo = 1')];
}
if (tem_perfil('admin', 'tecnico')) {
    $cards[] = ['titulo' => 'Chamados na fila de TI', 'icone' => '🖥️', 'link' => 'chamados.php',
        'valor' => valor("SELECT COUNT(*) FROM chamados WHERE status IN ('aberto','em_andamento','aguardando')")];
    $cards[] = ['titulo' => 'Chamados críticos', 'icone' => '🚨', 'link' => 'chamados.php',
        'valor' => valor("SELECT COUNT(*) FROM chamados WHERE prioridade = 'critica' AND status NOT IN ('resolvido','fechado')")];
}

json_resposta([
    'cards' => $cards,
    'proximas_tarefas' => linhas(
        "SELECT id, titulo, disciplina, data_entrega, prioridade, status FROM tarefas
          WHERE usuario_id = ? AND status <> 'concluida' ORDER BY data_entrega LIMIT 5", [$uid]),
    'proximas_reservas' => linhas(
        "SELECT r.data, r.hora_inicio, r.hora_fim, r.status, s.nome AS sala FROM reservas r
           JOIN salas s ON s.id = r.sala_id
          WHERE r.usuario_id = ? AND r.data >= CURDATE() AND r.status IN ('pendente','aprovada')
          ORDER BY r.data, r.hora_inicio LIMIT 5", [$uid]),
    'ultimos_chamados' => linhas(
        'SELECT id, titulo, status, prioridade, criado_em FROM chamados'
        . (tem_perfil('admin', 'tecnico') ? '' : ' WHERE usuario_id = ' . $uid)
        . ' ORDER BY criado_em DESC LIMIT 5'),
]);
