<!DOCTYPE html>
<html>
<head>
    <title>SFTP Staples Files</title>
</head>
<body>
    <img src="https://sql.centerpointgroup.com/images/logo.jpg">
    <p>Dear User,</p>
    @if (!$no_link_check)
        <p>The following files have been downloaded successfully. Click the links to access them:</p>
        <ul>
            @foreach($links as $link)
                <li><a href="{{ $link }}">{{ $link }}</a></li>
            @endforeach
        </ul>
    @else
        <p>Files not found.</p>
    @endif
    <p>Regards,</p>
    <p>CenterPoint Group</p>
</body>
</html>
