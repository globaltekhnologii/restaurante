<?php
/*
 * INTEGRACIÓN DE CLIENTES EN PROCESAR_PEDIDO.PHP
 * 
 * Agregar este código en procesar_pedido.php después de recibir los datos del pedido
 * y ANTES de insertar en la tabla pedidos
 */

// Incluir helper de clientes
require_once 'includes/clientes_helper.php';

// Variables del cliente (recibidas del formulario)
$telefono_cliente = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
$nombre_cliente = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$direccion_cliente = isset($_POST['direccion']) ? trim($_POST['direccion']) : '';
$email_cliente = isset($_POST['email']) ? trim($_POST['email']) : '';
$cliente_id_form = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;

$cliente_id = null;

// Si viene un cliente_id del formulario, usarlo
if ($cliente_id_form > 0) {
    $cliente_id = $cliente_id_form;
    
    // Actualizar estadísticas del cliente (se hará después de crear el pedido)
    // actualizarEstadisticasCliente($conn, $cliente_id);
} 
// Si no, intentar buscar por teléfono o crear nuevo
elseif (!empty($telefono_cliente)) {
    // Buscar cliente existente por teléfono
    $cliente_existente = buscarClientePorTelefono($conn, $telefono_cliente);
    
    if ($cliente_existente) {
        $cliente_id = $cliente_existente['id'];
    } else {
        // Crear nuevo cliente automáticamente
        $datos_cliente = [
            'nombre' => $nombre_cliente,
            'apellido' => '', // Separar el apellido del nombre si es necesario
            'telefono' => $telefono_cliente,
            'email' => $email_cliente,
            'direccion' => $direccion_cliente,
            'ciudad' => '' // Agregar si está disponible en el formulario
        ];
        
        $cliente_id = crearClienteAutomatico($conn, $datos_cliente);
    }
}

/*
 * MODIFICAR LA CONSULTA INSERT DE PEDIDOS
 * 
 * Cambiar de:
 * INSERT INTO pedidos (usuario_id, mesa_id, tipo_pedido, ...)
 * 
 * A:
 * INSERT INTO pedidos (usuario_id, mesa_id, tipo_pedido, cliente_id, nombre_cliente, telefono_cliente, ...)
 */

// Ejemplo de cómo debería quedar:
$cliente_id_sql = $cliente_id ? $cliente_id : 'NULL';
$nombre_cliente_esc = $conn->real_escape_string($nombre_cliente);
$telefono_cliente_esc = $conn->real_escape_string($telefono_cliente);

$sql_insert_pedido = "INSERT INTO pedidos (
    usuario_id, 
    mesa_id, 
    tipo_pedido,
    cliente_id,
    nombre_cliente,
    telefono_cliente,
    direccion_entrega,
    metodo_pago,
    total,
    estado,
    fecha_pedido
) VALUES (
    $usuario_id,
    $mesa_id,
    '$tipo_pedido',
    $cliente_id_sql,
    '$nombre_cliente_esc',
    '$telefono_cliente_esc',
    '$direccion_entrega',
    '$metodo_pago',
    $total,
    '$estado_inicial',
    NOW()
)";

// ... resto del código de procesar_pedido.php

/*
 * DESPUÉS DE CREAR EL PEDIDO EXITOSAMENTE
 * Actualizar estadísticas del cliente
 */
if ($cliente_id && $conn->insert_id) {
    actualizarEstadisticasCliente($conn, $cliente_id);
}

?>
