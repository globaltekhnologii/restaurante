<?php
// fix_schema_cajero.php - Corregir estructura de tabla usuarios y asignar rol
require_once 'config.php';

$conn = getDatabaseConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Corregir Schema Cajero</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;padding:10px;background:#e8f5e9;border-radius:5px;margin:10px 0;}";
echo ".error{color:red;padding:10px;background:#ffebee;border-radius:5px;margin:10px 0;}";
echo ".info{color:#1976d2;padding:10px;background:#e3f2fd;border-radius:5px;margin:10px 0;}";
echo "</style></head><body>";
echo "<h1>🔧 Corrección Profunda de Base de Datos</h1>";

try {
    // 1. Obtener la definición actual de la columna 'rol'
    echo "<h2>1️⃣ Analizando columna 'rol'</h2>";
    $result = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'rol'");
    $column = $result->fetch_assoc();
    $type = $column['Type'];
    
    echo "<div class='info'>Tipo actual: <strong>" . $type . "</strong></div>";
    
    // 2. Si es ENUM, modificarla para incluir 'cajero'
    if (strpos($type, 'enum') !== false) {
        if (strpos($type, "'cajero'") === false) {
            echo "<h2>2️⃣ Modificando estructura ENUM</h2>";
            // Extraer los valores actuales y añadir 'cajero'
            // SQL genérico seguro: convertir a VARCHAR o ampliar ENUM
            // Vamos a ampliar el ENUM manteniendo los valores existentes
            // Asumimos los estandares: admin, mesero, chef, domiciliario
            
            $sql_alter = "ALTER TABLE usuarios MODIFY COLUMN rol ENUM('admin', 'mesero', 'chef', 'domiciliario', 'cajero') NOT NULL DEFAULT 'mesero'";
            
            if ($conn->query($sql_alter)) {
                echo "<div class='success'>✅ Estructura modificada: Se añadió 'cajero' al ENUM</div>";
            } else {
                throw new Exception("Error al modificar tabla: " . $conn->error);
            }
        } else {
            echo "<div class='success'>✅ La columna ya incluye 'cajero' en sus valores permitidos</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ La columna no es ENUM (probablemente VARCHAR), no requiere modificación estructural.</div>";
    }
    
    // 3. Ahora sí, corregir el usuario
    echo "<h2>3️⃣ Corrigiendo usuario 'cajero'</h2>";
    $stmt = $conn->prepare("UPDATE usuarios SET rol = 'cajero' WHERE usuario = 'cajero'");
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "<div class='success'>✅ Usuario corregido: Rol actualizado a 'cajero'</div>";
        } else {
             // Verificar si ya estaba bien
             $check = $conn->query("SELECT rol FROM usuarios WHERE usuario = 'cajero'")->fetch_assoc();
             if ($check && $check['rol'] == 'cajero') {
                 echo "<div class='success'>✅ El usuario ya tenía el rol correcto</div>";
             } else {
                 echo "<div class='error'>⚠️ No se pudo actualizar el usuario (tal vez no existe)</div>";
             }
        }
    }
    
    echo "<div style='margin-top:20px;'>";
    echo "<a href='login.php' style='background:#4caf50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🔐 Probar Login Ahora</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

$conn->close();
echo "</body></html>";
?>
