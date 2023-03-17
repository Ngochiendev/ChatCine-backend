<?php

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
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::group(['namespace' => 'api'],function(){
    Route::any('/login', 'LoginController@login');
   
    // Route::any('/get_profile', 'LoginController@get_profile');
    Route::any('/contact', 'LoginController@contact')->middleware('CheckUser');
    Route::any('/get_rtc_token', 'AccessTokenController@get_rtc_token')->middleware('CheckUser');
    Route::any('/send_notice', 'LoginController@send_notice')->middleware('CheckUser');
    }
);