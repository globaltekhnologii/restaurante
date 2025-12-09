<?php
session_start();

require_once 'config.php';

// Obtener ID del pedido
if (!isset($_GET['id'])) {
    // Si no hay sesión, redirigir a mis_pedidos
    if (!isset($_SESSION['user_id'])) {
        header("Location: mis_pedidos.php");
        exit;
    }
    header("Location: " . ($_SESSION['rol'] === 'admin' ? 'admin_pedidos.php' : $_SESSION['rol'] . '.php'));
    exit;
}

$pedido_id = intval($_GET['id']);
$conn = getDatabaseConnection();

// Determinar si es acceso público o con sesión
$es_acceso_publico = !isset($_SESSION['user_id']);

if ($es_acceso_publico) {
    // Acceso público: solo obtener el pedido sin restricciones de usuario
    $sql = "SELECT p.*, m.numero_mesa, u.nombre as mesero_nombre, d.nombre as domiciliario_nombre, d.telefono as domiciliario_telefono
            FROM pedidos p 
            LEFT JOIN mesas m ON p.mesa_id = m.id 
            LEFT JOIN usuarios u ON p.usuario_id = u.id 
            LEFT JOIN usuarios d ON p.domiciliario_id = d.id 
            WHERE p.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pedido_id);
} else {
    // Acceso con sesión: aplicar restricciones según rol
    require_once 'auth_helper.php';
    verificarSesion();
    
    $user_id = $_SESSION['user_id'];
    $user_rol = $_SESSION['rol'];
    
    $sql = "SELECT p.*, m.numero_mesa, u.nombre as mesero_nombre, d.nombre as domiciliario_nombre, d.telefono as domiciliario_telefono
            FROM pedidos p 
            LEFT JOIN mesas m ON p.mesa_id = m.id 
            LEFT JOIN usuarios u ON p.usuario_id = u.id 
            LEFT JOIN usuarios d ON p.domiciliario_id = d.id 
            WHERE p.id = ?";
    
    // Si no es admin, agregar filtros de permiso
    if ($user_rol === 'mesero') {
        $sql .= " AND p.usuario_id = ?";
    } elseif ($user_rol === 'domiciliario') {
        $sql .= " AND p.domiciliario_id = ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($user_rol === 'mesero' || $user_rol === 'domiciliario') {
        $stmt->bind_param("ii", $pedido_id, $user_id);
    } else {
        $stmt->bind_param("i", $pedido_id);
    }
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    
    if ($es_acceso_publico) {
        header("Location: mis_pedidos.php?error=" . urlencode("Pedido no encontrado"));
    } else {
        header("Location: " . ($user_rol === 'admin' ? 'admin_pedidos.php' : $user_rol . '.php') . "?error=" . urlencode("Pedido no encontrado o sin permisos"));
    }
    exit;
}

$pedido = $result->fetch_assoc();
$stmt->close();

// Obtener items del pedido
$stmt = $conn->prepare("SELECT * FROM pedidos_items WHERE pedido_id = ?");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$items_result = $stmt->get_result();
$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

// Determinar color del estado
$estado_colors = [
    'pendiente' => '#ffd93d',
    'confirmado' => '#4299e1',
    'preparando' => '#ed8936',
    'en_camino' => '#4299e1',
    'entregado' => '#48bb78',
    'cancelado' => '#f44336'
];

