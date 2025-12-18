# Prueba de Pago Bold - Guía Paso a Paso

## 🧪 Tarjetas de Prueba Bold

### Tarjeta Aprobada
- **Número:** 4242 4242 4242 4242
- **CVV:** 123
- **Fecha:** Cualquier fecha futura
- **Resultado:** Pago aprobado ✅

### Tarjeta Rechazada
- **Número:** 4000 0000 0000 0002
- **CVV:** 123
- **Fecha:** Cualquier fecha futura
- **Resultado:** Pago rechazado ❌

---

## 📋 Pasos para Probar

### 1. Hacer un Pedido
1. Ir a http://localhost/Restaurante/index.php
2. Agregar productos al carrito
3. Ir al checkout

### 2. Completar Datos
- **Teléfono:** 3177731338 (o cualquiera)
- **Nombre:** Tu nombre
- **Tipo Doc:** CC
- **Número Doc:** 1234567890
- **Email:** test@email.com
- **Ciudad:** Tuluá
- **Dirección:** Calle 5 #10-20

### 3. Seleccionar Método de Pago
- ✅ Seleccionar: **💳 Pagar con Tarjeta (Bold)**

### 4. Confirmar Pedido
- Clic en "Confirmar Pedido"
- Deberías ser redirigido a Bold

### 5. Pagar en Bold
- Usar tarjeta de prueba: **4242 4242 4242 4242**
- CVV: 123
- Fecha: 12/25
- Completar pago

### 6. Verificar Confirmación
- Deberías volver a la página de confirmación
- Ver estado del pago

---

## 🔍 Qué Verificar

1. ✅ Redirección a Bold funciona
2. ✅ Pago se procesa correctamente
3. ✅ Webhook actualiza el estado
4. ✅ Página de confirmación muestra datos
5. ✅ Pedido queda marcado como "pagado"

---

## 📝 Logs

Revisa el archivo de logs del webhook:
`logs/bold_webhook.log`

Ahí verás las notificaciones de Bold.
