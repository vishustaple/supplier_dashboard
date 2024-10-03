@extends('layout.app', ['pageTitleCheck' => $pageTitle])
@section('content')
    <div id="layoutSidenav">
        @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
        <div id="layoutSidenav_content" >
            <h3 class="mb-0 ps-3">Edit Customer Name</h3>
            <div class="row align-items-end border-bottom pb-3 mb-4">
                <div class="col-md-12 mb-0 text-end">
                    <a href="{{ route('account') }}"  class="btn btn-primary border-0 bg_yellow" title="Back"><i class="fas fa-arrow-left me-2"></i>Back</a>
                </div>
            </div>
            <div class="alert alert-success mx-4" id="successMessage" style="display:none;"></div>
            <div class="alert alert-danger mx-4" id="errorMessage" style="display:none;"></div>
            <div class="px-4">
                <div class="edit_table container">
                    <table class="dataTable no-footer">
                        <thead>
                            <tr>
                                <th>Supplier Customer Number</th>
                                <th>Customer Name</th>
                                <th>Account Name</th>
                                <th>Supplier</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($missingAccount->isEmpty())
                            <tr><td colspan="5"  class="text-center">No Data Available.</td></tr>
                            @else
                            @foreach($missingAccount as $missingarray)
                                <tr>
                                    <td>{{$missingarray->account_number}}</td>
                                    <td>{{$missingarray->customer_name}}</td>
                                    <td class="missing_value"><input class="form-control" type="text" placeholder="Account Name" value=""></td>
                                    <td>{{getSupplierName($missingarray->supplier_id)}}</td>
                                    <td><button type="button" class="btn btn-primary missing_save" data-id="{{$missingarray->id}}">Save</button></td>
                                </tr>
                            @endforeach
                            @endif
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
                var id = $(this).data('id'),
                ColumnValue = $(this).closest('tr').find('.missing_value input'),
                inputValue = ColumnValue.val();

                inputValue = htmlspecialchars(inputValue); 

                ColumnValue.closest('.missing_value').find('.error-message').remove();

                if (inputValue.length <= 0) {
                    ColumnValue.after('<div class="error-message empty-value mt-2 alert alert-danger">Customer name cannot be blank.</div>');
                        
                } else {
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
                            ColumnValue.val("");
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