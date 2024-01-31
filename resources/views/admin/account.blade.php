<!-- resources/views/excel-import.blade.php -->


@extends('layout.app')
 @extends('layout.sidenav')
 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar')
    <div id="layoutSidenav_content">
        <div class="mx-auto py-4 d-flex justify-content-between align-items-center">
         <h2 class="mb-0">Accounts Data</h2>
                    <!-- Button trigger modal -->
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
                    Add Account
                    </button>

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
<!-- 
                                    <div class="form-check form-check-inline">
                                    <input type="checkbox" id="grandparent" class="form-check-input radio-checkbox" name="grandparent" value="0">
                                    <label class="form-check-label" for="grandparent">GrandParent</label>
                                    </div> -->
                                </div>
                                <div class="form-group">
                                    <label for="selectBox">Grand Parent:</label>
                                    <select id="grandparentSelect" name="grandparentSelect" class="form-control" disabled> 
                                        <option value="" selected>--Select--</option>
                                        <option value="test value">test value</option>
                                        @if(!empty($grandparent))
                                            @foreach($grandparent as $gp)
                                                <option value="{{$gp->id}}">{{$gp->customer_name}}</option>
                                            @endforeach
                                        @endif
                                    </select>
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
        <div class="container">
         
            <table id="account_data" class="data_table_files">
            <!-- Your table content goes here -->
            </table>
        </div>
        
    </div>
</div>
<script>
    $(document).ready(function() {
        $('#account_data').DataTable({
            "paging": true,   // Enable pagination
            "ordering": true, // Enable sorting
            "searching": true, // Enable search
            "pageLength": 40,
            "lengthChange":false,
            "data": <?php if(isset($accountsdata)){echo $accountsdata;}  ?>,
            "columns": [
                { title: 'SR. No' },
                { title: 'Account Name' },
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
                if($(this).attr('id') == "parent"){
                    $('#grandparentSelect').prop('disabled', false);
                }
                else{
                    $('#grandparentSelect').prop('disabled', true);
                }
                console.log($(this).attr('id') + ' is checked');
            } else{
                $('#grandparentSelect').prop('disabled', true);
            }
        });

        //submit form with ajax

        $("#add_supplier").on('submit', function (e){
            // alert("here");

        e.preventDefault();
        var formData = new FormData($('#add_supplier')[0]);
        console.log(formData);
        $.ajax({
                type: 'POST',
                url: '{{ route('account') }}', // Replace with your actual route name
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
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
                    }

                    // Set the content of the div with all accumulated error messages
                   
                    // if(response.error){
                   
                    //     $('#errorMessage').text(response.error);
                    //     $('#errorMessage').css('display','block');
                    //     setTimeout(function () {
                    //     $('#errorMessage').fadeOut();
                    //     }, 5000);
                      
                    // }
                    if(response.success){
                        $('#page-loader').hide();
                        $('#successMessage').text(response.success);
                        $('#successMessage').css('display','block');
                        $("form")[0].reset();
                        //disable all field 
                        $('#enddate,#file,#importBtn').prop('disabled', true);
                        setTimeout(function () {
                        $('#successMessage').fadeOut();
                        }, 5000); 
                    }
                    // Handle success response
                    console.log(response);
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