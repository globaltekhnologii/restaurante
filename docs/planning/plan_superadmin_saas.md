# 🚀 Plan de Implementación: Sistema Multi-Tenant SaaS con Super Admin

**Objetivo**: Convertir el sistema actual en una plataforma SaaS que permita gestionar múltiples restaurantes desde un panel central con capacidad de auto-actualización.

---

## 📋 Arquitectura Propuesta

### Modelo Multi-Tenant
```
┌─────────────────────────────────────┐
│     SUPER ADMIN PANEL               │
│  (superadmin.tudominio.com)         │
│  - Gestión de Restaurantes         │
│  - Auto-actualizaciones             │
│  - Monitoreo Global                 │
└─────────────────────────────────────┘
            │
            ├──────────────┬──────────────┬──────────────┐
            ▼              ▼              ▼              ▼
    ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
    │ Restaurante1 │ │ Restaurante2 │ │ Restaurante3 │
    │ (tenant_1)   │ │ (tenant_2)   │ │ (tenant_3)   │
    └──────────────┘ └──────────────┘ └──────────────┘
```

---

## 🗄️ Cambios en Base de Datos

### Nueva Tabla: `tenants` (Restaurantes)
```sql
CREATE TABLE tenants (
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
);
```

### Nueva Tabla: `super_admins`
```sql
CREATE TABLE super_admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario VARCHAR(100) UNIQUE NOT NULL,
    clave VARCHAR(255) NOT NULL,
    nombre VARCHAR(255),
    email VARCHAR(255),
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Nueva Tabla: `system_updates`
```sql
CREATE TABLE system_updates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    version VARCHAR(20) NOT NULL,
    descripcion TEXT,
    tipo ENUM('critico', 'seguridad', 'feature', 'bugfix'),
    archivo_url VARCHAR(500),
    checksum VARCHAR(64),
    fecha_publicacion DATETIME,
    aplicado TINYINT(1) DEFAULT 0,
    fecha_aplicacion DATETIME
);
```

### Nueva Tabla: `tenant_updates_log`
```sql
CREATE TABLE tenant_updates_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT,
    update_id INT,
    estado ENUM('pendiente', 'aplicando', 'exitoso', 'fallido'),
    log_detalle TEXT,
    fecha_intento DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (update_id) REFERENCES system_updates(id)
);
```

---

## 📁 Estructura de Archivos Propuesta

```
Restaurante/
├── superadmin/                    # NUEVO: Panel Super Admin
│   ├── index.php                  # Dashboard principal
│   ├── login.php                  # Login super admin
│   ├── tenants.php                # Gestión de restaurantes
│   ├── crear_tenant.php           # Wizard creación restaurante
│   ├── editar_tenant.php
│   ├── updates.php                # Gestor de actualizaciones
│   ├── buscar_updates.php         # API: Buscar nuevas versiones
│   ├── aplicar_update.php         # API: Aplicar actualización
│   └── css/
│       └── superadmin.css
│
├── core/                          # NUEVO: Núcleo compartido
│   ├── tenant_manager.php         # Gestión de tenants
│   ├── update_manager.php         # Sistema de actualizaciones
│   ├── version.php                # Versión actual del sistema
│   └── multi_tenant_config.php    # Config multi-tenant
│
├── config.php                     # MODIFICAR: Detectar tenant
├── index.php                      # MODIFICAR: Cargar tenant correcto
└── ... (resto de archivos actuales)
```

---

## 🔧 Componentes a Desarrollar

### 1. Sistema de Identificación de Tenant

**Archivo**: `core/tenant_manager.php`

Funciones principales:
- `detectarTenant()`: Identifica el restaurante por dominio/subdirectorio
- `cargarConfigTenant($tenant_id)`: Carga configuración específica
- `crearNuevoTenant($datos)`: Crea BD y archivos para nuevo restaurante
- `suspenderTenant($tenant_id)`: Desactiva acceso temporalmente
- `eliminarTenant($tenant_id)`: Elimina completamente (con backup)

### 2. Panel Super Admin

**Características**:
- Dashboard con métricas globales (total restaurantes, activos, ingresos)
- CRUD completo de restaurantes
- Asignación de planes y límites
- Monitoreo de uso (pedidos, usuarios, almacenamiento)
- Logs de actividad por tenant

### 3. Sistema de Auto-Actualización

**Archivo**: `core/update_manager.php`

**Flujo de Actualización**:
```
1. Botón "Buscar Actualizaciones" en Super Admin
   ↓
