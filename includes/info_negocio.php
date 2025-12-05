<?php
// includes/info_negocio.php

// Verificar si ya existe la conexión, si no, crearla
if (!isset($conn)) {
    require_once __DIR__ . '/../config.php';
    $conn = getDatabaseConnection();
    $cerrar_conexion = true;
} else {
    $cerrar_conexion = false;
}

// Obtener configuración
$sql = "SELECT * FROM configuracion_sistema WHERE id = 1 LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $info_negocio = $result->fetch_assoc();
} else {
    // Valores por defecto si falla la base de datos
    $info_negocio = [
        'nombre_restaurante' => 'Restaurante El Sabor',
        'logo_url' => 'img/logo_default.png',
        'direccion' => 'Calle Principal #123',
        'telefono' => '555-0123',
        'email' => 'contacto@restaurante.com',
        'sitio_web' => 'www.restaurante.com',
        'facebook' => '',
        'instagram' => '',
        'horario_atencion' => 'Lunes a Domingo: 12:00 PM - 10:00 PM',
        'pais' => 'Colombia',
        'departamento' => 'Cundinamarca',
        'ciudad' => 'Bogotá',
        'moneda' => 'COP',
        'impuesto_porcentaje' => 0.00,
        'horario_apertura_domicilios' => '09:00:00',
        'horario_cierre_domicilios' => '22:00:00',
        'domicilios_habilitados' => 1
    ];
}

// Cerrar conexión solo si fue creada en este script
if ($cerrar_conexion) {
    $conn->close();
}
?>
