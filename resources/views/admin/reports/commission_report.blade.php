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
                        <input type="hidden" id="commission_table_id" value="">
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
                            @for ($year = 2018; $year <= date('Y'); $year++)
                                <option value="{{$year}}">{{$year}}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group relative col-md-3 pt-2 mb-0">  
                        <label for="enddate">Select Payment:</label>
                        <select class="form-control" name="quarter" id="quarter" required>
                            <option value="">--Select--</option>
                            <option value="Annual">Annual</option>
                            <option value="Quarter 1">Quarter 1</option>
                            <option value="Quarter 2">Quarter 2</option>
                            <option value="Quarter 3">Quarter 3</option>
                            <option value="Quarter 4">Quarter 4</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4 pt-2 mb-0">
                        <label for="approved">Select Approved:</label>
                        <select id="approved" name="approved" class="form-control"> 
                            <option value="" selected>--Select--</option>
                            <option value="1">Yes</option>
                            <option value="0">NO</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4 pt-2 mb-0">
                        <label for="paid">Select Paid:</label>
                        <select id="paid" name="paid" class="form-control" > 
                            <option value="" selected>--Select--</option>
                            <option value="1">Yes</option>
                            <option value="0">NO</option>
                        </select>
                    </div>
                    <div class="col-md-3 pt-2 mb-0">
                        <button type="submit" class="btn btn-primary m-1">Submit</button>
                    </div>
                    <!-- Button trigger modal -->
                </div>
            </form>

            <div class="" id="successMessages"></div>
            <div class="" id="errorMessage"></div>
            <table id="commission_report_data" class="data_table_files"></table>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="paidModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="paidModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paidModalLabel">Please add paid date</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" id="paidDateSave">
                <div class="modal-body">
                    <label for="paid_date" class="col-form-label">Paid Date:</label>
                    <input type="text" class="form-control" id="paid_date" readonly>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" >Save</button>
                </div>
            </form>
            </div>
        </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog" style="max-width:1150px">
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script>
    $(document).ready(function() {
        $("#paid_date").datepicker();

        // Button click event
        $('#import_form').on('submit', function () {
            event.preventDefault();
            // Initiate DataTable AJAX request
            $('#commission_report_data').DataTable().ajax.reload();
            $('#commission_report_data1').DataTable().ajax.reload();
        });

        $(document).on('change', '.approved_input_select', function(){
            if ($(this).val() == 1) {
                if ($('.paid_'+$(this).data('approved_ids')+'').val() == 0) {
                    $('.paid_'+$(this).data('approved_ids')+'').prop('disabled', false);
                }
            } else {
                $('.paid_'+$(this).data('approved_ids')+'').prop('disabled', true);
            }

            var formData = { approved : $(this).val(), id : $(this).data('approved_id') },
            token = "{{ csrf_token() }}";

            $.ajax({
                type: 'POST',
                url: "{{route('approved.update')}}",
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
                    }
                },

                error: function(xhr, status, error) {
                    // Handle error response
                    console.error(xhr.responseText);
                }
            });
        });

        $(document).on('change', '.paid_input_select', function(){
            if ($(this).val() == 1 && $('.approved_'+$(this).data('paid_ids')+'').val() == 1) {
                $(this).prop('disabled', true);
                $('.approved_'+$(this).data('paid_ids')+'').prop('disabled', true);
                $('#paidModal').modal('show');
            }
        });

        $("#paidDateSave").submit(function(event) {
            event.preventDefault(); // Prevent default form submission
            $('#paidModal').modal('hide');

            var formData = { paid : $('.paid_input_select').val(), id : $('.paid_input_select').data('paid_id'), paid_date : $('#paid_date').val() },
            token = "{{ csrf_token() }}";

            $(this)[0].reset();
            $.ajax({
                type: 'POST',
                url: "{{route('paid.update')}}",
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
                    }
                },

                error: function(xhr, status, error) {
                    // Handle error response
                    console.error(xhr.responseText);
                }
            });
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
                    d.paid = $('#paid').val();
                    d.quarter = $('#quarter').val();
                    d.supplier = $('#supplier').val();
                    d.approved = $('#approved').val();
                    d.sales_rep = $('#sales_rep').val();
                },
            },

            beforeSend: function() {
                // Show both the DataTables processing indicator and the manual loader before making the AJAX request
                $('#manualLoader').show();
                $('.dataTables_processing').show();
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
                { data: 'approved', name: 'approved', title: 'Approved', 'orderable': false, 'searchable': false},
                { data: 'paid', name: 'paid', title: 'Paid', 'orderable': false, 'searchable': false},
                { data: 'sales_rep', name: 'sales_rep', title: 'Sales Rep', 'orderable': false, 'searchable': false},
                { data: 'cost', name: 'cost', title: 'Spend', 'orderable': false, 'searchable': false},
                { data: 'volume_rebate', name: 'volume_rebate', title: 'Volume Rebate', 'orderable': false, 'searchable': false},
                { data: 'start_date', name: 'start_date', title: 'Start Date', 'orderable': false, 'searchable': false},
                { data: 'end_date', name: 'end_date', title: 'End Date', 'orderable': false, 'searchable': false},
                { data: 'commissions', name: 'commissions', title: 'Commission', 'orderable': false, 'searchable': false},
            ],

            fnDrawCallback: function( oSettings ) {
                $('#commission_report_data1').DataTable().ajax.reload();
            },
        });  

        // DataTable initialization
        var supplierDataTable1 = $('#commission_report_data1').DataTable({
            oLanguage: {sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'},
            processing: true,
            serverSide: true,
            lengthMenu: [40], // Specify the options you want to show
            lengthChange: false, // Hide the "Show X entries" dropdown
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
                    d.supplier = $('#supplier').val();
                    d.sales_reps = $('#sales_rep').val();
                    d.commissions_rebate_id = $('#commission_table_id').val();
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
                { data: 'account_name', name: 'account_name', title: 'Business Name'},
                { data: 'supplier', name: 'supplier', title: 'Supplier'},
                { data: 'cost', name: 'cost', title: 'Spend'},
                { data: 'volume_rebate', name: 'volume_rebate', title: 'Volume Rebate'},
                { data: 'commissions', name: 'commissions', title: 'Commission'},
            ],
        });

        $(document).on('click', '#commission_report_data tbody #downloadCsvBtn', function() {
            // Trigger CSV download
            downloadCsv($(this).data('id'));
        });
        
        $(document).on('click', '#commission_report_data tbody #commissions_rebate_id', function() {
            $('#commission_table_id').val($(this).data('id'));
            $('#commission_report_data1').DataTable().ajax.reload();
        });

        function downloadCsv(id='') {
            // You can customize this URL to match your backend route for CSV download
            var csvUrl = '{{ route("report.export-commission_report-csv") }}',
            order = supplierDataTable.order();

            // Add query parameters for date range and supplier ID
            csvUrl += '?year=' + $('#year').val() + '&quarter=' + $('#quarter').val() + '&sales_rep=' + $('#sales_rep').val() + '&column=' + order[0][0] + '&order=' + order[0][1] + '&commissions_rebate_id=' + id;

            // Open a new window to download the CSV file
            window.open(csvUrl, '_blank');
        } 
    });        
</script>
@endsection