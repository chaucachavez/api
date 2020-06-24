<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ubigeo extends Model {

    protected $table = 'ubigeo';
    protected $primaryKey = 'idubigeo';
    protected $fillable = [
        'pais',
        'dpto',
        'prov',
        'dist',
        'nombre',
        'nacionalidad'
    ];

    public function paises() {
        $data = ubigeo::select('ubigeo.idubigeo', 'ubigeo.nombre')
                        ->where('dpto', '=', '000')->orderBy('nombre', 'DESC')->get()->toArray();
        return $data;
    }

    public function nacionalidades() {
        $data = ubigeo::select('ubigeo.nacionalidad')
                        ->where('dpto', '=', '000')->orderBy('nombre', 'DESC')->get()->toArray();
        return $data;
    }

}
