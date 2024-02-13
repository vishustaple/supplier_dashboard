@extends('layout.app', ['pageTitleCheck'=> (($currentTitle == 'Edit Account Data')?'Edit Accounts Data':'')])

 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck'=> (($fromTitle == 'account')?'Accounts Data':'')] )
    <div id="layoutSidenav_content" >
        <h3 class="mb-0 ps-2">Add Account</h3>
        <div class="row align-items-end border-bottom pb-3 mb-4">
            <div class="col-md-12 mb-0 text-end">
                
                <a href="{{ route('account') }}" class="btn btn-secondary border-0 bg_yellow" title="Back"><i class="fas fa-arrow-left me-2"></i>Back</a>
            </div>
        </div>
            <div class="alert alert-success mx-4" id="successMessage" style="display:none;">
            </div>
            <div class="alert alert-danger mx-4" id="errorMessage" style="display:none;">
            </div>
            <form class="px-4" id="add_supplier" method="post">
                @csrf
                <div class="row">
                <div class="form-group col-md-6">
                    <label>Customer ID </label>
                    <input type="text" placeholder="Enter Customer Id" class="form-control" name="customer_id" id="customer_id">
                </div> 
                <div class="form-group col-md-6">
                    <label>Customer Name</label>
                    <input type="text" placeholder="Enter Customer name" class="form-control" name="customer_name" id="customer_name">
                </div>
                <!-- <div class="form-group col-md-6">
                   
                </div> -->
                <div class="form-group col-md-6">
                <div class="form-check form-check-inline">
                    <input type="checkbox" id="parent" class="form-check-input radio-checkbox" name="parent" value="1">
                    <label class="form-check-label" for="parent">Parent</label>
                    </div>
                    <label for="selectBox" class="d-block">Grand Parent:</label>
                    <select id="grandparentSelect" name="grandparentSelect" class="form-control" disabled> 
                        <option value="" selected>--Select--</option>
                        @if(!empty($grandparent))
                            @foreach($grandparent as $gp)
                            <option value="{{ $gp->id }}">{{ $gp->alies }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="form-group col-md-6 mt-4">
                    <label>Account Name</label>
                    <input type="text" placeholder="Enter Account Name" class="form-control" name="account_name">
                </div>
                <div class="form-group col-md-6">
                    <label>Volume Rebate</label>
                    <input type="text" placeholder="Enter Volume Rebate" class="form-control" name="volume_rebate">
                </div>
                <div class="form-group col-md-6">
                    <label>Sales Representative</label>
                    <input type="text" placeholder="Enter Sales Representative" class="form-control" name="sales_representative">
                </div>
                <div class="form-group col-md-6">
                    <label>Customer Service Representative</label>
                    <input type="text" placeholder="Enter Customer Service Representative" class="form-control" name="customer_service_representative">
                </div>
                <div class="form-group col-md-6">
                    <label>Member Rebate</label>
                    <input type="text" placeholder="Enter Member Rebate" class="form-control" name="member_rebate">
                </div>
                <div class="form-group col-md-6">
                    <label>Temporary Active Date</label>
                    <input type="date" class="form-control" name="temp_active_date">
                </div>
                <div class="form-group col-md-6">
                    <label>Temporary End Date</label>
                    <input type="date" class="form-control" name="temp_end_date">
                </div>
                <div class="form-group col-md-6">
                    <label>Internal Reporting Name</label>
                    <input type="text" placeholder="Enter Internal Reporting Name" class="form-control" name="internal_reporting_name">
                </div>
                <div class="form-group col-md-6">
                    <label>QBR</label>
                    <input type="text" placeholder="Enter QBR" class="form-control" name="qbr">
                </div>
                <div class="form-group col-md-6">
                    <label>Spend Name</label>
                    <input type="text" placeholder="Enter Spend Name" class="form-control"          name="spend_name">
                </div>
                <div class="form-group col-md-6">
                    <label>Supplier Account Representative</label>
                    <input type="text" placeholder="Enter Supplier Account Representative" class="form-control" name="supplier_acct_rep">
                </div>
                <div class="form-group col-md-6">
                    <label>Management Fee</label>
                    <input type="text" placeholder="Enter Management Fee" class="form-control" name="management_fee">
                </div>
                <div class="form-group col-md-6">
                    <label>Record Type</label>
                    <input type="text" placeholder="Enter Record Type" class="form-control" name="record_type">
                </div>
                <div class="form-group col-md-6">
                    <label>Category Supplier</label>
                    <input type="text" placeholder="Enter Category Supplier" class="form-control" name="category_supplier">
                </div>
                <div class="form-group col-md-6">
                    <label>CPG Sales Representative</label>
                    <input type="text" placeholder="Enter CPG Sales Representative" class="form-control" name="cpg_sales_representative">
                </div>
                <div class="form-group col-md-6">
                    <label>CPG Customer Service Representative</label>
                    <input type="text" placeholder="Enter CPG Customer Service Representative" class="form-control" name="cpg_customer_service_rep">
                </div>
                <div class="form-group col-md-6">
                    <label>SF Cat</label>
                    <input type="text" placeholder="Enter SF Cat" class="form-control" name="sf_cat">
                </div>
                <div class="form-group col-md-6">
                    <label>Rebate Frequency</label>
                    <input type="text" placeholder="Enter Rebate Frequency" class="form-control" name="rebate_freq">
                </div>
                <div class="form-group col-md-6">
                    <label>Commission Rate</label>
                    <input type="text" placeholder="Enter Commission Rate" class="form-control" name="comm_rate">
                </div>
                </div>
                <div class="text-center py-3">
                <button type="submit" class="btn btn-primary mx-auto" id="supplier_add">Submit</button>
                </div>

            </form>
        
    </div>
</div>
<script>
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