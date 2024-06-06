<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedByUser(): BelongsTo
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
         
        $query = self::query()->selectRaw(
            "`uploaded_files`.`file_name` as `file_name`,
            `uploaded_files`.`created_by` as `created_by`,
            `uploaded_files`.`cron` as `cron`,
            `uploaded_files`.`id` as `id`,
            `uploaded_files`.`created_at` as `created_at`,
            `uploaded_files`.`deleted_at` as `deleted_at`,
            `uploaded_files`.`delete` as `delete`,
            `suppliers`.`supplier_name` as `supplier_name`,
            CONCAT(`users`.`first_name`, ' ', `users`.`last_name`) AS `user_name`"
        )
        ->leftJoin('users', 'uploaded_files.created_by', '=', 'users.id')
        ->leftJoin('suppliers', 'suppliers.id', '=', 'uploaded_files.supplier_id')
        ->withTrashed();

        /** Get total records count (without filtering) */
        $totalRecords = $query->count();

        /** Search functionality */
        if(isset($filter['search']['value']) && !empty($filter['search']['value'])) {
            $searchTerm = $filter['search']['value'];

            $query->where(function ($q) use ($searchTerm, $orderColumnArray) {
                foreach ($orderColumnArray as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $searchTerm . '%');
                }
            });
            
            $query->orWhere('suppliers.supplier_name', 'LIKE', '%' . $searchTerm . '%');
        }

        $query->orderBy('uploaded_files.id', 'desc');
        if (isset($filter['start']) && isset($filter['length'])) {
            /** Get paginated results based on start, length */
            $filteredData = $query->skip($filter['start'])->take($filter['length'])->get();
        } else {
            $filteredData = $query->get();
        }
        
        /** Print the SQL query
        * For debug query */
        // dd($query->toSql(), $query->getBindings());

        /** Get filtered records count */
        $filteredRecords = $query->getQuery()->getCountForPagination();
        
        $formatuserdata=[];
        foreach ($filteredData as $key => $data) {
            if ($data->cron == 1 || $data->cron == 11) {
                $cronString = 0;
            } elseif ($data->cron == 2) {
                $cronString = 30;
            } elseif ($data->cron == 4) {
                $cronString = 50;
            } elseif ($data->cron == 5) {
                $cronString = 70;
            } else {
                $cronString = 100;
            }
             
            if (isset($data->deleted_at) && !empty($data->deleted_at)) {
                $cronString = 'Deleted';
                $formatuserdata[$key]['status'] = $cronString;
            } elseif($data->cron == 10) {
                $cronString = 'Already Uploaded';
                $formatuserdata[$key]['status'] = $cronString;
            } else {
                $formatuserdata[$key]['status'] = '<div class="clear"></div><progress value="'.$cronString.'" max="100" id="progBar"><span id="downloadProgress"></span></progress><div id="progUpdate">'.$cronString.'% Uploaded</div>';
            }
           
            $formatuserdata[$key]['supplier_name'] = $data->supplier_name;
            $formatuserdata[$key]['file_name'] = '<div class="file_td">'.$data->file_name.'</div>';
            $formatuserdata[$key]['uploaded_by'] = $data->user_name;
            $formatuserdata[$key]['date'] = date_format(date_create($data->created_at), 'm/d/Y');
            $formatuserdata[$key]['id'] = (isset($data->delete) && !empty($data->delete)) ? ('<div class="spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div>') : (((isset($data->deleted_at) && !empty($data->deleted_at) || $data->cron == 10) ? '<button class="btn btn-danger btn-xs remove invisible" ><i class="fa-solid fa-trash"></i></button>' : '<button data-id="'.$data->id.'" class="btn btn-danger btn-xs remove" title="Remove File"><i class="fa-solid fa-trash"></i></button>'));
        }

        return [
            'data' => $formatuserdata,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
        ];
    }
}
