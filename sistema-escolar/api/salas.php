<?php
/**
 * API - Salas e laboratórios (CRUD - somente administrador altera)
 *
 *   GET    api/salas.php            lista (todos os usuários logados)
 *   GET    api/salas.php?id=1       detalhe
 *   POST   api/salas.php            cria       (admin)
 *   PUT    api/salas.php?id=1       atualiza   (admin)
 *   DELETE api/salas.php?id=1       exclui     (admin)
 */
require_once __DIR__ . '/_bootstrap.php';
api_exigir_login();

const TIPOS_SALA = ['sala', 'laboratorio', 'auditorio'];

function dados_sala(array $d): array
{
    exigir_campos($d, ['nome' => 'Nome', 'tipo' => 'Tipo', 'capacidade' => 'Capacidade']);
    $cap = (int) $d['capacidade'];
    if ($cap <= 0) {
        json_erro('A capacidade deve ser maior que zero.', 422);
    }
    return [
        trim($d['nome']),
        validar_opcao($d['tipo'], TIPOS_SALA, 'Tipo'),
        $cap,
        trim($d['localizacao'] ?? '') ?: null,
        trim($d['recursos'] ?? '') ?: null,
        !empty($d['ativa']) ? 1 : 0,
    ];
}

switch ($METODO) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = db()->prepare('SELECT * FROM salas WHERE id = ?');
            $stmt->execute([id_url()]);
            json_resposta($stmt->fetch() ?: json_erro('Sala não encontrada.', 404));
        }
        $sql = 'SELECT s.*,
                   (SELECT COUNT(*) FROM reservas r WHERE r.sala_id = s.id AND r.data >= CURDATE()
                      AND r.status IN ("pendente","aprovada")) AS reservas_futuras
                FROM salas s';
        if (!empty($_GET['ativas'])) {
            $sql .= ' WHERE s.ativa = 1';
        }
        json_resposta(db()->query($sql . ' ORDER BY s.tipo, s.nome')->fetchAll());

    case 'POST':
        api_exigir_perfil('admin');
        $stmt = db()->prepare(
            'INSERT INTO salas (nome, tipo, capacidade, localizacao, recursos, ativa) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute(dados_sala(corpo_requisicao()));
        json_resposta(['mensagem' => 'Sala cadastrada!', 'id' => (int) db()->lastInsertId()], 201);

    case 'PUT':
        api_exigir_perfil('admin');
        $stmt = db()->prepare(
            'UPDATE salas SET nome = ?, tipo = ?, capacidade = ?, localizacao = ?, recursos = ?, ativa = ? WHERE id = ?'
        );
        $stmt->execute([...dados_sala(corpo_requisicao()), id_url()]);
        json_resposta(['mensagem' => 'Sala atualizada!']);

    case 'DELETE':
        api_exigir_perfil('admin');
        db()->prepare('DELETE FROM salas WHERE id = ?')->execute([id_url()]);
        json_resposta(['mensagem' => 'Sala excluída!']);

    default:
        json_erro('Método não permitido.', 405);
}
