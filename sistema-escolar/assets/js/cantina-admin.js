/* Gestão da cantina (admin): fila de pedidos, histórico e CRUD de produtos */
const COLUNAS = [
    ['pendente', '🕐 Pendentes', 'preparando', 'Preparar'],
    ['preparando', '👩‍🍳 Preparando', 'pronto', 'Pronto'],
    ['pronto', '✅ Prontos p/ retirada', 'entregue', 'Entregar'],
    ['entregue', '📦 Entregues hoje', null, null],
];

async function mudarStatus(id, status) {
    if (status === 'cancelado' && !App.confirmar('Cancelar o pedido? O estoque será devolvido.')) return;
    try {
        App.toast((await App.api(`api/pedidos.php?id=${id}`, { method: 'PATCH', body: { status } })).mensagem);
        carregarKanban();
        carregarHistorico();
        carregarProdutos();
    } catch (e) { App.erro(e); }
}

/* ---------- Kanban de pedidos do dia ---------- */
async function carregarKanban() {
    try {
        const pedidos = await App.api(`api/pedidos.php?data=${App.hoje()}`);
        document.getElementById('kanban').innerHTML = COLUNAS.map(([status, titulo, proximo, rotulo]) => {
            const lista = pedidos.filter(p => p.status === status);
            return `<div class="kanban-coluna">
                <h3>${titulo} <span class="badge">${lista.length}</span></h3>
                ${lista.map(p => `
                    <div class="pedido-card">
                        <div class="topo-card"><span>#${p.id} - ${App.esc(p.cliente)}</span><span>${App.moeda(p.total)}</span></div>
                        <div>${App.esc(p.resumo || '')}</div>
                        ${p.observacao ? `<small class="texto-suave">Obs.: ${App.esc(p.observacao)}</small><br>` : ''}
                        <small class="texto-suave">${App.hora(p.criado_em.substring(11))} • ${App.rotulo(p.forma_pagamento)}</small>
                        <div class="acoes-card">
                            ${proximo ? `<button class="btn btn-pequeno btn-primario" data-id="${p.id}" data-status="${proximo}">${rotulo} →</button>` : ''}
                            ${status !== 'entregue' ? `<button class="btn btn-pequeno btn-perigo" data-id="${p.id}" data-status="cancelado">Cancelar</button>` : ''}
                        </div>
                    </div>`).join('') || '<p class="texto-suave" style="font-size:.85rem">Nenhum pedido</p>'}
            </div>`;
        }).join('');
    } catch (e) { App.erro(e); }
}

document.getElementById('kanban').addEventListener('click', ev => {
    const b = ev.target.closest('button[data-status]');
    if (b) mudarStatus(b.dataset.id, b.dataset.status);
});
document.getElementById('btn-atualizar').addEventListener('click', carregarKanban);
setInterval(carregarKanban, 20000);

/* ---------- Histórico ---------- */
const filtrosHist = document.getElementById('filtros-historico');
const tabelaHist = document.getElementById('tabela-historico');

async function carregarHistorico() {
    try {
        const pedidos = await App.api('api/pedidos.php?' + new URLSearchParams(App.lerForm(filtrosHist)));
        tabelaHist.innerHTML = pedidos.length ? pedidos.map(p => `
            <tr>
                <td>${p.id}</td><td>${App.dataHora(p.criado_em)}</td><td>${App.esc(p.cliente)}</td>
                <td><small>${App.esc(p.resumo || '')}</small></td><td>${App.rotulo(p.forma_pagamento)}</td>
                <td>${App.moeda(p.total)}</td><td>${App.badge(p.status)}</td>
                <td class="acoes"><button class="btn btn-pequeno btn-perigo" data-excluir="${p.id}">Excluir</button></td>
            </tr>`).join('') : '<tr><td colspan="8" class="vazio">Nenhum pedido encontrado.</td></tr>';
    } catch (e) { App.erro(e); }
}
filtrosHist.addEventListener('change', carregarHistorico);
tabelaHist.addEventListener('click', async ev => {
    const id = ev.target.dataset.excluir;
    if (!id || !App.confirmar('Excluir este pedido do histórico?')) return;
    try {
        App.toast((await App.api(`api/pedidos.php?id=${id}`, { method: 'DELETE' })).mensagem);
        carregarHistorico(); carregarKanban(); carregarProdutos();
    } catch (e) { App.erro(e); }
});

/* ---------- Produtos (CRUD) ---------- */
const formProd = document.getElementById('form-produto');
const tabelaProd = document.getElementById('tabela-produtos');
let produtos = [];

async function carregarProdutos() {
    try {
        produtos = await App.api('api/produtos.php');
        tabelaProd.innerHTML = produtos.map(p => `
            <tr class="${p.estoque < 10 ? 'atrasada' : ''}">
                <td><strong>${App.esc(p.nome)}</strong><br><small class="texto-suave">${App.esc(p.descricao || '')}</small></td>
                <td>${App.rotulo(p.categoria)}</td>
                <td>${App.moeda(p.preco)}</td>
                <td>${p.estoque}${p.estoque < 10 ? ' ⚠️' : ''}</td>
                <td>${App.badge(Number(p.ativo) ? 'ativo' : 'inativo')}</td>
                <td class="acoes">
                    <button class="btn btn-pequeno" data-editar="${p.id}">Editar</button>
                    <button class="btn btn-pequeno btn-perigo" data-excluir="${p.id}">Excluir</button>
                </td>
            </tr>`).join('');
    } catch (e) { App.erro(e); }
}

document.getElementById('btn-novo-produto').addEventListener('click', () => {
    formProd.reset();
    formProd.elements.id.value = '';
    document.getElementById('modal-titulo').textContent = 'Novo produto';
    App.abrirModal('modal-produto');
});

formProd.addEventListener('submit', async ev => {
    ev.preventDefault();
    const d = App.lerForm(formProd);
    try {
        const r = d.id
            ? await App.api(`api/produtos.php?id=${d.id}`, { method: 'PUT', body: d })
            : await App.api('api/produtos.php', { method: 'POST', body: d });
        App.toast(r.mensagem);
        App.fecharModal('modal-produto');
        carregarProdutos();
    } catch (e) { App.erro(e); }
});

tabelaProd.addEventListener('click', async ev => {
    const { editar, excluir } = ev.target.dataset;
    if (editar) {
        App.preencherForm(formProd, produtos.find(p => p.id == editar));
        document.getElementById('modal-titulo').textContent = 'Editar produto';
        App.abrirModal('modal-produto');
    }
    if (excluir && App.confirmar('Excluir este produto?')) {
        try {
            App.toast((await App.api(`api/produtos.php?id=${excluir}`, { method: 'DELETE' })).mensagem);
            carregarProdutos();
        } catch (e) { App.erro(e); }
    }
});

carregarKanban();
carregarHistorico();
carregarProdutos();
