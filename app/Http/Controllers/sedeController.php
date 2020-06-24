<?php

namespace App\Http\Controllers;

use App\Models\sede;
use App\Models\empresa;
use App\Models\entidad;
use App\Models\tarifario;
use Illuminate\Http\Request; 

class sedeController extends Controller {
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    public function index(Request $request, $enterprise) {

        $empresa = new empresa();
        $sede = new sede();

        $request = $request->all(); 

        $idempresa = $empresa->idempresa($enterprise);

        $data = $sede->sedes($idempresa);

        if ($data) {

            if (isset($request['preciocm']) && $request['preciocm']  === '1') {
                $idproducto = 1;
                $tarifario = tarifario::select('idsede', 'partref')->where(['idproducto' => $idproducto])->get()->all();

                foreach ($data as $value) {
                    $value->preciocm = 0;
                    foreach ($tarifario as $tarifa) {
                        if ($tarifa->idsede === $value->idsede) {
                            $value->preciocm = $tarifa->partref;
                            break;
                        }
                    }
                }
            }

            return $this->crearRespuesta($data, 200);
        }

        return $this->crearRespuestaError('Sede no encontrada', 404);
    }
    
    public function show($enterprise, $id) {

        $empresa = new empresa();

        $sede = sede::find($id);
        
        $idempresa = $empresa->idempresa($enterprise);

        if ($sede) {

            //Retraso VPS de 10s
            $hora = date('H:i:s', strtotime('-10 second', strtotime(date('Y-m-d H:i:s'))));
            $fecha = date('d/m/Y', strtotime('-10 second', strtotime(date('Y-m-d H:i:s'))));

            $listcombox = array( 
                'personal' => entidad::select('identidad', 'entidad')->where(['tipopersonal' => '1', 'idempresa' => $idempresa])->whereNull('entidad.deleted')->get()->all(),
                'horasi' => $empresa->horas('00:00:00', '23:45:00', 15, 0),
                'horasf' => $empresa->horas('00:14:00', '23:59:00', 15, 14),                
                'sedehorario' => $empresa->sedehorarios($id),                
                'camillas' =>  $empresa->camillas(['idsede' => $id]),
                'ips' =>  $empresa->ips(['idsede' => $id]),
                'horaactual' => $hora,
                'fechaactual' => $fecha
            ); 

            return $this->crearRespuesta($sede, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Sede no encotrado', 404);
    }
    
   public function autorizadas(Request $request, $enterprise) {

       $empresa = new empresa();
       $sede = new sede(); 
       
       $idempresa = $empresa->idempresa($enterprise);
       $param = array(
           'sede.idempresa' => $idempresa,
           'entidadsede.identidad' => $this->objTtoken->my
       );
       $data = $sede->autorizadas($param);

       return $this->crearRespuesta($data, 200);
   }

    public function store(Request $request, $enterprise) {

        $empresa = new empresa();
        $sede = new sede();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();
        $request['idempresa'] = $idempresa;

        \DB::beginTransaction();
        try {
            if ($request['principal'] === '1') {
                $sede->updateSede(['principal' => '0'], ['idempresa' => $idempresa]);
            }
            $sede = sede::create($request);
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta('La sede "' . $sede->nombre . '" ha sido creado.', 201);
    }

    public function update(Request $request, $enterprise, $id) {
        
        $empresa = new empresa();

        $idempresa = $empresa->idempresa($enterprise);        
        $sedehorario = $empresa->sedehorarios($id);
        $sede = sede::find($id);

        $request = $request->all();      
        
        $dataturnos = [];   

        if(isset($sedehorario) && $sedehorario->intervaloterapia) {
            $tiempo =  $this->horaaSegundos($sedehorario->intervaloterapia); 
            for ($i = 1; $i <= 7; $i++) {  //1:Lunes ... 7:Domingo
                switch ($i) {
                    case 1: $diaseminicio = 'luinicio'; $diasemfin = 'lufin';  break;
                    case 2: $diaseminicio = 'mainicio'; $diasemfin = 'mafin';  break;
                    case 3: $diaseminicio = 'miinicio'; $diasemfin = 'mifin';  break;
                    case 4: $diaseminicio = 'juinicio'; $diasemfin = 'jufin';  break;
                    case 5: $diaseminicio = 'viinicio'; $diasemfin = 'vifin';  break;
                    case 6: $diaseminicio = 'sainicio'; $diasemfin = 'safin';  break;
                    case 7: $diaseminicio = 'doinicio'; $diasemfin = 'dofin';  break;
                }

                if(!empty($request['sedehorario'][$diaseminicio]) && !empty($request['sedehorario'][$diasemfin])) {
                    $fechaIF = $this->fechaInicioFin(date('d/m/Y'), $request['sedehorario'][$diaseminicio], $request['sedehorario'][$diasemfin]);
                    $start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
                    $end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']); 

                    // if($sedehorario->$diaseminicio !== $request['sedehorario'][$diaseminicio] || $sedehorario->$diasemfin !== $request['sedehorario'][$diasemfin]){
                        while (($start_s + $tiempo) <= $end_s) {
                            $dataturnos[] = array(
                                'idempresa' => $idempresa,
                                'idsede' => $id,
                                'dia' => $i,
                                'inicio' => date('H:i:s', $start_s),
                                'fin' => date('H:i:s', $start_s + $tiempo) //44 minutos  
                            );
                            $start_s = $start_s + ($tiempo + 60); // 14min. + 1min. = 15 min.                                    
                        }
                    // } 
                }                       
            }
        }
        

        //return $this->crearRespuesta('OK', [200, 'info'], '', '', $dataturnos);
         
        $dataIps = [];
        foreach ($request['ip'] as $row) {
            $dataIps[] = ['idsede' => $id, 'idempresa' => $sede->idempresa, 'nombre' => $row['nombre']];
        }
        
        /* Obtener array de 'Insert', 'Update', 'Deleted' con campos auditores para tabla 'cicloautorizacion'.
         */
        $camillaInsert = [];
        $camillaUpdate = [];
        $camillaDelete = [];
        
        $dataCamillas = $empresa->camillas(['camilla.idsede' => $id]);
        foreach($request['camilla'] as $indice => $row){

            $nuevo = true;
            $update = false;
            foreach ($dataCamillas as $indice => $row2) {
                if (isset($row['idcamilla']) && $row['idcamilla'] === $row2->idcamilla) {                        
                    $nuevo = false;
                    $update = true;
                    unset($dataCamillas[$indice]);
                    break 1;
                }
            }
            
            $tmp = array( 
                'nombre' => $row['nombre'],
                'activo' => $row['activo'] 
            ); 
                    
            if ($nuevo) {
                $tmp['idempresa'] = $sede->idempresa;
                $tmp['idsede'] = $id;
                $tmp['created_at'] = date('Y-m-d H:i:s');
                $tmp['id_created_at'] = $this->objTtoken->my;

                $camillaInsert[] = $tmp;
            }

            if ($update) {
                $tmp['updated_at'] = date('Y-m-d H:i:s');
                $tmp['id_updated_at'] = $this->objTtoken->my;

                $camillaUpdate[] = array(
                    'data' => $tmp,
                    'where' => ['idcamilla' => $row['idcamilla']]
                );
            }
        }

        if (!empty($dataCamillas)) { 
            $tmp = array();
            $tmp['deleted'] = '1';
            $tmp['deleted_at'] = date('Y-m-d H:i:s');
            $tmp['id_deleted_at'] = $this->objTtoken->my;

            foreach ($dataCamillas as $row) {
                $camillaDelete[] = array(
                    'data' => $tmp,
                    'where' => array(
                        'idcamilla' => $row->idcamilla
                    )
                );
            }
        }
        
        if ($sede) { 
            \DB::beginTransaction();
            try {
                
                if ($request['sede']['principal'] === '1' && $sede->principal === '0') {
                    $sede->updateSede(['principal' => '0'], ['idempresa' => $sede->idempresa]);
                }
                
                $sede->fill($request['sede']);
                $sede->save();
                
                if(isset($sedehorario)) {
                    \DB::table('sedehorario')->where(array('idsede' => $id))->update($request['sedehorario']);
                }else {
                    $request['sedehorario']['idempresa'] = $idempresa;  
                    $request['sedehorario']['idsede'] = $id;
                    \DB::table('sedehorario')->insert($request['sedehorario']);
                } 
                 
                // $sede->GrabarIps($dataIps, $id);
                $sede->GrabarTurnoterapia($dataturnos, $id); 

                $sede->GrabarIps($dataIps, $id);

                /* Insertar, actualizar, eliminar en tabla 'camilla'.
                 */
                if(!empty($camillaInsert))
                    \DB::table('camilla')->insert($camillaInsert);
                
                foreach ($camillaUpdate as $fila) {
                    \DB::table('camilla')->where($fila['where'])->update($fila['data']);
                }
                foreach ($camillaDelete as $fila) {
                    \DB::table('camilla')->where($fila['where'])->update($fila['data']);
                }
                
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('La sede "' . $sede->nombre . '" ha sido editado. ', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una sede', 404);
    }

    public function destroy($enterprise, $id) {

        $sede = sede::find($id);

        if ($sede) {

            //VALIDACIONES            
            if ($sede->principal === '1') {
                return $this->crearRespuesta('La sede "' . $sede->nombre . '" no puede ser eliminado. Es sede principal.', [200, 'info']);
            }

            $entidad = new entidad();
            $data = $entidad->listaEntidadSede(['sede.idsede' => $id]);
            if (!empty($data)) {
                return $this->crearRespuesta('La sede "' . $sede->nombre . '" no puede ser eliminado. Esta asignado a usuarios.', [200, 'info']);
            }

            $sede->delete();
            return $this->crearRespuesta('La sede "' . $sede->nombre . '" a sido eliminado', 200);
        }

        return $this->crearRespuestaError('Sede no encontrado', 404);
    }

    

}
