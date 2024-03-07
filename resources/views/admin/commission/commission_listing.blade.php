@extends('layout.app', ['pageTitleCheck' => $pageTitle])

@section('content')
<div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
    <div id="layoutSidenav_content">
        <div class="container">
            <div class="m-1 mb-2 d-md-flex border-bottom pb-3 mb-3 align-items-center justify-content-between">
                <h3 class="mb-0 ">{{ $pageTitle }}</h3>
                <a href="{{ route('commission.add-view') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Add Commissions</a>
                <!-- <button id="downloadCommissionCsvBtn" class="btn-success btn" title="Csv Download"><i class="fa-solid me-2 fa-file-csv"></i>Download</button>
                    </div> -->
            </div>
            <table class="data_table_files" id="commission_data">
                <thead>
                    <tr>
                        <th>Customer Name</th>
                        <th>Supplier</th>
                        <th>Account Number</th>
                        <th>Commission</th>
                        <th>Sales Repersantative</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                    </tr>
                </thead>
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
</style>

<!-- Include Date Range Picker JavaScript -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
<script>
    $(document).ready(function() {
        // Button click event
        $('#import_form').on('submit', function () {
            event.preventDefault();
            // Initiate DataTable AJAX request
            $('#commission_data').DataTable().ajax.reload();
        });

        // DataTable initialization
        var dataTable = $('#commission_data').DataTable({
            oLanguage: {
                sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'
            },
            processing: true,
            serverSide: true,
            pageLength: 50,
            ajax: {
                url: '{{ route("commission.filter") }}',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
            },
            beforeSend: function() {
                // Show both the DataTables processing indicator and the manual loader before making the AJAX request
                $('.dataTables_processing').show();
                $('#manualLoader').show();
            },
            complete: function() {
                // Hide both the DataTables processing indicator and the manual loader when the DataTable has finished loading
                $('.dataTables_processing').hide();
                $('#manualLoader').hide();
            },
            columns: [
                { data: 'account_name', name: 'account_name' },
                { data: 'supplier_name', name: 'supplier_name' },
                { data: 'account_number', name: 'account_number' },
                { data: 'commission', name: 'commission' },
                { data: 'sales_rep', name: 'sales_rep' },
                { data: 'start_date', name: 'start_date' },
                { data: 'end_date', name: 'end_date' },
            ],
        });

  
        $('#downloadCommissionCsvBtn').on('click', function () {
            // Trigger CSV download
            downloadCommissionCsv();
        });

        function downloadCommissionCsv() {
            // You can customize this URL to match your backend route for CSV download
            var csvUrl = '{{ route("commission.export-csv") }}';
            csvUrl += '?search=' + dataTable.search();
            // Open a new window to download the CSV file
            window.open(csvUrl, '_blank');
        }
    });
</script>
@endsection