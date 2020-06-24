<?php

namespace App\Models; 

class cicloautorizacion extends apimodel {

    protected $table = 'cicloautorizacion';
    protected $primaryKey = 'idcicloautorizacion';
    public $timestamps = false;
    protected $fillable = [
        'idcicloatencion',
        'idsede',
        'fecha',
        'hora',
        'numero',
        'idestadoimpreso',
        'idaseguradora',
        'idpaciente',
        'idproducto',
        'idaseguradoraplan',
        'deducible',
        'idcoaseguro',
        'coaseguro',
        'idtipo',
        'codigo',
        'descripcion',
        'idtitular',
        'parentesco',
        'nombrecompania',
        'principal',
        'idventa',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];
//    protected $hidden = ['idempresa'];

    public function grid($param, $likename = '', $betweendate = '', $items = '', $orderName = '', $orderSort = '') {
        // dd($param, $likename, $betweendate, $items, $orderName, $orderSort);
        $select = \DB::table('cicloatencion')            
            ->join('cicloautorizacion', 'cicloatencion.idcicloatencion', '=', 'cicloautorizacion.idcicloatencion')
            ->join('sede', 'cicloautorizacion.idsede', '=', 'sede.idsede')
            ->join('aseguradora', 'cicloautorizacion.idaseguradora', '=', 'aseguradora.idaseguradora')
            ->join('entidad as paciente', 'cicloautorizacion.idpaciente', '=', 'paciente.identidad') 
            //Se puede facturar consultas , sin presupuesto
            ->leftJoin('presupuesto', 'cicloatencion.idcicloatencion', '=', 'presupuesto.idcicloatencion')
            ->leftJoin('presupuestodet', function($join) {
                $join->on('presupuesto.idpresupuesto', '=', 'presupuestodet.idpresupuesto')
                     ->on('presupuestodet.idproducto', '=', 'cicloautorizacion.idproducto');
            })
            ->join('aseguradoraplan', 'cicloautorizacion.idaseguradoraplan', '=', 'aseguradoraplan.idaseguradoraplan')            
            ->join('producto', 'cicloautorizacion.idproducto', '=', 'producto.idproducto')
            ->join('estadodocumento as tipo', 'cicloautorizacion.idtipo', '=', 'tipo.idestadodocumento')
            ->leftJoin('coaseguro', 'cicloautorizacion.idcoaseguro', '=', 'coaseguro.idcoaseguro') //acupuntura no tiene COASEGURO, fisioterapia SI TIENE
            ->leftJoin('entidad as cliente', 'aseguradoraplan.idcliente', '=', 'cliente.identidad')        
            ->leftJoin('historiaclinica', function($join) {
                $join->on('cicloautorizacion.idpaciente', '=', 'historiaclinica.idpaciente')
                     ->on('historiaclinica.idsede', '=', 'cicloautorizacion.idsede');
            })
            ->leftJoin('venta', 'cicloautorizacion.idventa', '=', 'venta.idventa') 
            ->leftJoin('documentofiscal', 'venta.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal')
            ->leftJoin('ventafactura', 'venta.idventa', '=', 'ventafactura.idventa')
            ->select('aseguradora.nombre as nombreaseguradora', 'aseguradoraplan.nombre as nombreaseguradoraplan', 'coaseguro.nombre as nombrecoaseguro', 'coaseguro.valor', 'producto.nombre as nombreproducto',
                    'cicloautorizacion.idcicloautorizacion', 'cicloautorizacion.idsede', 'cicloautorizacion.fecha', 'cicloautorizacion.idestadoimpreso','cicloautorizacion.deducible', 'cicloautorizacion.coaseguro', 'cicloautorizacion.principal',
                    'cicloautorizacion.idaseguradora', 'cicloautorizacion.idaseguradoraplan', 'cicloautorizacion.idcoaseguro', 'cicloautorizacion.idtipo', 'cicloautorizacion.codigo', 'cicloautorizacion.descripcion', 
                    'cicloatencion.idcicloatencion', 'cicloautorizacion.idproducto',  'cicloautorizacion.nombrecompania', 'cicloautorizacion.idtitular', 'cicloautorizacion.parentesco', 'cicloautorizacion.idpaciente',
                    'paciente.entidad as paciente', 'paciente.apellidopat', 'paciente.apellidomat', 'paciente.nombre', 'cicloatencion.idestadofactura', 'tipo.nombre as nombretipo',
                    'sede.nombre as nombresede', 'sede.sedeabrev', 'cicloatencion.primert', 'cicloatencion.ultimot', 'presupuestodet.cantefectivo', 'aseguradoraplan.idcliente',
                    'cliente.entidad as cliente', 'historiaclinica.hc', 'cicloautorizacion.idventa',
                    'venta.serie', 'venta.serienumero', 'documentofiscal.nombre as nombredocventa', 'venta.fechaventa', 'venta.idmodelo', 'venta.subtotal', 'venta.valorimpuesto', 'venta.total', 'venta.coaseguro as valorcoaseguro', 'venta.deducible as valordeducible', 'venta.descripcion as factdescripcion', 'ventafactura.titular', 'ventafactura.zona', 'ventafactura.empresa', 'ventafactura.ciclo', 'ventafactura.diagnostico', 'ventafactura.hc as sedehc', 'ventafactura.programa','ventafactura.deducible as factdeducible', 'ventafactura.coaseguro as factcoaseguro', 'ventafactura.consulta', 'ventafactura.sesiones', 'ventafactura.totaldetto', 'ventafactura.totalaseguradora', 'ventafactura.pcttotalcoaded', 'ventafactura.autorizacion')  
            ->whereNull('cicloatencion.deleted')
            ->whereNull('venta.deleted')
            ->whereNull('cicloautorizacion.deleted')
            ->where($param);
            // dd($param);
        if (!empty($likename)) { 
            $select->where('paciente.entidad', 'like', '%' . $likename . '%');
        }

        if (!empty($betweendate)) { 
            $select->whereBetween('cicloautorizacion.fecha', $betweendate);
        }

        $orderName = !empty($orderName) ? $orderName : 'cicloautorizacion.fecha';
        $orderSort = !empty($orderSort) ? $orderSort : 'desc'; 
        // dd($orderName, $orderSort);
        $select->orderBy($orderName, $orderSort);
        
        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        }
        
