<?php
// test_chat_api.php - Test directo del API del chatbot
$url = 'http://localhost/Restaurante/ChatbotSaaS/backend/api/chat_handler.php';

$data = [
    'tenant_id' => 2, // El tenant del restaurante
    'message' => 'Hola',
    'session_id' => 'test_session_123'
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data)
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "Response from API:\n";
echo $result;
echo "\n\n";

$decoded = json_decode($result, true);
print_r($decoded);
?>
