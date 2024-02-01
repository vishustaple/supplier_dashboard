<?php

namespace App\Http\Controllers;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
     /**
     * Create a new controller instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }
 
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('admin.index');
    }
    public function register()
    {
        return view('auth.register');
    }
    public function userview()
    {    
        $userdata=User::select('first_name','last_name','user_type')->where('user_type', '!=', User::USER_TYPE_ADMIN)->orderBy('id','desc')->get();
        $formatuserdata=[];
        $i=1;
        foreach ($userdata as $data) {
            
            $formatuserdata[] = [
                $i, 
                $data->first_name. ' ' .$data->last_name,
                ($data->user_type == 3)? 'Role User':'Role Admin ',
            ];
            $i++;
        }
        $data=json_encode($formatuserdata);
        return view('admin.user',compact('data'));
    }
    
    public function userRegister(Request $request)
    {
          $validator = Validator::make(
            [
                'first_name'      => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => $request->password,
                'confirm_password' => $request->confirm_password,
                'user_role' => $request->user_role,
            ],
            [
                'first_name'=>'required|string|max:255',
                'last_name'=>'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'confirm_password' => 'required|string|min:8|same:password',
                'user_role' => 'required',
            ]
        );
        if( $validator->fails() )
        {  
            return response()->json(['error' => $validator->errors()], 200);
     
        }
        else{
            try {
               
                $userType = ($request->user_role == 2) ? USER::USER_TYPE_ADMIN : USER::USER_TYPE_USER;

                $user = User::create([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'password' => bcrypt($request->password), // Hash the password before saving
                    'user_type'=> $userType,
                ]);
            
                return response()->json(['success' => 'Add User Successfully!'], 200);
               
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 200);
            }
        }

    }
    
        public function userLogin(Request $request)
    {
        $request->validate([
            "email" => "required|email",
            "password" => "required",
        ]);
        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            Auth::logoutOtherDevices($request->password);
            // Authentication passed...
            return redirect()->intended('/admin/upload-sheet');
        } else {
            
            // Authentication failed...
            return redirect()->route('login')->withErrors(['email' => 'Invalid credentials']);
        }
    }

        public function userLogout()
        {
            Auth::logout();

            return redirect('/');
        }
}
