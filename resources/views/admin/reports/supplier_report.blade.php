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
                <div class="row align-items-start py-3 border-top border-bottom mb-3">
                    <div class="form-group col-md-3 mb-0">
                        <label for="supplier">Select Supplier:</label>
                        <select id="supplier" name="supplier" class="form-control" required> 
                            <option value="" selected>--Select--</option>
                            @if(isset($categorySuppliers))
                                @foreach($categorySuppliers as $categorySupplier)
                                    <option value="{{ $categorySupplier->id }}">{{ $categorySupplier->supplier_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="form-group relative col-md-9 mb-0">  
                            <label for="enddate">Select Date:</label>
                            <div class="row">
                                <div class="col-4 d-flex align-items-center">
                            <label for="enddate" class="pe-2">From:</label>
                            <input class="form-control" type="text" name="start_date" id="start_date">
                                </div>
                                <div class="col-4 d-flex align-items-center">
                            <label for="enddate" class="pe-2">To:</label>
                            <input class="form-control" type="text" name="end_date" id="end_date">
                                </div>
                                <div class="form-group relative col-4 mb-0">
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
                                
                            </div>
                            <div class="row py-3 align-items-center">
                            
                        <div class="form-check relative col-8 text-end mb-0">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="volume_rebate_check" checked>
                            <label class="form-check-label" id="volume_rebate_check_label" for="volume_rebate_check">Volume Rebate</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="incentive_rebate_check" checked>
                            <label class="form-check-label" id="incentive_rebate_check_label" for="incentive_rebate_check">Incentive Rebate</label>
                        </div>
                    </div>
                    <div class="col-4 mt-2 text-end">
                        <button type="submit" class="btn btn-primary m-1">Submit</button>
                        <button id="downloadCsvBtn" class="btn-success btn m-1" title="Csv Download"><i class="fa-solid me-2 fa-file-csv"></i>Download</button>
                    </div>
</div>
                    
                   
                    <!-- Button trigger modal -->
                </div>
            </form>
            <div class="row justify-content-end py-3 header_bar" style="display:none !important;">
                <div class="col-md-4 card shadow border-0">
                    <h6 class="d-flex total_amount_header justify-content-between">Total Spend: <b style="color:#000;" id="total_amount"></b></h6>
                    <h6 class="d-flex volume_rebate_header justify-content-between">Total Volume Rebate: <b style="color:#000;" id="volume_rebate"></b></h6>
                    <h6 class="d-flex incentive_rebate_header justify-content-between">Total Incentive Rebate: <b style="color:#000;" id="incentive_rebate"></b></h6>
                    <h6 class="d-flex justify-content-between">Start Date: <b style="color:#000;" id="startDates"></b></h6>
                    <h6 class="d-flex justify-content-between">End Date: <b style="color:#000;" id="endDates"></b></h6>
                </div>
            </div>
            <table id="supplier_report_data" class="data_table_files">
                <!-- Your table content goes here -->
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

    #consolidated_supplier_data{
        display:block;
        overflow-x:auto;
    }

    #consolidated_supplier_data thead tr th {
        white-space: nowrap;
    }
