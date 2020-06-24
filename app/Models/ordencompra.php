<?php

namespace App\Models;
 
class ordencompra extends apimodel {

    protected $table = 'ordencompra';
    protected $primaryKey = 'idordencompra';
    public $timestamps = false;
    protected $fillable = [
        'idordencompra',
        'idempresa',
        'idsede', 
        'iddocumentofiscal',
        'serie',
        'serienumero',
        'idcliente', 
        'fechaventa',
        'horaventa',
        'idmediopago',
        'idestadodocumento',
        'total',
        'idcicloatencion',
        'idcitamedica',
        'canjedoc',
        'dispositivo',
        'token',
        'message',
        'mail',
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted' 
    ];
    protected $hidden = ['idempresa'];


     public function grid($param, $betweendate = '', $likename = '', $items = '', $orderName = '', $orderSort = '') {
 
        $fields = array('ordencompra.idordencompra', 'ordencompra.idsede',  'ordencompra.iddocumentofiscal', 'ordencompra.serie', 'ordencompra.serienumero',
                        'ordencompra.idcliente',  'ordencompra.fechaventa', 'ordencompra.horaventa', 'ordencompra.idmediopago',
                        'ordencompra.idestadodocumento', 'ordencompra.total', 'ordencompra.idcicloatencion', 'ordencompra.idcitamedica', 'ordencompra.dispositivo',
                        'ordencompra.created_at', 'ordencompra.updated_at', 'ordencompra.id_created_at',
                        'ordencompra.id_updated_at', 'documentofiscal.nombre as nombredocventa', 'cliente.entidad as nombrecliente', 'sede.nombre as nombresede',
                        'estadodocumento.nombre as estadodocumento', 'mediopago.nombre as nombremediopago', 'ordencompra.canjedoc',
                        'docfiscal.nombre as nombredocventaoc', 'venta.serie as serieoc', 'venta.serienumero as serienumerooc', 'ordencompra.token', 'ordencompra.message', 'ordencompra.mail');

        $select = \DB::table('ordencompra')
                ->join('documentofiscal', 'ordencompra.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal')
                ->join('entidad as cliente', 'ordencompra.idcliente', '=', 'cliente.identidad')
                ->join('sede', 'ordencompra.idsede', '=', 'sede.idsede')
                ->join('estadodocumento', 'ordencompra.idestadodocumento', '=', 'estadodocumento.idestadodocumento')
                ->join('mediopago', 'ordencompra.idmediopago', '=', 'mediopago.idmediopago')
                ->leftJoin('venta', 'ordencompra.idordencompra', '=', 'venta.idordencompra')
                ->leftJoin('documentofiscal as docfiscal', 'venta.iddocumentofiscal', '=', 'docfiscal.iddocumentofiscal')
                ->select($fields)
                ->whereNull('ordencompra.deleted')
                ->where($param);        

        if (!empty($betweendate)) {
            $select->whereBetween('ordencompra.fechaventa', $betweendate);
        } 

        if (!empty($likename)) {
            $select->where('cliente.entidad', 'like', '%' . $likename . '%');
        } 
        
        $orderName = !empty($orderName) ? $orderName : 'ordencompra.idordencompra';
        $orderSort = !empty($orderSort) ? $orderSort : 'desc';
        $select->orderBy($orderName, $orderSort);

        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        }

        // foreach ($data as $row) {
        //     $row->fechaventa = $this->formatFecha($row->fechaventa);
        //     $row->documentoSerieNumero = $row->nombredocventa . ' N° ' . $row->serie . '-' . str_pad($row->serienumero, 6, "0", STR_PAD_LEFT);  
        // }

         foreach ($data as $row) {
             $row->fechaventa = $this->formatFecha($row->fechaventa);
             $row->documentoSerieNumero = $row->nombredocventa . ' N° ' . $row->serie . '-' . str_pad($row->serienumero, 6, "0", STR_PAD_LEFT);
             $row->documentoSerieNumeroOc = null;
             if($row->nombredocventaoc) {
                 $row->documentoSerieNumeroOc = $row->nombredocventaoc . ' N° ' . $row->serieoc . '-' . str_pad($row->serienumerooc, 6, "0", STR_PAD_LEFT);

             }
         }

        return $data;
    } 

    public function ordencompra($id) {
        
        $fields = ['ordencompra.idordencompra', 'ordencompra.idsede',  'ordencompra.iddocumentofiscal', 'ordencompra.serie', 'ordencompra.serienumero',
                    'ordencompra.idcliente',  'ordencompra.fechaventa', 'ordencompra.horaventa', 'ordencompra.idmediopago', 'ordencompra.idestadodocumento',
                    'ordencompra.total', 'ordencompra.idcicloatencion', 'ordencompra.idcitamedica', 'ordencompra.dispositivo', 'ordencompra.created_at',
                    'ordencompra.updated_at', 'ordencompra.id_created_at', 'ordencompra.id_updated_at', 'documentofiscal.nombre as nombredocventa',
                    'cliente.entidad as nombrecliente', 'sede.nombre as nombresede',  'estadodocumento.nombre as estadodocumento', 'mediopago.nombre as nombremediopago',
                    'citamedica.fecha as fechacita', 'citamedica.inicio as horacita','documento.abreviatura as abrevdocumento', 'cliente.numerodoc'];


        //\DB::enableQueryLog();
        $row = \DB::table('ordencompra')
                ->join('documentofiscal', 'ordencompra.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal')
                ->join('entidad as cliente', 'ordencompra.idcliente', '=', 'cliente.identidad')
                ->join('sede', 'ordencompra.idsede', '=', 'sede.idsede')                
                ->join('estadodocumento', 'ordencompra.idestadodocumento', '=', 'estadodocumento.idestadodocumento')
                ->join('mediopago', 'ordencompra.idmediopago', '=', 'mediopago.idmediopago')
                ->join('documento', 'cliente.iddocumento', '=', 'documento.iddocumento')
                ->leftJoin('citamedica', 'ordencompra.idcitamedica', '=', 'citamedica.idcitamedica')
                ->select($fields)
                ->whereNull('ordencompra.deleted') 
                ->where('ordencompra.idordencompra', $id)
                ->first();
          
         if($row) {
             $row->documentoSerieNumero = $row->nombredocventa . ' N° ' . $row->serie . '-' . str_pad($row->serienumero, 6, "0", STR_PAD_LEFT);
             $row->fechacita = $this->formatFecha($row->fechacita);
             $row->fechaventa = $this->formatFecha($row->fechaventa);
         }

        return $row;
    }
    
    public function ordencompradet($id) { 
        $data = \DB::table('ordencompradet')
                ->join('producto', 'ordencompradet.idproducto', '=', 'producto.idproducto')  
                ->select('ordencompradet.idordencompradet', 'ordencompradet.idordencompra', 'producto.nombre as nombreproducto', 'ordencompradet.idproducto', 
                         'ordencompradet.cantidad', 'ordencompradet.idunidadmedida',  'ordencompradet.preciounit', 'ordencompradet.codigocupon', 'ordencompradet.descuento', 'ordencompradet.total',
                         'ordencompradet.created_at', 'ordencompradet.updated_at', 'ordencompradet.id_created_at', 'ordencompradet.id_updated_at')
                ->whereNull('ordencompradet.deleted')
                ->where('ordencompradet.idordencompra', $id)
                ->get()->all();

        return $data;
    }
    
}
