<?php
session_start();
require_once '../auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin'], '../login.php');

require_once '../config.php';
require_once '../includes/tenant_context.php'; // NUEVO: Soporte multi-tenencia
header('Content-Type: application/json');

$conn = getDatabaseConnection();
$tenant_id = getCurrentTenantId(); // Obtener tenant actual
$uploadDir = '../publicidad/';

// Asegurar que existe el directorio
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Acción solicitada
$accion = $_REQUEST['accion'] ?? '';

try {
    switch ($accion) {
        case 'listar':
            listarAnuncios($conn, $tenant_id);
            break;
            
        case 'crear':
            crearAnuncio($conn, $uploadDir, $tenant_id);
            break;
            
        case 'actualizar':
            actualizarAnuncio($conn, $tenant_id);
            break;
            
        case 'renovar':
            renovarAnuncio($conn, $tenant_id);
            break;
            
        case 'cambiar_estado':
            cambiarEstado($conn, $tenant_id);
            break;
            
        case 'eliminar':
            eliminarAnuncio($conn, $uploadDir, $tenant_id);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();

// Funciones Auxiliares

function listarAnuncios($conn, $tenant_id) {
    $sql = "SELECT * FROM publicidad WHERE tenant_id = $tenant_id ORDER BY orden ASC, fecha_creacion DESC";
    $result = $conn->query($sql);
    
    $anuncios = [];
    while ($row = $result->fetch_assoc()) {
        // Ajustar URL para frontend
        $row['archivo_url'] = 'publicidad/' . basename($row['archivo_url']);
        $anuncios[] = $row;
    }
    
    echo json_encode($anuncios);
}

function crearAnuncio($conn, $uploadDir, $tenant_id) {
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir archivo');
    }

    $titulo = $_POST['titulo'] ?? '';
    $tipo = $_POST['tipo'] ?? 'imagen';
    $link = $_POST['link_destino'] ?? '';
    $inicio = !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : date('Y-m-d');
    $fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : NULL;

    // Validar tipo de archivo
    $fileInfo = pathinfo($_FILES['archivo']['name']);
    $ext = strtolower($fileInfo['extension']);
    $allowedImages = ['jpg', 'jpeg', 'png', 'gif'];
    $allowedVideos = ['mp4', 'webm'];
    
    if ($tipo === 'video') {
        if (!in_array($ext, $allowedVideos)) throw new Exception('Formato de video no permitido');
    } else {
        if (!in_array($ext, $allowedImages)) throw new Exception('Formato de imagen no permitido');
    }

    // Generar nombre único
    $fileName = uniqid() . '.' . $ext;
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $targetPath)) {
        throw new Exception('Error al guardar el archivo en el servidor');
    }

    $sql = "INSERT INTO publicidad (tenant_id, titulo, tipo, archivo_url, link_destino, fecha_inicio, fecha_fin, activo) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssss", $tenant_id, $titulo, $tipo, $fileName, $link, $inicio, $fin);
    
    if (!$stmt->execute()) {
        unlink($targetPath); // Borrar archivo si falla BD
        throw new Exception('Error al guardar en base de datos: ' . $conn->error);
    }
    
    echo json_encode(['success' => true]);
}

function cambiarEstado($conn, $tenant_id) {
    $id = (int)($_POST['id'] ?? 0);
    $activo = (int)($_POST['activo'] ?? 0);
    
    $sql = "UPDATE publicidad SET activo = ? WHERE id = ? AND tenant_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $activo, $id, $tenant_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Error al actualizar estado');
    }
}

function eliminarAnuncio($conn, $uploadDir, $tenant_id) {
    $id = (int)($_POST['id'] ?? 0);
    
    // Obtener archivo para borrar
    $sql = "SELECT archivo_url FROM publicidad WHERE id = ? AND tenant_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $tenant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $filePath = $uploadDir . $row['archivo_url'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    $sqlDelete = "DELETE FROM publicidad WHERE id = ? AND tenant_id = ?";
    $stmtDelete = $conn->prepare($sqlDelete);
    $stmtDelete->bind_param("ii", $id, $tenant_id);
    
    if ($stmtDelete->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Error al eliminar registro');
    }
}

function actualizarAnuncio($conn, $tenant_id) {
    $id = (int)($_POST['id'] ?? 0);
    $titulo = $_POST['titulo'] ?? '';
    $fecha_inicio = !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : NULL;
    $fecha_fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : NULL;
    $link_destino = $_POST['link_destino'] ?? '';
    
    if (!$id) {
        throw new Exception('ID de anuncio no válido');
    }
    
    $sql = "UPDATE publicidad 
            SET titulo = ?, fecha_inicio = ?, fecha_fin = ?, link_destino = ? 
            WHERE id = ? AND tenant_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssii", $titulo, $fecha_inicio, $fecha_fin, $link_destino, $id, $tenant_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Error al actualizar anuncio: ' . $conn->error);
    }
}

function renovarAnuncio($conn) {
    $id = (int)($_POST['id'] ?? 0);
    $dias = (int)($_POST['dias'] ?? 30);
    
    if (!$id) {
        throw new Exception('ID de anuncio no válido');
    }
    
    // Calcular nueva fecha de fin
    $nueva_fecha_fin = date('Y-m-d', strtotime("+$dias days"));
    
    $sql = "UPDATE publicidad 
            SET fecha_fin = ?, activo = 1 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $nueva_fecha_fin, $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'nueva_fecha_fin' => $nueva_fecha_fin
        ]);
    } else {
        throw new Exception('Error al renovar anuncio: ' . $conn->error);
    }
}
?>
