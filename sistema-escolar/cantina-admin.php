<?php
require_once __DIR__ . '/includes/auth.php';
exigir_perfil('admin');

$titulo  = 'Gestão da Cantina';
$pagina  = 'cantina-admin.php';
$scripts = ['cantina-admin.js'];
require __DIR__ . '/includes/header.php';
?>
<div class="abas">
    <button class="aba ativa" data-aba="painel-pedidos">🧾 Pedidos do dia</button>
    <button class="aba" data-aba="painel-historico">📜 Histórico</button>
    <button class="aba" data-aba="painel-produtos">🥪 Produtos / Estoque</button>
</div>

<section id="painel-pedidos" class="painel-aba ativo">
    <div class="barra-acoes">
        <p class="texto-suave">Atualiza automaticamente a cada 20 segundos.</p>
        <button class="btn" id="btn-atualizar">↻ Atualizar</button>
    </div>
    <div class="kanban" id="kanban"></div>
</section>

<section id="painel-historico" class="painel-aba">
    <div class="card">
        <div class="barra-acoes">
            <form class="filtros" id="filtros-historico">
                <label>Data <input type="date" name="data"></label>
                <label>Status
                    <select name="status">
                        <option value="">Todos</option><option value="pendente">Pendente</option><option value="preparando">Preparando</option>
                        <option value="pronto">Pronto</option><option value="entregue">Entregue</option><option value="cancelado">Cancelado</option>
                    </select>
                </label>
            </form>
        </div>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>#</th><th>Data</th><th>Cliente</th><th>Itens</th><th>Pagamento</th><th>Total</th><th>Status</th><th></th></tr></thead>
                <tbody id="tabela-historico"></tbody>
            </table>
        </div>
    </div>
</section>

<section id="painel-produtos" class="painel-aba">
    <div class="card">
        <div class="barra-acoes">
            <p class="texto-suave">Produtos com estoque baixo (menos de 10) aparecem destacados.</p>
            <button class="btn btn-primario" id="btn-novo-produto">+ Novo produto</button>
        </div>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Produto</th><th>Categoria</th><th>Preço</th><th>Estoque</th><th>Situação</th><th></th></tr></thead>
                <tbody id="tabela-produtos"></tbody>
            </table>
        </div>
    </div>
</section>

<div class="modal" id="modal-produto">
    <div class="modal-caixa">
        <div class="modal-topo"><h3 id="modal-titulo">Novo produto</h3><button class="modal-fechar" data-fechar="modal-produto">&times;</button></div>
        <form class="modal-corpo" id="form-produto">
            <input type="hidden" name="id">
            <label>Nome * <input name="nome" required maxlength="100"></label>
            <label>Descrição <input name="descricao" maxlength="255"></label>
            <div class="grade-3">
                <label>Categoria *
                    <select name="categoria">
                        <option value="salgado">Salgado</option><option value="doce">Doce</option><option value="bebida">Bebida</option>
                        <option value="lanche">Lanche</option><option value="refeicao">Refeição</option><option value="outro">Outro</option>
                    </select>
                </label>
                <label>Preço (R$) * <input type="number" name="preco" step="0.01" min="0.01" required></label>
                <label>Estoque <input type="number" name="estoque" min="0" value="0"></label>
            </div>
            <label class="check"><input type="checkbox" name="ativo" checked> Disponível no cardápio</label>
            <div class="modal-rodape">
                <button type="button" class="btn" data-fechar="modal-produto">Cancelar</button>
                <button type="submit" class="btn btn-primario">Salvar</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
