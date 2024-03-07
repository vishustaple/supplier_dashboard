@extends('layout.app', ['pageTitleCheck' => 'Accounts Data'])

 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => 'Accounts Data'])
    <div id="layoutSidenav_content" >
        <h3 class="mb-0 ps-2">Manage Rebate</h3>
        <div class="row align-items-end border-bottom pb-3 pe-3 mb-4">
            <div class="col-md-12 mb-0 text-end">
                <!-- Button trigger modal -->
                <!-- <a href="{{ route('account.create')}}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Account</a> -->
                
                </div>
        </div> 
        <div class="container">
        <!-- <table class="table">
        <thead>
            <tr>
                <th scope="col">Account Number</th>
                <th scope="col">Customer Name</th>
                <th scope="col">Supplier</th>
                <th scope="col">Volume Rebate</th>
                <th scope="col">Incentive Rebate</th>
                <th scope="col">Action</th>
            </tr>
        </thead>
        <tbody>

        </tbody>
        </table> -->

        <table id="account_data" class="data_table_files">
            <thead>
                    <tr>
                        <th>Account Number</th>
                        <th>Customer Name</th>
                        <th>Account Name</th>
                        <th>Supplier</th>
                        <th>Volume Rebate</th>
                        <th>Incentive Rebate</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
    </div>
</div>
</div>
<script>
    $(document).ready(function(){
        var accountTable = $('#account_data').DataTable({
            oLanguage: {
                sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'
            },
            processing: true,
            serverSide: true,
            // lengthMenu: [],
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

    });
</script>
@endsection