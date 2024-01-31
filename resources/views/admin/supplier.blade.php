<!-- resources/views/excel-import.blade.php -->


@extends('layout.app')
 @extends('layout.sidenav', ['pageTitleCheck' => 'Supplier Data'])
 @section('content')

 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => 'Supplier Data'])
    <div id="layoutSidenav_content">
        <div class="m-1 d-md-flex flex-md-row align-items-center justify-content-between">
            <h1 class="mb-0 ps-2">Supplier Data</h1>
        </div>
        <div class="container">
         
            <table id="supplier_data" class="data_table_files">
            <!-- Your table content goes here -->
            </table>
        </div>
        
    </div>
</div>
<script>
     $(document).ready(function() {
     $('#supplier_data').DataTable({
            "paging": true,   // Enable pagination
            "ordering": true, // Enable sorting
            "searching": true, // Enable search
            "lengthChange":false,
            "data": <?php if(isset($data)){echo $data;}  ?>,
            "columns": [
                { title: 'SR. No' },
                { title: 'Supplier Name' },
                // { title: 'File Name' },
                // { title: 'Processing' },
                // { title: 'Created At' },
                // { title: 'Updated At' },
                // Add more columns as needed
            ]
        });
    });
        </script>
@endsection