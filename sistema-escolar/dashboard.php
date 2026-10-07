<?php
$titulo  = 'Painel';
$pagina  = 'dashboard.php';
$scripts = ['dashboard.js'];
require __DIR__ . '/includes/header.php';
?>
<p class="texto-suave" style="margin-bottom:18px">Olá, <strong><?= e($usuario['nome']) ?></strong>! Veja o resumo das suas atividades.</p>

<div class="cards-grid" id="cards"></div>

<div class="colunas">
    <div class="card">
        <h2>📚 Próximas tarefas</h2>
        <div id="lista-tarefas"></div>
        <a href="tarefas.php" class="btn btn-pequeno" style="margin-top:12px">Ver agenda</a>
    </div>
    <div class="card">
        <h2>🏫 Minhas próximas reservas</h2>
        <div id="lista-reservas"></div>
        <a href="reservas.php" class="btn btn-pequeno" style="margin-top:12px">Reservar sala</a>
    </div>
    <div class="card">
        <h2>🛠️ Últimos chamados</h2>
        <div id="lista-chamados"></div>
        <a href="chamados.php" class="btn btn-pequeno" style="margin-top:12px">Abrir chamado</a>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