$estado_color = $estado_colors[$pedido['estado']] ?? '#999';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido <?php echo htmlspecialchars($pedido['numero_pedido']); ?> - Restaurante El Sabor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar h1 { font-size: 1.3em; }
        
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
        }
        
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .pedido-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .pedido-numero {
            font-size: 1.8em;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
        
        .pedido-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .meta-item {
            padding: 15px;
            background: #f7fafc;
            border-radius: 8px;
        }
        
        .meta-label {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 5px;
        }
        
        .meta-value {
            font-weight: 600;
            color: #333;
        }
        
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
            color: white;
        }
        
        .section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .info-label {
            font-size: 0.85em;
            color: #666;
            font-weight: 600;
        }
        
        .info-value {
            font-size: 1em;
            color: #333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        thead {
            background: #f7fafc;
        }
        
        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #666;
            font-size: 0.9em;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .item-nombre {
            font-weight: 600;
        }
        
        .total-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 1.1em;
        }
        
        .total-final {
            font-size: 1.4em;
            font-weight: bold;
            color: #667eea;
            padding-top: 10px;
            border-top: 2px solid #e0e0e0;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -23px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #667eea;
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            left: -18px;
            top: 17px;
            width: 2px;
            height: calc(100% - 12px);
            background: #e0e0e0;
        }
        
        .timeline-item:last-child::after {
            display: none;
        }
        
        .timeline-time {
            font-size: 0.85em;
            color: #666;
        }
        
        .timeline-event {
            font-weight: 600;
            color: #333;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📋 Detalle del Pedido</h1>
        <a href="<?php echo $es_acceso_publico ? 'mis_pedidos.php?telefono=' . urlencode($pedido['telefono']) : ($user_rol === 'admin' ? 'admin_pedidos.php' : $user_rol . '.php'); ?>">← Volver</a>
    </div>

    <div class="container">
        <!-- Header del Pedido -->
        <div class="pedido-header">
            <div class="pedido-numero">
                🧾 <?php echo htmlspecialchars($pedido['numero_pedido']); ?>
            </div>
            <span class="badge" style="background: <?php echo $estado_color; ?>;">
                <?php echo ucfirst(str_replace('_', ' ', $pedido['estado'])); ?>
            </span>
            
            <div class="pedido-meta">
                <div class="meta-item">
                    <div class="meta-label">Fecha y Hora</div>
                    <div class="meta-value"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></div>
                </div>
                <?php if ($pedido['numero_mesa']): ?>
                <div class="meta-item">
                    <div class="meta-label">Mesa</div>
                    <div class="meta-value">🪑 <?php echo htmlspecialchars($pedido['numero_mesa']); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($pedido['mesero_nombre']): ?>
                <div class="meta-item">
                    <div class="meta-label">Mesero</div>
                    <div class="meta-value">👤 <?php echo htmlspecialchars($pedido['mesero_nombre']); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($pedido['domiciliario_nombre']): ?>
                <div class="meta-item">
                    <div class="meta-label">Domiciliario</div>
                    <div class="meta-value">🏍️ <?php echo htmlspecialchars($pedido['domiciliario_nombre']); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Acciones de Impresión -->
        <?php if (!$es_acceso_publico): ?>
        <div class="section" style="padding: 15px; display: flex; gap: 15px; justify-content: flex-end; flex-wrap: wrap;">
            <?php if ($pedido['estado'] === 'entregado' && !$pedido['pagado']): ?>
            <a href="registrar_pago.php?pedido_id=<?php echo $pedido['id']; ?>" class="btn" style="background: #48bb78; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: flex; align-items: center; gap: 8px;">
                💳 Registrar Pago
            </a>
            <?php endif; ?>
            
            <?php if (($user_rol === 'mesero' || $user_rol === 'admin') && ($pedido['estado'] === 'en_camino' || $pedido['estado'] === 'preparando')): ?>
            <a href="cambiar_estado_pedido.php?id=<?php echo $pedido['id']; ?>&estado=entregado&redirect=<?php echo urlencode('ver_pedido.php?id=' . $pedido['id']); ?>" class="btn" style="background: #ed8936; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: flex; align-items: center; gap: 8px;" onclick="return confirm('¿Confirmas que has entregado este pedido a la mesa?');">
                ✅ Marcar como Entregado
            </a>
            <?php endif; ?>
            
            <?php if ($pedido['pagado']): ?>
            <a href="ver_comprobante_pago.php?pedido_id=<?php echo $pedido['id']; ?>&print=true" target="_blank" class="btn" style="background: #48bb78; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: flex; align-items: center; gap: 8px;">
                📄 Ver Comprobante de Pago
            </a>
            <?php endif; ?>
            
            <a href="ver_ticket.php?id=<?php echo $pedido['id']; ?>&print=true" target="_blank" class="btn" style="background: #333; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: flex; align-items: center; gap: 8px;">
                🖨️ Ticket Cocina
            </a>
            <a href="ver_factura.php?id=<?php echo $pedido['id']; ?>&print=true" target="_blank" class="btn" style="background: #2b6cb0; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: flex; align-items: center; gap: 8px;">
                📄 Imprimir Factura
            </a>
        </div>
        <?php endif; ?>

        <!-- Información del Cliente -->
        <div class="section">
            <h2>👤 Información del Cliente</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Nombre:</span>
                    <span class="info-value"><?php echo htmlspecialchars($pedido['nombre_cliente']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Teléfono:</span>
                    <span class="info-value">📞 <?php echo htmlspecialchars($pedido['telefono']); ?></span>
                </div>
                <?php if ($pedido['direccion']): ?>
                <div class="info-item">
                    <span class="info-label">Dirección:</span>
                    <span class="info-value">📍 <?php echo htmlspecialchars($pedido['direccion']); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($pedido['notas']): ?>
                <div class="info-item" style="grid-column: 1 / -1;">
                    <span class="info-label">Notas Especiales:</span>
                    <span class="info-value">📝 <?php echo htmlspecialchars($pedido['notas']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Items del Pedido -->
        <div class="section">
            <h2>🍽️ Items del Pedido</h2>
            <table>
                <thead>
                    <tr>
                        <th>Plato</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="item-nombre"><?php echo htmlspecialchars($item['plato_nombre'] ?? $item['nombre_plato'] ?? 'Sin nombre'); ?></td>
                        <td>$<?php echo number_format($item['precio_unitario'] ?? $item['precio'] ?? 0, 2); ?></td>
                        <td><?php echo $item['cantidad']; ?>x</td>
                        <td><strong>$<?php echo number_format(($item['precio_unitario'] ?? $item['precio'] ?? 0) * $item['cantidad'], 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="total-section">
                <div class="total-row total-final">
                    <span>Total:</span>
                    <span>$<?php echo number_format($pedido['total'], 2); ?></span>
                </div>
            </div>
        </div>

        <!-- Timeline -->
        <!-- SECCIÓN DE SEGUIMIENTO EN TIEMPO REAL -->
        <div id="tracking-section" class="section" style="display: none;">
            <h2 style="display: flex; justify-content: space-between; align-items: center;">
                <span>📍 Seguimiento en Vivo</span>
                <span style="font-size: 0.6em; background: #e53e3e; color: white; padding: 4px 10px; border-radius: 12px; animation: pulse 2s infinite;">EN VIVO</span>
            </h2>
            
            <div id="domiciliario-info" style="margin-bottom: 20px; padding: 15px; background: #ebf8ff; border-radius: 8px; border-left: 4px solid #4299e1;">
                <div style="font-weight: bold; color: #2b6cb0; margin-bottom: 5px;">🏍️ Tu pedido está en camino</div>
                <div id="domiciliario-detalle">
                    <!-- Se llena con JS -->
                </div>
            </div>
            
            <div id="map" style="height: 400px; width: 100%; border-radius: 10px; border: 2px solid #e0e0e0; z-index: 1;"></div>
            <div style="margin-top: 10px; font-size: 0.85em; color: #666; text-align: center;">
                El mapa se actualiza automáticamente cada 10 segundos.
            </div>
        </div>

        <?php if ($pedido['hora_salida'] || $pedido['hora_entrega']): ?>
        <div class="section">
            <h2>⏱️ Historial</h2>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-time"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></div>
                    <div class="timeline-event">Pedido creado</div>
                </div>
                <?php if ($pedido['hora_salida']): ?>
                <div class="timeline-item">
                    <div class="timeline-time"><?php echo date('d/m/Y H:i', strtotime($pedido['hora_salida'])); ?></div>
                    <div class="timeline-event">Domiciliario salió a entregar</div>
                </div>
                <?php endif; ?>
                <?php if ($pedido['hora_entrega']): ?>
                <div class="timeline-item">
                    <div class="timeline-time"><?php echo date('d/m/Y H:i', strtotime($pedido['hora_entrega'])); ?></div>
                    <div class="timeline-event">Pedido entregado</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        const PEDIDO_ID = <?php echo $pedido_id; ?>;
        const ESTADO_PEDIDO = '<?php echo $pedido['estado']; ?>';
        const DIRECCION_CLIENTE = '<?php echo addslashes($pedido['direccion'] ?? ''); ?>';
        const CIUDAD_CLIENTE = '<?php echo addslashes($pedido['ciudad_entrega'] ?? 'Tuluá'); ?>';
        
        let map = null;
        let marker = null;
        let destinoMarker = null;
        let routeLine = null;
        let trackingInterval = null;
        let destinoCoords = null;

        document.addEventListener('DOMContentLoaded', function() {
            if (ESTADO_PEDIDO === 'en_camino') {
                iniciarSeguimiento();
            }
        });

        function iniciarSeguimiento() {
            // Mostrar sección
            document.getElementById('tracking-section').style.display = 'block';
            
            // Inicializar mapa (centro default por ahora)
            map = L.map('map').setView([4.08466, -76.19536], 13); // Tuluá default
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            // Geocodificar dirección del cliente
            geocodificarDestino();

            actualizarUbicacion();
            
            // Polling cada 10 segundos
            trackingInterval = setInterval(actualizarUbicacion, 10000);
        }

        async function geocodificarDestino() {
            if (!DIRECCION_CLIENTE) return;
            
            const query = `${DIRECCION_CLIENTE}, ${CIUDAD_CLIENTE}, Colombia`;
            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`;
            
            try {
                const response = await fetch(url);
                const data = await response.json();
                
                if (data && data.length > 0) {
                    destinoCoords = [parseFloat(data[0].lat), parseFloat(data[0].lon)];
                    
                    // Agregar marcador de destino
                    const homeIcon = L.divIcon({
                        html: '<div style="font-size: 28px;">🏠</div>',
                        className: 'marker-home',
                        iconSize: [30, 30],
                        iconAnchor: [15, 30]
                    });
                    
                    destinoMarker = L.marker(destinoCoords, {icon: homeIcon}).addTo(map);
                    destinoMarker.bindPopup('<b>Destino</b><br>' + DIRECCION_CLIENTE);
                }
            } catch (err) {
                console.error('Error geocodificando destino:', err);
            }
        }

        async function trazarRuta(origenLat, origenLng) {
            if (!destinoCoords) return;
            
            const url = `https://router.project-osrm.org/route/v1/driving/${origenLng},${origenLat};${destinoCoords[1]},${destinoCoords[0]}?overview=full&geometries=geojson`;
            
            try {
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                    const route = data.routes[0];
                    const coords = route.geometry.coordinates.map(c => [c[1], c[0]]);
                    
                    // Remover ruta anterior si existe
                    if (routeLine) {
                        map.removeLayer(routeLine);
                    }
                    
                    // Dibujar nueva ruta
                    routeLine = L.polyline(coords, {
                        color: '#4299e1',
                        weight: 4,
                        opacity: 0.7
                    }).addTo(map);
                    
                    // Ajustar vista para mostrar toda la ruta
                    const bounds = L.latLngBounds([origenLat, origenLng], destinoCoords);
                    map.fitBounds(bounds, {padding: [50, 50]});
                }
            } catch (err) {
                console.error('Error trazando ruta:', err);
            }
        }

        function actualizarUbicacion() {
            fetch(`api/obtener_ubicacion_pedido.php?pedido_id=${PEDIDO_ID}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizar info domiciliario
                        if (data.domiciliario) {
                            const domInfo = document.getElementById('domiciliario-detalle');
                            let phoneHtml = data.domiciliario.telefono 
                                ? `<a href="tel:${data.domiciliario.telefono}" style="color: #2b6cb0; text-decoration: none;">📞 ${data.domiciliario.telefono}</a>`
                                : '<span style="color: #666;">Sin teléfono</span>';
                                
                            domInfo.innerHTML = `
                                <div><strong>Domiciliario:</strong> ${data.domiciliario.nombre}</div>
                                <div style="margin-top: 5px;"><strong>Contacto:</strong> ${phoneHtml}</div>
                            `;
                        }

                        // Actualizar mapa si hay tracking activo
                        if (data.tracking_activo && data.ubicacion) {
                            const lat = data.ubicacion.lat;
                            const lng = data.ubicacion.lng;
                            const pos = [lat, lng];

                            if (!marker) {
                                // Icono de moto
                                const motoIcon = L.divIcon({
                                    html: '<div style="font-size: 24px;">🏍️</div>',
                                    className: 'marker-moto',
                                    iconSize: [30, 30],
                                    iconAnchor: [15, 15]
                                });
                                marker = L.marker(pos, {icon: motoIcon}).addTo(map);
                                map.setView(pos, 15);
                                
                                // Trazar ruta inicial
                                trazarRuta(lat, lng);
                            } else {
                                marker.setLatLng(pos);
                                map.panTo(pos);
                                
                                // Actualizar ruta
                                trazarRuta(lat, lng);
                            }
                        } else if (!data.tracking_activo && data.estado === 'en_camino') {
                            // Está en camino pero sin GPS reciente
                            // Podríamos mostrar un mensaje o dejar el último punto
                        } else if (data.estado !== 'en_camino') {
                            // Ya no está en camino (entregado?)
                            clearInterval(trackingInterval);
                            location.reload(); // Recargar para ver nuevo estado
                        }
                    }
                })
                .catch(err => console.error('Error tracking:', err));
        }

        // Estilos extra
        const style = document.createElement('style');
        style.innerHTML = `
            @keyframes pulse {
                0% { opacity: 1; transform: scale(1); }
                50% { opacity: 0.7; transform: scale(1.05); }
                100% { opacity: 1; transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
<?php $conn->close(); ?>
