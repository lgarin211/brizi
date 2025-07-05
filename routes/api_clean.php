<?php

use App\Http\Controllers\Api\ArgentaController;
use App\Http\Controllers\Api\FirebaseController;
use App\Http\Controllers\Api\FileStorageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Argenta Hub API Routes
Route::prefix('hub/argenta')->group(function () {
    Route::get('index', [ArgentaController::class, 'getIndexData']);
    Route::get('berita', [ArgentaController::class, 'getBeritaData']);
    Route::get('karir', [ArgentaController::class, 'getKarirData']);
    Route::get('mapubg', [ArgentaController::class, 'getMapData']);
});

// Branch Location Routes
Route::prefix('hub/branch')->group(function () {
    Route::post('pushmap', [ArgentaController::class, 'storeBranchLocation']);
});

// Firebase Push Routes
Route::prefix('firebase/push')->group(function () {
    Route::post('argenta/index', [FirebaseController::class, 'pushIndexData']);
    Route::post('argenta/berita', [FirebaseController::class, 'pushBeritaData']);
    Route::post('argenta/karir', [FirebaseController::class, 'pushKarirData']);
    Route::post('argenta/locations', [FirebaseController::class, 'pushLocationData']);
    Route::post('all', [FirebaseController::class, 'pushAllData']);
    Route::get('all', [FirebaseController::class, 'pushAllData']); // GET version
});

// Firebase Get Active Data Routes
Route::prefix('firebase/get')->group(function () {
    Route::get('argenta/index/active', [FirebaseController::class, 'getActiveIndexData']);
    Route::get('argenta/berita/active', [FirebaseController::class, 'getActiveBeritaData']);
    Route::get('argenta/karir/active', [FirebaseController::class, 'getActiveKarirData']);
    Route::get('argenta/locations/active', [FirebaseController::class, 'getActiveLocationData']);

    // History Routes with optional limit parameter
    Route::get('argenta/index/history', [FirebaseController::class, 'getHistoryIndexData']);
    Route::get('argenta/berita/history', [FirebaseController::class, 'getHistoryBeritaData']);
    Route::get('argenta/karir/history', [FirebaseController::class, 'getHistoryKarirData']);
    Route::get('argenta/locations/history', [FirebaseController::class, 'getHistoryLocationData']);
});

// Firebase Storage Routes
Route::prefix('storage')->group(function () {
    Route::post('upload', [FileStorageController::class, 'uploadFile']);
    Route::post('upload-base64', [FileStorageController::class, 'uploadBase64']);
    Route::delete('delete', [FileStorageController::class, 'deleteFile']);
    Route::post('url', [FileStorageController::class, 'getFileUrl']);
    Route::get('url', [FileStorageController::class, 'getFileUrl']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
