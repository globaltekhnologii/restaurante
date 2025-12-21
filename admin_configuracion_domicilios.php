<?php
session_start();
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin'], 'login.php');

require_once 'config.php';
require_once 'includes/tenant_context.php'; // NUEVO: Soporte multi-tenencia
require_once 'includes/geocoding_service.php';
$conn = getDatabaseConnection();

// Obtener tenant_id del usuario actual
$tenant_id = getCurrentTenantId();

// Procesar formulario
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        
        if ($_POST['accion'] === 'guardar_gps') {
            // Guardar coordenadas GPS del restaurante
            $lat = floatval($_POST['latitud_restaurante']);
            $lon = floatval($_POST['longitud_restaurante']);
            
            $sql = "UPDATE configuracion_sistema SET latitud_restaurante = ?, longitud_restaurante = ? WHERE tenant_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ddi", $lat, $lon, $tenant_id);
            
            if ($stmt->execute()) {
                $mensaje = "Coordenadas GPS del restaurante guardadas exitosamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al guardar coordenadas";
                $tipo_mensaje = "error";
            }
        }
        
        elseif ($_POST['accion'] === 'geocodificar_restaurante') {
            // Geocodificar dirección del restaurante
            $direccion = $_POST['direccion_restaurante'];
            $ciudad = $_POST['ciudad_restaurante'];
            $pais = $_POST['pais_restaurante'];
            
            $coords = geocodificarDireccion($direccion, $ciudad, $pais);
            
            if ($coords) {
                $lat = $coords['lat'];
                $lon = $coords['lon'];
                
                $sql = "UPDATE configuracion_sistema 
                        SET direccion = ?, ciudad = ?, pais = ?, latitud_restaurante = ?, longitud_restaurante = ? 
                        WHERE tenant_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssddi", $direccion, $ciudad, $pais, $lat, $lon, $tenant_id);
                
                if ($stmt->execute()) {
                    $mensaje = "Dirección geocodificada y guardada: {$coords['display_name']}";
                    $tipo_mensaje = "success";
                    
                    // Limpiar caché de sesión
                    unset($_SESSION['info_negocio']);
                } else {
                    $mensaje = "Error al guardar coordenadas";
                    $tipo_mensaje = "error";
                }
            } else {
                $mensaje = "No se pudo geocodificar la dirección. Verifica que esté correcta.";
                $tipo_mensaje = "error";
            }
        }
        
        elseif ($_POST['accion'] === 'guardar_tarifas') {
            // Guardar configuración de tarifas
            $tarifa_base = floatval($_POST['tarifa_base']);
            $costo_por_km = floatval($_POST['costo_por_km']);
            $distancia_maxima = floatval($_POST['distancia_maxima']);
            $usar_rangos = isset($_POST['usar_rangos']) ? 1 : 0;
            
            $sql = "UPDATE configuracion_domicilios 
                    SET tarifa_base = ?, costo_por_km = ?, distancia_maxima = ?, usar_rangos = ? 
                    WHERE tenant_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("dddii", $tarifa_base, $costo_por_km, $distancia_maxima, $usar_rangos, $tenant_id);
            
            if ($stmt->execute()) {
                $mensaje = "Configuración de tarifas guardada exitosamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al guardar configuración";
                $tipo_mensaje = "error";
            }
        }
    }
}

// Obtener configuración actual
$sql_config = "SELECT * FROM configuracion_sistema WHERE tenant_id = $tenant_id";
$result_config = $conn->query($sql_config);
$config_sistema = $result_config->fetch_assoc();

