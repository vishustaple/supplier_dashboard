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

                    @if (isset($orders) && !empty($orders))
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

                     @if (isset($account) && !empty($account))
                    @foreach ($account as $order)
                    <div class="row_details">
 
                    <p><b>Qbr</b>: {{ $order->qbr }}</p>
                    <p><b>Customer Name</b>: {{ $order->alies}}</p>
                    <p><b>Sf Cat</b>: {{ $order->sf_cat }}</p>
                    <p><b>Comm Rate</b>: {{ $order->comm_rate }}</p>
                    <p><b>Spend Name</b>: {{ $order->spend_name}}</p>
                    <p><b>Rebate Freq</b>: {{ $order->rebate_freq }}</p>
                    <p><b>Record Type</b>: {{ $order->record_type}}</p>
                    <p><b>Account Name</b>: {{ $order->account_name }}</p>
                    <p><b>Member Rebate</b>: {{ $order->member_rebate }}</p>
                    <p><b>Temp End Date</b>: {{ $order->temp_end_date }}</p>
                    <p><b>Volume Rebate</b>: {{ $order->volume_rebate}}</p>
                    <p><b>Management Fee</b>: {{ $order->management_fee }}</p>
                    <p><b>Customer Number</b>: {{ $order->customer_number}}</p>
                    <p><b>Temp Active Date</b>: {{ $order->temp_active_date }}</p>
                    <p><b>Category Supplier</b>: {{ $order->category_supplier}}</p>
                    <p><b>Supplier Acct Rep</b>: {{ $order->supplier_acct_rep }}</p>
                    <p><b>Sales Representative</b>: {{ $order->sales_representative }}</p>
                    <p><b>Internal Reporting Name</b>: {{ $order->internal_reporting_name }}</p>
                    <p><b>Cpg Sales Representative</b>: {{ $order->cpg_sales_representative}}</p>
                    <p><b>Cpg Customer Service Rep</b>: {{ $order->cpg_customer_service_rep}}</p>
                    <p><b>Customer Service Representative</b>: {{ $order->customer_service_representative}}</p>
                    
                    </div>
                    <!-- @endforeach -->
                    @endif

        </div>
        
    </div>
</div>
<script>
 
</script>

@endsection