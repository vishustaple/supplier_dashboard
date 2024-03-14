<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Email Subject</title>
    <style>
        /* Add your custom styles here */
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333333;
        }

        p {
            color: #555555;
        }

        /* Add more styles as needed */
    </style>
</head>
<body>
    <div class="container">
        <h1>New user login Notification</h1>
        <p>Hello Admin,</p>
        <p>There is new user login <br> **Email:** {{$data}}.</p>
        <p>**Login Time:** {{ now() }}</p>
        <p>Thank you!</p>
    </div>
</body>
</html>