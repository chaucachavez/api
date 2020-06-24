<?php

namespace App\Models; 

class autorizacionimagen extends apimodel {

    protected $table = 'autorizacionimagen';
    protected $primaryKey = 'idautorizacionimagen';
    public $timestamps = false;
    
    protected $fillable = [
        'idautorizacionimagen',
        'idcicloautorizacion', 
        'nombre',
        'archivo',
        'orden',
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
            $campos = ['idautorizacionimagen', 'idcicloautorizacion', 'nombre', 'archivo', 'orden']; 
        }      
        
        $select = \DB::table('autorizacionimagen')
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
                ->orderBy('orden', 'ASC') 
                ->get()->all();
        }
        
        return $data;
    }  

    public function autorizacionimagen($id) {
        $data = \DB::table('galeria')           
                ->select('idautorizacionimagen', 'idcicloautorizacion', 'nombre', 'archivo', 'orden')
                ->whereNull('deleted')
                ->where('idautorizacionimagen', $id)
                ->first(); 

        return $data;
    }

    public function autorizacionconimagenes($idcicloatencion) {

        $campos = ['cicloautorizacion.idcicloautorizacion'];

        $data = \DB::table('autorizacionimagen') 
                ->join('cicloautorizacion', 'autorizacionimagen.idcicloautorizacion', '=', 'cicloautorizacion.idcicloautorizacion')
                ->join('cicloatencion', 'cicloautorizacion.idcicloatencion', '=', 'cicloatencion.idcicloatencion')
                ->select($campos)
                ->where('cicloatencion.idcicloatencion', $idcicloatencion)
                ->whereNull('cicloautorizacion.deleted')
                ->whereNull('autorizacionimagen.deleted')
                ->orderBy('orden', 'ASC')
                ->distinct() 
                ->get()->all(); 
        
        return $data;
    }


}
