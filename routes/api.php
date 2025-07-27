<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlazaFest\FasilitasController;
use App\Http\Controllers\PlazaFest\TransaksiController;
use App\Http\Controllers\PlazaFest\MidtransController;
use App\Http\Controllers\PlazaFest\ArticleController;
use App\Http\Controllers\PlazaFest\MenuRestoController;
use App\Http\Controllers\PlazaFest\SportVenueController;
use App\Http\Controllers\PlazaFest\WelcomeController;
use App\Http\Controllers\PlazaFest\DetailBookingController;
use App\Http\Controllers\PlazaFest\AuthController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Authentication Routes
Route::prefix('auth')->group(function () {
    // Public routes (no authentication required)
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Protected routes (authentication required)
    Route::middleware('api.token.auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    });
});

// PlazaFest Routes

/*
Sample Request: GET /api/getlistFasility
Get all available facilities
*/
Route::get('/getlistFasility', [FasilitasController::class, 'getListFasility']);

/*
Sample Request: GET /api/getlistFasility/1
Get facility details by ID
*/
Route::get('/getlistFasility/{id}', [FasilitasController::class, 'getListFasilityById']);

/*
Sample Request: GET /api/getlistFasility/1/2
Get facility sub-details by ID and Sub-ID
*/
Route::get('/getlistFasility/{id}/{sid}', [FasilitasController::class, 'getListFasilityByIdSid']);

/*
Sample Request: GET /api/getArtikelList
Get list of articles
*/
Route::get('/getArtikelList', [FasilitasController::class, 'getArtikelList']);

/*
Sample Request: POST /api/makeTransaction

Basic transaction (cash payment):
{
    "idsubfacility": 1,
    "time_start": ["09:00", "10:00"],
    "price": 100000,
    "transactionpoin": "cash",
    "date_start": "2025-07-10"
}

Advanced transaction (with payment details for Midtrans):
{
    "idsubfacility": 1,
    "time_start": ["09:00", "10:00"],
    "price": 100000,
    "transactionpoin": "midtrans",
    "date_start": "2025-07-10",
    "detail": {
        "payment_type": "e_wallet",
        "customer_details": {
            "first_name": "John",
            "last_name": "Doe",
            "email": "john@example.com",
            "phone": "08123456789",
            "address": "Jl. Sudirman No. 1",
            "city": "Jakarta",
            "postal_code": "12190"
        },
        "bank": "bca",
        "ewallet_provider": "gopay",
        "store": "indomaret"
    }
}

Response (with payment details):
{
    "success": true,
    "transaction_id": 123,
    "message": "Transaction created successfully",
    "next_step": "Use /api/midtrans/create-payment endpoint to process payment",
    "payment_ready": true,
    "customer_details": { ... },
    "payment_type": "e_wallet"
}
*/
Route::post('/makeTransaction', [TransaksiController::class, 'makeTransaction']);

/*
Sample Request: GET /api/getTransaction?id=123
Get transaction details by ID
*/
Route::get('/getTransaction', [TransaksiController::class, 'getTransaction']);

// Article Routes

/*
Sample Request: GET /api/articles
Get list of published articles
*/
Route::get('/articles', [ArticleController::class, 'getArticleList']);

/*
Sample Request: GET /api/articles/1
Get article details by ID
*/
Route::get('/articles/{id}', [ArticleController::class, 'getDetailArticleById']);

// Welcome Route

/*
Sample Request: GET /api/welcome
Get welcome page data/statistics
*/
Route::get('/welcome', [WelcomeController::class, 'index']);

// Menu Restaurant Routes

/*
Sample Request: GET /api/menuresto/1
Get menu list for restaurant with ID 1
*/
Route::get('/menuresto/{id}', [MenuRestoController::class, 'getListMenu']);

// Sport Venue Routes

/*
Sample Request: GET /api/listvenue
Get list of available sport venues
*/
Route::get('/listvenue', [SportVenueController::class, 'getListVenue']);

// Detail Booking Routes

/*
Sample Request: GET /api/detailbooking
Get list of booking details
*/
Route::get('/detailbooking', [DetailBookingController::class, 'getListDetailBooking']);

// Midtrans Payment Routes

/*
Sample Request: GET /api/payment-methods
Response: List of available payment methods (Credit Card, Bank Transfer, E-Wallet, etc.)
*/
Route::get('/payment-methods', [MidtransController::class, 'getPaymentMethods']);

/*
Sample Request: POST /api/midtrans/create-payment
Body: {
    "transaction_id": 123,
    "payment_type": "e_wallet",
    "customer_details": {
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com",
        "phone": "08123456789",
        "address": "Jl. Sudirman No. 1",
        "city": "Jakarta",
        "postal_code": "12190"
    },
    "bank": "bca",
    "ewallet_provider": "gopay",
    "store": "indomaret"
}

Response: {
    "success": true,
    "order_id": "PLAZA-123-1673456789",
    "transaction_id": 123,
    "amount": "100000",
    "payment_type": "e_wallet",
    "snap_token": "66e4fa55-xxxx-xxxx-xxxx-c632bc0c8bac",
    "snap_redirect_url": "https://app.sandbox.midtrans.com/snap/v2/vtweb/66e4fa55-xxxx-xxxx-xxxx-c632bc0c8bac",
    "expires_at": "2025-07-06T10:30:00Z"
}

Note: Use snap_token with Midtrans Snap.js or redirect user to snap_redirect_url
*/
Route::post('/midtrans/create-payment', [MidtransController::class, 'createPayment']);

/*
Sample Request: POST /api/midtrans/notification (Webhook from Midtrans Sandbox)
Headers: Content-Type: application/json
Body: {
    "order_id": "PLAZA-123-1673456789",
    "status_code": "200",
    "gross_amount": "100000.00",
    "transaction_status": "settlement",
    "fraud_status": "accept",
    "payment_type": "credit_card",
    "signature_key": "abc123def456...",
    "transaction_time": "2025-07-05 16:30:00",
    "currency": "IDR"
}

Note: This is automatically sent by Midtrans when payment status changes
Set notification URL in Midtrans Dashboard to: https://yourdomain.com/api/midtrans/notification
*/
Route::post('/midtrans/notification', [MidtransController::class, 'notification'])->middleware('midtrans.callback');

/*
Sample Request: POST /api/midtrans/success
{
    "order_id": "PLAZA-123-1673456789"
}
*/
Route::post('/midtrans/success', [MidtransController::class, 'success'])->middleware('midtrans.callback');

/*
Sample Request: POST /api/midtrans/failed
{
    "order_id": "PLAZA-123-1673456789"
}
*/
Route::post('/midtrans/failed', [MidtransController::class, 'failed'])->middleware('midtrans.callback');

/*
Sample Request: GET/POST /api/midtrans/finish?order_id=PLAZA-123-1673456789
Used when user returns from Midtrans payment page

Note: Accepts both GET and POST methods to handle different Midtrans configurations
*/
Route::match(['get', 'post'], '/midtrans/finish', [MidtransController::class, 'finish'])->middleware('midtrans.callback');

/*
Sample Request: GET/POST /api/midtrans/unfinish?order_id=PLAZA-123-1673456789
Used when user leaves payment page without completing
*/
Route::match(['get', 'post'], '/midtrans/unfinish', [MidtransController::class, 'unfinish'])->middleware('midtrans.callback');

/*
Sample Request: GET/POST /api/midtrans/error?order_id=PLAZA-123-1673456789
Used when error occurs during payment process
*/
Route::match(['get', 'post'], '/midtrans/error', [MidtransController::class, 'error'])->middleware('midtrans.callback');

/*
Sample Request: GET /api/payment-status?order_id=PLAZA-123-1673456789
Check payment status by order ID
*/
Route::get('/payment-status', [MidtransController::class, 'checkStatus']);

/*
Debug endpoint - accepts any method and shows all received parameters
*/
Route::any('/midtrans/debug', [MidtransController::class, 'debugCallback'])->middleware('midtrans.callback');

/*
Test endpoint specifically for testing raw body parameter extraction
Try these tests:
- POST /api/midtrans/test-raw-body with JSON: {"order_id": "TEST-123"}
- POST /api/midtrans/test-raw-body with form: order_id=TEST-123
- GET /api/midtrans/test-raw-body?order_id=TEST-123
*/
Route::any('/midtrans/test-raw-body', [MidtransController::class, 'testRawBody'])->middleware('midtrans.callback');
