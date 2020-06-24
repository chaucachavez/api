<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class arbol extends Model {

    protected $table = 'arbol';
    protected $primaryKey = 'idarbol';
    public $timestamps = false;
    protected $fillable = [
        'idcategoria',
        'idempresa',
        'codigo',
        'parent',
        'nombre',
        'color',
        'activo'
    ];
    protected $hidden = ['idempresa'];

    public function grid($params) {
        // \DB::enableQueryLog(); 
        $data = arbol::select('idarbol', 'parent', 'codigo','nombre', 'color', 'activo')
                ->where($params)
                ->orderBy('nombre', 'asc')
                ->get()
                ->toArray();
        //dd(\DB::getQueryLog());

        return $data;
    } 

    public function validadorDataRelacionada($id) {
        $data = \DB::table('paquete')
                ->select('idcategoria')
                ->where('idcategoria', $id)
                ->whereNull('paquete.deleted')
                ->get()
                ->all();

        if (!empty($data)) {
            return ['validator' => true, 'message' => 'Tiene paquetes. No puede ser eliminado.'];
        }  

        return ['validator' => false];
    }

    public function validadorDataRelacionada6($id) {
        $data = \DB::table('paquetezona')
                ->select('idzona')
                ->where('idzona', $id) 
                ->get()
                ->all();

        if (!empty($data)) {
            return ['validator' => true, 'message' => 'Tiene programas. No puede ser eliminado.'];
        }  

        return ['validator' => false];
    }

}
