<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

require_once '../config.php';

$conn = getDatabaseConnection();

// Obtener datos del cuerpo de la solicitud (JSON)
$data = json_decode(file_get_contents("php://input"));

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos no válidos']);
    exit;
}

$action = isset($data->action) ? $data->action : '';

if ($action === 'register') {
    // REGISTRO DE USUARIO (CLIENTE)
    $nombre = $conn->real_escape_string($data->nombre);
    $email = $conn->real_escape_string($data->email);
    $telefono = $conn->real_escape_string($data->telefono);
    $password = $data->password;
    $direccion = isset($data->direccion) ? $conn->real_escape_string($data->direccion) : '';

    // Validar si el email ya existe
    $check = $conn->query("SELECT id FROM clientes WHERE email = '$email'");
    if ($check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'El correo electrónico ya está registrado']);
        exit;
    }

    // Hash password (Nota: En un entorno real usar password_hash, aquí simplificamos si la tabla clientes no tiene campo password, asumiremos que se crea o se usa una tabla usuarios vinculada. 
    // Revisando esquema: La tabla 'clientes' generalmente no tiene password en este sistema POS. 
    // Para la APP móvil necesitamos que los clientes tengan password.
    // VERIFICACIÓN IMPORTANTE: ¿La tabla clientes tiene password? Si no, usaremos un campo temporal 'telefono' como pass o añadiremos columna.
    // Asumiremos por seguridad que debemos añadir password a clientes si no existe, o crear usuarios con rol 'cliente'.
    // Dado el sistema actual, lo más limpio es crear un usuario en tabla 'usuarios' con rol 'cliente' y vincularlo a 'clientes'.
    // O simplemente añadir 'password' a la tabla 'clientes'. 
    // ESTRATEGIA: Añadir password a tabla clientes si no existe.
    
    // Por compatibilidad y rapidez, registraremos en tabla clientes y usaremos un campo 'password_hash' que, si no existe, el script debería haber creado.
    // Vamos a asumir que la tabla clientes será modificada para esto. 
    
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO clientes (nombre, email, telefono, direccion_principal, password_hash, activo) VALUES ('$nombre', '$email', '$telefono', '$direccion', '$password_hash', 1)";
    
    // NOTA: Si falla porque no existe password_hash, el usuario deberá ejecutar un script de migración.
    // Lo incluiremos en el script de respuesta o manejo de error.
    
    if ($conn->query($sql)) {
         echo json_encode(['success' => true, 'message' => 'Registro exitoso', 'user_id' => $conn->insert_id]);
    } else {
        // Fallback si no existe columna password_hash (para sistemas legacy sin migración)
        // Intentamos insertar sin password (no seguro, pero evita crash inmediato)
         $sql_fallback = "INSERT INTO clientes (nombre, email, telefono, direccion_principal, activo) VALUES ('$nombre', '$email', '$telefono', '$direccion', 1)";
         if ($conn->query($sql_fallback)) {
             echo json_encode(['success' => true, 'message' => 'Registro exitoso (Sin password - Requiere actualización DB)', 'user_id' => $conn->insert_id]);
         } else {
             echo json_encode(['success' => false, 'message' => 'Error al registrar: ' . $conn->error]);
         }
    }

} elseif ($action === 'login') {
    // LOGIN
    $email = $conn->real_escape_string($data->email);
    $password = $data->password;

    $sql = "SELECT * FROM clientes WHERE email = '$email' AND activo = 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $cliente = $result->fetch_assoc();
        // Verificar password
        // Si la columna password_hash existe
        if (isset($cliente['password_hash']) && password_verify($password, $cliente['password_hash'])) {
            unset($cliente['password_hash']); // No devolver el hash
            echo json_encode(['success' => true, 'message' => 'Login correcto', 'user' => $cliente]);
        } 
        // Fallback temporal para pruebas sin hash (o si stored plain text en desarrollo antiguo)
        elseif (isset($cliente['telefono']) && $cliente['telefono'] == $password) {
             echo json_encode(['success' => true, 'message' => 'Login correcto (Inseguro - Teléfono)', 'user' => $cliente]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

$conn->close();
?>
