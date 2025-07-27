<?php
/*
=================================================================================
DOKUMENTASI KONFIGURASI MIDTRANS NOTIFICATION URLS
=================================================================================

File ini adalah dokumentasi untuk konfigurasi URL notifikasi Midtrans.
Ini BUKAN file yang akan dieksekusi, melainkan panduan implementasi.

=== ROUTES UNTUK URL NOTIFIKASI BERBEDA ===
Tambahkan di routes/api.php jika ingin URL terpisah:

// 1. URL Notifikasi Utama
Route::post('/midtrans/notification', [MidtransController::class, 'notification'])
    ->middleware('midtrans.callback');

// 2. URL Notifikasi Backup/Berulang
Route::post('/midtrans/notification-backup', [MidtransController::class, 'notificationBackup'])
    ->middleware('midtrans.callback');

// 3. URL Notifikasi untuk Linking Akun
Route::post('/midtrans/notification-linking', [MidtransController::class, 'notificationLinking'])
    ->middleware('midtrans.callback');

=== METHODS CONTROLLER TAMBAHAN ===
Tambahkan di MidtransController.php jika diperlukan:

Backup notification handler:
public function notificationBackup(Request $request)
{
    // Log sebagai backup notification
    Log::info('Midtrans Backup Notification', [
        'order_id' => $request->order_id,
        'status' => $request->transaction_status,
        'timestamp' => now()
    ]);

    // Panggil method notification utama
    return $this->notification($request);
}

Account linking notification handler:
public function notificationLinking(Request $request)
{
    try {
        Log::info('Midtrans Account Linking Notification', [
            'account_id' => $request->account_id ?? null,
            'linking_status' => $request->linking_status ?? null,
            'payment_type' => $request->payment_type,
            'all_data' => $request->all()
        ]);

        // Handle account linking logic
        if ($request->linking_status === 'success') {
            // Process successful account linking
            // Update user account linking status, etc.
        }

        return response()->json(['message' => 'Account linking notification processed']);

    } catch (\Exception $e) {
        Log::error('Account Linking Notification Error: ' . $e->getMessage());
        return response()->json(['message' => 'Error processing linking notification'], 500);
    }
}

=== URL UNTUK MIDTRANS DASHBOARD ===

1. URL notifikasi pembayaran:
   https://service.plazafestival-gmsb.co.id/api/midtrans/notification

2. URL notifikasi pembayaran berulang:
   https://service.plazafestival-gmsb.co.id/api/midtrans/notification-backup

3. URL notifikasi menghubungkan akun:
   https://service.plazafestival-gmsb.co.id/api/midtrans/notification-linking

=== REKOMENDASI PENGGUNAAN ===

Untuk kemudahan maintenance, disarankan menggunakan URL yang sama untuk semua:
https://service.plazafestival-gmsb.co.id/api/midtrans/notification

Karena handler notification() sudah cukup robust untuk menangani semua jenis notifikasi.

=== TESTING URL NOTIFICATION ===

Gunakan cURL untuk testing:

curl -X POST https://service.plazafestival-gmsb.co.id/api/midtrans/notification \
  -H "Content-Type: application/json" \
  -d '{
    "transaction_time": "2024-01-01 12:00:00",
    "transaction_status": "capture",
    "transaction_id": "test-transaction-123",
    "status_message": "midtrans payment notification",
    "status_code": "200",
    "signature_key": "test-signature",
    "payment_type": "credit_card",
    "order_id": "ORDER-123456",
    "merchant_id": "your-merchant-id",
    "gross_amount": "100000.00",
    "fraud_status": "accept",
    "currency": "IDR"
  }'

=== CATATAN PENTING ===

1. Pastikan middleware 'midtrans.callback' sudah dikonfigurasi
2. Verifikasi signature_key untuk keamanan
3. Log semua notifikasi untuk debugging
4. Handle semua status transaksi yang mungkin
5. Backup data notifikasi untuk audit trail

*/
