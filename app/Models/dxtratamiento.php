<?php

namespace App\Models; 

class dxtratamiento extends apimodel {

    protected $table = 'dxtratamiento';
    protected $primaryKey = 'iddxtratamiento';
    public $timestamps = false;
    protected $fillable = [
        'idempresa',
        'grupodx',
        'dxi',
        'dxii',
        'dxiii',
        'eva',
        'grupotra',
        'puntuacion',
        'aprobacion', 
        'fechaaprobacion', 
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];
    protected $hidden = ['idempresa']; 
    
    public function dxtratamiento($param) {
        $data = \DB::table('dxtratamiento')                      
                    ->select('dxtratamiento.iddxtratamiento', 'dxtratamiento.grupodx', 'dxtratamiento.eva', 'dxtratamiento.grupotra',  
                             'dxtratamiento.puntuacion', 'dxtratamiento.aprobacion')
                    ->where($param) 
                    ->whereNull('dxtratamiento.deleted') 
                    ->first();  
        
        return $data;
    }
     
    
    public function grid($param, $items = '', $orderName = '', $orderSort = '', $iddiagnostico = null) {                
        
        $select = \DB::table('dxtratamiento')       
                    ->select('dxtratamiento.iddxtratamiento', 'dxtratamiento.grupodx', 'dxtratamiento.eva', 
                        'dxtratamiento.grupotra',  'dxtratamiento.puntuacion', 'dxtratamiento.aprobacion', 
                        'created.entidad as created', 'updated.entidad as updated', 
                        // 'dxtratamiento.created_at', 'dxtratamiento.updated_at',
                        'dxtratamiento.created_at as createdat', 'dxtratamiento.updated_at as updatedat', 
                        'dxtratamiento.fechaaprobacion')
                    ->join('entidad as created', 'dxtratamiento.id_created_at', '=', 'created.identidad')   
                    ->leftJoin('entidad as updated', 'dxtratamiento.id_updated_at', '=', 'updated.identidad')
                    ->whereNull('dxtratamiento.deleted')
                    ->where($param);
         
        if($iddiagnostico){
            $filtroeva = isset($param['dxtratamiento.eva']) ? (" AND dxtratamiento.eva = ".$param['dxtratamiento.eva']) : "";
            $select->whereRaw("(dxtratamiento.dxi = ". $iddiagnostico . " OR 
                              dxtratamiento.dxii = " . $iddiagnostico . " OR 
                              dxtratamiento.dxiii = " . $iddiagnostico .") " . $filtroeva);
        } 

        $orderName = !empty($orderName) ? $orderName : 'dxtratamiento.grupodx';
        $orderSort = !empty($orderSort) ? $orderSort : 'dxtratamiento.asc';

        $select->orderBy($orderName, $orderSort);
        $select->orderBy('dxtratamiento.grupotra', 'asc');
        $select->orderBy('dxtratamiento.eva', 'asc');

        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        }

