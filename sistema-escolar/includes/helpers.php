<?php
/**
 * Funções auxiliares usadas em todo o sistema.
 */

/** Escapa texto para exibição segura em HTML (evita XSS). */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/** Envia uma resposta JSON e encerra o script. */
function json_resposta($dados, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Envia uma resposta de erro em JSON. */
function json_erro(string $mensagem, int $status = 400): void
{
    json_resposta(['erro' => $mensagem], $status);
}

/** Lê o corpo da requisição (JSON enviado pelo fetch do JavaScript). */
function corpo_requisicao(): array
{
    $bruto = file_get_contents('php://input');
    $dados = json_decode($bruto ?: '', true);
    return is_array($dados) ? $dados : $_POST;
}

/** Garante que os campos informados foram preenchidos. */
function exigir_campos(array $dados, array $campos): void
{
    foreach ($campos as $campo => $rotulo) {
        if (!isset($dados[$campo]) || trim((string) $dados[$campo]) === '') {
            json_erro("O campo \"$rotulo\" é obrigatório.", 422);
        }
    }
}

/** Valida se um valor pertence a uma lista permitida. */
function validar_opcao($valor, array $permitidos, string $rotulo): string
{
    if (!in_array($valor, $permitidos, true)) {
        json_erro("Valor inválido para \"$rotulo\".", 422);
    }
    return $valor;
}

/** Valida uma data no formato AAAA-MM-DD. */
function validar_data(?string $data, string $rotulo): string
{
    $d = DateTime::createFromFormat('Y-m-d', (string) $data);
    if (!$d || $d->format('Y-m-d') !== $data) {
        json_erro("Data inválida em \"$rotulo\".", 422);
    }
    return $data;
}

/** Valida um horário HH:MM. */
function validar_hora(?string $hora, string $rotulo): string
{
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', (string) $hora)) {
        json_erro("Horário inválido em \"$rotulo\".", 422);
    }
    return substr($hora, 0, 5);
}

/** Token CSRF: protege contra envios de formulários forjados por outros sites. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valido(?string $token): bool
{
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}
