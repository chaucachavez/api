<?php

namespace App\Http\Controllers;

use \Firebase\JWT\JWT;
use Illuminate\Http\Request;

use App\Models\empresa;
use App\Models\producto;
use App\Models\tarifario;
use App\Models\sede;

class productoController extends Controller {
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $producto = new producto();

        $param = array();
        $param['producto.idempresa'] = $empresa->idempresa($enterprise);

        if (isset($paramsTMP['idtipoproducto'])) {
            $param['producto.idtipoproducto'] = $paramsTMP['idtipoproducto'];
        }
        
        $idsede = null;
        if (isset($paramsTMP['idsede']) &&  !empty($paramsTMP['idsede'])) {
            $idsede = $paramsTMP['idsede'];
        }
        
        if (isset($paramsTMP['ventaind']) &&  !empty($paramsTMP['ventaind'])) {
            $param['producto.ventaind'] = $paramsTMP['ventaind'];
        }

        if (isset($paramsTMP['seguroind']) &&  !empty($paramsTMP['seguroind'])) {
            $param['producto.seguroind'] = $paramsTMP['seguroind'];
        }

        if (isset($paramsTMP['tratamientoind']) &&  !empty($paramsTMP['tratamientoind'])) {
            $param['producto.tratamientoind'] = $paramsTMP['tratamientoind'];
        }

        $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'producto.nombre';
        $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'ASC';
        $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;

        $like = !empty($paramsTMP['likeproducto']) ? trim($paramsTMP['likeproducto']) : '';
        $data = $producto->grid($param, $like, $pageSize, $orderName, $orderSort, '', array(22, 58), '', $idsede);

        if ($data) {
            return $this->crearRespuesta($data->items(), 200, $data->total());
        }

