<!DOCTYPE html>
<html>
<head>
    <title>AYD Chat</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<iframe id="ayd-chat" width="100%" height="600" frameborder="0"></iframe>

<script>
$(document).ready(function() {
    // Load default unauthenticated view first
    $('#ayd-chat').attr('src', 'https://www.askyourdatabase.com/chatbot/{{ env('AYD_CHATBOT_ID') }}');

    // Listen for messages from AYD iframe
    window.addEventListener('message', function(event) {
        if (event.data.type === 'LOGIN_REQUIRED') {
            $.post('/ayd-session', function(response) {
                if (response.url) {
                    $('#ayd-chat').attr('src', response.url);
                }
            });
        } else if (event.data.type === 'LOGIN_SUCCESS') {
            $('#ayd-chat').attr('src', event.data.url);
        }
    });
});
</script>

</body>
</html>