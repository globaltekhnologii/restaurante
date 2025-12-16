# 📱 Cómo Funciona el Autocompletado - Guía Visual

## 🎯 Comportamiento Esperado

### Escenario 1: Cliente Recurrente

**Paso 1:** Cliente escribe su teléfono
```
Campo: Teléfono
Valor: 3177731338
```

**Paso 2:** Automático (1-2 segundos después)
- 🔍 Aparece mensaje: "Buscando..."
- Sistema consulta la base de datos

**Paso 3:** Si encuentra datos previos
- ✅ Mensaje: "¡Bienvenido de vuelta! Verifica tus datos"
- **Todos los campos se llenan automáticamente:**
  - Nombre: "Juan Pérez"
  - Email: "juan@email.com"
  - Tipo Documento: "CC"
  - Número Documento: "1234567890"
  - Ciudad: "Tuluá"
  - Dirección: "Calle 5 #10-20"
- 🟢 **Campos se resaltan en verde** por 2 segundos

### Escenario 2: Cliente Nuevo

**Paso 1:** Cliente escribe un teléfono nuevo
```
Campo: Teléfono
Valor: 3001234567
```

**Paso 2:** Automático
- 🔍 "Buscando..."
- No encuentra datos

**Paso 3:** Mensaje informativo
- ℹ️ "Cliente nuevo, completa tus datos"
- Campos permanecen vacíos
- Cliente debe llenarlos manualmente

---

## 🔧 Requisitos Técnicos

Para que funcione, necesitas:

1. ✅ **Campos en BD:** tipo_documento, numero_documento, ciudad_entrega
2. ✅ **API funcionando:** api/buscar_cliente_por_telefono.php
3. ✅ **JavaScript cargado:** checkout.php con event listeners
4. ✅ **Al menos 1 pedido previo** para probar con cliente recurrente

---

## 🧪 Cómo Probarlo

### Opción A: Con Datos Existentes
Si ya tienes pedidos en la BD:
1. Ir al checkout
2. Escribir un teléfono de un pedido anterior
3. Observar el autocompletado

### Opción B: Crear Datos de Prueba
1. Hacer un pedido completo con todos los datos
2. Volver al checkout
3. Escribir el mismo teléfono
4. Ver el autocompletado en acción

---

## ❓ Solución de Problemas

### No autocompleta
- ✅ Verificar que los campos existen en BD
- ✅ Abrir consola del navegador (F12) para ver errores
- ✅ Verificar que hay pedidos previos con ese teléfono

### Error en consola
- Revisar que la API responde: `api/buscar_cliente_por_telefono.php?telefono=3177731338`
- Verificar permisos de archivos

### Campos no se resaltan
- Es normal si no hay datos previos
- Solo se resaltan cuando encuentra un cliente existente
