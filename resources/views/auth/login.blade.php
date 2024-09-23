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
                                    <div class="card-header"><h3 class="text-center font-weight-light my-4">Login</h3></div>
                                        @if(session('success'))
                                        <div class="alert alert-success" id="successMessage">
                                        {{ session('success') }}
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
                                            <div class="text-center">
                                                <button type="submit" class="btn blue ">Login</button>
                                            </div>
                                            <div class="text-center pt-2">
                                                <a href="{{ route('microsoft') }}" class="btn blue" style="color:aliceblue; background-color:#b27929 !important">SignIn with Microsoft</a>
                                            </div>
                                            <div class="text-center mt-2">
                                                <a href="{{route('user.forget')}}" class="forgot-password-link">Forgot Password?</a>
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