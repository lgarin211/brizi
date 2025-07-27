<?php

namespace App\Http\Controllers\PlazaFest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;

class MidtransController extends Controller
{
    // Midtrans Configuration
    private $serverKey;
    private $clientKey;
    private $isProduction;
    private $snapUrl;

    public function __construct()
    {
        // Midtrans Sandbox Configuration
        $this->serverKey = env('MIDTRANS_SERVER_KEY', 'SB-Mid-server-MlgT90DqGHFfB4QIiKwD-es-');
        $this->clientKey = env('MIDTRANS_CLIENT_KEY', 'SB-Mid-client-OHINEOgP3IgwA9Wt');
        $this->isProduction = env('MIDTRANS_IS_PRODUCTION', false);
        $this->snapUrl = $this->isProduction
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    /**
     * Get available payment methods
     */
    public function getPaymentMethods()
    {
        $paymentMethods = [
            'credit_card' => [
                'type' => 'credit_card',
                'name' => 'Credit Card',
                'description' => 'Visa, MasterCard, JCB',
                'enabled' => true,
                'icon' => 'credit-card.png'
            ],
            'bank_transfer' => [
                'type' => 'bank_transfer',
                'name' => 'Bank Transfer',
                'description' => 'Transfer via ATM/Internet Banking',
                'enabled' => true,
                'icon' => 'bank-transfer.png',
                'banks' => [
                    'bca' => ['name' => 'BCA', 'code' => 'bca'],
                    'bni' => ['name' => 'BNI', 'code' => 'bni'],
                    'bri' => ['name' => 'BRI', 'code' => 'bri'],
                    'mandiri' => ['name' => 'Mandiri', 'code' => 'echannel'],
                    'permata' => ['name' => 'Permata Bank', 'code' => 'permata']
                ]
            ],
            'e_wallet' => [
                'type' => 'e_wallet',
                'name' => 'E-Wallet',
                'description' => 'Pembayaran digital',
                'enabled' => true,
                'icon' => 'e-wallet.png',
                'providers' => [
                    'gopay' => ['name' => 'GoPay', 'code' => 'gopay'],
                    'shopeepay' => ['name' => 'ShopeePay', 'code' => 'shopeepay'],
                    'dana' => ['name' => 'DANA', 'code' => 'dana'],
                    'ovo' => ['name' => 'OVO', 'code' => 'ovo'],
                    'linkaja' => ['name' => 'LinkAja', 'code' => 'linkaja']
                ]
            ],
            'convenience_store' => [
                'type' => 'convenience_store',
                'name' => 'Convenience Store',
                'description' => 'Bayar di toko terdekat',
                'enabled' => true,
                'icon' => 'convenience-store.png',
                'stores' => [
                    'indomaret' => ['name' => 'Indomaret', 'code' => 'cstore'],
                    'alfamart' => ['name' => 'Alfamart', 'code' => 'alfamart']
                ]
            ],
            'qris' => [
                'type' => 'qris',
                'name' => 'QRIS',
                'description' => 'Scan QR Code untuk bayar',
                'enabled' => true,
                'icon' => 'qris.png'
            ]
        ];

        return response()->json([
            'success' => true,
            'payment_methods' => $paymentMethods
        ]);
    }

    /**
     * Create Midtrans payment transaction
     */
    public function createPayment(Request $request)
    {
        $validated = $request->validate([
            'transaction_id' => 'required|integer',
            'payment_type' => 'required|string',
            'customer_details' => 'required|array',
            'customer_details.first_name' => 'required|string',
            'customer_details.last_name' => 'required|string',
            'customer_details.email' => 'required|email',
            'customer_details.phone' => 'required|string',
        ]);
        try {
            // Get transaction details
            $transaction = DB::table('transaction')->where('id', $validated['transaction_id'])->first();
            // dd($transaction);

            if (!$transaction) {
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            // Generate unique order ID
            $orderId = 'PLAZA-' . $transaction->id . '-' . time();

            // Get subfacility details for item name
            // dd($transaction->idsubfacility);
            $subFacility = DB::table('sub_facility')
                ->join('facility', 'sub_facility.idfacility', '=', 'facility.id')
                ->where('sub_facility.id', $transaction->idsubfacility)
                // ->select('sub_facility.name as subfacility_name', 'facility.name as facility_name')
                ->first();
            // dump($subFacility);
            $itemName = $subFacility
                ? $subFacility->name . ' - ' . $subFacility->name
                : 'Booking Fasilitas Plaza';

            // dd($itemName);

            // Prepare item details according to Midtrans documentation
            $itemDetails = [
                [
                    'id' => 'facility_' . $transaction->idsubfacility,
                    'price' => (int) $transaction->price,
                    'quantity' => 1,
                    'name' => $itemName,
                    'category' => 'facility_booking'
                ]
            ];

            // dd($itemDetails);

            // Prepare billing address if provided
            $billingAddress = [
                'first_name' => $validated['customer_details']['first_name'],
                'last_name' => $validated['customer_details']['last_name'],
                'email' => $validated['customer_details']['email'],
                'phone' => $validated['customer_details']['phone'],
                'address' => $request->input('customer_details.address', ''),
                'city' => $request->input('customer_details.city', 'Jakarta'),
                'postal_code' => $request->input('customer_details.postal_code', '12345'),
                'country_code' => 'IDN'
            ];

            // Prepare Midtrans Snap API payload according to documentation
            $snapPayload = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => (int) $transaction->price
                ],
                'item_details' => $itemDetails,
                'customer_details' => [
                    'first_name' => $validated['customer_details']['first_name'],
                    'last_name' => $validated['customer_details']['last_name'],
                    'email' => $validated['customer_details']['email'],
                    'phone' => $validated['customer_details']['phone'],
                    'billing_address' => $billingAddress,
                    'shipping_address' => $billingAddress
                ],
                'enabled_payments' => $this->getEnabledPayments($validated['payment_type']),
                'callbacks' => [
                    'finish' => url('/api/midtrans/finish?nbis='. $request->input('cathcallback')),
                    'unfinish' => url('/api/midtrans/unfinish'),
                    'error' => url('/api/midtrans/error')
                ],
                'expiry' => [
                    'start_time' => date('Y-m-d H:i:s O'),
                    'unit' => 'hour',
                    'duration' => 24
                ],
                'custom_field1' => 'Plaza Facility Booking',
                'custom_field2' => 'Transaction ID: ' . $transaction->id,
                'custom_field3' => 'Date: ' . $transaction->date_start
            ];

            // Add specific payment method configuration
            $snapPayload = $this->addPaymentSpecificConfig($snapPayload, $validated['payment_type'], $request);

            // Call Midtrans Snap API
            $snapResponse = $this->callMidtransSnapAPI($snapPayload);

            if (!$snapResponse['success']) {
                return response()->json([
                    'error' => 'Failed to create payment',
                    'message' => $snapResponse['message']
                ], 500);
            }

            // Update transaction with order ID and payment type
            DB::table('transaction')
                ->where('id', $transaction->id)
                ->update([
                    'order_id' => $orderId,
                    'payment_type' => $validated['payment_type'],
                    'timeexpaired' => now()->addHours(24),
                    'snap_token' => $snapResponse['data']['token'],
                    'snap_redirect_url' => $snapResponse['data']['redirect_url'],
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'order_id' => $orderId,
                'transaction_id' => $transaction->id,
                'amount' => $transaction->price,
                'payment_type' => $validated['payment_type'],
                'snap_token' => $snapResponse['data']['token'],
                'snap_redirect_url' => $snapResponse['data']['redirect_url'],
                'expires_at' => now()->addHours(24)->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Create Midtrans Payment Error: ' . $e->getMessage(), [
                'transaction_id' => $validated['transaction_id'] ?? null,
                'payment_type' => $validated['payment_type'] ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            dd($e);
            return response()->json(['error' => 'Failed to create payment', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Call Midtrans Snap API to create transaction token
     */
    private function callMidtransSnapAPI($payload)
    {
        try {
            // Prepare Authorization header with Server Key
            $authString = base64_encode($this->serverKey . ':');

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $authString
            ])->post($this->snapUrl, $payload);

            if ($response->successful()) {
                $responseData = $response->json();

                Log::info('Midtrans Snap API Success', [
                    'order_id' => $payload['transaction_details']['order_id'],
                    'token' => $responseData['token'] ?? null
                ]);

                return [
                    'success' => true,
                    'data' => $responseData
                ];
            } else {
                $errorData = $response->json();

                Log::error('Midtrans Snap API Error', [
                    'status_code' => $response->status(),
                    'response' => $errorData,
                    'payload' => $payload
                ]);

                return [
                    'success' => false,
                    'message' => $errorData['error_messages'][0] ?? 'Unknown error from Midtrans',
                    'error_code' => $errorData['status_code'] ?? $response->status()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Midtrans API Request Failed', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);

            return [
                'success' => false,
                'message' => 'Failed to connect to Midtrans API: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Midtrans notification callback
     */
    public function notification(Request $request)
    {
        try {
            $orderId = $request->order_id;
            $statusCode = $request->status_code;
            $grossAmount = $request->gross_amount;
            $transactionStatus = $request->transaction_status;
            $fraudStatus = $request->fraud_status ?? null;
            $paymentType = $request->payment_type;
            $signatureKey = $request->signature_key;

            // Verify signature according to Midtrans documentation
            if (!$this->verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
                Log::warning('Invalid Midtrans signature', [
                    'order_id' => $orderId,
                    'signature_key' => $signatureKey
                ]);
                return response()->json(['message' => 'Invalid signature'], 401);
            }

            Log::info('Midtrans Notification', [
                'order_id' => $orderId,
                'status' => $transactionStatus,
                'amount' => $grossAmount,
                'payment_type' => $paymentType
            ]);

            // Find transaction by order_id
            $transaction = DB::table('transaction')->where('order_id', $orderId)->first();

            if (!$transaction) {
                return response()->json(['message' => 'Transaction not found'], 404);
            }

            $status = 'pending';
            $timesuccess = null;

            // Determine status based on Midtrans response according to documentation
            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'challenge') {
                    $status = 'challenge';
                } else if ($fraudStatus == 'accept') {
                    $status = 'success';
                    $timesuccess = now();
                }
            } else if ($transactionStatus == 'settlement') {
                $status = 'success';
                $timesuccess = now();
            } else if ($transactionStatus == 'pending') {
                $status = 'pending';
            } else if ($transactionStatus == 'deny') {
                $status = 'failed';
            } else if ($transactionStatus == 'expire') {
                $status = 'expired';
            } else if ($transactionStatus == 'cancel') {
                $status = 'cancelled';
            } else if ($transactionStatus == 'refund') {
                $status = 'refunded';
            } else if ($transactionStatus == 'partial_refund') {
                $status = 'partial_refunded';
            }

            // Update transaction status
            DB::table('transaction')
                ->where('order_id', $orderId)
                ->update([
                    'status' => $status,
                    'timesuccess' => $timesuccess,
                    'midtrans_response' => json_encode($request->all()),
                    'updated_at' => now()
                ]);

            // Send OK response to Midtrans
            return response()->json(['message' => 'Notification processed successfully']);

        } catch (\Exception $e) {
            Log::error('Midtrans Notification Error: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error processing notification'], 500);
        }
    }

    /**
     * Verify Midtrans signature according to documentation
     */
    private function verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)
    {
        // Create signature string according to Midtrans documentation
        $input = $orderId . $statusCode . $grossAmount . $this->serverKey;
        $hash = hash('sha512', $input);

        return $hash === $signatureKey;
    }

    /**
     * Midtrans success callback
     */
    public function success(Request $request)
    {
        $orderId = $request->order_id;

        $transaction = DB::table('transaction')->where('order_id', $orderId)->first();

        if ($transaction) {
            return response()->json([
                'success' => true,
                'message' => 'Payment successful',
                'transaction_id' => $transaction->id,
                'status' => $transaction->status,
                'redirect_url' => url('/booking-success?order_id=' . $orderId)
            ]);
        }

        return response()->json(['error' => 'Transaction not found'], 404);
    }

    /**
     * Midtrans failed callback
     */
    public function failed(Request $request)
    {
        $orderId = $request->order_id;

        // Update transaction status to failed
        DB::table('transaction')
            ->where('order_id', $orderId)
            ->update([
                'status' => 'failed',
                'updated_at' => now()
            ]);

        return response()->json([
            'success' => false,
            'message' => 'Payment failed',
            'order_id' => $orderId,
            'redirect_url' => url('/booking-failed?order_id=' . $orderId)
        ]);
    }

     public function finish2(Request $request)
    {
        // Try to get order_id from different sources including raw body
        $orderId = $this->extractOrderId($request);
        // dd($request->all());
        // dd($orderId);
        // Build briziCallback URL based on transaction status
        $nbis = $request->input('nbis', $request->nbis ?? null);
        $baseCallback = $nbis ? rtrim($nbis, '/') . "/payment" : url("/payment");
        switch ($request->transaction_status) {
            case 'capture':
            $briziCallback = $baseCallback . '/success?orderid=' . $orderId;
            break;
            case 'deny':
            $briziCallback = $baseCallback . '/failed?orderid=' . $orderId;
            break;
            case 'expire':
            $briziCallback = $baseCallback . '/expired?orderid=' . $orderId;
            break;
            case 'cancel':
            $briziCallback = $baseCallback . '/cancelled?orderid=' . $orderId;
            break;
            case 'refund':
            $briziCallback = $baseCallback . '/refunded?orderid=' . $orderId;
            break;
            case 'partial_refund':
            $briziCallback = $baseCallback . '/partial_refunded?orderid=' . $orderId;
            break;
            default:
            $briziCallback = $baseCallback . '/pending?orderid=' . $orderId;
            break;
        }

        Log::info('Midtrans Finish Callback', [
            'all_request_data' => $request->all(),
            'query_params' => $request->query(),
            'post_data' => $request->input(),
            'raw_content' => $request->getContent(),
            'content_type' => $request->header('Content-Type'),
            'order_id_found' => $orderId
        ]);

        if (!$orderId) {
            return response()->json([
                'message' => 'Payment process completed but order ID not found',
                'order_id' => null,
                'status' => 'unknown',
                'redirect_url' => url('/booking-status'),
                'debug_info' => [
                    'request_method' => $request->method(),
                    'all_params' => $request->all(),
                    'raw_content' => $request->getContent(),
                    'content_type' => $request->header('Content-Type')
                ]
            ]);
        }

        $transaction = DB::table('transaction')->where('order_id', $orderId)->first();
        DB::table('transaction')
            ->where('order_id', $orderId)
            ->update([
                'status' => $request->transaction_status,
                'updated_at' => now()
            ]);
        // dd($transaction, $briziCallback);
        return redirect()->to($briziCallback)
            ->with('message', 'Payment process completed')
            ->with('order_id', $orderId)
            ->with('status', $transaction ? $transaction->status : 'unknown')
            ->with('transaction_found', $transaction ? true : false);
    }


    /**
     * Midtrans finish callback (user returns from Midtrans)
     */
    public function finish(Request $request)
    {
        // Try to get order_id from different sources including raw body
        $orderId = $this->extractOrderId($request);
        // dd($request->all());
        // dd($orderId);
        // Build briziCallback URL based on transaction status
        $nbis = $request->input('nbis', $request->nbis ?? null);
        $baseCallback = $nbis ? rtrim($nbis, '/') . "/payment" : url("/payment");
        switch ($request->transaction_status) {
            case 'capture':
            $briziCallback = $baseCallback . '/success?orderid=' . $orderId;
            break;
            case 'deny':
            $briziCallback = $baseCallback . '/failed?orderid=' . $orderId;
            break;
            case 'expire':
            $briziCallback = $baseCallback . '/expired?orderid=' . $orderId;
            break;
            case 'cancel':
            $briziCallback = $baseCallback . '/cancelled?orderid=' . $orderId;
            break;
            case 'refund':
            $briziCallback = $baseCallback . '/refunded?orderid=' . $orderId;
            break;
            case 'partial_refund':
            $briziCallback = $baseCallback . '/partial_refunded?orderid=' . $orderId;
            break;
            default:
            $briziCallback = $baseCallback . '/pending?orderid=' . $orderId;
            break;
        }

        Log::info('Midtrans Finish Callback', [
            'all_request_data' => $request->all(),
            'query_params' => $request->query(),
            'post_data' => $request->input(),
            'raw_content' => $request->getContent(),
            'content_type' => $request->header('Content-Type'),
            'order_id_found' => $orderId
        ]);

        if (!$orderId) {
            return response()->json([
                'message' => 'Payment process completed but order ID not found',
                'order_id' => null,
                'status' => 'unknown',
                'redirect_url' => url('/booking-status'),
                'debug_info' => [
                    'request_method' => $request->method(),
                    'all_params' => $request->all(),
                    'raw_content' => $request->getContent(),
                    'content_type' => $request->header('Content-Type')
                ]
            ]);
        }

        $transaction = DB::table('transaction')->where('order_id', $orderId)->first();
        DB::table('transaction')
            ->where('order_id', $orderId)
            ->update([
                'status' => $request->transaction_status,
                'updated_at' => now()
            ]);
        // dd($transaction, $briziCallback);
        return redirect()->to($briziCallback)
            ->with('message', 'Payment process completed')
            ->with('order_id', $orderId)
            ->with('status', $transaction ? $transaction->status : 'unknown')
            ->with('transaction_found', $transaction ? true : false);
    }

    /**
     * Midtrans unfinish callback (user leaves before completing payment)
     */
    public function unfinish(Request $request)
    {
        // Try to get order_id from different sources including raw body
        $orderId = $this->extractOrderId($request);

        Log::info('Midtrans Unfinish Callback', [
            'all_request_data' => $request->all(),
            'query_params' => $request->query(),
            'post_data' => $request->input(),
            'raw_content' => $request->getContent(),
            'content_type' => $request->header('Content-Type'),
            'order_id_found' => $orderId
        ]);

        return response()->json([
            'message' => 'Payment process not completed',
            'order_id' => $orderId,
            'redirect_url' => url('/booking-pending' . ($orderId ? '?order_id=' . $orderId : ''))
        ]);
    }

    /**
     * Midtrans error callback
     */
    public function error(Request $request)
    {
        // Try to get order_id from different sources including raw body
        $orderId = $this->extractOrderId($request);

        Log::error('Midtrans Error Callback', [
            'all_request_data' => $request->all(),
            'query_params' => $request->query(),
            'post_data' => $request->input(),
            'raw_content' => $request->getContent(),
            'content_type' => $request->header('Content-Type'),
            'order_id_found' => $orderId
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Payment error occurred',
            'order_id' => $orderId,
            'redirect_url' => url('/booking-error' . ($orderId ? '?order_id=' . $orderId : ''))
        ]);
    }

    /**
     * Check payment status
     */
    public function checkStatus(Request $request)
    {
        $orderId = $request->order_id;

        $transaction = DB::table('transaction')->where('order_id', $orderId)->first();

        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        return response()->json([
            'success' => true,
            'order_id' => $orderId,
            'transaction_id' => $transaction->id,
            'status' => $transaction->status,
            'payment_type' => $transaction->payment_type ?? null,
            'amount' => $transaction->price,
            'created_at' => $transaction->created_at,
            'updated_at' => $transaction->updated_at,
            'time_success' => $transaction->timesuccess,
            'time_expired' => $transaction->timeexpaired
        ]);
    }

    /**
     * Get enabled payments based on payment type
     */
    private function getEnabledPayments($paymentType)
    {
        switch ($paymentType) {
            case 'credit_card':
                return ['credit_card'];

            case 'bank_transfer':
                return ['bank_transfer'];

            case 'e_wallet':
                return ['gopay', 'shopeepay', 'dana', 'ovo', 'linkaja'];

            case 'convenience_store':
                return ['cstore', 'alfamart'];

            case 'qris':
                return ['qris'];

            default:
                return ['credit_card', 'bank_transfer', 'gopay', 'shopeepay', 'qris'];
        }
    }

    /**
     * Add payment specific configuration
     */
    private function addPaymentSpecificConfig($payload, $paymentType, $request)
    {
        switch ($paymentType) {
            case 'bank_transfer':
                if ($request->has('bank')) {
                    $payload['payment_type'] = 'bank_transfer';
                    $payload['bank_transfer'] = [
                        'bank' => $request->bank
                    ];
                }
                break;

            case 'e_wallet':
                if ($request->has('ewallet_provider')) {
                    $payload['payment_type'] = $request->ewallet_provider;
                }
                break;

            case 'convenience_store':
                if ($request->has('store')) {
                    $payload['payment_type'] = $request->store;
                    if ($request->store === 'cstore') {
                        $payload['cstore'] = [
                            'store' => 'indomaret',
                            'message' => 'Booking Fasilitas Plaza'
                        ];
                    }
                }
                break;
        }

        return $payload;
    }

    /**
     * Debug endpoint to check what parameters are received
     */
    public function debugCallback(Request $request)
    {
        $orderId = $this->extractOrderId($request);

        // Parse raw body for debugging
        $rawBody = $request->getContent();
        $parsedJson = null;
        $parsedForm = [];

        if (!empty($rawBody)) {
            $jsonData = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $parsedJson = $jsonData;
            }
            parse_str($rawBody, $parsedForm);
        }

        return response()->json([
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->header(),
            'query_params' => $request->query(),
            'post_data' => $request->input(),
            'all_data' => $request->all(),
            'raw_content' => $rawBody,
            'parsed_json' => $parsedJson,
            'parsed_form' => $parsedForm,
            'extracted_order_id' => $orderId,
            'content_type' => $request->header('Content-Type'),
            'content_length' => $request->header('Content-Length')
        ]);
    }

    /**
     * Extract order_id from various sources including raw body
     */
    private function extractOrderId(Request $request)
    {
        // List of possible parameter names for order_id
        $orderIdParams = [
            'order_id', 'ORDER_ID', 'orderId', 'orderid', 'ORDERID',
            'transaction_id', 'TRANSACTION_ID', 'transactionId',
            'id', 'ID'
        ];

        // First try regular request parameters (input and query)
        foreach ($orderIdParams as $param) {
            $orderId = $request->input($param) ?? $request->query($param);
            if ($orderId) {
                Log::info('Order ID found in request parameter', [
                    'parameter' => $param,
                    'value' => $orderId
                ]);
                return $orderId;
            }
        }

        // Try to get from middleware processed raw body
        $rawBody = $request->attributes->get('midtrans_raw_body') ?? $request->getContent();

        if (!empty($rawBody)) {
            Log::info('Attempting to extract order_id from raw body', [
                'raw_body' => $rawBody,
                'content_type' => $request->header('Content-Type')
            ]);

            // Try to parse as JSON first
            $jsonData = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                foreach ($orderIdParams as $param) {
                    if (isset($jsonData[$param])) {
                        Log::info('Order ID found in JSON data', [
                            'parameter' => $param,
                            'value' => $jsonData[$param]
                        ]);
                        return $jsonData[$param];
                    }
                }
            }

            // Try to parse as form data
            parse_str($rawBody, $parsedData);
            if (is_array($parsedData)) {
                foreach ($orderIdParams as $param) {
                    if (isset($parsedData[$param])) {
                        Log::info('Order ID found in form data', [
                            'parameter' => $param,
                            'value' => $parsedData[$param]
                        ]);
                        return $parsedData[$param];
                    }
                }
            }

            // Try to extract from URL-like string in raw body using more patterns
            $patterns = [
                '/[?&]order_id=([^&\s]+)/i',
                '/[?&]ORDER_ID=([^&\s]+)/i',
                '/[?&]orderId=([^&\s]+)/i',
                '/[?&]transaction_id=([^&\s]+)/i',
                '/[?&]id=([^&\s]+)/i',
                '/"order_id"\s*:\s*"([^"]+)"/i',
                '/"ORDER_ID"\s*:\s*"([^"]+)"/i'
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $rawBody, $matches)) {
                    $extractedId = urldecode($matches[1]);
                    Log::info('Order ID found using regex pattern', [
                        'pattern' => $pattern,
                        'value' => $extractedId
                    ]);
                    return $extractedId;
                }
            }
        }

        Log::warning('Order ID not found in any source', [
            'request_method' => $request->method(),
            'all_input' => $request->all(),
            'query_params' => $request->query(),
            'raw_body' => $rawBody,
            'content_type' => $request->header('Content-Type')
        ]);

        return null;
    }

    /**
     * Test endpoint specifically for raw body parameter extraction
     */
    public function testRawBody(Request $request)
    {
        $orderId = $this->extractOrderId($request);
        $rawBody = $request->getContent();
        $middlewareRawBody = $request->attributes->get('midtrans_raw_body');

        // Parse raw body manually for comparison
        $jsonParsed = null;
        $formParsed = [];

        if (!empty($rawBody)) {
            $jsonParsed = json_decode($rawBody, true);
            parse_str($rawBody, $formParsed);
        }

        return response()->json([
            'extracted_order_id' => $orderId,
            'request_method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'raw_body' => $rawBody,
            'middleware_raw_body' => $middlewareRawBody,
            'json_parsed' => $jsonParsed,
            'form_parsed' => $formParsed,
            'request_input' => $request->input(),
            'request_query' => $request->query(),
            'request_all' => $request->all(),
            'headers' => $request->headers->all(),
            'test_scenarios' => [
                'json_test' => '{"order_id": "TEST-123"}',
                'form_test' => 'order_id=TEST-123&status=success',
                'query_test' => '?order_id=TEST-123'
            ]
        ]);
    }
}
