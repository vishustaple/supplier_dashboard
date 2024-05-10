@extends('layout.app', ['pageTitleCheck' => $pageTitle])
@section('content')
    <div id="layoutSidenav">
        @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
        <div id="layoutSidenav_content">
            <div class="container">
                <div class="m-1 mb-2 d-md-flex border-bottom pb-3 mb-3 align-items-center justify-content-between">
                    <h3 class="mb-0 ">{{ $pageTitle }}</h3>
                </div>
                <form  id="import_form"  enctype="multipart/form-data">
                    @csrf
                    <div class="row align-items-start py-3 border-top border-bottom mb-3">
                    <div class="form-group col-md-3 mb-0">
                        <label for="supplier">Select Supplier:</label>
                        <select id="supplier_id" name="supplier" class="form-control" required> 
                            <option value="" selected>--Select--</option>
                            @if(isset($categorySuppliers))
                                @foreach($categorySuppliers as $categorySupplier)
                                    <option value="{{ $categorySupplier->id }}">{{ $categorySupplier->supplier_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="row align-items-end border-bottom pb-3 mb-4">
                        <div class="form-group col-md-3 mb-0">
                            <label for="selectBox">Select Account Name:</label>
                            <select id="account_name" name="account_name" class="form-control"> 
                                <option value="" selected>--Select--</option>
                                @if(isset($accountData))
                                    @foreach($accountData as $account)
                                        @if(!empty($account->account_name))
                                            <option value="{{ $account->account_name }}">{{ $account->account_name }}</option>
                                        @endif
                                    @endforeach    
                                @endif
                            </select>
                        </div>
                        <div class="form-group relative col-md-3 mb-0">  
                            <label for="enddate">Product Type:</label>
                            <select class="form-control" name="core" id="core" required>
                                <option value="1">Non-Core</option>
                                <option value="2">Core</option>
                            </select>
                        </div>
                        <div class="form-group relative col-md-3 mb-0">  
                            <label for="">Select Year: </label>
                            <select class="form-control" name="year" id="year" required>
                                <option value="">--Select--</option>
                                @for ($year = 2018; $year <= date('Y'); $year++)
                                    <option value="{{$year}}">{{$year}}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="form-group col-md-4 mb-0" id="selectContainer" style="display:none;">                        
                        </div>
                        <!-- <div class="form-group relative col-md-4 mb-0">  
                            <label for="enddate">Select Date:</label>
                            <input class="form-control" id="enddate" name="dates" placeholder="Enter Your End Date " >
                            <input type="hidden" id="start_date" name="start_date" />
                            <input type="hidden" id="end_date" name="end_date" />  
                        </div> -->
                        <!-- disabled -->
                        <div class="col-md-3 mt-1 mb-0 text-end">
                        <button id="submitBtn" class="btn btn-primary m-1">Submit</button>
                        <button id="downloadCsvBtn" class="btn-success btn m-1" title="Csv Download"><i class="fa-solid me-2 fa-file-csv"></i>Download</button>
                        </div>
                        <!-- Button trigger modal -->
                    </div>
                </form>
                <table class="data_table_files" id="business_data">
                </table>
            </div>
        </div>
    </div>
    <style>
        div#page-loader {
            top: 0;
            left: 0;
            position: fixed;
            width: 100%;
            height: 100%;
            background: #00000080;
            z-index: 999999;
        }

        div#page-loader-wrap {
            text-align: center;
            /* vertical-align: center !important; */
            margin-top: 20%;
        }

        #business_data{
            display:block;
            overflow-x:auto;
        }

        #business_data thead tr th {
            white-space: nowrap;
        }
        </style>
    <!-- Include Date Range Picker JavaScript -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
    <script>
        $(document).ready(function() {
            $('#account_name').on('change', function(){
                $.ajax({
                    type: 'POST',
                    url: "{{route('get.accountNumber')}}",
                    dataType: 'json',
                    data: JSON.stringify({ 'account_name': $(this).val() }),                        
                    headers: {'X-CSRF-TOKEN': "{{ csrf_token() }}"},
                    contentType: 'application/json',                     
                    processData: false,
                    success: function(response) {
                        // $('#selectContainer').html('');
                        // $('#selectContainer').show();
                        // // Assuming `response` is your array of objects
                        // var select = $('<select id="account_number" name="account_number" class="form-control">');

                        // $.each(response, function(index, obj) {
                        //     var option = $('<option>').val(obj.account_number).text(obj.account_number);
                        //     select.append(option);
                        // });

                        // // Create a label element
                        // var label = $('<label for="selectBox">').text('Select Account Number:');

                        // // Assuming `selectContainer` is the container where you want to append the select element
                        // var selectContainer = $('#selectContainer');
                        // selectContainer.append(label); // Append the label before the select element
                        // selectContainer.append(select); // Append the select elemen
                        
                        $('html, body').animate({ scrollTop: 0 }, 'slow');
                    },
                    error: function(xhr, status, error) {
                        // Handle error response
                        console.error(xhr.responseText);
                    }
                });
            });

            $('input[name="dates"]').daterangepicker();

            // Event handler for when the user applies the date range
            $('input[name="dates"]').on('apply.daterangepicker', function(ev, picker) {
                // Access the selected date range
                $('#start_date').val(picker.startDate.format('YYYY-MM-DD')),
                $('#end_date').val(picker.endDate.format('YYYY-MM-DD'));
                // Perform actions with the selected date range
                console.log('Selected Date Range:', startDate, 'to', endDate);
            });
        // });
            // Button click event
            $('#import_form').on('submit', function () {
                event.preventDefault();
                // Initiate DataTable AJAX request
                $('#business_data').DataTable().ajax.reload();
            });

            // DataTable initialization
            var businessdataTable = $('#business_data').DataTable({
                oLanguage: {
                    sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'
                },
                processing: true,
                serverSide: true,
                lengthMenu: [],
                paging: false,
                searching:false, 
                pageLength: 40,
                ajax: {
                    url: '{{ route('report.filter') }}',
                    type: 'POST',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: function (d) {
                        // Pass date range and supplier ID when making the request
                        d.year = $('#year').val();
                        d.core = $('#core').val();
                        d.supplier = $('#supplier_id').val();
                        d.account_name = $('#account_name').val();
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
                    { data: 'sku', name: 'sku', 'orderable': false, 'searchable': false, title: 'Sku' },
                    { data: 'description', name: 'description', 'orderable': false, 'searchable': false, title: 'Description' },
                    { data: 'uom', name: 'uom', 'orderable': false, 'searchable': false, title: 'UOM' },
                    { data: 'category', name: 'category', 'orderable': false, 'searchable': false, title: 'Category' },
                    { data: 'quantity_purchased', name: 'quantity_purchased', 'orderable': false, 'searchable': false, title: 'Quantity Purchased' },
                    { data: 'total_spend', name: 'total_spend', 'orderable': false, 'searchable': false, title: 'Total Spend' },
                    { data: 'unit_price_q1_price', name: 'unit_price_q1_price', 'orderable': false, 'searchable': false, title: 'Unit Q1 Price' },
                    { data: 'unit_price_q2_price', name: 'unit_price_q2_price', 'orderable': false, 'searchable': false, title: 'Unit Q2 Price' },
                    { data: 'unit_price_q3_price', name: 'unit_price_q3_price', 'orderable': false, 'searchable': false, title: 'Unit Q3 Price' },
                    { data: 'unit_price_q4_price', name: 'unit_price_q4_price', 'orderable': false, 'searchable': false, title: 'Unit Q4 Price' },
                    { data: 'web_price_q1_price', name: 'web_price_q1_price', 'orderable': false, 'searchable': false, title: 'Web Q1 Price' },
                    { data: 'web_price_q2_price', name: 'web_price_q2_price', 'orderable': false, 'searchable': false, title: 'Web Q2 Price' },
                    { data: 'web_price_q3_price', name: 'web_price_q3_price', 'orderable': false, 'searchable': false, title: 'Web Q3 Price' },
                    { data: 'web_price_q4_price', name: 'web_price_q4_price', 'orderable': false, 'searchable': false, title: 'Web Q4 Price' },
                    { data: 'lowest_price', name: 'lowest_price', 'orderable': false, 'searchable': false, title: 'Lowest Price' },
                ],
            });
            
            $('#business_data_length').hide();
            $('#business_data tbody').on('click', 'button', function () {
            var tr = $(this).closest('tr');
            var row = businessdataTable.row(tr);

            if ( row.child.isShown() ) {
                // This row is already open - close it
                row.child.hide();
                tr.removeClass('shown');
            }
            else {
                // Open this row
                row.child( format(row.data()) ).show();
                tr.addClass('shown');
                }
        });
        
            $('#downloadCsvBtn').on('click', function () {
                // Trigger CSV download
                downloadCsv();
            });

            function downloadCsv() {
                // You can customize this URL to match your backend route for CSV download
                var csvUrl = '{{ route('report.export-csv') }}';

                // Add query parameters for date range and supplier ID
                // csvUrl += '?start=' + $('#start_date').val() + '&end=' + $('#end_date').val() + '&supplierId=' + $('#supplierId').val();
                csvUrl += '?account_name=' + $('#account_name').val();
                // Open a new window to download the CSV file
                window.open(csvUrl, '_blank');
            }
        });
    </script>
@endsection