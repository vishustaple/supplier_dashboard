
@extends('layout.app', ['pageTitleCheck' => 'User Data'])

 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => 'User Data'])
    <div id="layoutSidenav_content">
        <style>
    .inactive-user {
        background-color: #f8d7da !important; /* Light red background for inactive users */
        color: #721c24 !important; /* Dark red text color */
    }

    div#updateuserModal button.close.updatemodal,div#userModal button.close{
        align-items: center;
    }

    .permission-group {
        margin-bottom: 30px;
    }

    .permission-heading {
        font-weight: bold;
        font-size: 1.1rem;
        margin-bottom: 5px;
    }

    .permission-items-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr); /* Two columns */
        gap: 10px 30px; /* row-gap and column-gap */
    }

    .permission-item {
        display: flex;
        align-items: center;
    }

    .permission-item input {
        margin-right: 8px;
    }
    
    label {
        display: inline-block;
        margin-bottom: -0.0rem;
    }
</style>
        <div class="m-1 px-2 d-md-flex border-bottom pb-3 mb-3 flex-md-row align-items-center justify-content-between">
            <h3 class="mb-0 ps-2">Manage Users</h3>
            <!-- Button trigger modal -->
            @if(isset($userInfo) && $userInfo->user_type != 3) 
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#userModal"><i class="fa-solid fa-plus"></i> User </button>
            @endif
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
                                @auth
                                    @if (Auth::user()->user_type == 1)
                                        <div class="form-group mb-3">
                                            <label for="userrole">User Status</label>
                                            <select id="user_status" name="user_status" class="form-control"> 
                                                <option value="" selected>--Select--</option>
                                                <option value="1">Active</option>
                                                <option value="0">In-Active</option>
                                            </select>
                                        </div>
                                    @endif
                                @endauth
                                <div class="form-group mb-3">
                                    <label for="userrole">User Role</label>
                                    <select id="user_role" name="user_role" class="form-control"> 
                                        <option value="" selected>--Select--</option>
                                        <option value="2">Admin</option>
                                        <option value="3">User</option>
                                    </select>
                                </div>
                                @php
                                    $reportPermissions = $permissions->where('report_type', 1);
                                    $powerBiPermissions = $permissions->where('report_type', 2);
                                    $userPagePermissions = $permissions->where('report_type', 3);
                                @endphp

                                <div class="permissions" id="add_permissions">
                                    @if($userPagePermissions->count())
                                        <div class="permission_headings permission-group">
                                            <p class="permission-heading">Page Permissions:</p>
                                            <div class="permission-items-grid">
                                                @foreach($userPagePermissions as $permission)
                                                    <div class="permission-item">
                                                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}">
                                                        <label>{{ $permission->name }}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if($reportPermissions->count())
                                        <div class=" report_permission_headings permission-group">
                                            <p class="permission-heading">Report:</p>
                                            <div class="permission-items-grid">
                                                @foreach($reportPermissions as $permission)
                                                    <div class="permission-item">
                                                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}">
                                                        <label>{{ $permission->name }}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if($powerBiPermissions->count())
                                        <div class="power_bi_report_permission_headings permission-group">
                                            <p class="permission-heading">Power BI Report:</p>
                                            <div class="permission-items-grid">
                                                @foreach($powerBiPermissions as $permission)
                                                    <div class="permission-item">
                                                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}">
                                                        <label>{{ $permission->name }}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
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
                                @auth
                                    @if (Auth::user()->user_type == 1)
                                        <div class="form-group mb-3">
                                            <label for="userrole">User Status</label>
                                            <select id="updateinputStatus" name="update_user_status" class="form-control"> 
                                                <option value="" selected>--Select--</option>
                                                <option value="0">In-Active</option>
                                                <option value="1">Active</option>
                                            </select>
                                        </div>
                                    @endif
                                @endauth
                                <div class="form-group mb-3">
                                    <label for="userrole">User Role</label>
                                        <select id="update_user_role" name="update_user_role" class="form-control"> 
                                        <option value="" selected>--Select--</option>
                                        <option value="2">Admin</option>
                                        <option value="3">User</option>
                                        </select>
                                </div>
                                <div class="permissions" id="permissions-container"></div>
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
                { title: 'User Name' },
                { title: 'User Role' },
                { title: 'User Status' },
                <?php if(isset($userInfo) && $userInfo->user_type != 3) { ?>
                { title: 'Action' },
                <?php }?>
            ],

            "createdRow": function(row, data, dataIndex) {
                if (data[2] === 'In-Active') { // Check if the status is 'In-Active'
                    $(row).addClass('inactive-user');
                }
            }
        });

        $('#userModal').on('show.bs.modal', function (e) {
            if ($(this).val() == 2) {
                $('.permission_heading').show();
                $('.report_permission_heading').hide();
                $('.power_bi_report_permission_heading').hide();

                $('.permission_headings').show();
                $('.report_permission_headings').hide();
                $('.power_bi_report_permission_headings').hide();

                $('input[type="checkbox"]').prop('checked', false);
                $('input[type="checkbox"]').parent().hide();
                $('input[type="checkbox"][value="4"]').parent().show();
            } else if ($(this).val() == 3) {
                $('.permission_heading').show();
                $('.report_permission_heading').show();
                $('.power_bi_report_permission_heading').show();

                $('.permission_headings').show();
                $('.report_permission_headings').show();
                $('.power_bi_report_permission_headings').show();

                $('input[type="checkbox"]').prop('checked', false);
                $('input[type="checkbox"]').parent().show();
            } else {
                $('.permission_heading').hide();
                $('.report_permission_heading').hide();
                $('.power_bi_report_permission_heading').hide();

                $('.permission_headings').hide();
                $('.report_permission_headings').hide();
                $('.power_bi_report_permission_headings').hide();

                $('input[type="checkbox"]').parent().hide();
            }
    
            $('#errorMessage').fadeOut();
            $('#successMessage').fadeOut();
            $("#add_user")[0].reset();
        })

        // Function to fetch user and permissions data
        function fetchUserPermissions(userId) {
            return new Promise(function(resolve, reject) {
                $.ajax({
                    url: "{{ route('user.editPermissions', ':userId') }}".replace(':userId', userId),
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        // Resolve the promise with the response data
                        resolve(response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Failed to fetch user permissions', error);
                        // Reject the promise with the error
                        reject(error);
                    }
                });
            });
        }

        // Function to render checkboxes for permissions
        async function renderPermissions(user, permissions) {
            var permissionsContainer = $('#permissions-container');
            permissionsContainer.empty();

            // Create grouped containers
            var reportContainer = $('<div class="report_permission_headings permission-group" id="report_permissions"></div>'),
                powerBIContainer = $('<div class="power_bi_report_permission_headings permission-group" id="power_bi_permissions"></div>'),
                userPermissionContainer = $('<div class="permission_headings permission-group" id="user_permissions"></div>');

            // Add section headings
            reportContainer.append('<p class="permission-heading">Report:</p>');
            powerBIContainer.append('<p class="permission-heading">Power BI Report:</p>');
            userPermissionContainer.append('<p class="permission-heading">Page Permissions:</p>');

            // Add inner grids for each section
            var reportGrid = $('<div class="permission-items-grid"></div>'),
                powerBIGrid = $('<div class="permission-items-grid"></div>'),
                userPermissionGrid = $('<div class="permission-items-grid"></div>');

            permissions.forEach(function(permission) {
                var checkbox = $('<input>', { type: 'checkbox', name: 'permissions[]', value: permission.id });
                var isPermissionChecked = user.permissions.some(function(userPermission) {
                    return userPermission.id === permission.id;
                });
                if (isPermissionChecked) checkbox.prop('checked', true);

                var label = $('<label>').text(permission.name);
                var wrapper = $('<div class="permission-item"></div>').append(checkbox, label);

                if (permission.report_type == 1) {
                    reportGrid.append(wrapper);
                } else if (permission.report_type == 2) {
                    powerBIGrid.append(wrapper);
                } else if (permission.report_type == 3) {
                    userPermissionGrid.append(wrapper);
                }
            });

            // Append grids to containers
            reportContainer.append(reportGrid);
            powerBIContainer.append(powerBIGrid);
            userPermissionContainer.append(userPermissionGrid);

            // Append everything to the main container
            permissionsContainer.append(reportContainer, powerBIContainer, userPermissionContainer);

            if ($('#update_user_role').val() == 2) {
                $('.permission_headings').show();
                $('.report_permission_headings').hide();
                $('.power_bi_report_permission_headings').hide();
            } else if ($('#update_user_role').val() == 3) {
                $('.permission_headings').show();
                $('.report_permission_headings').show();
                $('.power_bi_report_permission_headings').show();
            } else {
                $('.permission_headings').hide();
                $('.report_permission_headings').hide();
                $('.power_bi_report_permission_headings').hide();
            }
        }

        $('input[type="checkbox"]').parent().hide();
        if ($('#user_role').val() == 2) {
            $('.permission_heading').show();
            $('.report_permission_heading').hide();
            $('.power_bi_report_permission_heading').hide();

            $('input[type="checkbox"]').parent().hide();
            $('input[type="checkbox"]').prop('checked', false);
            $('input[type="checkbox"][value="4"]').parent().show();
        } else if ($('#user_role').val() == 3) {
            $('.permission_heading').show();
            $('.report_permission_heading').show();
            $('.power_bi_report_permission_heading').show();

            $('input[type="checkbox"]').prop('checked', false);
            $('input[type="checkbox"]').parent().show();
        } else {
            $('.permission_heading').hide();
            $('.report_permission_heading').hide();
            $('.power_bi_report_permission_heading').hide();

            $('input[type="checkbox"]').prop('checked', false);
            $('input[type="checkbox"]').parent().hide();
        }

        $('#user_role, #update_user_role').on('change', function(){
            if ($(this).val() == 2) {
                $('.permission_heading').show();
                $('.report_permission_heading').hide();
                $('.power_bi_report_permission_heading').hide();

                $('.permission_headings').show();
                $('.report_permission_headings').hide();
                $('.power_bi_report_permission_headings').hide();

                $('input[type="checkbox"]').prop('checked', false);
                $('input[type="checkbox"]').parent().hide();
                $('input[type="checkbox"][value="4"]').parent().show();
            } else if ($(this).val() == 3) {
                $('.permission_heading').show();
                $('.report_permission_heading').show();
                $('.power_bi_report_permission_heading').show();

                $('.permission_headings').show();
                $('.report_permission_headings').show();
                $('.power_bi_report_permission_headings').show();

                $('input[type="checkbox"]').prop('checked', false);
                $('input[type="checkbox"]').parent().show();
            } else {
                $('.permission_heading').hide();
                $('.report_permission_heading').hide();
                $('.power_bi_report_permission_heading').hide();

                $('.permission_headings').hide();
                $('.report_permission_headings').hide();
                $('.power_bi_report_permission_headings').hide();

                $('input[type="checkbox"]').parent().hide();
            }
        });

        //submit user form with ajax
        $("#add_user").on('submit', function (e){
            e.preventDefault();
            var formData = new FormData($('#add_user')[0]);
            $.ajax({
                type: 'POST',
                url: '{{ route("user.register") }}', // Replace with your actual route name
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    //  console.log(response);
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
                        if ($('#user_role').val() == 2) {
                            $('.permission_heading').show();
                            $('input[type="checkbox"]').parent().hide();
                            $('input[type="checkbox"]').prop('checked', false);
                            $('input[type="checkbox"][value="4"]').parent().show();
                        } else if ($('#user_role').val() == 3) {
                            $('.permission_heading').show();
                            $('input[type="checkbox"]').prop('checked', false);
                            $('input[type="checkbox"]').parent().show();
                        } else {
                            $('.permission_heading').hide();
                            $('input[type="checkbox"]').prop('checked', false);
                            $('input[type="checkbox"]').parent().hide();
                        }
                    }    
                },
                error: function(xhr, status, error) {          
                    const errorresponse = JSON.parse(xhr.responseText);
                }
            });
        });

        //get data on updateform
        $('.updateuser').on('click', async function(e) {
            e.preventDefault();
            var id = $(this).attr("data-userid");
            try {
                const response = await $.ajax({
                    type: 'GET',
                    url: '{{ route("user.updateuser") }}',
                    data: { id: id }
                });
                
                if (response.error) {
                    $('#errorMessage').text(response.error);
                    $('#errorMessage').css('display', 'block');
                    setTimeout(function() {
                        $('#errorMessage').fadeOut();
                    }, 3000);
                } else {
                    $('#update_user_id').val(response.editUserData.id);
                    $('#updateFirstName').val(response.editUserData.first_name);
                    $('#updateLastName').val(response.editUserData.last_name);
                    $('#updateinputEmail').val(response.editUserData.email);
                    $('#updateinputStatus').val(response.editUserData.status);
                    $('#update_user_role option[value="' + response.editUserData.user_type + '"]').prop('selected', true);

                    $('#updateuserModal').modal('show');
                    const responses = await fetchUserPermissions(id);

                    await renderPermissions(responses.user, responses.permissions);
                    if ($('#update_user_role').val() == 2) {
                        $('input[type="checkbox"]').parent().hide();
                        $('input[type="checkbox"][value="4"]').parent().show();
                    }                    
                }
            } catch (error) {
                $('#errorMessage').text(error.responseJSON.error);
                $('#errorMessage').css('display', 'block');
                setTimeout(function() {
                    $('#errorMessage').fadeOut();
                }, 3000);
            }
        });

        //close model on close 
        var updateuserModal = $('#updateuserModal'),
        closeButton = updateuserModal.find('.close');
        closeButton.on('click', function () {
            updateuserModal.modal('hide');
        });

        // updateform data on submit 
        $('#updateuserModal').on('submit',function(e){ 
            e.preventDefault();
            var formData = new FormData($('#update_user')[0]);
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
                        success:function(data){
                            $('#user_del_success').html('');
                            $('#user_del_success').css('display','block');
                            $('#user_del_success').append('<div class="alert alert-success alert-dismissible fade show m-3" role="alert"> User Delete Successfully! <button type="button" class="close deletemodal" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');  
                        },
                        error:function(error){
                            console.log(error);
                        }
                    });
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