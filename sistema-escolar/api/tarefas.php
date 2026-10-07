<?php
/**
 * API - Agenda de tarefas do aluno (CRUD)
 * Cada usuário só enxerga e altera as PRÓPRIAS tarefas.
 *
 *   GET    api/tarefas.php                 lista (filtros: status, busca)
 *   GET    api/tarefas.php?id=1            detalhe
 *   POST   api/tarefas.php                 cria
 *   PUT    api/tarefas.php?id=1            atualiza
 *   PATCH  api/tarefas.php?id=1            altera apenas o status { status }
 *   DELETE api/tarefas.php?id=1            exclui
 */
require_once __DIR__ . '/_bootstrap.php';
api_exigir_login();

const PRIORIDADES_TAREFA = ['baixa', 'media', 'alta'];
const STATUS_TAREFA      = ['pendente', 'em_andamento', 'concluida'];

$uid = usuario_id();

function buscar_tarefa(int $id, int $uid): array
{
    $stmt = db()->prepare('SELECT * FROM tarefas WHERE id = ? AND usuario_id = ?');
    $stmt->execute([$id, $uid]);
    $t = $stmt->fetch();
    if (!$t) {
        json_erro('Tarefa não encontrada.', 404);
    }
    return $t;
}

function dados_tarefa(array $d): array
{
    exigir_campos($d, ['titulo' => 'Título', 'data_entrega' => 'Data de entrega']);
    return [
        trim($d['titulo']),
        trim($d['descricao'] ?? '') ?: null,
        trim($d['disciplina'] ?? '') ?: null,
        validar_data($d['data_entrega'], 'Data de entrega'),
        validar_opcao($d['prioridade'] ?? 'media', PRIORIDADES_TAREFA, 'Prioridade'),
        validar_opcao($d['status'] ?? 'pendente', STATUS_TAREFA, 'Status'),
    ];
}

switch ($METODO) {
    case 'GET':
        if (isset($_GET['id'])) {
            json_resposta(buscar_tarefa(id_url(), $uid));
        }
        $sql = 'SELECT *, (data_entrega < CURDATE() AND status <> "concluida") AS atrasada
                FROM tarefas WHERE usuario_id = ?';
        $params = [$uid];
        if (!empty($_GET['status'])) {
            $sql .= ' AND status = ?';
            $params[] = $_GET['status'];
        }
        if (!empty($_GET['busca'])) {
            $sql .= ' AND (titulo LIKE ? OR disciplina LIKE ?)';
            $params[] = '%' . $_GET['busca'] . '%';
            $params[] = '%' . $_GET['busca'] . '%';
        }
        $sql .= ' ORDER BY status = "concluida", data_entrega ASC';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        json_resposta($stmt->fetchAll());

    case 'POST':
        $v = dados_tarefa(corpo_requisicao());
        $stmt = db()->prepare(
            'INSERT INTO tarefas (titulo, descricao, disciplina, data_entrega, prioridade, status, usuario_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([...$v, $uid]);
        json_resposta(['mensagem' => 'Tarefa criada!', 'id' => (int) db()->lastInsertId()], 201);

    case 'PUT':
        $id = id_url();
        buscar_tarefa($id, $uid);
        $v = dados_tarefa(corpo_requisicao());
        $stmt = db()->prepare(
            'UPDATE tarefas SET titulo = ?, descricao = ?, disciplina = ?, data_entrega = ?, prioridade = ?, status = ?
             WHERE id = ? AND usuario_id = ?'
        );
        $stmt->execute([...$v, $id, $uid]);
        json_resposta(['mensagem' => 'Tarefa atualizada!']);

    case 'PATCH':
        $id = id_url();
        buscar_tarefa($id, $uid);
        $status = validar_opcao(corpo_requisicao()['status'] ?? '', STATUS_TAREFA, 'Status');
        db()->prepare('UPDATE tarefas SET status = ? WHERE id = ? AND usuario_id = ?')->execute([$status, $id, $uid]);
        json_resposta(['mensagem' => 'Status atualizado!']);

    case 'DELETE':
        $id = id_url();
        buscar_tarefa($id, $uid);
        db()->prepare('DELETE FROM tarefas WHERE id = ? AND usuario_id = ?')->execute([$id, $uid]);
        json_resposta(['mensagem' => 'Tarefa excluída!']);

    default:
        json_erro('Método não permitido.', 405);
}
