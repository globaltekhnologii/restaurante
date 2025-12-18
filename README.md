# 🍽️ Sistema de Gestión para Restaurantes v2.5 (Seguro & Optimizado)

Plataforma integral para administración de restaurantes, pedidos en línea y gestión de inventario. Actualizada con estándares modernos de seguridad y optimización.

## 📅 Historial de Versiones (Cronológico)

### v2.5 - Estabilización y Seguridad (Diciembre 2025)
**Estado Actual: ✅ Estable**
- **Seguridad Crítica:**
    - Implementación de protección Anti-CSRF global (`includes/csrf_helper.php`).
    - Sanitización estricta de entradas (`includes/sanitize_helper.php`).
    - Configuración segura de sesiones (HttpOnly, SameSite).
    - Eliminación de deuda técnica y archivos de debug inseguros.
- **Correcciones de Bugs:**
    - Solucionado error "Headers already sent" en login y config.
    - Solucionado error `deprecated htmlspecialchars` con valores nulos.
    - Validación de tokens en todos los formularios administrativos.
- **Infraestructura:**
    - Sistema de Backups Automáticos (`scripts/backup_system.php`).
    - Endpoint de Health Check (`health_check.php`).
    - Visor de Logs Administrativo.
    - Framework de Testing Ligero (Unit & Integration tests).

### v2.1 - Optimización de Rendimiento
- **Cache de Sesión:** Reducción de consultas SQL almacenando configuración en sesión.
- **Optimización SQL:** Nuevos índices en tablas `platos` y `pedidos`.
- **Browser Caching:** Configuración `.htaccess` para activos estáticos.

### v2.0 - Módulo de Inventario Avanzado
- Gestión de stock en tiempo real.
- Recetas y cálculo de costos.
- Gestión de proveedores.

---

## 🚀 Instalación y Despliegue

### Requisitos Previa
- PHP 8.1 o superior
- MySQL / MariaDB
- Apache (con mod_rewrite)

### Configuración Local
1. Clonar el repositorio.
2. Importar `database_inventario.sql` (Estructura base).
3. Configurar `config.php` según el entorno (`LOCAL`, `AWS`, `GCP`).
4. Asegurar permisos de escritura en `backups/` y `logs/`.

### Testing
Ejecutar la suite de pruebas automatizada:
```bash
php tests/run_tests.php
```

## 📚 Documentación Técnica
Documentación detallada disponible en la carpeta `docs/`:
- [Plan de Implementación SaaS](docs/planning/plan_superadmin_saas.md)
- [Reporte de Seguridad y Estabilización](docs/security_report_2025.md)
- [Plan de Implementación de Seguridad](docs/planning/implementation_plan_v2.md)

## 🔮 Roadmap: SaaS Multi-Tenant (Próximamente)
Estamos trabajando en la transformación a arquitectura Multi-Tenant:
- Panel Super Admin centralizado.
- Bases de datos aisladas por restaurante.
- Sistema de Auto-Actualización.

---
© 2025 Global Tekhno Logii
