<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Your Password</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
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
                    <p>Please create your password by clicking the button below:</p>
                    <p>If you did not request this, please ignore this email.</p>
                    <p>Thank you!</p>
                    <a style="color: #fff;background-color: #007bff; border-color: #007bff;text-align:center;border-radius:5px;padding:7px 15px;margin:20px 0px;" href="{{route('create.password', ['id' => $userid])}}" class="btn btn-primary">Create Password</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>