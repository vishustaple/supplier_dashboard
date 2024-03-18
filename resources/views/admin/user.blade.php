
@extends('layout.app', ['pageTitleCheck' => 'User Data'])

 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => 'User Data'])
    <div id="layoutSidenav_content">
        <div class="m-1 px-2 d-md-flex border-bottom pb-3 mb-3 flex-md-row align-items-center justify-content-between">
            <h3 class="mb-0 ps-2">Manage Users</h3>
            <!-- Button trigger modal -->
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#userModal">
            <i class="fa-solid fa-plus"></i> User
            </button>
        </div>
        <div id="user_del_success" ></div>
        <div class="mx-auto py-0 d-flex justify-content-between align-items-center">
                    <!--Add User Modal -->
            <div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Add User</h5>
                            <button type="button" class="close addpopup" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div  id="successMessage" >
                            </div>
                            <div  id="errorMessage" >
                            </div>

                            <form action="" id="add_user" method="POST">
                                        @csrf
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group mb-3 mb-md-0">
                                                        <label for="inputFirstName">First name</label>
                                                        <input class="form-control" id="inputFirstName" name="first_name" type="text" placeholder="Enter your first name" />
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="inputLastName">Last name</label>
                                                        <input class="form-control" id="inputLastName" name="last_name"type="text" placeholder="Enter your last name" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label for="inputEmail">Email address</label>
                                                <input class="form-control" id="inputEmail" name="email" type="email" placeholder="name@example.com" />
                                            </div>
                                            <!-- <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <div class="form-group mb-3 mb-md-0">
                                                        <label for="inputPassword">Password</label>
                                                        <input class="form-control" id="inputPassword" name="password" type="password" placeholder="Create a password" />
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group mb-3 mb-md-0">
                                                        <label for="inputPasswordConfirm">Confirm Password</label>
                                                        <input class="form-control" id="inputPasswordConfirm" name="confirm_password" type="password" placeholder="Confirm password" />
                                                    </div>
                                                </div>
                                            </div> -->
                                            <div class="form-group mb-3">
                                                <label for="userrole">User Role</label>
                                                    <select id="user_role" name="user_role" class="form-control"> 
                                                    <option value="" selected>--Select--</option>
                                                    <option value="2">Admin</option>
                                                    <option value="3">User</option>
                                                    </select>
                                            </div>
                                            <div class="mt-4 mb-0">
                                                <div class="d-grid">
                                                    <!-- <a class="btn btn-primary btn-block" href="">Create Account</a> -->
                                                    <button type="submit" class="btn btn-primary mx-auto">Create User</button>
                                                </div>
                                            </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mx-auto py-0 d-flex justify-content-between align-items-center">
            <!-- Update User Modal -->
            <div class="modal fade" id="updateuserModal" tabindex="-1" role="dialog" aria-labelledby="updateuserModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="updateuserModalLabel">Update User</h5>
                            <button type="button" class="close updatemodal" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div  id="updatesuccessMessage" >  </div>
                            <div  id="updateerrorMessage" ></div>
                                <form action="" id="update_user" method="POST">
                                        @csrf
                                        <input type="hidden" name="update_user_id" id="update_user_id">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group mb-3 mb-md-0">
                                                        <label for="inputFirstName">First name</label>
                                                        <input class="form-control" id="updateFirstName" name="first_name" type="text" placeholder="Enter your first name" />
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="inputLastName">Last name</label>
                                                        <input class="form-control" id="updateLastName" name="last_name"type="text" placeholder="Enter your last name" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label for="inputEmail">Email address</label>
                                                <input class="form-control" id="updateinputEmail" name="email" type="email" placeholder="name@example.com" />
                                            </div>
                                            <!-- <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <div class="form-group mb-3 mb-md-0">
                                                        <label for="inputPassword">Password</label>
                                                        <input class="form-control" id="updateinputPassword" name="password" type="password" placeholder="Create a password" />
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group mb-3 mb-md-0">
                                                        <label for="inputPasswordConfirm">Confirm Password</label>
                                                        <input class="form-control" id="updateinputPasswordConfirm" name="confirm_password" type="password" placeholder="Confirm password" />
                                                    </div>
                                                </div>
                                            </div> -->
                                            <div class="form-group mb-3">
                                                <label for="userrole">User Role</label>
                                                    <select id="update_user_role" name="update_user_role" class="form-control"> 
                                                    <option value="" selected>--Select--</option>
                                                    <option value="2">Admin</option>
                                                    <option value="3">User</option>
                                                    </select>
                                            </div>
                                            <div class="mt-4 mb-0">
                                                <div class="d-grid">
                                                    <!-- <a class="btn btn-primary btn-block" href="">Create Account</a> -->
                                                    <button type="submit" class="btn btn-primary mx-auto">Update User</button>
                                                </div>
                                            </div>
                                </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <table id="user_data" class="data_table_files">
            <!-- Your table content goes here -->
            </table>
        </div>
        
    </div>
</div>

