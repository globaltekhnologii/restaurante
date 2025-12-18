<?php
// includes/clientes_helper.php - Funciones auxiliares para gestión de clientes

/**
 * Buscar cliente por teléfono
 */
function buscarClientePorTelefono($conn, $telefono) {
    $telefono = $conn->real_escape_string($telefono);
    $sql = "SELECT * FROM clientes WHERE telefono = '$telefono' AND activo = 1 LIMIT 1";
    $result = $conn->query($sql);
    return $result->fetch_assoc();
}

/**
 * Buscar cliente por ID
 */
function obtenerClientePorId($conn, $cliente_id) {
    $cliente_id = (int)$cliente_id;
    $sql = "SELECT * FROM clientes WHERE id = $cliente_id LIMIT 1";
    $result = $conn->query($sql);
    return $result->fetch_assoc();
}

/**
 * Crear cliente automáticamente desde pedido
 */
function crearClienteAutomatico($conn, $datos) {
    $nombre = $conn->real_escape_string($datos['nombre']);
    $apellido = $conn->real_escape_string($datos['apellido'] ?? '');
    $telefono = $conn->real_escape_string($datos['telefono']);
    $email = $conn->real_escape_string($datos['email'] ?? '');
    $direccion = $conn->real_escape_string($datos['direccion'] ?? '');
    $ciudad = $conn->real_escape_string($datos['ciudad'] ?? '');
    
    // Verificar si ya existe
    $existe = buscarClientePorTelefono($conn, $telefono);
    if ($existe) {
        return $existe['id'];
    }
    
    $sql = "INSERT INTO clientes (nombre, apellido, telefono, email, direccion_principal, ciudad) 
            VALUES ('$nombre', '$apellido', '$telefono', '$email', '$direccion', '$ciudad')";
    
    if ($conn->query($sql)) {
        $cliente_id = $conn->insert_id;
        
        // Crear dirección principal si existe
        if (!empty($direccion)) {
            $sql_dir = "INSERT INTO direcciones_clientes (cliente_id, alias, direccion, ciudad, es_principal) 
                        VALUES ($cliente_id, 'Principal', '$direccion', '$ciudad', 1)";
            $conn->query($sql_dir);
        }
        
        return $cliente_id;
    }
    
    return null;
}

/**
 * Obtener direcciones de un cliente
 */
function obtenerDireccionesCliente($conn, $cliente_id) {
    $cliente_id = (int)$cliente_id;
    $sql = "SELECT * FROM direcciones_clientes 
            WHERE cliente_id = $cliente_id 
            ORDER BY es_principal DESC, id";
    $result = $conn->query($sql);
    $direcciones = [];
    while ($row = $result->fetch_assoc()) {
        $direcciones[] = $row;
    }
    return $direcciones;
}

/**
 * Obtener historial de pedidos de un cliente
 */
function obtenerHistorialCliente($conn, $cliente_id) {
    $cliente_id = (int)$cliente_id;
    $sql = "SELECT p.*, 
            u.nombre as nombre_usuario,
            COUNT(dp.id) as cantidad_productos
            FROM pedidos p
            LEFT JOIN usuarios u ON p.usuario_id = u.id
            LEFT JOIN detalle_pedido dp ON p.id = dp.pedido_id
            WHERE p.cliente_id = $cliente_id
            GROUP BY p.id
            ORDER BY p.fecha_pedido DESC
            LIMIT 50";
    $result = $conn->query($sql);
    $pedidos = [];
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = $row;
    }
    return $pedidos;
}

/**
 * Actualizar estadísticas de cliente
 */
