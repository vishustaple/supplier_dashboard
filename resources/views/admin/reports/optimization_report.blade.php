@extends('layout.app', ['pageTitleCheck' => $pageTitle])
 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
    <div id="layoutSidenav_content">
        <div class="container">
            <div class="m-1 mb-2 d-md-flex align-items-center justify-content-between">
                <h3 class="mb-0 ">{{ $pageTitle }}</h3>
            </div>
            <form  id="import_form"  enctype="multipart/form-data">
                @csrf
                <div class="row align-items-end py-3 border-top border-bottom mb-3">
                    <div class="form-group col-md-4 mb-0">
                        <label for="selectBox">Select Account:</label>
                        <input type="hidden" class="count" value="1"><select class="mySelectAccountNames mySelectAccountName form-control-sm" name="account_name" required><option value="">Select</option></select>
                        </div>
                        <div class="form-group relative col-md-3 mb-0">  
                            <label for="enddate">Select Year:</label>
                            <select class="form-control" name="year" id="year" required>
                                <option value="">--Select--</option>
                                @for ($year = 2010; $year <= date('Y'); $year++)
                                    <option value="{{$year}}">{{$year}}</option>
                                @endfor
                            </select>
                        </div>

                    <div class="col-md-4 mb-0">
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <button id="downloadCsvBtn" class="btn-success btn m-1 d-none" title="Csv Download"><i class="fa-solid me-2 fa-file-csv"></i>Download</button>
                    </div>
                    <!-- Button trigger modal -->
                </div>
               
            </form>
            <style>  
            #select2-account_name-f1-container {
            width: 100%;
            }
            .select2-selection.select2-selection--single {
            height: 38px !important;
            padding: 4px;
            }
            .select2.select2-container.select2-container--default {
            width: 100% !important;
            }
            </style>
            <style>
                .quarter_card_row {
                    display: flex;
                }

                .quarter_card_row .quarter_card {
                    width: 50%;
                    border: 1px solid;
                    text-align: center;
                }

                .quarter_card_row .quarter_card h3 ,.h3_title{
                    font-size: 15px;
                    background: #ebc645;
                    padding: 5px;
                }
                .quarter_card_row .quarter_card span{
                padding-bottom: 20px;
                display: block;
                }
                .chart3 h3,.chart_4 h3{
                font-size: 15px;
                padding: 5px;
                }
                table{
                width: 100%;
                }
                .table_qaurter table thead tr th{
                border-bottom: 1px solid #000;
                }
                table tbody tr td:first-child{
                border-right: 1px solid #000;
                }
                .total_tr td{
                font-weight: bold;
                }
                .table_head{
                background-color: #ebc645;
                display: flex;
                justify-content: space-between;
                padding: 5px;
                font-size: 12px;
                }
                .row{
                margin: 0px;
                }
                td,th{
                font-size: 12px;
                text-align: center;
                }
                .row.table_3{
                height: 100%;
                }
                .table_llc {
                    max-height: 330px;
                    overflow: auto;
                }
                tbody tr:nth-child(even) td{
                background-color: #5c5c5c4a;
                }
                ::-webkit-scrollbar {
                width: 5px;
                }

                /* Track */
                .table_llc::-webkit-scrollbar-track {
                background: #f1f1f1; 
                }
                
                /* Handle */
                .table_llc::-webkit-scrollbar-thumb {
                background: #afaeac; 
                border-radius: 5px;
                }

                /* Handle on hover */
                .table_llc::-webkit-scrollbar-thumb:hover {
                background: #afaeac; 
                }
                .flex_between{
                display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                }
                .sticky_head{
                position: sticky;
                    top: 0px;
                    background: #fff;
                }
                .canvasjs-chart-credit {
                    display: none;
                }
                
            </style>
        </head>
    <body>
