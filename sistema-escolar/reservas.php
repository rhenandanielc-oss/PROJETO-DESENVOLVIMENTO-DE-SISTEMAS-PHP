<?php
$titulo  = 'Reservas de Salas e Laboratórios';
$pagina  = 'reservas.php';
$scripts = ['reservas.js'];
require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <div class="barra-acoes">
        <div class="filtros">
            <label>Ocupação do dia <input type="date" id="data-agenda"></label>
        </div>
        <button class="btn btn-primario" id="btn-nova">+ Nova reserva</button>
    </div>
    <div id="ocupacao" class="ocupacao"></div>
    <p class="texto-suave" style="font-size:.8rem;margin-top:8px">
        <span class="badge badge-aprovada">Aprovada</span> <span class="badge badge-pendente">Pendente</span> - horários das 07h às 23h
    </p>
</div>

<div class="card">
    <div class="barra-acoes">
        <h2 style="margin:0"><?= tem_perfil('admin') ? 'Todas as reservas' : 'Minhas reservas' ?></h2>
        <form class="filtros" id="filtros">
            <label>Sala <select name="sala_id" id="filtro-sala"><option value="">Todas</option></select></label>
            <label>Status
                <select name="status">
                    <option value="">Todos</option><option value="pendente">Pendente</option><option value="aprovada">Aprovada</option>
                    <option value="recusada">Recusada</option><option value="cancelada">Cancelada</option>
                </select>
            </label>
        </form>
    </div>
    <div class="tabela-wrap">
        <table>
            <thead><tr><th>Data</th><th>Horário</th><th>Sala</th><th>Finalidade</th><th>Solicitante</th><th>Status</th><th></th></tr></thead>
            <tbody id="tabela"></tbody>
        </table>
    </div>
</div>

<div class="modal" id="modal-reserva">
    <div class="modal-caixa">
        <div class="modal-topo"><h3 id="modal-titulo">Nova reserva</h3><button class="modal-fechar" data-fechar="modal-reserva">&times;</button></div>
        <form class="modal-corpo" id="form-reserva">
            <input type="hidden" name="id">
            <label>Sala / Laboratório * <select name="sala_id" id="campo-sala" required></select></label>
            <p id="info-sala" class="texto-suave" style="font-size:.85rem;margin:-6px 0 12px"></p>
            <div class="grade-3">
                <label>Data * <input type="date" name="data" required></label>
                <label>Início * <input type="time" name="hora_inicio" required min="07:00" max="23:00"></label>
                <label>Fim * <input type="time" name="hora_fim" required min="07:00" max="23:00"></label>
            </div>
            <label>Finalidade * <input name="finalidade" required maxlength="200" placeholder="Ex.: Aula prática de PHP - 2º DS"></label>
            <div class="modal-rodape">
                <button type="button" class="btn" data-fechar="modal-reserva">Cancelar</button>
                <button type="submit" class="btn btn-primario">Salvar</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
