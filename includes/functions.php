<?php
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function precioEfectivo(PDO $pdo, array $producto): float {
    // Coincidencia exacta por medidas y tipo; una regla "todos" sirve para cualquier tipo.
    if ($producto['ancho'] !== null && $producto['largo'] !== null) {
        $sql = "SELECT precio FROM reglas_precios
                WHERE activa = 1
                  AND ancho = :ancho AND largo = :largo
                  AND tipo_catalogo IN ('todos', :tipo)
                ORDER BY CASE WHEN tipo_catalogo = :tipo2 THEN 0 ELSE 1 END
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ancho' => $producto['ancho'],
            ':largo' => $producto['largo'],
            ':tipo' => $producto['tipo_catalogo'],
            ':tipo2' => $producto['tipo_catalogo']
        ]);
        $regla = $stmt->fetch();
        if ($regla) return (float)$regla['precio'];
    }
    return (float)$producto['precio_base'];
}

function imagenesProducto(PDO $pdo, int $id): array {
    $stmt = $pdo->prepare('SELECT ruta FROM producto_imagenes WHERE producto_id = ? ORDER BY orden, id');
    $stmt->execute([$id]);
    return array_column($stmt->fetchAll(), 'ruta');
}
