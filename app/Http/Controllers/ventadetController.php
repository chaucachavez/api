<?php

namespace App\Http\Controllers;

use Excel;
use App\Models\sede;
use \Firebase\JWT\JWT;
use App\Models\empresa;
use App\Models\ventadet;
use App\Exports\DataExport;
use Illuminate\Http\Request; 

class ventadetController extends Controller {

    public function __construct(Request $request) {
        $this->getToken($request);
    }

    public function construct(Request $request, $enterprise) {

        $sede = new sede(); 
        
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
                $documentoseries = $sede->documentoSeries(['documentoserie.idempresa' => $idempresa], [], 'entidad.acronimo');                                       
                $listcombox['dose'] = $this->ordenarafiliados($documentoseries);
            }            

            if(in_array('fact', $others)) {
                
                $paramDocu = array(
                    'documentoserie.idempresa' => $idempresa,
                    'documentoserie.iddocumentofiscal' => 1 
                );                
                $dataf = $sede->documentoSeries($paramDocu);
              
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
                $dataf = $sede->documentoSeries($paramDocu, [], 'documentoserie.identidad');
              
                foreach ($dataf as $row) {                
                    $serienumero = '(' . $row->acronimo . ') ' . $row->nombredocumento . ' N° ' . $row->serie . '-' . str_pad(($row->numero + 1), 6, "0", STR_PAD_LEFT);
                    $row->documentoSerieNumero = $serienumero;     
                } 
                $listcombox['documentos'] = $dataf;
            }
        }

        return $this->crearRespuesta($data, 200, '', '', $listcombox); 
    } 

    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $ventadet = new ventadet();

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

        if (isset($paramsTMP['iddocumentofiscal']) && !empty(trim($paramsTMP['iddocumentofiscal']))) {
            $arrayiddocumentofiscal = explode(',', $request['iddocumentofiscal']);
            if(count($arrayiddocumentofiscal) === 1) {
                $param['venta.iddocumentofiscal'] = $paramsTMP['iddocumentofiscal'];
            } 
        }

        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';
                        
        $data = $ventadet->grid($param, $between, $like, $pageSize, $orderName, $orderSort);

        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $data->total();
            $data = $data->items();
        }
                        
        if (isset($paramsTMP['formato']) && !empty($paramsTMP['formato'])) {
            if(in_array($paramsTMP['formato'], ['xls', 'xlsx'])){ 
                $dataventa = $data; 
                $data = array();
                
                foreach($dataventa as $row){ 
                    $data[] = array(
                        'IDVENTA' => $row->idventa, 
                        'SEDE' => $row->nombresede, 
                        'DOCUMENTO' => $row->nombredocventa, 
                        'AFILIADO' => $row->acronimo,
                        'SERIE' => $row->serie, 
                        'NRO.' => $row->serienumero,
                        'CLIENTE' => $row->nombrecliente,
                        'HC' => $row->hc,
                        'FECHA VENTA' => $row->fechaventa,
                        'ESTADO' => $row->estadodocumento,
                        'M.PAGO' => $row->mediopagonombre,
                        'M.PAGO2' => $row->mediopagosegnombre,
                        'TOTAL' => $row->total,
                        'CM' => $row->idcitamedica ? 'Si' : '',
                        'CICLO' => $row->idcicloatencion,
                        'PACIENTE' => $row->nombrepaciente,
                        'NHCPACIENTE' => $row->hcpaciente,
                        'MODELO' => $row->nombremodelo, 
                        'CODIGO PRODUCTO' => $row->codigoproducto, 
                        'CONCEPTO' => $row->nombreproducto, 
                        'CANTIDAD' => $row->cantidad,
                        'PRECIOUNIT' => $row->preciounit,
                        'SUBTOTAL' => $row->subtotal,
                        'MEDICOCONSULTA' => $row->nombremedico,
                        'SEGURO-PLAN/PRODUCTO' => $row->nombreaseguradoraplan,
                        'DEDUCIBLE' => $row->deducible,
                        'COASEGURO' => $row->coaseguro,
                        'CAJA' => $row->created 
                    );   
                }  
                
                return Excel::download(new DataExport($data), 'Reporte_' . date('Y-m-d H:i:s') . '.xlsx');
            }
        } else {
            return $this->crearRespuesta($data, 200, $total); 
        }  
    }
    
}