<main class="pt-5 main_div" style="display:none;">
   <div class="top_row">
    <div class="container-fluid">
     <div class="row">
       <div class="col-md-7 left p-0">
            <div class="row">
               <div class="col-md-6 flex_between  px-0">
                <div class="quarter_card_row ">
                    <div class="quarter_card">
                      <h3>Quater 1 Spend</h3>
                          <h5 class="rate">$157.123</h5>
                          <span>Total Spend</span>
                    </div>
                    <div class="quarter_card">
                        <h3>Quater 2 Spend</h3>
                            <h5 class="rate">$157.123</h5>
                            <span>Total Spend</span>
                      </div>
                </div>
                <div class="quarter_card_row">
                    <div class="quarter_card">
                      <h3>Quater 1 Spend</h3>
                          <h5 class="rate">$157.123</h5>
                          <span>Total Spend</span>
                    </div>
                    <div class="quarter_card">
                        <h3>Quater 2 Spend</h3>
                            <h5 class="rate">$157.123</h5>
                            <span>Total Spend</span>
                      </div>
                </div>
               </div>
               <div class="col-md-6 px-0 border border-dark">
                      <div class="chart_quater text-center">
                        <h3 class="h3_title ">Total Spend By Quarter</h3>
                        <!-- <img src="images/chartq.png" alt=""> -->
                        <div id="chartContainer" style="height: 200px; max-width: 800px; margin: 0px auto;"></div>
                      </div>
               </div>
            </div>
       </div>
       <div class="col-md-5 p-0 right border border-dark">
               <div class="chart3">
                   <h3>Total Spend by Quarter and Year</h3>
                   <div id="chartContainer1" style="height: 200px; width: 90%;"></div>
               </div>
       </div>
     </div>
    </div>
   </div>

   <div class="table_data_section">
    <div class="container-fluid">
    <div class="row">
     <div class="col-md-5 left p-0 border border-dark">
     <div class="table_qaurter">
        <table>
            <thead>
                <tr>
                    <th>Quarter</th>
                    <th>2021</th>
                    <th>2022</th>
                    <th>2023</th>
                    <th>2024</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>qtr1</td>
                    <td>$56786</td>
                    <td>$56786</td>
                    <td>$56786</td>
                    <td>$56786</td>
                    <td>$56786</td>
                </tr>
                <tr>
                    <td>qtr1</td>
                    <td>$56786</td>
                    <td>$56786</td>
                    <td>$56786</td>
                    <td>$56786</td>
                    <td>$56786</td>
                </tr>
                <tr>
                    <td>qtr1</td>
                    <td>$56786</td>
                    <td>$56786</td>
                    <td>$56786</td>
                    <td>$56786</td>
                    <td>$56786</td>
                </tr>
                <tr>
                    <td>qtr1</td>
                    <td>$56786</td>
                    <td>$56786</td>
                    <td>$56786</td>
                    <td>$56786</td>
                    <td>$56786</td>
                </tr>
                <tr class="total_tr">
                    <td>Total</td>
                    <td>$56786</td>
                    <td>$56786</td>
                    <td>$56786</td>
                    <td>$56786</td>
                    <td>$56786</td>
                </tr>
            </tbody>
        </table>
     </div>

       
       <div class="chart_4 border border-dark">
         <h3 class="">Total Spend by Quarter and Year</h3>
         <div id="chartContainer11" style="height: 200px; width: 100%;"></div>
         <!-- <img src="images/chart3.png" alt=""> -->
       </div>
     </div>
     <div class="col-md-7 right px-0 border border-dark">
      <div class="row table_3">
        <div class="col-md-6 px-0 border-end border-dark">
         <div class="table_head">
            <b class="selecter_name">WHATABRANDS LLC</b>
            <span>spend by user</span>
         </div>
        <div class="table_llc">
           <table>
            <thead class="sticky_head">
              <tr>
                <th>Year</th>
                <th colspan="2">2023</th>
              </tr>
              <tr>
                <th class="border-bottom border-dark">Contact Name</th>
                <th class="border-bottom border-dark">Total Spend</th>
                <th class="border-bottom border-dark">OD Core Ratio</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>JULLIANN TORES</td>
                <td>$42.3543</td>
                <td>21.5%</td>
              </tr>
              <tr>
                <td>JULLIANN TORES</td>
                <td>$42.3543</td>
                <td>21.5%</td>
              </tr>
              <tr>
                <td>JULLIANN TORES</td>
                <td>$42.3543</td>
                <td>21.5%</td>
              </tr>
              <tr>
                <td>JULLIANN TORES</td>
                <td>$42.3543</td>
                <td>21.5%</td>
              </tr>
              <tr>
                <td>JULLIANN TORES</td>
                <td>$42.3543</td>
                <td>21.5%</td>
              </tr>
              <tr>
                <td>JULLIANN TORES</td>
                <td>$42.3543</td>
                <td>21.5%</td>
              </tr>
              <tr>
                <td>JULLIANN TORES</td>
                <td>$42.3543</td>
                <td>21.5%</td>
              </tr>
              <tr>
                <td>JULLIANN TORES</td>
                <td>$42.3543</td>
                <td>21.5%</td>
              </tr>
              <tr>
                <td>JULLIANN TORES</td>
                <td>$42.3543</td>
                <td>21.5%</td>
              </tr>
              <tr>
                <td>JULLIANN TORES</td>
                <td>$42.3543</td>
                <td>21.5%</td>
              </tr>
              <tr>
                <td>JULLIANN TORES</td>
                <td>$42.3543</td>
                <td>21.5%</td>
              </tr>
              <tr>
                <td>JULLIANN TORES</td>
                <td>$42.3543</td>
                <td>21.5%</td>
              </tr>
              <tr>
                <td>JULLIANN TORES</td>
                <td>$42.3543</td>
                <td>21.5%</td>
              </tr>
              <tr>
                <td>JULLIANN TORES</td>
                <td>$42.3543</td>
                <td>21.5%</td>
              </tr>
              <tr>
                <td>JULLIANN TORES</td>
                <td>$42.3543</td>
                <td>21.5%</td>
              </tr>
              <tr>
                <td>JULLIANN TORES</td>
                <td>$42.3543</td>
                <td>21.5%</td>
              </tr>
              <tr>
                <td>JULLIANN TORES</td>
                <td>$42.3543</td>
                <td>21.5%</td>
              </tr>
              <tr>
                <td>JULLIANN TORES</td>
                <td>$42.3543</td>
                <td>21.5%</td>
              </tr>
              <tr>
                <td>JULLIANN TORES</td>
                <td>$42.3543</td>
                <td>21.5%</td>
              </tr>
            </tbody>
           </table>
        </div>
        </div>
        <div class="col-md-6 px-0">
          <div class="table_head">
            <b class="selecter_name">WHATABRANDS LLC</b>
            <span>Top Purchased Items</span>
         </div>
         <div class="table_llc">
          <table>
           <thead class="sticky_head">
             <tr>
               <th class="text-left">Year</th>
               <th colspan="3">2023</th>
             </tr>
             <tr>
               <th class="border-bottom border-dark">SKU</th>
               <th class="border-bottom border-dark">Description</th>
               <th class="border-bottom border-dark">QTY</th>
               <th class="border-bottom border-dark">Total Spend</th>
             </tr>
           </thead>
           <tbody>
            <tr>
              <td>2432</td>
              <td>paper sdfkjdslkzdlknv</td>
              <td>2433</td>
              <td>$43543</td>
            </tr>
            <tr>
              <td>2432</td>
              <td>paper sdfkjdslkzdlknv</td>
              <td>2433</td>
              <td>$43543</td>
            </tr>
            <tr>
              <td>2432</td>
              <td>paper sdfkjdslkzdlknv</td>
              <td>2433</td>
              <td>$43543</td>
            </tr>
            <tr>
              <td>2432</td>
              <td>paper sdfkjdslkzdlknv</td>
              <td>2433</td>
              <td>$43543</td>
            </tr>
            <tr>
              <td>2432</td>
              <td>paper sdfkjdslkzdlknv</td>
              <td>2433</td>
              <td>$43543</td>
            </tr>
            <tr>
              <td>2432</td>
              <td>paper sdfkjdslkzdlknv</td>
              <td>2433</td>
              <td>$43543</td>
            </tr>
            <tr>
              <td>2432</td>
              <td>paper sdfkjdslkzdlknv</td>
              <td>2433</td>
              <td>$43543</td>
            </tr>
            <tr>
              <td>2432</td>
              <td>paper sdfkjdslkzdlknv</td>
              <td>2433</td>
              <td>$43543</td>
            </tr>
            <tr>
              <td>2432</td>
              <td>paper sdfkjdslkzdlknv</td>
              <td>2433</td>
              <td>$43543</td>
            </tr>
            <tr>
              <td>2432</td>
              <td>paper sdfkjdslkzdlknv</td>
              <td>2433</td>
              <td>$43543</td>
            </tr>
            <tr>
              <td>2432</td>
              <td>paper sdfkjdslkzdlknv</td>
              <td>2433</td>
              <td>$43543</td>
            </tr>
            <tr>
              <td>2432</td>
              <td>paper sdfkjdslkzdlknv</td>
              <td>2433</td>
              <td>$43543</td>
            </tr>
            <tr>
              <td>2432</td>
              <td>paper sdfkjdslkzdlknv</td>
              <td>2433</td>
              <td>$43543</td>
            </tr>
            <tr>
              <td>2432</td>
              <td>paper sdfkjdslkzdlknv</td>
              <td>2433</td>
              <td>$43543</td>
            </tr>
            <tr>
              <td>2432</td>
              <td>paper sdfkjdslkzdlknv</td>
              <td>2433</td>
              <td>$43543</td>
            </tr>
            <tr>
              <td>2432</td>
              <td>paper sdfkjdslkzdlknv</td>
              <td>2433</td>
              <td>$43543</td>
            </tr>
            <tr>
              <td>2432</td>
              <td>paper sdfkjdslkzdlknv</td>
              <td>2433</td>
              <td>$43543</td>
            </tr>
            <tr>
              <td>2432</td>
              <td>paper sdfkjdslkzdlknv</td>
              <td>2433</td>
              <td>$43543</td>
            </tr>
            <tr>
              <td>2432</td>
              <td>paper sdfkjdslkzdlknv</td>
              <td>2433</td>
              <td>$43543</td>
            </tr>
            <tr>
              <td>2432</td>
              <td>paper sdfkjdslkzdlknv</td>
              <td>2433</td>
              <td>$43543</td>
            </tr>
            <tr>
              <td>2432</td>
              <td>paper sdfkjdslkzdlknv</td>
              <td>2433</td>
              <td>$43543</td>
            </tr>
           </tbody>
          </table>
       </div>
        </div>
      </div>
     
     </div>
    </div>
   </div>
  </div>
  <div class="logo py-5">
    <div class="container-fluid text-end">
    <img src="https://sql.centerpointgroup.com/images/logo-1.webp"  alt="">
    </div>
  </div>
