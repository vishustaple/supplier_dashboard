@extends('layout.app', ['pageTitleCheck' => $pageTitle])
@section('content')
    <div id="layoutSidenav">
        @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
        <div id="layoutSidenav_content">
            <div class="m-1 d-md-flex border-bottom pb-3 mb-3 flex-md-row align-items-center justify-content-between">
                <h3 class="mb-0 ps-2 ">Manage Supplier</h3>
                <!-- Button trigger modal -->
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
                    Add Supplier
                </button>
                <button id="show_all_supplier" type="button" data-id="all" class="btn btn-primary">
                    Show All Supplier
                </button>
            </div>
            <div class="container">
                <input type="hidden" value="0" id="show"/>

                <div class="modal fade" id="editSupplierModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="edit_supplier">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Supplier Edit</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                                        <select class="form-select" name="show" id="show" aria-label="Default select example" required>
                                            <option value="0">Show</option>
                                            <option value="1">Hide</option>
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
                <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="add_supplier">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Supplier Add</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="editerrorMessage"></div>
                                <div class="editsuccessMessage"></div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="supplier_name" class="form-label">Supplier Name</label>
                                        <input type="text" class="form-control" name="supplier_name" id="supplier_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="category" class="form-label">Supplier Category</label>
                                        <input type="text" class="form-control" name="category" id="category" required>
                                    </div>
                                    <div class="mb-3">
                                        <select class="form-select" name="show" aria-label="Default select example" required>
                                            <option value="0">Show</option>
                                            <option value="1">Hide</option>
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
                <table id="supplier_data" class="data_table_files"></table>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $('#show_all_supplier').on('click', function() {
                var dataId = $(this).attr('data-id');
                $('#show').val($(this).attr('data-id'));
                
                if (dataId == 0) {
                    $(this).text('Show All Supplier');
                    $(this).attr('data-id', 'all');  
                } else {
                    $(this).text('Hide supplier');
                    $(this).attr('data-id', 0);  
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
                var show = 1;
                if ($(this).is(":checked")) {
                    show = 0;   
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
                        
                        }

                        if (response.success) {
                            // $('#page-loader').hide();
                            // $('#editsuccessMessage').html('');
                            // $('#editsuccessMessage').append('<div class="alert alert-success m-2 alert-dismissible fade show" role="alert">'+response.success+'<button type="button" class="close" data-dismiss="alert" aria-label="Close" id="closesuccessMessage"><span aria-hidden="true">&times;</span></button></div>');
                            // setTimeout(() => {
                                location.reload();
                            // }, 5000);
                            // $("form")[0].reset();
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
                var button = event.relatedTarget, // Button that triggered the modal
                id = button.getAttribute('data-id'), // Extract value from data-* attributes
                supplier_name = button.getAttribute('data-supplier_name'),
                category = button.getAttribute('data-category'),
                show = button.getAttribute('data-show'),
                supplierIdNameInput = document.getElementById('supplier_id'),
                supplierNameInput = document.getElementById('supplier_name'),
                categoryInput = document.getElementById('category'),
                showIdInput = document.getElementById('show');
                // Set the value of the input element
                supplierIdNameInput.value = id;
                supplierNameInput.value = supplier_name;
                categoryInput.value = category;
                showIdInput.value = show;
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
                            // $('#page-loader').hide();
                            // $('#editsuccessMessage').html('');
                            // $('#editsuccessMessage').append('<div class="alert alert-success m-2 alert-dismissible fade show" role="alert">'+response.success+'<button type="button" class="close" data-dismiss="alert" aria-label="Close" id="closesuccessMessage"><span aria-hidden="true">&times;</span></button></div>');
                            // setTimeout(() => {
                                location.reload();
                            // }, 5000);
                            // $("form")[0].reset();
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
    </script>
@endsection