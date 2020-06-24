<?php

namespace App\Models;
 
class terapia extends apimodel {

    protected $table = 'terapia';
    protected $primaryKey = 'idterapia';
    public $timestamps = false;
    protected $fillable = [
        'idempresa', 
        'idsede',  
        'idpaciente', 
        'idcamilla',  
        'idterapista',   
        'idterapistajefe',   
        'idestado',  
        'hora_llegada',
        'hora_sala',
        'hora_salida',
        'fecha',
        'inicio',
        'fin',
        'tiempo',
        'terminar',
        'idcitaterapeutica',
        'hora_cita',
        'asistencia',
        'identidadctrol',
        'fechactrol',
        'control',
        'controlcomentario',
        'idcicloatencion',
        'montodisponible',
        'montoefectuado',
        'montopendiente',
        'montopagado',
        'numerosms',
        'codigosms',
        'puntajesms',
        'textosms',
        'numerosalasms',
        'codigosalasms',
        'puntajesalasms',
        'textosalasms',
        'comentario',
        'firma',
        'identidadfirma',
        'fechafirma',
        'identidadfirmadel',
        'fechafirmadel',
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];
    protected $hidden = ['idempresa'];
    
    public function terapia($id, $param = [], $whereIn = []) {
        
        $campos = ['terapia.idterapia', 'terapia.idsede', 'sede.nombre as nombresede', 'terapia.idterapista',  'terapia.idcamilla', 
                   'terapista.entidad as nombreterapista', 'terapia.idpaciente', 'paciente.entidad as nombrepaciente', 'terapia.idterapistajefe', 'paciente.numerodoc', 'documento.abreviatura as nombredocumento', 'paciente.telefono', 'paciente.celular'
                   , 'paciente.email',  
                   'terapia.idestado', 'estadodocumento.nombre as estadocita', 'terapia.fecha', 'terapia.inicio', 'terapia.fin', 
                   'camilla.nombre as nombrecamilla', 'terapia.terminar', 'terapia.hora_llegada', 'terapia.fechactrol', 'terapia.control', 'terapia.controlcomentario',
                   'responsable.entidad as nombreresponsable', 'terapia.comentario', 'terapia.tiempo'];
        
        $select = \DB::table('terapia')
                ->join('sede', 'terapia.idsede', '=', 'sede.idsede')
                ->leftJoin('entidad as terapista', 'terapia.idterapista', '=', 'terapista.identidad')
                ->join('entidad as paciente', 'terapia.idpaciente', '=', 'paciente.identidad')
                ->leftJoin('historiaclinica', function($join) {
                    $join->on('paciente.identidad', '=', 'historiaclinica.idpaciente')
                         ->on('historiaclinica.idsede', '=', 'terapia.idsede');
                })
                ->join('documento', 'paciente.iddocumento', '=', 'documento.iddocumento')
                ->join('estadodocumento', 'terapia.idestado', '=', 'estadodocumento.idestadodocumento')   
                ->leftJoin('camilla', 'terapia.idcamilla', '=', 'camilla.idcamilla')
                ->leftJoin('entidad as responsable', 'terapia.identidadctrol', '=', 'responsable.identidad') 
                ->select($campos)
                ->whereNull('terapia.deleted');
         
        if(!empty($id)){
            $select->where('terapia.idterapia', $id);
        }
        
        if(!empty($param)){
            $select->where($param);
        }
        
        if (!empty($whereIn)) {
            $select->whereIn('terapia.idestado', $whereIn);
        }
        
        $row = $select->first();
        
        if(isset($row->fecha)) { 
            $row->fecha = $this->formatFecha($row->fecha);
        }
        
        return $row; 
    }
    
