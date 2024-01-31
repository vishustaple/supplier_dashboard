<?php

namespace App\Http\Controllers;
use Validator;
use App\Models\Account;
use Illuminate\Http\Request;
use App\Rules\AtLeastOneChecked;

class AccountController extends Controller
{
    public function addAccount(Request $request){

    //   dd($request->all());
        $validator = Validator::make(
            [
                'customer_id'=> $request->customer_id,
                'customer_name' => $request->input('customer_name'),
                // 'parent'=> $request->parent,
                // 'grandparent'=>$request->grandparent,


            ],
            [
                'customer_id'=>'required',
                'customer_name'=>'required|regex:/^[a-zA-Z0-9\s]+$/',
                // 'parent' => 'nullable',
                // 'grandparentselect' => 'nullable|required_if:parent,1'

            ],
          
             );

        if( $validator->fails() ){  
           
            return response()->json(['error' => $validator->errors()], 200);
        }
        if($request->parent){
            if(empty($request->grandparentSelect)){
            return response()->json(['error' => "The GrandParent Field is Required."], 200);
            }
        }
        try{
            Account::create([
                'customer_number' => $request->customer_id,
                'customer_name' => $request->customer_name,
                'parent_id' => $request->input('grandparentselect') ?? null,
                'created_by'=>'1',
            ]);
            return response()->json(['success' => 'Add account Successfully!'], 200);

        } catch (QueryException $e) {   
            return response()->json(['error' => $e->getMessage()], 200);
        
        }
    }

    public function getParent(Request $request){
        dd($request->all());
        dd("here");
    }
}
