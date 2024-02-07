<?php

namespace App\Http\Controllers;

use League\Csv\Writer;
use App\Models\{Catalog};
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
class CatalogController extends Controller
{
    public function index($catalogType){
        $setPageTitleArray = [
            'catalog' => 'Catalog List',
        ];
        
        return view('admin.'. $catalogType .'', ['pageTitle' => $setPageTitleArray[$catalogType]]);
    }

    public function catalogAjaxFilter(Request $request){
        if ($request->ajax()) {
            $formatuserdata = Catalog::getFilterdCatalogData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function exportCatalogCsv(Request $request){
        /** Retrieve data based on the provided parameters */
        $filter = [];
        $csv = true;

        /** Fetch data using the parameters and transform it into CSV format */
        /** Replace this with your actual data fetching logic */
        $data = Catalog::getFilterdCatalogData($filter, $csv);

        /** Create a stream for output */
        $stream = fopen('php://temp', 'w+');

        /** Create a new CSV writer instance */
        $csvWriter = Writer::createFromStream($stream);
        
        /** Add column headings */
        $csvWriter->insertOne(['Sku', 'Description', 'Supplier Name', 'Price']);

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
        $response->headers->set('Content-Disposition', 'attachment; filename="CatalogData_'.now()->format('YmdHis').'.csv"');
  
        /** return $csvResponse; */
        return $response;
    }
}
