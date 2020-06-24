<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class diagnostico extends Model {

    protected $table = 'diagnostico';
    protected $primaryKey = 'iddiagnostico';
    public $timestamps = false;
    protected $fillable = [
        'idempresa',
        'codigo',
        'nombre',
        'activo',
        'cie10'
    ];
    protected $hidden = ['idempresa'];
 

    public function grid($param, $likename = '', $items = '', $orderName = '', $orderSort = '', $fields = [], $likecodigo = '', $whereIn = array()) {
        
        $campos = ['iddiagnostico', 'codigo', 'nombre', 'activo', 'cie10'];
        if(!empty($fields)){
            $campos = $fields;
        }
        
        $select = \DB::table('diagnostico')                
                ->select($campos)
                ->where($param);

        if (!empty($likename)) {
            $select->where('diagnostico.nombre', 'like', '%' . $likename . '%');
            //$select->whereRaw('sp_ascii(producto.nombre) ilike sp_ascii(?) ', ['%' . $likename . '%']);
        }
        
        if (!empty($whereIn)) {
            $select->whereIn('diagnostico.iddiagnostico', $whereIn);
        }

        if (!empty($likecodigo)) {
            $select->where('diagnostico.codigo', 'like', '%' . $likecodigo . '%'); 
        }
        
        if(!empty($items)) {
            $data = $select
                ->orderBy($orderName, $orderSort)
                ->paginate($items);
        } else {
            $data = $select 
                ->orderBy('diagnostico.nombre', 'ASC') 
                ->get()->all();
        }
       
        return $data;
    }
    
     
    
}
