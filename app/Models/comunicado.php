<?php

namespace App\Models; 

class comunicado extends apimodel {

    protected $table = 'comunicado';
    protected $primaryKey = 'idcomunicado';
    public $timestamps = false;

    protected $fillable = [
        'idcomunicado',
        'idempresa',
        'titulo',
        'fecha',
        'descripcion',
        'pregunta',
        'respuestaa',
        'respuestab',
        'respuestac',
        'respuestad',
        'publicado',
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
            $campos = ['comunicado.idcomunicado', 'comunicado.titulo', 'comunicado.fecha', 'comunicado.descripcion', 'created.entidad as created', 'comunicado.publicado', 'comunicado.pregunta', 'comunicado.respuestaa', 'comunicado.respuestab', 'comunicado.respuestac', 'comunicado.respuestad'];   
        }      
        
        $select = \DB::table('comunicado')  
                ->join('entidad as created', 'comunicado.id_created_at', '=', 'created.identidad');
          
        $select->select($campos)
                ->where($param)
                ->whereNull('comunicado.deleted');
        
        if (!empty($likename)) {
            $select->where('comunicado.titulo', 'like', '%' . $likename . '%');
        }
        
        if (!empty($betweendate)) {
            $select->whereBetween('comunicado.fecha', $betweendate);
        }
        // dd($items);
        if(!empty($items)) {
            $data = $select
                ->orderBy($orderName, $orderSort)
                ->paginate($items);
        } else {
            $data = $select 
                ->orderBy('comunicado.fecha', 'DESC') 
                ->get()->all();
        }

        foreach($data as $row){
            $row->fecha = $this->formatFecha($row->fecha);                       
        }
        
        return $data;
    }    

    public function gridRespuestas($param) {
        
        if(!empty($fields)) {
            $campos = $fields;
        } else {
            $campos = ['comunicado.idcomunicado', 'comunicado.titulo', 'comunicado.fecha', 'comunicado.descripcion', 'created.entidad as created', 'comunicado.publicado', 'comunicado.pregunta', 'comunicado.respuestaa', 'comunicado.respuestab', 'comunicado.respuestac', 'comunicado.respuestad'];
        }
        
        $select = \DB::table('comunicadorespuesta')
                    ->join('comunicado', 'comunicadorespuesta.idcomunicado', '=', 'comunicado.idcomunicado')
                    ->join('entidad', 'comunicadorespuesta.identidad', '=', 'entidad.identidad')
                    ->leftJoin('documento', 'entidad.iddocumento', '=', 'documento.iddocumento')
                    ->select(['documento.abreviatura', 'entidad.numerodoc', 'entidad.entidad', 'comunicado.titulo', 'comunicado.pregunta', 'comunicado.respuestaa', 'comunicado.respuestab', 'comunicado.respuestac', 'comunicado.respuestad', 'comunicadorespuesta.respuesta']);

        if (!empty($param)) {
            $select->where($param);
        }

        $data = $select->get()->all();
            
        // dd($data);
        return $data;
    } 

    public function comunicado($id) {

        $data = \DB::table('comunicado') 
                ->join('entidad as created', 'comunicado.id_created_at', '=', 'created.identidad')
                ->select('comunicado.idcomunicado', 'comunicado.titulo','comunicado.fecha','comunicado.descripcion', 'created.entidad as created', 'comunicado.publicado', 'comunicado.pregunta', 'comunicado.respuestaa', 'comunicado.respuestab', 'comunicado.respuestac', 'comunicado.respuestad')
                ->whereNull('comunicado.deleted')
                ->where('comunicado.idcomunicado', $id)
                ->first(); 

        if ($data) {
            $data->fecha = $this->formatFecha($data->fecha); 
        }

        return $data;
    }
}
