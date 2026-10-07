/* Reservas de salas - CRUD, aprovação (admin) e mapa de ocupação */
const tabela = document.getElementById('tabela');
const form = document.getElementById('form-reserva');
const filtros = document.getElementById('filtros');
const dataAgenda = document.getElementById('data-agenda');
const isAdmin = window.USUARIO.perfil === 'admin';
const HORA_INI = 7, HORA_FIM = 23; // faixa exibida no mapa de ocupação
let salas = [], reservas = [];

async function carregarSalas() {
    salas = await App.api('api/salas.php?ativas=1');
    const opcoes = salas.map(s => `<option value="${s.id}">${App.esc(s.nome)} (${App.rotulo(s.tipo)} - ${s.capacidade} lugares)</option>`).join('');
    document.getElementById('campo-sala').innerHTML = opcoes;
    document.getElementById('filtro-sala').innerHTML = '<option value="">Todas</option>' + opcoes;
}

async function carregar() {
    try {
        reservas = await App.api('api/reservas.php?' + new URLSearchParams(App.lerForm(filtros)));
        renderizar();
    } catch (e) { App.erro(e); }
}

function renderizar() {
    if (!reservas.length) {
        tabela.innerHTML = '<tr><td colspan="7" class="vazio">Nenhuma reserva encontrada.</td></tr>';
        return;
    }
    const hoje = App.hoje();
    tabela.innerHTML = reservas.map(r => {
        const minha = Number(r.usuario_id) === Number(window.USUARIO.id);
        const ativa = ['pendente', 'aprovada'].includes(r.status) && r.data >= hoje;
        let acoes = '';
        if (isAdmin && r.status === 'pendente') {
            acoes += `<button class="btn btn-pequeno btn-sucesso" data-status="aprovada" data-id="${r.id}">Aprovar</button>
                      <button class="btn btn-pequeno btn-alerta" data-status="recusada" data-id="${r.id}">Recusar</button>`;
        }
        if ((isAdmin || (minha && r.status === 'pendente')) && ativa) {
            acoes += `<button class="btn btn-pequeno" data-editar="${r.id}">Editar</button>`;
        }
        if ((minha || isAdmin) && ativa) {
            acoes += `<button class="btn btn-pequeno" data-status="cancelada" data-id="${r.id}">Cancelar</button>`;
        }
        if (isAdmin || minha) {
            acoes += `<button class="btn btn-pequeno btn-perigo" data-excluir="${r.id}">Excluir</button>`;
        }
        return `<tr>
            <td>${App.data(r.data)}</td>
            <td>${App.hora(r.hora_inicio)} - ${App.hora(r.hora_fim)}</td>
            <td>${App.esc(r.sala)}<br><small class="texto-suave">${App.rotulo(r.sala_tipo)}</small></td>
            <td>${App.esc(r.finalidade)}${r.observacao ? `<br><small class="texto-suave">Obs.: ${App.esc(r.observacao)}</small>` : ''}</td>
            <td>${App.esc(r.solicitante)}</td>
            <td>${App.badge(r.status)}</td>
            <td class="acoes">${acoes}</td>
        </tr>`;
    }).join('');
}

/** Desenha a ocupação de cada sala no dia escolhido (barra de 07h às 23h). */
async function carregarOcupacao() {
    try {
        const ocupadas = await App.api(`api/reservas.php?agenda=1&data=${dataAgenda.value}`);
        const total = (HORA_FIM - HORA_INI) * 60;
        const minutos = h => { const [hh, mm] = h.split(':').map(Number); return hh * 60 + mm - HORA_INI * 60; };
        const escala = Array.from({ length: (HORA_FIM - HORA_INI) / 2 + 1 }, (_, i) => `<span>${String(HORA_INI + i * 2).padStart(2, '0')}h</span>`).join('');

        document.getElementById('ocupacao').innerHTML =
            `<div class="ocupacao-escala"><div></div><div>${escala}</div></div>` +
            salas.map(s => {
                const blocos = ocupadas.filter(o => o.sala_id == s.id).map(o => {
                    const ini = Math.max(0, minutos(o.hora_inicio)), fim = Math.min(total, minutos(o.hora_fim));
                    return `<div class="ocupacao-bloco ${o.status}" style="left:${ini / total * 100}%;width:${(fim - ini) / total * 100}%"
                                 title="${App.hora(o.hora_inicio)}-${App.hora(o.hora_fim)} ${App.esc(o.finalidade)} (${App.esc(o.solicitante)})">
                                ${App.hora(o.hora_inicio)} ${App.esc(o.finalidade)}</div>`;
                }).join('');
                return `<div class="ocupacao-linha"><strong>${App.esc(s.nome)}</strong><div class="ocupacao-barra">${blocos}</div></div>`;
            }).join('');
    } catch (e) { App.erro(e); }
}

function mostrarInfoSala() {
    const s = salas.find(x => x.id == form.elements.sala_id.value);
    document.getElementById('info-sala').textContent = s ? `📍 ${s.localizacao || '-'} | Recursos: ${s.recursos || '-'}` : '';
}

document.getElementById('btn-nova').addEventListener('click', () => {
    form.reset();
    form.elements.id.value = '';
    form.elements.data.value = dataAgenda.value;
    form.elements.data.min = App.hoje();
    document.getElementById('modal-titulo').textContent = 'Nova reserva';
    mostrarInfoSala();
    App.abrirModal('modal-reserva');
});

form.elements.sala_id.addEventListener('change', mostrarInfoSala);

form.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const d = App.lerForm(form);
    try {
        const r = d.id
            ? await App.api(`api/reservas.php?id=${d.id}`, { method: 'PUT', body: d })
            : await App.api('api/reservas.php', { method: 'POST', body: d });
        App.toast(r.mensagem);
        App.fecharModal('modal-reserva');
        dataAgenda.value = d.data;
        carregar(); carregarOcupacao();
    } catch (e) { App.erro(e); }
});

tabela.addEventListener('click', async (ev) => {
    const b = ev.target.closest('button');
    if (!b) return;
    try {
        if (b.dataset.editar) {
            const r = reservas.find(x => x.id == b.dataset.editar);
            App.preencherForm(form, { ...r, hora_inicio: App.hora(r.hora_inicio), hora_fim: App.hora(r.hora_fim) });
            document.getElementById('modal-titulo').textContent = 'Editar reserva';
            mostrarInfoSala();
            App.abrirModal('modal-reserva');
            return;
        }
        if (b.dataset.status) {
            let observacao = '';
            if (b.dataset.status === 'recusada') {
                observacao = prompt('Motivo da recusa (opcional):') ?? '';
            } else if (b.dataset.status === 'cancelada' && !App.confirmar('Cancelar esta reserva?')) {
                return;
            }
            const r = await App.api(`api/reservas.php?id=${b.dataset.id}`, { method: 'PATCH', body: { status: b.dataset.status, observacao } });
            App.toast(r.mensagem);
        }
        if (b.dataset.excluir) {
            if (!App.confirmar('Excluir definitivamente esta reserva?')) return;
            App.toast((await App.api(`api/reservas.php?id=${b.dataset.excluir}`, { method: 'DELETE' })).mensagem);
        }
        carregar(); carregarOcupacao();
    } catch (e) { App.erro(e); }
});

filtros.addEventListener('change', carregar);
dataAgenda.addEventListener('change', carregarOcupacao);

(async () => {
    dataAgenda.value = App.hoje();
    try { await carregarSalas(); } catch (e) { App.erro(e); }
    carregar();
    carregarOcupacao();
})();
