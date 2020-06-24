<?php

namespace App\Models;

class autorizacionterapia extends apimodel {

    protected $table = 'autorizacionterapia';
    protected $primaryKey = 'idautorizacionterapia';
    public $timestamps = false;
    protected $fillable = [
        'idempresa', 
        'idsede', 
        'idcliente', 
        'idpersonal',                    
        'fecha',
        'usado',
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
     
    public function grid($param, $likename='', $items = '', $orderName = '', $orderSort = '', $fields = []) {
        
        if(empty($fields)){
            $fields = ['autorizacionterapia.idautorizacionterapia', 'autorizacionterapia.idcliente', 
                       'cliente.entidad as cliente', 'personal.entidad as personal', 'documento.abreviatura as nombredocumento', 'cliente.numerodoc',
                       'autorizacionterapia.fecha', 'autorizacionterapia.usado', 'autorizacionterapia.descripcion'];
        }
        
        $select = \DB::table('autorizacionterapia')  
                ->join('entidad as cliente', 'autorizacionterapia.idcliente', '=', 'cliente.identidad')
                ->join('entidad as personal', 'autorizacionterapia.idpersonal', '=', 'personal.identidad')                
                ->leftJoin('documento', 'cliente.iddocumento', '=', 'documento.iddocumento')  
                ->select($fields) 
                ->whereNull('autorizacionterapia.deleted')
                ->where($param);
        
        if (!empty($likename))
            $select->where('cliente.entidad', 'like', '%' . $likename . '%');         
         
        if(!empty($orderName) && !empty($orderSort)){
            $select->orderBy($orderName, $orderSort);
        }else{
            $select->orderBy('autorizacionterapia.fecha', 'ASC');
        }
         
        if(!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        }
        
        foreach ($data as $row) {
            $row->fecha = $this->formatFecha($row->fecha);
        }
        
        return $data;
    } 
    
    
    public function autorizacionterapia($id = '', $param = []) {
      
        $select = \DB::table('autorizacionterapia')             
                ->select('autorizacionterapia.fecha')
                ->whereNull('autorizacionterapia.deleted');
        
        if(!empty($id))
            $select->where('idautorizacionterapia', $id);        
        
        if(!empty($param))
            $select->where($param);        
                
        $row = $select->first();
                 
        if(!empty($row->fecha))
            $row->fecha = $this->formatFecha($row->fecha);
        
        return $row;  
    }
    
}
