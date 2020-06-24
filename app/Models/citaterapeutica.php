<?php

namespace App\Models;
  
class citaterapeutica extends apimodel {

    protected $table = 'citaterapeutica';
    protected $primaryKey = 'idcitaterapeutica';
    public $timestamps = false;
    protected $fillable = [
        'idempresa', 
        'idsede', 
        'idterapista', 
        'idpaciente',  
        'idestado',   
        'fecha', 
        'inicio', 
        'fin', 
        'notificado',
        'idaseguradora',
        'idcamilla',
        'reservaportal',
        'idcitareferencia',
        'puntajeatencion',
        'puntajeantes',
        'puntajedespues',
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];

    protected $hidden = ['idempresa']; 
    
    public function citaterapeutica($id) {
        
        $campos = ['citaterapeutica.idcitaterapeutica', 'citaterapeutica.idsede', 'sede.nombre as nombresede', 'citaterapeutica.idterapista', 
                   'terapista.entidad as nombreterapista', 'citaterapeutica.idpaciente', 'paciente.entidad as nombrepaciente', 
                   'paciente.numerodoc', 'documento.abreviatura as nombredocumento', 'paciente.telefono', 'paciente.celular', 'paciente.email',
                   'citaterapeutica.idestado', 'estadodocumento.nombre as estadocita', 'citaterapeutica.fecha', 'citaterapeutica.inicio', 'citaterapeutica.fin', 'citaterapeutica.idaseguradora', 'aseguradora.nombre as nombreseguro'];
        
        $data = \DB::table('citaterapeutica')
                ->join('sede', 'citaterapeutica.idsede', '=', 'sede.idsede')
                ->join('entidad as terapista', 'citaterapeutica.idterapista', '=', 'terapista.identidad')
                ->leftJoin('entidad as paciente', 'citaterapeutica.idpaciente', '=', 'paciente.identidad')
                ->leftJoin('documento', 'paciente.iddocumento', '=', 'documento.iddocumento')
                ->join('estadodocumento', 'citaterapeutica.idestado', '=', 'estadodocumento.idestadodocumento')          
                ->leftJoin('aseguradora', 'citaterapeutica.idaseguradora', '=', 'aseguradora.idaseguradora')
                ->select($campos)
                ->whereNull('citaterapeutica.deleted')
                ->where('citaterapeutica.idcitaterapeutica', $id)
                ->first();
        
        if($data) { 
            $data->fecha = $this->formatFecha($data->fecha);
        }
        
        return $data; 
    }
    
    public function listaLog($id) {
        // \DB::enableQueryLog(); 
        $data = \DB::table('citaterapeuticalog')  

                ->join('entidad as usuario', 'citaterapeuticalog.id_created_at', '=', 'usuario.identidad')
                ->select('citaterapeuticalog.idcitaterapeuticalog', 'citaterapeuticalog.descripcion', 
                         'citaterapeuticalog.created_at as fecha', 'usuario.entidad as usuario')                
                ->where('idcitaterapeutica', $id) 
                ->orderBy('citaterapeuticalog.idcitaterapeuticalog', 'DESC')
                ->get()->all();
        
        // dd(\DB::getQueryLog()); 
        foreach ($data as $row) {
            if ($row->fecha) {
                $fecha = $this->formatFecha(substr($row->fecha, 0, 10));
                $hora = substr($row->fecha, 11, 8);

                $row->fecha = $fecha . ' ' . $hora;
            }  
        }

        return $data;
    }

    // 24.10.2019 parametro "$noprogramado = false" se usaba en CRON probablemente este en desuso.
    public function grid($param, $betweendate='', $likename= '', $items = '', $orderName = '', $orderSort = '', $whereIn = array(), $whereInMed = array(), $betweenhora='', $whereInPac = [], $fechaMayor = '', $fields = [], $betweenCreatedAt = [], $betweenFechaHora = [], $noprogramado = false, $whereInFecha = [],  $whereInCamilla = [], $whereInId = [], $rawWhere = '') { 

        if(empty($fields)){
            $fields = ['citaterapeutica.idcitaterapeutica', 'citaterapeutica.idterapista', 'citaterapeutica.idpaciente', 'citaterapeutica.idestado', 
                        'citaterapeutica.fecha', 'citaterapeutica.inicio', 'citaterapeutica.fin', 'sede.idsede', 'sede.nombre as sedenombre', 
                        'terapista.entidad as terapista', 'terapista.colorcss', 'cliente.entidad as paciente', 'estadodocumento.nombre as estadocita',
                        'cliente.apellidopat', 'cliente.nombre', 'created.entidad as created', 'citaterapeutica.created_at as createdat', 'citaterapeutica.idaseguradora', 'aseguradora.nombre as nombreseguro', 'citaterapeutica.idcamilla', 'sede.sedeabrev', 'citaterapeutica.puntajeatencion', 'citaterapeutica.puntajeantes', 'citaterapeutica.puntajedespues'];
        }

        // \DB::enableQueryLog(); 
        $select = \DB::table('citaterapeutica')
                ->join('sede', 'citaterapeutica.idsede', '=', 'sede.idsede')
                ->join('entidad as terapista', 'citaterapeutica.idterapista', '=', 'terapista.identidad')                
                ->join('entidad as created', 'citaterapeutica.id_created_at', '=', 'created.identidad')
                ->join('estadodocumento', 'citaterapeutica.idestado', '=', 'estadodocumento.idestadodocumento')                
                ->leftJoin('entidad as cliente', 'citaterapeutica.idpaciente', '=', 'cliente.identidad')
                ->leftJoin('aseguradora', 'citaterapeutica.idaseguradora', '=', 'aseguradora.idaseguradora')
                ->select($fields)
                ->whereNull('citaterapeutica.deleted')
                ->where($param); 
        
        if (!empty($betweendate)) {
            $select->whereBetween('citaterapeutica.fecha', $betweendate);
        }

        if (!empty($fechaMayor)) {
            $select->where('citaterapeutica.fecha', '>=', $fechaMayor);
        } 
        
        if (!empty($betweenhora)) {
            $select->whereBetween('citaterapeutica.inicio', $betweenhora);
        }
        
        if (!empty($betweenCreatedAt)) {
            $select->whereBetween('citaterapeutica.created_at', $betweenCreatedAt);
        } 

        if (!empty($betweenFechaHora)) {
            $select->whereRaw("CONCAT(fecha,' ',inicio) BETWEEN '".$betweenFechaHora[0]."' and '".$betweenFechaHora[1]."'"); 
        } 

        if ($noprogramado) {             
            $select->whereNotExists(function($query)
                {
                    $query->select(\DB::raw(1))
                        ->from('citaterapeutica as ct')
                        ->whereRaw('ct.fecha > citaterapeutica.fecha and 
                                    ct.idpaciente = citaterapeutica.idpaciente')
                        ->whereNull('ct.deleted');
                });
        }

        if (!empty($likename)) {
            $select->where('cliente.entidad', 'like', '%' . $likename . '%');
            //$select->whereRaw('sp_ascii(entidad.entidad) ilike sp_ascii(?) ', ['%' . $likename . '%']);
        }
         
        if (!empty($whereInId)) {
            $select->whereIn('citaterapeutica.idcitaterapeutica', $whereInId);
        }

        if (!empty($whereIn)) {
            $select->whereIn('citaterapeutica.idestado', $whereIn);
        }
        
        if (!empty($whereInMed)) {
            $select->whereIn('citaterapeutica.idterapista', $whereInMed);
        }

        if (!empty($whereInPac)) {
            $select->whereIn('citaterapeutica.idpaciente', $whereInPac);
        }

        if (!empty($whereInFecha)) {
            $select->whereIn('citaterapeutica.fecha', $whereInFecha);
        }

        if (!empty($whereInCamilla)) {
            $select->whereIn('citaterapeutica.idcamilla', $whereInCamilla);
        }

        if (!empty($rawWhere)) {
            $select->whereRaw($rawWhere);
        }
        
        if(!empty($items)) {
            $data = $select
                ->orderBy($orderName, $orderSort)
                ->paginate($items);
        } else {
            if (!empty($orderName) && !empty($orderSort)) { 
                $data = $select
                    ->orderBy($orderName, $orderSort) 
                    ->orderBy('citaterapeutica.inicio', 'ASC')
                    ->get()->all();
            } else { 
                $data = $select
                    ->orderBy('citaterapeutica.fecha', 'DESC')
                    ->orderBy('citaterapeutica.inicio', 'ASC')
                    ->get()->all();
            }
            
        }
        
        // dd(\DB::getQueryLog()); 
        foreach ($data as $row) {
            $row->fecha = $this->formatFecha($row->fecha);
            if (isset($row->createdat)){
                $row->createdtimeat = substr($row->createdat, 11, 8);            
                $row->createdat = $this->formatFecha(substr($row->createdat, 0, 10));
            }
        }

        return $data;
    }

    public function gridreservas($param, $betweendate='', $likename= '', $items = '', $orderName = '', $orderSort = '', $whereIn = array(),  $fields = [], $betweenUpdatedAt = []) { 

        if(empty($fields)){
            $fields = ['citaterapeutica.idcitaterapeutica', 'citaterapeutica.fecha', 'citaterapeutica.inicio', 'citaterapeutica.idestado', 'cliente.entidad as paciente', 'estadodocumento.nombre as estadocita', 'updated.entidad as updated',  'sede.sedeabrev', 'citaterapeutica.updated_at'];

            // , 'citaref.fecha as fecharef', 'citaref.inicio as inicioref'
        }

        // \DB::enableQueryLog(); 
        $select = \DB::table('citaterapeutica')
                ->join('sede', 'citaterapeutica.idsede', '=', 'sede.idsede')     
                ->join('estadodocumento', 'citaterapeutica.idestado', '=', 'estadodocumento.idestadodocumento')                
                ->join('entidad as cliente', 'citaterapeutica.idpaciente', '=', 'cliente.identidad')
                ->leftJoin('entidad as updated', 'citaterapeutica.id_updated_at', '=', 'updated.identidad') 
                // ->leftJoin('citaterapeutica as citaref', 'citaterapeutica.idcitareferencia', '=', 'citaref.idcitaterapeutica') 
                ->select($fields)
                ->whereNull('citaterapeutica.deleted')
                ->where($param); 
        
        if (!empty($betweendate)) {
            $select->whereBetween('citaterapeutica.fecha', $betweendate);
        } 
        
        if (!empty($betweenUpdatedAt)) {
            $select->whereBetween('citaterapeutica.updated_at', $betweenUpdatedAt);
        } 
 
        if (!empty($likename)) {
            $select->where('cliente.entidad', 'like', '%' . $likename . '%');
            //$select->whereRaw('sp_ascii(entidad.entidad) ilike sp_ascii(?) ', ['%' . $likename . '%']);
        }
          
        if (!empty($whereIn)) {
            $select->whereIn('citaterapeutica.idestado', $whereIn);
        } 
        
        if(!empty($items)) {
            $data = $select
                ->orderBy($orderName, $orderSort)
                ->paginate($items);
        } else {
            if (!empty($orderName) && !empty($orderSort)) { 
                $data = $select
                    ->orderBy($orderName, $orderSort) 
                    ->orderBy('citaterapeutica.inicio', 'ASC')
                    ->get()->all();
            } else { 
                $data = $select
                    ->orderBy('citaterapeutica.fecha', 'ASC')
                    ->orderBy('citaterapeutica.inicio', 'ASC')
                    ->get()->all();
            } 
        }
        
        // dd(\DB::getQueryLog()); 
        foreach ($data as $row) {
            $row->fecha = $this->formatFecha($row->fecha);
            if (isset($row->updated_at)){ 
                $row->updated_at = $this->formatFecha(substr($row->updated_at, 0, 10)) . ' ' .substr($row->updated_at, 11, 8);
            } 
            // if (isset($row->fecharef)){ 
            //     $row->fecharef = $this->formatFecha($row->fecharef);
            // }
        }
        
        return $data;
    }

    public function segurosPaciente($param) {


        $data = \DB::table('cicloautorizacion')
                    ->join('aseguradora', 'cicloautorizacion.idaseguradora', '=', 'aseguradora.idaseguradora')
                    ->join('cicloatencion', 'cicloautorizacion.idcicloatencion', '=', 'cicloatencion.idcicloatencion')
                    ->select('aseguradora.idaseguradora', 'aseguradora.nombre as nombreseguro', 'aseguradora.nroagenda')
                    ->whereNull('cicloautorizacion.deleted')
                    ->where($param)
                    ->distinct() //tener cuidado, no poner idcicloatencion
                    ->get()->all(); 

        return $data;   
    }

    public function griddistinct($param, $betweendate='', $likename= '', $items = '', $orderName = '', $orderSort = '', $whereIn = array(), $whereInMed = array(), $betweenhora='') {
        // \DB::enableQueryLog(); 
        $select = \DB::table('citaterapeutica')
                ->join('sede', 'citaterapeutica.idsede', '=', 'sede.idsede')
                ->join('entidad as terapista', 'citaterapeutica.idterapista', '=', 'terapista.identidad')
                ->join('entidad as cliente', 'citaterapeutica.idpaciente', '=', 'cliente.identidad') 
                // ->join('cicloautorizacion', function($join) { 
                //     $join->on('cicloautorizacion.idpaciente', '=', 'cliente.identidad')
                //          ->on('cicloautorizacion.idsede', '=', 'citaterapeutica.idsede');
                // }) 
                ->join('entidad as created', 'citaterapeutica.id_created_at', '=', 'created.identidad')
                ->join('estadodocumento', 'citaterapeutica.idestado', '=', 'estadodocumento.idestadodocumento')
                ->join('cicloautorizacion', 'citaterapeutica.idpaciente', '=', 'cicloautorizacion.idpaciente')
                ->select('citaterapeutica.idcitaterapeutica', 'citaterapeutica.idterapista', 'citaterapeutica.idpaciente', 'citaterapeutica.idestado', 
                        'citaterapeutica.fecha', 'citaterapeutica.inicio', 'citaterapeutica.fin', 'sede.idsede', 'sede.nombre as sedenombre', 
                        'terapista.entidad as terapista', 'terapista.colorcss', 'cliente.entidad as paciente', 'estadodocumento.nombre as estadocita',
                        'cliente.apellidopat', 'cliente.nombre', 'created.entidad as created', 'citaterapeutica.created_at as createdat', 'cicloautorizacion.idaseguradora')
                ->whereNull('citaterapeutica.deleted')
                ->whereNull('cicloautorizacion.deleted')
                ->where($param); 
        
        if (!empty($betweendate)) {
            $select->whereBetween('citaterapeutica.fecha', $betweendate);
        }
        
        if (!empty($betweenhora)) {
            $select->whereBetween('citaterapeutica.inicio', $betweenhora);
        }
        
        if (!empty($likename)) {
            $select->where('cliente.entidad', 'like', '%' . $likename . '%');
            //$select->whereRaw('sp_ascii(entidad.entidad) ilike sp_ascii(?) ', ['%' . $likename . '%']);
        }
        
        if (!empty($whereIn)) {
            $select->whereIn('citaterapeutica.idestado', $whereIn);
        }
        
        if (!empty($whereInMed)) {
            $select->whereIn('citaterapeutica.idterapista', $whereInMed);
        }
        
        if(!empty($items)) {
            $data = $select 
                ->orderBy($orderName, $orderSort)
                ->paginate($items);
                dd('NO PLANEADO ESTO');
        } else {
            $data = $select
                // ->orderBy('citaterapeutica.fecha', 'DESC')
                // ->orderBy('citaterapeutica.inicio', 'ASC')
                ->distinct()
                ->get()->all();
        }
        
        // dd(\DB::getQueryLog()); 
        foreach ($data as $row) {
            $row->fecha = $this->formatFecha($row->fecha);
            $row->createdtimeat = substr($row->createdat, 11, 8);
            $row->createdat = $this->formatFecha(substr($row->createdat, 0, 10));
        }

        return $data;
    }


    public function grabarLog($id, $id_created_at, $valores = []) {

        $camposauditables = ['idterapista', 'idestado', 'fecha', 'inicio', 'notificado', 'idaseguradora'];

        $camposauditablesdesc = ['idterapista' => 'terapeuta', 'idestado' => 'estado', 'fecha' => 'fecha reserva', 'inicio' => 'hora reserva', 'idaseguradora' => 'aseguradora'];

        if (!empty($valores)) {
            $citaterapeutica = citaterapeutica::where('idcitaterapeutica', '=', $id)->first()->getAttributes(); 

            $descripcion = '';

            foreach ($citaterapeutica as $index => $valor) {
                foreach ($valores  as $index2 => $valornuevo) {  
                    // $descripcion.= '(Omitir) ' . $index . '|' . $index2;                   
                    if (in_array($index, $camposauditables) && $index === $index2 && $valor !== $valornuevo) {

                        if($index === 'idterapista') { 

                            $data = \DB::table('entidad')
                                        ->select('entidad')
                                        ->where('identidad', $valor) 
                                        ->first();

                            $valor = $data->entidad;

                            $data = \DB::table('entidad')
                                        ->select('entidad')
                                        ->where('identidad', $valornuevo) 
                                        ->first();

                            $valornuevo = $data->entidad;
                        }

                        if($index === 'fecha') { 
                            $valor = $this->formatFecha($valor);
                            $valornuevo = $this->formatFecha($valornuevo);
                        }

                        if($index === 'idestado') { 
                            if ($valor === 32) 
                                $valor = 'Pendiente';
                            if ($valor === 33) 
                                $valor = 'Confirmada';
                            if ($valor === 34) 
                                $valor = 'Atendida';
                            if ($valor === 35) 
                                $valor = 'Cancelada';

                            if ($valornuevo === 32) 
                                $valornuevo = 'Pendiente';
                            if ($valornuevo === 33) 
                                $valornuevo = 'Confirmada';
                            if ($valornuevo === 34) 
                                $valornuevo = 'Atendida';
                            if ($valornuevo === 35) 
                                $valornuevo = 'Cancelada'; 
                        }

                        $texto = $camposauditablesdesc[$index];

                        $descripcion .= (!empty($descripcion)?'|':'') . ('Cambió '.$texto.' de "'. $valor .'" a "' . $valornuevo.'"');

                        break;
                    }
                }            
            }
        } else {
            $descripcion = 'Registro nuevo creado.';
        }
        
        if (!empty($descripcion)) {
            $dataInsert = array(
                'idcitaterapeutica' => $id,
                'descripcion' => $descripcion,
                'created_at' => date('Y-m-d H:i:s'),
                'id_created_at' => $id_created_at 
            );

            \DB::table('citaterapeuticalog')->insert($dataInsert); 
        } 
    }
    
}
