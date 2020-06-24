<?php

namespace App\Models; 

class citamedica extends apimodel {

    protected $table = 'citamedica';
    protected $primaryKey = 'idcitamedica';
    public $timestamps = false;
    protected $fillable = [
        'idempresa',
        'idsede',
        'idapertura',
        'idmedico',
        'idpaciente', 
        'idestado',
        'idestadopago',
        'idcancelacion',
        'idreferencia',
        'idatencion',        
        'idventa',
        'idordencompra',
        'idcicloatencion',
        'idtipo',
        'fechahora',
        'fecha',
        'inicio',
        'fin',
        'descripcion',
        'motivo',
        'antecedente',
        'idconfirmacion',
        'fechaconfirmacion',
        'nota',
        'enfermedad',
        'enfermedadtiempo',
        'presupuesto',
        'tipocm',
        'tipocmcomentario', 
        'smsreservacion',
        'smsinformativa',
        'costocero',
        'altamedica',
        'altamedicacomentario',
        'rom',
        'romvalor',
        'notaexamenfis',
        'observacion',
        'fechaanterior',
        'horaespera',
        'idpersonalatencion',
        'fechaatencion',
        'horaatencion',
        'iddiagnostico',        
        'idpost', //Desaparecera
        'idpersonalrev',
        'fecharev',
        'cantidadlla',
        'cantidadllae',
        'ultimallae',
        'notaespecialidad',
        'notaexamen',
        'pruebafuncional',
        'fuerzamuscular',
        'notamedicamento',
        'eva',
        'idproducto',        
        'sugerencia',
        'adjunto',
        'idpaquete',  
        'firmapaciente',
        'firmamedico', 
        'idaseguradoraplan', 
        'fvpc',
        'fvfc',
        'fvpeso',
        'fvtalla',
        'frecuencia',
        'descansodesde',
        'descansohasta', 
        'descansodias',
        'telemedicina',
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];
    protected $hidden = ['idempresa']; 
    
    public function citamedica($id) {

        $data = \DB::table('citamedica')
                ->join('sede', 'citamedica.idsede', '=', 'sede.idsede')
                ->join('entidad as medico', 'citamedica.idmedico', '=', 'medico.identidad')
                ->join('entidad as paciente', 'citamedica.idpaciente', '=', 'paciente.identidad')                
                ->join('estadodocumento', 'citamedica.idestado', '=', 'estadodocumento.idestadodocumento')
                ->join('entidad as created', 'citamedica.id_created_at', '=', 'created.identidad')
                ->leftJoin('documento', 'paciente.iddocumento', '=', 'documento.iddocumento') 
                ->leftJoin('historiaclinica', function($join) {
                    $join->on('paciente.identidad', '=', 'historiaclinica.idpaciente')
                    ->on('historiaclinica.idsede', '=', 'citamedica.idsede');
                }) 
                ->leftJoin('estadodocumento as cancelacion', 'citamedica.idcancelacion', '=', 'cancelacion.idestadodocumento')
                ->leftJoin('referenciacita', 'citamedica.idreferencia', '=', 'referenciacita.idreferenciacita')
                ->leftJoin('estadodocumento as atencion', 'citamedica.idatencion', '=', 'atencion.idestadodocumento')
                ->leftJoin('estadodocumento as tipo', 'citamedica.idtipo', '=', 'tipo.idestadodocumento') 
                ->leftJoin('entidad as updated', 'citamedica.id_updated_at', '=', 'updated.identidad')
                ->leftJoin('entidad as confirm', 'citamedica.idconfirmacion', '=', 'confirm.identidad')
                ->leftJoin('producto', 'citamedica.idproducto', '=', 'producto.idproducto')   
                ->leftJoin('aseguradoraplan', 'citamedica.idaseguradoraplan', '=', 'aseguradoraplan.idaseguradoraplan')      
                          
                ->select('citamedica.idcitamedica', 'citamedica.idsede', 'sede.nombre as nombresede', 'sede.sedeabrev', 'citamedica.idmedico', 'medico.entidad as nombremedico', 'citamedica.idpaciente', 
                'paciente.entidad as nombrepaciente', 'paciente.numerodoc', 'documento.abreviatura as nombredocumento', 'paciente.telefono', 'paciente.celular', 'paciente.email', 'paciente.sexo as sexopaciente', 
                 'historiaclinica.hc', 'citamedica.idestado', 'citamedica.idestadopago', 'estadodocumento.nombre as estadocita', 'citamedica.idcancelacion', 'cancelacion.nombre as motivocancelacion', 
                'citamedica.idreferencia', 'referenciacita.nombre as referencia', 'citamedica.idatencion', 'atencion.nombre as atencion', 'citamedica.idventa', 'citamedica.idordencompra', 'citamedica.fecha', 'citamedica.inicio', 
                'citamedica.fin', 'citamedica.descripcion', 'citamedica.motivo', 'citamedica.antecedente', 'citamedica.nota', 'citamedica.enfermedadtiempo', 'citamedica.idcicloatencion', 
                'citamedica.idtipo', 'tipo.nombre as nombretipo', 'paciente.fechanacimiento', \DB::raw('TIMESTAMPDIFF(YEAR, paciente.fechanacimiento, CURDATE()) as edad'), 'created.entidad as created', 'updated.entidad as updated', 
                'confirm.entidad as confirm', 'citamedica.created_at', 'citamedica.updated_at', 'citamedica.fechaconfirmacion', 'citamedica.smsreservacion', 'citamedica.smsinformativa', 'citamedica.costocero', 
                'citamedica.altamedica', 'citamedica.notaespecialidad', 'citamedica.notaexamen', 'citamedica.notamedicamento', 'citamedica.eva', 'citamedica.idproducto', 'producto.nombre as nombreproducto', 
                'citamedica.sugerencia', 'citamedica.adjunto', 'citamedica.idaseguradoraplan','aseguradoraplan.nombre as nombreaseguradoraplan',
            
                'citamedica.firmapaciente',
                'citamedica.firmamedico',
                'citamedica.pruebafuncional',
                'citamedica.fuerzamuscular',
                'citamedica.altamedicacomentario',
                'citamedica.rom',
                'citamedica.romvalor',

                'citamedica.fvpc',
                'citamedica.fvfc',
                'citamedica.fvpeso',
                'citamedica.fvtalla',
                'citamedica.frecuencia',
                'citamedica.descansodesde',
                'citamedica.descansohasta',
                'paciente.ocupacion',
                'paciente.estadocivil',
                'paciente.hijos',
                'citamedica.enfermedad',
                'citamedica.notaexamenfis',
                'citamedica.observacion',
                'citamedica.descansodias',
                'citamedica.telemedicina'                
                )
                ->whereNull('citamedica.deleted')
                ->where('citamedica.idcitamedica', $id)
                ->first();

        if ($data) {
            $data->fecha = $this->formatFecha($data->fecha);
            $data->fechanacimiento = $this->formatFecha($data->fechanacimiento);

            if ($data->descansodesde) {
                $data->descansodesde = $this->formatFecha($data->descansodesde);
            }

            if($data->descansohasta) {
                $data->descansohasta = $this->formatFecha($data->descansohasta);
            }

            if($data->enfermedad) {
                $data->enfermedad = $this->formatFecha($data->enfermedad);
            }
        }

        return $data;
    }
    
    public function referencia($param) {
        //Ultima referencia de cita para el paciente.
        //Importante 'citamedica.fecha', 'DESC'
        $data = \DB::table('citamedica') 
                ->select('citamedica.idreferencia')
                ->whereNull('citamedica.deleted')
                ->where($param)
                ->orderBy('citamedica.fecha', 'DESC')
                ->first(); 

        return $data;
    }
    
