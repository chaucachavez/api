<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class modulo extends Model {

    protected $table = 'modulo';
    protected $primaryKey = 'idmodulo';
    public $timestamps = false;
    protected $fillable = [
        'parent',
        'nombre',
        'icono',
        'url',
        'orden',
        'nivel' 
    ];
    
    public function grid($params) {
        
        $data = \DB::table('modulo')            
                ->join('moduloempresa', 'modulo.idmodulo', '=', 'moduloempresa.idmodulo')                
                ->select('modulo.idmodulo', 'modulo.parent', 'modulo.nombre')
                ->where($params) 
                ->orderBy('nombre', 'asc')
                ->get()->all();
        
        return $data;
    } 
    
    public function insertarModuloEmpresa($data){
        \DB::table('moduloempresa')->insert($data);
    }
    
    public function eliminarModuloEmpresa($data){        
        \DB::table('moduloempresa')->where($data)->delete();
    }
    
    
    public function ListaModules($param) {
        $data = \DB::table('empresa')             
                ->join('moduloempresa', 'empresa.idempresa', '=', 'moduloempresa.idempresa')
                ->join('modulo', 'moduloempresa.idmodulo', '=', 'modulo.idmodulo')
                ->select('modulo.idmodulo', 'modulo.parent', 'modulo.orden', 'modulo.nombre', 'modulo.url as urlvista', 'modulo.icono', 'modulo.nivel',
                        'empresa.idempresa', 'empresa.url', 'empresa.razonsocial')
                ->where($param) 
                ->orderBy('modulo.parent', 'ASC')
                ->orderBy('modulo.orden', 'ASC')
                ->get()->all();              
                
        $modules = array();
        foreach ($data as $fila) {
            $modules[$fila->url]['name'] = $fila->razonsocial;   
            $modules[$fila->url]['modules'][] = $fila;
        }

        foreach ($modules as $urlente => $fila) {           

            $modules[$urlente]['modules'] = $this->_ordenarModuleEnterprise($fila['modules']);

            $newmodulos = array();
            foreach ($modules[$urlente]['modules'] as $valor) {
                if ($valor['level'] == 1) {
                    $newmodulos[$valor['idmodulo']]['id'] = $valor['idmodulo'];                    
                    $newmodulos[$valor['idmodulo']]['name'] = $valor['descripcion'];  
                    $newmodulos[$valor['idmodulo']]['level'] = $valor['level'];
                    $newmodulos[$valor['idmodulo']]['icon'] = $valor['iconmodu'];
                    $newmodulos[$valor['idmodulo']]['ordenT'] = $valor['ordenT'];
                    $newmodulos[$valor['idmodulo']]['parent'] = $valor['parent'];
                }
                if ($valor['level'] == 2) {
                    $newmodulos[$valor['parent']]['menus'][$valor['idmodulo']]['id'] = $valor['idmodulo'];                    
                    $newmodulos[$valor['parent']]['menus'][$valor['idmodulo']]['name'] = $valor['descripcion'];                    
                    $newmodulos[$valor['parent']]['menus'][$valor['idmodulo']]['uri'] = $valor['urlvista'];
                    $newmodulos[$valor['parent']]['menus'][$valor['idmodulo']]['children'] = $valor['condicion'];
                    $newmodulos[$valor['parent']]['menus'][$valor['idmodulo']]['icon'] = $valor['iconmodu'];
                    $newmodulos[$valor['parent']]['menus'][$valor['idmodulo']]['ordenT'] = $valor['ordenT'];
                    $newmodulos[$valor['parent']]['menus'][$valor['idmodulo']]['parent'] = $valor['parent'];
                }
                if ($valor['level'] == 3) {
                    $newmodulos[$valor['moduloselect']]['menus'][$valor['parent']]['options'][$valor['idmodulo']]['id'] = $valor['idmodulo'];                    
                    $newmodulos[$valor['moduloselect']]['menus'][$valor['parent']]['options'][$valor['idmodulo']]['name'] = $valor['descripcion'];
                    $newmodulos[$valor['moduloselect']]['menus'][$valor['parent']]['options'][$valor['idmodulo']]['uri'] = $valor['urlvista'];
                    $newmodulos[$valor['moduloselect']]['menus'][$valor['parent']]['options'][$valor['idmodulo']]['ordenT'] = $valor['ordenT'];
                    $newmodulos[$valor['moduloselect']]['menus'][$valor['parent']]['options'][$valor['idmodulo']]['parent'] = $valor['parent'];
                    $newmodulos[$valor['moduloselect']]['menus'][$valor['parent']]['options'][$valor['idmodulo']]['modulopadre'] = $valor['moduloselect'];                    
                }
            }
            $modules[$urlente]['modules'] = $newmodulos;
        }
         
        /* Esto es si queremos que los indices no sean los id de modulos, sino un correlativo.
         * Esta nueva matriz hara que el orden en angularjs se refleje ya que se trata de indice ascendente.
         * Si omito esto el AngularJs reordenara los indices ascedente que no es otro que los idmodulos*/
        
        $modulesFormat = [];
        foreach($modules as $pk => $row){
            $modulesFormat[$pk] = $row;            
            $im = 0;
            unset($modulesFormat[$pk]['modules']);            
            foreach($row['modules'] as $modulo){        
                $modulesFormat[$pk]['modules'][$im] = $modulo;
                if(!empty($modulo['menus'])){
                    $ime = 0;           
                    unset($modulesFormat[$pk]['modules'][$im]['menus']);
                    foreach($modulo['menus'] as $menu){                                               
                        $modulesFormat[$pk]['modules'][$im]['menus'][$ime] = $menu; 
                        if(!empty($menu['options'])){
                            $io = 0;
                            unset($modulesFormat[$pk]['modules'][$im]['menus'][$ime]['options']);
                            foreach($menu['options'] as $option){ 
                                $modulesFormat[$pk]['modules'][$im]['menus'][$ime]['options'][$io] = $option;
                                $io++;
                            }                            
                        }
                        $ime++;
                    }                    
                }
                $im++;
            }
        }
        
        return $modulesFormat;
        //return $modules;
    }


    private function _ordenarModuleEnterprise($data) {
        $tablaorden = array();
        foreach ($data as $fila) {
            $tablaorden[$fila->idmodulo] = '';
        }

        $tablaorden = $this->_ordenarPorJerarquia($tablaorden, $data);

        $matriz = array();
        $matrizTmp = $data;
        $i = 0;
        foreach ($data as $fila) {
            $condic = 0;
            foreach ($matrizTmp as $row) {
                if ($fila->idmodulo == $row->parent) {
                    $condic = 1;
                    break;
                }
            }

            $matriz[$i] = array(
                'idmodulo' => $fila->idmodulo,
                'parent' => $fila->parent,
                'descripcion' => $fila->nombre,
                'archivo' => $fila->url,
                'iconmodu' => $fila->icono,
                'level' => $fila->nivel,
                'orden' => $tablaorden[$fila->idmodulo],
                'condicion' => $condic,
                'urlvista' => $fila->urlvista,
                'ordenT' =>$fila->orden
            );
             
            $i++;
            
        }

        $data1 = $matriz;
        $data2 = $matriz;
        $data3 = $matriz;
        $nuevaMatriz = array();
        foreach ($data1 as $fila1) {
            if ($fila1['orden'] == '1') {
                $nuevaMatriz[] = $fila1;
                foreach ($data2 as $fila2) {
                    if ($fila2['orden'] == '2' && $fila1['idmodulo'] == $fila2['parent']) {
                        $nuevaMatriz[] = $fila2;
                        foreach ($data3 as $fila3) {
                            if ($fila3['orden'] == '3' && $fila2['idmodulo'] == $fila3['parent']) {
                                $nuevaMatriz[] = $fila3;
                            }
                        }
                    }
                }
            }
        }

        $data = array();
        $idmodulotmp = '';
        foreach ($nuevaMatriz as $fila) {
            if ($fila['orden'] == '1')
                $idmodulotmp = $fila['idmodulo'];

            $fila['moduloselect'] = $idmodulotmp;
            $data[] = $fila;
        }
        //dd($data);
        return $data;
    }

    private function _ordenarPorJerarquia($tablaorden, $data) {
        $data1 = $data;
        $data2 = $data;
        $orden = '';
        foreach ($data1 as $fila1) {
            $encontrado = FALSE;
            foreach ($data2 as $fila2) {
                if ($fila1->parent == $fila2->idmodulo) {
                    $encontrado = TRUE;
                    $orden = $tablaorden[$fila1->parent];
                    if (!empty($orden)) {
                        $orden = $orden + 1;
                        $tablaorden[$fila1->idmodulo] = $orden;
                        break;
                    }
                }
            }
            if (!$encontrado) {
                $tablaorden[$fila1->idmodulo] = 1;
            }
        }
        //$vacio = false;
        $entro = false;
        foreach ($tablaorden as $ind => $orden) {
            if (empty($orden)) {
                $entro = true;
                break;
            }
        }
        if ($entro) {
            $tablaorden = $this->_ordenarPorJerarquia($tablaorden, $data);
        }
        return $tablaorden;
    }

}
