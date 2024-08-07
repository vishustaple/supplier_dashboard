<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Your Password</title>
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
<link href="{{ asset('/admin/dist/css/styles.css') }}" rel="stylesheet" />

<style>
    *{
        box-sizing: border-box;
    }
    .card {
    box-shadow: 0 0 20px rgba(0,0,0,0.3);
}
.card-header {
    border: 0;
    background: #007bff24;
    color: #007bff;
}
</style>
</head>
<body style="background-color: #fdb91d">
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-4">
            <div class="card shadow-lg border-0 rounded-lg mt-5">
                <div class="brand_logo p-3 text-center">
                    <a class="logo_link" href="#">
                        <img style="max-width: 250px;" src="http://127.0.0.1:8000/images/logo-1.webp">
                    </a>
                </div>
                <div class="card-header py-2 border-bottom" style="background-color: transparent;">
                   <h4 class="text-center font-weight-light my-1 text-dark"> Create Your Password</h3>
                </div>
                <div class="card-body">
                    <form class="" id="create_password" method="post" action="{{ route('update.password') }}">
                     @csrf
                     <input type="hidden" name="user_id" value="{{ $userid }}">
                     <input type="hidden" name="token" value="{{ $token }}">
                 <div class="row mx-0">
                <div class="col-md-12 px-0 pb-2">
                    <div class="form-floating mb-3">
                        <input class="form-control" id="inputPassword" name="password" type="password" placeholder="Create a password" />
                        <label for="inputPassword">Password</label>
                        @error('password')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="col-md-12 px-0">
                    <div class="form-floating mb-3">
                        <input class="form-control" id="inputPasswordConfirm" name="confirm_password" type="password" placeholder="Confirm password" />
                        <label for="inputPasswordConfirm">Confirm Password</label>
                        @error('confirm_password')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="text-center ">
                <button type="submit" class=" btn blue" style="background-color: #1a4175 !important;color:#fff;" id="create_password">Submit</button>
                </div>

            </form>
                   
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>