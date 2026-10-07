/* Agenda de tarefas - CRUD completo via API */
const tabela = document.getElementById('tabela');
const form = document.getElementById('form-tarefa');
const filtros = document.getElementById('filtros');
let tarefas = [];

async function carregar() {
    const params = new URLSearchParams(App.lerForm(filtros));
    try {
        tarefas = await App.api('api/tarefas.php?' + params);
        renderizar();
    } catch (e) { App.erro(e); }
}

function renderizar() {
    if (!tarefas.length) {
        tabela.innerHTML = '<tr><td colspan="7" class="vazio">Nenhuma tarefa encontrada. Clique em "+ Nova tarefa".</td></tr>';
        return;
    }
    tabela.innerHTML = tarefas.map(t => `
        <tr class="${Number(t.atrasada) ? 'atrasada' : ''}">
            <td><input type="checkbox" data-concluir="${t.id}" ${t.status === 'concluida' ? 'checked' : ''} title="Marcar como concluída"></td>
            <td><strong>${App.esc(t.titulo)}</strong>${t.descricao ? `<br><small class="texto-suave">${App.esc(t.descricao)}</small>` : ''}</td>
            <td>${App.esc(t.disciplina || '-')}</td>
            <td>${App.data(t.data_entrega)} ${Number(t.atrasada) ? '<br><small style="color:var(--perigo)">Atrasada</small>' : ''}</td>
            <td>${App.badge(t.prioridade)}</td>
            <td>${App.badge(t.status)}</td>
            <td class="acoes">
                <button class="btn btn-pequeno" data-editar="${t.id}">Editar</button>
                <button class="btn btn-pequeno btn-perigo" data-excluir="${t.id}">Excluir</button>
            </td>
        </tr>`).join('');
}

document.getElementById('btn-nova').addEventListener('click', () => {
    form.reset();
    form.elements.id.value = '';
    form.elements.data_entrega.value = App.hoje();
    document.getElementById('modal-titulo').textContent = 'Nova tarefa';
    App.abrirModal('modal-tarefa');
});

form.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const dados = App.lerForm(form);
    try {
        const r = dados.id
            ? await App.api(`api/tarefas.php?id=${dados.id}`, { method: 'PUT', body: dados })
            : await App.api('api/tarefas.php', { method: 'POST', body: dados });
        App.toast(r.mensagem);
        App.fecharModal('modal-tarefa');
        carregar();
    } catch (e) { App.erro(e); }
});

tabela.addEventListener('click', async (ev) => {
    const editar = ev.target.dataset.editar;
    const excluir = ev.target.dataset.excluir;

    if (editar) {
        App.preencherForm(form, tarefas.find(t => t.id == editar));
        document.getElementById('modal-titulo').textContent = 'Editar tarefa';
        App.abrirModal('modal-tarefa');
    }
    if (excluir && App.confirmar('Excluir esta tarefa?')) {
        try {
            App.toast((await App.api(`api/tarefas.php?id=${excluir}`, { method: 'DELETE' })).mensagem);
            carregar();
        } catch (e) { App.erro(e); }
    }
});

tabela.addEventListener('change', async (ev) => {
    const id = ev.target.dataset.concluir;
    if (!id) return;
    try {
        await App.api(`api/tarefas.php?id=${id}`, { method: 'PATCH', body: { status: ev.target.checked ? 'concluida' : 'pendente' } });
        carregar();
    } catch (e) { App.erro(e); }
});

filtros.addEventListener('input', () => { clearTimeout(filtros.t); filtros.t = setTimeout(carregar, 300); });
filtros.addEventListener('submit', ev => ev.preventDefault());
carregar();
