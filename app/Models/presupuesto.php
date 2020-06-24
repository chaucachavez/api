<?php

namespace App\Models; 

class presupuesto extends apimodel {

    public $table = 'presupuesto';
    public $primaryKey = 'idpresupuesto';
    public $timestamps = true;
    public $fillable = [
        'idempresa',
        'idsede',
        'idcliente',
        'idcicloatencion',
        'idestado',
        'idestadopago',
        'fecha',
        'tipotarifa',
        'regular',
        'tarjeta',
        'efectivo',
        'montoefectuado',
        'montopago', 
        'montocredito',
        'total',
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];
    protected $hidden = ['idempresa', 'created_at', 'updated_at'];
  
    public function grid($param, $betweendate='',   $likename= '', $items = '', $orderName = '', $orderSort = '') {
        // \DB::enableQueryLog(); 
        $select = \DB::table('presupuesto') 
                ->join('entidad as cliente', 'presupuesto.idcliente', '=', 'cliente.identidad')
                ->join('cicloatencion', 'presupuesto.idcicloatencion', '=', 'cicloatencion.idcicloatencion')
                ->select('presupuesto.idpresupuesto', 'presupuesto.fecha', 'presupuesto.idcicloatencion', 'presupuesto.montopago', 'presupuesto.montocredito', 'presupuesto.montoefectuado')
                ->whereNull('presupuesto.deleted')
                ->where($param);
        
        if (!empty($betweendate)) {
            $select->whereBetween('presupuesto.fecha', $betweendate);
        }
        
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
                ->orderBy('presupuesto.fecha', 'ASC')
                ->get()->all();
        }
        
        // dd(\DB::getQueryLog()); 
        foreach ($data as $row) {
            $row->fecha = $this->formatFecha($row->fecha);
        }

