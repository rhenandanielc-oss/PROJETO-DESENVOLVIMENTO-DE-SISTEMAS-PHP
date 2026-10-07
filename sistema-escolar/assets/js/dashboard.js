/* Painel inicial */
(async function () {
    try {
        const d = await App.api('api/dashboard.php');

        document.getElementById('cards').innerHTML = d.cards.map(c => `
            <a class="card-indicador" href="${c.link}">
                <div class="ic">${c.icone}</div>
                <div><div class="num">${App.esc(String(c.valor))}</div><div class="rot">${App.esc(c.titulo)}</div></div>
            </a>`).join('');

        const lista = (itens, render) => itens.length
            ? `<table><tbody>${itens.map(render).join('')}</tbody></table>`
            : '<p class="vazio">Nada por aqui 🎉</p>';

        document.getElementById('lista-tarefas').innerHTML = lista(d.proximas_tarefas, t => `
            <tr class="${t.data_entrega < App.hoje() ? 'atrasada' : ''}">
                <td><strong>${App.esc(t.titulo)}</strong><br><small class="texto-suave">${App.esc(t.disciplina || '')}</small></td>
                <td>${App.data(t.data_entrega)}</td><td>${App.badge(t.prioridade)}</td></tr>`);

        document.getElementById('lista-reservas').innerHTML = lista(d.proximas_reservas, r => `
            <tr><td><strong>${App.esc(r.sala)}</strong></td>
                <td>${App.data(r.data)}<br><small>${App.hora(r.hora_inicio)} - ${App.hora(r.hora_fim)}</small></td>
                <td>${App.badge(r.status)}</td></tr>`);

        document.getElementById('lista-chamados').innerHTML = lista(d.ultimos_chamados, c => `
            <tr><td>#${c.id} ${App.esc(c.titulo)}</td><td>${App.badge(c.prioridade)}</td><td>${App.badge(c.status)}</td></tr>`);
    } catch (e) {
        App.erro(e);
    }
})();
