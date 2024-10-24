@extends('layout.app', ['pageTitleCheck' => $pageTitle])
@section('content')
    <div id="layoutSidenav">
        @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
        <div id="layoutSidenav_content">
            <div class="container">
                <div class="m-1 mb-2 d-md-flex border-bottom pb-3 mb-3 align-items-center justify-content-between">
                    <h3 class="mb-0 ">{{ $pageTitle }}</h3>
                </div>
                <div class="row align-items-end border-bottom pb-3 mb-4">
                    <table class="data_table_files" id="catalog_data"></table>
                </div>
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
            margin-top: 20%;
        }
    </style>
    <script>
        $(document).ready(function() {
            // Button click event
            $('#import_form').on('submit', function () {
                event.preventDefault();
                // Initiate DataTable AJAX request
                $('#catalog_data').DataTable().ajax.reload();
            });

            // DataTable initialization
            var dataTable = $('#catalog_data').DataTable({
                oLanguage: {
                    sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'
                },
                processing: true,
                serverSide: true,
                pageLength: 50,
                ajax: {
                    url: '{{ route("catalog.filter") }}',
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
                    { data: 'supplier_name', name: 'supplier_name', title: 'Supplier Name' },
                    { data: 'sku', name: 'sku', title: 'Sku' },
                    { data: 'description', name: 'description', title: 'Description' },
                    { data: 'price', name: 'price', title: 'Amount' },
                    { data: 'id', name: 'id', title: 'Detail' },
                ],
            });
    
            $('#downloadCatalogCsvBtn').on('click', function () {
                // Trigger CSV download
                downloadCatalogCsv();
            });
            $('#catalog_data_length').hide();

            function downloadCatalogCsv() {
                // You can customize this URL to match your backend route for CSV download
                var csvUrl = '{{ route("catalog.export-csv") }}';
                csvUrl += '?search=' + dataTable.search();
                // Open a new window to download the CSV file
                window.open(csvUrl, '_blank');
            }
        });
    </script>
@endsection