<?php
/**
 * Cabeçalho + menu lateral comum a todas as páginas internas.
 * Antes de incluir, defina: $titulo (texto da aba) e $pagina (item ativo do menu).
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
exigir_login();

$usuario = usuario_logado();

// [arquivo, rótulo, ícone, perfis que podem ver (vazio = todos)]
$menu = [
    ['dashboard.php',     'Painel',            '🏠', []],
    ['tarefas.php',       'Minhas Tarefas',    '📚', []],
    ['reservas.php',      'Reservas de Salas', '🏫', []],
    ['salas.php',         'Salas e Labs',      '🚪', ['admin']],
    ['cantina.php',       'Cantina',           '🍔', []],
    ['cantina-admin.php', 'Gestão da Cantina', '🧾', ['admin']],
    ['chamados.php',      'Chamados de TI',    '🛠️', []],
    ['relatorios.php',    'Relatórios',        '📊', []],
    ['usuarios.php',      'Usuários',          '👥', ['admin']],
    ['banco.php',         'Banco de Dados',    '🗄️', ['admin']],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($titulo ?? 'Sistema') ?> | <?= e(APP_NOME) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="logo">🎓 <span><?= e(APP_NOME) ?></span></div>
        <nav>
            <?php foreach ($menu as [$arquivo, $rotulo, $icone, $perfis]): ?>
                <?php if (!$perfis || tem_perfil(...$perfis)): ?>
                    <a href="<?= $arquivo ?>" class="<?= ($pagina ?? '') === $arquivo ? 'ativo' : '' ?>">
                        <span class="icone"><?= $icone ?></span> <?= $rotulo ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-rodape">
            <a href="perfil.php" class="usuario-box">
                <strong><?= e($usuario['nome']) ?></strong>
                <small><?= e(PERFIS[$usuario['perfil']]) ?></small>
            </a>
            <a href="logout.php" class="btn btn-sair">Sair</a>
        </div>
    </aside>

    <div class="conteudo">
        <header class="topo">
            <button class="btn-menu" onclick="document.getElementById('sidebar').classList.toggle('aberto')" aria-label="Menu">☰</button>
            <h1><?= e($titulo ?? '') ?></h1>
        </header>
        <main class="principal">
            <?php if (($_GET['erro'] ?? '') === 'acesso'): ?>
                <div class="alerta alerta-erro">Você não tem permissão para acessar aquela página.</div>
            <?php endif; ?>