    public function grid($param, $betweendate = '', $likename = '', $items = '', $orderName = '', $orderSort = '', $whereIn = array(), $notExists = false, 
                         $whereInMed = array(), $pendiente = false, $pagado = false, $fieldbetween = 'citamedica.fecha', $betweenHour = '', $cobro = false, 
                         $presupuesto = false, $cmpagada = false, $seguimiento = '', $ano = '', $fields = [], $betweenCreatedAt = [], $betweenFechaHora = [], $noprogramado = false, $whereInAtencion = [], $mayorFechaHora = '', $citasdeleted = false, $rawWhere = '', $solocitasdeleted = false, $distrito = false) {
                        
        if(empty($fields)){  
            $fields = ['citamedica.idcitamedica', 'citamedica.idmedico', 'citamedica.idpaciente', 'citamedica.idestado',  'citamedica.idestadopago', 'citamedica.idatencion', 'citamedica.cantidadlla', 'citamedica.cantidadllae',
            'citamedica.fecha', 'citamedica.inicio', 'citamedica.fin', 'sede.idsede', 'sede.nombre as sedenombre','sede.sedeabrev', \DB::raw("IF(DATEDIFF(CURDATE(), citamedica.fecha) > 0, DATEDIFF(CURDATE(), citamedica.fecha), '') as diasinasistencia"),  
            'citamedica.descripcion', 'citamedica.idventa', 'citamedica.idordencompra', 'citamedica.idcicloatencion', 'citamedica.idtipo', 'tipo.nombre as nombretipo', 'tipoatencion.nombre as nombreatencion', 'citamedica.idreferencia', 'referenciacita.nombre as nombrereferencia',
            'medico.entidad as medico', 'medico.sexo as sexomedico', 'medico.colorcss', 'cliente.entidad as paciente', 'cliente.numerodoc', 'cliente.celular', 'cliente.telefono', 'cliente.sexo', 'cliente.imgperfil', 'cliente.fechanacimiento', \DB::raw('TIMESTAMPDIFF(YEAR, cliente.fechanacimiento, citamedica.fecha) as edaddiacita'), 'documento.abreviatura as nombredocumento',
            'estadodocumento.nombre as estadocita', 'citamedica.id_created_at', 'created.entidad as created', 'citamedica.created_at as createdat', 'citamedica.presupuesto', 'citamedica.idapertura', 
            'citamedica.tipocm', 'citamedica.tipocmcomentario', 'citamedica.horaespera', 'atencion.entidad as atencion', 'citamedica.fechaatencion', 'citamedica.horaatencion', 'citamedica.iddiagnostico', 'diagnostico.nombre as diagnostico', 'historiaclinica.hc', 'citamedica.eva', 'aseguradoraplan.nombre as nombreaseguradoraplan', 'cicloatencion.primert', 'citamedica.deleted', 'cicloatencion.idestado as idestadociclo', 'citamedica.telemedicina'];

            if ($cobro || $cmpagada) {
                array_push($fields, 'entidad.identidad as idpersonal', 'entidad.entidad as personal', 'venta.fechaventa', 'venta.horaventa', 'afiliado.acronimo', 'venta.serienumero', 'control.acronimo as acronimoctrol', 'venta.fechactrol');
            }
        }
 
        // \DB::enableQueryLog();  
        $select = \DB::table('citamedica')
                ->join('sede', 'citamedica.idsede', '=', 'sede.idsede')
                ->join('entidad as medico', 'citamedica.idmedico', '=', 'medico.identidad')
                ->join('entidad as cliente', 'citamedica.idpaciente', '=', 'cliente.identidad')
                ->leftJoin('documento', 'cliente.iddocumento', '=', 'documento.iddocumento')
                ->join('estadodocumento', 'citamedica.idestado', '=', 'estadodocumento.idestadodocumento')
                ->join('entidad as created', 'citamedica.id_created_at', '=', 'created.identidad')
                ->leftJoin('estadodocumento as tipo', 'citamedica.idtipo', '=', 'tipo.idestadodocumento')
                ->leftJoin('estadodocumento as tipoatencion', 'citamedica.idatencion', '=', 'tipoatencion.idestadodocumento')                
                ->leftJoin('referenciacita', 'citamedica.idreferencia', '=', 'referenciacita.idreferenciacita') 
                ->leftJoin('entidad as atencion', 'citamedica.idpersonalatencion', '=', 'atencion.identidad')
                ->leftJoin('diagnostico', 'citamedica.iddiagnostico', '=', 'diagnostico.iddiagnostico') 
                ->leftJoin('aseguradoraplan', 'citamedica.idaseguradoraplan', '=', 'aseguradoraplan.idaseguradoraplan')  
                ->leftJoin('cicloatencion', 'citamedica.idcicloatencion', '=', 'cicloatencion.idcicloatencion')  
                ->leftJoin('historiaclinica', function($join) {
                    $join->on('cliente.identidad', '=', 'historiaclinica.idpaciente')
                         ->on('historiaclinica.idsede', '=', 'citamedica.idsede');
                });
        

        if ($distrito) {
            array_push($fields, 'ubigeo.nombre as distrito');            
            $select->leftJoin('ubigeo', 'cliente.idubigeo','=', 'ubigeo.idubigeo');
        }

        if ($cobro) {
            $select->leftJoin('venta', 'citamedica.idventa', '=', 'venta.idventa')
                   ->leftJoin('entidad', 'venta.id_created_at', '=', 'entidad.identidad')
                   ->leftJoin('entidad as afiliado', 'venta.idafiliado', '=', 'afiliado.identidad')
                   ->leftJoin('entidad as control', 'venta.identidadctrol', '=', 'control.identidad');
        }

        if ($cmpagada) {
            $select->join('venta', 'citamedica.idventa', '=', 'venta.idventa')
                   ->join('entidad', 'venta.id_created_at', '=', 'entidad.identidad')
                   ->join('entidad as afiliado', 'venta.idafiliado', '=', 'afiliado.identidad')
                   ->leftJoin('entidad as control', 'venta.identidadctrol', '=', 'control.identidad');
        } 
        
        if ($solocitasdeleted) {
            $select->join('entidad as personaleli', 'citamedica.id_deleted_at', '=', 'personaleli.identidad');
            array_push($fields, 'personaleli.entidad as personaleliminacion');
        }

        $select->select($fields)
                ->where($param);

        if ($notExists) {
            $select->whereNull('citamedica.idcicloatencion');
        }
        
        if ($cmpagada) {
            $select->whereNull('venta.deleted'); //PIENSO QUE NO ES NECESARIO PORQUE, EL idventa de CITAMEDICA, si la boleta esta eliminada no existe
            //$select->whereNull('citamedica.deleted');   //LA CITA ESTA PAGADA, INDISTINTA SI LUEGO SE ELIMINO LA CITA MEDICA  
        } else {
            if ($citasdeleted)  {
                // dd('entra');
                //En excel de descarga, solo en ese caso a AMAC le interesa los eliminados. 
            } else {

                if (!$solocitasdeleted) {
                    $select->whereNull('citamedica.deleted');
                }
            }

        } 

        if ($solocitasdeleted) {
            $select->whereNotNull('citamedica.deleted');
        }
        
        if ($pendiente) {
            $select->whereNull('citamedica.idventa');
        }

        if ($pagado) {
            $select->whereNotNull('citamedica.idventa');
        }

        if ($presupuesto) {
            $select->whereNotNull('citamedica.presupuesto');
            $select->where('citamedica.presupuesto', '!=', ''); //Es cadena 
        }

        if (!empty($betweendate)) {
            $select->whereBetween($fieldbetween, $betweendate);
        }

        if (!empty($betweenHour)) {
            $select->whereBetween('citamedica.inicio', $betweenHour);
        }

        if (!empty($betweenCreatedAt)) {
            $select->whereBetween('citamedica.created_at', $betweenCreatedAt);
        } 

        if (!empty($betweenFechaHora)) {
            $select->whereRaw("CONCAT(citamedica.fecha,' ',citamedica.inicio) BETWEEN '".$betweenFechaHora[0]."' and '".$betweenFechaHora[1]."'");
            // $select->whereBetween('citamedica.fechahora', $betweenFechaHora);
        }

        if (!empty($mayorFechaHora)) {
            $select->whereRaw("CONCAT(citamedica.fecha,' ',citamedica.inicio) > '".$mayorFechaHora."'");
        } 
 
        if ($noprogramado) {             
            $select->whereNotExists(function($query)
                {
                    $query->select(\DB::raw(1))
                        ->from('citamedica as cm')
                        ->whereRaw('cm.fecha > citamedica.fecha and 
                                    cm.idpaciente = citamedica.idpaciente')
                        ->whereNull('cm.deleted');
                });
        }

        if (!empty($ano)) {
            $select->whereRaw("YEAR(citamedica.fecha) = " . $ano);
        }
        
        if ($seguimiento !== '') { 
             if($seguimiento === '0'){
                $select->where(function ($query) use ($betweendate) {                     
                    $query->whereNull('citamedica.cantidadlla');      
                    $query->orwhere('citamedica.cantidadlla', '=', 0);          
                });
            }

            if($seguimiento === '1'){
                $select->where('citamedica.cantidadlla', '>', 0);
            } 
        }
        
        if (!empty($likename)) {
            $select->where('cliente.entidad', 'like', '%' . $likename . '%');
        }

        if (!empty($whereIn)) {
            $select->whereIn('citamedica.idestado', $whereIn);
        }

        if (!empty($whereInMed)) {
            $select->whereIn('citamedica.idmedico', $whereInMed);
        }
        
        if (!empty($whereIdcitamedicaIn)) {
            $select->whereIn('citamedica.idcitamedica', $whereIdcitamedicaIn);
        }

        if (!empty($rawWhere)) {
            $select->whereRaw($rawWhere);
        }

        //18:Sede 19:Telefono 70:Pagina web 
        if (!empty($whereInAtencion)) {
            $select->whereIn('citamedica.idatencion', $whereInAtencion);
        }
        
        $orderName = !empty($orderName) ? $orderName : 'citamedica.fecha';
        $orderSort = !empty($orderSort) ? $orderSort : 'desc';
 
        $select->orderBy($orderName, $orderSort)
                ->orderBy('citamedica.inicio', 'ASC');


        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        } 
        // dd(\DB::getQueryLog());

        foreach ($data as $row) {

            if (isset($row->fecha))
                $row->fecha = $this->formatFecha($row->fecha);
            
            if (isset($row->fechaanterior)){
                $row->fechaanterior = $this->formatFecha($row->fechaanterior);
            }

            if(isset($row->createdat)){
                $row->createdtimeat = substr($row->createdat, 11, 8);
                $row->createdat = $this->formatFecha(substr($row->createdat, 0, 10));
            }

            if(isset($row->createdatsegui)){
                $row->createdatsegui = $this->formatFecha(substr($row->createdatsegui, 0, 10));
            }
            
            if ($cobro || $cmpagada) {
                $row->fechaventa = $this->formatFecha($row->fechaventa);

                if($row->fechactrol)
                    $row->fechactrol = $this->formatFecha($row->fechactrol);
            }

            if (isset($row->fechanacimiento)){
                $row->fechanacimiento = $this->formatFecha($row->fechanacimiento);
            }
        }

        //dd(count($data));
        return $data;
    }

