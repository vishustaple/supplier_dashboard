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
            <div class="alert alert-danger" id="errorContainer" style="display:none;">
         
            </div>
            @if(session('error'))
            <div class="alert alert-danger" >
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
            <!-- <label for="startdate">Start Date</label>
            <input  class="form-control" id="startdate" name="startdate"
            placeholder="Enter Your Start Date ">
            </div><br> -->
            <div id="wrapper">
            <label for="enddate">Select Date</label>
            <input class="form-control" id="enddate" name="enddate" placeholder="Enter Your End Date " >                
            </div>
            </div>
            <br>
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
 <!-- Include Date Range Picker JavaScript -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
    </body>
    <script>
    $(document).ready(function() {
        $('#startdate,#enddate,#file').prop('disabled', true);   
        var endDateInput = document.getElementById('enddate');
        
        console.log(endDateInput);
        $('#wrapper').on('click', function () {
        console.log('Click event triggered!');
        //     alert("here");
        // if (endDateInput.disabled) {
            if ($('#enddate').prop('disabled')) {
        // Your custom error message
        var customErrorMessage = "Please Select Supplier First.";

        // Find the ul element within the error container
        var errorList = $('#errorContainer');
        errorList.text(customErrorMessage);
        errorList.css('display', 'block');
        // Create a new li element with the custom error message
        // var newLi = $('<li>').text(customErrorMessage);

        // // Append the new li element to the ul
        // errorList.append(newLi);
        //     $().get();
        // $('.alert .alert-danger').html("Please Select Supplier First.");
        console.log('The "Select Date" input is disabled.');
        } else {
        console.log('The "Select Date" input is not disabled.');
        }
        });
        $('#selectBox').val('');
       // $('#startdate,#enddate,#file').prop('disabled', true);     
        $('#selectBox').on('change', function() {
            var startDateInput = $('#enddate');
            if ($(this).val().trim() !== '') {
                startDateInput.prop('disabled', false);
            } else {
                startDateInput.prop('disabled', true);
            }
            var selectedSupplier = $(this).val();
        });

        
        $('#enddate').daterangepicker({  
            showDropdowns: false,
            linkedCalendars: false,
            isInvalidDate: function(date) {
            // Disable dates more than one month from the selected start date
            var startDate = $('#enddate').data('daterangepicker').startDate;
            var endDateLimit = moment(startDate).add(1, 'month');
            return date.isAfter(endDateLimit);
        }
        });
        $('#enddate').val('');
        $('#enddate').on('change', function() {
            var startDateInput = $('#file');  // Assuming you want to check the value of #file
            
            if ($(this).val().trim() !== '') {
            startDateInput.prop('disabled', false);
            } else {
            startDateInput.prop('disabled', true);
            }
        });
        $('#enddate').on('apply.daterangepicker', function(ev, picker) {
        var startDate = picker.startDate;
        var endDate = startDate.clone().add(1, 'month');
          console.log(endDate);
        // Set the end date in the date range picker
        $('#enddate').data('daterangepicker').setEndDate(endDate);
        });
            $('#example').DataTable({
            "paging": true,   // Enable pagination
            "ordering": true, // Enable sorting
            "searching": true, // Enable search
            "data": <?php if(isset($data)){echo $data;}  ?>,
            "columns": [
                { title: 'SR. No' },
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
daterangepicker
@endsection