$sql_tarifas = "SELECT * FROM configuracion_domicilios WHERE tenant_id = $tenant_id";
$result_tarifas = $conn->query($sql_tarifas);
$config_tarifas = $result_tarifas->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Domicilios GPS</title>
    <link rel="stylesheet" href="css/admin-modern.css">
    <style>
        .config-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }
        .config-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .config-card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #555;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-success {
            background: linear-gradient(135deg, #51cf66 0%, #40c057 100%);
            color: white;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        #map {
            height: 400px;
            width: 100%;
            border-radius: 8px;
            margin: 20px 0;
            border: 2px solid #e0e0e0;
        }
        .map-instructions {
            background: #fff3cd;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid #ffc107;
        }
    </style>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 15px 30px; color: white; margin-bottom: 20px;">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0;">🏪 Panel Administrativo</h2>
            <a href="admin.php" style="color: white; text-decoration: none; padding: 8px 16px; background: rgba(255,255,255,0.2); border-radius: 5px;">← Volver</a>
        </div>
    </div>
    
    <div class="config-container">
        <h1>⚙️ Configuración de Domicilios GPS</h1>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <!-- Configuración GPS del Restaurante -->
        <div class="config-card">
            <h2>📍 Ubicación del Restaurante</h2>
            
            <form method="POST">
                <input type="hidden" name="accion" value="geocodificar_restaurante">
                
                <div class="form-group">
                    <label>Dirección del Restaurante</label>
                    <input type="text" name="direccion_restaurante" 
                           value="<?php echo htmlspecialchars($config_sistema['direccion'] ?? ''); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Ciudad</label>
                        <input type="text" name="ciudad_restaurante" 
                               value="<?php echo htmlspecialchars($config_sistema['ciudad'] ?? 'Bogotá'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>País</label>
                        <input type="text" name="pais_restaurante" 
                               value="<?php echo htmlspecialchars($config_sistema['pais'] ?? 'Colombia'); ?>" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">🔍 Geocodificar Dirección</button>
            </form>
            
            <div class="info-box">
                <strong>Coordenadas actuales:</strong><br>
                Latitud: <?php echo $config_sistema['latitud_restaurante'] ?? 'No configurada'; ?><br>
                Longitud: <?php echo $config_sistema['longitud_restaurante'] ?? 'No configurada'; ?>
            </div>
            
            <!-- Mapa Interactivo -->
            <div style="margin-top: 30px;">
                <h3>🗺️ O selecciona la ubicación en el mapa:</h3>
                <div class="map-instructions">
                    <strong>💡 Instrucciones:</strong> Haz clic en el mapa para marcar la ubicación exacta de tu restaurante. Las coordenadas se actualizarán automáticamente.
                </div>
                <div id="map"></div>
            </div>
            
            <!-- Formulario manual de coordenadas -->
            <form method="POST" style="margin-top: 20px;">
                <input type="hidden" name="accion" value="guardar_gps">
                <p><strong>O ingresa las coordenadas manualmente:</strong></p>
                <div class="form-row">
                    <div class="form-group">
                        <label>Latitud</label>
                        <input type="number" step="0.00000001" name="latitud_restaurante" 
                               value="<?php echo $config_sistema['latitud_restaurante'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Longitud</label>
                        <input type="number" step="0.00000001" name="longitud_restaurante" 
                               value="<?php echo $config_sistema['longitud_restaurante'] ?? ''; ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">💾 Guardar Coordenadas</button>
            </form>
        </div>
        
        <!-- Configuración de Tarifas -->
        <div class="config-card">
            <h2>💰 Configuración de Tarifas</h2>
            
            <form method="POST">
                <input type="hidden" name="accion" value="guardar_tarifas">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Tarifa Base (COP)</label>
                        <input type="number" step="100" name="tarifa_base" 
                               value="<?php echo $config_tarifas['tarifa_base'] ?? 5000; ?>" required>
                        <small>Costo mínimo de domicilio</small>
                    </div>
                    <div class="form-group">
                        <label>Costo por Kilómetro (COP)</label>
                        <input type="number" step="100" name="costo_por_km" 
                               value="<?php echo $config_tarifas['costo_por_km'] ?? 1000; ?>" required>
                        <small>Costo adicional por cada km</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Distancia Máxima (km)</label>
                    <input type="number" step="0.1" name="distancia_maxima" 
                           value="<?php echo $config_tarifas['distancia_maxima'] ?? 10; ?>" required>
                    <small>Distancia máxima de entrega</small>
                </div>
                
                <button type="submit" class="btn btn-success">💾 Guardar Configuración</button>
            </form>
            
            <div class="info-box" style="margin-top: 20px;">
                <strong>📊 Ejemplo de cálculo:</strong><br>
                Para 5 km: $<?php 
                    $base = $config_tarifas['tarifa_base'] ?? 5000;
                    $por_km = $config_tarifas['costo_por_km'] ?? 1000;
                    echo number_format($base + (5 * $por_km), 0, ',', '.');
                ?> 
                (Base: $<?php echo number_format($base, 0, ',', '.'); ?> + 5km × $<?php echo number_format($por_km, 0, ',', '.'); ?>)
            </div>
        </div>
        
        <a href="admin.php" class="btn btn-primary">← Volver al Panel Admin</a>
    </div>
    
    <script>
        // Inicializar mapa
        const latActual = <?php echo $config_sistema['latitud_restaurante'] ?? '4.6097'; ?>;
        const lonActual = <?php echo $config_sistema['longitud_restaurante'] ?? '-74.0817'; ?>;
        
        // Crear mapa centrado en Bogotá (o en las coordenadas actuales si existen)
        const map = L.map('map').setView([latActual, lonActual], 13);
        
        // Agregar capa de OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);
        
        // Marcador inicial
        let marker = L.marker([latActual, lonActual], {
            draggable: true
        }).addTo(map);
        
        marker.bindPopup("<b>📍 Ubicación del Restaurante</b><br>Arrastra para ajustar").openPopup();
        
        // Actualizar coordenadas cuando se hace clic en el mapa
        map.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            
            // Mover marcador
            marker.setLatLng([lat, lng]);
            marker.bindPopup(`<b>📍 Nueva Ubicación</b><br>Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}`).openPopup();
            
            // Actualizar campos del formulario
            document.querySelector('input[name="latitud_restaurante"]').value = lat.toFixed(8);
            document.querySelector('input[name="longitud_restaurante"]').value = lng.toFixed(8);
        });
        
        // Actualizar coordenadas cuando se arrastra el marcador
        marker.on('dragend', function(e) {
            const lat = e.target.getLatLng().lat;
            const lng = e.target.getLatLng().lng;
            
            marker.bindPopup(`<b>📍 Nueva Ubicación</b><br>Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}`).openPopup();
            
            // Actualizar campos del formulario
            document.querySelector('input[name="latitud_restaurante"]').value = lat.toFixed(8);
            document.querySelector('input[name="longitud_restaurante"]').value = lng.toFixed(8);
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