function actualizarEstadisticasCliente($conn, $cliente_id) {
    $cliente_id = (int)$cliente_id;
    
    // Contar total de pedidos
    $sql_count = "SELECT COUNT(*) as total FROM pedidos WHERE cliente_id = $cliente_id";
    $result_count = $conn->query($sql_count);
    $total_pedidos = $result_count->fetch_assoc()['total'];
    
    // Obtener fecha del último pedido
    $sql_ultimo = "SELECT MAX(fecha_pedido) as ultimo FROM pedidos WHERE cliente_id = $cliente_id";
    $result_ultimo = $conn->query($sql_ultimo);
    $ultimo_pedido = $result_ultimo->fetch_assoc()['ultimo'];
    
    // Actualizar cliente
    $sql_update = "UPDATE clientes 
                   SET total_pedidos = $total_pedidos, 
                       ultimo_pedido = " . ($ultimo_pedido ? "'$ultimo_pedido'" : "NULL") . "
                   WHERE id = $cliente_id";
    $conn->query($sql_update);
}

/**
 * Buscar clientes por término (nombre, apellido, teléfono)
 */
function buscarClientes($conn, $termino, $limit = 10) {
    $termino = $conn->real_escape_string($termino);
    $sql = "SELECT id, nombre, apellido, telefono, email, direccion_principal 
            FROM clientes 
            WHERE (nombre LIKE '%$termino%' 
            OR apellido LIKE '%$termino%' 
            OR telefono LIKE '%$termino%')
            AND activo = 1
            ORDER BY nombre, apellido
            LIMIT $limit";
    $result = $conn->query($sql);
    $clientes = [];
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }
    return $clientes;
}

/**
 * Obtener resumen de estadísticas de clientes
 */
function obtenerEstadisticasClientes($conn) {
    $stats = [];
    
    // Total de clientes activos
    $result = $conn->query("SELECT COUNT(*) as total FROM clientes WHERE activo = 1");
    $stats['total_clientes'] = $result->fetch_assoc()['total'];
    
    // Nuevos clientes este mes
    $result = $conn->query("SELECT COUNT(*) as total FROM clientes 
                           WHERE activo = 1 
                           AND MONTH(fecha_registro) = MONTH(CURRENT_DATE())
                           AND YEAR(fecha_registro) = YEAR(CURRENT_DATE())");
    $stats['nuevos_mes'] = $result->fetch_assoc()['total'];
    
    // Con pedidos realizados
    $result = $conn->query("SELECT COUNT(*) as total FROM clientes 
                           WHERE activo = 1 AND total_pedidos > 0");
    $stats['con_pedidos'] = $result->fetch_assoc()['total'];
    
    // Top 5 clientes por pedidos
    $result = $conn->query("SELECT nombre, apellido, telefono, total_pedidos 
                           FROM clientes 
                           WHERE activo = 1 AND total_pedidos > 0
                           ORDER BY total_pedidos DESC 
                           LIMIT 5");
    $top_clientes = [];
    while ($row = $result->fetch_assoc()) {
        $top_clientes[] = $row;
    }
    $stats['top_clientes'] = $top_clientes;
    
    return $stats;
}

/**
 * Agregar nueva dirección a cliente
 */
function agregarDireccionCliente($conn, $cliente_id, $alias, $direccion, $ciudad, $referencia = '', $es_principal = false) {
    $cliente_id = (int)$cliente_id;
    $alias = $conn->real_escape_string($alias);
    $direccion = $conn->real_escape_string($direccion);
    $ciudad = $conn->real_escape_string($ciudad);
    $referencia = $conn->real_escape_string($referencia);
    $es_principal = $es_principal ? 1 : 0;
    
    // Si es principal, desmarcar las demás
    if ($es_principal) {
        $conn->query("UPDATE direcciones_clientes SET es_principal = 0 WHERE cliente_id = $cliente_id");
    }
    
    $sql = "INSERT INTO direcciones_clientes (cliente_id, alias, direccion, ciudad, referencia, es_principal) 
            VALUES ($cliente_id, '$alias', '$direccion', '$ciudad', '$referencia', $es_principal)";
    
    return $conn->query($sql);
}

/**
 * Formatear nombre completo de cliente
 */
function nombreCompletoCliente($cliente) {
    $nombre = trim($cliente['nombre'] . ' ' . ($cliente['apellido'] ?? ''));
    return $nombre;
}
