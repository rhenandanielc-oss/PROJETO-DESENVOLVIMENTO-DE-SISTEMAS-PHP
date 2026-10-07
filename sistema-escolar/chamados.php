<?php
$titulo  = 'Help Desk - Chamados de TI';
$pagina  = 'chamados.php';
$scripts = ['chamados.js'];
require __DIR__ . '/includes/header.php';
$equipe = tem_perfil('admin', 'tecnico');
?>
<div class="card">
    <div class="barra-acoes">
        <form class="filtros" id="filtros">
            <label>Buscar <input name="busca" placeholder="Título, descrição, local"></label>
            <label>Status
                <select name="status">
                    <option value="">Todos</option><option value="aberto">Aberto</option><option value="em_andamento">Em andamento</option>
                    <option value="aguardando">Aguardando</option><option value="resolvido">Resolvido</option><option value="fechado">Fechado</option>
                </select>
            </label>
            <label>Categoria
                <select name="categoria">
                    <option value="">Todas</option><option value="hardware">Hardware</option><option value="software">Software</option>
                    <option value="rede">Rede</option><option value="impressora">Impressora</option><option value="acesso">Acesso</option><option value="outro">Outro</option>
                </select>
            </label>
            <?php if ($equipe): ?>
                <label class="check" style="margin-top:22px"><input type="checkbox" name="meus"> Só os que eu abri</label>
            <?php endif; ?>
        </form>
        <button class="btn btn-primario" id="btn-novo">+ Abrir chamado</button>
    </div>
    <div class="tabela-wrap">
        <table>
            <thead><tr><th>#</th><th>Chamado</th><th>Categoria</th><th>Prioridade</th><th>Solicitante</th><th>Técnico</th><th>Aberto em</th><th>Status</th><th></th></tr></thead>
            <tbody id="tabela"></tbody>
        </table>
    </div>
</div>

<!-- Abrir / editar chamado -->
<div class="modal" id="modal-chamado">
    <div class="modal-caixa">
        <div class="modal-topo"><h3 id="modal-titulo">Abrir chamado</h3><button class="modal-fechar" data-fechar="modal-chamado">&times;</button></div>
        <form class="modal-corpo" id="form-chamado">
            <input type="hidden" name="id">
            <label>Título * <input name="titulo" required maxlength="150" placeholder="Resumo do problema"></label>
            <label>Descrição * <textarea name="descricao" required placeholder="Descreva o problema com detalhes: o que aconteceu, desde quando, mensagens de erro..."></textarea></label>
            <div class="grade-3">
                <label>Categoria *
                    <select name="categoria">
                        <option value="hardware">Hardware</option><option value="software">Software</option><option value="rede">Rede / Internet</option>
                        <option value="impressora">Impressora</option><option value="acesso">Acesso / Senha</option><option value="outro">Outro</option>
                    </select>
                </label>
                <label>Prioridade
                    <select name="prioridade"><option value="baixa">Baixa</option><option value="media" selected>Média</option><option value="alta">Alta</option><option value="critica">Crítica</option></select>
                </label>
                <label>Local <input name="local" maxlength="100" placeholder="Ex.: Lab. 1"></label>
            </div>
            <div class="modal-rodape">
                <button type="button" class="btn" data-fechar="modal-chamado">Cancelar</button>
                <button type="submit" class="btn btn-primario">Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- Detalhe do chamado -->
<div class="modal" id="modal-detalhe">
    <div class="modal-caixa largo">
        <div class="modal-topo"><h3 id="detalhe-titulo"></h3><button class="modal-fechar" data-fechar="modal-detalhe">&times;</button></div>
        <div class="modal-corpo">
            <div id="detalhe-info"></div>

            <?php if ($equipe): ?>
            <form id="form-atendimento" class="card" style="background:#f8fafc;box-shadow:none;border:1px solid var(--borda)">
                <h2>🛠️ Atendimento</h2>
                <div class="grade-3">
                    <label>Status
                        <select name="status">
                            <option value="aberto">Aberto</option><option value="em_andamento">Em andamento</option><option value="aguardando">Aguardando (peça/usuário)</option>
                            <option value="resolvido">Resolvido</option><option value="fechado">Fechado</option>
                        </select>
                    </label>
                    <label>Técnico responsável <select name="tecnico_id" id="campo-tecnico"></select></label>
                    <label>Prioridade
                        <select name="prioridade"><option value="baixa">Baixa</option><option value="media">Média</option><option value="alta">Alta</option><option value="critica">Crítica</option></select>
                    </label>
                </div>
                <label>Solução aplicada <textarea name="solucao" placeholder="Obrigatório para marcar como resolvido"></textarea></label>
                <button type="submit" class="btn btn-primario">Salvar atendimento</button>
            </form>
            <?php endif; ?>

            <div id="acoes-solicitante" class="barra-acoes"></div>

            <h3 style="font-size:1rem">💬 Histórico e comentários</h3>
            <div class="timeline" id="comentarios"></div>
            <form id="form-comentario" class="grade-2" style="grid-template-columns:1fr auto;align-items:end">
                <label style="margin:0">Novo comentário <input name="mensagem" required placeholder="Escreva uma atualização ou dúvida..."></label>
                <button type="submit" class="btn btn-primario">Enviar</button>
            </form>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