    public function grid($param, $betweendate = '', $likename = '', $items = '', $orderName = '', $orderSort = '', $whereIn = array(), 
                    $tratamientos = false, $whereIdterapiaIn = array(), $betweenhora = '', $ano = '', $fields = []) {
        // Se creun un INDICE, en el campo "idterapia" de la tabla "terapiatratamiento", paso de max_execution 30s(colgado) a 672.5ms
        // \DB::enableQueryLog();  

        if(empty($fields)){  
            $fields = ['terapia.idterapia', 'terapia.idpaciente', 'terapia.idestado', 'terapia.idcamilla', 'terapia.hora_llegada', 'terapia.hora_sala', 'terapia.hora_salida', 'terapia.fecha', 'terapia.inicio', 'terapia.fin', 
            'cliente.entidad as paciente', 'documento.abreviatura as nombredocumento', 'cliente.numerodoc', 'cliente.sexo', 'cliente.imgperfil', 'cliente.celular', 'estadodocumento.nombre as estadocita', 'terapia.hora_cita', 'terapia.asistencia', 
            'terapia.idterapista', 'terapista.entidad as nombreterapista', 'terapia.idsede', 'sede.nombre as nombresede', 'historiaclinica.hc', 'camilla.nombre as nombrecamilla', 
            'terapia.montodisponible', 'terapia.montoefectuado', 'terapia.montopendiente', 'terapia.montopagado', 'terapia.idcicloatencion', 'terapia.idcitaterapeutica', 'terapia.puntajesms', 'terapia.puntajesalasms', 'terapia.textosalasms', 'terapia.textosms', 'cliente.sms_sat', 'terapia.codigosalasms', 'terapia.codigosms', 'terapistajefe.entidad as nombreterapistajefe', 'terapia.comentario', 'terapia.firma', 'terapia.tiempo'];
        }

        $select = \DB::table('terapia')
                ->join('sede', 'terapia.idsede', '=', 'sede.idsede')                
                ->join('entidad as cliente', 'terapia.idpaciente', '=', 'cliente.identidad')
                ->join('documento', 'cliente.iddocumento', '=', 'documento.iddocumento')
                ->join('estadodocumento', 'terapia.idestado', '=', 'estadodocumento.idestadodocumento')
                ->leftJoin('camilla', 'terapia.idcamilla', '=', 'camilla.idcamilla')
                ->leftJoin('entidad as terapista', 'terapia.idterapista', '=', 'terapista.identidad')
                ->leftJoin('entidad as terapistajefe', 'terapia.idterapistajefe', '=', 'terapistajefe.identidad')
                ->leftJoin('historiaclinica', function($join) {
                    $join->on('cliente.identidad', '=', 'historiaclinica.idpaciente')
                         ->on('historiaclinica.idsede', '=', 'terapia.idsede');
                }); 
        if($tratamientos){
            array_push($fields, 'medico.entidad as medico', 'terapiatratamiento.idcicloatencion', 'terapiatratamiento.cantidad',
                                'producto.idproducto', 'producto.codigo', 'producto.nombre as nombreproducto');
            
            $select->leftJoin('terapiatratamiento', 'terapia.idterapia', '=', 'terapiatratamiento.idterapia')
                    ->leftJoin('producto', 'terapiatratamiento.idproducto', '=', 'producto.idproducto')
                    ->leftJoin('camilla', 'terapia.idcamilla', '=', 'camilla.idcamilla')                
                    ->leftJoin('cicloatencion', 'terapiatratamiento.idcicloatencion', '=', 'cicloatencion.idcicloatencion')
                    ->leftJoin('entidad as medico', 'cicloatencion.idmedico', '=', 'medico.identidad');
        }
         
        $select->select($fields)
                ->whereNull('terapia.deleted')
                ->where($param);

        if (!empty($betweendate)) {
            $select->whereBetween('terapia.fecha', $betweendate);
        }

        if (!empty($likename)) {
            $select->where('cliente.entidad', 'like', '%' . $likename . '%');
        }

        if (!empty($whereIn)) {
            $select->whereIn('terapia.idestado', $whereIn);
        }
        
        if (!empty($whereIdterapiaIn)) {
            $select->whereIn('terapia.idterapia', $whereIdterapiaIn);
        }
        
        if (!empty($betweenhora)) {
            $select->whereBetween('terapia.hora_llegada', $betweenhora);
        }

        if (!empty($ano)) {
            $select->whereRaw("YEAR(terapia.fecha) = " . $ano);
        }
 
        $orderName = !empty($orderName) ? $orderName : 'terapia.fecha';
        $orderSort = !empty($orderSort) ? $orderSort : 'desc';

        $select->orderBy($orderName, $orderSort)
                ->orderBy('terapia.inicio', 'desc');

        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        } 
        // dd(\DB::getQueryLog());
        
        foreach ($data as $row) {
            if(isset($row->fecha))
                $row->fecha = $this->formatFecha($row->fecha);
        }
        
