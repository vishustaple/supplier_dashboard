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
                    <div class="row align-items-end border-bottom pb-3 mb-4">
                        <div class="form-group col-md-5  mb-0">
                            <label for="selectBox">Select Supplier:</label>
                            <select id="supplierId" name="supplier_id[]" class="form-control" multiple>
                                <option value="" selected>--Select--</option>
                                <option value="all">Select All</option>
                                @if(isset($categorySuppliers))
                                    @foreach($categorySuppliers as $supplier)
                                        @if(!empty($supplier->supplier_name))
                                            <option value="{{ $supplier->id }}">{{ $supplier->supplier_name }}</option>
                                        @endif
                                    @endforeach    
                                @endif
                            </select>
                        </div>
                        <div class="form-group relative col-md-2 mb-0">  
                            <label for="enddate">Select Year:</label>
                            <select class="form-control" name="year" id="year" required>
                                <option value="">--Select--</option>
                                @for ($year = 2010; $year <= date('Y'); $year++)
                                    <option value="{{$year}}">{{$year}}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="form-group relative col-md-2 mb-0">  
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
                        <div class="col-md-3 mt-1 mb-0 text-end">
                            <button id="submitBtn" class="btn btn-primary m-1">Submit</button>
                            <button id="downloadCsvBtn" class="btn-success btn m-1" title="Csv Download"><i class="fa-solid me-2 fa-file-csv"></i>Download</button>
                        </div>
                    </div>
                </form>
                <table class="data_table_files" id="consolidated_supplier_data">
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
        #consolidated_supplier_data tbody tr td:nth-child(4) {
  overflow: hidden;
  max-height: 80px !important;
  overflow-y: auto;
  display: block;
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
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
    <script>
        $(document).ready(function() {
            $('input[name="dates"]').daterangepicker();

            // Event handler for when the user applies the date range
            $('input[name="dates"]').on('apply.daterangepicker', function(ev, picker) {
                // Access the selected date range
                $('#start_date').val(picker.startDate.format('YYYY-MM-DD')),
                $('#end_date').val(picker.endDate.format('YYYY-MM-DD'));
                // Perform actions with the selected date range
                console.log('Selected Date Range:', startDate, 'to', endDate);
            });

            // Button click event
            $('#import_form').on('submit', function () {
                event.preventDefault();
                // Initiate DataTable AJAX request
                $('#consolidated_supplier_data').DataTable().ajax.reload();
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
                        // Pass date range and supplier ID when making the request
                        d.year = $('#year').val();
                        d.quarter = $('#quarter').val();
                        d.supplier_id = $('#supplierId').val();
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
                    { data: 'category', name: 'category', title: 'Category' },
                    { data: 'current_rolling_spend', name: 'current_rolling_spend', title: 'Current Rolling Spend',  'searchable': false },
                    { data: 'previous_rolling_spend', name: 'previous_rolling_spend', title: 'Previous Rolling Spend', 'searchable': false },
                ],
            });

            $('#downloadCsvBtn').on('click', function () {
                // Trigger CSV download
                downloadCsv();
            });

            function downloadCsv() {
                // You can customize this URL to match your backend route for CSV download
                var csvUrl = '{{ route('consolidated-report.export-csv') }}', order = consolidateddataTable.order();;

                // Add query parameters for date range and supplier ID
                csvUrl += '?year=' + $('#year').val() + '&quarter=' + $('#quarter').val() + '&column=' + order[0][0] + '&order=' + order[0][1] + '&supplier_id=' + $('#supplierId').val();

                // Open a new window to download the CSV file
                window.open(csvUrl, '_blank');
            }
        });
    </script>
@endsection