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
        #commission_table tbody tr td:nth-of-type(2) input{
            width: 100%;
        }
        #commission_table tbody tr td:nth-of-type(3) {
            width: 150px;
        }

        #commission_table tbody tr td:nth-of-type(4) {
            width: 140px;
        }
        #commission_table tbody tr td select{
            background-color: transparent;
            border-color: #ccc;
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
        #commission_table tbody tr td:nth-of-type(6) #add_commission,
        #commission_table tbody  button.btn.btn-primary.check.save,
        #commission_table tbody  button.removeRowBtn.btn.btn-danger,
        #commission_table tbody button.btn.btn-primary.save.check2{
            height: 31px;
            display: flex;
            align-content: center;
            justify-content: center;
            line-height: normal;
            padding: 5px 10px;
            font-weight: 500;
        }
        #commission_table tbody  button.removeRowBtn.btn.btn-danger{
            min-width: 36px;
        }
        #commission_table tbody tr td:nth-of-type(6) button + button{
            margin-left:5px;
        }
      
    </style>
    
    <div id="layoutSidenav_content" >
        <h3 class="mb-0 ps-2">Manage Commission</h3>
        <div class="row align-items-end border-bottom pb-3 pe-3 mb-4">
            <div class="col-md-12 mb-0 text-end">
                <!-- Button trigger modal -->
                <a href="{{ route('commission.list', ['commissionType' => 'commission_listing'])}}" class="btn btn-secondary border-0 bg_yellow" title="Back"><i class="fas fa-arrow-left me-2"></i>Back</a>
                </div>
        </div> 
        <div class="container">
        <div class="" id="successMessages">
        </div>

        <div class="" id="errorMessage">    
        </div>
        <!-- <form id="commission_form" method="POST"> -->
        <table class="table" id="commission_table">
        <thead>
            <tr>
                <th scope="col">Account Name</th>
                <th scope="col">Supplier</th>
                <th scope="col">Sales Rep</th>
                <th scope="col">Commission %</th>
                <th scope="col">Start/End Date</th>
                <th scope="col">Action</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <form id="commission_form" >
                <td><input type="hidden" class="count" value="1"><select class="mySelectAccountName form-control-sm" name="account_name[]"><option value="">Select</option></select></td>
                <td><input type="hidden" class="supplier_id" name="supplier[]" value=""><input type="text" class="mySelectSupplier form-control-sm" disabled name="suppliers[]" value="" required>
                </td>
                <td>
                    <select id="selectBox" name="sales_rep[]" class="mySelectSalesRep form-control-sm"> 
                            <option value="" selected>Select</option>
                            @if(isset($salesRepersantative))
                            @foreach($salesRepersantative as $salesRep)
                            <option value="{{ $salesRep->id }}">{{ $salesRep->first_name ." ". $salesRep->last_name}}</option>
                            @endforeach
                            @endif
                    </select>
                </td>
                <td><input type="text" class="form-control form-control-sm commission" name="commission[]" id="" aria-describedby="helpId" placeholder="" /></td>
                <td><input type="text" name="date[]" class="dateRangePickers dateRangePicker form-control" placeholder="Select Date Range" readonly="readonly" ></td>
                <td>
                    <div class="d-flex">
                        <button type="button" id="add_commission" class="btn btn-success"><i class="fa-solid fa-plus"></i></button>
                        <button type="button"  class="btn btn-primary check save">Submit</button>
                    </div>
                </td>
            </form>
            </tr>
        </tbody>
        </table>
    </div>
