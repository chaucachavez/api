<?php

namespace App\Http\Controllers;

use Excel;
use App\Models\sede;
use App\Models\venta;
use \Firebase\JWT\JWT;
use App\Models\empresa;
use App\Models\entidad;
use App\Models\producto;
use App\Mail\InvoiceSend;
use App\Models\tarifario;
use App\Models\citamedica;
use App\Exports\DataExport;
use App\Models\notacredito;
use App\Models\presupuesto;
use Illuminate\Http\Request;
use App\Models\cicloatencion;
use App\Models\cupondescuento;
use App\Models\ciclomovimiento;
use App\Models\cicloautorizacion;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Pdfs\invoiController;

class ventaController extends Controller {
    
    // public $pathImg =  'C:\\xampp7.3\\htdocs\\apiosi\\public\\comprobantes\\';
    public $pathImg =  '/home/centromedico/public_html/apiosi/public/comprobantes/';
    // public $pathImg =  '/home/ositest/public_html/apiosi/public/comprobantes/';
    
    public $temporal;

    public function __construct(Request $request) {
        $this->getToken($request);
        $this->temporal = Config::get('custom.temporal'); 
    }

    public function construct(Request $request, $enterprise) {

        $sede = new sede();
        $objSede = new sede();
        
        $empresa = empresa::where('url', '=', $enterprise)->first();
        $idempresa = $empresa->idempresa;

        $param = array(
            'sede.idempresa' => $idempresa,
            'entidadsede.identidad' => $this->objTtoken->my
        );

        $data = array(
            'estadosventa' => $empresa->estadodocumentos(8),
            'sedes' => $sede->autorizadas($param, $this->objTtoken->mysede) 
        );

        $listcombox = [];
        if (isset($request['others'])) {
            $others = explode(',', $request['others']);

            if (in_array('dose', $others)) {
                $documentoseries = $objSede->documentoSeries(['documentoserie.idempresa' => $idempresa], [], 'entidad.acronimo');                                       
                $listcombox['dose'] = $this->ordenarafiliados($documentoseries);
            }            

            if(in_array('fact', $others)) {
                
                $paramDocu = array(
                    'documentoserie.idempresa' => $idempresa,
                    'documentoserie.iddocumentofiscal' => 1 
                );                
                $dataf = $objSede->documentoSeries($paramDocu);
              
                foreach ($dataf as $row) {                
                    $serienumero = '(' . $row->acronimo . ') ' . $row->nombredocumento . ' N° ' . $row->serie . '-' . str_pad(($row->numero + 1), 6, "0", STR_PAD_LEFT);
                    $row->documentoSerieNumero = $serienumero;     
                } 
                $listcombox['documentos'] = $dataf;
            }

            if(in_array('nocr', $others)) {
                
                $paramDocu = array(
                    'documentoserie.idempresa' => $idempresa,
                    'documentoserie.iddocumentofiscal' => 13 
                );                
                $dataf = $objSede->documentoSeries($paramDocu, [], 'documentoserie.identidad');
              
                foreach ($dataf as $row) {                
                    $serienumero = '(' . $row->acronimo . ') ' . $row->nombredocumento . ' N° ' . $row->serie . '-' . str_pad(($row->numero + 1), 6, "0", STR_PAD_LEFT);
                    $row->documentoSerieNumero = $serienumero;     
                } 
                $listcombox['documentos'] = $dataf;
            }

            if(in_array('asepla', $others)) {
                $listcombox['asepla'] = $empresa->aseguradorasplanes($idempresa, true);
            }
        }

        return $this->crearRespuesta($data, 200, '', '', $listcombox); 
    }

    public function showdocumentoserie(Request $request, $enterprise) {

        $request = $request->all();
        $objSede = new sede();

        $data = $objSede->documentoserie(['documentoserie.iddocumentoserie' => $request['iddocumentoserie']]);

        return $this->crearRespuesta($data, 200); 
    }


    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $venta = new venta();

        $param = array();
        $param['venta.idempresa'] = $empresa->idempresa($enterprise);
       
