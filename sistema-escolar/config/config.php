<?php
/**
 * Configurações gerais do sistema.
 * Ajuste os dados de acesso ao MySQL conforme o seu ambiente (XAMPP, WAMP, Laragon...).
 * Variáveis de ambiente (DB_HOST, DB_NAME...) têm prioridade - usadas no Docker.
 */

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'escola_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''); // XAMPP: senha vazia

define('APP_NOME', 'Escola Conectada');

// true = mostra a mensagem real dos erros (útil durante o desenvolvimento)
define('APP_DEBUG', true);

date_default_timezone_set('America/Sao_Paulo');
