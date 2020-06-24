<?php

namespace App\Models; 

class calls extends apimodel {

    protected $table = 'calls';
    protected $primaryKey = 'idcalls';
    public $timestamps = false;

    protected $fillable = [
        'idcalls',
        'idempresa', 
        'idcitamedica',
        'idcitaterapeutica',
        'fecha',
        'hora',
        'cliente', 
        'motivo', 
        'tipo', 
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];
    
    protected $hidden = ['idempresa'];

    public function grid($param, $between='', $likename = '', $items = '', $orderName = '', $orderSort = '', $fields = []) {
                        
        $select = \DB::table('calls')
                ->leftJoin('entidad as created', 'calls.id_created_at', '=', 'created.identidad') 

                ->leftJoin('citamedica', 'calls.idcitamedica', '=', 'citamedica.idcitamedica') 
                ->leftJoin('entidad as pacientecita', 'citamedica.idpaciente', '=', 'pacientecita.identidad') 
                ->leftJoin('sede as sedecita', 'citamedica.idsede', '=', 'sedecita.idsede') 

                ->leftJoin('citaterapeutica', 'calls.idcitaterapeutica', '=', 'citaterapeutica.idcitaterapeutica') 
                ->leftJoin('entidad as pacienteterapia', 'citaterapeutica.idpaciente', '=', 'pacienteterapia.identidad') 
                ->leftJoin('sede as sedeterapia', 'citaterapeutica.idsede', '=', 'sedeterapia.idsede') 

                ->select('calls.idcalls', 'calls.fecha', 'calls.hora', 'calls.cliente', 'calls.motivo', 'calls.tipo', 'calls.idcitamedica',  'calls.idcitaterapeutica',  'created.entidad as created', 

                'pacientecita.entidad as pacientecita',  'citamedica.fecha as fechacita', 'citamedica.inicio as iniciocita', 'sedecita.nombre as sedecita',

                'pacienteterapia.entidad as pacienteterapia', 'citaterapeutica.fecha as fechaterapia', 'citaterapeutica.inicio as inicioterapia', 'sedeterapia.nombre as sedeterapia'
                )
                ->where($param)
                ->whereNull('calls.deleted');
        
        if (!empty($between)) {
            $select->whereBetween('calls.fecha', $between);
        }
        
        if (!empty($likename)) {
            $select->where('calls.cliente', 'like', '%' . $likename . '%');
        }
        
        // dd($items);
        if(!empty($items)) {
            $data = $select
                ->orderBy($orderName, $orderSort)
                ->orderBy('calls.hora', 'desc') 
                ->paginate($items);
        } else {
            $data = $select 
                ->orderBy('calls.fecha', 'desc') 
                ->orderBy('calls.hora', 'desc') 
                ->get()->all();
        }

        foreach($data as $row){
            $row->fecha = $this->formatFecha($row->fecha);

            if ($row->fechacita) {
                $row->fechacita = $this->formatFecha($row->fechacita);
            }

            if ($row->fechaterapia) {
                $row->fechaterapia = $this->formatFecha($row->fechaterapia);
            }
        }
        
        return $data;
    }  

    public function calls($id) {

        $data = \DB::table('calls')           
                ->leftJoin('entidad as created', 'calls.id_created_at', '=', 'created.identidad') 
                ->leftJoin('citamedica', 'calls.idcitamedica', '=', 'citamedica.idcitamedica') 
                ->leftJoin('entidad as paciente', 'citamedica.idpaciente', '=', 'paciente.identidad') 
                ->leftJoin('citaterapeutica', 'calls.idcitaterapeutica', '=', 'citaterapeutica.idcitaterapeutica') 
                ->select('calls.fecha', 'calls.hora', 'calls.cliente', 'calls.motivo', 'calls.tipo',
                'paciente.entidad as paciente', 'citamedica.fecha as fechacita', 'citamedica.hora')
                ->whereNull('calls.deleted')
                ->where('calls.idcalls', $id)
                ->first(); 

        if ($data->fecha) {
            $data->fecha = $this->formatFecha($data->fecha);
        }

        return $data;
    }
}
