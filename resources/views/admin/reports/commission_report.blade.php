<!-- resources/views/excel-import.blade.php -->


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
                   
                
            </div>
            <form  id="import_form"  enctype="multipart/form-data">
                @csrf
                <div class="row align-items-end py-3 border-top border-bottom mb-3">
                    <div class="form-group col-md-4 mb-0">
                        <label for="supplier">Select Sales Rep:</label>
                        <select id="sales_rep" name="sales_rep" class="form-control" required> 
                            <option value="" selected>--Select--</option>
                            @if(isset($sales_rep))
                                @foreach($sales_rep as $sales)
                                    <option value="{{ $sales->id }}">{{ $sales->sales_rep }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="form-group relative col-md-3 mb-0">  
                        <label for="enddate">Select Year:</label>
                        <select class="form-control" name="year" id="year" required>
                            <option value="">--Select--</option>
                            @for ($year = 2010; $year <= date('Y'); $year++)
                                <option value="{{$year}}">{{$year}}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group relative col-md-3 mb-0">  
                        <label for="enddate">Select Quarter:</label>
                        <select class="form-control" name="quarter" id="quarter" required>
                            <option value="">--Select--</option>
                            <option value="Annual">Annual</option>
                            <option value="Quarter 1">Quarter 1</option>
                            <option value="Quarter 2">Quarter 2</option>
                            <option value="Quarter 3">Quarter 3</option>
                            <option value="Quarter 4">Quarter 4</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4 mb-0">
                        <label for="approved">Select Approved:</label>
                        <select id="approved" name="approved" class="form-control" required> 
                            <option value="" selected>--Select--</option>
                            <option value="1" selected>Yes</option>
                            <option value="0" selected>NO</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4 mb-0">
                        <label for="paid">Select Paid:</label>
                        <select id="paid" name="paid" class="form-control" required> 
                            <option value="" selected>--Select--</option>
                            <option value="1" selected>Yes</option>
                            <option value="0" selected>NO</option>
                        </select>
                    </div>
                    <!-- <div class="form-check relative col-md-2 mb-0">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="volume_rebate_check" checked>
                            <label class="form-check-label" for="volume_rebate_check">Volume Rebate</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="commission_rebate_check" checked>
                            <label class="form-check-label" for="commission_rebate_check">Commission</label>
                        </div>
                    </div> -->
                    <div class="col-md-3 mb-0">
                        <button type="submit" class="btn btn-primary m-1">Submit</button>
                        <button id="downloadCsvBtn" class="btn-success btn m-1" title="Csv Download"><i class="fa-solid me-2 fa-file-csv"></i>Download</button>
                    </div>
                    <!-- Button trigger modal -->
                </div>
            </form>
            <table id="commission_report_data" class="data_table_files">
                <!-- Your table content goes here -->
            </table>
        </div>
        <!-- Button trigger modal -->

        <!-- Modal -->
        <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog" style="max-width:850px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="staticBackdropLabel">Commission Detail</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body overflow-auto">
                        <table id="commission_report_data1" style="width:100%" class="data_table_files">
                            <!-- Your table content goes here -->
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <!-- <button type="button" class="btn btn-primary">Understood</button> -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Include Date Range Picker JavaScript -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
<script>
    $(document).ready(function() {
        // Button click event
        $('#import_form').on('submit', function () {
            event.preventDefault();
            // Initiate DataTable AJAX request
            $('#commission_report_data').DataTable().ajax.reload();
            $('#commission_report_data1').DataTable().ajax.reload();
        });

        // DataTable initialization
        var supplierDataTable = $('#commission_report_data').DataTable({
            oLanguage: {sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'},
            processing: true,
            serverSide: true,
            paging: false,
            info: false,
            searching:false, 
            pageLength: 40,
            order: [[3, 'desc']],
            ajax: {
                url: '{{ route("report.commission_report_filter") }}',
                type: 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: function (d) {
                    // Pass date range and supplier ID when making the request
                    d.year = $('#year').val();
                    d.quarter = $('#quarter').val();
                    d.sales_rep = $('#sales_rep').val();
                    d.supplier = $('#supplier').val();
                    d.approved = $('#approved').val();
                    d.paid = $('#paid').val();
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
                { data: 'approved', name: 'approved', title: 'Approved', 'orderable': false, 'searchable': false},
                { data: 'paid', name: 'paid', title: 'Paid', 'orderable': false, 'searchable': false},
                { data: 'sales_rep', name: 'sales_rep', title: 'Sales Rep', 'orderable': false, 'searchable': false},
                { data: 'amount', name: 'amount', title: 'Spend', 'orderable': false, 'searchable': false},
                { data: 'volume_rebate', name: 'volume_rebate', title: 'Volume Rebate', 'orderable': false, 'searchable': false},
                { data: 'commission', name: 'commission', title: 'Commission', 'orderable': false, 'searchable': false},
            ],

            fnDrawCallback: function( oSettings ) {
                // setPercentage();
                $('#commission_report_data1').DataTable().ajax.reload();
            },
        });  

        // function setPercentage(){
            // if (supplierDataTable.row(0).node()) {
            //     var buttonElement = supplierDataTable.row(0).node().querySelector('button');
            // }

            // if (buttonElement) {
            //     var id = buttonElement.getAttribute('data-id'); 
            // } else {
            //     var id = '';
            // }

            // DataTable initialization
            var supplierDataTable1 = $('#commission_report_data1').DataTable({
                oLanguage: {sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'},
                processing: true,
                serverSide: true,
                lengthMenu: [40], // Specify the options you want to show
                lengthChange: false, // Hide the "Show X entries" dropdown
                // paging: false,
                searching:false, 
                pageLength: 40,
                order: [[3, 'desc']],
                ajax: {
                    url: '{{ route("report.commission_report_filter_secound") }}',
                    type: 'POST',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: function (d) {
                        // Pass date range and supplier ID when making the request
                        d.year = $('#year').val();
                        d.quarter = $('#quarter').val();
                        d.sales_rep = $('#sales_rep').val();
                        d.supplier = $('#supplier').val();
                        if (supplierDataTable.row(0).node()) {
                            d.commission_rebate_id = supplierDataTable.row(0).node().querySelector('button').getAttribute('data-id');
                        }
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
                    { data: 'supplier', name: 'supplier', title: 'Supplier'},
                    // { data: 'account_name', name: 'account_name', title: 'Account Name'},
                    { data: 'amount', name: 'amount', title: 'Spend'},
                    { data: 'volume_rebate', name: 'volume_rebate', title: 'Volume Rebate'},
                    { data: 'commission', name: 'commission', title: 'Commission'},
                    { data: 'start_date', name: 'start_date', title: 'Start Date'},
                    { data: 'end_date', name: 'end_date', title: 'End Date'},
                ],
            });  
        // }


        $('#downloadCsvBtn').on('click', function () {
            // Trigger CSV download
            downloadCsv();
        });

        function downloadCsv() {
            // You can customize this URL to match your backend route for CSV download
            var csvUrl = '{{ route("report.export-commission_report-csv") }}';
            // Add query parameters for date range and supplier ID

            var order = supplierDataTable.order();
            csvUrl += '?year=' + $('#year').val() + '&quarter=' + $('#quarter').val() + '&sales_rep=' + $('#sales_rep').val() + '&column=' + order[0][0] + '&order=' + order[0][1] + '&supplier=' + $('#supplier').val();
            // Open a new window to download the CSV file
            window.open(csvUrl, '_blank');
        } 
    });        
</script>
@endsection