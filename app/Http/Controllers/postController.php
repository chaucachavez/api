<?php

namespace App\Http\Controllers;

use Excel;
use App\Models\post;
use App\Models\sede;
use \Firebase\JWT\JWT;
use App\Models\empresa;
use App\Models\entidad;
use App\Models\citamedica;
use App\Exports\DataExport;
use Illuminate\Http\Request;
use App\Models\horariomedico;
use App\Models\citaterapeutica; 

class postController extends Controller {
     
    public function __construct(Request $request) { 
        $this->getToken($request);
    }

    public function construct(Request $request, $enterprise) { 

        $empresa = new empresa();
        $sede = new sede();
        $entidad = new entidad();

        $idempresa = $empresa->idempresa($enterprise);

        $param = array(
            'sede.idempresa' => $idempresa,
            'entidadsede.identidad' => $this->objTtoken->my
        );

        $param2 = array(
            'entidad.idempresa' => $idempresa,
            'entidad.tipopersonal' => '1'
        );

        $data = array(
            'actividades' => $empresa->estadodocumentos(15),
            'llamadas' => $empresa->estadodocumentos(16),
            'categorias' => $empresa->estadodocumentos(17),
            'sedes' => $sede->autorizadas($param, $this->objTtoken->mysede),
            'personal' => $entidad->entidades($param2)
        );

        return $this->crearRespuesta($data, 200);
    }
    
    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $post = new post(); 

        $idempresa = $empresa->idempresa($enterprise);

        $param = [];
        $param['post.idempresa'] = $idempresa;          

        if (isset($paramsTMP['identidad']) && !empty($paramsTMP['identidad'])) {
            $param['post.identidad'] = $paramsTMP['identidad'];
        }

        if (isset($paramsTMP['idcategoria']) && !empty($paramsTMP['idcategoria'])) {
            $param['post.idcategoria'] = $paramsTMP['idcategoria'];
        } 

        if (isset($paramsTMP['idcicloatencion']) && !empty($paramsTMP['idcicloatencion'])) {
            $param['post.idcicloatencion'] = $paramsTMP['idcicloatencion'];
        } 

