@extends('layout.app', ['pageTitleCheck' => $pageTitle])
 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
    <div id="layoutSidenav_content">
        <div class="container">
            <div class="m-1 mb-2 row align-items-start justify-content-between">
                <div class="col-md-4">
                    <h3 class="mb-0 ">{{ $pageTitle }}</h3>
                </div>
                <div class="col-md-3 d-flex align-items-center justify-content-end pe-0">   
                    <a href="{{ route('supplier') }}" class="btn btn-secondary me-2 border-0 bg_yellow"><i class="fas fa-arrow-left me-2"></i>Back</a>
                    <button class="btn btn-primary" id="edit_supplier" data-bs-toggle="modal" data-bs-target="#addSupplierModal"><i class="fas fa-plus me-2"></i>Add</button>
                </div>
            </div>
            <div id="successMessages"></div>
            <div class="modal fade" id="editSupplierModal" tabindex="-1" aria-labelledby="editSupplierModalLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editSupplierModalLabel">Edit Supplier</h5>
                            <!-- Close icon -->
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div id="editsuccessMessage"></div>
                        <div  id="editerrorMessage" ></div>
                        <div class="modal-body">
                            <form method="post" id="edit_supplier_name">
                                @csrf
                                <div class="form-group">
                                    <input type="hidden" id="supplier_id"  value = "{{$id}}" name="supplier_id">
                                    <input type="hidden" id="supplier_detail_id" name="id">
                                    
                                    <div class="modal_input_wrap">
                                        <label>First Name</label>
                                        <input type="text" class="form-control" name="first_name" id="first_name" value="">
                                    </div>
                                    <div class="modal_input_wrap">
                                        <label>Last Name</label>
                                        <input type="text" class="form-control" name="last_name" id="last_name" value="">
                                    </div>
                                    <div class="modal_input_wrap">
                                        <label>Email</label>
                                        <input type="text" class="form-control" name="email" id="email" value="">
                                    </div>
                                    <div class="modal_input_wrap">
                                        <label>Phone</label>
                                        <input type="text" class="form-control" name="phone" id="phone" value="">
                                    </div>
                                    <div class="form-check ml-1">
                                        <input class="form-check-input" type="checkbox" name="main" value="1" id="checkboxs">
                                        <label class="form-check-label" for="checkboxs">Main</label>
                                    </div>
                                    <div class="row">
                                    <div class="modal_input_wrap col-md-6">
                                        <label for="" class="form-label">Status</label>
                                        <select class="form-select" name="status" id="status">
                                            <option value="1">Active</option>
                                            <option value="0">In-Active</option>
                                        </select>
                                    </div>
                                    <div class="modal_input_wrap col-md-6">
                                        <label>Department</label>
                                        <select class="form-select" name="department" id="department">
                                            <option value="" selected>Select</option>
                                            @if(isset($departments))
                                                @foreach($departments as $department)
                                                    <option value="{{ $department->id }}">{{ $department->department }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                </div>
                            </form>   
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="addSupplierModal" tabindex="-1" aria-labelledby="addSupplierModalLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addSupplierModalLabel">Add Supplier</h5>
                            <!-- Close icon -->
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div id="addsuccessMessage"></div>
                        <div  id="adderrorMessage" ></div>
                        <div class="modal-body">
                            <form method="post" id="add_supplier_name">
                                @csrf
                                <div class="form-group">
                                    <input type="hidden" id="supplier_id"  value = "{{$id}}" name="supplier_id">
                                 
                                    <div class="modal_input_wrap">
                                        <label>First Name</label>
                                        <input type="text" class="form-control" name="first_name" id="first_names" value="">
                                    </div>
                                    <div class="modal_input_wrap">
                                        <label>Last Name</label>
                                        <input type="text" class="form-control" name="last_name" id="last_names" value="">
                                    </div>
                                    <div class="modal_input_wrap">
                                        <label>Email</label>
                                        <input type="text" class="form-control" name="email" id="emails" value="">
                                    </div>
                                    <div class="modal_input_wrap">
                                        <label>Phone</label>
                                        <input type="text" class="form-control" name="phone" id="phones" value="">
                                    </div>
                                    <div class="form-check ml-1">
                                        <input class="form-check-input" name="main" type="checkbox" value="1" id="mains">
                                        <label class="form-check-label" for="mains">Main</label>
                                    </div>
                                    <div class="row">
                                    <div class="modal_input_wrap col-md-6">
                                        <label for="" class="form-label">Status</label>
                                        <select class="form-select" name="status" id="statuss">
                                            <option value="1">Active</option>
                                            <option value="0">In-Active</option>
                                        </select>
                                    </div>
                                    <div class="modal_input_wrap col-md-6">
                                        <label>Department</label>
                                        <select class="form-select" name="department" id="departments">
                                            <option value="" selected>Select</option>
                                            @if(isset($departments))
                                                @foreach($departments as $department)
                                                    <option value="{{ $department->id }}">{{ $department->department }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                </div>
                            </form>   
                        </div>
                    </div>
                </div>
            </div>
            <table id="supplier_detail_data" class="data_table_files">
                <!-- Your table content goes here -->
            </table>
        </div>
    </div>
</div>
<!-- Include Date Range Picker JavaScript -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
<script>
    $(document).ready(function() {
        // DataTable initialization
        var supplierDataTable = $('#supplier_detail_data').DataTable({
            oLanguage: {sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'},
            processing: true,
            serverSide: true,
            lengthMenu: [40], // Specify the options you want to show
            lengthChange: false, // Hide the "Show X entries" dropdown
            searching:false, 
            pageLength: 40,
            order: [[3, 'desc']],
            ajax: {
                url: '{{ route("supplier_detail_filter") }}',
                type: 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: function (d) {
                    // Pass date range and supplier ID when making the request
                    d.supplier_id = $('#supplier_id').val();
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
                { data: 'main', name: 'main', title: 'Main'},
                { data: 'name', name: 'name', title: 'Name'},
                { data: 'department', name: 'department', title: 'Department'},
                { data: 'email', name: 'email', title: 'Email'},
                { data: 'phone', name: 'phone', title: 'Phone'},
                { data: 'status', name: 'status', title: 'Status'},
                { data: 'id', name: 'id', title: 'Action', 'orderable': false, 'searchable': false},
            ],
        });

        document.getElementById('editSupplierModal').addEventListener('show.bs.modal', function (event) {
            // Set the value of the input element
            document.getElementById('phone').value = event.relatedTarget.getAttribute('data-phone');
            document.getElementById('department').value = event.relatedTarget.getAttribute('data-department');
            document.getElementById('email').value = event.relatedTarget.getAttribute('data-email');
            document.getElementById('supplier_detail_id').value = event.relatedTarget.getAttribute('data-id');
            document.getElementById('last_name').value = event.relatedTarget.getAttribute('data-last_name');
            document.getElementById('first_name').value = event.relatedTarget.getAttribute('data-first_name');
            
            if (event.relatedTarget.getAttribute('data-main') == 1) {
                document.getElementById('checkboxs').checked = true;
            }

            if (event.relatedTarget.getAttribute('data-status') == 1) {
                document.getElementById('status').options[0].selected = true;
            } else {
                document.getElementById('status').options[1].selected = true;
            }
        });

        $(document).on('click', '.remove', function(){
            var formData = { supplier_id : "{{ $id }}", id : $(this).data('id') },
            token = "{{ csrf_token() }}";
            $.ajax({
                type: 'POST',
                url: "{{route('supplierDetail.delete')}}",
                dataType: 'json',
                data: JSON.stringify(formData),                        
                headers: {'X-CSRF-TOKEN': token},
                contentType: 'application/json',                     
                processData: false,
                success: function(response) {
                    if(response.success){
                        $('#supplier_detail_data').DataTable().ajax.reload();
                    }
                },
                error: function(xhr, status, error) {
                    // Handle error response
                    $('#editerrorMessage').text(JSON.parse(xhr.responseText).error);
                    $('#editerrorMessage').css('display','block');
                    setTimeout(function () {
                        $('#editerrorMessage').fadeOut();
                    }, 5000);
                }
            });
        });

        // Assuming there is only one element with the class checkboxMain
        $(document).on('change', '.checkboxMain', function(){
            var main = 0;
            if ($(this).is(":checked")) {
                main = 1;   
            }

            var formData = { supplier_id : "{{ $id }}", id : $(this).data('id'), main : main },
            token = "{{ csrf_token() }}";
            $.ajax({
                type: 'POST',
                url: "{{route('main.update')}}",
                dataType: 'json',
                data: JSON.stringify(formData),                        
                headers: {'X-CSRF-TOKEN': token},
                contentType: 'application/json',                     
                processData: false,
                success: function(response) {
                    if(response.success){
                        $('#supplier_detail_data').DataTable().ajax.reload();
                    }
                },
                error: function(xhr, status, error) {
                    // Handle error response
                    $('#editerrorMessage').text(JSON.parse(xhr.responseText).error);
                    $('#editerrorMessage').css('display','block');
                    setTimeout(function () {
                        $('#editerrorMessage').fadeOut();
                    }, 5000);
                }
            });
        });

        $("#edit_supplier_name").on('submit', function (e){
            e.preventDefault();
            $.ajax({
                type: 'POST',
                url: '{{ route("supplier.edit") }}', // Replace with your actual route name
                data: new FormData($('#edit_supplier_name')[0]),
                processData: false,
                contentType: false,
                success: function(response) {
                    if(response.error){
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

                        $('#editerrorMessage').text('');
                        $('#editerrorMessage').append('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+errorMessage+'<button type="button" class="close" data-dismiss="alert" aria-label="Close" id="closeerrorMessage"><span aria-hidden="true">&times;</span></button></div>');
                    }

                    if(response.success){
                        $('#page-loader').hide();
                        $('#successMessages').html('');
                        $('#successMessages').append('<div class="alert alert-success alert-dismissible fade show" role="alert">' + response.success + '<button type="button" class="close" data-dismiss="alert" aria-label="Close" id="closesuccessMessage" ><span aria-hidden="true">&times;</span></button></div>');
                        $('#editSupplierModal').modal('hide');
                        $('#supplier_detail_data').DataTable().ajax.reload();
                    }
                },
                error: function(xhr, status, error) {
                    // Handle error response
                    $('#editerrorMessage').text(JSON.parse(xhr.responseText).error);
                    $('#editerrorMessage').css('display','block');
                    setTimeout(function () {
                        $('#editerrorMessage').fadeOut();
                    }, 5000);
                }
            });
        });

        $("#add_supplier_name").on('submit', function (e){
            e.preventDefault();
            $.ajax({
                type: 'POST',
                url: '{{ route("supplier.add") }}', // Replace with your actual route name
                data: new FormData($('#add_supplier_name')[0]),
                processData: false,
                contentType: false,
                success: function(response) {
                    if(response.error){
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

                        $('#adderrorMessage').text('');
                        $('#adderrorMessage').append('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+errorMessage+'<button type="button" class="close" data-dismiss="alert" aria-label="Close" id="closeerrorMessage"><span aria-hidden="true">&times;</span></button></div>');
                    }

                    if(response.success){
                        $('#page-loader').hide();
                        $('#successMessages').html('');
                        $('#successMessages').append('<div class="alert alert-success alert-dismissible fade show" role="alert">' + response.success + '<button type="button" class="close" data-dismiss="alert" aria-label="Close" id="closesuccessMessage" ><span aria-hidden="true">&times;</span></button></div>');
                        $('#addSupplierModal').modal('hide');
                        $('#supplier_detail_data').DataTable().ajax.reload();
                        document.getElementById("add_supplier_name").reset();
                    }
                },
                error: function(xhr, status, error) {
                    // Handle error response
                    $('#adderrorMessage').text(JSON.parse(xhr.responseText).error);
                    $('#adderrorMessage').css('display','block');
                    setTimeout(function () {
                        $('#adderrorMessage').fadeOut();
                    }, 5000);
                }
            });
        });

        $('#downloadCsvBtn').on('click', function () {
            // Trigger CSV download
            downloadCsv();
        });

        function downloadCsv() {
            // You can customize this URL to match your backend route for CSV download
            var csvUrl = '{{ route("report.export-supplier_report-csv") }}', order = supplierDataTable.order();

            // Add query parameters for date range and supplier ID
            csvUrl += '?year=' + $('#year').val() + '&quarter=' + $('#quarter').val() + '&column=' + order[0][0] + '&order=' + order[0][1] + '&supplier=' + $('#supplier').val();

            // Open a new window to download the CSV file
            window.open(csvUrl, '_blank');
        } 
    });        
</script>
@endsection