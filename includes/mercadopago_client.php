<?php
/**
 * Cliente Mercado Pago API
 * Maneja la comunicación con Mercado Pago
 */

class MercadoPagoClient {
    private $accessToken;
    private $apiUrl;
    private $mode;
    
    public function __construct() {
        $this->loadConfig();
    }
    
    private function loadConfig() {
        $conn = getDatabaseConnection();
        $result = $conn->query("SELECT * FROM config_pagos WHERE pasarela = 'mercadopago' AND activa = 1");
        $config = $result->fetch_assoc();
        $conn->close();
        
        if (!$config) {
            throw new Exception('Mercado Pago no está configurado o no está activo');
        }
        
        $this->accessToken = $config['secret_key'];
        $this->mode = $config['modo'];
        $this->apiUrl = 'https://api.mercadopago.com';
        
        if (empty($this->accessToken)) {
            throw new Exception('Access Token de Mercado Pago no configurado');
        }
    }
    
    /**
     * Crear preferencia de pago
     */
    public function crearPreferencia($data) {
        $endpoint = $this->apiUrl . '/checkout/preferences';
        
        $payload = [
            'items' => [
                [
                    'title' => $data['descripcion'],
                    'quantity' => 1,
                    'unit_price' => (float)$data['monto'],
                    'currency_id' => 'COP'
                ]
            ],
            'payer' => [
                'name' => $data['cliente_nombre'],
                'email' => $data['cliente_email'] ?: 'cliente@ejemplo.com',
                'phone' => [
                    'number' => $data['cliente_telefono']
                ],
                'identification' => [
                    'type' => $this->mapTipoDocumento($data['tipo_documento']),
                    'number' => $data['numero_documento']
                ]
            ],
            'back_urls' => [
                'success' => $data['url_retorno'],
                'failure' => $data['url_retorno'],
                'pending' => $data['url_retorno']
            ],
            'auto_return' => 'approved',
            'external_reference' => $data['referencia'],
            'notification_url' => $data['url_webhook']
        ];
        
        return $this->makeRequest('POST', $endpoint, $payload);
    }
    
    /**
     * Consultar pago
     */
    public function consultarPago($paymentId) {
        $endpoint = $this->apiUrl . '/v1/payments/' . $paymentId;
        return $this->makeRequest('GET', $endpoint);
    }
    
    private function mapTipoDocumento($tipo) {
        $map = [
            'CC' => 'CC',
            'CE' => 'CE',
            'NIT' => 'NIT',
            'Pasaporte' => 'PPN'
        ];
        return $map[$tipo] ?? 'CC';
    }
    
    private function makeRequest($method, $endpoint, $data = null) {
        $ch = curl_init();
        
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
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
            throw new Exception('Error en petición a Mercado Pago: ' . $error);
        }
        
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMsg = $decoded['message'] ?? 'Error desconocido';
            throw new Exception('Mercado Pago Error (' . $httpCode . '): ' . $errorMsg);
        }
        
        return $decoded;
    }
    
    public function isSandbox() {
        return $this->mode === 'sandbox';
    }
}
?>
