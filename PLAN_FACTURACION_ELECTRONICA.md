# Implementación: Autocompletado + Facturación Electrónica

## Objetivo
1. **Autocompletado:** Al ingresar teléfono, autocompletar datos del cliente
2. **Documento de Identidad:** Agregar campos para facturación electrónica
3. **Reordenar campos:** Teléfono primero en el formulario

---

## Cambios en Base de Datos

### Tabla `pedidos` - Agregar Campos
```sql
ALTER TABLE pedidos 
ADD COLUMN tipo_documento VARCHAR(10) NULL AFTER telefono,
ADD COLUMN numero_documento VARCHAR(50) NULL AFTER tipo_documento;
```

**Tipos de documento:**
- `CC` - Cédula de Ciudadanía
- `TI` - Tarjeta de Identidad
- `CE` - Cédula de Extranjería
- `PEP` - Permiso Especial de Permanencia
- `Pasaporte` - Pasaporte
- `NIT` - Número de Identificación Tributaria

---

## Componentes a Implementar

### 1. API de Búsqueda
**Archivo:** `api/buscar_cliente_por_telefono.php` (NUEVO)

**Retorna:**
```json
{
  "found": true,
  "data": {
    "nombre": "Juan Pérez",
    "telefono": "3177731338",
    "tipo_documento": "CC",
    "numero_documento": "1234567890",
    "email": "juan@email.com",
    "direccion": "Calle 5 #10-20",
    "ciudad_entrega": "Tuluá"
  }
}
```

---

### 2. Modificar Checkout - Nuevo Orden de Campos

**Orden propuesto:**
1. **Teléfono** ⭐ (Primero - activa autocompletado)
2. **Nombre**
3. **Tipo de Documento** (Select)
4. **Número de Documento**
5. **Email**
6. **Ciudad** (para domicilios)
7. **Dirección** (para domicilios)

---

### 3. Script de Migración
**Archivo:** `setup_facturacion_electronica.php` (NUEVO)

- Crear campos en tabla `pedidos`
- Migrar datos existentes (opcional)
- Verificar integridad

---

## Flujo de Usuario Mejorado

1. Cliente escribe **teléfono**: `3177731338`
2. Sistema busca automáticamente (al salir del campo)
3. **Si encuentra datos:**
   - ✅ Autocompleta: Nombre, Documento, Email, Dirección, Ciudad
   - Muestra: "✅ Bienvenido de nuevo, verifica tus datos"
4. **Si es nuevo:**
   - ℹ️ "Cliente nuevo, completa tus datos"
5. Cliente completa/edita campos
6. Confirma pedido
7. **Todos los datos se guardan** para:
   - Próximo pedido (autocompletado)
   - Facturación electrónica futura

---

## Archivos a Crear/Modificar

### NUEVOS:
1. `api/buscar_cliente_por_telefono.php` - API búsqueda
2. `setup_facturacion_electronica.php` - Migración BD

### MODIFICAR:
3. `checkout.php` - Reordenar campos + agregar documento + JS autocompletado
4. `procesar_pedido.php` - Guardar tipo_documento y numero_documento

---

## Validaciones

- Teléfono: mínimo 10 dígitos
- Documento: requerido para facturación
- Número documento: alfanumérico, según tipo
- Email: formato válido
- Dirección: requerida solo para domicilios

---

## Preparación para Facturación Electrónica

**Datos que se guardarán:**
- ✅ Tipo de documento
- ✅ Número de documento
- ✅ Nombre completo
- ✅ Teléfono
- ✅ Email
- ✅ Dirección
- ✅ Ciudad

**Próximos pasos futuros (no ahora):**
- Integración con DIAN
- Generación de PDF de factura
- Envío automático por email

---

## Orden de Implementación

1. ✅ Crear script de migración BD
2. ✅ Ejecutar migración
3. ✅ Crear API de búsqueda
4. ✅ Modificar checkout (HTML + campos)
5. ✅ Agregar JavaScript autocompletado
6. ✅ Modificar procesar_pedido.php
7. ✅ Probar flujo completo

---

## ¿Proceder con implementación?
