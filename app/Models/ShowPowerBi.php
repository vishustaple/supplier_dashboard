<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShowPowerBi extends Model
{
    use HasFactory;

    protected $table = 'show_power_bi';

    public static function getFilterdData($filter=[]) {
        $orderColumnArray = [
            0 => 'title',
            1 => 'iframe',
            2 => 'deleted_at',
            3 => 'id',
        ];

        $query = self::query() /** Eager load relationships */
        ->select(
            'id',	
            'title',	
            'iframe',	
            'deleted',	
            'deleted_at',
        );
         
        /** Get total records count (without filtering) */
        $totalRecords = $query->getQuery()->getCountForPagination();

        if (isset($filter['check']) && $filter['check'] == 1) {
            $query->where('deleted', $filter['check']);
        } else {
            $query->where('deleted', 0);
        }

        /** Search functionality */
        if (isset($filter['search']['value']) && !empty($filter['search']['value'])) {
            $searchTerm = $filter['search']['value'];

            $query->where(function ($q) use ($searchTerm, $orderColumnArray) {
                foreach ($orderColumnArray as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $searchTerm . '%');
                }
            });            
        }

        /** Order by column and direction */
        if (isset($filter['order'][0]['column']) && isset($orderColumnArray[$filter['order'][0]['column']]) && isset($filter['order'][0]['dir'])) {
            $query->orderBy($orderColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);
        } else {
            $query->orderBy($orderColumnArray[0], 'asc');
        }

        /** Get paginated results based on start, length */
        $filteredData = $query->when(isset($filter['start']) && isset($filter['length']), function ($query) use ($filter) {
            return $query->skip($filter['start'])->take($filter['length']);
        })->get();

        /** Print the SQL query */
        // dd($query->toSql());
        
        $formatuserdata=[];
        foreach ($filteredData as $key => $data) {
            $formatuserdata[$key]['title'] = $data->title;
            $formatuserdata[$key]['iframe'] = htmlspecialchars($data->iframe);
            $formatuserdata[$key]['deleted_at'] = ($data->deleted_at != null) ? (Carbon::parse($data->deleted_at)->format('d/m/Y')) : ('');
            if ($data->deleted == 0) {
                $formatuserdata[$key]['id'] = '<div class="row delete justify-content-start">
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-id="' . $data->id . '" data-title="' . htmlspecialchars($data->title) . '" data-iframe="' . htmlspecialchars($data->iframe) . '" data-bs-target="#editStaticBackdrop">
                            <i class="fa fa-pencil-square" aria-hidden="true"></i>
                        </button>
                   
                        <a class="btn btn-danger" href="javascript:void(0);" onclick="deletePowerBI(\'' . $data->id . '\', \'' . htmlspecialchars($data->title) . '\')">
                            <i class="fa fa-trash" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>';
            } else {
                $formatuserdata[$key]['id'] = '';
            }
        }
       
        /** Return the result along with total and filtered counts */
        return [
            'data' => $formatuserdata,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
        ];
    }
}
