<?php
$titulo  = 'Meu Perfil';
$pagina  = 'perfil.php';
$scripts = ['perfil.js'];
require __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:560px">
    <h2>👤 Meus dados</h2>
    <form id="form-perfil">
        <label>Nome <input name="nome" required value="<?= e($usuario['nome']) ?>"></label>
        <label>E-mail <input value="<?= e($usuario['email']) ?>" disabled></label>
        <label>Perfil <input value="<?= e(PERFIS[$usuario['perfil']]) ?>" disabled></label>
        <h2 style="margin-top:10px">🔒 Alterar senha</h2>
        <p class="texto-suave" style="margin-bottom:10px;font-size:.85rem">Deixe em branco para manter a senha atual.</p>
        <div class="grade-2">
            <label>Senha atual <input type="password" name="senha_atual" autocomplete="current-password"></label>
            <label>Nova senha <input type="password" name="nova_senha" minlength="6" autocomplete="new-password"></label>
        </div>
        <button type="submit" class="btn btn-primario">Salvar</button>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
