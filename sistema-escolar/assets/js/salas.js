/* Cadastro de salas (admin) */
const tabela = document.getElementById('tabela');
const form = document.getElementById('form-sala');
let salas = [];

async function carregar() {
    try {
        salas = await App.api('api/salas.php');
        tabela.innerHTML = salas.length ? salas.map(s => `
            <tr>
                <td><strong>${App.esc(s.nome)}</strong></td>
                <td>${App.rotulo(s.tipo)}</td>
                <td>${s.capacidade}</td>
                <td>${App.esc(s.localizacao || '-')}</td>
                <td><small>${App.esc(s.recursos || '-')}</small></td>
                <td>${s.reservas_futuras}</td>
                <td>${App.badge(Number(s.ativa) ? 'ativo' : 'inativo')}</td>
                <td class="acoes">
                    <button class="btn btn-pequeno" data-editar="${s.id}">Editar</button>
                    <button class="btn btn-pequeno btn-perigo" data-excluir="${s.id}">Excluir</button>
                </td>
            </tr>`).join('') : '<tr><td colspan="8" class="vazio">Nenhuma sala cadastrada.</td></tr>';
    } catch (e) { App.erro(e); }
}

document.getElementById('btn-nova').addEventListener('click', () => {
    form.reset();
    form.elements.id.value = '';
    document.getElementById('modal-titulo').textContent = 'Nova sala';
    App.abrirModal('modal-sala');
});

form.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const d = App.lerForm(form);
    try {
        const r = d.id
            ? await App.api(`api/salas.php?id=${d.id}`, { method: 'PUT', body: d })
            : await App.api('api/salas.php', { method: 'POST', body: d });
        App.toast(r.mensagem);
        App.fecharModal('modal-sala');
        carregar();
    } catch (e) { App.erro(e); }
});

tabela.addEventListener('click', async (ev) => {
    const { editar, excluir } = ev.target.dataset;
    if (editar) {
        App.preencherForm(form, salas.find(s => s.id == editar));
        document.getElementById('modal-titulo').textContent = 'Editar sala';
        App.abrirModal('modal-sala');
    }
    if (excluir && App.confirmar('Excluir esta sala? Todas as reservas dela também serão apagadas.')) {
        try {
            App.toast((await App.api(`api/salas.php?id=${excluir}`, { method: 'DELETE' })).mensagem);
            carregar();
        } catch (e) { App.erro(e); }
    }
});

carregar();
