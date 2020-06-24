<?php

namespace App\Models; 

class producto extends apimodel {

    protected $table = 'producto';
    protected $primaryKey = 'idproducto';
    public $timestamps = false;
    protected $fillable = [
        'idempresa',
        'idtipoproducto',
        'idarbol',
        'idunidadmedida',
        'idtipoingreso',
        'idmonedacompra',
        'idmonedaventa',
        'categoria',
        'codigo',
        'nombre',
        'marca',
        'valorcompra',
        'valorcompraigv',
        'valorventabase',
        'valorventaigv',
        'valorventa',
        'impuesto',
        'procedencia',
        'stockmin',
        'stockmax',
        'dsctomax',
        'comisionventa',
        'descripcion',
        'activo', 
        'autorizacionseguro',
        'ventaind',
        'seguroind',
        'tratamientoind',
        'ordencobro',
        'codigosunat'
    ];
    
    protected $hidden = ['idempresa'];

    public function grid($param, $likename = '', $items = '', $orderName = '', $orderSort = '', $fields = [], $whereNotIn = [], $whereIn = [], $idsede = '') {
        
        if(!empty($fields)) {
            $campos = $fields;
        } else {
            $campos = ['producto.idproducto', 'producto.idtipoproducto', 
                   'producto.categoria', 'producto.nombre', 'producto.codigo', 'unidadmedida.abreviatura as unidadmedidaabrev', 
                   'monedacompra.simbolo as monedacompraabrev', 'monedaventa.simbolo as monedaventaabrev', 'producto.valorcompra', 
                   'producto.valorventabase', 'producto.valorventaigv', 'producto.valorventa', 'producto.activo', 
                   'producto.stockmin', 'ordencobro', 'autorizacionseguro', 'ventaind', 'seguroind', 'tratamientoind', 'producto.codigosunat'];       
        }          
//      if(!empty($idsede)){             
//          array_push($campos, "partref as valorventa");
//      }
        
        if(!empty($idsede)){
            array_push($campos, "partref");
        }
        
        $select = \DB::table('producto') 
                ->leftJoin('unidadmedida', 'producto.idunidadmedida', '=', 'unidadmedida.idunidadmedida')
                ->leftJoin('moneda as monedacompra', 'producto.idmonedacompra', '=', 'monedacompra.idmoneda')
                ->leftJoin('moneda as monedaventa', 'producto.idmonedaventa', '=', 'monedaventa.idmoneda');
        
        if(!empty($idsede)){ 
            $select->leftJoin('tarifario', function($join) use ($idsede) {
                $join->on('producto.idproducto', '=', 'tarifario.idproducto')
                     ->where('tarifario.idsede', '=', $idsede);
            });                
        }
        
        $select->select($campos)
                ->where($param);
        
        if (!empty($likename)) {
            $select->where('producto.nombre', 'like', '%' . $likename . '%');
            //$select->whereRaw('sp_ascii(producto.nombre) ilike sp_ascii(?) ', ['%' . $likename . '%']);
        }
         
        
        if (!empty($whereNotIn)) {
                $select->whereNotIn('producto.idproducto', $whereNotIn);
        }
        
        if (!empty($whereIn)) {
            $select->whereIn('producto.idproducto', $whereIn);
        }
        
        if(!empty($items)) {
            $data = $select
                ->orderBy($orderName, $orderSort)
                ->paginate($items);
        } else {
            $data = $select 
                ->orderBy('producto.nombre', 'ASC') 
                ->get()->all();
        }
        
        foreach($data as $row){
            if(!empty($idsede))
                $row->valorventa = !empty($row->valorventa) ? $row->valorventa  : $row->partref;                       
        }
        
        return $data;
    }

    public function gridstock($param, $likename, $items = 25, $orderName, $orderSort) {

        $select = \DB::table('producto')  
                ->join('unidadmedida', 'producto.idunidadmedida', '=', 'unidadmedida.idunidadmedida')
                ->select('producto.idproducto', 'producto.categoria', 'producto.nombre', 'producto.codigo',  'producto.stockmin', 'producto.idtipoproducto', 'unidadmedida.abreviatura as unidadmedidaabrev')
                ->where($param);

        if (!empty($likename)) {
            $select->where('producto.nombre', 'like', '%' . $likename . '%');
            //$select->whereRaw('sp_ascii(producto.nombre) ilike sp_ascii(?) ', ['%' . $likename . '%']); 
        }
        $data = $select
                ->orderBy($orderName, $orderSort)
                ->paginate($items);

        return $data;
    } 

    public function metas($param, $whereIn = []) {

        $select = \DB::table('productometa') 
                ->where($param);

        if (!empty($whereIn)) {
            $select->whereIn('productometa.idproducto', $whereIn);
        }

        $data = $select->get()->all();
 
        return $data;
    } 

    public function metastotales($param, $whereIn = []) {

        $data = \DB::table('productometa') 
                ->select('idproducto', \DB::raw('SUM(ene) as ene'), \DB::raw('SUM(feb) as feb'), \DB::raw('SUM(mar) as mar'), \DB::raw('SUM(abr) as abr'),
                    \DB::raw('SUM(may) as may'), \DB::raw('SUM(jun) as jun'), \DB::raw('SUM(jul) as jun'), \DB::raw('SUM(ago) as ago'),
                    \DB::raw('SUM(seti) as seti'), \DB::raw('SUM(oct) as oct'), \DB::raw('SUM(nov) as nov'), \DB::raw('SUM(dic) as dic'))
                ->where($param)  
                ->whereIn('productometa.idproducto', $whereIn) 
                ->groupBy('productometa.idproducto') 
                ->get()->all();

        return $data;
    } 

