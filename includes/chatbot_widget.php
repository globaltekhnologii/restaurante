<?php
/**
 * Widget del Chatbot SaaS - Include para integrar en páginas
 * Este archivo debe incluirse antes del </body> en las páginas donde quieras el chatbot
 */

// Obtener información del tenant del restaurante principal
$conn_widget = new mysqli("localhost", "root", "", "menu_restaurante");

if (!$conn_widget->connect_error) {
    // Buscar el tenant del restaurante principal (el último creado que no sea demo)
    $result = $conn_widget->query("SELECT t.id, c.chatbot_name, c.welcome_message, c.primary_color 
        FROM saas_tenants t
        LEFT JOIN saas_chatbot_config c ON c.tenant_id = t.id
        WHERE t.id > 1
        ORDER BY t.id DESC
        LIMIT 1");
    
    if ($result && $result->num_rows > 0) {
        $tenant_data = $result->fetch_assoc();
        $tenant_id = $tenant_data['id'];
        $chatbot_name = $tenant_data['chatbot_name'] ?? 'AsistenteBot';
        $welcome_message = $tenant_data['welcome_message'] ?? '¡Hola! ¿En qué puedo ayudarte?';
        $primary_color = $tenant_data['primary_color'] ?? '#667eea';
    } else {
        // Fallback al tenant demo
        $tenant_id = 1;
        $chatbot_name = 'DemoBot';
        $welcome_message = '¡Hola! ¿En qué puedo ayudarte?';
        $primary_color = '#f97316';
    }
    
    $conn_widget->close();
} else {
    // Valores por defecto si falla la conexión
    $tenant_id = 1;
    $chatbot_name = 'AsistenteBot';
    $welcome_message = '¡Hola! ¿En qué puedo ayudarte?';
    $primary_color = '#667eea';
}
?>

<!-- Chatbot SaaS Widget -->
<script>
  window.chatbotConfig = {
    tenantId: <?php echo $tenant_id; ?>,
    primaryColor: '<?php echo htmlspecialchars($primary_color, ENT_QUOTES); ?>',
    chatbotName: '<?php echo htmlspecialchars($chatbot_name, ENT_QUOTES); ?>',
    welcomeMessage: '<?php echo htmlspecialchars($welcome_message, ENT_QUOTES); ?>'
  };
</script>
<script src="/Restaurante/ChatbotSaaS/widget/chatbot-widget.js"></script>
