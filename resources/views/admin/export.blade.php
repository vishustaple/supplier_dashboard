


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
                        <label for="selectBox">Supplier Format:</label>
                        <select id="selectBox" name="supplierselect" class="form-control"> 
                        <option value="" selected>--Select--</option>
                        @if(isset($categorySuppliers))
                        @foreach($categorySuppliers as $categorySupplier)
                        <option value="{{ $categorySupplier->id }}">{{ $categorySupplier->supplier_name }}</option>
                        @endforeach
                        @endif
                        </select>
                    </div>
                    <div id="enddates" class="form-group invisible relative col-md-6 mb-0">
                        <label for="enddate">Select Date:</label>
                        <input class="form-control " id="enddate" name="enddate" placeholder="Enter Your End Date " >
                    </div>
                    <div class="form-group relative col-md-6 pt-4 mb-0">
                        <label for="file">Usage Data Import:</label>
                        <input type="file" name="file" id="file" class="form-control">
                    </div>
                    <div class="col-md-6 pt-4 mb-0 d-flex justify-content-end">
                        <div class="relative imprt_wrapper text-end me-2">
                            <a id="sampleFileDownloadBtn" class="btn btn-primary invisible" href="#"><i class="fa fa-cloud-download" aria-hidden="true"></i> Sample File</a>
                        </div>
                        <div class="relative imprt_wrapper text-end me-2">
                            <button type="button" class="btn btn-primary invisible" id="necessaryFieldBtn" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><i class="fa fa-list" aria-hidden="true"></i> Columns List</button>
                        </div>
                        <div class="relative imprt_wrapper text-end">
                            <button type="submit" class="btn btn-primary" id="importBtn"><i class="me-2 fa-solid fa-file-import"></i>Import</button>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Modal -->
            <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable modal_custom">
                    <form>
                    <div class="modal-content">
                    <div class="modal-header flex-wrap">
                        <h3 class="modal-title" id="staticBackdropLabel">Fields List</h3>
                        <h5 id="sup_name" class="w-100 pt-2"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="close_popup"></button>
                    </div>
                    <div class="modal-body p-2">
                        <div class="row list_filed m-3  border border-secondary">
                    <table class="table column_table" id="table_column">
                        <thead>
                            <tr>
                                <th>Sr. No</th>
                                <th>Fields</th>
                                <th>Required</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <!-- Table rows will be appended here -->
                        </tbody>
                    </table>
                        </div>
                        
                    </div>
                    <div class="modal-footer">
                         <button type="button" class="btn btn-primary" id="saveChangesBtn">Save Changes</button>
                         <!-- <button type="button" class="btn btn-danger" id="closeBtn">Close</button> -->
                         <button type="button" class="btn btn-danger " data-bs-dismiss="modal" aria-label="Close" id="close_popup2">Close</button>
                        <!-- <button type="button" class="btn btn-primary">Understood</button> -->
                    </div>
                    </div>
                    </form>
                </div>
            </div>

            <table id="example" class="data_table_files">
            <!-- Your table content goes here -->
            </table>
        </div>
        @include('layout.footer')
    </div>
</div>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
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
    .list_filed .list-group-item {
        font-size: 14px;
        padding: 5px 10px 5px 10px;
        text-transform: capitalize !important;
        border: 0px;
        min-height: 31px;
    }
    #necessaryFieldList .list-group-item:nth-child(2n),
        #necessaryFieldList1 .list-group-item:nth-child(2n) {
        background-color: #cccccc5e !important;
    }
    .list_filed .list_heading{
        background-color: #b17828; 
        padding: 5px 10px 5px 10px;
        color: #fff;
        font-size: 17px;
    }
    #necessaryFieldList1 .list-group-item {
    color: #008000;
    }
    .modal_custom.modal-dialog.modal-dialog-scrollable {
    max-width: 800px;
}

.modal_custom.modal-dialog.modal-dialog-scrollable .edit_column {
    padding: 0px;
}

