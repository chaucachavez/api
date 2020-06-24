<?php

namespace App\Models; 

class grupodx extends apimodel {

    protected $table = 'grupodx';
    protected $primaryKey = 'idgrupodx';
    public $timestamps = false;

    protected $fillable = [
        'idgrupodx',
        'idempresa',
        'idcicloatencion',
        'nombre', 
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];
    
    protected $hidden = ['idempresa'];

    public function grid($param, $likename = '', $items = '', $orderName = '', $orderSort = '', $fields = []) {
        
        if(!empty($fields)) {
            $campos = $fields;
        } else {
            $campos = ['grupodx.idgrupodx', 'grupodx.nombre'];   
        }      
        
        $select = \DB::table('grupodx')
                ->select($campos)
                ->where($param)
                ->whereNull('grupodx.deleted');
        
        if (!empty($likename)) {
            $select->where('grupodx.nombre', 'like', '%' . $likename . '%');
        }
        
        // dd($items);
        if(!empty($items)) {
            $data = $select
                ->orderBy($orderName, $orderSort)
                ->paginate($items);
        } else {
            $data = $select 
                ->orderBy('grupodx.nombre', 'ASC') 
                ->get()->all();
        }
        
        return $data;
    }  

    public function grupodx($id) {

        $data = \DB::table('grupodx')           
                ->select('grupodx.idgrupodx', 'grupodx.nombre')
                ->whereNull('grupodx.deleted')
                ->where('grupodx.idgrupodx', $id)
                ->first(); 

        return $data;
    }
}
