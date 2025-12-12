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
        <div class="user-info" style="display: flex; align-items: center; gap: 15px;">
            <span><?php echo htmlspecialchars($current_admin['name'] ?? 'Admin'); ?></span>
            <div class="dropdown" style="position: relative; display: inline-block;">
                <button class="dropbtn" style="background: none; border: none; cursor: pointer; color: #6b7280;">⬇️</button>
                <div class="dropdown-content" style="display: none; position: absolute; right: 0; background-color: #f9f9f9; min-width: 160px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 1;">
                    <a href="profile.php" style="color: black; padding: 12px 16px; text-decoration: none; display: block;">👤 Mi Perfil</a>
                    <a href="logout.php" style="color: black; padding: 12px 16px; text-decoration: none; display: block;">🚪 Salir</a>
                </div>
            </div>
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
