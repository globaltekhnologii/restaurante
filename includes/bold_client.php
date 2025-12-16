<?php
/**
 * Cliente Bold API
 * Maneja la comunicación con la pasarela de pagos Bold
 */

class BoldClient {
    private $publicKey;
    private $secretKey;
    private $apiUrl;
    private $checkoutUrl;
    private $mode;
    
    public function __construct() {
        $this->loadConfig();
    }
    
    /**
     * Cargar configuración desde archivo .env.bold
     */
    private function loadConfig() {
        $envFile = __DIR__ . '/../.env.bold';
        
        if (!file_exists($envFile)) {
            throw new Exception('Archivo .env.bold no encontrado. Configura tus credenciales Bold.');
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $config = [];
        
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue; // Ignorar comentarios
            
            list($key, $value) = explode('=', $line, 2);
            $config[trim($key)] = trim($value);
        }
        
        $this->publicKey = $config['BOLD_PUBLIC_KEY'] ?? '';
        $this->secretKey = $config['BOLD_SECRET_KEY'] ?? '';
        $this->mode = $config['BOLD_MODE'] ?? 'production';
        $this->apiUrl = $config['BOLD_API_URL'] ?? 'https://api.bold.co/v1';
        $this->checkoutUrl = $config['BOLD_CHECKOUT_URL'] ?? 'https://checkout.bold.co';
        
        if (empty($this->publicKey) || empty($this->secretKey)) {
            throw new Exception('Credenciales Bold no configuradas correctamente');
        }
    }
    
    /**
     * Crear una orden de pago en Bold
     * 
     * @param array $data Datos del pedido
     * @return array Respuesta de Bold con URL de pago
     */
    public function crearOrdenPago($data) {
        $endpoint = $this->apiUrl . '/orders';
        
        $payload = [
            'currency' => 'COP',
            'amount' => (int)($data['monto'] * 100), // Bold usa centavos
            'description' => $data['descripcion'] ?? 'Pedido Restaurante',
            'redirectionUrl' => $data['url_retorno'],
            'webhookUrl' => $data['url_webhook'],
            'reference' => $data['referencia'], // Número de pedido
            'customer' => [
                'name' => $data['cliente_nombre'],
                'email' => $data['cliente_email'] ?? '',
                'phone' => $data['cliente_telefono'],
                'documentType' => $data['tipo_documento'] ?? 'CC',
                'documentNumber' => $data['numero_documento'] ?? ''
            ]
        ];
        
        $response = $this->makeRequest('POST', $endpoint, $payload);
        
        return $response;
    }
    
    /**
     * Consultar estado de una transacción
     * 
     * @param string $transactionId ID de la transacción en Bold
     * @return array Estado de la transacción
     */
    public function consultarTransaccion($transactionId) {
        $endpoint = $this->apiUrl . '/transactions/' . $transactionId;
        
        $response = $this->makeRequest('GET', $endpoint);
        
        return $response;
    }
    
    /**
     * Validar firma de webhook
     * 
     * @param string $payload Cuerpo de la petición
     * @param string $signature Firma recibida en headers
     * @return bool True si la firma es válida
     */
    public function validarWebhook($payload, $signature) {
        $expectedSignature = hash_hmac('sha256', $payload, $this->secretKey);
        
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Realizar petición HTTP a Bold API
     * 
     * @param string $method GET, POST, PUT, DELETE
     * @param string $endpoint URL completa del endpoint
     * @param array $data Datos a enviar (opcional)
     * @return array Respuesta decodificada
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $ch = curl_init();
        
        $headers = [
            'Authorization: ' . $this->secretKey,
            'Content-Type: application/json'
        ];
        
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data !== null && in_array($method, ['POST', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Error en petición a Bold: ' . $error);
        }
        
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMsg = $decoded['message'] ?? 'Error desconocido';
            throw new Exception('Bold API Error (' . $httpCode . '): ' . $errorMsg);
        }
        
        return $decoded;
    }
    
    /**
     * Obtener URL del checkout de Bold
     * 
     * @param string $orderId ID de la orden creada
     * @return string URL del checkout
     */
    public function getCheckoutUrl($orderId) {
        return $this->checkoutUrl . '/' . $orderId;
    }
    
    /**
     * Verificar si está en modo sandbox
     * 
     * @return bool
     */
    public function isSandbox() {
        return $this->mode === 'sandbox';
    }
}
?>
