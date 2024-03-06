<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{ExcelImportController, SalesTeamController, RebateController, CommissionController,  CatalogController, ReportController, HomeController, CategorySupplierController, AccountController};
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
Route::post('/user-login' , [HomeController::class,'userLogin'])->name('user.login');
Route::get('/user-logout' , [HomeController::class,'userLogout'])->name('user.logout');
Route::post('/user-register' , [HomeController::class,'userRegister'])->name('user.register');
  


Route::group(['prefix' => 'admin'], function () {
    Route::middleware(['auth'])->group(function () {
        Route::get('/dashboard', [HomeController::class, 'index'])->name('home');
        Route::get('/supplier' , [ExcelImportController::class,'allSupplier'])->name('supplier');
        Route::get('/upload-sheet' , [ExcelImportController::class,'index'])->name('upload.sheets');
        Route::post('/import-excel' , [ExcelImportController::class,'import'])->name('import.excel');
        Route::get('/delete-file/{id?}' , [ExcelImportController::class,'deleteFile'])->name('upload.delete');
        Route::get('/download/{id?}', [ExcelImportController::class, 'downloadSampleFile'])->name('file.download');

        /** Account Section Start */
        Route::get('/account/{id?}' , [AccountController::class,'allAccount'])->name('account');
        Route::post('/addaccount' , [AccountController::class,'addAccount'])->name('account.add');
        Route::get('/createaccount' , [AccountController::class,'createAccount'])->name('account.create');
        Route::get('/accounts/remove',[AccountController::class,'removeAccount'])->name('account.remove');
        Route::post('/accounts/update',[AccountController::class,'updateAccount'])->name('account.update');
        Route::get('/accounts/csv' , [AccountController::class,'exportAccountCsv'])->name('account.export-csv');
        Route::post('/account/filter' , [AccountController::class,'getAccountsWithAjax'])->name('account.filter');
        Route::get('/accounts/edit/{id}/{routename}' , [AccountController::class,'editAccount'])->name('account.edit');
        Route::get('/accounts/customer-edit' , [AccountController::class,'editCustomerName'])->name('account.customer-edit');
        ROute::post('/accounts/update-missing-account',[AccountController::class,'updateMissingAccount'])->name('account.missing');
        /** Account Section End */

        /** Report Section Start */
        Route::get('/back' , [ReportController::class,'Back'])->name('report.back');
        Route::post('/report/filter', [ReportController::class, 'dataFilter'])->name('report.filter');
        Route::get('/reports/csv' , [ReportController::class,'exportCsv'])->name('report.export-csv');
        Route::get('/report/{reportType}/{id?}' , [ReportController::class,'index'])->name('report.type');
        /** Report Section End */
    
        //not in use now this route     
        Route::get('/userlist' , [HomeController::class,'userview'])->name('user.show');
        Route::get('/remove', [HomeController::class, 'UserRemove'])->name('user.remove');
        Route::get('/updateuser', [HomeController::class, 'UpdateUser'])->name('user.updateuser');
        Route::post('/updateuserdata', [HomeController::class, 'UpdateUserData'])->name('user.updateuserdata');

        /** Catalog Section Start */
        Route::get('/catalog/{catalogType}/{id?}' , [CatalogController::class,'index'])->name('catalog.list');
        Route::post('/catalog/filter' , [CatalogController::class,'catalogAjaxFilter'])->name('catalog.filter');
        Route::get('/catalogs/csv' , [CatalogController::class,'exportCatalogCsv'])->name('catalog.export-csv');
      
        /** Catalog Section End */
        // ...
        // Route::get('/{routename}/viewdetail/{id}' ,  [AccountController::class,'viewDetails'])->name('view.detail');
        /**get column Route */
        Route::get('getcolumn',[ExcelImportController::class,'getColumns'])->name('manage.columns');
        Route::post('storecolumn',[ExcelImportController::class,'saveColumns'])->name('store.columns');
        
        /** Sales team Section Start */
        Route::get('/sales-team/{id?}' , [SalesTeamController::class,'index'])->name('sales.index');
        Route::match(['get', 'post'], '/add-sales' , [SalesTeamController::class,'addsales'])->name('sales.add');
        Route::get('/sales/remove',[SalesTeamController::class,'removeSales'])->name('sales.remove');
        Route::post('/sales/update',[SalesTeamController::class,'updateSales'])->name('sales.update');
        Route::post('/sales/filter' , [SalesTeamController::class,'salesAjaxFilter'])->name('sales.filter');
        Route::get('/sales/edit/{id}/{routename}' , [SalesTeamController::class,'editSales'])->name('sales.edit');
        Route::get('/sales/updatestatus' , [SalesTeamController::class,'status_sales'])->name('sales.status');

        /** Sales team Section End */


        /** Rebate Section Start */
        Route::get('/rebate/{rebateType}/{id?}' , [RebateController::class,'index'])->name('rebate.list');
        // Route::post('/rebate/filter' , [RebateController::class,'catalogAjaxFilter'])->name('rebate.filter');
        // Route::get('/rebates/csv' , [RebateController::class,'exportCatalogCsv'])->name('rebate.export-csv');
        
        /** Rebate Section End */

        /** Commission Section Start */
        Route::get('/commission/{commissionType}/{id?}' , [CommissionController::class,'index'])->name('commission.list');
        Route::post('/commissions/filter' , [CommissionController::class,'commissionAjaxFilter'])->name('commission.filter');
        Route::get('/commissions/view-add' , [CommissionController::class,'commissionAddView'])->name('commission.add-view');
        Route::get('/commissions/customer-search' , [CommissionController::class,'commissionAjaxCustomerSearch'])->name('commission.customerSearch');
        Route::get('/commissions/supplier-search' , [CommissionController::class,'commissionAjaxSupplierSearch'])->name('commission.supplierSearch');
        Route::post('/commissions/add' , [CommissionController::class,'commissionAdd'])->name('commission.add');
        Route::get('/commissions/csv' , [CommissionController::class,'exportCatalogCsv'])->name('commission.export-csv');
      
        /** Commission Section End */
    });
});

/** Default redirection */
Route::fallback(function () {
    return redirect()->back();
});

Route::get('/random-function' , [CategorySupplierController::class,'index'])->name('random.number');
Route::get('/create-password' , [HomeController::class,'createPassword'])->name('create.password');