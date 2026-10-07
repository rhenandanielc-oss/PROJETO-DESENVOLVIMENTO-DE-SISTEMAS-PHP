<?php
require_once __DIR__ . '/includes/auth.php';
exigir_perfil('admin');

$titulo  = 'Usuários';
$pagina  = 'usuarios.php';
$scripts = ['usuarios.js'];
require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <div class="barra-acoes">
        <form class="filtros" id="filtros">
            <label>Buscar <input name="busca" placeholder="Nome, e-mail ou matrícula"></label>
            <label>Perfil
                <select name="perfil">
                    <option value="">Todos</option>
                    <?php foreach (PERFIS as $valor => $rotulo): ?><option value="<?= $valor ?>"><?= $rotulo ?></option><?php endforeach; ?>
                </select>
            </label>
        </form>
        <button class="btn btn-primario" id="btn-novo">+ Novo usuário</button>
    </div>
    <div class="tabela-wrap">
        <table>
            <thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Matrícula</th><th>Turma</th><th>Situação</th><th>Cadastro</th><th></th></tr></thead>
            <tbody id="tabela"></tbody>
        </table>
    </div>
</div>

<div class="modal" id="modal-usuario">
    <div class="modal-caixa">
        <div class="modal-topo"><h3 id="modal-titulo">Novo usuário</h3><button class="modal-fechar" data-fechar="modal-usuario">&times;</button></div>
        <form class="modal-corpo" id="form-usuario">
            <input type="hidden" name="id">
            <label>Nome * <input name="nome" required maxlength="100"></label>
            <div class="grade-2">
                <label>E-mail * <input type="email" name="email" required maxlength="120"></label>
                <label>Perfil *
                    <select name="perfil">
                        <?php foreach (PERFIS as $valor => $rotulo): ?><option value="<?= $valor ?>"><?= $rotulo ?></option><?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="grade-2">
                <label>Matrícula <input name="matricula" maxlength="20"></label>
                <label>Turma <input name="turma" maxlength="30"></label>
            </div>
            <label><span id="rotulo-senha">Senha *</span> <input type="password" name="senha" minlength="6" autocomplete="new-password"></label>
            <label class="check"><input type="checkbox" name="ativo" checked> Usuário ativo (pode fazer login)</label>
            <div class="modal-rodape">
                <button type="button" class="btn" data-fechar="modal-usuario">Cancelar</button>
                <button type="submit" class="btn btn-primario">Salvar</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
