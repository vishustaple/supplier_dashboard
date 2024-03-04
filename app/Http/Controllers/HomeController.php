<?php
    namespace App\Http\Controllers;

    use Validator;
    use App\Models\User;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Mail;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Console\Command;

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
                
                    $userType = ($request->user_role == 2) ? USER::USER_TYPE_ADMIN : USER::USER_TYPE_USER;

                    $user = User::create([
                        'first_name' => $request->first_name,
                        'last_name' => $request->last_name,
                        'email' => $request->email,
                       // 'password' => bcrypt($request->password), // Hash the password before saving
                        'user_type'=> $userType,
                    ]);
                    $email="vishumehandiratta360@gmail.com";
                    try{
                        Log::info('Attempting to send email...');
                        // Mail::send('mail.pendingfile', ['suppliername' => "test"], function ($m) use ($email) {
                        //     $m->from($email, 'Supplier Admin'); // Use $email variable here
                        //     $m->to('vishustaple@yopmail.com')->subject('Pending Files else');
                        // });
                       
                        Mail::send('mail.pendingfile', ['suppliername' => "test"], function($message) {
                            $message->to('ankitsainisaini3333@gmail.com', 'Tutorials Point')->subject
                               ('Laravel Testing Mail with Attachment');
                            // $message->attach('C:\laravel-master\laravel\public\uploads\image.png');
                            // $message->attach('C:\laravel-master\laravel\public\uploads\test.txt');
                            // $message->from('vishumehandiratta360@gmail.com','Virat Gandhi');
                         });
                         
                        Log::info('Email sent successfully');
                    } catch (\Exception $e) {
                        /** Handle the exception here */
                        Log::error('Email sending failed: ' . $e->getMessage());
                        $this->error('Email sending failed: ' . $e->getMessage());
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
    }
