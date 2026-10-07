<?php
/**
 * API - Reservas de salas e laboratórios
 *
 *   GET    api/reservas.php                     minhas reservas (admin: todas) - filtros: status, sala_id, data
 *   GET    api/reservas.php?agenda=1&data=...   ocupação das salas em um dia (todos)
 *   POST   api/reservas.php                     solicita reserva (verifica conflito de horário)
 *   PUT    api/reservas.php?id=1                edita (dono, enquanto pendente / admin)
 *   PATCH  api/reservas.php?id=1                muda status { status, observacao }
 *                                               admin: aprovada/recusada/cancelada | dono: cancelada
 *   DELETE api/reservas.php?id=1                exclui (dono ou admin)
 */
require_once __DIR__ . '/_bootstrap.php';
api_exigir_login();

const STATUS_RESERVA = ['pendente', 'aprovada', 'recusada', 'cancelada'];

$uid     = usuario_id();
$isAdmin = tem_perfil('admin');

function buscar_reserva(int $id): array
{
    $stmt = db()->prepare('SELECT * FROM reservas WHERE id = ?');
    $stmt->execute([$id]);
    $r = $stmt->fetch();
    if (!$r) {
        json_erro('Reserva não encontrada.', 404);
    }
    return $r;
}

/** Verifica se já existe reserva ativa no mesmo horário para a sala. */
function verificar_conflito(int $salaId, string $data, string $ini, string $fim, int $ignorarId = 0): void
{
    $stmt = db()->prepare(
        'SELECT r.hora_inicio, r.hora_fim, u.nome
           FROM reservas r JOIN usuarios u ON u.id = r.usuario_id
          WHERE r.sala_id = ? AND r.data = ? AND r.id <> ?
            AND r.status IN ("pendente","aprovada")
            AND r.hora_inicio < ? AND r.hora_fim > ?
          LIMIT 1'
    );
    $stmt->execute([$salaId, $data, $ignorarId, $fim, $ini]);
    if ($c = $stmt->fetch()) {
        json_erro(sprintf(
            'Conflito de horário: a sala já está reservada das %s às %s (%s).',
            substr($c['hora_inicio'], 0, 5), substr($c['hora_fim'], 0, 5), $c['nome']
        ), 409);
    }
}

function dados_reserva(array $d): array
{
    exigir_campos($d, [
        'sala_id' => 'Sala', 'data' => 'Data', 'hora_inicio' => 'Hora inicial',
        'hora_fim' => 'Hora final', 'finalidade' => 'Finalidade',
    ]);
    $data = validar_data($d['data'], 'Data');
    $ini  = validar_hora($d['hora_inicio'], 'Hora inicial');
    $fim  = validar_hora($d['hora_fim'], 'Hora final');

    if ($data < date('Y-m-d')) {
        json_erro('Não é possível reservar em uma data passada.', 422);
    }
    if ($fim <= $ini) {
        json_erro('A hora final deve ser maior que a hora inicial.', 422);
    }

    $stmt = db()->prepare('SELECT id FROM salas WHERE id = ? AND ativa = 1');
    $stmt->execute([(int) $d['sala_id']]);
    if (!$stmt->fetch()) {
        json_erro('Sala inexistente ou inativa.', 422);
    }

    return [(int) $d['sala_id'], $data, $ini, $fim, trim($d['finalidade'])];
}

