<?php
// Copiá este archivo como access.local.php fuera del control de versiones.
// Configurá hashes generados con password_hash() y usa siempre HTTPS en producción.
return [
    'site_gate_enabled' => true,
    'site_username' => 'equipo',
    'site_password_hash' => 'REEMPLAZAR_POR_HASH',
    // Se usa cuando el sitio ya sea público y solo admin.php deba pedir clave.
    'admin_auth_enabled' => true,
    'admin_username' => 'editar',
    'admin_password_hash' => 'REEMPLAZAR_POR_HASH'
];
