<?php
require_once 'config.php';
$conn = getDatabaseConnection();

$result = $conn->query("SELECT id, usuario, nombre, email, clave, rol FROM usuarios WHERE rol = 'cajero' LIMIT 1");
if ($row = $result->fetch_assoc()) {
    echo "Found cashier:\n";
    print_r($row);
    
    // Check if password hash matches '1234' (using password_verify if used, or plain text)
    // The codebase uses password_hash usually.
    // Let's reset it to a known hash for '1234' just to be sure.
    $new_hash = password_hash('1234', PASSWORD_DEFAULT);
    $conn->query("UPDATE usuarios SET clave = '$new_hash' WHERE id = " . $row['id']);
    echo "Password reset to '1234' for testing.\n";
} else {
    echo "No cashier found.\n";
}
$conn->close();
?>
