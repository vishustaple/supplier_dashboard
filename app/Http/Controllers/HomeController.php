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
    public function userRegister(Request $request)
    {
       
        $validator = Validator::make(
            [
                'first_name'      => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => $request->password,
                'confirm_password' => $request->confirm_password,
            ],
            [
                'first_name'=>'required|string|max:255',
                'last_name'=>'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'confirm_password' => 'required|string|min:8|same:password',
            ]
        );
        if( $validator->fails() )
        {  
            
            return view('auth.register')->withErrors($validator); 
            
        }
        else{

            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => bcrypt($request->password), // Hash the password before saving
                'user_type'=> USER::USER_TYPE,
            ]);
            // Validation passed, so set a success message
            session()->flash('success_message', 'Registration successful! Please log in.');

            // Redirect to the login view
            return view('auth.login');
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
