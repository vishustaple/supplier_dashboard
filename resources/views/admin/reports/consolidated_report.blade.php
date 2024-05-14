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
                    <div class="row align-items-start border-bottom pb-3 mb-4">
                        <div class="form-group   col-md-3  mb-0">
                            <div class="form-check">
                                <input class="form-check-input checkboxs" name="supplier_id[]" type="checkbox" value="all" id="allCheckBox">
                                <label class="form-check-label" for="defaultCheck1">All Suppliers</label>
                            </div>
                            @if(isset($categorySuppliers))
                                @foreach($categorySuppliers as $supplier)
                                    <div class="form-check">
                                        @if(!empty($supplier->supplier_name))
                                            <input class="form-check-input checkboxs" name="supplier_id[]" type="checkbox" value="{{ $supplier->id }}">
                                            <label class="form-check-label" for="defaultCheck1">{{ $supplier->supplier_name }}</label>
                                        @endif
                                    </div>
                                @endforeach    
                            @endif
                        </div>
                        <div class="form-group relative col-md-9 mb-0">  
                            <label for="enddate">Select Date:</label>
                            <div class="row">
                                <div class="col-6 align-items-center">
                                    <label for="enddate" class="pe-2">From:</label>
                                    <input class="form-control" type="text" name="start_date" id="start_date">
                                </div>
                                <div class="col-6 align-items-center">
                                    <label for="enddate" class="pe-2">To:</label>
                                    <input class="form-control" type="text" name="end_date" id="end_date">
                                </div>  
                            </div>
                            <div class="row pt-3 align-items-center">
                                <div class="form-group relative col-md-6 col-4 mb-0">
                                    <label>Select Quarter</label>
                                    <select class="form-select" name="select_dates" id="select_dates">
                                        <option value="0" selected>Select</option>
                                        <option value="1">Last Quarter</option>
                                        <option value="2">Last Year</option>
                                        <option value="3">Last Month</option>
                                        <option value="4">Last 6 Months</option>
                                    </select>
                                    <input type="text" name="date1" value="" id="date1" hidden>
                                    <input type="text" name="date2" value="" id="date2" hidden>
                                </div>
                                <div class="form-group col-md-6 mb-0">
                                    <label for="selectBox">Select Account:</label>
                                    <select id="account_name" name="account_name" class="form-control"></select>
                                </div>
                                <div class="col-md-6 mt-1 mb-0 ms-auto text-end">
                                    <button id="submitBtn" class="btn btn-primary m-1">Submit</button>
                                    <button id="downloadCsvBtn" class="btn-success btn m-1" title="Csv Download"><i class="fa-solid me-2 fa-file-csv"></i>Download</button>
                                    <button id="downloadPdfBtn" class="btn-danger btn m-1 disabled" title="Pdf Download"><i class="fa-solid me-2 fa-file-pdf"></i>PDF</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                <table class="data_table_files" id="consolidated_supplier_data"></table>
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

        #consolidated_supplier_data{
            display:block;
            overflow-x:auto;
        }

        #consolidated_supplier_data thead tr th {
            white-space: nowrap;
        }

        .form-group.check_form_labels.mb-0 {
            display: flex;
            flex-wrap: wrap;
        }

        .form-group.check_form_labels.mb-0 .form-check {
            width: 50%;
            /* font-size: 15px; */
        }
        .select2-container .select2-selection--single{
            padding-top: 5px;
            height: 38px !important;
        }
    </style>
    <!-- Include Date Range Picker JavaScript -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
    <!-- <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script> -->
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#select_dates').on('change', function(){
                var selectValue = $(this).val();

                if (selectValue == 0) {
                    $('#start_date').prop('disabled', false);
                    $('#end_date').prop('disabled', false);
                } else {
                    $('#start_date').prop('disabled', true);
                    $('#end_date').prop('disabled', true);
                }
            });

            $('.checkboxs').click(function(){
                var anyChecked = false;
        
                // Loop through each checkbox with class "myCheckbox"
                $('.checkboxs').each(function(){
                    // Check if the current checkbox is checked
                    if ($(this).is(':checked')) {
                        anyChecked = true;
                        // Exit the loop if any checkbox is checked
                        return false;
                    }
                });
                
                // Output the result
                if (anyChecked) {
                    $('#account_name').val('').trigger('change');
                }
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
                                supplier_check: true,
                                supplier_array: checkedValues, // add your extra parameter here
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
                    minimumInputLength: 1
                });
            }

            selectCustomer ()

            $('input[name="start_date"]').datepicker({
                changeMonth: true,
                changeYear: true,
                yearRange: "-7:+0",
            });
            $('input[name="end_date"]').datepicker({
                changeMonth: true,
                changeYear: true,
                yearRange: "-7:+0",
            });

            $("#select_dates").on('change', function() {
                var selectedRange = $(this).val(); // Get the selected range
                var startDate, endDate;

                // Calculate start and end dates based on the selected range
                switch(selectedRange) {
                    case '1':
                        startDate = moment().clone().subtract(3, 'months').startOf('quarter');
                        endDate = startDate.clone().endOf('quarter');
                        break;
                    case '2':
                        startDate = moment().subtract(1, 'year').startOf('year').format('YYYY-MM-DD');
                        endDate = moment().subtract(1, 'year').endOf('year').format('YYYY-MM-DD');
                        break;
                    case '3':
                        startDate = moment().subtract(1, 'month').startOf('month').format('YYYY-MM-DD');
                        endDate = moment().subtract(1, 'month').endOf('month').format('YYYY-MM-DD');
                        break;
                    case '4':
                        startDate = moment().subtract(7, 'month').startOf('month').format('YYYY-MM-DD');
                        endDate = moment().subtract(1, 'month').endOf('month').format('YYYY-MM-DD');
                        break;
                    // Add additional cases for other predefined ranges if needed
                }

                $('#date1').val(startDate);
                $('#date2').val(endDate);
                
            });

            // // Event handler for when the user applies the date range
            // $('input[name="dates"]').on('apply.daterangepicker', function(ev, picker) {
            //     // Access the selected date range
            //     $('#start_date').val(picker.startDate.format('YYYY-MM-DD')),
            //     $('#end_date').val(picker.endDate.format('YYYY-MM-DD'));
            //     // Perform actions with the selected date range
            // });

            // Button click event
            $('#import_form').on('submit', function () {
                event.preventDefault();
                // Initiate DataTable AJAX request
                $('#consolidated_supplier_data').DataTable().ajax.reload();
            });

            $('#allCheckBox').change(function() {
                if ($(this).is(':checked')) {
                    $('.checkboxs').not(this).prop('checked', false).prop('disabled', true);
                } else {
                    $('.checkboxs').prop('disabled', false);
                }
            });

            // DataTable initialization
            var consolidateddataTable = $('#consolidated_supplier_data').DataTable({
                oLanguage: {
                    sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'
                },
                processing: true,
                serverSide: true,
                lengthMenu: [40],
                paging: true,
                pageLength: 40,
                ajax: {
                    url: '{{ route('consolidated-report.filter') }}',
                    type: 'POST',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: function (d) {
                        var checkedValues = [];
                        $('.checkboxs:checked').each(function() {
                            checkedValues.push($(this).val());
                        });

                        d.supplier_id = checkedValues;
                        d.account_name = $('#account_name').val();
                        if ($("#select_dates").val() == 0) {
                            // Pass date range and supplier ID when making the request
                            d.end_date = $.datepicker.formatDate('yy-mm-dd', $('#end_date').datepicker('getDate'));
                            d.start_date = $.datepicker.formatDate('yy-mm-dd', $('#start_date').datepicker('getDate'));
                        } else {
                            d.start_date = $('#date1').val();
                            d.end_date =  $('#date2').val();
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
                },

                columns: [
                    { data: 'supplier_name', name: 'supplier_name', title: 'Supplier Name' },
                    { data: 'account_name', name: 'account_name', title: 'Account Name' },
                    { data: 'spend', name: 'spend', title: 'Spend', 'searchable': false },
                    { data: 'category', name: 'category', title: 'Category', 'orderable': false, 'searchable': false },
                ],
            });

            $(document).on('change', '.checkboxMain', function() {
                var checkedValues = [];
                $('.checkboxMain:checked').each(function() {
                    checkedValues.push($(this).val());
                });
                console.log(checkedValues);
            });

            $('#downloadCsvBtn').on('click', function () {
                // Trigger CSV download
                downloadCsv();
            });

            function downloadCsv() {
                // You can customize this URL to match your backend route for CSV download
                var csvUrl = '{{ route('consolidated-report.export-csv') }}', order = consolidateddataTable.order();;

                var checkedValues = [];
                $('.checkboxs:checked').each(function() {
                    checkedValues.push($(this).val());
                });

                if ($("#select_dates").val() == 0) {
                    var start = $.datepicker.formatDate('yy-mm-dd', $('#start_date').datepicker('getDate')),
                    end = $.datepicker.formatDate('yy-mm-dd', $('#end_date').datepicker('getDate'));
                } else {
                    var start = $('#date1').val(),
                    end = $('#date2').val();
                }
             
                // Add query parameters for date range and supplier ID
                csvUrl += '?start_date=' + start + '&end_date=' + end + '&column=' + order[0][0] + '&order=' + order[0][1] + '&supplier_id=' + checkedValues + '&account_name=' + $('#account_name').val();

                // Open a new window to download the CSV file
                window.open(csvUrl, '_blank');
            }
        });
    </script>
@endsection