switch ($METODO) {
    case 'GET':
        // Ocupação de um dia (para todos verem os horários livres)
        if (!empty($_GET['agenda'])) {
            $data = validar_data($_GET['data'] ?? date('Y-m-d'), 'Data');
            $stmt = db()->prepare(
                'SELECT r.id, r.sala_id, s.nome AS sala, r.hora_inicio, r.hora_fim, r.status, r.finalidade, u.nome AS solicitante
                   FROM reservas r JOIN salas s ON s.id = r.sala_id JOIN usuarios u ON u.id = r.usuario_id
                  WHERE r.data = ? AND r.status IN ("pendente","aprovada")
                  ORDER BY s.nome, r.hora_inicio'
            );
            $stmt->execute([$data]);
            json_resposta($stmt->fetchAll());
        }

        if (isset($_GET['id'])) {
            $r = buscar_reserva(id_url());
            if (!$isAdmin && (int) $r['usuario_id'] !== $uid) {
                json_erro('Sem permissão.', 403);
            }
            json_resposta($r);
        }

        $sql = 'SELECT r.*, s.nome AS sala, s.tipo AS sala_tipo, u.nome AS solicitante, u.perfil
                  FROM reservas r
                  JOIN salas s    ON s.id = r.sala_id
                  JOIN usuarios u ON u.id = r.usuario_id
                 WHERE 1 = 1';
        $params = [];
        if (!$isAdmin) {
            $sql .= ' AND r.usuario_id = ?';
            $params[] = $uid;
        }
        foreach (['status' => 'r.status', 'sala_id' => 'r.sala_id', 'data' => 'r.data'] as $campo => $coluna) {
            if (!empty($_GET[$campo])) {
                $sql .= " AND $coluna = ?";
                $params[] = $_GET[$campo];
            }
        }
        $sql .= ' ORDER BY r.data DESC, r.hora_inicio';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        json_resposta($stmt->fetchAll());

    case 'POST':
        [$sala, $data, $ini, $fim, $fin] = dados_reserva(corpo_requisicao());
        verificar_conflito($sala, $data, $ini, $fim);
        $status = $isAdmin ? 'aprovada' : 'pendente'; // reservas do admin já nascem aprovadas
        $stmt = db()->prepare(
            'INSERT INTO reservas (sala_id, usuario_id, data, hora_inicio, hora_fim, finalidade, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$sala, $uid, $data, $ini, $fim, $fin, $status]);
        $msg = $isAdmin ? 'Reserva registrada e aprovada!' : 'Reserva solicitada! Aguarde a aprovação.';
        json_resposta(['mensagem' => $msg, 'id' => (int) db()->lastInsertId()], 201);

    case 'PUT':
        $id = id_url();
        $r  = buscar_reserva($id);
        if (!$isAdmin && ((int) $r['usuario_id'] !== $uid || $r['status'] !== 'pendente')) {
            json_erro('Só é possível editar reservas próprias que ainda estão pendentes.', 403);
        }
        [$sala, $data, $ini, $fim, $fin] = dados_reserva(corpo_requisicao());
        verificar_conflito($sala, $data, $ini, $fim, $id);
        db()->prepare(
            'UPDATE reservas SET sala_id = ?, data = ?, hora_inicio = ?, hora_fim = ?, finalidade = ? WHERE id = ?'
        )->execute([$sala, $data, $ini, $fim, $fin, $id]);
        json_resposta(['mensagem' => 'Reserva atualizada!']);

    case 'PATCH':
        $id     = id_url();
        $r      = buscar_reserva($id);
        $d      = corpo_requisicao();
        $status = validar_opcao($d['status'] ?? '', STATUS_RESERVA, 'Status');

        if (!$isAdmin) {
            if ((int) $r['usuario_id'] !== $uid || $status !== 'cancelada') {
                json_erro('Você só pode cancelar as suas próprias reservas.', 403);
            }
        }
        if ($status === 'aprovada') {
            verificar_conflito((int) $r['sala_id'], $r['data'], $r['hora_inicio'], $r['hora_fim'], $id);
        }
        db()->prepare('UPDATE reservas SET status = ?, observacao = ? WHERE id = ?')
            ->execute([$status, trim($d['observacao'] ?? '') ?: $r['observacao'], $id]);
        json_resposta(['mensagem' => 'Reserva ' . $status . '!']);

    case 'DELETE':
        $id = id_url();
        $r  = buscar_reserva($id);
        if (!$isAdmin && (int) $r['usuario_id'] !== $uid) {
            json_erro('Sem permissão.', 403);
        }
        db()->prepare('DELETE FROM reservas WHERE id = ?')->execute([$id]);
        json_resposta(['mensagem' => 'Reserva excluída!']);

    default:
        json_erro('Método não permitido.', 405);
}
