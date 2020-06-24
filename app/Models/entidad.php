<?php

namespace App\Models; 

class entidad extends apimodel {

    protected $table = 'entidad';
    protected $primaryKey = 'identidad';
    public $timestamps = false;
    protected $fillable = [
        'idempresa',
        'idubigeo',
        'idcargoorg',
        'iddocumento',
        'idempleador',
        'numerodoc',
        'apellidopat',
        'apellidomat',
        'nombre',
        'razonsocial',
        'entidad',
        'codigopostal',
        'direccion',
        'fechanacimiento',
        'sexo',
        'ocupacion',
        'estadocivil',
        'hijos',
        'email',
        'telefono',        
        'celular',
        'imgperfil',
        'vacganado',
        'vacgozado',
        'vacdisponible',
        'facebook',
        'twitter',
        'paginaweb',
        'whatsapp',
        'tipopersonal',
        'tipocliente',
        'tipoproveedor',
        'tipomedico',
        'tipovendedor',
        'tipoconductor',
        'tipoafiliado',
        //'password',
        // 'email_verified_at',
        // 'verification_token',
        // 'audittrail',
        'acceso',
        'validacionip', 
        'validacionhorario', 
        'admasistencia',
        'horario',
        'colorcss',
        'acronimo',
        'maxcamilla',
        'peso',
        'altura',
        'sangre',
        'emailportal',
        'breakinicio',
        'breakfin',
        'idperfil', //update entidad set idperfil = (SELECT idperfil FROM entidadperfil where entidadperfil.identidad = entidad.identidad);
        'sms_acm',
        'sms_ite',
        'sms_ate',
        'sms_sat',
        'enviocpe',
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];
    protected $hidden = ['password', 'idempresa']; //,'created_at','updated_at'
 
    public function entidadHC($param, $idsede) {

        $data = \DB::table('entidad')
                ->leftJoin('documento', 'entidad.iddocumento', '=', 'documento.iddocumento')
                ->leftJoin('historiaclinica', function($join) use($idsede){
                    $join->on('entidad.identidad', '=', 'historiaclinica.idpaciente')
                         ->where('historiaclinica.idsede', '=', $idsede);
                })
                ->select('entidad.*', 'documento.abreviatura as documento', 'historiaclinica.hc')
                ->where($param)
                ->whereNull('entidad.deleted')
                ->first();
        
        return $data;
    }
    
    public function entidadHCC($hc, $idsede, $idempresa) {

        $data = \DB::table('entidad')
                ->leftJoin('documento', 'entidad.iddocumento', '=', 'documento.iddocumento')
                ->join('historiaclinica', function($join) use($hc, $idsede){
                    $join->on('entidad.identidad', '=', 'historiaclinica.idpaciente')
                         ->where('historiaclinica.idsede', '=', $idsede)
                         ->where('historiaclinica.hc', '=', $hc);
                })
                ->where('entidad.idempresa', '=', $idempresa)
                ->whereNull('entidad.deleted')
                ->select('entidad.identidad', 'entidad.entidad', 'documento.abreviatura as documento', 'entidad.numerodoc', 'historiaclinica.hc')                
                ->first();
        
        return $data;
    }

    public function etiquetas($param) { 
        $campos = ['etiqueta.idetiqueta', 'etiqueta.nombre'];
        
        $select = \DB::table('etiqueta')
                ->join('entidad_etiqueta', 'etiqueta.idetiqueta', '=', 'entidad_etiqueta.idetiqueta'); 

        $select->select($campos); 
        
        $data =  $select
                ->where($param)
                ->whereNull('etiqueta.deleted')                
                ->orderBy('etiqueta.nombre', 'ASC') 
                ->get()->all();
                
        return $data; 
    }

    public function historiasclinicas($param, $identidad = '') {
        
        $fields = ['sede.idsede','sede.nombre as nombresede'];       
        
        $select = \DB::table('sede')
                ->join('entidadsede', 'sede.idsede', '=', 'entidadsede.idsede');

        if(!empty($identidad)){
            array_push($fields, 'historiaclinica.hc', 'historiaclinica.idhistoriaclinica');
            $select->leftJoin('historiaclinica', function($join) use($identidad){
                $join->on('sede.idsede', '=', 'historiaclinica.idsede')
                     ->where('historiaclinica.idpaciente', '=', $identidad);                    
            }); 
        }
            
        $data = $select->select($fields)      
                ->where($param)
                ->get()->all();
        
        return $data;
    }
    
    public function tarifariomedico($param, $identidad = '') {
        
        $fields = ['sede.idsede','sede.nombre as nombresede'];       
        
        $select = \DB::table('sede')
                  ->join('entidadsede', 'sede.idsede', '=', 'entidadsede.idsede');
        
        if(!empty($identidad)){
            $fields[] = 'tarifamedico.idtarifamedico';
            $fields[] = 'tarifamedico.idproducto';
            $fields[] = 'tarifamedico.preciounit';            
            
            $select->leftJoin('tarifamedico', function($join) use($identidad){
                $join->on('sede.idsede', '=', 'tarifamedico.idsede')
                     ->where('tarifamedico.idmedico', '=', $identidad);                    
            }); 
        }
            
        $data = $select->select($fields)     
                ->orderBy('sede.idsede', 'ASC')
                ->where($param)
                ->get()->all();
        
        return $data;
    }
    
