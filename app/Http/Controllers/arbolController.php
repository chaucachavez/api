<?php

namespace App\Http\Controllers;

use Excel;
use Illuminate\Http\Request;
use \Firebase\JWT\JWT;

use App\Models\empresa;
use App\Models\producto;
use App\Models\arbol;

class arbolController extends Controller {
     
    public function index(Request $request, $enterprise) {
 
        $paramsTMP = $request->all();
        $empresa = new empresa();
        $arbol = new arbol();

        $paramsTMP['idempresa'] = $empresa->idempresa($enterprise);

        // dd('bebe', $paramsTMP); 
        $data = $arbol->grid($paramsTMP);  
        
        $data = $this->procesarRaiz($data, ['ID' => 'idarbol', 'PARENT' => 'parent', 'CHILDREN' => 'children']);
        // dd($data);
        // SELECT * FROM `arbol` where idempresa = 1 and idcategoria = 3 and parent is null ORDER BY `arbol`.`codigo` ASC

        $others = [];

        if ($paramsTMP['idcategoria'] === '3') { 
             
            $others = arbol::select('idarbol', 'parent', 'codigo','nombre', 'color')
                ->where([
                    'idempresa' => $empresa->idempresa($enterprise),
                    'idcategoria' => $paramsTMP['idcategoria'] 
                ])
                ->whereNull('parent')
                ->orderBy('codigo', 'asc')
                ->get()->all();
        }

        
         return $this->crearRespuesta($data, 200, '', '', $others);

                 
    }

    public function descarga(Request $request, $enterprise) {
 
        $paramsTMP = $request->all();
        $empresa = new empresa();
        $arbol = new arbol();

        $param = array();
        $param['idempresa'] = $empresa->idempresa($enterprise);
        $param['idcategoria'] = $paramsTMP['idcategoria']; 

        // dd('bebe', $paramsTMP); 
        $data = $arbol->grid($param);         
        $dataTemp = $this->procesarRaiz($data, ['ID' => 'idarbol', 'PARENT' => 'parent', 'CHILDREN' => 'children']);
        // dd($data);

        if (isset($paramsTMP['formato']) && !empty($paramsTMP['formato'])) {
            if(in_array($paramsTMP['formato'], ['xls', 'xlsx'])) {  

                // dd('Hola', $dataTemp);

                $data = array();  
                $i = 0;
                foreach($dataTemp as $row) {   
                    $data[$i]['TIPO'] = 'CATEGORIA'; 
                    $data[$i]['CODIGO'] = $row['codigo']; 
                    $data[$i]['CATEGORIA'] = $row['nombre']; 
                    $i++;

                    foreach($row['children'] as $fila) {
                        $data[$i]['TIPO'] = 'SUBCATEGORIA'; 
                        $data[$i]['CODIGO'] = $fila['codigo']; 
                        $data[$i]['CATEGORIA'] = $fila['nombre']; 
                        $i++;
                    }
                    $i++;
                }

                // dd($data); 

                Excel::download('Reporte_' . date('Y-m-d H:i:s'), function($excel) use($data) {
                    $excel->sheet('Data', function($sheet) use($data) {
                        $sheet->fromArray($data);
                    }); 
                })->export($paramsTMP['formato']);
            }
        }

    }

    public function store(Request $request, $enterprise) {

        $empresa = new empresa();

        $idempresa = $empresa->idempresa($enterprise);

        $request = $request->all();
        $request['idempresa'] = $idempresa;

        $arbol = arbol::create($request);

        return $this->crearRespuesta('"' . $arbol->nombre . '" ha sido creado.', 201);
    }

    public function update(Request $request, $enterprise, $id) {

        $empresa = new empresa();
        $producto = new producto();

        $arbol = arbol::find($id);

        if ($arbol) {
            $request = $request->all();

            \DB::beginTransaction();
            try {
                //Graba en 1 tablaa(arbol) y actualiza en 1 tabla producto                  
                if ($arbol->nombre !== $request['nombre']) {
                    $where = array(
                        'idempresa' => $empresa->idempresa($enterprise),
                        'idarbol' => $id
                    );

                    $producto->updateProducto(array('categoria' => $request['nombre']), $where);
                }
                $arbol->fill($request);
                $arbol->save();
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('"' . $arbol->nombre . '" ha sido .actualizado.', 201);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un categor&iacute;a', 404);
    }

    public function destroy(Request $request, $enterprise, $id) {

        $arbol = arbol::find($id);
        $params = $request->all();

        if ($arbol) {
            // VALIDACIONES             
            // 1ERA VALIDACION
            $params['idempresa'] = $arbol->idempresa;
            $id = (int) $id;
            $data = $this->procesarRaiz($arbol->grid($params), ['ID' => 'idarbol', 'PARENT' => 'parent', 'CHILDREN' => 'children'], $id, true);

            $whereIn = [];
            foreach ($data as $row) {
                $whereIn[] = $row['idarbol'];
            }

            $count = producto::whereIn('idarbol', $whereIn)->count();
            if ($count > 0) {
                return $this->crearRespuesta('"' . $arbol->nombre . '" no puede ser eliminado. Est&aacute; asignado a productos.', [200, 'info']);
            }
            //FIN 1ERA VALIDACION

            if($arbol->idcategoria === 5) {
                $return = $arbol->validadorDataRelacionada($id);
                if ($return['validator']) {
                    return $this->crearRespuesta($return['message'], [200, 'info']);
                }
            } 

            if($arbol->idcategoria === 6) {
                $return = $arbol->validadorDataRelacionada6($id);
                if ($return['validator']) {
                    return $this->crearRespuesta($return['message'], [200, 'info']);
                }
            } 

            \DB::beginTransaction();
            try { 
                 arbol::destroy($whereIn);
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('"' . $arbol->nombre . '" a sido eliminado.', 200);
        }

        return $this->crearRespuestaError('Categor&iacute;a no encotrado.', 404);
    }

}
