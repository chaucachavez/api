<?php

namespace App\Models; 

class publicacion extends apimodel {

    protected $table = 'publicacion';
    protected $primaryKey = 'idpublicacion';
    public $timestamps = false;

    protected $fillable = [
        'idpublicacion',
        'idempresa',
        'titulo',
        'fecha',
        'descripcion',
        'url_pdf',
        'visor_pdf',
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];
    
    protected $hidden = ['idempresa'];

    public function grid($param,  $betweendate = '', $likename = '', $items = '', $orderName = '', $orderSort = '', $fields = []) {
        
        if(!empty($fields)) {
            $campos = $fields;
        } else {
            $campos = ['publicacion.idpublicacion', 'publicacion.titulo', 'publicacion.fecha', 'publicacion.descripcion', 'created.entidad as created', 'publicacion.url_pdf', 'publicacion.visor_pdf'];   
        }      
        
        $select = \DB::table('publicacion')  
                ->join('entidad as created', 'publicacion.id_created_at', '=', 'created.identidad');
          
        $select->select($campos)
                ->where($param)
                ->whereNull('publicacion.deleted');
        
        if (!empty($likename)) {
            $select->where('publicacion.titulo', 'like', '%' . $likename . '%');
        }
        
        if (!empty($betweendate)) {
            $select->whereBetween('publicacion.fecha', $betweendate);
        }
        // dd($items);
        if(!empty($items)) {
            $data = $select
                ->orderBy($orderName, $orderSort)
                ->paginate($items);
        } else {
            $data = $select 
                ->orderBy('publicacion.fecha', 'DESC') 
                ->get()->all();
        }

        foreach($data as $row){
            $row->fecha = $this->formatFecha($row->fecha);                       
        }
        
        return $data;
    } 

    public function gridPersonal($param,  $betweendate = '', $likename = '', $items = '', $orderName = '', $orderSort = '', $fields = []) {
        
        if(!empty($fields)) {
            $campos = $fields;
        } else {
            $campos = ['publicacion.idpublicacion', 'publicacion.titulo', 'publicacion.fecha', 'publicacion.descripcion', 'created.entidad as created', 'publicacion.url_pdf', 'publicacion.visor_pdf'];   
        }      
        
        $select = \DB::table('publicacion')  
                ->join('entidad as created', 'publicacion.id_created_at', '=', 'created.identidad')                
                ->join('etiqueta_publicacion', 'publicacion.idpublicacion', '=', 'etiqueta_publicacion.idpublicacion')
                ->join('entidad_etiqueta', 'etiqueta_publicacion.idetiqueta', '=', 'entidad_etiqueta.idetiqueta');
          
        $select->select($campos)
                ->where($param);
        
        if (!empty($likename)) {
            $select->where('publicacion.titulo', 'like', '%' . $likename . '%');
        }
        
        if (!empty($betweendate)) {
            $select->whereBetween('publicacion.fecha', $betweendate);
        }
        // dd($items);
        if(!empty($items)) {
            $data = $select
                ->orderBy($orderName, $orderSort)
                ->distinct()
                ->paginate($items);
        } else { 
            $data = $select 
                ->orderBy('publicacion.fecha', 'DESC') 
                ->distinct()
                ->get()->all();
        }

        foreach($data as $row){
            $row->fecha = $this->formatFecha($row->fecha);                       
        }
        
        return $data;
    }

    public function etiquetas($param = [], $whereIdpublicacionIn = []) { 
        $campos = ['etiqueta_publicacion.idpublicacion', 'etiqueta.idetiqueta', 'etiqueta.nombre'];
        
        $select = \DB::table('etiqueta')
                ->join('etiqueta_publicacion', 'etiqueta.idetiqueta', '=', 'etiqueta_publicacion.idetiqueta')
                ->select($campos); 
        
        if ($whereIdpublicacionIn) {
            $select->whereIn('etiqueta_publicacion.idpublicacion', $whereIdpublicacionIn);
        }

        if ($param) {
            $select->where($param);
        }  

        $data = $select 
                ->whereNull('etiqueta.deleted')
                ->orderBy('etiqueta.nombre', 'ASC') 
                ->get()->all();
                
        return $data; 
    }

    public function gridetiquetas($param) { 
        $campos = ['etiqueta.idetiqueta', 'etiqueta.nombre'];
        
        $select = \DB::table('etiqueta') ; 

        $select->select($campos); 
        
        $data =  $select
                ->where($param)
                ->whereNull('etiqueta.deleted')                
                ->orderBy('etiqueta.nombre', 'ASC') 
                ->get()->all();
                
        return $data; 
    }

    public function usuarios($param) { 
        $campos = ['entidad.identidad', 'entidad.entidad'];
        
        $select = \DB::table('entidad')
                ->join('entidad_publicacion', 'entidad.identidad', '=', 'entidad_publicacion.identidad'); 

        $select->select($campos); 
        
        $data =  $select
                ->where($param)
                ->whereNull('entidad.deleted')                
                ->orderBy('entidad.entidad', 'ASC') 
                ->get()->all();
                
        return $data; 
    }

    public function GrabarEtiquetas($data, $idpublicacion) { 
        \DB::table('etiqueta_publicacion')->where('idpublicacion', $idpublicacion)->delete();
        \DB::table('etiqueta_publicacion')->insert($data);
    } 

    public function GrabarPersonal($data, $idpublicacion) { 
        \DB::table('entidad_publicacion')->where('idpublicacion', $idpublicacion)->delete();
        \DB::table('entidad_publicacion')->insert($data);
    }   

    public function publicacion($id) {

        $data = \DB::table('publicacion') 
                ->join('entidad as created', 'publicacion.id_created_at', '=', 'created.identidad')               
                ->select('publicacion.idpublicacion', 'publicacion.titulo','publicacion.fecha','publicacion.descripcion', 'created.entidad as created', 'publicacion.url_pdf', 'publicacion.visor_pdf')
                ->whereNull('publicacion.deleted')
                ->where('publicacion.idpublicacion', $id)
                ->first(); 

        if ($data) {
            $data->fecha = $this->formatFecha($data->fecha); 
        }

        return $data;
    }
}
