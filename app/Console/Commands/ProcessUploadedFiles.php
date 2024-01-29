<?php

namespace App\Console\Commands;

use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use App\Models\{Account, Order, OrderDetails,UploadedFiles};

class ProcessUploadedFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-uploaded-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ab = [ 
        [
            'Account Number' =>'00218208',
            'Account Name' =>'Tradewind Energy, Inc.',
            'Record Type' =>'Office Supplies',
            'ParentID' =>'21958889',
            'GrandparentID' =>'1637',
        ],
        [
            'Account Number' => '0003003479',
            'Account Name' => 'Charles River Laboratories International, Inc.',
            'Record Type' => 'Office Supplies',
            'ParentID' => '',
            'GrandparentID' => '2140',
        ],
        [
            'Account Number' => '03449779',
            'Account Name' => 'Big Ass Fans',
            'Record Type' => 'Office Supplies',
            'ParentID' => '21958889',
            'GrandparentID' => '1637',
        ],
        [
            'Account Number' => '03915656',
            'Account Name' => 'Labor First, LLC',
            'Record Type' => 'Office Supplies',
            'ParentID' => '21958889',
            'GrandparentID' => '1637',
        ],
        [
            'Account Number' => '04998340',
            'Account Name' => 'LaSalle Capital Group',
            'Record Type' => 'Office Supplies',
            'ParentID' => '',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '05041668',
            'Account Name' => 'National Academy of Social Insurance',
            'Record Type' => 'Office Supplies',
            'ParentID' => '',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '05166110',
            'Account Name' => 'GreenDrop, LLC',
            'Record Type' => 'Office Supplies',
            'ParentID' => '21958889',
            'GrandparentID' => '1637',
        ],
        [
            'Account Number' =>'05355117',
            'Account Name' =>'Red Development',
            'Record Type' =>'Office Supplies',
            'ParentID' =>'21958889',
            'GrandparentID' =>'1637',
        ],
        [
            'Account Number' => '05722117',
            'Account Name' => 'AgroFresh Inc',
            'Record Type' => 'Office Supplies',
            'ParentID' => '21958889',
            'GrandparentID' => '1637',
        ],
        [
            'Account Number' => '06213889',
            'Account Name' => 'BlackLine Systems',
            'Record Type' => 'Office Supplies',
            'ParentID' => '21958889',
            'GrandparentID' => '1637',
        ],
        [
            'Account Number' => '06406845',
            'Account Name' => 'Eastern Minority Supplier Development Council',
            'Record Type' => 'Office Supplies',
            'ParentID' => '21958889',
            'GrandparentID' => '1637',
        ],
        [
            'Account Number' => '07482079',
            'Account Name' => 'Kane is Able, Inc.',
            'Record Type' => 'Office Supplies',
            'ParentID' => '21958889',
            'GrandparentID' => '1637',
        ],
        [
            'Account Number' => '0800117400',
            'Account Name' => 'APS',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPAP',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0800183089',
            'Account Name' => 'ZARTMAN CONSTRUCTION',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPCM',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0800198616',
            'Account Name' => 'SAPUTO',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPSP',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0800230278',
            'Account Name' => 'AVANTOR SCIENCES',
            'Record Type' => 'MRO Supplies',
            'ParentID' => '',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0800269078',
            'Account Name' => 'SAFT AMERICA',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPSF',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0800622425',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0800623076',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0801066317',
            'Account Name' => 'QUANTA SERVICES',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPQS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0801172644',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0801413816',
            'Account Name' => 'APLIX INC',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPCM',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0801439514',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0801569203',
            'Account Name' => 'BRIDGEFORD',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPBF',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0801874306',
            'Account Name' => 'KASTALON INC',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPCM',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0801896531',
            'Account Name' => 'CINCINNATI BELL',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPCB',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0801924614',
            'Account Name' => 'CINCINNATI BELL',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPCM',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0801967720',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0802017699',
            'Account Name' => 'ACOR ORTHOPEDIC',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPCM',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0802164038',
            'Account Name' => 'DOMINION',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPDM',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0802339143',
            'Account Name' => 'JACK COOPER',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPJC',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0802529867',
            'Account Name' => 'BRIDGEFORD',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPBF',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0802608083',
            'Account Name' => 'INTERSTATE BATTERY',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPAA',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0802771675',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0803639194',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0803704477',
            'Account Name' => 'SAPUTO',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPSP',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0803714344',
            'Account Name' => 'SAPUTO',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPSP',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0803866292',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0803964345',
            'Account Name' => 'PARADIGM',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPPD',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0804078582',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0804088631',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0804099562',
            'Account Name' => 'IMPERIAL DADE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPID',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0804146702',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0804185940',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0804360519',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0804764744',
            'Account Name' => 'QUANTA SERVICES',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPQS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0805021888',
            'Account Name' => 'AVANTOR SCIENCES',
            'Record Type' => 'MRO Supplies',
            'ParentID' => '',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0805114824',
            'Account Name' => 'ABF FREIGHT',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPAF',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0805167475',
            'Account Name' => 'BRIDGEFORD',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPBF',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0805218674',
            'Account Name' => 'SAPUTO',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPSP',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0805342276',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0805601853',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0805930963',
            'Account Name' => 'SAPUTO',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPSP',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0806102166',
            'Account Name' => 'BIOMET',
            'Record Type' => 'MRO Supplies',
            'ParentID' => '',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0806463915',
            'Account Name' => 'IMPERIAL DADE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPID',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0806467999',
            'Account Name' => 'CP FLEXIBLE PACKAGING',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPCP',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0806470860',
            'Account Name' => 'SAPUTO',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPSP',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0806674685',
            'Account Name' => 'SAPUTO',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPSP',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0806783445',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0806833604',
            'Account Name' => 'CHURCH & DWIGHT',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPCD',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0806920047',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0806964235',
            'Account Name' => 'ELIZABETHTOWN GAS',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPCM',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0807164843',
            'Account Name' => 'WIKA INSTRUMENTS',
            'Record Type' => 'MRO Supplies',
            'ParentID' => '',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0807520036',
            'Account Name' => 'DOMINION',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPDM',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0807558580',
            'Account Name' => 'DOMINION',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPDM',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0807691456',
            'Account Name' => 'JACK COOPER',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPJC',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0808293567',
            'Account Name' => 'APS',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPAP',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0808311229',
            'Account Name' => 'APS',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPAP',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0808375935',
            'Account Name' => 'APS',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPAP',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0808416119',
            'Account Name' => 'APS',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPAP',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0808548705',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0808902951',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0809045735',
            'Account Name' => 'INTERSTATE BATTERY',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPAA',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0809053937',
            'Account Name' => 'DOMINION',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPDM',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0809065980',
            'Account Name' => 'DOMINION',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPDM',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0809068273',
            'Account Name' => 'DOMINION',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPDM',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0809158728',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0809601990',
            'Account Name' => 'QUANTA SERVICES',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPQS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0809655392',
            'Account Name' => 'CHURCH & DWIGHT',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPCD',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0809684061',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0810807230',
            'Account Name' => 'SAFT AMERICA',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPCM',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0811028315',
            'Account Name' => 'CHURCH & DWIGHT',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPCD',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0811283761',
            'Account Name' => 'INTERSTATE SERVICE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPCM',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0811363175',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0811454495',
            'Account Name' => 'DOMINION',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPDM',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0811632793',
            'Account Name' => 'THE WICHITA EAGLE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPCM',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0812835346',
            'Account Name' => 'JACK COOPER',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPJC',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0812990711',
            'Account Name' => 'IMPERIAL DADE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPID',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0814662268',
            'Account Name' => 'CP FLEXIBLE PACKAGING',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPCP',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0815241443',
            'Account Name' => 'MADELAINE CHOCOLATE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPCM',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0815502422',
            'Account Name' => 'DOMINION',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPDM',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0815520671',
            'Account Name' => 'SELECTIVE INSURANCE CO',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPCM',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0815934229',
            'Account Name' => 'SAPUTO',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPSP',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0815953393',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPFS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' =>'0816503619',
            'Account Name' =>'SAFT AMERICA',
            'Record Type' =>'MRO Supplies',
            'ParentID' =>'CUPSF',
            'GrandparentID' =>'',
        ],
        [
            'Account Number' => '0816745103',
            'Account Name' => 'QUANTA SERVICES',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPQS',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0817082860',
            'Account Name' => 'IMPERIAL DADE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => '',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0817199672',
            'Account Name' => 'FLOWSERVE',
            'Record Type' => 'MRO Supplies',
            'ParentID' => '',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0817603822',
            'Account Name' => 'ABF FREIGHT',
            'Record Type' => 'MRO Supplies',
            'ParentID' => 'CUPAF',
            'GrandparentID' => '',
        ],
        [
            'Account Number' => '0818084501',
            'Account Name' => 'AVANTOR SCIENCES',
            'Record Type' => 'MRO Supplies',
            'ParentID' => '',
            'GrandparentID' => '',
        ]
        ];
        foreach ($ab as $key => $row) {
        if (empty($row['ParentID']) && empty($row['GrandparentID'])) {  
            $customer = Account::where('customer_number', $row['Account Number'])->first();
            $recordTypeExist = RecordType::where('account_name', $row['Record Type'])->first();
            if (empty($recordTypeExist)) {
            $RecordTypeId = RecordType::create(['account_name' => $row['Record Type'], 'created_by' => 1])->first();
            } else {
            $RecordTypeId = $recordTypeExist;
            }

            if (empty($customer)) {
            Account::create(['customer_number' => $row['Account Number'], 'customer_name' => $row['Account Name'], 'parent_id' => null, 'created_by' => 1, 'record_type_id' => $recordTypeExist->id]);
            } else {
            DB::table('account')->where('id', $customer->id)->update(['record_type_id' => $recordTypeExist->id]);
            }

        } elseif (!empty($row['ParentID']) && empty($row['GrandparentID'])) {
            $perent = Account::where('customer_number', $row['ParentID'])->first();

            $customer = Account::where('customer_number', $row['Account Number'])->first();
            $recordTypeExist = RecordType::where('account_name', $row['Record Type'])->first();
            if (empty($recordTypeExist)) {
            $RecordTypeId = RecordType::create(['account_name' => $row['Record Type'], 'created_by' => 1])->first();
            } else {
            $RecordTypeId = $recordTypeExist;
            }
            
            if (empty($customer)) {
            DB::table('account')->where('id', $perent->id)->update(['record_type_id' => $recordTypeExist->id]);
            Account::create(['customer_number' => $row['Account Number'], 'customer_name' => $row['Account Name'], 'parent_id' => $perent->id, 'created_by' => 1, 'record_type_id' => $recordTypeExist->id]);
            } else {
            DB::table('account')->where('id', $perent->id)->update(['record_type_id' => $recordTypeExist->id]);
            DB::table('account')->where('id', $customer->id)->update(['record_type_id' => $recordTypeExist->id]);
            }
        } elseif (!empty($row['ParentID']) && !empty($row['GrandparentID'])) {
            $gdPerent = Account::where('customer_number', $row['GrandparentID'])->first();
            $perent = Account::where('customer_number', $row['ParentID'])->first();

            $customer = Account::where('customer_number', $row['Account Number'])->first();
            $recordTypeExist = RecordType::where('account_name', $row['Record Type'])->first();
            if (empty($recordTypeExist)) {
            $RecordTypeId = RecordType::create(['account_name' => $row['Record Type'], 'created_by' => 1])->first();
            } else {
            $RecordTypeId = $recordTypeExist;
            }
            
            if (empty($customer)) {
            DB::table('account')->where('id', $perent->id)->update(['record_type_id' => $recordTypeExist->id]);
            DB::table('account')->where('id', $gdPerent->id)->update(['record_type_id' => $recordTypeExist->id]);
            Account::create(['customer_number' => $row['Account Number'], 'customer_name' => $row['Account Name'], 'parent_id' => $perent->id, 'created_by' => 1, 'record_type_id' => $recordTypeExist->id]);
            } else {
            DB::table('account')->where('id', $perent->id)->update(['record_type_id' => $recordTypeExist->id]);
            DB::table('account')->where('id', $gdPerent->id)->update(['record_type_id' => $recordTypeExist->id]);
            DB::table('account')->where('id', $customer->id)->update(['record_type_id' => $recordTypeExist->id]);
            }
        } else {
        }
        
        }
  



        /** This is the folder path where we save the file */
        $destinationPath = public_path('/excel_sheets');

        try{
            /** Select those file name where cron is one */
            $fileValue = DB::table('uploaded_files')->select('id', 'supplier_id', 'file_name', 'start_date', 'end_date', 'created_by')->where('cron', '=', UploadedFiles::UPLOAD)->first();

            // $monthsDifference = $interval->m;
            // $yearsDifference = $interval->y;
            
            if ($fileValue !== null) {

                /** Update cron two means start processing data into excel */
                // DB::table('uploaded_files')->where('id', $fileValue->id)
                // ->update([
                // 'cron' => UploadedFiles::CRON
                // ]);



                /** Add column name here those row you want to skip */
                $skipRowArray = ["Shipto Location Total", "Shipto & Location Total", "TOTAL FOR ALL LOCATIONS", "Total"];
                 
                /** This array for dynmically get column name for save data into tables */
                $columnArray = [ 
                    1 => ['customer_number' => 'SOLD TOACCOUNT', 'amount' => 'ON-CORESPEND', 'invoice_no' => '', 'invoice_date' => ''],

                    2 => ['customer_number' => 'Account Number', 'amount' => 'Actual Price Paid', 'invoice_no' => 'Invoice Number', 'invoice_date' => 'Bill Date'],

                    3 => ['customer_number' => 'CUSTOMER ID', 'amount' => 'Total Spend', 'invoice_no' => 'Invoice #', 'invoice_date' => 'Shipped Date'],

                    4 => ['customer_number' => 'MASTER_CUSTOMER', 'amount' => 'ADJGROSSSALES', 'invoice_no' => 'INVOICENUMBER', 'invoice_date' => 'INVOICEDATE'],

                    5 => ['customer_number' => 'Customer Num', 'amount' => 'Current List', 'invoice_no' => 'Invoice Num', 'invoice_date' => 'Invoice Date'],

                    6 => ['customer_number' => 'Leader customer 1', 'amount' => 'Sales Amount - P', 'invoice_no' => 'Billing Document', 'invoice_date' => 'Billing Date'],

                    7 => ['customer_number' => 'Account ID', 'amount' => '', 'invoice_no' => '', 'invoice_date' => ''],
                ];

                try {
                    /** Increasing the memory limit becouse memory limit issue */
                    ini_set('memory_limit', '1024M');

                    /** Inserting files data into the database after doing excel import */
                    // foreach ($fileValue as $fileKey => $fileValue) {
                        // dd($fileValue);
                        $date1 = Carbon::parse($fileValue->start_date);
                        $date2 = Carbon::parse($fileValue->end_date);

                        /** Calculate the difference between the two dates */
                        $interval = $date1->diff($date2);

                        /** Access the difference in days, months, and years */
                        $daysDifference = $interval->days;

                        if ($daysDifference <= 7) {
                            $weeklyCheck = true;
                        } else {
                            $weeklyCheck = false;
                            $ordersData = DB::table('order_details')->select('order_id')->where('order_file_name', $fileValue->supplier_id."_weekly_".date_format(date_create($fileValue->start_date),"Y/m"))->get();

                            OrderDetails::where('order_file_name', $fileValue->supplier_id."_weekly_".date_format(date_create($fileValue->start_date),"Y/m"))->delete();

                            foreach ($ordersData as $order) {
                                Order::destroy($order->order_id);
                            }
                        }   

                        unset($spreadSheet, $reader);
                        // print_r($fileValue->created_by);die;
                        $reader = new Xlsx(); /** Creating object of php excel library class */ 

                        /** Loading excel file using path and name of file from table "uploaded_file" */
                        $spreadSheet = $reader->load($destinationPath . '/' . $fileValue->file_name, 2);
                        
                        $sheetCount = $spreadSheet->getSheetCount(); /** Getting sheet count for run loop on index */
                        
                        if ($fileValue->supplier_id == 4 || $fileValue->supplier_id == 3) {
                            $sheetCount = ($sheetCount > 1) ? $sheetCount - 2 : $sheetCount; /** Handle case if sheet count is one */
                        } else {
                            $sheetCount = ($sheetCount > 1) ? $sheetCount - 1 : $sheetCount;
                        }
                        
                        // print_r($sheetCount);
                        // die;

                        for ($i = 0; $i <= $sheetCount; $i++) {
                            $count = $maxNonEmptyCount = 0;

                            // print_r($i);
                            
                            if (($sheetCount == 1 && $i == 1 && $fileValue->supplier_id != 5) || ($fileValue->supplier_id == 5 && $i == 0) || ($fileValue->supplier_id == 7 && in_array($i, [0, 1, 3, 4, 5, 6, 7]))) {
                                continue;
                            }

                            $workSheetArray = $spreadSheet->getSheet($i)->toArray(); /** Getting worksheet using index */
                            foreach ($workSheetArray as $key=>$values) {
                                /** Checking not empty columns */
                                $nonEmptyCount = count(array_filter(array_values($values), function ($item) {
                                    return !empty($item);
                                }));
                                
                                /** If column count is greater then previous row columns count. Then assigen value to '$maxNonEmptyvalue' */
                                if ($nonEmptyCount > $maxNonEmptyCount) {
                                    $maxNonEmptyValue = $values;
                                    $startIndexValueArray = $key;
                                    $maxNonEmptyCount = $nonEmptyCount;
                                } 
                                
                                /** Stop loop after reading 31 rows from excel file */
                                if ($key > 20) {
                                    break;
                                }
                            }

                            /** Clean up the values */
                            $maxNonEmptyValue = array_map(function ($value) {
                                /** Remove line breaks and trim whitespace */
                                return str_replace(["\r", "\n"], '', $value);
                            }, $maxNonEmptyValue);

                            // print_r($maxNonEmptyValue);
                            // die;

                            if ($fileValue->supplier_id == 7) {
                                $weeklyPriceColumnArray = [];
                                foreach ($maxNonEmptyValue as $key => $value) {
                                    if ($key >= 16) {
                                        $weeklyPriceColumnArray[$key] = $value;
                                        // $weeklyArrayKey++;
                                    }
                                }
                            }

                            /** Unset the "$maxNonEmptyCount" for memory save */
                            unset($maxNonEmptyCount);

                            $startIndex = $startIndexValueArray; /** Specify the starting index for get the excel column value */

                            /** Unset the "$startIndexValueArray" for memory save */
                            unset($startIndexValueArray);

                            if ($fileValue->supplier_id == 2) {
                               $graingerCount = $startIndex + 1;
                            }

                            foreach ($workSheetArray as $key => $row) {
                                if($key > $startIndex){
                                    $workSheetArray1[] = $row;
                                    if (($fileValue->supplier_id == 2 && $key > $graingerCount) || $fileValue->supplier_id == 3 || $fileValue->supplier_id == 7) {
                                        $gdPerent = Account::where('customer_number', $row[0])->first();
                                        $perent = Account::where('customer_number', $row[2])->first();
                                        $customer = Account::where('customer_number', $row[4])->first();

                                        if (empty($gdPerent) && empty($perent) && empty($customer)) {
                                            $lastInsertGdPerentId = Account::create(['customer_number' => $row[0], 'customer_name' => $row[1], 'parent_id' => null, 'created_by' => $fileValue->created_by]);

                                            $lastInsertPerentId = Account::create(['customer_number' => $row[2], 'customer_name' => $row[3], 'parent_id' => $lastInsertGdPerentId->id, 'created_by' => $fileValue->created_by]);

                                            Account::create(['customer_number' => $row[4], 'customer_name' => $row[5], 'parent_id' => $lastInsertPerentId->id, 'created_by' => $fileValue->created_by]);

                                        } elseif (!empty($gdPerent) && empty($perent) && empty($customer)) {
                                            $lastInsertPerentId = Account::create(['customer_number' => $row[2], 'customer_name' => $row[3], 'parent_id' => $gdPerent->id, 'created_by' => $fileValue->created_by]);

                                            Account::create(['customer_number' => $row[4], 'customer_name' => $row[5], 'parent_id' => $lastInsertPerentId->id, 'created_by' => $fileValue->created_by]);

                                        } elseif (!empty($gdPerent) && !empty($perent) && empty($customer)) {
                                            Account::create(['customer_number' => $row[4], 'customer_name' => $row[5], 'parent_id' => $perent->id, 'created_by' => $fileValue->created_by]);

                                        } else {
                                            // echo "hello";
                                        }
                                    }
                                    
                                    if ($fileValue->supplier_id == 4) {
                                        $gdPerent = Account::where('customer_number', $row[0])->first();
                                        $perent = Account::where('customer_number', $row[2])->first();

                                        if (empty($gdPerent) && empty($perent)) {
                                            $lastInsertGdPerentId = Account::create(['customer_number' => $row[0], 'customer_name' => $row[1], 'parent_id' => null, 'created_by' => $fileValue->created_by]);

                                            Account::create(['customer_number' => $row[2], 'customer_name' => $row[3], 'parent_id' => $lastInsertGdPerentId->id, 'created_by' => $fileValue->created_by]);

                                        } elseif (!empty($gdPerent) && empty($perent)) {
                                            Account::create(['customer_number' => $row[2], 'customer_name' => $row[3], 'parent_id' => $gdPerent->id, 'created_by' => $fileValue->created_by]);

                                        } else {
                                            // echo "hello";
                                        }
                                    }

                                    if ($fileValue->supplier_id == 6) {
                                        /** Exploding the "$row" get this  */ 
                                        $customerName1 = $c1 = explode(" ", $row[12]);
                                        $customerName2 = $c2 = explode(" ", $row[13]);
                                        $customerName3 = $c3 = explode(" ", $row[14]);
                                        $customerName4 = $c4 = explode(" ", $row[15]);
                                        $customerName5 = $c5 = explode(" ", $row[16]);
                                        $customerName6 = $c6 = explode(" ", $row[17]);

                                        $lc1 = Account::where('customer_number', $c1[0])->first();
                                        $lc2 = Account::where('customer_number', $c2[0])->first();
                                        $lc3 = Account::where('customer_number', $c3[0])->first();
                                        $lc4 = Account::where('customer_number', $c4[0])->first();
                                        $lc5 = Account::where('customer_number', $c5[0])->first();
                                        $lc6 = Account::where('customer_number', $c6[0])->first();

                                        /** Here we unsetting the zero index because we need full name of customer */
                                        unset($customerName1[0], $customerName2[0], $customerName3[0], $customerName4[0], $customerName5[0], $customerName6[0]);
 
                                        $customerName1 = implode(" ", $customerName1);
                                        $customerName2 = implode(" ", $customerName2);
                                        $customerName3 = implode(" ", $customerName3);
                                        $customerName4 = implode(" ", $customerName4);
                                        $customerName5 = implode(" ", $customerName5);
                                        $customerName6 = implode(" ", $customerName6);
                                        
                                        if (empty($lc1) && empty($lc2) && empty($lc3) && empty($lc4) && empty($lc5) && empty($lc6)) {
                                            $li1 = Account::create(['customer_number' => $c1[0], 'customer_name' => $customerName1, 'parent_id' => null, 'created_by' => $fileValue->created_by]);
                                            $li2 = Account::create(['customer_number' => $c2[0], 'customer_name' => $customerName2, 'parent_id' => $li1->id, 'created_by' => $fileValue->created_by]);
                                            $li3 = Account::create(['customer_number' => $c3[0], 'customer_name' => $customerName3, 'parent_id' => $li2->id, 'created_by' => $fileValue->created_by]);
                                            $li4 = Account::create(['customer_number' => $c4[0], 'customer_name' => $customerName4, 'parent_id' => $li3->id, 'created_by' => $fileValue->created_by]);
                                            $li5 = Account::create(['customer_number' => $c5[0], 'customer_name' => $customerName5, 'parent_id' => $li4->id, 'created_by' => $fileValue->created_by]);

                                            Account::create(['customer_number' => $c6[0], 'customer_name' => $customerName6, 'parent_id' => $li5->id, 'created_by' => $fileValue->created_by]);

                                        } elseif (!empty($lc1) && empty($lc2) && empty($lc3) && empty($lc4) && empty($lc5) && empty($lc6)) {
                                            $li2 = Account::create(['customer_number' => $c2[0], 'customer_name' => $customerName2, 'parent_id' => $lc1->id, 'created_by' => $fileValue->created_by]);
                                            $li3 = Account::create(['customer_number' => $c3[0], 'customer_name' => $customerName3, 'parent_id' => $li2->id, 'created_by' => $fileValue->created_by]);
                                            $li4 = Account::create(['customer_number' => $c4[0], 'customer_name' => $customerName4, 'parent_id' => $li3->id, 'created_by' => $fileValue->created_by]);
                                            $li5 = Account::create(['customer_number' => $c5[0], 'customer_name' => $customerName5, 'parent_id' => $li4->id, 'created_by' => $fileValue->created_by]);

                                            Account::create(['customer_number' => $c6[0], 'customer_name' => $customerName6, 'parent_id' => $li5->id, 'created_by' => $fileValue->created_by]);

                                        } elseif (!empty($lc1) && !empty($lc2) && empty($lc3) && empty($lc4) && empty($lc5) && empty($lc6)) {
                                            $li3 = Account::create(['customer_number' => $c3[0], 'customer_name' => $customerName3, 'parent_id' => $lc2->id, 'created_by' => $fileValue->created_by]);
                                            $li4 = Account::create(['customer_number' => $c4[0], 'customer_name' => $customerName4, 'parent_id' => $li3->id, 'created_by' => $fileValue->created_by]);
                                            $li5 = Account::create(['customer_number' => $c5[0], 'customer_name' => $customerName5, 'parent_id' => $li4->id, 'created_by' => $fileValue->created_by]);

                                            Account::create(['customer_number' => $c6[0], 'customer_name' => $customerName6, 'parent_id' => $li5->id, 'created_by' => $fileValue->created_by]);

                                        }elseif (!empty($lc1) && !empty($lc2) && !empty($lc3) && empty($lc4) && empty($lc5) && empty($lc6)) {
                                            $li4 = Account::create(['customer_number' => $c4[0], 'customer_name' => $customerName4, 'parent_id' => $lc3->id, 'created_by' => $fileValue->created_by]);
                                            $li5 = Account::create(['customer_number' => $c5[0], 'customer_name' => $customerName5, 'parent_id' => $li4->id, 'created_by' => $fileValue->created_by]);
                                            
                                            Account::create(['customer_number' => $c6[0], 'customer_name' => $customerName6, 'parent_id' => $li5->id, 'created_by' => $fileValue->created_by]);

                                        } elseif (!empty($lc1) && !empty($lc2) && !empty($lc3) && !empty($lc4) && empty($lc5) && empty($lc6)) {
                                            $li5 = Account::create(['customer_number' => $c5[0], 'customer_name' => $customerName5, 'parent_id' => $lc4->id, 'created_by' => $fileValue->created_by]);

                                            Account::create(['customer_number' => $c6[0], 'customer_name' => $customerName6, 'parent_id' => $li5->id, 'created_by' => $fileValue->created_by]);

                                        } elseif (!empty($lc1) && !empty($lc2) && !empty($lc3) && !empty($lc4) && empty($lc5) && empty($lc6)) {
                                            Account::create(['customer_number' => $c6[0], 'customer_name' => $customerName6, 'parent_id' => $lc5->id, 'created_by' => $fileValue->created_by]);

                                        } else {
                                            // echo "hello";
                                        }
                                    }

                                    if ($fileValue->supplier_id == 5) {
                                        $perent = Account::where('customer_number', $row[1])->first();
                                        if (empty($perent)) {
                                            Account::create(['customer_number' => $row[1], 'customer_name' => $row[2], 'parent_id' => null, 'created_by' => $fileValue->created_by]);
                                        }
                                    }
                                }
                            }

                            /** For insert data into the database */
                            foreach ($workSheetArray1 as $key => $row) {
                                if (count(array_intersect($skipRowArray, $row)) <= 0) {
                                    if (!empty($columnArray[$fileValue->supplier_id]['customer_number'])) {
                                        $keyCustomerNumber = array_search($columnArray[$fileValue->supplier_id]['customer_number'], $maxNonEmptyValue);
                                    }

                                    if (!empty($columnArray[$fileValue->supplier_id]['amount'])) {
                                        if ($fileValue->supplier_id == 1) {
                                            $keyOffCoreAmount = array_search('OFF-CORESPEND', $maxNonEmptyValue);
                                        }

                                        $keyAmount = array_search($columnArray[$fileValue->supplier_id]['amount'], $maxNonEmptyValue);
                                    }

                                    if (!empty($columnArray[$fileValue->supplier_id]['invoice_no'])) {
                                        $keyInvoiceNumber = array_search($columnArray[$fileValue->supplier_id]['invoice_no'], $maxNonEmptyValue);
                                    }

                                    if (!empty($columnArray[$fileValue->supplier_id]['invoice_date'])) {
                                        $keyInvoiceDate = array_search($columnArray[$fileValue->supplier_id]['invoice_date'], $maxNonEmptyValue);
                                    }
 
                                    if (isset($keyCustomerNumber) && !empty($row[$keyCustomerNumber])) {
                                        foreach ($row as $key1 => $value) {
                                            if(!empty($maxNonEmptyValue[$key1])) {
                                                $finalInsertArray[] = [
                                                    'value' => $value,
                                                    'key' => $maxNonEmptyValue[$key1],
                                                    'file_name' => $fileValue->file_name,
                                                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                ];  

                                                // if (!empty($columnArray[$fileValue->supplier_id]['customer_number']) && $columnArray[$fileValue->supplier_id]['customer_number'] == preg_replace('/\s+/', ' ', $maxNonEmptyValue[$key1])) {
                                                //     $finalOrderInsertArray['customer_number'] = $value;
                                                // }

                                                // if (!empty($columnArray[$fileValue->supplier_id]['amount']) && $columnArray[$fileValue->supplier_id]['amount'] == preg_replace('/\s+/', ' ', $maxNonEmptyValue[$key1])) {
                                                //     $finalOrderInsertArray['amount'] = $value;
                                                // } elseif ($fileValue->supplier_id == 1 && "OFF-CORE SPEND" == preg_replace('/\s+/', ' ', $maxNonEmptyValue[$key1]) && !empty($value)) {
                                                //     if (!empty($value)) {
                                                //         $finalOrderInsertArray['amount'] = str_replace(",", "", number_format($value, 2, '.'));
                                                //     } else {
                                                //         $finalOrderInsertArray['amount'] = '0.0';
                                                //     }
                                                // } else {
                                                //     $finalOrderInsertArray['amount'] = '0.0';
                                                // }

                                                // if (!empty($columnArray[$fileValue->supplier_id]['invoice_no']) && $columnArray[$fileValue->supplier_id]['invoice_no'] == preg_replace('/\s+/', ' ', $maxNonEmptyValue[$key1])) {
                                                //     if (empty($value)) {
                                                //         $finalOrderInsertArray['invoice_no'] = OrderDetails::randomInvoiceNum();
                                                //     } else {
                                                //         $finalOrderInsertArray['invoice_no'] = $value;
                                                //     }
                                                // } else {
                                                //     $finalOrderInsertArray['invoice_no'] = OrderDetails::randomInvoiceNum();
                                                // }

                                                // if (!empty($columnArray[$fileValue->supplier_id]['invoice_date']) && $columnArray[$fileValue->supplier_id]['invoice_date'] == preg_replace('/\s+/', ' ', $maxNonEmptyValue[$key1])) {
                                                //     if (!empty($value)) {
                                                //         if ($fileValue->supplier_id == 4) {
                                                //             $finalOrderInsertArray['invoice_date'] = Carbon::createFromFormat('Y-m-d H:i:s', $value);
                                                //         } else {
                                                //             $finalOrderInsertArray['invoice_date'] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value))->format('Y-m-d H:i:s');
                                                //         }
                                                //     } else { 
                                                //         $finalOrderInsertArray['invoice_date'] = $fileValue->start_date;                                                    
                                                //     }
                                                // } else {
                                                //     $finalOrderInsertArray['invoice_date'] = $fileValue->start_date;                                                
                                                // }
                                            }
                                        }

                                        if ($fileValue->supplier_id == 7) {
                                            foreach ($weeklyPriceColumnArray as $key => $value) {
                                                if (!empty($row[$key])) {                                                    
                                                    $date = explode("-", $workSheetArray[7][$key]);

                                                    $orderLastInsertId = Order::create([
                                                        'created_by' => $fileValue->created_by,
                                                        'supplier_id' => $fileValue->supplier_id,
                                                        'amount' => str_replace(",", "", number_format($row[$key], 2, '.')),
                                                        'date' =>  (!empty($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'customer_number' => (!empty($keyCustomerNumber) && !empty($row[$keyCustomerNumber])) ? ($row[$keyCustomerNumber]) : (''),
                                                    ]);

                                                    if ($weeklyCheck) {
                                                        OrderDetails::create([
                                                            'order_id' => $orderLastInsertId->id,
                                                            'created_by' => $fileValue->created_by,
                                                            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                            'invoice_date' => (!empty($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                            'invoice_number' => (!empty($keyInvoiceNumber) && !empty($row[$keyInvoiceNumber])) ? ($row[$keyInvoiceNumber]) : (OrderDetails::randomInvoiceNum()),
                                                            'order_file_name' => $fileValue->supplier_id."_weekly_".date_format(date_create($fileValue->start_date),"Y/m"),
                                                        ]);
                                                    } else {
                                                        OrderDetails::create([
                                                            'order_id' => $orderLastInsertId->id,
                                                            'created_by' => $fileValue->created_by,
                                                            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                            'invoice_number' => (!empty($keyInvoiceNumber) && !empty($row[$keyInvoiceNumber])) ? ($row[$keyInvoiceNumber]) : (OrderDetails::randomInvoiceNum()),
                                                            'invoice_date' => (!empty($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                            'order_file_name' => $fileValue->supplier_id."_monthly_".date_format(date_create($fileValue->start_date),"Y/m"),
                                                        ]);
                                                    }
                                                }
                                            }
                                        } else {
                                            if ($fileValue->supplier_id == 6) {
                                                $customerNumber = explode(" ", $row[$keyCustomerNumber]);
                                                $orderLastInsertId = Order::create([
                                                    'created_by' => $fileValue->created_by,
                                                    'supplier_id' => $fileValue->supplier_id,
                                                    'amount' => (isset($keyAmount) && !empty($row[$keyAmount])) ? ($row[$keyAmount]) : ((!empty($keyOffCoreAmount) && !empty($row[$keyOffCoreAmount]) && $fileValue->supplier_id) ? ($row[$keyOffCoreAmount]) : ('0.0')),
                                                    'date' =>  (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                    'customer_number' => $customerNumber[0],
                                                ]);
                                            } else {
                                                $orderLastInsertId = Order::create([
                                                    'created_by' => $fileValue->created_by,
                                                    'supplier_id' => $fileValue->supplier_id,
                                                    'amount' => (isset($keyAmount) && !empty($row[$keyAmount])) ? ($row[$keyAmount]) : ((!empty($keyOffCoreAmount) && !empty($row[$keyOffCoreAmount]) && $fileValue->supplier_id) ? ($row[$keyOffCoreAmount]) : ('0.0')),
                                                    'date' =>  (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                    'customer_number' => $row[$keyCustomerNumber],
                                                ]);
                                            }

                                            if ($weeklyCheck) {
                                                $orderDetailsArray[] = [
                                                    'order_id' => $orderLastInsertId->id,
                                                    'created_by' => $fileValue->created_by,
                                                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                    'invoice_date' => (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                    // 'invoice_number' => (isset($keyInvoiceNumber) && !empty($row[$keyInvoiceNumber])) ? ($row[$keyInvoiceNumber]) : (OrderDetails::randomInvoiceNum((isset($orderDetailsArray) ? ($orderDetailsArray) : ([])))),
                                                    'invoice_number' => (isset($keyInvoiceNumber) && !empty($row[$keyInvoiceNumber])) ? ($row[$keyInvoiceNumber]) : (OrderDetails::randomInvoiceNum()),
                                                    'order_file_name' => $fileValue->supplier_id."_weekly_".date_format(date_create($fileValue->start_date),"Y/m"),
                                                ];
                                            } else {
                                                $orderDetailsArray[] = [
                                                    'order_id' => $orderLastInsertId->id,
                                                    'created_by' => $fileValue->created_by,
                                                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                    // 'invoice_number' => (isset($keyInvoiceNumber) && !empty($row[$keyInvoiceNumber])) ? ($row[$keyInvoiceNumber]) : (OrderDetails::randomInvoiceNum((isset($orderDetailsArray) ? ($orderDetailsArray) : ([])))),
                                                    'invoice_number' => (isset($keyInvoiceNumber) && !empty($row[$keyInvoiceNumber])) ? ($row[$keyInvoiceNumber]) : (OrderDetails::randomInvoiceNum()),
                                                    'invoice_date' => (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                    'order_file_name' => $fileValue->supplier_id."_monthly_".date_format(date_create($fileValue->start_date),"Y/m"),
                                                ];
                                            }
                                        }
                                    // }

                                        foreach ($finalInsertArray as &$item) {
                                            if (!isset($item['order_id']) && empty($item['order_id'])) {
                                                $item['order_id'] = $orderLastInsertId->id;
                                            }
                                        }
                                    }
                                    
                                    if ($count == 70) {
                                        $count = 0;
                                        try {
                                            if ($fileValue->supplier_id != 7) {
                                                DB::table('order_details')->insert($orderDetailsArray);
                                            }
                                            DB::table('order_product_details')->insert($finalInsertArray);
                                        } catch (QueryException $e) {   
                                            Log::error('Error in YourScheduledTask: ' . $e->getMessage());
                                            echo "Database insertion failed: " . $e->getMessage();
                                            echo $e->getTraceAsString();
                                            die;
                                        }
                                        
                                        unset($finalInsertArray, $orderDetailsArray);
                                    }

                                    $count++; 
                                } else {
                                    continue;
                                }
                            }

                            unset($workSheetArray1, $count, $maxNonEmptyValue);

                            if (!empty($finalInsertArray)) {
                                try {
                                    if ($fileValue->supplier_id != 7) {
                                        DB::table('order_details')->insert($orderDetailsArray);
                                    }
                                    DB::table('order_product_details')->insert($finalInsertArray);
                                } catch (QueryException $e) {   
                                    Log::error('Error in YourScheduledTask: ' . $e->getMessage());
                                    echo "Database insertion failed: " . $e->getMessage();
                                }
                            }

                            unset($finalInsertArray, $finalOrderInsertArray);
                        }
                    // }
                } catch (\Exception $e) {
                    echo "Error loading spreadsheet: " . $e->getMessage();
                }

                try {
                    /** Update the 'cron' field three after processing */
                    // DB::table('uploaded_files')->where('id', $fileValue->id)->update(['cron' => UploadedFiles::PROCESSED]);

                    $this->info('Uploaded files processed successfully.');
                } catch (QueryException $e) {   
                    echo "Database updation failed: " . $e->getMessage();
                    die;
                }
            } else {
                echo "No file left to upload.";
            }
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            echo "Error loading spreadsheet: " . $e->getMessage();
            die;
        } catch (QueryException $e) {   
            echo "Database table uploaded_files select query failed: " . $e->getMessage();
            die;
        }  
    }
}
