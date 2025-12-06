<?php
require_once 'config.php';

$conn = new mysqli("localhost", "root", "", "menu_restaurante");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Agregar columnas a pedidos
$sql1 = "ALTER TABLE pedidos 
ADD COLUMN origen VARCHAR(20) DEFAULT 'web',
ADD COLUMN conversation_id INT NULL,
ADD COLUMN notificado BOOLEAN DEFAULT FALSE";

if ($conn->query($sql1) === TRUE) {
    echo "Columnas agregadas exitosamente a 'pedidos'.\n";
} else {
    echo "Error agregando columnas: " . $conn->error . "\n";
}

$conn->close();
?>
