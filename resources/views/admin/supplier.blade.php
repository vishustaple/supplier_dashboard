@extends('layout.app', ['pageTitleCheck' => $pageTitle])
@section('content')
    <div id="layoutSidenav">
        @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
        <div id="layoutSidenav_content">
            <div class="m-1 d-md-flex border-bottom pb-3 mb-3 flex-md-row align-items-center justify-content-between">
                <h3 class="mb-0 ps-2 ">Manage Supplier</h3>
            </div>
            <div class="container">
                <table id="supplier_data" class="data_table_files">
                <!-- Your table content goes here -->
                </table>
            </div>
        </div>
    </div>
    <div class="modal fade" id="editSupplierModal" tabindex="-1" aria-labelledby="editSupplierModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog">
      <form action="{{route('accountname.edit')}}" method="post" id="edit_supplier_name">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSupplierModalLabel">Edit Supplier</h5>
                <!-- Close icon -->
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div id="editsuccessMessage"></div>
            <div  id="editerrorMessage" ></div>
                <div class="modal-body">
                    <div class="form-group">
                    @csrf
                    <input type="hidden" id="supplier_id" name="supplier_id">
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
                    <div class="modal_input_wrap">
                        <label for="" class="form-label">Status</label>
                        <select class="form-control" name="status" id="status">
                            <option value="1">Active</option>
                            <option value="0">In-Active</option>
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
    <script>
        $(document).ready(function() {
        var myModal = document.getElementById('editSupplierModal');
        myModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Button that triggered the modal
            // Set the value of the input element
            document.getElementById('first_name').value = button.getAttribute('data-firt_name');
            document.getElementById('last_name').value = button.getAttribute('data-last_name');
            document.getElementById('phone').value = button.getAttribute('data-phone');
            document.getElementById('email').value = button.getAttribute('data-email');
            document.getElementById('supplier_id').value = button.getAttribute('data-id');
            if (button.getAttribute('data-status') == 1) {
                document.getElementById('status').options[0].selected = true;
            } else {
                document.getElementById('status').options[1].selected = true;
            }
        });

        $("#edit_supplier_name").on('submit', function (e){
            e.preventDefault();
            var formData = new FormData($('#edit_supplier_name')[0]);
            $.ajax({
                type: 'POST',
                url: '{{ route("supplier.edit") }}', // Replace with your actual route name
                data: formData,
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
                        $('#editsuccessMessage').append('<div class="alert alert-success alert-dismissible fade show" role="alert">' + response.success + '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                        location.reload();
                        // accountTable.ajax.reload();   
                    }
                },
                error: function(xhr, status, error) {
                    // Handle error response
                    // console.error(xhr.responseText);
                    const errorresponse = JSON.parse(xhr.responseText);
                    $('#editerrorMessage').text(errorresponse.error);
                    $('#editerrorMessage').css('display','block');
                    setTimeout(function () {
                        $('#editerrorMessage').fadeOut();
                    }, 5000);
                }
            });
        });

        $(document).ready(function() {
        var supplierTable = $('#supplier_data').DataTable({
                "paging": true,   // Enable pagination
                "ordering": true, // Enable sorting
                "searching": true, // Enable search
                "pageLength": 40,
                "lengthChange":false,
                "data": <?php if(isset($data)){echo $data;}  ?>,
                "columns": [
                    { title: 'Supplier Name' },
                    { title: 'First_name' },
                    { title: 'Last_name' },
                    { title: 'Phone' },
                    { title: 'Status' },
                    { title: 'Action' },
                ]
            });
            if (supplierTable.data().count() > 40) {
                $('#supplier_data_paginate').show(); // Enable pagination
            } else {
                $('#supplier_data_paginate').hide();
            }
        });
    });
    </script>
@endsection