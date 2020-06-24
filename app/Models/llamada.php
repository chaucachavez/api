<?php

namespace App\Models;
 
class llamada extends apimodel {

    protected $table = 'llamada';
    protected $primaryKey = 'idllamada';
    public $timestamps = false;
    protected $fillable = [
        'idllamada',
        'idempresa',
        'identidad',
        'items',
        'fecharegistro',
        'horaregistro',         
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];
    protected $hidden = ['idempresa']; 

    public function grid($param, $betweendate='', $likename='', $items = '',  $orderName = '', $orderSort = '') {
        // \DB::enableQueryLog();
        $select = \DB::table('llamada')
                ->join('entidad', 'entidad.identidad', '=', 'llamada.identidad') 
                ->select('llamada.idllamada','entidad.entidad', 'llamada.fecharegistro', 'llamada.horaregistro', 'llamada.items')
                ->whereNull('llamada.deleted')
                ->where($param);
        
        if (!empty($betweendate)) {
            $select->whereBetween('llamada.fecharegistro', $betweendate);
        }
        
        if (!empty($likename)) {
            $select->where('entidad.entidad', 'like', '%' . $likename . '%'); 
        } 
        
        if(!empty($items)) {            
            $data = $select 
                ->orderBy($orderName, $orderSort)
                ->paginate($items); 
        } else {
            $data = $select 
                ->orderBy('llamada.fecharegistro', 'DESC')
                ->orderBy('llamada.horaregistro', 'DESC')
                ->get()->all();
        }        
        
        foreach ($data as $row) { 
            $row->fecharegistro = $this->formatFecha($row->fecharegistro);    
        }
        
        return $data;
    }
    
    public function llamadadet($param, $betweendate = '', $fields = [], $fieldbetween = '', $betweenhour = array(), $whereInAnexo = array(), $formatdt = false) {     
        //\DB::enableQueryLog();
        if(empty($fields)){
            $fields = ['llamadadet.fechahora', 'llamadadet.tipo', 'llamadadet.origen', 'llamadadet.destino', 'llamadadet.desvio', 'llamadadet.estado', 'llamadadet.duracion', 
                   'llamadadet.costominuto','llamadadet.costobolsa','llamadadet.costototal','grupotimbrado.clave as nombregrupo', 'anexo.clave as nombreanexo'];
        }
        
        $select = \DB::table('llamadadet') 
                ->join('llamada', 'llamadadet.idllamada', '=', 'llamada.idllamada') 
                ->leftJoin('grupotimbrado', 'llamadadet.idgrupotimbrado', '=', 'grupotimbrado.idgrupotimbrado')
                ->leftJoin('anexo', 'llamadadet.idanexo', '=', 'anexo.idanexo')
                ->select($fields)  
                ->where($param);
        
        if (!empty($betweendate)) {
            $select->whereBetween($fieldbetween, $betweendate);
        }
        
        if (!empty($betweenhour)) {
            $select->whereBetween('llamadadet.hora', $betweenhour);
        }
        
        if (!empty($whereInAnexo)) { 
            $select->whereIn('anexo.idanexo', $whereInAnexo);
        }
        
        $select->orderBy('llamadadet.fechahora', 'ASC');
        
        $data = $select->get()->all();        
        //dd(\DB::getQueryLog());

        if($formatdt){
            foreach ($data as $row) {
                $row->fecha = $this->formatFecha($row->fecha);
            }
        }

        return $data;        
    } 
}
