@extends('layout.app', ['pageTitleCheck' => $pageTitle])
@section('content')
    <div id="layoutSidenav">
        @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
        <div id="layoutSidenav_content">
            <div class="container">
                <div class="m-1 mb-2 d-md-flex border-bottom pb-3 mb-3 align-items-center justify-content-between">
                    <h3 class="mb-0 ">{{ $pageTitle }}</h3>
                </div>
                <div id="error"></div>
                <div id="successMessages"></div>
                <form  id="import_form"  enctype="multipart/form-data">
                    @csrf
                    <div class="row align-items-start border-bottom pb-3 mb-4">
                        <div class="form-group check_form_labels  col-md-3  mb-0">
                            <div class="form-check">
                                <input class="form-check-input checkboxs" name="supplier_id[]" type="checkbox" value="all" id="allCheckBox">
                                <label class="form-check-label" for="defaultCheck1">All Suppliers</label>
                            </div>
                            @if(isset($categorySuppliers))
                                @foreach($categorySuppliers as $supplier)
                                    <div class="form-check">
                                        @if(!empty($supplier->supplier_name) &&  $supplier->id != 7)
                                            <input class="form-check-input checkboxs" name="supplier_id[]" type="checkbox" value="{{ $supplier->id }}">
                                            <label class="form-check-label" for="defaultCheck1">{{ $supplier->supplier_name }}</label>
                                        @endif
                                    </div>
                                @endforeach    
                            @endif
                        </div>
                        <div class="form-group relative col-md-9 mb-0">  
                            <div class="row">
                                <div class="col-md-7">
                                    <div class="form-group relative  mb-3 row">
                                        <div class="col-6">
                                            <label for="startdate">Select Start Date:</label>
                                            <input class="form-control" id="startdate" name="dates" placeholder="Enter Your Start Date " >
                                        </div>  
                                        <div class="col-6">
                                            <label for="enddate">Select End Date:</label>
                                            <input class="form-control" id="enddate" name="dates" placeholder="Enter Your End Date " >
                                        </div>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label for="selectBox">Select Account:</label>
                                        <select id="account_name" name="account_name" class="form-control"></select>
                                    </div>
                                </div>
                               
                                <div class="col-md-5 mt-1 mb-0 ms-auto text-end">
                                    <div class="card bg-light mb-3 ms-3" style=" display: none;">
                                        <div class="card-body">
                                            <p class="card-text d-flex justify-content-between"><b>Test: </b></p>
                                        </div>
                                    </div>
                                    <button id="submitBtn" class="btn btn-primary m-1">Submit</button>
                                    <button id="downloadPdfBtn" class="btn-danger btn m-1 disabled" title="Pdf Download"><i class="fa-solid me-1 fa-file-pdf"></i>PDF</button>
                                    <button id="downloadCsvBtn" class="btn-success btn m-1" title="Csv Download"><i class="fa-solid me-1 fa-file-csv"></i>Download</button>
                                    @if($consolidatedFile)
                                        <a class="btn-success px-3 btn m-1" id="downloadLinkReport" href="{{ route('report.download-user-file', ['file' => $consolidatedFile]) }}"><i class="fa-solid me-1 fa-file-csv"></i>Download Genrated Report</a>
                                    @elseif($file_user_id)
                                        <button id="queProcess" class="btn-success px-3 btn m-1 disabled" href=""><i class="fa-solid me-1 fa-file-csv"></i>Download Genrated Report</button>
                                    @else
                                        <button id="downloadButton" class="btn-success px-3 btn m-1" title="Csv Download"><i class="fa-solid me-1 fa-file-csv"></i>Download Large Selected Account Data</button>
                                    @endif
                                    <button id="downloadButtonSmall" class="btn-success px-3 btn m-1" title="Csv Download"><i class="fa-solid me-1 fa-file-csv"></i>Download Small Selected Account Data</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                <table class="data_table_files" id="consolidated_supplier_data">
                <thead>
                    <tr>
                        <th>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" value="all_accounts" id="selectAllAccounts">Account Name
                            </div>
                        </th>
                        <th>Supplier Name</th>
                        <th>Spend</th>
                        <th>Category</th>
                    </tr>
                </thead>
                </table>
            </div>
        </div>
    </div>
    <style>
        div#page-loader {
            top: 0;
            left: 0;
            position: fixed;
            width: 100%;
            height: 100%;
            background: #00000080;
            z-index: 999999;
        }

        div#page-loader-wrap {
            text-align: center;
            /* vertical-align: center !important; */
            margin-top: 20%;
        }

        #consolidated_supplier_data{
            display:block;
            overflow-x:auto;
        }

        #consolidated_supplier_data thead tr th {
            white-space: nowrap;
        }

        .form-group.check_form_labels.mb-0 {
            display: flex;
            flex-wrap: wrap;
        }

        .form-group.check_form_labels.mb-0 .form-check {
            width: 50%;
            padding-bottom: 10px;
        }
        
        .select2-container .select2-selection--single{
            padding-top: 5px;
            height: 38px !important;
        }
    </style>
    <!-- Include Date Range Picker JavaScript -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
             // Check if the button with a specific ID exists
             if ($('#queProcess').length > 0) {
                // Set the interval time (in milliseconds)
                var intervalTime = 2000; // 5 seconds

                // Set the interval and store the interval ID so you can clear it later
                var intervalId = setInterval(function() {
                    // Perform your AJAX request
                    $.ajax({
                        url: '{{ route("consolidated-report.check") }}', // Set the endpoint for the request
                        type: 'GET', // Specify the request type (GET, POST, etc.)
                        success: function(response) {
                            if (response.success && response.fileName) {
                                $('#queProcess').remove();

                                var consolidatedFile = response.fileName; // Pass the PHP variable to JS
                                var downloadUrl = "{{ url('admin/reports/consolidated/consolidate-report') }}/" + consolidatedFile; // Build the URL dynamically

                                if ($('#downloadCsvBtn').length > 0) {
                                    $('#downloadLinkReport').remove();
                                    $('#downloadCsvBtn').after('<a class="btn-success px-3 btn m-1" id="downloadLinkReport" href="' + downloadUrl + '"><i class="fa-solid me-1 fa-file-csv"></i>Download Generated Report</a>');
                                }

                                // Stop the interval once the file is ready
                                clearInterval(intervalId);
                            }

                            console.log('Request successful:', response);
                            // You can handle the response here
                        },
                        error: function(xhr, status, error) {
                            console.error('Request failed:', error);
                            // You can handle errors here
                        }
                    });
                }, intervalTime);
            }
            var defaultStartDate = moment().subtract(1, 'month').startOf('month'); // Default start date (1 month ago)
            var defaultEndDate = moment(); // Default end date (today)
            
            // Set the default start date in the input
            $('#startdate').val(defaultStartDate.format('MM/DD/YYYY'));
            
            // Set the default end date in the input
            $('#enddate').val(defaultEndDate.format('MM/DD/YYYY'));

            $(document).on('click', '#downloadButton', function(e) {
            // $('#downloadButton').on('click', function(e) {
                e.preventDefault();
                // Get all checked checkboxes within the DataTable
                var oldselect = 0,
                    checkedValues = [],
                    selectedAccounts = [],
                    selectedSupplierIds = [];
                
                // Selecting the account name and create the selectedAccounts array
                $('#consolidated_supplier_data tbody tr').each(function() {
                    var checkbox = $(this).find('input[type="checkbox"]:checked');
                    if (checkbox.length > 0) {
                        var accountName = $(this).find('td').eq(0).text().trim(), // Assuming the Account Name is in the first column
                            selectedSupplier = $(this).find('input[type="hidden"]').val(); // Assuming the Supplier Ids is hidden
            
                        selectedAccounts.push(accountName);
                        if (oldselect !== selectedSupplier) {
                            oldselect = selectedSupplier;

                            selectedSupplierIds.push(selectedSupplier);
                        }
                    }
                });

                if ($('#selectAllAccounts').is(':checked')) {
                    checkedAllAccount = 1;
                }

                // Selecting the supplier_id and create the supplier_id array
                $('.checkboxs:checked').each(function() {
                    checkedValues.push($(this).val());
                });

                // Added validation of empty supplier_id array
                if (checkedValues.length === 0) {
                    $('#error').append('<svg xmlns="http://www.w3.org/2000/svg" style="display: none;"><symbol id="exclamation-triangle-fill" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></symbol></svg><div class="alert alert-danger alert-dismissible fade show" role="alert">  <svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="Danger:"><use xlink:href="#exclamation-triangle-fill"/></svg><strong>Error</strong> Please the supplier.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
                } else {
                    // Added validation of empty account name array
                    if (selectedAccounts.length === 0) {
                        $('#error').append('<svg xmlns="http://www.w3.org/2000/svg" style="display: none;"><symbol id="exclamation-triangle-fill" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></symbol></svg><div class="alert alert-danger alert-dismissible fade show" role="alert">  <svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="Danger:"><use xlink:href="#exclamation-triangle-fill"/></svg><strong>Error</strong> Please check the account.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
                    } else {
                        var button = document.getElementById('downloadButton'),
                        formData = new FormData($('#import_form')[0]);
                        button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Loading';
                        button.disabled = true;
                        // After checked the validation finally send ajax request
                        $.ajax({
                            url: '{{ route("consolidated-report.download") }}',
                            type: 'POST',
                            data: { 
                                account_name: selectedAccounts,
                                supplier_id: selectedSupplierIds,
                                checkedAllAccount:checkedAllAccount,
                                start_date: moment($('#startdate').val(), 'MM/DD/YYYY').format('YYYY-MM-DD'),
                                end_date: moment($('#enddate').val(), 'MM/DD/YYYY').format('YYYY-MM-DD') 
                            },
                            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                            // xhrFields: { responseType: 'blob' },
                            success: function (response) {
                                $('#successMessages').html('');
                                $('#successMessages').append('<div class="alert alert-success alert-dismissible fade show" role="alert">'+response.message+'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                                $('#downloadButton').remove();

                                if ($('#downloadCsvBtn').length > 0) {
                                    // Append a new button after the existing button
                                    $('#downloadCsvBtn').after('<button id="queProcess" class="btn-success px-3 btn m-1 disabled" href=""><i class="fa-solid me-1 fa-file-csv"></i>Download Genrated Report</button>');
                                }

                                // Check if the button with a specific ID exists
                                if ($('#queProcess').length > 0) {
                                    // Set the interval time (in milliseconds)
                                    var intervalTime = 2000; // 5 seconds

                                    // Set the interval and store the interval ID so you can clear it later
                                    var intervalId = setInterval(function() {
                                        // Perform your AJAX request
                                        $.ajax({
                                            url: '{{ route("consolidated-report.check") }}', // Set the endpoint for the request
                                            type: 'GET', // Specify the request type (GET, POST, etc.)
                                            success: function(response) {
                                                if (response.success && response.fileName) {
                                                    $('#queProcess').remove();

                                                    var consolidatedFile = response.fileName; // Pass the PHP variable to JS
                                                    var downloadUrl = "{{ url('admin/reports/consolidated/consolidate-report') }}/" + consolidatedFile; // Build the URL dynamically

                                                    if ($('#downloadCsvBtn').length > 0) {
                                                        $('#downloadLinkReport').remove();
                                                        $('#downloadCsvBtn').after('<a class="btn-success px-3 btn m-1" id="downloadLinkReport" href="' + downloadUrl + '"><i class="fa-solid me-1 fa-file-csv"></i>Download Generated Report</a>');
                                                    }

                                                    // Stop the interval once the file is ready
                                                    clearInterval(intervalId);
                                                }

                                                console.log('Request successful:', response);
                                                // You can handle the response here
                                            },
                                            error: function(xhr, status, error) {
                                                console.error('Request failed:', error);
                                                // You can handle errors here
                                            }
                                        });
                                    }, intervalTime);
                                }
                            },
                            error: function(xhr, status, error) {
                                button.disabled = false;
                                button.innerHTML = '<i class="fa-solid me-1 fa-file-csv"></i>Download Selected Account Data';
                                alert('File download failed!');
                            }
                        });
                    }
                }
            });

            $(document).on('click', '#downloadLinkReport', function() {
                $(this).remove();
                if ($('#downloadCsvBtn').length > 0) {
                    // Append a new button after the existing button
                    $('#downloadCsvBtn').after('<button id="downloadButton" class="btn-success px-3 btn m-1" title="Csv Download"><i class="fa-solid me-1 fa-file-csv"></i>Download Large Selected Account Data</button>');
                }
            });

            $(document).on('click', '#downloadButtonSmall', function(e) {
                e.preventDefault();
                // Get all checked checkboxes within the DataTable
                var oldselect = 0,
                checkedValues = [],
                selectedAccounts = [],
                checkedAllAccount = 0,
                selectedSupplierIds = [];
                
                // Selecting the account name and create the selectedAccounts array
                $('#consolidated_supplier_data tbody tr').each(function() {
                    var checkbox = $(this).find('input[type="checkbox"]:checked');
                    if (checkbox.length > 0) {
                        var accountName = $(this).find('td').eq(0).text().trim(), // Assuming the Account Name is in the first column
                            selectedSupplier = $(this).find('input[type="hidden"]').val(); // Assuming the Supplier Ids is hidden
            
                        selectedAccounts.push(accountName);
                        if (oldselect !== selectedSupplier) {
                            oldselect = selectedSupplier;

                            selectedSupplierIds.push(selectedSupplier);
                        }
                    }
                });

                // Selecting the supplier_id and create the supplier_id array
                $('.checkboxs:checked').each(function() {
                    checkedValues.push($(this).val());
                });

                if ($('#selectAllAccounts').is(':checked')) {
                    checkedAllAccount = 1;
                }

                // Added validation of empty supplier_id array
                if (checkedValues.length === 0) {
                    $('#error').append('<svg xmlns="http://www.w3.org/2000/svg" style="display: none;"><symbol id="exclamation-triangle-fill" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></symbol></svg><div class="alert alert-danger alert-dismissible fade show" role="alert">  <svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="Danger:"><use xlink:href="#exclamation-triangle-fill"/></svg><strong>Error</strong> Please the supplier.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
                } else {
                    // Added validation of empty account name array
                    if (selectedAccounts.length === 0) {
                        $('#error').append('<svg xmlns="http://www.w3.org/2000/svg" style="display: none;"><symbol id="exclamation-triangle-fill" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></symbol></svg><div class="alert alert-danger alert-dismissible fade show" role="alert">  <svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="Danger:"><use xlink:href="#exclamation-triangle-fill"/></svg><strong>Error</strong> Please check the account.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
                    } else {
                        var button = document.getElementById('downloadButtonSmall'),
                        formData = new FormData($('#import_form')[0]);
                        button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Loading';
                        button.disabled = true;
                        // After checked the validation finally send ajax request
                        $.ajax({
                            url: '{{ route("consolidated-report.download") }}',
                            type: 'POST',
                            data: {
                                small_data:1,
                                account_name: selectedAccounts,
                                supplier_id: selectedSupplierIds,
                                checkedAllAccount:checkedAllAccount,
                                start_date: moment($('#startdate').val(), 'MM/DD/YYYY').format('YYYY-MM-DD'),
                                end_date: moment($('#enddate').val(), 'MM/DD/YYYY').format('YYYY-MM-DD') 
                            },
                            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                            xhrFields: { responseType: 'blob' },
                            success: function(data, status, xhr) {
                                var blob = new Blob([data], { type: 'text/csv' });
                                var link = document.createElement('a');
                                link.href = window.URL.createObjectURL(blob);
                                var now = new Date();
                                var dateStr = now.getFullYear() + "-" +
                                ("0" + (now.getMonth() + 1)).slice(-2) + "-" +
                                ("0" + now.getDate()).slice(-2) + "_" +
                                ("0" + now.getHours()).slice(-2) + "-" +
                                ("0" + now.getMinutes()).slice(-2) + "-" +
                                ("0" + now.getSeconds()).slice(-2);
                                link.download = 'Consolidated_Account_Report_' + dateStr + '.csv';
                                link.click();
                                button.disabled = false;
                                button.innerHTML = '<i class="fa-solid me-1 fa-file-csv"></i>Download Small Selected Account Data';
                            },
                            error: function(xhr, status, error) {
                                button.disabled = false;
                                button.innerHTML = '<i class="fa-solid me-1 fa-file-csv"></i>Download Small Selected Account Data';
                                alert('File download failed!');
                            }
                        });
                    }
                }
            });

            // Function to update checked values and check for supplier ID
            function updateCheckedValues() {
                // checkedValuess = []; // Clear the array
                // $('.checkboxs:checked').each(function() {
                //     checkedValuess.push($(this).val());
                // });

                // var supplierIdToCheck = 3; // Replace with the supplier ID you want to check
                // var supplierIdToCheck1 = 'all'; // Replace with the supplier ID you want to check
                // checkedAccounts = [];

                // if (checkedValuess.includes(supplierIdToCheck.toString())) { // Convert to string for comparison
                //     $('#selectAllAccounts').hide();
                //     $('#selectAllAccounts').prop('checked', false)
                //     $('#consolidated_supplier_data').DataTable().ajax.reload();
                // } else if (checkedValuess.includes(supplierIdToCheck1.toString())) {
                //     $('#selectAllAccounts').hide();
                //     $('#selectAllAccounts').prop('checked', false)
                //     $('#consolidated_supplier_data').DataTable().ajax.reload();
                // } else {
                    // $('#selectAllAccounts').show();
                    // $('#selectAllAccounts').prop('checked', false)
                    // $('#consolidated_supplier_data').DataTable().ajax.reload();
                // }
            }

            // Attach the change event to checkboxes
            // $('.checkboxs').change(function() {
            //     updateCheckedValues();
            // });

            $('#select_dates').on('change', function(){
                var selectValue = $(this).val();

                if (selectValue == 0) {
                    $('#start_date').prop('disabled', false);
                    $('#end_date').prop('disabled', false);
                } else {
                    $('#start_date').prop('disabled', true);
                    $('#end_date').prop('disabled', true);
                }
            });

            $('.checkboxs').click(function(){
                var anyChecked = false;
        
                // Loop through each checkbox with class "myCheckbox"
                $('.checkboxs').each(function(){
                    // Check if the current checkbox is checked
                    if ($(this).is(':checked')) {
                        anyChecked = true;
                        // Exit the loop if any checkbox is checked
                        return false;
                    }
                });
                
                // Output the result
                if (anyChecked) {
                    $('#account_name').val('').trigger('change');
                }
            });

            function selectCustomer () {
                $('#account_name').select2({
                    ajax: {
                        url: "{{ route('commissions.customerSearch') }}",
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            var checkedValues = [];
                            $('.checkboxs:checked').each(function() {
                                checkedValues.push($(this).val());
                            });

                            var data = {
                                q: params.term, // search term
                                supplier_check: true,
                                supplier_array: checkedValues, // add your extra parameter here
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
                });
            }

            selectCustomer ()

            // Start Date Picker with custom ranges
            $('#startdate').daterangepicker({
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
                    $('#startdate').val(start.format('MM/DD/YYYY')); // Set start date
                    $('#enddate').val(end.format('MM/DD/YYYY')); // Set end date
                } else {
                    // If a normal date is picked, only set the startDate
                    $('#startdate').val(start.format('MM/DD/YYYY'));
                }
            });

            // End Date Picker - Simple calendar
            $('#enddate').daterangepicker({
                autoApply: true,
                showDropdowns: true,
                singleDatePicker: true,
                locale: {
                    format: 'MM/DD/YYYY'
                }
            }, function(start) {
                $('#enddate').val(start.format('MM/DD/YYYY')); // Manually set the selected date for end date
            });

            // Button click event
            $('#import_form').on('submit', function () {
                event.preventDefault();
                // Initiate DataTable AJAX request
                $('#consolidated_supplier_data').DataTable().ajax.reload();
            });

            $('#allCheckBox').change(function() {
                if ($(this).is(':checked')) {
                    $('.checkboxs').not(this).prop('checked', false).prop('disabled', true);
                } else {
                    $('.checkboxs').prop('disabled', false);
                }
            });

            function setTotalAmount() {
                if ($('.total_amount').val() != null) {
                    $('.card-body').html('');
                    $('.card').show();
                    $('.card-body').html('<p class="card-text d-flex justify-content-start"><b>Total Amount: </b> $' + $('.total_amount').val() + '</p>');
                } else {
                    $('.card').hide();
                }
            }

            // DataTable initialization
            var consolidateddataTable = $('#consolidated_supplier_data').DataTable({
                oLanguage: {
                    sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'
                },
                processing: true,
                serverSide: true,
                lengthMenu: [40],
                searching: false,
                paging: true,
                pageLength: 40,
                ajax: {
                    url: '{{ route("consolidated-report.filter") }}',
                    type: 'POST',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: function (d) {
                        var checkedValues = [];
                        $('.checkboxs:checked').each(function() {
                            checkedValues.push($(this).val());
                        });

                        d.supplier_id = checkedValues;
                        d.account_name = $('#account_name').val();
                        d.start_date = moment($('#startdate').val(), 'MM/DD/YYYY').format('YYYY-MM-DD');
                        d.end_date = moment($('#enddate').val(), 'MM/DD/YYYY').format('YYYY-MM-DD');
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
                },

                columns: [
                    { data: 'account_name', name: 'account_name' },
                    { data: 'supplier_name', name: 'supplier_name' },
                    { data: 'spend', name: 'spend', 'searchable': false },
                    { data: 'category', name: 'category', 'orderable': false, 'searchable': false },
                ],

                fnDrawCallback: function( oSettings ) {
                    setTotalAmount();
                },
            });

            $('#consolidated_supplier_data_length').hide();

            $(document).on('change', '.checkboxMain', function() {
                var checkedValues = [];
                $('.checkboxMain:checked').each(function() {
                    checkedValues.push($(this).val());
                });
            });

            $('#downloadCsvBtn').on('click', function () {
                // Trigger CSV download
                downloadCsv();
            });

            // Array to store the checked checkbox IDs
            var checkedAccounts = [];

            // Handle 'Select All' checkbox click
            $('#selectAllAccounts').on('click', function () {
                var rows = consolidateddataTable.rows().nodes();
                $('input[type="checkbox"]', rows).prop('checked', this.checked);

                // Update the checkedAccounts array based on the "Select All" state
                checkedAccounts = this.checked ? consolidateddataTable.rows().data().map(row => row.id).toArray() : [];
            });

            // Handle individual row checkbox click
            $('#supplierReport tbody').on('click', 'input[type="checkbox"]', function () {
                var checkboxId = $(this).data('id'); // Assuming each checkbox has a unique identifier in a data attribute

                if (!this.checked) {
                    $('#selectAllAccounts').prop('checked', false);
                    // Remove unchecked ID from checkedAccounts
                    checkedAccounts = checkedAccounts.filter(id => id !== checkboxId);
                } else {
                    // Add checked ID to checkedAccounts
                    if (!checkedAccounts.includes(checkboxId)) {
                        checkedAccounts.push(checkboxId);
                    }
                }

                // Check if all checkboxes are checked
                if ($('input[type="checkbox"]:checked', consolidateddataTable.rows().nodes()).length === $('input[type="checkbox"]', consolidateddataTable.rows().nodes()).length) {
                    $('#selectAllAccounts').prop('checked', true);
                }
            });

            // After reloading the DataTable
            consolidateddataTable.on('draw', function () {
                // Restore checked states
                $('input[type="checkbox"]', consolidateddataTable.rows().nodes()).each(function () {
                    var checkboxId = $(this).data('id');
                    $(this).prop('checked', checkedAccounts.includes(checkboxId));
                });
            });

            function downloadCsv() {
                // You can customize this URL to match your backend route for CSV download
                var csvUrl = '{{ route("consolidated-report.export-csv") }}',
                order = consolidateddataTable.order(),
                start = moment($('#startdate').val(), 'MM/DD/YYYY').format('YYYY-MM-DD'),
                end = moment($('#enddate').val(), 'MM/DD/YYYY').format('YYYY-MM-DD'),
                checkedValues = [];
                $('.checkboxs:checked').each(function() {
                    checkedValues.push($(this).val());
                });

                // Add query parameters for date range and supplier ID
                csvUrl += '?start_date=' + start + '&end_date=' + end + '&column=' + order[0][0] + '&order=' + order[0][1] + '&supplier_id=' + checkedValues + '&account_name=' + $('#account_name').val();

                // Open a new window to download the CSV file
                window.open(csvUrl, '_blank');
            }
        });
    </script>
@endsection