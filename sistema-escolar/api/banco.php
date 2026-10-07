<?php
/**
 * API - Visualizador do Banco de Dados (somente administrador)
 * Permite ao professor/administrador inspecionar o banco direto pelo sistema.
 *
 *   GET  api/banco.php                               lista tabelas e views com nº de registros
 *   GET  api/banco.php?tabela=usuarios&modo=dados    registros (até 500)
 *   GET  api/banco.php?tabela=usuarios&modo=estrutura  colunas, tipos, chaves
 *   GET  api/banco.php?tabela=usuarios&modo=sql      comando CREATE TABLE
 *   POST api/banco.php  { sql }                      console SQL SOMENTE LEITURA (SELECT/SHOW/DESCRIBE/EXPLAIN)
 */
require_once __DIR__ . '/_bootstrap.php';
api_exigir_perfil('admin');

/** Lista de tabelas reais do banco - usada também para validar o nome recebido. */
function tabelas(): array
{
    $lista = [];
    foreach (db()->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as [$nome, $tipo]) {
        $lista[$nome] = $tipo === 'VIEW' ? 'view' : 'tabela';
    }
    return $lista;
}

/** Esconde o hash das senhas na visualização. */
function ocultar_senhas(array $linhas): array
{
    foreach ($linhas as &$l) {
        if (array_key_exists('senha', $l)) {
            $l['senha'] = '•••••• (hash bcrypt)';
        }
    }
    return $linhas;
}

if ($METODO === 'POST') {
    $sql = trim(corpo_requisicao()['sql'] ?? '');
    $sql = rtrim($sql, "; \n\r\t");
    if ($sql === '') {
        json_erro('Digite um comando SQL.', 422);
    }
    if (!preg_match('/^(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN)\b/i', $sql) || str_contains($sql, ';')) {
        json_erro('Por segurança, o console aceita apenas UM comando de consulta (SELECT, SHOW, DESCRIBE ou EXPLAIN). '
            . 'Para alterar dados use as telas do sistema ou o phpMyAdmin.', 422);
    }
    if (preg_match('/\b(INTO\s+(OUTFILE|DUMPFILE)|LOAD_FILE|SLEEP|BENCHMARK)\b/i', $sql)) {
        json_erro('Comando não permitido no console.', 422);
    }
    $pdo = db();
    $pdo->exec('START TRANSACTION READ ONLY'); // garante que nada será alterado
    try {
        $inicio = microtime(true);
        $linhas = $pdo->query($sql)->fetchAll();
        $tempo  = round((microtime(true) - $inicio) * 1000, 1);
    } catch (PDOException $ex) {
        $pdo->exec('ROLLBACK');
        json_erro('Erro no SQL: ' . $ex->getMessage(), 422);
    }
    $pdo->exec('ROLLBACK');
    json_resposta([
        'linhas' => ocultar_senhas(array_slice($linhas, 0, 1000)),
        'total'  => count($linhas),
        'tempo_ms' => $tempo,
    ]);
}

if ($METODO !== 'GET') {
    json_erro('Método não permitido.', 405);
}

$lista = tabelas();

if (empty($_GET['tabela'])) {
    $saida = [];
    foreach ($lista as $nome => $tipo) {
        $saida[] = [
            'nome'      => $nome,
            'tipo'      => $tipo,
            'registros' => (int) db()->query("SELECT COUNT(*) FROM `$nome`")->fetchColumn(),
        ];
    }
    json_resposta([
        'banco'    => DB_NAME,
        'servidor' => db()->getAttribute(PDO::ATTR_SERVER_VERSION),
        'tabelas'  => $saida,
    ]);
}

$tabela = $_GET['tabela'];
if (!isset($lista[$tabela])) {
    json_erro('Tabela não encontrada.', 404);
}

switch ($_GET['modo'] ?? 'dados') {
    case 'estrutura':
        $colunas = db()->query("SHOW FULL COLUMNS FROM `$tabela`")->fetchAll();
        $fks = db()->prepare(
            'SELECT COLUMN_NAME AS coluna, REFERENCED_TABLE_NAME AS tabela_ref, REFERENCED_COLUMN_NAME AS coluna_ref
               FROM information_schema.KEY_COLUMN_USAGE
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL'
        );
        $fks->execute([$tabela]);
        json_resposta(['colunas' => $colunas, 'chaves_estrangeiras' => $fks->fetchAll()]);

    case 'sql':
        $linha = db()->query("SHOW CREATE TABLE `$tabela`")->fetch(PDO::FETCH_NUM);
        json_resposta(['sql' => $linha[1]]);

    default:
        $linhas = db()->query("SELECT * FROM `$tabela` LIMIT 500")->fetchAll();
        json_resposta(['linhas' => ocultar_senhas($linhas)]);
}
