@extends('layout.app')

@section('content')
<div id="layoutSidenav">
    @include('layout.sidenavbar')
    <div id="layoutSidenav_content" >
    <div class="row ps-5 justify-content-center mt-5">
        <div class="col-md-6">
            <!-- Display success message -->
            @if(session('success'))
                    <div class="alert alert-success alert-dismissible">
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    {{ session('success') }}
                    </div>
            @endif
            <div class="card password_card shadow">
                <div class="card-header">
                    Change Your Password
                </div>
                <div class="card-body">
                     
                    <form class="" id="create_password" method="post" action="{{ route('update.password') }}">
                     @csrf
                     <input type="hidden" name="user_id" value="{{ $adminUser->id }}">
                 <div class="row mx-0">
                <div class="col-md-12 px-0 pb-2">
                    <div class="form-group mb-3 mb-md-0">
                        <label for="inputPassword">Password</label>
                        <input class="form-control" id="inputPassword" name="password" type="password" placeholder="Create a password" />
                        @error('password')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="col-md-12 px-0">
                    <div class="form-group mb-3 mb-md-0">
                        <label for="inputPasswordConfirm">Confirm Password</label>
                        <input class="form-control" id="inputPasswordConfirm" name="confirm_password" type="password" placeholder="Confirm password" />
                    </div>
                    @error('confirm_password')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                <div class="py-3 ps-0">
                <button type="submit" class="btn btn-dark mx-auto text-center" id="change_password">Change Password</button>
                </div>

            </form>
                   
                </div>
            </div>
        </div>
    </div>
       
    </div>
</div>   
@endsection