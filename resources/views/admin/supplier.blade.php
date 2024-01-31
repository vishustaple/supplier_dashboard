<!-- resources/views/excel-import.blade.php -->


@extends('layout.app')
 @extends('layout.sidenav')
 @section('content')

 <div id="layoutSidenav">
    @include('layout.sidenavbar')
    <div id="layoutSidenav_content">
        <div class="mx-auto py-4">
        <h2 class="mb-0">Supplier Data</h2> 
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