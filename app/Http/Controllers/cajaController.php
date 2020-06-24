<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\empresa;
use App\Models\sede;
use App\Models\entidad; 
use App\Models\venta; 
use App\Models\movimiento;

class cajaController extends Controller {

    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    public function construct(Request $request, $enterprise) {
        /* Obtiene solo las sedes a las que tiene acceso el usuario 
         */
        $sede = new sede();
        $empresa = new empresa();

        $idempresa = $empresa->idempresa($enterprise);
        $param = array(
            'sede.idempresa' => $idempresa,
            'entidadsede.identidad' => $this->objTtoken->my
        );

        $data = array(
            'sedes' => $sede->autorizadas($param, $this->objTtoken->mysede),
        );

        return $this->crearRespuesta($data, 200);
    }

    public function index(Request $request, $enterprise) {
 
        $empresa = new empresa();
        $sede = new sede();

        $idempresa = $empresa->idempresa($enterprise);
  
        $data = $sede->sedes($idempresa);

        return $this->crearRespuesta($data, 200);
    }

    public function indexaperturas(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $venta = new venta();
        $sede = new sede();        

        $idempresa = $empresa->idempresa($enterprise);

        $param = array();
        $param['sede.idempresa'] = $idempresa;
        $param['sede.idsede'] = $paramsTMP['idsede'];

        if (isset($paramsTMP['fechacierre']) && !empty($paramsTMP['fechacierre'])) 
            $param['apertura.fechacierre'] = $this->formatFecha($paramsTMP['fechacierre'], 'yyyy-mm-dd');        

        $betweendate = [];
        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta']) && !empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {            
            $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
            $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
            $betweendate = [$paramsTMP['desde'], $paramsTMP['hasta']]; 
        }
             
        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'apertura.fechaapertura';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'desc';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        } 
                
        // dd($param);
        $data = $sede->gridAperturas($param, $pageSize, $orderName, $orderSort, $betweendate);   
        

        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $data->total();
            $data = $data->items();
        }

        if (isset($paramsTMP['ventas'])  && $paramsTMP['ventas'] === '1') {
            $whereIdaperturaIn = [];
            foreach($data as $row){
                $whereIdaperturaIn[] = $row->idapertura;
                $row->ventas = [];
            }

            $param = array('venta.idempresa' => $idempresa); 
            $dataventas = [];
           
            if (!empty($whereIdaperturaIn)) {
                $dataventas = $venta->grid($param, '', '', '', '', '', '', false, false,  $whereIdaperturaIn);
            }

            $dataventas = $this->ordenarMultidimension($dataventas, 'acronimo', SORT_ASC, 'iddocumentofiscal', SORT_ASC, 'serienumero', SORT_ASC);

            foreach($data as $row){
                foreach($dataventas as $row2){
                    if($row->idapertura === $row2->idapertura)                       
                        $row->ventas[] = $row2; 
                } 
            }  
        } 

        return $this->crearRespuesta($data, 200, $total);  
    }

    public function porAbrir(Request $request, $enterprise) {

        $paramsTMP = $request->all();
 
        $empresa = new empresa();

        $sede = sede::find($paramsTMP['idsede']);

        $param = array(
            'sede.idempresa' => $sede->idempresa,
            'sede.idsede' => $paramsTMP['idsede'],
            'sede.estado' => '2' //1: Aperturado 2:Cerrado
        );

        if ($sede) {
            $listcombox = array(
                'cajasporabrir' => $sede->cajasPorAbrir($param)
            );
            return $this->crearRespuesta($sede, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una sede', 404);
    }

    public function porCerrar(Request $request, $enterprise) { 
        
        $objSede = new sede();
        $objVenta = new venta();
        $empresa = new empresa();
        $movimiento = new movimiento(); 

        $paramsTMP = $request->all();
        //1: Aperturado 2:Cerrado 
        $idempresa = $empresa->idempresa($enterprise);
        $apertura = $objSede->apertura(array('sede.idempresa' => $idempresa, 'sede.idsede' => $paramsTMP['idsede'], 'apertura.estado' => '1'));

        $listcombox = [];

        if ($apertura) { 
            //idestadodocumento 26:pendiente 27:pagado 28:anulado
            $ventasrealizadas = $objVenta->grid(['venta.idapertura' => $apertura->idapertura, 'venta.movecon' => '1']); //, 'venta.idestadodocumento' => 27
            $ventasrealizadas = $this->ordenarMultidimension($ventasrealizadas, 'acronimo', SORT_ASC, 'iddocumentofiscal', SORT_ASC, 'serienumero', SORT_ASC);
            // dd($ventasrealizadas);
            $resumenVentas = $this->resumenVentas($ventasrealizadas);
             
            $apertura->totaldeposito = $resumenVentas['ventadeposito'];
            $apertura->totalculqiexpress = $resumenVentas['ventaculqiexpress'];
            $apertura->totalefectivo = $resumenVentas['ventaefectivo'];
            $apertura->totaltarjeta = $resumenVentas['ventatarjeta'];
            $apertura->ventatarjetaVisa = $resumenVentas['ventatarjetaVisa'];
            $apertura->ventatarjetaMastercad = $resumenVentas['ventatarjetaMastercad'];
            $apertura->totalventa = $resumenVentas['ventaefectivo'] + $resumenVentas['ventadeposito'] + $resumenVentas['ventaculqiexpress'] + $resumenVentas['ventatarjeta']; 
            $apertura->ventanotacredito = $resumenVentas['ventanotacredito']; 

            $otrosegresos = $movimiento->grid(['movimiento.idapertura' => $apertura->idapertura, 'movimiento.tipo' => '2']);  

            $listcombox = array( 
                'resumeningresos' => $this->resumenIngresos($ventasrealizadas),
                'ventasrealizadas' => $ventasrealizadas,
                'ventasanuladas' => $objVenta->grid(['venta.idapertura' => $apertura->idapertura, 'venta.movecon' => '0'], '', '', '', '', '', '', FALSE, TRUE),   //, 'venta.idestadodocumento' => 28             
                'otrosingresos' => $movimiento->grid(array('movimiento.idapertura' => $apertura->idapertura, 'movimiento.tipo' => '1')),
                'otrosegresos' => $otrosegresos
            );
        }
        return $this->crearRespuesta($apertura, 200, '', '', $listcombox);
    }  

    public function show($enterprise, $id) {

        $objSede = new sede();
        $empresa = new empresa();
        $entidad = new entidad();
        $entidad = new entidad();

        $idempresa = $empresa->idempresa($enterprise);
        $sede = sede::find($id);  

        if ($sede) {
            $param = array(
                'entidad.idempresa' => $idempresa,
                'entidadsede.idsede' => $sede->idsede,
                'entidad.tipopersonal' => '1'
            );

            $param2 = array(
                'entidad.idempresa' => $idempresa,
                'entidad.tipoafiliado' => '1'
            );

            $documentoseries = $objSede->documentoSeries(['sede.idsede' => $id]);
            // foreach($documentoseries as $row){
            //     $where = array(
            //         'idafiliado' => $row->identidad,
            //         'iddocumentofiscal' => $row->iddocumentofiscal,
            //         'serie' => $row->serie
            //     );
            //     $row->count = $count = Venta::where($where)->whereNull('deleted')->count();

            // }

            $listcombox = array(
                'cajeras' => $objSede->cajaCajeras(['sede.idsede' => $id]),
                'documentoseries' => $documentoseries,
                'personal' => $entidad->entidades($param, true),
                'documentofiscales' => $empresa->documentosfiscales('1'), //Ventas
                'afiliados' => $entidad->entidades($param2, FALSE, NULL, ['entidad.identidad', 'entidad.entidad', 'entidad.acronimo'])
            );

            return $this->crearRespuesta($sede, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Sede no encotrado', 404);
    }

    public function newcaja(Request $request, $enterprise) {

        $empresa = new empresa();
        $entidad = new entidad();

        $row = (object) array(); 

        $listcombox = array(
            'documentofiscales' => $empresa->documentosfiscales('1') //Ventas   
        );

        if (isset($request['idsede'])) {
            $row = sede::find($request['idsede']);
            $param = array(
                'entidad.idempresa' => $row->idempresa,
                'entidadsede.idsede' => $row->idsede,
                'entidad.tipopersonal' => '1'
            );
            $listcombox['personal'] = $entidad->entidades($param, true);
        }

        $param = array(
            'entidad.idempresa' => $row->idempresa,
            'entidad.tipopersonal' => '1'
        );

        $listcombox['afiliados'] = $entidad->entidades($param);

        return $this->crearRespuesta($row, 200, '', '', $listcombox);
    }

    public function abrirapertura(Request $request, $enterprise, $id) {
 
        $sede = sede::find($id); 

        //VALIDACIONES        
        // NO ES NECESARIO XQ OSI CALERA NO NECESITA.
        // if (empty($sede->cajaCajeras(['sede.idsede' => $id]))) {
        //     return $this->crearRespuesta('Sede no tiene asignado responsables', [200, 'info']);
        // }
        
        // if (empty($sede->documentoSeries(['sede.idsede' => $id]))) {
        //     return $this->crearRespuesta('Sede no tiene asignado documentos y series', [200, 'info']);
        // }
        //FIN VALIDACIONES 

        $request = $request->all();
        $request['apertura']['identidadapertura'] = $this->objTtoken->my;
        $request['apertura']['fechaapertura'] = date('Y-m-d');
        $request['apertura']['horaapertura'] = date('H:i:s');
        $request['apertura']['idmoneda'] = '1'; //1:Nuevo sol
        $request['apertura']['estado'] = '1'; //1: Aperturado 2:Cerrado        
        $request['apertura']['saldoinicial'] = isset($request['apertura']['saldoinicial']) ? $request['apertura']['saldoinicial'] : 0;

        /* Campos auditores */
        $request['apertura']['created_at'] = date('Y-m-d H:i:s');
        $request['apertura']['id_created_at'] = $this->objTtoken->my;
        /* Campos auditores */

        if ($sede) {
            $sede->fill(['estado' => '1']);
            $sede->save();
            $boolean = \DB::table('apertura')->insert($request['apertura']);

            return $this->crearRespuesta('Sede ha sido aperturado.', 201);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una sede', 404);
    }
    public function updateapertura(Request $request, $enterprise, $id) {

        $objVenta = new venta(); 
        $sede = sede::find($id);

        $request = $request->all();
 
        $apertura = $sede->apertura(array('apertura.idapertura' => $request['apertura']['idapertura']));
        //dd($apertura);
        /* Campos auditores */
        $update['updated_at'] = date('Y-m-d H:i:s');
        $update['id_updated_at'] = $this->objTtoken->my;
        /* Campos auditores */
        
        if ($sede) { 
            $update['visanetlote'] = $request['apertura']['visanetlote'];
            $update['mastercadlote'] = $request['apertura']['mastercadlote'];
            $update['saldoinicial'] = $request['apertura']['saldoinicial']; 
            $update['tcdolar'] = $request['apertura']['tcdolar'];
            if ($apertura->estado === '2') {//Cerrado
                $update['totalsoles'] = $request['apertura']['totalsoles']; 
                $update['totaldolares'] = $request['apertura']['totaldolares']; 
                $update['faltantesobrante'] = ($request['apertura']['totalsoles'] + $request['apertura']['totaldolares'] * $request['apertura']['tcdolar']) - $apertura->cajafinal;

                if (isset($request['apertura']['totalvisa']))
                    $update['totalvisa'] = $request['apertura']['totalvisa']; 

                if (isset($request['apertura']['totalmastercard']))
                    $update['totalmastercard'] = $request['apertura']['totalmastercard']; 
            }

            \DB::table('apertura')->where(array('idapertura' => $request['apertura']['idapertura']))->update($update);

            return $this->crearRespuesta('Sede ha sido editado.', 201, '', '', $update);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una sede', 404);
    }
    
    public function cerrarapertura(Request $request, $enterprise, $id) {

        $objVenta = new venta();
        $movimiento = new movimiento(); 
        $empresa = new empresa();

        $sede = sede::find($id);
        $idempresa = $empresa->idempresa($enterprise);

        $request = $request->all();

        /* Campos auditores */
        $update['updated_at'] = date('Y-m-d H:i:s');
        $update['id_updated_at'] = $this->objTtoken->my;
        /* Campos auditores */

        if ($sede) {
            
            $resumenVentas = $this->resumenVentas($objVenta->grid(['venta.idapertura' => $request['apertura']['idapertura']]));
             
            $update['totalefectivo'] = $resumenVentas['ventaefectivo'];
            $update['totaltarjeta'] = $resumenVentas['ventatarjeta'];            
            $update['identidadcierre'] = $this->objTtoken->my;
            $update['fechacierre'] = date('Y-m-d');
            $update['horacierre'] = date('H:i:s');
            $update['estado'] = '2'; //1: Aperturado 2:Cerrado
            $update['saldoinicial'] = $request['apertura']['saldoinicial']; 
            $update['tcdolar'] = $request['apertura']['tcdolar']; 
            $update['visanetlote'] = $request['apertura']['visanetlote'];
            $update['mastercadlote'] = $request['apertura']['mastercadlote'];
            $update['totalsoles'] = $request['apertura']['totalsoles'];
            $update['totalvisa'] = $request['apertura']['totalvisa'];
            $update['totalmastercard'] = $request['apertura']['totalmastercard'];
            $update['totaldolares'] = $request['apertura']['totaldolares']; 
            
            $otrosingresos = $movimiento->grid(array('movimiento.idapertura' => $request['apertura']['idapertura'], 'movimiento.tipo' => '1'));

            $otrosegresos = $movimiento->grid(array('movimiento.idapertura' => $request['apertura']['idapertura'], 'movimiento.tipo' => '2'));   

            $totalotrosegresos = 0;
            $totalotrosingresos = 0; 
            foreach($otrosegresos as $row){ 
                $totalotrosegresos += $row->total;  
            }
            foreach($otrosingresos as $row){
                $totalotrosingresos += $row->total; 
            }

            $update['cajafinal'] = $request['apertura']['saldoinicial'] + $resumenVentas['ventaefectivo'] + $totalotrosingresos - $totalotrosegresos;
            $update['faltantesobrante'] = ($request['apertura']['totalsoles'] + $request['apertura']['totaldolares'] * $request['apertura']['tcdolar']) - $update['cajafinal'];

            $sede->fill(['estado' => '2']); //1: Aperturado 2:Cerrado
            $sede->save();

            \DB::table('apertura')->where(array('idapertura' => $request['apertura']['idapertura']))->update($update);

            return $this->crearRespuesta('Sede ha sido cerrado.', 201);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una sede', 404);
    } 

    

    private function validarNumeroExistente($idafiliado, $iddocumentofiscal, $serie, $serienumero, $idventa = ''){
        $param = array(
            'idafiliado' => $idafiliado,
            'iddocumentofiscal' => $iddocumentofiscal,
            'serie' => $serie,
            'serienumero' => $serienumero
        );  

        $select = \DB::table('venta')
                    ->where($param)
                    ->whereNull('venta.deleted');
                    
        if(!empty($idventa))
            $select->where('venta.idventa', '!=', $idventa);
        
        $documento = $select->get()->all();
 
        $return =  count($documento) > 0 ? true : false;

        return $return; 
    }
    
    public function update(Request $request, $enterprise, $id) {
        $empresa = new empresa();

        $sede = sede::find($id);
        $request = $request->all();

        $idempresa = $empresa->idempresa($enterprise); 
        $dataDocseries = $sede->documentoSeries(['sede.idsede' => $id]); 
                

        //Graba en tabla 'cajero'
        $dataCajero = [];
        foreach ($request['cajero'] as $row) {
            $dataCajero[] = array(
                'idsede' => $id,
                'identidad' => $row['identidad'],
                'activo' => $row['activo']
            );
        } 
        /* Obtener array de 'Insert', 'Update', 'Deleted' con campos auditores para tabla 'documentoserie'.
         */
        $documentoserieInsert = [];
        $documentoserieUpdate = [];
        $documentoserieDelete = [];

        foreach ($request['documentoserie'] as $row) {
            $nuevo = true;
            $update = false;
            foreach ($dataDocseries as $row2) {
                if (isset($row['iddocumentoserie']) && $row['iddocumentoserie'] === $row2->iddocumentoserie) {
                    $nuevo = false;
                    $update = true; 
                    break 1;
                }
            }

            $tmp = array(
                'idsede' => $id,
                'identidad' => $row['identidad'],
                'iddocumentofiscal' => $row['iddocumentofiscal'],
                'serie' => $row['serie'], 
                'numero' => $row['numero'], 
                'montomax' => $row['montomax'],  
                'uso' => $row['uso'],
                'iddocumentofiscalref' => $row['iddocumentofiscalref'],
                'numeroeditable' => isset($row['numeroeditable']) ? $row['numeroeditable'] : '0',
                'seesunat' => isset($row['seesunat']) ? $row['seesunat'] : '0',
                'sucursalsunat' => isset($row['sucursalsunat']) ? $row['sucursalsunat'] : null,
            );   

            if ($nuevo) { 
                $tmp['idempresa'] = $idempresa;
                $tmp['idsede'] = $id;
                $tmp['created_at'] = date('Y-m-d H:i:s');
                $tmp['id_created_at'] = $this->objTtoken->my;
                $documentoserieInsert[] = $tmp;
            }

            if ($update) {
                $tmp['updated_at'] = date('Y-m-d H:i:s');
                $tmp['id_updated_at'] = $this->objTtoken->my;

                $documentoserieUpdate[] = array(
                    'data' => $tmp,
                    'where' => ['iddocumentoserie' => $row['iddocumentoserie']]
                );
            }
        }

        if (!empty($dataDocseries)) {

            $tmp = array();
            $tmp['deleted'] = '1';
            $tmp['deleted_at'] = date('Y-m-d H:i:s');
            $tmp['id_deleted_at'] = $this->objTtoken->my;

            $validation['inValid'] = false;
            foreach ($dataDocseries as $row2) {
                $eliminar = true;
                foreach ($request['documentoserie'] as $row) {
                    if (isset($row['iddocumentoserie']) && $row['iddocumentoserie'] === $row2->iddocumentoserie){                         
                        $eliminar = false;
                        break 1;
                    }
                }

                if ($eliminar) {
                    $documentoserieDelete[] = array(
                        'data' => $tmp,
                        'where' => array(
                            'iddocumentoserie' => $row2->iddocumentoserie
                        )
                    ); 
                }
            } 
        }

        if ($sede) { 

            \DB::beginTransaction();
            try {

                $sede->GrabarCajero($dataCajero, $id);

                /* Insertar, actualizar, eliminar en tabla 'documentoserie'.
                 */
                if (!empty($documentoserieInsert))
                    \DB::table('documentoserie')->insert($documentoserieInsert);

                foreach ($documentoserieUpdate as $fila) {
                    \DB::table('documentoserie')->where($fila['where'])->update($fila['data']);
                }
                foreach ($documentoserieDelete as $fila) {
                    \DB::table('documentoserie')->where($fila['where'])->update($fila['data']);
                }
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Sede ha sido editado', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una sede', 404);
    } 
    

     

}
