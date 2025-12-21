<?php
session_start();
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin'], 'login.php');

require_once 'config.php';
require_once 'includes/tenant_context.php'; // NUEVO: Soporte multi-tenencia
require_once 'includes/info_negocio.php';
$conn = getDatabaseConnection();

// Obtener tenant_id del usuario actual
$tenant_id = getCurrentTenantId();

// Obtener estadísticas filtradas por tenant
$total_ingredientes = $conn->query("SELECT COUNT(*) as total FROM ingredientes WHERE tenant_id = $tenant_id AND activo = 1")->fetch_assoc()['total'];
$criticos = $conn->query("SELECT COUNT(*) as total FROM ingredientes WHERE tenant_id = $tenant_id AND activo = 1 AND stock_actual <= stock_minimo")->fetch_assoc()['total'];
$agotados = $conn->query("SELECT COUNT(*) as total FROM ingredientes WHERE tenant_id = $tenant_id AND activo = 1 AND stock_actual = 0")->fetch_assoc()['total'];

// Obtener ingredientes con stock bajo filtrados por tenant
$sql_alertas = "SELECT * FROM ingredientes WHERE tenant_id = $tenant_id AND activo = 1 AND stock_actual <= stock_minimo ORDER BY stock_actual ASC LIMIT 10";
$alertas = $conn->query($sql_alertas);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/themes.css">
    <link rel="stylesheet" href="css/animations.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/admin-modern.css">
    <title>Sistema de Inventario - <?php echo htmlspecialchars($info_negocio['nombre_restaurante']); ?></title>
</head>
<body>
    <!-- Navbar -->
    <div class="admin-navbar">
        <h1>📦 Sistema de Inventario</h1>
        <div class="navbar-actions">
            <a href="admin.php">🏠 Inicio</a>
            <a href="ingredientes.php">📦 Ingredientes</a>
            <a href="movimientos.php">📝 Movimientos</a>
            <a href="recetas.php">🍽️ Recetas</a>
            <a href="proveedores.php">🏢 Proveedores</a>
            <div class="theme-switcher-container"></div>
            <a href="logout.php">🚪 Salir</a>
        </div>
    </div>

    <div class="admin-container">
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-content">
                    <h3>Total Ingredientes</h3>
                    <div class="number"><?php echo $total_ingredientes; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-content">
                    <h3>Stock Crítico</h3>
                    <div class="number"><?php echo $criticos; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🚨</div>
                <div class="stat-content">
                    <h3>Agotados</h3>
                    <div class="number"><?php echo $agotados; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <h3>Stock Normal</h3>
                    <div class="number"><?php echo $total_ingredientes - $criticos; ?></div>
                </div>
            </div>
        </div>

        <!-- Alertas de Stock -->
        <?php if ($alertas->num_rows > 0): ?>
        <div class="form-section">
            <h2>🚨 Alertas de Stock Bajo</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Ingrediente</th>
                            <th>Categoría</th>
                            <th>Stock Actual</th>
                            <th>Stock Mínimo</th>
                            <th>Unidad</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($alerta = $alertas->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($alerta['nombre']); ?></strong></td>
                            <td><?php echo htmlspecialchars($alerta['categoria']); ?></td>
                            <td style="color: <?php echo $alerta['stock_actual'] == 0 ? '#ef4444' : '#f59e0b'; ?>; font-weight: 600;">
                                <?php echo number_format($alerta['stock_actual'], 2); ?>
                            </td>
                            <td><?php echo number_format($alerta['stock_minimo'], 2); ?></td>
                            <td><?php echo $alerta['unidad_medida']; ?></td>
                            <td>
                                <?php if ($alerta['stock_actual'] == 0): ?>
                                    <span style="background: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 9999px; font-size: 0.8125rem; font-weight: 500;">
                                        🚨 Agotado
                                    </span>
                                <?php else: ?>
                                    <span style="background: #fef3c7; color: #78350f; padding: 4px 12px; border-radius: 9999px; font-size: 0.8125rem; font-weight: 500;">
                                        ⚠️ Crítico
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="movimientos.php?ingrediente=<?php echo $alerta['id']; ?>&tipo=entrada" class="btn-small btn-edit">
                                    + Agregar Stock
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Acciones Rápidas -->
        <div class="form-section">
            <h2>⚡ Acciones Rápidas</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                <a href="ingredientes.php" style="text-decoration: none;">
                    <div style="background: #dbeafe; padding: 24px; border-radius: 16px; text-align: center; transition: all 0.2s ease; cursor: pointer;" 
                         onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'"
                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                        <div style="font-size: 3rem; margin-bottom: 8px;">📦</div>
                        <h3 style="color: #1e40af; margin-bottom: 4px;">Gestionar Ingredientes</h3>
                        <p style="color: #6b7280; font-size: 0.875rem;">Ver, agregar y editar ingredientes</p>
                    </div>
                </a>
                
                <a href="movimientos.php" style="text-decoration: none;">
                    <div style="background: #fef3c7; padding: 24px; border-radius: 16px; text-align: center; transition: all 0.2s ease; cursor: pointer;"
                         onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'"
                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                        <div style="font-size: 3rem; margin-bottom: 8px;">📝</div>
                        <h3 style="color: #78350f; margin-bottom: 4px;">Registrar Movimiento</h3>
                        <p style="color: #6b7280; font-size: 0.875rem;">Entradas, salidas y ajustes</p>
                    </div>
                </a>
                
                <a href="recetas.php" style="text-decoration: none;">
                    <div style="background: #d1fae5; padding: 24px; border-radius: 16px; text-align: center; transition: all 0.2s ease; cursor: pointer;"
                         onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'"
                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                        <div style="font-size: 3rem; margin-bottom: 8px;">🍽️</div>
                        <h3 style="color: #065f46; margin-bottom: 4px;">Gestionar Recetas</h3>
                        <p style="color: #6b7280; font-size: 0.875rem;">Ingredientes por plato</p>
                    </div>
                </a>
                
                <a href="proveedores.php" style="text-decoration: none;">
                    <div style="background: #fce7f3; padding: 24px; border-radius: 16px; text-align: center; transition: all 0.2s ease; cursor: pointer;"
                         onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'"
                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                        <div style="font-size: 3rem; margin-bottom: 8px;">🏢</div>
                        <h3 style="color: #831843; margin-bottom: 4px;">Gestionar Proveedores</h3>
                        <p style="color: #6b7280; font-size: 0.875rem;">Contactos y productos</p>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Theme Manager -->
    <script src="js/theme-manager.js"></script>
</body>
</html>
<?php $conn->close(); ?>
