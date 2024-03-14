@extends('layout.app', ['pageTitleCheck' => $pageTitle])

 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
    <div id="layoutSidenav_content" >
        <h3 class="mb-0 ps-2 ms-1">Manage Rebate</h3>
        <div class="row align-items-end border-bottom pb-3 pe-3 mb-4">
            <div class="col-md-12 mb-0 text-end">
                <!-- Button trigger modal -->
                <!-- <a href="{{ route('account.create')}}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Account</a> -->
                
                </div>
        </div> 
        <div class="container">
        <div class="" id="successMessages">
        </div>

        <div class="" id="errorMessage">    
        </div>
        <table id="rebate_data" class="data_table_files">
            <thead>
                    <tr>
                        <th>Account Number</th>
                        <th>Customer Name</th>
                        <th>Account Name</th>
                        <th>Supplier</th>
                        <th>Volume Rebate</th>
                        <th class="inncnetive_rebate">Incentive Rebate</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
    </div>
</div>
</div>
<script>
    $(document).ready(function(){
      
        var accountTable = $('#rebate_data').DataTable({
            oLanguage: {
                sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'
            },
            processing: true,
            serverSide: true,
            pageLength: 50,
            ajax: {
                url: '{{ route("rebate.filter") }}',
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
                { data: 'account_number', name: 'account_number' },
                { data: 'customer_name', name: 'customer_name' },
                { data: 'account_name', name: 'account_name' },
                { data: 'supplier_name', name: 'supplier_name' },
                { data: 'volume_rebate', name: 'volume_rebate' },
                { data: 'incentive_rebate', name: 'incentive_rebate' },
                { data: 'id', name: 'id', 'orderable': false, 'searchable': false }
            ],
            
        });
        $('#rebate_data_length').hide();
        $(document).on('click', '.save_rebate', function(){
            var rowData = accountTable.row($(this).closest('tr')).data(),
            formData = { account_number : $(this).closest('tr').find('.account_number').val(), volume_rebate : $(this).closest('tr').find('.volume_rebate').val(), incentive_rebate : $(this).closest('tr').find('.incentive_rebate').val()},
            token = "{{ csrf_token() }}";

            $.ajax({
                type: 'POST',
                url: "{{route('rebate.update')}}",
                dataType: 'json',
                data: JSON.stringify(formData),                        
                headers: {'X-CSRF-TOKEN': token},
                contentType: 'application/json',                     
                processData: false,
                
                success: function(response) {
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                    if(response.error){
                        var errorMessage = '';
                        if (typeof response.error === 'object') {
                            // Iterate over the errors object
                            $.each(response.error, function (key, value) {
                                errorMessage += value[0] + '<br>';
                            });
                        } else {
                            errorMessage = response.error;
                        }
                        $('#errorMessage').html('');
                        $('#errorMessage').append('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+errorMessage+'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                    }

                    if(response.success){
                        $('#successMessages').html('');
                        $('#successMessages').append('<div class="alert alert-success alert-dismissible fade show" role="alert">'+response.success+'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                        // window.location.href = "{{ route('commission.list', ['commissionType' => 'commission_listing']) }}";
                    }
                },
                error: function(xhr, status, error) {
                    // Handle error response
                    console.error(xhr.responseText);
                }
            });
        });
    });
</script>
@endsection