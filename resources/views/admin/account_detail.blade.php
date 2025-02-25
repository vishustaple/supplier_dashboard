@extends('layout.app', ['pageTitleCheck' => $pageTitle])
@section('content')
    <div id="layoutSidenav">
        @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
        <div id="layoutSidenav_content">
            <div class="container">
                <div class="m-1 mb-2 row align-items-start justify-content-between">
                    <div class="col-md-4">
                        <h3 class="mb-0 ">{{ $pageTitle }}</h3>
                    </div>
                    <div class="col-md-3 d-flex align-items-center justify-content-end pe-0">   
                        <a href="{{ route('account') }}" class="btn btn-secondary  border-0 bg_yellow"><i class="fas fa-arrow-left me-2"></i>Back</a>
                    </div>
                </div>
                <table id="account_detail_data" class="data_table_files"></table>
            </div>
        </div>
    </div>
    <style>
        @media (max-width:1366px) {
            th{
                padding: 8px 10px !important;
            }
            td{
                font-size: 14px;
            }
        }
    </style>
    <script>
        $(document).ready(function() {
            // DataTable initialization
            var accountDataTable = $('#account_detail_data').DataTable({
                oLanguage: {sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'},
                processing: true,
                serverSide: true,
                lengthMenu: [40], // Specify the options you want to show
                lengthChange: false, // Hide the "Show X entries" dropdown
                searching:false, 
                pageLength: 40,
                order: [[3, 'desc']],
                ajax: {
                    url: '{{ route("account.detail") }}',
                    type: 'POST',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: function (d) {
                        // Pass date range and supplier ID when making the request
                        d.account_name = '{{ $account->account_name }}';
                    },
                },

                beforeSend: function() {
                    // Show both the DataTables processing indicator and the manual loader before making the AJAX request
                    $('.dataTables_processing').show();
                    $('#manualLoader').show();
                },

                complete: function(response) {
                    // Hide both the DataTables processing indicator and the manual loader when the DataTable has finished loading
                    $('.dataTables_processing').hide();
                    $('#manualLoader').hide();
                    if (businessdataTable.data().count() > 40) {
                        $('#business_data_paginate').show(); // Enable pagination
                    } else {
                        $('#business_data_paginate').hide();
                    }
                },

                columns: [
                    { data: 'customer_number', name: 'customer_number', title: 'Supplier Customer Number'},
                    { data: 'customer_name', name: 'customer_name', title: 'Customer Name'},
                    { data: 'account_name', name: 'account_name', title: 'Account Name'},
                    { data: 'parent_id', name: 'parent_id', title: 'Parent Id'},
                    { data: 'parent_name', name: 'parent_name', title: 'Parent Name'},
                    { data: 'supplier_name', name: 'supplier_name', title: 'Supplier Name'},
                    { data: 'record_type', name: 'record_type', title: 'Record Type'},
                    { data: 'date', name: 'date', title: 'Date'},
                ],
            });
        });        
    </script>
@endsection