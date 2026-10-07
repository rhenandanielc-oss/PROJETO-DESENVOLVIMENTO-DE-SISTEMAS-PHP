<?php
/**
 * Arquivo incluído no início de todos os endpoints da API.
 * - Carrega configuração, banco, helpers e autenticação
 * - Trata exceções devolvendo JSON
 * - Valida o token CSRF em requisições que alteram dados
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

set_exception_handler(function (Throwable $ex) {
    error_log($ex->getMessage());
    $msg = 'Erro interno no servidor.';
    if ($ex instanceof PDOException && str_contains($ex->getMessage(), 'Duplicate entry')) {
        json_erro('Registro duplicado: este valor já está cadastrado.', 409);
    }
    if ($ex instanceof PDOException && str_contains($ex->getMessage(), 'foreign key constraint fails')) {
        json_erro('Não é possível excluir: existem registros vinculados a este item.', 409);
    }
    if (APP_DEBUG) {
        $msg .= ' ' . $ex->getMessage();
    }
    json_erro($msg, 500);
});

$METODO = $_SERVER['REQUEST_METHOD'];

if ($METODO !== 'GET' && !csrf_valido($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    json_erro('Token de segurança inválido. Recarregue a página.', 419);
}

/** Lê o parâmetro ?id= da URL. */
function id_url(): int
{
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        json_erro('ID não informado.', 400);
    }
    return $id;
}
