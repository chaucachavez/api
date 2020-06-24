<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class paquete extends apimodel {

    protected $table = 'paquete';
    protected $primaryKey = 'idpaquete';
    public $timestamps = false;
    protected $fillable = [
        'idpaquete',
        'idempresa',
        'nombre',
        'descripcion',
        'idcategoria', 
        'total', 
        'dias', 
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];

    protected $hidden = ['idempresa'];

    public function grid($param, $likename = '', $items = '', $orderName = '', $orderSort = '') {
        $select = \DB::table('paquete') 
            ->select('paquete.*', 'arbol.nombre as nombrecategoria', 'arbol.color')
            ->join('arbol', 'paquete.idcategoria', '=', 'arbol.idarbol')
            
            ->whereNull('paquete.deleted')
            ->where($param);

        if (!empty($likename)) {
            $select->where('paquete.nombre', 'like', '%' . $likename . '%');
            //$select->whereRaw('sp_ascii(perfil.nombre) ilike sp_ascii(?) ', ['%' . $likename . '%']);
        } 

        $orderName = !empty($orderName) ? $orderName : 'paquete.nombre';
        $orderSort = !empty($orderSort) ? $orderSort : 'asc';

        $select->orderBy($orderName, $orderSort);
                
        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        }  

        return $data;
    } 

    public function paquetedetalles($param = array(), $whereIn = array()) { 
        $campos = ['paquete.nombre as nombrepaquete', 'producto.nombre as nombreproducto', 'paquetedet.punit', 'paquetedet.cantidad', 'paquetedet.total', 'paquetedet.idproducto'];
        
        $select = \DB::table('paquetedet')
                ->join('paquete', 'paquetedet.idpaquete', '=', 'paquete.idpaquete')
                ->join('producto', 'paquetedet.idproducto', '=', 'producto.idproducto')          
                ->select($campos) 
                ->whereNull('paquete.deleted')
                ->whereNull('paquetedet.deleted');

        if (!empty($param)) {
            $select->where($param);
        }

        if (!empty($whereIn)) {
            $select->whereIn('paquete.idpaquete', $whereIn);
        }

        $data = $select->orderBy('paquete.idpaquete', 'DESC') 
                ->get()->all();
        
        return $data; 
    }

    public function paqueteprotocolos($param = array(), $whereIn = array()) { 
        $campos = ['paquete.nombre as nombrepaquete', 'producto.nombre as nombreproducto', 'paqueteproto.dia', 
        'paqueteproto.cantidad', 'paqueteproto.idproducto'];
        
        $select = \DB::table('paqueteproto')
                ->join('paquete', 'paqueteproto.idpaquete', '=', 'paquete.idpaquete')
                ->join('producto', 'paqueteproto.idproducto', '=', 'producto.idproducto')          
                ->select($campos)
                ->whereNull('paquete.deleted')
                ->whereNull('paqueteproto.deleted');
        
        if (!empty($param)) {
            $select->where($param);
        }

        if (!empty($whereIn)) {
            $select->whereIn('paquete.idpaquete', $whereIn);
        }
        
        $data = $select->orderBy('paquete.idpaquete', 'DESC') 
                ->get()->all();
        
        return $data; 
    }

    public function GrabarPaquetedetalles($data, $id = NULL) {
        if($id)
            \DB::table('paquetedet')->where('idpaquete', $id)->delete();
        \DB::table('paquetedet')->insert($data);
    }

    public function GrabarPaqueteprotocolos($data, $id = NULL) {
        if($id)
            \DB::table('paqueteproto')->where('idpaquete', $id)->delete();
        \DB::table('paqueteproto')->insert($data);
    }

    public function paquete($id) {

        $data = \DB::table('paquete')
                ->join('arbol', 'paquete.idcategoria', '=', 'arbol.idarbol') 
                ->select('paquete.idpaquete', 'paquete.idempresa', 'paquete.nombre','paquete.descripcion', 'paquete.idcategoria', 'paquete.total', 'paquete.dias', 'arbol.idarbol', 'arbol.nombre as nombrecategoria', 'arbol.color')
                ->whereNull('paquete.deleted') 
                ->where('paquete.idpaquete', $id)
                ->first();

        if ($data) { 
            $data->paquetedet = $this->paquetedetalles(array('paquete.idpaquete' => $id));
            $data->paqueteproto = $this->paqueteprotocolos(array('paquete.idpaquete' => $id)); 
        }

        return $data;
    }

    public function GrabarZonas($data, $id) { 
        \DB::table('paquetezona')->where('idpaquete', $id)->delete();

        if(!empty($data))
            \DB::table('paquetezona')->insert($data);
    }

    public function zonas($param, $whereIn = []) {
        $campos = ['paquetezona.idzona', 'paquetezona.idpaquete', 'arbol.nombre'];

        $select = \DB::table('paquetezona')                
                ->join('arbol', 'paquetezona.idzona', '=', 'arbol.idarbol') 
                ->select($campos);

        if (!empty($param)) {
            $select->where($param);
        }

        if (!empty($whereIn)) {
            $select->whereIn('paquetezona.idpaquete', $whereIn);
            //->distinct();
        }

        $data = $select->get()->all(); 
        return $data;
    }

}
