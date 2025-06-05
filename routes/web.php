<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;

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
    return redirect()->route('dashboard');
});

// Dashboard routes
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/documents/search', [DocumentController::class, 'searchView'])->name('documents.search');

// معالجة عملية البحث
Route::get('/documents/searchHandle', [DocumentController::class, 'search'])->name('documents.searchHandle');

// الموارد الأساسية للمستندات
Route::resource('documents', DocumentController::class);//Route::get('/documents/search', [DocumentController::class, 'search'])->name('documents.search');

