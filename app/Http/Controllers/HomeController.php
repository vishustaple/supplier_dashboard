<?php
    namespace App\Http\Controllers;

    use App\Models\{User, Permission};
    use Illuminate\Support\Str;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Mail;
    use Illuminate\Support\Facades\Crypt;
    use Illuminate\Support\Facades\Validator;
    use Illuminate\Support\Facades\Http;


    class HomeController extends Controller
    {
        public function __construct(){
            // $this->middleware('user.type')->only(['index', 'userview', 'UserRemove', 'UpdateUser', 'UpdateUserData']);
            $this->middleware('permission:Manage Users')->only(['userview', 'UserRemove', 'UpdateUser', 'UpdateUserData']);
        }
    
        /**
         * Show the application dashboard.
         *
         * @return \Illuminate\Contracts\Support\Renderable
         */
        public function index(){
            return view('admin.index');
        }

        public function register(){
            return view('auth.register');
        }

        public function showPowerBi(){
            $pageTitle = 'Power Bi';
            $data = DB::table('show_power_bi')->select('id', 'title', 'iframe')->get();
            return view('admin.power_bi', compact('pageTitle', 'data'));
        }

        public function userview(){    
            $userdata=User::where('user_type', '!=', User::USER_TYPE_SUPERADMIN)->orderBy('id','desc')->get();
            $formatuserdata=[];
            $i=1;
            $userInfo = Auth::user();
            foreach ($userdata as $data) {
                $formatuserdata[] = [
                    $data->first_name. ' ' .$data->last_name,
                    ($data->user_type == 3)? 'User':' Admin ',
                    (($data->user_type != 2 && $userInfo->user_type == 2) || $userInfo->user_type == 1 || (!in_array($data->user_type, [2, 3]) && $userInfo->user_type == 3)) ? ('<button style="cursor:pointer" title="Edit User" class="btn btn-primary btn-xs updateuser" data-userid="' . Crypt::encryptString($data->id) . '"><i class="fa-regular fa-pen-to-square"></i></button><button data-id="' . Crypt::encryptString($data->id) . '" class="btn btn-danger btn-xs remove" title="Remove User"><i class="fa-solid fa-trash"></i></button>') : (''),
                ];
                $i++;
            }

            /** Get all permissions */
            $permissions = Permission::all();
            $data = json_encode($formatuserdata);
            return view('admin.user',compact('data', 'permissions', 'userInfo'));
        }

        public function editPermissions($userId){
            /** Find the user by ID */
            $user = User::with('permissions')->findOrFail(Crypt::decryptString($userId));

            /** Get all permissions */
            $permissions = Permission::all();

            /** Return user and permissions data as JSON response */
            return response()->json([
                'user' => $user,
                'permissions' => $permissions
            ]);
        }
        
        public function userRegister(Request $request){   
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'user_role' => 'required',
            ]);

            if ($validator->fails()) {  
                return response()->json(['error' => $validator->errors()], 200);
            } else {
                try {
                    $user1 = Auth::user();
                    $userType = ($request->user_role == USER::USER_TYPE_ADMIN) ? USER::USER_TYPE_ADMIN : USER::USER_TYPE_USER;
                    if (($userType != 2 && $user1->user_type == 2) || $user1->user_type == 1 || (!in_array($userType, [2, 3]) && $user1->user_type == 3)) {
                        $token = Str::random(40);
                        $user = User::create([
                            'first_name' => $request->first_name,
                            'last_name' => $request->last_name,
                            'email' => $request->email,
                            'remember_token' => $token,
                            'user_type'=> $userType,
                        ]);

                        /** Sync permissions for the user */
                        $user->permissions()->sync($request->input('permissions'));

                        $email=$request->email;
                        $key = env('APP_KEY');
                        $salt = openssl_random_pseudo_bytes(16); /** Generate salt */
                        $data = ''.$user->id . '|' . $user->remember_token.'';

                        try{
                            Log::info('Attempting to send email...');
                            Mail::send('mail.updatepassword', ['data' => encryptData($data, $key, $salt)], function($message) use ($email) {
                                    $message->to($email)
                                            ->subject('Password Creation Form');
                                });
                            
                            Log::info('Email sent successfully');
                        } catch (\Exception $e) {
                            /** Handle the exception here */
                            Log::error('Email sending failed: ' . $e->getMessage());
                        }
                        return response()->json(['success' => 'Add User Successfully!'], 200);
                    } else {
                        return response()->json(['error' => 'You do not have permission to add user'], 200);    
                    }
                } catch (\Exception $e) {
                    return response()->json(['error' => $e->getMessage()], 200);
                }
            }
        }
        
        public function userLogin(Request $request){
            $request->validate([
                "email" => "required|email",
                "password" => "required",
            ]);
            
            $credentials = $request->only('email', 'password');
            $remember = $request->has('remember');

            if (Auth::attempt($credentials, $remember)) {
                Auth::logoutOtherDevices($request->password);
                /** Connection could not be established with host "mailpit:1025": stream_socket_client(): php_network_getaddresses: getaddrinfo for mailpit failed: Name or service not known */
                /** Log::info('Email sent successfully'); */
                return redirect()->intended('/admin/upload-sheet');
            } else {
                /** Authentication failed... */
                return redirect()->route('login')->withErrors(['email' => 'Invalid credentials']);
            }
        }

        public function userLogout(){
            Auth::logout();
            return redirect('/');
        }

        public function UpdateUser(Request $request){
            $userId = $request->id;
            $editUserData = User::where('id', Crypt::decryptString($userId))->first()->toArray();
            $editUserData['id'] = Crypt::encryptString($editUserData['id']);
            if ($editUserData) {
                return response()->json(['success' => true, 'editUserData' => $editUserData]);
            } else {
                return response()->json(['error' => 'User not found'], 404);
            }
        }
    
        public function UpdateUserData(Request $request){
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . Crypt::decryptString($request->update_user_id),
                'update_user_role' => 'required',
            ]);
            
            if ($validator->fails()) {  
                return response()->json(['error' => $validator->errors()], 200);
            } else {
                try {
                    $user = User::find(Crypt::decryptString($request->update_user_id));
                    $user1 = Auth::user();
                    if ($user) {
                        $userType = ($request->update_user_role == 2) ? USER::USER_TYPE_ADMIN : USER::USER_TYPE_USER;
                        if (($userType != 2 && $user1->user_type == 2) || $user1->user_type == 1 || (!in_array($userType, [2, 3]) && $user1->user_type == 3)) {
                            $user->update([
                                'first_name' => $request->first_name,
                                'last_name' => $request->last_name,
                                'email' => $request->email,
                                'user_type' => $userType,
                            ]);

                            /** Sync user permissions */
                            $user->permissions()->sync($request->input('permissions'));
                        } else {
                            return response()->json(['error' => 'You do not have permission to update this user'], 200);
                        }
                    }

                    return response()->json(['success' => 'Update User Successfully!'], 200);
                } catch (\Exception $e) {
                    return response()->json(['error' => $e->getMessage()], 200);
                }
            }
        }

        public function UserRemove(Request $request){
            $user = User::find(Crypt::decryptString($request->id));
            $user1 = Auth::user();
            if (($user->user_type != 2 && $user1->user_type == 2) || $user1->user_type == 1 || (!in_array($user->user_type, [2, 3]) && $user1->user_type == 3)) {
                /** Disable foreign key checks */
                DB::statement('SET FOREIGN_KEY_CHECKS=0');

                DB::table('users')->where('id', Crypt::decryptString($request->id))->delete();

                /** Re-enable foreign key checks */
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
                return response()->json(['success' => true]);
            } else {
                return response()->json(['success' => false]);
            }
        }

        public function createPassword(Request $request){
            $key = env('APP_KEY');
            $data = $request->input('data');
            if ($data === null) {
                $message = 'Create Password link is invalid';
                return view('admin.linkexpire',compact('message'));
            }

            $decryptedData = decryptData($data, $key);

            if (count(explode('|', $decryptedData)) <= 1) {
                $message = 'Create Password link is invalid';
                return view('admin.linkexpire',compact('message'));
            }

            [$userid, $token] = explode('|', $decryptedData);
            $dbtoken = User::select('remember_token')->where('id',$userid)->first();
            if ($dbtoken->remember_token === null) {
                return view('admin.linkexpire');
            } else {
                return view('admin.createpassword',compact('userid','token'));
            }
        }

        public function updatePassword(Request $request){
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

            if ($validator->fails()) {  
                return redirect()->back()->withErrors($validator)->withInput();
            } else {
                /** Find the user */
                $user = User::findOrFail($request->user_id);
                if ($user->user_type == User::USER_TYPE_SUPERADMIN) {
                    $user->update(['password' => bcrypt($request->password)]);
                    Log::info('Admin Password has been updated.');
                    return redirect()->back()->with('success', 'Your Password updated successfully.');
                }
                /** Verify the token */
                
                if ($user->remember_token === $request->token) {
                    /** Update the user's password */
                    $user->password = bcrypt($request->password);
                    $user->save();
                    $user->update(['remember_token' => null]);

                    /** Redirect to the login route */
                    return redirect()->route('login')->with('success', 'Password updated successfully. Please log in with your new password.');
                } else {
                    return view('admin.linkexpire');
                    /** Token does not match, return error or redirect back */
                    return redirect()->back()->with('error', 'Invalid token.');
                }
            }
        }

        public function changePasswordView(){
            $adminUser= User::where('user_type',User::USER_TYPE_SUPERADMIN)->first();
            return view('admin.profile',compact('adminUser'));
        }  

        public function userForgetPassword(Request $request){
            return view('auth.forget_password'); 
        }

        public function userResetPassword(Request $request){
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email|max:255',
            ]);

            if ($validator->fails()) {  
                session()->flash('error', 'The email field is required.');
                return redirect()->route('user.forget');
            } else {
                try {
                    $user = DB::table('users')->where('email', trim($request->input('email')))->first();
                    if (isset($user->email) && !empty($user->email)) {
                        $token = Str::random(40);
                        $a = DB::table('users')
                        ->where('id', $user->id)
                        ->update(['remember_token' => $token]);

                        $email = $request->email;
                        $key = env('APP_KEY');
                        $salt = openssl_random_pseudo_bytes(16); /** Generate salt */
                        $data = ''.$user->id . '|' . $token.'';
                    
                        try{
                            Log::info('Attempting to send email...');
                            Mail::send('mail.updatepassword', ['data' => encryptData($data, $key, $salt)], function($message) use ($email) {
                                    $message->to($email)
                                            ->subject('Forget Password Form');
                                });
                            
                            Log::info('Email sent successfully');
                        } catch (\Exception $e) {
                            /** Handle the exception here */
                            Log::error('Email sending failed: ' . $e->getMessage());
                        }

                        /** Set the success message here */
                        session()->flash('success', 'Password reset link sended on your email.');
                    } else {
                        session()->flash('error', 'Please enter valid account email.');
                    }
                } catch (\Exception $e) {
                    session()->flash('error', $e->getMessage());
                }
            }

            return redirect()->route('user.forget');
        }

        public function powerBiAdd(Request $request){
            try {
                DB::table('show_power_bi')
                ->insert([
                    'title' => $request->input('title'),
                    'iframe' => $request->input('iframe'),
                ]);

                return redirect()->route('power_bi.show');
            } catch (\Exception $e) {
                Log::error('error', $e->getMessage());
            }
        }

        public function powerBiEdit(Request $request){
            try {
                DB::table('show_power_bi')
                ->where('id', $request->input('id'))
                ->update([
                    'title' => $request->input('titles'),
                    'iframe' => $request->input('iframes'),
                ]);

                return redirect()->route('power_bi.show');
            } catch (\Exception $e) {
                Log::error('error', $e->getMessage());
            }
        }

        public function powerBiDelete($id){
            try {
                DB::table('show_power_bi')->where('id', $id)->delete();
                return redirect()->route('power_bi.show');
            } catch (\Exception $e) {
                Log::error('error', $e->getMessage());
            }
        }

        public function powerBiReport(Request $request){
            if ($request->ajax()) {
                $record =  DB::table('show_power_bi')->select('id', 'title')->get();
                if ($record->isNotEmpty()) {
                    $titles = $record->pluck('title')->toArray();

                    $pageTitleCheck = $request->input('pageTitleCheck');
    
                    /** Generate the HTML content */
                    $data = '
                    <a class="nav-link ' . ((isset($pageTitleCheck) && in_array($pageTitleCheck, $titles)) ? 'active' : '') . '" data-toggle="collapse" href="#subMenuPowerBi">
                        <div class="sb-nav-link-icon"><i class="fa fa-th-list" aria-hidden="true"></i></div>
                        PowerBi Reports<i class="fas fa-caret-down"></i>
                    </a>
                    <div class="collapse ' . ((isset($pageTitleCheck) && in_array($pageTitleCheck, $titles)) ? 'show' : '') . '" id="subMenuPowerBi">';
    
                    foreach ($record as $key => $value) {
                        $data .= '
                        <a class="nav-link ml-3 ' . ((isset($pageTitleCheck) && $pageTitleCheck == $value->title) ? 'active' : '') . '" href="' . route('powerbi_report.type', ['id' => $value->id, 'reportType' => $value->title]) . '">' . htmlspecialchars($value->title, ENT_QUOTES, 'UTF-8') . '</a>';
                    }
    
                    $data .= '</div>';
    
                    return response()->json([
                        'success' => true,
                        'data' => $data,
                    ]);
                } else {
                    return response()->json([
                        'success' => true,
                        'data' => '',
                    ]);
                }
            }
        }

        public function powerBiReportViewRender($id, $reportType) {
            $data = DB::table('show_power_bi')->select('title', 'iframe')->where('id', $id)->first();

            if ($data) {
                return view('admin.powerbi_report', ['pageTitle' => $reportType, 'data' => $data]);
            }
        }

        // value=fV~8Q~l9Ui1KgzANhK8rZ9XTq6_NtagfddXmya5a
        // secrateid=db4bd365-58d7-45ab-b6de-7afe4ff0daa4
        // public function redirectToProvider(){
        //     $response = Http::asForm()->post('https://login.microsoftonline.com/'.env('AZURE_AD_TENANT_ID').'/oauth2/v2.0/token', [
        //         'client_id' => env('AZURE_AD_CLIENT_ID'),
        //         'client_secret' => env('AZURE_AD_CLIENT_SECRET'),
        //         'grant_type' => 'client_credentials',
        //         'scope' => env('AZURE_AD_SCOPE'),
        //     ]);
            
        //     $accessToken = $response->json()['access_token'];
        //     dd($accessToken);
        //     // Your Power BI API endpoint
        //     $apiUrl = 'https://api.powerbi.com/v1.0/myorg/reports/a43b8268-0440-4e69-b90c-69aaf1a16b63';

        //     // Make the API request using Laravel's HTTP client
        //     $response = Http::withHeaders([
        //         'Authorization' => "Bearer $accessToken",
        //         'Content-Type' => 'application/json'
        //     ])->get($apiUrl);

        //     // Check the response status
        //     if ($response->successful()) {
        //         // Successful response
        //         return response()->json([
        //             'status' => 'success',
        //             'data' => $response->json()
        //         ]);
        //     } elseif ($response->status() == 401) {
        //         // Unauthorized - token might be invalid or expired
        //         return response()->json([
        //             'status' => 'error',
        //             'message' => 'Unauthorized - Invalid or expired token.'
        //         ], 401);
        //     } elseif ($response->status() == 403) {
        //         // Forbidden - Token does not have permissions
        //         return response()->json([
        //             'status' => 'error',
        //             'message' => 'Forbidden - Insufficient permissions.'
        //         ], 403);
        //     } else {
        //         // Other errors
        //         return response()->json([
        //             'status' => 'error',
        //             'message' => 'HTTP Status Code ' . $response->status()
        //         ], $response->status());
        //     }
        // }
    }
