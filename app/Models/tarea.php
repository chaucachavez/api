<?php

namespace App\Models; 

class tarea extends apimodel {

    protected $table = 'tarea';
    protected $primaryKey = 'idtarea';
    public $timestamps = false;

    protected $fillable = [
        'idtarea',
        'idsede',
        'identidad',
        'idcitamedica',
        'idcicloatencion',
        'idcitaterapeutica',       
        'idestado',       
        'idautomatizacion',   
        'cantdiasrest',
        'created_at',
        'id_created_at' 
    ];

    protected $hidden = ['idempresa'];
    
    public function grid($param, $betweendate = [], $likename = '', $items = '', $orderName = '', $orderSort = '', $fields = [], $whereIn = [], $whereInCitaterapeutica = []) {
        
        $campos = ['tarea.idtarea', 'tarea.idsede', 'tarea.identidad', 'tarea.idcitamedica', 'tarea.idcicloatencion', 'tarea.idcitaterapeutica', 'tarea.idestado', 'tarea.idautomatizacion', 'tarea.cantdiasrest', 'tarea.created_at', 'tarea.id_created_at', 'paciente.entidad as nombrepaciente', 'automatizacion.nombre as nombreautomatizacion', 'estadodocumento.nombre as nombreestado', 'presupuesto.total', 'presupuesto.montopago', 'sede.sedeabrev', 'cmcreated.entidad as cmpropietario', 'terapiacreated.entidad as terapiapropietario', 'ciclocreated.entidad as ciclopropietario'];

        if (!empty($fields)) {
            $campos = $fields;
        }
        
        $select = \DB::table('tarea') 
                ->join('sede', 'tarea.idsede', '=', 'sede.idsede')
                ->join('estadodocumento', 'tarea.idestado', '=', 'estadodocumento.idestadodocumento')
                ->join('entidad as paciente', 'tarea.identidad', '=', 'paciente.identidad')
                ->join('automatizacion', 'tarea.idautomatizacion', '=', 'automatizacion.idautomatizacion')
                ->leftJoin('cicloatencion', 'tarea.idcicloatencion', '=', 'cicloatencion.idcicloatencion')
                ->leftJoin('citamedica', 'tarea.idcitamedica', '=', 'citamedica.idcitamedica')
                ->leftJoin('citaterapeutica', 'tarea.idcitaterapeutica', '=', 'citaterapeutica.idcitaterapeutica') 
                ->leftJoin('presupuesto', 'cicloatencion.idcicloatencion', '=', 'presupuesto.idcicloatencion')
                ->leftJoin('entidad as cmcreated', 'citamedica.id_created_at', '=', 'cmcreated.identidad')
                ->leftJoin('entidad as terapiacreated', 'citaterapeutica.id_created_at', '=', 'terapiacreated.identidad')
                ->leftJoin('entidad as ciclocreated', 'cicloatencion.id_created_at', '=', 'ciclocreated.identidad')
                ->select($campos)
                ->whereNull('tarea.deleted') 
                ->where($param);

        if (!empty($whereIn)) {
            $select->whereIn('tarea.idcicloatencion', $whereIn);
        }

        if (!empty($whereInCitaterapeutica)) {
            $select->whereIn('tarea.idcitaterapeutica', $whereInCitaterapeutica);
        }

        if (!empty($likename)) {
            // dd($likename);
            $select->where('paciente.entidad', 'like', '%' . $likename . '%');
        }

        if (!empty($betweendate)) {
            // $select->whereRaw("CONCAT(fecha,' ',inicio) BETWEEN '".$betweenFechaHora[0]."' and '".$betweenFechaHora[1]."'");
            $rango = array($betweendate[0] . ' 00:00:00', $betweendate[1] . ' 23:59:59');
            $select->whereBetween('tarea.created_at', $rango);
        }

        if (!empty($items)) {
            $data = $select
                ->orderBy($orderName, $orderSort)
                ->paginate($items);
        } else {
            $data = $select 
                ->orderBy('tarea.idtarea', 'ASC') 
                ->get()->all();
        } 

        foreach ($data as $row) { 
            $fecha = $this->formatFecha(substr($row->created_at, 0, 10));
            $hora = substr($row->created_at, 11, 8);
            $row->created_at = $fecha .' '. $hora; 
        }

        return $data;
    }

    public function tarea($id) {
        $campos = ['tarea.idtarea', 'tarea.identidad', 'tarea.idsede', 'tarea.idcitamedica', 'tarea.idcicloatencion', 'tarea.idcitaterapeutica', 'tarea.idestado', 'tarea.idautomatizacion', 'tarea.created_at', 'tarea.id_created_at', 'paciente.entidad as nombrepaciente', 'automatizacion.nombre as nombreautomatizacion', 'estadodocumento.nombre as nombreestado', 'presupuesto.total', 'presupuesto.montopago'];

        $data = \DB::table('tarea')
                ->join('estadodocumento', 'tarea.idestado', '=', 'estadodocumento.idestadodocumento')
                ->join('entidad as paciente', 'tarea.identidad', '=', 'paciente.identidad')
                ->join('automatizacion', 'tarea.idautomatizacion', '=', 'automatizacion.idautomatizacion')
                ->leftJoin('cicloatencion', 'tarea.idcicloatencion', '=', 'cicloatencion.idcicloatencion')
                ->leftJoin('presupuesto', 'cicloatencion.idcicloatencion', '=', 'presupuesto.idcicloatencion')
                ->select($campos)                
                ->where('tarea.idtarea', $id) 
                ->whereNull('tarea.deleted') 
                ->first();

        if ($data) {
            // $data->fecha = $this->formatFecha($data->fecha);

            $fecha = $this->formatFecha(substr($data->created_at, 0, 10));
            $hora = substr($data->created_at, 11, 8);
            $data->created_at = $fecha .' '. $hora;
        }

        return $data;
    }
    
}
