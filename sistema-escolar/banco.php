<?php
require_once __DIR__ . '/includes/auth.php';
exigir_perfil('admin');

$titulo  = 'Banco de Dados';
$pagina  = 'banco.php';
$scripts = ['banco.js'];
require __DIR__ . '/includes/header.php';
?>
<div class="alerta alerta-info">
    Visualização direta do banco <strong><?= e(DB_NAME) ?></strong> (servidor <span id="servidor">...</span>).
    Também é possível acessar pelo <strong>phpMyAdmin</strong> (XAMPP: <code>http://localhost/phpmyadmin</code> |
    Docker: <code>http://localhost:8081</code>) ou por qualquer cliente MySQL - veja o <code>README.md</code>.
</div>

<div class="banco-layout">
    <aside class="card">
        <h2>🗄️ Tabelas e views</h2>
        <div class="lista-tabelas" id="lista-tabelas"></div>
    </aside>

    <div>
        <div class="card">
            <div class="abas">
                <button class="aba ativa" data-aba="painel-dados">📄 Dados</button>
                <button class="aba" data-aba="painel-estrutura">🧱 Estrutura</button>
                <button class="aba" data-aba="painel-sql">📝 CREATE TABLE</button>
                <button class="aba" data-aba="painel-console">💻 Console SQL</button>
            </div>
            <h2 id="nome-tabela" style="font-family:Consolas,monospace">Selecione uma tabela</h2>

            <section class="painel-aba ativo" id="painel-dados">
                <div class="barra-acoes"><span class="texto-suave" id="info-dados"></span>
                    <button class="btn btn-pequeno" id="btn-csv">⬇️ Exportar CSV</button></div>
                <div id="dados"></div>
            </section>
            <section class="painel-aba" id="painel-estrutura"><div id="estrutura"></div></section>
            <section class="painel-aba" id="painel-sql"><pre class="codigo" id="create-sql"></pre></section>
            <section class="painel-aba console-sql" id="painel-console">
                <form id="form-sql">
                    <label>Consulta (somente leitura: SELECT, SHOW, DESCRIBE, EXPLAIN)
                        <textarea name="sql" spellcheck="false">SELECT u.nome, COUNT(t.id) AS tarefas
FROM usuarios u
LEFT JOIN tarefas t ON t.usuario_id = u.id
GROUP BY u.id
ORDER BY tarefas DESC</textarea>
                    </label>
                    <button class="btn btn-primario">▶ Executar (Ctrl+Enter)</button>
                    <span class="texto-suave" id="info-sql" style="margin-left:10px"></span>
                </form>
                <div id="resultado-sql" style="margin-top:14px"></div>
            </section>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