        return $data;
    } 

    public function gridlight($param, $betweendate = '', $likename = '', $items = '', $orderName = '', $orderSort = '', $whereIn = array(), 
                    $tratamientos = false, $whereIdterapiaIn = array(), $betweenhora = '', $ano = '', $fields = []) {
      

        $select = \DB::table('terapia') 
                ->leftJoin('entidad as terapista', 'terapia.idterapista', '=', 'terapista.identidad') 
                ->select($fields)
                ->whereNull('terapia.deleted')
                ->where($param);

        if (!empty($betweendate)) {
            $select->whereBetween('terapia.fecha', $betweendate);
        } 

        if (!empty($whereIn)) {
            $select->whereIn('terapia.idestado', $whereIn);
        }
        
        if (!empty($whereIdterapiaIn)) {
            $select->whereIn('terapia.idterapia', $whereIdterapiaIn);
        }  
        
        if (!empty($betweenhora)) {
            $select->whereBetween('terapia.hora_llegada', $betweenhora);
        }

        $orderName = !empty($orderName) ? $orderName : 'terapia.fecha';
        $orderSort = !empty($orderSort) ? $orderSort : 'desc';

        $select->orderBy($orderName, $orderSort);

        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        }  
        
        foreach ($data as $row) {
            if(isset($row->fecha))
                $row->fecha = $this->formatFecha($row->fecha);
        }
        
        return $data;
    } 

    public function camillasterapia($param, $idestado, $fecha) {
        //dd($param2);
        $data = \DB::table('camilla')  
                ->leftJoin('terapia', function($join) use($idestado, $fecha){ 
                    $join->on('camilla.idcamilla', '=', 'terapia.idcamilla')
                         ->where('terapia.idestado', '=', $idestado)
                         ->where('terapia.fecha', '=', $fecha);
                })
                ->leftJoin('entidad as cliente', 'terapia.idpaciente', '=', 'cliente.identidad') 
                ->leftJoin('documento', 'cliente.iddocumento', '=', 'documento.iddocumento')
                ->select('camilla.idcamilla', 'camilla.nombre', 
                         'terapia.idterapia', 'terapia.idpaciente', 'terapia.fecha', 'terapia.inicio', 'terapia.fin', 'terapia.terminar',
                         'cliente.entidad as paciente', 'cliente.imgperfil', 'cliente.sexo', 'documento.abreviatura as nombredocumento', 'cliente.numerodoc', 'cliente.celular', 'cliente.sms_sat')
                ->whereNull('camilla.deleted')
                ->where($param)  
                ->orderBy('camilla.nombre', 'ASC')
                ->get()->all();
                
        foreach ($data as $row) {           
            $row->fecha = $this->formatFecha($row->fecha);
        }
        //dd($data);
        return $data;
    }
    
    public function camillasdisponibles($param, $param2) {
        // \DB::enableQueryLog(); 
        $data = \DB::table('camilla')  
                ->whereNotExists(function ($query) use($param2)  {
                    $query->select(\DB::raw(1))
                          ->from('terapia')
                          ->where($param2)
                          ->whereNull('terapia.deleted')
                          ->whereRaw('terapia.idcamilla = camilla.idcamilla');
                })
                ->select('camilla.idcamilla', 'camilla.nombre')
                ->whereNull('camilla.deleted')
                ->where($param)  
                ->orderBy('camilla.nombre', 'ASC')
                ->get()->all();
        
        //dd($data); 
        //dd(\DB::getQueryLog()); 
        return $data;
    } 

    public function camillasnodisponibles($param) {
        // \DB::enableQueryLog(); 
        $data = \DB::table('terapia')   
                ->select('idcamilla')     
                ->whereNotNull('idcamilla')           
                ->whereNull('deleted')
                ->where($param)   
                ->get()->all();
        
        //dd($data); 
        //dd(\DB::getQueryLog()); 
        return $data;
    }
    
    public function terapiatratamientos($param, $fields = [], $cantMayor = false, $distinct = false, $betweendate='', $whereNotIn='', $betweenhora = '', $whereIn = array(), $ano = '', $whereIdcicloatencionIn = [], $responFirma = false) {
        
        if(empty($fields)){  
            $fields = ['terapia.fecha', 'terapia.hora_llegada', 'terapia.hora_sala', 'terapia.hora_salida', 'terapiatratamiento.idcicloatencion',                       
                       'terapiatratamiento.cantidad', 'terapia.inicio','terapia.fin', 'sede.idsede', 'sede.nombre as nombresede', 'sede.sedeabrev',
                       'producto.idproducto', 'producto.codigo', 'producto.nombre as nombreproducto', 'terapista.entidad as nombreterapista', 'terapia.idpaciente', 'paciente.entidad as paciente', 'medico.entidad as medico', 'camilla.nombre as nombrecamilla',
                       'estadodocumento.nombre as estadoterapia', 'terapia.idterapista', 'terapia.idterapia', 'historiaclinica.hc', 'terapiatratamiento.idgrupodx'];

            if(!empty($ano)){
                $fields[] = \DB::raw('MONTH(terapia.fecha) as mes');
            }
        }
        
        $select = \DB::table('terapia')  
                ->join('estadodocumento', 'terapia.idestado', '=', 'estadodocumento.idestadodocumento')
                ->join('sede', 'terapia.idsede', '=', 'sede.idsede')
                ->join('terapiatratamiento', 'terapia.idterapia', '=', 'terapiatratamiento.idterapia') 
                ->join('producto', 'terapiatratamiento.idproducto', '=', 'producto.idproducto')
                ->join('camilla', 'terapia.idcamilla', '=', 'camilla.idcamilla')
                ->join('entidad as terapista', 'terapia.idterapista', '=', 'terapista.identidad')
                ->join('entidad as paciente', 'terapia.idpaciente', '=', 'paciente.identidad');
                
        if ($responFirma) { 
            $select->leftJoin('entidad as respfirma', 'terapia.identidadfirma', '=', 'respfirma.identidad');
        }

        $select = $select->leftJoin('historiaclinica', function($join) {
                    $join->on('paciente.identidad', '=', 'historiaclinica.idpaciente')
                         ->on('historiaclinica.idsede', '=', 'terapia.idsede');
                })            
                ->join('cicloatencion', 'terapiatratamiento.idcicloatencion', '=', 'cicloatencion.idcicloatencion')
                ->leftJoin('entidad as medico', 'cicloatencion.idmedico', '=', 'medico.identidad')
                ->select($fields)
                ->whereNull('terapia.deleted')
                ->whereNull('cicloatencion.deleted')
                ->whereNull('terapiatratamiento.deleted');
         
        if (!empty($betweendate)) {
                $select->whereBetween('terapia.fecha', $betweendate);
        }
        
        if (!empty($whereNotIn)) {
                $select->whereNotIn('producto.idproducto', $whereNotIn);
        }

        if (!empty($betweenhora)) {
            $select->whereBetween('terapia.hora_llegada', $betweenhora);
        }

        if (!empty($ano)) {
            $select->whereRaw("YEAR(terapia.fecha) = " . $ano);
        }

        if (!empty($whereIn)) {
            $select->whereIn('terapia.idestado', $whereIn);
        }

        if (!empty($whereIdcicloatencionIn)) { 
            $select->whereIn('cicloatencion.idcicloatencion', $whereIdcicloatencionIn);
        }

        if($cantMayor)
                $select->where('terapiatratamiento.cantidad', '>', 0);
        
        if($distinct)
                $select->distinct();
        
        $data = $select
                ->where($param)  
                ->orderBy('terapia.fecha', 'ASC')
                ->orderBy('terapia.hora_llegada', 'ASC')
                ->get()->all(); 
        
        foreach ($data as $row) {
            if(isset($row->fecha))
                $row->fecha = $this->formatFecha($row->fecha); 
        }
        
        return $data;
    }

    public function terapiatratamientoslight($param = '', $fields = [], $cantMayor = false, $betweendate='', $whereNotIn=[], $order = false, $whereIdcicloatencionIn = [], $distinct = false, $whereIdterapiaIn = []) {
          

        $select = \DB::table('terapia')   
                ->join('terapiatratamiento', 'terapia.idterapia', '=', 'terapiatratamiento.idterapia')  
                ->join('entidad as terapista', 'terapia.idterapista', '=', 'terapista.identidad')       
                ->join('cicloatencion', 'terapiatratamiento.idcicloatencion', '=', 'cicloatencion.idcicloatencion')
                ->select($fields)
                ->whereNull('terapia.deleted') 
                ->whereNull('terapiatratamiento.deleted');
        
        if (!empty($param)) {
            $select->where($param);
        }  

        if (!empty($betweendate)) {
            $select->whereBetween('terapia.fecha', $betweendate);
        }  
        
        if (!empty($whereNotIn)) {
            $select->whereNotIn('terapiatratamiento.idproducto', $whereNotIn);
        }

        if (!empty($whereIdterapiaIn)) {
            $select->whereIn('terapiatratamiento.idterapia', $whereIdterapiaIn);
        }

        if (!empty($whereIdcicloatencionIn)) {
            $select->whereIn('terapiatratamiento.idcicloatencion', $whereIdcicloatencionIn);
        }

        if($cantMayor)
            $select->where('terapiatratamiento.cantidad', '>', 0);         

        if($distinct)
            $select->distinct();

        if($order) {
            $select->orderBy('terapia.fecha', 'ASC');
        }

        $data = $select->get()->all();  
                
        foreach ($data as $row) {
            if(isset($row->fecha))
                $row->fecha = $this->formatFecha($row->fecha); 
            
            if(isset($row->fechaopenciclo))
                $row->fechaopenciclo = $this->formatFecha($row->fechaopenciclo); 
        }

        return $data;
    }

    public function ultimoTratamiento($param) {         
        
        $row = \DB::table('terapia')   
                ->join('terapiatratamiento', 'terapia.idterapia', '=', 'terapiatratamiento.idterapia')  
                ->select('terapia.fecha')
                ->whereNull('terapia.deleted') 
                ->whereNull('terapiatratamiento.deleted')
                ->where('terapiatratamiento.cantidad', '>', 0) 
                ->where($param)  
                ->orderBy('terapia.fecha', 'desc')
                ->first(); 
         
        return $row ? $row->fecha : null;
    }

    public function primerTratamiento($param) {         
        
        $row = \DB::table('terapia')   
                ->join('terapiatratamiento', 'terapia.idterapia', '=', 'terapiatratamiento.idterapia')  
                ->select('terapia.fecha')
                ->whereNull('terapia.deleted') 
                ->whereNull('terapiatratamiento.deleted')
                ->where('terapiatratamiento.cantidad', '>', 0) 
                ->where($param)  
                ->orderBy('terapia.fecha', 'asc')
                ->first(); 
         
        return $row ? $row->fecha : null;
    }
    
    public function updateTerapia($data, $id) {
        \DB::table('terapia')->where('idterapia', $id)->update($data);
    }

    public function grabarLog($idterapia, $id_created_at) {

        $logterapia = terapia::where('idterapia', '=', $idterapia)->first()->getAttributes(); 
        $logterapia['created_at'] = date('Y-m-d H:i:s');
        $logterapia['id_created_at'] = $id_created_at; 

        $logterapiadet = \DB::table('terapiatratamiento')
                        ->whereNull('terapiatratamiento.deleted')
                        ->where('idterapia', $idterapia)->get()->all();  

        $idlogterapia = \DB::table('logterapia')->insertGetId($logterapia, 'idlogterapia'); 

        $datalogterapiadet = array();
        foreach($logterapiadet as $row){
            $row->idlogterapia = $idlogterapia;
            unset ($row->idterapiatratamiento);
            $datalogterapiadet[] = (array) $row;
        }

        \DB::table('logterapiadet')->insert($datalogterapiadet);
    }
  
    public function listaLogTerapia($idterapia) {

        $data = \DB::table('logterapia')                
                ->join('entidad as usuario', 'logterapia.id_created_at', '=', 'usuario.identidad')
                ->select('logterapia.idlogterapia', 'logterapia.created_at as fecha', 'usuario.entidad as usuario')
                ->whereNull('logterapia.deleted')
                ->where('logterapia.idterapia', $idterapia)
                ->orderBy('logterapia.idlogterapia', 'DESC')
                ->get()->all();

        return $data;
    }  

    public function logterapia($id) {

        $campos = ['logterapia.idterapia', 'logterapia.idsede', 'sede.nombre as nombresede', 'logterapia.idterapista', 'logterapia.idcamilla',
            'terapista.entidad as nombreterapista', 'logterapia.idpaciente', 'paciente.entidad as nombrepaciente', 'terapistajefe.entidad as nombrejefeterapista',  
            'paciente.numerodoc', 'documento.abreviatura as nombredocumento', 'paciente.telefono', 'paciente.celular', 'paciente.email', 'historiaclinica.hc',
            'logterapia.idestado', 'estadodocumento.nombre as estadocita', 'logterapia.fecha', 'logterapia.inicio', 'logterapia.fin', 'camilla.nombre as nombrecamilla', 'logterapia.terminar'];
 
        $select = \DB::table('logterapia') 
                ->join('sede', 'logterapia.idsede', '=', 'sede.idsede')
                ->leftJoin('entidad as terapista', 'logterapia.idterapista', '=', 'terapista.identidad')
                ->leftJoin('entidad as terapistajefe', 'logterapia.idterapistajefe', '=', 'terapistajefe.identidad')                
                ->join('entidad as paciente', 'logterapia.idpaciente', '=', 'paciente.identidad')
                ->leftJoin('historiaclinica', function($join) {
                    $join->on('paciente.identidad', '=', 'historiaclinica.idpaciente')
                    ->on('historiaclinica.idsede', '=', 'logterapia.idsede');
                })
                ->join('documento', 'paciente.iddocumento', '=', 'documento.iddocumento')
                ->join('estadodocumento', 'logterapia.idestado', '=', 'estadodocumento.idestadodocumento')
                ->leftJoin('camilla', 'logterapia.idcamilla', '=', 'camilla.idcamilla')
                ->select($campos)
                ->whereNull('logterapia.deleted');

        if (!empty($id)) {
            $select->where('logterapia.idlogterapia', $id);
        }

        $row = $select->first();

        if ($row) {
            $row->fecha = $this->formatFecha($row->fecha);
        }

        return $row;
    }

    public function logterapiatratamientos($param) { 

        $data = \DB::table('logterapia')                                
                ->join('sede', 'logterapia.idsede', '=', 'sede.idsede')
                ->join('entidad as paciente', 'logterapia.idpaciente', '=', 'paciente.identidad')           
                ->join('logterapiadet', 'logterapia.idlogterapia', '=', 'logterapiadet.idlogterapia')
                ->join('producto', 'logterapiadet.idproducto', '=', 'producto.idproducto') 
                ->select('logterapiadet.idcicloatencion', 'logterapiadet.cantidad',  'producto.nombre as nombreproducto')
                ->where($param)
                ->whereNull('logterapia.deleted') 
                ->whereNull('logterapiadet.deleted')  
                ->orderBy('logterapia.fecha', 'ASC')
                ->get()->all(); 

        return $data;
    }

    

    public function tecnicasmanuales($param) { 
        
        $data = \DB::table('terapiatecnica')
                ->join('terapia', 'terapia.idterapia', '=', 'terapiatecnica.idterapia')  
                ->select('terapiatecnica.*', 'terapia.fecha')
                ->where($param) 
                ->orderBy('terapia.fecha', 'ASC')
                ->get()->all();
            
        foreach ($data as $row) {
            if ($row->fecha) 
                $row->fecha = $this->formatFecha($row->fecha);
        }
        
        return $data; 
    }

    public function procedimientos($param) {
        $data = \DB::table('terapiaprocedimiento')   
                ->join('terapia', 'terapia.idterapia', '=', 'terapiaprocedimiento.idterapia')  
                ->select('terapiaprocedimiento.*', 'terapia.fecha', 'terapia.fecha', 'terapia.inicio')
                ->whereNull('terapia.deleted') 
                ->whereNull('terapiaprocedimiento.deleted_at')
                ->where($param)
                ->orderBy('terapia.fecha', 'ASC')
                ->get()->all();  

        foreach ($data as $row) {
            if ($row->fecha) 
                $row->fecha = $this->formatFecha($row->fecha);
        }

        return $data;
    }

    public function puntosimagen($param) {
        $data = \DB::table('terapiaimagen')     
                ->select('terapiaimagen.*') 
                ->where($param) 
                ->get()->all();   

        return $data;
    }

    public function GrabarProcedimientos($data, $where) {
        \DB::table('terapiaprocedimiento')->where($where)->delete();
        \DB::table('terapiaprocedimiento')->insert($data);
    }
    
    public function GrabarTecnicas($data, $where) {
        \DB::table('terapiatecnica')->where($where)->delete();
        \DB::table('terapiatecnica')->insert($data);
    }

    public function GrabarPuntos($data, $where) {
        \DB::table('terapiaimagen')->where($where)->delete();
        \DB::table('terapiaimagen')->insert($data);
    }
}
