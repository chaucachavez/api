<?php

namespace App\Models; 

class presupuestodetcant extends apimodel {

    public $table = 'presupuestodetcant';
    public $primaryKey = 'idpresupuestodetcant';
    public $timestamps = true;
    public $fillable = [
        'idpresupuestodet',
        'idproducto',
        'idpersonal',
        'cantidad',        
        'fecha',        
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];
    protected $hidden = ['created_at', 'updated_at'];
  
    public function grid($param, $likename= '', $items = '', $orderName = '', $orderSort = '') {
        // \DB::enableQueryLog(); 
        $select = \DB::table('presupuestodetcant') 
                ->join('entidad as cliente', 'presupuestodetcant.idpersonal', '=', 'cliente.identidad') 
                ->join('producto', 'presupuestodetcant.idproducto', '=', 'producto.idproducto') 
                ->leftJoin('entidad as created', 'presupuestodetcant.id_created_at', '=', 'created.identidad') 
                ->select('presupuestodetcant.cantidad', 'presupuestodetcant.fecha', 'cliente.entidad as nombrepersonal', 'producto.nombre', 'created.entidad as created')
                ->whereNull('presupuestodetcant.deleted')
                ->where($param);
        
        if (!empty($likename)) {
            $select->where('cliente.entidad', 'like', '%' . $likename . '%');
            //$select->whereRaw('sp_ascii(entidad.entidad) ilike sp_ascii(?) ', ['%' . $likename . '%']);
        }
        
        if(!empty($items)) {
            $data = $select 
                ->orderBy($orderName, $orderSort)
                ->paginate($items);
        } else {
            $data = $select 
                ->orderBy('presupuestodetcant.fecha', 'ASC')
                ->get()->all();
        }
        
        // dd(\DB::getQueryLog()); 
        foreach ($data as $row) {
            $row->fecha = $this->formatFecha($row->fecha);
        }

        return $data;
    }
    
    
}