        foreach ($data as $row) { 
            $row->createdat = $this->formatFecha(substr($row->createdat, 0, 10)); 
             $row->updatedat = $this->formatFecha(substr($row->updatedat, 0, 10)); 
            // $row->created_at = $this->formatFecha($row->created_at);
            // $row->updated_at = $this->formatFecha($row->updated_at);
        }
        //dd(count($data));
        return $data;
    }

    public function diagnosticos($param, $items = '', $orderName = '', $orderSort = '') {

        $data = \DB::table('dxtratamiento')       
                ->join('diagnostico as diagnosticoi', 'dxtratamiento.dxi', '=', 'diagnosticoi.iddiagnostico')
                ->leftJoin('diagnostico as diagnosticoii', 'dxtratamiento.dxii', '=', 'diagnosticoii.iddiagnostico')
                ->leftJoin('diagnostico as diagnosticoiii', 'dxtratamiento.dxiii', '=', 'diagnosticoiii.iddiagnostico')
                ->select('diagnosticoi.iddiagnostico as iddiagnosticoi', 'diagnosticoi.nombre as nombrediagnosticoi', 
                         'diagnosticoii.iddiagnostico as iddiagnosticoii', 'diagnosticoii.nombre as nombrediagnosticoii',
                         'diagnosticoiii.iddiagnostico as iddiagnosticoiii', 'diagnosticoiii.nombre as nombrediagnosticoiii')
                ->where($param) 
                ->whereNull('dxtratamiento.deleted')                
                ->distinct()
                ->get()->all();
        
        $datatmp = [];
        $diagnosticosIn = [];
     
        foreach ($data as $row){
            if (!in_array($row->iddiagnosticoi, $diagnosticosIn)) {
                $diagnosticosIn[] = $row->iddiagnosticoi;
                $datatmp[] = array('iddiagnostico' => $row->iddiagnosticoi, 'nombre' => $row->nombrediagnosticoi);
            }
            
            if ($row->iddiagnosticoii && !in_array($row->iddiagnosticoii, $diagnosticosIn)) {
                $diagnosticosIn[] = $row->iddiagnosticoii;
                $datatmp[] = array('iddiagnostico' => $row->iddiagnosticoii, 'nombre' => $row->nombrediagnosticoii);
            }

            if ($row->iddiagnosticoiii && !in_array($row->iddiagnosticoiii, $diagnosticosIn)) {
                $diagnosticosIn[] = $row->iddiagnosticoiii;
                $datatmp[] = array('iddiagnostico' => $row->iddiagnosticoiii, 'nombre' => $row->nombrediagnosticoiii);
            }
        }

        return $datatmp;
    }

    public function GrabarDxtratamiento($idproductos, $iddiagnosticos, $idempresa, $eva, $idcreated) {  
  
        $empresa = \DB::table('empresa') 
                ->select(['codeaguja'])
                ->where('idempresa', '=', $idempresa)
                ->first();

        $wheredx = '';
        $wheretrat = ''; 
        foreach($iddiagnosticos as $row){            
            $wheredx .= (strlen($wheredx) > 1 ? ',':'').$row['iddiagnostico'];
        }

        
        foreach($idproductos as $row){
            if(!empty($empresa->codeaguja)) {
                if($row['idproducto'] !== $empresa->codeaguja)
                    $wheretrat .= (strlen($wheretrat) > 1 ? ',':'').$row['idproducto'].':'.$row['cantidad'];
            } else {
                $wheretrat .= (strlen($wheretrat) > 1 ? ',':'').$row['idproducto'].':'.$row['cantidad']; 
            }
        }

        return $wheretrat;

        if(!empty($wheretrat)){
 
            $dxtratamiento = $this->dxtratamiento(array('dxtratamiento.grupodx' => $wheredx, 'dxtratamiento.grupotra' => $wheretrat, 'dxtratamiento.eva' =>$eva));
              
            if($dxtratamiento){ 
                \DB::table('dxtratamiento')
                    ->where(array('dxtratamiento.grupodx' => $wheredx, 'dxtratamiento.grupotra' => $wheretrat, 'dxtratamiento.eva' => $eva)) 
                    ->update(['puntuacion' => $dxtratamiento->puntuacion + 1]);
            }else { 
                $dxtratamientofirst = $this->dxtratamiento(array('dxtratamiento.grupodx' => $wheredx, 'dxtratamiento.eva' => $eva));       
                
                if(empty($dxtratamientofirst)) {
                    $tmp = explode(',', $wheredx);
                    $insert = array(
                        'idempresa' => $idempresa, 
                        'grupodx' => $wheredx, 
                        'dxi' => $tmp[0], 
                        'dxii' => isset($tmp[1]) ? $tmp[1] : null, 
                        'dxiii' => isset($tmp[2]) ? $tmp[2] : null, 
                        'eva' => $eva, 
                        'grupotra' => $wheretrat, 
                        'puntuacion' => 1,  
                        'id_created_at' => $idcreated, 
                        'created_at' => date('Y-m-d H:i:s') 
                    ); 
                    \DB::table('dxtratamiento')->insert($insert);         

                                   
                } else {
                    //Existe pero tiene otros tratamientos.
                    
                }
            }
        }

    }

}
