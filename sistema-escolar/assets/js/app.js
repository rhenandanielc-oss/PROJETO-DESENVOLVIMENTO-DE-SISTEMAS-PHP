/* =====================================================================
   app.js - Funções compartilhadas por todas as páginas
   Faz a integração Front-end <-> Back-end usando fetch() + JSON
   ===================================================================== */

const App = {
    csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',

    /**
     * Chama a API PHP e devolve o JSON.
     * Ex.: await App.api('api/tarefas.php', { method: 'POST', body: {...} })
     */
    async api(url, { method = 'GET', body = null } = {}) {
        const opcoes = {
            method,
            headers: { 'Accept': 'application/json', 'X-CSRF-Token': App.csrf },
            credentials: 'same-origin',
        };
        if (body !== null) {
            opcoes.headers['Content-Type'] = 'application/json';
            opcoes.body = JSON.stringify(body);
        }

        const resp = await fetch(url, opcoes);
        let dados;
        try {
            dados = await resp.json();
        } catch {
            throw new Error('Resposta inválida do servidor (verifique o PHP/MySQL).');
        }
        if (resp.status === 401) {
            window.location.href = 'login.php';
        }
        if (!resp.ok) {
            throw new Error(dados.erro || 'Erro ao processar a requisição.');
        }
        return dados;
    },

    /** Notificação temporária no canto da tela. */
    toast(mensagem, tipo = 'sucesso') {
        const caixa = document.getElementById('toasts');
        if (!caixa) return alert(mensagem);
        const el = document.createElement('div');
        el.className = `toast ${tipo}`;
        el.textContent = mensagem;
        caixa.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    },

    erro(e) {
        App.toast(e.message || String(e), 'erro');
    },

    /* ---------- Modais ---------- */
    abrirModal(id) {
        document.getElementById(id).classList.add('aberto');
    },
    fecharModal(id) {
        document.getElementById(id).classList.remove('aberto');
    },

    /* ---------- Formulários ---------- */
    lerForm(form) {
        const obj = {};
        new FormData(form).forEach((v, k) => { obj[k] = typeof v === 'string' ? v.trim() : v; });
        form.querySelectorAll('input[type=checkbox]').forEach(cb => { obj[cb.name] = cb.checked ? 1 : 0; });
        return obj;
    },
    preencherForm(form, dados) {
        form.reset();
        Object.entries(dados).forEach(([k, v]) => {
            const campo = form.elements[k];
            if (!campo) return;
            if (campo.type === 'checkbox') campo.checked = Number(v) === 1;
            else campo.value = v ?? '';
        });
    },

    /* ---------- Formatação ---------- */
    esc(texto) {
        const d = document.createElement('div');
        d.textContent = texto ?? '';
        return d.innerHTML;
    },
    data(iso) {
        if (!iso) return '-';
        const [a, m, d] = iso.substring(0, 10).split('-');
        return `${d}/${m}/${a}`;
    },
    dataHora(iso) {
        if (!iso) return '-';
        return `${App.data(iso)} ${iso.substring(11, 16)}`;
    },
    hora(h) {
        return (h || '').substring(0, 5);
    },
    moeda(v) {
        return Number(v || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    },
    hoje() {
        const d = new Date();
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        return d.toISOString().substring(0, 10);
    },
    rotulo(valor) {
        const nomes = {
            em_andamento: 'Em andamento', concluida: 'Concluída', media: 'Média', critica: 'Crítica',
            laboratorio: 'Laboratório', auditorio: 'Auditório', refeicao: 'Refeição', cartao: 'Cartão',
            tecnico: 'Técnico', admin: 'Administrador',
        };
        return nomes[valor] || (valor ? valor.charAt(0).toUpperCase() + valor.slice(1) : '');
    },
    badge(valor) {
        return `<span class="badge badge-${App.esc(valor)}">${App.esc(App.rotulo(valor))}</span>`;
    },

    /** Confirmação simples antes de excluir. */
    confirmar(msg = 'Tem certeza que deseja excluir?') {
        return window.confirm(msg);
    },

    /** Exporta uma lista de objetos para CSV (abre no Excel). */
    exportarCSV(nomeArquivo, linhas) {
        if (!linhas.length) return App.toast('Nada para exportar.', 'erro');
        const colunas = Object.keys(linhas[0]);
        const escapar = v => `"${String(v ?? '').replace(/"/g, '""')}"`;
        const csv = [colunas.map(escapar).join(';'), ...linhas.map(l => colunas.map(c => escapar(l[c])).join(';'))].join('\r\n');
        const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = nomeArquivo;
        a.click();
        URL.revokeObjectURL(a.href);
    },

    /** Monta uma tabela HTML simples a partir de uma lista de objetos. */
    tabelaSimples(linhas) {
        if (!linhas.length) return '<p class="vazio">Nenhum registro encontrado.</p>';
        const cols = Object.keys(linhas[0]);
        return `<div class="tabela-wrap"><table>
            <thead><tr>${cols.map(c => `<th>${App.esc(c)}</th>`).join('')}</tr></thead>
            <tbody>${linhas.map(l => `<tr>${cols.map(c => `<td>${l[c] === null ? '<em class="texto-suave">NULL</em>' : App.esc(String(l[c]))}</td>`).join('')}</tr>`).join('')}</tbody>
        </table></div>`;
    },
};

/* Comportamentos globais */
document.addEventListener('click', (ev) => {
    // Fecha modal pelo botão [data-fechar] ou clicando no fundo escuro
    const fechar = ev.target.closest('[data-fechar]');
    if (fechar) App.fecharModal(fechar.dataset.fechar);
    if (ev.target.classList.contains('modal')) ev.target.classList.remove('aberto');

    // Abas genéricas: <button class="aba" data-aba="idDoPainel">
    const aba = ev.target.closest('.aba[data-aba]');
    if (aba) {
        const grupo = aba.parentElement;
        grupo.querySelectorAll('.aba').forEach(a => a.classList.toggle('ativa', a === aba));
        grupo.querySelectorAll('.aba').forEach(a => {
            document.getElementById(a.dataset.aba)?.classList.toggle('ativo', a === aba);
        });
        aba.dispatchEvent(new CustomEvent('aba-ativada', { bubbles: true, detail: aba.dataset.aba }));
    }
});

document.addEventListener('keydown', (ev) => {
    if (ev.key === 'Escape') document.querySelectorAll('.modal.aberto').forEach(m => m.classList.remove('aberto'));
});
