@extends('layout.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Admin Profile</div>

                    <div class="card-body">
                        <p><strong>Name:</strong> {{ $admin->name }}</p>
                        <p><strong>Email:</strong> {{ $admin->email }}</p>
                        <!-- Add more profile information here -->

                        <!-- Change Password Button -->
                        <a href="{{ route('admin.changePassword') }}" class="btn btn-primary">Change Password</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection