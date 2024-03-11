@extends('layout.app', ['pageTitleCheck'=> (($currentTitle == 'Edit sales Data')?'Edit Account Data':'')])
 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck'=> (($fromTitle == 'account')?'Accounts Data':'')] )
    <div id="layoutSidenav_content" >
        <h3 class="mb-0 ps-2">Edit Data</h3>
        <div class="row align-items-end border-bottom pb-3 mb-4">
            <div class="col-md-12 mb-0 text-end">
                <a href="{{ route('sales.index') }}"  class="btn btn-primary border-0 bg_yellow" title="Back"><i class="fas fa-arrow-left me-2"></i>Back</a>
            </div>
        </div>
        <div  id="editsuccessMessage" >
        </div>
        <div  id="editerrorMessage" >
        </div>
        <div class="edit_wrapper_block ps-3 m-3">
            <div class="card shadow border-0 p-5">
        <form class="" id="edit_sales">
            @csrf
            <input type="hidden" value="{{$sales->id}}" name="id" id="id">
            <div class="row">
                <div class="form-group col-md-6">
                    <label>First Name</label>
                    <input type="text" placeholder="Enter First Name" class="form-control" value="{{$sales->first_name}}" name="first_name" id="first_name" required>
                </div>
                <div class="form-group col-md-6">
                    <label>Last Name</label>
                    <input type="text" placeholder="Enter Last name" class="form-control" value="{{$sales->last_name}}" name="last_name" id="last_name" required>
                </div>

                <div class="form-group col-md-6">
                    <label>Email</label>
                    <input type="text" placeholder="Enter Email" class="form-control" value="{{$sales->email}}" name="email" id="email" required>
                </div>

                <div class="form-group col-md-6">
                    <label>Phone Number</label>
                    <input type="tel" placeholder="Enter Phone Number" class="form-control" value="{{$sales->phone}}" name="phone_number" pattern="[0-9]{10}" id="phone_number" required>
                </div>

                <div class="form-group col-md-6">
                    <label for="selectBox">Status</label>
                    <select id="selectBox" name="status" class="form-control"> 
                        <option value="">--Select--</option>
                        <?php 
                            if($sales->status == 1){ 
                                $selectedActive = "selected";
                                $selectedInActive = "";
                            } else { 
                                $selectedActive = "";
                                $selectedInActive = "selected";
                            }
                        ?>
                        <option value="1" {{$selectedActive}}>Active</option>
                        <option value="0" {{$selectedInActive}}>In-Active</option>
                    </select>
                </div>
                 
                <div class="form-group col-md-6">
                    <label for="selectBox">Team User Type</label>
                    <select id="selectBox" name="user_type" class="form-control"> 
                    <option value="">--Select--</option>
                    <option value="1" {{ $sales->team_user_type == 1 ? 'selected' : '' }}>Sales</option>
                    <option value="2" {{ $sales->team_user_type == 2 ? 'selected' : '' }}>Agent</option>
                    <option value="3" {{ $sales->team_user_type == 3 ? 'selected' : '' }}>Customer Services</option>
                    </select>
                </div>

            </div>

            <div class="row pt-3">
            <div class="text-center col-md-12">
                <button type="submit" class="btn btn-primary mx-auto" id="sales_edits">Submit</button>
            </div>    
            </div>

        </form>
        </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('#edit_sales').on('submit',function(e){ 
        e.preventDefault();
        var formData = new FormData($('#edit_sales')[0]);
        console.log(formData);
        $.ajax({
            type: 'POST',
            url: '{{ route("sales.update") }}',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log(response);
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
                    $('#editerrorMessage').html('');
                    $('#editerrorMessage').append('<div class="alert alert-danger m-2 alert-dismissible fade show" role="alert">'+errorMessage+'<button type="button" class="close" data-dismiss="alert" aria-label="Close" id="closeerrorMessage"><span aria-hidden="true">&times;</span></button></div>');
                    $('#closeerrorMessage').on('click', function() {
                                location.reload();
                            });
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                   
                }

                if(response.success){
                    $('#page-loader').hide();
                    $('#editsuccessMessage').html('');
                    $('#editsuccessMessage').append('<div class="alert alert-success m-2 alert-dismissible fade show" role="alert">'+response.success+'<button type="button" class="close" data-dismiss="alert" aria-label="Close" id="closesuccessMessage"><span aria-hidden="true">&times;</span></button></div>');
                    $('#closesuccessMessage').on('click', function() {
                                location.reload();
                            });
                    $("form")[0].reset();
                }
            },
            error:function(xhr, status, error) {
                const errorresponse = JSON.parse(xhr.responseText);
                $('#errorMessage').text(errorresponse.error);
                $('#errorMessage').css('display','block');
                $('html, body').animate({ scrollTop: 0 }, 'slow');
                setTimeout(function () {
                    $('#errorMessage').fadeOut();
                }, 3000);
            }
        });
    });
});   
</script>

@endsection