        return $data;
    }
    
    public function presupuesto($param) {
        
        $campos = ['presupuesto.idpresupuesto', 'presupuesto.idsede', 'presupuesto.idcliente', 'presupuesto.idestado', 'estadodocumento.nombre as nombreestadodoc', 'presupuesto.fecha',
            'presupuesto.tipotarifa', 'presupuesto.regular', 'presupuesto.tarjeta', 'presupuesto.efectivo', 
            'presupuesto.montoefectuado', 'presupuesto.montopago', 'presupuesto.montocupon', 'presupuesto.montocredito', 'entidad.entidad as cliente', 'presupuesto.idcicloatencion',
            'presupuesto.idestadopago', 'presupuesto.total', 'paquete.nombre as nombrepaquete'];

        $data = \DB::table('presupuesto')
                ->join('estadodocumento', 'presupuesto.idestado', '=', 'estadodocumento.idestadodocumento')
                ->join('entidad', 'presupuesto.idcliente', '=', 'entidad.identidad')
                ->join('cicloatencion', 'presupuesto.idcicloatencion', '=', 'cicloatencion.idcicloatencion')
                ->leftJoin('paquete', 'cicloatencion.idpaquete', '=', 'paquete.idpaquete')
                ->select($campos)
                ->whereNull('presupuesto.deleted')
                ->where($param)
                ->first();

        if ($data) {
            $data->fecha = $this->formatFecha($data->fecha);
        }

        return $data;
    }

    public function presupuestodetalle($param = [], $whereIdcicloatencionIn = []) {

        $select = \DB::table('presupuestodet')
                ->join('producto', 'presupuestodet.idproducto', '=', 'producto.idproducto')
                ->join('presupuesto', 'presupuestodet.idpresupuesto', '=', 'presupuesto.idpresupuesto')
                ->join('cicloatencion', 'presupuesto.idcicloatencion', '=', 'cicloatencion.idcicloatencion')
                ->select('presupuestodet.idpresupuesto', 'presupuestodet.idpresupuestodet', 'presupuestodet.idproducto', 'producto.nombre as nombreproducto', 'presupuestodet.cantmedico', 'presupuestodet.cantcliente', 'presupuestodet.cantpagada', 'presupuestodet.cantefectivo', 'presupuestodet.tipoprecio', 'presupuestodet.descuento', 'presupuestodet.preciounitregular', 'presupuestodet.totalregular', 'presupuestodet.preciounittarjeta', 'presupuestodet.totaltarjeta', 'presupuestodet.preciounitefectivo', 'presupuestodet.totalefectivo', 'producto.idtipoproducto', 'presupuestodet.observacion', 'presupuesto.idcicloatencion', 'presupuesto.tipotarifa')
                ->whereNull('presupuestodet.deleted')
                ->whereNull('presupuesto.deleted')
                ->whereNull('cicloatencion.deleted');

        if (!empty($param)) {
            $select->where($param);
        }  

        if (!empty($whereIdcicloatencionIn)) { 
            $select->whereIn('cicloatencion.idcicloatencion', $whereIdcicloatencionIn);
        }

        $data = $select->get()->all();

        return $data;
    }
    
    public function presupuestodet($id) {

        $data = \DB::table('presupuestodet')
                ->join('producto', 'presupuestodet.idproducto', '=', 'producto.idproducto')
                ->select('presupuestodet.idpresupuestodet', 'presupuestodet.idproducto', 'producto.nombre as nombreproducto', 'producto.codigo', 'presupuestodet.cantmedico', 'presupuestodet.cantcliente', 'presupuestodet.cantpagada', 'presupuestodet.cantefectivo', 'presupuestodet.tipoprecio', 'presupuestodet.descuento', 'presupuestodet.preciounitregular', 'presupuestodet.totalregular', 'presupuestodet.preciounittarjeta', 'presupuestodet.totaltarjeta', 'presupuestodet.preciounitefectivo', 'presupuestodet.totalefectivo', 'producto.idtipoproducto', 'presupuestodet.observacion', 'presupuestodet.idpresupuestodet')
                ->whereNull('presupuestodet.deleted')
                ->where('presupuestodet.idpresupuesto', $id)
                ->get()->all();

        return $data;
    }
    
    public function grabarLog($idpresupuesto, $id_created_at) { 
        
        $presupuesto = presupuesto::where('idpresupuesto', '=', $idpresupuesto)->first();
        $logpresupuesto = array(
            'idpresupuesto' => $presupuesto->idpresupuesto,
            'idempresa' => $presupuesto->idempresa,
            'idsede' => $presupuesto->idsede,
            'idcliente' => $presupuesto->idcliente, 
            'idcicloatencion' => $presupuesto->idcicloatencion,
            'idestado' => $presupuesto->idestado,
            'fecha' => $presupuesto->fecha,
            'tipotarifa' => $presupuesto->tipotarifa,
            'regular' => $presupuesto->regular,
            'tarjeta' => $presupuesto->tarjeta,
            'efectivo' => $presupuesto->efectivo,
            'montoefectuado' => $presupuesto->montoefectuado,
            'montopago' => $presupuesto->montopago,
            'montocupon' => $presupuesto->montocupon,
            'montocredito' => $presupuesto->montocredito,
            'created_at' => date('Y-m-d H:i:s'), 
            'id_created_at' => $id_created_at, 
            'deleted' => $presupuesto->deleted
        );
        
        $idlogpresupuesto = \DB::table('logpresupuesto')->insertGetId($logpresupuesto, 'idlogpresupuesto');

        $presupuestodet = \DB::table('presupuestodet')
                ->whereNull('presupuestodet.deleted')
                ->where('idpresupuesto', $idpresupuesto)->get()->all();
        
        //Debe usar, este tiuene la particularidad qye esta en el modelo y no en el controlado, falta probar
        //$presupuestodet = $presupuesto->presupuestodet($presupuesto->idpresupuesto);
        
        $logpresupuestodet = [];
        foreach ($presupuestodet as $row) {

            $logpresupuestodet[] = array(
                'idlogpresupuesto' => $idlogpresupuesto,
                'idproducto' => $row->idproducto,
                'cantmedico' => $row->cantmedico,
                'cantcliente' => $row->cantcliente,
                'cantpagada' => $row->cantpagada,
                'cantefectivo' => $row->cantefectivo,
                'tipoprecio' => $row->tipoprecio,
                'codigocupon' => $row->codigocupon,
                'usado' => $row->usado,
                'descuento' => $row->descuento,
                'preciounitregular' => $row->preciounitregular,
                'totalregular' => $row->totalregular,
                'preciounittarjeta' => $row->preciounittarjeta,
                'totaltarjeta' => $row->totaltarjeta,
                'preciounitefectivo' => $row->preciounitefectivo,
                'totalefectivo' => $row->totalefectivo,
                'observacion' => $row->observacion,
                'created_at' => date('Y-m-d H:i:s'), 
                'id_created_at' => $id_created_at, 
                'deleted' => $row->deleted
            );
        }
        \DB::table('logpresupuestodet')->insert($logpresupuestodet);
    }
    
    public function listaLogPresupuesto($idpresupuesto) {
        // \DB::enableQueryLog(); 
        $data = \DB::table('logpresupuesto') 
                ->join('estadodocumento', 'logpresupuesto.idestado', '=', 'estadodocumento.idestadodocumento')
                ->join('entidad as usuario', 'logpresupuesto.id_created_at', '=', 'usuario.identidad')                
                ->select('logpresupuesto.idlogpresupuesto','estadodocumento.nombre as nombreestadodoc', 
                         'logpresupuesto.created_at as fecha', 'usuario.entidad as usuario')
                ->whereNull('logpresupuesto.deleted')
                ->where('idpresupuesto', $idpresupuesto) 
                ->orderBy('logpresupuesto.idlogpresupuesto', 'DESC')
                ->get()->all();
        
        // dd(\DB::getQueryLog()); 
        foreach ($data as $row) {
            //$row->fecha = $this->formatFecha($row->fecha);
        }

        return $data;
    }
    
    public function logpresupuesto($param) {

        $campos = ['logpresupuesto.idlogpresupuesto','logpresupuesto.idestado', 'estadodocumento.nombre as nombreestadodoc', 'logpresupuesto.fecha',
                   'logpresupuesto.tipotarifa', 'logpresupuesto.regular', 'logpresupuesto.tarjeta', 'logpresupuesto.efectivo', 
                   'logpresupuesto.montoefectuado', 'logpresupuesto.montopago', 'logpresupuesto.montocupon', 'logpresupuesto.montocredito', 'entidad.entidad as cliente']; 

        $data = \DB::table('logpresupuesto')  
                ->join('estadodocumento', 'logpresupuesto.idestado', '=', 'estadodocumento.idestadodocumento')
                ->join('entidad', 'logpresupuesto.idcliente', '=', 'entidad.identidad')
                ->select($campos)
                ->whereNull('logpresupuesto.deleted')
                ->where($param)
                ->first();

        if ($data) {
            $data->fecha = $this->formatFecha($data->fecha);
        }

        return $data;
    }
    
    public function logpresupuestodet($id) {

        $data = \DB::table('logpresupuestodet')
                ->join('producto', 'logpresupuestodet.idproducto', '=', 'producto.idproducto')
                ->select('logpresupuestodet.idproducto', 'producto.nombre as nombreproducto', 'logpresupuestodet.cantmedico', 'logpresupuestodet.cantcliente', 'logpresupuestodet.cantpagada', 'logpresupuestodet.cantefectivo', 'logpresupuestodet.tipoprecio', 'logpresupuestodet.descuento', 'logpresupuestodet.preciounitregular', 'logpresupuestodet.totalregular', 'logpresupuestodet.preciounittarjeta', 'logpresupuestodet.totaltarjeta', 'logpresupuestodet.preciounitefectivo', 'logpresupuestodet.totalefectivo', 'producto.idtipoproducto', 'logpresupuestodet.observacion')
                ->whereNull('logpresupuestodet.deleted')
                ->where('logpresupuestodet.idlogpresupuesto', $id)
                ->get()->all();

        return $data;
    }
}