        if (isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])) {
            $param['venta.idsede'] = $paramsTMP['idsede'];
        }

        if (isset($paramsTMP['idcliente']) && !empty($paramsTMP['idcliente'])) {
            $param['venta.idcliente'] = $paramsTMP['idcliente'];
        }
        
        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'venta.fechaventa';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'desc';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }
        
        $between = array();
        $betweenHora = array();

        if (isset($paramsTMP['serie']) && !empty(trim($paramsTMP['serie']))) {
            $param['venta.serie'] = trim($paramsTMP['serie']);
        }

        if (isset($paramsTMP['serienumero']) && !empty(trim($paramsTMP['serienumero']))) {
            $param['venta.serienumero'] = trim($paramsTMP['serienumero']);
        }

        if (isset($paramsTMP['idafiliado']) && !empty(trim($paramsTMP['idafiliado']))) {
            $param['venta.idafiliado'] = trim($paramsTMP['idafiliado']);
        }

        if (isset($paramsTMP['idestadodocumento']) && !empty($paramsTMP['idestadodocumento'])) {
            $param['venta.idestadodocumento'] = $paramsTMP['idestadodocumento'];
        }

        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta'])) {
            if (!empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {
                $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
                $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
                $between = [$paramsTMP['desde'], $paramsTMP['hasta']];
            }
        }

        if (isset($paramsTMP['horaventainicio']) && isset($paramsTMP['horaventafin'])) {
            if (!empty($paramsTMP['horaventainicio']) && !empty($paramsTMP['horaventafin'])) {
                $betweenHora = [$paramsTMP['horaventainicio'], $paramsTMP['horaventafin']];
            }
        }

        $whereIddocumentofiscal = [];
        if (isset($paramsTMP['iddocumentofiscal']) && !empty(trim($paramsTMP['iddocumentofiscal']))) {
            $arrayiddocumentofiscal = explode(',', $request['iddocumentofiscal']);
            if(count($arrayiddocumentofiscal) === 1) {
                $param['venta.iddocumentofiscal'] = $paramsTMP['iddocumentofiscal'];
            }else {
                $whereIddocumentofiscal = $arrayiddocumentofiscal;
            }
        }else {
            //Omitir factura, cuando no especifica
            $whereIddocumentofiscal = [2,3,4,10,11,13];
        }

        $datosfactura = false;
        if (isset($paramsTMP['facturacion']) &&  $paramsTMP['facturacion'] === '1') {
            $datosfactura = true;
        }

        $ventareferencia = false;
        if (isset($paramsTMP['ventareferencia']) &&  $paramsTMP['ventareferencia'] === '1') {
            $ventareferencia = true;
        }

        // Validacion 45 por COVID
        if (isset($paramsTMP['formato']) && 
            !empty($paramsTMP['formato']) &&  
            in_array($paramsTMP['formato'], ['xls', 'xlsx']) && 
            $this->objTtoken->myperfilid !== 1 && 
            $this->objTtoken->my !== 28874 // Maribel
        ) {

            $fechaMaxima = strtotime('-45 day', strtotime(date('Y-m-d')));
            $fechausuario = strtotime($paramsTMP['desde']);

            if ($fechausuario <= $fechaMaxima) {
                return response()->json('ACCESO DENEGADO: Solo puedes descargar ultimo 45 dias.', 200);
            }
        }

        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';

        $likepaciente = !empty($paramsTMP['likepaciente']) ? trim($paramsTMP['likepaciente']) : '';

        $data = $venta->grid($param, $between, $like, $pageSize, $orderName, $orderSort, $betweenHora, false, false, [], [], [], $whereIddocumentofiscal, $likepaciente, $datosfactura, $ventareferencia);

        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $data->total();
            $data = $data->items();
        }
        
        if (isset($paramsTMP['detalle']) && $paramsTMP['detalle'] === '1' && !empty($data)) { 
            foreach ($data as $row) {
                $row->ventadet = \DB::table('ventadet')  
                        ->join('venta', 'ventadet.idventa', '=', 'venta.idventa')
                        ->join('producto', 'ventadet.idproducto', '=', 'producto.idproducto')
                        ->select('ventadet.*', 'producto.nombre as nombreproducto', 'venta.iddocumentofiscal')       
                        ->where('ventadet.idventa', $row->idventa)    
                        ->whereNull('ventadet.deleted')                
                        ->whereNull('venta.deleted')
                        ->get()->all();
            } 
        }

        if (isset($paramsTMP['formato']) && !empty($paramsTMP['formato'])) { 
            if (in_array($paramsTMP['formato'], ['xls', 'xlsx'])) { 

                $dataventa = $data; 
                $data = array();
                
                $whereIn = array();
                foreach($dataventa as $row){ 
                    $whereIn[] = $row->idventa;
                    $row->ventadet = [];
                }
 
                if (isset($paramsTMP['facturacion']) && $paramsTMP['facturacion'] === '1' && $whereIn) {
                    $ventadet = \DB::table('ventadet')
                        // ->select('ventadet.idventa', 'ventadet.renombreproducto as nombre', 'ventadet.cantidad')
                        ->select('ventadet.idventa', 'ventadet.nombre', 'ventadet.cantidad')
                        ->whereIn('ventadet.idventa', $whereIn)    
                        ->whereNull('ventadet.deleted')                
                        ->get()->all();
                    
                    if ($ventadet) {
                        foreach($dataventa as $row){
                            foreach($ventadet as $row2){
                                if ($row->idventa === $row2->idventa) {
                                    $row->ventadet[] = $row2;
                                }
                            }                            
                        }
                    }
                } 

                $i = 0;
                foreach($dataventa as $row){                 

                    if (isset($paramsTMP['facturacion']) &&  $paramsTMP['facturacion'] === '1') {

                        $cm = NULL;
                        $proced1 = NULL;
                        $proced1cant = NULL;
                        $proced2 = NULL;
                        $proced2cant = NULL;

                        switch (count($row->ventadet)) {
                            case 1: 
                                $cm = $row->ventadet[0]->cantidad;
                                break; 
                            case 2: 
                                $cm = $row->ventadet[0]->cantidad;
                                $proced1 = $row->ventadet[1]->nombre;
                                $proced1cant = $row->ventadet[1]->cantidad;
                                break;
                            case 3: 
                                $cm = $row->ventadet[0]->cantidad;
                                $proced1 = $row->ventadet[1]->nombre;
                                $proced1cant = $row->ventadet[1]->cantidad;
                                $proced2 = $row->ventadet[2]->nombre;
                                $proced2cant = $row->ventadet[2]->cantidad;
                                break;
                            //Otras no está definido
                        }
                    }
                    
                    $refDocumentoSerieNumero = '';
                    $refDocumentoFecha = '';
                    if (isset($paramsTMP['ventareferencia']) &&  $paramsTMP['ventareferencia'] === '1') {
                        $refDocumentoSerieNumero = isset($row->refDocumentoSerieNumero) ? $row->refDocumentoSerieNumero : '';
                        $refDocumentoFecha = isset($row->reffechaemision) ? $row->reffechaemision : '';
                    }

                    switch ($row->iddocumento) {
                        case 1: 
                            $nombredocumento = 'DNI';
                            break;
                        case 2: 
                            $nombredocumento = 'RUC';
                            break;
                        case 3: 
                            $nombredocumento = 'CARNET EXT.';
                            break;
                        case 4: 
                            $nombredocumento = 'PASAPORTE';
                            break; 
                        default:
                            $nombredocumento = '';
                            break;
                    } 

                    $fechaventaigualciclo = 'NO';
                    if($row->fechaaperturaciclo === $row->fechaventa) {
                        $fechaventaigualciclo = 'SI';
                    }

                    $data[$i] = array(
                        'SEDE' => $row->nombresede, 
                        'COMPROBANTE' => $row->nombredocventa, 
                        'AFILIADO' => $row->acronimo, 
                        'SERIE' => $row->serie, 
                        'NUMERO' => $row->serienumero, 
                        'FECHA VENTA' => $row->fechaventa,
                        'COMP.ASOC. A NC' => $refDocumentoSerieNumero,
                        'COMP.ASOC. F.EMISION' => $refDocumentoFecha,
                        'CLIENTE' => $row->nombrecliente,
                        'CLIENTE_DOCUMENTO' => $nombredocumento,
                        'IDENTIFICACION' => $row->numerodoc
                    ); 


                    $data[$i]['ESTADO'] = $row->estadodocumento;
                    $data[$i]['M.PAGO'] = $row->mediopagonombre;
                    $data[$i]['M.PAGO2'] = $row->mediopagosegnombre;
                    // $data[$i]['CM'] = isset($row->idcitamedica)?'Si':'';
                    $data[$i]['COD.CICLO'] = $row->idcicloatencion;
                    $data[$i]['APERTURA CICLO'] = $row->fechaaperturaciclo;
                        
                    if (isset($paramsTMP['facturacion']) &&  $paramsTMP['facturacion'] === '1') {
                        if ($row->idestadodocumento === 28) {

                            $data[$i]['PERIODO'] = 'ANULADO';
                            $data[$i]['H.C'] = 'ANULADO';
                            $data[$i]['PACIENTE'] = 'ANULADO';
                            $data[$i]['PARENTESCO'] = 'ANULADO';
                            $data[$i]['SEGURO'] = 'ANULADO';
                            $data[$i]['EMPRESA'] = 'ANULADO';
                            $data[$i]['CICLO'] = 'ANULADO';
                            $data[$i]['AUTORIZACION'] = 'ANULADO';
                            $data[$i]['COD.MODELO'] = 'ANULADO';
                            $data[$i]['DIAGNOSTICO'] = 'ANULADO';
                            $data[$i]['CM'] = 'ANULADO';
                            $data[$i]['CONSULTA'] = 'ANULADO';
                            $data[$i]['SESIONES'] = 'ANULADO';
                            $data[$i]['PROCEDIMIENTO 1'] = 'ANULADO';
                            $data[$i]['UNIDAD PROC. 1'] = 'ANULADO';
                            $data[$i]['PROCEDIMIENTO 2'] = 'ANULADO';
                            $data[$i]['UNIDAD PROC. 2'] = 'ANULADO';
                            $data[$i]['TOTAL DE TTO'] = 'ANULADO';
                            $data[$i]['TOTAL ASEGURADORA'] = 'ANULADO';
                            $data[$i]['COA %'] = 'ANULADO';
                            $data[$i]['COASEGURO'] = 'ANULADO';
                            $data[$i]['DEDUCIBLE'] = 'ANULADO';
                            $data[$i]['DEDUCIBLE SIN IGV'] = 'ANULADO';
                            $data[$i]['PCT TOTAL COAS +DED'] = 'ANULADO';
                            $data[$i]['SUB TOTAL'] = 'ANULADO';
                            $data[$i]['IGV'] = 'ANULADO';

                        } else {
                            $data[$i]['PERIODO'] = isset($row->faprograma)?$row->faprograma:'';
                            $data[$i]['H.C'] = isset($row->fahc)?$row->fahc:'';
                            $data[$i]['PACIENTE'] = isset($row->fapaciente)?$row->fapaciente:'';
                            $data[$i]['PARENTESCO'] = isset($row->fatitular)?$row->fatitular:'';
                            $data[$i]['SEGURO'] = isset($row->nombreaseguradoraplan)?$row->nombreaseguradoraplan:'';
                            $data[$i]['EMPRESA'] = isset($row->faempresa)?$row->faempresa:'';
                            $data[$i]['CICLO'] = isset($row->faciclo)?$row->faciclo:'';
                            $data[$i]['AUTORIZACION'] = isset($row->faautorizacion)?$row->faautorizacion:'';
                            $data[$i]['COD.MODELO'] = $row->nombremodelo;
                            $data[$i]['DIAGNOSTICO'] = isset($row->fadiagnostico)?$row->fadiagnostico:'';
                            $data[$i]['CM'] = isset($cm)?$cm:'';
                            $data[$i]['CONSULTA'] = isset($row->faconsulta)?$row->faconsulta:'';
                            $data[$i]['SESIONES'] = isset($row->fasesiones)?$row->fasesiones:'';
                            $data[$i]['PROCEDIMIENTO 1'] = $proced1;
                            $data[$i]['UNIDAD PROC. 1'] = $proced1cant;
                            $data[$i]['PROCEDIMIENTO 2'] = $proced2;
                            $data[$i]['UNIDAD PROC. 2'] = $proced2cant;
                            $data[$i]['TOTAL DE TTO'] = isset($row->fatotaldetto)?$row->fatotaldetto:'';
                            $data[$i]['TOTAL ASEGURADORA'] = isset($row->fatotalaseguradora)?$row->fatotalaseguradora:'';
                            $data[$i]['COA %'] = isset($row->facoaseguro)?$row->facoaseguro:'';
                            $data[$i]['COASEGURO'] = $row->coaseguro;
                            $data[$i]['DEDUCIBLE'] = isset($row->fadeducible)?$row->fadeducible:'';
                            $data[$i]['DEDUCIBLE SIN IGV'] = $row->deducible;
                            $data[$i]['PCT TOTAL COAS +DED'] = isset($row->fapcttotalcoaded)?$row->fapcttotalcoaded:'';
                            $data[$i]['SUB TOTAL'] = $row->subtotal;
                            $data[$i]['IGV'] = $row->valorimpuesto;
                        } 
                    } 
                    
                    $data[$i]['TOTAL'] = $row->total;

                    if (isset($paramsTMP['facturacion']) &&  $paramsTMP['facturacion'] === '1') {
                        if ($row->idestadodocumento === 28) {
                            // $data[$i]['TOTAL EN LETRAS'] = 'ANULADO';
                            $data[$i]['REEMPLAZOS'] = 'ANULADO';
                        } else {
                            // $data[$i]['TOTAL EN LETRAS'] = isset($row->faletra)?$row->faletra:'';
                            $data[$i]['REEMPLAZOS'] = $row->descripcion;
                        }                        
                    }   
                    $data[$i]['CAJA'] = $row->created; 
                    $data[$i]['CAJA REGISTRO'] = $row->created_at; 
                    $data[$i]['CICLO_IGUALFECHAVENTA'] = $fechaventaigualciclo;  
                    $i++;
                }
                     
                return Excel::download(new DataExport($data), 'Reporte_' . date('Y-m-d H:i:s') . '.xlsx');
            }
        } else {
            return $this->crearRespuesta($data, 200, $total); 
        } 
    } 

    public function show(Request $request, $enterprise, $id) {

        $empresa = new empresa();
        $Objventa = new venta();
        $objSede = new sede(); 

        $idempresa = $empresa->idempresa($enterprise);
        $venta = $Objventa->venta($id, false, false, true);

        if ($venta) {
            $serienumero = '(' . $venta->acronimo . ') ' . $venta->nombredocfiscal . ' N° ' . $venta->serie . '-' . str_pad($venta->serienumero, 6, "0", STR_PAD_LEFT);
            $venta->documentoSerieNumero = $serienumero;
            $listcombox = array(
                'ventadet' => $Objventa->ventadet($id),
                'ventafactura' => $Objventa->ventafactura($id) 
            ); 
           
            if (isset($request['others'])) {
                $others = explode(',', $request['others']);
 
                if (in_array('dose', $others)) {
                    $documentoseries = $objSede->documentoSeries(['documentoserie.idempresa' => $idempresa], [], 'entidad.acronimo');                                       
                    $listcombox['dose'] = $this->ordenarafiliados($documentoseries);
                }

                if (in_array('mepa', $others)) {
                     $listcombox['mepa'] = $empresa->mediopagos();
                } 

                // if (in_array('nocr', $others)) { 
                //     $paramDocu = array( 
                //         'documentoserie.identidad' => $venta->idafiliado,
                //         'documentoserie.iddocumentofiscal' => 13, //Nota de credito
                //         'documentoserie.iddocumentofiscalref' => $venta->iddocumentofiscal 
                //     );   
                //     $datanocr = $objSede->documentoSeries($paramDocu); 
                //     foreach ($datanocr as $row) {             
                //         $row->documentoSerieNumero = '(' . $row->acronimo . ') ' . $row->nombredocumento . ' N° ' . $row->serie . '-' . str_pad(($row->numero + 1), 6, "0", STR_PAD_LEFT); 
                //     }  
                //     $listcombox['nocr'] = !empty($datanocr) && count($datanocr) === 1 ? $datanocr[0] : null; 
                // } 
 
            }
 
            // dd($listcombox);
            return $this->crearRespuesta($venta, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Venta no encotrado', 404);
    }

    private function ordenarafiliados($data){
        $ds = [];
        foreach ($data as $row) {
            $ds[$row->identidad]['identidad'] = $row->identidad;
            $ds[$row->identidad]['acronimo'] = $row->acronimo;
            $ds[$row->identidad]['entidad'] = $row->entidad;
            $ds[$row->identidad]['documentos'][$row->iddocumentofiscal]['iddocumentofiscal'] = $row->iddocumentofiscal;
            $ds[$row->identidad]['documentos'][$row->iddocumentofiscal]['nombredocumento'] = $row->nombredocumento;
            $ds[$row->identidad]['documentos'][$row->iddocumentofiscal]['serie'][$row->serie]['serie'] = $row->serie; 
        } 

        $tmp = array();
        $i = 0; 
        foreach ($ds as $row) {
            $tmp[$i] = array('identidad' => $row['identidad'], 'acronimo' => $row['acronimo'], 'entidad' => $row['entidad'], 'documentos' => []);
            $j = 0;
            
            foreach ($row['documentos'] as $row2) {
                $tmp[$i]['documentos'][$j] = array('iddocumentofiscal' => $row2['iddocumentofiscal'], 'nombredocumento' => $row2['nombredocumento'], 'serie' => []);                            
                $k = 0;          
                foreach ($row2['serie'] as $row3) {
                    $tmp[$i]['documentos'][$j]['serie'][$k] = array('serie' => $row3['serie']);
                    $k++;
                }
                $j++;
            }
            $i++;
        }

        return $tmp;
    }

    public function newventa(Request $request, $enterprise) {
        $paramsTMP = $request->all();

        $cicloatencion = new cicloatencion();
        $empresa = new empresa();
        $sede = new sede();

        $idempresa = $empresa->idempresa($enterprise);

        $param = array(
            'sede.idempresa' => $idempresa,
            'entidadsede.identidad' => $this->objTtoken->my //1//$this->objTtoken->my
        );

        $sedes = $sede->autorizadas($param, $this->objTtoken->mysede);  //[1,2,3,4]); //



        $listcombox = array(
            'sedes' => $sedes,
            'cajas' => [],
            'documentoseries' => [],
            'mediopagos' => $empresa->mediopagos(),
            'fechaactual' => date('d/m/Y')
        );

        return $this->crearRespuesta([], 200, '', '', $listcombox);
    }

    public function graficames(Request $request, $enterprise) {

        $empresa = new empresa();
        $venta = new venta();

        $request = $request->all();
        $idempresa = $empresa->idempresa($enterprise);

        $param = array(
            'venta.idempresa' => $idempresa,
            'venta.anoventa' => $request['ano'],
            'venta.mesventa' => $request['mes']
        );

        $ventas = $venta->grid($param);
        $data = [];

        foreach ($ventas as $row) {
            //idestadodocumento: 26:Pago pendiente 27:Pagado 28:Anulado 
            if ($row->idestadodocumento === 27) {
                $dia = (int) substr($row->fechaventa, 0, 2);
                $data[$row->idsede]['name'] = $row->nombresede;
                $data[$row->idsede]['type'] = 'line';
                $data[$row->idsede]['data'][$dia] = @$data[$row->idsede]['data'][$dia] + $row->total;
            }
        }

        $series = [];
        $legend = [];
        $xAxisData = [];
        foreach ($data as $row) {
            $valores = [];
            for ($i = 1; $i <= cal_days_in_month(CAL_GREGORIAN, $request['mes'], $request['ano']); $i++) {
                $valores[] = isset($row['data'][$i]) ? $row['data'][$i] : 0;
            }
            $row['data'] = $valores;
            $series[] = $row;

            $legend['data'][] = $row['name'];
        }

        for ($i = 1; $i <= cal_days_in_month(CAL_GREGORIAN, $request['mes'], $request['ano']); $i++) {
            $xAxisData[] = $i;
        }

        $ventas = array(
            'legend' => $legend,
            'xAxisData' => $xAxisData,
            'series' => $series
        );
        //dd($ventas);
        return $this->crearRespuesta($ventas, 200);
    }

    public function graficaafiliado(Request $request, $enterprise) {

        $empresa = new empresa();
        $venta = new venta(); 
        $sede = new sede();
        $request = $request->all();
        
        $empresa = $empresa->empresa(['empresa.url' => $enterprise], ['empresa.idempresa', 'empresa.alertamontomin', 'empresa.montomin']);
        $idempresa = $empresa->idempresa;

        $param = array( 
            'venta.idempresa' => $idempresa,
            'venta.idestadodocumento' => 27 //pagado
        );

        if(isset($request['idsede']) && !empty($request['idsede']) && $request['idsede'] !== 'all') {
            $param['venta.idsede'] = $request['idsede'];
        }

        $IdsedeIn = []; 
        if (isset($request['idsede']) && $request['idsede'] === 'all') {
            //Sedes autorizados
            $params = array(
                'sede.idempresa' => $idempresa,
                'entidadsede.identidad' => $this->objTtoken->my 
            );

            $sedesautorizadas = $sede->autorizadas($params); 
            foreach ($sedesautorizadas as $row) {
                $IdsedeIn[] = $row->idsede;
            } 
        }

        if (isset($request['desde']) && isset($request['hasta'])) {
            if (!empty($request['desde']) && !empty($request['hasta'])) {
                $request['desde'] = $this->formatFecha($request['desde'], 'yyyy-mm-dd');
                $request['hasta'] = $this->formatFecha($request['hasta'], 'yyyy-mm-dd');
                $between = [$request['desde'], $request['hasta']];
            }
        }

        $fields = [
            'sede.nombre as nombresede',
            'afiliado.acronimo',
            'afiliado.entidad as nombreafiliado',
            'documentofiscal.nombre as nombredocventa',
            'venta.serie',
            'venta.total',
            'venta.idsede',
            'venta.idafiliado',
            'venta.iddocumentofiscal',
            \DB::raw('CONCAT(venta.idsede, venta.idafiliado, documentofiscal.iddocumentofiscal, venta.serie) as codigo')
        ];

        // dd($fields);
        $data = $venta->grid($param, $between, '', '', 'sede.nombre', 'ASC', '', false, false, [], $fields, [], [1,2,3,11,13], '', false, false, $IdsedeIn); 

        $data = $this->ventasacumulativasporafiliado($data, $empresa);
        // dd($data); 

        return $this->crearRespuesta($data, 200, '', '', $IdsedeIn);
    }

    private function ventasacumulativasporafiliado($data, $empresa){

        $sede = new sede();

        $whereInIdsede = []; 
        $whereInIdentidad  = []; 
        $whereInIddocu = []; 
        $whereInSerie = []; 
        $database = []; 
        foreach ($data as $row) {
            $database[$row->codigo]['idsede'] = $row->idsede;
            $database[$row->codigo]['nombresede'] = $row->nombresede;
            $database[$row->codigo]['acronimo'] = $row->acronimo;
            $database[$row->codigo]['idafiliado'] = $row->idafiliado;
            $database[$row->codigo]['nombreafiliado'] = $row->nombreafiliado;
            $database[$row->codigo]['iddocumentofiscal'] = $row->iddocumentofiscal;
            $database[$row->codigo]['nombredocventa'] = $row->nombredocventa;
            $database[$row->codigo]['serie'] = $row->serie;
            $database[$row->codigo]['acumulado'] = (isset($database[$row->codigo]['acumulado']) ? $database[$row->codigo]['acumulado'] : 0) + $row->total;
           
            if(!in_array($row->idsede, $whereInIdsede)) 
                $whereInIdsede[] = $row->idsede;
            
            if(!in_array($row->idafiliado, $whereInIdentidad)) 
                $whereInIdentidad[] = $row->idafiliado;

            if(!in_array($row->iddocumentofiscal, $whereInIddocu)) 
                $whereInIddocu[] = $row->iddocumentofiscal;

            if(!in_array($row->serie, $whereInSerie)) 
                $whereInSerie[] = $row->serie; 
        } 
        
        $montosmaximos = $sede->documentosMontomaximos(['documentoserie.idempresa' => $empresa->idempresa], $whereInIdsede, $whereInIdentidad, $whereInIddocu, $whereInSerie);
        
        $data = [];
        //dd($database, $montosmaximos);
        foreach($database as $row){ 
            $documentoserie = null;
            foreach($montosmaximos as $maximo) {
                if( $maximo->idsede === $row['idsede'] && 
                    $maximo->identidad === $row['idafiliado'] && 
                    $maximo->iddocumentofiscal === $row['iddocumentofiscal'] && 
                    $maximo->serie === $row['serie']) { 
                        $documentoserie = $maximo;
                        break;
                }                
            }

            $row['montomax'] = NULL;        
            $row['uso'] = NULL; 
            $row['acumuladoporc'] = NULL;
            $row['acumuladoalerta'] = 0; 

            if($documentoserie && $documentoserie->montomax > 0){
                $row['acumuladoporc'] = $documentoserie->montomax > 0 ? round($row['acumulado'] * 100 /(float) $documentoserie->montomax, 0) : null;
                $resta = (float) $documentoserie->montomax - $row['acumulado'];
                $row['acumuladoalerta'] = $empresa->montomin > 0 && $resta < (float) $empresa->montomin ? 1 : 0;
            }

            if($documentoserie){
                $row['montomax'] = $documentoserie->montomax;
                $row['uso'] = $documentoserie->uso;               
            }
            
            $data[]= $row;
        } 
        // dd($data);
        return $data;
    }

    public function graficaano(Request $request, $enterprise) {

        $empresa = new empresa();
        $venta = new venta();

        $request = $request->all();
        $idempresa = $empresa->idempresa($enterprise);

        $param = array(
            'venta.idempresa' => $idempresa,
            'venta.anoventa' => $request['ano']
        );

        $ventas = $venta->grid($param);
        $data = [];

        foreach ($ventas as $row) {
            //idestadodocumento: 26:Pago pendiente 27:Pagado 28:Anulado 
            if ($row->idestadodocumento === 27) {
                $mes = (int) substr($row->fechaventa, 3, 2);
                $data[$row->idsede]['name'] = $row->nombresede;
                $data[$row->idsede]['type'] = 'bar';
                $data[$row->idsede]['data'][$mes] = @$data[$row->idsede]['data'][$mes] + $row->total;
            }
        }

        $series = [];
        $legend = [];
        $xAxisData = [];
        foreach ($data as $row) {
            $valores = [];
            for ($i = 1; $i <= 12; $i++) {
                $valores[] = isset($row['data'][$i]) ? $row['data'][$i] : 0;
            }
            $row['data'] = $valores;
            $series[] = $row;

            $legend['data'][] = $row['name'];
        }

        $mes = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Set', 'Oct', 'Nov', 'Dic'];
        for ($i = 0; $i <= 11; $i++) {
            $xAxisData[] = $mes[$i];
        }

        $ventas = array(
            'legend' => $legend,
            'xAxisData' => $xAxisData,
            'series' => $series
        );
        //dd($ventas);
        return $this->crearRespuesta($ventas, 200);
    }

    public function cajasydocumentos(Request $request, $enterprise) {
        /* Opcion para mostrar Cajas o documentos de Venta, es invocado desde formulario de "Ventas en caja"
         * cuando el vendedor dispone de mas de una caja
         */

        $empresa = new empresa();        
        $venta = new venta();
        $objSede = new sede();

        $empresa = $empresa->empresa(['empresa.url' => $enterprise], ['empresa.idempresa', 'empresa.alertamontomin', 'empresa.montomin']); //$empresa->idempresa($enterprise);
        
        $idempresa = $empresa->idempresa;

        $request = $request->all();

        $sede = sede::find($request['idsede']);

        $data = [];
        $aperturas = []; 
        $cajeras = []; 

        if ($request['tipo'] === 'documentos' || $request['tipo'] === 'documentosproximos') {

            $paramDocu = array(
                'sede.idempresa' => $idempresa,
                'sede.idsede' => $request['idsede'] 
            );

            $proximos = $request['tipo'] === 'documentosproximos' ? true : false;                   
            $data = $this->obtenerSeriedocumento($objSede->documentoSeries($paramDocu), $proximos);            

            $datatmp = [];
            if($empresa->alertamontomin === '1' && $empresa->montomin > 0){ 

                $param = array( 
                    'venta.idempresa' => $idempresa,
                    'venta.idestadodocumento' => 27, //pagado
                    'venta.idsede' => $request['idsede'] 
                ); 

                $between = [date('Y-m').'-01', date('Y-m-d')];

                $fields = [
                    'sede.nombre as nombresede',
                    'afiliado.acronimo',
                    'afiliado.entidad as nombreafiliado',
                    'documentofiscal.nombre as nombredocventa',
                    'venta.serie',
                    'venta.total',
                    'venta.idsede',
                    'venta.idafiliado',
                    'venta.iddocumentofiscal',
                    'venta.serie', 
                    \DB::raw('CONCAT(venta.idsede, venta.idafiliado, documentofiscal.iddocumentofiscal, venta.serie) as codigo')
                ];
                
                $datatmp = $venta->gridLight($param, $between, '', '', '', 'ASC',  $fields);  
                $datatmp = $this->ventasacumulativasporafiliado($datatmp, $empresa); 
            }
                
            foreach ($data as $row) {
                //$row->documentoSerieNumero = '('.$row->acronimo.') '.$row->nombredocumento.' N° '.$row->serie.'-0000000'.($row->numero + 1);                
                $serienumero = '(' . $row->acronimo . ') ' . $row->nombredocumento . ' N° ' . $row->serie . '-' . str_pad(($row->numero + 1), 6, "0", STR_PAD_LEFT);
                $row->documentoSerieNumero = $serienumero;                
                $row->acumulado = null;
                $row->acumuladoalerta = 0;

                $tmprow = '';
                foreach($datatmp as $row2) {
                    if($row2['idafiliado'] === $row->identidad && $row2['iddocumentofiscal'] === $row->iddocumentofiscal && $row2['serie'] === $row->serie){
                        $tmprow = $row2;
                        break;
                    }
                }

                if(!empty($tmprow)){
                    $resta = (float) $row->montomax - $tmprow['acumulado'];
                    $row->acumulado = $tmprow['acumulado'];
                    $row->acumuladoalerta = $empresa->montomin > 0 && $resta < (float) $empresa->montomin ? 1 : 0;
                }
            } 

            //Apertura
            $paramCaja = array(                
                'sede.idempresa' => $idempresa,
                'sede.idsede' => $request['idsede'],
                'cajero.activo' => '1'
            );

            $cajeras = $objSede->cajaCajeras($paramCaja); 


            //Lista de cajas que esten abiertas estado == 1
            $aperturas = $objSede->cajasAbiertas(['apertura.idsede' => $request['idsede']]);
        }

        $listcombox = array(
            'data' => $data,
            'aperturas' => $aperturas,
            'cajeras' => $cajeras
        );

        return $this->crearRespuesta([], 200, '', '', $listcombox);
    }

    private function obtenerSeriedocumento($documentoseries, $proximos = false) {
        //Nota GENERAL: Es importante que "$documentoseries" este ordenado por 'documentoserie.orden' 'asc'

        $documentos = []; 

        if ($proximos) {             
            foreach ($documentoseries as $row) {
                if ($row->uso !== '1') 
                    $documentos[] = $row;                    
            }
        } else {
            foreach ($documentoseries as $row) {                   
                if ($row->uso === '1') 
                    $documentos[] = $row;                
            }
        }

        return $documentos;
    }

    public function store(Request $request, $enterprise) {

        $citamedica = new citamedica();
        $empresa = new empresa();
        $sede = new sede();   
        $venta = new venta();

        $idempresa = $empresa->idempresa($enterprise);

        // return $this->crearRespuesta('Suspensión de 1:00pm a 1:20pm', [200, 'info']);

        if (isset($request['venta']['iddocumentoserie']) && !empty($request['venta']['iddocumentoserie'])) {
            $documentoserie = 
                    \DB::table('documentoserie')
                    ->join('documentofiscal', 'documentoserie.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal')
                    ->select('documentoserie.*', 'documentofiscal.codigosunat')
                    ->whereNull('documentoserie.deleted')
                    ->where(array(
                        'iddocumentoserie' => $request['venta']['iddocumentoserie'],
                    ))
                    ->first(); 
        } else { 
            $param = array(
                'documentoserie.identidad' => $request['venta']['idafiliado'],
                'documentoserie.iddocumentofiscal' => $request['venta']['iddocumentofiscal'],
                'documentoserie.serie' => $request['venta']['serie']
            );

            if ($request['venta']['iddocumentofiscal'] === 13) { //NC requiere especificar si es BV o F. 
                $venta = $venta->venta($request['venta']['idventaref']);
                if ($request['tiponc'] === '1' or $request['tiponc'] === '3') { 
                    $param['iddocumentofiscalref'] = $venta->iddocumentofiscal;
                }
            }

            $documentoserie = \DB::table('documentoserie')
                    ->join('documentofiscal', 'documentoserie.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal')
                    ->select('documentoserie.*', 'documentofiscal.codigosunat')
                    ->whereNull('documentoserie.deleted')
                    ->where($param)
                    ->first();
        }

        // return $this->crearRespuesta('XD', [200, 'info'], '', '', $documentoserie);

        // VALIDACIONES    
        // 1. Validar iddocumentoserie    
        if (empty($documentoserie))
        {
            return $this->crearRespuesta('Comprobante no tiene configurado nota de crédito.', [200, 'info'], '', '', $param);
        }

        // 2. NC: Validar que comprobante al cual se emite no tenga nota de crédito
        if (isset($request['venta']['iddocumentofiscal']) && $request['venta']['iddocumentofiscal'] === 13) { 
            $notacredito = venta::where('idventaref', '=', $request['venta']['idventaref'])->first();
            // if ($notacredito) {
            //     return $this->crearRespuesta('Ya existe nota de crédito para comprobante', [200, 'info']);
            // }

            if (empty($request['venta']['idventaref'])){
                return $this->crearRespuesta('Se requiere documento referencia para nota de crédito', [200, 'info']);
            }
        }

        // 3.Validar caja este abierta 
        if (isset($request['venta']['iddocumentoserie']) && !empty($request['venta']['iddocumentoserie'])) {
            $aperturas = $sede->cajasAbiertas(['apertura.idsede' => $request['venta']['idsede']]);
            if (empty($aperturas)) {
                return $this->crearRespuesta('Sede se encuentra cerrada.', [200, 'info'], '', '', $aperturas);
            }
        }

        //  4.Validar numero documento. 
        $docnumero = $documentoserie->numero + 1;
        if (isset($request['tiponc']) && $request['tiponc'] === '2' && !empty($request['venta']['serienumero'])) {
            $docnumero = $request['venta']['serienumero'];
        }

        if ($this->validarNumeroExistente($documentoserie->identidad, 
                $documentoserie->iddocumentofiscal, 
                $documentoserie->serie, 
                $docnumero)) 
        {
            return $this->crearRespuesta('Número de documento ya existe', [200, 'info']);
        }   

        $idciclos = [];
        foreach ($request['ventadet'] as $row) {
            if (isset($row['idcicloatencion']) && 
                    !empty($row['idcicloatencion']) && 
                        !in_array($row['idcicloatencion'], $idciclos)) 
            { 
                $idciclos[] = $row['idcicloatencion'];              
            }
        }     

        // 4.Validar presupuesto y presupuestodet
        /*
        foreach ($idciclos as $idcicloatencion) {

            $presupuesto = presupuesto::where('idcicloatencion', '=', $idcicloatencion)->first(); 
            // if (empty($presupuesto)) {
            //     return $this->crearRespuesta('Ciclo no tiene presupuesto', [200, 'info']);
            //     break;
            // }

            if ($presupuesto) {
                $presupuestodet = $presupuesto->presupuestodet($presupuesto->idpresupuesto);
                if (empty($presupuestodet)) {
                    // Quiere pagar solo CM. AKIKO 01/08/2018
                    // return $this->crearRespuesta('Presupuesto no tiene tratamientos', [200, 'info']);
                    // break;
                }
            }
        }
        */

        //0: reemplazo en apertura 1: Ingreso dinero
        $movecon =  isset($request['venta']['movecon']) && $request['venta']['movecon'] !== '' ? $request['venta']['movecon']: '1';

        $idapertura = null;
        if (isset($request['venta']['iddocumentoserie']) && !empty($request['venta']['iddocumentoserie'])) {
            $idapertura = isset($request['venta']['idapertura']) && !empty($request['venta']['idapertura']) ? $request['venta']['idapertura'] : $aperturas[0]->idapertura;
        }

        $idcicloatencion = isset($request['venta']['idcicloatencion']) ? $request['venta']['idcicloatencion'] : NULL;

        $idtarjetapri = isset($request['venta']['idtarjetapri']) && !empty($request['venta']['idtarjetapri']) ? $request['venta']['idtarjetapri'] : NULL;

        $tarjetapriope = isset($request['venta']['tarjetapriope']) && !empty($request['venta']['tarjetapriope']) ? $request['venta']['tarjetapriope'] : NULL;

        $tarjetaprimonto = isset($request['venta']['tarjetaprimonto']) && !empty($request['venta']['tarjetaprimonto']) ? $request['venta']['tarjetaprimonto'] : NULL;

        $idtarjetaseg = isset($request['venta']['idtarjetaseg']) && !empty($request['venta']['idtarjetaseg']) ? $request['venta']['idtarjetaseg'] : NULL; 

        $tarjetasegope = isset($request['venta']['tarjetasegope']) && !empty($request['venta']['tarjetasegope']) ? $request['venta']['tarjetasegope'] : NULL;

        $tarjetasegmonto = isset($request['venta']['tarjetasegmonto']) && !empty($request['venta']['tarjetasegmonto']) ? $request['venta']['tarjetasegmonto'] : NULL; 

        $fechaventa = isset($request['venta']['fechaventa']) && !empty($request['venta']['fechaventa']) ? $this->formatFecha($request['venta']['fechaventa'], 'yyyy-mm-dd') : date('Y-m-d');

        $idpaciente = isset($request['venta']['idpaciente']) && !empty($request['venta']['idpaciente']) ? $request['venta']['idpaciente'] : NULL; 

        $idmediopago = isset($request['venta']['idmediopago']) && !empty($request['venta']['idmediopago']) ? $request['venta']['idmediopago'] : NULL; 

        $nrooperacion = isset($request['venta']['nrooperacion']) && !empty($request['venta']['nrooperacion']) ? $request['venta']['nrooperacion'] : NULL; 

        $partetipotarjeta = isset($request['venta']['partetipotarjeta']) && !empty($request['venta']['partetipotarjeta']) ? $request['venta']['partetipotarjeta'] : NULL; 

        $parteopetarjeta = isset($request['venta']['parteopetarjeta']) && !empty($request['venta']['parteopetarjeta']) ? $request['venta']['parteopetarjeta'] : NULL; 

        $partemontotarjeta = isset($request['venta']['partemontotarjeta']) && !empty($request['venta']['partemontotarjeta']) ? $request['venta']['partemontotarjeta'] : NULL; 

        $parteefectivo = isset($request['venta']['parteefectivo']) && !empty($request['venta']['parteefectivo']) ? $request['venta']['parteefectivo'] : NULL;

        $igv = isset($request['venta']['igv']) && !empty($request['venta']['igv']) ? $request['venta']['igv'] : NULL; 

        $idventaref = isset($request['venta']['idventaref']) && !empty($request['venta']['idventaref']) ? $request['venta']['idventaref'] : NULL;

        $tiponotacredito = isset($request['venta']['tiponotacredito']) && !empty($request['venta']['tiponotacredito']) ? $request['venta']['tiponotacredito'] : NULL;
 
        $descripcion = isset($request['venta']['descripcion']) && !empty($request['venta']['descripcion']) ? $request['venta']['descripcion'] : NULL;

        $cpecorreo = isset($request['venta']['cpecorreo']) ? $request['venta']['cpecorreo'] : NULL;

        $coaseguro = isset($request['venta']['coaseguro']) && !empty($request['venta']['coaseguro']) ? $request['venta']['coaseguro'] : NULL;
        $deducible = isset($request['venta']['deducible']) && !empty($request['venta']['deducible']) ? $request['venta']['deducible'] : NULL;

        $descuento = isset($request['venta']['descuento'])?$request['venta']['descuento']:NULL;

        $param = array(
            'idempresa' => $idempresa,
            'idsede' => $request['venta']['idsede'], 
            'idafiliado' => $documentoserie->identidad,
            'iddocumentofiscal' => $documentoserie->iddocumentofiscal,
            'serie' => $documentoserie->serie,
            'serienumero' => $docnumero, 
            'idcliente' => $request['venta']['idcliente'], 
            'idapertura' => $idapertura,
            'movecon' => $movecon, 
            'fechaventa' => $fechaventa,   
            'idmoneda' => 1,   
            'idestadodocumento' => 27,  
            'idmediopago' => $idmediopago,          
            'descuento' => $descuento,
            'idventareemplazo' => isset($request['venta']['idventareemplazo'])?$request['venta']['idventareemplazo']:NULL,            
            'subtotal' => $request['venta']['subtotal'],
            'igv' => $igv,
            'valorimpuesto' => $request['venta']['valorimpuesto'],
            'total' => $request['venta']['total'],
            'nrooperacion' => $nrooperacion,
            'partetipotarjeta' => $partetipotarjeta,
            'parteopetarjeta' => $parteopetarjeta,
            'partemontotarjeta' => $partemontotarjeta,
            'parteefectivo' => $parteefectivo,   
            'idcicloatencion' => $idcicloatencion, //Eliminar este campo. Se guarda en ventadet, porque una boleta puede tener el pago de n ciclos.
            'idtarjetapri' => $idtarjetapri,  
            'tarjetapriope' => $tarjetapriope,  
            'tarjetaprimonto' => $tarjetaprimonto,  
            'idtarjetaseg' => $idtarjetaseg, 
            'tarjetasegope' => $tarjetasegope,  
            'tarjetasegmonto' => $tarjetasegmonto,  
            'idpaciente' => $idpaciente,  
            'idventaref' => $idventaref,  
            'tiponotacredito' => $tiponotacredito,  
            'descripcion' => $descripcion,   
            'cpecorreo' => $cpecorreo,
            'coaseguro' => $coaseguro,
            'deducible' => $deducible,
            'created_at' => date('Y-m-d H:i:s'),
            'id_created_at' => $this->objTtoken->my
        ); 
          
        \DB::beginTransaction();
        try { 

            // return $this->crearRespuesta('XD', [200, 'info'], '', '', $documentoserie); 
            $venta = venta::create($param);

            $dataVentadet = [];
            $citasmedicas = [];
            $i = 0;
            foreach ($request['ventadet'] as $row) {

                if (isset($row['idcitamedica']) && !empty($row['idcitamedica'])) {
                    $citasmedicas[] = $row['idcitamedica'];
                }

                $nombre = null;
                // CM OSI ONLINE
                if ($row['idproducto'] === 1 && $request['venta']['idsede'] === 15) {
                    $nombre = 'Teleconsulta médica orientadora';
                } else {
                    $nombre = isset($row['nombreproducto']) ? $row['nombreproducto'] : NULL;
                }

                $dataVentadet[$i] = array(
                    'idventa' => $venta->idventa,
                    'idproducto' => $row['idproducto'],
                    'cantidad' => $row['cantidad'],
                    'preciounit' => $row['preciounit'],                    
                    'valorunit' => $row['valorunit'],
                    'valorventa' => $row['valorventa'],
                    'montototalimpuestos' => $row['montototalimpuestos'],
                    'total' => $row['total'], 
                    'codigocupon' => isset($row['codigocupon']) ? $row['codigocupon'] : null,
                    'descuento' => isset($row['descuento']) ? $row['descuento'] : null,
                    'nombre' => $nombre, 
                    'descripcion' => isset($row['descripcion']) ? $row['descripcion'] : NULL, 
                    'idcitamedica' => isset($row['idcitamedica']) ? $row['idcitamedica'] : NULL,
                    'idcicloatencion' => isset($row['idcicloatencion']) ? $row['idcicloatencion'] : NULL,
                    // 'renombreproducto' => isset($row['renombreproducto']) ? $row['renombreproducto'] : NULL,
                    
                    'created_at' => date('Y-m-d H:i:s'),
                    'id_created_at' => $this->objTtoken->my
                );

                $i = $i + 1;
            }

            \DB::table('ventadet')->insert($dataVentadet);
  
            //Actualizar citamedica
            //{ displayName: 'Estado', field: 'estadodocumento', width: '80' },. Excepto NC
            if (in_array($venta->iddocumentofiscal, [1,2,3,4,11]) ) {
                // AQUI PODRIA ESTAR EL ERROR DE IN
                foreach ($citasmedicas as $idcitamedica) {
                    \DB::table('citamedica')
                        ->where('idcitamedica', $idcitamedica)
                        ->update(['idventa' => $venta->idventa, 'idestadopago' => 71, 'idapertura' => $venta->idapertura]);
                } 
            }

            //NC
            if (in_array($venta->iddocumentofiscal, [13])) { 
                foreach ($citasmedicas as $idcitamedica) {                                 
                    $update = array(
                        'idventa' => null,
                        'idestadopago' => 72, //Pago pendiente
                        'updated_at' => date('Y-m-d H:i:s'),
                        'id_updated_at' => $this->objTtoken->my
                    );                    

                    $citamedica->grabarLogv2($idcitamedica, $this->objTtoken->my, $update);

                    \DB::table('citamedica')
                            ->where('idcitamedica', $idcitamedica)
                            ->update($update);                     
                }

                if (!empty($venta->idventaref) && ($venta->tiponotacredito === '1' ||  $venta->tiponotacredito === '2')) {
                    //Actualizar cicloautorizacion 
                    \DB::table('cicloautorizacion')
                        ->where('idventa', $venta->idventaref)
                        ->update(['idventa' => NULL, 'idestadoimpreso' => 83]); 
                        //Por facturar(antes por imprimir)
                }
            }

            //Actualizar documentoserie
            \DB::table('documentoserie')
                    ->where('iddocumentoserie', $documentoserie->iddocumentoserie)
                    ->update(array(
                        'numero' => $docnumero,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'id_updated_at' => $this->objTtoken->my
                    ));

            //Actualizar presupuesto  
            $puroTratamientosenCiclo = true;
            foreach ($idciclos as $idcicloatencion) {
                $presupuesto = presupuesto::where('idcicloatencion', '=', $idcicloatencion)->first();  

                if ($presupuesto) {
                    $presupuestodet = $presupuesto->presupuestodet($presupuesto->idpresupuesto);
                                        
                    $ventatotal = 0; 
                    foreach ($request['ventadet'] as $row) {
                        if (isset($row['idcicloatencion']) && !empty($row['idcicloatencion']) && 
                            $row['idcicloatencion'] === $idcicloatencion && empty($row['idcitamedica'])) 
                        {
                            // Costo de venta incluido **Acuenta** 
                            $ventatotal += $row['total'];     

                            // Actualizar presupuestodet 
                            foreach ($presupuestodet as $rowpres) {
                                if ($rowpres->idproducto === $row['idproducto']) 
                                {  

                                    //F,BV,RH,RI,RHE. Excepto NC
                                    if (in_array($venta->iddocumentofiscal, [1,2,3,4,11]) ) { 
                                        $cantpagada = $rowpres->cantpagada + $row['cantidad'];
                                    }

                                    //NC
                                    if (in_array($venta->iddocumentofiscal, [13])) {
                                        $cantpagada = $rowpres->cantpagada - $row['cantidad'];
                                    }

                                    \DB::table('presupuestodet')
                                         ->where(['idpresupuestodet' => $rowpres->idpresupuestodet])
                                         ->update(['cantpagada' => $cantpagada]);                                
                                    break;
                                }
                            }
                        } else {
                            $puroTratamientosenCiclo = false;
                        }
                    }

                    //Para comprobante factura, y que todos los conceptos de cobro pertenezcan a un solo ciclo exceptuando producto consulta médica que también tiene el codigo de ciclo de atencion.
                    if (count($idciclos) === 1 && $puroTratamientosenCiclo && $venta->iddocumentofiscal === 1) {
                        $ventatotal = $venta->total;
                    }

                    //F,BV,RH,RI,RHE. Excepto NC
                    if (in_array($venta->iddocumentofiscal, [1,2,3,4,11])) {  

                        if (isset($descuento) && (float) $descuento > 0 ) {
                            $ventatotal -= (float) $descuento * 1.18;
                        }                       

                        $montopago = $presupuesto->montopago + $ventatotal;
                    } 

                    //NC
                    if (in_array($venta->iddocumentofiscal, [13])) {

                        if (isset($descuento) && (float) $descuento > 0 ) {
                            $ventatotal -= (float) $descuento * 1.18;
                        } 

                        $montopago = $presupuesto->montopago - $ventatotal;
                    } 
                    
                    if ($montopago >= $presupuesto->total && $presupuesto->total > 0) {
                        $idestadopago = 68;
                    } else if ($montopago > 0 && $montopago < $presupuesto->total) {
                        $idestadopago = 67;
                    } else {
                        $idestadopago = 66;
                    }

                    $paramPresupuesto = array(
                        'updated_at' => date('Y-m-d H:i:s'),
                        'id_updated_at' => $this->objTtoken->my,
                        'montopago' => $montopago,
                        'montocredito' => $montopago,//Temporal hasta quitar de los JS, se debe usan montopago
                        'idestadopago' => $idestadopago 
                    ); 

                    $presupuesto->fill($paramPresupuesto);
                    $presupuesto->save(); 
                    $presupuesto->grabarLog($presupuesto->idpresupuesto, $this->objTtoken->my);
                }
            } 

            if (isset($request['ventafactura'])) {
                $dataVentafactura = [
                    'idventa' => $venta->idventa, 
                    'paciente' => isset($request['ventafactura']['paciente']) ? mb_strtoupper($request['ventafactura']['paciente'], 'UTF-8') : null, 
                    'programa' => isset($request['ventafactura']['programa']) ? mb_strtoupper($request['ventafactura']['programa'], 'UTF-8') : null, 
                    'titular' => isset($request['ventafactura']['titular']) ? mb_strtoupper($request['ventafactura']['titular'], 'UTF-8') : null, 
                    'diagnostico' => isset($request['ventafactura']['diagnostico']) ? mb_strtoupper($request['ventafactura']['diagnostico'], 'UTF-8') : null,                
                    'indicacion' => isset($request['ventafactura']['indicacion']) ? mb_strtoupper($request['ventafactura']['indicacion'], 'UTF-8') : null,
                    'deducible' => isset($request['ventafactura']['deducible']) ? mb_strtoupper($request['ventafactura']['deducible'], 'UTF-8') : null,
                    'coaseguro' => isset($request['ventafactura']['coaseguro']) ? mb_strtoupper($request['ventafactura']['coaseguro'], 'UTF-8') : null,
                    'empresa' => isset($request['ventafactura']['empresa']) ? mb_strtoupper($request['ventafactura']['empresa'], 'UTF-8') : null,
                    'autorizacion' => isset($request['ventafactura']['autorizacion']) ? mb_strtoupper($request['ventafactura']['autorizacion'], 'UTF-8') : null,
                ];

                \DB::table('ventafactura')->insert($dataVentafactura);
            } 

            // return $this->crearRespuesta('X', [200, 'info'], '', '', [$montopago, $ventatotal]);

        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        //Es necesario retornar "idventa" para pasar a la vista de impresion
        $cpeComprobante = $this->cpeComprobante($enterprise, $venta->idventa);
        $others = array( 
            'idventa' => $venta->idventa, 
            'cpe' => $cpeComprobante['comprobante'],
            'authentication' => $cpeComprobante['authentication']
        ); 

        return $this->crearRespuesta('Venta ha sido creado.', 201, '', '', $others);
    }     

    public function storefactura(Request $request, $enterprise) {
        
        $request = $request->all();
         
        $empresa = new empresa(); 
       
        $idempresa = $empresa->idempresa($enterprise);  

        //return $this->crearRespuesta('Suspensión de 1:00pm a 1:20pm', [200, 'info']);  

        $coaseguros = $empresa->coaseguros();

        $existeporcentaseguro = false;
        if(isset($request['ventafactura']['coaseguro'])) { 
            foreach($coaseguros as $row){
                if ((int)$request['ventafactura']['coaseguro'] === $row->valor) {
                    $existeporcentaseguro = true;
                }
            }

            if (!$existeporcentaseguro) {
                return $this->crearRespuesta('Porcentaje coaseguro no válido', [200, 'info']);
            }
        } 

        // return $this->crearRespuesta('Actualizar ERP y comunicarse con Sistemas 970 879 206.', [200, 'info']);

        //Documento serie         
        $documentoserie = \DB::table('documentoserie')
            ->where(array(
                    'identidad' => $request['venta']['idafiliado'], 
                    'iddocumentofiscal' => $request['venta']['iddocumentofiscal'],
                    'serie' => $request['venta']['serie']
            )) 
            ->whereNull('documentoserie.deleted')
            ->get()->all();

        if (count($documentoserie) > 1) {
            return $this->crearRespuesta('Existen documentos duplicados para afiliados.', [200, 'info'], '', '', $documentoserie);
        }

        //  1.- Validacion: No permitir numero existente.
        if ($this->validarNumeroExistente($request['venta']['idafiliado'], $request['venta']['iddocumentofiscal'], $request['venta']['serie'], (int) $request['venta']['serienumero'])) {
            return $this->crearRespuesta('N&uacute;mero de documento ya existe', [200, 'info']);
        }

        //Campos obligatorios
        $param = array(
            'idempresa' => $idempresa,
            'idsede' => $request['venta']['idsede'], 
            'idafiliado' => $request['venta']['idafiliado'], //$documentoserie->identidad,
            'iddocumentofiscal' => $request['venta']['iddocumentofiscal'], //$documentoserie->iddocumentofiscal,
            'serie' => $request['venta']['serie'], //$documentoserie->serie,
            'serienumero' => $request['venta']['serienumero'], //($documentoserie->numero + 1),
            'fecharegistro' => date('Y-m-d'),
            'horaregistro' => date('H:i:s'),
            'idcliente' => $request['venta']['idcliente'],            
            'fechaventa' => $this->formatFecha($request['venta']['fechaventa'], 'yyyy-mm-dd'),
            'horaventa' => date('H:i:s'),
            'anoventa' => date('Y'),
            'mesventa' => date('m'),
            'idmoneda' => 1,                       
            'movecon' => 0,                
            'idcicloatencion' => $request['venta']['idcicloatencion'],
            'deducible' => $request['venta']['deducible'],
            'coaseguro' => $request['venta']['coaseguro'],
            'descuento' => isset($request['venta']['descuento']) ? $request['venta']['descuento'] : NULL,         
            'subtotal' => $request['venta']['subtotal'],            
            'valorimpuesto' => $request['venta']['valorimpuesto'],
            'total' => $request['venta']['total'],
            'idestadodocumento' => $request['venta']['idestadodocumento'],//26,
            'idestadoseguro' => $request['venta']['idestadoseguro'],//78, 
            'descripcion' => $request['venta']['descripcion'],
            //Medios de pago
            'idmediopago' => $request['venta']['idmediopago'],
            'idnotacredito' => $request['venta']['idnotacredito'],
            'notacreditovalor' => $request['venta']['notacreditovalor'], 
            'idtarjetapri' => $request['venta']['idtarjetapri'],
            'tarjetapriope' => $request['venta']['tarjetapriope'],
            'tarjetaprimonto' => $request['venta']['tarjetaprimonto'],
            'idtarjetaseg' => $request['venta']['idtarjetaseg'],
            'tarjetasegope' => $request['venta']['tarjetasegope'],
            'tarjetasegmonto' => $request['venta']['tarjetasegmonto'],
            'partetipotarjeta' => $request['venta']['partetipotarjeta'],
            'parteopetarjeta' => $request['venta']['parteopetarjeta'],
            'partemontotarjeta' => $request['venta']['partemontotarjeta'],
            'parteefectivo' => $request['venta']['parteefectivo'],
            'cpecorreo' => isset($request['venta']['cpecorreo']) ? $request['venta']['cpecorreo'] : NULL, 
            'created_at'=> date('Y-m-d H:i:s'),
            'id_created_at'=> $this->objTtoken->my
        ); 

        if(isset($request['venta']['idcicloautorizacion']) && !empty($request['venta']['idcicloautorizacion'])) {
             $param['idcicloautorizacion'] = $request['venta']['idcicloautorizacion'];
        }

        if(isset($request['venta']['idpaciente']) && !empty($request['venta']['idpaciente'])) {
             $param['idpaciente'] = $request['venta']['idpaciente'];
        }

        if(isset($request['venta']['idmodelo']) && !empty($request['venta']['idmodelo'])) {
             $param['idmodelo'] = $request['venta']['idmodelo'];
        }
 

        \DB::beginTransaction();
        try {

            if (isset($request['ventafactura']['apellidopat']) && isset($request['ventafactura']['apellidomat']) && isset($request['ventafactura']['nombre'])) { 

                $request['ventafactura']['apellidopat'] = trim($request['ventafactura']['apellidopat']);
                $request['ventafactura']['apellidomat'] = trim($request['ventafactura']['apellidomat']);
                $request['ventafactura']['nombre'] = trim($request['ventafactura']['nombre']);

                $espacio = !empty($request['ventafactura']['apellidomat']) ? ' ' : '';

                $entidad = $request['ventafactura']['apellidopat'] . $espacio . 
                           $request['ventafactura']['apellidomat'] . ', ' . $request['ventafactura']['nombre'];

                $update = array(
                    'apellidopat' => $request['ventafactura']['apellidopat'], 
                    'apellidomat' => $request['ventafactura']['apellidomat'],
                    'nombre' => $request['ventafactura']['nombre'],
                    'entidad' => $entidad 
                ); 

                // return $this->crearRespuesta('sTOP.', [200, 'info'], '', '', $update);

                \DB::table('entidad') 
                    ->where(array('identidad' => $request['venta']['idpaciente'])) 
                    ->update($update); 
            } 

            if (isset($request['cicloatencion']['idcicloatencion'])) {
                cicloatencion::where('idcicloatencion', $request['cicloatencion']['idcicloatencion'])
                      ->whereNull('deleted')  
                      ->update($request['cicloatencion']);
            }

            $rowentidad = \DB::table('entidad')
                    ->where(array('identidad' => $request['venta']['idpaciente']))
                    ->first();

            $venta = venta::create($param);
            $dataVentafactura = [
                'idventa' => $venta->idventa,
                'hc' => mb_strtoupper($request['ventafactura']['hc'], 'UTF-8'),
                //'paciente' => $request['ventafactura']['paciente'],
                'paciente' => mb_strtoupper($rowentidad->entidad, 'UTF-8'),
                'seguroplan' => mb_strtoupper($request['ventafactura']['seguroplan'], 'UTF-8'),
                'titular' => mb_strtoupper($request['ventafactura']['titular'], 'UTF-8'),
                'empresa' => mb_strtoupper($request['ventafactura']['empresa'], 'UTF-8'),
                'diagnostico' => mb_strtoupper($request['ventafactura']['diagnostico'], 'UTF-8'),
                'zona' => isset($request['ventafactura']['zona']) ? mb_strtoupper($request['ventafactura']['zona'], 'UTF-8') : null,
                'indicacion' => mb_strtoupper($request['ventafactura']['indicacion'], 'UTF-8'),
                'autorizacion' => mb_strtoupper($request['ventafactura']['autorizacion'], 'UTF-8'),
                'programa' => mb_strtoupper($request['ventafactura']['programa'], 'UTF-8'),
                'deducible' => mb_strtoupper($request['ventafactura']['deducible'], 'UTF-8'),
                'coaseguro' => mb_strtoupper($request['ventafactura']['coaseguro'], 'UTF-8'),
                'ciclo' => mb_strtoupper($request['venta']['ciclo'], 'UTF-8'),
                'letra' => $this->num2letras((float) $request['venta']['total']),
                'consulta' => mb_strtoupper($request['ventafactura']['consulta'], 'UTF-8'),
                'sesiones' => mb_strtoupper($request['ventafactura']['sesiones'], 'UTF-8'),
                'totaldetto' => mb_strtoupper($request['ventafactura']['totaldetto'], 'UTF-8'),
                'totalaseguradora' => mb_strtoupper($request['ventafactura']['totalaseguradora'], 'UTF-8'),
                'pcttotalcoaded' => mb_strtoupper($request['ventafactura']['pcttotalcoaded'], 'UTF-8')
            ]; 
            \DB::table('ventafactura')->insert($dataVentafactura);

            $dataVentadet = [];
            foreach ($request['ventadet'] as $row) {
                $dataVentadet[] = array(
                    'idventa' => $venta->idventa,
                    'idproducto' => $row['idproducto'],
                    'nombre' => isset($row['nombreproducto']) ? $row['nombreproducto'] : NULL, 
                    'descripcion' => isset($row['descripcion']) ? $row['descripcion'] : NULL, 
                    // 'renombreproducto' => $row['descripcion'],
                    'cantidad' => $row['cantidad'],
                    'valorunit' => $row['valorunit'], 
                    'preciounit' => $row['preciounit'], 
                    'valorventa' => $row['valorventa'], 
                    'montototalimpuestos' => $row['montototalimpuestos'], 
                    'total' => $row['total'], 
                    'created_at' => date('Y-m-d H:i:s'),
                    'id_created_at' => $this->objTtoken->my                    
                );
            }
            \DB::table('ventadet')->insert($dataVentadet);

            if (isset($request['venta']['idcicloautorizacion'])) { 
                $update = array();
                $update['idventa'] = $venta->idventa;
                $update['parentesco'] = $request['ventafactura']['titular'];
                $update['nombrecompania'] = $request['ventafactura']['empresa'];
                $update['deducible'] = $request['ventafactura']['deducible'];
                $update['updated_at'] = date('Y-m-d H:i:s');
                $update['id_updated_at'] = $this->objTtoken->my;
                $update['idestadoimpreso'] = 84; //impreso 
                $update['idaseguradoraplan'] = $request['ventafactura']['idaseguradoraplan'];

                $aseguradoraplan = \DB::table('aseguradoraplan')
                    ->where(array('idaseguradoraplan' => $request['ventafactura']['idaseguradoraplan']))
                    ->first();

                 $update['idaseguradora'] = $aseguradoraplan->idaseguradora;

                foreach($coaseguros as $row){
                    if ((int)$request['ventafactura']['coaseguro'] === $row->valor) {
                        $update['idcoaseguro'] = $row->idcoaseguro;
                        break;
                    }
                } 

                cicloautorizacion::where('idcicloautorizacion', $request['venta']['idcicloautorizacion'])
                              ->whereNull('deleted')  
                              ->update($update);
            }

            //Actualizacion de DocumentoSerie
            $paramDocSerie = [];
            $paramDocSerie['numero'] = $request['venta']['serienumero'] ;
            $paramDocSerie['updated_at'] = date('Y-m-d H:i:s');
            $paramDocSerie['id_updated_at'] = $this->objTtoken->my;
            \DB::table('documentoserie')
                //->where('iddocumentoserie', $documentoserie->iddocumentoserie)
                ->where(array(
                    'identidad' => $request['venta']['idafiliado'], 
                    'iddocumentofiscal' => $request['venta']['iddocumentofiscal'],
                    'serie' => $request['venta']['serie'] 
                )) 
                ->update($paramDocSerie); 

        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();
   
        //Es necesario retornar "idventa" para pasar a la vista de impresion
        // $cpeComprobante = $this->cpeComprobante($enterprise, $venta->idventa);
        $others = array( 
            'idventa' => $venta->idventa
            // 'cpe' => $cpeComprobante['comprobante'],
            // 'authentication' => $cpeComprobante['authentication']
        ); 
 
        return $this->crearRespuesta('Venta ha sido creado.', 201, '', '', $others);
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

    public function correoenvio(Request $request, $enterprise, $id){

        $objVenta = new venta();

        $venta = $objVenta->venta($id);

        $request = $request->all();  

        $filePDF = $this->pathImg . $venta->numerodocafiliado . '-' . $venta->codigosunat . '-' . $venta->serie . '-' . $venta->serienumero . '.pdf';
        $fileXML = $this->pathImg . $venta->numerodocafiliado . '-' . $venta->codigosunat . '-' . $venta->serie . '-' . $venta->serienumero . '.xml';
         
        if (!file_exists($filePDF)) {
            return $this->crearRespuesta('PDF no existe. Comunicarse con sistemas.', [200, 'info']);
        }

        if (!file_exists($fileXML)) {
            return $this->crearRespuesta('XML no existe. Comunicarse con sistemas.', [200, 'info']);
        }
   
        if (empty($request['correo'])) {            
            return $this->crearRespuesta('Correo inválido', [200, 'info']);
        }

        try{
            // for ($i=0; $i < 20; $i++) { 
            \Log::info(print_r(date('H:i:s') . ' inicio.', true)); 
            $return = Mail::to($request['correo'])->send(new InvoiceSend($venta, $filePDF, $fileXML)); 
            \Log::info(print_r(date('H:i:s') . ' fin ' . $request['correo'], true)); 
            // } 
        } 
        catch(\Exception $e){            
            \Log::info(print_r($e->getMessage(), true)); 
            return $this->crearRespuesta('Algo anda mal', [200, 'info']);
        }
        
        return $this->crearRespuesta('Enviado a: ' . $request['correo'], 200, '','', $venta);
    }

    public function anular(Request $request, $enterprise, $id)
    {
        $empresa = new empresa();
        $objSede = new sede(); 
        $objCicloatencion = new cicloatencion();
        $objCiclomovimiento = new ciclomovimiento();

        //return $this->crearRespuesta('Suspensión de 1:00pm a 1:20pm', [200, 'info']);

        $venta = venta::find($id);

        $request = $request->all(); 
        
        $idempresa = $empresa->idempresa($enterprise);          

        //VALIDACIONES

        //1.- Validar que no tenga NC

        if ($venta) {
            $ventaref = venta::where('idventaref', $id)->first(); 
            if ($ventaref) {
                return $this->crearRespuesta('Venta no puede anularse, tiene Nota de crédito N° '.$ventaref->serie.'-'.$ventaref->serienumero, [200, 'info']);
            }            
        } 

        //2.- Fecha máxima para anular
        $fechaMaxima = strtotime('-5 day', strtotime(date('Y-m-d')));
        $fechausuario = strtotime($venta->fechaventa);

        // return $this->crearRespuesta(date('Y-m-d', $fechausuario).'|'.date('Y-m-d', $fechaMaxima), [200, 'info']);

        if ($fechausuario < $fechaMaxima && in_array($venta->iddocumentofiscal, [1,2,13])) {
            return $this->crearRespuesta('No procede, transcurrió más de 5 dias desde su emisión', [200, 'info']);
        } 

        if (date('m', $fechausuario) !==  date('m', $fechaMaxima) && in_array($venta->iddocumentofiscal, [1,2,13])) {
            //return $this->crearRespuesta('No procede, emisión es de un mes distinto al actual', [200, 'info']);
        }

        // return $this->crearRespuesta('XD'. '=>' . $venta->fechaventa, [200, 'info'], '', '', $venta); 

        if ($venta) {
            $ventadet = $venta->ventadet($venta->idventa);
            
            $idciclos = [];
            foreach ($ventadet as $row) {
                if (isset($row->idcicloatencion) && 
                        !empty($row->idcicloatencion) && 
                            !in_array($row->idcicloatencion, $idciclos)) 
                {
                    $idciclos[] = $row->idcicloatencion;
                }
            }  

            \DB::beginTransaction();
            try {

                //Actualizar presupuesto  
                $puroTratamientosenCiclo = true;
                foreach ($idciclos as $idcicloatencion) { 
                    $presupuesto = presupuesto::where('idcicloatencion', '=', $idcicloatencion)->first(); 
                    
                    if ($presupuesto && $venta->idestadodocumento === 27) {
                        
                        $presupuestodet = $presupuesto->presupuestodet($presupuesto->idpresupuesto);

                        $ventatotal = 0; 
                        foreach ($ventadet as $row) {

                            if(isset($row->idcicloatencion) && !empty($row->idcicloatencion) && $row->idcicloatencion === $idcicloatencion && empty($row->idcitamedica)) {
                                                                  
                                $ventatotal += $row->total;                                     

                                // Actualizar presupuestodet 
                                foreach ($presupuestodet as $rowpres) {
                                    if ($rowpres->idproducto === $row->idproducto) {
                                        //F,BV,RH,RI,RHE. Excepto NC
                                        if (in_array($venta->iddocumentofiscal, [1,2,3,4,11]) ) { 
                                            $cantpagada = $rowpres->cantpagada - $row->cantidad;
                                        }

                                        //NC
                                        if (in_array($venta->iddocumentofiscal, [13])) {
                                            $cantpagada = $rowpres->cantpagada + $row->cantidad;
                                        }

                                        \DB::table('presupuestodet')
                                             ->where(['idpresupuestodet' => $rowpres->idpresupuestodet])
                                             ->update(['cantpagada' => $cantpagada]);                                
                                        break;
                                    }
                                }
                            } else {
                                $puroTratamientosenCiclo = false;
                            }
                        }     

                        //Para comprobante factura, y que todos los conceptos de cobro pertenezcan a un solo ciclo exceptuando producto consulta médica que también tiene el codigo de ciclo de atencion.
                        if (count($idciclos) === 1 && $puroTratamientosenCiclo && $venta->iddocumentofiscal === 1) {                            
                            $ventatotal = $venta->total;
                        }        

                        //F,BV,RH,RI,RHE. Excepto NC
                        if (in_array($venta->iddocumentofiscal, [1,2,3,4,11])) {   
                            
                            if (isset($descuento) && (float) $descuento > 0 ) {
                                $ventatotal += (float) $descuento * 1.18;
                            }   

                            $montopago = $presupuesto->montopago - $ventatotal;
                        }

                        //NC
                        if (in_array($venta->iddocumentofiscal, [13])) {

                            if (isset($descuento) && (float) $descuento > 0 ) {
                                $ventatotal += (float) $descuento * 1.18;
                            }
                            
                            $montopago = $presupuesto->montopago + $ventatotal;
                        }
                
                        if ($montopago >= $presupuesto->total && $presupuesto->total > 0) {
                            $idestadopago = 68;
                        } else if ($montopago > 0 && $montopago < $presupuesto->total) {
                            $idestadopago = 67;
                        } else {
                            $idestadopago = 66;
                        }

                        $presupuesto->fill(array(
                            'montopago' => $montopago,
                            'montocredito' => $montopago,
                            'idestadopago' => $idestadopago,
                            'id_updated_at' => $this->objTtoken->my,
                            'updated_at' => date('Y-m-d H:i:s')                            
                        ));

                        // Actualizar presupuesto
                        $presupuesto->save();
                        $presupuesto->grabarLog($presupuesto->idpresupuesto, $this->objTtoken->my); 
                    }
                } 
                 
                //Actualizar citamedica
                //F,BV,RH,RI,RHE. Excepto NC
                if (in_array($venta->iddocumentofiscal, [1,2,3,4,11]) ) { 
                    foreach ($ventadet as $row) { 
                        if (!empty($row->idcitamedica)) { 
                            $update = array(
                                'idventa' => null,
                                'idestadopago' => 72,
                                'updated_at' => date('Y-m-d H:i:s'),
                                'id_updated_at' => $this->objTtoken->my
                            );

                            \DB::table('citamedica')
                                    ->where('idcitamedica', $row->idcitamedica)
                                    ->update($update);
                        }
                    }  
                }
                    
                //NC
                if (in_array($venta->iddocumentofiscal, [13]) ) { 
                    foreach ($ventadet as $row) { 
                        if (!empty($row->idcitamedica)) { 
                            $update = array(
                                'idventa' => $venta->idventaref,
                                'idestadopago' => 71,
                                'updated_at' => date('Y-m-d H:i:s'),
                                'id_updated_at' => $this->objTtoken->my
                            );

                            \DB::table('citamedica')
                                    ->where('idcitamedica', $row->idcitamedica)
                                    ->update($update);
                        }
                    }  
                }

                //Actualiza venta                
                $auditoria = [ 
                    'idestadodocumento' => '28',
                    'updated_at' => date('Y-m-d H:i:s'),
                    'id_updated_at' => $this->objTtoken->my,
                    'idventaanulado' => $this->objTtoken->my
                ];

                //Si es caja abierta, se elimina movimiento economico, sino NO porque es venta de una caja cerrada.
                //NC no tiene idapertura
                if ($venta->idapertura) { 
                    $apertura = $objSede->apertura(array('apertura.idapertura' => $venta->idapertura, 'apertura.estado' => '1'));
                    if(!empty($apertura))
                        $auditoria['movecon'] = '0'; 
                }

                if (isset($request['venta']['descripcion'])) 
                    $auditoria['descripcion'] = $request['venta']['descripcion'];

                $venta->fill($auditoria);
                $venta->save();

                if (in_array($venta->iddocumentofiscal, [1,2,3,4,11]) ) {
                    //Al generar NC(tipo 1,2) se anula el cicloautorizacion
                    //Actualizar cicloautorizacion 
                    \DB::table('cicloautorizacion')
                        ->where('idventa', $id)
                        ->update(['idventa' => NULL, 'idestadoimpreso' => 83]); 
                        //Por facturar(antes por imprimir)
                }

            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            //imoprtante $id, sirve para enviar a sunat despues de eliminado.
            return $this->crearRespuesta('Documento de venta ha sido anulado.', 200, '', '', $id);
        }

        return $this->crearRespuestaError('Documento de venta no encotrado', 404);
    }  

    public function destroy(Request $request, $enterprise, $id) {
        //Al eliminar una venta
        /* deleted = '1' para tabla 'venta' y 'ventadet', 
         * libera idcitamedica en tabla 'citamedica', 
         * usado = '0' en tabla 'cupondescuento',
         * Del tipo tratamiento: deleted= '1' para tabla 'venta' y 'ventadet', 
         * Presupuesto:
         * usado = null, cantpagada = cantpagada - n en tabla para tabla 'presupuestodet'

         * venta.idestadodocumento 26:Pago pendiente 27:Pagado 28:Anulado
         */
        $objSede = new sede(); 

        $venta = venta::find($id);
        $request = $request->all();

        //return $this->crearRespuesta('Suspensión de 1:00pm a 1:20pm', [200, 'info']);

        //1.- Validacion que no este eliminado. 
        if ($venta && $venta->deleted === '1' ) {
            return $this->crearRespuesta('Documento no existe.', [200, 'info']);
        } 

        //2.- Validar que no tenga NC
        if ($venta) {
            $ventaref = venta::where('idventaref', $id)->first(); 
            if ($ventaref) {
                return $this->crearRespuesta('Venta no puede eliminarse, tiene Nota de crédito N° '.$ventaref->serie.'-'.$ventaref->serienumero, [200, 'info']);
            }            
        } 

        if ($venta->iddocumentofiscal === 13) {             
            return $this->crearRespuesta('Venta no puede eliminase nota de crédito', [200, 'info']);            
        }

        //cpeemision puede ser "0" u otro valor.
        if (isset($venta->cpeemision) || isset($venta->cpeanulacion)) {
            return $this->crearRespuesta('Comprobante electrónico no puede eliminarse', [200, 'info']);            
        }

        // return $this->crearRespuesta('XD', [200, 'info']);
        // return $this->crearRespuesta('XD', [200, 'info'], '', '', $venta);
 
        //3.- Validacion que caja este abierta.
        //Fabiola quiere eliminar 10.04.2017 
        if (!empty($venta->idapertura)) {
            
            $apertura = $objSede->apertura(array('apertura.idapertura' => $venta->idapertura, 'apertura.estado' => '1'));
            
            if (empty($apertura) && isset($request['forzareliminacion']) && $request['forzareliminacion'] === 0) {
                return $this->crearRespuesta('No puede eliminarse. Caja se encuentra cerrado.', [200, 'info'], '', '', $request['forzareliminacion']);
            }
        }

        if ($venta) {
            
            $ventadet = $venta->ventadet($venta->idventa);

            $idciclos = []; 
            foreach ($ventadet as $row) {
                if (isset($row->idcicloatencion) && 
                        !empty($row->idcicloatencion) && 
                            !in_array($row->idcicloatencion, $idciclos)) 
                {
                    $idciclos[] = $row->idcicloatencion;
                }
            } 

            \DB::beginTransaction();

            try {

                //Actualizar presupuesto  
                $puroTratamientosenCiclo = true;
                foreach ($idciclos as $idcicloatencion) { 
                    $presupuesto = presupuesto::where('idcicloatencion', '=', $idcicloatencion)->first(); 
                    
                    if ($presupuesto && $venta->idestadodocumento === 27) {
                        
                        $presupuestodet = $presupuesto->presupuestodet($presupuesto->idpresupuesto);

                        $ventatotal = 0; 
                        foreach ($ventadet as $row) {

                            if(isset($row->idcicloatencion) && !empty($row->idcicloatencion) && $row->idcicloatencion === $idcicloatencion && empty($row->idcitamedica)) {                                                                
                                $ventatotal += $row->total;                                    

                                // Actualizar presupuestodet 
                                foreach ($presupuestodet as $rowpres) {
                                    if ($rowpres->idproducto === $row->idproducto) 
                                    {  
                                        \DB::table('presupuestodet')
                                             ->where(['idpresupuestodet' => $rowpres->idpresupuestodet])
                                             ->update(['cantpagada' => $rowpres->cantpagada - $row->cantidad]);                                
                                        break;
                                    }
                                }
                            } else {
                                $puroTratamientosenCiclo = false;
                            }
                        }      

                        //Para comprobante factura, y que todos los conceptos de cobro pertenezcan a un solo ciclo exceptuando producto consulta médica que también tiene el codigo de ciclo de atencion.
                        if (count($idciclos) === 1 && $puroTratamientosenCiclo && $venta->iddocumentofiscal === 1) {                            
                            $ventatotal = $venta->total;
                        } 

                        $montopago = $presupuesto->montopago - $ventatotal;
                
                        if ($montopago >= $presupuesto->total && $presupuesto->total > 0) {
                            $idestadopago = 68;
                        } else if ($montopago > 0 && $montopago < $presupuesto->total) {
                            $idestadopago = 67;
                        } else {
                            $idestadopago = 66;
                        }

                        $presupuesto->fill(array(
                            'montopago' => $montopago,
                            'montocredito' => $montopago,
                            'idestadopago' => $idestadopago,
                            'id_updated_at' => $this->objTtoken->my,
                            'updated_at' => date('Y-m-d H:i:s')                            
                        ));
                        
                        // Actualizar presupuesto
                        $presupuesto->save();
                        $presupuesto->grabarLog($presupuesto->idpresupuesto, $this->objTtoken->my); 
                    }
                } 

                //Actualizar citamedica 
                if ($venta->idestadodocumento === 27) {
                    foreach ($ventadet as $row) { 
                        if (!empty($row->idcitamedica)) { 
                            $update = array(
                                'idventa' => null,
                                'idestadopago' => '72',
                                'updated_at' => date('Y-m-d H:i:s'),
                                'id_updated_at' => $this->objTtoken->my
                            );

                            \DB::table('citamedica')
                                    ->where('idcitamedica', $row->idcitamedica)
                                    ->update($update);
                        }
                    }
                }

                // Actualizar Venta 
                $update = array(
                    'deleted_at' => date('Y-m-d H:i:s'),
                    'id_deleted_at' => $this->objTtoken->my,
                    'deleted' => '1',
                    'motivo' => isset($request['motivo']) ? $request['motivo'] : null
                );  

                $venta->fill($update);
                $venta->save(); 

                // Actualizar Ventadet 
                \DB::table('ventadet')
                        ->where('idventa', $id)
                        ->update([
                            'deleted_at' => date('Y-m-d H:i:s'),
                            'id_deleted_at' => $this->objTtoken->my,
                            'deleted' => '1'
                        ]);  

                // Actualizar ventafactura 
                \DB::table('ventafactura')
                    ->where('idventa', $id)
                    ->update(['deleted' => '1' ]); 

                // Actualizar cicloautorizacion
                // caso de facturas a seguros, siempre están con 26.
                if ($venta->idestadodocumento === 26 || $venta->idestadodocumento === 27) {
                    \DB::table('cicloautorizacion')
                        ->where('idventa', $id)
                        ->update(['idventa' => NULL, 'idestadoimpreso' => 83]); //Por facturar(antes por imprimir)
                }
                

            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Documento de venta ha sido eliminado.', 200, '', '', $id);
        }
        return $this->crearRespuestaError('Documento de venta no encotrado', 404);
    } 

    private function getPrecio($tarifario, $idproducto, $idsede, $tipo) {

        $precio = NULL;

        foreach ($tarifario as $row) {
            //Precio de tarifario con SEGURO NO CUBIERTO
            if ($row->idproducto === $idproducto && $row->idsede === $idsede && $tipo === 'SINCOBERTURA') {
                $precio = $row->sscoref;
                break;
            }

            //Precio de tarifario PARTICULAR
            if ($row->idproducto === $idproducto && $row->idsede === $idsede && $tipo === 'PARTICULAR') {
                $precio = $row->partref;
                break;
            }
        }

        return $precio;
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

            \DB::table('citamedica')->where(['idcitamedica' => $row->idcitamedica])->update(['presupuesto' => $row->presupuesto]);
        }        
    }

    public function updatenumerodocumento(Request $request, $enterprise, $id) {

        $request = $request->all();

        $documentoserie = \DB::table('documentoserie')->where('iddocumentoserie', $id)->first();

        if (empty($documentoserie)) {
            return $this->crearRespuesta('No existe documento asociado a serie.', [200, 'info']);
        }

        $param = array(
            'idafiliado' => $documentoserie->identidad,
            'iddocumentofiscal' => $documentoserie->iddocumentofiscal,
            'serie' => $documentoserie->serie,
            'serienumero' => (int) $request['numero']            
        );

        $documento = \DB::table('venta')->where($param)->whereNull('venta.deleted')->first();
        if ($documento) {
            return $this->crearRespuesta('N&uacute;mero de documento ya existe', [200, 'info']);
        }

        $update = array(
            'numero' => ((int) $request['numero'] - 1) 
        );

        if(isset($request['idsede'])) {
            $update['idsede']  = $request['idsede'];
        }

        \DB::table('documentoserie')->where('iddocumentoserie', $id)    
        ->update($update);

        return $this->crearRespuesta('N&uacute;mero cambiado a ' . ((int) $request['numero']), 200);
    }

    public function updatenumerodocventa(Request $request, $enterprise, $id) {
 

        $request = $request->all();

        $venta = \DB::table('venta')->where('idventa', $id)->first(); 

        if (empty($venta)) {
            return $this->crearRespuesta('No existe documento de venta.', [200, 'info']);
        }  

        //  1.- Validacion: No permitir numero existente. 
        if ($this->validarNumeroExistente($request['idafiliado'], $request['iddocumentofiscal'], $request['serie'], (int) $request['serienumero'], $id)) {
            return $this->crearRespuesta('N&uacute;mero de documento ya existe', [200, 'info']);
        }
 
        $fechaventa = $this->formatFecha($request['fechaventa'], 'yyyy-mm-dd');

        $update = array(
            'fechaventa' => $fechaventa,            
            'idafiliado' => $request['idafiliado'],
            'iddocumentofiscal' => $request['iddocumentofiscal'],
            'serie' => $request['serie'],
            'serienumero' => (int) $request['serienumero'] 
        );

        $update['updated_at'] = date('Y-m-d H:i:s');
        $update['id_updated_at'] = $this->objTtoken->my;

        if (isset($request['idmediopago']))
            $update['idmediopago'] = $request['idmediopago'];
        if (isset($request['idapertura']))
            $update['idapertura'] = $request['idapertura'];
        if (isset($request['revision']))
            $update['revision'] = $request['revision'];
        if (isset($request['revisioncomentario']))
            $update['revisioncomentario'] = $request['revisioncomentario'];
        if (isset($request['control']))
            $update['control'] = $request['control'];
        if (isset($request['controlcomentario']))
            $update['controlcomentario'] = $request['controlcomentario'];
        if (isset($request['tarjetapriope']))
            $update['tarjetapriope'] = $request['tarjetapriope'];
        if (isset($request['tarjetasegope']))
            $update['tarjetasegope'] = $request['tarjetasegope'];  
        if (isset($request['movecon']))
            $update['movecon'] = $request['movecon']; 

        if (isset($update['revision']) && ($update['revision'] != $venta->revision or $update['revisioncomentario'] != $venta->revisioncomentario)){
               $update['identidadrevision'] = $this->objTtoken->my; 
        }

        if (isset($update['control']) && ($update['control'] != $venta->control or $update['controlcomentario'] != $venta->controlcomentario)){
               $update['identidadctrol'] = $this->objTtoken->my; 
               $update['fechactrol'] = date('Y-m-d');  
        }

        //
        if (isset($request['cambiarmediopago']) && $request['cambiarmediopago'] === '1') {
            $update['idtarjetapri'] = $request['idtarjetapri']; 
            $update['tarjetapriope'] = $request['tarjetapriope']; 
            $update['tarjetaprimonto'] = $request['tarjetaprimonto'];         
            $update['idtarjetaseg'] = $request['idtarjetaseg'];         
            $update['tarjetasegope'] = $request['tarjetasegope'];         
            $update['tarjetasegmonto'] = $request['tarjetasegmonto'];        
            $update['partetipotarjeta'] = $request['partetipotarjeta'];        
            $update['parteopetarjeta'] = $request['parteopetarjeta'];    
            $update['partemontotarjeta'] = $request['partemontotarjeta'];    
            $update['parteefectivo'] = $request['parteefectivo'];
        }
        
        \DB::beginTransaction();
        try {

            //Actualizar citamedica
            \DB::table('venta')->where('idventa', $id)->update($update);

            //Actualizar citamedica
            $citamedica = \DB::table('citamedica')->where(['idventa' => $id])->whereNull('citamedica.deleted')->first();

            if($citamedica && isset($request['idapertura'])){
                \DB::table('citamedica')
                      ->where('idcitamedica', $citamedica->idcitamedica)
                      ->update(array('idapertura' => $request['idapertura']));
            }

            // return $this->crearRespuesta('N&uacute;mero de documento ya existe', [200, 'info'], '', '', [$update, $citamedica ]);

        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta('Documento de venta ha sido editado.', 200);
    }

    public function update(Request $request, $enterprise, $id) { 

        $request = $request->all();

        $venta = \DB::table('venta')->where('idventa', $id)->first(); 

        if (empty($venta)) {
            return $this->crearRespuesta('No existe documento de venta.', [200, 'info']);
        }   
        
        // return $this->crearRespuesta('Vacio', [200, 'info'], '', '', gettype($request['idestadodocumento']));

        $update = array(
            // 'fechaventa' => $this->formatFecha($request['fechaventa'], 'yyyy-mm-dd'), 
            'idestadodocumento' => $request['idestadodocumento'],
            'idestadoseguro' => $request['idestadoseguro'],
            'descripcion' => $request['descripcion'] 
        );

        if (//$update['fechaventa'] != $venta->fechaventa or  
           $update['idestadodocumento'] != $venta->idestadodocumento or 
           $update['idestadoseguro'] != $venta->idestadoseguro or 
           $update['descripcion'] != $venta->descripcion){
               $update['updated_at'] = date('Y-m-d H:i:s');
               $update['id_updated_at'] = $this->objTtoken->my;
        }
        
        \DB::beginTransaction();
        try { 
            //Actualizar citamedica
            \DB::table('venta')->where('idventa', $id)->update($update); 

            //idestadodocumento 28:Anulado
            if($venta->iddocumentofiscal === 1 && $request['idestadodocumento'] === 28) { 

                \DB::table('cicloautorizacion')
                        ->where('idventa', $id)
                        ->update(['idventa' => NULL, 'idestadoimpreso' => 83]); //Por facturar(antes por imprimir)
            }

        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta('Documento de venta ha sido editado.', 200);
    }
    
    public function updateTalonario(Request $request, $enterprise, $id) {

        $sede = sede::find($id);

        $request = $request->all();

        $documentoserieUpdate = [];
        $documentofiscalIn = [];
        foreach ($request['documentoserie'] as $row) {
            if ($row['uso'] === '1') {
                if (!in_array($row['iddocumentofiscal'], $documentofiscalIn)) {
                    $documentofiscalIn[] = $row['iddocumentofiscal'];
                }

                $tmp = array('uso' => $row['uso']);
                $tmp['updated_at'] = date('Y-m-d H:i:s');
                $tmp['id_updated_at'] = $this->objTtoken->my;

                $documentoserieUpdate[] = array(
                    'data' => $tmp,
                    'where' => ['iddocumentoserie' => $row['iddocumentoserie']]
                );
            }
        }

        if ($sede) {
            \DB::beginTransaction();
            try {

                foreach ($documentofiscalIn as $iddocumentofiscal) {
                    \DB::table('documentoserie')
                            ->where('idsede', $id)
                            ->where('iddocumentofiscal', $iddocumentofiscal)
                            ->whereNull('deleted')
                            ->update(['uso' => '0']);
                }

                foreach ($documentoserieUpdate as $fila) {
                    \DB::table('documentoserie')->where($fila['where'])->update($fila['data']);
                }
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Talonario ha sido editado', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una sede', 404);
    }

    public function updateCpe(Request $request, $enterprise, $id) {

        $empresa = new empresa();

        $request = $request->all();

        $idempresa = $empresa->idempresa($enterprise); 

        $venta = \DB::table('venta')->where('idventa', $id)->whereNull('venta.deleted')->first();


        // Validacions
        // 1.- 
        if (empty($venta)) {
            return $this->crearRespuesta('No existe documento.', [200, 'info']);
        } 

        // 2.- Si ya tiene ticket, no debemos modificar ticket.
        if (isset($request['ticket']) && !empty($venta->cpeticket)) {
            return $this->crearRespuesta('Venta tiene ticket de anulación.', [200, 'info']);
        }

        $update = array();

        if (isset($request['cpeemision'])) {
            $update['cpeemision']  = $request['cpeemision'];
        }

        if (isset($request['cpeanulacion'])) {
            $update['cpeanulacion']  = $request['cpeanulacion'];
        }

        if (isset($request['cpemensaje'])) {
            $update['cpemensaje']  = $request['cpemensaje'];
        }

        if (isset($request['ticket']) && isset($request['ticket']['cpeticket'])) {
            $update['cpeticket']  = $request['ticket']['cpeticket'];
        }

        if (empty($update)) {
            return $this->crearRespuesta('No hay valores a actualizar.', [200, 'info']);
        }

        \DB::beginTransaction();
        try {
            \DB::table('venta')->where('idventa', $id)    
                ->update($update);

            if (isset($request['ticket']) && isset($request['ticket']['cpeticket'])) { 
                \DB::table('serienumero')  
                    ->where(array(
                        'serienumero.idempresa' => $idempresa,
                        'serienumero.idafiliado' => $venta->idafiliado,
                        'serienumero.documento' => $request['ticket']['cpetipo'] 
                    ))
                    ->update(array( 
                        'serienumero.numero' => $request['ticket']['cpenumero']
                    )); 
            }
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta('Comprobante actualizado.', 200);
    }

    public function cpeemision($enterprise, $id, $return = false) {

        $comprobante = $this->cpeComprobante($enterprise, $id); 

        $fileNamePdf = NULL;

        if (isset($comprobante) && $comprobante['cpeemision'] !== '0') {      

            $data = $comprobante['comprobante'];
            $telefono = $comprobante['telefono'];
            $cpecorreo = $comprobante['cpecorreo'];
            $idafiliado = $comprobante['idafiliado'];

            $authentication = $comprobante['authentication'];
            $archivoEmision = $this->archivoFe($data);
            // \Log::info(print_r($archivoEmision['nombreArchivo'] . '.json', true));
            // \Log::info(print_r($data, true));
            $dataEmi = array(
                'customer' => array(
                    'username' => $archivoEmision['numeroDocId'] . $authentication['cpeuser'],
                    'password' => $authentication['cpepassword']
                ),
                'fileName' => $archivoEmision['nombreArchivo'] . '.json',
                'fileContent' => base64_encode(json_encode($data))
            );

            $dataQRXml = array(
                'user' => array(
                    'username' => $archivoEmision['numeroDocId'] . $authentication['cpeuser'], 
                    'password' => $authentication['cpepassword'] 
                ),
                'codCPE' => $archivoEmision['tipo'],
                'numSerieCPE' => $archivoEmision['serie'],
                'numCPE' => $archivoEmision['numero']
            );
            
            // Parametros
            $header = array(
                'Content-Type: application/json'
            );

            if (true) {
                $urlEmi = 'https://www.escondatagate.net/wsParser_2_1/rest/parserWS';
                $urlQR  = 'https://www.escondatagate.net/wsBackend/clients/getPdfQRCode';
                $urlXml = 'https://www.escondatagate.net/wsBackend/clients/getDocumentXML';
            } else {
                $urlEmi = 'https://calidad.escondatagate.net/wsParser_2_1/rest/parserWS';
                $urlQR  = 'https://calidad.escondatagate.net/wsBackend/clients/getPdfQRCode';
                $urlXml = 'https://calidad.escondatagate.net/wsBackend/clients/getDocumentXML';
            }

            $dataParamEmi = json_encode($dataEmi); 
            $dataParamQRXml = json_encode($dataQRXml);
            \Log::info('1. Enviar CPE');
            // 1. Enviar CPE
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $urlEmi);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataParamEmi);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $tmprespuesta = curl_exec($ch); 
            curl_close($ch);  
            $respuestaEmi = json_decode($tmprespuesta, true);  
            // \Log::info($respuestaEmi);
            \Log::info($tmprespuesta); 
            \Log::info(print_r($respuestaEmi, true)); 

            \Log::info('2. Obtener código QR');
            // 2. Obtener código QR  
            $chQR = curl_init();
            curl_setopt($chQR, CURLOPT_URL, $urlQR);
            curl_setopt($chQR, CURLOPT_HTTPHEADER, $header);
            curl_setopt($chQR, CURLOPT_POST, 1);
            curl_setopt($chQR, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($chQR, CURLOPT_POSTFIELDS, $dataParamQRXml);
            curl_setopt($chQR, CURLOPT_RETURNTRANSFER, true);
            $tmprespuesta = curl_exec($chQR); 
            curl_close($chQR); 
            $respuestaQR = json_decode($tmprespuesta, true); 
            \Log::info(print_r($respuestaQR, true)); 

            // Guardar QR 
            if (isset($respuestaQR['codigo']) && $respuestaQR['codigo'] === 0) { 
                $nombre = $this->pathImg . $archivoEmision['nombreArchivo'] . '.png';
                \Log::info(print_r($nombre, true)); 
                file_put_contents($nombre, base64_decode($respuestaQR['pdfQRCode']));
            }

            \Log::info('2. Obtener XML');
            // 3. Obtener XML
            $chXML = curl_init();
            curl_setopt($chXML, CURLOPT_URL, $urlXml);
            curl_setopt($chXML, CURLOPT_HTTPHEADER, $header);
            curl_setopt($chXML, CURLOPT_POST, 1);
            curl_setopt($chXML, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($chXML, CURLOPT_POSTFIELDS, $dataParamQRXml);
            curl_setopt($chXML, CURLOPT_RETURNTRANSFER, true);
            $tmprespuesta = curl_exec($chXML); 
            curl_close($chXML); 
            $respuestaXml = json_decode($tmprespuesta, true); 
            // \Log::info(print_r($respuestaXml, true)); 
    
            // Guardar XML
            if (isset($respuestaXml['codigo']) && $respuestaXml['codigo'] === 0) { 
                $nombre = $this->pathImg . $archivoEmision['nombreArchivo'] . '.xml';
                file_put_contents($nombre, base64_decode($respuestaXml['xml']));
            }

            // Actualizar tabla venta
            if (isset($respuestaEmi['responseCode']) && $respuestaEmi['responseCode'] === '0') {
                $update = array(
                    'cpeemision' => $respuestaEmi['responseCode'],
                    'cpemensaje' => $respuestaEmi['responseContent'],
                );
                \DB::table('venta')->where('idventa', $id)->update($update);
                    
                $invoice = new invoiController();

                $fileNamePdf = $invoice->reporte($id, $data, $telefono, $archivoEmision['tipo'], $cpecorreo, $idafiliado);
                \Log::info('FIN. Enviar CPE');
            } else {                
                if ($respuestaEmi['responseContent'] === '<detail><code>1033</code><description>El comprobante fue registrado previamente con otros datos</description></detail>') {
                    // En Lentitudes de ESCON, reciben el comprobante pero no retornar una respuesta.
                    // Por tanto necesitamos regularizar 
                    $update = array(
                        'cpeemision' => '0',
                        'cpemensaje' => $respuestaEmi['responseContent'],
                    );
                    \DB::table('venta')->where('idventa', $id)->update($update);

                    $invoice = new invoiController();
                    $invoice->reporte($id, $data, $telefono, $archivoEmision['tipo'], $cpecorreo, $idafiliado);
                } else {
                    //Otros motivos
                    $comprobante['excepcionOSE'] = $respuestaEmi['responseContent'];
                }
            }
        }

        if ($return) { 
            return $fileNamePdf; 
        } else {
            return $this->crearRespuesta($comprobante, 200); 
        }
    }

    public function cpexml(Request $request, $enterprise) {
 
        $request = $request->all();

        if (isset($request['descarga']) and $request['descarga'] === '1') { 
            return response()->download($this->pathImg . $request['documento']);
        } else {
            $nombre = $request['documento'];   
            $contenido = $request['contenido'];   

            Storage::disk('local')->put($nombre, base64_decode($contenido));  
            return response()->download(storage_path('app/') . $nombre);
        } 
    }

    private function cpeComprobante($enterprise, $id) {
        $empresa = new empresa();
        $Objventa = new venta();  
        $idempresa = $empresa->idempresa($enterprise);
        $venta = $Objventa->venta($id, false, true); 

        $ventadet = $Objventa->ventadet($id);
        $ventafactura = $Objventa->ventafactura($id);

        $documentoserie = \DB::table('documentoserie')
                ->join('documentofiscal', 'documentoserie.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal')
                ->join('sede', 'documentoserie.idsede', '=', 'sede.idsede') 
                ->select('documentoserie.*', 'documentofiscal.codigosunat', 'sede.direccion', 'sede.telefono')
                ->where(array(
                    'documentoserie.identidad' => $venta->idafiliado,
                    'documentoserie.iddocumentofiscal' => $venta->iddocumentofiscal,
                    'documentoserie.serie' => $venta->serie
        ))->first();
        
        // \Log::info(print_r($documentoserie, true)); 
        if ($documentoserie->seesunat !== '1') {
            // return $this->crearRespuesta('Comprobante no está en el Sistema de Emisión Electrónica', [200, 'info']);
            return NULL;
        }

        // \Log::info(print_r($documentoserie, true)); 
        if (empty($documentoserie->sucursalsunat)) {
            // return $this->crearRespuesta('Comprobante no tiene código de local', [200, 'info']);
            return NULL;
        } 
        // dd($documentoserie);        
        $objCliente = new entidad();

        $dataAfiliado = \DB::table('entidad') 
                    ->join('documento', 'documento.iddocumento', '=', 'entidad.iddocumento')
                    ->select('entidad.*', 'departamento.nombre as departamento', 'provincia.nombre as provincia', 'ubigeo.nombre as distrito', 'documento.codigosunat') 
                    ->leftJoin('ubigeo as departamento', \DB::raw("CONCAT(SUBSTRING(entidad.idubigeo,1,5), '0000')"),'=', 'departamento.idubigeo')
                    ->leftJoin('ubigeo as provincia', \DB::raw("CONCAT(SUBSTRING(entidad.idubigeo,1,7), '00')"),'=', 'provincia.idubigeo')
                    ->leftJoin('ubigeo', 'entidad.idubigeo','=', 'ubigeo.idubigeo')
                    ->where('entidad.identidad', $venta->idafiliado)
                    ->whereNull('entidad.deleted')
                    ->first(); 

        $dataCliente = \DB::table('entidad') 
                    ->join('documento', 'documento.iddocumento', '=', 'entidad.iddocumento')
                    ->select('entidad.*', 'departamento.nombre as departamento', 'provincia.nombre as provincia', 'ubigeo.nombre as distrito', 'documento.codigosunat') 
                    ->leftJoin('ubigeo as departamento', \DB::raw("CONCAT(SUBSTRING(entidad.idubigeo,1,5), '0000')"),'=', 'departamento.idubigeo')
                    ->leftJoin('ubigeo as provincia', \DB::raw("CONCAT(SUBSTRING(entidad.idubigeo,1,7), '00')"),'=', 'provincia.idubigeo')
                    ->leftJoin('ubigeo', 'entidad.idubigeo','=', 'ubigeo.idubigeo')
                    ->where('entidad.identidad', $venta->idcliente)
                    // ->where('entidad.identidad', 240)
                    ->whereNull('entidad.deleted')
                    ->first();

        $tipocomprobante = '';
        $temporal = '';
        switch ($venta->codigosunat) {
            case '01': //Factura
                $tipocomprobante = 'factura';
                $temporal = strlen($venta->serie) === 3 ? ('F'.$venta->serie) : $venta->serie;
                break;

            case '03': //Boleta
                $tipocomprobante = 'boleta';
                $temporal = strlen($venta->serie) === 3 ? ('B'.$venta->serie) : $venta->serie;
                break;

            case '07': //Nota de crédito
                $tipocomprobante = 'notaCredito'; 

                if ($venta->refcodigosunat === '01') {
                    $temporal = strlen($venta->serie) === 3 ? ('F'.$venta->serie) : $venta->serie;
                }

                if ($venta->refcodigosunat === '03') {
                    $temporal = strlen($venta->serie) === 3 ? ('B'.$venta->serie) : $venta->serie;
                }
                break;
        }

        if (empty($tipocomprobante)) {
            // return $this->crearRespuesta('Comprobante desconocido', [200, 'info']);
            return NULL;
        } 

        /* Solo Boletas tienen descuento en item, lo idea sera llevarlo al descuento general.
         *  
         */ 
               
        $venta->total = (float)$venta->total; 
        $venta->subtotal = (float)$venta->subtotal;          
        $venta->valorimpuesto = (float)$venta->valorimpuesto; 

        /*Temporal*/        
        // $dataAfiliado->codigosunat = 6;
        // $dataAfiliado->numerodoc = '20600567528';
        // $dataAfiliado->entidad = 'MOUTEC DIGITAL S.A.';
        // $dataAfiliado->idubigeo = 'PE0150141';
        // $dataAfiliado->direccion = 'AV. AVIACION NRO. 4004 URB. LA CALERA DE LA MERCED';
        // $dataAfiliado->distrito = 'Surquillo';
        /**/   
        // $venta->serie = $temporal;
        // $documentoserie->sucursalsunat = '0000';
        
        $comprobante = array(
            $tipocomprobante => array(
                'IDE' => array( 
                    'numeracion' => $venta->serie . '-' .$venta->serienumero,
                    'fechaEmision' => $this->formatFecha($venta->fechaventa, 'yyyy-mm-dd'),
                    // 'horaEmision' => '', //substr($venta->created_at, 11, 8),                    
                    'tipoMoneda' => 'PEN' 
                ),
                'EMI' => array(
                    'tipoDocId' => (string) $dataAfiliado->codigosunat,
                    'numeroDocId' => $dataAfiliado->numerodoc, 
                    'razonSocial' => $dataAfiliado->entidad,
                    // 'ubigeo' => substr($dataAfiliado->idubigeo, 3, 6),
                    // 'direccion' => $dataAfiliado->direccion,                    
                    // 'departamento' => $dataAfiliado->departamento,
                    // 'provincia' => $dataAfiliado->provincia,
                    // 'distrito' => $dataAfiliado->distrito,
                    // 'direccion' => $venta->direccionsede,
                    'direccion' => $documentoserie->direccion,
                    'codigoPais' => substr($dataAfiliado->idubigeo, 0, 2),
                    'codigoAsigSUNAT' => $documentoserie->sucursalsunat //150113
                ),
                'REC' => array(
                    'tipoDocId' => (string) $dataCliente->codigosunat,
                    'numeroDocId' => $dataCliente->numerodoc,
                    'razonSocial' => $dataCliente->entidad 
                ),
                'CAB' => array(
                    'gravadas' => array(
                        'codigo' => '1001', //Catálogo 14:Total valor de venta - operaciones gravadas
                        'totalVentas' => number_format($venta->subtotal, 2)
                    ),
                    'totalImpuestos' => array(
                        array(
                            'idImpuesto' => '1000', //Catálogo 05:IGV Impuesto General a las Ventas
                            'montoImpuesto' => number_format($venta->valorimpuesto, 2)
                        )
                    ), 
                    'importeTotal' => number_format($venta->total, 2),
                    'tipoOperacion' => '0101', //Catálogo 51:Venta interna
                    'leyenda' => array(
                        array(
                            'codigo' => '1000', //Catálogo 52:Monto en Letras
                            'descripcion' => $this->num2letras((float)$venta->total),
                        )
                    ),
                    'montoTotalImpuestos' => number_format($venta->valorimpuesto, 2) 
                ),
                'DET' => array() 
            )
        );  

        switch ($venta->codigosunat) {
            case '01': //Factura 
                $comprobante[$tipocomprobante]['IDE']['codTipoDocumento'] = $venta->codigosunat;
                break;
            case '03': //Boleta
                $comprobante[$tipocomprobante]['IDE']['codTipoDocumento'] = $venta->codigosunat;
                break;
            case '07': //Nota de crédito 

                if ($venta->tiponotacredito === '1') {
                    $codigoMotivo = '01';
                }

                if ($venta->tiponotacredito === '2') {
                    $codigoMotivo = '06';
                }

                if ($venta->tiponotacredito === '3') {
                    $codigoMotivo = '07';
                } 
                
                //$venta->refserie siempre va a ser igual a $venta->serie, excepto si se trata de un boleta fisica o emitida por portal
                $comprobante[$tipocomprobante]['DRF'] = array();
                $comprobante[$tipocomprobante]['DRF'][] = array(
                    'tipoDocRelacionado' => $venta->refcodigosunat,
                    'numeroDocRelacionado' => $venta->refserie . '-' . $venta->refserienumero,
                    'codigoMotivo' => $codigoMotivo,
                    'descripcionMotivo' => $venta->descripcion
                );
                //Tb podria añadir Guia de Remisión ejemplo "idventaguiaref"
                break;
        }

        if (!empty($dataCliente->direccion)) {
            $comprobante[$tipocomprobante]['REC']['direccion'] = $dataCliente->direccion; 
        }        

        if (!empty($dataCliente->distrito)) {
            $comprobante[$tipocomprobante]['REC']['distrito'] = $dataCliente->distrito;
        }

        if (!empty($dataCliente->provincia)) {
            $comprobante[$tipocomprobante]['REC']['provincia'] = $dataCliente->provincia;
        }

        if (!empty($dataCliente->departamento)) {
            $comprobante[$tipocomprobante]['REC']['departamento'] = $dataCliente->departamento;
        }

        if (!empty($dataCliente->idubigeo)) {
            $comprobante[$tipocomprobante]['REC']['codigoPais'] = substr($dataCliente->idubigeo, 0, 2);
        }

        if (!empty($dataCliente->telefono)) {
            $comprobante[$tipocomprobante]['REC']['telefono'] = $dataCliente->telefono;
        }
        // $venta->cpecorreo = 'chaucachavez@gmail.com';
    
        // if ($this->temporal) {
        //     $setear = true;
        //     if (!empty($venta->cpecorreo)) {
        //         $comprobante[$tipocomprobante]['REC']['correoElectronico'] = $venta->cpecorreo; 
        //         $setear = false;
        //     }

        //     if ($dataAfiliado->identidad === 4844 && !empty($dataCliente->email) && $setear) { // && false    
        //         $comprobante[$tipocomprobante]['REC']['correoElectronico'] = $dataCliente->email;
        //     }
        // }

        if ((float) $venta->descuento > 0) {
            $venta->descuento = (float) $venta->descuento;        
            $montoBaseCargoDescuento = $venta->subtotal + $venta->descuento; 
            $factorCargoDescuento = ($venta->descuento / $montoBaseCargoDescuento); 
            // a/b/100
            $comprobante[$tipocomprobante]['CAB']['cargoDescuento'] = array(
                array(
                    'indicadorCargoDescuento' => 'false', //true: cargo false: descuento
                    'codigoCargoDescuento' => '02', //Catálogo 53:Descuentos globales que afectan la base imponible del IGV/IVAP
                    'factorCargoDescuento' => number_format($factorCargoDescuento, 5),
                    'montoCargoDescuento' => (string) $venta->descuento, 
                    'montoBaseCargoDescuento' => number_format($montoBaseCargoDescuento, 2)
                )
            ); 
        } 

        // Campos adicionales
        switch ($venta->codigosunat) {
            case '01': //Factura 
                if(isset($ventafactura)) {
                    $comprobante[$tipocomprobante]['ADI'] = array();
                }

                if (isset($ventafactura) && $ventafactura->paciente) { 
                    $comprobante[$tipocomprobante]['ADI'][] = array(
                        'tituloAdicional' => 'Paciente', 
                        'valorAdicional' => $ventafactura->paciente 
                    );
                }

                if (isset($ventafactura) && $ventafactura->titular) {
                    $comprobante[$tipocomprobante]['ADI'][] = array(
                        'tituloAdicional' => 'Parentesco', 
                        'valorAdicional' => $ventafactura->titular 
                    );
                }

                if (isset($ventafactura) && $ventafactura->empresa) {
                    $comprobante[$tipocomprobante]['ADI'][] = array(
                        'tituloAdicional' => 'Empresa',
                        'valorAdicional' => $ventafactura->empresa 
                    );
                }

                if (isset($ventafactura) && $ventafactura->diagnostico) {
                    $comprobante[$tipocomprobante]['ADI'][] = array(
                        'tituloAdicional' => 'Diagnóstico', 
                        'valorAdicional' => $ventafactura->diagnostico 
                    );
                }  

                if (isset($ventafactura) && $ventafactura->indicacion) {
                    $comprobante[$tipocomprobante]['ADI'][] = array(
                        'tituloAdicional' => 'Indicación', 
                        'valorAdicional' => $ventafactura->indicacion 
                    );
                } 

                if (isset($ventafactura) && $ventafactura->autorizacion) {
                    $comprobante[$tipocomprobante]['ADI'][] = array(
                        'tituloAdicional' => 'Autorización', 
                        'valorAdicional' => $ventafactura->autorizacion 
                    );
                } 

                if (isset($ventafactura) && $ventafactura->programa) {
                    $comprobante[$tipocomprobante]['ADI'][] = array(
                        'tituloAdicional' => 'Programa', 
                        'valorAdicional' => $ventafactura->programa 
                    );
                } 

                if (isset($ventafactura) && $ventafactura->deducible) {
                    $comprobante[$tipocomprobante]['ADI'][] = array(
                        'tituloAdicional' => 'Deducible ('.$ventafactura->deducible.')', 
                        'valorAdicional' => $venta->deducible 
                    );
                }

                if (isset($ventafactura) && $ventafactura->coaseguro) {
                    $comprobante[$tipocomprobante]['ADI'][] = array(
                        'tituloAdicional' => 'Coaseguro ('.$ventafactura->coaseguro.'%)',
                        'valorAdicional' => $venta->coaseguro 
                    );
                }  

                break;
            case '03': //Boleta
                
                if ($venta->idpaciente) {
                    $comprobante[$tipocomprobante]['ADI'] = array();

                    $comprobante[$tipocomprobante]['ADI'][] = array(
                        'tituloAdicional' => 'Paciente', 
                        'valorAdicional' => $venta->paciente 
                    );
                }

                break;
        }
        // dd($ventadet); 
        
        $i = 1;
        foreach($ventadet as $row) {
            if ((float)$row->total >= 0) {
 
                $row->preciounit = (float)$row->preciounit;
                $row->valorunit = (float)$row->valorunit;
                $row->valorventa = (float)$row->valorventa;
                $row->montototalimpuestos = (float)$row->montototalimpuestos;
 
                $nombreproducto = $row->nombreproducto;

                if(!empty($row->descripcion)) {
                    $nombreproducto .= ' ' . $row->descripcion;
                }

                $comprobante[$tipocomprobante]['DET'][] = array(
                    'numeroItem' => (string) $i,
                    'codProductoSunat' => $row->codigosunat,
                    // 'descripcionProducto' => !empty($row->renombreproducto) ? $row->renombreproducto : $row->nombreproducto,
                    'descripcionProducto' => $nombreproducto,
                    'cantidadItems' => number_format($row->cantidad, 2),
                    'unidad' => 'ZZ',//'NIU', //Catálogo 03:Código de tipo de unidad de medida comercial
                    'valorUnitario' => number_format($row->valorunit, 3),
                    'precioVentaUnitario' => number_format($row->preciounit, 2),
                    'totalImpuestos' => array(
                        array(
                            'idImpuesto' => '1000', //Catálogo 05:IGV Impuesto General a las Ventas
                            'montoImpuesto' => number_format($row->montototalimpuestos, 2),
                            'tipoAfectacion' => '10', //Catálogo 07:Gravado - Operación Onerosa
                            'montoBase' => number_format($row->valorventa, 2),
                            'porcentaje' => number_format(18, 2)
                        )
                    ),
                    'valorVenta' => number_format($row->valorventa, 2),
                    'montoTotalImpuestos' => number_format($row->montototalimpuestos, 2)
                );
                $i++;
            }
        }

        return array(
            'comprobante' => $comprobante,
            'authentication' => array(
                'cpeuser' => $dataAfiliado->cpeuser,
                'cpepassword' => $dataAfiliado->cpepassword
            ),
            'cpeemision' => $venta->cpeemision, 
            'telefono' => $documentoserie->telefono,
            'cpecorreo' => $venta->cpecorreo,
            'idafiliado' => $venta->idafiliado
        );
    }

    public function cpeanulacion(Request $request, $enterprise, $id) {

        $empresa = new empresa();
        $Objventa = new venta(); 

        $request = $request->all();

        $idempresa = $empresa->idempresa($enterprise);
        $venta = $Objventa->venta($id, false, true); 
  
        $objCliente = new entidad();

        if (substr($venta->serie, 0, 1) === '0') {
            return $this->crearRespuesta('No es comprobante electrónico', [200, 'info']);
        } 

        $dataAfiliado = \DB::table('entidad') 
                    ->join('documento', 'documento.iddocumento', '=', 'entidad.iddocumento')
                    ->select('entidad.*', 'departamento.nombre as departamento', 'provincia.nombre as provincia', 'ubigeo.nombre as distrito', 'documento.codigosunat') 
                    ->leftJoin('ubigeo as departamento', \DB::raw("CONCAT(SUBSTRING(entidad.idubigeo,1,5), '0000')"),'=', 'departamento.idubigeo')
                    ->leftJoin('ubigeo as provincia', \DB::raw("CONCAT(SUBSTRING(entidad.idubigeo,1,7), '00')"),'=', 'provincia.idubigeo')
                    ->leftJoin('ubigeo', 'entidad.idubigeo','=', 'ubigeo.idubigeo')
                    ->where('entidad.identidad', $venta->idafiliado)
                    ->whereNull('entidad.deleted')
                    ->first(); 

        $dataCliente = \DB::table('entidad') 
                    ->join('documento', 'documento.iddocumento', '=', 'entidad.iddocumento')
                    ->select('entidad.*', 'departamento.nombre as departamento', 'provincia.nombre as provincia', 'ubigeo.nombre as distrito', 'documento.codigosunat') 
                    ->leftJoin('ubigeo as departamento', \DB::raw("CONCAT(SUBSTRING(entidad.idubigeo,1,5), '0000')"),'=', 'departamento.idubigeo')
                    ->leftJoin('ubigeo as provincia', \DB::raw("CONCAT(SUBSTRING(entidad.idubigeo,1,7), '00')"),'=', 'provincia.idubigeo')
                    ->leftJoin('ubigeo', 'entidad.idubigeo','=', 'ubigeo.idubigeo')
                    ->where('entidad.identidad', $venta->idcliente)
                    ->whereNull('entidad.deleted')
                    ->first();  

        $tipocomprobante = '';
        $temporal = '';
        switch ($venta->codigosunat) {
            case '01': //Factura
                $tipocomprobante = 'comunicacionBaja'; 
                $temporal = strlen($venta->serie) === 3 ? ('F'.$venta->serie) : $venta->serie; 
                $tipo = 'RA';
                break;

            case '03': //Boleta
                $tipocomprobante = 'resumenComprobantes';
                $temporal = strlen($venta->serie) === 3 ? ('B'.$venta->serie) : $venta->serie;
                $tipo = 'RC';
                break;

            case '07': //Nota de crédito 
                if ($venta->refcodigosunat === '01') {
                    $tipocomprobante = 'comunicacionBaja'; 
                    $temporal = strlen($venta->serie) === 3 ? ('F'.$venta->serie) : $venta->serie;
                    $tipo = 'RA';
                }

                if ($venta->refcodigosunat === '03') {
                    $tipocomprobante = 'resumenComprobantes'; 
                    $temporal = strlen($venta->serie) === 3 ? ('B'.$venta->serie) : $venta->serie;    
                    $tipo = 'RC';
                } 
                break;
        }

        if (empty($tipocomprobante)) {
            return $this->crearRespuesta('Comprobante desconocido', [200, 'info']);
        } 

        $documentoserie = \DB::table('serienumero') 
            ->select('serienumero.numero')
            ->where(array(
                'serienumero.idafiliado' => $venta->idafiliado,
                'serienumero.documento' => $tipo 
            ))
            ->first(); 

        $documentoseries = \DB::table('documentoserie')
                ->join('documentofiscal', 'documentoserie.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal')
                ->join('sede', 'documentoserie.idsede', '=', 'sede.idsede') 
                ->select('sede.direccion')
                ->where(array(
                    'documentoserie.identidad' => $venta->idafiliado,
                    'documentoserie.iddocumentofiscal' => $venta->iddocumentofiscal,
                    'documentoserie.serie' => $venta->serie
        ))->first(); 


        // dd($venta->idafiliado, $tipo );
        if (empty($documentoserie)) {
            return $this->crearRespuesta('No está configurado el correlativo para anulación', [200, 'info']);
        } 

        $numero = explode('-', $documentoserie->numero); 

        if (date('Ymd') === $numero[0]) {
            $documentoserie->numero = $numero[0] . '-' . ((integer) $numero[1] + 1);
        } else {
            $documentoserie->numero = date('Ymd') . '-' . 1;
        }   
 
        $comprobante = array(
            $tipocomprobante => array(
                'IDE' => array( 
                    'numeracion' => $tipo . '-' .$documentoserie->numero,
                    'fechaEmision' => date('Y-m-d')
                ),
                'EMI' => array(
                    'tipoDocId' => (string) $dataAfiliado->codigosunat,
                    'numeroDocId' => $dataAfiliado->numerodoc, 
                    'razonSocial' => $dataAfiliado->entidad, 
                    'direccion' => $documentoseries->direccion,
                    'codigoPais' => substr($dataAfiliado->idubigeo, 0, 2) 
                ) 
            )
        );  

        switch ($venta->codigosunat) {
            case '01': //Factura  
                $comprobante[$tipocomprobante]['CBR']['fechaReferencia'] = $this->formatFecha($venta->fechaventa, 'yyyy-mm-dd');
                
                $comprobante[$tipocomprobante]['DBR'] = array(
                    array(
                        'numeroItem' => '1',
                        'tipoComprobanteItem' => $venta->codigosunat,
                        'serieItem' => $venta->serie,
                        'correlativoItem' => (string) $venta->serienumero,
                        'motivoBajaItem' => 'CANCELADO'
                    )
                );
                break;

            case '03': //Boleta 
                $comprobante[$tipocomprobante]['IDE']['fechaReferencia'] = $this->formatFecha($venta->fechaventa, 'yyyy-mm-dd');

                $comprobante[$tipocomprobante]['DET'] = array(
                    array(
                        'numeroItem' => '1',
                        'monedaItem' => 'PEN', 
                        'numeracionItem' => $venta->serie . '-' .$venta->serienumero,
                        'tipoComprobanteItem' => $venta->codigosunat,
                        'tipoDocIdAdq' => (string) $dataCliente->codigosunat,
                        'numeroDocIdAdq' => $dataCliente->numerodoc, 
                        'estadoItem' => '3',  
                        'gravadas' => array(
                            'codigo' => '01', //Catálogo 14:Total valor de venta - operaciones gravadas
                            'totalVentas' => number_format($venta->subtotal, 2)
                        ),
                        'totalImpuestos' => array(
                            array(
                                'idImpuesto' => '1000', //Catálogo 05:IGV Impuesto General a las Ventas
                                'montoImpuesto' => number_format($venta->valorimpuesto, 2)
                            )
                        ), 
                        'importeTotal' => number_format($venta->total, 2) 
                    )
                );
                break;

            case '07': //Nota de crédito   
                if ($venta->refcodigosunat === '01') { //NC de Factura                 
                    $comprobante[$tipocomprobante]['CBR']['fechaReferencia'] = $this->formatFecha($venta->fechaventa, 'yyyy-mm-dd');
                
                    $comprobante[$tipocomprobante]['DBR'] = array(
                        array(
                            'numeroItem' => '1',
                            'tipoComprobanteItem' => $venta->codigosunat,
                            'serieItem' => $venta->serie, 
                            'correlativoItem' => (string) $venta->serienumero,
                            'motivoBajaItem' => 'CANCELADO'//$venta->descripcion
                        )
                    );
                }

                if ($venta->refcodigosunat === '03') { //NC de Boleta

                    $comprobante[$tipocomprobante]['IDE']['fechaReferencia'] = $this->formatFecha($venta->fechaventa, 'yyyy-mm-dd');

                    $comprobante[$tipocomprobante]['DET'] = array(
                        array(
                            'numeroItem' => '1',
                            'monedaItem' => 'PEN', 
                            'numeracionItem' => $venta->serie . '-' .$venta->serienumero,
                            'tipoComprobanteItem' => $venta->codigosunat,
                            'tipoDocIdAdq' => (string) $dataCliente->codigosunat,
                            'numeroDocIdAdq' => $dataCliente->numerodoc, 
                            'estadoItem' => '3',  
                            'gravadas' => array(
                                'codigo' => '01', //Catálogo 14:Total valor de venta - operaciones gravadas
                                'totalVentas' => number_format($venta->subtotal, 2)
                            ),
                            'totalImpuestos' => array(
                                array(
                                    'idImpuesto' => '1000', //Catálogo 05:IGV Impuesto General a las Ventas
                                    'montoImpuesto' => number_format($venta->valorimpuesto, 2)
                                )
                            ), 
                            'importeTotal' => number_format($venta->total, 2) 
                        )
                    ); 

                    $comprobante[$tipocomprobante]['DET'][0]['serieCorrelativoMod'] = $venta->refserie . '-' .$venta->refserienumero;
                    $comprobante[$tipocomprobante]['DET'][0]['tipoComprobanteMod'] = $venta->refcodigosunat; 
                    break;
                } 
                break;
        }  
        
        // dd($comprobante);

        $retorno = array(
            'comprobante' => $comprobante,
            'authentication' => array(
                'cpeuser' => $dataAfiliado->cpeuser,
                'cpepassword' => $dataAfiliado->cpepassword
            ),
            'cpeticket' => $venta->cpeticket,
            'cpeemision' => $venta->cpeemision,
            'cpeanulacion' => $venta->cpeanulacion
        );

        return $this->crearRespuesta($retorno, 200); 
    }
    
    public function pdf() {

        dd('Hola'); 
    }

    private function archivoFe($data) {
        $tipo = '';
        $codTipoDocumento;
        $comprobante;

        if (isset($data['factura'])) {
            $comprobante = 'factura';
            $codTipoDocumento = '-01';
            $tipo = '01';
        }

        if (isset($data['boleta'])) {
            $comprobante = 'boleta';
            $codTipoDocumento = '-03';
            $tipo = '03';
        }

        if (isset($data['notaCredito'])) {
            $comprobante = 'notaCredito';
            $codTipoDocumento = '-07';
            $tipo = '07';
        }

        if (isset($data['comunicacionBaja'])) {
            $comprobante = 'comunicacionBaja';
            $codTipoDocumento = '';
        }

        if (isset($data['resumenComprobantes'])) {
            $comprobante = 'resumenComprobantes';
            $codTipoDocumento = '';
        }

        $numeroDocId = $data[$comprobante]['EMI']['numeroDocId'];
        $filename = $numeroDocId . $codTipoDocumento . '-' . $data[$comprobante]['IDE']['numeracion'];
        $arrayDeCadenas = explode('-', $data[$comprobante]['IDE']['numeracion']);

        return array(
            'nombreArchivo' => $filename,
            'comprobante' => $comprobante,
            'numeroDocId' => $numeroDocId,
            'tipo' => $tipo,
            'serie' => $arrayDeCadenas[0],
            'numero' => $arrayDeCadenas[1]
        );
    }
}
