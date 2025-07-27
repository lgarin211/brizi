<?php

// Test script untuk authentication endpoints
// Jalankan dengan: php test_auth.php

$baseUrl = 'http://localhost:8000/api/auth';

function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $headers[] = 'Content-Type: application/json';
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

echo "=== Testing PlazaFest Authentication API ===\n\n";

// Test 1: Register
echo "1. Testing Registration...\n";
$registerData = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john.doe@test.com',
    'phone' => '081234567890',
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'address' => 'Jl. Test No. 1',
    'city' => 'Jakarta'
];

$result = makeRequest($baseUrl . '/register', 'POST', $registerData);
echo "Status: " . $result['status'] . "\n";
echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";

$apiToken = $result['body']['data']['api_token'] ?? null;

// Test 2: Login
echo "2. Testing Login...\n";
$loginData = [
    'email' => 'john.doe@test.com',
    'password' => 'password123'
];

$result = makeRequest($baseUrl . '/login', 'POST', $loginData);
echo "Status: " . $result['status'] . "\n";
echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";

if (!$apiToken && isset($result['body']['data']['api_token'])) {
    $apiToken = $result['body']['data']['api_token'];
}

if ($apiToken) {
    // Test 3: Get Profile
    echo "3. Testing Get Profile...\n";
    $result = makeRequest($baseUrl . '/profile', 'GET', null, ['Authorization: Bearer ' . $apiToken]);
    echo "Status: " . $result['status'] . "\n";
    echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";

    // Test 4: Update Profile
    echo "4. Testing Update Profile...\n";
    $updateData = [
        'first_name' => 'Jane',
        'city' => 'Bandung'
    ];
    
    $result = makeRequest($baseUrl . '/profile', 'PUT', $updateData, ['Authorization: Bearer ' . $apiToken]);
    echo "Status: " . $result['status'] . "\n";
    echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";

    // Test 5: Refresh Token
    echo "5. Testing Refresh Token...\n";
    $result = makeRequest($baseUrl . '/refresh-token', 'POST', null, ['Authorization: Bearer ' . $apiToken]);
    echo "Status: " . $result['status'] . "\n";
    echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";
    
    if (isset($result['body']['data']['api_token'])) {
        $apiToken = $result['body']['data']['api_token'];
    }

    // Test 6: Logout
    echo "6. Testing Logout...\n";
    $result = makeRequest($baseUrl . '/logout', 'POST', null, ['Authorization: Bearer ' . $apiToken]);
    echo "Status: " . $result['status'] . "\n";
    echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";

    // Test 7: Try to access profile after logout (should fail)
    echo "7. Testing Access After Logout (should fail)...\n";
    $result = makeRequest($baseUrl . '/profile', 'GET', null, ['Authorization: Bearer ' . $apiToken]);
    echo "Status: " . $result['status'] . "\n";
    echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";
}

echo "=== Testing Completed ===\n";
