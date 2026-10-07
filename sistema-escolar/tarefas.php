<?php
$titulo  = 'Minhas Tarefas';
$pagina  = 'tarefas.php';
$scripts = ['tarefas.js'];
require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <div class="barra-acoes">
        <form class="filtros" id="filtros">
            <label>Buscar <input name="busca" placeholder="Título ou disciplina"></label>
            <label>Status
                <select name="status">
                    <option value="">Todos</option>
                    <option value="pendente">Pendente</option>
                    <option value="em_andamento">Em andamento</option>
                    <option value="concluida">Concluída</option>
                </select>
            </label>
        </form>
        <button class="btn btn-primario" id="btn-nova">+ Nova tarefa</button>
    </div>

    <div class="tabela-wrap">
        <table>
            <thead><tr><th>Concluída</th><th>Tarefa</th><th>Disciplina</th><th>Entrega</th><th>Prioridade</th><th>Status</th><th></th></tr></thead>
            <tbody id="tabela"></tbody>
        </table>
    </div>
</div>

<!-- Modal de cadastro/edição -->
<div class="modal" id="modal-tarefa">
    <div class="modal-caixa">
        <div class="modal-topo"><h3 id="modal-titulo">Nova tarefa</h3><button class="modal-fechar" data-fechar="modal-tarefa">&times;</button></div>
        <form class="modal-corpo" id="form-tarefa">
            <input type="hidden" name="id">
            <label>Título * <input name="titulo" required maxlength="150"></label>
            <label>Descrição <textarea name="descricao"></textarea></label>
            <div class="grade-2">
                <label>Disciplina <input name="disciplina" maxlength="80" list="disciplinas"></label>
                <label>Data de entrega * <input type="date" name="data_entrega" required></label>
            </div>
            <datalist id="disciplinas">
                <option>Banco de Dados</option><option>Programação Web</option><option>Redes</option>
                <option>Front-end</option><option>Matemática</option><option>Português</option><option>Projeto</option>
            </datalist>
            <div class="grade-2">
                <label>Prioridade
                    <select name="prioridade"><option value="baixa">Baixa</option><option value="media" selected>Média</option><option value="alta">Alta</option></select>
                </label>
                <label>Status
                    <select name="status"><option value="pendente">Pendente</option><option value="em_andamento">Em andamento</option><option value="concluida">Concluída</option></select>
                </label>
            </div>
            <div class="modal-rodape">
                <button type="button" class="btn" data-fechar="modal-tarefa">Cancelar</button>
                <button type="submit" class="btn btn-primario">Salvar</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
