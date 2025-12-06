<?php
require_once 'config.php';
$conn = getDatabaseConnection();

$result = $conn->query("SELECT * FROM usuarios WHERE rol = 'cajero' LIMIT 1");
if ($row = $result->fetch_assoc()) {
    echo "Found cashier: " . $row['nombre'] . " / User: " . $row['email'] . "\n"; 
    // Usually password is not retrievable if hashed, but we need to know the 'email' or 'username' to login.
    // Login system uses 'nombre' or 'email'? Let's check login.php logic if needed.
} else {
    echo "No cashier found.\n";
}
$conn->close();
?>
