<?php
/**
 * API - Usuários (CRUD - somente administrador)
 *
 *   GET    api/usuarios.php             lista (filtros: perfil, busca)
 *   GET    api/usuarios.php?id=1
 *   POST   api/usuarios.php             cria
 *   PUT    api/usuarios.php?id=1        atualiza (senha opcional)
 *   DELETE api/usuarios.php?id=1        exclui
 *
 *   PUT    api/usuarios.php?perfil=1    qualquer usuário altera o próprio nome/senha
 */
require_once __DIR__ . '/_bootstrap.php';
api_exigir_login();

const CAMPOS_PUBLICOS = 'id, nome, email, perfil, matricula, turma, ativo, criado_em';

// Atualização do próprio perfil (disponível para todos)
if ($METODO === 'PUT' && !empty($_GET['perfil'])) {
    $d = corpo_requisicao();
    exigir_campos($d, ['nome' => 'Nome']);
    if (!empty($d['nova_senha'])) {
        $stmt = db()->prepare('SELECT senha FROM usuarios WHERE id = ?');
        $stmt->execute([usuario_id()]);
        if (!password_verify($d['senha_atual'] ?? '', $stmt->fetchColumn())) {
            json_erro('Senha atual incorreta.', 422);
        }
        if (strlen($d['nova_senha']) < 6) {
            json_erro('A nova senha deve ter pelo menos 6 caracteres.', 422);
        }
        db()->prepare('UPDATE usuarios SET senha = ? WHERE id = ?')
            ->execute([password_hash($d['nova_senha'], PASSWORD_DEFAULT), usuario_id()]);
    }
    db()->prepare('UPDATE usuarios SET nome = ? WHERE id = ?')->execute([trim($d['nome']), usuario_id()]);
    $_SESSION['usuario']['nome'] = trim($d['nome']);
    json_resposta(['mensagem' => 'Perfil atualizado!']);
}

api_exigir_perfil('admin');

function dados_usuario(array $d, bool $novo): array
{
    exigir_campos($d, ['nome' => 'Nome', 'email' => 'E-mail', 'perfil' => 'Perfil']);
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        json_erro('E-mail inválido.', 422);
    }
    if ($novo && strlen($d['senha'] ?? '') < 6) {
        json_erro('A senha deve ter pelo menos 6 caracteres.', 422);
    }
    return [
        trim($d['nome']),
        trim($d['email']),
        validar_opcao($d['perfil'], array_keys(PERFIS), 'Perfil'),
        trim($d['matricula'] ?? '') ?: null,
        trim($d['turma'] ?? '') ?: null,
        !empty($d['ativo']) ? 1 : 0,
    ];
}

switch ($METODO) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = db()->prepare('SELECT ' . CAMPOS_PUBLICOS . ' FROM usuarios WHERE id = ?');
            $stmt->execute([id_url()]);
            json_resposta($stmt->fetch() ?: json_erro('Usuário não encontrado.', 404));
        }
        $sql = 'SELECT ' . CAMPOS_PUBLICOS . ' FROM usuarios WHERE 1 = 1';
        $params = [];
        if (!empty($_GET['perfil'])) {
            $sql .= ' AND perfil = ?';
            $params[] = $_GET['perfil'];
        }
        if (!empty($_GET['busca'])) {
            $sql .= ' AND (nome LIKE ? OR email LIKE ? OR matricula LIKE ?)';
            array_push($params, ...array_fill(0, 3, '%' . $_GET['busca'] . '%'));
        }
        $stmt = db()->prepare($sql . ' ORDER BY nome');
        $stmt->execute($params);
        json_resposta($stmt->fetchAll());

    case 'POST':
        $d = corpo_requisicao();
        $v = dados_usuario($d, true);
        db()->prepare(
            'INSERT INTO usuarios (nome, email, perfil, matricula, turma, ativo, senha) VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([...$v, password_hash($d['senha'], PASSWORD_DEFAULT)]);
        json_resposta(['mensagem' => 'Usuário cadastrado!', 'id' => (int) db()->lastInsertId()], 201);

    case 'PUT':
        $id = id_url();
        $d  = corpo_requisicao();
        $v  = dados_usuario($d, false);
        if ($id === usuario_id() && ($v[2] !== 'admin' || !$v[5])) {
            json_erro('Você não pode remover seu próprio acesso de administrador.', 422);
        }
        if (!empty($d['senha']) && strlen($d['senha']) < 6) {
            json_erro('A senha deve ter pelo menos 6 caracteres.', 422);
        }
        db()->prepare(
            'UPDATE usuarios SET nome = ?, email = ?, perfil = ?, matricula = ?, turma = ?, ativo = ? WHERE id = ?'
        )->execute([...$v, $id]);
        if (!empty($d['senha'])) {
            db()->prepare('UPDATE usuarios SET senha = ? WHERE id = ?')
                ->execute([password_hash($d['senha'], PASSWORD_DEFAULT), $id]);
        }
        json_resposta(['mensagem' => 'Usuário atualizado!']);

    case 'DELETE':
        $id = id_url();
        if ($id === usuario_id()) {
            json_erro('Você não pode excluir o seu próprio usuário.', 422);
        }
        db()->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
        json_resposta(['mensagem' => 'Usuário excluído!']);

    default:
        json_erro('Método não permitido.', 405);
}
