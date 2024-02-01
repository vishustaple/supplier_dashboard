<!-- Include jQuery -->
<script src="{{asset('js/bootstrap.min.js')}}" crossorigin="anonymous"></script>
     
        <script>
    document.addEventListener('DOMContentLoaded', function () {
        var successMessage = document.getElementById('successMessage');
    
        var errorMessage = document.getElementById('errorMessage');
        if (successMessage) {
            setTimeout(function () {
                successMessage.style.display = 'none';
            }, 5000); // 5000 milliseconds = 5 seconds
        }
        if (successMessage) {
            setTimeout(function () {
                successMessage.style.display = 'none';
            }, 10000); // 5000 milliseconds = 5 seconds
        }
});
</script>