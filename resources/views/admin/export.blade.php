<!-- resources/views/excel-import.blade.php -->


@extends('layout.app')
 @extends('layout.sidenav')
 @section('content')

 <div id="layoutSidenav">
           @include('layout.sidenavbar')
            <div id="layoutSidenav_content">
            <div class="mx-auto py-4">
            <h2 class="mb-0">Upload Sheets</h2> 
            </div>
            <div class="container">
            @if(session('success'))
            <div class="alert alert-success" id="successMessage">
            {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div class="alert alert-danger">
            {{ session('error') }}
            </div>
            @endif
            @if ($errors->any())
            <div class="alert alert-danger">
            <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
            </ul>
            </div>
            @endif
           
            <form action="{{ route('import.excel') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="selectBox">Select Supplier:</label>
                <select id="selectBox" name="supplierselect" class="form-control"> 
                <option value="" selected>--Select--</option>
                @if(isset($categorySuppliers))
                @foreach($categorySuppliers as $categorySupplier)
                <option value="{{ $categorySupplier->id }}">{{ $categorySupplier->supplier_name }}</option>
                @endforeach
                @endif
                </select>
            </div><br>
            <div class="form-group">
            <label for="file">Choose Excel File</label>
            <input type="file" name="file" id="file" class="form-control">
            </div>
            <br>
            <button type="submit" class="btn btn-primary">Import</button>
            </form>
            <table id="example" class="display:block;">
            <!-- Your table content goes here -->
            </table>
            </div>
               @include('layout.footer')
            </div>
          
          


    </div>
    
    </body>
    <script>
    $(document).ready(function() {

            $('#example').DataTable({
            "paging": true,   // Enable pagination
            "ordering": true, // Enable sorting
            "searching": true, // Enable search
            "data": <?php echo $data; ?>,
            "columns": [
                { title: 'S. No' },
                { title: 'Supplier Name' },
                { title: 'File Name' },
                { title: 'Processing' },
                { title: 'Created At' },
                // { title: 'Updated At' },
                // Add more columns as needed
            ]
        });
    });
</script>
</html>

@endsection