    public function gridLight($param, $betweendate = '', $likename = '', $items = '', $orderName = '', $orderSort = '') {
                        
        if(empty($fields)){  
            $fields = [
                'citamedica.idcitamedica', 'citamedica.idmedico', 'citamedica.fecha', 'citamedica.inicio', 'citamedica.idpaciente', 'citamedica.idsede', 'citamedica.idestado', 'citamedica.idestado', 'citamedica.idestadopago', 'cliente.entidad as paciente', 'estadodocumento.nombre as estadocita', 'sede.nombre as sedenombre', 'citamedica.idventa'];
        }
 
        // \DB::enableQueryLog();  
        $select = \DB::table('citamedica')
                ->join('sede', 'citamedica.idsede', '=', 'sede.idsede')
                ->join('entidad as cliente', 'citamedica.idpaciente', '=', 'cliente.identidad')
                ->join('estadodocumento', 'citamedica.idestado', '=', 'estadodocumento.idestadodocumento')
                ->select($fields)
                ->where($param)
                ->whereNull('citamedica.deleted'); 
 
        if (!empty($betweendate)) {
            $select->whereBetween('citamedica.fecha', $betweendate);
        }  
        
        if (!empty($likename)) {
            $select->where('cliente.entidad', 'like', '%' . $likename . '%');
        }
        
        $orderName = !empty($orderName) ? $orderName : 'citamedica.fecha';
        $orderSort = !empty($orderSort) ? $orderSort : 'desc';
 
        $select->orderBy($orderName, $orderSort)
                ->orderBy('citamedica.inicio', 'ASC');

        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        } 
        // dd(\DB::getQueryLog());

        foreach ($data as $row) {

            if (isset($row->fecha))
                $row->fecha = $this->formatFecha($row->fecha);
            
            if(isset($row->createdat)){
                $row->createdtimeat = substr($row->createdat, 11, 8);
                $row->createdat = $this->formatFecha(substr($row->createdat, 0, 10));
            }

            if (isset($row->fechanacimiento)){
                $row->fechanacimiento = $this->formatFecha($row->fechanacimiento);
            }
        }

