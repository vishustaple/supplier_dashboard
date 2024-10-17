@extends('layout.app', ['pageTitleCheck' => $pageTitle])
@section('content')
    <div id="layoutSidenav">
        @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
        <div id="layoutSidenav_content">
            <div class="container">
                <div class="m-1 mb-2 d-md-flex  pb-3 mb-3 align-items-center justify-content-between">
                    <h3 class="mb-0 ">{{ $pageTitle }}</h3>
                </div>
                <form  id="import_form"  enctype="multipart/form-data">
                    @csrf
                    <div class="row align-items-end py-3 border-top border-bottom mb-3">
                        <div class="form-group col-md-3 mb-0">
                            <label for="selectBox">Select Account:</label>
                            <select id="account_name" name="account_name" class="form-control" required></select>
                        </div>
                        <div class="form-group col-md-2 mb-0" id="dynamicFormContainer"></div>
                        <div class="form-group relative col-md-2 mb-0">  
                            <label for="enddate">Product Type:</label>
                            <select class="form-control" name="core" id="core" required>
                                <option value="1">Non-Core</option>
                                <option value="2">Core</option>
                            </select>
                        </div>
                        <div class="form-group relative col-md-2 mb-0">  
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
                        <div class="col-md-3 mt-1 mb-0 text-end">
                            <button id="submitBtn" class="btn btn-primary m-1">Submit</button>
                            <button id="downloadCsvBtn" class="btn-success btn m-1" title="Csv Download"><i class="fa-solid me-2 fa-file-csv"></i>Download</button>
                        </div>
                    </div>
                </form>
                <table class="data_table_files" id="business_data"></table>
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

        .select2-container .select2-selection--single{
            height: 38px !important;
            padding-top: 5px;
        }

        div#business_data_wrapper table thead tr th{
            padding: 10px;
        }

        div#business_data_wrapper table thead tr th:nth-child(6) {
            width: 70px !important;
            min-width: 70px;
            max-width: 70px;
            white-space: break-spaces;
            padding: 0px;
        }

        div#business_data_wrapper table thead tr th:nth-child(5) {
            width: 90px !important;
            min-width: 90px;
            max-width: 90px;
            white-space: break-spaces;
            padding: 0px;
        }
    </style>
    <!-- Include Date Range Picker JavaScript -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Change event handler for checkboxes
            $(document).on('change', '.checkboxs', function(){
                var selectedValues = [];
                $('input[name="supplier_id[]"]:checked').each(function(){
                    selectedValues.push($(this).val());
                });
                // Perform AJAX request here
            });

            // Button click event
            $('#import_form').on('submit', function () {
                event.preventDefault();
                $('#business_data').DataTable().ajax.reload();
            });

            function hideColumns() {
                var anyChecked1 = anyChecked2 = anyChecked3 = anyCheckedOther1 = anyChecked4 = anyChecked5 = anyChecked6 = anyChecked7 = anyCheckedOther = false;
                 // Loop through each checkbox with class "myCheckbox"
                 $('.checkboxs').each(function(){
                    // Check if the current checkbox is checked
                    if ($(this).is(':checked')) {
                        if ($(this).val() == 1) {
                            anyChecked1 = true;
                        }

                        if ($(this).val() == 2) {
                            anyChecked2 = true;
                        }

                        if ($(this).val() == 3) {
                            anyChecked3 = true;
                        }

                        if ($(this).val() == 4) {
                            anyChecked4 = true;
                        }

                        if ($(this).val() == 5) {
                            anyChecked5 = true;
                        }

                        if ($(this).val() == 6) {
                            anyChecked6 = true;
                        }

                        if ($(this).val() == 7) {
                            anyChecked7 = true;
                        }

                        if ($(this).val() != 3) {
                            anyCheckedOther = true;
                        }

                        if ($(this).val() != 2) {
                            anyCheckedOther1 = true;
                        }
                    }
                });

                if (anyChecked2 == true && anyCheckedOther1 == true) {
                    businessdataTable.column('uom:name').visible(true);
                }  else if (anyChecked2 == true && anyCheckedOther1 == false) {
                    businessdataTable.column('uom:name').visible(false);
                } else {
                    businessdataTable.column('uom:name').visible(true);
                }

                if (anyChecked3 == true && anyCheckedOther == true) {
                    businessdataTable.column('category:name').visible(true);
                } else if (anyChecked3 == true && anyCheckedOther == false) {
                    businessdataTable.column('category:name').visible(false);
                } else {
                    businessdataTable.column('category:name').visible(true);
                }

                if (!anyChecked3) {
                    businessdataTable.column('unit_price_q1_price:name').visible(false);
                    businessdataTable.column('unit_price_q2_price:name').visible(false);
                    businessdataTable.column('unit_price_q3_price:name').visible(false);
                    businessdataTable.column('unit_price_q4_price:name').visible(false);
                    businessdataTable.column('web_price_q1_price:name').visible(false);
                    businessdataTable.column('web_price_q2_price:name').visible(false);
                    businessdataTable.column('web_price_q3_price:name').visible(false);
                    businessdataTable.column('web_price_q4_price:name').visible(false);
                    businessdataTable.column('lowest_price:name').visible(false);
                } else {
                    businessdataTable.column('unit_price_q1_price:name').visible(true);
                    businessdataTable.column('unit_price_q2_price:name').visible(true);
                    businessdataTable.column('unit_price_q3_price:name').visible(true);
                    businessdataTable.column('unit_price_q4_price:name').visible(true);
                    businessdataTable.column('web_price_q1_price:name').visible(true);
                    businessdataTable.column('web_price_q2_price:name').visible(true);
                    businessdataTable.column('web_price_q3_price:name').visible(true);
                    businessdataTable.column('web_price_q4_price:name').visible(true);
                    businessdataTable.column('lowest_price:name').visible(true);
                }
            }

            $('#supplier_id').on('change', function(){
                $('#account_name').val('').trigger('change');
                $('#business_data').DataTable().ajax.reload();
            });

            function selectCustomer () {
                $('#account_name').select2({
                    ajax: {
                        url: "{{ route('commission.customerSearch') }}",
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            var checkedValues = [];
                            $('.checkboxs:checked').each(function() {
                                checkedValues.push($(this).val());
                            });
                            
                            var data = {
                                q: params.term, // search term
                            };

                            return data;
                        },
                        processResults: function(data) {
                            return {
                                results: data
                            };
                        },
                        cache: true
                    },
                    placeholder: "Select an account",
                    allowClear: true,
                    minimumInputLength: 1
                }).on('select2:select', function (e) {
                    var accountName = e.params.data.id;
                    // Perform AJAX request to get supplier data
                    $.ajax({
                        url: "{{ route('commission.supplierSearch') }}",
                        method: 'GET',
                        data: { account_name: accountName , check: true},
                        success: function(response){
                            // Assuming response is an array of objects with id and name properties
                            $('#dynamicFormContainer').html(' ');
                            response.forEach(function(suppliers){
                                var checkboxDiv = $('<div class="form-check">' +
                                                        '<input class="form-check-input checkboxs" name="supplier_id[]" type="checkbox" value="' + suppliers.id + '" >' +
                                                        '<label class="form-check-label" for="defaultCheck1">' + suppliers.supplier + '</label>' +
                                                    '</div>');
                                $('#dynamicFormContainer').append(checkboxDiv);
                            });
                        },
                        error: function(xhr, status, error){
                            console.error('Error fetching supplier data:', error);
                        }
                    });
                });
            }

            selectCustomer ()

            $('#allCheckBox').change(function() {
                if ($(this).is(':checked')) {
                    $('.checkboxs').not(this).prop('checked', false).prop('disabled', true);
                } else {
                    $('.checkboxs').prop('disabled', false);
                }
            });
            
            // DataTable initialization
            var businessdataTable = $('#business_data').DataTable({
                oLanguage: {sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'},
                processing: true,
                serverSide: true,
                lengthMenu: [],
                paging: false,
                info: false,
                searching: false, 
                pageLength: 40,
                ajax: {
                    url: '{{ route('report.filter') }}',
                    type: 'POST',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: function (d) {
                        var checkedValues = [];
                        $('.checkboxs:checked').each(function() {
                            checkedValues.push($(this).val());
                        });

                        // Pass date range and supplier ID when making the request
                        d.year = $('#year').val();
                        d.core = $('#core').val();
                        d.supplier = checkedValues;
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

                fnDrawCallback: function( oSettings ) {
                    hideColumns();
                },
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

            var checkedValues = [];
            $('.checkboxs:checked').each(function() {
                checkedValues.push($(this).val());
            });

            // Add query parameters for date range and supplier ID
            csvUrl += '?account_name=' + encodeURIComponent($('#account_name').val()) +
            '&supplier=' + encodeURIComponent(checkedValues) +
            '&year=' + encodeURIComponent($('#year').val()) +
            '&core=' + encodeURIComponent($('#core').val());

            // Open a new window to download the CSV file
            window.open(csvUrl, '_blank');
        }
    });
    </script>
@endsection