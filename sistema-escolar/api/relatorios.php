<?php
/**
 * API - Relatórios
 *
 *   GET api/relatorios.php?tipo=tarefas&inicio=AAAA-MM-DD&fim=AAAA-MM-DD
 *   tipos: tarefas (todos), reservas (todos - admin vê geral), cantina (admin), chamados (admin/técnico)
 *
 * Resposta: { resumo: {...}, grupos: { nome: [ {rotulo, valor} ] }, linhas: [...] }
 */
require_once __DIR__ . '/_bootstrap.php';
api_exigir_login();

$tipo   = $_GET['tipo'] ?? '';
$inicio = validar_data($_GET['inicio'] ?? date('Y-m-01'), 'Data inicial');
$fim    = validar_data($_GET['fim'] ?? date('Y-m-t'), 'Data final');
$uid    = usuario_id();

if ($fim < $inicio) {
    json_erro('A data final deve ser maior ou igual à inicial.', 422);
}

/** Executa uma consulta e devolve todas as linhas. */
function consulta(string $sql, array $params): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function primeira(string $sql, array $params): array
{
    return consulta($sql, $params)[0] ?? [];
}

switch ($tipo) {
    case 'tarefas':
        // Aluno vê as próprias tarefas; administrador e professor veem as de todos os alunos
        $geral  = tem_perfil('admin', 'professor');
        $filtro = 't.data_entrega BETWEEN ? AND ? AND ' . ($geral ? "u.perfil = 'aluno'" : 't.usuario_id = ?');
        $p      = $geral ? [$inicio, $fim] : [$inicio, $fim, $uid];
        $from   = 'FROM tarefas t JOIN usuarios u ON u.id = t.usuario_id';

        $resumo = primeira(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(t.status = 'concluida'), 0) AS concluidas,
                    COALESCE(SUM(t.status <> 'concluida'), 0) AS abertas,
                    COALESCE(SUM(t.status <> 'concluida' AND t.data_entrega < CURDATE()), 0) AS atrasadas
               $from WHERE $filtro", $p
        );
        $resumo['percentual_concluido'] = $resumo['total'] ? round($resumo['concluidas'] * 100 / $resumo['total']) : 0;

        $grupos = [
            'Por status' => consulta(
                "SELECT t.status AS rotulo, COUNT(*) AS valor $from WHERE $filtro GROUP BY t.status", $p),
            'Por disciplina' => consulta(
                "SELECT COALESCE(t.disciplina,'(sem disciplina)') AS rotulo, COUNT(*) AS valor
                   $from WHERE $filtro GROUP BY t.disciplina ORDER BY valor DESC", $p),
        ];
        if ($geral) {
            $grupos['Conclusão por aluno (%)'] = consulta(
                "SELECT u.nome AS rotulo, ROUND(SUM(t.status = 'concluida') * 100 / COUNT(*)) AS valor
                   $from WHERE $filtro GROUP BY u.id ORDER BY valor DESC", $p);
        }
        $linhas = consulta(
            'SELECT ' . ($geral ? "u.nome AS 'Aluno', u.turma AS 'Turma', " : '') .
            "t.titulo AS 'Título', t.disciplina AS 'Disciplina', t.data_entrega AS 'Entrega',
                    t.prioridade AS 'Prioridade', t.status AS 'Status'
               $from WHERE $filtro ORDER BY t.data_entrega", $p
        );
        break;

    case 'reservas':
        $admin  = tem_perfil('admin');
        $filtro = 'r.data BETWEEN ? AND ?' . ($admin ? '' : ' AND r.usuario_id = ?');
        $p      = $admin ? [$inicio, $fim] : [$inicio, $fim, $uid];

        $resumo = primeira(
            "SELECT COUNT(*) AS total,
                    SUM(r.status = 'aprovada')  AS aprovadas,
                    SUM(r.status = 'pendente')  AS pendentes,
                    SUM(r.status IN ('recusada','cancelada')) AS recusadas_canceladas,
                    ROUND(SUM(CASE WHEN r.status = 'aprovada'
                          THEN TIME_TO_SEC(TIMEDIFF(r.hora_fim, r.hora_inicio)) ELSE 0 END) / 3600, 1) AS horas_reservadas
               FROM reservas r WHERE $filtro", $p
        );
        $grupos = [
            'Reservas por sala' => consulta(
                "SELECT s.nome AS rotulo, COUNT(*) AS valor FROM reservas r JOIN salas s ON s.id = r.sala_id
                  WHERE $filtro GROUP BY s.id ORDER BY valor DESC", $p),
            'Horas aprovadas por sala' => consulta(
                "SELECT s.nome AS rotulo,
                        ROUND(SUM(TIME_TO_SEC(TIMEDIFF(r.hora_fim, r.hora_inicio))) / 3600, 1) AS valor
                   FROM reservas r JOIN salas s ON s.id = r.sala_id
                  WHERE $filtro AND r.status = 'aprovada' GROUP BY s.id ORDER BY valor DESC", $p),
            'Por status' => consulta(
                "SELECT r.status AS rotulo, COUNT(*) AS valor FROM reservas r WHERE $filtro GROUP BY r.status", $p),
        ];
        $linhas = consulta(
            "SELECT r.data AS 'Data', CONCAT(TIME_FORMAT(r.hora_inicio,'%H:%i'),' - ',TIME_FORMAT(r.hora_fim,'%H:%i')) AS 'Horário',
                    s.nome AS 'Sala', u.nome AS 'Solicitante', r.finalidade AS 'Finalidade', r.status AS 'Status'
               FROM reservas r JOIN salas s ON s.id = r.sala_id JOIN usuarios u ON u.id = r.usuario_id
              WHERE $filtro ORDER BY r.data, r.hora_inicio", $p
        );
        break;

    case 'cantina':
        api_exigir_perfil('admin');
        $p      = [$inicio, $fim];
        $filtro = "DATE(p.criado_em) BETWEEN ? AND ? AND p.status <> 'cancelado'";

        $resumo = primeira(
            "SELECT COUNT(*) AS pedidos, COALESCE(SUM(p.total),0) AS faturamento,
                    COALESCE(ROUND(AVG(p.total),2),0) AS ticket_medio
               FROM pedidos p WHERE $filtro", $p
        );
        $resumo['cancelados'] = primeira(
            "SELECT COUNT(*) AS n FROM pedidos WHERE DATE(criado_em) BETWEEN ? AND ? AND status = 'cancelado'", $p
        )['n'];

        $grupos = [
            'Faturamento por dia (R$)' => consulta(
                "SELECT DATE_FORMAT(DATE(p.criado_em),'%d/%m') AS rotulo, SUM(p.total) AS valor
                   FROM pedidos p WHERE $filtro GROUP BY DATE(p.criado_em) ORDER BY DATE(p.criado_em)", $p),
            'Produtos mais vendidos (qtd)' => consulta(
                "SELECT pr.nome AS rotulo, SUM(i.quantidade) AS valor
                   FROM pedido_itens i JOIN pedidos p ON p.id = i.pedido_id JOIN produtos pr ON pr.id = i.produto_id
                  WHERE $filtro GROUP BY pr.id ORDER BY valor DESC LIMIT 10", $p),
            'Faturamento por categoria (R$)' => consulta(
                "SELECT pr.categoria AS rotulo, SUM(i.subtotal) AS valor
                   FROM pedido_itens i JOIN pedidos p ON p.id = i.pedido_id JOIN produtos pr ON pr.id = i.produto_id
                  WHERE $filtro GROUP BY pr.categoria ORDER BY valor DESC", $p),
            'Por forma de pagamento (R$)' => consulta(
                "SELECT p.forma_pagamento AS rotulo, SUM(p.total) AS valor FROM pedidos p
                  WHERE $filtro GROUP BY p.forma_pagamento", $p),
        ];
        $linhas = consulta(
            "SELECT pr.nome AS 'Produto', pr.categoria AS 'Categoria', SUM(i.quantidade) AS 'Qtd vendida',
                    SUM(i.subtotal) AS 'Total (R$)', pr.estoque AS 'Estoque atual'
               FROM pedido_itens i JOIN pedidos p ON p.id = i.pedido_id JOIN produtos pr ON pr.id = i.produto_id
              WHERE $filtro GROUP BY pr.id ORDER BY SUM(i.subtotal) DESC", $p
        );
        break;

    case 'chamados':
        api_exigir_perfil('admin', 'tecnico');
        $p      = [$inicio, $fim];
        $filtro = 'DATE(c.criado_em) BETWEEN ? AND ?';

        $resumo = primeira(
            "SELECT COUNT(*) AS total,
                    SUM(c.status IN ('aberto','em_andamento','aguardando')) AS em_aberto,
                    SUM(c.status IN ('resolvido','fechado')) AS resolvidos,
                    ROUND(AVG(TIMESTAMPDIFF(MINUTE, c.criado_em, c.resolvido_em)) / 60, 1) AS tempo_medio_horas
               FROM chamados c WHERE $filtro", $p
        );
        $grupos = [
            'Por status' => consulta(
                "SELECT c.status AS rotulo, COUNT(*) AS valor FROM chamados c WHERE $filtro GROUP BY c.status", $p),
            'Por categoria' => consulta(
                "SELECT c.categoria AS rotulo, COUNT(*) AS valor FROM chamados c WHERE $filtro
                  GROUP BY c.categoria ORDER BY valor DESC", $p),
            'Por prioridade' => consulta(
                "SELECT c.prioridade AS rotulo, COUNT(*) AS valor FROM chamados c WHERE $filtro
                  GROUP BY c.prioridade ORDER BY FIELD(c.prioridade,'critica','alta','media','baixa')", $p),
            'Atendimentos por técnico' => consulta(
                "SELECT COALESCE(t.nome,'(não atribuído)') AS rotulo, COUNT(*) AS valor
                   FROM chamados c LEFT JOIN usuarios t ON t.id = c.tecnico_id
                  WHERE $filtro GROUP BY c.tecnico_id ORDER BY valor DESC", $p),
        ];
        $linhas = consulta(
            "SELECT c.id AS '#', c.titulo AS 'Título', c.categoria AS 'Categoria', c.prioridade AS 'Prioridade',
                    c.status AS 'Status', u.nome AS 'Solicitante', COALESCE(t.nome,'-') AS 'Técnico',
                    DATE_FORMAT(c.criado_em,'%d/%m/%Y %H:%i') AS 'Aberto em',
                    COALESCE(ROUND(TIMESTAMPDIFF(MINUTE, c.criado_em, c.resolvido_em)/60,1),'-') AS 'Horas p/ resolver'
               FROM chamados c JOIN usuarios u ON u.id = c.usuario_id LEFT JOIN usuarios t ON t.id = c.tecnico_id
              WHERE $filtro ORDER BY c.criado_em DESC", $p
        );
        break;

    default:
        json_erro('Tipo de relatório inválido.', 400);
}

json_resposta(['resumo' => $resumo, 'grupos' => $grupos, 'linhas' => $linhas, 'periodo' => [$inicio, $fim]]);
