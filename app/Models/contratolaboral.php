<?php

namespace App\Models;
 
class contratolaboral extends apimodel {

    protected $table = 'contratolaboral';
    protected $primaryKey = 'idcontratolaboral';
    public $timestamps = false;
    protected $fillable = [
        'idcontratolaboral',
        'idempresa',
        'identidad',
        'inicio',
        'fin',
        'idplanhorario',
        'sueldo',
        'descripcion',
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];
    protected $hidden = ['idempresa']; 

    public function grid($param, $items = '', $orderName = '', $orderSort = '') {

        $fields = ['contratolaboral.idcontratolaboral', 'contratolaboral.identidad','contratolaboral.inicio', 'contratolaboral.fin', 'contratolaboral.idplanhorario', 'contratolaboral.sueldo', 'contratolaboral.descripcion', 'planhorario.nombre as nombrehorario'];

        $select = \DB::table('contratolaboral')
                ->leftJoin('planhorario', 'contratolaboral.idplanhorario', '=', 'planhorario.idplanhorario')
                ->select($fields)
                ->whereNull('contratolaboral.deleted')
                ->whereNull('planhorario.deleted')
                ->where($param);
  
        $orderName = !empty($orderName) ? $orderName : 'contratolaboral.inicio';
        $orderSort = !empty($orderSort) ? $orderSort : 'asc';

        $select->orderBy($orderName, $orderSort);

        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        } 

        foreach ($data as $row) { 
            $row->inicio = $this->formatFecha($row->inicio);  
            $row->fin = $this->formatFecha($row->fin);  
        }

        return $data;
    }  
 
    public function contratolaboral($param = []) {

        $fields = ['contratolaboral.idcontratolaboral','contratolaboral.identidad','contratolaboral.inicio', 'contratolaboral.fin', 'contratolaboral.idplanhorario', 'contratolaboral.sueldo', 'contratolaboral.descripcion', 'planhorario.nombre as nombrehorario'];
        
        $row = \DB::table('contratolaboral')
                ->leftJoin('planhorario', 'contratolaboral.idplanhorario', '=', 'planhorario.idplanhorario')
                ->select($fields)
                ->whereNull('contratolaboral.deleted')
                ->where($param) 
                ->first();

        $row->inicio = $this->formatFecha($row->inicio);  
        $row->fin = $this->formatFecha($row->fin); 

        return $row;
    }

}