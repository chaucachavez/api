<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\sede;
use App\Models\modelo;
use App\Models\empresa;
use App\Models\citamedica;
use App\Models\presupuesto;
use App\Models\cicloatencion;
use App\Models\ciclomovimiento;
use App\Models\cicloautorizacion; 

class cicloautorizacionController extends Controller {

    public function __construct(Request $request) {
        $this->getToken($request);
    }

    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();
        
        $empresa = new empresa();
        $cicloautorizacion = new cicloautorizacion();

        $idempresa = $empresa->idempresa($enterprise);

        $param = array(); 

        if (isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])) {
            $param['cicloautorizacion.idsede'] = $paramsTMP['idsede'];
        }

        if (isset($paramsTMP['idestadofactura']) && !empty($paramsTMP['idestadofactura'])) {
            $param['cicloatencion.idestadofactura'] = $paramsTMP['idestadofactura'];
        }

        if (isset($paramsTMP['idestadoimpreso']) && !empty($paramsTMP['idestadoimpreso'])) {
            $param['cicloautorizacion.idestadoimpreso'] = $paramsTMP['idestadoimpreso'];
        }

        if (isset($paramsTMP['idcicloatencion']) && !empty($paramsTMP['idcicloatencion'])) {
            $param['cicloautorizacion.idcicloatencion'] = $paramsTMP['idcicloatencion'];
        }

        $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'cicloautorizacion.fecha';
        $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'desc';
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
        // dd($param, $like, $between, $pageSize, $orderName, $orderSort);
        $data = $cicloautorizacion->grid($param, $like, $between, $pageSize, $orderName, $orderSort);
        // dd($data);

        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $data->total();
            $data = $data->items();
        }

        // dd($data);
        if (isset($paramsTMP['facturacion']) && $paramsTMP['facturacion'] === '1' && !empty($data)) { 
            $data = $this->datosFactura($data, $idempresa);
        }

        return $this->crearRespuesta($data, 200, $total);
    }

    private function datosFactura($data, $idempresa) {

        $modelo = new modelo();
        $objSede = new sede();
        $empresa = new empresa();

        $objCicloatencion = new cicloatencion();

        $tmpmodelos = $modelo->grid(['modelo.idempresa' => $idempresa]); 

        $whereIn = []; 
        foreach($data as $row) {
            $whereIn[] = $row->idcicloatencion;            
        }

        $paramDocu = array(
            'documentoserie.idempresa' => $idempresa, 
            'documentoserie.iddocumentofiscal' => 1 
        );
        $dataf = $objSede->documentoSeries($paramDocu);
        
        foreach ($dataf as $row) {                
            $serienumero = '(' . $row->acronimo . ') ' . $row->nombredocumento . ' N° ' . $row->serie . '-' . str_pad(($row->numero + 1), 6, "0", STR_PAD_LEFT);
            $row->documentoSerieNumero = $serienumero;     
        } 

        $datafactura = [];
        foreach ($dataf as $row) {                
            $datafactura[] = array(
                'idafiliado' => $row->identidad,
                'iddocumentofiscal' => $row->iddocumentofiscal,
                'serie' => $row->serie,
                'numero' => $row->numero,
                'iddocumentoserie' => $row->iddocumentoserie, 
                'nombre' => $row->documentoSerieNumero, 
                'idsede' => $row->idsede
            );
        }  

        $datadiagnosticos = \DB::table('diagnosticomedico') 
                ->join('diagnostico', 'diagnosticomedico.iddiagnostico', '=', 'diagnostico.iddiagnostico') 
                ->join('citamedica', 'diagnosticomedico.idcitamedica', '=', 'citamedica.idcitamedica') 
                ->select('citamedica.idcicloatencion', 'diagnostico.iddiagnostico', 'diagnostico.nombre as diagnostico')                                           
                ->where('citamedica.idestado', 6) 
                ->whereIn('citamedica.idcicloatencion', $whereIn)            
                ->whereNull('citamedica.deleted')    
                ->orderBy('citamedica.idcicloatencion', 'DESC')
                ->orderBy('diagnostico.nombre', 'ASC')
                ->distinct()
                ->get()->all(); 

        // dd($datadiagnosticos);
        $datacitasmedicas = $objCicloatencion->cicloCitasmedicas([], [], [], $whereIn);

        $aseguradorasplanes = $empresa->aseguradorasplanes($idempresa, true); 

        foreach($data as $i => $row) { 
 
            
            if ($row->idventa) {
                $serienumero = $row->nombredocventa . ' N° ' . $row->serie . '-' . str_pad(($row->serienumero), 6, "0", STR_PAD_LEFT);

                $row->facturas = array(['iddocumentoserie' => $row->idventa, 'nombre' => $serienumero]);
                $row->modelos = array(['idmodelo' => $row->idmodelo, 'nombre' => $row->idmodelo]);
                $row->diagnosticos = array(['iddiagnostico' => $row->idventa, 'nombre' => $row->diagnostico]); 
                //$row->zonas = array(['idzona' => $row->idventa, 'nombre' => $row->zona]); 
                $row->citasmedicas = []; 
                $row->aseguradorasplanes = array(['idaseguradoraplan' => $row->idaseguradoraplan, 'nombre' => $row->nombreaseguradoraplan]);
            } else {

                $modelos = [];
                foreach($tmpmodelos as $rowd){      
                    // foreach($rowd->modeloseguro as $row2){ 
                    //     if($row->idaseguradoraplan === $row2->idaseguradoraplan) {
                    //         $modelos[] = array(
                    //             'idmodelo' => $rowd->idmodelo, 
                    //             'nombre' => $rowd->nombre,
                    //             'modelodet' => $rowd->modelodet
                    //         ); 
                    //     }
                    // }   
                    $modelos[] = array(
                        'idmodelo' => $rowd->idmodelo, 
                        'nombre' => $rowd->nombre,
                        'modelodet' => $rowd->modelodet
                    );          
                } 

                $diagnosticos = [];
                foreach($datadiagnosticos as $rowd){                
                    if($rowd->idcicloatencion === $row->idcicloatencion) 
                        $diagnosticos[] = array('iddiagnostico' => $rowd->iddiagnostico, 'nombre' => $rowd->diagnostico);  
                }

                $citasmedicas = [];
                foreach($datacitasmedicas as $rowd){                
                    if($rowd->idcicloatencion === $row->idcicloatencion) 
                        $citasmedicas[] = array('idcitamedica' => $rowd->idcitamedica, 'fecha' => $rowd->fecha);  
                }

                ///////////////////////////////////////////////////////////////////////////
                
                $row->facturas = $datafactura;  
                $row->modelos = $modelos;   
                $row->diagnosticos = $diagnosticos;
                //$row->zonas = $datazonas;   
                $row->citasmedicas = $citasmedicas;//$objCicloatencion->cicloCitasmedicas(['cicloatencion.idcicloatencion' => $row->idcicloatencion]);

                $row->aseguradorasplanes = $aseguradorasplanes; 
            }
                     
        }
        //dd($data);
        return $data;
    }
 
    public function updateImpresion(Request $request, $enterprise) {
        
        $empresa = new empresa();

        $request = $request->all();

        $idempresa = $empresa->idempresa($enterprise);

        //return $this->crearRespuesta(' ha sido ', 200, '', '', $request);
        
        $whereIn = [];

        foreach($request['cicloautorizaciones'] as $row) {
            $whereIn[] = $row['idcicloautorizacion'];            
        }

        if (!$whereIn) {
            return $this->crearRespuesta('Especifique al menos una autorización', [200, 'info']);
        } 

        /* Campos auditores */
        $update['idventa'] = NULL;
        $update['updated_at'] = date('Y-m-d H:i:s');
        $update['id_updated_at'] = $this->objTtoken->my;
        $update['idestadoimpreso'] = $request['idestadoimpreso'];

        cicloautorizacion::where('idempresa', $idempresa)
                      ->whereNull('deleted') 
                      ->whereIn('idcicloautorizacion', $whereIn) 
                      ->update($update);

        return $this->crearRespuesta('Autorización(es) actualizado', 200, '', '', '');
    }

}

// Amigo, Todo el apoyo en tu denuncia contra la Orquesta, no se trata de hacer quedar mal a la orquesta, se trata de que se respete los derechos de las otras personas, tan simple como esto. Adelante Antonio