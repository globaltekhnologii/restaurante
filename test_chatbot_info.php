<?php
// test_chatbot_info.php - Probar que el chatbot tenga la información correcta
$url = 'http://localhost/Restaurante/ChatbotSaaS/backend/api/chat_handler.php';

$data = [
    'tenant_id' => 2,
    'message' => '¿Cuál es la dirección del restaurante?',
    'session_id' => 'test_info_' . time()
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

echo "Pregunta: ¿Cuál es la dirección del restaurante?\n";
echo "Respuesta del chatbot:\n";
$decoded = json_decode($result, true);
if ($decoded && isset($decoded['message'])) {
    echo $decoded['message'] . "\n\n";
} else {
    echo "Error: " . $result . "\n";
}

// Segunda pregunta
$data['message'] = '¿Cuál es el teléfono?';
$data['session_id'] = 'test_info2_' . time();
$options['http']['content'] = json_encode($data);
$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "Pregunta: ¿Cuál es el teléfono?\n";
echo "Respuesta del chatbot:\n";
$decoded = json_decode($result, true);
if ($decoded && isset($decoded['message'])) {
    echo $decoded['message'] . "\n\n";
}

// Tercera pregunta
$data['message'] = '¿Cuál es el horario de atención?';
$data['session_id'] = 'test_info3_' . time();
$options['http']['content'] = json_encode($data);
$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "Pregunta: ¿Cuál es el horario de atención?\n";
echo "Respuesta del chatbot:\n";
$decoded = json_decode($result, true);
if ($decoded && isset($decoded['message'])) {
    echo $decoded['message'] . "\n";
}
?>
