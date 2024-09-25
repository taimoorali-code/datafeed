<?php

use App\Http\Controllers\DataFeedController;
use App\Http\Controllers\ProductController;
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

Route::get('/feed/{language}', [DataFeedController::class, 'generateFeed']);
// Route::get('/products', [ProductController::class, 'index']); // Get all products
// Route::get('/get-translated-data', [ProductController::class, 'getTranslatedData']);
// Route::get('/englishtproducts', [ProductController::class, 'getenglishproduct']); // Get all products
Route::get('/syncproduct', [ProductController::class, 'syncProducts']); // Get all products
// Route::get('/sync-products', [ProductController::class, 'syncProducts']);
