<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Commission Report</title>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap">
        <style>
            @page {
                footer: html_myfooter;
                margin-bottom: 50px;
            }
 
            .footer {
                width: 100%;
                text-align: right;
                font-size: 12px;
                padding-right: 20px;
            }

            body {
                padding: 15px;
                font-family: "Roboto", sans-serif;
            }
         *{
            box-sizing: border-box;
         }
            .page-break {
                page-break-after: always;
            }

            .clearfix {
                overflow: auto;
            }

            .clearfix::after {
                content: "";
                clear: both;
                display: table;
            }

            table {
                width: 100%;
                border: 3px solid #000;
                margin-bottom: 50px;
                border-spacing: 0px;
            }

            th {
                font-style: italic;
            }

            .border-top {
                border-top: 2px solid #000;
            }

            .border-bottom {
                border-bottom: 2px solid #000;
            }

            .border-right {
                border-right: 2px solid #000;
            }

            .border-left {
                border-left: 2px solid #000;
            }

            th, td {
                padding: 2px 10px;
                text-align: center;
            }

            .container {
                max-width: 800px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="pdf_header clearfix">
                <div class="logo_block">
                    <img src="{{ public_path('/images') .'/'. 'logo.jpg'}}" alt="">
                </div>
            </div>
            <div class="detail_top_pdf clearfix" style="margin: 35px 0px 10px 0px;">
                <div class="left_commission" style="width: 65%;float:left;">
                    <p style="padding-top:4px;margin: 0px;padding-bottom: 5px;font-size:13px;"><b>Commission Statement for period: {{explode(' ', $commission_statement_text)[0]}} to </b><b>{{explode(' ', $commission_statement_text)[1]}} {{$year}}</b></p>
                    <p style="margin: 0px;padding-bottom: 5px;font-size:13px;"><b>Agent Name: {{ $sales_rep }}</b></p>
                </div>
                <div class="right_quarter" style="width: 35%;float:left;margin-top: 0px;">
                    <div style="padding-left: 10px;">
                    @if(isset($quarter1) && $quarter1 != 0)
                        <p style="margin:2px 0px; font-size:13px;">Quarter 1 Commission <b>${{ $quarter1 }}</b></p>
                    @endif
                    @if(isset($quarter2) && $quarter2 != 0)
                        <p style="margin:2px 0px; font-size:13px;">Quarter 2 Commission <b>${{ $quarter2 }}</b></p>
                    @endif
                    @if(isset($quarter3) && $quarter3 != 0)
                        <p style="margin:2px 0px; font-size:13px;">Quarter 3 Commission <b>${{ $quarter3 }}</b></p>
                    @endif
                    @if(isset($quarter4) && $quarter4 != 0)
                        <p style="margin:2px 0px; font-size:13px;">Quarter 4 Commission <b>${{ $quarter4 }}</b></p>
                    @endif
                </div>
                </div>
            </div>
            <div class="generated_date" style="padding-bottom: 20px;"><b>Generated on: {{ now()->format('m/d/Y') }}</b></div>
            <div class="table_wrapper">
                <table>
                    <thead>
                        <tr>
                            <th class="border-bottom">Account Name <br>Commissionable Period</th>
                            <th class="border-bottom">Month / Year</th>
                            <th class="border-bottom">Customer <br>
                                Spend</th>
                            <th class="border-bottom">Management <br>
                                Fee</th>
                            <th class="border-bottom">Commission <br>
                                Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($commission_data))
                        @php $commission_total1 = 0; @endphp
                            @foreach($commission_data as $key1 => $commissionss)
                                <tr class="subtotal" style="background-color: #d0d0d0;">
                                    <th colspan="2" class="border-bottom border-top" style="text-align: center;background-color: #d0d0d0;">{{ $key1 }}</th>
                                    <th class="border-bottom border-top"  colspan="3"></th>
                                </tr>
                                @php $cost = $rebate = $commission_total = 0; @endphp
                                @foreach($commissionss as $key2 => $commissions)
                                    <?php 
                                        $cost += (float)str_replace(',', '', $commissions['total_amount']);
                                        $rebate += (float)str_replace(',', '', $commissions['total_volume_rebate']);
                                        $commission_total += (float)str_replace(',', '', $commissions['total_commissions']);
                                        $commission_total1 += (float)str_replace(',', '', $commissions['total_commissions']);
                                        // Remove 'YTD' key from the array
                                        $filteredArray = array_filter($commissions['month'], function($value, $key) {
                                            return $key !== 'YTD';
                                        }, ARRAY_FILTER_USE_BOTH);

                                        // Count non-zero values in the filtered array
                                        $nonZeroCount = count(array_filter($filteredArray, function($value) {
                                            return $value != 0;
                                        }));
                                        $firstTime = true;
                                    ?>
                                    @foreach($commissions['month'] as $key => $month)
                                        @if(!empty($commissions['month_amount'][$key]) && $key != 'YTD')
                                            <tr>
                                                @if($firstTime == true)
                                                    @php $firstTime = false; @endphp
                                                    <td rowspan="{{$nonZeroCount + 1}}" style="text-align: center;" class="border-right border-bottom">{{ $commissions['supplier'] }} <br>
                                                    {{ $commissions['commission_start_date'] }} - {{ $commissions['commission_end_date'] }}</td>
                                                @endif
                                                <td>{{ $key }} {{ $year }}</td>
                                                <td>${{ number_format($commissions['month_amount'][$key], 2) }}</td>
                                                <td>${{ number_format($commissions['month_rebate'][$key], 2) }}</td>
                                                <td>${{ number_format($commissions['month'][$key], 2) }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                    <tr style="background-color: #f2f2f2;">
                                        <th class="border-top border-bottom">{{ $commissions['supplier'] }} Subtotal</th>
                                        <th class="border-top border-bottom">${{ number_format($commissions['total_amount'], 2) }}</th>
                                        <th class="border-top border-bottom">${{ number_format($commissions['total_volume_rebate'], 2) }}</th>
                                        <th class="border-top border-bottom">${{ number_format($commissions['total_commissions'], 2) }}</th>
                                    </tr>
                                @endforeach
                                <tr class="subtotal" style="background-color: #f2f2f2;">
                                    <th class="border-top border-bottom" colspan="2">{{ $commissions['account_name'] }} Subtotal</th>
                                    <th class="border-top border-bottom">${{ number_format($cost, 2) }}</th>
                                    <th class="border-top border-bottom">${{ number_format($rebate, 2) }}</th>
                                    <th class="border-top border-bottom">${{ number_format($commission_total, 2); }}</th>
                                </tr>
                            @endforeach
                        @endif
                        <tr class="grand_total">
                            <th colspan="4" style="padding: 5px 10px; background-color: #000; color: #fff;">Grand Total</th>
                            <th style="padding: 5px 10px; background-color: #000; color: #fff;">$ {{ number_format($commission_total1, 2) }}</th>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <htmlpagefooter name="myfooter">
            <div style="width: 100%; border: none;">
                <table style="width: 100%; border-collapse: collapse; border: none; margin-bottom: 0;">
                    <tr>
                        <td style="text-align: left; vertical-align: middle; border: none;">
                            <p style="margin: 0; display: flex; align-items: center;">
                                @if ($paid_check == false)
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 448 512" style="margin-right: 5px;">
                                        <path d="M64 80c-8.8 0-16 7.2-16 16V416c0 8.8 7.2 16 16 16H384c8.8 0 16-7.2 16-16V96c0-8.8-7.2-16-16-16H64zM0 96C0 60.7 28.7 32 64 32H384c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96zM337 209L209 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L303 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/>
                                    </svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 448 512" style="margin-right: 5px;">
                                        <path d="M64 80c-8.8 0-16 7.2-16 16V416c0 8.8 7.2 16 16 16H384c8.8 0 16-7.2 16-16V96c0-8.8-7.2-16-16-16H64zM0 96C0 60.7 28.7 32 64 32H384c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96z"/>
                                    </svg>
                                @endif
                                @if($paid_check == false && isset($approved_by) && !empty($approved_by))
                                    Approved by <u>{{$approved_by}}</u>
                                @else
                                    Approved by _________________________
                                @endif
                            </p>
                        </td>
                        <td style="text-align: right; vertical-align: middle; border: none;">
                            <p style="margin: 0;">
                                Page {PAGENO} of {nb}
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </htmlpagefooter>
    </body>
</html>