2. Consulta API remota (ej: updates.tuservidor.com/check)
   ↓
3. Compara versión actual vs disponible
   ↓
4. Descarga paquete de actualización (.zip)
   ↓
5. Verifica checksum (seguridad)
   ↓
6. Crea backup automático
   ↓
7. Aplica cambios (archivos + SQL)
   ↓
8. Ejecuta scripts de migración
   ↓
9. Actualiza versión en BD
   ↓
10. Notifica resultado
```

**Funciones clave**:
- `buscarActualizaciones()`: Consulta servidor remoto
- `descargarUpdate($version)`: Descarga paquete
- `verificarIntegridad($archivo, $checksum)`: Valida descarga
- `aplicarUpdate($archivo)`: Extrae y aplica cambios
- `rollback($backup_id)`: Revierte si falla

---

## 🎨 Diseño del Super Admin Panel

### Dashboard Principal
```
┌─────────────────────────────────────────────────┐
│ 🏢 SUPER ADMIN - Sistema Multi-Restaurante     │
├─────────────────────────────────────────────────┤
│                                                 │
│  📊 Estadísticas Globales                       │
│  ┌──────────┬──────────┬──────────┬──────────┐ │
│  │ Total    │ Activos  │ Suspen.  │ Ingresos │ │
│  │   45     │   42     │    3     │ $12,450  │ │
│  └──────────┴──────────┴──────────┴──────────┘ │
│                                                 │
│  🔄 Actualizaciones                             │
│  ┌─────────────────────────────────────────┐   │
│  │ Versión Actual: 2.5.1                   │   │
│  │ [🔍 Buscar Actualizaciones]             │   │
│  └─────────────────────────────────────────┘   │
│                                                 │
│  🏪 Restaurantes Recientes                      │
│  ┌─────────────────────────────────────────┐   │
│  │ • El Sabor      [Activo]   [Editar]     │   │
│  │ • La Cocina     [Activo]   [Editar]     │   │
│  │ • Delicias      [Suspendido] [Activar]  │   │
│  └─────────────────────────────────────────┘   │
│                                                 │
│  [➕ Crear Nuevo Restaurante]                   │
└─────────────────────────────────────────────────┘
```

---

## 🚦 Plan de Implementación por Fases

### Fase 1: Infraestructura Base (Semana 1)
- [x] Crear tablas de BD (`tenants`, `super_admins`, `system_updates`)
- [ ] Desarrollar `core/tenant_manager.php`
- [ ] Modificar `config.php` para detectar tenant
- [ ] Crear login super admin básico

### Fase 2: Panel Super Admin (Semana 2)
- [ ] Dashboard con estadísticas
- [ ] CRUD de restaurantes
- [ ] Wizard de creación de tenant (con BD automática)
- [ ] Sistema de suspensión/activación

### Fase 3: Sistema de Actualizaciones (Semana 3)
- [ ] Desarrollar `core/update_manager.php`
- [ ] Crear endpoint de búsqueda de updates
- [ ] Implementar descarga y verificación
- [ ] Sistema de backup pre-actualización
- [ ] Aplicación automática de cambios

### Fase 4: Características Avanzadas (Semana 4)
- [ ] Límites por plan (usuarios, platos, almacenamiento)
- [ ] Monitoreo de uso en tiempo real
- [ ] Sistema de notificaciones
- [ ] Logs de auditoría
- [ ] Reportes globales

---

## ⚠️ Consideraciones Importantes

### Seguridad
- Super admin debe tener autenticación 2FA
- Actualizaciones deben verificar firma digital
- Backups automáticos antes de cada update
- Logs de todas las acciones críticas

### Performance
- Cada tenant debe tener su propia BD (aislamiento)
- Cache de configuración de tenant
- Índices optimizados en tabla `tenants`

### Escalabilidad
- Preparar para múltiples servidores
- Considerar CDN para archivos estáticos
- Queue system para actualizaciones masivas

---

## 📝 Próximos Pasos

1. **Revisar y aprobar este plan**
2. **Decidir estrategia de actualización**:
   - ¿Servidor propio para distribuir updates?
   - ¿GitHub releases?
   - ¿Sistema custom?
3. **Definir planes y límites** (Básico, Pro, Enterprise)
4. **Comenzar implementación Fase 1**

---

> **Nota**: Este es un proyecto ambicioso que transformará el sistema actual en una plataforma SaaS completa. Requerirá aproximadamente 4 semanas de desarrollo enfocado.
