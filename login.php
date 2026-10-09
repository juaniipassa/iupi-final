<?php
require __DIR__ . '/includes/auth.php';
$config = iupyAuthConfig();
$mode = ($_GET['mode'] ?? 'site') === 'admin' ? 'admin' : 'site';
$next = basename($_GET['next'] ?? ($mode === 'admin' ? 'admin.php' : 'catalogo.php'));
if (!in_array($next, ['catalogo.php', 'admin.php'], true)) $next = 'catalogo.php';
$error = '';

$host = strtolower(preg_replace('/:\\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
$isLocal = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
if (empty($config['site_gate_enabled']) && empty($config['admin_auth_enabled']) && $isLocal) {
    header('Location: catalogo.php');
    exit;
}
if (empty($config['site_gate_enabled']) && $mode === 'site') $mode = 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    iupyStartSession();
    $token = (string)($_POST['csrf'] ?? '');
    if (empty($_SESSION['iupy_csrf']) || !hash_equals($_SESSION['iupy_csrf'], $token)) {
        $error = 'La sesión venció. Actualizá la página e intentá de nuevo.';
    } else {
        $isAdmin = ($_POST['mode'] ?? 'site') === 'admin';
        $usernameKey = $isAdmin ? 'admin_username' : 'site_username';
        $hashKey = $isAdmin ? 'admin_password_hash' : 'site_password_hash';
        if (empty($config[$usernameKey]) || empty($config[$hashKey]) || strpos($config[$hashKey], 'REEMPLAZAR_') === 0) {
            iupyAuthUnavailable();
        }
        $userOk = hash_equals((string)$config[$usernameKey], (string)($_POST['username'] ?? ''));
        $passOk = password_verify((string)($_POST['password'] ?? ''), (string)$config[$hashKey]);
        if ($userOk && $passOk) {
            session_regenerate_id(true);
            if ($isAdmin) $_SESSION['iupy_admin_ok'] = true;
            else $_SESSION['iupy_site_ok'] = true;
            header('Location: ' . $next);
            exit;
        }
        $error = 'Usuario o contraseña incorrectos.';
    }
}

iupyStartSession();
if (empty($_SESSION['iupy_csrf'])) $_SESSION['iupy_csrf'] = bin2hex(random_bytes(32));
$title = $mode === 'admin' ? 'Acceso de edición' : 'Acceso del equipo';
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> | IUPY</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#fff4f8;color:#30242b;font:16px Arial,sans-serif}.box{width:min(420px,calc(100% - 36px));padding:28px;background:#fff;border-radius:18px;box-shadow:0 8px 30px #49223818}h1{color:#ed1e79;margin-top:0}label{display:block;margin:16px 0;font-weight:700}input{display:block;width:100%;box-sizing:border-box;padding:12px;margin-top:7px;border:1px solid #d9c6cf;border-radius:8px;font:inherit}button{border:0;border-radius:8px;padding:12px 18px;background:#ed1e79;color:white;font-weight:700;cursor:pointer}.error{color:#b42318}</style></head><body><main class="box"><h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['iupy_csrf'], ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="mode" value="<?= $mode ?>">
<label>Usuario<input name="username" autocomplete="username" required autofocus></label><label>Contraseña<input type="password" name="password" autocomplete="current-password" required></label><button type="submit">Ingresar</button></form></main></body></html>
