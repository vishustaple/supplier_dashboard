@extends('layout.app', ['pageTitleCheck' => $pageTitle])
@section('content')
<div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
    <div id="layoutSidenav_content">
        <div class="container">
            <div class="m-1 mb-2 d-md-flex align-items-center justify-content-between">
                <h3 class="mb-0 ">{{ $pageTitle }}</h3>
            </div>
            <form  id="import_form"  enctype="multipart/form-data">
                @csrf
                <div class="row align-items-end py-3 border-top border-bottom mb-3">
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
                    <div class="card bg-light mb-3" style="width: 18rem; display: none;">
                                    <div class="card-body">
                                        <p class="card-text"><b>Test: </b></p>
                                    </div>
                                </div>
                    <!-- <div class="form-group col-md-3 relative  mb-3">  
                        <label for="enddate">Select Date:</label>
                        <input class="form-control" id="date" type="date" name="date" placeholder="Enter Your End Date " >
                    </div> -->
                    <div class="col-md-12 mb-0">
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <button id="downloadCsvBtn" class="btn-success btn m-1" title="Csv Download"><i class="fa-solid me-2 fa-file-csv"></i>Download</button>
                    </div>
                    <!-- Button trigger modal -->
                </div>
            </form>
            <table id="accounts_data" class="data_table_files">
                <!-- Your table content goes here -->
            </table>
        </div>
    </div>
</div>
<!-- Include Date Range Picker JavaScript -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
<script>
    $(document).ready(function() {
        // DataTable initialization
        var accountsData = $('#accounts_data').DataTable({
            oLanguage: {sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'},
            processing: true,
            serverSide: false,
            lengthMenu: [40], // Specify the options you want to show
            lengthChange: false, // Hide the "Show X entries" dropdown
            searching:false, 
            pageLength: 40,
            order: [[3, 'desc']],
            ajax: {
                url: '{{ route("report.operational_anomaly_report") }}',
                type: 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: function (d) {
                    // Pass date range and supplier ID when making the request
                    // d.date = $('#date').val();
                    d.supplier = $('#supplier option:selected').text();
                    d.supplier_id = $('#supplier').val();
                    // d.quarter = $('#quarter').val();
                    // d.sales_reps = $('#sales_rep').val();
                    // d.commission_rebate_id = $('#commission_table_id').val();
                },
            },

            beforeSend: function() {
                // Show both the DataTables processing indicator and the manual loader before making the AJAX request
                $('.dataTables_processing').show();
                $('#manualLoader').show();
            },

            complete: function(response) {
                // Hide both the DataTables processing indicator and the manual loader when the DataTable has finished loading
                $('#manualLoader').hide();
                $('.dataTables_processing').hide();
                if (businessdataTable.data().count() > 40) {
                    $('#business_data_paginate').show(); // Enable pagination
                } else {
                    $('#business_data_paginate').hide();
                }
            },

            columns: [
                { data: 'account_name', name: 'account_name', title: 'Account Name', 'orderable': true, 'searchable': false },
                { data: 'supplier_name', name: 'supplier_name', title: 'Supplier Name', 'orderable': true, 'searchable': false },
                { data: 'fifty_two_wk_avg', name: 'fifty_two_wk_avg', title: '52wk AVG', 'orderable': true, 'searchable': false },
                { data: 'ten_week_avg', name: 'ten_wk_avg', title: '10wk AVG', 'orderable': true, 'searchable': false },
                { data: 'two_wk_avg_percentage', name: 'two_wk_avg_percentage', title: '2wk AVG.', 'orderable': true, 'searchable': false },
                { data: 'drop', name: 'drop', title: 'Percentage Drop', 'orderable': true, 'searchable': false },
                { data: 'median', name: 'median', title: '52wk Median', 'orderable': true, 'searchable': false },
            ],
            
            fnDrawCallback: function( oSettings ) {
                setDate();
            },
        });

        function setDate() {
            if ($('#supplier_date').val() != null) {
                $('.card-body').html('');
                $('.card').show();
                $('.card-body').html('<p class="card-text"><b>Start Date: </b>' + $('#supplier_date').val() + '</p>');
            } else {
                $('.card').hide();
            }
        }

        $("#import_form").on('submit', function (e){
            e.preventDefault();
            $('#accounts_data').DataTable().ajax.reload();
        });

        $('#downloadCsvBtn').on('click', function () {
            // Trigger CSV download
            downloadCsv();
        });

        function downloadCsv() {
            // You can customize this URL to match your backend route for CSV download
            var csvUrl = '{{ route("operational-anomaly-report.export-csv") }}';

            // Add query parameters for date range and supplier ID
            csvUrl += '?date=' + $('#date').val() + '&supplier=' + $('#supplier option:selected').text() + '&supplier_id=' + $('#supplier').val();

            // Open a new window to download the CSV file
            window.open(csvUrl, '_blank');
        }
    });
</script>
@endsection