<?php
// Obtener el nombre del archivo actual para marcar activo el enlace
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <div class="navbar-brand">👑 Super Admin</div>
    <div class="navbar-menu">
        <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a>
        <a href="tenants.php" class="<?php echo ($current_page == 'tenants.php') ? 'active' : ''; ?>">Restaurantes</a>
        <a href="subscriptions.php" class="<?php echo ($current_page == 'subscriptions.php') ? 'active' : ''; ?>">Suscripciones</a>
        <a href="monitoring.php" class="<?php echo ($current_page == 'monitoring.php') ? 'active' : ''; ?>">📊 Monitoreo</a>
        <a href="notifications.php" class="<?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">🔔 Notificaciones</a>
        <a href="audit_logs.php" class="<?php echo ($current_page == 'audit_logs.php') ? 'active' : ''; ?>">📝 Auditoría</a>
        <a href="updates.php" class="<?php echo ($current_page == 'updates.php') ? 'active' : ''; ?>">🔄 Actualizaciones</a>
        
        <div style="display: flex; align-items: center; gap: 15px; margin-left: 20px; padding-left: 20px; border-left: 2px solid #e5e7eb;">
            <span style="color: #6b7280;">👤 <?php echo htmlspecialchars($current_admin['name'] ?? 'Admin'); ?></span>
            <a href="logout.php" style="background: #ef4444; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: background 0.2s;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                🚪 Cerrar Sesión
            </a>
        </div>
    </div>
</nav>

<style>
/* Estilos básicos para el dropdown (pueden ser movidos a style.css global si existiera uno centralizado) */
.navbar {
    background: white;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.navbar-brand {
    font-size: 1.5rem;
    font-weight: bold;
    color: #3b82f6;
}

.navbar-menu {
    display: flex;
    gap: 2rem;
    align-items: center;
}

.navbar-menu a {
    text-decoration: none;
    color: #6b7280;
    font-weight: 500;
    transition: color 0.2s;
}

.navbar-menu a:hover, .navbar-menu a.active {
    color: #3b82f6;
}

.dropdown:hover .dropdown-content {display: block;}
.dropdown-content a:hover {background-color: #f1f1f1;}
</style>
