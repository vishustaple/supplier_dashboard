@extends('layout.app', ['pageTitleCheck' => 'Commission'])

 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => 'Commission'])
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        #commission_table tbody tr td:first-child {
            width: 280px;
            min-width: 280px;
            max-width: 280px;
        } 

        #commission_table tbody tr td:nth-of-type(2) {
            width: 170px;
            min-width: 170px;
            max-width: 170px;
        } 

        #commission_table tbody tr td:nth-of-type(3) {
            width: 150px;
        }

        #commission_table tbody tr td:nth-of-type(4) {
            width: 130px;
        }

        #commission_table tbody tr td:nth-of-type(5) {
            width: 176px;
        }

        #commission_table tbody tr td:nth-of-type(6) {
            width: 50px;
        }

        #commission_table .select2.select2-container.select2-container--default {
            width: 100% !important;
            padding: 0px;
        }

        #commission_table .select2.select2-container.select2-container--default .select2-selection.select2-selection--single {
            height: 31px;
            padding: 2px;
        }

        #commission_table .dateRangePickers.form-control {
            font-size: 13px;
            height: 31px;
        }

        #commission_table td{
            padding:.50rem;
        }
        #commission_table tbody tr td:nth-of-type(6) #add_commission {
            height: 31px;
            display: flex;
            align-content: center;
            justify-content: center;
            line-height: normal;
            padding: 5px 10px;
            font-weight: 500;
        }
    </style>
    <div id="layoutSidenav_content" >
        <h3 class="mb-0 ps-2">Manage Commission</h3>
        <div class="row align-items-end border-bottom pb-3 pe-3 mb-4">
            <div class="col-md-12 mb-0 text-end">
                <!-- Button trigger modal -->
                <!-- <a href="{{ route('account.create')}}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Account</a> -->
                </div>
        </div> 
        <div class="container">
        <div class="" id="successMessages">
        </div>

        <div class="" id="errorMessage">    
        </div>
        <form id="commission_form" method="POST">
        <table class="table" id="commission_table">
        <thead>
            <tr>
                <th scope="col">Customer Name</th>
                <th scope="col">Supplier</th>
                <th scope="col">Account Number</th>
                <th scope="col">Commission %</th>
                <th scope="col">Start/End Date</th>
                <th scope="col">Action</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><input type="hidden" class="count" value="1"><select class="mySelectCustomerName" name="customer[]"><option value="">Select</option></select></td>
                <td><select class="mySelectSupplier" name="supplier[]"><option value="">Select</option></select></td>
                <td><select class="mySelectAccount" name="account_name[]"><option value="">Select</option></select></td>
                <td><input type="text" class="form-control form-control-sm" name="commission[]" id="" aria-describedby="helpId" placeholder="" /></td>
                <td><input type="text" name="date[]" class="dateRangePickers dateRangePicker form-control" placeholder="Select Date Range"></td>
                <td><button type="button" name="" id="add_commission" class="btn btn-success" ><i class="fa-solid fa-plus"></i></button></td>
            </tr>
        </tbody>
        </table>
        <button type="submit" class="btn btn-success">Submit</button>
        </form>
    </div>
</div>
</div>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
<script>
    $(document).ready(function() {  
        $('#commission_form').on('submit', function() {
            event.preventDefault();
            var formData = new FormData($('#commission_form')[0]);
            $.ajax({
                type: 'POST',
                url: "{{route('commission.add')}}", // Replace with your actual route name
                data: formData,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
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

                        $('#errorMessage').append('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+errorMessage+'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                    }

                    if(response.success){
                        // $('#successMessages').append('<div class="alert alert-success alert-dismissible fade show" role="alert">'+response.success+'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                        // $("form")[0].reset();
                        window.location.href = "{{ route('commission.list', ['commissionType' => 'commission_listing']) }}";
                    }
                },
                error: function(xhr, status, error) {
                    // Handle error response
                    console.error(xhr.responseText);
                }
            });

        });

        function selectCustomer (count='') {
            $('.mySelectCustomerName'+count+'').select2({
                ajax: {
                    url: "{{ route('commission.customerSearch') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term // search term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1
            });
        }

        function selectSupplier(count=''){
            $('.mySelectSupplier'+count+'').select2({
                ajax: {
                    url: "{{ route('commission.supplierSearch') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term, // search term
                            customer_number: $('.mySelectCustomerName'+count+'').val(),
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1
            });
        }
        
        function selectAccount(count=''){
            $('.mySelectAccount'+count+'').select2({
                ajax: {
                    url: "{{ route('commission.supplierSearch') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term, // search term
                            supplier_number: $('.mySelectSupplier'+count+'').val(),
                            customer_number: $('.mySelectCustomerName'+count+'').val(),
                            account: true
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1
            });
        }

        function setDate(count=''){
            $('.dateRangePicker'+count+'').daterangepicker({  
                showDropdowns: false,
                linkedCalendars: false,
            });
        }

        setDate();
        selectCustomer();
        selectSupplier();
        selectAccount();
        $('#add_commission').on('click', function(){
            var count = parseInt($('#commission_table tbody tr:last-child td:first-child .count').val()) + 1;
            $('#commission_table').append('<tr><td><input type="hidden" class="count" value="'+count+'"><select  class="mySelectCustomerName'+count+'" name="customer[]"><option value="">Select</option></select></td><td><select  class="mySelectSupplier'+count+'" name="supplier[]"><option value="">Select</option></select></td><td><select class="mySelectAccount'+count+'" name="account_name[]"><option value="">Select</option></select></td><td><input type="text" class="form-control form-control-sm" name="commission[]" id="" aria-describedby="helpId" placeholder="" /></td><td><input type="text" name="date[]" class="dateRangePickers dateRangePicker'+count+' form-control" placeholder="Select Date Range"></td><td><button type="button" class="removeRowBtn btn btn-danger"><i class="fa-solid fa-xmark"></i></button></td></tr>');

            setDate(count);
            selectCustomer(count);
            selectSupplier(count);
            selectAccount(count);
        });
        
        $('#commission_table').on('click', '.removeRowBtn', function() {
            $(this).closest('tr').remove();
        });
    });
</script>

@endsection