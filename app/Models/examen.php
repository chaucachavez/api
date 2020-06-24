<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class examen extends Model {

    protected $table = 'examen';
    protected $primaryKey = 'idexamen';
    public $timestamps = false;
    protected $fillable = [
        'idexamen', 
        'nombre', 
    ];
    protected $hidden = ['idempresa'];
 

    public function grid($param, $likename = '', $items = '', $orderName = '', $orderSort = '', $fields = []) {
        
        $campos = ['idexamen', 'nombre'];
        if(!empty($fields)){
            $campos = $fields;
        }
        
        $select = \DB::table('examen')                
                ->select($campos)
                ->where($param);
                
        if(!empty($items)) {
            $data = $select
                ->orderBy($orderName, $orderSort)
                ->paginate($items);
        } else {
            $data = $select 
                ->orderBy('examen.nombre', 'ASC') 
                ->get()->all();
        }
       
        return $data;
    }
    
     
    
}
