/* Help desk - chamados de TI */
const tabela = document.getElementById('tabela');
const filtros = document.getElementById('filtros');
const form = document.getElementById('form-chamado');
const formAtend = document.getElementById('form-atendimento'); // só existe para técnicos/admin
const formComent = document.getElementById('form-comentario');
const equipe = ['admin', 'tecnico'].includes(window.USUARIO.perfil);
let chamados = [], atual = null;

async function carregar() {
    try {
        const f = App.lerForm(filtros);
        if (!f.meus) delete f.meus;
        chamados = await App.api('api/chamados.php?' + new URLSearchParams(f));
        tabela.innerHTML = chamados.length ? chamados.map(c => `
            <tr>
                <td>${c.id}</td>
                <td><a href="#" data-ver="${c.id}"><strong>${App.esc(c.titulo)}</strong></a><br><small class="texto-suave">📍 ${App.esc(c.local || '-')}</small></td>
                <td>${App.rotulo(c.categoria)}</td>
                <td>${App.badge(c.prioridade)}</td>
                <td>${App.esc(c.solicitante)}</td>
                <td>${App.esc(c.tecnico || '-')}</td>
                <td>${App.dataHora(c.criado_em)}</td>
                <td>${App.badge(c.status)}</td>
                <td class="acoes">
                    <button class="btn btn-pequeno" data-ver="${c.id}">Abrir</button>
                    ${podeEditar(c) ? `<button class="btn btn-pequeno" data-editar="${c.id}">Editar</button>` : ''}
                    ${podeExcluir(c) ? `<button class="btn btn-pequeno btn-perigo" data-excluir="${c.id}">Excluir</button>` : ''}
                </td>
            </tr>`).join('') : '<tr><td colspan="9" class="vazio">Nenhum chamado encontrado.</td></tr>';
    } catch (e) { App.erro(e); }
}

const meu = c => Number(c.usuario_id) === Number(window.USUARIO.id);
const podeEditar = c => equipe || (meu(c) && c.status === 'aberto');
const podeExcluir = c => window.USUARIO.perfil === 'admin' || (meu(c) && c.status === 'aberto' && !c.tecnico_id);

/** Abre o detalhe com comentários. */
async function verChamado(id) {
    try {
        atual = await App.api(`api/chamados.php?id=${id}`);
        const c = atual;
        document.getElementById('detalhe-titulo').textContent = `Chamado #${c.id} - ${c.titulo}`;
        document.getElementById('detalhe-info').innerHTML = `
            <div class="detalhe-grid">
                <div><span>Status</span>${App.badge(c.status)}</div>
                <div><span>Prioridade</span>${App.badge(c.prioridade)}</div>
                <div><span>Categoria</span>${App.rotulo(c.categoria)}</div>
                <div><span>Local</span>${App.esc(c.local || '-')}</div>
                <div><span>Solicitante</span>${App.esc(c.solicitante)}</div>
                <div><span>Técnico</span>${App.esc(c.tecnico || 'Não atribuído')}</div>
                <div><span>Aberto em</span>${App.dataHora(c.criado_em)}</div>
                <div><span>Resolvido em</span>${App.dataHora(c.resolvido_em)}</div>
            </div>
            <p style="white-space:pre-wrap;margin-bottom:14px">${App.esc(c.descricao)}</p>
            ${c.solucao ? `<div class="alerta alerta-sucesso"><strong>Solução:</strong> ${App.esc(c.solucao)}</div>` : ''}`;

        document.getElementById('comentarios').innerHTML = c.comentarios.length ? c.comentarios.map(m => `
            <div class="comentario">
                <div class="meta"><strong>${App.esc(m.nome)}</strong> ${App.badge(m.perfil)} • ${App.dataHora(m.criado_em)}</div>
                <div>${App.esc(m.mensagem)}</div>
            </div>`).join('') : '<p class="texto-suave">Sem comentários ainda.</p>';

        if (formAtend) {
            App.preencherForm(formAtend, { status: c.status, tecnico_id: c.tecnico_id || '', prioridade: c.prioridade, solucao: c.solucao || '' });
        }

        // Solicitante pode confirmar a solução (fechar) ou reabrir
        let acoes = '';
        if (!equipe && meu(c) && c.status === 'resolvido') {
            acoes = `<span>O problema foi resolvido?</span><div>
                <button class="btn btn-sucesso" data-acao="fechado">Sim, fechar chamado</button>
                <button class="btn btn-alerta" data-acao="aberto">Não, reabrir</button></div>`;
        }
        document.getElementById('acoes-solicitante').innerHTML = acoes;

        App.abrirModal('modal-detalhe');
    } catch (e) { App.erro(e); }
}

document.getElementById('btn-novo').addEventListener('click', () => {
    form.reset();
    form.elements.id.value = '';
    document.getElementById('modal-titulo').textContent = 'Abrir chamado';
    App.abrirModal('modal-chamado');
});

form.addEventListener('submit', async ev => {
    ev.preventDefault();
    const d = App.lerForm(form);
    try {
        const r = d.id
            ? await App.api(`api/chamados.php?id=${d.id}`, { method: 'PUT', body: d })
            : await App.api('api/chamados.php', { method: 'POST', body: d });
        App.toast(r.mensagem);
        App.fecharModal('modal-chamado');
        carregar();
    } catch (e) { App.erro(e); }
});

tabela.addEventListener('click', async ev => {
    const el = ev.target.closest('[data-ver],[data-editar],[data-excluir]');
    if (!el) return;
    ev.preventDefault();
    const { ver, editar, excluir } = el.dataset;
    if (ver) verChamado(ver);
    if (editar) {
        App.preencherForm(form, chamados.find(c => c.id == editar));
        document.getElementById('modal-titulo').textContent = 'Editar chamado';
        App.abrirModal('modal-chamado');
    }
    if (excluir && App.confirmar('Excluir este chamado?')) {
        try {
            App.toast((await App.api(`api/chamados.php?id=${excluir}`, { method: 'DELETE' })).mensagem);
            carregar();
        } catch (e) { App.erro(e); }
    }
});

async function atualizarChamado(dados) {
    try {
        App.toast((await App.api(`api/chamados.php?id=${atual.id}`, { method: 'PATCH', body: dados })).mensagem);
        verChamado(atual.id);
        carregar();
    } catch (e) { App.erro(e); }
}

formAtend?.addEventListener('submit', ev => {
    ev.preventDefault();
    atualizarChamado(App.lerForm(formAtend));
});

document.getElementById('acoes-solicitante').addEventListener('click', ev => {
    if (ev.target.dataset.acao) atualizarChamado({ status: ev.target.dataset.acao });
});

formComent.addEventListener('submit', async ev => {
    ev.preventDefault();
    try {
        await App.api(`api/chamados.php?id=${atual.id}&acao=comentar`, { method: 'POST', body: App.lerForm(formComent) });
        formComent.reset();
        verChamado(atual.id);
    } catch (e) { App.erro(e); }
});

filtros.addEventListener('input', () => { clearTimeout(filtros.t); filtros.t = setTimeout(carregar, 300); });
filtros.addEventListener('submit', ev => ev.preventDefault());

(async () => {
    if (equipe) {
        try {
            const tecnicos = await App.api('api/chamados.php?tecnicos=1');
            document.getElementById('campo-tecnico').innerHTML = '<option value="">- Não atribuído -</option>' +
                tecnicos.map(t => `<option value="${t.id}">${App.esc(t.nome)}</option>`).join('');
        } catch (e) { App.erro(e); }
    }
    carregar();
})();
