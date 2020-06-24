<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class asistencia extends apimodel {

    protected $table = 'asistencia';
    protected $primaryKey = 'idasistencia';
    public $timestamps = false;
    protected $fillable = [
        'idasistencia',
        'idempresa',
        'idsede',
        'idplanhorario',
        'nombre',
        'identidad',
        'idexcepcion',
        'laborfechainicio',
        'laborfechafin',
        'laborinicio',
        'laborfin',
        'tiempoprogramado',
        'estado',
        'tipo', 
        'fecha',
        'horai',
        'horao',
        'tiempo',
        'tiempotardanza',
        'tiempoextra',
        'tardanza',
        'sancion',
        'plan60',
        'observacion',
        'registro',
        'ipi',
        'ipo',
        'adjunto',
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];

    protected $hidden = ['idempresa'];

    public function grid($param, $between='', $likename = '', $items = '', $orderName = '', $orderSort = '', $betweenhora='', $whereInFecha= '') {
        
        // dd($param, $between, $likename, $items, $orderName, $orderSort);

        $select = \DB::table('asistencia')
            ->join('sede', 'asistencia.idsede', '=', 'sede.idsede')
            ->join('entidad as personal', 'asistencia.identidad', '=', 'personal.identidad')
            ->leftJoin('entidad as updated', 'asistencia.id_updated_at', '=', 'updated.identidad')
            ->leftJoin('planhorario', 'asistencia.idplanhorario', '=', 'planhorario.idplanhorario')
            ->leftJoin('excepcion', 'asistencia.idexcepcion', '=', 'excepcion.idexcepcion')
            ->select('asistencia.idasistencia', 'asistencia.nombre', 'asistencia.laborfechainicio', 'asistencia.laborfechafin', 'asistencia.laborinicio', 'asistencia.laborfin','asistencia.estado', 'asistencia.tipo', 'asistencia.idsede', 'asistencia.identidad', 'asistencia.idexcepcion','asistencia.fecha', 'asistencia.horai',
                'asistencia.horao', 'asistencia.tiempo', 'asistencia.tiempoprogramado', 'tiempotardanza', 'tiempoextra', 'asistencia.registro', 'asistencia.ipi', 'asistencia.ipo', 'personal.entidad as personal', 'sede.nombre as nombresede', 'sede.sedeabrev', 'updated.entidad as updated', 'asistencia.tardanza', 'asistencia.sancion', 'asistencia.plan60', 'asistencia.observacion', 'excepcion.nombre as nombremotivo', 'asistencia.adjunto')
            ->whereNull('asistencia.deleted')
            ->where($param);

        if (!empty($likename)) {
            $select->where('personal.entidad', 'like', '%' . $likename . '%');
            //$select->whereRaw('sp_ascii(perfil.nombre) ilike sp_ascii(?) ', ['%' . $likename . '%']);
        }

        if (!empty($between)) {
            // $select->whereBetween('asistencia.fecha', $between);
            $select->whereBetween('asistencia.laborfechainicio', $between);
        }

        if (!empty($betweenhora)) {
            $select->whereBetween('asistencia.laborinicio', $betweenhora);
        }

        if (!empty($whereInFecha)) {
            $select->whereIn('asistencia.laborfechainicio', $whereInFecha);
        }

        $orderName = !empty($orderName) ? $orderName : 'asistencia.laborfechainicio';
        $orderSort = !empty($orderSort) ? $orderSort : 'desc';

        $select->orderBy($orderName, $orderSort)
                // ->orderBy('asistencia.horai', 'desc');
        ->orderBy('asistencia.laborinicio', 'asc');
                
        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        } 

        foreach ($data as $row) {
            $row->fecha = $this->formatFecha($row->fecha);
            $row->laborfechainicio = $this->formatFecha($row->laborfechainicio);
            $row->laborfechafin = $this->formatFecha($row->laborfechafin); 
        }

        return $data;
    }

    public function asistencia($param = []) {
        $fields = ['asistencia.idasistencia',  'asistencia.idsede', 'asistencia.identidad', 'asistencia.idexcepcion', 'asistencia.fecha', 'asistencia.horai',
            'asistencia.horao', 'asistencia.tiempo', 'asistencia.registro', 'asistencia.ipi', 'asistencia.ipo',
            'personal.entidad as personal', 'sede.nombre as nombresede', 'asistencia.adjunto'];

        $row = \DB::table('asistencia')
            ->join('sede', 'asistencia.idsede', '=', 'sede.idsede')
            ->join('entidad as personal', 'asistencia.identidad', '=', 'personal.identidad')
            ->select($fields)
            ->whereNull('asistencia.deleted')
            ->where($param)
            ->first();

        $row->fecha = $this->formatFecha($row->fecha);

        return $row;
    }

}
