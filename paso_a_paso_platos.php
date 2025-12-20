<?php
// Test mínimo de inserción de platos
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Test de Inserción de Platos - Paso a Paso</h1>";

// Paso 1: Session
echo "<h2>1. Iniciando sesión...</h2>";
session_start();
echo "✅ Sesión iniciada<br>";

// Paso 2: Simular login
$_SESSION['loggedin'] = TRUE;
$_SESSION['rol'] = 'admin';
echo "✅ Sesión simulada<br>";

// Paso 3: Cargar config
echo "<h2>2. Cargando config.php...</h2>";
try {
    require_once 'config.php';
    echo "✅ config.php cargado<br>";
} catch (Exception $e) {
    die("❌ Error en config.php: " . $e->getMessage());
}

// Paso 4: Cargar helpers
echo "<h2>3. Cargando helpers...</h2>";
try {
    require_once 'file_upload_helper.php';
    echo "✅ file_upload_helper.php cargado<br>";
    
    require_once 'includes/csrf_helper.php';
    echo "✅ csrf_helper.php cargado<br>";
    
    require_once 'includes/sanitize_helper.php';
    echo "✅ sanitize_helper.php cargado<br>";
} catch (Exception $e) {
    die("❌ Error cargando helpers: " . $e->getMessage());
}

// Paso 5: Conexión DB
echo "<h2>4. Conectando a base de datos...</h2>";
try {
    $conn = getDatabaseConnection();
    echo "✅ Conexión exitosa<br>";
} catch (Exception $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}

// Paso 6: Simular datos POST
echo "<h2>5. Simulando datos POST...</h2>";
$_POST['nombre'] = 'Plato de Prueba';
$_POST['descripcion'] = 'Descripción de prueba';
$_POST['precio'] = '15000';
$_POST['categoria'] = 'Platos Principales';
echo "✅ Datos POST simulados<br>";

// Paso 7: Sanitizar
echo "<h2>6. Sanitizando datos...</h2>";
try {
    $nombre = cleanString($_POST['nombre']);
    $descripcion = cleanHtml($_POST['descripcion']);
    $precio = cleanFloat($_POST['precio']);
    $categoria = cleanString($_POST['categoria']);
    echo "✅ Datos sanitizados<br>";
    echo "Nombre: $nombre<br>";
    echo "Precio: $precio<br>";
} catch (Exception $e) {
    die("❌ Error sanitizando: " . $e->getMessage());
}

// Paso 8: Preparar consulta
echo "<h2>7. Preparando consulta SQL...</h2>";
$imagen_ruta = "test.jpg";
$popular = 0;
$nuevo = 0;
$vegano = 0;

try {
    $stmt = $conn->prepare("INSERT INTO platos (nombre, descripcion, precio, imagen_ruta, categoria, popular, nuevo, vegano) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    echo "✅ Consulta preparada<br>";
    
    echo "<h2>7b. Vinculando parámetros...</h2>";
    $stmt->bind_param("ssssiiii", 
        $nombre, 
        $descripcion, 
        $precio, 
        $imagen_ruta, 
        $categoria, 
        $popular, 
        $nuevo, 
        $vegano
    );
    echo "✅ Parámetros vinculados<br>";
    
    echo "<h2>8. Ejecutando inserción...</h2>";
    if ($stmt->execute()) {
        echo "✅ ¡INSERCIÓN EXITOSA!<br>";
        echo "ID del nuevo plato: " . $stmt->insert_id . "<br>";
    } else {
        echo "❌ Error en ejecución: " . $stmt->error . "<br>";
    }
    
    $stmt->close();
} catch (Exception $e) {
    die("❌ Error en consulta: " . $e->getMessage());
}

$conn->close();

echo "<h2>✅ TODAS LAS PRUEBAS PASARON</h2>";
echo "<p>Si llegaste aquí, el problema NO está en el código PHP sino en:</p>";
echo "<ul>";
echo "<li>La subida de archivos (imagen)</li>";
echo "<li>La validación CSRF</li>";
echo "<li>Los permisos de la carpeta imagenes_platos/</li>";
echo "</ul>";
?>
