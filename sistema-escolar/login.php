<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

if (usuario_logado()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title>Entrar | <?= e(APP_NOME) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="pagina-login">
<div class="login-card">
    <div class="login-logo">🎓</div>
    <h1><?= e(APP_NOME) ?></h1>
    <p class="texto-suave">Agenda • Reservas • Cantina • Help Desk</p>

    <?php if (isset($_GET['saiu'])): ?>
        <div class="alerta alerta-sucesso">Você saiu do sistema.</div>
    <?php endif; ?>

    <div class="abas">
        <button class="aba ativa" data-aba="form-login">Entrar</button>
        <button class="aba" data-aba="form-cadastro">Sou aluno, quero me cadastrar</button>
    </div>

    <form id="form-login" class="painel-aba ativo">
        <label>E-mail <input type="email" name="email" required autofocus placeholder="seu@email.com"></label>
        <label>Senha <input type="password" name="senha" required placeholder="••••••"></label>
        <button type="submit" class="btn btn-primario btn-bloco">Entrar</button>
        <details class="demo">
            <summary>Usuários de demonstração</summary>
            <p>Senha de todos: <code>123456</code></p>
            <ul>
                <li><a href="#" data-email="admin@escola.com">admin@escola.com</a> - Administrador</li>
                <li><a href="#" data-email="professor@escola.com">professor@escola.com</a> - Professor</li>
                <li><a href="#" data-email="aluno@escola.com">aluno@escola.com</a> - Aluno</li>
                <li><a href="#" data-email="tecnico@escola.com">tecnico@escola.com</a> - Técnico de TI</li>
            </ul>
        </details>
    </form>

    <form id="form-cadastro" class="painel-aba">
        <label>Nome completo <input name="nome" required></label>
        <label>E-mail <input type="email" name="email" required></label>
        <div class="grade-2">
            <label>Matrícula <input name="matricula" required></label>
            <label>Turma <input name="turma" required placeholder="Ex.: 2º DS - A"></label>
        </div>
        <label>Senha <input type="password" name="senha" required minlength="6"></label>
        <button type="submit" class="btn btn-primario btn-bloco">Cadastrar</button>
    </form>
</div>

<div id="toasts" class="toasts"></div>
<script src="assets/js/app.js"></script>
<script src="assets/js/login.js"></script>
</body>
</html>