</main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  </body>
  <script src="https://canvasjs.com/assets/script/jquery-1.11.1.min.js"></script>
  <script src="https://canvasjs.com/assets/script/jquery.canvasjs.min.js"></script>
  <!-- <script src="https://cdn.jsdelivr.net/npm/es6-promise/dist/es6-promise.auto.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.4.1/html2canvas.min.js"></script> -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  
        </div>
        
    </div>
</div>
<script>

    function as() {
    
    var options = {
      animationEnabled: true,
      title: {
        // text: "ACME Corporation Apparel Sales"
      },
      data: [{
        type: "doughnut",
        innerRadius: "0%",
        outerRadius: "70%", // Adjust the outer radius to your liking        // showInLegend: true,
        legendText: "{label}",
        // indexLabel: "{label}: #percent%",
        dataPoints: [
          { color: "#ebc645", label: "Qtr 2", y: 25 },
          { color: "black", label: "Qtr 1", y: 25 },
          { color: "#fef3ca", label: "Qtr 3", y: 25 },
          { color: "#afaeac", label: "Qtr 4", y: 25 },
        ]
      }]
    };
    $("#chartContainer").CanvasJSChart(options);
      // chart.render();
    }

    function ad(year='') {
        if (year !== '') {
            var year1 = year - 1, year2 = year1 - 1;
        }
    var chart = new CanvasJS.Chart("chartContainer1",
    {
        title:{
            // text: "A Multi-series Column Chart"
        },
        axisY:{    
            
            valueFormatString:  "#,##0.##", // move comma to change formatting
            prefix: "$"
        },
            markerType: "triangle",
            markerSize: 18,
            legend: {
            horizontalAlign: "left", // left, center ,right 
            verticalAlign: "top",  // top, center, bottom
        },   
        data: [
            {        
                type: "column",
                showInLegend: true, 
                name: "series1",
                color: "#afaeac",
                legendText: String(year2),
                dataPoints: [
                    { color: "#afaeac", label: "Qtr 1", x: 10, y: 110 },
                ]
            },
            {        
                type: "column",
                showInLegend: true, 
                name: "series2",
                color: "black",
                legendText: String(year1),
                dataPoints: [
                    { color: "black", label: "Qtr 1",  x: 10, y: 150 },
                    { color: "#afaeac", label: "Qtr 2", x: 20, y: 120},
                    { color: "#afaeac", label: "Qtr 3", x: 30, y: 130 },
                    { color: "#afaeac", label: "Qtr 3", x: 40, y: 120 },
                ]
            },
            {        
                type: "column",
                showInLegend: true, 
                name: "series3",
                color: "#ebc645",
                legendText: String(year),
                dataPoints: [
                    { color: "#ebc645", label: "Qtr 1",  x: 10, y: 100},
                    { color: "black", label: "Qtr 2", x: 20, y: 140},
                    { color: "black", label: "Qtr 3", x: 30, y: 140 },
                    { color: "black", label: "Qtr 4", x: 40, y: 130 },
                ]
            },    
        ]
    });

    chart.render();
  }

  function add(year='') {
    if (year !== '') {
            var year1 = year - 1, year2 = year1 - 1;
        }
    var chart1 = new CanvasJS.Chart("chartContainer11", {
        title:{
            // text: "A Multi-series Column Chart"
        },
        axisY:{    
            valueFormatString:  "#,##0.##", // move comma to change formatting
            prefix: "$",
            labelFontSize: 10,
        },
        axisX: {
            interval: 10,
            labelAngle: -30,
            labelFontSize: 10,
        },
        markerType: "triangle",
        markerSize: 18,
        legend: {
            horizontalAlign: "left", // left, center ,right 
            verticalAlign: "top",  // top, center, bottom
        },   
        data: [
            {        
                type: "column",
                showInLegend: true, 
                name: "series1",
                color: "#afaeac",
                legendText: String(year2),
                dataPoints: [
                    { color: "#afaeac", label: "January", x: 10, y: 110 },
                    { color: "#afaeac", label: "February", x: 20, y: 120 },
                ]
            },
            {        
                type: "column",
                showInLegend: true, 
                name: "series2",
                color: "black",
                legendText: String(year1),
                dataPoints: [
                    { color: "black", label: "January", x: 10, y: 150 },
                    { color: "black", label: "February", x: 20, y: 120},
                    { color: "#afaeac", label: "March", x: 30, y: 130 },
                    { color: "#afaeac", label: "April", x: 40, y: 120 },
                    { color: "#afaeac", label: "May", x: 50, y: 130 },
                    { color: "#afaeac", label: "June", x: 60, y: 140 },
                    { color: "#afaeac", label: "July", x: 70, y: 150 },
                    { color: "#afaeac", label: "August", x: 80, y: 160 },
                    { color: "#afaeac", label: "September", x: 90, y: 170 },
                    { color: "#afaeac", label: "October", x: 100, y: 180 },
                    { color: "#afaeac", label: "November", x: 110, y: 190 },
                    { color: "#afaeac", label: "December", x: 120, y: 200 },
                ]
            },
            {        
                type: "column",
                showInLegend: true, 
                name: "series3",
                color: "#ebc645",
                legendText: String(year),
                dataPoints: [
                    { color: "#ebc645", label: "January", x: 10, y: 100},
                    { color: "#ebc645", label: "February", x: 20, y: 140},
                    { color: "black", label: "March", x: 30, y: 210 },
                    { color: "black", label: "April", x: 40, y: 190 },
                    { color: "black", label: "May", x: 50, y: 140 },
                    { color: "black", label: "June", x: 60, y: 150 },
                    { color: "black", label: "July", x: 70, y: 160 },
                    { color: "black", label: "August", x: 80, y: 170 },
                    { color: "black", label: "September", x: 90, y: 150 },
                    { color: "black", label: "October", x: 100, y: 130 },
                    { color: "black", label: "November", x: 110, y: 100 },
                    { color: "black", label: "December", x: 120, y: 210 },
                ]
            },    
        ]
    });

    chart1.render();
    // // Use html2canvas to capture the chart as an image
    // html2canvas(document.getElementById("chartContainer11")).then(function(canvas) {
    //     var imgData = canvas.toDataURL('image/png');

    //     // Create a new jsPDF instance
    //     var pdf = new jsPDF('p', 'pt', 'a4');

    //     // Add the image to the PDF
    //     pdf.addImage(imgData, 'PNG', 10, 10, 500, 300);

    //     // Save or display the PDF
    //     pdf.save('chart.pdf');
    // });
}



