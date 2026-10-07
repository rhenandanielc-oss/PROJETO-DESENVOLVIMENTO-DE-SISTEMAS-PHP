/* Login e cadastro de aluno */
const formLogin = document.getElementById('form-login');
const formCadastro = document.getElementById('form-cadastro');

formLogin.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const botao = formLogin.querySelector('button[type=submit]');
    botao.disabled = true;
    try {
        await App.api('api/auth.php?acao=login', { method: 'POST', body: App.lerForm(formLogin) });
        window.location.href = 'dashboard.php';
    } catch (e) {
        App.erro(e);
        botao.disabled = false;
    }
});

formCadastro.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    try {
        const r = await App.api('api/auth.php?acao=cadastro', { method: 'POST', body: App.lerForm(formCadastro) });
        App.toast(r.mensagem);
        formLogin.elements.email.value = formCadastro.elements.email.value;
        formCadastro.reset();
        document.querySelector('.aba[data-aba="form-login"]').click();
    } catch (e) {
        App.erro(e);
    }
});

// Preenche o e-mail ao clicar em um usuário de demonstração
document.querySelectorAll('[data-email]').forEach(link => link.addEventListener('click', (ev) => {
    ev.preventDefault();
    formLogin.elements.email.value = link.dataset.email;
    formLogin.elements.senha.value = '123456';
}));
