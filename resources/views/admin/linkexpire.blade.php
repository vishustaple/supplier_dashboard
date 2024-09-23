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
            @if(isset($message))
                <h2>{{$message}}</h2>    
            @else
                <h2>Create Password link has been Expired.</h2>
            @endif
        </div>
    </div>
</body>
</html>