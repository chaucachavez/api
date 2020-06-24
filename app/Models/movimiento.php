<?php

namespace App\Models;
 
class movimiento extends apimodel {

    protected $table = 'movimiento';
    protected $primaryKey = 'idmovimiento';
    public $timestamps = false;
    protected $fillable = [
        'idmovimiento',
        'idempresa',
        'idsede',
        'tipo',
        'identidad',
        'iddocumentofiscal',
        'idarbol',
        'fecha',
        'concepto',
        'numero',
        'tipopago',
        'banco',
        'cheque',
        'total',
        'idsedebeneficio',
        'idproveedor',
        'adjunto',
        'idapertura',
        'identidadrevision',
        'revision',
        'revisioncomentario',
        'identidadctrol',
        'fechactrol',
        'control',
        'controlcomentario',
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];
    protected $hidden = ['idempresa']; 

    public function grid($param, $between='', $likename = '', $items = '', $orderName = '', $orderSort = '', $fields = []) {

        if (empty($fields)) {
            $fields = ['movimiento.idmovimiento',  'movimiento.idsede', 'movimiento.tipo', 'movimiento.identidad', 'movimiento.iddocumentofiscal', 
                        'movimiento.idarbol', 'movimiento.fecha', 'arbol.codigo', 'arbol.nombre as nombregasto', 'movimiento.concepto', 'movimiento.numero', 'movimiento.tipopago', 'movimiento.banco', 
                        'movimiento.cheque', 'movimiento.total', 'entidad.entidad', 'personal.entidad as personal', 'sede.nombre as nombresede', 'movimiento.idapertura', 
                        'documentofiscal.nombre as nombredocumento', 'proveedor.entidad as proveedor'];
        }
        // \DB::enableQueryLog();
        $select = \DB::table('movimiento')
                ->join('sede', 'movimiento.idsede', '=', 'sede.idsede')
                ->join('entidad', 'movimiento.identidad', '=', 'entidad.identidad')
                ->leftJoin('arbol', 'movimiento.idarbol', '=', 'arbol.idarbol')
                ->join('documentofiscal', 'movimiento.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal')
                ->join('entidad as personal', 'movimiento.id_created_at', '=', 'personal.identidad')
                ->leftJoin('entidad as proveedor', 'movimiento.idproveedor', '=', 'proveedor.identidad')
                //->join('documento', 'cliente.iddocumento', '=', 'documento.iddocumento')
                ->select($fields)
                ->whereNull('movimiento.deleted')
                ->where($param);
        
        if (!empty($between)) {
            $select->whereBetween('movimiento.fecha', $between);
        }
        
        if (!empty($likename))
            $select->where('entidad.entidad', 'like', '%' . $likename . '%');

        if (!empty($orderName) && !empty($orderSort)) {
            $select->orderBy($orderName, $orderSort);
        } else {
            $select->orderBy('movimiento.tipo', 'ASC');
            $select->orderBy('movimiento.idapertura', 'DESC');
            $select->orderBy('movimiento.fecha', 'ASC');
        }

        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        } 

        // dd(\DB::getQueryLog());
        foreach ($data as $row) {
            $row->fecha = $this->formatFecha($row->fecha);
        }

        return $data;
    }

    public function destinos($param) { 
        $campos = ['movimiento.idmovimiento', 'arbol.idarbol', 'arbol.nombre'];
        
        $data = \DB::table('movimiento')
                ->join('movimientodestino', 'movimiento.idmovimiento', '=', 'movimientodestino.idmovimiento')
                ->join('arbol', 'movimientodestino.idarbol', '=', 'arbol.idarbol')          
                ->select($campos) 
                ->whereNull('movimiento.deleted')
                ->where($param)  
                ->get()->all();
                
        return $data; 
    }

    public function GrabarDestinos($data, $id) {
        \DB::table('movimientodestino')->where('idmovimiento', $id)->delete();
        \DB::table('movimientodestino')->insert($data);
    }
 
    public function movimiento($param = []) {
        $fields = ['movimiento.idmovimiento',  'movimiento.idsede', 'movimiento.tipo', 'movimiento.identidad', 'movimiento.iddocumentofiscal', 'movimiento.adjunto',
                    'movimiento.idarbol', 'movimiento.fecha', 'movimiento.concepto', 'movimiento.numero', 'movimiento.tipopago', 'movimiento.banco', 
                    'movimiento.cheque', 'movimiento.total', 'entidad.entidad', 'personal.entidad as personal',  'sede.nombre as nombresede',
                    'movimiento.idproveedor', 'proveedor.entidad as proveedor', 'movimiento.idsedebeneficio', 'sedebeneficio.nombre as sedebeneficio',
                    'movimiento.revision', 'movimiento.revisioncomentario', 'movimiento.fechactrol', 'movimiento.control', 'movimiento.controlcomentario',
                    'responsable.entidad as nombreresponsable', 'revision.entidad as nombreresponsablerev'];
        
        $row = \DB::table('movimiento')
                ->join('entidad as personal', 'movimiento.id_created_at', '=', 'personal.identidad')                 
                ->join('sede', 'movimiento.idsede', '=', 'sede.idsede')                
                ->join('entidad', 'movimiento.identidad', '=', 'entidad.identidad')
                ->leftJoin('entidad as proveedor', 'movimiento.idproveedor', '=', 'proveedor.identidad')
                ->leftJoin('sede as sedebeneficio', 'movimiento.idsedebeneficio', '=', 'sedebeneficio.idsede') 
                ->leftJoin('entidad as responsable', 'movimiento.identidadctrol', '=', 'responsable.identidad')  
                ->leftJoin('entidad as revision', 'movimiento.identidadrevision', '=', 'revision.identidad')  
                ->select($fields)
                ->whereNull('movimiento.deleted') 
                ->where($param) 
                ->first();
 
        $row->fecha = $this->formatFecha($row->fecha);

        return $row;
    }

    public function generaRecibointerno($idempresa, $idsede) {
        $numero = \DB::table('movimiento')
                ->where(array('idempresa' => $idempresa, 'idsede' => $idsede, 'iddocumentofiscal' => 9)) //Recibo interno
                ->whereNull('movimiento.deleted') 
                ->max('numero'); 
        
        return $numero + 1;
    }

}
