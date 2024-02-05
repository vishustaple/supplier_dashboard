<!-- resources/views/excel-import.blade.php -->


@extends('layout.app')

 @section('content')

 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => 'Upload Sheets'])
    <div id="layoutSidenav_content">
        <div class="m-1 d-md-flex flex-md-row align-items-center justify-content-between">
                <h1 class="mb-0 ps-2">Upload Sheets</h1>
        </div>
        <div class="container">
            <div class="alert alert-success" id="successMessage" style="display:none;">
            </div>
            <div class="alert alert-danger" id="errorMessage" style="display:none;">
            </div>
        
            <form  id="import_form"  enctype="multipart/form-data">
                @csrf
                <div class="row py-4 align-items-end">
                <div class="form-group col-md-4 mb-0">
                    <label for="selectBox">Select Supplier:</label>
                    <select id="selectBox" name="supplierselect" class="form-control"> 
                    <option value="" selected>--Select--</option>
                    @if(isset($categorySuppliers))
                    @foreach($categorySuppliers as $categorySupplier)
                    <option value="{{ $categorySupplier->id }}">{{ $categorySupplier->supplier_name }}</option>
                    @endforeach
                    @endif
                    </select>
                </div>
                <div class="form-group relative col-md-3 mb-0">
                
                    <label for="enddate">Select Date:</label>
                    <input class="form-control" id="enddate" name="enddate" placeholder="Enter Your End Date " >   
                    <div class="input-overlay"></div>             
                </div>
                <div class="form-group relative col-md-3 mb-0">
                    <label for="file">Choose Excel File:</label>
                    <input type="file" name="file" id="file" class="form-control">
                    <div class="input-overlay-file"></div>  
                </div>
                <div class="col-md-2 mb-0">
                <div class="relative imprt_wrapper">
                    <button type="submit" class="btn btn-primary" id="importBtn">Import</button>
                    <div class="overlay" id="overlay"></div>
                </div>
</div>
                </div>
                
            
            
                <!-- Transparent overlay on top of the disabled input -->

            
            
                
            </form>
            <table id="example" class="data_table_files">
            <!-- Your table content goes here -->
            </table>
        </div>
        @include('layout.footer')
    </div>
