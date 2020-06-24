<?php

namespace App\Models;
 
class planhorario extends apimodel {

    protected $table = 'planhorario';
    protected $primaryKey = 'idplanhorario';
    public $timestamps = false;
    protected $fillable = [
        'idplanhorario',
        'idempresa',
        'nombre', 
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

        $fields = ['planhorario.idplanhorario',  'planhorario.nombre'];

        // \DB::enableQueryLog();
        $select = \DB::table('planhorario')
                ->select($fields)
                ->whereNull('planhorario.deleted')
                ->where($param); 
        
        if (!empty($likename))
            $select->where('planhorario.nombre', 'like', '%' . $likename . '%');
 

        $orderName = !empty($orderName) ? $orderName : 'planhorario.nombre';
        $orderSort = !empty($orderSort) ? $orderSort : 'asc';

        $select->orderBy($orderName, $orderSort);

        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        }

        $whereIn = [];
        foreach($data as $row){
            $whereIn[] = $row->idplanhorario;
        }

        //Anadir data planhorariodet
        $horariodet = $this->planhorariodet([], $whereIn);
        
        foreach($data as $row){
            $row->planhorariodet = [];
            foreach($horariodet as $row2) {
                if($row->idplanhorario === $row2->idplanhorario)
                    $row->planhorariodet[] = $row2;
            }
        }
        // Fin planhorariodet 

        return $data;
    }

    public function planhorariodet($param, $whereIn = []) {
        $campos = ['planhorariodet.idplanhorario', 'planhorariodet.nombre', 'planhorariodet.dia', 'planhorariodet.inicio', 'planhorariodet.fin'];

        $select = \DB::table('planhorariodet')      
                ->select($campos);

        if (!empty($param)) {
            $select->where($param);
        }

        if (!empty($whereIn)) {
            $select->whereIn('planhorariodet.idplanhorario', $whereIn);
            //->distinct();
        }

        $data = $select->get()->all(); 
        return $data;
    }

    public function GrabarPlanhorariodet($data, $id) {
        \DB::table('planhorariodet')->where('idplanhorario', $id)->delete();

        if(!empty($data))
            \DB::table('planhorariodet')->insert($data);

    }
 
    public function planhorario($param = []) {
        $fields = ['planhorario.idplanhorario',  'planhorario.nombre'];
        
        $row = \DB::table('planhorario')
                ->select($fields)
                ->whereNull('planhorario.deleted')
                ->where($param) 
                ->first();

        return $row;
    }

}