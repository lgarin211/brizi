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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// PlazaFest Routes
Route::get('/getlistFasility', [FasilitasController::class, 'getListFasility']);
Route::get('/getlistFasility/{id}', [FasilitasController::class, 'getListFasilityById']);
Route::get('/getlistFasility/{id}/{sid}', [FasilitasController::class, 'getListFasilityByIdSid']);
Route::get('/getArtikelList', [FasilitasController::class, 'getArtikelList']);

Route::post('/makeTransaction', [TransaksiController::class, 'makeTransaction']);
Route::get('/getTransaction', [TransaksiController::class, 'getTransaction']);

// Article Routes
Route::get('/articles', [ArticleController::class, 'getArticleList']);
Route::get('/articles/{id}', [ArticleController::class, 'getDetailArticleById']);

// Welcome Route
Route::get('/welcome', [WelcomeController::class, 'index']);

// Menu Restaurant Routes
Route::get('/menuresto/{id}', [MenuRestoController::class, 'getListMenu']);

// Sport Venue Routes
Route::get('/listvenue', [SportVenueController::class, 'getListVenue']);

// Detail Booking Routes
Route::get('/detailbooking', [DetailBookingController::class, 'getListDetailBooking']);

// Midtrans Payment Routes
Route::get('/payment-methods', [MidtransController::class, 'getPaymentMethods']);
Route::post('/midtrans/create-payment', [MidtransController::class, 'createPayment']);
Route::post('/midtrans/notification', [MidtransController::class, 'notification']);
Route::post('/midtrans/success', [MidtransController::class, 'success']);
Route::post('/midtrans/failed', [MidtransController::class, 'failed']);
Route::get('/midtrans/finish', [MidtransController::class, 'finish']);
Route::get('/midtrans/unfinish', [MidtransController::class, 'unfinish']);
Route::get('/midtrans/error', [MidtransController::class, 'error']);
Route::get('/payment-status', [MidtransController::class, 'checkStatus']);
