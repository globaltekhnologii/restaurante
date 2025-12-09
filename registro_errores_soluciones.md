# Registro de Errores y Soluciones

Este documento registra todos los errores encontrados y corregidos en el sistema del restaurante.

---

## Error #009 - Filtros de Reportes No Funcionan
**Fecha:** 2025-12-08  
**Módulo:** Reportes y Estadísticas  
**Estado:** ✅ Resuelto

### Problema
Al hacer clic en los filtros "Hoy", "Esta Semana", "Este Mes" aparecía el error:
```
Error al cargar los reportes. Por favor, intenta nuevamente.
```

### Causa Raíz
Bug en JavaScript: la función `setQuickFilter()` usaba `event.target` sin que `event` estuviera definido como parámetro.

### Archivos Modificados
1. `js/reportes.js` - Corregido manejo de eventos
2. `reportes.php` - Actualizados onclick handlers  
3. `api/get_ventas_periodo.php` - Agregado COALESCE
4. `api/get_productos_vendidos.php` - Agregado COALESCE

### Solución
- Agregado parámetro `e` a función `setQuickFilter(e, filter)`
- Actualizado todos los onclick para pasar `event`
- Robustecido backend con COALESCE para manejar NULL

### Documentación Detallada
Ver: `CORRECCION_009_REPORTES_2025-12-08.md`

---

## Resumen General
- **Total errores corregidos:** 9
- **Total archivos modificados:** 18 (14 de ayer + 4 de hoy)
- **Última actualización:** 2025-12-08
