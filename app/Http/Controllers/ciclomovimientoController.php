<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\empresa;
use App\Models\citamedica; 
use App\Models\presupuesto; 
use App\Models\cicloatencion; 
use App\Models\ciclomovimiento; 

class ciclomovimientoController extends Controller {

    public function __construct(Request $request) {
        $this->getToken($request);
    }

    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();
        
        $empresa = new empresa();
        $ciclomovimiento = new ciclomovimiento();

        $idempresa = $empresa->idempresa($enterprise);

        $param = array();
        $param['ciclomovimiento.idempresa'] = $idempresa;
        

        if (isset($paramsTMP['idcicloatencion']) && !empty($paramsTMP['idcicloatencion'])) {
            $param['ciclomovimiento.idcicloatencion'] = $paramsTMP['idcicloatencion'];
        }

        $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'ciclomovimiento.fecha';
        $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'ASC';
        $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;

        $between = array();

        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta'])) {
            if (!empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {
                $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
                $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
                $between = [$paramsTMP['desde'], $paramsTMP['hasta']];
            }
        }

        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';

        $data = $ciclomovimiento->grid($param, $like, $between, $pageSize, $orderName, $orderSort);

        return $this->crearRespuesta($data->items(), 200, $data->total());
    } 

    public function show($enterprise, $id) {

        $objCiclomovimiento = new ciclomovimiento(); 

        $ciclomovimiento = $objCiclomovimiento->ciclomovimiento($id);

        if ($ciclomovimiento) {
            return $this->crearRespuesta($ciclomovimiento, 200);
        }

        return $this->crearRespuestaError('Ciclo atención no encotrado', 404);
    }
    
    public function store(Request $request, $enterprise) {
        // [2020-04-28 16:08:19] local.ERROR: Undefined index: ciclomovimiento {"exception":"[object] (ErrorException(code: 0): Undefined index: ciclomovimiento at /home/centromedico/public_html/apiosi/app/Http/Controllers/ciclomovimientoController.php:80)

        $objCiclomovimiento = new ciclomovimiento();
        $empresa = new empresa();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();
       

        $idcicloatencion = $request['ciclomovimiento']['idcicloatencion'];
        $cicloatencion = cicloatencion::find($idcicloatencion);
        $presupuesto = presupuesto::where('idcicloatencion', '=', $idcicloatencion)->first();

        //VALIDACIONES
        if (empty($presupuesto)) {
            return $this->crearRespuesta('Ciclo de atenci&oacute;n no tiene presupuesto.', [200, 'info']);
        }
        
        if ((float) $request['ciclomovimiento']['monto'] <= 0) {
            return $this->crearRespuesta('Monto inv&aacute;lido.', [200, 'info']);
        }

        if ($request['ciclomovimiento']['tipo'] === '2' && $request['ciclomovimiento']['idcicloatencionref'] === $idcicloatencion) {
            return $this->crearRespuesta('El ciclo a transferir es el mismo. Seleccione un ciclo distinto.', [200, 'info']);
        }

        // 1: Devolucion  2: Transferencia 3: Anidacion
        $creditodisp = $presupuesto->montocredito - $presupuesto->montoefectuado;
        if (in_array($request['ciclomovimiento']['tipo'], ['1', '2']) && (float) $request['ciclomovimiento']['monto'] > $creditodisp) {
            return $this->crearRespuesta('Monto es mayor al cr&eacute;dito disponible.', [200, 'info']);
        }

        // return $this->crearRespuesta('Test'. gettype($request['ciclomovimiento']['tipo']), [200, 'info'], '', '', $request);
        //FIN VALIDACIONES 

        $request['ciclomovimiento']['idempresa'] = $idempresa;
        $request['ciclomovimiento']['idsede'] = $cicloatencion->idsede;
        $request['ciclomovimiento']['numero'] = $objCiclomovimiento->generaNUMERO($cicloatencion->idsede);
        $request['ciclomovimiento']['fecha'] = date('Y-m-d');
        $request['ciclomovimiento']['identidad'] = $this->objTtoken->my;
        /* Campos auditores */
        $request['ciclomovimiento']['created_at'] = date('Y-m-d H:i:s');
        $request['ciclomovimiento']['id_created_at'] = $this->objTtoken->my;
        /* Campos auditores */

        //return $this->crearRespuesta('Ciclo de atención se encuentra cerrado. No puede editarse.', [200, 'info'],'','', $request['ciclomovimiento']);

        \DB::beginTransaction();
        try {

            //tipo 1: Devolucion  2: Transferencia 3: Anidacion
            if($request['ciclomovimiento']['tipo'] === '3'){                
                $request['ciclomovimiento']['idcicloatencionref'] = $request['ciclomovimiento']['idcicloatencion'];
                unset($request['ciclomovimiento']['idcicloatencion']);
            }
            $ciclomovimiento = ciclomovimiento::create($request['ciclomovimiento']);
 
            if(in_array($request['ciclomovimiento']['tipo'], ['1', '2'])) {
                $nuevomonto =  $presupuesto->montopago - (float) $request['ciclomovimiento']['monto'];
            } else if($request['ciclomovimiento']['tipo'] === '3') {
                $nuevomonto =  $presupuesto->montopago + (float) $request['ciclomovimiento']['monto'];
            }

            $total = (double)($presupuesto->tipotarifa === 1 ? $presupuesto->regular : ($presupuesto->tipotarifa === 2 ? $presupuesto->tarjeta : $presupuesto->efectivo));
            $fillPre = array(
                'montopago' => $nuevomonto,
                'montocredito' => $nuevomonto,
                'updated_at' => date('Y-m-d H:i:s'),
                'id_updated_at' => $this->objTtoken->my,
                'idestadopago' => $nuevomonto >= $total && $total > 0 ? 68 : ($nuevomonto > 0 && $nuevomonto < $total ? 67 : 66),                    
                'total' => $total 
            ); 
            
            $presupuesto->fill($fillPre);
            $presupuesto->save();
            $presupuesto->grabarLog($presupuesto->idpresupuesto, $this->objTtoken->my);
            $this->actualizarPagopresupuestoCitaMedica($presupuesto);

            if ($request['ciclomovimiento']['tipo'] === '2') {
                $idcicloatencion = $request['ciclomovimiento']['idcicloatencionref'];
                $presupuesto = presupuesto::where('idcicloatencion', '=', $idcicloatencion)->first();
                $total = (double)($presupuesto->tipotarifa === 1 ? $presupuesto->regular : ($presupuesto->tipotarifa === 2 ? $presupuesto->tarjeta : $presupuesto->efectivo));

                $nuevomonto = $presupuesto->montopago + (float) $request['ciclomovimiento']['monto'];
                $fillPre = array(
                    'montopago' => $nuevomonto,
                    'montocredito' => $nuevomonto,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'id_updated_at' => $this->objTtoken->my,
                    'idestadopago' => $nuevomonto >= $total && $total > 0 ? 68 : ($nuevomonto > 0 && $nuevomonto < $total ? 67 : 66),                    
                    'total' => $total 
                ); 

                $presupuesto->fill($fillPre);
                $presupuesto->save();
                $presupuesto->grabarLog($presupuesto->idpresupuesto, $this->objTtoken->my);
                $this->actualizarPagopresupuestoCitaMedica($presupuesto);
            } 

        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta('Nota de saldo N°' . $ciclomovimiento->numero . ' ha sido creado.', 201);
    }

    public function destroy($enterprise, $id) {
                        
        $ciclomovimiento = ciclomovimiento::find($id);

        //tipo 1: Devolucion  2: Transferencia 3: Anidacion
        if(in_array($ciclomovimiento->tipo, ['1', '2'])) {
            $idcicloatencion = $ciclomovimiento->idcicloatencion;
        } else if($ciclomovimiento->tipo === '3') {
            $idcicloatencion = $ciclomovimiento->idcicloatencionref;
        }

        $presupuesto = presupuesto::where('idcicloatencion', '=', $idcicloatencion)->first(); 
         
        if ($ciclomovimiento) { 
   
            if(in_array($ciclomovimiento->tipo, ['1', '2'])) {
                $nuevomonto =  $presupuesto->montopago + $ciclomovimiento->monto;
            } else if($ciclomovimiento->tipo === '3') {
                $nuevomonto =  $presupuesto->montopago - $ciclomovimiento->monto;
            }

            \DB::beginTransaction();
            try {                         
                $total = (double)($presupuesto->tipotarifa === 1 ? $presupuesto->regular : ($presupuesto->tipotarifa === 2 ? $presupuesto->tarjeta : $presupuesto->efectivo));
                $fillPre = array(
                    'montopago' => $nuevomonto,  
                    'montocredito' => $nuevomonto,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'id_updated_at' => $this->objTtoken->my,
                    'idestadopago' => $nuevomonto >= $total && $total > 0 ? 68 : ($nuevomonto > 0 && $nuevomonto < $total ? 67 : 66),                    
                    'total' => $total 
                );               
                $presupuesto->fill($fillPre);
                $presupuesto->save();
                $presupuesto->grabarLog($presupuesto->idpresupuesto, $this->objTtoken->my);
                $this->actualizarPagopresupuestoCitaMedica($presupuesto);   

                // 1: Devolucion  2: Transferencia
                if($ciclomovimiento->tipo === '2'){ 
                    $idcicloatencion = $ciclomovimiento->idcicloatencionref;                
                    $presupuesto = presupuesto::where('idcicloatencion', '=', $idcicloatencion)->first(); 
                    $total = (double)($presupuesto->tipotarifa === 1 ? $presupuesto->regular : ($presupuesto->tipotarifa === 2 ? $presupuesto->tarjeta : $presupuesto->efectivo));

                    $nuevomonto = $presupuesto->montopago - $ciclomovimiento->monto;
                    $fillPre = array(
                        'montopago' => $nuevomonto,  
                        'montocredito' => $nuevomonto,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'id_updated_at' => $this->objTtoken->my,
                        'idestadopago' => $nuevomonto >= $total && $total > 0 ? 68 : ($nuevomonto > 0 && $nuevomonto < $total ? 67 : 66),                    
                        'total' => $total 
                    );

                    $presupuesto->fill($fillPre);
                    $presupuesto->save();
                    $presupuesto->grabarLog($presupuesto->idpresupuesto, $this->objTtoken->my);       
                    $this->actualizarPagopresupuestoCitaMedica($presupuesto);   
                }
                
                $fill = ['deleted' => '1', 'deleted_at' => date('Y-m-d H:i:s'), 'id_deleted_at' => $this->objTtoken->my];
                $ciclomovimiento->fill($fill);
                $ciclomovimiento->save();                                   
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Nota de saldo ha sido eliminado.', 200);
        }
        return $this->crearRespuestaError('Nota de saldo no encotrado', 404);
    }
    
    private function actualizarPagopresupuestoCitaMedica($presupuesto) {
        /* Setear 'todo A', 'Acuenta B', 'Todo B', 'Acuenta C', 'Todo C' en tabla CITAMEDICA;
         * Se considera primera cita, por orden de fecha y hora de inicio de cita.
         */
        $cicloatencion = new cicloatencion();
        $citamedica = new citamedica();
        
        $montopago = $presupuesto->montopago;
        $citasmedicas = $cicloatencion->cicloCitasmedicas(['cicloatencion.idcicloatencion' => $presupuesto->idcicloatencion]);
        $citasmedicas = $this->ordenarMultidimension($citasmedicas, 'fecha', SORT_ASC, 'inicio', SORT_ASC);
        $presupuestodet = $presupuesto->presupuestodet($presupuesto->idpresupuesto);
        $tmp = [];
        foreach ($citasmedicas as $row) {
            $CMCosto = 0;

            $tratamientosmedicos = $citamedica->tratamientomedico(['citamedica.idcitamedica' => $row->idcitamedica]);
            foreach ($tratamientosmedicos as $tratamiento) {
                $costo = 0;
                foreach ($presupuestodet as $rowpres) {
                    if ($tratamiento->idproducto === $rowpres->idproducto) {
                        $cantidad = $tratamiento->cantidad;
                        if (!empty($tratamiento->parentcantidad)) {
                            $cantidad = $tratamiento->cantidad * $tratamiento->parentcantidad;
                        }
                        $preciounit = $presupuesto->tipotarifa === 1 ? $rowpres->preciounitregular : ($presupuesto->tipotarifa === 2 ? $rowpres->preciounittarjeta : $rowpres->preciounitefectivo);
                        $costo = $preciounit * $cantidad;
                        break;
                    }
                }
                $CMCosto += $costo;
            }

            if ($montopago > 0) {

                $dinero = 0;
                if ($CMCosto <= $montopago) {
                    if ($row->presupuesto === 'Acuenta C' || empty($row->presupuesto)) {
                        $row->presupuesto = 'Todo C';
                    }
                    if ($row->presupuesto === 'Acuenta B') {
                        $row->presupuesto = 'Todo B';
                    }
                    $dinero = $CMCosto;
                }

                if ($CMCosto > $montopago) {
                    switch ($row->presupuesto) {
                        case 'Todo A':
                        case 'Todo B':
                            $row->presupuesto = 'Acuenta B';
                            break;
                        default: //'Todo C' o '' o null
                            $row->presupuesto = 'Acuenta C';
                            break;
                    }
                    $dinero = $montopago;
                }
                $montopago = $montopago - $dinero;
            } else {
                $row->presupuesto = '';
            }
//            array_push($tmp, ['idcitamedica' => $row->idcitamedica, 'presupuesto' => $row->presupuesto]);
            \DB::table('citamedica')->where(['idcitamedica' => $row->idcitamedica])->update(['presupuesto' => $row->presupuesto]);
        }
//                return $this->crearRespuesta('=>', [200, 'info'],'','', $tmp);
    }
}
