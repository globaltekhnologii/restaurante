<?php
// Script para verificar y crear la tabla de publicidad si no existe
require_once 'config.php';

try {
    $conn = getDatabaseConnection();
    
    // Verificar si la tabla existe
    $result = $conn->query("SHOW TABLES LIKE 'publicidad'");
    
    if ($result->num_rows == 0) {
        echo "⚠️ La tabla 'publicidad' no existe. Creándola...<br><br>";
        
        $sql = "CREATE TABLE publicidad (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titulo VARCHAR(255) NOT NULL,
            tipo ENUM('imagen', 'video') DEFAULT 'imagen',
            archivo_url VARCHAR(500) NOT NULL,
            link_destino VARCHAR(500) NULL,
            fecha_inicio DATE NULL,
            fecha_fin DATE NULL,
            orden INT DEFAULT 0,
            activo TINYINT(1) DEFAULT 1,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_activo (activo),
            INDEX idx_fechas (fecha_inicio, fecha_fin)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql)) {
            echo "✅ Tabla 'publicidad' creada exitosamente.<br><br>";
            
            // Crear directorio para archivos
            $uploadDir = 'publicidad/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
                echo "✅ Directorio 'publicidad/' creado.<br><br>";
            }
            
            echo "<strong>✅ Sistema de publicidad configurado correctamente.</strong><br><br>";
            echo "Ahora puedes agregar anuncios desde el panel de administración.<br>";
            echo "<a href='admin.php'>Ir al Panel Admin</a>";
            
        } else {
            echo "❌ Error al crear la tabla: " . $conn->error;
        }
    } else {
        echo "✅ La tabla 'publicidad' ya existe.<br><br>";
        
        // Verificar si hay anuncios
        $result = $conn->query("SELECT COUNT(*) as total FROM publicidad WHERE activo = 1");
        $row = $result->fetch_assoc();
        
        if ($row['total'] > 0) {
            echo "✅ Hay <strong>{$row['total']}</strong> anuncio(s) activo(s).<br><br>";
            echo "El carrusel debería mostrarse en la página principal.<br>";
            echo "<a href='index.php'>Ver Menú</a> | <a href='admin.php'>Panel Admin</a>";
        } else {
            echo "⚠️ No hay anuncios activos en la base de datos.<br><br>";
            echo "Para que el carrusel se muestre, necesitas agregar al menos un anuncio desde el panel de administración.<br><br>";
            echo "<a href='admin.php'>Ir al Panel Admin</a>";
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