$('#import_form').on('submit', function () {
    event.preventDefault();
    // Initiate DataTable AJAX request
    $('.main_div').css('display','block');
    var selectedValue = $('.mySelectAccountName').val();
    var selectedOption = $('.mySelectAccountName').find('option[value="' + selectedValue + '"]');
    var selectedText = selectedOption.text();
    $('.selecter_name').text(selectedText);
    as()
    ad($('#year').val())
    add($('#year').val())
    // $('.main_div').removeClass('d-none');
    // $('#commission_report_data').DataTable().ajax.reload();
    // $('#commission_report_data1').DataTable().ajax.reload();
});

$(document).on('click', '#commission_report_data tbody #downloadCsvBtn', function() {
    // Trigger CSV download
    downloadCsv($(this).data('id'));
});

function downloadCsv(id='') {
    // You can customize this URL to match your backend route for CSV download
    var csvUrl = '{{ route("report.export-commission_report-csv") }}',
    order = supplierDataTable.order();

    // Add query parameters for date range and supplier ID
    csvUrl += '?year=' + $('#year').val() + '&quarter=' + $('#quarter').val() + '&sales_rep=' + $('#sales_rep').val() + '&column=' + order[0][0] + '&order=' + order[0][1] + '&commission_rebate_id=' + id + '&supplier=' + $('#supplier').val();

    // Open a new window to download the CSV file
    window.open(csvUrl, '_blank');
} 

        $('.mySelectAccountName').select2({
            ajax: {
                url: "{{ route('commissions.customerSearch') }}",
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term // search term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            minimumInputLength: 1
        });
    // // }
    // selectCustomer()

</script>
@endsection