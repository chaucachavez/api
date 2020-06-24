<?php

namespace App\Models; 

class galeria extends apimodel {

    protected $table = 'galeria';
    protected $primaryKey = 'idgaleria';
    public $timestamps = false;

    protected $fillable = [
        'idgaleria',
        'idempresa',
        'identidad',
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

    public function grid($param, $likename = '', $items = '', $orderName = '', $orderSort = '', $fields = []) {
        
        if(!empty($fields)) {
            $campos = $fields;
        } else {
            $campos = ['galeria.idgaleria', 'galeria.identidad', 'galeria.nombre', 'galeria.archivo'];   
        }      
        
        $select = \DB::table('galeria')
                ->select($campos)
                ->where($param)
                ->whereNull('galeria.deleted');
        
        if (!empty($likename)) {
            $select->where('galeria.nombre', 'like', '%' . $likename . '%');
        }
        
        // dd($items);
        if(!empty($items)) {
            $data = $select
                ->orderBy($orderName, $orderSort)
                ->paginate($items);
        } else {
            $data = $select 
                ->orderBy('galeria.nombre', 'ASC') 
                ->get()->all();
        }
        
        return $data;
    }  

    public function galeria($id) {

        $data = \DB::table('galeria')           
                ->select('galeria.idgaleria', 'galeria.identidad', 'galeria.nombre', 'galeria.archivo')
                ->whereNull('galeria.deleted')
                ->where('galeria.idgaleria', $id)
                ->first(); 

        return $data;
    }
}
