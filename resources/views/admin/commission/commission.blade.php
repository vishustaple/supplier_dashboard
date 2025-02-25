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
                width: 140px;
                min-width: 140px;
                max-width: 140px;
            }
            #commission_table tbody tr td:nth-of-type(2) select{
                  width: 100%;
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
                padding: 10px;
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
                    <a href="{{ route('commissions.list', ['commissionType' => 'commission_listing'])}}" class="btn btn-secondary border-0 bg_yellow" title="Back"><i class="fas fa-arrow-left me-2"></i>Back</a>
                </div>
            </div> 
            <div class="container">
                <div class="" id="successMessages"></div>
                <div class="" id="errorMessage"></div>
                <form id="commission_form" action="route('commissions.add')" method="POST">
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
                                <td>
                                    <input type="hidden" class="count" value="1"><select class="mySelectAccountNames mySelectAccountName form-control-sm" name="account_name[]" required><option value="">Select</option></select>
                                </td>
                                <td>
                                    <select class="mySelectSupplier form-control-sm" name="supplier[]" aria-label="Default select example" required>
                                        <option selected>--select--</option>
                                    </select>
                                </td>
                                <td>
                                    <select id="selectBox" name="sales_rep[]" class="mySelectSalesRep form-control-sm" required> 
                                        <option value="" selected>Select</option>
                                        @if(isset($salesRepersantative))
                                            @foreach($salesRepersantative as $salesRep)
                                                <option value="{{ $salesRep->id }}">{{ $salesRep->first_name ." ". $salesRep->last_name}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm commissions" name="commissions[]" id="" aria-describedby="helpId" placeholder=""  required />
                                </td>
                                <td class="row">
                                    <div class="col-6">
                                        <!-- <label for="startdate">Select Start Date:</label> -->
                                        <input class="form-control startdate" id="start_date" name="start_date[]" placeholder="Enter Your Start Date " >
                                    </div>  
                                    <div class="col-6">
                                        <!-- <label for="enddate">Select End Date:</label> -->
                                        <input class="form-control enddate" id="end_date" name="end_date[]" placeholder="Enter Your End Date " >
                                    </div>
                                    <!-- <input type="text" name="date[]" class="dateRangePickers dateRangePicker form-control" placeholder="Select Date Range" readonly="readonly" required> -->
                                </td>
                                <td>
                                    
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="text-end">
                        <button type="button" id="add_commission" class="btn btn-success"><i class="fa-solid fa-plus"></i></button>
                        <button type="submit"  class="btn btn-primary check save">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
    <script>
        $(document).ready(function() {
            $('#commission_form').on('submit', function(){
                event.preventDefault();
                $.ajax({
                    type: 'POST',
                    url: "{{route('commissions.add')}}",
                    dataType: 'json',
                    data: new FormData($('#commission_form')[0]),
                    headers: {'X-CSRF-TOKEN': "{{ csrf_token() }}"},
                    contentType: false,
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
                            $("form")[0].reset();
                            $('.mySelectAccountNames').empty();
                            $('tr').each(function() {
                                // Add your condition here to determine which row to remove
                                if ($(this).find('.removeRowBtn').length > 0) {
                                    $(this).remove();
                                }
                            });
                            $('#successMessages').append('<div class="alert alert-success alert-dismissible fade show" role="alert">'+response.success+'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Handle error response
                        console.error(xhr.responseText);
                    }
                });
            });

            function selectCustomer (count='') {
                $('.mySelectAccountName'+count+'').select2({
                    ajax: {
                        url: "{{ route('commissions.customerSearch') }}",
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            var data = {
                                all_supplier: true,
                                supplier_check: true,
                                q: params.term, // search term
                            };

                            return data;
                        },
                        processResults: function(data) {
                            return {
                                results: data
                            };
                        },
                        cache: true
                    },
                    placeholder: "Select an account",
                    allowClear: true,
                    minimumInputLength: 1
                }).on('select2:select', function (e) {
                    var accountName = e.params.data.id; // Selected account_name ID
                    // Perform AJAX request here using the selected account_name ID
                    $.ajax({
                        url: "{{ route('commissions.supplierSearch') }}",
                        method: 'GET',
                        data: {
                            check: true,
                            account_name: accountName,
                        },
                        success: function(response) {
                            // Handle the AJAX response
                            // Clear any existing options in the select element
                            $('.mySelectSupplier'+count+'').empty();

                            // Append each object as an option in the select element
                            response.forEach(function(item) {
                                $('.mySelectSupplier'+count+'').append(
                                    $('<option>', {
                                        value: item.id,
                                        text: item.supplier
                                    })
                                );
                            });
                        },
                        error: function(xhr, status, error) {
                            // Handle errors
                            console.error(error);
                        }
                    });
                });
            }

            function setDate(count=''){
                 // Start Date Picker with custom ranges
                $('.startdate'+count+'').daterangepicker({
                    autoApply: true,
                    showDropdowns: true,
                    singleDatePicker: true,
                    locale: {
                        format: 'MM/DD/YYYY'
                    },
                    minYear: moment().subtract(7, 'years').year(),
                    maxYear: moment().add(7, 'years').year(),
                    ranges: {
                        'Last Quarter': [moment().subtract(3, 'month').startOf('quarter'), moment().subtract(3, 'month').endOf('quarter')],
                        'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                        'Last 6 Months': [moment().subtract(6, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                    }
                }, function(start, end, label) {
                    // If a custom range is selected, populate both startDate and endDate
                    if (
                        label === 'Last Year' ||
                        label === 'Last Month' ||
                        label === 'Last Quarter' ||
                        label === 'Last 6 Months'
                    ) {
                        $('.startdate'+count+'').val(start.format('MM/DD/YYYY')); // Set start date
                        $('.enddate'+count+'').val(end.format('MM/DD/YYYY')); // Set end date
                    } else {
                        // If a normal date is picked, only set the startDate
                        $('.startdate'+count+'').val(start.format('MM/DD/YYYY'));
                    }
                });

                // End Date Picker - Simple calendar
                $('.enddate'+count+'').daterangepicker({
                    autoApply: true,
                    showDropdowns: true,
                    singleDatePicker: true,
                    locale: {
                        format: 'MM/DD/YYYY'
                    }
                }, function(start) {
                    $('.enddate'+count+'').val(start.format('MM/DD/YYYY')); // Manually set the selected date for end date
                });
            }

            setDate();
            selectCustomer();

            $('#add_commission').on('click', function(){
                // Your existing code to append rows to the table
                var count = $('#commission_table tbody tr').length + 1;
                $('#commission_table').append('<tr><td><input type="hidden" class="count" value="'+count+'"><select class="mySelectAccountNames mySelectAccountName'+count+'" name="account_name[]" required><option value="">Select</option></select></td><td><select class="mySelectSupplier'+count+' form-control-sm" name="supplier[]" aria-label="Default select example" required><option selected>--select--</option></select></td><td><select id="selectBox" name="sales_rep[]" class="mySelectSalesRep'+count+' form-control-sm" required><option value="" selected>Select</option>@if(isset($salesRepersantative))@foreach($salesRepersantative as $salesRep)<option value="{{ $salesRep->id }}">{{ $salesRep->first_name ." ". $salesRep->last_name }}</option> @endforeach @endif </select></td><td><input type="text" class="form-control form-control-sm commissions'+count+'" name="commissions[]" id="" aria-describedby="helpId" placeholder="" required /></td><td class="row"><div class="col-6"><input class="form-control dateRangePickers startdate'+count+'" required id="start_date" name="start_date[]" placeholder="Enter Your Start Date " ></div> <div class="col-6"><input class="form-control dateRangePickers enddate'+count+'" required id="end_date" name="end_date[]" placeholder="Enter Your End Date " ></div></td><td><div class="d-flex"><button type="button" class="removeRowBtn btn btn-danger"><i class="fa-solid fa-xmark"></i></button></div></td></tr>');

                setDate(count);
                selectCustomer(count);
            });
            
            $('#commission_table').on('click', '.removeRowBtn', function() {
                $(this).closest('tr').remove();
            });       
        });
    </script>

@endsection