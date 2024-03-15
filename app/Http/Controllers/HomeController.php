<?php
    namespace App\Http\Controllers;

    use Validator;
    use App\Models\User;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Mail;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Console\Command;
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Crypt;

    class HomeController extends Controller
    {
        public function __construct()
        {
            $this->middleware('user.type')->only(['index', 'userview', 'UserRemove', 'UpdateUser', 'UpdateUserData']);
        }
    
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
            $userdata=User::where('user_type', '!=', User::USER_TYPE_SUPERADMIN)->orderBy('id','desc')->get();
            $formatuserdata=[];
            $i=1;
            foreach ($userdata as $data) {
                
                $formatuserdata[] = [
                    // $i, 
                    $data->first_name. ' ' .$data->last_name,
                    ($data->user_type == 3)? 'User':' Admin ',
                    '<button style="cursor:pointer" title="Edit User" class="btn btn-primary btn-xs updateuser " data-userid="' . $data->id . '" ><i class="fa-regular fa-pen-to-square"></i></button><button data-id="' . $data->id . '" class="btn btn-danger btn-xs remove" title="Remove User"><i class="fa-solid fa-trash"></i></button>',
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
                    'first_name' => $request->first_name,
                    // 'last_name' => $request->last_name,
                    'email' => $request->email,
                    // 'password' => $request->password,
                    // 'confirm_password' => $request->confirm_password,
                   
                    'user_role' => $request->user_role,
                ],
                [
                    'first_name'=>'required|string|max:255',
                    // 'last_name'=>'required|string|max:255',
                    'email' => 'required|string|email|max:255|unique:users',
                    // 'password' => 'required|string|min:8',
                    // 'confirm_password' => 'required|string|min:8|same:password',
                    
                    'user_role' => 'required',
                ]
            );
            if( $validator->fails() )
            {  
                return response()->json(['error' => $validator->errors()], 200);
        
            }
            else{
                try {
                
                    $userType = ($request->user_role == USER::USER_TYPE_ADMIN) ? USER::USER_TYPE_ADMIN : USER::USER_TYPE_USER;
                    $token=Str::random(40);
                    $user = User::create([
                        'first_name' => $request->first_name,
                        'last_name' => $request->last_name,
                        'email' => $request->email,
                       // 'password' => bcrypt($request->password), // Hash the password before saving
                        'remember_token' => $token,
                        'user_type'=> $userType,
                    ]);

                    $email=$request->email;
                    $key = 'base64:58UejRIrfFUBpHhIZ1uoxbP9iYHTNXpP2Glzx02Fgp8=';
                    $salt = openssl_random_pseudo_bytes(16); // Generate salt
                    $data = ''.$user->id . '|' . $user->remember_token.'';
                    // encryptData($data, $key, $salt);
                    // $truncatedEncryptedData =encrypt($user->id . '|' . $user->remember_token);
                    // $encryptedDataWithDelimiter = $truncatedEncryptedData . '|';
                    // $limitedEncryptedData = substr($encryptedDataWithDelimiter, 0, 20);
                    // dd($truncatedEncryptedData);


                    try{
                        Log::info('Attempting to send email...');

                        // Mail::send('mail.updatepassword', ['userid' => $user->id,'token' =>$user->remember_token], function($message) use ($email) {
                        //     $message->to($email)
                        //             ->subject('Password Creation Form');
                        // });
                        Mail::send('mail.updatepassword', ['data' => encryptData($data, $key, $salt)], function($message) use ($email) {
                                $message->to($email)
                                        ->subject('Password Creation Form');
                            });
                         
                        Log::info('Email sent successfully');
                    } catch (\Exception $e) {
                        /** Handle the exception here */
                        Log::error('Email sending failed: ' . $e->getMessage());
                        // $this->error('Email sending failed: ' . $e->getMessage());
                    }
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
                $email='vishustaple.in@gmail.com';
                $key = 'base64:58UejRIrfFUBpHhIZ1uoxbP9iYHTNXpP2Glzx02Fgp8=';
                $salt = openssl_random_pseudo_bytes(16); // Generate salt
                $data = $request->email;
                Log::info('new login attempt...');
                Mail::send('mail.newlogin', ['data' => $data], function($message) use ($email) {
                    $message->to($email)
                            ->subject('New User Login Notification');
                });
             
                Log::info('Email sent successfully');
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

        public function UpdateUser(Request $request)
        {
            
            $userId = $request->id;
            $editUserData = User::where('id',$userId)->first();
            
            if ($editUserData) {
            return response()->json(['success' => true, 'editUserData' => $editUserData]);
            } else {
            return response()->json(['error' => 'User not found'], 404);
            }

            
        }

        public function UpdateUserData(Request $request){

            $validator = Validator::make(
                [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    // 'password' => $request->password,
                    // 'confirm_password' => $request->confirm_password,
                    'user_role' => $request->update_user_role,
                ],
                [
                    'first_name'=>'required|string|max:255',
                    // 'last_name'=>'required|string|max:255',
                    'email' => 'required|string|email|max:255|unique:users,email,'.$request->update_user_id,
                    // 'password' => $request->password != null ? 'required|string|min:8' : '',
                    // // 'password' => 'required_if:password,filled|string|min:8',
                    // 'confirm_password' => $request->confirm_password != null ?'string|min:8|same:password':'',
                    'user_role' => 'required',
                ]
            );
            if( $validator->fails() )
            {  
                return response()->json(['error' => $validator->errors()], 200);
            
            }
            else{
                try {
                    $user = User::find($request->update_user_id);
                    
                    if($user){
                        $userType = ($request->update_user_role == 2) ? USER::USER_TYPE_ADMIN : USER::USER_TYPE_USER;

                        $user->update([
                            'first_name' => $request->first_name,
                            'last_name' => $request->last_name,
                            'email' => $request->email,
                            // 'password' => bcrypt($request->password), // Hash the password before saving
                            'user_type' => $userType,
                        ]);

                    }
                    return response()->json(['success' => 'Update User Successfully!'], 200);
                    
                } catch (\Exception $e) {
                    return response()->json(['error' => $e->getMessage()], 200);
                }
            }

        }

        public function UserRemove(Request $request)
        {
            
                $data = User::where('id',$request->id)->delete();
                return response()->json(['success' => true]);
        }

        public function createPassword(Request $request){
            $key = 'base64:58UejRIrfFUBpHhIZ1uoxbP9iYHTNXpP2Glzx02Fgp8=';
            $data = $request->input('data');
            $decryptedData=decryptData($data, $key);
            //  dd($pdata);
            // Decrypt the data
            // dd(decrypt('eyJpdiI6ImhJMXE3dHVL'));
            // $decryptedData = decrypt($data);
            [$userid, $token] = explode('|', $decryptedData);
            $dbtoken = User::select('remember_token')->where('id',$userid)->first();
             if($dbtoken->remember_token === null){
                 return view('admin.linkexpire');
                }
                else{
                 return view('admin.createpassword',compact('userid','token'));
             }
        }

        public function updatePassword(Request $request)
            {
 
     
                $validator = Validator::make(
                    [
                        'user_id' => 'required|exists:users,id',
                        'password' => $request->password,
                        'confirm_password' => $request->confirm_password,
                       
                    ],
                    [
                        'password' => 'required|string|min:8',
                        'confirm_password' => 'required|string|min:8|same:password',
               
                    ]
                );
                if( $validator->fails() )
                {  
                    return redirect()->back()->withErrors($validator)->withInput();
            
                }
                else{

                    // Find the user
                    $user = User::findOrFail($request->user_id);
                    if($user->user_type == User::USER_TYPE_SUPERADMIN){
                       
                        $user->update(['password' => bcrypt($request->password)]);
                        Log::info('Admin Password has been updated.');
                        return redirect()->back()->with('success', 'Your Password updated successfully.');

                    }
                     // Verify the token
                    if ($user->remember_token === $request->token) {
                        // Update the user's password
                        $user->password = bcrypt($request->password);
                        $user->save();
                        $user->update(['remember_token' => null]);

                        // Redirect to the login route
                        return redirect()->route('login')->with('success', 'Password updated successfully. Please log in with your new password.');
                    } else {
                        return view('admin.linkexpire');
                        // Token does not match, return error or redirect back
                        return redirect()->back()->with('error', 'Invalid token.');
                    }
                }
                
            }


           public function changePasswordView(){

              $adminUser= User::where('user_type',User::USER_TYPE_SUPERADMIN)->first();
              
              return view('admin.profile',compact('adminUser'));

           }  
    }
