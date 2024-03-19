<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class UploadedFiles extends Model
{
    use HasFactory, SoftDeletes;
    const UPLOAD = 1;
    const CRON = 2;
    const PROCESSED = 3;
    protected $table = 'uploaded_files';
     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cron',
        'end_date',
        'file_name',
        'start_date',
        'created_by',
        'deleted_by',
        'supplier_id',
    ];

    protected $dates = ['deleted_at'];

    // Define the relationship with the supplier table
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    // Define the relationship with the user table
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedByUser()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public static function getFilterdExcelData($filter = []){

        $orderColumnArray = [
            0 => 'uploaded_files.supplier_id',
            1 => 'uploaded_files.file_name',
            2 => 'uploaded_files.cron',
            3 => 'uploaded_files.created_by',
            4 => 'uploaded_files.created_at',
            5 => 'uploaded_files.id',
        ];
         
        $query = self::query() /** Eager load relationships */
        ->withTrashed()->select('uploaded_files.file_name as file_name',
         'uploaded_files.created_by as created_by',
         'uploaded_files.cron as cron',
         'uploaded_files.id as id',
         'uploaded_files.created_at as created_at',
         'uploaded_files.deleted_at as deleted_at',
         'suppliers.supplier_name as supplier_name')
        ->leftJoin('suppliers', 'suppliers.id', '=', 'uploaded_files.supplier_id');
       
       
         /** Search functionality */
         if (isset($filter['search']['value']) && !empty($filter['search']['value'])) {
            $searchTerm = $filter['search']['value'];

            $query->where(function ($q) use ($searchTerm, $orderColumnArray) {
                foreach ($orderColumnArray as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $searchTerm . '%');
                }
            });
            
            $query->orWhere('suppliers.supplier_name', 'LIKE', '%' . $searchTerm . '%');
        }

        /** Get total records count (without filtering) */
        $totalRecords = $query->count();
        // dd($totalRecords);
      
        // if (isset($filter['order'][0]['column']) && isset($orderColumnArray[$filter['order'][0]['column']]) && isset($filter['order'][0]['dir'])) {
        //     /** Order by column and direction */
        //     $query->orderBy($orderColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);
        // } else {
            // $query->orderBy('uploaded_files.id', 'desc');
        // }

        $query->orderBy('uploaded_files.id', 'desc');
        if (isset($filter['start']) && isset($filter['length'])) {
            /** Get paginated results based on start, length */
            $filteredData = $query->skip($filter['start'])->take($filter['length'])->get();
        } else {
            $filteredData = $query->get();
        }
            /** Print the SQL query */
        // dd($query->toSql()); 
        /** Get filtered records count */
        $filteredRecords = $query->count();
        
        $formatuserdata=[];
        foreach ($filteredData as $key => $data) {
            if ($data->cron == 1) {
                $cronString = 'Pending';
            } elseif ($data->cron == 2) {
                $cronString = 'Processing';
            } else {
                $cronString = 'Uploaded';
            }
             
            if (isset($data->deleted_at) && !empty($data->deleted_at)) {
                $cronString = 'Deleted';
            }

            $formatuserdata[$key]['supplier_name'] = $data->supplier_name;
            $formatuserdata[$key]['file_name'] = '<div class="file_td">'.$data->file_name.'</div>';
            $formatuserdata[$key]['status'] = $cronString;
            $formatuserdata[$key]['uploaded_by'] = $data->createdByUser->first_name.' '.$data->createdByUser->last_name;
            $formatuserdata[$key]['date'] = date_format(date_create($data->created_at), 'm/d/Y');
            $formatuserdata[$key]['id'] = (isset($data->delete) && !empty($data->delete)) ? ('<div class="spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div>') : ((isset($data->deleted_at) && !empty($data->deleted_at) ? '<button class="btn btn-danger btn-xs remove invisible" ><i class="fa-solid fa-trash"></i></button>' : '<button data-id="'.$data->id.'" class="btn btn-danger btn-xs remove" title="Remove File"><i class="fa-solid fa-trash"></i></button>'));

        }
// dd($formatuserdata);
        return [
            'data' => $formatuserdata,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
        ];

    }

}
