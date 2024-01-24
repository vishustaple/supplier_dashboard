<!-- Include jQuery -->
<!-- <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script> -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="{{ asset('admin/dist/js/scripts.js') }}"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <script src="{{ asset('/admin/dist/assets/demo/chart-area-demo.js') }}"></script>
        <script src="{{ asset('/admin/dist/assets/demo/chart-bar-demo.js') }}"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="{{ asset('/admin/dist/js/datatables-simple-demo.js') }}"></script>
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