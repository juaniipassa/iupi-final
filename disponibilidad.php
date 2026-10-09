<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
iupyRequireSiteAccess();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function responder(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(405, ['error' => 'Método no permitido.']);
$productoId = filter_input(INPUT_POST, 'producto', FILTER_VALIDATE_INT);
$fechaIso = trim((string)($_POST['fecha'] ?? ''));
$fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $fechaIso);
$erroresFecha = DateTimeImmutable::getLastErrors();
if (!$fecha || ($erroresFecha !== false && ($erroresFecha['warning_count'] || $erroresFecha['error_count'])) || $fecha->format('Y-m-d') !== $fechaIso) {
    responder(400, ['error' => 'Ingresá una fecha válida.']);
}

$idsRemotos = [
    'Super Gigante' => 51, 'Castillo 3x5' => 7, 'Pelotero 3x3' => 8,
    'Reloj 5x5' => 72, 'Castillo 3x4' => 13, 'Desafío 6x6' => 63,
    'Aventura 4 6x5' => 68, 'Canchita 8x4' => 12, 'Canchita Box 8x4' => 69, 'Jirafa 4x5' => 2,
    'Selvita 4x4' => 62, 'Tejo' => 56, 'Metegol' => 24,
    'Sala Blanda 8 unidades' => 15, 'Cama Elástica 3m' => 11,
    'Deslizador Común' => 3,
];

require __DIR__ . '/config/db.php';
$stmt = $pdo->prepare('SELECT nombre, stock FROM productos WHERE id = ? AND activo = 1 LIMIT 1');
$stmt->execute([$productoId]);
$producto = $stmt->fetch();
if (!$producto || !isset($idsRemotos[$producto['nombre']])) responder(404, ['error' => 'Este producto no tiene consulta de disponibilidad configurada.']);

$configPath = __DIR__ . '/config/fabricadefiestas.local.php';
if (!is_file($configPath)) responder(503, ['error' => 'Falta configurar el acceso remoto en config/fabricadefiestas.local.php.']);
$config = require $configPath;
if (!is_array($config) || empty($config['usuario']) || !array_key_exists('contrasena', $config)) {
    responder(503, ['error' => 'La configuración de acceso remoto está incompleta.']);
}
if (!function_exists('curl_init')) responder(503, ['error' => 'PHP cURL no está habilitado en XAMPP.']);

$curl = curl_init('https://www.fabricadefiestas.com/alquileres/buscar/');
$headers = ['Content-Type: application/x-www-form-urlencoded; charset=UTF-8', 'Accept: text/html,application/xhtml+xml'];
if (!empty($config['cookie'])) $headers[] = 'Cookie: ' . $config['cookie'];
$authChallenge = '';
curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['search_fecha' => $fecha->format('d/m/Y')]),
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_USERPWD => $config['usuario'] . ':' . (string)$config['contrasena'],
    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 3,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_HEADERFUNCTION => static function ($handle, string $header) use (&$authChallenge): int {
        if (stripos($header, 'WWW-Authenticate:') === 0) $authChallenge = trim(substr($header, strlen('WWW-Authenticate:')));
        return strlen($header);
    },
]);
$html = curl_exec($curl);
$status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
$curlError = curl_error($curl);
curl_close($curl);
if ($html === false || $status < 200 || $status >= 300) {
    if ($html === false) {
        responder(502, ['error' => 'No se pudo conectar con fabricadefiestas.com.', 'detalle' => $curlError ?: 'Error de conexión cURL.']);
    }
    if ($status === 401 || $status === 403) {
        $detalle = $status === 401 && $authChallenge !== ''
            ? 'El servidor solicita autenticación HTTP; revisá usuario y contraseña.'
            : 'La página rechazó la sesión o el acceso; puede faltar una sesión vigente.';
        responder(502, ['error' => 'fabricadefiestas.com rechazó el acceso (HTTP ' . $status . '). ' . $detalle]);
    }
    if ($status === 404) {
        responder(502, ['error' => 'fabricadefiestas.com devolvió HTTP 404 para la consulta de alquileres.']);
    }
    responder(502, ['error' => 'fabricadefiestas.com respondió con HTTP ' . $status . '.']);
}

libxml_use_internal_errors(true);
$documento = new DOMDocument();
if (!$documento->loadHTML('<?xml encoding="utf-8" ?>' . $html)) responder(502, ['error' => 'No se pudo leer la respuesta del sistema remoto.']);
$xpath = new DOMXPath($documento);
$idRemoto = $idsRemotos[$producto['nombre']];
$nodos = $xpath->query('//input[@name="items[' . $idRemoto . ']"]');
if (!$nodos || $nodos->length === 0) responder(502, ['error' => 'El sistema remoto cambió su página y no se pudo encontrar el producto.']);

$reservadas = 0;
$input = $nodos->item(0);
for ($nodo = $input->nextSibling; $nodo; $nodo = $nodo->nextSibling) {
    if ($nodo instanceof DOMElement && strtolower($nodo->tagName) === 'input') break;
    if (!$nodo instanceof DOMElement || !in_array(strtolower($nodo->tagName), ['span', 'label'], true)) continue;
    $clases = ' ' . preg_replace('/\s+/', ' ', trim($nodo->getAttribute('class'))) . ' ';
    if (str_contains($clases, ' label-important ')) {
        $texto = trim($nodo->textContent);
        if (preg_match('/\d+/', $texto, $coincidencia)) $reservadas = (int)$coincidencia[0];
        break;
    }
}

$stock = max(0, (int)$producto['stock']);
$disponibles = max(0, $stock - $reservadas);
responder(200, [
    'disponible' => $disponibles > 0,
    'mensaje' => $disponibles > 0
        ? 'Disponible para esa fecha (' . $disponibles . ' unidad' . ($disponibles === 1 ? '' : 'es') . ').'
        : 'No disponible para esa fecha.',
]);
