<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\returnArgument;
//

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/',function(){
    return 'a';// redirect(('https://progesio.my.id'));
});

Route::group(['prefix' => 'admin', 'middleware' => ['voyager.firebase.trigger']], function () {
    Voyager::routes();
});
