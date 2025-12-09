# Documentación Técnica: Sistema de Domicilios GPS

**Estado:** ✅ Completado y Verificado  
**Fecha:** 8 de Diciembre, 2025

---

## 🚀 Resumen del Sistema

El sistema permite calcular automáticamente el costo del domicilio basándose en la distancia real entre el restaurante y el cliente. Utiliza servicios gratuitos de OpenStreetMap para geocodificación y mapas.

### Componentes Principales

1.  **Motor de Cálculo (Backend)**
    *   `geocoding_service.php`: Conecta con la API de Nominatim.
    *   `distance_calculator.php`: Implementa fórmula Haversine para distancias precisas.
    *   `api/calcular_costo_domicilio.php`: Endpoint que conecta el frontend con el motor de cálculo.

2.  **Interfaz de Cliente (Checkout)**
    *   Detección automática de cambios en dirección.
    *   **Campo "Ciudad"**: Agregado para resolver ambigüedades en direcciones (Default: Tuluá).
    *   Feedback visual inmediato de distancia y costo.

3.  **Panel Administrativo**
    *   Ubicación: `admin_configuracion_domicilios.php`
    *   **Mapa Interactivo**: Permite al dueño arrastrar un marcador para fijar la ubicación exacta del restaurante.
    *   Configuración flexible: Tarifa Base, Costo por Km, Distancia Máxima.

### Base de Datos

Se agregaron nuevas tablas y campos mediante `setup_delivery_gps.php`:

*   **Tabla `configuracion_domicilios`**: Almacena las reglas de cobro.
*   **Tabla `pedidos`**: Nuevas columnas `latitud_cliente`, `longitud_cliente`, `distancia_km`, `costo_domicilio`.
*   **Tabla `configuracion_sistema`**: Nuevas columnas para latitud/longitud del restaurante.

---

## 🛠️ Guía de Uso y Mantenimiento

### Configuración Inicial (Admin)
1.  Ingresar a "🗺️ Domicilios GPS" en el panel.
2.  Usar el mapa para ubicar el restaurante o ingresar coordenadas manualmente.
3.  Definir tarifas (ej. Base $5,000 + $1,000/km).

### Flujo del Cliente
1.  Selecciona "Domicilio" en el checkout.
2.  Verifica/Cambia la ciudad (ej. Tuluá).
3.  Ingresa dirección.
4.  El sistema muestra costo automáticamente.

### Solución de Problemas Comunes

*   **"Distancia 590km"**: Ocurre si la geocodificación falla o confunde la ciudad. **Solución:** Se implementó el campo "Ciudad" obligatorio para dar contexto al GPS.
*   **Error al Confirmar**: Si aparece error de base de datos, verificar logs. **Solución:** Se corrigió el mapeo de tipos en `procesar_pedido.php`.

---

## 📦 Archivos del Proyecto

| Archivo | Descripción |
|---------|-------------|
| `api/calcular_costo_domicilio.php` | API AJAX para frontend |
| `includes/geocoding_service.php` | Servicio Nominatim |
| `includes/distance_calculator.php` | Servicio Haversine |
| `admin_configuracion_domicilios.php` | Panel con mapa Leaflet |
| `checkout.php` | Interfaz de compra mejorada |
| `procesar_pedido.php` | Guardado de datos y validación |

---

## 🔧 Scripts de Utilidad (Eliminar en Producción)

*   `guardar_gps_temp.php`
*   `configurar_tarifas_temp.php`
*   `verificar_horario.php`
