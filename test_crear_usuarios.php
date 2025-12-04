<?php
// test_crear_usuarios.php - Script para crear usuarios de prueba

require_once 'config.php';

$conn = getDatabaseConnection();

echo "\u003ch2\u003eCreando usuarios de prueba...\u003c/h2\u003e\u003cbr\u003e";

// Array de usuarios a crear
$usuarios = [
    [
        'usuario' => 'mesero_test',
        'clave' => 'Mesero@123',
        'nombre' => 'Juan Pérez (Mesero)',
        'email' => 'mesero@restaurante.com',
        'rol' => 'mesero'
    ],
    [
        'usuario' => 'chef_test',
        'clave' => 'Chef@123',
        'nombre' => 'María García (Chef)',
        'email' => 'chef@restaurante.com',
        'rol' => 'chef'
    ],
    [
        'usuario' => 'domiciliario_test',
        'clave' => 'Domiciliario@123',
        'nombre' => 'Carlos López (Domiciliario)',
        'email' => 'domiciliario@restaurante.com',
        'rol' => 'domiciliario'
    ]
];

foreach ($usuarios as $user) {
    // Verificar si el usuario ya existe
    $check = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
    $check->bind_param("s", $user['usuario']);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        echo "⚠️ Usuario '{$user['usuario']}' ya existe. Omitiendo...\u003cbr\u003e";
        $check->close();
        continue;
    }
    $check->close();
    
    // Hashear contraseña
    $clave_hash = password_hash($user['clave'], PASSWORD_DEFAULT);
    
    // Insertar usuario
    $stmt = $conn->prepare("INSERT INTO usuarios (usuario, clave, nombre, email, rol, activo) VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("sssss", $user['usuario'], $clave_hash, $user['nombre'], $user['email'], $user['rol']);
    
    if ($stmt->execute()) {
        echo "✅ Usuario '{$user['usuario']}' creado exitosamente\u003cbr\u003e";
        echo "   - Nombre: {$user['nombre']}\u003cbr\u003e";
        echo "   - Email: {$user['email']}\u003cbr\u003e";
        echo "   - Rol: {$user['rol']}\u003cbr\u003e";
        echo "   - Contraseña: {$user['clave']}\u003cbr\u003e\u003cbr\u003e";
    } else {
        echo "❌ Error al crear usuario '{$user['usuario']}': " . $stmt->error . "\u003cbr\u003e\u003cbr\u003e";
    }
    
    $stmt->close();
}

echo "\u003chr\u003e";
echo "\u003ch3\u003eResumen de usuarios en el sistema:\u003c/h3\u003e";

// Mostrar todos los usuarios
$query = "SELECT usuario, nombre, email, rol, activo FROM usuarios ORDER BY rol, usuario";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "\u003ctable border='1' cellpadding='10' style='border-collapse: collapse;'\u003e";
    echo "\u003ctr\u003e\u003cth\u003eUsuario\u003c/th\u003e\u003cth\u003eNombre\u003c/th\u003e\u003cth\u003eEmail\u003c/th\u003e\u003cth\u003eRol\u003c/th\u003e\u003cth\u003eActivo\u003c/th\u003e\u003c/tr\u003e";
    
    while ($row = $result->fetch_assoc()) {
        $activo = $row['activo'] ? '✅' : '❌';
        echo "\u003ctr\u003e";
        echo "\u003ctd\u003e{$row['usuario']}\u003c/td\u003e";
        echo "\u003ctd\u003e{$row['nombre']}\u003c/td\u003e";
        echo "\u003ctd\u003e{$row['email']}\u003c/td\u003e";
        echo "\u003ctd\u003e\u003cstrong\u003e{$row['rol']}\u003c/strong\u003e\u003c/td\u003e";
        echo "\u003ctd\u003e{$activo}\u003c/td\u003e";
        echo "\u003c/tr\u003e";
    }
    
    echo "\u003c/table\u003e";
} else {
    echo "No hay usuarios en el sistema.";
}

$conn->close();

echo "\u003chr\u003e";
echo "\u003cp\u003e\u003ca href='login.php'\u003eIr al Login\u003c/a\u003e | \u003ca href='admin.php'\u003eIr al Panel Admin\u003c/a\u003e\u003c/p\u003e";
?>