        // dd($data);
        foreach ($data as $row) {
            if (isset($row->fecha))
                $row->fecha = $this->formatFecha($row->fecha);
            if (isset($row->primert))
                $row->primert = $this->formatFecha($row->primert);
            if (isset($row->ultimot))
                $row->ultimot = $this->formatFecha($row->ultimot);
            if (isset($row->fechaventa))
                $row->fechaventa = $this->formatFecha($row->fechaventa);
        }
        
        return $data;
    }

    public function cicloautorizacion($id) { 

        $data = \DB::table('cicloatencion')
            ->join('cicloautorizacion', 'cicloatencion.idcicloatencion', '=', 'cicloautorizacion.idcicloatencion')
            ->join('aseguradora', 'cicloautorizacion.idaseguradora', '=', 'aseguradora.idaseguradora')
            ->join('entidad as cliente', 'cicloatencion.idpaciente', '=', 'cliente.identidad')
            ->leftJoin('estadodocumento as tipo', 'cicloautorizacion.idtipo', '=', 'tipo.idestadodocumento')
            ->leftJoin('aseguradoraplan', 'cicloautorizacion.idaseguradoraplan', '=', 'aseguradoraplan.idaseguradoraplan')
            ->leftJoin('coaseguro', 'cicloautorizacion.idcoaseguro', '=', 'coaseguro.idcoaseguro')
            ->leftJoin('producto', 'cicloautorizacion.idproducto', '=', 'producto.idproducto')
            ->leftJoin('entidad as titular', 'cicloautorizacion.idtitular', '=', 'titular.identidad')             
            ->select('aseguradora.nombre as nombreaseguradora', 'aseguradoraplan.nombre as nombreaseguradoraplan', 'coaseguro.nombre as nombrecoaseguro', 'coaseguro.valor', 'producto.nombre as nombreproducto',
                    'cicloautorizacion.idcicloautorizacion', 'cicloautorizacion.idsede', 'cicloautorizacion.fecha', 'cicloautorizacion.deducible', 'cicloautorizacion.coaseguro', 'cicloautorizacion.principal',
                    'cicloautorizacion.idaseguradora', 'cicloautorizacion.idaseguradoraplan', 'cicloautorizacion.idcoaseguro', 'cicloautorizacion.idtipo', 'cicloautorizacion.codigo', 'cicloautorizacion.descripcion',
                    'cicloatencion.idcicloatencion', 'cicloatencion.idpaciente', 'cicloautorizacion.idproducto', 'titular.entidad as nombretitular', 'cicloautorizacion.nombrecompania', 'cicloautorizacion.idtitular', 'cicloautorizacion.parentesco', 
                    'cliente.entidad as paciente', 'cicloautorizacion.idtipo', 'tipo.nombre as nombretipo', 'aseguradoraplan.abreviatura') 
            ->whereNull('cicloatencion.deleted')
            ->whereNull('cicloautorizacion.deleted')
            ->where('cicloautorizacion.idcicloautorizacion', $id)
            ->first();

       
       $data->fecha = $this->formatFecha($data->fecha);
 
        return $data;
    }

     

}
