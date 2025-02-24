@extends('layout.app', ['pageTitleCheck' => $pageTitle])
@section('content')
    <div id="layoutSidenav">
        @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
        <div id="layoutSidenav_content">
            <div class="m-1 d-md-flex border-bottom pb-3 mb-3 flex-md-row align-items-center justify-content-between">
                <h3 class="mb-0 ps-2 ">Manage Supplier Catalog</h3>            
            </div>
            <style>
                .loading-icon {
                    display: none; /* Hidden by default */
                }
            </style>
            <div class="container">
                <input type="hidden" value="1" id="show"/>
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
                    url: "{{route('import.supplier_catalog_file')}}", // Replace with your actual route name
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
                    url: "{{route('import.supplier_catalog_file')}}", // Replace with your actual route name
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
                    url: "{{route('add.supplier_catalog_file')}}", // Replace with your actual route name
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
                    url: "{{route('edit.supplier_catalog_file')}}", // Replace with your actual route name
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

            $('#supplier_data').DataTable({
                oLanguage: {sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'},
                processing: true,
                serverSide: true,
                lengthMenu: [40], // Specify the options you want to show
                lengthChange: false, // Hide the "Show X entries" dropdown
                searching:false, 
                pageLength: 40,
                order: [[0, 'desc']],
                ajax: {
                    url: '{{ route("supplier_catalog_ajax.filter") }}',
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
                    { data: 'supplier_name', name: 'supplier_name', title: 'Supplier Name'},
                    { data: 'category', name: 'category', title: 'Category'},
                    { data: 'edit', name: 'edit', title: 'Action', 'orderable': false, 'searchable': false},
                ],
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