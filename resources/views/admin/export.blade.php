


@extends('layout.app', ['pageTitleCheck' => 'Upload Sheets'])

 @section('content')

 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => 'Upload Sheets'])
    <div id="layoutSidenav_content">
        <div class="m-1 d-md-flex border-bottom pb-3 mb-3 flex-md-row align-items-center justify-content-between">
                <h3 class="mb-0 ps-2">Data Management</h3>
        </div>
        <div class="alert alert-success m-3" id="user_del_success" style="display:none;"></div>
        <div class="container">
            <div class="alert alert-success" id="successMessage" style="display:none;">
            </div>
            <div class="alert alert-danger" id="errorMessage" style="display:none;">
            </div>
        
            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form  id="import_form"  enctype="multipart/form-data">
                @csrf
                <div class="row py-4 align-items-end border-bottom mb-3">
                <div class="form-group col-md-6 mb-0">
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
                <div class="form-group relative col-md-6 mb-0">
                
                    <label for="enddate">Select Date:</label>
                    <input class="form-control" id="enddate" name="enddate" placeholder="Enter Your End Date " >   
                    <!-- <div class="input-overlay"></div>              -->
                </div>
                <div class="form-group relative col-md-6 pt-4 mb-0">
                    <label for="file">Choose Excel File:</label>
                    <input type="file" name="file" id="file" class="form-control">
                    <!-- <div class="input-overlay-file"></div>   -->
                </div>
                <div class="col-md-6 pt-4 mb-0 d-flex justify-content-end">
                    <div class="relative imprt_wrapper text-end me-2">
                        <button type="button" class="btn btn-primary invisible" id="sampleFileDownloadBtn"><i class="fa fa-cloud-download" aria-hidden="true"></i> Sample File</button>
                    </div>
                    <div class="relative imprt_wrapper text-end me-2">
                        <button type="button" class="btn btn-primary invisible" id="necessaryFieldBtn" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><i class="fa fa-list" aria-hidden="true"></i> Mandatory Columns</button>
                    </div>
                    <div class="relative imprt_wrapper text-end">
                    <button type="submit" class="btn btn-primary" id="importBtn"><i class="me-2 fa-solid fa-file-import"></i>Import</button>
                    <div class="overlay" id="overlay"></div>
                </div>
                </div>
        </div>
                
        <!-- Modal -->
        <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Columns List</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="list-group" id="necessaryFieldList">
                        <li class="list-group-item">An item</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <!-- <button type="button" class="btn btn-primary">Understood</button> -->
                </div>
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
<style>
    .spinner {
        margin: 0 auto;
        width: 70px;
        text-align: center;
    }

    .spinner div {
        width: 10px;
        height: 10px;
        background-color: #333;
        border-radius: 100%;
        display: inline-block;
        animation: sk-bouncedelay 1.2s infinite ease-in-out both;
    }

    .spinner  .bounce1 {
        animation-delay: -0.32s;
    }

    .spinner  .bounce2 {
        animation-delay: -0.16s;
    }

    @-webkit-keyframes sk-bouncedelay {
        0%, 80%, 100% {
            transform: scale(0);
        }

        40% {
            transform: scale(1);
        }
    }

    @keyframes sk-bouncedelay {
        0%, 80%, 100% {
            transform: scale(0);
        }

        40% {
            transform: scale(1);
        }
    }
      
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
    .file_td{
  width: 388px;
  display: block;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
  text-overflow: ellipsis;
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
                    if(response.error){
                        $('#page-loader').hide();
                        $('#errorMessage').text(response.error);
                        $('#errorMessage').css('display','block');
                        setTimeout(function () {
                        $('#errorMessage').fadeOut();
                        }, 5000);
                      
                    }
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
                        location.reload();
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
        // $('#enddate,#file,#importBtn').prop('disabled', true); 
        var endDateInput = document.getElementById('enddate');
            // Event handler for click on the overlay

        //check if supplier not select
    //     $(".input-overlay").click(function() {
    //     var customErrorMessage = "Please Select Supplier First.";
    //     var errorList = $('#errorMessage');
    //     errorList.text(customErrorMessage);
    //     errorList.css('display', 'block');
    //     setTimeout(function () {
    //     errorList.fadeOut();
    //     // errorList.css('display', 'none');
    //     }, 2000);
    //   });
       //check if date is not selected
    //   $(".input-overlay-file").click(function() {
    //     var customErrorMessage2 = "Please Select date First.";
    //     var errorList2 = $('#errorMessage');
    //     errorList2.text(customErrorMessage2);
    //     errorList2.css('display', 'block');
    //     setTimeout(function () {
    //         errorList2.fadeOut();
    //     // errorList2.css('display', 'none');
    //     }, 2000);
    //   });
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
            var dataIdValue = $(this).val(); // Replace with your dynamic value
            
            // Creating a multidimensional array
            var multiArray = [
                [],
                ['SOLD TO NAME', 'SOLD TOACCOUNT', 'ON-CORESPEND'],
                ['Track Code', 'Track Code Name', 'Sub track Code', 'Sub Track Code Name', 'Account Name', 'Account Number', 'Actual Price Paid', 'Invoice Number', 'Bill Date'],
                ['CUSTOMER GRANDPARENT ID', 'CUSTOMER GRANDPARENT NM', 'CUSTOMER PARENT ID', 'CUSTOMER PARENT NM', 'CUSTOMER ID', 'Total Spend', 'Invoice #', 'Shipped Date'],
                ['MASTER_CUSTOMER', 'MASTER_CUSTOMER', 'ADJGROSSSALES', 'INVOICENUMBER', 'INVOICEDATE'],
                ['Customer Name', 'Customer Num', 'Current List', 'Invoice Num', 'Invoice Date'],
                ['Leader customer 2', 'Leader customer 3', 'Leader customer 4', 'Leader customer 5', 'Leader customer 6', 'Leader customer 1', 'Sales Amount - P', 'Billing Document', 'Billing Date'],
                ['Account ID'],
            ],

            // Define the list items content (you can fetch this dynamically if needed)
            listItemsContent = multiArray[dataIdValue];
            console.log(listItemsContent);
            // Clear existing list items (if any)
            $("#necessaryFieldList").empty();

            // Add new list items
            $.each(listItemsContent, function(index, content) {
                $("#necessaryFieldList").append("<li>" + content + "</li>");
            });

            if (dataIdValue != '') {
                necessaryFieldBtn
                $('#necessaryFieldBtn').removeClass('invisible');
                $('#sampleFileDownloadBtn').removeClass('invisible');
                // Set data-id attribute
                $('#sampleFileDownloadBtn').attr('data-id', dataIdValue);
            } else {
                $('#sampleFileDownloadBtn').addClass('invisible');
                $('#necessaryFieldBtn').addClass('invisible');
            }

            var startDateInput = $('#enddate');
            if ($(this).val() == '2') {
                $(".input-overlay").css("display","none");
                startDateInput.prop('disabled', false);
            } else {
                $(".input-overlay").css("position","absolute");
                startDateInput.prop('disabled', true);
            }
            var selectedSupplier = $(this).val();
        });
       
        $('#sampleFileDownloadBtn').on('click', function() {
            // Optionally, you can also retrieve the data-id attribute value
            var retrievedDataIdValue = $(this).data('id');
            var xlsxUrl = "{{ route('file.download') }}";

            // Append the ID to the URL if it's not null
            if (retrievedDataIdValue !== null) {
                xlsxUrl += "/" + retrievedDataIdValue;
            }

            // Open a new window to download the XLSX file
            window.open(xlsxUrl, '_blank');
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
     
   
       


         var exportTable =  $('#example').DataTable({
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
                { title: 'Uploaded By' },
                { title: 'Deleted By' },
                { title: 'Date' },
                { title: 'Action' },
                // { title: 'Updated At' },
                // Add more columns as needed
            ],
            "rowCallback": function(row, data, index) {
                // Loop through each cell in the row
                $('td', row).each(function() {
                    // Check if the cell contains a button with a specific class
                    if ($(this).find('button.invisible').length) {
                        $(row).css('background-color','#f09b9b');
                    }
                });
            }
            
        });
        if (exportTable.data().count() > 40) {
            // console.log("here");
            $('#example_paginate').show(); // Enable pagination
        } else {
            console.log("here");
            $('#example_paginate').hide();
        }
        
        $(document).on('click','.remove',function(){               
            var id = $(this).attr('data-id');
            
            swal.fire({
                title: "Oops....",
                text: "Are you sure you want to delete this file?",
                icon: "error",
                showCancelButton: true,
                confirmButtonText: 'YES',
                cancelButtonText: 'NO',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $(this).attr('disabled', true); // Disable the element
                    window.location.href = "{{ route('upload.delete') }}/"+id
                } 
            });
        });
    });
</script>
</html>

@endsection