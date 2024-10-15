<?php
    namespace App\Http\Controllers;

    use Exception;
    use Illuminate\Support\Str;
    use Illuminate\Http\Request;
    use App\Models\{User, Permission};
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Mail;
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Crypt;
    use Illuminate\Support\Facades\Session;
    use Illuminate\Database\QueryException;
    use Illuminate\Support\Facades\Validator;


    class HomeController extends Controller
    {
        public function __construct() {
            $this->middleware('permission:Manage Users')->only(['userview', 'UserRemove', 'UpdateUser', 'UpdateUserData']);
            $this->middleware('permission:Manage Power BI Reports')->only(['showPowerBi', 'powerBiAdd', 'powerBiEdit', 'powerBiDelete', '']);
        }
    
        /**
         * Show the application dashboard.
         *
         * @return \Illuminate\Contracts\Support\Renderable
         */
        public function register() {
            return view('auth.register');
        }

        public function showPowerBi() {
            $pageTitle = 'Power Bi';
            $data = DB::table('show_power_bi')->select('id', 'title', 'iframe', 'deleted')->get();
            return view('admin.power_bi', compact('pageTitle', 'data'));
        }

        public function userview() {    
            $userdata = User::where('user_type', '!=', User::USER_TYPE_SUPERADMIN)->orderBy('id','desc')->get();
            $formatuserdata = [];
            $i = 1;
            $userInfo = Auth::user();
            foreach ($userdata as $data) {
                $formatuserdata[] = [
                    $data->first_name. ' ' .$data->last_name,
                    ($data->user_type == 3) ? 'User' : 'Admin',
                    ($data->status == 1) ? 'Active' : 'In-Active',
                    (($data->user_type != 2 && $userInfo->user_type == 2) || $userInfo->user_type == 1 || (!in_array($data->user_type, [2, 3]) && $userInfo->user_type == 3)) ? ('<button style="cursor:pointer" title="Edit User" class="btn btn-primary btn-xs updateuser" data-userid="' . Crypt::encryptString($data->id) . '"><i class="fa-regular fa-pen-to-square"></i></button><button data-id="' . Crypt::encryptString($data->id) . '" class="btn btn-danger btn-xs remove" title="Remove User"><i class="fa-solid fa-trash"></i></button>') : (''),
                ];
                $i++;
            }

            /** Get all permissions */
            $permissions = Permission::all();
            $data = json_encode($formatuserdata);
            return view('admin.user',compact('data', 'permissions', 'userInfo'));
        }

        public function editPermissions($userId) {
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
        
        public function userRegister(Request $request) {   
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'user_role' => 'required',
                // 'user_status' => 'required',
            ]);

            if ($validator->fails()) {  
                return response()->json(['error' => $validator->errors()], 200);
            } else {
                try {
                    $user1 = Auth::user();
                    $userType = ($request->user_role == USER::USER_TYPE_ADMIN) ? USER::USER_TYPE_ADMIN : USER::USER_TYPE_USER;
                    if (($userType != 2 && $user1->user_type == 2) || $user1->user_type == 1 || (!in_array($userType, [2, 3]) && $user1->user_type == 3)) {
                        $token = Str::random(40);
                        if ($user1->user_type == 1) {
                            $user = User::create([
                                'user_type' => $userType,
                                'email' => $request->email,
                                'remember_token' => $token,
                                'status' => $request->user_status,
                                'last_name' => $request->last_name,
                                'first_name' => $request->first_name,
                            ]);
                        } else { 
                            $user = User::create([
                                'user_type'=> $userType,
                                'email' => $request->email,
                                'remember_token' => $token,
                                'last_name' => $request->last_name,
                                'first_name' => $request->first_name,
                            ]);
                        }

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
        
        public function userLogin(Request $request) {
            $request->validate([
                "email" => "required|email",
                "password" => "required",
            ]);
            
            $credentials = $request->only('email', 'password');
            $remember = $request->has('remember');

            /** Login user into the website */
            if (Auth::attempt($credentials, $remember)) {
                /** Logout user into other devices */
                Auth::logoutOtherDevices($request->password);

                /** Unset the session of the microsoft_access_token and microsoft_refresh_token */
                Session::forget('microsoft_access_token');
                Session::forget('microsoft_refresh_token');

                /** Getting login user data using auth */
                $user = Auth::user();
                if ($user->status == 0) {
                    /** Log out the user if they are inactive */
                    Auth::logout();
                    
                    return redirect()->route('login')->withErrors(['email' => 'Your account is inactive. Please contact the administrator.']);
                }
                /** Connection could not be established with host "mailpit:1025": stream_socket_client(): php_network_getaddresses: getaddrinfo for mailpit failed: Name or service not known */
                /** Log::info('Email sent successfully'); */
                return redirect()->intended('/admin/upload-sheet');
            } else {
                /** Authentication failed... */
                return redirect()->route('login')->withErrors(['email' => 'Invalid credentials']);
            }
        }

        public function userLogout() {
            /** Unset the session of the microsoft_access_token and microsoft_refresh_token */
            Session::forget('microsoft_access_token');
            Session::forget('microsoft_refresh_token');

            /** Logout the user from website */
            Auth::logout();

            /** Redirect user to back to the login page */
            return redirect('/');
        }

        public function UpdateUser(Request $request) {
            /** Getting the user id from the request */
            $userId = $request->id;

            /** With the help of user id we will get the user data for edit */
            $editUserData = User::where('id', Crypt::decryptString($userId))->first()->toArray();

            /** Here we encrypting the id for front end */
            $editUserData['id'] = Crypt::encryptString($editUserData['id']);
            
            /** Sending data into front-end for edit */
            if ($editUserData) {
                return response()->json(['success' => true, 'editUserData' => $editUserData]);
            } else {
                return response()->json(['error' => 'User not found'], 404);
            }
        }
    
        public function UpdateUserData(Request $request) {
            /** Validating the request data */
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . Crypt::decryptString($request->update_user_id),
                'update_user_role' => 'required',
                // 'update_user_status' => 'required',
            ]);
            
            /** If faild to validate then redirect back with error message into the response */
            if ($validator->fails()) {  
                return response()->json(['error' => $validator->errors()], 200);
            } else {
                try {
                    $user = User::find(Crypt::decryptString($request->update_user_id));
                    $user1 = Auth::user();
                    if ($user) {
                        $userType = ($request->update_user_role == 2) ? USER::USER_TYPE_ADMIN : USER::USER_TYPE_USER;
                        if (($userType != 2 && $user1->user_type == 2) || $user1->user_type == 1 || (!in_array($userType, [2, 3]) && $user1->user_type == 3)) {
                            if ($user1->user_type == 1) {
                                $user->update([
                                    'user_type' => $userType,
                                    'email' => $request->email,
                                    'status' => $request->update_user_status,
                                    'last_name' => $request->last_name,
                                    'first_name' => $request->first_name,
                                ]);
                            } else {
                                $user->update([
                                    'user_type' => $userType,
                                    'email' => $request->email,
                                    'last_name' => $request->last_name,
                                    'first_name' => $request->first_name,
                                ]);
                            }

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

        public function UserRemove(Request $request) {
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

        public function createPassword(Request $request) {
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

        public function updatePassword(Request $request) {
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

        public function changePasswordView() {
            $adminUser= User::where('user_type',User::USER_TYPE_SUPERADMIN)->first();
            return view('admin.profile',compact('adminUser'));
        }  

        public function userForgetPassword(Request $request) {
            return view('auth.forget_password'); 
        }

        public function userResetPassword(Request $request) {
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

        public function powerBiAdd(Request $request) {
            try {
                DB::table('permissions')
                ->insert([
                    'name' => $request->input('title')
                ]);

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

        public function powerBiEdit(Request $request) {
            try {
                dd($request->all());
                $report = DB::table('show_power_bi')
                ->where(['id' => $request->input('id'), 'deleted' => 0])
                ->select('title')
                ->first();

                DB::table('permissions')
                ->where('name', $report->title)
                ->update([
                    'name' => $request->input('title')
                ]);

                DB::table('show_power_bi')
                ->where(['id' => $request->input('id'), 'deleted' => 0])
                ->update([
                    'title' => $request->input('titles'),
                    'iframe' => $request->input('iframes'),
                ]);

                return redirect()->route('power_bi.show');
            } catch (\Exception $e) {
                Log::error('error', $e->getMessage());
            }
        }

        public function powerBiDelete($id) {
            try {
                $report = DB::table('show_power_bi')
                ->select('title')
                ->where(['id' => $id])
                ->first();

                DB::table('permissions')
                ->where('name', $report->title)
                ->delete();

                DB::table('show_power_bi')
                ->where('id', $id)
                ->update(['deleted' => 1]);

                return redirect()->route('power_bi.show');
            } catch (QueryException $e) {
                Log::error('error', $e->getMessage());
            }
        }

        public function powerBiReport(Request $request) {
            if ($request->ajax()) {
                if (!in_array(Auth::user()->user_type, [User::USER_TYPE_SUPERADMIN,User::USER_TYPE_ADMIN])) {

                    /** Convert the collection to array */
                    $titleArray = DB::table('show_power_bi')->select('title')->where('deleted', 0)->get()->map(function ($item) {
                        return [$item->title];
                    })->toArray();

                    /** Convert the collection to array */
                    $permissionArray = DB::table('permissions')->select('id')->whereIn('name', $titleArray)->get()->map(function ($item) {
                        return [$item->id];
                    })->toArray();

                    /** Convert the collection to array */
                    $userHasPermissionArray = DB::table('permission_user')->select('permission_id')->where('user_id', Auth::user()->id)->whereIn('permission_id', $permissionArray)->get()->map(function ($item) {
                        return [$item->permission_id];
                    })->toArray();

                    /** Convert the collection to array */
                    $titlesArray = DB::table('permissions')->select('name')->whereIn('id', $userHasPermissionArray)->get()->map(function ($item) {
                        return [$item->name];
                    })->toArray();

                    $record =  DB::table('show_power_bi')
                    ->whereIn('title', $titlesArray)
                    ->where('deleted', 0)
                    ->select('id', 'title')
                    ->get();
                } else {
                    $record =  DB::table('show_power_bi')
                    ->where('deleted', 0)
                    ->select('id', 'title')
                    ->get();
                }

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

                    foreach ($record as $value) {
                        $data .= '
                        <a class="nav-link ml-3 ' . ((isset($pageTitleCheck) && $pageTitleCheck == $value->title) ? 'active' : '') . '" href="' . route('powerbi_report.type', ['id' => $value->id, 'reportType' => $value->title]) . '">' . htmlspecialchars($value->title, ENT_QUOTES, 'UTF-8') . '</a>';
                    }

                    $data .= '</div>';

                    return response()->json([
                        'data' => $data,
                        'success' => true,
                    ]);
                } else {
                    return response()->json([
                        'data' => '',
                        'success' => true,
                    ]);
                }
            }
        }

        public function powerBiReportViewRender($id, $reportType) {
            if (!in_array(Auth::user()->user_type, [User::USER_TYPE_SUPERADMIN,User::USER_TYPE_ADMIN])) {
                $title = DB::table('show_power_bi')->select('title')->where(['id' => $id, 'deleted' => 0])->first();
    
                $permission = DB::table('permissions')->select('id')->where('name', $title->title)->first();
    
                $userHasPermission = DB::table('permission_user')->select('permission_id')->where('user_id', Auth::user()->id)->where('permission_id', $permission->id)->first();
    
                if (!$userHasPermission) {
                    return redirect()->route('upload.sheets')->withErrors(['error' => 'You do not have permissions to access this report.']);;
                }
            }

            /** Getting power bi report ifram using id */
            $data = DB::table('show_power_bi')->select('title', 'iframe')->where(['id' => $id, 'deleted' => 0])->first();

            /** Setting the title */
            $pageTitle = $data->title;

            /** Getting access token into session */
            $accessToken = Session::get('microsoft_access_token'); /** Assuming the token is already stored in session */
            
            if ($data) {
                if (isset($accessToken) && !empty($accessToken)) {                    
                    /** Get the reportId into the ifram */
                    preg_match('/reportId=([a-zA-Z0-9-]+)/', $data->iframe, $matches);
                    $reportId = $matches[1] ?? null;

                    /** Rendring the view */
                    return view('admin.powerbi_report', compact('pageTitle', 'accessToken', 'reportId'));
                } else {
                    /** Setting the access token false and setting ifram value */
                    $accessToken = false;
                    $ifram = $data->iframe;

                    /** Rendring the view */
                    return view('admin.powerbi_report', compact('pageTitle', 'accessToken', 'ifram'));
                }
            } else {
                /** Setting the access token false and setting ifram value */
                $accessToken = false;
                $ifram = "<h1> Kindly add embeded Code </h1>";

                /** Rendring the view */
                return view('admin.powerbi_report', compact('pageTitle', 'accessToken', 'ifram'));
            }
        }

        public function microsoft() {
            /** coding to redirect to the Microsoft application just created. */
            $url = 'https://login.microsoftonline.com/d9448cd0-bd4f-4914-a17f-522139087ae4/oauth2/v2.0/authorize?' . http_build_query([
                'client_id' => env('MICROSOFT_API_ID'),
                'response_type' => 'code',
                'redirect_uri' => env('MICROSOFT_API_REDIRECT_URI'),
                'scope' => "openid profile User.Read email offline_access",
                'grant_type' => 'authorization_code',
            ]);

            return redirect($url); 
        }

        public function profile(Request $request) {
            try {
                $code = $request->input('code');

                if (isset($code)) {
                    /** Get access token using the authorization code */
                    $url = "https://login.microsoftonline.com/common/oauth2/v2.0/token";
                    
                    $fields = array(
                        "client_id" => env('MICROSOFT_API_ID'),
                        "redirect_uri" => env('MICROSOFT_API_REDIRECT_URI'),
                        "client_secret" => env('MICROSOFT_API_SECRET'),
                        "code" => $code,
                        "grant_type" => "authorization_code"
                    );

                    $fields_string = http_build_query($fields);
                
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded"));
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    
                    $result = curl_exec($ch);
                    
                    if (curl_errno($ch)) {
                        /** Creating error log for checking errors */
                        Log::error('Error in Microsoft Login Error During Token Request: ' . $ch);

                        /** Authentication failed... */
                        return redirect()->route('login')->withErrors(['email' => 'Something wrong. Try after some time.']);
                    }
                    
                    curl_close($ch);
                    
                    /** Getting final tokens data after decoding the JSON */
                    $result = json_decode($result, true);

                    /** Debugging output */
                    // dd($result);

                    /** this is the refresh token used to access Microsoft Live REST APIs */
                    if (isset($result['access_token'])) {
                        /** Changing the scope of the token and creating new token using refresh token */
                        $response = Http::asForm()->post('https://login.microsoftonline.com/d9448cd0-bd4f-4914-a17f-522139087ae4/oauth2/v2.0/token', [
                            'client_id' => env('MICROSOFT_API_ID'),
                            'client_secret' => env('MICROSOFT_API_SECRET'),
                            'grant_type' => 'refresh_token',
                            'refresh_token' => $result['refresh_token'],
                            'scope' => "https://analysis.windows.net/powerbi/api/Report.Read.All",
                        ]);
                        
                        /** Setting tokens value into $token variable */
                        $tokens = $response->json();

                        /** Unsetting the old tokens from session */
                        Session::forget('microsoft_access_token');
                        Session::forget('microsoft_refresh_token');

                        /** Store the access token in the session */
                        Session::put('microsoft_access_token', $tokens['access_token']);
                        Session::put('microsoft_refresh_token', $tokens['refresh_token']);

                        /**  Use Microsoft Graph API instead of Live API */
                        $url = "https://graph.microsoft.com/v1.0/me";

                        /** Adding the header and bearer token into the request */
                        $options = [
                            "http" => [
                                "header" => "Authorization: Bearer " . $result['access_token']
                            ]
                        ];

                        /** Perpare the url adding Bearer token */
                        $context = stream_context_create($options);

                        /** Hitting the url adding Bearer token */
                        $data_json = file_get_contents($url, false, $context);

                        /** Getthing microsoft user data and decode the JSON */
                        $data = json_decode($data_json); 

                        /** Check data in the database and then login into application */
                        if ($data->id) { 
                            /** Getting email from the data */
                            $email = $data->mail;

                            /** Getting the user data of this email */
                            $data_info = User::where('email', $email)->first();

                            /** If data_info having user data then login user using user id */
                            if ($data_info != '') {  
                                if (Auth::loginUsingId($data_info->id)) {
                                    /** Authentication Successed... */
                                    return redirect()->intended('/admin/upload-sheet');
                                } else {
                                    /** Authentication failed... */
                                    return redirect('/')->with('message', 'Something want wrong. Try after some time.');
                                }
                            } else {
                                /** Authentication failed... */
                                return redirect()->route('login')->withErrors(['email' => 'Something wrong. Try after some time.']);
                            }
                        }
                    } else { 
                       /** Authentication failed... */
                        return redirect()->route('login')->withErrors(['email' => 'Something wrong. Try after some time.']);
                    }
                } else {
                   /** Authentication failed... */
                   return redirect()->route('login')->withErrors(['email' => 'Something wrong. Try after some time.']);
                }
            /** Handled exception here */
            } catch (Exception $ex) {
                /** Creating error log for checking errors */
                Log::error('Error in Microsoft Login Error: ' . $ex);

                /** Authentication failed... */
                return redirect()->route('login')->withErrors(['email' => 'Something wrong.Try after some time.']);
            }
        }   
    }
