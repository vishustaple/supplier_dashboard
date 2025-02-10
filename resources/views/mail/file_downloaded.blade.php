<!DOCTYPE html>
<html>
<head>
    <title>File Download Links</title>
</head>
<body>
    <p>Dear User,</p>
    <p>The following files have been downloaded successfully. Click the links to access them:</p>
    <ul>
        @foreach($links as $link)
            <li><a href="{{ $link }}">{{ $link }}</a></li>
        @endforeach
    </ul>
    <p>Regards,</p>
    <p>Your Company</p>
</body>
</html>
