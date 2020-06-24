<?php

namespace App\Models; 

class etiqueta extends apimodel {

    protected $table = 'etiqueta';
    protected $primaryKey = 'idetiqueta';
    public $timestamps = false;

    protected $fillable = [
        'idetiqueta',
        'idempresa',
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
            $campos = ['etiqueta.idetiqueta', 'etiqueta.nombre'];   
        }      
        
        $select = \DB::table('etiqueta')
                ->select($campos)
                ->where($param)
                ->whereNull('etiqueta.deleted');
        
        if (!empty($likename)) {
            $select->where('etiqueta.nombre', 'like', '%' . $likename . '%');
        }
        
        // dd($items);
        if(!empty($items)) {
            $data = $select
                ->orderBy($orderName, $orderSort)
                ->paginate($items);
        } else {
            $data = $select 
                ->orderBy('etiqueta.nombre', 'ASC') 
                ->get()->all();
        }
        
        return $data;
    }  

    public function etiqueta($id) {

        $data = \DB::table('etiqueta')           
                ->select('etiqueta.idetiqueta', 'etiqueta.nombre')
                ->whereNull('etiqueta.deleted')
                ->where('etiqueta.idetiqueta', $id)
                ->first(); 

        return $data;
    }
}
