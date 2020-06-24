<?php

namespace App\Models;
 
class modelo extends apimodel {

    protected $table = 'modelo';
    protected $primaryKey = 'idmodelo';
    public $timestamps = false;
    protected $fillable = [
        'idmodelo',
        'idempresa',
        'nombre',
        'predeterminado',
        'orden',
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

        $fields = ['modelo.idmodelo',  'modelo.nombre', 'modelo.predeterminado', 'modelo.orden'];

        // \DB::enableQueryLog();
        $select = \DB::table('modelo')
                ->select($fields)
                ->whereNull('modelo.deleted')
                ->where($param); 
        
        if (!empty($likename))
            $select->where('modelo.nombre', 'like', '%' . $likename . '%');

        if (!empty($orderName) && !empty($orderSort)) {
            $select->orderBy($orderName, $orderSort);
        } else {
            $select->orderBy('modelo.orden', 'ASC');
        }

        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        }

        $whereIn = [];
        foreach($data as $row){
            $whereIn[] = $row->idmodelo;
        }

        //Anadir data modeloseguro
        $planes = $this->planes([], $whereIn);
        
        foreach($data as $row){
            $row->modeloseguro = [];
            foreach($planes as $row2) {
                if($row->idmodelo === $row2->idmodelo)
                    $row->modeloseguro[] = $row2;
            }
        }
        // Fin modeloseguro

        //Anadir data modelodet
        $modelodet = $this->modelodet([], $whereIn);

        foreach($data as $row){
            $row->modelodet = [];
            foreach($modelodet as $row2) {
                if($row->idmodelo === $row2->idmodelo)
                    $row->modelodet[] = $row2;
            }
        }
        // Fin modelodet

        return $data;
    }

    public function planes($param, $whereIn = []) {
        $campos = ['modeloseguro.idmodelo', 'modeloseguro.idaseguradoraplan', 'aseguradoraplan.nombre'];

        $select = \DB::table('modeloseguro')                
                ->join('aseguradoraplan', 'modeloseguro.idaseguradoraplan', '=', 'aseguradoraplan.idaseguradoraplan')
                //->join('aseguradora', 'aseguradoraplan.idaseguradora', '=', 'aseguradoraplan.idaseguradora')
                ->select($campos);

        if (!empty($param)) {
            $select->where($param);
        }

        if (!empty($whereIn)) {
            $select->whereIn('modeloseguro.idmodelo', $whereIn);
            //->distinct();
        }

        $data = $select->get()->all(); 
        return $data;
    }

    

    public function modelodet($param, $whereIn = []) {
        $campos = ['modelodet.idmodelodet', 'modelodet.idmodelo', 'modelodet.idproducto',  'modelodet.codigo', 'modelodet.descripcion', 'modelodet.cantidad', 'modelodet.precio'];

        $select = \DB::table('modelodet')
            ->select($campos);

        if (!empty($param)) {
            $select->where($param);
        }

        if (!empty($whereIn)) {
            $select->whereIn('modelodet.idmodelo', $whereIn);
        }

        $data = $select->get()->all();

        return $data;
    }

    public function GrabarPlanes($data, $id) {
        \DB::table('modeloseguro')->where('idmodelo', $id)->delete();

        if(!empty($data))
            \DB::table('modeloseguro')->insert($data);
    }

    public function GrabarModelodet($data, $id) {
        \DB::table('modelodet')->where('idmodelo', $id)->delete();

        if(!empty($data))
            \DB::table('modelodet')->insert($data);

    }
 
    public function modelo($param = []) {
        $fields = ['modelo.idmodelo',  'modelo.nombre', 'modelo.predeterminado'];
        
        $row = \DB::table('modelo')
                ->select($fields)
                ->whereNull('modelo.deleted')
                ->where($param) 
                ->first();

        return $row;
    }

}