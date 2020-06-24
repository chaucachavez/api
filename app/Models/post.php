<?php

namespace App\Models; 

class post extends apimodel {

    protected $table = 'post';
    protected $primaryKey = 'idpost';
    public $timestamps = false;
    protected $fillable = [
        'idempresa', 
        'identidad', 
        'idseccion',
        'idcategoria',
        'idactividad',
        'idllamada',
        'idcitamedica',
        'idcicloatencion',
        'fecha',
        'hora',
        'tarea',
        'realizado',        
        'fecharecordatorio',
        'horarecordatorio', 
        'mensaje',
        'iditem',     
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];
    protected $hidden = ['idempresa']; 
  
    public function grid($param,  $likename = '', $items = '', $orderName = '', $orderSort = '', $whereIn = []) {
    
        $fields = ['post.idpost', 'post.identidad', 'post.idcitamedica', 'post.idcicloatencion', 'post.fecha', 'post.hora', 'post.mensaje', 'post.idcategoria', 'categoria.nombre as nombrecategoria',
                    'post.fecharecordatorio', 'post.horarecordatorio', 'post.tarea', 'post.realizado', 'post.iditem',  
                    'seccion.nombre as nombreseccion', 'actividad.nombre as nombreactividad', 'llamada.nombre as nombrellamada', 'post.idseccion', 'post.idactividad', 'post.idllamada',
                    'entidad.entidad', 'item.nombre as nombreitem', 'citamedica.fecha as fechacita',  'cicloatencion.fecha as fechaciclo',
                    'post.id_created_at as idcreated', 'created.entidad as created', 'post.created_at as createdat', 'created.numerodoc', 'created.sexo', 'created.imgperfil'];
                        
        $select = \DB::table('post') 
                ->join('entidad', 'post.identidad', '=', 'entidad.identidad') 
                ->join('entidad as created', 'post.id_created_at', '=', 'created.identidad')
                ->join('estadodocumento as seccion', 'post.idseccion', '=', 'seccion.idestadodocumento')
                ->join('estadodocumento as categoria', 'post.idcategoria', '=', 'categoria.idestadodocumento')
                ->leftJoin('estadodocumento as actividad', 'post.idactividad', '=', 'actividad.idestadodocumento')
                ->leftJoin('estadodocumento as llamada', 'post.idllamada', '=', 'llamada.idestadodocumento')  
                ->leftJoin('item', 'post.iditem', '=', 'item.iditem')  
                ->leftJoin('citamedica', 'post.idcitamedica', '=', 'citamedica.idcitamedica')  
                ->leftJoin('cicloatencion', 'post.idcicloatencion', '=', 'cicloatencion.idcicloatencion')
                ->select($fields)
                ->where($param) 
                ->whereNull('post.deleted'); 
           
        if (!empty($likename)) {
            $select->where('entidad.entidad', 'like', '%' . $likename . '%');
        } 
        
        if (!empty($whereIn)) {
            $select->whereIn('post.idcicloatencion', $whereIn);
        }

        $orderName = !empty($orderName) ? $orderName : 'post.fecha';
        $orderSort = !empty($orderSort) ? $orderSort : 'asc';
        // dd($orderName, $orderSort);
        $select->orderBy($orderName, $orderSort)
               ->orderBy('post.hora', 'asc');

        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        }
        // dd($data);
        foreach ($data as $row) {
            $row->fecha = $this->formatFecha($row->fecha);
            $row->fechacita = $this->formatFecha($row->fechacita);
            $row->fecharecordatorio = $this->formatFecha($row->fecharecordatorio);
            $row->fechaciclo = $this->formatFecha($row->fechaciclo);
            // $row->createdtimeat = substr($row->createdat, 11, 8);
            // $row->createdat = $this->formatFecha(substr($row->createdat, 0, 10)); 
        }

        return $data;
    } 

    public function ultimallamadaefectiva($param, $id, $tabla) {         
        
        $row = \DB::table('post')    
                ->select('post.fecha')
                ->whereNull('post.deleted')  
                ->where('post.idllamada', 61) //Contestado
                ->where($param)  
                ->orderBy('post.fecha', 'DESC')
                ->first();  

        switch ($tabla) {
            case 'cicloatencion':
                if($row && $row->fecha)
                    \DB::table('cicloatencion')->where('idcicloatencion', $id)->update(['ultimallae' => $row->fecha]);
                break; 
            case 'citamedica':
                if($row && $row->fecha)
                    \DB::table('citamedica')->where('idcitamedica', $id)->update(['ultimallae' => $row->fecha]);
                break; 
        }        

        return $row;
    }

    public function cantidadllamadaefectiva($param, $id, $tabla) {

        $row = null;
        $cantidadllae = 0;
        
        switch ($tabla) {
            case 'cicloatencion': 
                $row = \DB::table('cicloatencion')    
                        ->select('cicloatencion.ultimot')
                        ->whereNull('cicloatencion.deleted')  
                        ->where('cicloatencion.idcicloatencion', $id)   
                        ->first(); 
                break; 

            case 'citamedica':
                $row = \DB::table('citamedica')    
                        ->whereNull('citamedica.deleted')  
                        ->where('citamedica.idcitamedica', $id)   
                        ->first(); 
                break; 
        }
        
        //cantidadllae
        $select = \DB::table('post')    
            ->select('post.fecha')
            ->whereNull('post.deleted')  
            ->where('post.idllamada', 61) //Contestado
            ->where($param);

        if($tabla === 'cicloatencion' && $row->ultimot){
            $select->where('fecha', '>', $row->ultimot);
        }

        $cantidadllae = $select->count();
        
        //cantidadlla
        $cantidadlla = \DB::table('post')    
            ->select('post.fecha')
            ->whereNull('post.deleted')   
            ->where($param)
            ->count();

        switch ($tabla) {
            case 'cicloatencion': 
                \DB::table('cicloatencion')->where('idcicloatencion', $id)->update(['cantidadllae' => $cantidadllae, 'cantidadlla' => $cantidadlla]);
                break; 
 
            case 'citamedica':
                \DB::table('citamedica')->where('idcitamedica', $id)->update(['cantidadllae' => $cantidadllae, 'cantidadlla' => $cantidadlla]);
                break; 
        }         

        return $cantidadllae;
    }
    
}