</div>
<div id="page-loader">
      <div id="page-loader-wrap">
        <div class="spinner-grow text-primary" role="status">
          <span class="sr-only">Loading...</span>
        </div>
        <div class="spinner-grow text-success" role="status">
          <span class="sr-only">Loading...</span>
        </div>
        <div class="spinner-grow text-danger" role="status">
          <span class="sr-only">Loading...</span>
        </div>
        <div class="spinner-grow text-warning" role="status">
          <span class="sr-only">Loading...</span>
        </div>
        <div class="spinner-grow text-info" role="status">
          <span class="sr-only">Loading...</span>
        </div>
        <div class="spinner-grow text-light" role="status">
          <span class="sr-only">Loading...</span>
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
    </body>
    <script>
    $(document).ready(function() {
        $('#page-loader').hide();
        $( '#importBtn' ).on( "click", function( event ) {
            event.preventDefault();
            $('#page-loader').show();
            var formData = new FormData($('#import_form')[0]);
            console.log(formData);
            
            $.ajax({
                type: 'POST',
                url: '{{route('import.excel')}}', // Replace with your actual route name
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                    // if(response.error){
                    //     $('#page-loader').hide();
                    //     $('#errorMessage').text(response.error);
                    //     $('#errorMessage').css('display','block');
                    //     setTimeout(function () {
                    //     $('#errorMessage').fadeOut();
                    //     }, 5000);
                      
                    // }
                    let errorMessages = [];

                    if (response && response.error) {
                    $('#page-loader').hide();
                    // Iterate over each field in the error object
                    Object.keys(response.error).forEach(field => {
                    // Get the error messages for the current field
                    let fieldErrorMessages = response.error[field];

                    // Concatenate the field name and its error messages
                    let errorMessageText = `${fieldErrorMessages.join('</br>')}`;
                    console.log(errorMessageText);

                    // Accumulate the error messages
                    errorMessages.push(errorMessageText);
                    });
                    $('#errorMessage').html(errorMessages.join('<br>'));
                    $('#errorMessage').css('display','block');
                    }

                    if(response.success){
                        $('#page-loader').hide();
                        $('#successMessage').text(response.success);
                        $('#successMessage').css('display','block');
                        $("form")[0].reset();
                        //disable all field 
                        $('#enddate,#file,#importBtn').prop('disabled', true);
                        setTimeout(function () {
                        $('#successMessage').fadeOut();
                        window.location.reload();
                        }, 2000); 
                       
                    }
                    // Handle success response
                    console.log(response);
                },
                error: function(xhr, status, error) {
                    // Handle error response
                    console.error(xhr.responseText);
                }
            });
        });
   
        //disable all field 
        $('#enddate,#file,#importBtn').prop('disabled', true); 
        var endDateInput = document.getElementById('enddate');
            // Event handler for click on the overlay

        //check if supplier not select
        $(".input-overlay").click(function() {
        var customErrorMessage = "Please Select Supplier First.";
        var errorList = $('#errorMessage');
        errorList.text(customErrorMessage);
        errorList.css('display', 'block');
        setTimeout(function () {
        errorList.fadeOut();
        // errorList.css('display', 'none');
        }, 2000);
      });
       //check if date is not selected
      $(".input-overlay-file").click(function() {
        var customErrorMessage2 = "Please Select date First.";
        var errorList2 = $('#errorMessage');
        errorList2.text(customErrorMessage2);
        errorList2.css('display', 'block');
        setTimeout(function () {
            errorList2.fadeOut();
        // errorList2.css('display', 'none');
        }, 2000);
      });
        //check for all fields 
        $(".overlay").click(function() {
        var customErrorMessage3 = "Please Select all mandatory field.";
        console.log(customErrorMessage3);
        var errorList3 = $('#errorMessage');
        errorList3.text(customErrorMessage3);
        errorList3.css('display', 'block');
        setTimeout(function () {
        errorList3.fadeOut();
        // errorList2.css('display', 'none');
        }, 2000);
        });
        //add rangepicker on field 
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

        $('#selectBox').val('');
       // $('#startdate,#enddate,#file').prop('disabled', true);     
        $('#selectBox').on('change', function() {
            var startDateInput = $('#enddate');
            if ($(this).val().trim() !== '') {
                $(".input-overlay").css("display","none");
                startDateInput.prop('disabled', false);
            } else {
                $(".input-overlay").css("position","absolute");
                startDateInput.prop('disabled', true);
            }
            var selectedSupplier = $(this).val();
        });

       
        $('#enddate').val('');
        $('#enddate').on('change', function() {
            var EndDateInput = $('#file');  // Assuming you want to check the value of #file
            
            if ($(this).val().trim() !== '') {
                $(".input-overlay-file").css("display","none");
                EndDateInput.prop('disabled', false);
            } else {
                $(".input-overlay-file").css("position","absolute");
                EndDateInput.prop('disabled', true);
            }
        });
        $('#file').on('change', function() {
            var ImportInput = $('#importBtn');  // Assuming you want to check the value of #file
            
            if ($(this).val().trim() !== '') {
                $(".overlay").css("display","none");
                ImportInput.prop('disabled', false);
            } else {
                $(".overlay").css("position","absolute");
                ImportInput.prop('disabled', true);
            }
        });
        // $('#enddate').on('apply.daterangepicker', function(ev, picker) {
        // var startDate = picker.startDate;
        // var endDate = startDate.clone().add(1, 'month');
        //   console.log(endDate);
        // // Set the end date in the date range picker
        // $('#enddate').data('daterangepicker').setEndDate(endDate);
        // });
 
   
            $('#example').DataTable({
            "paging": true,   // Enable pagination
            "ordering": true, // Enable sorting
            "searching": true, // Enable search
            "lengthChange":false,
            "pageLength": 40,
            "data": <?php if(isset($data)){echo $data;}  ?>,
            "columns": [
                // { title: 'SR. No' },
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