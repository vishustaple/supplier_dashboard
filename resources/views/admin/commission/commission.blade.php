@extends('layout.app', ['pageTitleCheck' => 'Accounts Data'])

 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => 'Accounts Data'])
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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
                <td><select class="mySelectCustomerName" name="customer[]"><option value="">Select</option></select></td>
                <td><select class="mySelectSupplier" name="supplier[]"><option value="">Select</option></select></td>
                <td><select class="mySelectAccount" name="account_name[]"><option value="">Select</option></select></td>
                <td><input type="text" class="form-control form-control-sm" name="commission[]" id="" aria-describedby="helpId" placeholder="" /></td>
                <td><input type="text" name="date[]" class="dateRangePicker form-control" placeholder="Select Date Range"></td>
                <td><button type="button" name="" id="add_commission" class="btn btn-success" ><i class="fa-solid fa-plus"></i></button></td>
            </tr>
        </tbody>
        </table>
    </div>
</div>
</div>
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

    #commission_table .dateRangePicker.form-control {
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
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
<script>
   
$(document).ready(function() {    
    function selectCustomer () {
        $('.mySelectCustomerName').select2({
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

    function selectSupplier(customerNumber=''){
        $('.mySelectSupplier').select2({
            ajax: {
                url: "{{ route('commission.supplierSearch') }}",
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term, // search term
                        customer_number: customerNumber
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
    
    function selectAccount(customerNumber='', supplierNumber=''){
        $('.mySelectAccount').select2({
            ajax: {
                url: "{{ route('commission.supplierSearch') }}",
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term, // search term
                        customer_number: customerNumber,
                        supplier_number: supplierNumber,
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

    function setDate(){
        $('.dateRangePicker').daterangepicker({  
            showDropdowns: false,
            linkedCalendars: false,
        });
    }

    setDate();
    selectCustomer();
    selectSupplier();
    selectAccount();

    $('#add_commission').on('click', function(){
        $('#commission_table').append('<tr><td><select  class="mySelectCustomerName" name="customer[]"><option value="">Select</option></select></td><td><select  class="mySelectSupplier" name="supplier[]"><option value="">Select</option></select></td><td><select class="mySelectAccount" name="account_name[]"><option value="">Select</option></select></td><td><input type="text" class="form-control form-control-sm" name="commission[]" id="" aria-describedby="helpId" placeholder="" /></td><td><input type="text" name="date[]" class="dateRangePicker form-control" placeholder="Select Date Range"></td><td><button type="button" class="removeRowBtn btn btn-danger"><i class="fa-solid fa-xmark"></i></button></td></tr>');

        setDate();
        selectCustomer();
        selectSupplier();
        selectAccount();
    });
    
    $('#commission_table').on('click', '.removeRowBtn', function() {
        $(this).closest('tr').remove();
    });

    $('.mySelectCustomerName').change(function() {
        var row = $(this).closest('tr'),
        customerValue = row.find('.mySelectCustomerName').val();
        
        if (customerValue !== '') {
            selectSupplier(customerValue);
        } else {
            console.log("Please select both customer and supplier");
        }
    });

    $('.mySelectSupplier').change(function() {
        var row = $(this).closest('tr'),
        // accountNameInput = row.find('input[name="account_name[]"]'),
        customerValue = row.find('.mySelectCustomerName').val(),
        supplierValue = row.find('.mySelectSupplier').val();

        if (customerValue !== '' && supplierValue !== '') {
            selectAccount(customerValue, supplierValue);
        } else {
            console.log("Please select both customer and supplier");
        }
    });
});
</script>

@endsection