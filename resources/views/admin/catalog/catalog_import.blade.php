@extends('layout.app', ['pageTitleCheck' => $pageTitle])
@section('content')
<div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
    <div id="layoutSidenav_content">
        <div class="m-1 d-md-flex border-bottom pb-3 mb-3 flex-md-row align-items-center justify-content-between">
            <h3 class="mb-0 ps-2">Catalog Data Management</h3>
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
                    <div class="form-group col-md-6 mb-0">
                        <label for="selectBox">Catalog Price Type:</label>
                        <select id="productSelect" name="catalog_price_type_id" class="form-control">
                            <option value="">Select Catalog Price Type</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6 mb-0">
                        <label for="monthSelect">Select Month:</label>
                        <select id="monthSelect" name="month" class="form-control" required>
                            <option value="">Select Month</option>
                            <option value="1">January</option>
                            <option value="2">February</option>
                            <option value="3">March</option>
                            <option value="4">April</option>
                            <option value="5">May</option>
                            <option value="6">June</option>
                            <option value="7">July</option>
                            <option value="8">August</option>
                            <option value="9">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                    </div>
                    <!-- <div id="enddates" class="form-group invisible relative col-md-6 mb-0">
                        <label for="enddate">Select Date:</label>
                        <input class="form-control " id="enddate" name="enddate" placeholder="Enter Your End Date " >
                    </div> -->
                    <div class="form-group col-md-6 mb-0">
                        <label for="yearSelect">Select Year:</label>
                        <select id="yearSelect" name="year" class="form-control" required>
                            <option value="">Select Year</option>
                            <!-- Year options will be generated dynamically with JavaScript -->
                        </select>
                    </div>
                    <div class="form-group relative col-md-6 pt-4 mb-0">
                        <label for="file">Usage Data Import:</label>
                        <input type="file" name="file" id="file" class="form-control">
                    </div>
                    <div class="col-md-6 pt-4 mb-0 d-flex justify-content-end">
                        <div class="relative imprt_wrapper text-end me-2">
                            <a id="sampleFileDownloadBtn" class="btn btn-primary invisible" href="#"><i class="fa fa-cloud-download" aria-hidden="true"></i> Sample File</a>
                        </div>
                        <div class="relative imprt_wrapper text-end">
                            <button type="submit" class="btn btn-primary" id="importBtn"><i class="me-2 fa-solid fa-file-import"></i>Import</button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="colors_spans">
                <div class="colors">
                    <span style="background-color: rgb(248, 240, 121);"></span>
                    <p>File Re-Processing</p>
                </div>
                <div class="colors">
                    <span style="background-color: rgb(240, 155, 155);"></span>
                    <p>File Deleted</p>
                </div>
                <div class="colors">
                    <span style="background-color: rgb(182 235 176);"></span>
                    <p>File Duplicate</p>
                </div>
                <div class="colors">
                    <span></span>
                    <p>File Uploaded</p>
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

        .colors_spans {
            display: flex;
            /* justify-content: end; */
            gap: 20px;
            margin-bottom: -30px;
        }
        .colors_spans span {
            width: 25px;
            display: block;
            height: 25px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        
        .colors_spans .colors {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .colors_spans .colors p {
            margin-bottom: 0px;
        } 
    </style>
    </body>
    <script>
        $(document).ready(function() {
            // Convert the PHP data into a JavaScript object
            var catalogPriceType = @json($catalogPriceType);
            // console.log(catalogPriceType);

             // Get the current year
            const currentYear = new Date().getFullYear(),
            startYear = currentYear - 7;
            // Populate the year dropdown with a range of years
            const yearSelect = document.getElementById("yearSelect");

            for (let year = startYear; year <= currentYear; year++) {
                const option = document.createElement("option");
                option.value = year;
                option.text = year;
                yearSelect.appendChild(option);
            }

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
                    url: '{{ route("export_catalog.filter") }}',
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
                        if ($(this).find('button.invisible2').length > 0) {
                            // If a button with the class 'invisible2' exists
                            $(row).css('background-color', 'rgb(248, 240, 121)'); // Highlight the row
                        } else if ($(this).find('button.invisible3').length > 0) {
                            // If a button with the class 'invisible' exists
                            $(row).css('background-color', 'rgb(182 235 176)'); // Highlight the row with a different color
                        } else if ($(this).find('button.invisible1').length > 0) {
                            // If a button with the class 'invisible' exists
                            $(row).css('background-color', '#f09b9b'); // Highlight the row with a different color
                        } else {
                            // Default case: no specific button found
                            $(row).css('background-color', ''); // Reset background color (optional)
                        }
                    });
                }
            });

            setInterval(() => {
                $('#example').DataTable().ajax.reload();
            }, 2000);

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
                    url: "{{route('import_catalog.excel')}}", // Replace with your actual route name
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
            // Event listener for supplier dropdown change
            $('#selectBox').on('change', function() {
                const selectedSupplierId = $(this).val(),
                filteredProducts = catalogPriceType.filter(catalogPriceType => catalogPriceType.supplier_id == selectedSupplierId);
                
                // Clear previous options
                $('#productSelect').empty();
                $('#productSelect').append('<option value="">Select Catalog price Type</option>');

                // Add new options based on filtered products
                filteredProducts.forEach(function(catalogPriceType) {
                    $('#productSelect').append(`<option value="${catalogPriceType.id}">${catalogPriceType.name}</option>`);
                });

                var suppliername =  $(this).find("option:selected").text()

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
                            $("#tableBody").append("<tr><td>" + i +"</td><td>" + column.raw_label + "</td><td>" + requiredIcon + "</td><td>" + editButton + "</td></tr>");
                            i++;
                        });
                    },
                    error: function(xhr, status, error) {
                    
                    }
                });

                if (dataIdValue != '') {
                    $('#sampleFileDownloadBtn').removeClass('invisible');
                    var xlsxUrl = "{{ route('file_catalog.download') }}/"+dataIdValue;

                    // Set the href attribute of the anchor tag
                    $('#sampleFileDownloadBtn').attr('href', xlsxUrl);
                } else {
                    $('#sampleFileDownloadBtn').addClass('invisible');
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