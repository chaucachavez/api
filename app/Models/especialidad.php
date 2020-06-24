<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class especialidad extends Model {

    protected $table = 'especialidad';
    protected $primaryKey = 'idespecialidad';
    public $timestamps = false;
    protected $fillable = [
        'idempresa',
        'nombre',
        'descripcion'
    ];

    public function especialidades($param) {
        $data = \DB::table('especialidad')
                ->select('especialidad.idespecialidad', 'especialidad.nombre')
                ->where($param)
                ->get()->all();

        return $data;
    }

    public function grid($param, $likename = '', $items = '', $orderName = '', $orderSort = '', $fields = []) {
        
        $campos = ['idespecialidad', 'nombre'];
        if(!empty($fields)){
            $campos = $fields;
        }
        
        $select = \DB::table('especialidad')                
                ->select($campos)
                ->where($param);
                
        if(!empty($items)) {
            $data = $select
                ->orderBy($orderName, $orderSort)
                ->paginate($items);
        } else {
            $data = $select 
                ->orderBy('especialidad.nombre', 'ASC') 
                ->get()->all();
        }
       
        return $data;
    }

}