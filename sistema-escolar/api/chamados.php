<?php
/**
 * API - Help desk (chamados de TI)
 * Técnicos e administradores veem todos os chamados; os demais veem apenas os seus.
 *
 *   GET    api/chamados.php                         lista (filtros: status, categoria, prioridade, busca)
 *   GET    api/chamados.php?id=1                    detalhe + comentários
 *   GET    api/chamados.php?tecnicos=1              lista de técnicos (para atribuição)
 *   POST   api/chamados.php                         abre chamado
 *   POST   api/chamados.php?id=1&acao=comentar      adiciona comentário { mensagem }
 *   PUT    api/chamados.php?id=1                    edita dados (dono enquanto aberto / equipe TI)
 *   PATCH  api/chamados.php?id=1                    atendimento { status, tecnico_id, prioridade, solucao }
 *   DELETE api/chamados.php?id=1                    exclui (admin, ou dono se ainda não atendido)
 */
require_once __DIR__ . '/_bootstrap.php';
api_exigir_login();

const CATEGORIAS_CHAMADO  = ['hardware', 'software', 'rede', 'impressora', 'acesso', 'outro'];
const PRIORIDADES_CHAMADO = ['baixa', 'media', 'alta', 'critica'];
const STATUS_CHAMADO      = ['aberto', 'em_andamento', 'aguardando', 'resolvido', 'fechado'];

$uid    = usuario_id();
$equipe = tem_perfil('admin', 'tecnico');

function buscar_chamado(int $id, int $uid, bool $equipe): array
{
    $stmt = db()->prepare(
        'SELECT c.*, u.nome AS solicitante, u.email AS email_solicitante, t.nome AS tecnico
           FROM chamados c
           JOIN usuarios u      ON u.id = c.usuario_id
           LEFT JOIN usuarios t ON t.id = c.tecnico_id
          WHERE c.id = ?'
    );
    $stmt->execute([$id]);
    $c = $stmt->fetch();
    if (!$c) {
        json_erro('Chamado não encontrado.', 404);
    }
    if (!$equipe && (int) $c['usuario_id'] !== $uid) {
        json_erro('Sem permissão para ver este chamado.', 403);
    }
    return $c;
}

function dados_chamado(array $d): array
{
    exigir_campos($d, ['titulo' => 'Título', 'descricao' => 'Descrição', 'categoria' => 'Categoria']);
    return [
        trim($d['titulo']),
        trim($d['descricao']),
        validar_opcao($d['categoria'], CATEGORIAS_CHAMADO, 'Categoria'),
        validar_opcao($d['prioridade'] ?? 'media', PRIORIDADES_CHAMADO, 'Prioridade'),
        trim($d['local'] ?? '') ?: null,
    ];
}

