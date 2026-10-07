/* Atualização dos dados do próprio usuário */
const form = document.getElementById('form-perfil');
form.addEventListener('submit', async ev => {
    ev.preventDefault();
    try {
        App.toast((await App.api('api/usuarios.php?perfil=1', { method: 'PUT', body: App.lerForm(form) })).mensagem);
        form.elements.senha_atual.value = '';
        form.elements.nova_senha.value = '';
        document.querySelector('.usuario-box strong').textContent = form.elements.nome.value;
    } catch (e) { App.erro(e); }
});
