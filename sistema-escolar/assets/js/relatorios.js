/* Relatórios com filtros de período, gráficos de barras, impressão e CSV */
const filtros = document.getElementById('filtros');
let ultimo = null;

const ROTULOS_RESUMO = {
    total: 'Total', concluidas: 'Concluídas', abertas: 'Em aberto', atrasadas: 'Atrasadas',
    percentual_concluido: '% concluído', aprovadas: 'Aprovadas', pendentes: 'Pendentes',
    recusadas_canceladas: 'Recusadas/Canceladas', horas_reservadas: 'Horas reservadas',
    pedidos: 'Pedidos', faturamento: 'Faturamento', ticket_medio: 'Ticket médio', cancelados: 'Cancelados',
    em_aberto: 'Em aberto', resolvidos: 'Resolvidos', tempo_medio_horas: 'Tempo médio (h)',
};
const MONETARIOS = ['faturamento', 'ticket_medio'];

/** Gráfico de barras horizontal feito só com HTML/CSS. */
function grafico(titulo, dados) {
    const max = Math.max(...dados.map(d => Number(d.valor)), 1);
    const corpo = dados.length ? dados.map(d => `
        <div class="barra-linha">
            <span class="rotulo" title="${App.esc(d.rotulo)}">${App.esc(App.rotulo(d.rotulo))}</span>
            <div class="barra-trilho"><div class="barra-preenchida" style="width:${Number(d.valor) / max * 100}%"></div></div>
            <span class="val">${titulo.includes('R$') ? App.moeda(d.valor) : Number(d.valor).toLocaleString('pt-BR')}</span>
        </div>`).join('') : '<p class="vazio">Sem dados no período.</p>';
    return `<div class="card"><h2>${App.esc(titulo)}</h2><div class="grafico-barras">${corpo}</div></div>`;
}

async function gerar(ev) {
    ev?.preventDefault();
    const f = App.lerForm(filtros);
    try {
        ultimo = await App.api('api/relatorios.php?' + new URLSearchParams(f));
        const nome = filtros.elements.tipo.selectedOptions[0].textContent.replace(/^\S+\s/, '');
        const titulo = `Relatório de ${nome} - ${App.data(f.inicio)} a ${App.data(f.fim)}`;
        document.getElementById('titulo-relatorio').textContent = titulo;
        document.getElementById('titulo-impressao').textContent = titulo;

        document.getElementById('resumo').innerHTML = Object.entries(ultimo.resumo).map(([k, v]) => `
            <div><span>${ROTULOS_RESUMO[k] || k}</span>
            <strong>${MONETARIOS.includes(k) ? App.moeda(v) : (v ?? 0)}${k === 'percentual_concluido' ? '%' : ''}</strong></div>`).join('');

        document.getElementById('graficos').innerHTML = Object.entries(ultimo.grupos).map(([t, d]) => grafico(t, d)).join('');
        document.getElementById('detalhes').innerHTML = App.tabelaSimples(ultimo.linhas.map(l => {
            const copia = { ...l };
            ['Entrega', 'Data'].forEach(c => { if (copia[c]) copia[c] = App.data(copia[c]); });
            ['Status', 'Prioridade', 'Categoria'].forEach(c => { if (copia[c]) copia[c] = App.rotulo(copia[c]); });
            return copia;
        }));
    } catch (e) { App.erro(e); }
}

filtros.addEventListener('submit', gerar);
filtros.elements.tipo.addEventListener('change', gerar);
document.getElementById('btn-imprimir').addEventListener('click', () => window.print());
document.getElementById('btn-csv').addEventListener('click', () => {
    if (ultimo) App.exportarCSV(`relatorio-${filtros.elements.tipo.value}-${App.hoje()}.csv`, ultimo.linhas);
});

// Período padrão: mês atual (com margem de 30 dias para frente, útil para tarefas e reservas)
const hoje = new Date();
const inicioMes = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
const fimPeriodo = new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0);
const iso = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
filtros.elements.inicio.value = iso(inicioMes);
filtros.elements.fim.value = iso(new Date(Math.max(fimPeriodo, new Date(hoje.getTime() + 30 * 864e5))));
gerar();
