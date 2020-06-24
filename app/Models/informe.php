<?php

namespace App\Models; 

class informe extends apimodel {

    protected $table = 'informe';
    protected $primaryKey = 'idinforme';
    public $timestamps = false;

    protected $fillable = [
        'idinforme',
        'idempresa',
        'idcitamedica',
        'archivo',
        'identidad_firma',
        'fecha_firma',
        'mensaje',
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at' 
    ];
    
    protected $hidden = ['idempresa'];

    public function informe($id) {

        $data = \DB::table('informe')                 
                ->whereNull('informe.id_deleted_at')
                ->where('informe.idinforme', $id)
                ->first(); 

        if ($data) {
            $data->fecha = $this->formatFecha($data->fecha); 
        }

        return $data;
    }
}
