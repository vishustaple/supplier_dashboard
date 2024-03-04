<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Your Password</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
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
<body>
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Create Your Password
                </div>
                <div class="card-body">
                    <p>Dear User,</p>
                    <p>Please create your password by filling the Form below:</p>
                    <form class="" id="create_password" method="post" action="{{ route('update.password') }}">
                     @csrf
                     <input type="hidden" name="user_id" value="{{ $userid }}">
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
                <div class="text-center py-3">
                <button type="submit" class="btn btn-primary mx-auto text-center" id="create_password">Submit</button>
                </div>

            </form>
                   
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>