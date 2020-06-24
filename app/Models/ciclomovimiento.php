<?php

namespace App\Models; 

class ciclomovimiento extends apimodel {

    protected $table = 'ciclomovimiento';
    protected $primaryKey = 'idciclomovimiento';
    public $timestamps = false;
    protected $fillable = [
        'idempresa',
        'idcicloatencion',
        'idsede',
        'numero',
        'monto',
        'fecha',
        'identidad',
        'tipo',
        'idventa',
        'idcicloatencionref',
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];
    protected $hidden = ['idempresa']; 

    public function grid($param, $likename = '', $betweendate = '', $items = '', $orderName = '', $orderSort = '') {
        // \DB::enableQueryLog();         

        $select = \DB::table('ciclomovimiento')
                ->join('sede', 'ciclomovimiento.idsede', '=', 'sede.idsede')
                ->join('entidad', 'ciclomovimiento.identidad', '=', 'entidad.identidad')
                ->select('entidad.entidad', 'sede.nombre as nombresede', 'sede.nombre as nombresede', 'sede.sedeabrev', 'ciclomovimiento.idcicloatencion',
                        'ciclomovimiento.numero', 'ciclomovimiento.monto', 'ciclomovimiento.fecha', 'ciclomovimiento.idciclomovimiento',
                        'ciclomovimiento.tipo', 'ciclomovimiento.idcicloatencionref')
                ->whereNull('ciclomovimiento.deleted')
                ->where($param); 

        if(isset($param['ciclomovimiento.idcicloatencion'])){ 
            $select->orWhere('ciclomovimiento.idcicloatencionref', $param['ciclomovimiento.idcicloatencion'])
                   ->whereNull('ciclomovimiento.deleted');
        }

        if (!empty($likename))
            $select->where('entidad.entidad', 'like', '%' . $likename . '%');        
        
        if (!empty($betweendate))
            $select->whereBetween('ciclomovimiento.fecha', $betweendate);        
        
        if (empty($orderName))
            $orderName = 'ciclomovimiento.fecha';
  
        if (empty($orderSort))
            $orderSort = 'ASC';
        
        $select->orderBy($orderName, $orderSort)
               ->orderBy('ciclomovimiento.numero', 'DESC');
         
        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        }
        // dd(\DB::getQueryLog());
        foreach ($data as $row) {
            if (isset($row->fecha))
                $row->fecha = $this->formatFecha($row->fecha);
        }
        
        
        return $data;
    }
    
    public function movimiento($param, $param2) {
        // \DB::enableQueryLog();         

        //Resta dinero del ciclo
        $fists = \DB::table('ciclomovimiento') 
                ->select('ciclomovimiento.idcicloatencion', 'ciclomovimiento.fecha', 'ciclomovimiento.monto', 
                        'ciclomovimiento.tipo', 'ciclomovimiento.idcicloatencionref', 'ciclomovimiento.numero',\DB::raw("'notadebito' as tiponota"))
                ->whereNull('ciclomovimiento.deleted')
                ->where($param); 
        

        //Otorga dinero al ciclo con 'Nota crédito interno'
        $data = \DB::table('ciclomovimiento') 
                ->select('ciclomovimiento.idcicloatencion', 'ciclomovimiento.fecha', 'ciclomovimiento.monto', 
                        'ciclomovimiento.tipo', 'ciclomovimiento.idcicloatencionref', 'ciclomovimiento.numero',\DB::raw("'notacredito' as tiponota"))
                ->whereNull('ciclomovimiento.deleted') 
                ->where($param2)  
                ->orderBy('ciclomovimiento.fecha', 'ASC')
                ->union($fists)
                ->get()->all(); 
                

        foreach ($data as $row) {
            if (isset($row->fecha))
                $row->fecha = $this->formatFecha($row->fecha);
        }
         
        return $data;
    }
    
    public function generaNUMERO($idsede) {
        $numero = \DB::table('ciclomovimiento')
                ->where(array('idsede' => $idsede))
                ->max('numero');
        
        return $numero += 1;
    } 
    
    public function ciclomovimiento($id) {
        //\DB::enableQueryLog();
        $data = \DB::table('ciclomovimiento')
                ->join('sede', 'ciclomovimiento.idsede', '=', 'sede.idsede')
                ->join('entidad', 'ciclomovimiento.idmedico', '=', 'entidad.identidad')
                ->select('entidad.entidad', 'sede.nombre as nombresede', 'ciclomovimiento.idcicloatencion', 
                        'ciclomovimiento.numero', 'ciclomovimiento.monto', 'ciclomovimiento.fecha',  
                        'ciclomovimiento.tipo', 'ciclomovimiento.idcicloatencionref')
                ->where('idciclomovimiento', $id)
                ->first();

        //dd(\DB::getQueryLog()); 
        $data->fecha = $this->formatFecha($data->fecha);
        
        return $data;
    }
}
