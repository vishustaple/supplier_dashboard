@extends('layout.app')
 @extends('layout.sidenav')
 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
    <div id="layoutSidenav_content">
        <div class="container">
            <div class="m-1 mb-2 d-md-flex align-items-center justify-content-between">
                <h1 class="mb-0 ">{{ $pageTitle }}</h1>
            </div>
            <form  id="import_form"  enctype="multipart/form-data">
                @csrf
                <div class="row align-items-end">
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
                        <input type="hidden" id="start_date" name="start_date" />
                        <input type="hidden" id="end_date" name="end_date" />  
                    </div>
                    <div class="col-md-4 mb-0">
                    <button id="submitBtn" class="btn btn-primary">Submit</button>
                    </div>
                    <!-- Button trigger modal -->
                </div>
               
            </form>
            <table class="data_table_files" id="account_data">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer Number</th>
                        <th>Customer Name</th>
                        <th>Amount</th>
                        <th>Supplier Name</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
        
    </div>
</div>
<!-- Include Date Range Picker JavaScript -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
<script>
    $(document).ready(function() {
        $('input[name="dates"]').daterangepicker();

        // Event handler for when the user applies the date range
        $('input[name="dates"]').on('apply.daterangepicker', function(ev, picker) {
            // Access the selected date range
            $('#start_date').val(picker.startDate.format('MM-DD-YYYY')),
            $('#end_date').val(picker.endDate.format('MM-DD-YYYY'));
            // Perform actions with the selected date range
            console.log('Selected Date Range:', startDate, 'to', endDate);
        });
    // });
        // Button click event
        $('#import_form').on('submit', function () {
            event.preventDefault();
            // Initiate DataTable AJAX request
            $('#account_data').DataTable().ajax.reload();
        });
        // $.ajaxSetup({
        //     headers: {
        //         'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        //     }
        // });

        // DataTable initialization
        $('#account_data').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: '{{ route('report.filter') }}',
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
    columns: [
        { data: 'id', name: 'id' },
        { data: 'customer_number', name: 'customer number' },
        { data: 'customer_name', name: 'customer name' },
        { data: 'supplier_name', name: 'supplier name' },
        { data: 'amount', name: 'amount' },
        { data: 'date', name: 'date' },
        // { data: 'action', name: 'action', orderable: false, searchable: false },
    ],
});


        $('#downloadCsvBtn').on('click', function () {
            // Trigger CSV download
            downloadCsv();
         });

         function downloadCsv() {
            // You can customize this URL to match your backend route for CSV download
            var csvUrl = '{{ route('report.export-csv') }}';

            // Add query parameters for date range and supplier ID
            csvUrl += '?daterange=' + $('#daterange').val() + '&supplierId=' + $('#supplierId').val();

            // Open a new window to download the CSV file
            window.open(csvUrl, '_blank');
         }
    //   });
    });
</script>
@endsection