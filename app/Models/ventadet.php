<?php

namespace App\Models;
 
class ventadet extends apimodel {

    protected $table = 'ventadet';
    protected $primaryKey = 'idventadet';
    public $timestamps = false;
    protected $fillable = [
        
    ];

    protected $hidden = ['idempresa'];


     public function grid($param, $betweendate = '', $likename = '', $items = '', $orderName = '', $orderSort = '') { 
     
            $fields = ['venta.idventa', 'venta.idafiliado', 'afiliado.acronimo', 'documentofiscal.iddocumentofiscal', 'documentofiscal.nombre as nombredocventa', 'venta.serie', 'venta.serienumero', 'venta.fecharegistro', 'cliente.identidad', 'cliente.entidad as nombrecliente', 'cliente.numerodoc', 'moneda.simbolo as abrevmoneda', 'estadodocumento.nombre as estadodocumento', 'venta.fechaventa', 'venta.horaventa', 'venta.total', 'venta.idmediopago', 'mediopago.nombre as mediopagonombre', 'venta.idestadodocumento', 'venta.parteefectivo', 'venta.partemontotarjeta', 'venta.idsede', 'venta.mesventa', 'venta.partetipotarjeta', 'sede.nombre as nombresede', 'citamedica.idcitamedica', 'historiaclinica.hc', 'venta.idapertura', 'venta.created_at', 'venta.updated_at', 'venta.control', 'venta.revision', 'venta.idnotacredito', 'venta.tarjetapriope',
            'notacredito.valorcredito', 'venta.notacreditovalor', 'notacredito.serie as ncserie', 'notacredito.serienumero as ncserienumero', 'ncafiliado.acronimo as ncacronimo', 'ncdocumentofiscal.nombre as ncnombredocventa', 'venta.movecon',
            'venta.tarjetaprimonto', 'venta.tarjetasegmonto', 'mediopagoseg.nombre as mediopagosegnombre', 'venta.idtarjetaseg', 'venta.idcicloatencion',
            'venta.idpaciente', 'paciente.entidad as nombrepaciente', 'venta.idmodelo', 'venta.idcicloautorizacion', 'modelo.nombre as nombremodelo', 'hcp.hc as hcpaciente', 'producto.codigo as codigoproducto', 
            // 'producto.nombre as nombreproducto', 
            'ventadet.nombre as nombreproducto', 'ventadet.descripcion', 
            'ventadet.cantidad', 'ventadet.preciounit', 'ventadet.total as subtotal', 'medico.entidad as nombremedico', 'created.entidad as created',
            \DB::raw('(SELECT GROUP_CONCAT(CONCAT(a.nombre, "/", p.nombre)) from cicloautorizacion ca, aseguradoraplan a, producto p
                WHERE ca.idaseguradoraplan = a.idaseguradoraplan AND ca.idcicloatencion = venta.idcicloatencion AND  p.idproducto = ca.idproducto AND ca.deleted IS NULL) as nombreaseguradoraplan'),
            \DB::raw('(SELECT GROUP_CONCAT(ca.deducible) from cicloautorizacion ca, aseguradoraplan a
                WHERE ca.idaseguradoraplan = a.idaseguradoraplan AND ca.idcicloatencion = venta.idcicloatencion AND ca.deleted IS NULL) as deducible'),
            \DB::raw('(SELECT GROUP_CONCAT(ca.coaseguro) from cicloautorizacion ca, aseguradoraplan a
                WHERE ca.idaseguradoraplan = a.idaseguradoraplan AND ca.idcicloatencion = venta.idcicloatencion AND ca.deleted IS NULL) as coaseguro')
        ]; 
        
        
        $select = \DB::table('venta') 
                ->join('ventadet', 'venta.idventa', '=', 'ventadet.idventa')
                ->join('producto', 'ventadet.idproducto', '=', 'producto.idproducto')
                ->join('entidad as afiliado', 'venta.idafiliado', '=', 'afiliado.identidad')
                ->join('documentofiscal', 'venta.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal')
                ->join('entidad as cliente', 'venta.idcliente', '=', 'cliente.identidad')
                ->join('sede', 'venta.idsede', '=', 'sede.idsede')
                ->join('entidad as created', 'venta.id_created_at', '=', 'created.identidad')
                ->leftJoin('moneda', 'venta.idmoneda', '=', 'moneda.idmoneda')
                ->leftJoin('citamedica', 'venta.idventa', '=', 'citamedica.idventa')
                ->leftJoin('entidad as medico', 'citamedica.idmedico', '=', 'medico.identidad')  
                ->leftJoin('estadodocumento', 'venta.idestadodocumento', '=', 'estadodocumento.idestadodocumento')
                ->leftJoin('mediopago', 'venta.idmediopago', '=', 'mediopago.idmediopago')
                ->leftJoin('mediopago as mediopagoseg', 'venta.idtarjetaseg', '=', 'mediopagoseg.idmediopago')                  
                ->leftJoin('notacredito', 'venta.idnotacredito', '=', 'notacredito.idnotacredito')
                ->leftJoin('entidad as ncafiliado', 'notacredito.idafiliado', '=', 'ncafiliado.identidad')
                ->leftJoin('modelo', 'venta.idmodelo', '=', 'modelo.idmodelo')
                ->leftJoin('entidad as paciente', 'venta.idpaciente', '=', 'paciente.identidad')  

                ->leftJoin('documentofiscal as ncdocumentofiscal', 'notacredito.iddocumentofiscal', '=', 'ncdocumentofiscal.iddocumentofiscal')
                ->leftJoin('historiaclinica', function($join) {
                    $join->on('cliente.identidad', '=', 'historiaclinica.idpaciente')
                         ->on('historiaclinica.idsede', '=', 'venta.idsede');
                })
                ->leftJoin('historiaclinica as hcp', function($join) {
                    $join->on('paciente.identidad', '=', 'hcp.idpaciente')
                         ->on('hcp.idsede', '=', 'venta.idsede');
                })
                ;

         

        $select->select($fields)
                ->whereNull('venta.deleted')
                ->whereNull('ventadet.deleted')
                ->where($param);        

        if (!empty($betweendate)) {
            $select->whereBetween('venta.fechaventa', $betweendate);
        }

        if (!empty($likename)) {
            $select->where('cliente.entidad', 'like', '%' . $likename . '%');
        } 
        
        $orderName = !empty($orderName) ? $orderName : 'venta.fechaventa';
        $orderSort = !empty($orderSort) ? $orderSort : 'desc';
 
        $select->orderBy('venta.idventa', 'desc');

        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        }

        foreach ($data as $row) {
            
            if(isset($row->fecharegistro))
                $row->fecharegistro = $this->formatFecha($row->fecharegistro);

            if(isset($row->fechaventa))   
                $row->fechaventa = $this->formatFecha($row->fechaventa);

            if(isset($row->serienumero))
                $row->documentoSerieNumero = '(' . $row->acronimo . ') ' . $row->nombredocventa . ' N° ' . $row->serie . '-' . str_pad($row->serienumero, 6, "0", STR_PAD_LEFT);
 
            
            if(isset($row->idnotacredito)) 
                $row->documentoNcSerieNumero = '(' . $row->ncacronimo . ') ' . $row->ncnombredocventa . ' N° ' . $row->ncserie . '-' . str_pad($row->ncserienumero, 6, "0", STR_PAD_LEFT);     
            
        }

        return $data;
    }

    
    
}
