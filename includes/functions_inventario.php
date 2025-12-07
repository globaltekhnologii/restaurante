<?php
// includes/functions_inventario.php

/**
 * Verifica si hay stock suficiente para una lista de platos y cantidades
 * 
 * @param mysqli $conn Conexión a la base de datos
 * @param array $items Array asociativo o de objetos con 'plato_id' y 'cantidad'
 * @return array ['valido' => bool, 'mensaje' => string, 'faltantes' => array]
 */
function validarStockPedido($conn, $items) {
    $faltantes = [];
    
    foreach ($items as $item) {
        $plato_id = is_object($item) ? $item->plato_id : $item['plato_id'];
        $cantidad_pedida = is_object($item) ? $item->cantidad : $item['cantidad'];
        
        // Obtener receta del plato
        $sql = "SELECT r.cantidad_necesaria, i.id as ingrediente_id, i.nombre, i.stock_actual, i.unidad_medida 
                FROM recetas r 
                JOIN ingredientes i ON r.ingrediente_id = i.id 
                WHERE r.plato_id = $plato_id";
        
        $receta = $conn->query($sql);
        
        while ($ing = $receta->fetch_assoc()) {
            $cantidad_total_necesaria = $ing['cantidad_necesaria'] * $cantidad_pedida;
            
            if ($ing['stock_actual'] < $cantidad_total_necesaria) {
                // Verificar si ya está en la lista de faltantes para sumar
                if (isset($faltantes[$ing['ingrediente_id']])) {
                    $faltantes[$ing['ingrediente_id']]['necesario'] += $cantidad_total_necesaria;
                } else {
                    $faltantes[$ing['ingrediente_id']] = [
                        'nombre' => $ing['nombre'],
                        'stock' => $ing['stock_actual'],
                        'necesario' => $cantidad_total_necesaria,
                        'unidad' => $ing['unidad_medida']
                    ];
                }
            }
        }
    }
    
    // Verificar si hay faltantes acumulados
    $errores = [];
    foreach ($faltantes as $id => $f) {
        if ($f['stock'] < $f['necesario']) {
            $errores[] = "{$f['nombre']}: Stock {$f['stock']} / Necesario {$f['necesario']} {$f['unidad']}";
        }
    }
    
    if (!empty($errores)) {
        return [
            'valido' => false,
            'mensaje' => "Stock insuficiente: " . implode(", ", $errores),
            'faltantes' => $errores
        ];
    }
    
    return ['valido' => true, 'mensaje' => 'Stock suficiente'];
}

/**
 * Descuenta el stock de ingredientes para un pedido confirmado
 * 
 * @param mysqli $conn Conexión a la base de datos
 * @param int $pedido_id ID del pedido
 * @param array $items Array con 'plato_id' y 'cantidad'
 * @param int $usuario_id ID del usuario responsable
 * @return bool True si tuvo éxito
 */
function descontarStockPedido($conn, $pedido_id, $items, $usuario_id) {
    foreach ($items as $item) {
        $plato_id = is_object($item) ? $item->plato_id : $item['plato_id'];
        $cantidad_pedida = is_object($item) ? $item->cantidad : $item['cantidad'];
        
        // Obtener receta
        $sql = "SELECT r.cantidad_necesaria, i.id as ingrediente_id, i.stock_actual 
                FROM recetas r 
                JOIN ingredientes i ON r.ingrediente_id = i.id 
                WHERE r.plato_id = $plato_id";
        
        $receta = $conn->query($sql);
        
        while ($ing = $receta->fetch_assoc()) {
            $cantidad_a_descontar = $ing['cantidad_necesaria'] * $cantidad_pedida;
            $ingrediente_id = $ing['ingrediente_id'];
            $stock_nuevo = $ing['stock_actual'] - $cantidad_a_descontar;
            
            // Registrar movimiento
            $sql_mov = "INSERT INTO movimientos_inventario 
                        (ingrediente_id, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, motivo, usuario_id, pedido_id) 
                        VALUES (?, 'salida', ?, ?, ?, 'Venta Pedido #$pedido_id', ?, ?)";
            
            $stmt = $conn->prepare($sql_mov);
            $stmt->bind_param("idddii", $ingrediente_id, $cantidad_a_descontar, $ing['stock_actual'], $stock_nuevo, $usuario_id, $pedido_id);
            $stmt->execute();
            
            // Actualizar ingrediente
            $conn->query("UPDATE ingredientes SET stock_actual = $stock_nuevo WHERE id = $ingrediente_id");
        }
    }
    return true;
}
?>
