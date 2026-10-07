<?php
/**
 * Autenticação e controle de acesso por perfil.
 * Perfis: admin, professor, aluno, tecnico
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,   // JavaScript não lê o cookie de sessão
        'samesite' => 'Lax',
    ]);
    session_name('ESCOLA_SESSID');
    session_start();
}

const PERFIS = [
    'admin'     => 'Administrador',
    'professor' => 'Professor',
    'aluno'     => 'Aluno',
    'tecnico'   => 'Técnico de TI',
];

/** Dados do usuário logado ou null. */
function usuario_logado(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function usuario_id(): int
{
    return (int) ($_SESSION['usuario']['id'] ?? 0);
}

/** Verifica se o usuário logado possui um dos perfis informados. */
function tem_perfil(string ...$perfis): bool
{
    $u = usuario_logado();
    return $u !== null && in_array($u['perfil'], $perfis, true);
}

/** Grava o usuário na sessão após login bem-sucedido. */
function autenticar(array $usuario): void
{
    session_regenerate_id(true); // evita fixação de sessão
    $_SESSION['usuario'] = [
        'id'     => (int) $usuario['id'],
        'nome'   => $usuario['nome'],
        'email'  => $usuario['email'],
        'perfil' => $usuario['perfil'],
    ];
}

function encerrar_sessao(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* ---------- Proteção de PÁGINAS (redireciona) ---------- */

function exigir_login(): void
{
    if (!usuario_logado()) {
        header('Location: login.php');
        exit;
    }
}

function exigir_perfil(string ...$perfis): void
{
    exigir_login();
    if (!tem_perfil(...$perfis)) {
        http_response_code(403);
        header('Location: dashboard.php?erro=acesso');
        exit;
    }
}

/* ---------- Proteção da API (responde JSON) ---------- */

function api_exigir_login(): void
{
    if (!usuario_logado()) {
        json_erro('Sessão expirada. Faça login novamente.', 401);
    }
}

function api_exigir_perfil(string ...$perfis): void
{
    api_exigir_login();
    if (!tem_perfil(...$perfis)) {
        json_erro('Você não tem permissão para esta ação.', 403);
    }
}
