<?php

namespace App\Http\Controllers\PlazaFest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;

class TransaksiController extends Controller
{

    public function makeTransaction(Request $request)
    {
        $urlformRq = $request->urlformRq ?? null;
        $validated = $request->validate([
            'idsubfacility' => 'required|integer',
            'time_start' => 'required|array|min:1',
            'price' => 'required|numeric',
            'transactionpoin' => 'required|string',
            'date_start' => 'required|date',
            'detail' => 'sometimes|array',
            'detail.payment_type' => 'sometimes|string',
            'detail.customer_details' => 'sometimes|array',
            'detail.customer_details.first_name' => 'sometimes|string',
            'detail.customer_details.last_name' => 'sometimes|string',
            'detail.customer_details.email' => 'sometimes|email',
            'detail.customer_details.phone' => 'sometimes|string',
            'detail.customer_details.address' => 'sometimes|string',
            'detail.customer_details.city' => 'sometimes|string',
            'detail.customer_details.postal_code' => 'sometimes|string',
            'detail.bank' => 'sometimes|string',
            'detail.ewallet_provider' => 'sometimes|string',
            'detail.store' => 'sometimes|string',
        ]);

        $now = now();

        // Prepare basic transaction data
        $insertData = [
            'idsubfacility'    => $validated['idsubfacility'],
            'time_start'       => json_encode($validated['time_start']),
            'status'           => 'pending',
            'timesuccess'      => null,
            'timeexpaired'     => null,
            'price'            => $validated['price'],
            'transactionpoin'  => $validated['transactionpoin'],
            'date_start'       => $validated['date_start'],
            'created_at'       => $now,
            'updated_at'       => $now,
            'deleted_at'       => null,
        ];

        // Add payment details if provided
        if (isset($validated['detail'])) {
            $detail = $validated['detail'];

            // Store payment type if provided (for easy querying)
            if (isset($detail['payment_type'])) {
                $insertData['payment_type'] = $detail['payment_type'];
            }

            // Store entire detail object as JSON in additonaldata
            $insertData['additonaldata'] = json_encode($detail);
        }

        // Insert transaction and get ID
        $transactionId = DB::table('transaction')->insertGetId($insertData);

        // Prepare response data
        $responseData = [
            'success' => true,
            'transaction_id' => $transactionId,
            'message' => 'Transaction created successfully'
        ];

        // Call to Midtrans payment creation if transaction point is not cash
        if (isset($validated['detail']) && $validated['transactionpoin'] !== 'cash') {
            try {
                // Prepare Midtrans payment data
                $midtransData = [
                    'transaction_id' => $transactionId,
                    'payment_type' => $validated['detail']['payment_type'] ?? 'credit_card',
                    'customer_details' => $validated['detail']['customer_details'] ?? [],
                    'cathcallback' => $urlformRq ? $urlformRq : null,
                ];

                // Add payment method specific data
                if (isset($validated['detail']['bank'])) {
                    $midtransData['bank'] = $validated['detail']['bank'];
                }
                if (isset($validated['detail']['ewallet_provider'])) {
                    $midtransData['ewallet_provider'] = $validated['detail']['ewallet_provider'];
                }
                if (isset($validated['detail']['store'])) {
                    $midtransData['store'] = $validated['detail']['store'];
                }

                // Create new request instance for Midtrans
                $midtransRequest = new Request($midtransData);
                $midtransController = new \App\Http\Controllers\PlazaFest\MidtransController();
                $midtransResponse = $midtransController->createPayment($midtransRequest);

                // Check if Midtrans payment creation was successful
                $responseData['midtrans_response'] = json_decode($midtransResponse->getContent(), true);
                // dd($responseData['midtrans_response']);
                if ($midtransResponse->getStatusCode() === 200) {
                    $midtransData = json_decode($midtransResponse->getContent(), true);
                    if (isset($midtransData['success']) && $midtransData['success']) {
                        $responseData['payment_url'] = $midtransData['snap_redirect_url'] ?? null;
                        $responseData['snap_token'] = $midtransData['snap_token'] ?? null;
                        $responseData['order_id'] = $midtransData['order_id'] ?? null;
                    }
                } else {
                    $responseData['payment_error'] = 'Failed to create Midtrans payment';
                }

            } catch (\Exception $e) {
                $responseData['payment_error'] = 'Error creating Midtrans payment: ' . $e->getMessage();
            }

            $responseData['next_step'] = 'Use snap_token with Midtrans Snap.js or redirect to payment_url';
            $responseData['payment_ready'] = true;

            // Include customer details in response for reference
            if (isset($validated['detail']['customer_details'])) {
                $responseData['customer_details'] = $validated['detail']['customer_details'];
            }
            if (isset($validated['detail']['payment_type'])) {
                $responseData['payment_type'] = $validated['detail']['payment_type'];
            }
        }
        // dd($responseData);
        return response()->json($responseData);
    }

    public function getTransaction(Request $request)
    {
        $id = $request->id;
        $transactions = DB::table('listsuccesstransction')
            ->where('t_id',$id)
            ->first();
        if (!$transactions) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }
        if($transactions->barcode == null){
            $convert=json_encode($transactions);
            $convert=Hash::make($convert);
            $transactions->barcode =  "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data='".$convert."'";
        }
        return response()->json($transactions);
    }
}
