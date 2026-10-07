<?php
require_once __DIR__ . '/includes/auth.php';
exigir_perfil('admin');

$titulo  = 'Salas e Laboratórios';
$pagina  = 'salas.php';
$scripts = ['salas.js'];
require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <div class="barra-acoes">
        <p class="texto-suave">Cadastre as salas, laboratórios e auditórios disponíveis para reserva.</p>
        <button class="btn btn-primario" id="btn-nova">+ Nova sala</button>
    </div>
    <div class="tabela-wrap">
        <table>
            <thead><tr><th>Nome</th><th>Tipo</th><th>Capacidade</th><th>Localização</th><th>Recursos</th><th>Reservas futuras</th><th>Situação</th><th></th></tr></thead>
            <tbody id="tabela"></tbody>
        </table>
    </div>
</div>

<div class="modal" id="modal-sala">
    <div class="modal-caixa">
        <div class="modal-topo"><h3 id="modal-titulo">Nova sala</h3><button class="modal-fechar" data-fechar="modal-sala">&times;</button></div>
        <form class="modal-corpo" id="form-sala">
            <input type="hidden" name="id">
            <label>Nome * <input name="nome" required maxlength="80"></label>
            <div class="grade-2">
                <label>Tipo *
                    <select name="tipo"><option value="sala">Sala de aula</option><option value="laboratorio">Laboratório</option><option value="auditorio">Auditório</option></select>
                </label>
                <label>Capacidade * <input type="number" name="capacidade" min="1" required value="30"></label>
            </div>
            <label>Localização <input name="localizacao" maxlength="100" placeholder="Ex.: Bloco B - 1º andar"></label>
            <label>Recursos <textarea name="recursos" placeholder="Ex.: 30 computadores, projetor, ar-condicionado"></textarea></label>
            <label class="check"><input type="checkbox" name="ativa" checked> Disponível para reservas</label>
            <div class="modal-rodape">
                <button type="button" class="btn" data-fechar="modal-sala">Cancelar</button>
                <button type="submit" class="btn btn-primario">Salvar</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
