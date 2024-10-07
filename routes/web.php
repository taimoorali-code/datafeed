<?php

use App\Http\Controllers\DataFeedController;
use App\Http\Controllers\ProductBonanaController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductSyncController;
use App\Http\Controllers\XmlProductController;
use Illuminate\Support\Facades\Route;


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

Route::get('/', function () {
    return view('welcome');
});


// For NohaNobail Store
Route::get('/feed/{language}', [DataFeedController::class, 'generateFeed']);
Route::get('/syncproduct', [ProductController::class, 'syncProducts']); // Get all products

// For Bonana Store
Route::get('/generate-xml/{language}', [XmlProductController::class, 'generateXml']);
Route::get('/syncproductbonana', [ProductSyncController::class, 'syncProducts']);
Route::get('/getProduct', [ProductSyncController::class, 'getproduct']);
