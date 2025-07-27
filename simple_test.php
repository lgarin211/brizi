<?php
// Simple test untuk authentication API
echo "=== Testing PlazaFest Authentication API ===\n\n";

$baseUrl = 'http://localhost:8000/api/auth';

function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $headers[] = 'Content-Type: application/json';
    }

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return [
            'status' => 0,
            'error' => $error,
            'body' => null
        ];
    }

    return [
        'status' => $httpCode,
        'body' => json_decode($response, true),
        'raw' => $response
    ];
}

// Test 1: Register
echo "1. Testing Registration...\n";
$registerData = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john.test' . time() . '@example.com', // unique email
    'phone' => '08123456789' . rand(0, 9),  // unique phone
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'city' => 'Jakarta'
];

$result = makeRequest($baseUrl . '/register', 'POST', $registerData);
echo "Status: " . $result['status'] . "\n";
if ($result['error']) {
    echo "Error: " . $result['error'] . "\n";
} else {
    echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n";
}
echo "\n";

// Test 2: Check if routes exist
echo "2. Testing Route Availability...\n";
$result = makeRequest($baseUrl . '/register', 'OPTIONS');
echo "OPTIONS /register Status: " . $result['status'] . "\n";

$result = makeRequest($baseUrl . '/login', 'OPTIONS');
echo "OPTIONS /login Status: " . $result['status'] . "\n";
echo "\n";

// Test 3: Check basic app health
echo "3. Testing App Health...\n";
$result = makeRequest('http://localhost:8000/api/getlistFasility', 'GET');
echo "GET /api/getlistFasility Status: " . $result['status'] . "\n";
if ($result['body']) {
    echo "Existing API is working\n";
}

echo "\n=== Testing Completed ===\n";
