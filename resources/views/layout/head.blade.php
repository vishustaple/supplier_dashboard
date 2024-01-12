<head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Dashboard - SB Admin</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="{{ asset('/admin/dist/css/styles.css') }}" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <!-- DataTables CSS -->
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">

        <!-- Custom css -->
        <link rel="stylesheet" href="{{asset('css/custom.css')}}">
 <style>
      div#example_wrapper {
      overflow: auto;
      }
      div#example_wrapper table#example thead tr th:nth-child(3){
      width: 150px !important;
      min-width: 150px !important;
      max-width: 150px !important;
      overflow: hidden;
      }
      div#example_wrapper table#example tbody tr td:nth-child(3) {
      width: 150px !important;
      min-width: 150px !important;
      max-width: 150px !important;
      overflow: hidden;
      }
      /* Apply border to the table */
      table.dataTable {
      border-collapse: collapse;
      width: 100%;
      }

      /* Apply border to table cells */
      table.dataTable td,
      table.dataTable th {
      border: 1px solid #ddd; /* Set the border color and style */
      padding: 8px; /* Adjust the cell padding */
      }

      /* Optional: Style the table header */
      table.dataTable thead th {
      background-color: #f2f2f2; /* Set a background color for the header */
      }

      /* Optional: Style the table hover effect */
      table.dataTable tr:hover {
      background-color: #f5f5f5; /* Set a background color for the hover effect */
      }
 </style>
        <!-- DataTables JS -->
        <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
    </head>