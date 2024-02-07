<!-- resources/views/excel-import.blade.php -->


@extends('layout.app')

 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => 'Accounts Data'])
    <div id="layoutSidenav_content">
        <h3 class="mb-0 ps-2">Manage Accounts</h3>
        <div class="row align-items-end border-bottom pb-3 mb-4">
            <div class="col-md-12 mb-0 text-end">
                <!-- Button trigger modal -->
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
                <i class="fa-solid fa-plus"></i> Account
                </button>
                <button id="downloadAccountCsvBtn" class="btn-success btn" title="Csv Download"><i class="fa-solid me-2 fa-file-csv"></i>Download</button>
            </div>
        </div>
        <div class="mx-auto d-flex justify-content-between align-items-center">
        

                    <!-- Modal -->
                    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Add Account</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                        <div class="alert alert-success" id="successMessage" style="display:none;">
                        </div>
                        <div class="alert alert-danger" id="errorMessage" style="display:none;">
                        </div>
                            <form class="" id="add_supplier" method="post">
                                @csrf
                                <div class="form-group">
                                    <label>Customer ID </label>
                                    <input type="text" placeholder="Enter Customer Id" class="form-control" name="customer_id" id="customer_id">
                                </div> 
                                <div class="form-group">
                                    <label>Customer Name</label>
                                    <input type="text" placeholder="Enter Customer name" class="form-control" name="customer_name" id="customer_name">
                                </div>
                                <div class="form-group">
                                    <div class="form-check form-check-inline">
                                    <input type="checkbox" id="parent" class="form-check-input radio-checkbox" name="parent" value="1">
                                    <label class="form-check-label" for="parent">Parent</label>
                                    </div>
                                    <!-- <div class="form-check form-check-inline">
                                    <input type="checkbox" id="grandparent" class="form-check-input radio-checkbox" name="grandparent" value="0">
                                    <label class="form-check-label" for="grandparent">GrandParent</label>
                                    </div> -->
                                </div>
                                <div class="form-group">
                                    <label for="selectBox">Grand Parent:</label>
                                    <select id="grandparentSelect" name="grandparentSelect" class="form-control" disabled> 
                                        <option value="" selected>--Select--</option>
                                        @if(!empty($grandparent))
                                            @foreach($grandparent as $gp)
                                            <option value="{{ $gp->id }}">{{ $gp->customer_name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <!-- <div class="form-group">
                                    <label for="selectBox"> Parent:</label>
                                <select id="parentSelect" name="parentSelect" class="form-control" disabled> 
                                <option value="" selected>--Select--</option>
                             
                                <option value=""></option>
                              
                                </select>
                                </div> -->

                                <div class="text-center">
                                <button type="submit" class="btn btn-primary mx-auto" id="supplier_add">Submit</button>
                                </div>

                            </form>
                        </div>
                        </div>
                    </div>
                    </div>
                    
        </div>
        <div class="container">
      
            <table id="account_data" class="data_table_files">
            <thead>
                    <tr>
                        <th>Customer Number</th>
                        <th>Customer Name</th>
                        <th>Supplier Catagory</th>
                        <th>Account Name</th>
                        <th>Record Type</th>
                        <th>Date</th>
                    </tr>
                </thead>
            </table>
        </div>
        
    </div>
</div>
<script>
    $(document).ready(function() {
        // $('#grandparentSelect').change(function(){
        // var selectedValue = $(this).val();
        // console.log(selectedValue);
        // $.ajax({
        //     type: 'GET',
        //         url: '{{route('getparent')}}', 
        //         data:{ selectedValue: selectedValue },
        //         success: function(response) {},
        //         error: function(xhr, status, error) {

        //         }


        // });
            
        // });

        // $('#account_data').DataTable({
        //     "paging": true,   // Enable pagination
        //     // "ordering": true, // Enable sorting
        //     "searching": true, // Enable search
        //     "pageLength": 40,
        //     "lengthChange":false,
        //     "data": <?php if(isset($accountsdata)){echo $accountsdata;}  ?>,
        //     "columns": [
        //         { title: 'SR. No' },
        //         { title: 'Account Name' },
        //         { title: 'Account Number' },
        //         { title: 'Parent Name' },
        //         { title: 'GrandParent Name' },
        //         { title :'Internal Reporting Name'},
        //         { title :'QBR'},
        //         { title :'Spend Name'},
        //         { title :'Supplier Acct Rep'},
        //         { title :'Management Fee'},
        //         { title :'Record Type'},
        //         { title :'Categroy Supplier'},
        //         { title :'CPG Sales Representative'},
        //         { title :'CPG Customer Service Rep'},
        //         { title :'SF Cat'},
        //         { title :'Rebate Freq'},
        //         { title :'Member Rebate'},
        //         { title :'Comm Rate'},

              
        //     ]
        // });

        var accountTable = $('#account_data').DataTable({
            oLanguage: {
                sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'
            },
            processing: true,
            serverSide: true,
            pageLength: 50,
            ajax: {
                url: '{{ route('account.filter') }}',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: function (d) {
                    // Pass date range and supplier ID when making the request
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                    d.supplierId = $('#supplierId').val();
                },
            },
            beforeSend: function() {
                // Show both the DataTables processing indicator and the manual loader before making the AJAX request
                $('.dataTables_processing').show();
                $('#manualLoader').show();
            },
            complete: function() {
                // Hide both the DataTables processing indicator and the manual loader when the DataTable has finished loading
                $('.dataTables_processing').hide();
                $('#manualLoader').hide();
            },
            columns: [
                { data: 'customer_number', name: 'customer_number' },
                { data: 'customer_name', name: 'customer_name' },
                { data: 'supplier_name', name: 'supplier_name' },
                { data: 'account_name', name: 'account_name' },
                { data: 'record_type', name: 'record_type' },
                { data: 'date', name: 'date'},
                // { data: 'action', name: 'action', orderable: false, searchable: false },
            ],

        });

        if (accountTable.data().count() > 40) {
            $('#account_data_paginate').show(); // Enable pagination
        } else {
            $('#account_data_paginate').hide();
        }
        
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
                url: '{{ route('account.add') }}', // Replace with your actual route name
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
                        $('#enddate,#file,#importBtn').prop('disabled', true);
                        setTimeout(function () {
                            $('#successMessage').fadeOut();
                            window.location.reload();
                        }, 5000); 
                        
                    }
                    // Handle success response
                    // console.log(response);
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

        $('#downloadAccountCsvBtn').on('click', function () {
            // Trigger CSV download
            downloadAccountCsv();
        });

        function downloadAccountCsv() {
            // You can customize this URL to match your backend route for CSV download
            var csvUrl = '{{ route('account.export-csv') }}';

            // Open a new window to download the CSV file
            window.open(csvUrl, '_blank');
        }

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