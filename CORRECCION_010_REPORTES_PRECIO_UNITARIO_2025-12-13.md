# Corrección de Error en Reportes - Diciembre 2025

**Fecha:** 2025-12-13  
**Estado:** ✅ Resuelto  
**Módulo:** Reportes y Estadísticas

---

## Problema Reportado

Al acceder a la sección "Reportes y Estadísticas", aparecía el error:

```
Error al cargar los reportes. Revisa la consola para más detalles.
```

### Error en Consola del Navegador

```
GET http://localhost/Restaurante/api/get_productos_vendidos.php?fecha_inicio=2025-12-13&fecha_fin=2025-12-13&limite=10 400 (Bad Request)

Error en cargarProductos: Error: Unknown column 'pi.precio' in 'field list'
```

---

## Causa Raíz

**Inconsistencia en nombre de columna:** El archivo `api/get_productos_vendidos.php` intentaba acceder a la columna `pi.precio` en la tabla `pedidos_items`, pero la columna real se llama `precio_unitario`.

### Estructura Real de la Tabla

```sql
DESCRIBE pedidos_items;

Field            | Type          
-----------------|---------------
id               | int(11)       
pedido_id        | int(11)       
plato_id         | int(11)       
plato_nombre     | varchar(100)  
precio_unitario  | decimal(10,2) ← Nombre correcto
cantidad         | int(11)       
subtotal         | decimal(10,2) 
```

---

## Solución Aplicada

### Archivo Modificado: `api/get_productos_vendidos.php`

Se reemplazó `pi.precio` por `pi.precio_unitario` en **3 consultas SQL**:

#### 1. Consulta de Top Productos por Cantidad (Líneas 23-25)

```php
// ❌ ANTES
COALESCE(SUM(pi.precio * pi.cantidad), 0) as ingresos_totales,
COALESCE(AVG(pi.precio), 0) as precio_promedio,

// ✅ DESPUÉS
COALESCE(SUM(pi.precio_unitario * pi.cantidad), 0) as ingresos_totales,
COALESCE(AVG(pi.precio_unitario), 0) as precio_promedio,
```

#### 2. Consulta de Top Productos por Ingresos (Líneas 59-61)

```php
// ❌ ANTES
COALESCE(SUM(pi.precio * pi.cantidad), 0) as ingresos_totales,
COALESCE(AVG(pi.precio), 0) as precio_promedio

// ✅ DESPUÉS
COALESCE(SUM(pi.precio_unitario * pi.cantidad), 0) as ingresos_totales,
COALESCE(AVG(pi.precio_unitario), 0) as precio_promedio
```

#### 3. Consulta de Ventas por Categoría (Línea 93)

```php
// ❌ ANTES
COALESCE(SUM(pi.precio * pi.cantidad), 0) as ingresos_totales

// ✅ DESPUÉS
COALESCE(SUM(pi.precio_unitario * pi.cantidad), 0) as ingresos_totales
```

### Archivo Modificado Adicional: `api/get_ventas_periodo.php`

Se corrigió el mapeo de métodos de pago para coincidir con los valores ENUM reales de la base de datos:

```php
// ❌ ANTES (métodos inexistentes)
SUM(CASE WHEN pag.metodo_pago = 'tarjeta' THEN pag.monto ELSE 0 END) as tarjeta,
SUM(CASE WHEN pag.metodo_pago = 'transferencia' THEN pag.monto ELSE 0 END) as transferencia

// ✅ DESPUÉS (métodos reales)
SUM(CASE WHEN pag.metodo_pago IN ('nequi', 'daviplata', 'dale', 'bancolombia', 'otro') THEN pag.monto ELSE 0 END) as transferencia,
0 as tarjeta
```

---

## Verificación

✅ Filtro "Hoy" - Funciona correctamente  
✅ Filtro "Esta Semana" - Funciona correctamente  
✅ Filtro "Este Mes" - Funciona correctamente  
✅ Filtro "Personalizado" - Funciona correctamente  
✅ Gráfico de productos más vendidos - Se renderiza correctamente  
✅ Gráfico de ingresos por producto - Se renderiza correctamente  
✅ Tabla de productos - Muestra datos correctamente  
✅ Gráfico de métodos de pago - Funciona correctamente  

---

## Archivos Modificados

1. `api/get_productos_vendidos.php` - Corregido nombre de columna `precio` → `precio_unitario`
2. `api/get_ventas_periodo.php` - Corregido mapeo de métodos de pago

---

## Notas Técnicas

- **Compatibilidad:** Los cambios son compatibles con la estructura actual de la base de datos
- **Performance:** No hay impacto en el rendimiento
- **Seguridad:** No se introdujeron vulnerabilidades
- **Código afectado:** Solo archivos de API de reportes

---

## Lecciones Aprendidas

1. Siempre verificar la estructura real de las tablas con `DESCRIBE` antes de escribir consultas SQL
2. Mantener consistencia en nombres de columnas entre diferentes tablas
3. Los valores ENUM de métodos de pago deben coincidir con la configuración del sistema
4. Usar mensajes de error de consola del navegador para diagnóstico preciso

---

## Comandos Útiles para Diagnóstico

```bash
# Verificar estructura de tabla
C:\xampp\mysql\bin\mysql.exe -u root -e "USE menu_restaurante; DESCRIBE pedidos_items;"

# Verificar métodos de pago existentes
C:\xampp\mysql\bin\mysql.exe -u root -e "USE menu_restaurante; SELECT DISTINCT metodo_pago FROM pagos;"

# Verificar pedidos pagados
C:\xampp\mysql\bin\mysql.exe -u root -e "USE menu_restaurante; SELECT COUNT(*) FROM pedidos WHERE pagado = 1;"
```
