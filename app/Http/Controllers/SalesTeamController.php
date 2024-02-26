<?php

namespace App\Http\Controllers;

use DB;
use Validator;
use App\Models\SalesTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesTeamController extends Controller
{
    public function index(){
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
            ],
            [
                'first_name' => 'required|regex:/^[a-zA-Z0-9\s]+$/',
                'last_name' => 'required|regex:/^[a-zA-Z0-9\s]+$/',
                'email' => 'required|regex:/^\S+@\S+\.\S+$/',
                'phone' => 'required',
                'status' => 'required',
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
                ]);

            }
            return response()->json(['success' => 'Sales Repersantative Update Successfully'], 200);
           
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 200);
        }
    }

    public function addsales(Request $request){
        $validator = Validator::make(
            [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone_number,
                'status' => $request->status,
            ],
            [
                'first_name' => 'required|regex:/^[a-zA-Z0-9\s]+$/',
                'last_name' => 'required|regex:/^[a-zA-Z0-9\s]+$/',
                'email' => 'required|regex:/^\S+@\S+\.\S+$/',
                'phone' => 'required',
                'status' => 'required',
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
            ]);

            return response()->json(['success' => 'Add Sales Repersantative Successfully'], 200);
        } catch (QueryException $e) {   
            return response()->json(['error' => $e->getMessage()], 200);
        }
    }
}
