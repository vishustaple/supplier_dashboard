@extends('layout.app', ['pageTitleCheck'=> (($currentTitle == 'Sales Team')?'Sales Team':'')])

 @section('content')
    <div id="layoutSidenav">
        @include('layout.sidenavbar', ['pageTitleCheck'=> (($fromTitle == 'SalesTeam')?'Sales Data':'')] )
        <div id="layoutSidenav_content" >
            <h3 class="mb-0 ps-2">Add Account</h3>
            <div class="row align-items-end border-bottom pb-3 mb-4">
                <div class="col-md-12 mb-0 text-end">    
                    <a href="{{ route('sales.index') }}" class="btn btn-secondary border-0 bg_yellow" title="Back"><i class="fas fa-arrow-left me-2"></i>Back</a>
                </div>
            </div>
            <div  id="successMessages"></div>
            <div  id="errorMessage"></div>
            <form class="" id="add_sales" method="post">
                @csrf
                <div class="col-md-12 row">
                    <div class="form-group col-md-6">
                        <label>First Name</label>
                        <input type="text" placeholder="Enter First Name" class="form-control" name="first_name" id="first_name" required>
                    </div>

                    <div class="form-group col-md-6">
                        <label>Last Name</label>
                        <input type="text" placeholder="Enter Last name" class="form-control" name="last_name" id="last_name" required>
                    </div>

                    <div class="form-group col-md-6">
                        <label>Email</label>
                        <input type="text" placeholder="Enter Email" class="form-control" name="email" id="email" required>
                    </div>

                    <div class="form-group col-md-6">
                        <label>Phone Number</label>
                        <input type="tel" placeholder="Enter Phone Number" class="form-control" name="phone_number" pattern="[0-9]{10}" id="phone_number" required>
                    </div>
                    
                    <div class="form-group col-md-6">
                        <label for="selectBox">Status</label>
                        <select id="selectBox" name="status" class="form-control"> 
                            <option value="">--Select--</option>
                            <option value="1">Active</option>
                            <option value="0">In-Active</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="selectBox">Team User Type</label>
                        <select id="selectBox" name="user_type" class="form-control"> 
                            <option value="">--Select--</option>
                            <option value="1">Sales</option>
                            <option value="2">Agent</option>
                            <option value="3">Customer Services</option>
                        </select>
                    </div>
                </div>
                <div class="text-left col-md-6">
                    <button type="submit" class="btn btn-primary mx-auto" id="sales_add">Submit</button>
                </div>
            </form> 
        </div>
    </div>
    <script>
        $("#add_sales").on('submit', function (e){
            e.preventDefault();
            $.ajax({
                type: 'POST',
                url: '{{ route("sales.add") }}', // Replace with your actual route name
                data: new FormData($('#add_sales')[0]),
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

                        $('#errorMessage').append('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+errorMessage+'<button type="button" class="close" data-dismiss="alert" aria-label="Close" id="closeerrorMessage"><span aria-hidden="true">&times;</span></button></div>');
                        $('#closeerrorMessage').on('click', function() {
                            location.reload();
                        });
                        $('html, body').animate({ scrollTop: 0 }, 'slow');
                    }                
                    
                    if(response.success){
                        $('#page-loader').hide();
                        $('#successMessages').append('<div class="alert alert-success alert-dismissible fade show m-2" role="alert">'+response.success+'<button type="button" class="close" data-dismiss="alert" aria-label="Close" id="closesuccessMessage"><span aria-hidden="true">&times;</span></button></div>');
                        $('#closesuccessMessage').on('click', function() {
                            location.reload();
                        });
                        $("form")[0].reset();
                    }
                },
                error: function(xhr, status, error) {
                    // Handle error response
                    const errorresponse = JSON.parse(xhr.responseText);
                    $('#errorMessage').text(errorresponse.error);
                    $('#errorMessage').css('display','block');
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                    setTimeout(function () {
                        $('#errorMessage').fadeOut();
                    }, 5000);
                }
            });
        });    
    </script>
@endsection