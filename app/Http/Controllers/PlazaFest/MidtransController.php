<?php

namespace App\Http\Controllers\PlazaFest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class MidtransController extends Controller
{
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

            if (!$transaction) {
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            // Generate unique order ID
            $orderId = 'PLAZA-' . $transaction->id . '-' . time();

            // Prepare item details
            $itemDetails = [
                [
                    'id' => 'facility_' . $transaction->idsubfacility,
                    'price' => (int) $transaction->price,
                    'quantity' => 1,
                    'name' => 'Booking Fasilitas Plaza',
                    'category' => 'facility_booking'
                ]
            ];

            // Prepare Midtrans payload
            $payload = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => (int) $transaction->price
                ],
                'item_details' => $itemDetails,
                'customer_details' => $validated['customer_details'],
                'enabled_payments' => $this->getEnabledPayments($validated['payment_type']),
                'callbacks' => [
                    'finish' => url('/api/midtrans/finish'),
                    'unfinish' => url('/api/midtrans/unfinish'),
                    'error' => url('/api/midtrans/error')
                ],
                'expiry' => [
                    'start_time' => date('Y-m-d H:i:s O'),
                    'unit' => 'hour',
                    'duration' => 24
                ]
            ];

            // Add specific payment method configuration
            $payload = $this->addPaymentSpecificConfig($payload, $validated['payment_type'], $request);

            // Update transaction with order ID and payment type
            DB::table('transaction')
                ->where('id', $transaction->id)
                ->update([
                    'order_id' => $orderId,
                    'payment_type' => $validated['payment_type'],
                    'timeexpaired' => now()->addHours(24),
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'order_id' => $orderId,
                'transaction_id' => $transaction->id,
                'amount' => $transaction->price,
                'payment_type' => $validated['payment_type'],
                'midtrans_payload' => $payload,
                'snap_token' => null // This should be filled by frontend after calling Midtrans Snap API
            ]);

        } catch (\Exception $e) {
            Log::error('Create Midtrans Payment Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create payment'], 500);
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

            // Determine status based on Midtrans response
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

            return response()->json(['message' => 'Notification processed successfully']);

        } catch (\Exception $e) {
            Log::error('Midtrans Notification Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error processing notification'], 500);
        }
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

    /**
     * Midtrans finish callback (user returns from Midtrans)
     */
    public function finish(Request $request)
    {
        $orderId = $request->order_id;

        $transaction = DB::table('transaction')->where('order_id', $orderId)->first();

        return response()->json([
            'message' => 'Payment process completed',
            'order_id' => $orderId,
            'status' => $transaction ? $transaction->status : 'unknown',
            'redirect_url' => url('/booking-status?order_id=' . $orderId)
        ]);
    }

    /**
     * Midtrans unfinish callback (user leaves before completing payment)
     */
    public function unfinish(Request $request)
    {
        $orderId = $request->order_id;

        return response()->json([
            'message' => 'Payment process not completed',
            'order_id' => $orderId,
            'redirect_url' => url('/booking-pending?order_id=' . $orderId)
        ]);
    }

    /**
     * Midtrans error callback
     */
    public function error(Request $request)
    {
        $orderId = $request->order_id;

        Log::error('Midtrans Error Callback', $request->all());

        return response()->json([
            'success' => false,
            'message' => 'Payment error occurred',
            'order_id' => $orderId,
            'redirect_url' => url('/booking-error?order_id=' . $orderId)
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
}
