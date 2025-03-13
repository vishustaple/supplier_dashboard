@extends('layout.app', ['pageTitleCheck' => $pageTitle])
 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
    <div id="layoutSidenav_content" >
        <h3 class="mb-0 ps-2 ms-1">Manage Rebate</h3>
        <div class="row align-items-end border-bottom pb-3 pe-3 mb-4">
            <div class="col-md-12 mb-0 text-end">
                <a href="{{route('rebate.list', ['rebateType' => 'edit_rebate'])}}" class="bell_icon_link btn btn-info position-relative">
                    <i class="fa-solid fa-bell"></i>
                    @if($totalMissingRebate)
                        @if($totalMissingRebate > 0)
                            <span class="notification-count">{{ $totalMissingRebate }}</span>
                        @endif
                    @endif
                </a>
            </div>
        </div> 
        <div class="container">
            <div class="form-group col-md-3 relative  mb-3">
                <label for="supplier">Select Supplier:</label>
                <select id="supplier" name="supplier" class="form-control"> 
                    <option value="" selected>--Select--</option>
                    @if(isset($categorySuppliers))
                        @foreach($categorySuppliers as $categorySupplier)
                            @if($categorySupplier->id != 7)
                                <option value="{{ $categorySupplier->id }}">{{ $categorySupplier->supplier_name }}</option>
                            @endif
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="col-md-12 mb-0">
                <button id="downloadCsvBtn" class="btn-success btn m-1" title="Csv Download"><i class="fa-solid me-2 fa-file-csv"></i>Download</button>
            </div>
            <div class="" id="successMessages"></div>
            <div class="" id="errorMessage"></div>
            <table id="rebate_data" class="data_table_files"></table>
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
                { data: 'account_number', name: 'account_number', title: 'Account Number' },
                { data: 'customer_name', name: 'customer_name', title: 'Customer Name' },
                { data: 'account_name', name: 'account_name', title: 'Account Name' },
                { data: 'supplier_name', name: 'supplier_name', title: 'Supplier' },
                { data: 'volume_rebate', name: 'volume_rebate', 'searchable': false, title: 'Volume Rebate'},
                { data: 'incentive_rebate', name: 'incentive_rebate', 'searchable': false, title: 'Incentive Rebate'},
                { data: 'id', name: 'id', 'orderable': false, 'searchable': false, title: 'Action' }
            ],
        });
        $('#rebate_data_length').hide();
        $(document).on('click', '.save_rebate', function(){
            var rowData = accountTable.row($(this).closest('tr')).data(),
            formData = { supplier_id : $(this).closest('tr').find('.supplier_id').val(), account_name : $(this).closest('tr').find('.account_name').val(), volume_rebate : $(this).closest('tr').find('.volume_rebate').val(), incentive_rebate : $(this).closest('tr').find('.incentive_rebate').val()},
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
                    // $('html, body').animate({ scrollTop: 0 }, 'slow');
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
                    }
                },
                error: function(xhr, status, error) {
                    // Handle error response
                    console.error(xhr.responseText);
                }
            });
        });

        $('#downloadCsvBtn').on('click', function () {
            // Trigger CSV download
            downloadCsv();
        });

        function downloadCsv() {
            // You can customize this URL to match your backend route for CSV download
            var csvUrl = '{{ route("rebate.export-csv") }}';

            // Add query parameters for date range and supplier ID
            csvUrl += '?supplier_id=' + $('#supplier').val();

            // Open a new window to download the CSV file
            window.open(csvUrl, '_blank');
        }
    });
</script>
@endsection