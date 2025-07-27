<?php

// Contoh implementasi URL notifikasi terpisah untuk Midtrans

/*
=== ROUTES UNTUK URL NOTIFIKASI BERBEDA ===
Tambahkan di routes/api.php jika ingin URL terpisah:
*/

// 1. URL Notifikasi Utama
Route::post('/midtrans/notification', [MidtransController::class, 'notification'])
    ->middleware('midtrans.callback');

// 2. URL Notifikasi Backup/Berulang
Route::post('/midtrans/notification-backup', [MidtransController::class, 'notificationBackup'])
    ->middleware('midtrans.callback');

// 3. URL Notifikasi untuk Linking Akun
Route::post('/midtrans/notification-linking', [MidtransController::class, 'notificationLinking'])
    ->middleware('midtrans.callback');

/*
=== METHODS CONTROLLER TAMBAHAN ===
Tambahkan di MidtransController.php jika diperlukan:
*/

/**
 * Backup notification handler
 */
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

/**
 * Account linking notification handler
 */
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

/*
=== URL UNTUK MIDTRANS DASHBOARD ===

1. URL notifikasi pembayaran:
   https://yourdomain.com/api/midtrans/notification

2. URL notifikasi pembayaran berulang:
   https://yourdomain.com/api/midtrans/notification-backup

3. URL notifikasi menghubungkan akun:
   https://yourdomain.com/api/midtrans/notification-linking

=== REKOMENDASI ===

Untuk kemudahan maintenance, gunakan URL yang sama untuk semua:
https://yourdomain.com/api/midtrans/notification

Handler sudah cukup robust untuk menangani semua jenis notifikasi.
*/
