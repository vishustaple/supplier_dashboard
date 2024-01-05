<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{ExcelImportController,HomeController};
use Illuminate\Support\Facades\Auth;
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
    if(Auth::check()){
        return view('admin.index');
    }
    else{
        return view('auth.login');
    }
     
})->name('login');
  Route::get('/register' , [HomeController::class,'register'])->name('register');
  Route::post('/user-register' , [HomeController::class,'userRegister'])->name('user.register');
  Route::post('/user-login' , [HomeController::class,'userLogin'])->name('user.login');
  Route::get('/user-logout' , [HomeController::class,'userLogout'])->name('user.logout');
  

Route::prefix('admin')->group(function () {
    // Routes under the 'admin' prefix
    Route::get('/dashboard', [HomeController::class, 'index'])->name('home');
    Route::get('/upload-sheet' , [ExcelImportController::class,'index'])->name('upload.sheets');
    // ...
});

Route::post('/import-excel' , [ExcelImportController::class,'import'])->name('import.excel');