    public function tarifariomedicoproducto($param) {  
        
        $row = \DB::table('tarifamedico')
                  ->select('tarifamedico.preciounit')  
                  ->where($param)
                  ->first();
         
        $preciounit = isset($row->preciounit) ? $row->preciounit : null;
        
        return $preciounit;
    }
    
    public function grid($param, $likename, $items = '', $orderName='', $orderSort='', $idsede = '', $likedni = '', $verPerfil = false) {
        // $idsede : Servira para traer la HC del paciente 
        // \DB::enableQueryLog();  
        $campos = ['entidad.identidad', 'entidad.iddocumento', 'entidad.numerodoc', 'entidad.entidad', 'entidad.telefono', 'entidad.email', 
                   'documento.abreviatura as documentoabrev', 'tipopersonal', 'tipovendedor', 'tipomedico', 'tipocliente', 'tipoproveedor', 
                   'cargoorg.nombre', 'fechanacimiento', 'imgperfil', 'direccion', 'celular', 'entidad.acceso', 'entidad.validacionip', 'entidad.validacionhorario', 'entidad.sexo'];
        if(!empty($idsede)){
            $campos = ['entidad.identidad', 'entidad.iddocumento', 'entidad.numerodoc', 'entidad.entidad', 'entidad.telefono', 'entidad.email', 
                   'documento.abreviatura as documentoabrev', 'tipopersonal', 'tipovendedor', 'tipomedico', 'tipocliente', 'tipoproveedor', 
                   'cargoorg.nombre', 'fechanacimiento', 'imgperfil', 'direccion', 'celular', 'historiaclinica.hc', 'entidad.acceso', 'entidad.validacionip', 'entidad.validacionhorario', 'entidad.sexo'];
        }
        
        // if(!empty($verPerfil)){
            array_push($campos, 'perfil.idperfil', 'perfil.nombre as nombreperfil', 'perfil.idsuperperfil');
        // } 
        
        $select = \DB::table('entidad')
                ->leftJoin('documento', 'entidad.iddocumento', '=', 'documento.iddocumento')
                ->leftJoin('cargoorg', 'entidad.idcargoorg', '=', 'cargoorg.idcargoorg');                
                if(!empty($idsede)){
                    $select->leftJoin('historiaclinica', function($join) use($idsede){
                        $join->on('entidad.identidad', '=', 'historiaclinica.idpaciente')
                             ->where('historiaclinica.idsede', '=', $idsede);
                    });
                }
                
                if(!empty($verPerfil)){
                    $select->join('entidadperfil', 'entidad.identidad', '=', 'entidadperfil.identidad')
                           ->join('perfil', 'entidadperfil.idperfil', '=', 'perfil.idperfil');
                }else {
                    $select->leftJoin('entidadperfil', 'entidad.identidad', '=', 'entidadperfil.identidad')
                           ->leftJoin('perfil', 'entidadperfil.idperfil', '=', 'perfil.idperfil');
                }
                
                $select->select($campos)
                       ->where($param);
                
        if (!empty($likename)) {
            $select->where('entidad.entidad', 'like', '%' . $likename . '%');
            //$select->whereRaw('sp_ascii(entidad.entidad) ilike sp_ascii(?) ', ['%' . $likename . '%']);
        }
        
        if (!empty($likedni)) {
            $select->where('entidad.numerodoc', 'like', '%' . $likedni . '%');
            //$select->whereRaw('sp_ascii(entidad.entidad) ilike sp_ascii(?) ', ['%' . $likename . '%']);
        }
        
        if(empty($orderName) && empty($orderSort)){
            $orderName = !empty($orderName) ? $orderName : 'entidad.entidad';
            $orderSort = !empty($orderSort) ? $orderSort : 'ASC';
            
            $select->orderBy('perfil.nombre', 'ASC')
                   ->orderBy('entidad.acceso', 'ASC')
                   ->orderBy('entidad.entidad', 'ASC');
        }else {
            $orderName = !empty($orderName) ? $orderName : 'entidad.entidad';
            $orderSort = !empty($orderSort) ? $orderSort : 'ASC';
            $select->orderBy($orderName, $orderSort);
        }                

        $select->whereNull('entidad.deleted');
 
        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        } 

    
        foreach ($data as $row) {
            $row->fechanacimiento = $this->formatFecha($row->fechanacimiento);
        }

