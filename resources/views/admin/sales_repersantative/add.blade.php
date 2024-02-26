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
        <div class="alert alert-success mx-4" id="successMessage" style="display:none;"></div>
        <div class="alert alert-danger mx-4" id="errorMessage" style="display:none;"></div>
        <form class="" id="add_sales" method="post">
            @csrf
            <div class="form-group">
                <label>First Name</label>
                <input type="text" placeholder="Enter First Name" class="form-control" name="first_name" id="first_name" required>
            </div>

            <div class="form-group">
                <label>Last Name</label>
                <input type="text" placeholder="Enter Last name" class="form-control" name="last_name" id="last_name" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="text" placeholder="Enter Email" class="form-control" name="email" id="email" required>
            </div>

            <div class="form-group">
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

            <div class="text-center">
                <button type="submit" class="btn btn-primary mx-auto" id="sales_add">Submit</button>
            </div>
        </form> 
    </div>
</div>
<script>
    //submit form with ajax
    $("#add_sales").on('submit', function (e){
        e.preventDefault();
        var formData = new FormData($('#add_sales')[0]);
        $.ajax({
            type: 'POST',
            url: '{{ route('sales.add') }}', // Replace with your actual route name
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if(response.error){
                    $('#errorMessage').text(response.error);
                    $('#errorMessage').css('display','block');
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
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
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
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
                    // $('#enddate,#file,#importBtn').prop('disabled', true);
                    // Scroll to the top of the window
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                    setTimeout(function () {
                        $('#successMessage').fadeOut();
                        window.location.href = "{{ route('account') }}";
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
                $('html, body').animate({ scrollTop: 0 }, 'slow');
                setTimeout(function () {
                    $('#errorMessage').fadeOut();
                }, 5000);
            }
        });
    });    
</script>

@endsection