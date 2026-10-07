/* Cantina - cardápio, carrinho e meus pedidos */
const EMOJIS = { salgado: '🥟', doce: '🍫', bebida: '🥤', lanche: '🥪', refeicao: '🍛', outro: '🛍️' };
let produtos = [];
let categoriaAtual = '';
const carrinho = new Map(); // produto_id -> quantidade

async function carregarProdutos() {
    try {
        produtos = await App.api('api/produtos.php?disponiveis=1');
        const cats = [...new Set(produtos.map(p => p.categoria))];
        document.getElementById('categorias').innerHTML =
            ['', ...cats].map(c => `<button class="${c === categoriaAtual ? 'ativo' : ''}" data-cat="${c}">${c ? App.rotulo(c) : 'Todos'}</button>`).join('');
        renderizarProdutos();
    } catch (e) { App.erro(e); }
}

function renderizarProdutos() {
    const lista = produtos.filter(p => !categoriaAtual || p.categoria === categoriaAtual);
    document.getElementById('produtos').innerHTML = lista.length ? lista.map(p => `
        <div class="produto">
            <div class="emoji">${EMOJIS[p.categoria] || '🛍️'}</div>
            <h4>${App.esc(p.nome)}</h4>
            <small>${App.esc(p.descricao || '')}<br>Estoque: ${p.estoque}</small>
            <div class="preco">${App.moeda(p.preco)}</div>
            <button class="btn btn-primario btn-pequeno" data-add="${p.id}">Adicionar</button>
        </div>`).join('') : '<p class="vazio">Nenhum produto disponível.</p>';
}

function renderizarCarrinho() {
    const caixa = document.getElementById('carrinho-itens');
    let total = 0;
    if (!carrinho.size) {
        caixa.innerHTML = '<p class="vazio">Carrinho vazio</p>';
    } else {
        caixa.innerHTML = [...carrinho].map(([id, qtd]) => {
            const p = produtos.find(x => x.id == id);
            total += p.preco * qtd;
            return `<div class="carrinho-item">
                <div><strong>${App.esc(p.nome)}</strong><br><small>${App.moeda(p.preco)} un.</small></div>
                <div class="qtd"><button data-menos="${id}">−</button><span>${qtd}</span><button data-mais="${id}">+</button></div>
            </div>`;
        }).join('');
    }
    document.getElementById('carrinho-total').textContent = App.moeda(total);
    document.getElementById('btn-finalizar').disabled = !carrinho.size;
}

function alterarQtd(id, delta) {
    const p = produtos.find(x => x.id == id);
    const nova = (carrinho.get(id) || 0) + delta;
    if (nova > p.estoque) return App.toast(`Só há ${p.estoque} unidade(s) de ${p.nome}.`, 'erro');
    nova <= 0 ? carrinho.delete(id) : carrinho.set(id, nova);
    renderizarCarrinho();
}

document.getElementById('categorias').addEventListener('click', ev => {
    if (ev.target.dataset.cat === undefined) return;
    categoriaAtual = ev.target.dataset.cat;
    document.querySelectorAll('#categorias button').forEach(b => b.classList.toggle('ativo', b === ev.target));
    renderizarProdutos();
});
document.getElementById('produtos').addEventListener('click', ev => {
    if (ev.target.dataset.add) alterarQtd(Number(ev.target.dataset.add), 1);
});
document.getElementById('carrinho-itens').addEventListener('click', ev => {
    if (ev.target.dataset.mais) alterarQtd(Number(ev.target.dataset.mais), 1);
    if (ev.target.dataset.menos) alterarQtd(Number(ev.target.dataset.menos), -1);
});

document.getElementById('btn-finalizar').addEventListener('click', async (ev) => {
    ev.target.disabled = true;
    try {
        const r = await App.api('api/pedidos.php', {
            method: 'POST',
            body: {
                itens: [...carrinho].map(([produto_id, quantidade]) => ({ produto_id, quantidade })),
                forma_pagamento: document.getElementById('pagamento').value,
                observacao: document.getElementById('observacao').value,
            },
        });
        App.toast(`${r.mensagem} Total: ${App.moeda(r.total)}`);
        carrinho.clear();
        document.getElementById('observacao').value = '';
        renderizarCarrinho();
        carregarProdutos();
        carregarPedidos();
    } catch (e) {
        App.erro(e);
        ev.target.disabled = false;
    }
});

/* ---------- Meus pedidos ---------- */
const tabelaPedidos = document.getElementById('tabela-pedidos');

async function carregarPedidos() {
    try {
        const pedidos = await App.api('api/pedidos.php?meus=1');
        tabelaPedidos.innerHTML = pedidos.length ? pedidos.map(p => `
            <tr>
                <td>${p.id}</td>
                <td>${App.dataHora(p.criado_em)}</td>
                <td>${App.esc(p.resumo || '')}${p.observacao ? `<br><small class="texto-suave">Obs.: ${App.esc(p.observacao)}</small>` : ''}</td>
                <td>${App.rotulo(p.forma_pagamento)}</td>
                <td><strong>${App.moeda(p.total)}</strong></td>
                <td>${App.badge(p.status)}</td>
                <td class="acoes">${p.status === 'pendente' ? `<button class="btn btn-pequeno btn-perigo" data-cancelar="${p.id}">Cancelar</button>` : ''}</td>
            </tr>`).join('') : '<tr><td colspan="7" class="vazio">Você ainda não fez pedidos.</td></tr>';
    } catch (e) { App.erro(e); }
}

tabelaPedidos.addEventListener('click', async ev => {
    const id = ev.target.dataset.cancelar;
    if (!id || !App.confirmar('Cancelar este pedido?')) return;
    try {
        App.toast((await App.api(`api/pedidos.php?id=${id}`, { method: 'PATCH', body: { status: 'cancelado' } })).mensagem);
        carregarPedidos();
        carregarProdutos();
    } catch (e) { App.erro(e); }
});

carregarProdutos();
carregarPedidos();