<script type="text/javascript">
   
    $(document).ready(function() {
     
       var userTable = $('#user_data').DataTable({
            "paging": true,   // Enable pagination
            "ordering": false, // Enable sorting
            "searching": true, // Enable search
            "pageLength": 40,
            "lengthChange":false,
            "data": <?php if(isset($data)){echo $data;}  ?>,
            "columns": [
                // { title: 'SR. No' },
                { title: 'User Name' },
                { title: 'User Role' },
                { title: 'Action' },

            
            ]
        });
        // if (userTable.data().count() > 40) {
        //     $('#user_data_paginate').show(); // Enable pagination
        // } else {
        //     $('#user_data_paginate').hide();
        // }

        $('#userModal').on('show.bs.modal', function (e) {
            $('#errorMessage').fadeOut();
            $('#successMessage').fadeOut();
            $("#add_user")[0].reset();
        })

        //submit user form with ajax

        $("#add_user").on('submit', function (e){
        e.preventDefault();
        var formData = new FormData($('#add_user')[0]);
        // console.log(formData);
        $.ajax({
                type: 'POST',
                url: '{{ route("user.register") }}', // Replace with your actual route name
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                     console.log(response);
                     if(response.error){
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
                        $('#errorMessage').css('display','block');
                        $('#page-loader').hide();
                        $('#errorMessage').append('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+errorMessage+'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                    }
                    if(response.success){
                        $('#page-loader').hide();
                        $('#successMessage').html('');
                        $('#successMessage').css('display','block');
                        $('#successMessage').append('<div class="alert alert-success alert-dismissible fade show" role="alert">' + response.success + '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                        $("form")[0].reset();
                    }    
                },
                error: function(xhr, status, error) {          
                    const errorresponse = JSON.parse(xhr.responseText);
                        // $('#errorMessage').text(errorresponse.error);
                        // $('#errorMessage').css('display','block');
                        // setTimeout(function () {
                        // $('#errorMessage').fadeOut();
                        // }, 3000);
                }
            });
        });

       
         //get data on updateform
            $('.updateuser').on('click',function(e){ 
            e.preventDefault();
            var id=$(this).attr("data-userid"); 
            // alert(id);
            $.ajax({
                type: 'GET',
                url: '{{ route("user.updateuser") }}',
                data: { id: id },
                success: function(response) {
                    if (response.error) {
                        $('#errorMessage').text(response.error);
                        $('#errorMessage').css('display', 'block');
                        setTimeout(function () {
                        $('#errorMessage').fadeOut();
                        }, 3000);
                    } else {
                    // console.log(response.editUserData.password);
                        $('#update_user_id').val(response.editUserData.id);
                        $('#updateFirstName').val(response.editUserData.first_name);
                        $('#updateLastName').val(response.editUserData.last_name);
                        $('#updateinputEmail').val(response.editUserData.email);
                        // $('#user_type').val(response.editUserData.user_type);
                        $('#update_user_role option[value="' + response.editUserData.user_type + '"]').prop('selected', true);

                        $('#updateuserModal').modal('show');
                    }
                },
                error:function(xhr, status, error) {
               
                    const errorresponse = JSON.parse(xhr.responseText);
                    $('#errorMessage').text(errorresponse.error);
                    $('#errorMessage').css('display','block');
                    setTimeout(function () {
                    $('#errorMessage').fadeOut();
                    }, 3000);
                    }
                });
            });
               //close model on close 
                var updateuserModal = $('#updateuserModal');
                var closeButton = updateuserModal.find('.close');
                closeButton.on('click', function () {
                updateuserModal.modal('hide');
                });


            // updateform data on submit 
            $('#updateuserModal').on('submit',function(e){ 
            e.preventDefault();
            var formData = new FormData($('#update_user')[0]);
            // console.log(formData);
                $.ajax({
                type: 'POST',
                url: '{{ route("user.updateuserdata") }}',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log(response);
                    if(response.error){
                        var errorMessage = '';
                        if (typeof response.error === 'object') {
                            // Iterate over the errors object
                            $.each(response.error, function (key, value) {
                                errorMessage += value[0] + '<br>';
                            });
                        } else {   
                            errorMessage = response.error;
                        }
                        $('#updateerrorMessage').html('');
                        $('#updateerrorMessage').css('display','block');
                        $('#page-loader').hide();
                        $('#updateerrorMessage').append('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+errorMessage+'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                    }
                    
                    if(response.success){
                        $('#page-loader').hide();
                        $('#updatesuccessMessage').html('');
                        $('#updatesuccessMessage').css('display','block');
                        $('#updatesuccessMessage').append('<div class="alert alert-success alert-dismissible fade show" role="alert">' + response.success + '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                        $("form")[0].reset();
                    }   
                },
                error:function(xhr, status, error) {
               
                    const errorresponse = JSON.parse(xhr.responseText);
                    $('#updateerrorMessage').text(errorresponse.error);
                    $('#updateerrorMessage').css('display','block');
                    setTimeout(function () {
                    $('#updateerrorMessage').fadeOut();
                    }, 3000);
                    }   
                });

            });

               //to remove user 
                $(document).on('click','.remove',function(){               
                var id = $(this).attr('data-id');
                    swal.fire({
                        title: "Oops....",
                        text: "Are you sure you want to delete this user?",
                        icon: "error",
                        showCancelButton: true,
                        confirmButtonText: 'YES',
                        cancelButtonText: 'NO',
                        reverseButtons: true
                        }).then((result) => {
                                 if (result.isConfirmed) {
                                    $.ajax({
                                    url:"{{route('user.remove')}}",
                                    data:{id:id},
                                    success:function(data)
                                    {
                                        $('#user_del_success').html('');
                                        $('#user_del_success').css('display','block');
                                        $('#user_del_success').append('<div class="alert alert-success alert-dismissible fade show m-3" role="alert"> User Delete Successfully! <button type="button" class="close deletemodal" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');  
                                    },
                                    error:function(error){
                                    
                                    }
                                    });
                            } 
                            else {

                            }
                    });
                });
    });
     //on close window reload after adding user 
        $(document).on('click', '.updatemodal, .addpopup, .deletemodal', function() {
              location.reload();
        
        });
      
 
    
</script>

@endsection