<!-- resources/views/excel-import.blade.php -->


@extends('layout.app', ['pageTitleCheck' => 'Accounts Data'])

 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => 'Accounts Data','totalmissingaccount' => $totalmissingaccount])
    <div id="layoutSidenav_content" >
        <h3 class="mb-0 ps-2 ms-1">Manage Accounts</h3>
        <div class="row align-items-end border-bottom pb-3 pe-3 mb-4">
            <div class="col-md-12 mb-0 text-end">
                <!-- Button trigger modal -->
                <!-- <a href="{{ route('account.create')}}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Account</a> -->
                <button id="downloadAccountCsvBtn" class="btn-success btn" title="Csv Download"><i class="fa-solid me-2 fa-file-csv"></i>Download</button>
                    <a href="{{ route('account.customer-edit', ['totalmissingaccount' => $totalmissingaccount])}}" class="bell_icon_link btn btn-info position-relative">
                       <i class="fa-solid fa-bell"></i>
                       @if($totalmissingaccount)
                        @if($totalmissingaccount > 0)
                            <span class="notification-count">{{ $totalmissingaccount }}</span>
                        @endif
                        @endif
                    </a>
            </div>
        </div>
        <div class="mx-auto d-flex justify-content-between align-items-center">
        

                    <!-- Modal -->
                    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Add Account</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                        <div class="alert alert-success" id="successMessage" style="display:none;">
                        </div>
                        <div class="alert alert-danger" id="errorMessage" style="display:none;">
                        </div>
                            <form class="" id="add_supplier" method="post">
                                @csrf
                                <div class="form-group">
                                    <label>Customer ID </label>
                                    <input type="text" placeholder="Enter Customer Id" class="form-control" name="customer_id" id="customer_id">
                                </div> 
                                <div class="form-group">
                                    <label>Customer Name</label>
                                    <input type="text" placeholder="Enter Customer name" class="form-control" name="customer_name" id="customer_name">
                                </div>
                                <div class="form-group">
                                    <div class="form-check form-check-inline">
                                    <input type="checkbox" id="parent" class="form-check-input radio-checkbox" name="parent" value="1">
                                    <label class="form-check-label" for="parent">Parent</label>
                                    </div>
                                    <!-- <div class="form-check form-check-inline">
                                    <input type="checkbox" id="grandparent" class="form-check-input radio-checkbox" name="grandparent" value="0">
                                    <label class="form-check-label" for="grandparent">GrandParent</label>
                                    </div> -->
                                </div>
                                
                                <!-- <div class="form-group">
                                    <label for="selectBox"> Parent:</label>
                                <select id="parentSelect" name="parentSelect" class="form-control" disabled> 
                                <option value="" selected>--Select--</option>
                             
                                <option value=""></option>
                              
                                </select>
                                </div> -->

                                <div class="text-center">
                                <button type="submit" class="btn btn-primary mx-auto" id="supplier_add">Submit</button>
                                </div>

                            </form>
                        </div>
                        </div>
                    </div>
                    </div>
                    
        </div>
        <div class="alert alert-success m-3" id="account_del_success" style="display:none;">
                        </div>
        <div class="container">
      
            <table id="account_data" class="data_table_files">
            <thead>
                    <tr>
                        <th>Customer Number</th>
                        <th>Customer Name</th>
                        <th>Account Name</th>
                        <th>Supplier</th>
                        <th>Parent Name</th>
                        <th>Parent Number</th>
                        <!-- <th>GP Name</th> -->
                        <!-- <th>GP Number</th> -->
                        <th>Category</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
<div class="modal fade" id="editAccountModal" tabindex="-1" aria-labelledby="editAccountModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog">
      <form action="{{route('accountname.edit')}}" method="post" id="edit_account_name">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAccountModalLabel">Edit Account</h5>
                <!-- Close icon -->
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div id="editsuccessMessage"></div>
            <div  id="editerrorMessage" ></div>
                <div class="modal-body">
                    <div class="form-group">
                    @csrf

                    <div class="modal_input_wrap">
                    <input type="hidden" name="account_id" id="account_id" value="">
                    <label>Account Name</label>
                    </div>

                    <div class="modal_input_wrap pb-3">
                    <input type="text" placeholder="Enter Account Name" class="form-control" name="account_name" id="account_name" value="">
                    <div id="account_name_error"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> -->
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
            </form>   
        </div>
    </div>
</div>
        
    </div>
