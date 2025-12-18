<?php
// scripts/setup_saas_db.php
require_once __DIR__ . '/../config.php';

echo "🚀 Iniciando configuración de base de datos SaaS...\n";

$conn = getDatabaseConnection();

// 1. Tabla de Tenants
$sql_tenants = "CREATE TABLE IF NOT EXISTS tenants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    dominio VARCHAR(255),
    db_name VARCHAR(100) NOT NULL,
    estado ENUM('activo', 'suspendido', 'inactivo') DEFAULT 'activo',
    plan ENUM('basico', 'pro', 'enterprise') DEFAULT 'basico',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATE,
    max_usuarios INT DEFAULT 5,
    max_platos INT DEFAULT 50,
    features JSON,
    metadata JSON
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_tenants)) {
    echo "✅ Tabla 'tenants' creada o verificada.\n";
} else {
    echo "❌ Error creando 'tenants': " . $conn->error . "\n";
}

// 2. Tabla Super Admins
$sql_super_admins = "CREATE TABLE IF NOT EXISTS super_admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario VARCHAR(100) UNIQUE NOT NULL,
    clave VARCHAR(255) NOT NULL,
    nombre VARCHAR(255),
    email VARCHAR(255),
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_super_admins)) {
    echo "✅ Tabla 'super_admins' creada o verificada.\n";
} else {
    echo "❌ Error creando 'super_admins': " . $conn->error . "\n";
}

// 3. Tabla System Updates
$sql_updates = "CREATE TABLE IF NOT EXISTS system_updates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    version VARCHAR(20) NOT NULL,
    descripcion TEXT,
    tipo ENUM('critico', 'seguridad', 'feature', 'bugfix'),
    archivo_url VARCHAR(500),
    checksum VARCHAR(64),
    fecha_publicacion DATETIME,
    aplicado TINYINT(1) DEFAULT 0,
    fecha_aplicacion DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_updates)) {
    echo "✅ Tabla 'system_updates' creada o verificada.\n";
} else {
    echo "❌ Error creando 'system_updates': " . $conn->error . "\n";
}

// 4. Tabla Logs de Updates
$sql_logs = "CREATE TABLE IF NOT EXISTS tenant_updates_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT,
    update_id INT,
    estado ENUM('pendiente', 'aplicando', 'exitoso', 'fallido'),
    log_detalle TEXT,
    fecha_intento DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (update_id) REFERENCES system_updates(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_logs)) {
    echo "✅ Tabla 'tenant_updates_log' creada o verificada.\n";
} else {
    echo "❌ Error creando 'tenant_updates_log': " . $conn->error . "\n";
}

// Crear usuario super admin por defecto si no hay
$check_admin = $conn->query("SELECT id FROM super_admins LIMIT 1");
if ($check_admin->num_rows === 0) {
    // Usuario: admin_saas / Pass: Admin123!
    $pass_hash = password_hash('Admin123!', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO super_admins (usuario, clave, nombre, email) VALUES ('admin_saas', '$pass_hash', 'Super Administrador', 'admin@saas.com')");
    echo "👤 Usuario super admin creado por defecto (User: admin_saas / Pass: Admin123!).\n";
}

$conn->close();
echo "\n🏁 Configuración de BD finalizada.\n";
?>
