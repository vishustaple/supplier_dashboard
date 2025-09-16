<?php

namespace App\Http\Controllers;

use App\Models\Order;
use League\Csv\Writer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CronResourcePage extends Controller
{
    public function __construct() {
        $this->middleware('permission:Cron Resource Page')->only(['index']);
        $this->middleware('permission:Database Log')->only(['queryFilter', 'queryTypeFilter', 'queriesExportCsv']);
    }

    public function index() {
        return view('admin.cron_resource_page', ['pageTitle' => "Cron Resource Page"]);
    }

    public function queryFilter() {
        return view('admin.query_filter', ['pageTitle' => "Database Log"]);
    }

    public function queryTypeFilter(Request $request) {
        if ($request->ajax()) {
            $formatuserdata = Order::getQueryTypeFilteredData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function queriesExportCsv(Request $request) {
        /** Retrieve data based on the provided parameters */
        $filter['end_date'] = $request->input('end_date');
        $filter['order'][0]['dir'] = $request->input('order');
        $filter['start_date'] = $request->input('start_date');
        $filter['query_type'] = $request->input('query_type');
        $filter['order'][0]['column'] = $request->input('column');

        $csv = true;

        /** Fetch data using the parameters and transform it into CSV format */
        /** Replace this with your actual data fetching logic */
        $data = Order::getQueryTypeFilteredData($filter, $csv);

        /** Create a stream for output */
        $stream = fopen('php://temp', 'w+');

        /** Create a new CSV writer instance */
        $csvWriter = Writer::createFromStream($stream);
        
        $heading = $data['heading'];
        unset($data['heading']);

        /** Add column headings */
        $csvWriter->insertOne($heading);

        /** Insert the data into the CSV */
        $csvWriter->insertAll($data);

        /** Rewind the stream pointer */
        rewind($stream);

        /** Create a streamed response with the CSV data */
        $response = new StreamedResponse(function () use ($stream) {
            fpassthru($stream);
        });

        /** Set headers for CSV download */
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="query_file_'.now()->format('YmdHis').'.csv"');
  
        /** return $csvResponse; */
        return $response;
    }
}
