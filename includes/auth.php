<?php
function iupyAuthConfig(): array {
    static $config = null;
    if ($config !== null) return $config;

    $localConfig = dirname(__DIR__) . '/config/access.local.php';
    if (is_file($localConfig)) {
        $loaded = require $localConfig;
        $config = is_array($loaded) ? $loaded : [];
        return $config;
    }

    // El catálogo es público. Sólo el acceso de edición requiere una credencial.
    $config = ['site_gate_enabled' => false, 'admin_auth_enabled' => true];
    return $config;
}

function iupyStartSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/'
    ]);
    session_start();
}

function iupyAuthUnavailable() {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('El acceso todavía no está configurado para este sitio.');
}

function iupyRequireSiteAccess(): void {
    $config = iupyAuthConfig();
    if (empty($config['site_gate_enabled'])) return;
    iupyStartSession();
    if (!empty($_SESSION['iupy_site_ok'])) return;

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        http_response_code(401);
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
            header('Content-Type: application/json; charset=UTF-8');
            exit(json_encode(['error' => 'Iniciá sesión para continuar.'], JSON_UNESCAPED_UNICODE));
        }
        exit('Iniciá sesión para continuar.');
    }

    $next = basename($_SERVER['SCRIPT_NAME'] ?? 'catalogo.php');
    header('Location: login.php?next=' . rawurlencode($next));
    exit;
}

function iupyRequireAdminAccess(): void {
    $config = iupyAuthConfig();
    iupyRequireSiteAccess();
    if (empty($config['admin_auth_enabled']) || empty($config['admin_username']) || empty($config['admin_password_hash'])) {
        iupyAuthUnavailable();
    }
    iupyStartSession();
    if (!empty($_SESSION['iupy_admin_ok'])) return;
    header('Location: login.php?mode=admin&next=admin.php');
    exit;
}

