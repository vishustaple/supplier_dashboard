<?php

namespace App\Http\Controllers;
use Validator;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function addAccount(Request $request){

      
        $validator = Validator::make(
            [
                'customer_id'=> $request->customer_id,
                'customer_name' => $request->input('customer_name'),

            ],
            [
                'customer_id'=>'required',
                'customer_name'=>'required|regex:/^[a-zA-Z0-9\s]+$/',

            ]
            
        );
        if( $validator->fails() ){  
           
            return response()->json(['error' => $validator->errors()], 200);
        }

        try{
            Account::create([
                'customer_number' => $request->customer_id,
                'customer_name' => $request->customer_name,
                'parent_id' => $request->input('grandparentselect') ?? null,
                'created_by'=>'1',
            ]);
            return response()->json(['success' => 'Add account!'], 200);

        } catch (QueryException $e) {   
            return response()->json(['error' => $e->getMessage()], 200);
            // return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
