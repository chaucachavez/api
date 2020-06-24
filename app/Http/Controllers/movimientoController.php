<?php

namespace App\Http\Controllers;

use Excel;
use App\Models\sede;
use App\Models\arbol;
use App\Models\empresa;
use App\Models\movimiento;
use App\Exports\DataExport;
use Illuminate\Http\Request;

class movimientoController extends Controller {

    // public $pathImg =  'C:\\xampp7.3\\htdocs\\apiosi\\public\\img\\osi\\'; 
    public $pathImg =  '/home/centromedico/public_html/apiosi/public/img/osi/';
    // public $pathImg =  '/home/ositest/public_html/apiosi/public/img/osi/';
    
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

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $movimiento = new movimiento();
        $param = array();

        $idempresa = $empresa->idempresa($enterprise);
        $param['movimiento.idempresa'] = $idempresa;
        if (isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])) {
            $param['movimiento.idsede'] = $paramsTMP['idsede'];
        }

        if (isset($paramsTMP['tipo']) && !empty($paramsTMP['tipo'])) {
            $param['movimiento.tipo'] = $paramsTMP['tipo'];
        }
        
        $between = [];
        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta']) && !empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {            
            $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
            $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
            $between = [$paramsTMP['desde'], $paramsTMP['hasta']]; 
        }
        
        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'movimiento.fecha';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'DESC';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }
        
        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : ''; 

        $datamov = $movimiento->grid($param, $between, $like, $pageSize, $orderName, $orderSort);
        
        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $datamov->total();
            $datamov = $datamov->items();
        }
        // dd($data->items());  
        if (isset($paramsTMP['formato']) && !empty($paramsTMP['formato'])) {
            if(in_array($paramsTMP['formato'], ['xls', 'xlsx'])){ 
                $data = array(); 
                $i = 0;
                foreach($datamov as $row){ 
                    
                    $data[$i] = array(
                        'SEDE' => $row->nombresede, 
                        'CAJA' => $row->idapertura,
                        'TIPO' => $row->tipo === '1' ? 'Ingreso' : 'Egreso', 
                        'FECHA' => $row->fecha,
                        'PERSONAL' => ucwords(strtolower($row->entidad)),
                        'PROVEEDOR' => ucwords(strtolower($row->proveedor)),
                        'DOCUMENTO' => $row->nombredocumento,
                        'NUMERO' => $row->numero,
                        'C' => $row->codigo,
                        'GASTO' => $row->nombregasto,
                        'DESCRIPCION' => $row->concepto, 
                        'REGISTRADO' => ucwords(strtolower($row->personal)), 
                        'TOTAL' => number_format($row->total, 2, '.', ',')
                    );   
                    $i++;
                }  
                    
                return Excel::download(new DataExport($data), 'Reporte_' . date('Y-m-d H:i:s') . '.xlsx');
            }
        } else {
            return $this->crearRespuesta($datamov, 200, $total);
        }        
    }            
    
    public function show(Request $request, $enterprise, $id) {

        $objMovimiento = new movimiento();          
        $empresa = new empresa();
        $arbol = new arbol();
        $sede = new sede();
        
        $idempresa = $empresa->idempresa($enterprise);  
         
        $movimiento = $objMovimiento->movimiento(array('movimiento.idmovimiento' =>$id));
        
        $paramsTMP = $request->all(); 

        $gastos = $arbol->grid(array('arbol.idempresa' => $idempresa, 'arbol.idcategoria' => 3, 'arbol.activo' => '1')); 
        $gastos = $this->ordenarMultidimension($gastos, 'codigo', SORT_ASC, 'parent', SORT_ASC, 'nombre', SORT_ASC); 


        $gastos2 = $gastos;
        $nuevoOrden = [];
        $flat = true;
        foreach($gastos as $i => $row) {
            if ($row['codigo'] === 'O' && $flat) {            
                //Añade las Ñ
                foreach($gastos2 as $row2) {
                    if ($row2['codigo'] === 'Ñ'){ 
                        $nuevoOrden[] = $row2;
                    }
                }
                //Añade las Ñ
                $nuevoOrden[] = $row;
                $flat = false;
            } elseif ($row['codigo'] !== 'Ñ'){                
                $nuevoOrden[] = $row;
            }            
        }

        if ($movimiento) {  
            //dd($movimiento);
            $listcombox = array(
                'sedes' => $sede->autorizadas(array('sede.idempresa' => $idempresa, 'entidadsede.identidad' => $this->objTtoken->my)),
                'gastos' => $nuevoOrden, 
                'documentofiscales' => $empresa->documentosfiscales('3'), 
                'movimientodestinos' => $objMovimiento->destinos(['movimiento.idmovimiento' => $id]),
                'destinos' => $arbol->grid(array('arbol.idempresa' => $idempresa, 'arbol.idcategoria' => 4)) 
            ); 
            return $this->crearRespuesta($movimiento, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Cita médica no encotrado', 404);
    }
    
    public function newmovimiento(Request $request, $enterprise) {
        
        $arbol = new arbol();
        $empresa = new empresa();
        $sede = new sede(); 
        $objMovimiento = new movimiento(); 

        $idempresa = $empresa->idempresa($enterprise); 
         
        $gastos = $arbol->grid(array('arbol.idempresa' => $idempresa, 'arbol.idcategoria' => 3, 'arbol.activo' => '1')); 
        $gastos = $this->ordenarMultidimension($gastos, 'codigo', SORT_ASC, 'parent', SORT_ASC, 'nombre', SORT_ASC); 

        $gastos2 = $gastos;
        $nuevoOrden = [];
        $flat = true;
        foreach($gastos as $i => $row) {
            if ($row['codigo'] === 'O' && $flat) {            
                //Añade las Ñ
                foreach($gastos2 as $row2) {
                    if ($row2['codigo'] === 'Ñ'){ 
                        $nuevoOrden[] = $row2;
                    }
                }
                //Añade las Ñ
                $nuevoOrden[] = $row;
                $flat = false;
            } elseif ($row['codigo'] !== 'Ñ'){                
                $nuevoOrden[] = $row;
            }            
        }

        $listcombox = array(             
            'sedes' => $sede->autorizadas(array('sede.idempresa' => $idempresa, 'entidadsede.identidad' => $this->objTtoken->my)),
            'gastos' => $nuevoOrden,
            'documentofiscales' => $empresa->documentosfiscales('3'), 
            'destinos' => $arbol->grid(array('arbol.idempresa' => $idempresa, 'arbol.idcategoria' => 4))
        );

        $sedes = sede::select('idsede', 'nombre', 'direccion')->where('idempresa', '=', $idempresa)->get()->all();
        $listcombox['recibosproximos'] = [];

        foreach($sedes as $row2) {
            $listcombox['recibosproximos'][] = array(
                'idsede'=> $row2->idsede, 
                'nombre'=> $row2->nombre, 
                'numero' => $objMovimiento->generaRecibointerno($idempresa, $row2->idsede)
            );
        }

        return $this->crearRespuesta([], 200, '', '', $listcombox);
    }
    
    public function store(Request $request, $enterprise) {

        $empresa = new empresa(); 
        $sede = new sede();
        $objmovimiento = new movimiento();

        $request = $request->all(); 
        $idempresa = $empresa->idempresa($enterprise); 
        

        //VALIDACIONES
        if (!(isset($request['movimiento']['idapertura']) && !empty($request['movimiento']['idapertura']))) {
            $aperturas = $sede->gridAperturas(['sede.idempresa' => $idempresa, 'sede.idsede' => $request['movimiento']['idsede'], 'apertura.estado' => '1']);
            //return $this->crearRespuesta('No hay cajas abiertas.', [200, 'info'],'','',$aperturas);
            if(count($aperturas) === 0){
                return $this->crearRespuesta('No hay cajas abiertas.', [200, 'info']);
            } 
            
            if(count($aperturas) > 1){
                return $this->crearRespuesta('Tiene mas de una caja abierta.', [200, 'info']);
            } 
        }

        if (isset($request['movimiento']['idapertura']) && !empty($request['movimiento']['idapertura'])) {
            $apertura = $sede->apertura(['apertura.idapertura' => $request['movimiento']['idapertura']]);
            //VALIDACIONES 
            if ($apertura->estado === '2' && !in_array($this->objTtoken->myperfilid, [1, 10, 6, 19])) {
                return $this->crearRespuesta('No se puede registrar, caja relacionada está cerrada.', [200, 'info']);
            } 
            //VALIDACIONES 
        }

        //FIN VALIDACIONES
        if($request['movimiento']['iddocumentofiscal'] === 9){
            $request['movimiento']['numero'] = $objmovimiento->generaRecibointerno($idempresa, $request['movimiento']['idsede']);
        }

        $request['movimiento']['idempresa'] = $idempresa;
        if (!(isset($request['movimiento']['idapertura']) && !empty($request['movimiento']['idapertura']))) {
            $request['movimiento']['idapertura'] = $aperturas[0]->idapertura;
        }

        if (isset($request['movimiento']['fecha']) && !empty($request['movimiento']['fecha'])) {
            $request['movimiento']['fecha'] = $this->formatFecha($request['movimiento']['fecha'], 'yyyy-mm-dd');
        } else {
            $request['movimiento']['fecha'] = date('Y-m-d'); 
        }        
          
        /*Campos auditores*/
        $request['movimiento']['created_at'] = date('Y-m-d H:i:s');  
        $request['movimiento']['id_created_at'] = $this->objTtoken->my;  
        /*Campos auditores*/        

        \DB::beginTransaction();
        try {      
            $movimiento = movimiento::create($request['movimiento']);

            if (isset($request['movimientodestinos'])) {
                $dataMovimientodestinos = [];
                foreach ($request['movimientodestinos'] as $row) {
                    $dataMovimientodestinos[] = ['idmovimiento' => $movimiento->idmovimiento, 'idarbol' => $row['idarbol']];
                } 
                $movimiento->GrabarDestinos($dataMovimientodestinos, $movimiento->idmovimiento);
            } 
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        $mensaje = $request['movimiento']['tipo'] === '1'?'Ingreso': 'Egreso';

        return $this->crearRespuesta($mensaje.' ha sido creado.', 201);
    }
     
    public function update(Request $request, $enterprise, $id) {
         
        $sede = new sede();
        
        $movimiento = movimiento::find($id); 
        
        $request = $request->all();
        
        if(isset($request['movimiento']['fecha']) && !empty($request['movimiento']['fecha']))
            $request['movimiento']['fecha'] = $this->formatFecha($request['movimiento']['fecha'], 'yyyy-mm-dd');                

        if( (isset($request['movimiento']['revision']) && $request['movimiento']['revision'] != $movimiento->revision) ||
            (isset($request['movimiento']['revisioncomentario']) && $request['movimiento']['revisioncomentario'] != $movimiento->revisioncomentario) )
            $request['movimiento']['identidadrevision'] = $this->objTtoken->my;         

        if( (isset($request['movimiento']['control']) && $request['movimiento']['control'] != $movimiento->control) ||  
            (isset($request['movimiento']['controlcomentario']) && $request['movimiento']['controlcomentario'] != $movimiento->controlcomentario) ){
            $request['movimiento']['identidadctrol'] = $this->objTtoken->my; 
            $request['movimiento']['fechactrol'] = date('Y-m-d');  
        } 

        if ($movimiento) { 
            $apertura = $sede->apertura(['apertura.idapertura' => $movimiento->idapertura]); 
            //VALIDACIONES 
            // if ($apertura->estado === '2' && (float) $movimiento->total !== (float)$request['movimiento']['total']){
            if ($apertura->estado === '2' && !in_array($this->objTtoken->myperfilid, [1, 10])) {
                return $this->crearRespuesta('No se puede editar, caja relacionada está cerrada.', [200, 'info']);
            }
            
            $eliminado = false;
            if (isset($request['movimiento']['adjunto']) && empty($request['movimiento']['adjunto'])) {                  
                if (!empty($movimiento->adjunto) && unlink($this->pathImg . $movimiento->adjunto))
                    $eliminado = true;            
            }
 
            \DB::beginTransaction();
            try {      
                $movimiento->fill($request['movimiento']);
                $movimiento->save();

                if (isset($request['movimientodestinos'])) {
                    $dataMovimientodestinos = [];
                    foreach ($request['movimientodestinos'] as $row) {
                        $dataMovimientodestinos[] = ['idmovimiento' => $movimiento->idmovimiento, 'idarbol' => $row['idarbol']];
                    } 
                    $movimiento->GrabarDestinos($dataMovimientodestinos, $movimiento->idmovimiento);
                } 
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();
            
            $mensaje = $movimiento->tipo  === '1'?'Ingreso': 'Egreso';
            return $this->crearRespuesta($mensaje. ' ha sido editado. ', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un perfil', 404);
    }
    
    public function destroy($enterprise, $id) { 
         
        $sede = new sede();
        
        $movimiento = movimiento::find($id);           
        
        if ($movimiento) {

            \Log::info(print_r($this->objTtoken, true));

             
            $apertura = $sede->apertura(['apertura.idapertura' => $movimiento->idapertura]);
            //VALIDACIONES 
            if ($apertura->estado === '2' && !in_array($this->objTtoken->myperfilid, [1, 10])) {
                return $this->crearRespuesta('No se puede eliminar, caja relacionada está cerrada.', [200, 'info']);
            } 
            //VALIDACIONES 
            
            $auditoria = ['deleted'=>'1', 'deleted_at'=>date('Y-m-d H:i:s'), 'id_deleted_at'=>$this->objTtoken->my];
            $movimiento->fill($auditoria);                
            $movimiento->save();
            
            $mensaje = $movimiento->tipo  === '1'?'Ingreso': 'Egreso';
            return $this->crearRespuesta($mensaje.' ha sido eliminado', 200, '', '', $auditoria);
        }
        
        return $this->crearRespuestaError('Movimiento no encotrado', 404);
    }
}