        return $this->crearRespuestaError('Producto no encontrado', 404);
    } 
    
    public function indextarifas(Request $request, $enterprise) {

        $request = $request->all();
        
        $tarifario = tarifario::where(['idsede' => $request['idsede'], 'idproducto' => $request['idproducto']])->first();

        if ($tarifario) {
            return $this->crearRespuesta($tarifario, 200);
        }

        return $this->crearRespuestaError('Tarifario no encotrado', 404);
    }
    
    public function metas(Request $request, $enterprise) {

        $producto = new producto();

        $request = $request->all();
        
        $data = $producto->metas(['productometa.ano' => $request['ano'], 'productometa.idsede' => $request['idsede'], 'productometa.idproducto' => $request['idproducto']]);
 
        return $this->crearRespuesta($data, 200);
    }

    public function material(Request $request, $enterprise, $id) {
        
        $empresa = new empresa();
        $producto = new producto();
        
        $param = array(
            'producto.idproducto' => $id,
            'producto.idempresa' => $empresa->idempresa($enterprise),
        );
        $listcombox['productos'] = $producto->productoServicios($param);  

        return $this->crearRespuesta(null, 200, '', '', $listcombox);        
    }
     
    private function setStock($data, $idempresa) {

        $producto = new producto();

        $whereIns = [];
        foreach ($data->items() as $row) {
            $whereIns[] = $row->idproducto;
            $row->stock = 0;
        }

        $stock = [];
        // Obtiene Ingresos y Salidas de productos de Almacendet, agrupado por(idproducto, movimiento) 
        if (!empty($whereIns)) {
            $stock = $producto->productostock($whereIns, $idempresa);
        }

        //Actualiza grid paginacion con Stock
        if (!empty($stock)) {
            foreach ($data->items() as $row) {
                if (isset($stock[$row->idproducto])) {
                    $row->stock = $stock[$row->idproducto]['stock'];
                    $row->stockalert = ($stock[$row->idproducto]['stock'] < $row->stockmin) ? true : false;
                }
            }
        }

        return $data;
    }

    public function show($enterprise, $id) {

        $empresa = new empresa();
        $sede = new sede();
        
        $producto = producto::find($id);

        if ($producto) {
            $idempresa = $producto->idempresa;

            $listcombox = array( 
                'unidadmedidas' => $empresa->unidadmedidas($idempresa),  
                'anos' => $empresa->anos($idempresa)
            ); 

            if ($producto->idtipoproducto === 2) {
                
                $param = array('producto.idproducto' => $id);                
                $listcombox['productoservicios'] = $producto->productoServicios($param); 
                
                $fieldsProducto = ['producto.idproducto', 'producto.nombre',  'producto.codigo', 'unidadmedida.abreviatura'];
                $listcombox['productos'] = $producto->grid(['producto.idtipoproducto' => 1, 'producto.idempresa' => $idempresa], '', '', '', '', $fieldsProducto); //Producto

                $param = array(
                    'sede.idempresa' => $idempresa,
                    'entidadsede.identidad' => $this->objTtoken->my
                ); 
                $listcombox['sedes'] = $sede->autorizadas($param, $this->objTtoken->mysede); 
            }

            return $this->crearRespuesta($producto, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Producto no encotrado', 404);
    }

    public function newproducto($enterprise) {
        
        $empresa = new empresa();
        $sede = new sede();
        $producto = new producto();
        
        $idempresa = $empresa->idempresa($enterprise);
        
        $param = array(
            'sede.idempresa' => $idempresa,
            'entidadsede.identidad' => $this->objTtoken->my
        );
        
        $listcombox = array( 
            'unidadmedidas' => $empresa->unidadmedidas($idempresa),  
            'sedes' => $sede->autorizadas($param),
            'anos' => $empresa->anos($idempresa)
        );
        
        $fieldsProducto = ['producto.idproducto', 'producto.nombre',  'producto.codigo', 'unidadmedida.abreviatura'];                
        $listcombox['productos'] = $producto->grid(['producto.idtipoproducto' => 1, 'producto.idempresa' => $idempresa], '', '', '', '', $fieldsProducto); //Producto
        
        return $this->crearRespuesta([], 200, '', '', $listcombox);
    }

    public function store(Request $request, $enterprise) {

        $empresa = new empresa();
        $sede = new sede();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        $request['producto']['idempresa'] = $idempresa;

        //VALIDACIONES 

        if (!empty($request['producto']['codigo'])) {
            $codigoproducto = $request['producto']['codigo'];
            $producto = producto::where(['idempresa' => $idempresa,'codigo' => $codigoproducto])->first();
            if ($producto) {
                return $this->crearRespuesta('No puede registrarse, el c&oacute;digo de producto "' . $codigoproducto . '" ya existe. Pertenece a ' . $producto->nombre, [200, 'info']);
            }
        }

        $dataProdServ = [];

        \DB::beginTransaction();
        try {
            //Graba en 1 tablaa(producto)            
            $producto = producto::create($request['producto']); 
            $id = $producto->idproducto;
            
            if ($idempresa === 2) {    
                if ($request['producto']['idtipoproducto'] === 2 && isset($request['producto']['valorventa']) && !empty($request['producto']['valorventa'])) {

                    $data = [];
                    $datasede = $sede->sedes($idempresa, ['sede.idsede', 'sede.nombre']);
                    foreach ($datasede as $row) {                        
                        $data[] = array(                        
                            'idempresa' => $idempresa,
                            'idsede' => $row->idsede,
                            'idproducto' => $id,
                            'partref' => $request['producto']['valorventa'], 
                            'partcta' => $request['producto']['valorventa'], 
                            'partsta' => $request['producto']['valorventa'], 
                            'sscoref' => $request['producto']['valorventa'], 
                            'sscocta' => $request['producto']['valorventa'], 
                            'sscosta' => $request['producto']['valorventa']
                        );
                    }   

                    $producto->GrabarTarifasDeleteInset($data, $id);                
                }
            } else {
                // $request['tarifario']['idempresa'] = $idempresa;      
                $request['tarifario']['idproducto'] = $id;   

                if(isset($request['tarifario']['idsede'])){
                    $producto->GrabarTarifas($request);
                }
            }

            if(isset($request['productoservicio'])){
                foreach ($request['productoservicio'] as $row) {
                    $dataProdServ[] = ['idproducto' => $id, 'idproductoitem' => $row['idproducto'], 'cantidad' => $row['cantidad']];
                }
                $producto->GrabarProductoServicio($dataProdServ, $id);
            }

            if(isset($request['productometa']) && isset($request['ano'])){
                $data = [];
                foreach ($request['productometa'] as $row) {
                    $update = array(                        
                        'idempresa' => $idempresa,
                        'idsede' => $request['idsede'],
                        'idproducto' => $id,
                        'ano' => $request['ano'], 
                        'ene' => $row['ene'], 
                        'feb' => $row['feb'], 
                        'mar' => $row['mar'], 
                        'abr' => $row['abr'], 
                        'may' => $row['may'], 
                        'jun' => $row['jun'], 
                        'jul' => $row['jul'], 
                        'ago' => $row['ago'], 
                        'seti' => $row['seti'], 
                        'oct' => $row['oct'], 
                        'nov' => $row['nov'],
                        'dic' => $row['dic']
                    );
                    $data = $update;
                }   
                $producto->GrabarMetas($data, 
                    [
                        'idproducto' => $id, 
                        'idsede' => $request['idsede'], 
                        'ano' => $request['ano']
                    ]
                );                 
            }

            // return $this->crearRespuesta('No  ' , [200, 'info'], '', '', $request['productometa']);
            
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta('"' . $producto->nombre . '" ha sido creado.', 201);
    }

    public function update(Request $request, $enterprise, $id) {
        $empresa = new empresa();
        $sede = new sede();
        
        $idempresa = $empresa->idempresa($enterprise);

        $producto = producto::find($id);

        if ($producto) {
            $request = $request->all();
            
            $request['producto']['codigo'] = trim($request['producto']['codigo']);
            
            if(empty($request['producto']['codigo'])){
               $request['producto']['codigo'] = null;     
            }

            //VALIDACIONES 
            $codigoproducto = $request['producto']['codigo'];
            $consultado = false;
            if ($codigoproducto !== $producto->codigo && !empty($codigoproducto)) {
                $consultado = true;
                $row = producto::where('codigo', '=', $codigoproducto)->first();
                if ($row) {
                    return $this->crearRespuesta('No puede registrarse, el c&oacute;digo de producto "' . $codigoproducto . '" ya existe. Pertenece a ' . $row->nombre, [200, 'info']);
                }
            }

            $producto->fill($request['producto']);

            \DB::beginTransaction();
            try {
                //Graba en 2 tablaa(producto, tarifario)                                   
                $producto->save();

                $idtarifario = null;
                if ($idempresa === 2) {    
                    if ($request['producto']['idtipoproducto'] === 2 && isset($request['producto']['valorventa']) && !empty($request['producto']['valorventa'])) {
                        $data = [];
                        $datasede = $sede->sedes($idempresa, ['sede.idsede', 'sede.nombre']);
                        foreach ($datasede as $row) {                        
                            $data[] = array(                        
                                'idempresa' => $idempresa,
                                'idsede' => $row->idsede,
                                'idproducto' => $id,
                                'partref' => $request['producto']['valorventa'], 
                                'partcta' => $request['producto']['valorventa'], 
                                'partsta' => $request['producto']['valorventa'], 
                                'sscoref' => $request['producto']['valorventa'], 
                                'sscocta' => $request['producto']['valorventa'], 
                                'sscosta' => $request['producto']['valorventa']
                            );
                        }   

                        $producto->GrabarTarifasDeleteInset($data, $id);  
                    }
                } else {
                    $idtarifario = $producto->GrabarTarifas($request);
                }

                if(isset($request['productoservicio'])){
                    $dataProdServ = [];
                    foreach ($request['productoservicio'] as $row) {
                        $dataProdServ[] = ['idproducto' => $id, 'idproductoitem' => $row['idproducto'], 'cantidad' => $row['cantidad']];
                    }
                    $producto->GrabarProductoServicio($dataProdServ, $id);
                }

                if(isset($request['productometa']) && isset($request['ano'])){
                    $data = [];
                    foreach ($request['productometa'] as $row) {
                        $update = array(
                            'idempresa' => $idempresa,
                            'idsede' => $request['idsede'],
                            'idproducto' => $id,
                            'ano' => $request['ano'], 
                            'ene' => $row['ene'], 
                            'feb' => $row['feb'], 
                            'mar' => $row['mar'], 
                            'abr' => $row['abr'], 
                            'may' => $row['may'], 
                            'jun' => $row['jun'], 
                            'jul' => $row['jul'], 
                            'ago' => $row['ago'], 
                            'seti' => $row['seti'], 
                            'oct' => $row['oct'], 
                            'nov' => $row['nov'],
                            'dic' => $row['dic']
                        );
                        $data = $update;
                    }   
                    $producto->GrabarMetas($data, ['idproducto' => $id, 'idsede' => $request['idsede'], 'ano' => $request['ano']]);                 
                }
                
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();
            //$idtarifario para caso sea una nueva tarifa
            return $this->crearRespuesta('Producto "' . $producto->nombre . '" ha sido editado. ', 200,'','',$idtarifario);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un producto', 404);
    } 
    
    public function destroy($enterprise, $id) {

        $producto = producto::find($id);

        if ($producto) {

            $return = $producto->validadorDataRelacionada($id);
            if ($return['validator']) {
                return $this->crearRespuesta($return['message'], [200, 'info']);
            }
            
            \DB::beginTransaction();
            try {
                //Elimina en 1 tablas(producto, productoservicio)        
                \DB::table('productometa')->where('idproducto', $id)->delete();
                \DB::table('tarifario')->where('idproducto', $id)->delete();
                $producto->GrabarProductoServicio([], $id); 
                $producto->delete();
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Producto "' . $producto->nombre . '" a sido eliminado.', 200);
        }
        return $this->crearRespuestaError('producto no encotrado', 404);
    }

}
