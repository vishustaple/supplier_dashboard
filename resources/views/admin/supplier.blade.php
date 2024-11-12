@extends('layout.app', ['pageTitleCheck' => $pageTitle])
@section('content')
    <div id="layoutSidenav">
        @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
        <div id="layoutSidenav_content">
            <div class="m-1 d-md-flex border-bottom pb-3 mb-3 flex-md-row align-items-center justify-content-between">
                <h3 class="mb-0 ps-2 ">Manage Supplier</h3>
                <!-- Button trigger modal -->
                <div class="supplier_buttons">
                    <button type="button" class="btn btn-primary mr-3" data-bs-toggle="modal" data-bs-target="#exampleModal">
                        Add Supplier
                    </button>
                    <button id="show_all_supplier" type="button" data-id="all" class="btn btn-primary">
                        Show All Supplier
                    </button>
                </div>
            </div>
            <style>
                .loading-icon {
                    display: none; /* Hidden by default */
                }
            </style>
            <div class="container">
                <input type="hidden" value="1" id="show"/>
                <div class="modal fade" data-bs-backdrop="static" id="editSupplierModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="edit_supplier">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Supplier Edit</h5>
                                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                                </div>
                                <div class="editerrorMessage"></div>
                                <div class="editsuccessMessage"></div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <input type="hidden" name="supplier_id" id="supplier_id">
                                        <label for="supplier_name" class="form-label">Supplier Name</label>
                                        <input type="text" class="form-control" name="supplier_name" id="supplier_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="category" class="form-label">Supplier Category</label>
                                        <input type="text" class="form-control" name="category" id="category" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="category" class="form-label">Show/Hide Supplier On Webite</label>
                                        <select class="form-select" name="show" id="shows" aria-label="Default select example" required>
                                            <option value="1">Hide</option>
                                            <option value="0">Show</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="category" class="form-label">Show/Hide Supplier</label>
                                        <select class="form-select" name="hide_show" id="hide_show" aria-label="Default select example" required>
                                            <option value="0">Hide</option>
                                            <option value="1">Show</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal -->
                <div class="modal fade" data-bs-backdrop="static" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="add_supplier">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Supplier Add</h5>
                                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                                </div>
                                <div class="editerrorMessage"></div>
                                <div class="editsuccessMessage"></div>
                                <div class="modal-body row">
                                    <div class="mb-3 col-12">
                                        <label for="supplier_name" class="form-label">Supplier Name</label>
                                        <input type="text" class="form-control" name="supplier_name" id="supplier_name" required>
                                    </div>
                                    <div class="mb-3 col-6">
                                        <label for="category" class="form-label">Supplier Category</label>
                                        <input type="text" class="form-control" name="category" id="category" required>
                                    </div>
                                    <div class="mb-3 col-6">
                                    <label for="category" class="form-label">Show/Hide Supplier On Webite</label>
                                        <select class="form-select" name="show" aria-label="Default select example" required>
                                            <option value="1">Hide</option>
                                            <option value="0">Show</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 col-6">
                                    <label for="category" class="form-label">Show/Hide Supplier</label>
                                        <select class="form-select" name="hide_show" aria-label="Default select example" required>
                                            <option value="0">Hide</option>
                                            <option value="1">Show</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer justify-content-center">
                                    <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> -->
                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal fade" data-bs-backdrop="static" id="editSupplierFileFormatModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">File Edit</h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                            </div>
                            <div class="editerrorMessages"></div>
                            <div class="editsuccessMessage"></div>
                            <div class="modal-body">
                            <form id="edit_file_format">
                                <input type="hidden" name="supplier_id" id="supplier_data_edit_id_one">
                            </form>
                            <form action="" id="columns_form_edit">
                                <input type="hidden" name="supplier_id" id="supplier_attachment_id">
                                <table class="table" id="dynamicTableOne">
                                    <thead>
                                        <tr>
                                            <th scope="col">Sr. No</th>
                                            <th scope="col">Columns</th>
                                            <th scope="col">Map Columns</th>
                                        </tr>
                                    </thead>
                                        <tbody></tbody>
                                </table>
                            </div>
                            <div class="modal-footer">
                                <button type="button" id="importBtn" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary "><i class="me-2 fa-solid fa-file-import"></i>Save changes</button>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal fade" data-bs-backdrop="static" id="addSupplierFileFormatModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">File Add</h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                            </div>
                            <div class="editerrorMessages"></div>
                            <div class="editsuccessMessage"></div>
                            <div class="modal-body">
                            <form id="add_file_format">
                                <div class="mb-3">
                                    <label for="formFile" class="form-label">Upload format file</label>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-spinner fa-spin loading-icon me-2" id="fileLoader"></i>
                                        <input class="form-control" type="file" name="excel_file" id="formFile">
                                    </div>
                                </div>
                            </form>
                            <form action="" id="columns_form">
                                @csrf
                                <input type="hidden" name="supplier_id" id="supplier_data_edit_id_two">
                                <table class="table" id="dynamicTable">
                                    <thead>
                                        <tr>
                                            <th scope="col">Sr. No</th>
                                            <th scope="col">Columns</th>
                                            <th scope="col">Map Columns</th>
                                        </tr>
                                    </thead>
                                        <tbody></tbody>
                                </table>
                            </div>
                            <div class="modal-footer">
                                <button type="button" id="importBtn" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary "><i class="me-2 fa-solid fa-file-import"></i>Save changes</button>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
                <table id="supplier_data" class="data_table_files"></table>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $('#formFile').on('change', function(event) {
                var button = document.getElementById('importBtn'),
                formData = new FormData($('#add_file_format')[0]),
                loader = $('#fileLoader');
                loader.show();
                $.ajax({
                    type: 'POST',
                    url: "{{route('import.supplier_file')}}", // Replace with your actual route name
                    data: formData,
                    headers: {'X-CSRF-TOKEN': token},
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.error) {
                            var errorMessage = '';
                            if (typeof response.error === 'object') {
                                // Iterate over the errors object
                                $.each(response.error, function (key, value) {
                                    errorMessage += value[0] + '<br>';
                                });
                            } else {
                                errorMessage = response.error;
                            }

                            $('.editerrorMessages').append('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+errorMessage+'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                            loader.hide();
                        }

                        if (response.success) {
                            loader.hide();
                            var tableBody = $('#dynamicTable tbody');
                            tableBody.empty();
                            $.each(response.final, function(index, value) {
                                var row = '<tr class="rows">' +
                                    '<td>' + (index + 1) + '</td>' +
                                    '<td>' + value.excel_field + '</td>' +
                                    '<td>' + value.map_columns + '</td>' + // Placeholder, update as necessary
                                    '</tr>';
                                tableBody.append(row);
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        // Handle error response
                        console.error(xhr.responseText);
                    }
                });
            });

            var myModal = document.getElementById('editSupplierFileFormatModal');
            myModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal
                // Set the value of the input element
                $('#supplier_data_edit_id_one').val(button.getAttribute('data-id'));
                $('#supplier_attachment_id').val(button.getAttribute('data-id'));
                
                formData = new FormData($('#edit_file_format')[0]),
                $.ajax({
                    type: 'POST',
                    url: "{{route('import.supplier_file')}}", // Replace with your actual route name
                    data: formData,
                    headers: {'X-CSRF-TOKEN': token},
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.error) {
                            var errorMessage = '';
                            if (typeof response.error === 'object') {
                                // Iterate over the errors object
                                $.each(response.error, function (key, value) {
                                    errorMessage += value[0] + '<br>';
                                });
                            } else {
                                errorMessage = response.error;
                            }

                            $('.editerrorMessages').append('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+errorMessage+'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                            loader.hide();
                        }

                        if (response.success) {
                            var tableBody = $('#dynamicTableOne tbody');
                            tableBody.empty();
                            $.each(response.final, function(index, value) {
                                var row = '<tr class="rows">' +
                                    '<td>' + (index + 1) + '</td>' +
                                    '<td>' + value.excel_field + '</td>' +
                                    '<td>' + value.map_columns + '</td>' + // Placeholder, update as necessary
                                    '</tr>';
                                tableBody.append(row);
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        // Handle error response
                        console.error(xhr.responseText);
                    }
                });
            });

            var myModal = document.getElementById('addSupplierFileFormatModal');
            myModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal
                // Set the value of the input element
                $('#supplier_data_edit_id_two').val(button.getAttribute('data-id'));
            });

            $('#addSupplierFileFormatModal').on('hidden.bs.modal', function () {
                location.reload();
            });

            $('#editSupplierFileFormatModal').on('hidden.bs.modal', function () {
                location.reload();
            });

            $('#columns_form').on('submit', function(e){
                e.preventDefault();
                var formData = new FormData(this)
                $.ajax({
                    type: 'POST',
                    url: "{{route('add.supplier_file')}}", // Replace with your actual route name
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.error) {
                            var errorMessage = '';
                            if (typeof response.error === 'object') {
                                // Iterate over the errors object
                                $.each(response.error, function (key, value) {
                                    errorMessage += value[0] + '<br>';
                                });
                            } else {
                                errorMessage = response.error;
                            }

                            $('.editerrorMessages').append('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+errorMessage+'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                        }

                        if (response.success) {
                            location.reload();
                        }
                    },
                    error: function(xhr, status, error) {
                        // Handle error response
                        console.error(xhr.responseText);
                    }
                });
            });

            $('#columns_form_edit').on('submit', function(e){
                e.preventDefault();
                var formData = new FormData(this)
                $.ajax({
                    type: 'POST',
                    url: "{{route('edit.supplier_file')}}", // Replace with your actual route name
                    data: formData,
                    headers: {'X-CSRF-TOKEN': token},
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.error) {
                            var errorMessage = '';
                            if (typeof response.error === 'object') {
                                // Iterate over the errors object
                                $.each(response.error, function (key, value) {
                                    errorMessage += value[0] + '<br>';
                                });
                            } else {
                                errorMessage = response.error;
                            }

                            $('.editerrorMessages').append('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+errorMessage+'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                        }

                        if (response.success) {
                            location.reload();
                        }
                    },
                    error: function(xhr, status, error) {
                        // Handle error response
                        console.error(xhr.responseText);
                    }
                });
            });

            $(document).on('change', '.excel_col', function() {
                var selectedValue = $(this).val();
                var previousValue = $(this).data('previous');
                var currentRow = $(this).closest('.rows');

                // Enable the previously selected option in other rows, unless it's zero
                if (previousValue && previousValue !== '0') {
                    $('.rows').not(currentRow).each(function() {
                        $(this).find('.excel_col option[value="' + previousValue + '"]').prop('disabled', false);
                    });
                }

                // Update the previous value with the current value
                $(this).data('previous', selectedValue);

                // Disable the selected option in other rows, unless it's zero
                if (selectedValue !== '0') {
                    $('.rows').not(currentRow).each(function() {
                        $(this).find('.excel_col option[value="' + selectedValue + '"]').prop('disabled', true);
                    });
                }

                // Enable options that are not selected in the current row
                currentRow.find('.excel_col').not(this).each(function() {
                    $(this).find('option').prop('disabled', false);
                });
            });

            $('#importBtn').on( "click", function(event) {
                event.preventDefault();
                $('#page-loader').show();
             
            });

            $('#show_all_supplier').on('click', function() {
                var dataId = $(this).attr('data-id');
                $('#show').val($(this).attr('data-id'));
                
                if (dataId == 1) {
                    $(this).text('Show All Supplier');
                    $(this).attr('data-id', 'all');  
                } else {
                    $(this).text('Hide supplier');
                    $(this).attr('data-id', 1);  
                }

                $('#supplier_data').DataTable().ajax.reload();
            });

            $('#supplier_data').DataTable({
                oLanguage: {sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'},
                processing: true,
                serverSide: true,
                lengthMenu: [40], // Specify the options you want to show
                lengthChange: false, // Hide the "Show X entries" dropdown
                searching:false, 
                pageLength: 40,
                order: [[3, 'desc']],
                ajax: {
                    url: '{{ route("supplier_ajax.filter") }}',
                    type: 'POST',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: function (d) {
                        // Pass date range and supplier ID when making the request
                        d.show = $('#show').val();
                    },
                },

                beforeSend: function() {
                    // Show both the DataTables processing indicator and the manual loader before making the AJAX request
                    $('.dataTables_processing').show();
                    $('#manualLoader').show();
                },

                complete: function(response) {
                    // Hide both the DataTables processing indicator and the manual loader when the DataTable has finished loading
                    $('.dataTables_processing').hide();
                    $('#manualLoader').hide();
                    if (businessdataTable.data().count() > 40) {
                        $('#business_data_paginate').show(); // Enable pagination
                    } else {
                        $('#business_data_paginate').hide();
                    }
                },

                columns: [
                    { data: 'show', name: 'show', title: 'Show', 'orderable': false, 'searchable': false},
                    { data: 'supplier_name', name: 'supplier_name', title: 'Supplier Name'},
                    { data: 'department', name: 'department', title: 'Department'},
                    { data: 'name', name: 'name', title: 'Name'},
                    { data: 'email', name: 'email', title: 'Email'},
                    { data: 'phone', name: 'phone', title: 'Phone'},
                    { data: 'category', name: 'category', title: 'Category'},
                    { data: 'status', name: 'status', title: 'Status'},
                    { data: 'edit', name: 'edit', title: 'Action', 'orderable': false, 'searchable': false},
                ],
            });

            // Assuming there is only one element with the class checkboxMain
            $(document).on('change', '.checkboxMain', function(){
                var show = 0;
                if ($(this).is(":checked")) {
                    show = 1;   
                }

                var formData = { id : $(this).data('id'), show : show },
                token = "{{ csrf_token() }}";
                $.ajax({
                    type: 'POST',
                    url: "{{route('supplier_show.update')}}",
                    dataType: 'json',
                    data: JSON.stringify(formData),                        
                    headers: {'X-CSRF-TOKEN': token},
                    contentType: 'application/json',                     
                    processData: false,
                    success: function(response) {
                        if (response.success) {
                            $('#supplier_data').DataTable().ajax.reload(); 
                        }
                    },
                    error: function(xhr, status, error) {
                        // Handle error response
                        $('#editerrorMessages').html('');
                        $('#editerrorMessages').css('display','block');
                        $('#editerrorMessages').append('<div class="alert alert-danger alert-dismissible fade show" role="alert">' + JSON.parse(xhr.responseText).error + '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                        setTimeout(() => {
                            location.reload();
                        }, 3000);
                    }
                });
            });

            $('#add_supplier').on('submit',function(e){ 
                e.preventDefault();
                var formData = new FormData($('#add_supplier')[0]),
                token = "{{ csrf_token() }}";
                $.ajax({
                    type: 'POST',
                    url: '{{ route("suppliers.add") }}',
                    headers: {'X-CSRF-TOKEN': token},
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.error) {
                            // Iterate over each field in the error object
                            var errorMessage = '';
                                if (typeof response.error === 'object') {
                                    // Iterate over the errors object
                                    $.each(response.error, function (key, value) {
                                        errorMessage += value[0] + '<br>';
                                    });
                                } else {
                                    errorMessage = response.error;
                                }
                            $('#editerrorMessage').html('');
                            $('#editerrorMessage').append('<div class="alert alert-danger m-2 alert-dismissible fade show" role="alert">'+errorMessage+'<button type="button" class="close" data-dismiss="alert" aria-label="Close" id="closeerrorMessage"><span aria-hidden="true">&times;</span></button></div>');
                            $('html, body').animate({ scrollTop: 0 }, 'slow');
                        } else {
                            $('#editsuccessMessage').html('');
                            $('#editsuccessMessage').append('<div class="alert alert-success m-2 alert-dismissible fade show" role="alert">Supplier created successfully <button type="button" class="close" data-dismiss="alert" aria-label="Close" id="closeerrorMessage"><span aria-hidden="true">&times;</span></button></div>');
                            $('html, body').animate({ scrollTop: 0 }, 'slow');
                        }

                        if (response.success) {
                            location.reload();
                        }
                    },
                    error:function(xhr, status, error) {
                        const errorresponse = JSON.parse(xhr.responseText);
                        $('#errorMessage').text(errorresponse.error);
                        $('#errorMessage').css('display','block');
                        $('html, body').animate({ scrollTop: 0 }, 'slow');
                        setTimeout(function () {
                            $('#errorMessage').fadeOut();
                        }, 3000);
                    }
                });
            });

            //set modal value 
            var myModal = document.getElementById('editSupplierModal');
            myModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal
                // Set the value of the input element
                $('#supplier_id').val(button.getAttribute('data-id'));
                $('#supplier_name').val(button.getAttribute('data-supplier_name'));
                $('#category').val(button.getAttribute('data-category'));
                $('#hide_show').val(button.getAttribute('data-hide_show'));
                $('#shows').val(button.getAttribute('data-show'));
            });

            $('#edit_supplier').on('submit',function(e){ 
                e.preventDefault();
                var formData = new FormData($('#edit_supplier')[0]),
                token = "{{ csrf_token() }}";
                $.ajax({
                    type: 'POST',
                    url: '{{ route("suppliers.update") }}',
                    headers: {'X-CSRF-TOKEN': token},
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.error) {
                            // Iterate over each field in the error object
                            var errorMessage = '';
                                if (typeof response.error === 'object') {
                                    // Iterate over the errors object
                                    $.each(response.error, function (key, value) {
                                        errorMessage += value[0] + '<br>';
                                    });
                                } else {
                                    errorMessage = response.error;
                                }
                            $('#editerrorMessage').html('');
                            $('#editerrorMessage').append('<div class="alert alert-danger m-2 alert-dismissible fade show" role="alert">'+errorMessage+'<button type="button" class="close" data-dismiss="alert" aria-label="Close" id="closeerrorMessage"><span aria-hidden="true">&times;</span></button></div>');
                            $('html, body').animate({ scrollTop: 0 }, 'slow');
                        }

                        if (response.success) {
                            location.reload();
                        }
                    },
                    error:function(xhr, status, error) {
                        const errorresponse = JSON.parse(xhr.responseText);
                        $('#errorMessage').text(errorresponse.error);
                        $('#errorMessage').css('display','block');
                        $('html, body').animate({ scrollTop: 0 }, 'slow');
                        setTimeout(function () {
                            $('#errorMessage').fadeOut();
                        }, 3000);
                    }
                });
            });
        });

        $(document).on('click', '.delete_format', function () {
            var id = $(this).attr('data-id');
            swal.fire({
                text: "Are you sure you want to delete this File format?",
                icon: "error",
                showCancelButton: true,
                confirmButtonText: 'YES',
                cancelButtonText: 'NO',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('remove.file_format') }}",
                        data: { id: id },
                        success: function (response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                // Handle other cases where response.success is false
                            }
                        },
                        error: function (error) {
                            console.log(error);
                            // Handle error
                        }
                    });
                } else {
                    // Handle cancellation
                }
            });    
        });
    </script>
@endsection