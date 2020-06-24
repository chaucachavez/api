<?php

namespace App\Models; 

class proceso extends apimodel {

    protected $table = 'proceso';
    protected $primaryKey = 'idproceso';
    public $timestamps = false;

    protected $fillable = [
        'idproceso',
        'idempresa',
        'idautomatizacion',
        'nombre',
        'orden',
        'plantillasms',       
        'plantillamail',  
        'activosms',
        'activomail',
        'created_at',
        'id_created_at' 
    ];

    protected $hidden = ['idempresa'];
    
    public function grid($param, $betweendate = [], $likename = '', $items = '', $orderName = '', $orderSort = '', $fields = []) {
        
        $campos = ['proceso.idproceso', 'proceso.idempresa', 'proceso.idautomatizacion', 'proceso.nombre', 'proceso.orden', 'proceso.plantillasms', 'proceso.plantillamail', 'proceso.created_at', 'proceso.id_created_at', 'automatizacion.nombre as nombreautomatizacion', 'proceso.activosms', 'proceso.activomail'];

        if (!empty($fields)) { 
            $campos = $fields;
        }
        
        $select = \DB::table('proceso')  
                ->join('automatizacion', 'proceso.idautomatizacion', '=', 'automatizacion.idautomatizacion')  
                ->select($campos)
                ->whereNull('proceso.deleted') 
                ->where($param); 

        if (!empty($betweendate)) { 
            $select->whereBetween('proceso.created_at', $betweendate);
        }

        if (!empty($items)) {
            $data = $select
                ->orderBy($orderName, $orderSort)
                ->paginate($items);
        } else {
            $data = $select 
                ->orderBy('proceso.idproceso', 'ASC') 
                ->get()->all();
        } 

        foreach ($data as $row) { 
            $fecha = $this->formatFecha(substr($row->created_at, 0, 10));
            $hora = substr($row->created_at, 11, 8);
            $row->created_at = $fecha .' '. $hora;
        }

        return $data;
    }

    public function proceso($id) {
        $campos = ['proceso.idproceso', 'proceso.idempresa', 'proceso.idautomatizacion', 'proceso.nombre', 'proceso.orden', 'proceso.plantillasms', 'proceso.plantillamail', 'proceso.created_at', 'proceso.id_created_at', 'automatizacion.nombre as nombreautomatizacion', 'proceso.activosms', 'proceso.activomail'];

        $data = \DB::table('proceso')  
                ->join('automatizacion', 'proceso.idautomatizacion', '=', 'automatizacion.idautomatizacion')  
                ->select($campos)
                ->whereNull('proceso.deleted')        
                ->where('proceso.idproceso', $id) 
                ->first();

        if ($data) {
            $fecha = $this->formatFecha(substr($data->created_at, 0, 10));
            $hora = substr($data->created_at, 11, 8);
            $data->created_at = $fecha .' '. $hora;
        }

        return $data;
    }
    
}
