<?php

namespace App\Models; 

class citamedicaarchivo extends apimodel {

    protected $table = 'citamedicaarchivo';
    protected $primaryKey = 'idcitamedicaarchivo';
    public $timestamps = false;
    
    protected $fillable = [
        'idcitamedicaarchivo',
        'idcitamedica', 
        'nombre',
        'archivo',
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];
    
    protected $hidden = ['idempresa'];

    public function grid($param, $items = '', $orderName = '', $orderSort = '', $fields = []) {
        
        if(!empty($fields)) {
            $campos = $fields;
        } else {
            $campos = ['idcitamedicaarchivo', 'idcitamedica', 'nombre', 'archivo']; 
        }      
        
        $select = \DB::table('citamedicaarchivo')
                ->select($campos)
                ->where($param)
                ->whereNull('deleted');
         
        // dd($items);
        if(!empty($items)) {
            $data = $select
                ->orderBy($orderName, $orderSort)
                ->paginate($items);
        } else {
            $data = $select 
                ->orderBy('idcitamedicaarchivo', 'ASC') 
                ->get()->all();
        }
        
        return $data;
    }  
}
