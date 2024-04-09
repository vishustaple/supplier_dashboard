@extends('layout.app', ['pageTitleCheck' => $pageTitle])
@section('content')
    <div id="layoutSidenav">
        @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
        <div id="layoutSidenav_content">
            <div class="m-1 d-md-flex border-bottom pb-3 mb-3 flex-md-row align-items-center justify-content-between">
                <h3 class="mb-0 ps-2 ">Manage Supplier</h3>
            </div>
            <div class="container">
                <table id="supplier_data" class="data_table_files">
                <!-- Your table content goes here -->
                </table>
            </div>
        </div>
    </div>
    
    <script>
        $('#supplier_data').DataTable({
            "paging": true,   // Enable pagination
            "ordering": true, // Enable sorting
            "searching": true, // Enable search
            "pageLength": 40,
            "lengthChange":false,
            "data": <?php if(isset($data)){echo $data;}  ?>,
            "columns": [
                { title: 'Supplier Name' },
                { title: 'Title' },
                { title: 'Name' },
                { title: 'Email' },
                { title: 'Phone' },
                { title: 'Status' },
            ]
        });
    </script>
@endsection