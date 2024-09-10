@extends('layout.app', ['pageTitleCheck' => $pageTitle])

@section('content')
<div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
    <div id="layoutSidenav_content">
        <div class="container">
            <div class="m-1 mb-2 d-md-flex border-bottom pb-3 mb-3 align-items-center justify-content-between">
                <h3 class="mb-0 ">{{ $pageTitle }}</h3>
                <a href="{{ route('commissions.add-view') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Add Commissions</a>
            </div>
            <table class="data_table_files" id="commission_data">
                <thead>
                    <tr>
                        <th>Account Name</th>
                        <th>Supplier</th>
                        <th>Sales Rep</th>
                        <th>Commission</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="modal fade" id="editCommissionModal" tabindex="-1" aria-labelledby="editCommissionModalLabel" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editCommissionModalLabel">Edit Commission</h5>
                        <!-- Close icon -->
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div id="editsuccessMessage"></div>
                    <div id="editerrorMessage" ></div>
                        <form action="{{route('commissions.edit')}}" method="post" id="edit_commission_name">
                            @csrf
                            <div class="modal-body">
                                <input type="hidden" name="commission_id" id="commission_id" value="">
                                <div class="form-group">
                                    <label>Commission</label>
                                    <input type="text" placeholder="Enter Commission" class="form-control" name="commissions" id="commissions" value="" required>
                                </div>
                                <div class="form-group ps-1">
                                    <div class="form-check col-md-6">
                                        <input class="form-check-input" type="radio"  value="1" name="status" id="checked">
                                        <label class="form-check-label" for="flexRadioDefault1">
                                            Active
                                        </label>
                                    </div>
                                    <div class="form-check col-md-6">
                                        <input class="form-check-input" type="radio" value="0" name="status" id="unchecked">
                                        <label class="form-check-label" for="flexRadioDefault2">
                                            In-Active
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="text" name="date" id="commission_date" class="dateRangePickers dateRangePicker form-control" placeholder="Select Date Range" readonly="readonly" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">Save changes</button>
                            </div>
                        </form>   
                    </div>
                </div>
            </div>
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
</style>

<!-- Include Date Range Picker JavaScript -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
<script>
    $(document).ready(function() {
        // DataTable initialization
        var commissionDatatable = $('#commission_data').DataTable({
            oLanguage: {
                sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'
            },
            processing: true,
            serverSide: true,
            pageLength: 50,
            ajax: {
                url: '{{ route("commissions.filter") }}',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
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
                { data: 'account_name', name: 'account_name' },
                { data: 'supplier_name', name: 'supplier_name' },
                { data: 'sales_rep', name: 'sales_rep' },
                { data: 'commissions', name: 'commissions' },
                { data: 'start_date', name: 'start_date' },
                { data: 'end_date', name: 'end_date' },
                { data: 'status', name: 'status' },
                { data: 'id', name: 'id', 'orderable': false, 'searchable': false }
            ],
        });

        $('#commission_data_length').hide();
        $('#downloadCommissionCsvBtn').on('click', function () {
            // Trigger CSV download
            downloadCommissionCsv();
        });

        function downloadCommissionCsv() {
            // You can customize this URL to match your backend route for CSV download
            var csvUrl = '{{ route("commissions.export-csv") }}';
            csvUrl += '?search=' + dataTable.search();
            // Open a new window to download the CSV file
            window.open(csvUrl, '_blank');
        }

        $('.dateRangePicker').daterangepicker({
            autoApply: true,
            showDropdowns: true,
            minYear: moment().subtract(7, 'years').year(),
            maxYear: moment().add(7, 'years').year(),
            // maxDate: moment(),
            ranges: {
                'Last Quarter': [moment().subtract(3, 'month').startOf('quarter'), moment().subtract(3, 'month').endOf('quarter')],
                'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Last 6 Months': [moment().subtract(6, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        });
        
        var myModal = document.getElementById('editCommissionModal');
        myModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget, // Button that triggered the modal
            id = button.getAttribute('data-id'), // Extract value from data-* attributes
            commissionNumber = button.getAttribute('data-commissions'),
            date = button.getAttribute('data-date'),
            commissionStatus = button.getAttribute('data-status'),
            
            // Getting input using ids
            commissionId = document.getElementById('commission_id'),
            commissions = document.getElementById('commissions'),
            commissionDate = document.getElementById('commission_date'),
            commissionChecked = document.getElementById('checked'),
            unChecked = document.getElementById('unchecked');

            // Set the value of the input element
            commissionId.value = id,
            commissions.value = commissionNumber,
            commissionDate.value = date;
            if (commissionStatus == 1) {
                checked.checked  = true;
            } else {
                unChecked.checked  = true;
            }
        });

        $("#edit_commission_name").on('submit', function (e){
            e.preventDefault();
            commissions = document.getElementById('commissions'),
            commissionDate = document.getElementById('commission_date');
            if (commissions.value.trim() == '') {
                $('#editerrorMessage').append('<div class="alert alert-danger alert-dismissible fade show" role="alert">Please add commissions <button type="button" class="close" data-dismiss="alert" aria-label="Close" id="closeerrorMessage"><span aria-hidden="true">&times;</span></button></div>');
                return;
            }

            if (commissionDate.value == '') {
                $('#editerrorMessage').append('<div class="alert alert-danger alert-dismissible fade show" role="alert">Please select date<button type="button" class="close" data-dismiss="alert" aria-label="Close" id="closeerrorMessage"><span aria-hidden="true">&times;</span></button></div>');
                return;
            } 

            var formData = new FormData($('#edit_commission_name')[0]);
            $.ajax({
                type: 'POST',
                url: '{{ route("commissions.edit") }}', // Replace with your actual route name
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if(response.error){
                        // Iterate over each field in the error object
                        var errorMessage = '';
                        if (typeof response.error === 'object') {
                            // Iterate over the errors object
                            $.each(response.error, function (key, value) {
                                errorMessage += value[0] + '<br>';
                            });
                        } else {
                            errorMessage = response.error;
                        }

                        $('#editerrorMessage').text('');
                        $('#editerrorMessage').append('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+errorMessage+'<button type="button" class="close" data-dismiss="alert" aria-label="Close" id="closeerrorMessage"><span aria-hidden="true">&times;</span></button></div>');
                    }

                    if(response.success){
                        $('#page-loader').hide();
                        $('#editsuccessMessage').html('');
                        $('#editsuccessMessage').append('<div class="alert alert-success alert-dismissible fade show" role="alert">' + response.success + '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                        commissionDatatable.ajax.reload();   
                    }
                },
                error: function(xhr, status, error) {
                    // Handle error response
                    // console.error(xhr.responseText);
                    const errorresponse = JSON.parse(xhr.responseText);
                    $('#editerrorMessage').text(errorresponse.error);
                    $('#editerrorMessage').css('display','block');
                    setTimeout(function () {
                        $('#editerrorMessage').fadeOut();
                    }, 5000);
                }
            });
        });
    });
</script>
@endsection