        if (isset($paramsTMP['idcitamedica']) && !empty($paramsTMP['idcitamedica'])) {
            $param['post.idcitamedica'] = $paramsTMP['idcitamedica'];
        } 

        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'post.fecha';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'DESC';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        } 

        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : ''; 
        
        $datapost = $post->grid($param,  $like, $pageSize, $orderName, $orderSort);
        // dd($datapost);           

        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $datapost->total();
            $datapost = $datapost->items();
        }
        
        if (isset($paramsTMP['formato']) && !empty($paramsTMP['formato'])) {
            if(in_array($paramsTMP['formato'], ['xls', 'xlsx'])){  
               
                $data = array(); 
                $i = 0;
                foreach($datapost as $row){  
                    if($param['post.idcategoria'] === '62'){
                        $data[$i] = array( 
                            'TIPO' => $row->nombrecategoria,
                            'FECHA LLAMADA' => $row->fecha, 
                            'HORA LLAMADA' => $row->hora,                         
                            'CLIENTE' => $row->entidad,   
                            'IDCITA' => $row->idcitamedica, 
                            'FECHA CITA' => $row->fechacita, 
                            'LLAMADA ¿CONTESTÓ?' => $row->nombrellamada,  
                            'RESPUESTA' => $row->nombreactividad,  
                            'MOTIVO' => $row->nombreitem,  
                            'REGISTRÓ' => $row->created,
                            'COMENTARIO' => $row->mensaje                        
                        );                                        
                    }

                    if($param['post.idcategoria'] === '63'){
                        $data[$i] = array( 
                            'TIPO' => $row->nombrecategoria,
                            'FECHA LLAMADA' => $row->fecha, 
                            'HORA LLAMADA' => $row->hora,                         
                            'CLIENTE' => $row->entidad,   
                            'CICLO' => $row->idcicloatencion, 
                            'FECHA CICLO' => $row->fechaciclo, 
                            'LLAMADA ¿CONTESTÓ?' => $row->nombrellamada,  
                            'RESPUESTA' => $row->nombreactividad,  
                            'MOTIVO' => $row->nombreitem,  
                            'REGISTRÓ' => $row->created,
                            'COMENTARIO' => $row->mensaje                        
                        );                                         
                    }
                     
                    $i++;
                }  
                
                return Excel::download(new DataExport($data), 'Reporte_' . date('Y-m-d H:i:s') . '.xlsx');
            }
        } else {
            return $this->crearRespuesta($datapost, 200, $total);
        } 

    }

    public function newpost(Request $request, $enterprise) { 

        $empresa = new empresa();  

        $idempresa = $empresa->idempresa($enterprise);
 
        $data = array(
            'actividades' => $empresa->estadodocumentos(15),
            'llamadas' => $empresa->estadodocumentos(16) 
        );

        return $this->crearRespuesta($data, 200);
    }

    public function store(Request $request, $enterprise) {

        $objEntidad = new entidad();
        $empresa = new empresa();

        $idempresa = $empresa->idempresa($enterprise); 
        $request = $request->all(); 

        $request['post']['idempresa'] = $idempresa; 

        if(isset($request['post']['idcitamedica'])){
            $citamedica = citamedica::find($request['post']['idcitamedica']); 
        }

        if(isset($request['citamedica'])){

            $horariomedico = new horariomedico();  

            $param2 = [];
            $param2['horariomedico.idempresa'] = $idempresa;
            $param2['horariomedico.idsede'] = $citamedica->idsede;
            $param2['horariomedico.idmedico'] = $request['citamedica']['idmedico'];
            $param2['horariomedico.fecha'] = $this->formatFecha($request['citamedica']['fecha'], 'yyyy-mm-dd');
            $datahorario = $horariomedico->grid($param2);

            foreach ($datahorario as $row) {
                $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
                $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
                $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']); 
            }

            $fechaIF = $this->fechaInicioFin($request['citamedica']['fecha'], $request['citamedica']['inicio'], $request['citamedica']['fin']);
            $start = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $end = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            $validation = ['inValid' => true, 'message' => 'Cita est&aacute; fuera del horario del m&eacute;dico'];
            foreach ($datahorario as $row) {
                if ($row->start_s <= $start && $row->end_s >= $end) {
                    //Cita esta dentro del horario del medico
                    $validation['inValid'] = false;
                    $validation['message'] = '';
                    break;
                }
            }
            if ($validation['inValid']) {
                return $this->crearRespuesta($validation['message'], [200, 'info']); 
            }

            $request['citamedica']['idestado'] = 4; //Pendiente
            $request['citamedica']['fechaanterior'] = $citamedica->fecha;
            $request['citamedica']['fecha'] = $this->formatFecha($request['citamedica']['fecha'], 'yyyy-mm-dd');
            /* Campos auditores */
            $request['citamedica']['updated_at'] = date('Y-m-d H:i:s');
            $request['citamedica']['id_updated_at'] = $this->objTtoken->my;
            /* Campos auditores */
        }
 
        if (isset($request['post']['fecha']) && !empty($request['post']['fecha'])) {
            $request['post']['fecha'] = $this->formatFecha($request['post']['fecha'], 'yyyy-mm-dd');
        }else{
            $request['post']['fecha'] =  date('Y-m-d');
        }  

        if (!isset($request['post']['hora']) || empty($request['post']['hora'])) { 
            $request['post']['hora'] =  date('H:i:s');
        } 

        if (isset($request['post']['fecharecordatorio']) && !empty($request['post']['fecharecordatorio'])) {
            $request['post']['fecharecordatorio'] = $this->formatFecha($request['post']['fecharecordatorio'], 'yyyy-mm-dd');
        }  

        /* Campos auditores */
        $request['post']['realizado'] = '0';
        $request['post']['created_at'] = date('Y-m-d H:i:s');
        $request['post']['id_created_at'] = $this->objTtoken->my;
        /* Campos auditores */
         
        \DB::beginTransaction();
        try {
            
            $post = post::create($request['post']);
             
            if(isset($request['post']['idcicloatencion']) && !empty($request['post']['idcicloatencion'])){                 
                $p = array(
                    'post.idempresa' => $idempresa,
                    'post.idcicloatencion' => $request['post']['idcicloatencion']
                );                 
                $post->ultimallamadaefectiva($p, $request['post']['idcicloatencion'], 'cicloatencion');
                $post->cantidadllamadaefectiva($p, $request['post']['idcicloatencion'], 'cicloatencion');
            }

            if(isset($request['post']['idcitamedica']) && !empty($request['post']['idcitamedica'])){                
                $p = array(
                    'post.idempresa' => $idempresa,
                    'post.idcitamedica' => $request['post']['idcitamedica'],
                );                 
                $post->ultimallamadaefectiva($p, $request['post']['idcitamedica'], 'citamedica');
                $post->cantidadllamadaefectiva($p, $request['post']['idcitamedica'], 'citamedica');
            }

            if(isset($request['citamedica'])){ 
                $request['citamedica']['idpost'] = $post->idpost;                
                $citamedica->fill($request['citamedica']);
                $citamedica->save();
            }
 
            if(isset($request['post']['idcitamedica']) && $citamedica->idestado === 4 && $request['post']['idactividad'] === 56){
                $request['citamedica']['idpost'] = $post->idpost;                
                $citamedica->fill(array('idestado' => 5));
                $citamedica->save(); 
            }

            if(isset($request['citaterapeutica'])){
                $request['citaterapeutica']['idempresa'] = $idempresa;
                $request['citaterapeutica']['fecha'] = $this->formatFecha($request['citaterapeutica']['fecha'], 'yyyy-mm-dd');
                /* Campos auditores */
                $request['citaterapeutica']['created_at'] = date('Y-m-d H:i:s');
                $request['citaterapeutica']['id_created_at'] = $this->objTtoken->my;
                /* Campos auditores */
                $citaterapeutica = citaterapeutica::create($request['citaterapeutica']);
            }
            

        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta('Post ha sido creado.', 201);
    }

    public function destroy($enterprise, $id) {

        $post = post::find($id); 
        $empresa = new empresa();

        $idempresa = $empresa->idempresa($enterprise);

        if ($post) {
            \DB::beginTransaction();
            try {             
                $auditoria = ['deleted' => '1', 'deleted_at' => date('Y-m-d H:i:s'), 'id_deleted_at' => $this->objTtoken->my];
                $post->fill($auditoria);
                $post->save();
                
                if($post->idcitamedica){
                    $p = array(
                        'post.idempresa' => $idempresa,
                        'post.idcitamedica' => $post->idcitamedica
                    );
                    $post->ultimallamadaefectiva($p, $post->idcitamedica, 'citamedica');
                    $post->cantidadllamadaefectiva($p, $post->idcitamedica, 'citamedica');
                }

                if($post->idcicloatencion){
                    $p = array(
                        'post.idempresa' => $idempresa,
                        'post.idcicloatencion' => $post->idcicloatencion
                    );
                    $post->ultimallamadaefectiva($p, $post->idcicloatencion, 'cicloatencion');
                    $post->cantidadllamadaefectiva($p, $post->idcicloatencion, 'cicloatencion');
                }
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Post a sido eliminado.', 200);
        }
        return $this->crearRespuestaError('Post no encotrado', 404);
    }

    public function update(Request $request, $enterprise, $id) {

        $post = post::find($id);        
         
        $request = $request->all(); 
                 

        if ($post) {
             
            if (isset($request['post']['fecha']) && !empty($request['post']['fecha'])) {
                $request['post']['fecha'] = $this->formatFecha($request['post']['fecha'], 'yyyy-mm-dd');
            }  

            if (isset($request['post']['fecharecordatorio']) && !empty($request['post']['fecharecordatorio'])) {
                $request['post']['fecharecordatorio'] = $this->formatFecha($request['post']['fecharecordatorio'], 'yyyy-mm-dd');
            }  

            /* Campos auditores */
            //$request['post']['realizado'] = '0';
            $request['post']['updated_at'] = date('Y-m-d H:i:s');
            $request['post']['id_updated_at'] = $this->objTtoken->my;
            /* Campos auditores */  

            \DB::beginTransaction();
            try {
                
                $post->fill($request['post']); 
                $post->save();  

            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('El post ha sido editado. ', 200, '', '', $request);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un post', 404);
    }
    
}
