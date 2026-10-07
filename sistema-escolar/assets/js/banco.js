/* Visualizador do banco de dados (admin) */
let tabelaAtual = null, dadosAtuais = [];

async function carregarTabelas() {
    try {
        const r = await App.api('api/banco.php');
        document.getElementById('servidor').textContent = r.servidor;
        document.getElementById('lista-tabelas').innerHTML = r.tabelas.map(t => `
            <a href="#" data-tabela="${App.esc(t.nome)}">
                <span>${t.tipo === 'view' ? '👁️' : '📋'} ${App.esc(t.nome)}</span>
                <small>${t.registros}</small>
            </a>`).join('');
        if (r.tabelas.length) abrirTabela(r.tabelas[0].nome);
    } catch (e) { App.erro(e); }
}

async function abrirTabela(nome) {
    tabelaAtual = nome;
    document.getElementById('nome-tabela').textContent = nome;
    document.querySelectorAll('#lista-tabelas a').forEach(a => a.classList.toggle('ativo', a.dataset.tabela === nome));
    const url = modo => `api/banco.php?tabela=${encodeURIComponent(nome)}&modo=${modo}`;
    try {
        const [dados, estrutura, sql] = await Promise.all([App.api(url('dados')), App.api(url('estrutura')), App.api(url('sql'))]);
        dadosAtuais = dados.linhas;
        document.getElementById('info-dados').textContent = `${dados.linhas.length} registro(s) exibido(s) (máx. 500)`;
        document.getElementById('dados').innerHTML = App.tabelaSimples(dados.linhas);

        const fks = Object.fromEntries(estrutura.chaves_estrangeiras.map(f => [f.coluna, `${f.tabela_ref}.${f.coluna_ref}`]));
        document.getElementById('estrutura').innerHTML = App.tabelaSimples(estrutura.colunas.map(c => ({
            Coluna: c.Field, Tipo: c.Type, Nulo: c.Null, Chave: c.Key === 'PRI' ? 'PK' : (fks[c.Field] ? `FK → ${fks[c.Field]}` : c.Key),
            Padrão: c.Default, Extra: c.Extra, Comentário: c.Comment,
        })));
        document.getElementById('create-sql').textContent = sql.sql;
    } catch (e) { App.erro(e); }
}

document.getElementById('lista-tabelas').addEventListener('click', ev => {
    const a = ev.target.closest('[data-tabela]');
    if (!a) return;
    ev.preventDefault();
    abrirTabela(a.dataset.tabela);
});

document.getElementById('btn-csv').addEventListener('click', () => App.exportarCSV(`${tabelaAtual}.csv`, dadosAtuais));

const formSql = document.getElementById('form-sql');
async function executarSql(ev) {
    ev?.preventDefault();
    try {
        const r = await App.api('api/banco.php', { method: 'POST', body: App.lerForm(formSql) });
        document.getElementById('info-sql').textContent = `${r.total} linha(s) em ${r.tempo_ms} ms`;
        document.getElementById('resultado-sql').innerHTML = App.tabelaSimples(r.linhas);
    } catch (e) {
        document.getElementById('info-sql').textContent = '';
        document.getElementById('resultado-sql').innerHTML = `<div class="alerta alerta-erro">${App.esc(e.message)}</div>`;
    }
}
formSql.addEventListener('submit', executarSql);
formSql.elements.sql.addEventListener('keydown', ev => { if (ev.ctrlKey && ev.key === 'Enter') executarSql(ev); });

carregarTabelas();