</div>
</div>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
<script>
   

    $(document).ready(function() {
        
        function saveData(count=''){
            var accountName = $('.mySelectAccountName'+count+'').find('option:selected').text(),
            supplierName = $('.mySelectSupplier'+count+'').val(),
            sales = $('.mySelectSalesRep'+count+'').find('option:selected').text(),
            date = $('.dateRangePicker'+count+'').val().replace(" - ", " to "),
            commission = $('.commission'+count+'').val();

            if (accountName == 'Select') {
                $('#errorMessage').append('<div class="alert alert-danger alert-dismissible fade show" role="alert">Please select account name. <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                return;
            }

            if (supplierName == '') {
                $('#errorMessage').append('<div class="alert alert-danger alert-dismissible fade show" role="alert">Please select supplier. <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                return;
            }

            if (sales == 'Select') {
                $('#errorMessage').append('<div class="alert alert-danger alert-dismissible fade show" role="alert">Please select sales repersentive <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                return;
            }
            
            if (commission == '') {
                $('#errorMessage').append('<div class="alert alert-danger alert-dismissible fade show" role="alert">Please add commission. <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                return;
            }
            swal.fire({
                title: "Oops....",
                text: "Please confirm " + accountName + " will receive commissions for the " + supplierName + " Charles River account (" + sales + ") between " + date + " ?",
                icon: "error",
                showCancelButton: true,
                confirmButtonText: 'Accept',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {

                    // var formData = new FormData($('#commission_form'+count+'')[0]);
                    console.log('#commission_form'+count+'');
                    var formData = {
                        'sales_rep': $('.mySelectSalesRep'+count+'').val(),
                        'supplier': $('.supplier_id'+count+'').val(),
                        'account_name': $('.mySelectAccountName'+count+'').val(),
                        'commission': commission,
                        'date': $('.dateRangePicker'+count+'').val(),
                    };
                    console.log("FormData:-",formData);
                    var token = "{{ csrf_token() }}";
                    $.ajax({
                        type: 'POST',
                        url: "{{route('commission.add')}}",
                        dataType: 'json',
                        data: JSON.stringify(formData),                        
                        headers: {'X-CSRF-TOKEN': token},
                        contentType: 'application/json',                     
                        processData: false,
                        
                       
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
                                // $('.check'+count+'').prop('disabled', true);
                                $('#successMessages').append('<div class="alert alert-success alert-dismissible fade show" role="alert">'+response.success+'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                                $("form")[0].reset();
                                $('.mySelectAccountName'+count+'').empty();
                                // $('#mySelect').val(null).trigger('change');
                                // $("#select2_example").empty();
                                // window.location.href = "{{ route('commission.list', ['commissionType' => 'commission_listing']) }}";
                            }
                        },
                        error: function(xhr, status, error) {
                            // Handle error response
                            console.error(xhr.responseText);
                        }
                    });

                } 
            });
        }

        function selectCustomer (count='') {
            $('.mySelectAccountName'+count+'').select2({
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
            }).on('select2:select', function (e) {
                var customerId = e.params.data.id; // Selected account_name ID
                // Perform your AJAX request here using the selected account_name ID
                $.ajax({
                    url: "{{ route('commission.supplierSearch') }}",
                    method: 'GET',
                    data: {
                        customer_number: customerId
                    },
                    success: function(response) {
                        // Handle the AJAX response
                        $('.mySelectSupplier'+count+'').val(response[0].supplier);
                        $('.supplier_id'+count+'').val(response[0].id);
                    },
                    error: function(xhr, status, error) {
                        // Handle errors
                        console.error(error);
                    }
                });
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
        $('#add_commission').on('click', function(){
            // Your existing code to append rows to the table
            var count = $('#commission_table tbody tr').length + 1;

            $('#commission_table').append('<tr><form id="commission_form'+count+'"><td><input type="hidden" class="count" value="'+count+'"><select class="mySelectAccountName'+count+'" name="account_name[]"><option value="">Select</option></select></td><td><input class="supplier_id'+count+'" type="hidden" name="supplier[]" value=""><input type="text" required class="mySelectSupplier'+count+' form-control-sm" disabled name="suppliers[]" value=""></td><td><select id="selectBox" name="sales_rep[]" class="mySelectSalesRep'+count+' form-control-sm"><option value="" selected>Select</option>@if(isset($salesRepersantative))@foreach($salesRepersantative as $salesRep)<option value="{{ $salesRep->id }}">{{ $salesRep->first_name ." ". $salesRep->last_name }}</option> @endforeach @endif </select></td><td><input type="text" class="form-control form-control-sm commission'+count+'" name="commission[]" id="" aria-describedby="helpId" placeholder="" /></td><td><input type="text" name="date[]" readonly="readonly" class="dateRangePickers dateRangePicker'+count+' form-control" placeholder="Select Date Range"></td><td><div class="d-flex"><button type="button" class="removeRowBtn btn btn-danger"><i class="fa-solid fa-xmark"></i></button><button type="button" value="'+count+'" class="btn btn-primary save check'+count+'">Submit</button></div></td></form></tr>');
            setDate(count);
            selectCustomer(count);
        });
        
        $('#commission_table').on('click', '.removeRowBtn', function() {
            $(this).closest('tr').remove();
        });

        // Add a submit event listener to the table
        $("body").on("click", ".save", function() {
            var currentCount = $(this).val(); // Get the current count value
            // console.log('Form submitted for count:', currentCount);
            saveData(currentCount); // Call the saveData function with the current count value
        });
       
    });
</script>

@endsection