.column_table tbody#tableBody tr td:nth-child(3n) {
    color: green;
}
table.table.column_table thead {
    position: sticky;
    top: -10px;
}
button#close_popup {
    position: absolute;
    right: 15px;
    top: 15px;
}
button#closeErrorMessage {
    position: absolute;
    right: 20px;
    top: 5px;
}
div#errorMessage {
    position: relative;
}
</style>
 <!-- Include Date Range Picker JavaScript -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
    </body>
    <script>
    $(document).ready(function() {
        $('#page-loader').hide();
        $('#importBtn').on( "click", function(event) {
            event.preventDefault();
            $('#page-loader').show();
            var button = document.getElementById('importBtn'),
            formData = new FormData($('#import_form')[0]);
            
            button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Loading';
            button.disabled = true;
        
            $.ajax({
                type: 'POST',
                url: "{{route('import.excel')}}", // Replace with your actual route name
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                    if(response.error){
                       
                        console.log("error from first");
                        $('#page-loader').hide();
                        $('#errorMessage').text(response.error);
                        $('#errorMessage').css('display','block');
                         // Adding close button
                        $('#errorMessage').append('<button type="button" id="closeErrorMessage"class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>');

                        // Functionality to close the error message when close button is clicked
                        $('#closeErrorMessage').on('click', function() {
                        $('#errorMessage').fadeOut();
                        $('#import_form')[0].reset();
                        });
                        // setTimeout(function () {
                        // $('#errorMessage').fadeOut();
                        // }, 10000);
                      
                    }
                    let errorMessages = [];

                    if (response && response.error) {

                        console.log("error from second");
                        button.innerHTML = '<i class="me-2 fa-solid fa-file-import"></i> Import';
                        button.disabled = false;

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
                        $('#errorMessage').append('<button type="button" id="closeErrorMessage"class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>');

                        // Functionality to close the error message when close button is clicked
                        $('#closeErrorMessage').on('click', function() {
                        $('#errorMessage').fadeOut();
                        $('#import_form')[0].reset();
                        });
                    }

                    if(response.success){
                        document.getElementById('importBtn').disabled = false;
                        button.innerHTML = '<i class="me-2 fa-solid fa-file-import"></i> Import';
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
                    // console.log(response);
                },
                error: function(xhr, status, error) {
                    // Handle error response
                    console.error(xhr.responseText);
                }
            });
        });

        $('#selectBox').val('');
       // $('#startdate,#enddate,#file').prop('disabled', true);     
        $('#selectBox').on('change', function() {
           
            var suppliername =  $(this).find("option:selected").text()
            console.log(suppliername);
            $('#sup_name').text(suppliername);
            var dataIdValue = $(this).val(); // Replace with your dynamic value
            
            // if (dataIdValue == 1) {
            //     //add rangepicker on field 
            //     $('#enddate').daterangepicker({  
            //         showDropdowns: false,
            //         linkedCalendars: false,
            //         isInvalidDate: function(date) {
            //             // Disable dates more than one month from the selected start date
            //             var startDate = $('#enddate').data('daterangepicker').startDate;
            //             var endDateLimit = moment(startDate).add(1, 'month');
            //             return date.isAfter(endDateLimit);
            //         }
            //     });
            // }
             
            $.ajax({
                type: 'GET',
                url: '{{ route("manage.columns") }}', // Replace with your actual route name
                data: { dataIdValue: dataIdValue },
                success: function(response) {
                     console.log(response);
                    $("#tableBody").empty();
                    var i=1;
                    response.forEach(function(column) {
                   
                    var requiredIcon = column.required == 1 ? '<i class="fa-solid fa-check"></i>' : '';
                    var editButton = '<button class="btn btn-link edit_column" type="button" data-id="' + column.id + '"><i class="fas fa-edit"></i></button>';

                    // Append table row
                    $("#tableBody").append("<tr><td>" + i +"</td><td>" + column.field_name + "</td><td>" + requiredIcon + "</td><td>" + editButton + "</td></tr>");
                    i++;
                    });
                    },
                    error: function(xhr, status, error) {
                
                    }
            });

            if (dataIdValue != '') {
                $('#necessaryFieldBtn').removeClass('invisible');
                $('#sampleFileDownloadBtn').removeClass('invisible');
                var xlsxUrl = "{{ route('file.download') }}/"+dataIdValue;

                // Set the href attribute of the anchor tag
                $('#sampleFileDownloadBtn').attr('href', xlsxUrl);
            } else {
                $('#sampleFileDownloadBtn').addClass('invisible');
                $('#necessaryFieldBtn').addClass('invisible');
            }

            // var startDateInput = $('#enddate');
            // if ($(this).val() == '1') {
            //     $(".input-overlay").css("display","none");
            //     // startDateInput.prop('disabled', false);
            //     $('#enddates').removeClass('invisible');
            // } else {
            //     $(".input-overlay").css("position","absolute");
            //     $('#enddates').addClass('invisible');
            // }
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

         var exportTable =  $('#example').DataTable({
            "paging": true,   // Enable pagination
            "ordering": false, // Enable sorting
            "searching": true, // Enable search
            "lengthChange":false,
            "pageLength": 40,
            "data": <?php if(isset($data)){ echo $data; }  ?>,
            "columns": [
                { title: 'Supplier' },
                { title: 'File Name' },
                { title: 'Status' },
                { title: 'Uploaded By' },
                { title: 'Date' },
                { title: 'Action' },
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
          
            $('#example_paginate').hide();
        }
        $(document).on('click','.edit_column',function(){
            var id = $(this).attr('data-id'); 
            console.log(id);
            var td = $(this).closest("tr").find("td:eq(1)");
            var fieldValue = td.text().trim();
           
            // Replace content with input field
            td.html("<input type='text' name='field_names[]' data-id="+ id +" class='form-control' id='final_column' value='" + fieldValue + "' required>");
            
            // var td3 = $(this).closest("tr").find("td:last-child");
            //  td3.html("<button id='edit_save' class='edit_save btn btn-success me-2' data-id='" + id + "'>save</button><button class='close_edit btn btn-danger'>close</button>");
             // Disable the button
             $(this).prop('disabled', true);
        });
         
          //on edit click
        // Handle close edit button click
        // $(document).on("click", ".close_edit", function() {
        //     var td = $(this).closest("tr").find("td:eq(1)");
        //     var fieldValue = td.find("input").val(); // Get the current value from the input field

        //     // Set the td value back
        //     td.html(fieldValue);

        //     // Restore the original buttons
        //     var id = $(this).closest("tr").find(".edit_save").attr('data-id');
        //     var td3 = $(this).closest("tr").find("td:last-child");
        //     td3.html("<button class='btn btn-link edit_column' data-id='" + id + "'><i class='fas fa-edit'></i></button>");
        // });
      
        $("#saveChangesBtn").click(function() {
       
        var dataToSave = []; // Array to store the data to be saved
        var fieldValues = {};
        var isValid = true;
        // Iterate over each input field with name 'field_name[]'
        $('input[name="field_names[]"]').each(function(index) {
            var fieldValue = $(this).val(); // Get the value of the input field
            console.log(fieldValue);
            var fieldId = $(this).data('id'); // Get the name attribute of the input field
            let inputField = $(this);
            console.log();
             // Check if the field value is blank
             if (fieldValue === '') {
                isValid = false;
            
                // alert("Field value cannot be blank.");
                var closestDiv = $(this).next('.empty-value');
                if (closestDiv.length <= 0) {
                    inputField.after('<div class="error-message empty-value mt-2 alert alert-danger">Field value cannot be blank.</div>');
                }
                // $('#staticBackdrop').animate({ scrollTop: inputField }, 'slow');

                $('#staticBackdrop').animate({
                    scrollTop : $('.error-message').offset().top - $('#staticBackdrop').offset().top + $('#staticBackdrop').scrollTop()
                },'slow');
              
                // scrollToError(inputField);
               // return false; // Exit the loop early
            } else {
                var closestDivs = $(this).next('.empty-value');
                closestDivs.remove();
                isValid = true;
                 // Check if the field value is duplicate
                    if (fieldValues.hasOwnProperty(fieldValue)) {
                        isValid = false;
                        // alert("Field value '" + fieldValue + "' is already used. Please enter a unique value.");
                        var closestDiv = $(this).next('.same-value');
                        if (closestDiv.length <= 0) {
                            inputField.after('<div class="error-message same-value mt-2 alert alert-danger">Field value \'' + fieldValue + '\' is already used. Please enter a unique value.</div>');
                        }
                        // $('#staticBackdrop').animate({ scrollTop: inputField }, 'slow');
                        $('#staticBackdrop').animate({
                            scrollTop : $('.error-message').offset().top - $('#staticBackdrop').offset().top + $('#staticBackdrop').scrollTop()
                        },'slow');
                        
                        // scrollToError(inputField);
                        //return false; // Exit the loop early
                    } else {
                        isValid = true;
                        var closestDiv = $(this).next('.same-value');
                        closestDiv.remove();
                    }
                }
            // Store the field value for validation
            fieldValues[fieldValue] = true;

            // Store the data in an object
            var inputData = {
                fieldId: fieldId,
                fieldValue: fieldValue
            };

            // Push the object to the array
            dataToSave.push(inputData);
        });

        if (isValid == true) {
            // Example AJAX call to send data to the server
            $.ajax({
                type: "POST",
                url: '{{ route("store.columns") }}',
                data: JSON.stringify(dataToSave), // Convert array to JSON string
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                contentType: "application/json",
                success: function(response) {
                    console.log(response);
                    var serverResponse = JSON.stringify(response);
                    console.log(response);
                    if (response.status == "success") {
                     location.reload();
                    }
                    // Handle success response from the server
                    console.log("Data saved successfully:", response);

                    // Optionally, you can show a success message or perform other actions
                },
                error: function(xhr, status, error) {
                    // Handle error response from the server
                    console.error("Error saving data:", error);
                    // Optionally, you can show an error message or perform other actions
                }
            });
        }
    });
    
        //on save click
       // Handle save edit button click
        // $(document).on("click", ".edit_save", function(event) {
        //     event.stopPropagation(); // Prevent multiple form submissions
        //     // var id = $(this).data('id');
       
        //     var id = $(this).closest("tr").find(".edit_save").attr('data-id');
        //     var columnValue = $(this).closest("tr").find("#final_column").val();
        //     var self = this;
        //     // Debugging: Log the column value
        //      console.log("Column Value:", columnValue);
        //     // var columnValue = $('#final_column').val();
        //     // console.log(columnValue);
        //     $.ajax({
        //         type: 'POST',
        //         url: '{{ route('store.columns') }}',
        //         data: { id: id, columnValue: columnValue },
        //         headers: {
        //             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        //         },
        //         success: function(response) {
        //             console.log(response);
        //             $(self).closest("tr").find("td:eq(1)").text(columnValue);
            
        //            // Remove the input field
        //             $(self).closest("tr").find("#final_column").parent().remove();

        //             // Append the edit button to the third cell
        //             var editButtonHtml = '<button class="edit_column btn btn-link" data-id="' + id + '"><i class="fas fa-edit"></i></button>';
        //             $(self).closest("tr").find("td:last-child").html(editButtonHtml);
        //             // Handle success response
        //         },
        //         error: function(xhr, status, error) {
        //             // Handle error response
        //         }
        //     });
        // });
        //reset table after closing popup
        $("#close_popup").click(function() {

            // Loop through each table row
            $("#table_column tbody tr").each(function() {
            var td = $(this).find("td:eq(1)");
            var fieldValue = td.find("input").val(); // Get the current value from the input field

            // Set the td value back
            td.html(fieldValue);

            // Restore the original buttons
            var id = $(this).find(".edit_save").attr('data-id');
            var td3 = $(this).find("td:last-child");
            td3.html("<button class='btn btn-link edit_column' data-id='" + id + "'><i class='fas fa-edit'></i></button>");
            });
        });

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