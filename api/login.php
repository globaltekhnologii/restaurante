<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

require_once '../config.php';

$conn = getDatabaseConnection();

// Obtener datos del cuerpo de la solicitud (JSON)
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

// Verificar si se recibieron los datos
if (!isset($input['usuario']) || !isset($input['clave'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Faltan datos (usuario y clave requeridos)'
    ]);
    exit;
}

$usuario_input = trim($input['usuario']);
$clave_input = trim($input['clave']);

// Preparar consulta
$stmt = $conn->prepare("SELECT id, usuario, clave, rol, nombre FROM usuarios WHERE usuario = ? AND activo = 1");
$stmt->bind_param("s", $usuario_input);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    
    // Verificar rol si se especifica (opcional, pero útil para evitar que chefs logueen en app de domiciliarios)
    if (isset($input['rol']) && $input['rol'] !== $row['rol']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Rol incorrecto para esta aplicación'
        ]);
        exit;
    }

    $password_valida = false;
    
    // Verificar contraseña (Hash o Texto Plano con actualización)
    if (password_verify($clave_input, $row['clave'])) {
        $password_valida = true;
    } elseif ($clave_input === $row['clave']) {
        $password_valida = true;
        // Actualizar a hash por seguridad (igual que en web)
        $clave_hash = password_hash($clave_input, PASSWORD_DEFAULT);
        $update_pwd = $conn->prepare("UPDATE usuarios SET clave = ? WHERE id = ?");
        $update_pwd->bind_param("si", $clave_hash, $row['id']);
        $update_pwd->execute();
        $update_pwd->close();
    }
    
    if ($password_valida) {
        // Login exitoso
        
        // Registrar último acceso
        $update = $conn->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
        $update->bind_param("i", $row['id']);
        $update->execute();
        $update->close();

        echo json_encode([
            'success' => true,
            'message' => 'Login correcto',
            'user' => [
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                'usuario' => $row['usuario'],
                'rol' => $row['rol']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
}

$stmt->close();
$conn->close();
?>
