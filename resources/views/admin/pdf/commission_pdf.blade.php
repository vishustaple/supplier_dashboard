<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        /* @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@100&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap');
        body{
            padding: 15px;
            font-family: "Roboto", sans-serif;
        } */

        .clearfix {
            overflow: auto;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        th, td {
            border: 1px solid black;
            border-collapse: collapse;
            border-right: 0px;
        }

        .pdf_header,.pdf_data_details{
            overflow: auto;
            display: table;
            width: 100%;
        }

        .logo_block,.middle_block,.right_block{
            width: 33%;
            float: left;
            text-align: center;
            display: table;
        }

        .logo_block{
            text-align: left;
        }

        .middle_block span,.right_block span{
            display: table-cell;
            vertical-align: bottom;
            height: 54px;
        }

        .details{
            padding-top: 25px;
            width: 40%;
            padding-bottom: 25px;
        }

        .details p{
            font-size: 13px;
            margin: 0px;
            margin-bottom: 5px;
        }

        table{
            width: 100%;
            border-spacing: 0px;
            margin-bottom: 50px;
        }

        th{
            color: #fff;
        }

        th,td{
            font-size: 11px;
        }

        .border-0{
            border: 0px;
            text-align: center;
        }

        tr th:last-child,tr td:last-child{
            border-right: 1px solid #000;
        }

        tr td{
            border-top: 0px;
        }

        .total_tr td:first-child{
            border-top: 1px solid #000;
        }

        .right_sign{
            width: 40%;
            float: right;
        }

        .border-right-0{
            border-right:0px;
            border-left:0px;
            padding-left: 2px;
            padding-right: 0px;
        }

        .border-right-0 + td{
            border-left: 0px;
            padding-left: 2px;
        }

        .text-left{
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="pdf_header clearfix">
     <div class="logo_block">
     <img src="https://sql.centerpointgroup.com/images/logo-1.webp" alt="">
     </div>
     <div class="middle_block">
      <span>Q4 2023</span>
     </div>
     <div class="right_block">
          <span>Amount: $ {{$amount}} </span>
     </div>
    </div>

    <div class="pdf_data_details clearfix">
       <div class="details">
            <p>Agent Name: {{ $sales_rep }}</p>
            <p>Generated on: {{ now()->format('m/d/Y') }}</p>
            @if(isset($quarter1) && $quarter1 != 0)
                <p>Quarter 1 Commission: $ {{ $quarter1 }}</p>
            @endif
            @if(isset($quarter2) && $quarter2 != 0)
                <p>Quarter 2 Commission: $ {{ $quarter2 }}</p>
            @endif
            @if(isset($quarter3) && $quarter3 != 0)
                <p>Quarter 3 Commission: $ {{ $quarter3 }}</p>
            @endif
            @if(isset($quarter4) && $quarter4 != 0)
                <p>Quarter 4 Commission: $ {{ $quarter4 }}</p>
            @endif
            <p>YTD Commission: $ {{ $anual }}</p>
       </div>
    </div>

    <div class="table_responsive">
    <table>
         <thead>
            <tr>
                <th class="border-0"></th>
                <th class="border-0"></th>
                <th class="border-0"></th>
                <th class="border-0"></th>
                <th class="border-0"></th>
                <!-- <th colspan="13" style="background-color: #538dd5;text-align: center;color: #fff;border-bottom: 0px;">Client Spend 2023</th> -->
                <th colspan="13" style="background-color: #538dd5;text-align: center;color: #fff;border-bottom: 0px;">Commission 2023</th>
            </tr>
            <tr style="background-color: #b8cce4;">
                <th>Business Name</th>
                <th>Supplier</th>
                <th>Rate #1</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Jan</th>
                <th>Feb</th>
                <th>Mar</th>
                <th>Apr</th>
                <th>May</th>
                <th>Jun</th>
                <th>Jul</th>
                <th>Aug</th>
                <th>Sep</th>
                <th>Oct</th>
                <th>Nov</th>
                <th>Dec</th>
                <th>YTD</th>
            </tr>
         </thead>
         <tbody>
            @if(isset($commission_data))
                @foreach($commission_data as $key => $commission)
                    <tr>
                        <td class="border-0 text-left">{{ $commission['account_name'] }}</td>
                        <td class="border-0">{{ $commission['supplier'] }}</td>
                        <td class="border-0">{{ $commission['commission'] }}%</td>
                        <td class="border-0">{{ $commission['start_date'] }}</td>
                        <td class="border-0">{{ $commission['end_date'] }}</td>
                        <td class="">$ {{ $month[$key]['January'] }}</td>
                        <td class="border-right-0">$ {{ $month[$key]['February'] }}</td>
                        <td>$ {{ $month[$key]['March'] }}</td>                        
                        <td class="">$ {{ $month[$key]['April'] }}</td>
                        <td class="border-right-0">$ {{ $month[$key]['May'] }}</td>
                        <td>$ {{ $month[$key]['June'] }}</td>
                        <td class="">$ {{ $month[$key]['July'] }}</td>
                        <td class="border-right-0">$ {{ $month[$key]['August'] }}</td>
                        <td>$ {{ $month[$key]['September'] }}</td>
                        <td class="">$ {{ $month[$key]['October'] }}</td>
                        <td class="border-right-0">$ {{ $month[$key]['November'] }}</td>
                        <td>$ {{ $month[$key]['December'] }}</td>
                        <td>$ {{ $month[$key]['YTD'] }}</td>
                    </tr>
                @endforeach
            @endif
            <tr class="total_tr">
               <td colspan="5">Total</td>
               <td class="">$ {{ $January }}</td>
               <td class="border-right-0">$ {{ $February }} </td>
               <td>$ {{ $March }}</td>
               <td class="">$ {{ $April }}</td>
               <td class="border-right-0">$ {{ $May }}</td>
               <td>$ {{ $June }}</td>
               <td class="">$ {{ $July }}</td>
               <td class="border-right-0">$ {{ $August }}</td>
               <td>$ {{ $September }}</td>
               <td class="">$ {{ $October }}</td>
               <td class="border-right-0">$ {{ $November }}</td>
               <td>$ {{ $December }}</td>
               <td>$ {{ $YTD }}</td>
           </tr>
         </tbody>
    </table>
    </div>
    @if($paid_check == false)
        <div class="sign_block clearfix">
        <div class="right_sign">
            <div class="line_sign">
                <span style="padding-bottom: 15px; display: block;">X</span>
                <img width="200px" height="90px"  src="{{ public_path('/excel_sheets') .'/'. 'images.png'}}" alt="">
                <div style="border-bottom: 2px solid #000;margin-bottom: 15px;"></div>
                <p style="text-align: center;">Approved by</p>
            </div>
            <div class="line_sign">
                <span style="padding-bottom: 15px; display: block;">X</span>
                <img width="200px" height="90px" src="{{ public_path('/excel_sheets') .'/'. 'paid.jpg'}}" alt="">
                <div style="border-bottom: 2px solid #000;margin-bottom: 15px;"></div>
                <p style="text-align: center;">Paid on</p>
            </div>
        </div>
        </div>
    @endif
</body>
</html>