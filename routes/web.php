<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    SavedQueryController,
    ExcelImportController,
    SalesTeamController,
    RebateController,
    CommissionController,
    CatalogController,
    ReportController,
    HomeController,
    CategorySupplierController,
    AccountController
};

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
Route::get('/microsoft',[HomeController::class, 'microsoft'])->name('microsoft');
Route::get('/microsoft/profile',[HomeController::class, 'profile']);

Route::get('/register', [HomeController::class, 'register'])->name('register');
Route::post('/user-login', [HomeController::class, 'userLogin'])->name('user.login');
Route::get('/user-logout', [HomeController::class, 'userLogout'])->name('user.logout');
Route::get('/user-forget', [HomeController::class, 'userForgetPassword'])->name('user.forget');
Route::post('/user-reset', [HomeController::class, 'userResetPassword'])->name('user.reset');
Route::post('/user-register', [HomeController::class, 'userRegister'])->name('user.register');
  


Route::group(['prefix' => 'admin'], function () {
    Route::middleware(['auth'])->group(function () {
      
        Route::get('/queries', [SavedQueryController::class, 'index'])->name('queries.index');
        Route::get('/queries/create', [SavedQueryController::class, 'create'])->name('queries.create');
        Route::post('/queries', [SavedQueryController::class, 'store'])->name('queries.store');
        Route::get('/queries/{query}', [SavedQueryController::class, 'show'])->name('queries.show');
        Route::get('/queries/{query}/edit', [SavedQueryController::class, 'edit'])->name('queries.edit');
        Route::put('/queries/{query}', [SavedQueryController::class, 'update'])->name('queries.update');
        Route::delete('/queries/delete/{query}', [SavedQueryController::class, 'destroy'])->name('queries.destroy');
        

        Route::get('/dashboard', [HomeController::class, 'index'])->name('home');
        Route::get('/supplier', [ExcelImportController::class, 'allSupplier'])->name('supplier');
        Route::get('/supplier/{id?}', [ExcelImportController::class, 'showSupplier'])->name('supplier.show');
        Route::post('/suppliers/edit', [ExcelImportController::class, 'editSupplierName'])->name('supplier.edit');
        Route::post('/suppliers/add', [ExcelImportController::class, 'addSupplierName'])->name('supplier.add');
        Route::post('/suppliers/delete/supplier/detail', [ExcelImportController::class, 'deleteSupplier'])->name('supplierDetail.delete');
        Route::post('/supplier/show/edit', [ExcelImportController::class, 'editSupplierShowHide'])->name('supplier_show.update');
        Route::post('/supplier/add', [ExcelImportController::class, 'supplierAdd'])->name('suppliers.add');
        Route::post('/supplier/update', [ExcelImportController::class, 'supplierUpdate'])->name('suppliers.update');
        Route::post('/show/supplier/filter', [ExcelImportController::class, 'ShowAllSupplier'])->name('supplier_ajax.filter');
        Route::post('/import/supplier/file', [ExcelImportController::class, 'supplierFileFormatImport'])->name('import.supplier_file');
        Route::post('/add/supplier/file', [ExcelImportController::class, 'addSupplierFileFormatImport'])->name('add.supplier_file');
        Route::post('/edit/supplier/file', [ExcelImportController::class, 'editSupplierFileFormatImport'])->name('edit.supplier_file');
        Route::get('/delete/supplier/file', [ExcelImportController::class, 'removeSupplierFileFormatImport'])->name('remove.file_format');

        Route::post('/suppliers/updatemain', [ExcelImportController::class, 'addSupplierMain'])->name('main.update');
        Route::post('/supplier/details', [ExcelImportController::class, 'getSupplierDetailWithAjax'])->name('supplier_detail_filter');
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
        Route::post('/account/detail', [AccountController::class, 'getAccountsDetailWithAjax'])->name('account.detail');
        Route::get('/accounts/remove', [AccountController::class, 'removeAccount'])->name('account.remove');
        Route::post('/accounts/update', [AccountController::class, 'updateAccount'])->name('account.update');
        Route::get('/accounts/csv', [AccountController::class, 'exportAccountCsv'])->name('account.export-csv');
        Route::post('/account/filter', [AccountController::class, 'getAccountsWithAjax'])->name('account.filter');
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
        Route::post('/reports/commissionss/paid', [ReportController::class, 'paidUpdate'])->name('paid.update');
        Route::post('/reports/commissionss/approve', [ReportController::class, 'approvedUpdate'])->name('approved.update');
        Route::post('/reports/supplier-filter', [ReportController::class, 'supplierReportFilter'])->name('report.supplier_filter');
        Route::get('/reports/supplier-csv', [ReportController::class, 'supplierReportExportCsv'])->name('report.export-supplier_report-csv');
        Route::get('/reports/commissions-csv', [ReportController::class, 'downloadSampleCommissionFile'])->name('report.export-commission_report-csv');
        Route::post('/reports/commissions-report-filter', [ReportController::class, 'commissionReportFilter'])->name('report.commission_report_filter');
        Route::post('/reports/commissionss-report-filter', [ReportController::class, 'getCommissionsWithAjax'])->name('report.commission_report_filter_secound');
        Route::post('/report/consolidated/filter', [ReportController::class, 'consolidatedReportFilter'])->name('consolidated-report.filter');
        Route::get('/reports/consolidated/csv', [ReportController::class, 'exportConsolidatedCsv'])->name('consolidated-report.export-csv');        
        Route::post('/reports/consolidated/download', [ReportController::class, 'exportConsolidatedDownload'])->name('consolidated-report.download');        
        /** Report Section End */
    
        //not in use now this route     
        Route::get('/userlist', [HomeController::class, 'userview'])->name('user.show');
        Route::get('/powerbi', [HomeController::class, 'showPowerBi'])->name('power_bi.show');
        Route::post('/powerbi/add', [HomeController::class, 'powerBiAdd'])->name('powerbi.add');
        Route::post('/powerbi/edit', [HomeController::class, 'powerBiEdit'])->name('powerbi.update');
        Route::get('/powerbi/report', [HomeController::class, 'powerBiReport'])->name('powerbi.report');
        Route::get('/powerbi/delete/{id?}', [HomeController::class, 'powerBiDelete'])->name('powerbi.delete');
        Route::get('/power-bi/reports/{id?}/{reportType?}', [HomeController::class, 'powerBiReportViewRender'])->name('powerbi_report.type');

        Route::get('/remove', [HomeController::class, 'UserRemove'])->name('user.remove');
        Route::get('/updateuser', [HomeController::class, 'UpdateUser'])->name('user.updateuser');
        Route::post('/updateuserdata', [HomeController::class, 'UpdateUserData'])->name('user.updateuserdata');
        Route::get('/user/{userId}/edit-permissions', [HomeController::class, 'editPermissions'])->name('user.editPermissions');

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
        Route::post('/commissionss/add', [CommissionController::class, 'commissionAdd'])->name('commissions.add');
        Route::post('/commissionss/edit', [CommissionController::class, 'editCommission'])->name('commissions.edit');
        Route::get('/commissionss/csv', [CommissionController::class, 'exportCatalogCsv'])->name('commissions.export-csv');
        Route::get('/commissions/{commissionType}/{id?}', [CommissionController::class, 'index'])->name('commissions.list');
        Route::post('/commissionss/filter', [CommissionController::class, 'commissionAjaxFilter'])->name('commissions.filter');
        Route::get('/commissionss/view-add', [CommissionController::class, 'commissionAddView'])->name('commissions.add-view');
        Route::get('/commissionss/customer-search', [CommissionController::class, 'commissionAjaxCustomerSearch'])->name('commissions.customerSearch');
        Route::get('/commissionss/supplier-search', [CommissionController::class, 'commissionAjaxSupplierSearch'])->name('commissions.supplierSearch');
      
        /** Commission Section End */

        Route::post('/report/operational-anomaly/filter', [ReportController::class, 'operationalAnomalyReportFilter'])->name('report.operational_anomaly_report');
        Route::get('/reports/operational-anomaly/csv', [ReportController::class, 'operationalAnomalyReportExportCsv'])->name('operational-anomaly-report.export-csv');
    });
});

/** Default redirection */
// Route::fallback(function () {
//     return redirect()->back();
// });

Route::get('/create-password', [HomeController::class, 'createPassword'])->name('create.password');
Route::get('/random-function', [CategorySupplierController::class, 'index'])->name('random.number');
Route::post('/update-password', [HomeController::class, 'updatePassword'])->name('update.password');