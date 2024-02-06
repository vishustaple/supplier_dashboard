<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{ExcelImportController, ReportController, HomeController, CategorySupplierController, AccountController};
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
        // return view('admin.export');
        return redirect()->intended('/admin/upload-sheet');
    }
    else{
        return view('auth.login');
    }
     
})->name('login');
  Route::get('/register' , [HomeController::class,'register'])->name('register');
  Route::post('/user-register' , [HomeController::class,'userRegister'])->name('user.register');
  Route::post('/user-login' , [HomeController::class,'userLogin'])->name('user.login');
  Route::get('/user-logout' , [HomeController::class,'userLogout'])->name('user.logout');
  


  Route::group(['prefix' => 'admin'], function () {
    Route::middleware([
        'auth'])->group(function () {
    // Routes under the 'admin' prefix
    Route::get('/dashboard', [HomeController::class, 'index'])->name('home');
    Route::get('/upload-sheet' , [ExcelImportController::class,'index'])->name('upload.sheets');
    Route::post('/import-excel' , [ExcelImportController::class,'import'])->name('import.excel');
    Route::get('/supplier' , [ExcelImportController::class,'allSupplier'])->name('supplier');
    Route::get('/account' , [ExcelImportController::class,'allAccount'])->name('account');
    Route::post('/addaccount' , [AccountController::class,'addAccount'])->name('account.add');
    Route::get('/report/{reportType}' , [ReportController::class,'index'])->name('report.type');
    Route::post('/report/filter' , [ReportController::class,'dataFilter'])->name('report.filter');
    Route::post('/account/filter' , [ExcelImportController::class,'getAccountsWithAjax'])->name('account.filter');
    
    Route::get('/report/csv' , [ReportController::class,'exportCsv'])->name('report.export-csv');
    Route::get('/userlist' , [HomeController::class,'userview'])->name('user.show');
    //not in use now this route 
    Route::get('/getparent',[AccountController::class,'getParent'])->name('getparent');
    // ...
});
});

Route::get('/random-function' , [CategorySupplierController::class,'index'])->name('random.number');
