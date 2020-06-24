<?php

namespace App\Http\Controllers;

use Excel;
use \Firebase\JWT\JWT; 
use Illuminate\Http\Request;

use App\Models\empresa; 
use App\Models\presupuestodetcant;  

class presupuestodetcantController extends Controller {
     
    public function __construct(Request $request) { 
        $this->getToken($request);
    } 
    
    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $presupuestodetcant = new presupuestodetcant(); 

        $param = [];         

        if (isset($paramsTMP['idpersonal']) && !empty($paramsTMP['idpersonal'])) {
            $param['presupuestodetcant.idpersonal'] = $paramsTMP['idpersonal'];
        }

        if (isset($paramsTMP['idproducto']) && !empty($paramsTMP['idproducto'])) {
            $param['presupuestodetcant.idproducto'] = $paramsTMP['idproducto'];
        } 

        if (isset($paramsTMP['idpresupuestodet']) && !empty($paramsTMP['idpresupuestodet'])) {
            $param['presupuestodetcant.idpresupuestodet'] = $paramsTMP['idpresupuestodet'];
        }

        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'presupuestodetcant.fecha';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'DESC';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        } 

        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : ''; 
        
        $datapresupuestodetcant = $presupuestodetcant->grid($param,  $like, $pageSize, $orderName, $orderSort); 

        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $datapresupuestodetcant->total();
            $datapresupuestodetcant = $datapresupuestodetcant->items();
        }
        
        if (isset($paramsTMP['formato']) && !empty($paramsTMP['formato'])) {
            if(in_array($paramsTMP['formato'], ['xls', 'xlsx'])){  
                               
            }
        } else {
            return $this->crearRespuesta($datapresupuestodetcant, 200, $total);
        } 

    }
    
}
