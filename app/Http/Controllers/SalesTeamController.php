<?php

namespace App\Http\Controllers;

use League\Csv\Writer;
use App\Models\SalesTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesTeamController extends Controller
{
    public function __construct(){
        $this->middleware('permission:Sales Rep')->only(['index', 'salesAjaxFilter', 'editSales', 'updateSales', 'addsales', 'removeSales', 'status_sales', 'exportSaleCsv']);
    }

    public function index(Request $request){
        $saleId = $request->id;
        if (isset($saleId) && !empty($saleId)) {
            $salesData = SalesTeam::query() 
            ->where('id', $saleId)
            ->select('first_name', 'last_name', 'email', 'phone', 'status','team_user_type')->get()->toArray();
            return view('admin.viewdetail', compact('salesData'));
        }

        return view('admin.sales_repersantative.salesTeam', ['pageTitle' => 'Sales Team']);
    }
    
    
    public function salesAjaxFilter(Request $request){
        if ($request->ajax()) {
            $formatuserdata = SalesTeam::getFilterdSalesData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function editSales(Request $request){
        $salesId = $request->id;
        $editSalesData = SalesTeam::where('id', $salesId)->first();
        $frompageTitle = $request->routename;
        $currentpageTitle = 'Edit Sales Repersantative';
        return view('admin.sales_repersantative.edit',['fromTitle' => $frompageTitle,'currentTitle' => $currentpageTitle,'sales' => $editSalesData] );
    }

    public function updateSales(Request $request){
        $validator = Validator::make(
            [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone_number,
                'status' => $request->status,
                'user_type' => $request->user_type,

            ],
            [
                'first_name' => 'required|regex:/^[a-zA-Z0-9\s]+$/',
                'last_name' => 'required|regex:/^[a-zA-Z0-9\s]+$/',
                'email' => 'required|regex:/^\S+@\S+\.\S+$/|unique:sales_team,email,'.$request->id,
                'phone' => 'required|digits:10|unique:sales_team,phone,'.$request->id,
                'status' => 'required',
                'user_type' => 'required',
            ],
        );

        if( $validator->fails() ){  
            return response()->json(['error' => $validator->errors()], 200);
        }
      
        try {
            $sales = SalesTeam::find($request->id);

            if($sales){
                $sales->update([
                    'phone' => $request->phone_number,
                    'email' => $request->email,
                    'status' => $request->status,
                    'last_name' => $request->last_name,
                    'first_name' => $request->first_name,
                    'team_user_type' => $request->user_type,
                ]);
            }
            return response()->json(['success' => 'Sales Repersantative Updated Successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 200);
        }
    }

    public function addsales(Request $request){
        if ($request->ajax()) {            
            $validator = Validator::make(
                [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'phone' => $request->phone_number,
                    'status' => $request->status,
                    'user_type' => $request->user_type,
                ],
                [
                    'first_name' => 'required|regex:/^[a-zA-Z0-9\s]+$/',
                    'last_name' => 'required|regex:/^[a-zA-Z0-9\s]+$/',
                    'email' => 'required|regex:/^\S+@\S+\.\S+$/|required|email|unique:sales_team,email',
                    'phone' => 'required|digits:10|unique:sales_team',
                    'status' => 'required',
                    'user_type' => 'required',
                ],
            );
    
            if( $validator->fails() ){  
                return response()->json(['error' => $validator->errors()], 200);
            }
    
            try{
                SalesTeam::create([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'phone' => $request->phone_number,
                    'status' => $request->status,
                    'team_user_type' => $request->user_type,
                ]);
    
                return response()->json(['success' => 'Sales Repersantative Added Successfully'], 200);
            } catch (QueryException $e) {   
                return response()->json(['error' => $e->getMessage()], 200);
            }
        } else {
            $fromTitle = 'SalesTeam';
            $currentTitle ='Sales Team';
            return view('admin.sales_repersantative.add',compact('fromTitle','currentTitle'));
        }
    }

    public function removeSales(Request $request){
        $saleId = $request->id;
        $sale = SalesTeam::find($saleId);
        if($sale) {
            $sale->delete();
            return response()->json(['success' => 'Sales Repersantative deleted successfully']);
        } else {
            return response()->json(['error' => 'Sales Repersantative not found'], 404);
        }
    }

    public function status_sales(Request $request){
        try{
            $getstatus = SalesTeam::find($request->id); 
            $status = ($getstatus->status == SalesTeam::STATUS_ACTIVE) ? SalesTeam::STATUS_INACTIVE : SalesTeam::STATUS_ACTIVE;
            $data = SalesTeam::where('id', $request->id)->update([
                'status' => $status
            ]);

            if ($data) {
                return response()->json(['success' => 'Status updated successfully']);
            } else {
                return response()->json(['error' => 'Failed to update status'], 500);
            }
            }
        catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function exportSaleCsv(Request $request){
        /** Retrieve data based on the provided parameters */
        $filter['search']['value'] = $request->query('search');
        $csv = true;

        /** Fetch data using the parameters and transform it into CSV format */
        /** Replace this with your actual data fetching logic */
        $data = SalesTeam::getFilterdSalesData($filter, $csv);

        /** Create a stream for output */
        $stream = fopen('php://temp', 'w+');

        /** Create a new CSV writer instance */
        $csvWriter = Writer::createFromStream($stream);

        $heading = $data['heading'];
        unset($data['heading']);

        /** Add column headings */
        $csvWriter->insertOne($heading);
        
        /** Insert the data into the CSV */
        $csvWriter->insertAll($data);

        /** Rewind the stream pointer */
        rewind($stream);

        /** Create a streamed response with the CSV data */
        $response = new StreamedResponse(function () use ($stream) {
            fpassthru($stream);
        });

        /** Set headers for CSV download */
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="SalesTeamData_'.now()->format('YmdHis').'.csv"');
  
        /** return $csvResponse; */
        return $response;
    }
}
