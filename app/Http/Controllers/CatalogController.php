<?php

namespace App\Http\Controllers;

use League\Csv\Writer;
use App\Models\Catalog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CatalogController extends Controller
{
    public function index(Request $request, $catalogType, $id=null){
        if (!isset($id)) {
            $id = $request->query('id');
        }

        $setPageTitleArray = [
            'catalog' => 'Catalog List',
        ];

        if (isset($id)) {
            $catalog = Catalog::query() 
            ->leftJoin('suppliers', 'catalog.supplier_id', '=', 'suppliers.id')
            ->leftJoin('catalog_details', 'catalog.id', '=', 'catalog_details.catalog_id')
            ->where('catalog.id', '=', $id)
            ->whereNotNull('catalog_details.table_value')
            ->select('catalog_details.table_key as key', 'catalog_details.table_value as value', 'catalog.sku as sku','catalog.price as price','suppliers.supplier_name as supplier_name','catalog.description as description')->get()->toArray();
            return view('admin.viewdetail',compact('catalog'));
        }

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
        $filter['search']['value'] = $request->query('search');
        $csv = true;

        /** Fetch data using the parameters and transform it into CSV format */
        /** Replace this with your actual data fetching logic */
        $data = Catalog::getFilterdCatalogData($filter, $csv);
        // echo"<pre>";
        // print_r($data);
        // die;

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
        $response->headers->set('Content-Disposition', 'attachment; filename="CatalogData_'.now()->format('YmdHis').'.csv"');
  
        /** return $csvResponse; */
        return $response;
    }
}
