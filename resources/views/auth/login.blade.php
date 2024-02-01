@extends('layout.app')
@section('content')
    <body class="yellow">
        <div id="layoutAuthentication">
            <div id="layoutAuthentication_content">
                <main>
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-lg-5">
                               
                                <div class="card shadow-lg border-0 rounded-lg mt-5">
                                <div class="brand_logo">
                                    <a class="logo_link" href="#">
                                        <img src="{{ asset('images/logo-1.webp') }}">
                                    </a>
                                </div>
                                    <div class="card-header"><h3 class="text-center font-weight-light my-4">Login</h3></div>
                                        @if(session('success_message'))
                                        <div class="alert alert-success" id="successMessage">
                                        {{ session('success_message') }}
                                        </div>
                                        @endif
                                        @if ($errors->any())
                                        <div class="alert alert-danger">
                                        <ul>
                                        @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                        @endforeach
                                        </ul>
                                        </div>
                                        @endif 
                                    <div class="card-body">
                                        <form action="{{route('user.login')}}" method="POST">
                                        @csrf
                                            <div class="form-floating mb-3">
                                                <input class="form-control" id="inputEmail" name="email" type="email" placeholder="name@example.com" />
                                                <label for="inputEmail">Email address</label>
                                            </div>
                                            <div class="form-floating mb-3">
                                                <input class="form-control" id="inputPassword" name="password" type="password" placeholder="Password" />
                                                <label for="inputPassword">Password</label>
                                            </div>
                                            <!-- <div class="form-check mb-3">
                                                <input class="form-check-input" id="inputRememberPassword" name="remember" type="checkbox" value="1" />
                                                <label class="form-check-label" for="inputRememberPassword">Remember Password</label>
                                            </div> -->
                                            <div class="text-center ">
                                                <!-- <a class="small" href="password.html">Forgot Password?</a> -->
                                                <!-- <a class="btn btn-primary" href="index.html">Login</a> -->
                                                <button type="submit" class="btn blue ">Login</button>
                                            </div>
                                        </form>
                                    </div>
                                    <!-- <div class="card-footer text-center py-3">
                                        <div class="small"><a href="{{route('register')}}">Need an account? Sign up!</a></div>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
           
        </div>
   
        
             @include('layout.footer')
        
    </body>
</html>
@endsection