</style>
<!-- Include Date Range Picker JavaScript -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
<!-- <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script> -->
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script>
    $(document).ready(function() {
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

        // Assuming your select element has id 'mySelect'
        $('#supplier').change(function() {
            // Get the selected value
            var selectedValue = $(this).val();
            
            if (selectedValue == 3) {
                $('#incentive_rebate_check').show();
                $('#incentive_rebate_check_label').show();
                $('#incentive_rebate_check').prop('checked', true);
            } else {
                $('#incentive_rebate_check').hide();
                $('#incentive_rebate_check_label').hide();
                $('#incentive_rebate_check').prop('checked', false);
            }

            // // Initiate DataTable AJAX request
            // $('#supplier_report_data').DataTable().ajax.reload();
        });

        // Button click event
        $('#import_form').on('submit', function () {
            event.preventDefault();
            if ($("#select_dates").val() == 0) {
                $('#startDates').text($('#start_date').val());
                $('#endDates').text($('#end_date').val());
            } else {
                $('#startDates').text(moment($('#date1').val()).format('MM/DD/YYYY'));
                $('#endDates').text(moment($('#date2').val()).format('MM/DD/YYYY'));
            }

            $('.header_bar').attr('style', 'display:flex !important;');

            // Initiate DataTable AJAX request
            $('#supplier_report_data').DataTable().ajax.reload();
        });

        function setPercentage() {
            var selectedValues = $('#supplier').val();
        
            if (selectedValues == 3) {
                $('#incentive_rebate_check').show();
                $('#incentive_rebate_check_label').show();
                $('#incentive_rebate_check').prop('checked', true);
            } else {
                $('#incentive_rebate_check').hide();
                $('#incentive_rebate_check_label').hide();
                $('#incentive_rebate_check').prop('checked', false);
            }

            var $html = $('<div>' + (supplierDataTable.column(2).data()[0] !== undefined ? supplierDataTable.column(2).data()[0] : '<input type="hidden" value="0"class="total_amount">') + ' ' + (supplierDataTable.column(3).data()[0] !== undefined ? supplierDataTable.column(3).data()[0] : '<input type="hidden" value="0"class="input_volume_rebate">') + ' ' + (supplierDataTable.column(4).data()[0] !== undefined ? supplierDataTable.column(4).data()[0] : '<input type="hidden" value="0" class="input_incentive_rebate">') + '</div>'),
            hiddenVolumeRebateInputValue = $html.find('.input_volume_rebate').val(),
            hiddenIncentiveRebateInputValue = $html.find('.input_incentive_rebate').val(),
            totalAmount = $html.find('.total_amount').val();

            $('#total_amount').text('$'+totalAmount);

            if ($('#volume_rebate_check').is(':checked')) {
                supplierDataTable.column('volume_rebate:name').visible(true);
                $('#volume_rebate').text((hiddenVolumeRebateInputValue !== '0' ? '$' + hiddenVolumeRebateInputValue : 'N/A'));
                $('.volume_rebate_header').attr('style', 'display:flex !important;');
            } else {
                supplierDataTable.column('volume_rebate:name').visible(false);
                $('.volume_rebate_header').attr('style', 'display:none !important;');
                $('#volume_rebate').text('');
            }

            if ($('#incentive_rebate_check').is(':checked')) {
                supplierDataTable.column('incentive_rebate:name').visible(true);
                $('#incentive_rebate').text((hiddenIncentiveRebateInputValue !== '0' ? '$' + hiddenIncentiveRebateInputValue : 'N/A'));
                $('.incentive_rebate_header').attr('style', 'display:flex !important;');
            } else {
                supplierDataTable.column('incentive_rebate:name').visible(false);
                $('.incentive_rebate_header').attr('style', 'display:none !important;');
                $('#incentive_rebate').text('');
            }
        }

        // DataTable initialization
        var supplierDataTable = $('#supplier_report_data').DataTable({
            oLanguage: {sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'},
            processing: true,
            serverSide: true, 
            lengthMenu: [40], // Specify the options you want to show
            lengthChange: false, // Hide the "Show X entries" dropdown
            searching:false, 
            pageLength: 40,
            order: [[3, 'desc']],
            ajax: {
                url: '{{ route("report.supplier_filter") }}',
                type: 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: function (d) {
                    // Pass date range and supplier ID when making the request
                    d.supplier = $('#supplier').val();
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
                if (businessdataTable.data().count() > 40) {
                    $('#business_data_paginate').show(); // Enable pagination
                } else {
                    $('#business_data_paginate').hide();
                }
            },

            columns: [
                { data: 'supplier', name: 'supplier', title: 'Supplier'},
                { data: 'account_name', name: 'account_name', title: 'Account Name'},
                { data: 'amount', name: 'amount', title: 'Spend'},
                { data: 'volume_rebate', name: 'volume_rebate', title: 'Volume Rebate'},
                { data: 'incentive_rebate', name: 'incentive_rebate', title: 'Incentive Rebate'},
            ],

            fnDrawCallback: function( oSettings ) {
                setPercentage();
            },
        });  


        $('#downloadCsvBtn').on('click', function () {
            // Trigger CSV download
            downloadCsv();
        });

        function downloadCsv() {
            // You can customize this URL to match your backend route for CSV download
            var csvUrl = '{{ route("report.export-supplier_report-csv") }}', order = supplierDataTable.order();

            if ($("#select_dates").val() == 0) {
                var start = $.datepicker.formatDate('yy-mm-dd', $('#start_date').datepicker('getDate')),
                end = $.datepicker.formatDate('yy-mm-dd', $('#end_date').datepicker('getDate'));
            } else {
                var start = $('#date1').val(),
                end = $('#date2').val();
            }

            // Add query parameters for date range and supplier ID
            csvUrl += '?start_date=' + start + '&end_date=' + end + '&column=' + order[0][0] + '&order=' + order[0][1] + '&supplier=' + $('#supplier').val();

            // Open a new window to download the CSV file
            window.open(csvUrl, '_blank');
        } 
    });        
</script>
@endsection