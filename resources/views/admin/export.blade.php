


@extends('layout.app', ['pageTitleCheck' => $pageTitle])

 @section('content')

 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
    <div id="layoutSidenav_content">
        <div class="m-1 d-md-flex border-bottom pb-3 mb-3 flex-md-row align-items-center justify-content-between">
                <h3 class="mb-0 ps-2">Data Management</h3>
        </div>
        <div class="card mx-2 shadow p-3 my-3 alert alert-warning">
        <h4 class="alert-heading"><i class="fa fa-info-circle" aria-hidden="true"></i> Important</h4>    
        <div class="border-top border-warning py-2"></div>
        Before uploading, please make sure to add the keyword "date" in the column names that have date values.
        </div>
        <div class="alert alert-success m-3" id="user_del_success" style="display:none;"></div>
        <div class="container">
            <div class="" id="successMessages"></div>
            <div class="" id="errorMessage"></div>

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
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
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close" id="close_popup"><span aria-hidden="true">×</span></button>
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
                         <button type="button" class="btn btn-danger " data-bs-dismiss="modal" aria-label="Close" id="close_popup2">Close</button>
                    </div>
                    </div>
                    </form>
                </div>
            </div>

            <table id="example" class="data_table_files">
            <!-- Your table content goes here -->
            <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>File Name</th>
                        <th>Status</th>
                        <th style="white-space: nowrap;">Uploaded By</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
        @include('layout.footer')
    </div>
</div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        input#btnUpload {
            background:#999;
            border:1px solid #666;
            color:#fff;
            cursor:pointer;
            display:block;
            margin:0 0 10px;
            outline:none;
            padding:5px;
        }

        progress, #downloadProgress, #progUpdate {
            float:left;
        }

        progress, #downloadProgress {
            margin-right:10px;
        }

        #progUpdate, #downloadProgress {
            font:12px Arial, Verdana, sans-serif;
            color:#000;
        }

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
        #progBar {
            width: 100%;
        }
    </style>
    <!-- Include Date Range Picker JavaScript -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
    </body>
    <script>
    $(document).ready(function() {
        $('#btnUpload').click(function() {
            var bar = document.getElementById('progBar'),
                fallback = document.getElementById('downloadProgress'),
                loaded = 0;

            var load = function() {
                loaded += 1;
                bar.value = loaded;

                /* The below will be visible if the progress tag is not supported */
                $(fallback).empty().append("HTML5 progress tag not supported: ");
                $('#progUpdate').empty().append(loaded + "% loaded");

                if (loaded == 100) {
                    clearInterval(beginLoad);
                    $('#progUpdate').empty().append("Upload Complete");
                    console.log('Load was performed.');
                }
            };

            var beginLoad = setInterval(function() {
                load();
            }, 50);
        });
        
        var exportTable = $('#example').DataTable({
            oLanguage: {
                sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'
            },
            processing: true,
            serverSide: true,
            lengthMenu: [],
            pageLength: 50,
            ajax: {
                url: '{{ route("export.filter") }}',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: function (d) {
                    // Pass date range and supplier ID when making the request
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
                { data: 'supplier_name', name: 'supplier_name' , 'orderable': false, 'searchable': false },
                { data: 'file_name', name: 'file_name' , 'orderable': false, 'searchable': false },
                { data: 'status', name: 'status' , 'orderable': false, 'searchable': false },
                { data: 'uploaded_by', name: 'uploaded_by' , 'orderable': false, 'searchable': false },
                { data: 'date', name: 'date' , 'orderable': false, 'searchable': false },
                { data: 'id', name: 'id' , 'orderable': false, 'searchable': false },
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

        $('#page-loader').hide();
        $('#example_length').hide();

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
                    console.log(response);
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                    if(response.error){
                        button.innerHTML = '<i class="me-2 fa-solid fa-file-import"></i> Import';
                        button.disabled = false;
                        var errorMessage = '';
                        if (typeof response.error === 'object') {
                            // Iterate over the errors object
                            $.each(response.error, function (key, value) {
                                errorMessage += value[0] + '<br>';
                            });
                        } else {
                                errorMessage = response.error;
                        }
                        $('#errorMessage').html('');
                        $('#page-loader').hide();
                        $('#errorMessage').append('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+errorMessage+'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                    }

                    if(response.success){                    
                        button.disabled = false;
                        button.innerHTML = '<i class="me-2 fa-solid fa-file-import"></i> Import';
                        $('#successMessages').html('');
                        $('#page-loader').hide();
                        $('#successMessages').append('<div class="alert alert-success alert-dismissible fade show" role="alert">'+response.success+'<button type="button" class="successclose close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                        
                        var dataTable = $('#example').DataTable();
                        if (dataTable) {
                        // Reload DataTable only if it exists
                        dataTable.ajax.reload();
                        } else {
                        console.error('DataTable instance is not available or properly initialized.');
                        }
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
        $('#selectBox').on('change', function() {
            var suppliername =  $(this).find("option:selected").text()
            console.log(suppliername);
            $('#sup_name').text(suppliername);
            var dataIdValue = $(this).val(); // Replace with your dynamic value
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
      
        $(document).on('click','.edit_column',function(){
            var id = $(this).attr('data-id'); 
            console.log(id);
            var td = $(this).closest("tr").find("td:eq(1)");
            var fieldValue = td.text().trim();
           
            // Replace content with input field
            td.html("<input type='text' name='field_names[]' data-id="+ id +" class='form-control' id='final_column' value='" + fieldValue + "' required>");
            
            // Disable the button
            $(this).prop('disabled', true);
        });

        $("#saveChangesBtn").click(function() {
            // Define the htmlspecialchars function
            function htmlspecialchars(str) {
                var elem = document.createElement('div');
                elem.innerText = str;
                return elem.innerHTML;
            }
            var dataToSave = []; // Array to store the data to be saved
            var fieldValues = {};
            var isValid = true;
            // Iterate over each input field with name 'field_name[]'
            $('input[name="field_names[]"]').each(function(index) {
                var fieldValue = $(this).val(); // Get the value of the input field
                fieldValue = htmlspecialchars(fieldValue); 
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

                    $('#staticBackdrop').animate({
                        scrollTop : $('.error-message').offset().top - $('#staticBackdrop').offset().top + $('#staticBackdrop').scrollTop()
                    },'slow');
                } else {
                    var closestDivs = $(this).next('.empty-value');
                    closestDivs.remove();
                    isValid = true;
                    // Check if the field value is duplicate
                        if (fieldValues.hasOwnProperty(fieldValue)) {
                            isValid = false;
                            var closestDiv = $(this).next('.same-value');
                            if (closestDiv.length <= 0) {
                                inputField.after('<div class="error-message same-value mt-2 alert alert-danger">Field value \'' + fieldValue + '\' is already used. Please enter a unique value.</div>');
                            }

                            $('#staticBackdrop').animate({
                                scrollTop : $('.error-message').offset().top - $('#staticBackdrop').offset().top + $('#staticBackdrop').scrollTop()
                            },'slow');
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
                            $("#close_popup").trigger("click");
                            $('#successMessages').append('<div class="alert alert-success alert-dismissible fade show" role="alert">'+response.message+'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
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

        //reset table after closing popup
        $("#close_popup,#close_popup2").click(function() {
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
                // title: "Oops....",
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