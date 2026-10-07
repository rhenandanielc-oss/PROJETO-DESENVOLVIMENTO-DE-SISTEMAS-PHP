<?php
/**
 * API - Pedidos da cantina
 *
 *   GET    api/pedidos.php              meus pedidos (admin: todos) - filtros: status, data
 *   GET    api/pedidos.php?id=1         pedido com itens
 *   POST   api/pedidos.php              faz pedido { itens:[{produto_id, quantidade}], forma_pagamento, observacao }
 *   PATCH  api/pedidos.php?id=1         muda status { status }  admin: qualquer | cliente: cancelar se pendente
 *   DELETE api/pedidos.php?id=1         exclui (admin)
 *
 * O estoque é baixado ao fazer o pedido e devolvido se o pedido for cancelado.
 * Tudo dentro de TRANSAÇÕES para manter o banco consistente.
 */
require_once __DIR__ . '/_bootstrap.php';
api_exigir_login();

const STATUS_PEDIDO    = ['pendente', 'preparando', 'pronto', 'entregue', 'cancelado'];
const PAGAMENTOS       = ['dinheiro', 'pix', 'cartao'];

$uid     = usuario_id();
$isAdmin = tem_perfil('admin');

function buscar_pedido(int $id): array
{
    $stmt = db()->prepare(
        'SELECT p.*, u.nome AS cliente FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id WHERE p.id = ?'
    );
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if (!$p) {
        json_erro('Pedido não encontrado.', 404);
    }
    $itens = db()->prepare(
        'SELECT i.*, pr.nome AS produto FROM pedido_itens i JOIN produtos pr ON pr.id = i.produto_id WHERE i.pedido_id = ?'
    );
    $itens->execute([$id]);
    $p['itens'] = $itens->fetchAll();
    return $p;
}

function devolver_estoque(int $pedidoId): void
{
    db()->prepare(
        'UPDATE produtos pr JOIN pedido_itens i ON i.produto_id = pr.id
            SET pr.estoque = pr.estoque + i.quantidade
          WHERE i.pedido_id = ?'
    )->execute([$pedidoId]);
}

switch ($METODO) {
    case 'GET':
        if (isset($_GET['id'])) {
            $p = buscar_pedido(id_url());
            if (!$isAdmin && (int) $p['usuario_id'] !== $uid) {
                json_erro('Sem permissão.', 403);
            }
            json_resposta($p);
        }
        $sql = 'SELECT p.*, u.nome AS cliente,
                       (SELECT GROUP_CONCAT(CONCAT(i.quantidade, "x ", pr.nome) SEPARATOR ", ")
                          FROM pedido_itens i JOIN produtos pr ON pr.id = i.produto_id
                         WHERE i.pedido_id = p.id) AS resumo
                  FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id WHERE 1 = 1';
        $params = [];
        if (!$isAdmin || !empty($_GET['meus'])) {
            $sql .= ' AND p.usuario_id = ?';
            $params[] = $uid;
        }
        if (!empty($_GET['status'])) {
            $sql .= ' AND p.status = ?';
            $params[] = $_GET['status'];
        }
        if (!empty($_GET['data'])) {
            $sql .= ' AND DATE(p.criado_em) = ?';
            $params[] = $_GET['data'];
        }
        $stmt = db()->prepare($sql . ' ORDER BY p.criado_em DESC LIMIT 200');
        $stmt->execute($params);
        json_resposta($stmt->fetchAll());

    case 'POST':
        $d     = corpo_requisicao();
        $itens = $d['itens'] ?? [];
        if (!is_array($itens) || count($itens) === 0) {
            json_erro('O carrinho está vazio.', 422);
        }
        $pagamento = validar_opcao($d['forma_pagamento'] ?? 'pix', PAGAMENTOS, 'Forma de pagamento');

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO pedidos (usuario_id, forma_pagamento, observacao) VALUES (?, ?, ?)')
                ->execute([$uid, $pagamento, trim($d['observacao'] ?? '') ?: null]);
            $pedidoId = (int) $pdo->lastInsertId();

            // FOR UPDATE trava a linha do produto até o fim da transação (evita vender além do estoque)
            $selProd = $pdo->prepare('SELECT * FROM produtos WHERE id = ? AND ativo = 1 FOR UPDATE');
            $insItem = $pdo->prepare(
                'INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario, subtotal) VALUES (?, ?, ?, ?, ?)'
            );
            $baixa = $pdo->prepare('UPDATE produtos SET estoque = estoque - ? WHERE id = ?');

            $total = 0;
            foreach ($itens as $item) {
                $qtd = (int) ($item['quantidade'] ?? 0);
                if ($qtd <= 0) {
                    throw new InvalidArgumentException('Quantidade inválida.');
                }
                $selProd->execute([(int) ($item['produto_id'] ?? 0)]);
                $prod = $selProd->fetch();
                if (!$prod) {
                    throw new InvalidArgumentException('Produto indisponível.');
                }
                if ($prod['estoque'] < $qtd) {
                    throw new InvalidArgumentException("Estoque insuficiente de {$prod['nome']} (restam {$prod['estoque']}).");
                }
                $subtotal = round($prod['preco'] * $qtd, 2);
                $insItem->execute([$pedidoId, $prod['id'], $qtd, $prod['preco'], $subtotal]);
                $baixa->execute([$qtd, $prod['id']]);
                $total += $subtotal;
            }

            $pdo->prepare('UPDATE pedidos SET total = ? WHERE id = ?')->execute([$total, $pedidoId]);
            $pdo->commit();
        } catch (InvalidArgumentException $ex) {
            $pdo->rollBack();
            json_erro($ex->getMessage(), 422);
        } catch (Throwable $ex) {
            $pdo->rollBack();
            throw $ex;
        }
        json_resposta(['mensagem' => "Pedido #$pedidoId realizado!", 'id' => $pedidoId, 'total' => $total], 201);

    case 'PATCH':
        $id     = id_url();
        $p      = buscar_pedido($id);
        $status = validar_opcao(corpo_requisicao()['status'] ?? '', STATUS_PEDIDO, 'Status');

        if (!$isAdmin && ((int) $p['usuario_id'] !== $uid || $status !== 'cancelado' || $p['status'] !== 'pendente')) {
            json_erro('Você só pode cancelar seus pedidos enquanto estiverem pendentes.', 403);
        }
        if ($p['status'] === 'cancelado') {
            json_erro('Pedido cancelado não pode mudar de status.', 422);
        }

        $pdo = db();
        $pdo->beginTransaction();
        if ($status === 'cancelado') {
            devolver_estoque($id);
        }
        $pdo->prepare('UPDATE pedidos SET status = ? WHERE id = ?')->execute([$status, $id]);
        $pdo->commit();
        json_resposta(['mensagem' => 'Status do pedido atualizado!']);

    case 'DELETE':
        api_exigir_perfil('admin');
        $id = id_url();
        $p  = buscar_pedido($id);
        $pdo = db();
        $pdo->beginTransaction();
        if (!in_array($p['status'], ['cancelado', 'entregue'], true)) {
            devolver_estoque($id);
        }
        $pdo->prepare('DELETE FROM pedidos WHERE id = ?')->execute([$id]);
        $pdo->commit();
        json_resposta(['mensagem' => 'Pedido excluído!']);

    default:
        json_erro('Método não permitido.', 405);
}
