<?php
// Simple test to check if our server can handle requests

$url = "http://localhost:8000/api/auth/register";
$data = [
    'email' => 'test@test.com',
    'password' => 'password123',
    'name' => 'Test User',
    'role' => 'user'
];

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "Error: Could not connect to server\n";
} else {
    echo "Response: " . $result . "\n";
}

// Also check HTTP response headers
if (isset($http_response_header)) {
    echo "HTTP Response Headers:\n";
    foreach ($http_response_header as $header) {
        echo $header . "\n";
    }
}
?>
