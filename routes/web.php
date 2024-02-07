<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{ExcelImportController, CatalogController, ReportController, HomeController, CategorySupplierController, AccountController};
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
    Route::middleware(['auth'])->group(function () {
        // Routes under the 'admin' prefix
        Route::get('/dashboard', [HomeController::class, 'index'])->name('home');
        Route::get('/upload-sheet' , [ExcelImportController::class,'index'])->name('upload.sheets');
        Route::post('/import-excel' , [ExcelImportController::class,'import'])->name('import.excel');
        Route::get('/supplier' , [ExcelImportController::class,'allSupplier'])->name('supplier');

        /** Account Section Start */
        Route::get('/account' , [ExcelImportController::class,'allAccount'])->name('account');
        Route::post('/addaccount' , [AccountController::class,'addAccount'])->name('account.add');
        Route::post('/account/filter' , [ExcelImportController::class,'getAccountsWithAjax'])->name('account.filter');
        /** Account Section End */

        /** Report Section Start */
        Route::get('/report/{reportType}' , [ReportController::class,'index'])->name('report.type');
        Route::post('/report/filter', [ReportController::class, 'dataFilter'])->name('report.filter');
        Route::get('/reports/csv' , [ReportController::class,'exportCsv'])->name('report.export-csv');
        /** Report Section End */
    
        Route::get('/userlist' , [HomeController::class,'userview'])->name('user.show');
        //not in use now this route 
        Route::get('/getparent',[AccountController::class,'getParent'])->name('getparent');
        Route::get('/updateuser', [HomeController::class, 'UpdateUser'])->name('user.updateuser');
        Route::post('/updateuserdata', [HomeController::class, 'UpdateUserData'])->name('user.updateuserdata');
        Route::get('/remove', [HomeController::class, 'UserRemove'])->name('user.remove');

        /** Catalog Section Start */
        Route::get('/catalog/{catalogType}' , [CatalogController::class,'index'])->name('catalog.list');
        Route::post('/catalog/filter' , [CatalogController::class,'catalogAjaxFilter'])->name('catalog.filter');
        Route::get('/catalogs/csv' , [CatalogController::class,'exportCatalogCsv'])->name('catalog.export-csv');
        /** Catalog Section End */
        // ...
    });
});

Route::get('/random-function' , [CategorySupplierController::class,'index'])->name('random.number');
