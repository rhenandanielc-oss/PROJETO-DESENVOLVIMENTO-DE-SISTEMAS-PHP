<?php
/**
 * API de autenticação
 *   POST api/auth.php?acao=login     { email, senha }
 *   POST api/auth.php?acao=cadastro  { nome, email, senha, matricula, turma }  (cadastro de aluno)
 *   POST api/auth.php?acao=logout
 *   GET  api/auth.php                -> usuário logado
 */
require_once __DIR__ . '/_bootstrap.php';

$acao = $_GET['acao'] ?? '';

if ($METODO === 'GET') {
    api_exigir_login();
    json_resposta(['usuario' => usuario_logado()]);
}

if ($METODO !== 'POST') {
    json_erro('Método não permitido.', 405);
}

$dados = corpo_requisicao();

switch ($acao) {
    case 'login':
        exigir_campos($dados, ['email' => 'E-mail', 'senha' => 'Senha']);

        $stmt = db()->prepare('SELECT * FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([trim($dados['email'])]);
        $usuario = $stmt->fetch();

        if (!$usuario || !password_verify($dados['senha'], $usuario['senha'])) {
            json_erro('E-mail ou senha incorretos.', 401);
        }
        if (!$usuario['ativo']) {
            json_erro('Usuário desativado. Procure a secretaria.', 403);
        }

        autenticar($usuario);
        json_resposta(['mensagem' => 'Login realizado!', 'usuario' => usuario_logado(), 'csrf' => csrf_token()]);

    case 'cadastro':
        exigir_campos($dados, [
            'nome' => 'Nome', 'email' => 'E-mail', 'senha' => 'Senha',
            'matricula' => 'Matrícula', 'turma' => 'Turma',
        ]);
        if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            json_erro('E-mail inválido.', 422);
        }
        if (strlen($dados['senha']) < 6) {
            json_erro('A senha deve ter pelo menos 6 caracteres.', 422);
        }

        $stmt = db()->prepare(
            'INSERT INTO usuarios (nome, email, senha, perfil, matricula, turma) VALUES (?, ?, ?, "aluno", ?, ?)'
        );
        $stmt->execute([
            trim($dados['nome']), trim($dados['email']),
            password_hash($dados['senha'], PASSWORD_DEFAULT),
            trim($dados['matricula']), trim($dados['turma']),
        ]);
        json_resposta(['mensagem' => 'Cadastro realizado! Faça login.'], 201);

    case 'logout':
        encerrar_sessao();
        json_resposta(['mensagem' => 'Sessão encerrada.']);

    default:
        json_erro('Ação inválida.', 400);
}
