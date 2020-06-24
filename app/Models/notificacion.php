<?php

namespace App\Models; 

class notificacion extends apimodel {

    protected $table = 'notificacion';
    protected $primaryKey = 'idnotificacion';
    public $timestamps = false;

    protected $fillable = [
        'idnotificacion',         
        'identidad',
        'idproceso',   
        'idcitamedica', 
        'idcicloatencion', 
        'idcitaterapeutica', 
        'sms', 
        'email',
        'sms_numero',
        'email_correo',
        'email_codigo',
        'sms_text',
        'email_text',
        'sms_codigo',
        'sms_count',
        'email_count',
        'created_at'
    ];

    protected $hidden = ['idempresa'];
    
    public function grid($param, $likename = '', $items = '', $orderName = '', $orderSort = '', $fields = [], $whereInCitamedica = [], $whereInCicloatencion = [], $whereInCitaterapeutica = [], $betweendate = []) {
        
        $campos = ['notificacion.idnotificacion', 'notificacion.identidad', 'notificacion.idproceso', 'notificacion.idcitamedica', 'notificacion.idcicloatencion', 'notificacion.idcitaterapeutica', 'notificacion.sms', 'notificacion.email', 'notificacion.sms_numero', 'notificacion.email_correo', 'notificacion.email_codigo', 'notificacion.sms_text', 'notificacion.email_text', 'notificacion.sms_codigo', 'notificacion.sms_count', 'notificacion.email_count', 'notificacion.created_at', 'paciente.entidad as nombrepaciente', 'proceso.nombre as nombreproceso'];

        if (!empty($fields)) {
            $campos = $fields;
        }
        
        $select = \DB::table('notificacion')   
                ->leftJoin('entidad as paciente', 'notificacion.identidad', '=', 'paciente.identidad')
                ->leftJoin('proceso', 'notificacion.idproceso', '=', 'proceso.idproceso')               
                ->select($campos)
                ->where($param);

        if (!empty($likename)) {
            // dd($likename);
            $select->where('paciente.entidad', 'like', '%' . $likename . '%');
        }
              
        if (!empty($whereInCitamedica)) {
            $select->whereIn('notificacion.idcitamedica', $whereInCitamedica);
        }

        if (!empty($whereInCicloatencion)) {
            $select->whereIn('notificacion.idcicloatencion', $whereInCicloatencion);
        }

        if (!empty($whereInCitaterapeutica)) {
            $select->whereIn('notificacion.idcitaterapeutica', $whereInCitaterapeutica);
        }

        if (!empty($betweendate)) {

            // $select->whereRaw("CONCAT(fecha,' ',inicio) BETWEEN '".$betweenFechaHora[0]."' and '".$betweenFechaHora[1]."'");
            $rango = array($betweendate[0] . ' 00:00:00', $betweendate[1] . ' 23:59:59');
            $select->whereBetween('notificacion.created_at', $rango);
        }

        if (!empty($items)) {
            $data = $select
                ->orderBy($orderName, $orderSort)
                ->paginate($items);
        } else {
            $data = $select 
                ->orderBy('notificacion.idnotificacion', 'DESC') 
                ->get()->all();
        }


        foreach ($data as $row) {
            $fecha = $this->formatFecha(substr($row->created_at, 0, 10));
            $hora = substr($row->created_at, 11, 8);
            $row->created_at = $fecha .' '. $hora;
        }
        return $data;
    }
    
}
