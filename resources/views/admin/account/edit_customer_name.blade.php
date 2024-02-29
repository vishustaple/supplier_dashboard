@extends('layout.app', ['pageTitleCheck' => 'Accounts Data'])

 @section('content')
    <div id="layoutSidenav">
        @include('layout.sidenavbar', ['pageTitleCheck' => 'Accounts Data'])
        <div id="layoutSidenav_content" >
        <h3 class="mb-0 ps-3">Edit Customer Name</h3>

        <div class="alert alert-success" id="successMessage" style="display:none;"></div>
        <div class="alert alert-danger" id="errorMessage" style="display:none;"></div>
 
        <div class="py-5">
            <div class="edit_table container">
        <table class="dataTable no-footer">
            <thead>
                <tr>
                <th>Customer Number</th>
                <th>Customer Name</th>
                <th>Account Name</th>
                <th>Supplier</th>
                <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($missingAccount as $missingarray)
                <tr>
                    <td>{{$missingarray->customer_number}}</td>
                    <td class="missing_value"><input class="form-control" type="text" placeholder="Customer Name" value=""></td>
                    <td>{{$missingarray->account_name}}</td>
                    <td>{{getSupplierName($missingarray->category_supplier)}}</td>
                    <td><button type="button" class="btn btn-primary missing_save" data-id="{{$missingarray->id}}">Save</button></td>
                </tr>
                @endforeach
            </tbody>
        </table>
            </div>
            </div>

            </div>
        </div>
    <script>
     $(document).ready(function() {
        function htmlspecialchars(str) {
            var elem = document.createElement('div');
            elem.innerText = str;
            return elem.innerHTML;
        }
        $('#page-loader').hide();
        $('.missing_save').click(function(){
        $('#page-loader').show();
        var id = $(this).data('id');
        var ColumnValue = $(this).closest('tr').find('.missing_value input');
        var inputValue = ColumnValue.val();
        inputValue = htmlspecialchars(inputValue); 
        console.log(inputValue.length);
        ColumnValue.closest('.missing_value').find('.error-message').remove();
        if (inputValue.length <= 0) {
            ColumnValue.after('<div class="error-message empty-value mt-2 alert alert-danger">Customer name cannot be blank.</div>');
                
        }
        else{
          
             $.ajax({
                     type: 'POST',
                     url: '{{ route("account.missing") }}', 
                     headers: {
                     'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                     },
                     data: {id:id, ColumnValue:inputValue},
                     success: function(response) {
                         console.log(response);
                         $('#successMessage').text(response.success);
                         $('#successMessage').css('display','block');
                        //  $('#successMessage').append('<button type="button" id="closeSuccessMessage" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>');
                        //  $('#closeSuccessMessage').on('click', function() {
                        //     location.reload();
                        // });
                         setTimeout(() => {
                            location.reload();
                         }, 3000);
 
                     },
                     error: function(xhr, status, error) {
                         console.log(error);
                     }
                 });
         }       

        });
    });
 </script>
 @endsection