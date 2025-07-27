<?php

/**
 * Script untuk testing Midtrans Notification URL
 * Jalankan dengan: php test_midtrans_notification.php
 */

// URL endpoint yang akan ditest
$notification_url = 'https://service.plazafestival-gmsb.co.id/api/midtrans/notification';

// Server key untuk signature (default sandbox key)
$server_key = 'SB-Mid-server-MlgT90DqGHFfB4QIiKwD-es-';

// Function untuk generate signature yang benar
function generateSignature($orderId, $statusCode, $grossAmount, $serverKey) {
    $input = $orderId . $statusCode . $grossAmount . $serverKey;
    return hash('sha512', $input);
}

// Data contoh notifikasi Midtrans
$order_id = 'ORDER-TEST-' . time();
$status_code = '200';
$gross_amount = '100000.00';
$transaction_status = 'capture';

// Generate signature yang benar
$signature_key = generateSignature($order_id, $status_code, $gross_amount, $server_key);

$test_data = [
    'transaction_time' => date('Y-m-d H:i:s'),
    'transaction_status' => $transaction_status,
    'transaction_id' => 'test-transaction-' . time(),
    'status_message' => 'midtrans payment notification',
    'status_code' => $status_code,
    'signature_key' => $signature_key,
    'payment_type' => 'credit_card',
    'order_id' => $order_id,
    'merchant_id' => 'G141532850',
    'gross_amount' => $gross_amount,
    'fraud_status' => 'accept',
    'currency' => 'IDR'
];

echo "=== TESTING MIDTRANS NOTIFICATION URL ===\n";
echo "URL: " . $notification_url . "\n";
echo "Server Key: " . substr($server_key, 0, 20) . "...\n";
echo "Signature Input: " . $order_id . $status_code . $gross_amount . "[SERVER_KEY]\n";
echo "Generated Signature: " . $signature_key . "\n";
echo "Data: " . json_encode($test_data, JSON_PRETTY_PRINT) . "\n\n";

// Initialize cURL
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => $notification_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($test_data),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: MidtransNotificationTest/1.0'
    ],
    CURLOPT_SSL_VERIFYPEER => false, // Untuk testing saja
    CURLOPT_SSL_VERIFYHOST => false, // Untuk testing saja
    CURLOPT_VERBOSE => true
]);

echo "Mengirim request...\n";

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);

curl_close($curl);

echo "=== HASIL TESTING ===\n";
echo "HTTP Code: " . $http_code . "\n";

if ($error) {
    echo "cURL Error: " . $error . "\n";
} else {
    echo "Response: \n" . $response . "\n";
}

// Test dengan status yang berbeda
$test_statuses = [
    ['status' => 'capture', 'code' => '200'],
    ['status' => 'settlement', 'code' => '200'],
    ['status' => 'pending', 'code' => '201'],
    ['status' => 'deny', 'code' => '200'],
    ['status' => 'cancel', 'code' => '200'],
    ['status' => 'expire', 'code' => '407']
];

echo "\n=== TESTING BERBAGAI STATUS ===\n";

foreach ($test_statuses as $test_status) {
    $status = $test_status['status'];
    $code = $test_status['code'];
    
    $test_order_id = 'ORDER-' . strtoupper($status) . '-' . time();
    $test_signature = generateSignature($test_order_id, $code, $gross_amount, $server_key);
    
    $test_data = [
        'transaction_time' => date('Y-m-d H:i:s'),
        'transaction_status' => $status,
        'transaction_id' => 'test-' . $status . '-' . time(),
        'status_message' => 'midtrans payment notification',
        'status_code' => $code,
        'signature_key' => $test_signature,
        'payment_type' => 'credit_card',
        'order_id' => $test_order_id,
        'merchant_id' => 'G141532850',
        'gross_amount' => $gross_amount,
        'fraud_status' => 'accept',
        'currency' => 'IDR'
    ];
    
    echo "Testing status: " . $status . " (code: " . $code . ")... ";
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $notification_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($test_data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($http_code == 200) {
        echo "✓ OK";
        // Decode response to show status
        $resp_data = json_decode($response, true);
        if (isset($resp_data['message'])) {
            echo " - " . $resp_data['message'];
        }
        echo "\n";
    } else {
        echo "✗ Failed (HTTP " . $http_code . ")";
        if (!empty($response)) {
            $resp_data = json_decode($response, true);
            if (isset($resp_data['message'])) {
                echo " - " . $resp_data['message'];
            }
        }
        echo "\n";
    }
    
    // Delay untuk menghindari rate limiting
    sleep(1);
}

echo "\nTesting selesai!\n";