    public function productoServicios($params, $whereIn = []) {

        $select = \DB::table('producto')
                ->join('productoservicio', 'producto.idproducto', '=', 'productoservicio.idproducto')
                ->join('producto as productoitem', 'productoservicio.idproductoitem', '=', 'productoitem.idproducto')
                ->join('unidadmedida', 'productoitem.idunidadmedida', '=', 'unidadmedida.idunidadmedida')
                ->select('producto.idproducto as idservicio', 'producto.nombre as nombreservicio', 
                         'productoitem.idproducto', 'productoitem.nombre as producto', 'productoitem.codigo', 
                         'unidadmedida.abreviatura as unidadabrev', 'productoservicio.cantidad', 'productoservicio.idproductoservicio')
                ->where($params)
                ->orderBy('productoitem.nombre', 'ASC');
        
        if (!empty($whereIn)) {
            $select->whereIn('producto.idproducto', $whereIn)
                   ->where('productoservicio.cantidad', '>' , 0);
        }
        
        $data = $select->get()->all();

        return $data;
    }

    public function GrabarProductoServicio($data, $idproducto) {
        \DB::table('productoservicio')->where('idproducto', $idproducto)->delete();
        \DB::table('productoservicio')->insert($data);
    }

    

    public function GrabarMetas($data, $where) {
        \DB::table('productometa')->where($where)->delete();
        \DB::table('productometa')->insert($data);
    }
     
    public function GrabarTarifas($data) {

        if (isset($data['tarifario']) && isset($data['tarifario']['idtarifario'])) {
            return \DB::table('tarifario')->where('idtarifario', $data['tarifario']['idtarifario'])->update($data['tarifario']);
        } else {
            if (isset($data['tarifario'])) {
                //\DB::table('tarifario')->insert($data['tarifario']);
                return \DB::table('tarifario')->insertGetId($data['tarifario'], 'idtarifario');
            }
        }
    }

    public function GrabarTarifasDeleteInset($data, $idproducto) {
        \DB::table('tarifario')->where('idproducto', $idproducto)->delete();
        \DB::table('tarifario')->insert($data);
    }

    public function listaProducto($params) {
        $data = \DB::table('producto')
                ->select('producto.*')
                ->where($params)
                ->get()->all();
        return $data;
    }

    public function updateProducto($data, $where) {
        \DB::table('producto')->where($where)->update($data);
    }

    public function validadorDataRelacionada($id) {
        $data = \DB::table('ventadet')
            ->select('idproducto')
            ->where('idproducto', $id)
            ->whereNull('deleted')
            ->get()
            ->all();
            
        if (!empty($data)) {              
            return ['validator' => true, 'message' => 'Tiene ventas. No puede ser eliminado.'];
        } 

        $data = \DB::table('productoservicio')->select('idproductoitem')->where('idproductoitem', $id)->get()->all();
        if (!empty($data)) {
            return ['validator' => true, 'message' => 'Tiene datos en materiales por tratamiento. No puede ser eliminado.'];
        }

        $data = \DB::table('tratamientomedico')->select('idproducto')->where('idproducto', $id)->whereNull('deleted')->get()->all();
        if (!empty($data)) {
            return ['validator' => true, 'message' => 'Tiene tratamientos. No puede ser eliminado.'];
        }

        $data = \DB::table('terapiatratamiento')->select('idproducto')->where('idproducto', $id)->whereNull('deleted')->get()->all();
        if (!empty($data)) {
            return ['validator' => true, 'message' => 'Tiene terapias. No puede ser eliminado.'];
        }

        return ['validator' => false];
    }

    public function producto($idproducto, $idsede) {
                
        $campos = ['producto.idproducto', 'producto.idtipoproducto',  
                    'producto.valorventa', 'tarifario.partref', 'tarifario.partcta', 'tarifario.partsta', 'tarifario.sscoref', 'tarifario.sscocta', 'tarifario.sscosta', 
                    'tarifario.sccocien', 'tarifario.scconoventacinco', 'tarifario.scconoventa','tarifario.sccoochentacinco', 'tarifario.sccoochenta', 'tarifario.sccosetentacinco', 'tarifario.sccosetenta', 'tarifario.sccosesentacinco', 'tarifario.sccosesenta', 'tarifario.sccocincuentacinco', 'tarifario.sccocincuenta', 'tarifario.sccocuarentacinco', 'tarifario.sccocuarenta', 
                    'tarifario.sccotreintacinco', 'tarifario.sccotreinta', 'tarifario.sccoveintecinco', 'tarifario.sccoveinte', 'tarifario.sccoquince', 'tarifario.sccodiez', 'tarifario.sccocero'];
                
        $data = \DB::table('producto') 
                ->leftJoin('tarifario', function($join) use ($idsede) {
                    $join->on('producto.idproducto', '=', 'tarifario.idproducto')
                         ->where('tarifario.idsede', '=', $idsede);
                })   
                ->select($campos) 
                ->where('producto.idproducto', $idproducto) 
                ->first();    
         
        return $data; 
    }
}
