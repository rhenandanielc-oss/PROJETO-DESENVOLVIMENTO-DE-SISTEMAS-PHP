<?php
$titulo  = 'Relatórios';
$pagina  = 'relatorios.php';
$scripts = ['relatorios.js'];
require __DIR__ . '/includes/header.php';

// Relatórios disponíveis conforme o perfil
$tipos = ['tarefas' => '📚 Tarefas', 'reservas' => '🏫 Reservas'];
if (tem_perfil('admin')) {
    $tipos['cantina'] = '🍔 Cantina (vendas)';
}
if (tem_perfil('admin', 'tecnico')) {
    $tipos['chamados'] = '🛠️ Chamados de TI';
}
?>
<div class="card nao-imprimir">
    <form class="filtros" id="filtros">
        <label>Relatório
            <select name="tipo">
                <?php foreach ($tipos as $valor => $rotulo): ?>
                    <option value="<?= $valor ?>" <?= ($_GET['tipo'] ?? '') === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>De <input type="date" name="inicio"></label>
        <label>Até <input type="date" name="fim"></label>
        <button type="submit" class="btn btn-primario">Gerar</button>
        <button type="button" class="btn" id="btn-imprimir">🖨️ Imprimir / PDF</button>
        <button type="button" class="btn" id="btn-csv">⬇️ Exportar CSV (Excel)</button>
    </form>
</div>

<div class="cabecalho-impressao">
    <h2><?= e(APP_NOME) ?> - <span id="titulo-impressao"></span></h2>
    <p>Emitido por <?= e($usuario['nome']) ?> em <?= date('d/m/Y H:i') ?></p>
</div>

<div class="card">
    <h2 id="titulo-relatorio">Resumo</h2>
    <div class="resumo-relatorio" id="resumo"></div>
</div>
<div class="colunas" id="graficos"></div>
<div class="card">
    <h2>Detalhamento</h2>
    <div id="detalhes"></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
