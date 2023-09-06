<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CodesController;
use App\Http\Controllers\ImagesController;
use App\Http\Controllers\ChatsController;
use Illuminate\Support\Str;



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
Route::post('/codes',[CodesController::class,'store']);
Route::post('/images',[ImagesController::class,'store']);
Route::post('/chatlists',[ChatsController::class,'index']);
Route::post('/chats',[ChatsController::class,'store']);