        //dd(count($data));
        return $data;
    }
    
    public function citasatendidas($param, $betweendate, $whereIn = array()){
        
        $select = \DB::table('citamedica')
            ->join('cicloatencion', 'citamedica.idcicloatencion', '=', 'cicloatencion.idcicloatencion')
            ->join('presupuesto', 'citamedica.idcicloatencion', '=', 'presupuesto.idcicloatencion')    
            ->join('entidad as medico', 'citamedica.idmedico', '=', 'medico.identidad')
            ->select('cicloatencion.idmedico', 'medico.entidad as medico', 'presupuesto.idestadopago', 'citamedica.fecha')
            ->whereNull('citamedica.deleted')
            ->whereNull('cicloatencion.deleted') 
            ->whereNull('presupuesto.deleted');         
            
        if (!empty($betweendate)) {
            $select->whereBetween('citamedica.fecha', $betweendate);
        }
        
        if (!empty($whereIn)) {
            $select->whereIn('citamedica.idestado', $whereIn);
        } 
        
        $data =  $select
                ->where($param) 
                ->orderBy('citamedica.fecha', 'ASC') //IMPORTA ARA LOS REPORTES no mover
                ->get()->all();        

        foreach ($data as $row) {
            $row->fecha = $this->formatFecha($row->fecha);
        }
        
        return $data; 
    }

    public function controldiario($param, $param2, $betweendate='', $betweendate2='', $whereIn = array(),  $whereInAtencion= []) {
  
        $fists = \DB::table('citamedica')  
                ->join('entidad as medico', 'citamedica.idmedico', '=', 'medico.identidad')
                ->join('sede', 'citamedica.idsede', '=', 'sede.idsede')    
                ->join('entidad as created', 'citamedica.id_created_at', '=', 'created.identidad') 
                ->select('citamedica.idcitamedica',  'citamedica.created_at as fecha', 'sede.idsede', 
                         'sede.nombre as sedenombre', 'citamedica.id_created_at as identidad', 'created.entidad as personal', \DB::raw("'reserva' as tipo"), 'citamedica.presupuesto',
                         'medico.identidad as idmedico', 'medico.entidad as nombremedico', 'citamedica.fecha as fechacita', 'citamedica.inicio', 'citamedica.fin')
                ->whereNull('citamedica.deleted')
                ->where($param) 
                ->whereIn('citamedica.idatencion', $whereInAtencion)
                ->whereBetween('citamedica.created_at', $betweendate)                 
                ->whereIn('citamedica.idestado', $whereIn);
                   
        $data = \DB::table('venta')  
                ->join('citamedica', 'venta.idventa', '=', 'citamedica.idventa')  
                ->join('entidad as medico', 'citamedica.idmedico', '=', 'medico.identidad')
                ->join('sede', 'venta.idsede', '=', 'sede.idsede')    
                ->join('entidad as created', 'venta.id_created_at', '=', 'created.identidad') 
                ->select('citamedica.idcitamedica',  \DB::raw("CONCAT(venta.fechaventa,' ',venta.horaventa)  AS fecha"), 'sede.idsede', 
                         'sede.nombre as sedenombre', 'venta.id_created_at as identidad', 'created.entidad as personal', \DB::raw("'venta' as tipo"), 'citamedica.presupuesto', 
                         'medico.identidad as idmedico', 'medico.entidad as nombremedico', 'citamedica.fecha as fechacita', 'citamedica.inicio', 'citamedica.fin')
                //->whereNull('citamedica.deleted')  //LA CITA ESTA PAGADA, INDISTINTA SI LUEGO SE ELIMINO LA CITA MEDICA              
                //->whereIn('citamedica.idestado', $whereIn)
                ->whereNull('venta.deleted')
                ->where($param2) 
                ->whereBetween('venta.fechaventa', $betweendate2)  
                ->orderBy('venta.id_created_at', 'ASC') 
                ->union($fists)
                ->get()->all(); 
        
        foreach ($data as $row) { 
            $row->hora =  substr($row->fecha, 11, 8);
            $row->fecha = $this->formatFecha(substr($row->fecha, 0, 10));  
            $row->fechacita = $this->formatFecha($row->fechacita);  
        } 
        //dd($data);
        return $data;
    }  
    
    public function diagnosticomedico($param = [], $betweendate = '', $whereIdcicloatencionIn = [], $whereIdcitamedicaIn = [], $whereIdgrupodxIn = []) { 
        $campos = ['citamedica.idcitamedica', 'diagnostico.iddiagnostico', 'diagnostico.codigo', 'diagnostico.nombre', 'diagnosticomedico.idzona', 'grupodx.nombre as nombregrupodx', 'grupodx.idgrupodx'];
        
        $select = \DB::table('diagnostico')
                ->join('diagnosticomedico', 'diagnostico.iddiagnostico', '=', 'diagnosticomedico.iddiagnostico')
                ->join('citamedica', 'diagnosticomedico.idcitamedica', '=', 'citamedica.idcitamedica')
                ->leftJoin('grupodx', 'diagnosticomedico.idgrupodx', '=', 'grupodx.idgrupodx');
                
        if(!empty($whereIdcicloatencionIn)){
            $campos[] = 'cicloatencion.idcicloatencion';
            $select->join('cicloatencion', 'citamedica.idcicloatencion', '=', 'cicloatencion.idcicloatencion');
        }

        $select->select($campos);

        if (!empty($betweendate)) {
            $select->whereBetween('citamedica.fecha', $betweendate);
        }

        if(!empty($whereIdcicloatencionIn)){ 
            $select->whereIn('cicloatencion.idcicloatencion', $whereIdcicloatencionIn);
        }

        if(!empty($whereIdcitamedicaIn)){ 
            $select->whereIn('citamedica.idcitamedica', $whereIdcitamedicaIn);
        }

        if(!empty($whereIdgrupodxIn)){ 
            $select->whereIn('diagnosticomedico.idgrupodx', $whereIdgrupodxIn);
        }
        
        $data =  $select
                ->whereNull('citamedica.deleted')
                ->where($param)
                ->orderBy('citamedica.idcitamedica', 'ASC') 
                ->get()->all();
                
        return $data; 
    }

    public function examenescita($param) { 
        $campos = ['citamedica.idcitamedica', 'examen.idexamen', 'examen.nombre', 'examencita.descripcion'];
        
        $data = \DB::table('examen')
                ->join('examencita', 'examen.idexamen', '=', 'examencita.idexamen')
                ->join('citamedica', 'examencita.idcitamedica', '=', 'citamedica.idcitamedica')          
                ->select($campos) 
                ->whereNull('citamedica.deleted')
                ->where($param)
                ->orderBy('citamedica.idcitamedica', 'ASC') 
                ->get()->all();
                
        return $data; 
    } 

    public function examenescitaobs($param) { 
        $campos = ['citamedica.idcitamedica', 'examen.idexamen', 'examen.nombre', 'examencitaobs.descripcion'];
        
        $data = \DB::table('examen')
                ->join('examencitaobs', 'examen.idexamen', '=', 'examencitaobs.idexamen')
                ->join('citamedica', 'examencitaobs.idcitamedica', '=', 'citamedica.idcitamedica')          
                ->select($campos) 
                ->whereNull('citamedica.deleted')
                ->where($param)
                ->orderBy('citamedica.idcitamedica', 'ASC') 
                ->get()->all();
                
        return $data; 
    }

    public function examenfisicocita($param) { 
        $campos = ['citamedica.idcitamedica', 'examenfisico.zona', 'examenfisico.rom', 'examenfisico.muscular', 'examenfisico.funcional', 'examenfisico.eva', 'examenfisico.idgrupodx', 'grupodx.nombre as nombregrupodx'];
        
        $data = \DB::table('examenfisico')                 
                ->join('citamedica', 'examenfisico.idcitamedica', '=', 'citamedica.idcitamedica') 
                ->leftJoin('grupodx', 'examenfisico.idgrupodx', '=', 'grupodx.idgrupodx')
                ->select($campos) 
                ->whereNull('citamedica.deleted')
                ->whereNull('examenfisico.deleted_at')
                ->where($param)
                ->orderBy('examenfisico.zona', 'ASC') 
                ->get()->all();
                
        return $data; 
    }

    public function informes($param = [], $whereIdcitamedicaIn = []) { 
        $campos = ['citamedica.idcitamedica', 'informe.idinforme', 'informe.archivo', 'informe.created_at as created', 'medico.entidad as nombremedico', 'informe.identidad_firma', 'informe.fecha_firma', 'firmante.entidad as firmante'];
        
        $select = \DB::table('informe') 
                ->join('citamedica', 'informe.idcitamedica', '=', 'citamedica.idcitamedica') 
                ->join('entidad as medico', 'citamedica.idmedico', '=', 'medico.identidad')  
                ->leftJoin('entidad as firmante', 'informe.identidad_firma', '=', 'firmante.identidad')  
                ->select($campos);

        if(!empty($param)){ 
            $select->where($param);
        }

        if(!empty($whereIdcitamedicaIn)){ 
            $select->whereIn('informe.idcitamedica', $whereIdcitamedicaIn);
        }

        $data =  $select
                ->whereNull('citamedica.deleted') 
                ->whereNull('informe.deleted_at')                
                ->orderBy('informe.idinforme', 'ASC') //
                ->get()->all();

        foreach ($data as $row) { 
            $row->hora =  substr($row->created, 11, 8);
            $row->created = $this->formatFecha(substr($row->created, 0, 10));   

            if (!empty($row->fecha_firma))
                $row->fecha_firma =  $this->formatFecha(substr($row->fecha_firma, 0, 10)) . ' ' .substr($row->fecha_firma, 11, 8);                
        } 

        return $data; 
    }

    public function especialidadescita($param) { 
        $campos = ['citamedica.idcitamedica', 'especialidad.idespecialidad', 'especialidad.nombre'];
        
        $data = \DB::table('especialidad')
                ->join('especialidadcita', 'especialidad.idespecialidad', '=', 'especialidadcita.idespecialidad')
                ->join('citamedica', 'especialidadcita.idcitamedica', '=', 'citamedica.idcitamedica')          
                ->select($campos) 
                ->whereNull('citamedica.deleted')
                ->where($param)
                ->orderBy('citamedica.idcitamedica', 'ASC') 
                ->get()->all();
                
        return $data; 
    } 

    public function tratamientomedico($param, $precios = false, $material = false, $diagnostico = false, $betweendate = '', $presupuesto = false) {
                
        //$param = ['citamedica.idcicloatencion' => 3];
        $campos = ['tratamientomedico.idtratamientomedico', 'tratamientomedico.idmedico', 'medico.entidad as nombremedico',  'tratamientomedico.parentcantidad',
                   'tratamientomedico.idproducto', 'producto.idtipoproducto', 'producto.nombre as nombreproducto', 'tratamientomedico.cantidad', 
                   'tratamientomedico.parent', 'tratamiento.nombre as tratamiento', 'cliente.entidad as paciente', 
                    'sede.nombre as nombresede', 'citamedica.fecha', 'citamedica.inicio as hora', 'producto.codigo', 'citamedica.idcitamedica', 'citamedica.idmedico as iddoctor', 'tratamientomedico.idgrupodx', 'grupodx.nombre as nombregrupodx'];
        //iddoctor debe sustituir a tratamientomedico.idmedico y tratamientomedico.idmedico debe eliminarse o renombrarse a idpersonal

        if($presupuesto){
            array_push($campos, 'citamedica.idcicloatencion', 'presupuesto.montopago', 'presupuestodet.cantefectivo');
        }
        
        if($precios){
            $campos = ['tratamientomedico.idtratamientomedico', 'tratamientomedico.idmedico', 'medico.entidad as nombremedico', 
                    'tratamientomedico.idproducto', 'producto.idtipoproducto', 'producto.nombre as nombreproducto', 'tratamientomedico.cantidad', 
                    'tratamientomedico.parent', 'tratamientomedico.parentcantidad', 'tratamiento.nombre as tratamiento', 
                    'producto.valorventa', 'partref','partcta', 'partsta', 'sscoref', 'sscocta', 'sscosta', 'citamedica.idcicloatencion',
                    'sccocien','scconoventacinco','scconoventa','sccoochentacinco','sccoochenta','sccosetentacinco', 'sccosetenta',
                    'sccosesentacinco','sccosesenta','sccocincuentacinco','sccocincuenta','sccocuarentacinco','sccocuarenta',
                    'sccotreintacinco','sccotreinta','sccoveintecinco','sccoveinte','sccoquince','sccodiez','sccocero'];
        }
        
        $select = \DB::table('citamedica')
                ->join('tratamientomedico', 'citamedica.idcitamedica', '=', 'tratamientomedico.idcitamedica')
                ->join('entidad as cliente', 'citamedica.idpaciente', '=', 'cliente.identidad')
                ->join('producto', 'tratamientomedico.idproducto', '=', 'producto.idproducto')
                ->join('sede', 'citamedica.idsede', '=', 'sede.idsede')
                ->leftJoin('grupodx', 'tratamientomedico.idgrupodx', '=', 'grupodx.idgrupodx');

        if($presupuesto){
            $select->join('cicloatencion', 'citamedica.idcicloatencion', '=', 'cicloatencion.idcicloatencion')
                   ->join('presupuesto', 'citamedica.idcicloatencion', '=', 'presupuesto.idcicloatencion')
                   ->join('presupuestodet', function($join) {
                        $join->on('presupuesto.idpresupuesto', '=', 'presupuestodet.idpresupuesto')
                            ->on('presupuestodet.idproducto', '=', 'tratamientomedico.idproducto');
                    });
        }
        
            $select->leftJoin('tarifario', function($join) {
                    $join->on('producto.idproducto', '=', 'tarifario.idproducto')
                         ->on('citamedica.idsede', '=', 'tarifario.idsede');
                })
                ->leftJoin('entidad as medico', 'tratamientomedico.idmedico', '=', 'medico.identidad')
                ->leftJoin('producto as tratamiento', 'tratamientomedico.parent', '=', 'tratamiento.idproducto')
                ->select($campos)
                ->whereNull('tratamientomedico.deleted')
                ->whereNull('citamedica.deleted');
                
        if($presupuesto){
            $select->whereNull('cicloatencion.deleted')
                   ->whereNull('presupuesto.deleted')
                   ->whereNull('presupuestodet.deleted');
        } 

        if (!empty($betweendate)) {
            $select->whereBetween('citamedica.fecha', $betweendate);
        }
                
        $data =  $select
                ->where($param)
                ->orderBy('citamedica.idcitamedica', 'DESC')
                ->orderBy('producto.nombre', 'ASC')
                ->get()->all();
        
         
        if($diagnostico){
            $diagnosticos = $this->diagnosticomedico($param, $betweendate);     
            foreach ($data as $row) {
                $texto = '';  
                foreach ($diagnosticos as $row2) {    
                    if($row->idcitamedica === $row2->idcitamedica){
                        $texto .= $row2->nombre.' | ';                        
                    }
                } 
                
                $row->nombrediagnostico = !empty($texto) ? substr($texto, 0, -3) : $texto;
                 
                if(!empty($row->parentcantidad)){ 
                    $row->cantidad = $row->cantidad * $row->parentcantidad;
                }
            }   
        }
        
        if(!$precios){
            foreach ($data as $row) {             
                $row->fecha = $this->formatFecha($row->fecha);
                $row->hora =  substr($row->hora, 0, 5); 
            }
        }
        
        if($precios){
            $tmp = [];
            foreach($data as $row){                
                $cantidad = $row->cantidad;
                if(!empty($row->parentcantidad)){ 
                    $cantidad = $row->cantidad * $row->parentcantidad;
                }
                
                $cantidadAcum = isset($tmp[$row->idproducto]['cantidad']) ? $tmp[$row->idproducto]['cantidad'] : 0;

                $tmp[$row->idproducto]['idproducto'] = $row->idproducto;
                $tmp[$row->idproducto]['idtipoproducto'] = $row->idtipoproducto;
                $tmp[$row->idproducto]['nombreproducto'] = $row->nombreproducto;
                $tmp[$row->idproducto]['cantidad'] = $cantidadAcum + $cantidad;
                $tmp[$row->idproducto]['valorventa'] = $row->valorventa;
                
                $tmp[$row->idproducto]['partref'] = $row->partref;
                $tmp[$row->idproducto]['partcta'] = $row->partcta;
                $tmp[$row->idproducto]['partsta'] = $row->partsta;
                $tmp[$row->idproducto]['sscoref'] = $row->sscoref;
                $tmp[$row->idproducto]['sscocta'] = $row->sscocta;
                $tmp[$row->idproducto]['sscosta'] = $row->sscosta;

                $tmp[$row->idproducto]['sccocien'] = $row->sccocien;
                $tmp[$row->idproducto]['scconoventacinco'] = $row->scconoventacinco;
                $tmp[$row->idproducto]['scconoventa'] = $row->scconoventa;
                $tmp[$row->idproducto]['sccoochentacinco'] = $row->sccoochentacinco;
                $tmp[$row->idproducto]['sccoochenta'] = $row->sccoochenta;
                $tmp[$row->idproducto]['sccosetentacinco'] = $row->sccosetentacinco;
                $tmp[$row->idproducto]['sccosetenta'] = $row->sccosetenta;
                $tmp[$row->idproducto]['sccosesentacinco'] = $row->sccosesentacinco;
                $tmp[$row->idproducto]['sccosesenta'] = $row->sccosesenta;
                $tmp[$row->idproducto]['sccocincuentacinco'] = $row->sccocincuentacinco;
                $tmp[$row->idproducto]['sccocincuenta'] = $row->sccocincuenta;
                $tmp[$row->idproducto]['sccocuarentacinco'] = $row->sccocuarentacinco;
                $tmp[$row->idproducto]['sccocuarenta'] = $row->sccocuarenta;
                $tmp[$row->idproducto]['sccotreintacinco'] = $row->sccotreintacinco;
                $tmp[$row->idproducto]['sccotreinta'] = $row->sccotreinta;
                $tmp[$row->idproducto]['sccoveintecinco'] = $row->sccoveintecinco;
                $tmp[$row->idproducto]['sccoveinte'] = $row->sccoveinte;
                $tmp[$row->idproducto]['sccoquince'] = $row->sccoquince;
                $tmp[$row->idproducto]['sccodiez'] = $row->sccodiez;         
                $tmp[$row->idproducto]['sccocero'] = $row->sccocero;
            } 
            $data = $tmp;
        }
        
        if($material){
            $tmp = [];
            foreach($data as $row){
                $cantidad = $row->cantidad;
                if(!empty($row->parentcantidad)){ 
                    $cantidad = $row->cantidad * $row->parentcantidad;
                }

                $tmp[$row->parent.'-'.$row->idproducto]['idproducto'] = $row->idproducto;
                $tmp[$row->parent.'-'.$row->idproducto]['idservicio'] = $row->parent;
                $tmp[$row->parent.'-'.$row->idproducto]['nombreproducto'] = $row->nombreproducto;
                $tmp[$row->parent.'-'.$row->idproducto]['cantidad'] = @$tmp[$row->parent.'-'.$row->idproducto]['cantidad'] + $cantidad;
            }

          
            $data = [];
            $tmp2 = $tmp;

             //dd($tmp);
            foreach($tmp as $row){
                if(!empty($row['idservicio'])){
                    $total = 0;
                    foreach($tmp2 as $row2){                            
                        if($row2['idproducto'] === $row['idservicio']){
                            $total = $row2['cantidad'];  //Cantidad de Ejemplo Aupuntura
                            break;
                        }
                    } 
                                        
                    if($total === 0) 
                        $total = 1;

                    $row['dividendo'] = $row['cantidad']; //No obligatorio
                    $row['divisor'] = $total; //No obligatorio
                    $row['cantidad'] = ceil($row['cantidad'] / $total); 
                    
                    $data[] = $row;
                }
            } 
        }
         
        return $data; 
    }

    public function tratamientomedicoLight($idcicloatencion) {
                
        $campos2 = ['producto.idproducto', 'producto.nombre as nombreproducto', 
                    'ciclotratamiento.cantidad', 'ciclotratamiento.idgrupodx', //'NULL as parentcantidad', 
                    'grupodx.nombre as nombregrupodx'];

        $first  = \DB::table('cicloatencion')
                ->join('ciclotratamiento', 'cicloatencion.idcicloatencion', '=', 'ciclotratamiento.idcicloatencion') 
                ->join('producto', 'ciclotratamiento.idproducto', '=', 'producto.idproducto')    
                ->join('grupodx', 'ciclotratamiento.idgrupodx', '=', 'grupodx.idgrupodx')              
                ->select($campos2) 
                ->where('ciclotratamiento.idcicloatencion', $idcicloatencion)
                ->whereNull('ciclotratamiento.deleted_at') 
                ->whereNull('cicloatencion.deleted'); 

        $campos1 = ['producto.idproducto', 'producto.nombre as nombreproducto', 
                   'tratamientomedico.cantidad', 'tratamientomedico.idgrupodx', //'tratamientomedico.parentcantidad', 
                   'grupodx.nombre as nombregrupodx'];
        
        $data = \DB::table('citamedica')
                ->join('tratamientomedico', 'citamedica.idcitamedica', '=', 'tratamientomedico.idcitamedica') 
                ->join('producto', 'tratamientomedico.idproducto', '=', 'producto.idproducto') 
                ->join('grupodx', 'tratamientomedico.idgrupodx', '=', 'grupodx.idgrupodx') 
                ->select($campos1)
                ->where('citamedica.idcicloatencion', $idcicloatencion)
                ->whereNull('tratamientomedico.deleted')
                ->whereNull('citamedica.deleted') 
                ->unionAll($first)
                ->get()->all(); 

        // dd($data);

        return $data; 
    }

    public function tratamientomedicoAdicionales($idcicloatencion) {
        
        $campos1 = ['tratamientomedico.idproducto', 'producto.idtipoproducto', 'producto.nombre as nombreproducto', 
                'tratamientomedico.cantidad', 'tratamientomedico.parentcantidad', 'producto.valorventa', 'partref', 
                'partcta', 'partsta', 'sscoref', 'sscocta', 'sscosta', 'citamedica.idcicloatencion', 'sccocien', 
                'scconoventacinco','scconoventa','sccoochentacinco','sccoochenta','sccosetentacinco', 'sccosetenta',
                'sccosesentacinco','sccosesenta','sccocincuentacinco','sccocincuenta','sccocuarentacinco','sccocuarenta',
                'sccotreintacinco','sccotreinta','sccoveintecinco','sccoveinte','sccoquince','sccodiez', 'sccocero'];
                
        $data1 = \DB::table('citamedica')
                ->join('tratamientomedico', 'citamedica.idcitamedica', '=', 'tratamientomedico.idcitamedica') 
                ->join('producto', 'tratamientomedico.idproducto', '=', 'producto.idproducto') 
                ->leftJoin('tarifario', function($join) {
                    $join->on('producto.idproducto', '=', 'tarifario.idproducto')
                         ->on('citamedica.idsede', '=', 'tarifario.idsede');
                })  
                ->select($campos1) 
                ->where('citamedica.idcicloatencion', $idcicloatencion)
                ->whereNull('tratamientomedico.deleted') 
                ->whereNull('citamedica.deleted')
                ->get()->all();        

        $campos2 = ['ciclotratamiento.idproducto', 'producto.idtipoproducto', 'producto.nombre as nombreproducto', 
                'ciclotratamiento.cantidad', 'producto.valorventa', 'partref', 
                'partcta', 'partsta', 'sscoref', 'sscocta', 'sscosta', 'ciclotratamiento.idcicloatencion', 'sccocien', 
                'scconoventacinco','scconoventa','sccoochentacinco','sccoochenta','sccosetentacinco', 'sccosetenta',
                'sccosesentacinco','sccosesenta','sccocincuentacinco','sccocincuenta','sccocuarentacinco','sccocuarenta',
                'sccotreintacinco','sccotreinta','sccoveintecinco','sccoveinte','sccoquince','sccodiez', 'sccocero'];

        $data2 = \DB::table('cicloatencion')
                ->join('ciclotratamiento', 'cicloatencion.idcicloatencion', '=', 'ciclotratamiento.idcicloatencion') 
                ->join('producto', 'ciclotratamiento.idproducto', '=', 'producto.idproducto') 
                ->leftJoin('tarifario', function($join) {
                    $join->on('producto.idproducto', '=', 'tarifario.idproducto')
                         ->on('cicloatencion.idsede', '=', 'tarifario.idsede');
                })  
                ->select($campos2) 
                ->where('ciclotratamiento.idcicloatencion', $idcicloatencion)
                ->whereNull('ciclotratamiento.deleted_at') 
                ->whereNull('cicloatencion.deleted')
                ->get()->all();

        $tmp = [];
        foreach ($data1 as $row) {                
            $cantidad = $row->cantidad;
            if (!empty($row->parentcantidad)) { 
                $cantidad = $row->cantidad * $row->parentcantidad;
            }
            
            $cantidadAcum = isset($tmp[$row->idproducto]['cantidad']) ? $tmp[$row->idproducto]['cantidad'] : 0;

            $tmp[$row->idproducto]['idproducto'] = $row->idproducto;
            $tmp[$row->idproducto]['idtipoproducto'] = $row->idtipoproducto;
            $tmp[$row->idproducto]['nombreproducto'] = $row->nombreproducto;
            $tmp[$row->idproducto]['cantidad'] = $cantidadAcum + $cantidad;
            $tmp[$row->idproducto]['valorventa'] = $row->valorventa;
            
            $tmp[$row->idproducto]['partref'] = $row->partref;
            $tmp[$row->idproducto]['partcta'] = $row->partcta;
            $tmp[$row->idproducto]['partsta'] = $row->partsta;
            $tmp[$row->idproducto]['sscoref'] = $row->sscoref;
            $tmp[$row->idproducto]['sscocta'] = $row->sscocta;
            $tmp[$row->idproducto]['sscosta'] = $row->sscosta;

            $tmp[$row->idproducto]['sccocien'] = $row->sccocien;
            $tmp[$row->idproducto]['scconoventacinco'] = $row->scconoventacinco;
            $tmp[$row->idproducto]['scconoventa'] = $row->scconoventa;
            $tmp[$row->idproducto]['sccoochentacinco'] = $row->sccoochentacinco;
            $tmp[$row->idproducto]['sccoochenta'] = $row->sccoochenta;
            $tmp[$row->idproducto]['sccosetentacinco'] = $row->sccosetentacinco;
            $tmp[$row->idproducto]['sccosetenta'] = $row->sccosetenta;
            $tmp[$row->idproducto]['sccosesentacinco'] = $row->sccosesentacinco;
            $tmp[$row->idproducto]['sccosesenta'] = $row->sccosesenta;
            $tmp[$row->idproducto]['sccocincuentacinco'] = $row->sccocincuentacinco;
            $tmp[$row->idproducto]['sccocincuenta'] = $row->sccocincuenta;
            $tmp[$row->idproducto]['sccocuarentacinco'] = $row->sccocuarentacinco;
            $tmp[$row->idproducto]['sccocuarenta'] = $row->sccocuarenta;
            $tmp[$row->idproducto]['sccotreintacinco'] = $row->sccotreintacinco;
            $tmp[$row->idproducto]['sccotreinta'] = $row->sccotreinta;
            $tmp[$row->idproducto]['sccoveintecinco'] = $row->sccoveintecinco;
            $tmp[$row->idproducto]['sccoveinte'] = $row->sccoveinte;
            $tmp[$row->idproducto]['sccoquince'] = $row->sccoquince;
            $tmp[$row->idproducto]['sccodiez'] = $row->sccodiez;
            $tmp[$row->idproducto]['sccocero'] = $row->sccocero;
        } 

        foreach ($data2 as $row) {                
            $cantidad = $row->cantidad; 
            
            $cantidadAcum = isset($tmp[$row->idproducto]['cantidad']) ? $tmp[$row->idproducto]['cantidad'] : 0;

            $tmp[$row->idproducto]['idproducto'] = $row->idproducto;
            $tmp[$row->idproducto]['idtipoproducto'] = $row->idtipoproducto;
            $tmp[$row->idproducto]['nombreproducto'] = $row->nombreproducto;
            $tmp[$row->idproducto]['cantidad'] = $cantidadAcum + $cantidad;
            $tmp[$row->idproducto]['valorventa'] = $row->valorventa;
            
            $tmp[$row->idproducto]['partref'] = $row->partref;
            $tmp[$row->idproducto]['partcta'] = $row->partcta;
            $tmp[$row->idproducto]['partsta'] = $row->partsta;
            $tmp[$row->idproducto]['sscoref'] = $row->sscoref;
            $tmp[$row->idproducto]['sscocta'] = $row->sscocta;
            $tmp[$row->idproducto]['sscosta'] = $row->sscosta;

            $tmp[$row->idproducto]['sccocien'] = $row->sccocien;
            $tmp[$row->idproducto]['scconoventacinco'] = $row->scconoventacinco;
            $tmp[$row->idproducto]['scconoventa'] = $row->scconoventa;
            $tmp[$row->idproducto]['sccoochentacinco'] = $row->sccoochentacinco;
            $tmp[$row->idproducto]['sccoochenta'] = $row->sccoochenta;
            $tmp[$row->idproducto]['sccosetentacinco'] = $row->sccosetentacinco;
            $tmp[$row->idproducto]['sccosetenta'] = $row->sccosetenta;
            $tmp[$row->idproducto]['sccosesentacinco'] = $row->sccosesentacinco;
            $tmp[$row->idproducto]['sccosesenta'] = $row->sccosesenta;
            $tmp[$row->idproducto]['sccocincuentacinco'] = $row->sccocincuentacinco;
            $tmp[$row->idproducto]['sccocincuenta'] = $row->sccocincuenta;
            $tmp[$row->idproducto]['sccocuarentacinco'] = $row->sccocuarentacinco;
            $tmp[$row->idproducto]['sccocuarenta'] = $row->sccocuarenta;
            $tmp[$row->idproducto]['sccotreintacinco'] = $row->sccotreintacinco;
            $tmp[$row->idproducto]['sccotreinta'] = $row->sccotreinta;
            $tmp[$row->idproducto]['sccoveintecinco'] = $row->sccoveintecinco;
            $tmp[$row->idproducto]['sccoveinte'] = $row->sccoveinte;
            $tmp[$row->idproducto]['sccoquince'] = $row->sccoquince;
            $tmp[$row->idproducto]['sccodiez'] = $row->sccodiez;
            $tmp[$row->idproducto]['sccocero'] = $row->sccocero;
        }

        $data = [];
        foreach ($tmp as $row){
            $data[] = $row;
        }
        
        return $data; 
    }
    
    public function GrabarDiagnosticomedico($data, $id) {
        \DB::table('diagnosticomedico')->where('idcitamedica', $id)->delete();
        \DB::table('diagnosticomedico')->insert($data);
    }

    public function GrabarEspecialidadescita($data, $id) {
        \DB::table('especialidadcita')->where('idcitamedica', $id)->delete();
        \DB::table('especialidadcita')->insert($data);
    }

    public function GrabarExamenescita($data, $id) {
        \DB::table('examencita')->where('idcitamedica', $id)->delete();
        \DB::table('examencita')->insert($data);
    }

    public function GrabarExamenescitaobs($data, $id) {
        \DB::table('examencitaobs')->where('idcitamedica', $id)->delete();
        \DB::table('examencitaobs')->insert($data);
    }

    public function GrabarAnamnesis($data, $id) {
        \DB::table('examenfisico')->where('idcitamedica', $id)->delete();
        \DB::table('examenfisico')->insert($data);
    }

    public function GrabarAntecedentemedico($data, $id) {
        \DB::table('antecedentemedico')->where('idcitamedica', $id)->delete();
        \DB::table('antecedentemedico')->insert($data);
    }
    
    public function listaLog($param) { 
        // \DB::enableQueryLog(); 
        $data = \DB::table('logcitamedica')                 
                ->join('entidad as usuario', 'logcitamedica.id_created_at', '=', 'usuario.identidad')                
                ->select('logcitamedica.idlogcitamedica', 
                         'logcitamedica.created_at as createdat', 'usuario.entidad as usuario', 'logcitamedica.tipocm', 'logcitamedica.tipocmcomentario')
                ->whereNull('logcitamedica.deleted')
                ->where($param) 
                ->orderBy('logcitamedica.idlogcitamedica', 'DESC')
                ->get()->all();
        
        // dd(\DB::getQueryLog()); 
        foreach ($data as $row) {
            //$row->fecha = $this->formatFecha($row->fecha); 
            $row->createdat = $this->formatFecha(substr($row->createdat, 0, 10)).' '.substr($row->createdat, 11, 8);            
        }
        
        return $data;
    }
    
    public function grabarLog($idcitamedica, $id_created_at) { 
        
        $citamedica = citamedica::where('idcitamedica', '=', $idcitamedica)->first();
        
        $logcitamedica = array( 
            'idcitamedica' => $citamedica->idcitamedica,
            'idempresa' => $citamedica->idempresa,
            'idsede' => $citamedica->idsede,
            'idmedico' => $citamedica->idmedico, 
            'idpaciente' => $citamedica->idpaciente,
            'idestado' => $citamedica->idestado,
            'idestadopago' => $citamedica->idestadopago,
            'idcancelacion' => $citamedica->idcancelacion,
            'idreferencia' => $citamedica->idreferencia,
            'idatencion' => $citamedica->idatencion,
            'idventa' => $citamedica->idventa,
            'idordencompra' => $citamedica->idordencompra,
            'idcicloatencion' => $citamedica->idcicloatencion,
            'idtipo' => $citamedica->idtipo,
            'fecha' => $citamedica->fecha,
            'inicio' => $citamedica->inicio,
            'fin' => $citamedica->fin,
            'descripcion' => $citamedica->descripcion,
            'motivo' => $citamedica->motivo,
            'antecedente' => $citamedica->antecedente,
            'idconfirmacion' => $citamedica->idconfirmacion,
            'fechaconfirmacion' => $citamedica->fechaconfirmacion,
            'nota' => $citamedica->nota,
            'presupuesto' => $citamedica->presupuesto,
            'tipocm' => $citamedica->tipocm,
            'tipocmcomentario' => $citamedica->tipocmcomentario,
            'created_at' => date('Y-m-d H:i:s'), 
            'id_created_at' => $id_created_at, 
            'deleted' => $citamedica->deleted
        );
        
        \DB::table('logcitamedica')->insert($logcitamedica);        
    }

    public function grabarLogv2($id, $id_created_at, $valores = []) {

        $camposauditables = ['idmedico', 'idestado', 'fecha', 'inicio', 'idestadopago', 'idventa'];

        $camposauditablesdesc = ['idmedico' => 'médico', 'idestado' => 'estado', 'fecha' => 'fecha cita', 'inicio' => 'hora cita', 'idestadopago' => 'estado de pago', 'idventa' => 'ID de venta'];

        if (!empty($valores)) {
            $citamedica = citamedica::where('idcitamedica', '=', $id)->first()->getAttributes(); 

            $descripcion = '';

            foreach ($citamedica as $index => $valor) {
                foreach ($valores  as $index2 => $valornuevo) {  
                    // $descripcion.= '(Omitir) ' . $index . '|' . $index2;                   
                    if (in_array($index, $camposauditables) && $index === $index2 && $valor !== $valornuevo) {

                        if ($index === 'idmedico') { 

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

                        if ($index === 'fecha') { 
                            $valor = $this->formatFecha($valor);
                            $valornuevo = $this->formatFecha($valornuevo);
                        }

                        if ($index === 'idestado') { 
                            if ($valor === 4) 
                                $valor = 'Pendiente';
                            if ($valor === 5) 
                                $valor = 'Confirmada';
                            if ($valor === 6) 
                                $valor = 'Atendido';
                            if ($valor === 7) 
                                $valor = 'Cancelada';
                            if ($valor === 48) 
                                $valor = 'Faltó';

                            if ($valornuevo === 4) 
                                $valornuevo = 'Pendiente';
                            if ($valornuevo === 5) 
                                $valornuevo = 'Confirmada';
                            if ($valornuevo === 6) 
                                $valornuevo = 'Atendido';
                            if ($valornuevo === 7) 
                                $valornuevo = 'Cancelada';
                            if ($valornuevo === 48) 
                                $valornuevo = 'Faltó'; 
                        }

                        if ($index === 'idestadopago') { 
                            if ($valor === 71) 
                                $valor = 'Pagado';
                            if ($valor === 72) 
                                $valor = 'Pago pendiente'; 

                            if ($valornuevo === 71) 
                                $valornuevo = 'Pagado';
                            if ($valornuevo === 72) 
                                $valornuevo = 'Pago pendiente'; 
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
                'idcitamedica' => $id,
                'descripcion' => $descripcion,
                'created_at' => date('Y-m-d H:i:s'),
                'id_created_at' => $id_created_at 
            );

            \DB::table('citamedicalog')->insert($dataInsert); 
        } 
    }

    public function anamnesis($param) { 
        $campos = ['anamnesis.idcuestionariocita', 'anamnesis.nombre', 'anamnesis.tipo'];
        
        $select = \DB::table('citamedica')
                ->join('anamnesis', 'citamedica.idcitamedica', '=', 'anamnesis.idcitamedica');
                // ->join('cuestionariocita', 'anamnesis.idcuestionariocita', '=', 'cuestionariocita.id');                         
        $select->select($campos); 
        
        $data =  $select
                ->whereNull('citamedica.deleted')
                ->where($param)
                ->orderBy('citamedica.idcitamedica', 'ASC') 
                ->get()->all();
                
        return $data; 
    }

    public function cuestionariocita($param) { 
        $campos = ['cuestionariocita.*'];
        
        $select = \DB::table('cuestionariocita');                         
        $select->select($campos);  
        $data =  $select 
                ->where($param) 
                ->get()->all();
                
        return $data; 
    }

    public function antecedentemedico($param) { 
        $campos = ['antecedentemedico.*'];
        
        $select = \DB::table('antecedentemedico');                         
        $select->select($campos);  
        $data =  $select 
                ->where($param) 
                ->get()->all();
                
        return $data; 
    }
}
