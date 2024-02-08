<!-- resources/views/excel-import.blade.php -->


@extends('layout.app')

 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => 'view Data'])
    <div id="layoutSidenav_content">
        <h3 class="mb-0 ps-2">View Detail</h3>
        <div class="row align-items-end border-bottom pb-3 mb-4">
            <div class="col-md-12 mb-0 text-end">
            @if (isset($orders) && !empty($orders))
            <a href="{{ route('report.back') }}" class="btn btn-secondary">Back</a>
            @endif
            @if (isset($account) && !empty($account))
            <a href="{{ route('account') }}" class="btn btn-secondary">Back</a>
            @endif
            <!-- Add redirect conditions here -->
            </div>
        </div>
       
        <div class="container">
            @if (isset($orders) && !empty($orders))
                <div class="row_details">
                    <p><b>Customer Number</b>: {{ $orders->customer_number }}</p>
                    <p><b>Customer Name</b>: {{ $orders->customer_name??'null' }}</p>
                    <p><b>Supplier Name</b>: {{ $orders->supplier_name }}</p>
                    <p><b>Amount</b>: {{ '$'.$orders->amount }}</p>
                    <p><b>Date</b>: {{ date_format(date_create($data->date), 'm/d/Y') }}</p>
                    <p><b>Internal Reporting Name</b>: {{ $orders->internal_reporting_name ?? 'null' }}</p>
                    <p><b>QBR</b>: {{ $orders->qbr ??'null' }}</p>
                    <p><b>Supplier Acc Rep</b>: {{ $orders->supplier_acct_rep??'null' }}</p>
                    <p><b>Mangement Fee</b>: {{ $orders->management_fee??'null'  }}</p>
                    <p><b>Record type </b>: {{ $orders->record_type ??'null' }}</p>
                    <p><b>CPG Sales Representative </b>: {{ $orders->cpg_sales_representative??'null'  }}</p>
                    <p><b>CPG Customer Service Rep </b>: {{ $orders->cpg_customer_service_rep??'null'  }}</p>
                    <p><b>SF Cat</b>: {{ $orders->sf_cat??'null'  }}</p>
                    <p><b>Rebate Freq</b>: {{ $orders->rebate_freq	??'null'  }}</p>
                    <p><b>Member Rebate</b>: {{ $orders->member_rebate??'null'  }}</p>
                    <p><b>Comm Rate</b>: {{ $orders->comm_rate??'null'  }}</p>
                </div>
            @endif

            @if (isset($account) && !empty($account))
                <div class="row_details">
                    <p><b>Qbr</b>: {{ $account->qbr }}</p>
                    <p><b>Customer Name</b>: {{ $account->alies}}</p>
                    <p><b>Sf Cat</b>: {{ $account->sf_cat }}</p>
                    <p><b>Comm Rate</b>: {{ $account->comm_rate }}</p>
                    <p><b>Spend Name</b>: {{ $account->spend_name}}</p>
                    <p><b>Rebate Freq</b>: {{ $account->rebate_freq }}</p>
                    <p><b>Record Type</b>: {{ $account->record_type}}</p>
                    <p><b>Account Name</b>: {{ $account->account_name }}</p>
                    <p><b>Member Rebate</b>: {{ $account->member_rebate }}</p>
                    <p><b>Temp End Date</b>: {{  date_format(date_create($account->temp_end_date), 'm/d/Y') }}</p>
                    <p><b>Volume Rebate</b>: {{ $account->volume_rebate}}</p>
                    <p><b>Management Fee</b>: {{ $account->management_fee }}</p>
                    <p><b>Customer Number</b>: {{ $account->customer_number}}</p>
                    <p><b>Temp Active Date</b>: {{  date_format(date_create($account->temp_active_date), 'm/d/Y')}}</p>
                    <p><b>Category Supplier</b>: {{ $account->category_supplier}}</p>
                    <p><b>Supplier Acct Rep</b>: {{ $account->supplier_acct_rep }}</p>
                    <p><b>Parent Name</b>: {{ $account->parent_name }}</p>
                    <p><b>Grand Parent Name</b>: {{ $account->grand_parent_name }}</p>
                    <p><b>Sales Representative</b>: {{ $account->sales_representative }}</p>
                    <p><b>Internal Reporting Name</b>: {{ $account->internal_reporting_name }}</p>
                    <p><b>Cpg Sales Representative</b>: {{ $account->cpg_sales_representative}}</p>
                    <p><b>Cpg Customer Service Rep</b>: {{ $account->cpg_customer_service_rep}}</p>
                    <p><b>Customer Service Representative</b>: {{ $account->customer_service_representative}}</p>
                </div>
            @endif

            @if (isset($catalog) && !empty($catalog))
                @foreach ($catalog as $order)
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
                @endforeach
            @endif
        </div>
    </div>
</div>
<script>
 
</script>

@endsection