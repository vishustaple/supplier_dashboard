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

Route::get('/register', [HomeController::class, 'register'])->name('register');
Route::post('/user-login', [HomeController::class, 'userLogin'])->name('user.login');
Route::get('/user-logout', [HomeController::class, 'userLogout'])->name('user.logout');
Route::post('/user-register', [HomeController::class, 'userRegister'])->name('user.register');
  


Route::group(['prefix' => 'admin'], function () {
    Route::middleware(['auth'])->group(function () {
        Route::get('/dashboard', [HomeController::class, 'index'])->name('home');
        Route::get('/supplier', [ExcelImportController::class, 'allSupplier'])->name('supplier');
        Route::get('/upload-sheet', [ExcelImportController::class, 'index'])->name('upload.sheets');
        Route::post('/import-excel', [ExcelImportController::class, 'import'])->name('import.excel');
        Route::get('/delete-file/{id?}', [ExcelImportController::class, 'deleteFile'])->name('upload.delete');
        Route::post('/changepassword', [HomeController::class, 'changePassword'])->name('admin.changePassword');
        Route::post('/export/filter', [ExcelImportController::class, 'getExportWithAjax'])->name('export.filter');
        Route::get('/download/{id?}', [ExcelImportController::class, 'downloadSampleFile'])->name('file.download');
        Route::get('/adminupdate', [HomeController::class, 'changePasswordView'])->name('admin.changePasswordView');

        /** Account Section Start */
        Route::get('/accounts/p-name', [AccountController::class, 'PName'])->name('ParentName');
        Route::get('/account/{id?}', [AccountController::class, 'allAccount'])->name('account');
        Route::post('/addaccount', [AccountController::class, 'addAccount'])->name('account.add');
        Route::get('/accounts/p-number', [AccountController::class, 'PNumber'])->name('ParentNumber');
        Route::get('/accounts/gp-name', [AccountController::class, 'gPName'])->name('grandParentName');
        Route::get('/createaccount', [AccountController::class, 'createAccount'])->name('account.create');
        Route::get('/accounts/remove', [AccountController::class, 'removeAccount'])->name('account.remove');
        Route::post('/accounts/update', [AccountController::class, 'updateAccount'])->name('account.update');
        Route::get('/accounts/gp-number', [AccountController::class, 'gPNumber'])->name('grandParentNumber');
        Route::get('/accounts/csv', [AccountController::class, 'exportAccountCsv'])->name('account.export-csv');
        Route::post('/account/filter', [AccountController::class, 'getAccountsWithAjax'])->name('account.filter');
        Route::get('/accounts/edit/{id}/{routename}', [AccountController::class, 'editAccount'])->name('account.edit');
        Route::post('/accounts/editaccountname', [AccountController::class, 'editAccountName'])->name('accountname.edit');
        Route::get('/accounts/count', [AccountController::class, 'getEmptyAccountNameAccounts'])->name('accounts.counts');
        Route::post('/accounts/account-number', [AccountController::class, 'getAccountNumber'])->name('get.accountNumber');
        Route::get('/accounts/customer-edit', [AccountController::class, 'editCustomerName'])->name('account.customer-edit');
        Route::post('/accounts/update-missing-account', [AccountController::class, 'updateMissingAccount'])->name('account.missing');
        /** Account Section End */

        /** Report Section Start */
        Route::get('/back', [ReportController::class, 'Back'])->name('report.back');
        Route::post('/report/filter', [ReportController::class, 'dataFilter'])->name('report.filter');
        Route::get('/reports/csv', [ReportController::class, 'exportCsv'])->name('report.export-csv');
        Route::get('/report/{reportType}/{id?}', [ReportController::class, 'index'])->name('report.type');
        Route::post('/reports/commissions/paid', [ReportController::class, 'paidUpdate'])->name('paid.update');
        Route::post('/reports/commissions/approve', [ReportController::class, 'approvedUpdate'])->name('approved.update');
        Route::post('/reports/supplier-filter', [ReportController::class, 'supplierReportFilter'])->name('report.supplier_filter');
        Route::get('/reports/supplier-csv', [ReportController::class, 'supplierReportExportCsv'])->name('report.export-supplier_report-csv');
        Route::get('/reports/commission-csv', [ReportController::class, 'commissionReportExportCsv'])->name('report.export-commission_report-csv');
        Route::post('/reports/commission-report-filter', [ReportController::class, 'commissionReportFilter'])->name('report.commission_report_filter');
        Route::post('/reports/commissions-report-filter', [ReportController::class, 'getCommissionsWithAjax'])->name('report.commission_report_filter_secound');
        
        /** Report Section End */
    
        //not in use now this route     
        Route::get('/userlist', [HomeController::class, 'userview'])->name('user.show');
        Route::get('/remove', [HomeController::class, 'UserRemove'])->name('user.remove');
        Route::get('/updateuser', [HomeController::class, 'UpdateUser'])->name('user.updateuser');
        Route::post('/updateuserdata', [HomeController::class, 'UpdateUserData'])->name('user.updateuserdata');

        /** Catalog Section Start */
        Route::get('/catalog/{catalogType}/{id?}', [CatalogController::class, 'index'])->name('catalog.list');
        Route::post('/catalog/filter', [CatalogController::class, 'catalogAjaxFilter'])->name('catalog.filter');
        Route::get('/catalogs/csv', [CatalogController::class, 'exportCatalogCsv'])->name('catalog.export-csv');
      
        /** Catalog Section End */

        /**get column Route */
        Route::get('getcolumn', [ExcelImportController::class, 'getColumns'])->name('manage.columns');
        Route::post('storecolumn', [ExcelImportController::class, 'saveColumns'])->name('store.columns');
        
        /** Sales team Section Start */
        Route::get('/sales-team/{id?}', [SalesTeamController::class, 'index'])->name('sales.index');
        Route::get('/sales/remove', [SalesTeamController::class, 'removeSales'])->name('sales.remove');
        Route::post('/sales/update', [SalesTeamController::class, 'updateSales'])->name('sales.update');
        Route::get('/sales/csv', [SalesTeamController::class, 'exportSaleCsv'])->name('sales.export-csv');
        Route::post('/sales/filter', [SalesTeamController::class, 'salesAjaxFilter'])->name('sales.filter');
        Route::get('/sales/updatestatus', [SalesTeamController::class, 'status_sales'])->name('sales.status');
        Route::get('/sales/edit/{id}/{routename}', [SalesTeamController::class, 'editSales'])->name('sales.edit');
        Route::match(['get', 'post'], '/add-sales', [SalesTeamController::class, 'addsales'])->name('sales.add');

        /** Sales team Section End */


        /** Rebate Section Start */
        Route::get('/rebates/count', [RebateController::class, 'rebateCount'])->name('rebate.counts');
        Route::post('/rebates/update', [RebateController::class, 'rebateUpdate'])->name('rebate.update');
        Route::get('/rebate/{rebateType}/{id?}', [RebateController::class, 'index'])->name('rebate.list');
        Route::post('/rebates/filter', [RebateController::class, 'getRebateWithAjax'])->name('rebate.filter');
        Route::post('/rebates/update-filter', [RebateController::class, 'getUpdateRebateWithAjax'])->name('rebate.update-filter');
        // Route::get('/rebates/csv' , [RebateController::class,'exportCatalogCsv'])->name('rebate.export-csv');
        
        /** Rebate Section End */

        /** Commission Section Start */
        Route::post('/commissions/add', [CommissionController::class, 'commissionAdd'])->name('commission.add');
        Route::post('/commissions/edit', [CommissionController::class, 'editCommission'])->name('commission.edit');
        Route::get('/commissions/csv', [CommissionController::class, 'exportCatalogCsv'])->name('commission.export-csv');
        Route::get('/commission/{commissionType}/{id?}', [CommissionController::class, 'index'])->name('commission.list');
        Route::post('/commissions/filter', [CommissionController::class, 'commissionAjaxFilter'])->name('commission.filter');
        Route::get('/commissions/view-add', [CommissionController::class, 'commissionAddView'])->name('commission.add-view');
        Route::get('/commissions/customer-search', [CommissionController::class, 'commissionAjaxCustomerSearch'])->name('commission.customerSearch');
        Route::get('/commissions/supplier-search', [CommissionController::class, 'commissionAjaxSupplierSearch'])->name('commission.supplierSearch');
      
        /** Commission Section End */
    });
});

/** Default redirection */
Route::fallback(function () {
    return redirect()->back();
});

Route::get('/create-password', [HomeController::class, 'createPassword'])->name('create.password');
Route::get('/random-function', [CategorySupplierController::class, 'index'])->name('random.number');
Route::post('/update-password', [HomeController::class, 'updatePassword'])->name('update.password');