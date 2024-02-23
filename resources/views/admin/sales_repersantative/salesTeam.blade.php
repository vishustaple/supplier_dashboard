<!-- resources/views/excel-import.blade.php -->


@extends('layout.app', ['pageTitleCheck' => $pageTitle])

 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
    <div id="layoutSidenav_content" >
        <h3 class="mb-0 ps-2">Sales Team Accounts</h3>
        <div class="row align-items-end border-bottom pb-3 mb-4">
            <div class="col-md-12 mb-0 text-end">
                <!-- Button trigger modal -->
                <a href="{{ route('sales.add')}}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Sales Repersentative Account</a>
                <button id="downloadSaleTeamCsvBtn" class="btn-success btn" title="Csv Download"><i class="fa-solid me-2 fa-file-csv"></i>Download</button>
            </div>
        </div>
        <div class="alert alert-success m-3" id="account_del_success" style="display:none;"></div>
        <div class="container">
      
            <table id="sales_data" class="data_table_files">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
        
    </div>
</div>
<script>
    $(document).ready(function() {
        var accountTable = $('#sales_data').DataTable({
            oLanguage: {
                sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'
            },
            processing: true,
            serverSide: true,
            lengthMenu: [],
            pageLength: 50,
            ajax: {
                url: '{{ route("sales.filter") }}',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: function (d) {
                    // Pass date range and supplier ID when making the request
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                    d.supplierId = $('#supplierId').val();
                },
            },
            beforeSend: function() {
                // Show both the DataTables processing indicator and the manual loader before making the AJAX request
                $('.dataTables_processing').show();
                $('#manualLoader').show();
            },
            complete: function() {
                // Hide both the DataTables processing indicator and the manual loader when the DataTable has finished loading
                $('.dataTables_processing').hide();
                $('#manualLoader').hide();
            },
            columns: [
                { data: 'name', name: 'name' },
                { data: 'email', name: 'email' },
                { data: 'phone', name: 'phone' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
        });

    $('#account_data_length').hide();
    

    });

    $('#downloadAccountCsvBtn').on('click', function () {
        // Trigger CSV download
        downloadAccountCsv();
    });

    function downloadAccountCsv() {
        // You can customize this URL to match your backend route for CSV download
        var csvUrl = '{{ route('account.export-csv') }}';

        // Open a new window to download the CSV file
        window.open(csvUrl, '_blank');
    }

    // JavaScript to make checkboxes act like radio buttons
    const radioCheckboxes = document.querySelectorAll('.radio-checkbox');

        radioCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
            // Uncheck all other checkboxes in the group
            radioCheckboxes.forEach(otherCheckbox => {
                if (otherCheckbox !== checkbox) {
                otherCheckbox.checked = false;
                }
            });
        });
    });
    
     //to remove user 
     $(document).on('click', '.remove', function () {
    var id = $(this).attr('data-id');
    swal.fire({
        title: "Oops....",
        text: "Are you sure you want to delete this Account?",
        icon: "error",
        showCancelButton: true,
        confirmButtonText: 'YES',
        cancelButtonText: 'NO',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ route('account.remove') }}",
                data: { id: id },
                success: function (response) {
                    if (response.success) {
                        $('#account_del_success').text('User Delete Successfully!');
                        $('#account_del_success').css('display', 'block');
                        setTimeout(function () {
                            $('#account_del_success').fadeOut();
                            location.reload();
                        }, 3000);
                    } else {
                        // Handle other cases where response.success is false
                    }
                },
                error: function (error) {
                    console.log(error);
                    // Handle error
                }
            });
        } else {
            // Handle cancellation
        }
    });
});

</script>

@endsection