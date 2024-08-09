<!-- resources/views/excel-import.blade.php -->


@extends('layout.app', ['pageTitleCheck' => $pageTitle])

 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
    <div id="layoutSidenav_content">
        <div class="container">
            <div class="m-1 mb-2 d-md-flex align-items-center justify-content-between">
                <h3 class="mb-0 ">{{ $pageTitle }}</h3>
            </div>
            <!-- <form  id="import_form"  enctype="multipart/form-data">
                @csrf
                <div class="row align-items-end py-3 border-top border-bottom mb-3">
                    <div class="form-group col-md-4 mb-0">
                        <label for="selectBox">Select Supplier:</label>
                        <select id="selectBox" name="supplierselect" class="form-control"> 
                            <option value="" selected>--Select--</option>
                            @if(isset($categorySuppliers))
                            @foreach($categorySuppliers as $categorySupplier)
                            <option value="{{ $categorySupplier->id }}">{{ $categorySupplier->supplier_name }}</option>
                            @endforeach
                            @endif
                            </select>
                        </div>
                        <div class="form-group relative col-md-4 mb-0">  
                            <label for="enddate">Select Date:</label>
                            <input class="form-control" id="enddate" name="dates" placeholder="Enter Your End Date " >
                        </div>

                    <div class="col-md-4 mb-0">
                    <button type="submit" class="btn btn-primary">Submit</button>
                    </div> -->
                    <!-- Button trigger modal -->
                <!-- </div>
               
            </form> -->
            <table id="accounts_data" class="data_table_files">
                <!-- Your table content goes here -->
            </table>
        </div>
    </div>
</div>
<!-- Include Date Range Picker JavaScript -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
<script>
    $(document).ready(function() {
        $('input[name="dates"]').daterangepicker({
            autoApply: true,
            showDropdowns: true,
            minYear: moment().subtract(7, 'years').year(),
            maxYear: moment().add(7, 'years').year(),
            ranges: {
                'Last Quarter': [moment().subtract(3, 'month').startOf('quarter'), moment().subtract(3, 'month').endOf('quarter')],
                'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Last 6 Months': [moment().subtract(6, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        });

        // DataTable initialization
        var accountsData = $('#accounts_data').DataTable({
            oLanguage: {sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'},
            processing: true,
            serverSide: true,
            lengthMenu: [40], // Specify the options you want to show
            lengthChange: false, // Hide the "Show X entries" dropdown
            searching:false, 
            pageLength: 40,
            order: [[3, 'desc']],
            ajax: {
                url: '{{ route("report.operational_anomaly_report") }}',
                type: 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: function (d) {
                    // Pass date range and supplier ID when making the request
                    // d.year = $('#year').val();
                    // d.quarter = $('#quarter').val();
                    // d.supplier = $('#supplier').val();
                    // d.sales_reps = $('#sales_rep').val();
                    // d.commission_rebate_id = $('#commission_table_id').val();
                },
            },

            beforeSend: function() {
                // Show both the DataTables processing indicator and the manual loader before making the AJAX request
                $('.dataTables_processing').show();
                $('#manualLoader').show();
            },

            complete: function(response) {
                // Hide both the DataTables processing indicator and the manual loader when the DataTable has finished loading
                $('#manualLoader').hide();
                $('.dataTables_processing').hide();
                if (businessdataTable.data().count() > 40) {
                    $('#business_data_paginate').show(); // Enable pagination
                } else {
                    $('#business_data_paginate').hide();
                }
            },

            columns: [
                { data: 'account_name', name: 'account_name', title: 'Account Name'},
                { data: 'fifty_two_wk_avg', name: 'fifty_two_wk_avg', title: '52wk AVG'},
                { data: 'ten_week_avg', name: 'ten_wk_avg', title: '10wk AVG'},
                { data: 'two_wk_avg_percentage', name: 'two_wk_avg_percentage', title: '2wk AVG 20%.'},
                { data: 'drop', name: 'drop', title: 'Drop'},
                { data: 'median', name: 'median', title: '52wk Median'},
            ],
        });

        $("#add_supplier").on('submit', function (e){
            e.preventDefault();
            var formData = new FormData($('#add_supplier')[0]);
            $.ajax({
                type: 'POST',
                url: '{{ route("account.add") }}', // Replace with your actual route name
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                     if(response.error){
                        $('#errorMessage').text(response.error);
                        $('#errorMessage').css('display','block');
                        
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

                            // Accumulate the error messages
                            errorMessages.push(errorMessageText);
                        });

                        $('#errorMessage').html(errorMessages.join('<br>'));
                        $('#errorMessage').css('display','block');

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
                        $('#enddate, #file, #importBtn').prop('disabled', true);

                        setTimeout(function () {
                            $('#successMessage').fadeOut();
                            window.location.reload();
                        }, 5000);
                    }
                },
                error: function(xhr, status, error) {
                    // Handle error response
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
</script>
@endsection