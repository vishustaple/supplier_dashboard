@extends('layout.app', ['pageTitleCheck' => $pageTitle])
 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
    <div id="layoutSidenav_content">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

        <div class="container">
            <div class="m-1 mb-2 row align-items-start justify-content-between">
                <div class="col-md-4">
                    <h3 class="mb-0 ">{{ $pageTitle }}</h3>
                </div>
            </div>
            <form  id="import_form"  enctype="multipart/form-data">
                @csrf
                <div class="row align-items-end py-3 border-top border-bottom mb-3">
                    <div class="form-group col-md-2 mb-0">
                        <label for="supplier">Select Query:</label>
                        <select id="query_type" name="query_type" class="form-control" required> 
                            <option value="" selected>--Select--</option>
                            <option value="1" >Delete</option>
                            <option value="2" >Update</option>
                        </select>
                    </div>
                    <div class="form-group relative  mb-3 row">
                        <div class="col-6">
                            <label for="startdate">Select Start Date:</label>
                            <input class="form-control" id="startdate" name="dates" placeholder="Enter Your Start Date " >
                        </div>  
                        <div class="col-6">
                            <label for="enddate">Select End Date:</label>
                            <input class="form-control" id="enddate" name="dates" placeholder="Enter Your End Date " >
                        </div>
                    </div>
                    <div class="col-5 mt-2 text-end">
                        <button type="submit" class="btn btn-primary m-1">Submit</button>
                        <button id="downloadCsvBtn" class="btn-success btn m-1" title="Csv Download"><i class="fa-solid me-2 fa-file-csv"></i>Download</button>
                        <!-- <button id="downloadPdfBtn" class="btn-danger btn m-1 disabled" title="Pdf Download"><i class="fa-solid me-2 fa-file-pdf"></i>PDF</button> -->
                    </div>
                </div>
            </form>
        </div>
        <table id="supplier_report_data" class="data_table_files"></table>
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
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script>
    $(document).ready(function() {
        var defaultStartDate = moment().subtract(1, 'month').startOf('month'), // Default start date (1 month ago)
        defaultEndDate = moment(); // Default end date (today)
        
        // Set the default start date in the input
        $('#startdate').val(defaultStartDate.format('MM/DD/YYYY'));
        
        // Set the default end date in the input
        $('#enddate').val(defaultEndDate.format('MM/DD/YYYY'));

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

        const ranges = {
            'Last Quarter': [
                moment().subtract(1, 'quarter').startOf('quarter'),
                moment().subtract(1, 'quarter').endOf('quarter')
            ],
            'Last Year': [
                moment().subtract(1, 'year').startOf('year'),
                moment().subtract(1, 'year').endOf('year')
            ],
            'Last Month': [
                moment().subtract(1, 'month').startOf('month'),
                moment().subtract(1, 'month').endOf('month')
            ],
            'Last 6 Months': [
                moment().subtract(6, 'month').startOf('month'),
                moment().subtract(1, 'month').endOf('month')
            ],
        };

        const minSelectableDate = moment('2025-06-06', 'YYYY-MM-DD');

        $('#startdate').daterangepicker({
            autoApply: true,
            showDropdowns: true,
            singleDatePicker: true, // This makes the UI show only one calendar
            showCustomRangeLabel: true,
            minDate: minSelectableDate,
            minYear: minSelectableDate.year(),
            maxYear: moment().add(7, 'years').year(),
            ranges: ranges,
            locale: {
                format: 'MM/DD/YYYY'
            }
        }, function (start, end, label) {
            $('#startdate').val(start.format('MM/DD/YYYY'));

            // Only set end date if range is selected (i.e., start !== end)
            if (!start.isSame(end, 'day')) {
                $('#enddate').val(end.format('MM/DD/YYYY'));
            } else {
                // $('#enddate').val(''); // Clear end date if it's the same as start
            }
        });

        // Optional: end date picker as fallback/manual override
        $('#enddate').daterangepicker({
            autoApply: true,
            showDropdowns: true,
            singleDatePicker: true,
            minDate: minSelectableDate,
            locale: {
                format: 'MM/DD/YYYY'
            }
        }, function (start) {
            $('#enddate').val(start.format('MM/DD/YYYY'));
        });

        // Button click event
        $('#import_form').on('submit', function () {
            event.preventDefault();
            $('#startDates').text(moment($('#startdate').val(), 'MM/DD/YYYY').format('MM/DD/YYYY'));
            $('#endDates').text(moment($('#enddate').val(), 'MM/DD/YYYY').format('MM/DD/YYYY'));
            $('.header_bar').attr('style', 'display:flex !important;');

            // Initiate DataTable AJAX request
            $('#supplier_report_data').DataTable().ajax.reload();
        });

        // DataTable initialization
        var supplierDataTable = $('#supplier_report_data').DataTable({
            oLanguage: {sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'},
            processing: true,
            serverSide: true, 
            lengthMenu: [40], // Specify the options you want to show
            lengthChange: false, // Hide the "Show X entries" dropdown
            searching:false, 
            pageLength: 40,
            ajax: {
                url: '{{ route("query.type_filter") }}',
                type: 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: function (d) {
                    // Pass date range and supplier ID when making the request
                    d.query_type = $('#query_type').val();
                    d.start_date = moment($('#startdate').val(), 'MM/DD/YYYY').format('YYYY-MM-DD');
                    d.end_date = moment($('#enddate').val(), 'MM/DD/YYYY').format('YYYY-MM-DD');
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
                { data: 'event_time', name: 'event_time', title: 'Event Time' },
                { data: 'user_host', name: 'user_host', title: 'User Host' },
                { data: 'query', name: 'query', title: 'Query' },
            ],
        });

        $('#downloadCsvBtn').on('click', function () {
            // Trigger CSV download
            downloadCsv();
        });

        function downloadCsv() {
            // You can customize this URL to match your backend route for CSV download
            var csvUrl = '{{ route("query.csv-export-query") }}',
            order = supplierDataTable.order(),
            start = moment($('#startdate').val(), 'MM/DD/YYYY').format('YYYY-MM-DD'),
            end = moment($('#enddate').val(), 'MM/DD/YYYY').format('YYYY-MM-DD');

            // Add query parameters for date range and supplier ID
            csvUrl += '?start_date=' + start + '&end_date=' + end + '&column=' + order[0][0] + '&order=' + order[0][1] + '&query_type=' + $('#query_type').val();

            // Open a new window to download the CSV file
            window.open(csvUrl, '_blank');
        }
    });        
</script>
@endsection