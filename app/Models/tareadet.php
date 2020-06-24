<?php

namespace App\Models;
 
class tareadet extends apimodel {

    protected $table = 'tareadet';
    protected $primaryKey = 'idtareadet';
    public $timestamps = false;

    protected $fillable = [
        'idtareadet',
        'idtarea',
        'idempresa', 
        'idrespuesta',  
        'tiporesultado', 
        'tiporespuesta',  
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

    public function grid($param, $fields = [], $whereIn = []) {
        
        $campos = ['tareadet.idtareadet', 'tareadet.idtarea', 'tareadet.idrespuesta', 'tareadet.tiporesultado', 'tareadet.tiporespuesta', 'tareadet.descripcion', 'tareadet.created_at', 'tareadet.id_created_at', 'created.entidad as created', 'created.numerodoc', 'created.sexo', 'created.imgperfil',  'respuesta.nombre as nombrerespuesta'];

        if (!empty($fields)) {
            $campos = $fields;
        }
        
        $select = \DB::table('tareadet') 
                ->leftJoin('respuesta', 'tareadet.idrespuesta', '=', 'respuesta.idrespuesta')
                ->join('entidad as created', 'tareadet.id_created_at', '=', 'created.identidad')
                ->select($campos)
                ->whereNull('tareadet.deleted') 
                ->where($param);

        if (!empty($whereIn)) {
            $select->whereIn('tareadet.idtarea', $whereIn);
        }

        $data = $select
                ->orderBy('tareadet.idtareadet', 'desc') 
                ->get()->all();

        foreach ($data as $row) { 
            $fecha = $this->formatFecha(substr($row->created_at, 0, 10));
            $hora = substr($row->created_at, 11, 8);
            $row->created_at = $fecha .' '. $hora;
        }

        return $data;
    }
        
}
