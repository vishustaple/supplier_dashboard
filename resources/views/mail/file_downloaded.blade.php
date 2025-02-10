<!DOCTYPE html>
<html>
<head>
    <title>File Downloaded</title>
</head>
<body>
    <h1>Staples Diversity Data Downloaded</h1>
    <p>The following files have been downloaded:</p>
    <ul>
        @foreach($downloadLinks as $link)
            <li>{{ $link }}</li>
        @endforeach
    </ul>
</body>
</html>