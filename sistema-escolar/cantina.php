<?php
$titulo  = 'Cantina Escolar';
$pagina  = 'cantina.php';
$scripts = ['cantina.js'];
require __DIR__ . '/includes/header.php';
?>
<div class="abas">
    <button class="aba ativa" data-aba="painel-cardapio">🍽️ Cardápio</button>
    <button class="aba" data-aba="painel-pedidos">🧾 Meus pedidos</button>
</div>

<section id="painel-cardapio" class="painel-aba ativo">
    <div class="cantina-layout">
        <div>
            <div class="categoria-filtro" id="categorias"></div>
            <div class="produtos-grid" id="produtos"></div>
        </div>
        <aside class="card carrinho">
            <h2>🛒 Meu pedido</h2>
            <div id="carrinho-itens"><p class="vazio">Carrinho vazio</p></div>
            <div class="carrinho-total"><span>Total</span><span id="carrinho-total">R$ 0,00</span></div>
            <label>Pagamento
                <select id="pagamento"><option value="pix">PIX</option><option value="dinheiro">Dinheiro</option><option value="cartao">Cartão</option></select>
            </label>
            <label>Observação <input id="observacao" maxlength="255" placeholder="Ex.: sem cebola, retirar no intervalo"></label>
            <button class="btn btn-sucesso btn-bloco" id="btn-finalizar" disabled>Finalizar pedido</button>
        </aside>
    </div>
</section>

<section id="painel-pedidos" class="painel-aba">
    <div class="card">
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>#</th><th>Data</th><th>Itens</th><th>Pagamento</th><th>Total</th><th>Status</th><th></th></tr></thead>
                <tbody id="tabela-pedidos"></tbody>
            </table>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
