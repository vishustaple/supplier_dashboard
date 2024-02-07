<!-- resources/views/excel-import.blade.php -->


@extends('layout.app')

 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => 'view Data'])
    <div id="layoutSidenav_content">
        <h3 class="mb-0 ps-2">View Detail</h3>
        <div class="row align-items-end border-bottom pb-3 mb-4">
            <div class="col-md-12 mb-0 text-end">
            <a href="{{ route('report.back') }}" class="btn btn-secondary">Back</a>

            </div>
        </div>
       
        <div class="container">

                    @if (!empty($orders))
                    <!-- @foreach ($orders as $order) -->
                    <div class="row_details">
                    
                    <p><b>Customer Number</b>: {{ $order->customer_number }}</p>
                    <p><b>Customer Name</b>: {{ $order->customer_name??'null' }}</p>
                    <p><b>Supplier Name</b>: {{ $order->supplier_name }}</p>
                    <p><b>Amount</b>: {{ $order->amount }}</p>
                    <p><b>Date</b>: {{ $order->date }}</p>
                    <p><b>Internal Reporting Name</b>: {{ $order->internal_reporting_name ?? 'null' }}</p>
                    <p><b>QBR</b>: {{ $order->qbr ??'null' }}</p>
                    <p><b>Supplier Acc Rep</b>: {{ $order->supplier_acct_rep??'null' }}</p>
                    <p><b>Mangement Fee</b>: {{ $order->management_fee??'null'  }}</p>
                    <p><b>Record type </b>: {{ $order->record_type ??'null' }}</p>
                    <p><b>CPG Sales Representative </b>: {{ $order->cpg_sales_representative??'null'  }}</p>
                    <p><b>CPG Customer Service Rep </b>: {{ $order->cpg_customer_service_rep??'null'  }}</p>
                    <p><b>SF Cat</b>: {{ $order->sf_cat??'null'  }}</p>
                    <p><b>Rebate Freq</b>: {{ $order->rebate_freq	??'null'  }}</p>
                    <p><b>Member Rebate</b>: {{ $order->member_rebate??'null'  }}</p>
                    <p><b>Comm Rate</b>: {{ $order->comm_rate??'null'  }}</p>
                    
                    </div>
                    <!-- @endforeach -->
                    @endif

        </div>
        
    </div>
</div>
<script>
 
</script>

@endsection