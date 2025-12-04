<?php
// Script de depuración para index.php
require_once 'config.php';

echo "<h2>Depuración de index.php</h2>";

try {
    $conn = getDatabaseConnection();
    echo "<p>✅ Conexión exitosa</p>";
    
    // Misma consulta que index.php
    $sql = "SELECT nombre, descripcion, precio, imagen_ruta, 
            COALESCE(categoria, 'General') as categoria,
            COALESCE(popular, 0) as popular,
            COALESCE(nuevo, 0) as nuevo,
            COALESCE(vegano, 0) as vegano
            FROM platos 
            ORDER BY categoria, nombre";
    
    echo "<p>Ejecutando consulta...</p>";
    $result = $conn->query($sql);
    
    if ($result) {
        echo "<p>✅ Consulta exitosa. Registros encontrados: " . $result->num_rows . "</p>";
        
        $platos = [];
        $categorias = [];
        
        while ($row = $result->fetch_assoc()) {
            $platos[] = $row;
            if (!isset($categorias[$row['categoria']])) {
                $categorias[$row['categoria']] = [];
            }
            $categorias[$row['categoria']][] = $row;
        }
        
        echo "<h3>Categorías encontradas:</h3>";
        echo "<ul>";
        foreach ($categorias as $cat => $items) {
            echo "<li><strong>$cat</strong>: " . count($items) . " platos</li>";
        }
        echo "</ul>";
        
        echo "<h3>Primeros 5 platos:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Popular</th><th>Nuevo</th><th>Vegano</th></tr>";
        for ($i = 0; $i < min(5, count($platos)); $i++) {
            $p = $platos[$i];
            echo "<tr>";
            echo "<td>{$p['nombre']}</td>";
            echo "<td>{$p['categoria']}</td>";
            echo "<td>\${$p['precio']}</td>";
            echo "<td>" . ($p['popular'] ? '✅' : '❌') . "</td>";
            echo "<td>" . ($p['nuevo'] ? '✅' : '❌') . "</td>";
            echo "<td>" . ($p['vegano'] ? '✅' : '❌') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p>❌ Error en consulta: " . $conn->error . "</p>";
    }
    
    closeDatabaseConnection($conn);
    
} catch (Exception $e) {
    echo "<p>❌ Excepción: " . $e->getMessage() . "</p>";
}
?>
