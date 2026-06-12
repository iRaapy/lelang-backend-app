<?php

use App\Http\Controllers\Api\AuctionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BidController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/auctions', [AuctionController::class, 'index']);
    Route::post('/auctions', [AuctionController::class, 'store']);
    Route::get('/auctions/my', [AuctionController::class, 'myAuctions']);
    Route::get('/auctions/{auction}', [AuctionController::class, 'show']);
    Route::put('/auctions/{auction}', [AuctionController::class, 'update']);
    Route::delete('/auctions/{auction}', [AuctionController::class, 'destroy']);

    // Bid
    Route::post('/auctions/{auction}/bids', [BidController::class, 'store']);
});