@extends('layout.app')
@section('content')
    <body class="yellow">
        <div id="layoutAuthentication">
            <div id="layoutAuthentication_content">
                <main>
                    <div class="container login_container">
                        <div class="row justify-content-center">
                            <div class="col-lg-4">
                                <div class="card shadow-lg border-0 rounded-lg mt-5">
                                <div class="brand_logo">
                                    <a class="logo_link" href="#">
                                        <img src="{{ asset('images/logo-1.webp') }}">
                                    </a>
                                </div>
                                    <div class="card-header"><h3 class="text-center font-weight-light my-4">Reset Password</h3></div>
                                        @if(session('success'))
                                        <div class="alert alert-success" id="successMessage">
                                        {{ session('success') }}
                                        </div>
                                        @endif
                                        @if (session('error'))
                                        <div class="alert alert-danger">
                                        {{session('error')}}
                                        </div>
                                        @endif 
                                    <div class="card-body">
                                        <form action="{{route('user.reset')}}" method="POST">
                                        @csrf
                                            <div class="form-floating mb-3">
                                                <input class="form-control" id="inputEmail" name="email" type="email" placeholder="name@example.com" />
                                                <label for="inputEmail">Email address</label>
                                            </div>
                                            <div class="text-center">
                                                <button type="submit" class="btn blue ">Send Email</button>
                                            </div>
                                            <div class="text-center mt-2">
                                                <a href="{{route('login')}}" class="forgot-password-link">Back to Login</a>
                                            </div>
                                        </form>
                                    </div>
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