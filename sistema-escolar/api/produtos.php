<?php
/**
 * API - Produtos da cantina (CRUD - administrador altera)
 *
 *   GET    api/produtos.php                 lista (?disponiveis=1 -> só ativos com estoque)
 *   GET    api/produtos.php?id=1
 *   POST   api/produtos.php                 (admin)
 *   PUT    api/produtos.php?id=1            (admin)
 *   DELETE api/produtos.php?id=1            (admin) - se já foi vendido, apenas desativa
 */
require_once __DIR__ . '/_bootstrap.php';
api_exigir_login();

const CATEGORIAS_PRODUTO = ['salgado', 'doce', 'bebida', 'lanche', 'refeicao', 'outro'];

function dados_produto(array $d): array
{
    exigir_campos($d, ['nome' => 'Nome', 'categoria' => 'Categoria', 'preco' => 'Preço']);
    $preco   = (float) str_replace(',', '.', (string) $d['preco']);
    $estoque = (int) ($d['estoque'] ?? 0);
    if ($preco <= 0) {
        json_erro('O preço deve ser maior que zero.', 422);
    }
    if ($estoque < 0) {
        json_erro('O estoque não pode ser negativo.', 422);
    }
    return [
        trim($d['nome']),
        trim($d['descricao'] ?? '') ?: null,
        validar_opcao($d['categoria'], CATEGORIAS_PRODUTO, 'Categoria'),
        round($preco, 2),
        $estoque,
        !empty($d['ativo']) ? 1 : 0,
    ];
}

switch ($METODO) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = db()->prepare('SELECT * FROM produtos WHERE id = ?');
            $stmt->execute([id_url()]);
            json_resposta($stmt->fetch() ?: json_erro('Produto não encontrado.', 404));
        }
        $sql = 'SELECT * FROM produtos';
        if (!empty($_GET['disponiveis'])) {
            $sql .= ' WHERE ativo = 1 AND estoque > 0';
        }
        json_resposta(db()->query($sql . ' ORDER BY categoria, nome')->fetchAll());

    case 'POST':
        api_exigir_perfil('admin');
        db()->prepare(
            'INSERT INTO produtos (nome, descricao, categoria, preco, estoque, ativo) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute(dados_produto(corpo_requisicao()));
        json_resposta(['mensagem' => 'Produto cadastrado!', 'id' => (int) db()->lastInsertId()], 201);

    case 'PUT':
        api_exigir_perfil('admin');
        db()->prepare(
            'UPDATE produtos SET nome = ?, descricao = ?, categoria = ?, preco = ?, estoque = ?, ativo = ? WHERE id = ?'
        )->execute([...dados_produto(corpo_requisicao()), id_url()]);
        json_resposta(['mensagem' => 'Produto atualizado!']);

    case 'DELETE':
        api_exigir_perfil('admin');
        $id   = id_url();
        $stmt = db()->prepare('SELECT COUNT(*) FROM pedido_itens WHERE produto_id = ?');
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            // Mantém o histórico de vendas: apenas desativa
            db()->prepare('UPDATE produtos SET ativo = 0 WHERE id = ?')->execute([$id]);
            json_resposta(['mensagem' => 'Produto possui vendas registradas e foi apenas desativado.']);
        }
        db()->prepare('DELETE FROM produtos WHERE id = ?')->execute([$id]);
        json_resposta(['mensagem' => 'Produto excluído!']);

    default:
        json_erro('Método não permitido.', 405);
}
