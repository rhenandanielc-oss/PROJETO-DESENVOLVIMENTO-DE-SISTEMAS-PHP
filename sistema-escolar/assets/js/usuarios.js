/* Cadastro de usuários (admin) */
const tabela = document.getElementById('tabela');
const filtros = document.getElementById('filtros');
const form = document.getElementById('form-usuario');
let usuarios = [];

async function carregar() {
    try {
        usuarios = await App.api('api/usuarios.php?' + new URLSearchParams(App.lerForm(filtros)));
        tabela.innerHTML = usuarios.length ? usuarios.map(u => `
            <tr>
                <td><strong>${App.esc(u.nome)}</strong></td>
                <td>${App.esc(u.email)}</td>
                <td>${App.badge(u.perfil)}</td>
                <td>${App.esc(u.matricula || '-')}</td>
                <td>${App.esc(u.turma || '-')}</td>
                <td>${App.badge(Number(u.ativo) ? 'ativo' : 'inativo')}</td>
                <td>${App.data(u.criado_em)}</td>
                <td class="acoes">
                    <button class="btn btn-pequeno" data-editar="${u.id}">Editar</button>
                    ${u.id != window.USUARIO.id ? `<button class="btn btn-pequeno btn-perigo" data-excluir="${u.id}">Excluir</button>` : ''}
                </td>
            </tr>`).join('') : '<tr><td colspan="8" class="vazio">Nenhum usuário encontrado.</td></tr>';
    } catch (e) { App.erro(e); }
}

function abrir(usuario = null) {
    form.reset();
    if (usuario) App.preencherForm(form, usuario);
    form.elements.id.value = usuario?.id || '';
    form.elements.senha.value = '';
    form.elements.senha.required = !usuario;
    document.getElementById('rotulo-senha').textContent = usuario ? 'Nova senha (deixe em branco para manter)' : 'Senha *';
    document.getElementById('modal-titulo').textContent = usuario ? 'Editar usuário' : 'Novo usuário';
    App.abrirModal('modal-usuario');
}

document.getElementById('btn-novo').addEventListener('click', () => abrir());

form.addEventListener('submit', async ev => {
    ev.preventDefault();
    const d = App.lerForm(form);
    try {
        const r = d.id
            ? await App.api(`api/usuarios.php?id=${d.id}`, { method: 'PUT', body: d })
            : await App.api('api/usuarios.php', { method: 'POST', body: d });
        App.toast(r.mensagem);
        App.fecharModal('modal-usuario');
        carregar();
    } catch (e) { App.erro(e); }
});

tabela.addEventListener('click', async ev => {
    const { editar, excluir } = ev.target.dataset;
    if (editar) abrir(usuarios.find(u => u.id == editar));
    if (excluir && App.confirmar('Excluir este usuário? Tarefas, reservas, pedidos e chamados dele também serão apagados.')) {
        try {
            App.toast((await App.api(`api/usuarios.php?id=${excluir}`, { method: 'DELETE' })).mensagem);
            carregar();
        } catch (e) { App.erro(e); }
    }
});

filtros.addEventListener('input', () => { clearTimeout(filtros.t); filtros.t = setTimeout(carregar, 300); });
filtros.addEventListener('submit', ev => ev.preventDefault());
carregar();