        return $data;
    }


    public function search($param, $likename, $items = '', $orderName='', $orderSort='', $idsede = '', $likedni = '', $verPerfil = false) {
        // $idsede : Servira para traer la HC del paciente 
        // \DB::enableQueryLog();  
        $campos = ['entidad.identidad', 'entidad.iddocumento', 'entidad.numerodoc', 'entidad.entidad', 'entidad.telefono', 'entidad.email', 
                   'documento.abreviatura as documentoabrev', 'tipopersonal', 'tipovendedor', 'tipomedico', 'tipocliente', 'tipoproveedor', 
                   'cargoorg.nombre', 'fechanacimiento', 'imgperfil', 'direccion', 'celular', 'entidad.acceso', 'entidad.validacionip', 'entidad.validacionhorario', 'entidad.sexo'];
        if(!empty($idsede)){
            $campos = ['entidad.identidad', 'entidad.iddocumento', 'entidad.numerodoc', 'entidad.entidad', 'entidad.telefono', 'entidad.email', 
                   'documento.abreviatura as documentoabrev', 'tipopersonal', 'tipovendedor', 'tipomedico', 'tipocliente', 'tipoproveedor', 
                   'cargoorg.nombre', 'fechanacimiento', 'imgperfil', 'direccion', 'celular', 'historiaclinica.hc', 'entidad.acceso', 'entidad.validacionip', 'entidad.validacionhorario', 'entidad.sexo'];
        }
        
        // if(!empty($verPerfil)){
            array_push($campos, 'perfil.idperfil', 'perfil.nombre as nombreperfil', 'perfil.idsuperperfil');
        // } 
        
        $select = \DB::table('entidad')
                ->leftJoin('documento', 'entidad.iddocumento', '=', 'documento.iddocumento')
                ->leftJoin('cargoorg', 'entidad.idcargoorg', '=', 'cargoorg.idcargoorg');                
                if(!empty($idsede)){
                    $select->leftJoin('historiaclinica', function($join) use($idsede){
                        $join->on('entidad.identidad', '=', 'historiaclinica.idpaciente')
                             ->where('historiaclinica.idsede', '=', $idsede);
                    });
                }
                
                if(!empty($verPerfil)){
                    $select->join('entidadperfil', 'entidad.identidad', '=', 'entidadperfil.identidad')
                           ->join('perfil', 'entidadperfil.idperfil', '=', 'perfil.idperfil');
                }else {
                    $select->leftJoin('entidadperfil', 'entidad.identidad', '=', 'entidadperfil.identidad')
                           ->leftJoin('perfil', 'entidadperfil.idperfil', '=', 'perfil.idperfil');
                }
                
                $select->select($campos)
                       ->where($param);
                
        if (!empty($likename)) {
            $select->where('entidad.entidad', 'like', '%' . $likename . '%');
            //$select->whereRaw('sp_ascii(entidad.entidad) ilike sp_ascii(?) ', ['%' . $likename . '%']);
        }
        
        if (!empty($likedni)) {
            $select->where('entidad.numerodoc', 'like', '%' . $likedni . '%');
            //$select->whereRaw('sp_ascii(entidad.entidad) ilike sp_ascii(?) ', ['%' . $likename . '%']);
        }
        
        if(empty($orderName) && empty($orderSort)){
            $orderName = !empty($orderName) ? $orderName : 'entidad.entidad';
            $orderSort = !empty($orderSort) ? $orderSort : 'ASC';
            
            $select->orderBy('perfil.nombre', 'ASC')
                   ->orderBy('entidad.acceso', 'ASC')
                   ->orderBy('entidad.entidad', 'ASC');
        }else {
            $orderName = !empty($orderName) ? $orderName : 'entidad.entidad';
            $orderSort = !empty($orderSort) ? $orderSort : 'ASC';
            $select->orderBy($orderName, $orderSort);
        }                

        $select->whereNull('entidad.deleted');
 
        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        } 

    
        foreach ($data as $row) {
            $row->fechanacimiento = $this->formatFecha($row->fechanacimiento);
        }

        return $data;
    }
    
    public function password($param) {
        $data = DB::table('entidad')
                ->select('entidad.password')
                ->where($param)
                ->whereNull('entidad.deleted')
                ->first();

        return $data;
    }      

    public function updateEntidad($data, $identidad) {
        \DB::table('entidad')->where('identidad', $identidad)->whereNull('entidad.deleted')->update($data);
    }  

    public function GrabarEntidadPerfil($data, $identidad) {
        \DB::table('entidadperfil')->where('identidad', $identidad)->delete();
        \DB::table('entidadperfil')->insert($data);
    }

    public function GrabarEntidadEspecialidad($data, $identidad) {
        \DB::table('entidadespecialidad')->where('identidad', $identidad)->delete();
        \DB::table('entidadespecialidad')->insert($data);
    }

    public function GrabarEntidadSede($data, $identidad) {
        \DB::table('entidadsede')->where('identidad', $identidad)->delete();
        \DB::table('entidadsede')->insert($data);
    }

    public function entidad($param, $idsede = '', $empleador = false) {        
        
        $campos = ['entidad.*', \DB::raw('TIMESTAMPDIFF(YEAR, entidad.fechanacimiento, CURDATE()) as edad'), 'documento.nombre as documento',
                'documento.abreviatura as documentoabrev', 'cargoorg.nombre as cargoorg', 'perfil.nombre as perfil', 'entidadperfil.idperfil', 'perfil.idsuperperfil', 'documento.codigosunat as documentocodigosunat', 'ubigeo.nombre as distrito'];

        if ($empleador) {
            array_push($campos, 'empleador.entidad as empleador');
        }

        if (!empty($idsede)) {
            $campos[]  = 'historiaclinica.hc';
        }

        $select = \DB::table('entidad');

        if(!empty($idsede)){
            $select->leftJoin('historiaclinica', function($join) use($idsede){
                $join->on('entidad.identidad', '=', 'historiaclinica.idpaciente')
                     ->where('historiaclinica.idsede', '=', $idsede);
            });
        }

        $select->leftJoin('entidadperfil', 'entidad.identidad', '=', 'entidadperfil.identidad')
                ->leftJoin('perfil', 'entidadperfil.idperfil', '=', 'perfil.idperfil')
                ->leftJoin('documento', 'entidad.iddocumento', '=', 'documento.iddocumento')
                ->leftJoin('cargoorg', 'entidad.idcargoorg', '=', 'cargoorg.idcargoorg') 
                ->leftJoin('ubigeo', 'entidad.idubigeo','=', 'ubigeo.idubigeo');

        if ($empleador) {
            $select->leftJoin('entidad as empleador', 'entidad.idempleador', '=', 'empleador.identidad');
        }

        $data = $select->select($campos)
                ->where($param)
                ->whereNull('entidad.deleted')
                ->first();
        
        return $data;
    }

    public function getCumpleanos($param) {


        $data = \DB::table('entidad')
                ->leftJoin('documento', 'entidad.iddocumento', '=', 'documento.iddocumento')
                ->leftJoin('cargoorg', 'entidad.idcargoorg', '=', 'cargoorg.idcargoorg')
                ->select('entidad.entidad', 'tipopersonal', 'tipovendedor', 'tipomedico', 'tipocliente', 'tipoproveedor', 'fechanacimiento', 'imgperfil', 'sexo'
                        ,\DB::raw('TIMESTAMPDIFF(YEAR, fechanacimiento, CURDATE()) as edad')) //Mysql
                        //, \DB::raw("date_part('year', age( fechanacimiento )) as edad")) //Postgresql
                ->where($param)
                ->whereRaw("day(fechanacimiento)=day(NOW()) and month(fechanacimiento)=month(NOW())")
                ->whereNull('entidad.deleted')
                //->whereRaw("extract(day from fechanacimiento) = extract(day from NOW()) and extract(month from fechanacimiento) = extract(month from NOW())")
                ->get()->all();

        foreach ($data as $row) {
            if ($row->tipopersonal === '1') {
                $row->tipoentidaddesc = 'personal';
            }
            if ($row->tipocliente === '1') {
                $row->tipoentidaddesc = 'cliente';
            }
            if ($row->tipoproveedor === '1') {
                $row->tipoentidaddesc = 'proveedor';
            }
            if ($row->tipomedico === '1') {
                $row->tipoentidaddesc = 'médico';
            }
            $row->fechanacimiento = $this->formatFecha($row->fechanacimiento);
        }

        return $data;
    }

    public function entidades($param, $bool = FALSE, $id = NULL, $fields = ['entidad.identidad', 'entidad.entidad'] ) {
         
        //\DB::enableQueryLog(); 
        $select = \DB::table('entidad');
        if ($bool) {
            $select->join('entidadsede', 'entidad.identidad', '=', 'entidadsede.identidad');                   
        }
            $select->select($fields)
                   ->where($param)
                   ->whereNull('entidad.deleted');
        if (isset($id)) {
            $select->orWhere('entidad.identidad', $id);
        }
        $data = $select 
                ->orderBy('entidad.entidad', 'asc')
                ->distinct()
                ->get()->all();
        //dd(\DB::getQueryLog());
        return $data;
    }
    
    public function entidadesSedes($param) {
        
        $data = \DB::table('entidad')
                ->join('entidadsede', 'entidad.identidad', '=', 'entidadsede.identidad')
                ->join('sede', 'entidadsede.idsede', '=', 'sede.idsede')
                ->select('entidad.identidad', 'entidad.entidad', 'entidad.colorcss', 'sede.nombre as sedenombre')
                ->where($param)
                ->whereNull('entidad.deleted')
                ->orderBy('entidad', 'asc')
                ->get()->all();
        
        $tmp = []; 
        foreach($data as $row){
            $tmp[$row->identidad]['identidad'] = $row->identidad;
            $tmp[$row->identidad]['entidad'] = $row->entidad;
            $tmp[$row->identidad]['colorcss'] = $row->colorcss;
            $tmp[$row->identidad]['sedes'][] = $row->sedenombre;
        } 
        
        $data = [];
        foreach($tmp as $row){
            $data[] = $row;
        }
        
        return $data;
    }

    public function ListaEmpresas($param) {
        $data = \DB::table('empresa')
                ->join('entidad', 'empresa.idempresa', '=', 'entidad.idempresa')
                ->select('empresa.ruc', 'empresa.razonsocial', 'empresa.url')
                ->where($param)
                ->whereNull('entidad.deleted')
                ->get()->all();

        return $data;
    }

    public function listaEntidadModulo($param) {
        $data = \DB::table('entidadmodulo')
                ->join('modulo', 'entidadmodulo.idmodulo', '=', 'modulo.idmodulo')
                ->select('entidadmodulo.idmodulo', 'modulo.nombre')
                ->where($param)
                ->get()->all();

        return $data;
    }

    public function listaEntidadEspecialidad($param) {
        $data = \DB::table('entidadespecialidad')
                ->join('especialidad', 'entidadespecialidad.idespecialidad', '=', 'especialidad.idespecialidad')
                ->select('especialidad.idespecialidad', 'especialidad.nombre')
                ->where($param)
                ->get()->all();

        return $data;
    }

    public function listaEntidadSede($param) {
        $data = \DB::table('entidadsede')
                ->join('sede', 'entidadsede.idsede', '=', 'sede.idsede')
                ->select('sede.idsede', 'sede.nombre', 'sede.direccion')
                ->where($param)
                ->get()->all();

        return $data;
    }
    
    public function logsAcceso($param) {        
        $data = \DB::table('logacceso') 
                ->join('entidad', 'logacceso.identidad', '=', 'entidad.identidad')                
                ->where($param)
                ->whereNull('entidad.deleted')
                ->get()->all();

        return $data;
    }
    
    public function grabarlogAcceso($param) {  
        if(isset($param['fechaout']) && isset($param['horaout']) && isset($param['token'])){            
            $where = array('token' => $param['token']); 
            if(\DB::table('logacceso')->where($where)->first()){
                $update = array(
                    'token' => $param['token'], 
                    'fechaout' => $param['fechaout'], 
                    'horaout' => $param['horaout']
                );                
                \DB::table('logacceso')->where($where)->update($update);
            }
        } else {
            \DB::table('logacceso')->insert($param);
        }            
    }

    public function ListaModules($param, $leftJoin = false) {
        /** Martes 12 Ene 2016
         * Se retira JOIN entidadmodulo, 
         * Se coloca JOIN entidadperfil, perfil, perfilmodulo
         * El cliente necesita manejar autentificacion de opciones basado en perfiles. 
         * No desea opciones basado en opcion por opcion para entidad.
         */
        $select = \DB::table('empresa');
        if ($leftJoin) {
            $select->join('moduloempresa', 'empresa.idempresa', '=', 'moduloempresa.idempresa')
                    ->join('modulo', 'moduloempresa.idmodulo', '=', 'modulo.idmodulo');
        } else {
            $select->join('entidad', 'empresa.idempresa', '=', 'entidad.idempresa')
                    //->join('entidadmodulo', 'entidad.identidad', '=', 'entidadmodulo.identidad')
                    ->join('entidadperfil', 'entidad.identidad', '=', 'entidadperfil.identidad')
                    ->join('perfil', 'entidadperfil.idperfil', '=', 'perfil.idperfil')
                    ->join('perfilmodulo', 'perfil.idperfil', '=', 'perfilmodulo.idperfil')
                    ->join('modulo', 'perfilmodulo.idmodulo', '=', 'modulo.idmodulo')
                    ->join('moduloempresa', 'modulo.idmodulo', '=', 'moduloempresa.idmodulo');
        }
        $data = $select
                ->select('modulo.idmodulo', 'modulo.parent', 'modulo.orden', 'modulo.nombre', 'modulo.url as urlvista', 
                'modulo.icono', 'modulo.nivel', 'empresa.idempresa', 'empresa.url', 'empresa.razonsocial', 'modulo.icon', 'modulo.state')
                ->where($param)
                ->orderBy('modulo.parent', 'ASC')
                ->orderBy('modulo.orden', 'ASC')
                ->get()->all();

        $modules = array();
        foreach ($data as $fila) {
            $modules[$fila->url]['name'] = $fila->razonsocial;   
            $modules[$fila->url]['modules'][] = $fila;
        }

        foreach ($modules as $urlente => $fila) {

            $modules[$urlente]['modules'] = $this->_ordenarModuleEnterprise($fila['modules']);

            $newmodulos = array();
            foreach ($modules[$urlente]['modules'] as $valor) {
                if ($valor['level'] == 1) {
                    $newmodulos[$valor['idmodulo']]['id'] = $valor['idmodulo'];
                    $newmodulos[$valor['idmodulo']]['name'] = $valor['descripcion'];
                    $newmodulos[$valor['idmodulo']]['level'] = $valor['level'];
                    $newmodulos[$valor['idmodulo']]['icono'] = $valor['iconmodu'];
                    $newmodulos[$valor['idmodulo']]['state'] = $valor['state'];
                    $newmodulos[$valor['idmodulo']]['icon'] = $valor['icon'];
                }
                if ($valor['level'] == 2) {
                    $newmodulos[$valor['parent']]['menus'][$valor['idmodulo']]['id'] = $valor['idmodulo'];
                    $newmodulos[$valor['parent']]['menus'][$valor['idmodulo']]['name'] = $valor['descripcion'];
                    $newmodulos[$valor['parent']]['menus'][$valor['idmodulo']]['uri'] = $valor['urlvista'];
                    $newmodulos[$valor['parent']]['menus'][$valor['idmodulo']]['children'] = $valor['condicion'];
                    $newmodulos[$valor['parent']]['menus'][$valor['idmodulo']]['icono'] = $valor['iconmodu'];
                    $newmodulos[$valor['parent']]['menus'][$valor['idmodulo']]['state'] = $valor['state'];
                    $newmodulos[$valor['parent']]['menus'][$valor['idmodulo']]['icon'] = $valor['icon'];
                }
                if ($valor['level'] == 3) {
                    $newmodulos[$valor['moduloselect']]['menus'][$valor['parent']]['options'][$valor['idmodulo']]['id'] = $valor['idmodulo'];
                    $newmodulos[$valor['moduloselect']]['menus'][$valor['parent']]['options'][$valor['idmodulo']]['name'] = $valor['descripcion'];
                    $newmodulos[$valor['moduloselect']]['menus'][$valor['parent']]['options'][$valor['idmodulo']]['uri'] = $valor['urlvista'];
                    $newmodulos[$valor['moduloselect']]['menus'][$valor['parent']]['options'][$valor['idmodulo']]['state'] = $valor['state'];
                }
            }
            $modules[$urlente]['modules'] = $newmodulos;
        }

        //Eliminar los indice de los array en los modulos
        /* Esto es si queremos que los indices no sean los id de modulos, sino un correlativo.
         * Esta nueva matriz hara que el orden en angularjs se refleje ya que se trata de indice ascendente.
         * Si omito esto el AngularJs reordenara los indices ascedente que no es otro que los idmodulos */

        $modulesFormat = [];
        foreach ($modules as $pk => $row) {
            $modulesFormat[$pk] = $row;
            $im = 0;
            unset($modulesFormat[$pk]['modules']);
            foreach ($row['modules'] as $modulo) {
                $modulesFormat[$pk]['modules'][$im] = $modulo;
                if (!empty($modulo['menus'])) {
                    $ime = 0;
                    unset($modulesFormat[$pk]['modules'][$im]['menus']);
                    foreach ($modulo['menus'] as $menu) {
                        $modulesFormat[$pk]['modules'][$im]['menus'][$ime] = $menu;
                        if (!empty($menu['options'])) {
                            $io = 0;
                            unset($modulesFormat[$pk]['modules'][$im]['menus'][$ime]['options']);
                            foreach ($menu['options'] as $option) {
                                $modulesFormat[$pk]['modules'][$im]['menus'][$ime]['options'][$io] = $option;
                                $io++;
                            }
                        }
                        $ime++;
                    }
                }
                $im++;
            }
        }

        return $modulesFormat;
        //return $modules;
    }

    public function ListaPerfiles($param) {
        $data = \DB::table('empresa')
                ->join('entidad', 'empresa.idempresa', '=', 'entidad.idempresa')
                ->join('entidadperfil', 'entidad.identidad', '=', 'entidadperfil.identidad')
                ->join('perfil', 'entidadperfil.idperfil', '=', 'perfil.idperfil')
                ->select('entidad.identidad', 'entidad.apellidopat', 'entidad.apellidomat', 'entidad.nombre', 'entidad.iddocumento', 
                         'entidad.numerodoc', 'entidad.imgperfil', 'entidad.sexo', 'entidad.entidad', 'perfil.idsuperperfil',
                         'perfil.idperfil', 'perfil.nombre as perfil', 'perfil.nuevo','perfil.editar', 'perfil.eliminar', 
                         'empresa.url', 'empresa.razonsocial', 'empresa.imglogologin', 'empresa.imglogosistema', 'perfil.optinforme')
                ->where($param)
                ->whereNull('entidad.deleted')
                ->orderBy('empresa.idempresa', 'ASC')
                ->orderBy('entidadperfil.idperfil', 'ASC')
                ->get()->all();

        $profiles = array();
        foreach ($data as $fila) {
            //USUARIO     
            $profiles[$fila->url]['identidad'] = $fila->identidad;
            $profiles[$fila->url]['numerodoc'] = $fila->numerodoc;
            $profiles[$fila->url]['entidad'] = $fila->apellidopat . ' ' . $fila->apellidomat . ', ' . $fila->nombre;
            $profiles[$fila->url]['nombres'] = empty($fila->nombre) ? $fila->entidad : $fila->nombre;
            $profiles[$fila->url]['apellidos'] = $fila->apellidopat . ' ' . $fila->apellidomat;
            $profiles[$fila->url]['imgperfil'] = $fila->imgperfil;
            $profiles[$fila->url]['sexo'] = $fila->sexo;
            //EMPRESA          
            $profiles[$fila->url]['razonsocial'] = $fila->razonsocial;
            $profiles[$fila->url]['imglogologin'] = $fila->imglogologin;
            $profiles[$fila->url]['imglogosistema'] = $fila->imglogosistema;
            $profiles[$fila->url]['perfilidparent'] = $fila->idsuperperfil;
            $profiles[$fila->url]['perfilid'] = $fila->idperfil;
            $profiles[$fila->url]['perfilnombre'] = $fila->perfil; //La linea de arriba no debe ir porque los perfiles son unicos
            $profiles[$fila->url]['perfilnuevo'] = $fila->nuevo;
            $profiles[$fila->url]['perfileditar']= $fila->editar;
            $profiles[$fila->url]['perfileliminar'] = $fila->eliminar;
            //Permisos especiales
            $profiles[$fila->url]['optinforme'] = $fila->optinforme;
        }

        return $profiles;
    }

    private function _ordenarModuleEnterprise($data) {
        $tablaorden = array();
        foreach ($data as $fila) {
            $tablaorden[$fila->idmodulo] = '';
        }

        $tablaorden = $this->_ordenarPorJerarquia($tablaorden, $data);

        $matriz = array();
        $matrizTmp = $data;
        $i = 0;
        foreach ($data as $fila) {
            $condic = 0;
            foreach ($matrizTmp as $row) {
                if ($fila->idmodulo == $row->parent) {
                    $condic = 1;
                    break;
                }
            }

            $matriz[$i] = array(
                'idmodulo' => $fila->idmodulo,
                'parent' => $fila->parent,
                'descripcion' => $fila->nombre,
                'archivo' => $fila->url,
                'iconmodu' => $fila->icono,                
                'level' => $fila->nivel,
                'orden' => $tablaorden[$fila->idmodulo],
                'condicion' => $condic,
                'urlvista' => $fila->urlvista,
                'icon' => $fila->icon,
                'state' => $fila->state
            );

            $i++;
        }

        $data1 = $matriz;
        $data2 = $matriz;
        $data3 = $matriz;
        $nuevaMatriz = array();
        foreach ($data1 as $fila1) {
            if ($fila1['orden'] == '1') {
                $nuevaMatriz[] = $fila1;
                foreach ($data2 as $fila2) {
                    if ($fila2['orden'] == '2' && $fila1['idmodulo'] == $fila2['parent']) {
                        $nuevaMatriz[] = $fila2;
                        foreach ($data3 as $fila3) {
                            if ($fila3['orden'] == '3' && $fila2['idmodulo'] == $fila3['parent']) {
                                $nuevaMatriz[] = $fila3;
                            }
                        }
                    }
                }
            }
        }

        $data = array();
        $idmodulotmp = '';
        foreach ($nuevaMatriz as $fila) {
            if ($fila['orden'] == '1')
                $idmodulotmp = $fila['idmodulo'];

            $fila['moduloselect'] = $idmodulotmp;
            $data[] = $fila;
        }

        return $data;
    }

    private function _ordenarPorJerarquia($tablaorden, $data) {
        $data1 = $data;
        $data2 = $data;
        $orden = '';
        foreach ($data1 as $fila1) {
            $encontrado = FALSE;
            foreach ($data2 as $fila2) {
                if ($fila1->parent == $fila2->idmodulo) {
                    $encontrado = TRUE;
                    $orden = $tablaorden[$fila1->parent];
                    if (!empty($orden)) {
                        $orden = $orden + 1;
                        $tablaorden[$fila1->idmodulo] = $orden;
                        break;
                    }
                }
            }
            if (!$encontrado) {
                $tablaorden[$fila1->idmodulo] = 1;
            }
        }

        $entro = false;
        foreach ($tablaorden as $ind => $orden) {
            if (empty($orden)) {
                $entro = true;
                break;
            }
        }
        if ($entro) {
            $tablaorden = $this->_ordenarPorJerarquia($tablaorden, $data);
        }
        return $tablaorden;
    }

    public function generaHC($idpaciente, $idsede, $generar, $my = '') {
        $hc = \DB::table('historiaclinica')
                ->join('sede', 'historiaclinica.idsede', '=', 'sede.idsede')
                ->join('empresa', 'sede.idempresa', '=', 'empresa.idempresa')
                ->where(array('historiaclinica.idsede' => $idsede))
                ->max('hc');
        $nuevoHc = $hc + 1;
        
        if($generar){  
            $entidad = \DB::table('historiaclinica')->insert(['idpaciente' => $idpaciente, 'idsede' => $idsede, 'hc' => $nuevoHc, 'created_at' => date('Y-m-d H:i:s'), 'id_created_at' => $my], 'hc'); 
            if ($entidad) {
                return $nuevoHc;
            }
        }else{
            return $nuevoHc;
        }
    }
    
    public function validadorDataRelacionada($id) {
        $data = \DB::table('citamedica')->where('idpaciente', $id)->get()->all();
        if (!empty($data)) {
            return ['validator' => true, 'message' => 'Tiene citas m&eacute;dicas programadas. No puede ser eliminado.'];
        }

        $data = \DB::table('citamedica')->where('idmedico', $id)->get()->all();
        if (!empty($data)) {
            return ['validator' => true, 'message' => 'Tiene citas m&eacute;dicas programadas. No puede ser eliminado.'];
        }

        $data = \DB::table('terapia')->where('idpaciente', $id)->get()->all();
        if (!empty($data)) {
            return ['validator' => true, 'message' => 'Tiene terapias programadas. No puede ser eliminado.'];
        }
        
        $data = \DB::table('terapia')->where('idterapista', $id)->get()->all();
        if (!empty($data)) {
            return ['validator' => true, 'message' => 'Tiene terapias programadas. No puede ser eliminado.'];
        }
        
        $data = \DB::table('horariomedico')->where('idmedico', $id)->get()->all();
        if (!empty($data)) {
            return ['validator' => true, 'message' => 'Tiene horario laboral. No puede ser eliminado.'];
        } 

        $data = \DB::table('movimiento')->where('identidad', $id)->get()->all();
        if (!empty($data)) {
            return ['validator' => true, 'message' => 'Tiene otros egresos/ingresos. No puede ser eliminado.'];
        } 

        return ['validator' => false];
    }

    public function pacientehistorias($param, $betweendate) {

        $campos = ['entidad.identidad', 'entidad.numerodoc', 'entidad.entidad as paciente', 'documento.abreviatura as documentoabrev', 
                   'sede.idsede', 'sede.sedeabrev', 'historiaclinica.hc', 'historiaclinica.created_at as nhccreatedat', 'created.entidad as created', 'entidad.created_at as createdat','entidad.direccion', 'entidad.fechanacimiento', 
                   'entidad.telefono', 'entidad.celular', 'entidad.email', 'entidad.sexo', 'entidad.acceso',  'departamento.nombre as departamento', 'provincia.nombre as provincia',
                   'ubigeo.nombre as distrito'];
        
        $select = \DB::table('entidad')
                ->leftJoin('documento', 'entidad.iddocumento', '=', 'documento.iddocumento')
                ->leftJoin('historiaclinica', 'entidad.identidad', '=', 'historiaclinica.idpaciente')
                ->leftJoin('sede', 'historiaclinica.idsede', '=', 'sede.idsede')
                ->leftJoin('entidad as created', 'entidad.id_created_at', '=', 'created.identidad')
                ->leftJoin('ubigeo as departamento', \DB::raw("CONCAT(SUBSTRING(entidad.idubigeo,1,5), '0000')"),'=', 'departamento.idubigeo')
                ->leftJoin('ubigeo as provincia', \DB::raw("CONCAT(SUBSTRING(entidad.idubigeo,1,7), '00')"),'=', 'provincia.idubigeo')
                ->leftJoin('ubigeo', 'entidad.idubigeo','=', 'ubigeo.idubigeo')
                ->select($campos) 
                ->where($param)
                ->whereNull('entidad.deleted');

        if (!empty($betweendate)) {
            $select->whereBetween('entidad.created_at', $betweendate);
        }

        $data = $select->orderBy('entidad.entidad', 'asc')->get()->all();  

        //$data = collect($data)->map(function($x){ return (array) $x; })->toArray(); 
        foreach ($data as $row) { 
            $row->createdat = $this->formatFecha(substr($row->createdat, 0, 10));

            if ($row->fechanacimiento) {
                $row->fechanacimiento = $this->formatFecha($row->fechanacimiento);
            }
        }
        
        return $data;
    }

    public function pacientehistoriasLight($param, $betweendate) {
        //Para determinar la primera nroHc en descarga excel
        $data   = \DB::table('entidad')
                ->join('historiaclinica', 'entidad.identidad', '=', 'historiaclinica.idpaciente')
                ->join('sede', 'historiaclinica.idsede', '=', 'sede.idsede')
                ->select(['entidad.identidad', 'historiaclinica.hc', 'sede.nombre']) 
                ->where($param)
                ->whereNull('entidad.deleted') 
                ->whereBetween('entidad.created_at', $betweendate) 
                ->orderBy('entidad.identidad', 'asc') 
                ->orderBy('historiaclinica.created_at', 'asc') 
                ->get()->all();   
        
        return $data;
    }

    public function GrabarEtiquetas($data, $identidad) { 
        \DB::table('entidad_etiqueta')->where('identidad', $identidad)->delete();
        \DB::table('entidad_etiqueta')->insert($data);
    }
}
