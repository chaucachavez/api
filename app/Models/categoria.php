<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class categoria extends apimodel {

    protected $table = 'categoria';
    protected $primaryKey = 'idcategoria';
    public $timestamps = false;
    protected $fillable = [
        'idcategoria',
        'idempresa',
        'nombre',
        'descripcion',
        'color', 
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];

    protected $hidden = ['idempresa'];

    public function grid($param, $likename, $items = 25, $orderName = '', $orderSort = '') {
        $select = \DB::table('categoria') 
            ->select('categoria.*')
            ->whereNull('categoria.deleted')
            ->where($param);

        if (!empty($likename)) {
            $select->where('categoria.nombre', 'like', '%' . $likename . '%');
            //$select->whereRaw('sp_ascii(perfil.nombre) ilike sp_ascii(?) ', ['%' . $likename . '%']);
        } 

        $orderName = !empty($orderName) ? $orderName : 'categoria.idcategoria';
        $orderSort = !empty($orderSort) ? $orderSort : 'desc';

        $select->orderBy($orderName, $orderSort);
                
        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        }  

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

}
