<?php

namespace App\Http\Controllers;

use Excel;
use App\Models\sede;
use \Firebase\JWT\JWT;
use App\Models\empresa;
use App\Models\entidad;
use App\Models\terapia;
use App\Models\cargoorg;
use App\Models\logfusion;
use App\Mail\RecoverySend;
use App\Exports\DataExport;
use App\Models\presupuesto;
use App\Models\publicacion;
use App\Mail\BienvenidoSend;
use App\Models\especialidad;
use Illuminate\Http\Request;
use App\Models\cicloatencion;
use App\Models\horariomedico;
use App\Mail\ConfirmacionSend;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class entidadController extends Controller {
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    public function construct(Request $request, $enterprise) {
        /* Obtiene solo las sedes a las que tiene acceso el usuario 
         */
        $sede = new sede(); 
        $empresa = new empresa();
        $entidad = new entidad();
        
        $idempresa = $empresa->idempresa($enterprise); 
        $param = array(
            'sede.idempresa' => $idempresa,
            'entidadsede.identidad' => $this->objTtoken->my
        );
        
        $data = array(
            'sedes' => $sede->autorizadas($param, $this->objTtoken->mysede),
        );

        if(isset($request['getmedicos']) && $request['getmedicos'] === '1'){
            $data['medicos'] = $entidad->entidades(['entidad.idempresa' => $idempresa, 'entidad.tipomedico' => '1']);
        }

        if(isset($request['getperfiles']) && $request['getperfiles'] == '1'){
            $data['perfiles'] = $empresa->perfiles($idempresa);
        }
        
        return $this->crearRespuesta($data, 200);        
    }

    public function index(Request $request, $enterprise) {

        $empresa = new empresa();
        $entidad = new entidad();
        $param = array();

        $paramsTMP = $request->all();
        $idempresa = $empresa->idempresa($enterprise);

        $param['entidad.idempresa'] = $idempresa;
        
        $likedni = '';
        $idSede = '';
        
        if (isset($paramsTMP['numerodoc']) && !empty($paramsTMP['numerodoc'])) {
            $param['entidad.numerodoc'] = $paramsTMP['numerodoc'];
        } 

        if (isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede']) && isset($paramsTMP['likehc']) && !empty($paramsTMP['likehc'])) {
            $idSede = $paramsTMP['idsede'];
        }
        
        if (isset($paramsTMP['likehc']) && !empty($paramsTMP['likehc'])) {
            $param['historiaclinica.hc'] = $paramsTMP['likehc'];
        }

        if (isset($paramsTMP['likenumerodoc']) && !empty($paramsTMP['likenumerodoc'])) {
            $likedni = $paramsTMP['likenumerodoc'];
        }

        if (isset($paramsTMP['acceso']) && $paramsTMP['acceso'] !== '') {
            $param['entidad.acceso'] = $paramsTMP['acceso'];
        }

        if (isset($paramsTMP['idperfil']) && !empty($paramsTMP['idperfil'])) {
            $param['perfil.idperfil'] = $paramsTMP['idperfil'];
        }

        if (isset($paramsTMP['tipoentidad'])) {
            switch ($paramsTMP['tipoentidad']) {
                case 'cliente':
                    $param['entidad.tipocliente'] = '1';
                    break;
                case 'personal':
                    $param['entidad.tipopersonal'] = '1';
                    break;
                case 'medico':
                    $param['entidad.tipomedico'] = '1';
                    break;
                case 'proveedor':
                    $param['entidad.tipoproveedor'] = '1';
                    break;
                default:
                    break;
            }
        } 
 
        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) { 
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }

        if (isset($paramsTMP['orderName']) && !empty($paramsTMP['orderName']) && isset($paramsTMP['orderSort']) && !empty($paramsTMP['orderSort'])) {
            $orderName = $paramsTMP['orderName'];
            $orderSort = $paramsTMP['orderSort'];   
        }

        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';
        

        $verPerfil = isset($paramsTMP['verPerfil']) && $paramsTMP['verPerfil'] === '1' ? true: false;

        $dataentidad = $entidad->grid($param, $like, $pageSize, $orderName, $orderSort, $idSede, $likedni, $verPerfil);
 
        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $dataentidad->total();
            $dataentidad = $dataentidad->items();
        }
 
        if (isset($paramsTMP['formato']) && !empty($paramsTMP['formato'])) {
            if(in_array($paramsTMP['formato'], ['xls', 'xlsx'])){
                $data = array(); 
                foreach($dataentidad as $row){
                  
                    $data[] = array(
                        'DOC' => $row->documentoabrev, 
                        'NUMERO' => $row->numerodoc,                        
                        'APELLIDOS Y NOMBRES' => $row->entidad, 
                        'MOVIL' => $row->celular,
                        'CORREO E' => $row->email,
                        'PERFIL' => $row->nombreperfil,
                        'PERMITIR ACCESO' => $row->acceso,
                        'VALIDACION IP' => $row->validacionip,
                        'VALIDACION HORARIO' => $row->validacionhorario 
                    );
                }

                return Excel::download(new DataExport($data), 'Reporte_' . date('Y-m-d H:i:s') . '.xlsx');
            }
        } else {
            return $this->crearRespuesta($dataentidad, 200, $total); 
        }
    }

    public function search(Request $request, $enterprise) {

        $empresa = new empresa();
        $entidad = new entidad();
        $param = array();

        $paramsTMP = $request->all();
        $idempresa = $empresa->idempresa($enterprise);

        $param['entidad.idempresa'] = $idempresa;
        
        $likedni = '';
        $idSede = '';
         
 
        $pageSize = 15;
        $orderName = '';
        $orderSort = ''; 

        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';
        
        $verPerfil = isset($paramsTMP['verPerfil']) && $paramsTMP['verPerfil'] === '1' ? true: false;

        $dataentidad = $entidad->search($param, $like, $pageSize, $orderName, $orderSort, $idSede, $likedni, $verPerfil);
        

        // dd($dataentidad);
        $total = '';
        // if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $dataentidad->total();
            $dataentidad = $dataentidad->items();
        // }
       
        return $this->crearRespuesta($dataentidad, 200, $total);     
    }

    public function cumpleanos(Request $request, $enterprise) {
        $empresa = new empresa();
        $entidad = new entidad();

        $param['entidad.idempresa'] = $empresa->idempresa($enterprise);
        $data = $entidad->getCumpleanos($param);

        return $this->crearRespuesta($data, 200);
    }

    public function recovery(Request $request, $enterprise) {

        if (!$request->filled('iddocumento')) { 
            return $this->crearRespuesta('Tipo documento es requerido', [200, 'info']);
        }

        if (!$request->filled('numerodoc')) { 
            return $this->crearRespuesta('N° Documento es requerido', [200, 'info']);
        }
 
        $entidad = new entidad();

        $request = $request->all();

        $fields = array('email_verified_at', 'identidad', 'numerodoc', 'password', 
                        'apellidopat', 'apellidomat', 'nombre', 
                        'imgperfil', 'sexo', 'email', 'iddocumento', 'acceso');

        $user = entidad::select($fields)
                    ->where('idempresa', 1)
                    ->where('iddocumento', $request['iddocumento'])
                    ->where('numerodoc', $request['numerodoc'])
                    ->whereNull('entidad.deleted')
                    ->first();

        $documento = '';
        if ($request['iddocumento'] === 1) {
            $documento = 'DNI: ' .$request['numerodoc']; 
        }

        if ($request['iddocumento'] === 3) {
            $documento = 'CARNET EXT.: ' .$request['numerodoc']; 
        }

        if ($request['iddocumento'] === 4) {
            $documento = 'PASAPORTE Y OTROS: ' .$request['numerodoc']; 
        }

        //1.- Usuario existe
        if (is_null($user)) {    
            return $this->crearRespuesta($documento . ' no existe', [200, 'info']);
        }

        //2.- Usuario tenga acceso
        if (empty($user->email) ) {
            return $this->crearRespuesta('No tiene correo.', [200, 'info']);
        }

        //2.- Usuario tenga acceso
        if ($user->acceso === 0 || is_null($user->acceso) && ($user->email_verified_at === 0 || is_null($user->email_verified_at))) {
            return $this->crearRespuesta('Tiene un correo '. $user->email .' por favor confirme su registro.', [200, 'info']);
        }

        $urlPortal = '';
        if (isset($request['web']) && $request['web'] === 'reservatuconsulta') {
            $urlPortal = 'reservatuconsulta';
        }

        \Log::info(print_r(date("d/m/Y H:i:s") . ' '.$user->email, true));
        Mail::to($user->email)->send(new RecoverySend($user, $urlPortal)); 
        \Log::info(print_r(date("d/m/Y H:i:s"), true));
        
        $respuesta = array(
            'nombre' => $user->nombre,
            'email' => $user->email
        );
        return $this->crearRespuesta($respuesta, 200); 
    }

    public function showprofile($enterprise, $id) {

        $empresa = new empresa(); 
        $entidad = new entidad(); 

        $row = $entidad->entidad(['entidad.identidad' => $id], '', true);

        if ($row) {
            $row->fechanacimiento = $this->formatFecha($row->fechanacimiento, true);

            $listcombox = array(   
                'entidadsede' => $entidad->listaEntidadSede(['entidadsede.identidad' => $id]),
                'entidadespecialidad' => $entidad->listaEntidadEspecialidad(['identidad' => $id]), 
                'timezonedatephp' => env('APP_TIMEZONE') . ' - ' . date("d/m/Y H:i:s")
            );

            $ubigeo = $row->idubigeo;
         
            if (!empty($ubigeo)) {
                $pais = substr($ubigeo, 0, 2);
                $dpto = substr($ubigeo, 2, 3);
                $prov = substr($ubigeo, 5, 2);
                $dist = substr($ubigeo, 7, 2);
                $ubigeorow['paises'] = $empresa->paises(['pais' => $pais]);
                $ubigeorow['departamentos'] = $empresa->departamentos($pais, ['dpto' => $dpto]);
                $ubigeorow['provincias'] = $empresa->provincias($pais, $dpto, ['prov' => $prov]);
                $ubigeorow['distritos'] = $empresa->distritos($pais, $dpto, $prov, ['dist' => $dist]);
                $row->pais = empty($ubigeorow['paises']) ? '' : $ubigeorow['paises'][0]->nombre;
                $row->dpto = empty($ubigeorow['departamentos']) ? '' : $ubigeorow['departamentos'][0]->nombre;
                $row->prov = empty($ubigeorow['provincias']) ? '' : $ubigeorow['provincias'][0]->nombre;
                $row->dist = empty($ubigeorow['distritos']) ? '' : $ubigeorow['distritos'][0]->nombre;
            }

            return $this->crearRespuesta($row, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Entidad no encotrado', 404);
    }


    public function show($enterprise, $id) {

        $empresa = new empresa();
        $entidad = new entidad(); 
        $especialidad = new especialidad();
        $publicacion = new publicacion();

        $row = $entidad->entidad(['entidad.identidad' => $id]);

        if ($row) {
            $row->fechanacimiento = $this->formatFecha($row->fechanacimiento);
            $idempresa = $empresa->idempresa($enterprise);
                
            $param = array( 
                'sede.idempresa' => $idempresa,
                'entidadsede.identidad' => $this->objTtoken->my // '1'
            );
            
            $param2 = array(
                'entidad.idempresa' => $idempresa,
                'entidad.tipoafiliado' => '1'
            );

            $sedes = sede::select('idsede', 'nombre', 'direccion')->where('idempresa', '=', $idempresa)->get()->all();
            $listcombox = array(
                'documentos' => $empresa->documentos(),
                'perfiles' => $empresa->perfiles($idempresa),                
                'sedes' => $sedes,
                'entidadsede' => $entidad->listaEntidadSede(['entidadsede.identidad' => $id]), 
                'cargosorg' => cargoorg::where('idempresa', '=', $idempresa)->get()->all(), 
                'especialidades' => $especialidad->especialidades(['especialidad.idempresa' => $idempresa]), 
                'entidadespecialidad' => $entidad->listaEntidadEspecialidad(['identidad' => $id]),  
                'hclinicas' => $entidad->historiasclinicas($param, $id),
                'tarifariomedico' => $entidad->tarifariomedico($param, $id),
                'horasi' => $empresa->horas('00:00:00', '23:45:00', 15, 0),
                'horasf' => $empresa->horas('00:14:00', '23:59:00', 15, 14),
                'etiquetas' => $publicacion->gridetiquetas(['etiqueta.idempresa' => $idempresa]), 
                'entidadEtiquetas' => $entidad->etiquetas(['entidad_etiqueta.identidad' => $id]),
                'afiliados' => $entidad->entidades($param2, FALSE, NULL, ['entidad.identidad', 'entidad.entidad', 'entidad.acronimo'])
            );

            //dd($entidad->historiasclinicas($param, $id));
            
            $listcombox['hcproximas'] = [];
            foreach($sedes as $row2) {
                $listcombox['hcproximas'][] = array(
                    'idsede'=> $row2->idsede, 
                    'nombre'=> $row2->nombre, 
                    'hc' => $entidad->generaHC('', $row2->idsede, false)
                ); 
            }   

            $ubigeo = $row->idubigeo;
            if (!empty($ubigeo)) {
                $pais = substr($ubigeo, 0, 2);
                $dpto = substr($ubigeo, 2, 3);
                $prov = substr($ubigeo, 5, 2);
                $dist = substr($ubigeo, 7, 2);

                // dd($empresa->departamentos($pais));
                $listcombox['paises'] = $empresa->paises();
                $listcombox['departamentos'] = $empresa->departamentos($pais);
                $listcombox['provincias'] = $empresa->provincias($pais, $dpto);
                $listcombox['distritos'] = $empresa->distritos($pais, $dpto, $prov);
                $row->pais = $pais;
                $row->dpto = $dpto;
                $row->prov = $prov;
                $row->dist = $dist;
            } else {
                $listcombox['paises'] = $empresa->paises();
            }
            return $this->crearRespuesta($row, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Entidad no encotrado', 404);
    }
    
    public function generarhc(Request $request, $enterprise){
        
        $objEntidad = new entidad();
        $empresa = new empresa($enterprise);

        $idempresa = $empresa->idempresa;

        $request = $request->all();
        
        /* 1.- GenerarHC, caso no tenga. */ 
        $entidad = $objEntidad->entidadHC(array('identidad' => $request['idpaciente']), $request['idsede']);
        
        if (isset($entidad->idhistoriaclinica)) {  
            if (empty($entidad->hc)) {
                $entidad->hc = $objEntidad->generaHC('', $request['idsede'], false); 
                \DB::table('historiaclinica')
                    ->where('idhistoriaclinica', $entidad->idhistoriaclinica)
                    ->update(array('hc' => $entidad->hc, 'id_updated_at' => $this->objTtoken->my)); 

                return $this->crearRespuesta('Hitoria clínica generado.', 200, '', '', $entidad->hc); 
            }else{  
                return $this->crearRespuesta('Paciente ya tiene N°Historia, es '.$entidad->hc, [200, 'info']);
            }
        } else { 
            $entidad->hc = $objEntidad->generaHC($request['idpaciente'], $request['idsede'], true, $this->objTtoken->my); 
            return $this->crearRespuesta('Hitoria clínica generado.', 200, '', '', $entidad->hc); 
        } 
    }

    public function nrodocumento(Request $request, $enterprise) {
        
        $empresa = new empresa($enterprise);       
        $objEntidad = new entidad();

        $paramsTMP = $request->all();

        $idempresa = $empresa->idempresa;
        
        $row = array('existeEntidad' => false);
        
        if(isset($paramsTMP['idsede'])){
            //Busqueda de HC y nombre de DocumentoIdentidad            
            $entidad = $objEntidad->entidadHC(array('entidad.numerodoc' => $paramsTMP['numerodoc'], 'entidad.idempresa' => $idempresa), $paramsTMP['idsede']);            
        } else {
            $entidad = entidad::where(['entidad.numerodoc' => $paramsTMP['numerodoc'], 'entidad.idempresa' => $idempresa])->first();
        }

        if ($entidad) {
            switch ($paramsTMP['tipoentidad']) {
                case 'personal':
                    $tipo = 'tipopersonal';
                    break;
                case 'medico':
                    $tipo = 'tipomedico';
                    break;
                case 'cliente':
                    $tipo = 'tipocliente';
                    break;
                case 'proveedor':
                    $tipo = 'tipoproveedor';
                    break;
                default:
                    $tipo = '';
                    break;
            }

            $row['existeEntidad'] = true;
            $row['existeSubEntidad'] = false;
            $row['nombreEntidad'] = $entidad->entidad;
            $row['numeroDocEntidad'] = $entidad->numerodoc;
            $row['idEntidad'] = $entidad->identidad;
            $row['telefonoEntidad'] = $entidad->telefono;
            $row['celularEntidad'] = $entidad->celular;
            $row['emailEntidad'] = $entidad->email;
            
            if(isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])){
                $row['documentoEntidad'] = $entidad->documento;
                $row['hcEntidad'] = $entidad->hc;
            }
            
            if ($entidad->$tipo === '1') {
                $row['existeSubEntidad'] = true;
            }
        }
        return $this->crearRespuesta($row, 201);
    }
    
    public function nrodocumentohc(Request $request, $enterprise) {

        $paramsTMP = $request->all();
        
        $objEntidad = new entidad();

        $idempresa = $empresa->idempresa($enterprise);

        $entidad = $objEntidad->entidadHCC($paramsTMP['hc'], $paramsTMP['idsede'], $idempresa);
        
        return $this->crearRespuesta($entidad, 201);
    }
    
    public function updatesubentidad(Request $request, $enterprise, $id) {

        $entidad = entidad::find($id);

        if ($entidad) {
            $paramsTMP = $request->all();

            switch ($paramsTMP['tipoentidad']) {
                case 'personal':
                    $tipo = 'tipopersonal';
                    break;
                case 'medico':
                    $tipo = 'tipomedico';
                    break;
                case 'cliente':
                    $tipo = 'tipocliente';
                    break;
                case 'proveedor':
                    $tipo = 'tipoproveedor';
                    break;
                default:
                    $tipo = '';
                    break;
            }
            $entidad->updateEntidad([$tipo => '1'], $id);

            return $this->crearRespuesta($entidad->entidad . ' ha sido creado.', 201);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una entidad', 404);
    }

    public function updatepassword(Request $request, $enterprise, $id) {
        $entidad = new entidad();

        $row = $entidad->entidad(['entidad.identidad' => $id]);
        $paramsTMP = $request->all();

        if ($row) { 
            if(isset($paramsTMP['reset']) && $paramsTMP['reset'] === '1' ){
                $param = array('password' => $row->numerodoc);
            }else{
                if ($paramsTMP['contrasenaactual'] !== $row->password) {
                    return $this->crearRespuesta('Contraseña actual no es correcta.', [200, 'info']);
                }
                $param = array('password' => $paramsTMP['contrasenanueva']);
            } 
            
            $entidad->updateEntidad($param, $id);

            return $this->crearRespuesta('Contraseña a sido actualizado.', 201);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una entidad', 404);
    }

    public function newentidad(Request $request, $enterprise) {

        $empresa = new empresa();
        $especialidad = new especialidad();
        $entidad = new entidad();
        $publicacion = new publicacion();
        
        $paramsTMP = $request->all();
        $idempresa = $empresa->idempresa($enterprise);
        $listcombox = array( 
            'documentos' => $empresa->documentos(),
            'paises' => $empresa->paises(),
            'horasi' => $empresa->horas('00:00:00', '23:45:00', 15, 0),
            'horasf' => $empresa->horas('00:14:00', '23:59:00', 15, 14),
            'etiquetas' => $publicacion->gridetiquetas(['etiqueta.idempresa' => $idempresa])
        ); 
        
        $param = array(
            'sede.idempresa' => $idempresa,
            'entidadsede.identidad' => $this->objTtoken->my
        );

        $param2 = array(
            'entidad.idempresa' => $idempresa,
            'entidad.tipoafiliado' => '1'
        );
        
        switch ($paramsTMP['tipoentidad']) {
            case 'proveedor':
                break;
            case 'cliente':
                $listcombox['hclinicas'] = $entidad->historiasclinicas($param);
                break; 
            case 'personal': 
                $listcombox['perfiles'] = $empresa->perfiles($idempresa);
                $listcombox['sedes'] = sede::select('idsede', 'nombre', 'direccion')->where('idempresa', '=', $idempresa)->get()->all();
                $listcombox['cargosorg'] = cargoorg::where('idempresa', '=', $idempresa)->get()->all();

                $listcombox['afiliados'] = $entidad->entidades($param2, FALSE, NULL, ['entidad.identidad', 'entidad.entidad', 'entidad.acronimo']);
                break;
            case 'medico':
                $listcombox['perfiles'] = $empresa->perfiles($idempresa);
                $listcombox['sedes'] = sede::select('idsede', 'nombre', 'direccion')->where('idempresa', '=', $idempresa)->get()->all();
                $listcombox['cargosorg'] = cargoorg::where('idempresa', '=', $idempresa)->get()->all();
                $listcombox['especialidades'] = $especialidad->especialidades(['especialidad.idempresa' => $idempresa]);
                $listcombox['tarifariomedico'] = $entidad->tarifariomedico($param);
                break;
            default:
                break;
        }

        return $this->crearRespuesta([], 200, '', '', $listcombox);
    }

    public function storePaciente(Request $request, $enterprise) {
 
        $empresa = new empresa(); 

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        $espacio = '';
        $apellidomat = '';
        if (isset($request['entidad']['apellidomat'])) {                        
            $apellidomat = ucwords(mb_strtolower(trim($request['entidad']['apellidomat'])));
            $espacio = !empty($apellidomat) ? ' ' : '';
        } else {
            $apellidomat = '';
        }

        $apellidopat = ucwords(mb_strtolower(trim($request['entidad']['apellidopat'])));
        $nombre = ucwords(mb_strtolower(trim($request['entidad']['nombre'])));
        $nombrecompleto = $apellidopat . $espacio . $apellidomat . ', ' . $nombre;

        $nacimiento = null;
        if(!empty($request['entidad']['fechanacimiento'])) {
            $nacimiento = $this->formatFecha($request['entidad']['fechanacimiento'], 'yyyy-mm-dd');
        }

        // VALIDACIONES        
        // 1. Validar Nro. Documento
        $numerodoc = trim($request['entidad']['numerodoc']);
        $email = trim($request['entidad']['email']);

        $entidad = entidad::select('numerodoc', 'entidad')
                    ->where('idempresa', $idempresa)
                    ->where('iddocumento', $request['entidad']['iddocumento'])
                    ->where('numerodoc', $numerodoc)
                    ->first();

        switch ($request['entidad']['iddocumento']) {
            case '1': $tipodoc = 'DNI'; break;
            case '3': $tipodoc = 'CARNET EXT.'; break;
            case '4': $tipodoc = 'PASAPORTE U OTROS'; break;
        }

        if ($entidad) {
            return $this->crearRespuesta(
                array('code' => '1', 'message' => "El usuario ya existe."), [200, 'info']);
        }

        // 2. Validar Email
        $entidad = entidad::select('email')
                    ->where('idempresa', $idempresa)
                    ->where('email', $email) 
                    ->first();

        if ($entidad) {
            return $this->crearRespuesta(
                array('code' => '2', 'message' => $email .' ya existe.'), [200, 'info']);
        }

        $request['entidad']['fechanacimiento'] = $nacimiento;
        $request['entidad']['apellidopat'] = $apellidopat;
        $request['entidad']['nombre'] = $nombre;
        $request['entidad']['entidad'] = $nombrecompleto;    
        $request['entidad']['idempresa'] = $idempresa;
        $request['entidad']['email_verified_at'] = '0';
        $request['entidad']['acceso'] = '1';
        $request['entidad']['tipocliente'] = '1';
        $request['entidad']['horario'] = '1';        
        $request['entidad']['created_at'] = date('Y-m-d H:i:s');
        $request['entidad']['id_created_at'] = 4844; 

        \DB::beginTransaction();
        try {
            $entidad = entidad::create($request['entidad']);

            $key = "x1TLVtPhZxN64JQB3fN8cHSp69999999";
            $token = array(
                "iss" => "https://wwww.centromedicoosi.com",
                "my" => $entidad->identidad,
                "myentidad" => $entidad->entidad,  
                "mytime" => date('Y-m-d H:i:s'), 
            ); 
            $jwt = JWT::encode($token, $key);

            $entidad->password = $request['entidad']['password'];
            $entidad->verification_token = $jwt;
            $entidad->save();

            $return = Mail::to($entidad->email)->send(new ConfirmacionSend($entidad));
            // \Log::info(print_r($return, true));
            // $id = $entidad->identidad;
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit(); 
 
        return $this->crearRespuesta(array('correo' => $request['entidad']['email'], 'message' => 'Paciente registrado'), 201);
    }

    public function store(Request $request, $enterprise) {

        $objEntidad = new entidad();
        $empresa = new empresa();
        // $sendEmail = new sendEmail();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        if (isset($request['entidad']['fechanacimiento'])) {
            $request['entidad']['fechanacimiento'] = $this->formatFecha($request['entidad']['fechanacimiento'], 'yyyy-mm-dd');
        }

        if ($request['entidad']['iddocumento'] == 1 || $request['entidad']['iddocumento'] == 3 || $request['entidad']['iddocumento'] == 4 || is_null($request['entidad']['iddocumento'])) {


            $select = \DB::table('entidad')            
                    ->where('entidad.idempresa','=', $idempresa)
                    ->where('entidad.apellidopat','=', $request['entidad']['apellidopat']);

            if(isset($request['entidad']['apellidomat']) && !empty($request['entidad']['apellidomat']))
                    $select->where('entidad.apellidomat','=', $request['entidad']['apellidomat']);

            $data =  $select
                        ->where('entidad.nombre', 'like', '%' . $request['entidad']['nombre'] . '%') 
                        ->get()->all();

            if (!empty($data)) {

                $apellidopat = ', ';
                if ($data[0]->apellidomat)
                    $apellidopat = ' '.$data[0]->apellidomat . ', ';

                $tmpEntidad = $data[0]->apellidopat . $apellidopat . $data[0]->nombre;
                return $this->crearRespuesta('Paciente: '.$tmpEntidad.'. Ya existe.', [200, 'info']);
            }
 
            if(isset($request['validarentidad']) && $request['validarentidad'] === 1) {
                $tmpnombres = explode(" ", $request['entidad']['nombre']);
                if (count($tmpnombres) > 1) {
                    $tmpEntidad = null;
                    foreach ($tmpnombres as $nombre) {                        
                        $select = \DB::table('entidad')            
                            ->where('entidad.idempresa','=', $idempresa)
                            ->where('entidad.apellidopat','=', $request['entidad']['apellidopat']);

                        if(isset($request['entidad']['apellidomat']) && !empty($request['entidad']['apellidomat']))
                            $select->where('entidad.apellidomat','=', $request['entidad']['apellidomat']);

                        $data =  $select
                                    ->where('entidad.nombre', 'like', '%' . $nombre . '%')
                                    ->get()->all(); 
                            
                        if($data) {

                            $apellidopat = ', ';
                            if ($data[0]->apellidomat)
                                $apellidopat = ' '.$data[0]->apellidomat . ', ';

                            $tmpEntidad = $data[0]->apellidopat . $apellidopat . $data[0]->nombre;
                            break;
                        }
                    }

                    if (!empty($tmpEntidad)) 
                        return $this->crearRespuesta('Paciente: '.$tmpEntidad.'. Ya existe.', [200, 'info']);
                }
            }
        }

        // return $this->crearRespuesta('STOP', [200, 'info']);

        if ($request['entidad']['iddocumento'] == 1 || $request['entidad']['iddocumento'] == 3 || $request['entidad']['iddocumento'] == 4 || is_null($request['entidad']['iddocumento'])) { //DNI
            $espacio = '';
            if(isset($request['entidad']['apellidomat'])) {                        
                $request['entidad']['apellidomat'] = ucwords(mb_strtolower(trim($request['entidad']['apellidomat'])));
                $espacio = !empty($request['entidad']['apellidomat']) ? ' ' : '';
            }else{
                $request['entidad']['apellidomat'] = '';
            }

            $request['entidad']['apellidopat'] = ucwords(mb_strtolower(trim($request['entidad']['apellidopat']))); 
            $request['entidad']['nombre'] = ucwords(mb_strtolower(trim($request['entidad']['nombre'])));   

            $request['entidad']['entidad'] = $request['entidad']['apellidopat'] . $espacio . $request['entidad']['apellidomat'] . ', ' . $request['entidad']['nombre']; 
            // return $this->crearRespuesta('No puede registrarse'.$request['entidad']['entidad'], [200, 'info']);
        }
        
        //return $this->crearRespuesta($request['entidad']['entidad'], [200, 'info']);        
        if ($request['entidad']['iddocumento'] == 2) { //RUC  
            $request['entidad']['entidad'] = $request['entidad']['razonsocial'];
        } 

        // return $this->crearRespuesta('No', [200, 'info'], '', '', $request['entidad']['entidad']);

        $request['entidad']['idubigeo'] = NULL;
        if (!empty($request['entidad']['pais'])) {
            $dpto = empty($request['entidad']['dpto']) ? '00' : $request['entidad']['dpto'];
            $prov = empty($request['entidad']['prov']) ? '00' : $request['entidad']['prov'];
            $dist = empty($request['entidad']['dist']) ? '00' : $request['entidad']['dist'];
            $request['entidad']['idubigeo'] = $request['entidad']['pais'] . $dpto . $prov . $dist;
        }

        $request['entidad']['idempresa'] = $idempresa;
        $request['entidad']['acceso'] = '1';

        //VALIDACIONES 
        $numerodoc = $request['entidad']['numerodoc'];
        if(!empty($numerodoc)){
            $count = entidad::select('numerodoc')->where(['numerodoc' => $numerodoc, 'idempresa' => $idempresa])->count();
            if ($count > 0) {
                return $this->crearRespuesta('No puede registrarse, el n&uacute;mero de documento "' . $numerodoc . '" ya existe.', [200, 'info']);
            }
        }

        if(isset($request['hclinica'])){
            $validation = ['inValid' => false];
            
            foreach ($request['hclinica'] as $row) {                
                $entidad = $objEntidad->entidadHCC($row['hc'], $row['idsede'], $idempresa);                
                if ($entidad) {
                    $validation['inValid'] = true;
                    $validation['message'] = 'Historia "'.$row['hc'].'" ya existe. Pertenece a '.$entidad->entidad;
                    break;
                }
            }
            
            if ($validation['inValid']) {
                return $this->crearRespuesta($validation['message'], [200, 'info']);
            }
        }
        
        //


        \DB::beginTransaction();
        try {
            //Graba en 7 tablas(entidad, entidadperfil, SUBENTIDADES, entidadespecialidad, entidadsede)
            switch ($request['tipoentidad']) {
                case 'proveedor':
                    $request['entidad']['tipoproveedor'] = '1';
                    break;
                case 'cliente':
                    $request['entidad']['tipocliente'] = '1';
                    break;
                case 'medico':
                    $request['entidad']['tipomedico'] = '1';
                    $request['entidad']['tipopersonal'] = '1';
                    break;
                case 'personal':
                    $request['entidad']['tipopersonal'] = '1';
                    break;
            }
            
            /* Campos auditores */
            $request['entidad']['created_at'] = date('Y-m-d H:i:s');
            $request['entidad']['id_created_at'] = $this->objTtoken->my;
            /* Campos auditores */

            $entidad = entidad::create($request['entidad']); 
            $id = $entidad->identidad;

            /* */ 
            if(isset($request['entidad']['numerodoc'])) {
                \DB::table('entidad')
                    ->where('identidad', $id)
                    ->update(['password' => $request['entidad']['numerodoc']]);

            } 
            
            
            //Enviar si tiene email, es cliente, y tiene documento(DNI, PASAPORTE, C.E.)
            if($entidad->email && !empty($entidad->numerodoc) && empty($entidad->emailportal)) {
                //$entidadcliente = $entidad->entidad(['entidad.identidad' => $entidad->identidad]);
                //$build = $sendEmail->send($entidad->email, 'Bienvenido al portal web del paciente - Centro Médico OSI', $this->htmlemail($entidadcliente));
                //$entidad->updateEntidad(['emailportal' => $entidad->email], $id);
            }
            
            if(isset($request['hclinica'])){
                $historiaclinicaInsert = [];
                foreach ($request['hclinica'] as $row) {
                    $historiaclinicaInsert[] = ['idpaciente' => $id, 'idsede' => $row['idsede'], 'hc' => $row['hc']];
                }
            }
            
            if(isset($request['tarifas'])){
                $tarifasInsert = [];
                foreach ($request['tarifas'] as $row) {
                    $tarifasInsert[] = array( 
                        'idsede' => $row['idsede'],
                        'idmedico' => $id,
                        'idproducto' => $row['idproducto'],
                        'preciounit' => $row['preciounit']
                    );
                }
            }
            
            if(isset($request['entidadespecialidad'])){
                $dataEntEsp = [];
                foreach ($request['entidadespecialidad'] as $row) {
                    $dataEntEsp[] = ['identidad' => $id, 'idespecialidad' => $row['idespecialidad']];
                }
            }
            
            if(isset($request['entidadsede'])){
                $dataEntSed = [];
                foreach ($request['entidadsede'] as $row) {
                    $dataEntSed[] = ['identidad' => $id, 'idsede' => $row['idsede']];
                }
            }
            
            $dataEntPer = [];
            if(isset($request['entidad']['idperfil'])){                
                if (!empty($request['entidad']['idperfil'])) {
                    $dataEntPer = array('identidad' => $id, 'idperfil' => $request['entidad']['idperfil']);
                }
            }
            
            switch ($request['tipoentidad']) {
                case 'proveedor':
                    $request['proveedor']['identidad'] = $id;
                    break;
                case 'cliente':
                    //No es necesario grabar en tabla cliente.
                    //$request['cliente']['identidad'] = $id;                     
                    break;
                case 'personal':
                    $request['personal']['identidad'] = $id;
                    
                    if(isset($request['entidadsede'])){
                        $entidad->GrabarEntidadSede($dataEntSed, $id);
                    }

                    $entidad->GrabarEntidadPerfil($dataEntPer, $id);
                    $entidad->updateEntidad(['password' => $request['entidad']['numerodoc']], $id);
                    break;
                case 'medico':
                    $request['personal']['identidad'] = $id;
                    $request['medico']['identidad'] = $id;
                    $entidad->GrabarEntidadSede($dataEntSed, $id);
                    $entidad->GrabarEntidadEspecialidad($dataEntEsp, $id);
                    $entidad->GrabarEntidadPerfil($dataEntPer, $id);
                    $entidad->updateEntidad(['password' => $request['entidad']['numerodoc']], $id);
                    break;
                default:
                    break;
            }
            
            if (isset($request['hclinica'])) 
                \DB::table('historiaclinica')->insert($historiaclinicaInsert); 
                
            if (isset($request['tarifas'])) 
                \DB::table('tarifamedico')->insert($tarifasInsert); 

            if(isset($request['etiquetas'])){
                $dataEtiquetas = [];
                foreach ($request['etiquetas'] as $row) {
                    $dataEtiquetas[] = ['identidad' => $id, 'idetiqueta' => $row['idetiqueta']];
                }
                $entidad->GrabarEtiquetas($dataEtiquetas, $id);
            }
                 
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit(); 

        //Retorna el "identidad" para opcion de una "Nueva citas medica", y no volver a consultar. 
        return $this->crearRespuesta( $entidad->entidad . ' ha sido creado.', 201, '','', $entidad->identidad);
    }

    private function htmlemail($entidadcliente) {
        return '<img src="https://sistemas.centromedicoosi.com/img/osi/email/emailhead.png" width="100%">
                        <div style="padding: 0px 30px 0px 30px; color: #333; font-family: Arial; line-height: 20px;">
                            <h5><strong>HOLA! '.mb_strtoupper($entidadcliente->nombre).',</strong></h5>
                            <p>¡Bienvenido al portal web del Centro Médico OSI! Ahora desde la portal web del paciente podrás realizar: </p>
                            
                            <div style="line-height: 25px; padding-left: 10px;">
                                <ul>
                                    <li>Reserva de cita médica.</li>
                                    <li>Pagos en linea de tu cita médica.</li>
                                    <li>Consultar resultados de citas médicas(Tratamientos).</li>
                                    <li>Consultar terapias realizadas.</li>
                                    <li>Editar tus datos personales y mucho más.</li>
                                </ul>                                
                            </div>
                            <div style="font-weight: bold;"><u>Tus datos de ingreso al portal del paciente son:</u></div>
                            <div style="line-height: 25px; padding-left: 10px;">
                                <strong>Documento:</strong> '. $entidadcliente->documentoabrev .'<br>
                                <strong>N° Documento:</strong> '. $entidadcliente->numerodoc .'<br>
                                <strong>Contraseña:</strong> '. $entidadcliente->password .'<br>
                            </div>
                            <p>Accede al portal web desde este enlace <a href="https://sistemas.centromedicoosi.com">http://sistemas.centromedicoosi.com</a></p>
                            <p>Que tengas un buen día.</p>
                        </div>
                        <img src="https://sistemas.centromedicoosi.com/img/osi/email/emailfooter.jpg" width="100%">';
    }
    
    public function updateProfile(Request $request, $enterprise, $id) {
        
        $empresa = new empresa(); 

        $request = $request->all();

        $entidad = entidad::find($id); 
        
        if ($entidad) {
            
            $param = array(
                'celular' => $request['entidad']['celular'],
                'email' => $request['entidad']['email'],
            );
            /* Campos auditores */
            $param['updated_at'] = date('Y-m-d H:i:s');
            $param['id_updated_at'] = $this->objTtoken->my;
            /* Campos auditores */
            $entidad->fill($param);

            \DB::beginTransaction();
            try {
                $entidad->save();
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta($entidad->entidad . ' ha sido editado. ', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una entidad', 404);
    }

    public function update(Request $request, $enterprise, $id) {
        
        $empresa = new empresa();
        // $sendEmail = new sendEmail();

        $entidad = entidad::find($id);
        $idempresa = $empresa->idempresa($enterprise);
        
        if ($entidad) {
            $request = $request->all();
            if(isset($request['entidad']['fechanacimiento']))
                $request['entidad']['fechanacimiento'] = $this->formatFecha($request['entidad']['fechanacimiento'], 'yyyy-mm-dd');
            
            if(isset($request['entidad']['iddocumento'])){ 
                if ($request['entidad']['iddocumento'] == 1 || $request['entidad']['iddocumento'] == 3 || $request['entidad']['iddocumento'] == 4) { //DNI   
                    $espacio = '';
                    if(isset($request['entidad']['apellidomat'])) {                        
                        $request['entidad']['apellidomat'] = ucwords(mb_strtolower(trim($request['entidad']['apellidomat'])));
                        $espacio = !empty($request['entidad']['apellidomat']) ? ' ' : '';
                    }else
                        $request['entidad']['apellidomat'] = '';                    
                    $request['entidad']['apellidopat'] = ucwords(mb_strtolower(trim($request['entidad']['apellidopat']))); 
                    $request['entidad']['nombre'] = ucwords(mb_strtolower(trim($request['entidad']['nombre'])));   
 
                    $request['entidad']['entidad'] = $request['entidad']['apellidopat'] . $espacio . $request['entidad']['apellidomat'] . ', ' . $request['entidad']['nombre'];
                }
                if ($request['entidad']['iddocumento'] == 2) { //RUC
                    $request['entidad']['entidad'] = trim($request['entidad']['razonsocial']);
                }
            }
            
            if(isset($request['entidad']['pais'])){
                $request['etidad']['idubigeo'] = NULL;
                if (!empty($request['entidad']['pais'])) {
                    $dpto = empty($request['entidad']['dpto']) ? '00' : $request['entidad']['dpto'];
                    $prov = empty($request['entidad']['prov']) ? '00' : $request['entidad']['prov'];
                    $dist = empty($request['entidad']['dist']) ? '00' : $request['entidad']['dist'];
                    $request['entidad']['idubigeo'] = $request['entidad']['pais'] . $dpto . $prov . $dist;
                }
            }
            
            if(isset($request['entidadespecialidad'])){
                $dataEntEsp = [];
                foreach ($request['entidadespecialidad'] as $row) {
                    $dataEntEsp[] = ['identidad' => $id, 'idespecialidad' => $row['idespecialidad']];
                }
            }
            
            if(isset($request['entidadsede'])){
                $dataEntSed = [];
                foreach ($request['entidadsede'] as $row) {
                    $dataEntSed[] = ['identidad' => $id, 'idsede' => $row['idsede']];
                }
            }
            
            if(isset($request['entidad']['idperfil'])){
                $dataEntPer = [];
                if (!empty($request['entidad']['idperfil'])) {
                    $dataEntPer = array('identidad' => $id, 'idperfil' => $request['entidad']['idperfil']);
                }
            } 
            
            if(isset($request['entidad']['numerodoc'])){
                $numerodoc = $request['entidad']['numerodoc'];
                $consultado = false;
                if ($numerodoc !== $entidad->numerodoc) {
                    $consultado = true;
                    $count = entidad::select('numerodoc')->where(['numerodoc' => $numerodoc, 'idempresa' => $idempresa])->count();
                    if ($count > 0) {
                        return $this->crearRespuesta('No puede registrarse, el n&uacute;mero de documento "' . $numerodoc . '" ya existe.', [200, 'info']);
                    }
                }
            }
            
            if (isset($request['hclinica'])) {
                $validation = ['inValid' => false, 'message' => ''];       
                $historiaclinicaInsert = [];
                $historiaclinicaUpdate = [];
                foreach ($request['hclinica'] as $row) {

                    if(!empty($row['hc'])){ 
                        $proximahc = $entidad->generaHC('', $row['idsede'], false);
                        if($row['hc'] > $proximahc ){
                            $validation['inValid'] = true;
                            $validation['message'] = 'N° '.$row['hc'] . ' no permitido. Historia es mayor a historia próxima a generarse';
                            break; 
                        }                        
                    } 

                    if(isset($row['idhistoriaclinica'])){
                        //Editar
                        if(empty($row['hc'])){
                            $historiaclinicaUpdate[] = array('where' => ['idhistoriaclinica' => $row['idhistoriaclinica']], 'data' => ['hc' => NULL]);
                        }else{
                            $objeto = $entidad->entidadHCC($row['hc'], $row['idsede'], $idempresa);
                            if ($objeto && $entidad->identidad !== $objeto->identidad) {
                                $validation['inValid'] = true;
                                $validation['message'] = 'Historia "'.$row['hc'].'" ya existe. Pertenece a '.$objeto->entidad;
                                break;                        
                            }else{
                                $historiaclinicaUpdate[] = array('where' => ['idhistoriaclinica' => $row['idhistoriaclinica']], 'data' => ['hc' => $row['hc']]);
                            }
                        }
                    }else{
                        //Nuevo
                        $objeto = $entidad->entidadHCC($row['hc'], $row['idsede'], $idempresa);
                        if ($objeto) {
                            $validation['inValid'] = true;
                            $validation['message'] = 'Historia "'.$row['hc'].'" ya existe. Pertenece a '.$objeto->entidad;
                            break;                        
                        }else{
                            $historiaclinicaInsert[] = array('idpaciente' => $entidad->identidad, 'idsede' => $row['idsede'], 'hc' => $row['hc']);
                        }
                    }
                }

                if ($validation['inValid']) {
                    return $this->crearRespuesta($validation['message'], [200, 'info']);
                }
            }
            
            if (isset($request['tarifas'])) {
                $tarifamedicoInsert = [];
                $tarifamedicoUpdate = [];
                foreach ($request['tarifas'] as $row) {
                    if(isset($row['idtarifamedico'])){
                        //Editar
                        if(empty($row['preciounit']))
                            $tarifamedicoUpdate[] = array('where' => ['idtarifamedico' => $row['idtarifamedico']], 'data' => ['idproducto' => $row['idproducto'], 'preciounit' => NULL]);
                        else
                            $tarifamedicoUpdate[] = array('where' => ['idtarifamedico' => $row['idtarifamedico']], 'data' => ['idproducto' => $row['idproducto'], 'preciounit' => $row['preciounit']]);                        
                    }else{
                        //Nuevo
                        $tarifamedicoInsert[] = array('idsede' => $row['idsede'], 'idmedico' => $entidad->identidad, 'idproducto' => $row['idproducto'], 'preciounit' => $row['preciounit']);                        
                    }
                }
            }
            
            $numerodocBefore = $entidad->numerodoc; 
            /* Campos auditores */
            $request['entidad']['updated_at'] = date('Y-m-d H:i:s');
            $request['entidad']['id_updated_at'] = $this->objTtoken->my;
            /* Campos auditores */
            $entidad->fill($request['entidad']);

            \DB::beginTransaction();
            try {
                //Graba en 7 tablas(entidad, entidadperfil, SUBENTIDADES, entidadespecialidad, entidadsede)
                if(isset($request['tipoentidad'])){
                    switch ($request['tipoentidad']) {
                        case 'proveedor':
                            if (!isset($request['proveedor']['identidad'])) {
                                $request['proveedor']['identidad'] = $id;
                            }
                            break;
                        case 'cliente':
                            if (!isset($request['cliente']['identidad'])) {
                                $request['cliente']['identidad'] = $id;
                            }   
                            break;
                        case 'personal':
                            if (!isset($request['personal']['identidad'])) {
                                $request['personal']['identidad'] = $id;
                            }
                            $entidad->GrabarEntidadSede($dataEntSed, $id);
                            if(isset($request['entidad']['idperfil'])){
                                $entidad->GrabarEntidadPerfil($dataEntPer, $id);
                            }
                            break;
                        case 'medico':
                            if (!isset($request['personal']['identidad'])) {
                                $request['personal']['identidad'] = $id;
                            }
                            if (!isset($request['medico']['identidad'])) {
                                $request['medico']['identidad'] = $id;
                            }
                            $entidad->GrabarEntidadEspecialidad($dataEntEsp, $id);
                            $entidad->GrabarEntidadSede($dataEntSed, $id);
                            if(isset($request['entidad']['idperfil'])){
                                $entidad->GrabarEntidadPerfil($dataEntPer, $id);
                            }
                            break;
                        default:
                            break;
                    }
                } 
                
                if (isset($request['hclinica'])) {
                    if (!empty($historiaclinicaInsert))
                        \DB::table('historiaclinica')->insert($historiaclinicaInsert);

                    foreach ($historiaclinicaUpdate as $fila) {
                        \DB::table('historiaclinica')->where($fila['where'])->update($fila['data']);
                    } 
                }
                
                if (isset($request['tarifas'])) {
                    if (!empty($tarifamedicoInsert))
                        \DB::table('tarifamedico')->insert($tarifamedicoInsert);

                    foreach ($tarifamedicoUpdate as $fila) {
                        \DB::table('tarifamedico')->where($fila['where'])->update($fila['data']);
                    } 
                }
                
                if(isset($request['etiquetas'])){
                    $dataEtiquetas = [];
                    foreach ($request['etiquetas'] as $row) {
                        $dataEtiquetas[] = ['identidad' => $id, 'idetiqueta' => $row['idetiqueta']];
                    }
                    $entidad->GrabarEtiquetas($dataEtiquetas, $id);
                }

                $entidad->save();
                //Actualiza contrasena de vacio a su DNI
                if (isset($request['entidad']['numerodoc']) && empty($numerodocBefore)) {
                    $entidad->updateEntidad(['password' => $request['entidad']['numerodoc']], $id);
                }

                //Enviar si tiene email
                if($entidad->email && !empty($entidad->numerodoc) && empty($entidad->emailportal)) {
                    $entidadcliente = $entidad->entidad(['entidad.identidad' => $entidad->identidad]);
                    //$build = $sendEmail->send($entidad->email, 'Bienvenido al portal web del paciente - Centro Médico OSI', $this->htmlemail($entidadcliente));
                    //$entidad->updateEntidad(['emailportal' => $entidad->email], $id);
                }

            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta($entidad->entidad . ' ha sido editado. ', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una entidad', 404);
    }

    public function destroy(Request $request, $enterprise, $id) {
                        
        $entidad = entidad::find($id);

        if ($entidad) {
            $request = $request->all(); 
            //VALIDACIONES con tablas relacionadas  
            $cont = 0;
            if (!empty($entidad->tipopersonal)) {
                $cont++;
            }
            if (!empty($entidad->tipomedico)) {
                $cont++;
            }
            if (!empty($entidad->tipocliente)) {
                $cont++;
            }
            if (!empty($entidad->tipoproveedor)) {
                $cont++;
            }
            
            $return = $entidad->validadorDataRelacionada($id);
            if ($return['validator']) {
                return $this->crearRespuesta($return['message'], [200, 'info']);
            }
            
            \DB::beginTransaction();
            try {
                if ($cont === 1) {
                    //Elimina en 7 tablas(entidad, entidadperfil, SUBENTIDADES, entidadespecialidad, entidadsede)                
                    $entidad->GrabarEntidadPerfil([], $id); 
                    $entidad->GrabarEntidadEspecialidad([], $id);
                    $entidad->GrabarEntidadSede([], $id);

                    // $entidad->delete();
                    $update = array(
                        'deleted_at' => date('Y-m-d H:i:s'),
                        'id_deleted_at' => $this->objTtoken->my,
                        'deleted' => '1'
                    );
                    $entidad->fill($update);
                    $entidad->save();

                } else {
                    //Elimina segun el tipo de entidad
                    switch ($request['tipoentidad']) {
                        case 'personal':
                            if (empty($entidad->tipomedico)) {
                                $entidad->GrabarEntidadPerfil([], $id);
                                $entidad->GrabarEntidadSede([], $id);
                            }
                            $tipo = 'tipopersonal';
                            break;
                        case 'medico':
                            if (empty($entidad->tipopersonal)) {
                                $entidad->GrabarEntidadPerfil([], $id);
                                $entidad->GrabarEntidadSede([], $id);
                            }
                            $entidad->GrabarEntidadEspecialidad([], $id);
                            $tipo = 'tipomedico';
                            break;
                        case 'cliente':
                            $tipo = 'tipocliente';
                            break;
                        case 'proveedor':
                            $tipo = 'tipoproveedor';
                            break;
                        default:
                            $tipo = '';
                            break;
                    }
                     
                    $entidad->updateEntidad([$tipo => NULL], $id);
                }
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('"' . $entidad->entidad . '" a sido eliminado', 200);
        }
        return $this->crearRespuestaError('entidad no encotrado', 404);
    }

    public function modulos(Request $request, $enterprise) {

        $empresa = new empresa();
        $entidad = new entidad();

        $params = array(
            'entidad.numerodoc' => $this->objTtoken->myusername,
            'empresa.url' => $enterprise
        );

        $data['empresa'] = $empresa->empresa(['url' => $enterprise]);
        $data['userProfiles'] = $entidad->ListaPerfiles($params);
        $data['userModules'] = $entidad->ListaModules($params);

        return $this->crearRespuesta($data, 200);
    } 
    
    public function authenticate($enterprise, Request $request) {
        /* Autor: chaucachavez@gmail.com
         * Descripcion: Validacion de autenticacion
         * Nota: En servidor de despliegue descomentar Hash para la encriptacion de datos.
         * En servidor de desarrollo no es conveniente encriptar.
         */ 

        if (!$request->filled('username')) { 
            return $this->crearRespuesta('N° Documento es requerido', [200, 'info']);
        } 

        if (!$request->filled('password')) { 
            return $this->crearRespuesta('Contraseña es requerido', [200, 'info']);
        } 

        $key = "x1TLVtPhZxN64JQB3fN8cHSp69999999";
        $entidad = new entidad();
        $empresa = new empresa();
        $sede = new sede();
        $horariomedico = new horariomedico(); 
        
        $request = $request->all();
        
        $ip = isset($request['ip'])?$request['ip']:NULL; 
        $navegador = $request['navegador']; 
        $url =  $enterprise;              
        
        $idempresa = $empresa->idempresa($enterprise);
        
        $user = $entidad->entidad(['entidad.iddocumento' => $request['iddocumento'], 'numerodoc' => $request['username'], 'entidad.idempresa' => $idempresa]);
        
        $documento = '';
        if ($request['iddocumento'] === 1) {
            $documento = 'DNI: ' .$request['username']; 
        }

        if ($request['iddocumento'] === 3) {
            $documento = 'CARNET EXT.: ' .$request['username']; 
        }

        if ($request['iddocumento'] === 4) {
            $documento = 'PASAPORTE Y OTROS: ' .$request['username']; 
        }

        //1.- Usuario existe
        if (is_null($user)) {    
            return $this->crearRespuesta($documento . ' no existe', [200, 'info']);
        } 
        
        //2.- Contraseña correcta
        //if(Hash::check($request->get('password'), $user->password)) {
        if ($request['password'] !== $user->password) {
            return $this->crearRespuesta('Contraseña incorrecta', [200, 'info']);
        }

        //3.- Usuario tenga acceso
        // if ($user->acceso === 0 || is_null($user->acceso) && ($user->email_verified_at === 0 || is_null($user->email_verified_at))) {
        //     return $this->crearRespuesta('Tiene un correo '. $user->email .' por favor confirme su registro.', [200, 'info']);
        // }
        
        //3.- Usuario tenga acceso
        if ($user->acceso === 0 || is_null($user->acceso)) {
            // return $this->crearRespuesta('Su cuenta no tiene permitido el acceso. Comuníquese con el administrador de sistema.', [200, 'info']);
        }


         
        if (is_null($user->idperfil)) {
            // return $this->crearRespuesta('Su cuenta no tiene asociado un perfil de sistema. Comuníquese con el administrador de sistema.', [200, 'info']);
        }
        
        //4.- Autenticacion desde una IP valida    
        if ($user->validacionip === 1 && ($user->tipopersonal === '1' || $user->tipoproveedor === '1' || $user->tipomedico === '1')) { 

            $where = array('idempresa' => $idempresa, 'nombre' => $ip);          
            $ips = $empresa->ips($where);    
            
            // if(empty($ips) && false){ 
            //     return $this->crearRespuesta('Restringido el acceso al sistema desde su ubicación IP.', [200, 'info']);
            // } 
        } 
        
        //5.- Autenticacion segun su horario laboral
        $ok = true;
        $message = 'Restringido el acceso por su horario laboral.'; 
        if ($user->validacionhorario === 1) { 
            $validarHora = date('H:i').':00';
            $param = array(
                'horariomedico.idempresa' => $idempresa,
                'horariomedico.fecha' => date('Y-m-d'),
                'entidad.identidad' => $user->identidad                                        
            ); 

            // dd($between);
            $data = $horariomedico->grid($param, '', '', $validarHora);                                    
            if(empty($data)){
                //No tiene horario
                $ok = false;
                $data = $horariomedico->grid($param); 
                if(!empty($data)){
                    $message .= ' Su horario  es:';
                    foreach($data as $row){ 
                        $hora = $this->formatFecha($row->fecha, 'yyyy-mm-dd').' '.$row->fin; date('Y-m-d H:i:s');
                        $fin = date('H:i:s', strtotime('+1 minute', strtotime($hora)));
                        $message .= ' ('.substr($row->inicio, 0, 5).' - '.substr($fin, 0, 5).')';
                    }
                } else{
                    $message .= ' Hoy no tiene horario.';
                }
            }
        }        
        
        // Por el momento todos deben ingresar
        if (!$ok) { 
            // return $this->crearRespuesta($message, [200, 'info']);
        }             
                        
        //Brindar acceso
        $params = array(
            'entidad.numerodoc' => $request['username'],
            'empresa.idempresa' => $idempresa
        );

        $userProfiles = $entidad->ListaPerfiles($params);
        $userModules = $entidad->ListaModules($params);

        $token = array(
            "iss" => "http://wwww.lagranescuela.com",
            "my" => $user->identidad,
            "myusername" => $request['username'],                                       
            "mysede" => [],
            "mytime" => date('Y-m-d H:i:s'),
            "myadmasistencia" => $user->admasistencia
        );
        //return $this->crearRespuesta($userProfiles, 200);  
        if(!empty($userProfiles)){
            $token["myenterprise"] = $userProfiles[$url]['perfilid'];
            $token["myperfilidparent"] = $userProfiles[$url]['perfilidparent'];
            $token["myperfilid"] = $userProfiles[$url]['perfilid'];
            $token["myperfilnuevo"] = $userProfiles[$url]['perfilnuevo'];
            $token["myperfileditar"] = $userProfiles[$url]['perfileditar'];
            $token["myperfileliminar"] = $userProfiles[$url]['perfileliminar'];
            $token["optinforme"] = $userProfiles[$url]['optinforme'];
        }

        $jwt = JWT::encode($token, $key);

        //Sedes autorizados
        $params = array(
            'sede.idempresa' => $idempresa,
            'entidadsede.identidad' => $user->identidad
        );
        $sedesautorizadas = $sede->autorizadas($params);  

        $stdObjeto = (object) array(
            'identidad' => $user->identidad, 
            'numerodoc' => $user->numerodoc,
            'entidad' => $user->apellidopat . ' ' . $user->apellidomat . ', ' . $user->nombre,
            'nombres' => empty($user->nombre) ? $user->entidad : $user->nombre,
            'apellidos' => $user->apellidopat. ' '.$user->apellidomat,
            'imgperfil' => $user->imgperfil,
            'sexo' => $user->sexo,
            'celular' => $user->celular,
            'tipocliente' => $user->tipocliente,
            'tipopersonal' => $user->tipopersonal,
            'tipomedico' => $user->tipomedico,
            'tipoproveedor' => $user->tipoproveedor,
            'perfilid' => isset($userProfiles[$url]['perfilid'])?$userProfiles[$url]['perfilid']:null,
            'sedesAutorizadas' => $sedesautorizadas
        ); 

        $respuesta = array(
            'entidad' => $stdObjeto, 
            'token' => $jwt, 
            'userModules' => $userModules,
            'userProfiles' => $userProfiles
        ); 
        //El primer usuario logeado del dia, ejecuta el cierre de ciclos.
        $accesosHoy = $entidad->logsAcceso(['fechain' => date('Y-m-d')]);        
        if(empty($accesosHoy)) {
            // return $this->crearRespuesta("B", [200, 'info']);
            $respuesta['cicloscerrados'] = $this->cerrarCiclosdeatencionV2($idempresa);
        } 
        
        //Guardar log de acceso al sistema  
        $data = array(
            'identidad' => $user->identidad,
            'fechain' =>  date('Y-m-d'),
            'horain' =>  date('H:i:s'),                                    
            'token' => $jwt,
            'tokenstatus' => '1',
            'ip' => $ip,
            'navegador' => $navegador                                    
        );
        $entidad->grabarlogAcceso($data); 
        
        return $this->crearRespuesta($respuesta, 200);      
    }

    public function pacienteToken($enterprise, $tokenconfirm, Request $request) {


        $objEntidad = new entidad();

        $request = $request->all();

        $fields = array('email_verified_at', 'identidad', 'numerodoc', 'password', 
                        'apellidopat', 'apellidomat', 'nombre', 
                        'imgperfil', 'sexo', 'email', 'celular', 'iddocumento', 'acceso');

        $user = entidad::select($fields)
                    ->where('idempresa', 1)
                    ->where('verification_token', $tokenconfirm) 
                    ->whereNull('entidad.deleted')
                    ->first();
 
        //1.- Usuario existe
        if (is_null($user)) {    
            $return = array(
                'code' => '1',
                'message' => 'Token invalido'
            );
             
            return $this->crearRespuesta($return, [200, 'info']);
        }

        //2.- Usuario tenga acceso
        if ($user->email_verified_at === '1') {
            $return = array(
                'code' => '2',
                'message' => 'Registro de paciente ya fue verificado.'
            );

            return $this->crearRespuesta($return, [200, 'info']);
        }

        $token = array(
            "iss" => "http://wwww.lagranescuela.com",
            "my" => $user->identidad,
            "myusername" => $user->numerodoc,                                       
            "mysede" => [],
            "mytime" => date('Y-m-d H:i:s'),
            "myadmasistencia" => 0
        ); 

        $key = "x1TLVtPhZxN64JQB3fN8cHSp69999999";        

        $jwt = JWT::encode($token, $key); 

        $stdObjeto = (object) array(
            'identidad' => $user->identidad, 
            'numerodoc' => $user->numerodoc,
            'entidad' => $user->apellidopat . ' ' . $user->apellidomat . ', ' . $user->nombre,
            'nombres' => $user->nombre,
            'apellidos' => $user->apellidopat. ' '.$user->apellidopat,
            'imgperfil' => $user->imgperfil,
            'sexo' => $user->sexo,
            'celular' => $user->celular,
            'tipocliente' => $user->tipocliente
        );      

        $respuesta = array(
            'entidad' => $stdObjeto, 
            'token' => $jwt, 
            'userModules' => [],
            'userProfiles' => []
        );         

        //Guardar log de acceso al sistema  
        $navegador = $request['navegador']; 

        $data = array(
            'identidad' => $user->identidad,
            'fechain' =>  date('Y-m-d'),
            'horain' =>  date('H:i:s'),                                    
            'token' => $jwt,
            'tokenstatus' => '1',
            'ip' => null,
            'navegador' => $navegador                                    
        );

        \DB::beginTransaction();
        try {  

            $user->email_verified_at = '1';
            $user->acceso = 1;

            $user->save();
            $objEntidad->grabarlogAcceso($data); 

            Mail::to($user->email)->send(new BienvenidoSend($user));

        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit(); 

        return $this->crearRespuesta($respuesta, 200);      
    }
    
    public function logout(Request $request) {
        
        $entidad = new entidad();
        
        $tokenRequest = $request->header('AuthorizationToken');        
        $url = $request->get('enterprise'); 
        
        $data = $entidad->logsAcceso(['logacceso.token' => $tokenRequest]);    
        if ($data) {
            $param = array('token' => $tokenRequest, 'fechaout' => date('Y-m-d'), 'horaout' => date('H:i:s'));            
            $entidad->grabarlogAcceso($param);
            $respuesta = ['success' => true];           
        } else {
            $respuesta = ['success' => true, 'message' => 'No tiene token'];
            //$respuesta = ['success' => false];
        }

        return response()->json($respuesta);
    }

    public function definirCmPrincipalEnCiclo($idempresa, $idcicloatencion = '') {

        $cicloatencion = new cicloatencion();  
        $resultado = [];
        $idempresa = 1;

        $param = array(
            'cicloatencion.idempresa' => $idempresa,
            //'cicloatencion.idcicloatencion' => 11083, 
            //'cicloatencion.idestado' => 20 //20:Aperturado 21:Cerrado 22:Cancelado
        ); 
 
        $campos = array('cicloatencion.idcicloatencion', 'cicloatencion.fecha');
        //['2018-01-01', '2018-02-28']
        $dataciclo = $cicloatencion->grid($param, '', ['2017-01-01', '2017-04-31'], '', '', '', false, $campos);

        //dd($dataciclo);

        foreach ($dataciclo as $row) {

            $cita = \DB::table('citamedica') 
                ->select(['citamedica.idcitamedica', 'citamedica.tipocm', 'citamedica.idestado']) 
                ->join('cicloatencion', 'cicloatencion.idcicloatencion', '=', 'citamedica.idcicloatencion')             
                ->whereNull('citamedica.deleted')
                //->whereNull('cicloatencion.idcitamedica')
                ->where('citamedica.idcicloatencion', $row->idcicloatencion)
                ->whereIn('citamedica.idestado', [4, 5, 6, 48, 7]) //4:pendiente, 5:confirmada, 6:atendida, 7:cancelada, 48:noasistió
                ->orderBy('citamedica.fecha', 'asc') 
                ->get()->all();

            if ($cita) {
                $citaAtendida = null;
                foreach ($cita as $fila) {                
                    if($fila->idestado === 6) {
                        $citaAtendida = $fila;
                        break;
                    }
                }

                if($citaAtendida) {         
                    $idcitamedica = $citaAtendida->idcitamedica;                
                }else {  
                    $idcitamedica = $cita[0]->idcitamedica;                
                }

                // setea interconsulta a consulta
                \DB::table('citamedica')
                    ->where('idcicloatencion', $row->idcicloatencion)
                    ->whereNull('citamedica.deleted')
                    ->update(['tipocm' => 2]); 

                // setea consulta principal solo a una consulta
                \DB::table('citamedica')
                    ->where('idcitamedica', $idcitamedica) 
                    ->update(['tipocm' => 1]); 

                $resultado[] = $idcitamedica;
            }
        } 

        return array($resultado);
    }

    public function setearCitaACiclo($idempresa, $idcicloatencion = '') {

        $cicloatencion = new cicloatencion(); 
        $maxDiasTerapia = 21; //al 22vo dia en que ya no viene a terapia se cerra el ciclo de atencion del paciente
        $maxDiasOpenCiclo = 21; 
        $resultado = [];
        $idempresa = 1;

        $param = array(
            'cicloatencion.idempresa' => $idempresa,
            // 'cicloatencion.idcicloatencion' => 11083 
            //'cicloatencion.idestado' => 20 //20:Aperturado 21:Cerrado 22:Cancelado
        ); 
 
        $campos = array('cicloatencion.idcicloatencion', 'cicloatencion.fecha');
        //['2018-01-01', '2018-02-28']
        $dataciclo = $cicloatencion->grid($param, '', ['2016-01-01', '2016-12-31'], '', '', '', false, $campos);

        //dd($dataciclo);

        foreach ($dataciclo as $row) {

            $cita = \DB::table('citamedica') 
                ->select(['citamedica.idcitamedica', 'citamedica.tipocm']) 
                ->join('cicloatencion', 'cicloatencion.idcicloatencion', '=', 'citamedica.idcicloatencion')             
                ->whereNull('citamedica.deleted')
                //->whereNull('cicloatencion.idcitamedica')
                ->where('citamedica.idcicloatencion', $row->idcicloatencion)
                ->whereIn('citamedica.idestado', [4, 5, 6, 48])
                ->orderBy('citamedica.fecha', 'asc') 
                ->get()->all();

            $cant1 = 0;
            $temp = null;
            foreach ($cita as $fila) {
                
                if($fila->tipocm === 1) {
                    $cant1++;
                    $temp = $fila;
                }
            }
            
            if($cant1 > 1) {
                dd('ciclo mas de uno: ' . $row->idcicloatencion);
            }

            if($cant1 === 0 && $cita) {
                dd('ciclo puro tipo 2: ' . $row->idcicloatencion);
            }

            if ($cita && $cant1 === 1) {
                \DB::table('cicloatencion')
                    ->where('idcicloatencion', $row->idcicloatencion)
                    ->update(['idcitamedica' => $temp->idcitamedica]); 

                $resultado[] = $row->idcicloatencion;
            }
        } 

        return array($resultado);
    }

    public function cerrarCiclosdeatencionV2($idempresa, $idcicloatencion = '') {

        $cicloatencion = new cicloatencion(); 
        $maxDiasTerapia = 21; //al 22vo dia en que ya no viene a terapia se cerra el ciclo de atencion del paciente
        $maxDiasOpenCiclo = 21; 
        $resultado = [];

        $param = array(
            'cicloatencion.idempresa' => $idempresa,
            'cicloatencion.idestado' => 20 //20:Aperturado 21:Cerrado 22:Cancelado
        );

        if(!empty($idcicloatencion))
            $param['cicloatencion.idcicloatencion'] = $idcicloatencion; 

        // Todos los ciclos abiertos.
        // Ciclo (cantcliente es igual cantefectivo) entonces SE CIERRA. 
        // Ciclo con más de 21 días desde ultima terapia. SE CIERRA.
        // Ciclo con más de 21 días que no inicia terapias. SE CIERRA.
        $campos = array('cicloatencion.idcicloatencion', 'cicloatencion.fecha', 'cicloatencion.terminot', 'cicloatencion.ultimot');
        $dataciclo = $cicloatencion->grid($param, '', [], '', '', '', false, $campos);
 

        foreach($dataciclo as $row){ 
            $fechaIF = $this->fechaInicioFin($row->fecha, '00:00:00', '00:00:00');
            $fecha_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $hoy_s = mktime(0, 0, 0, (int) date('m'), (int) date('d'), (int) date('Y'));              
            $row->fecha_dias_tr = ($hoy_s - $fecha_s)/86400; //1 dia = 86400s 

            $row->ultimot_dias_tr = NULL;
            if($row->ultimot) {
                $fechaIF = $this->fechaInicioFin($row->ultimot, '00:00:00', '00:00:00');
                $fecha_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
                $hoy_s = mktime(0, 0, 0, (int) date('m'), (int) date('d'), (int) date('Y'));              
                $row->ultimot_dias_tr = ($hoy_s - $fecha_s)/86400; //1 dia = 86400s 
            }

            if ($row->terminot === '1' || 
                ($row->ultimot_dias_tr && $row->ultimot_dias_tr > $maxDiasTerapia) || 
                (!$row->ultimot_dias_tr && $row->fecha_dias_tr > $maxDiasOpenCiclo )) { 
                $resultado[] = $row->idcicloatencion;
            }
        } 

        // foreach($resultado as $val){
        //     \DB::table('cicloatencion')->where(['idcicloatencion' => $val])->update(['idestado' => 21, 'fechacierre' => date('Y-m-d')]); //20:Aperturado 21:Cerrado 22:Cancelado

        // }
        
        if($resultado) {
            \DB::table('cicloatencion')->whereIn('idcicloatencion', $resultado)
                ->update(['idestado' => 21, 'fechacierre' => date('Y-m-d')]); //20:Aperturado 21:Cerrado 22:Cancelado
        }

        return array($resultado);
    }

    public function cerrarCiclosdeatencion() {
        $idempresa = 1; $hT = true; $idcicloatencion = '';

        $maxDias = 21; //al 22vo dia en que ya no viene a terapia se cerra el ciclo de atencion del paciente
        $objPresupuesto = new presupuesto();
        $terapia = new terapia();
                        
        $param = array(
            'presupuesto.idempresa' => $idempresa,
            'cicloatencion.idestado' => 20 //20:Aperturado 21:Cerrado 22:Cancelado
        );
        

        $betweendate = [];
        if(!empty($idcicloatencion)) {
            $param['cicloatencion.idcicloatencion'] = $idcicloatencion;        
        } else {
            $fechaMaxima = strtotime('-30 day', strtotime(date('Y-m-d')));
            
            $betweendate = array(date('Y-m-d', $fechaMaxima), date('Y-m-d'));
        }

        $whereIdcicloatencionIn = [];
        $presupuestos = $objPresupuesto->grid($param, $betweendate);
        dd(count($presupuestos));
        foreach ($presupuestos as $value) {
            $whereIdcicloatencionIn[] = $value->idcicloatencion;
        }

        // dd($whereIdcicloatencionIn);
        $presupuestosdetalles = $objPresupuesto->presupuestodetalle($param, $whereIdcicloatencionIn);        
        
        

        $resultado = [];                
        $terapiasTerminadas = [];
        $terapiasPorpagarse = [];         
        
        foreach($presupuestos as $row){
            $row->terapiaterminada = false;
            foreach($presupuestosdetalles as $row2){
                if($row2->idpresupuesto === $row->idpresupuesto){
                    if($row2->cantcliente === $row2->cantefectivo){
                        $row->terapiaterminada = true;
                    }else{
                        $row->terapiaterminada = false;
                        break;
                        //Efectuado todas las indicadas por el cliente
                    }
                }
            }
            
            if($row->terapiaterminada)
                $terapiasTerminadas[] = $row->idcicloatencion;            
            
            $creditodisp = $row->montopago - $row->montoefectuado; 
            
            if($creditodisp < 0)
                $terapiasPorpagarse[] = $row->idcicloatencion;            
        } 
        
        /*Historial terapeutico*/
        if($hT){
            $terapiasTiempomax = [];
            $param2 = array(
                'terapia.idempresa' => $idempresa,
                'cicloatencion.idestado' => 20, //20:Aperturado 21:Cerrado 22:Cancelado
                'terapia.idestado' => 38 //36: Espera  37: Sala 38: Atendido  39: Cancelada
            );
                                            
            $historialterapeutico = $terapia->terapiatratamientos($param2, ['terapia.fecha', 'cicloatencion.idcicloatencion'], true, true, '', '', '', [], '', $whereIdcicloatencionIn);

            
            foreach($historialterapeutico as $row){ 
                $fechaIF = $this->fechaInicioFin($row->fecha, '00:00:00', '00:00:00');
                $row->fecha_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
                $hoy_s = mktime(0, 0, 0, (int) date('m'), (int) date('d'), (int) date('Y')); 
                $segundos = $hoy_s - $row->fecha_s;  
                $row->dias_tr = $segundos/86400; //1 dia = 86400s 
            }


            $historialterapeutico = $this->ordenarMultidimension($historialterapeutico, 'idcicloatencion', SORT_DESC, 'fecha_s', SORT_DESC);
            dd($historialterapeutico);

            $idtmp = null;
            foreach($historialterapeutico as $row){
                if($idtmp !== $row->idcicloatencion){ //Tomara el primer valor
                    if($row->dias_tr > $maxDias)
                        $terapiasTiempomax[] = $row->idcicloatencion;                    
                    $idtmp = $row->idcicloatencion; 
                }
            }
            
            foreach($terapiasTiempomax as $val){
                if(!in_array($val, $resultado) && !in_array($val, $terapiasPorpagarse))
                    $resultado[] = $val;
            }
        }
        /*Fin Historial terapeutico*/        
        
        foreach($terapiasTerminadas as $val){
            if(!in_array($val, $resultado) && !in_array($val, $terapiasPorpagarse))
                $resultado[] = $val;
        }
        
        foreach($resultado as $val){
            \DB::table('cicloatencion')->where(['idcicloatencion' => $val])->update(['idestado' => 21, 'fechacierre' => date('Y-m-d')]); //20:Aperturado 21:Cerrado 22:Cancelado
        }
        
        return array($resultado, $terapiasPorpagarse);
    } 

    public function pacientehistorias(Request $request, $enterprise) {
        $empresa = new empresa();
        $entidad = new entidad();
        $sede = new sede();
        
        

        $param = array();
        $paramsTMP = $request->all();
        $idempresa = $empresa->idempresa($enterprise);
        $param['entidad.idempresa'] = $idempresa;         
        
        if (isset($paramsTMP['tipoentidad'])) {
            switch ($paramsTMP['tipoentidad']) {
                case 'cliente':
                    $param['entidad.tipocliente'] = '1';
                    break;
                case 'personal':
                    $param['entidad.tipopersonal'] = '1';
                    break;
                case 'medico':
                    $param['entidad.tipomedico'] = '1';
                    break;
                case 'proveedor':
                    $param['entidad.tipoproveedor'] = '1';
                    break;
                default:
                    break;
            }
        }
        
        $sedes = $sede->sedes($idempresa, ['sede.idsede', 'sede.sedeabrev']);
        // dd($sedes);
        $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd') . ' 00:00:00';
        $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd') . ' 23:59:59';
        $between = [$paramsTMP['desde'], $paramsTMP['hasta']];
        $tmp = $entidad->pacientehistorias($param, $between); 
        $tmpData = $entidad->pacientehistoriasLight($param, $between); 
        

        $idTemp = null;
        $dataTemp = [];
        foreach ($tmpData as $row) {
            if ($idTemp !== $row->identidad) {
                $idTemp = $row->identidad;
                $dataTemp['ID'.$row->identidad] = $row->nombre;
            }
        } 

        $data = array();
        $idclientes = array();  

        foreach($tmp as $row){
            //$tmp[$row->identidad]['identidad'] = $row->identidad;
            $data[$row->identidad]['DOC'] = $row->documentoabrev;
            $data[$row->identidad]['NUMERO'] = $row->numerodoc;
            $data[$row->identidad]['PACIENTE'] = $row->paciente; 

            if(!in_array($row->identidad, $idclientes)){
                foreach($sedes as $sede){
                    $data[$row->identidad]['HC '.$sede->sedeabrev] = '';
                } 

                $data[$row->identidad]['PRIMERA HC'] = '';
                $idclientes[] = $row->identidad;
            }
            
            if($row->hc) {
                $data[$row->identidad]['HC '.$row->sedeabrev] = $row->hc;
                $data[$row->identidad]['PRIMERA HC'] = isset($dataTemp['ID'.$row->identidad]) ? $dataTemp['ID'.$row->identidad] : '';
            }
                        
            $data[$row->identidad]['TELEFONO'] = $row->telefono;
            $data[$row->identidad]['CELULAR'] = $row->celular;
            $data[$row->identidad]['CORREO'] = $row->email;
            $data[$row->identidad]['SEXO'] = $row->sexo;
            $data[$row->identidad]['DEPARTAMENTO'] = $row->departamento;
            $data[$row->identidad]['PROVINCIA'] = $row->provincia;
            $data[$row->identidad]['DISTRITO'] = $row->distrito;
            $data[$row->identidad]['DIRECCION'] = $row->direccion;
            $data[$row->identidad]['DIRECCION'] = $row->direccion;
            $data[$row->identidad]['FECHA_NACIMIENTO'] = $row->fechanacimiento;
            $data[$row->identidad]['ACCESO_SISTEMA'] = $row->acceso;
            $data[$row->identidad]['FECHA_REGISTRO'] = $row->createdat;            
            $data[$row->identidad]['REGISTRO'] = $row->created; 
        }

        $temp = [];
        foreach($data as $row){
            $temp[] = $row;
        }

        return Excel::download(new DataExport($temp), 'Reporte_' . date('Y-m-d H:i:s') . '.xlsx');
    }

    public function unirduplicados(Request $request, $enterprise) {

        $empresa = new empresa();
        $entidad = new entidad();
        $sede = new sede();
        
        $param = array();

        $request = $request->all();
        $idempresa = $empresa->idempresa($enterprise);

        $iddelete = $request['eliminar'];
        $idconserva = $request['conservar'];

        // Validaciones
        $entidadEliminar = entidad::where('identidad', $iddelete)->whereNull('deleted')->first();
        if (!$entidadEliminar) {
            return $this->crearRespuesta('Persona a eliminar no existe', [200, 'info']);
        }

        $entidadConservar = entidad::where('identidad', $idconserva)->whereNull('deleted')->first();
        if (!$entidadConservar) {
            return $this->crearRespuesta('Persona a conservar no existe', [200, 'info']);
        }

        // return $this->crearRespuesta('XD', [200, 'info'], '', '', $request);
        \DB::beginTransaction();
        try {      
            //Crear registro logfusion
            $param = array(
                'idempresa' => $idempresa,
                'identidadeliminado' => $request['eliminar'],
                'identidadconservado' => $request['conservar'],
                'id_created_at' => $this->objTtoken->my,
                'created_at' => date('Y-m-d H:i:s')
            );
            $logfusion = logfusion::create($param);  

            //apertura: idapertura 
            $actualizaciones = array(
                ['tabla' => 'apertura', 'pk' => 'idapertura', 'where' => 'identidadapertura', 'deleted' => true],
                ['tabla' => 'apertura', 'pk' => 'idapertura', 'where' => 'identidadcierre', 'deleted' => true],
                ['tabla' => 'apertura', 'pk' => 'idapertura', 'where' => 'id_created_at', 'deleted' => true],
                ['tabla' => 'apertura', 'pk' => 'idapertura', 'where' => 'id_updated_at', 'deleted' => true],
                //Se repetira.
                ['tabla' => 'aseguradoraplan', 'pk' => 'idaseguradoraplan', 'where' => 'idcliente', 'deleted' => false],
                ['tabla' => 'asistencia', 'pk' => 'idasistencia', 'where' => 'identidad', 'deleted' => true],
                ['tabla' => 'asistencia', 'pk' => 'idasistencia', 'where' => 'id_created_at', 'deleted' => true],
                ['tabla' => 'asistencia', 'pk' => 'idasistencia', 'where' => 'id_updated_at', 'deleted' => true],
                ['tabla' => 'automatizacion', 'pk' => 'idautomatizacion', 'where' => 'id_created_at', 'deleted' => true],
                ['tabla' => 'automatizacion', 'pk' => 'idautomatizacion', 'where' => 'id_updated_at', 'deleted' => true],
                ['tabla' => 'autorizacionterapia', 'pk' => 'idautorizacionterapia', 'where' => 'idcliente', 'deleted' => true],
                ['tabla' => 'autorizacionterapia', 'pk' => 'idautorizacionterapia', 'where' => 'idpersonal', 'deleted' => true],
                ['tabla' => 'autorizacionterapia', 'pk' => 'idautorizacionterapia', 'where' => 'id_created_at', 'deleted' => true],
                ['tabla' => 'autorizacionterapia', 'pk' => 'idautorizacionterapia', 'where' => 'id_updated_at', 'deleted' => true],
                //Se repetira.
                ['tabla' => 'cajero', 'pk' => 'idcajero', 'where' => 'identidad', 'deleted' => false],
                ['tabla' => 'camilla', 'pk' => 'idcamilla', 'where' => 'id_created_at', 'deleted' => true],
                ['tabla' => 'camilla', 'pk' => 'idcamilla', 'where' => 'id_updated_at', 'deleted' => true]
            );

            foreach($actualizaciones as $row) {
                $select = DB::table($row['tabla'])
                        ->select($row['pk']);

                if ($row['deleted']) {
                    $select->whereNull('deleted');
                }

                $data = $select->where($row['where'], $iddelete)
                            ->get()->all();

                if ($data) {
                    $valores = [];
                    foreach($data as $fila) {
                        $valores[] = $fila->$row['pk'];
                    }

                    $insert = array(
                        'idlogfusion' => $logfusion->idlogfusion,
                        'tabla' => $row['tabla'],
                        'campo' => $row['where'],
                        'valores' => implode(",", $valores),
                        'cantidad' => count($valores),
                        'id_created_at' => $this->objTtoken->my,
                        'created_at' => date('Y-m-d H:i:s')
                    );

                    DB::table('logfusiondet')->insert($insert);

                    DB::table($row['tabla'])
                        ->whereNull('deleted')
                        ->where($row['where'], $iddelete)
                        ->update([$row['where'] => $idconserva]);                            
                }
            } 

            //Eliminar registro logfusion
            $entidadEliminar->fill(array(
                'deleted_at' => date('Y-m-d H:i:s'),
                'id_deleted_at' => $this->objTtoken->my,
                'deleted' => '1'
            ));

            $entidadEliminar->save();

            // Actualizar registro logfusion
            // $logfusion->fill(array( 
            //     'id_updated_at' => $this->objTtoken->my,
            //     'updated_at' => date('Y-m-d H:i:s')
            // )); 
            // $logfusion->save();   
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit(); 
 
        return $this->crearRespuesta('Personas se han unido.', 201, '','', $request);
    }

}
