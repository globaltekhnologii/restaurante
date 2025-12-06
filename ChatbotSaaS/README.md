# 🤖 SaaS Chatbot para Restaurantes

Sistema Multi-inquilino (SaaS) de Chatbot con IA para restaurantes pequeños.

## 📋 Características

✅ **Multi-tenant**: Múltiples restaurantes en una sola instalación  
✅ **IA Conversacional**: Soporte para Anthropic Claude y OpenAI GPT  
✅ **Widget Embebible**: JavaScript puro, sin dependencias  
✅ **Personalizable**: Colores, nombre, mensajes de bienvenida  
✅ **Gestión de Menú**: Cada restaurante gestiona su propio menú  
✅ **Historial de Conversaciones**: Todas las interacciones se guardan  

## 🚀 Instalación Rápida

### 1. Ejecutar Setup de Base de Datos

Abre en tu navegador:
```
http://localhost/Restaurante/ChatbotSaaS/setup_saas_db.php
```

Esto creará:
- 5 tablas necesarias
- Un tenant de prueba
- Menú de ejemplo

### 2. Credenciales de Prueba

```
Email: demo@restaurante.com
Password: demo123
```

### 3. Configurar API Key

1. Ve al panel admin (próximamente)
2. Configura tu API Key de:
   - **Anthropic**: https://console.anthropic.com
   - **OpenAI**: https://platform.openai.com

### 4. Probar el Widget

Abre:
```
http://localhost/Restaurante/ChatbotSaaS/demo/test_landing.html
```

## 📁 Estructura del Proyecto

```
ChatbotSaaS/
├── setup_saas_db.php          # Script de instalación (ejecutar 1 vez)
├── backend/
│   ├── config.php              # Configuración y helpers
│   └── api/
│       └── chat_handler.php    # API para chat con IA
├── widget/
│   └── chatbot-widget.js       # Widget embebible (Vanilla JS)
├── demo/
│   └── test_landing.html       # Página de prueba
└── admin/                      # Panel administrativo (próximamente)
```

## 🗄️ Base de Datos

### Tablas Creadas

1. **saas_tenants**: Restaurantes clientes
2. **saas_chatbot_config**: Configuración de cada chatbot
3. **saas_menu_items**: Menú de cada restaurante
4. **saas_conversations**: Conversaciones
5. **saas_messages**: Mensajes individuales

## 🎨 Integrar el Widget en tu Sitio

Agrega este código antes del `</body>`:

```html
<!-- Configuración -->
<script>
  window.chatbotConfig = {
    tenantId: 1,                    // Tu ID de tenant
    primaryColor: '#f97316',        // Color principal
    chatbotName: 'MiBot',          // Nombre del bot
    welcomeMessage: '¡Hola! 👋'    // Mensaje de bienvenida
  };
</script>

<!-- Widget -->
<script src="http://localhost/Restaurante/ChatbotSaaS/widget/chatbot-widget.js"></script>
```

## 🔧 Configuración Avanzada

### Cambiar Proveedor de IA

En la tabla `saas_chatbot_config`:
- `ai_provider`: `'anthropic'` o `'openai'`
- `api_key`: Tu clave API

### Personalizar Colores

Modifica `primaryColor` en la configuración del widget.

### Agregar Items al Menú

Inserta en `saas_menu_items`:
```sql
INSERT INTO saas_menu_items (tenant_id, name, category, price, description)
VALUES (1, 'Pizza Napolitana', 'Pizzas', 28000, 'Tomate, mozzarella, albahaca');
```

## 🧪 Testing

### Probar API Directamente

```bash
curl -X POST http://localhost/Restaurante/ChatbotSaaS/backend/api/chat_handler.php \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 1,
    "session_id": "test_123",
    "message": "Hola, quiero una pizza"
  }'
```

## 📊 Próximas Funcionalidades

- [ ] Panel administrativo completo
- [ ] Gestión de tenants (crear/editar/eliminar)
- [ ] Analíticas y reportes
- [ ] Integración con WhatsApp Business
- [ ] Sistema de suscripciones/pagos
- [ ] Exportar conversaciones

## 🐛 Troubleshooting

### El widget no aparece
- Verifica que `tenantId` sea correcto
- Revisa la consola del navegador (F12)
- Confirma que ejecutaste `setup_saas_db.php`

### Error "API Key no configurada"
- Actualiza `saas_chatbot_config.api_key` en la BD
- Verifica que la API key sea válida

### El bot no responde
- Revisa que tu API key tenga créditos
- Verifica la conexión a internet
- Revisa los logs de PHP (`error_log`)

## 💡 Diferencias con el Código Original

| Original (React/Node) | Nueva Versión (PHP) |
|----------------------|---------------------|
| MongoDB | MySQL |
| Express.js | PHP nativo |
| React Components | Vanilla JS |
| npm/Node.js | XAMPP/Apache |

## 📝 Notas

- Este es un entorno de **desarrollo local**
- Para producción, considera:
  - HTTPS obligatorio
  - Rate limiting en la API
  - Validación de inputs más estricta
  - Caché de respuestas frecuentes

## 🤝 Soporte

Si algo no funciona, verifica:
1. ✅ XAMPP corriendo (Apache + MySQL)
2. ✅ `setup_saas_db.php` ejecutado
3. ✅ API Key configurada
4. ✅ Tenant ID correcto en el widget

---

**Creado con ❤️ para restaurantes pequeños con presupuesto limitado**
