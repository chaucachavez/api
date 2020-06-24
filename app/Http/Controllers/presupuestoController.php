<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\venta;
use App\Models\empresa;
use App\Models\grupodx;
use App\Models\terapia;
use App\Models\producto;
use App\Models\citamedica;
use App\Models\presupuesto;
use App\Models\cicloatencion;
use App\Models\ciclomovimiento;

class presupuestoController extends Controller {
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    public function show(Request $request, $enterprise, $id) {
        
        $empresa = new empresa();
        $producto = new producto();
        $citamedica = new citamedica();
        $terapia = new terapia();
        $objPresupuesto = new presupuesto();
        $cicloatencion = new cicloatencion();

        $request = $request->all();
        $presupuesto =  $objPresupuesto->presupuesto(['cicloatencion.idcicloatencion' => $id]);
        $idempresa = $empresa->idempresa($enterprise); 
        
        $param = array(
            'producto.idempresa' => $idempresa
        );
        
        if($presupuesto){
            $presupuestodet = $objPresupuesto->presupuestodet($presupuesto->idpresupuesto);
            
            $whereIn = [];
            foreach($presupuestodet as $row){
                $preciounit = $presupuesto->tipotarifa === 1 ? $row->preciounitregular : ($presupuesto->tipotarifa === 2 ? $row->preciounittarjeta : $row->preciounitefectivo);
                $row->preciounit = $preciounit;
                if($row->idtipoproducto === 2)
                    $whereIn[] = $row->idproducto;
            }
            
            $listcombox = array(  
                'presupuestodet' => $presupuestodet
            );
            
            if (isset($request['prodserv']) && $request['prodserv'] === '1') {  
                $param2 = array(
                    'citamedica.idempresa' => $idempresa,
                    'citamedica.idcicloatencion' => $id 
                );  
                $listcombox['productoservicios'] = $citamedica->tratamientomedico($param2, false, true);                
                $listcombox['ordenservicios'] = $producto->grid($param, '', '', '', '', ['producto.idproducto', 'producto.ordencobro'], FALSE, $whereIn);  
                
                $listcombox['autorizaciones'] = $cicloatencion->cicloAutorizaciones(['cicloatencion.idcicloatencion' => $id]);
            }
            
            if (isset($request['diagnost']) && $request['diagnost'] === '1') {                
                $listcombox['diagnosticosmedico'] = $citamedica->diagnosticomedico(['citamedica.idcicloatencion' => $id]);
            }

            if (isset($request['idterapia']) && !empty($request['idterapia'])) {
                // 19.09.2019 No tiene sentido por el momento.

                // $param = array(
                //     'terapiaprocedimiento.idterapia' => $request['idterapia'],
                //     'terapiaprocedimiento.idcicloatencion' => $id 
                // );
                // $listcombox['procedimientos'] = [];
                // $listcombox['procedimientos'] = $terapia->procedimientos($param);

                // $param = array(
                //     'terapiatecnica.idterapia' => $request['idterapia'],
                //     'terapiatecnica.idcicloatencion' => $id 
                // );
                // $listcombox['tecnicas'] = [];
                // $listcombox['tecnicas'] = $terapia->tecnicasmanuales($param);

                // $param = array(
                //     'terapiaimagen.idterapia' => $request['idterapia'],
                //     'terapiaimagen.idcicloatencion' => $id 
                // );
                // $listcombox['puntos'] = [];
                // $listcombox['puntos'] = $terapia->puntosimagen($param);
                $listcombox['procedimientos'] = [];                
                $listcombox['tecnicas'] = [];
                $listcombox['puntos'] = [];
            }

            if (isset($request['pagos']) && $request['pagos'] === '1') {    

                $venta = new venta();
                $ciclomovimiento = new ciclomovimiento();  

                $pagosrealizadas = array();

                $saldos = $ciclomovimiento->movimiento(['idcicloatencion' => $id], ['idcicloatencionref' => $id]);
                $ventas = $venta->grid(['venta.idcicloatencion' => $id, 'venta.idestadodocumento' => 27]);
                // $whereIdventaCmIn = [];
                foreach ($ventas as $row) {
                    if(empty($row->idcitamedica)){
                        $pagosrealizadas[] = array(
                            'documento' => $row->documentoSerieNumero, 
                            'fechaventa' => $row->fechaventa, 
                            'mediopago' => $row->mediopagonombre, 
                            'total' =>  $row->total, 
                            'nota' => 'notacredito', 
                            'iddocumentofiscal' => $row->iddocumentofiscal,
                            'idventa' => $row->idventa,
                            'idventaref' => $row->idventaref
                        );
                    }
                }

                foreach ($saldos as $i => $row) {
                    $nota = $row->tiponota;
                    if($row->tiponota === 'notadebito'){ // Para cobrarle un adicional mas          
                        $tiponota = 'Nota de saldo';      
                    } 

                    if($row->tiponota === 'notacredito'){ // Para devolver dinero         
                        $tiponota = 'Nota de saldo';             
                    } 

                    $pagosrealizadas[] = array(
                        'documento' => $tiponota.' N° '.$row->numero, 
                        'fechaventa' => $row->fecha, 
                        'mediopago' => '', 
                        'total' =>  $row->monto, 
                        'nota' => $nota,
                        'iddocumentofiscal' => NULL,
                        'idventa' => NULL,
                        'idventaref' => NULL
                    );
                }

                $listcombox['pagosrealizadas'] = $pagosrealizadas;
            } 

            if (isset($request['gruposdx']) && $request['gruposdx'] === '1') {

                $grupodx = new grupodx();  
                
                $gruposDx = $grupodx->grid(['grupodx.idcicloatencion' => $id]);
                $citas = $cicloatencion->cicloCitasmedicas(['cicloatencion.idcicloatencion' => $id]);
                $diagnosticos = $citamedica->diagnosticomedico(['citamedica.idcicloatencion' => $id]);
                $tratamientos = $citamedica->tratamientomedicoLight($id); 
                $efectuadas = $terapia->terapiatratamientos(['cicloatencion.idcicloatencion' => $id, 'terapia.idestado' => 38], array('terapiatratamiento.idproducto', 'terapiatratamiento.idgrupodx', 'terapiatratamiento.cantidad'), TRUE);
                
                // BEGIN Citas medicas //
                $whereInIdcita = [];
                foreach ($citas as $item) { 
                    $item->ultimo_informe = null;
                    $whereInIdcita[] = $item->idcitamedica;
                }

                if (!empty($whereInIdcita)) {
                    $informes = $citamedica->informes([], $whereInIdcita); 
                    $informes = $this->ordenarMultidimension($informes, 'idinforme', SORT_ASC);

                    foreach ($citas as $item) { 
                        $ultimo_informemedico = null;
                        foreach ($informes as $informe) { 
                            if ($informe->idcitamedica === $item->idcitamedica) {
                                $ultimo_informemedico = $informe->archivo;
                            }
                        }

                        $item->ultimo_informe = $ultimo_informemedico;
                    }
                }
                $listcombox['citas'] = $citas;
                // END  Citas medicas //

                $tmpTratamientos = [];
                foreach ($tratamientos as $item) { 
                    $preciounit = null;
                    foreach ($presupuestodet as $row) {
                        if ($item->idproducto === $row->idproducto) {
                            $preciounit = $presupuesto->tipotarifa === 1 ? $row->preciounitregular : ($presupuesto->tipotarifa === 2 ? $row->preciounittarjeta : $row->preciounitefectivo);                           
                        }
                    }

                    if (!isset($tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx])) {
                        $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['idproducto'] = $item->idproducto; 
                        $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['nombreproducto'] = $item->nombreproducto; 
                        $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['cantmedico'] = 0; 
                        $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['cantefectivo'] = 0; 
                        $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['preciounit'] = (float) $preciounit; 
                        $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['idgrupodx'] = $item->idgrupodx;  
                    }

                    $tmpTratamientos[$item->idproducto.'-'.$item->idgrupodx]['cantmedico'] += $item->cantidad;  
                } 

                $tmpEfectuadas = [];
                foreach ($efectuadas as $item) { 
                    if (!isset($tmpEfectuadas[$item->idproducto.'-'.$item->idgrupodx])) {  
                        $tmpEfectuadas[$item->idproducto.'-'.$item->idgrupodx]['idproducto'] = $item->idproducto;
                        $tmpEfectuadas[$item->idproducto.'-'.$item->idgrupodx]['idgrupodx'] = $item->idgrupodx;  
                        $tmpEfectuadas[$item->idproducto.'-'.$item->idgrupodx]['cantefectivo'] = 0;  
                    }

                    $tmpEfectuadas[$item->idproducto.'-'.$item->idgrupodx]['cantefectivo'] += $item->cantidad;  
                }  

                foreach ($gruposDx as $row) { 
                    $row->diagnosticos = [];
                    $row->tratamientos = []; 

                    foreach ($diagnosticos as $diagnostico) {
                        if ($diagnostico->idgrupodx === $row->idgrupodx) {
                            $row->diagnosticos[] = $diagnostico;
                        } 
                    }

                    foreach ($tmpTratamientos as $tratamiento) {
                        if ($tratamiento['idgrupodx'] === $row->idgrupodx) { 

                            foreach ($tmpEfectuadas as $efectuada) {
                                if ($tratamiento['idgrupodx'] === $efectuada['idgrupodx'] && $tratamiento['idproducto'] === $efectuada['idproducto']) { 
                                    $tratamiento['cantefectivo'] = $efectuada['cantefectivo'];
                                } 
                            } 

                            $row->tratamientos[] = $tratamiento;
                        } 
                    } 
                }  

                $listcombox['gruposdx'] = $gruposDx;
                // dd($listcombox['gruposdx']);
            }

            return $this->crearRespuesta($presupuesto, 200, '', '', $listcombox);
        }                

        return $this->crearRespuestaError('Presupuesto no encotrado', 404);
    }  
    
    public function log($enterprise, $id) {
         
        $objPresupuesto = new presupuesto();  
        
        $data = $objPresupuesto->listaLogPresupuesto($id);
                
        return $this->crearRespuesta($data, 200); 
    } 
    
    public function logshow($enterprise, $id) {
          
        $objPresupuesto = new presupuesto();         
            
        $presupuesto =  $objPresupuesto->logpresupuesto(['idlogpresupuesto' => $id]); 
         
        if($presupuesto){           
            
            $listcombox = array(  
                'presupuestodet' => $objPresupuesto->logpresupuestodet($presupuesto->idlogpresupuesto) 
            );
            return $this->crearRespuesta($presupuesto, 200, '', '', $listcombox);
        }                

        return $this->crearRespuestaError('Presupuesto no encotrado', 404);
    }  
}
