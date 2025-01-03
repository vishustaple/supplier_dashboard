<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogAttachments extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'catalog_attachments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cron',
        'file_name',
        'created_by',
        'deleted_by',
        'supplier_id',
        'catalog_price_type_id',
    ];

    protected $dates = ['deleted_at'];

    public function supplier(): BelongsTo {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedByUser(): BelongsTo {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public static function getFilterdCatalogData($filter = []) {
        $orderColumnArray = [
            0 => 'catalog_attachments.supplier_id',
            1 => 'catalog_attachments.file_name',
            2 => 'catalog_attachments.cron',
            3 => 'catalog_attachments.created_by',
            4 => 'catalog_attachments.created_at',
            5 => 'catalog_attachments.id',
        ];
         
        $query = self::query()->selectRaw("
            `catalog_attachments`.`id` as `id`,
            `catalog_attachments`.`cron` as `cron`,
            `catalog_attachments`.`delete` as `delete`,
            `catalog_attachments`.`file_name` as `file_name`,
            `catalog_attachments`.`created_by` as `created_by`,
            `catalog_attachments`.`created_at` as `created_at`,
            `catalog_attachments`.`deleted_at` as `deleted_at`,
            `suppliers`.`supplier_name` as `supplier_name`,
            CONCAT(`users`.`first_name`, ' ', `users`.`last_name`) AS `user_name`
        ")

        ->leftJoin('users', 'catalog_attachments.created_by', '=', 'users.id')
        ->leftJoin('suppliers', 'suppliers.id', '=', 'catalog_attachments.supplier_id')
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

        $query->orderBy('catalog_attachments.id', 'desc');

        /** Get paginated results based on start, length */
        if (isset($filter['start']) && isset($filter['length'])) {
            $filteredData = $query->skip($filter['start'])->take($filter['length'])->get();
        } else {
            $filteredData = $query->get();
        }
        
        /** Print the SQL query
        * For debug query 
        */
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
           
            $formatuserdata[$key]['uploaded_by'] = $data->user_name;
            $formatuserdata[$key]['supplier_name'] = $data->supplier_name;
            $formatuserdata[$key]['date'] = date_format(date_create($data->created_at), 'm/d/Y');
            $formatuserdata[$key]['file_name'] = '<div class="file_td">'.$data->file_name.'</div>';
            if ($data->cron == 10){
                $formatuserdata[$key]['id'] = '<button class="btn btn-success btn-xs disabled remove button invisible3 border-0" ><i class="fa fa-clone" aria-hidden="true"></i></button>';
            } else {
                $formatuserdata[$key]['id'] = (isset($data->delete) && !empty($data->delete)) ? ('<div class="spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div>') : (((isset($data->deleted_at) && !empty($data->deleted_at) || $data->cron == 10) ? '<button class="btn btn-danger btn-xs remove invisible1 disabled" ><i class="fa-solid fa-trash"></i></button>' : '<button data-id="'.$data->id.'" class="btn btn-danger btn-xs remove" title="Remove File"><i class="fa-solid fa-trash"></i></button>'));
            }
        }

        return [
            'data' => $formatuserdata,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
        ];
    }
}
