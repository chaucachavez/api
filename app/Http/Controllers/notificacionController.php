<?php

namespace App\Http\Controllers;

use Excel;
use App\Models\sede;
use \Firebase\JWT\JWT;
use App\Models\empresa;
use App\Exports\DataExport;
use App\Models\notificacion;
use Illuminate\Http\Request;

class notificacionController extends Controller {

    public function __construct(Request $request) {
        $this->getToken($request);
    }  

    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $notificacion = new notificacion();

        $param = array();
        $param['notificacion.idempresa'] = $empresa->idempresa($enterprise);
        
        if (isset($paramsTMP['identidad']) && !empty($paramsTMP['identidad'])) {
            $param['notificacion.identidad'] = $paramsTMP['identidad'];
        }

        if (isset($paramsTMP['idcitamedica']) && !empty($paramsTMP['idcitamedica'])) {
            $param['notificacion.idcitamedica'] = $paramsTMP['idcitamedica'];
        }

        if (isset($paramsTMP['idcitaterapeutica']) && !empty($paramsTMP['idcitaterapeutica'])) {
            $param['notificacion.idcitaterapeutica'] = $paramsTMP['idcitaterapeutica'];
        }

        if (isset($paramsTMP['idcicloatencion']) && !empty($paramsTMP['idcicloatencion'])) {
            $param['notificacion.idcicloatencion'] = $paramsTMP['idcicloatencion'];
        }
        
        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'notificacion.idnotificacion';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'desc';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }
        
        $between = array();
        $betweenHora = array();

        if (isset($paramsTMP['sms_numero']) && !empty(trim($paramsTMP['sms_numero']))) {
            $param['notificacion.sms_numero'] = trim($paramsTMP['sms_numero']);
        }

        if (isset($paramsTMP['email_correo']) && !empty(trim($paramsTMP['email_correo']))) {
            $param['notificacion.email_correo'] = trim($paramsTMP['email_correo']);
        } 

        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta'])) {
            if (!empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {
                $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
                $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
                $between = [$paramsTMP['desde'], $paramsTMP['hasta']];
            }
        }

        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';

        $likepaciente = !empty($paramsTMP['likepaciente']) ? trim($paramsTMP['likepaciente']) : '';
                        
        $data = $notificacion->grid($param, $like, $pageSize, $orderName, $orderSort, [], [], [], [], $between);

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
                        'SEDE' => $row->nombresede, 
                        'DOCUMENTO' => $row->nombredocventa, 
                        'AFILIADO' => $row->acronimo,
                        'SERIE' => $row->serie, 
                        'NRO.' => $row->serienumero,
                        'CLIENTE' => $row->nombrecliente,
                        'FECHA VENTA' => $row->fechaventa,
                        'ESTADO' => $row->estadodocumento,
                        'M.PAGO' => $row->mediopagonombre,
                        'TOTAL' => $row->total,
                        'CM' => $row->idcitamedica ? 'Si' : '',
                        'CICLO' => $row->idcicloatencion,
                        'PACIENTE' => $row->nombrepaciente,
                        'NHCPACIENTE' => $row->hcpaciente,
                        'MODELO' => $row->nombremodelo 
                    );   
                }   

                return Excel::download(new DataExport($data), 'Reporte_' . date('Y-m-d H:i:s') . '.xlsx');
            }
        } else {
            return $this->crearRespuesta($data, 200, $total); 
        } 
    }
    
}