switch ($METODO) {
    case 'GET':
        if (!empty($_GET['tecnicos'])) {
            json_resposta(db()->query(
                'SELECT id, nome FROM usuarios WHERE perfil IN ("tecnico","admin") AND ativo = 1 ORDER BY nome'
            )->fetchAll());
        }

        if (isset($_GET['id'])) {
            $c = buscar_chamado(id_url(), $uid, $equipe);
            $stmt = db()->prepare(
                'SELECT cc.*, u.nome, u.perfil FROM chamado_comentarios cc
                   JOIN usuarios u ON u.id = cc.usuario_id
                  WHERE cc.chamado_id = ? ORDER BY cc.criado_em'
            );
            $stmt->execute([$c['id']]);
            $c['comentarios'] = $stmt->fetchAll();
            json_resposta($c);
        }

        $sql = 'SELECT c.*, u.nome AS solicitante, t.nome AS tecnico
                  FROM chamados c
                  JOIN usuarios u      ON u.id = c.usuario_id
                  LEFT JOIN usuarios t ON t.id = c.tecnico_id
                 WHERE 1 = 1';
        $params = [];
        if (!$equipe || !empty($_GET['meus'])) {
            $sql .= ' AND c.usuario_id = ?';
            $params[] = $uid;
        }
        foreach (['status', 'categoria', 'prioridade'] as $campo) {
            if (!empty($_GET[$campo])) {
                $sql .= " AND c.$campo = ?";
                $params[] = $_GET[$campo];
            }
        }
        if (!empty($_GET['busca'])) {
            $sql .= ' AND (c.titulo LIKE ? OR c.descricao LIKE ? OR c.local LIKE ?)';
            array_push($params, ...array_fill(0, 3, '%' . $_GET['busca'] . '%'));
        }
        $sql .= ' ORDER BY FIELD(c.status,"aberto","em_andamento","aguardando","resolvido","fechado"),
                           FIELD(c.prioridade,"critica","alta","media","baixa"), c.criado_em DESC';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        json_resposta($stmt->fetchAll());

    case 'POST':
        if (($_GET['acao'] ?? '') === 'comentar') {
            $c = buscar_chamado(id_url(), $uid, $equipe);
            $d = corpo_requisicao();
            exigir_campos($d, ['mensagem' => 'Mensagem']);
            db()->prepare('INSERT INTO chamado_comentarios (chamado_id, usuario_id, mensagem) VALUES (?, ?, ?)')
                ->execute([$c['id'], $uid, trim($d['mensagem'])]);
            json_resposta(['mensagem' => 'Comentário adicionado!'], 201);
        }

        db()->prepare(
            'INSERT INTO chamados (titulo, descricao, categoria, prioridade, local, usuario_id) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([...dados_chamado(corpo_requisicao()), $uid]);
        $id = (int) db()->lastInsertId();
        json_resposta(['mensagem' => "Chamado #$id aberto com sucesso!", 'id' => $id], 201);

    case 'PUT':
        $c = buscar_chamado(id_url(), $uid, $equipe);
        if (!$equipe && $c['status'] !== 'aberto') {
            json_erro('O chamado já está em atendimento e não pode mais ser editado.', 403);
        }
        db()->prepare(
            'UPDATE chamados SET titulo = ?, descricao = ?, categoria = ?, prioridade = ?, local = ? WHERE id = ?'
        )->execute([...dados_chamado(corpo_requisicao()), $c['id']]);
        json_resposta(['mensagem' => 'Chamado atualizado!']);

    case 'PATCH':
        $c = buscar_chamado(id_url(), $uid, $equipe);
        $d = corpo_requisicao();
        $status = validar_opcao($d['status'] ?? $c['status'], STATUS_CHAMADO, 'Status');

        if (!$equipe) {
            // O solicitante pode apenas fechar (confirmar a solução) ou reabrir um chamado resolvido
            if (!in_array($status, ['fechado', 'aberto'], true)
                || ($status === 'aberto' && $c['status'] !== 'resolvido')) {
                json_erro('Ação não permitida.', 403);
            }
            $tecnico    = $c['tecnico_id'];
            $prioridade = $c['prioridade'];
            $solucao    = $c['solucao'];
        } else {
            $tecnico    = !empty($d['tecnico_id']) ? (int) $d['tecnico_id'] : null;
            $prioridade = validar_opcao($d['prioridade'] ?? $c['prioridade'], PRIORIDADES_CHAMADO, 'Prioridade');
            $solucao    = trim($d['solucao'] ?? '') ?: null;
            if ($status === 'resolvido' && !$solucao) {
                json_erro('Descreva a solução para marcar como resolvido.', 422);
            }
            // Ao assumir um chamado aberto sem técnico, atribui automaticamente
            if (!$tecnico && $status !== 'aberto') {
                $tecnico = $uid;
            }
        }

        $resolvidoEm = in_array($status, ['resolvido', 'fechado'], true)
            ? ($c['resolvido_em'] ?? date('Y-m-d H:i:s'))
            : null;

        db()->prepare(
            'UPDATE chamados SET status = ?, tecnico_id = ?, prioridade = ?, solucao = ?, resolvido_em = ? WHERE id = ?'
        )->execute([$status, $tecnico, $prioridade, $solucao, $resolvidoEm, $c['id']]);

        if ($status !== $c['status']) {
            db()->prepare('INSERT INTO chamado_comentarios (chamado_id, usuario_id, mensagem) VALUES (?, ?, ?)')
                ->execute([$c['id'], $uid, "Status alterado de \"{$c['status']}\" para \"$status\"."]);
        }
        json_resposta(['mensagem' => 'Chamado atualizado!']);

    case 'DELETE':
        $c = buscar_chamado(id_url(), $uid, $equipe);
        $podeExcluir = tem_perfil('admin')
            || ((int) $c['usuario_id'] === $uid && $c['status'] === 'aberto' && !$c['tecnico_id']);
        if (!$podeExcluir) {
            json_erro('Este chamado não pode mais ser excluído.', 403);
        }
        db()->prepare('DELETE FROM chamados WHERE id = ?')->execute([$c['id']]);
        json_resposta(['mensagem' => 'Chamado excluído!']);

    default:
        json_erro('Método não permitido.', 405);
}
