<?php

namespace App\Models; 

class cupondescuento extends apimodel {

    protected $table = 'cupondescuento';
    protected $primaryKey = 'idcupondescuento';
    public $timestamps = false;
    protected $fillable = [
        'idcupondescuento',
        'idempresa', 
        'codigo',
        'idmoneda',
        'valor', 
        'descripcion',
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];
    
    protected $hidden = ['idempresa']; 
    
    public function grid($param, $items = '', $orderName = '', $orderSort = '', $fields = [], $whereIn = []) {
        
        $campos = ['idcupondescuento', 'cupondescuento.codigo', 'valor', 
                   'descripcion', 'sede.nombre as nombresede', 'cupondescuento.idsede',  
                   'cupondescuento.idmoneda', 'moneda.simbolo', 'entidad.entidad as nombrecliente', 
                   'documento.abreviatura as nombredocumento', 'entidad.numerodoc'];
        
        if(!empty($fields)){
            $campos = $fields;
        }
        
        $select = \DB::table('cupondescuento')
                ->join('moneda', 'cupondescuento.idmoneda', '=', 'moneda.idmoneda') 
                ->join('historiaclinica', function($join){
                        $join->on('cupondescuento.hc', '=', 'historiaclinica.hc')
                             ->on('cupondescuento.idsede', '=', 'historiaclinica.idsede');
                }) 
                ->join('documento', 'entidad.iddocumento', '=', 'documento.iddocumento')
                ->select($campos)
                ->whereNull('cupondescuento.deleted')
                ->where($param);
                                
        if (!empty($whereIn)) {
            $select->whereIn($whereIn['id'], $whereIn['in']);
        }
        
        if(!empty($items)) {
            $data = $select 
                ->orderBy($orderName, $orderSort)
                ->paginate($items);
        } else {
            $data = $select 
                ->get()->all();
        } 
        
        return $data;
    }
    
    public function cupondescuento($param) {
        
        $data = \DB::table('cupondescuento') 
                ->select('codigo', 'valor')
                ->where($param)
                ->whereNull('cupondescuento.deleted')
                ->first();
         
        return $data;
    }

}
