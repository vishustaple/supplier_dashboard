<!-- resources/views/excel-import.blade.php -->


@extends('layout.app', ['pageTitleCheck' => $pageTitle])

 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
    <div id="layoutSidenav_content">
        <div class="container">
            <div class="m-1 mb-2 row align-items-start justify-content-between">
                <div class="col-md-4">
                <h3 class="mb-0 ">{{ $pageTitle }}</h3>
            </div>
                   
                
            </div>
            <form  id="import_form"  enctype="multipart/form-data">
                @csrf
                <div class="row align-items-end py-3 border-top border-bottom mb-3">
                    <div class="form-group col-md-4 mb-0">
                        <label for="supplier">Select Supplier:</label>
                        <select id="supplier" name="supplier" class="form-control"> 
                            <option value="" selected>--Select--</option>
                            @if(isset($categorySuppliers))
                                @foreach($categorySuppliers as $categorySupplier)
                                    <option value="{{ $categorySupplier->id }}">{{ $categorySupplier->supplier_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="form-group relative col-md-3 mb-0">  
                        <label for="enddate">Select Date:</label>
                        <input class="form-control" id="dates" name="dates" readonly>
                    </div>
                    <div class="form-check relative col-md-2 mb-0">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="volume_rebate_check" checked>
                            <label class="form-check-label" for="volume_rebate_check">Volume Rebate</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="incentive_rebate_check" checked>
                            <label class="form-check-label" for="incentive_rebate_check">Incentive Rebate</label>
                        </div>
                    </div>
                    <div class="col-md-1 mb-0">
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                    <!-- Button trigger modal -->
                </div>
            </form>
            <div class="row justify-content-end py-3 header_bar" style="display:none !important;">
                <div class="col-md-4 card shadow border-0">
                    <h6 class="d-flex volume_rebate_header justify-content-between">Total Volume Rebate: <b style="color:#000;" id="volume_rebate"></b></h6>
                    <h6 class="d-flex incentive_rebate_header justify-content-between">Total Incentive Rebate: <b style="color:#000;" id="incentive_rebate"></b></h6>
                    <h6 class="d-flex justify-content-between">Start Date: <b style="color:#000;" id="startDates"></b></h6>
                    <h6 class="d-flex justify-content-between">End Date: <b style="color:#000;" id="endDates"></b></h6>
                </div>
            </div>
            <table id="supplier_report_data" class="data_table_files">
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
        $('#dates').daterangepicker();

        // Button click event
        $('#import_form').on('submit', function () {
            event.preventDefault();
            $('#endDates').text($('#dates').val().split(" - ")[1]);
            $('#startDates').text($('#dates').val().split(" - ")[0]);
            $('.header_bar').attr('style', 'display:flex !important;')
            // Initiate DataTable AJAX request
            $('#supplier_report_data').DataTable().ajax.reload();
        });

        // Event handler for when the user applies the date range
        $('#dates').on('apply.daterangepicker', function(ev, picker) {
            // $('#endDates').text(picker.endDate.format('MM/DD/YYYY'));
            // $('#startDates').text(picker.startDate.format('MM/DD/YYYY'));
        });

        function setPercentage() {
            var $html = $('<div>' + (supplierDataTable.column(3).data()[0] !== undefined ? supplierDataTable.column(3).data()[0] : '<input type="hidden" value="0"class="input_volume_rebate">') + ' ' + (supplierDataTable.column(4).data()[0] !== undefined ? supplierDataTable.column(4).data()[0] : '<input type="hidden" value="0" class="input_incentive_rebate">') + '</div>'),
            hiddenVolumeRebateInputValue = $html.find('.input_volume_rebate').val(),
            hiddenIncentiveRebateInputValue = $html.find('.input_incentive_rebate').val();
    
            if ($('#volume_rebate_check').is(':checked')) {
                supplierDataTable.column('volume_rebate:name').visible(true);
                $('#volume_rebate').text((hiddenVolumeRebateInputValue !== '0' ? '$' + parseFloat(hiddenVolumeRebateInputValue).toFixed(2) : ''));
                $('.volume_rebate_header').attr('style', 'display:flex !important;');
            } else {
                supplierDataTable.column('volume_rebate:name').visible(false);
                $('.volume_rebate_header').attr('style', 'display:none !important;');
                $('#volume_rebate').text('');
            }

            if ($('#incentive_rebate_check').is(':checked')) {
                supplierDataTable.column('incentive_rebate:name').visible(true);
                $('#incentive_rebate').text((hiddenIncentiveRebateInputValue !== '0' ? '$' + parseFloat(hiddenIncentiveRebateInputValue).toFixed(2) : ''));
                $('.incentive_rebate_header').attr('style', 'display:flex !important;');
            } else {
                supplierDataTable.column('incentive_rebate:name').visible(false);
                $('.incentive_rebate_header').attr('style', 'display:none !important;');
                $('#incentive_rebate').text('');
            }

            
        }

        // DataTable initialization
        var supplierDataTable = $('#supplier_report_data').DataTable({
            oLanguage: {sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'},
            processing: true,
            serverSide: true,
            lengthMenu: [40], // Specify the options you want to show
            lengthChange: false, // Hide the "Show X entries" dropdown
            // paging: false,
            searching:false, 
            pageLength: 40,
            order: [[3, 'desc']],
            ajax: {
                url: '{{ route("report.supplier_filter") }}',
                type: 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: function (d) {
                    // Pass date range and supplier ID when making the request
                    d.dates = $('#dates').val();
                    d.supplier = $('#supplier').val();
                },
            },

            beforeSend: function() {
                // Show both the DataTables processing indicator and the manual loader before making the AJAX request
                $('.dataTables_processing').show();
                $('#manualLoader').show();
            },

            complete: function(response) {
                // Hide both the DataTables processing indicator and the manual loader when the DataTable has finished loading
                $('.dataTables_processing').hide();
                $('#manualLoader').hide();
                if (businessdataTable.data().count() > 40) {
                    $('#business_data_paginate').show(); // Enable pagination
                } else {
                    $('#business_data_paginate').hide();
                }
            },

            columns: [
                { data: 'supplier', name: 'supplier', title: 'Supplier'},
                { data: 'account_name', name: 'account_name', title: 'Account Name'},
                { data: 'amount', name: 'amount', title: 'Amount'},
                { data: 'volume_rebate', name: 'volume_rebate', title: 'Volume Rebate'},
                { data: 'incentive_rebate', name: 'incentive_rebate', title: 'Incentive Rebate'},
                { data: 'date', name: 'date', title: 'Date'},
                // { data: 'start_date', name: 'start_date', title: 'Start Date'},
                // { data: 'end_date', name: 'end_date', title: 'End Date'},
            ],

            fnDrawCallback: function( oSettings ) {
                setPercentage();
                // $('#endDates').text($('#dates').val().split(" - ")[1]);
                // $('#startDates').text($('#dates').val().split(" - ")[0]);
            },
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

        $('#exampleModal').on('show.bs.modal', function (e) {
            $('#errorMessage').fadeOut();
            $("#add_supplier")[0].reset();
            $('#grandparentSelect').prop('disabled', true);
        })

        //submit form with ajax

        $("#add_supplier").on('submit', function (e){
            // alert("here");

        e.preventDefault();
        var formData = new FormData($('#add_supplier')[0]);
        console.log(formData);
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
                    console.log(errorMessageText);

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
                        $('#date,#file,#importBtn').prop('disabled', true);
                        setTimeout(function () {
                            $('#successMessage').fadeOut();
                            window.location.reload();
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