</div>
<script>
// Show/hide the divs based on radio button selection
// $('input[type="radio"]').change(function() {
//         var selectedValue = $(this).val();
//         console.log()
//         if(selectedValue == '1') {
//             $('.div1').show();
//             $('.div2').hide();
//         } else {
//             $('.div1').hide();
//             $('.div2').show();
//         }
//     });
        //set modal value 
    var myModal = document.getElementById('editAccountModal');
    myModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget, // Button that triggered the modal
        recipient = button.getAttribute('data-name'), // Extract value from data-* attributes
        id = button.getAttribute('data-id'),
        accountNameInput = document.getElementById('account_name'),
        accountIdInput = document.getElementById('account_id');
        // Set the value of the input element
        accountNameInput.value = recipient;
        accountIdInput.value = id;
    });

    function selectCustomer (count='') {
        $('.mySelectAccountGPName').select2({
            ajax: {
                url: "{{ route('commission.customerSearch') }}",
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term // search term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            minimumInputLength: 1
        }).on('select2:select', function (e) {
            var customerId = e.params.data.id; // Selected account_name ID
            // Perform your AJAX request here using the selected account_name ID
            $.ajax({
                url: "{{ route('commission.supplierSearch') }}",
                method: 'GET',
                data: {
                    customer_number: customerId
                },
                success: function(response) {
                    // Handle the AJAX response
                    $('.mySelectSupplier'+count+'').val(response[0].supplier);
                    $('.supplier_id'+count+'').val(response[0].id);
                },
                error: function(xhr, status, error) {
                    // Handle errors
                    console.error(error);
                }
            });
        });
    }

    $(document).ready(function() {
        var accountTable = $('#account_data').DataTable({
            oLanguage: {
                sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'
            },
            processing: true,
            serverSide: true,
            lengthMenu: [],
            pageLength: 50,
            ajax: {
                url: '{{ route("account.filter") }}',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: function (d) {
                    // Pass date range and supplier ID when making the request
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                    d.supplierId = $('#supplierId').val();
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
                { data: 'customer_number', name: 'customer_number' },
                { data: 'customer_name', name: 'customer_name' },
                { data: 'account_name', name: 'account_name' },
                { data: 'supplier_name', name: 'supplier_name' },
                { data: 'parent_name', name: 'parent_name' },
                { data: 'parent_id', name: 'parent_id' },
                // { data: 'grand_parent_name', name: 'grand_parent_name' },
                // { data: 'grand_parent_id', name: 'grand_parent_id' },
                { data: 'record_type', name: 'record_type' },
                { data: 'id', name: 'id', 'orderable': false, 'searchable': false }
            ],
        });

        // var rowCount = accountTable.rows().count();
        // console.log(rowCount);

        // alert(accountTable.fnGetData().length);
        // if (accountTable.data().count() > 40) {
        //     $('#account_data_paginate').show(); // Enable pagination
        // } else {
        //     $('#account_data_paginate').hide();
        // }

        $('#account_data_length').hide();
        
        // Attach a change event handler to the checkboxes
        $('input[type="checkbox"]').change(function() {
            // Check if the checkbox is checked or unchecked
            if ($(this).prop('checked')) {
                // $('#grandparentSelect').prop('disabled', false);
            } else{
                // $('#grandparentSelect').val('');
                // $('#grandparentSelect').prop('disabled', true);
            }
        });

        $('#exampleModal').on('show.bs.modal', function (e) {
            $('#errorMessage').fadeOut();
            $("#add_supplier")[0].reset();
            // $('#grandparentSelect').prop('disabled', true);
        })

        //submit form with ajax
        $("#add_supplier").on('submit', function (e){
            e.preventDefault();
            var formData = new FormData($('#add_supplier')[0]);
            $.ajax({
                type: 'POST',
                url: '{{ route("account.add") }}', // Replace with your actual route name
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if(response.error){
                        $('#errorMessage').text(response.error);
                        $('#errorMessage').css('display','block');
                        setTimeout(function () {
                            $('#errorMessage').fadeOut();
                        }, 5000);
                    }
                    // Assuming `response` is the error response object
                    let errorMessages = [];
                    if (response && response.error) {
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

                    // Set the content of the div with all accumulated error messages
                    if(response.success){
                        $('#page-loader').hide();
                        $('#successMessages').html('');
                        $('#successMessagess').append('<div class="alert alert-success alert-dismissible fade show" role="alert">' + response.success + '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                        $("form")[0].reset();
                    }
                    // Handle success response
                    // console.log(response);
                },
                error: function(xhr, status, error) {
                    // Handle error response
                    // console.error(xhr.responseText);
                    const errorresponse = JSON.parse(xhr.responseText);
                    $('#errorMessage').text(errorresponse.error);
                    $('#errorMessage').css('display','block');
                    setTimeout(function () {
                        $('#errorMessage').fadeOut();
                    }, 5000);
                }
            });
        });

        //update account name 
        $(document).on('click', '.btn-clos', function () {
            e.preventDefault();
            $('#editAccountModal form')[0].reset();
            $('#edit_account_name')[0].reset();
        });

        $('#editAccountModal').on('hidden.bs.modal', function (e) {
            // Reset the form inside the modal
            $('#editAccountModal form')[0].reset();
        });

        $("#edit_account_name").on('submit', function (e){
            e.preventDefault();
            var formData = new FormData($('#edit_account_name')[0]);
            $.ajax({
                type: 'POST',
                url: '{{ route("accountname.edit") }}', // Replace with your actual route name
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
                        accountTable.ajax.reload();   
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

        $('#downloadAccountCsvBtn').on('click', function () {
            // Trigger CSV download
            downloadAccountCsv();
        });

        function downloadAccountCsv() {
            // You can customize this URL to match your backend route for CSV download
            var csvUrl = '{{ route("account.export-csv") }}';

            // Open a new window to download the CSV file
            window.open(csvUrl, '_blank');
        }
    });
            
    // JavaScript to make checkboxes act like radio buttons
    const radioCheckboxes = document.querySelectorAll('.radio-checkbox');
    radioCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Uncheck all other checkboxes in the group
            radioCheckboxes.forEach(otherCheckbox => {
                if (otherCheckbox !== checkbox) {
                    otherCheckbox.checked = false;
                }
            });
        });
    });
    
    //to remove user 
    $(document).on('click', '.remove', function () {
        var id = $(this).attr('data-id');
        swal.fire({
            // title: "Oops....",
            text: "Are you sure you want to delete this Account?",
            icon: "error",
            showCancelButton: true,
            confirmButtonText: 'YES',
            cancelButtonText: 'NO',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('account.remove') }}",
                    data: { id: id },
                    success: function (response) {
                        if (response.success) {
                            $('#account_del_success').text('User Delete Successfully!');
                            $('#account_del_success').css('display', 'block');
                            setTimeout(function () {
                                $('#account_del_success').fadeOut();
                                location.reload();
                            }, 3000);
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