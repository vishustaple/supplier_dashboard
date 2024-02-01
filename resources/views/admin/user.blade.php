<!-- resources/views/excel-import.blade.php -->


@extends('layout.app')
 @extends('layout.sidenav')
 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => 'Accounts Data'])
    <div id="layoutSidenav_content">
        <div class="m-1 d-md-flex flex-md-row align-items-center justify-content-between">
            <h1 class="mb-0 ps-2">User Data</h1>
            <!-- Button trigger modal -->
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#userModal">
            Add User
            </button>
        </div>
        <div class="mx-auto py-4 d-flex justify-content-between align-items-center">
        

                    <!-- Modal -->
                    <div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
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
                        <form action="{{route('user.register')}}" method="POST">
                                        @csrf
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <div class="form-floating mb-3 mb-md-0">
                                                        <input class="form-control" id="inputFirstName" name="first_name" type="text" placeholder="Enter your first name" />
                                                        <label for="inputFirstName">First name</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-floating">
                                                        <input class="form-control" id="inputLastName" name="last_name"type="text" placeholder="Enter your last name" />
                                                        <label for="inputLastName">Last name</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-floating mb-3">
                                                <input class="form-control" id="inputEmail" name="email" type="email" placeholder="name@example.com" />
                                                <label for="inputEmail">Email address</label>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <div class="form-floating mb-3 mb-md-0">
                                                        <input class="form-control" id="inputPassword" name="password" type="password" placeholder="Create a password" />
                                                        <label for="inputPassword">Password</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-floating mb-3 mb-md-0">
                                                        <input class="form-control" id="inputPasswordConfirm" name="confirm_password" type="password" placeholder="Confirm password" />
                                                        <label for="inputPasswordConfirm">Confirm Password</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-4 mb-0">
                                                <div class="d-grid">
                                                    <!-- <a class="btn btn-primary btn-block" href="">Create Account</a> -->
                                                    <button type="submit" class="btn btn-primary btn-block">Create Account</button>
                                                </div>
                                            </div>
                                        </form>
                        </div>
                        </div>
                    </div>
                    </div>
        </div>
        <div class="container">
         
            <table id="account_data" class="data_table_files">
            <!-- Your table content goes here -->
            </table>
        </div>
        
    </div>
</div>
<script>
    $(document).ready(function() {
        // $('#grandparentSelect').change(function(){
        // var selectedValue = $(this).val();
        // console.log(selectedValue);
        // $.ajax({
        //     type: 'GET',
        //         url: '{{route('getparent')}}', 
        //         data:{ selectedValue: selectedValue },
        //         success: function(response) {},
        //         error: function(xhr, status, error) {

        //         }


        // });
            
        // });

        $('#account_data').DataTable({
            "paging": true,   // Enable pagination
            // "ordering": true, // Enable sorting
            "searching": true, // Enable search
            "pageLength": 40,
            "lengthChange":false,
            "data": <?php if(isset($accountsdata)){echo $accountsdata;}  ?>,
            "columns": [
                { title: 'SR. No' },
                { title: 'Account Name' },
                { title: 'Account Number' },
                { title: 'Parent Name' },
                { title: 'GrandParent Name' },
                { title :'Internal Reporting Name'},
                { title :'QBR'},
                { title :'Spend Name'},
                { title :'Supplier Acct Rep'},
                { title :'Management Fee'},
                { title :'Record Type'},
                { title :'Categroy Supplier'},
                { title :'CPG Sales Representative'},
                { title :'CPG Customer Service Rep'},
                { title :'SF Cat'},
                { title :'Rebate Freq'},
                { title :'Member Rebate'},
                { title :'Comm Rate'},
              
            ]
        });

        
        // Attach a change event handler to the checkboxes
        $('input[type="checkbox"]').change(function() {
            // Check if the checkbox is checked or unchecked
            if ($(this).prop('checked')) {
                $('#grandparentSelect').prop('disabled', false);
            } else{
                $('#grandparentSelect').val('');
                $('#grandparentSelect').prop('disabled', true);
            }
        });

        $('#exampleModal').on('show.bs.modal', function (e) {
            $('#errorMessage').fadeOut();
            $("#add_supplier")[0].reset();
            $('#grandparentSelect').prop('disabled', true);
        })

        //submit form with ajax

        $("#add_supplier").on('submit', function (e){
            // alert("here");

        e.preventDefault();
        var formData = new FormData($('#add_supplier')[0]);
        console.log(formData);
        $.ajax({
                type: 'POST',
                url: '{{ route('account.add') }}', // Replace with your actual route name
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
                    setTimeout(function () {
                        $('#errorMessage').fadeOut();
                        }, 5000);
                    }

                    // Set the content of the div with all accumulated error messages
                   
                   
                    if(response.success){
                        $('#page-loader').hide();
                        $('#successMessage').text(response.success);
                        $('#successMessage').css('display','block');
                        $("form")[0].reset();
                        //disable all field 
                        $('#enddate,#file,#importBtn').prop('disabled', true);
                        setTimeout(function () {
                            $('#successMessage').fadeOut();
                            window.location.reload();
                        }, 5000); 
                        
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
    
</script>

@endsection