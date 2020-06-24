<?php

namespace App\Models;
 
class venta extends apimodel {

    protected $table = 'venta';
    protected $primaryKey = 'idventa';
    public $timestamps = false;
    protected $fillable = [
        'idventa',
        'idempresa',
        'idsede',
        'idafiliado', 
        'iddocumentofiscal',
        'serie',
        'serienumero',
        'fecharegistro',
        'horaregistro',
        'idcliente',
        'idpaciente',
        'idmodelo',
        'idapertura',
        'fechaventa',
        'horaventa',
        'anoventa',
        'mesventa',
        'idmoneda',
        'idmediopago',
        'idnotacredito',
        'notacreditovalor',
        'idestadodocumento',
        'subtotal',
        'descuento',
        'igv',
        'valorimpuesto',
        'total', 
        'nrooperacion',
        'idtarjetapri', 
        'tarjetapriope', 
        'tarjetaprimonto',
        'idtarjetaseg',
        'tarjetasegope',
        'tarjetasegmonto',
        'partetipotarjeta',
        'parteopetarjeta',
        'partemontotarjeta',
        'parteefectivo',
        'descripcion',
        'motivo',
        'idcicloatencion',
        'idcicloautorizacion',
        'movecon',
        'idventaanulado',
        'idventareemplazo', 
        'identidadrevision',
        'revision',
        'revisioncomentario',
        'identidadctrol',
        'fechactrol',
        'control',
        'controlcomentario',
        'idordencompra',
        'deducible',
        'coaseguro',
        'idestadoseguro',
        'tiponotacredito',
        'idventaref',
        'cpeemision',
        'cpeanulacion',
        'cpeticket',
        'cpecorreo',
        'cpemensaje',
        'correolog',
        'culqitkn',
        'culqichr',
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];

    protected $hidden = ['idempresa'];


    public function grid($param, $betweendate = '', $likename = '', $items = '', $orderName = '', $orderSort = '', $betweenhora = '', 
                            $nullIdcitamedica = FALSE, $ventareemplazo = FALSE, $whereIdaperturaIn = [], $fields = [], $whereIdnotacreditoIn = [], $whereIddocumentofiscal = [], $likepaciente = '', $datosfactura = false, $ventareferencia = false, $IdsedeIn = []) {

        if(empty($fields)){
            $fields = ['venta.idventa', 'venta.idafiliado', 'afiliado.acronimo', 'afiliado.numerodoc as numerodocafiliado', 'afiliado.entidad as afiliado', 'documentofiscal.iddocumentofiscal', 'documentofiscal.nombre as nombredocventa', 'documentofiscal.iddocumentofiscal', 'documentofiscal.codigosunat', 'venta.serie', 'venta.serienumero', 'venta.fecharegistro', 'cliente.identidad', 'cliente.entidad as nombrecliente', 'cliente.iddocumento', 'cliente.numerodoc', 'moneda.simbolo as abrevmoneda', 'estadodocumento.nombre as estadodocumento', 'venta.fechaventa', 'venta.horaventa', 'venta.total', 'venta.nrooperacion', 'venta.idmediopago', 'mediopago.nombre as mediopagonombre', 'venta.idestadodocumento', 'venta.parteefectivo', 'venta.partemontotarjeta', 'venta.idsede', 'venta.mesventa', 'venta.partetipotarjeta', 'sede.nombre as nombresede', 'citamedica.idcitamedica', 'historiaclinica.hc', 'venta.idapertura',
            'created.entidad as created', 'updated.entidad as updated', 'venta.created_at', 'venta.updated_at', 'venta.control', 'venta.revision', 'venta.tarjetapriope',
               'venta.movecon',
            'venta.tarjetaprimonto', 'venta.tarjetasegmonto', 'mediopagoseg.nombre as mediopagosegnombre', 'venta.idtarjetaseg', 'venta.idcicloatencion',
            'venta.idpaciente', 'paciente.entidad as nombrepaciente', 'venta.idmodelo', 'venta.idcicloautorizacion', 'modelo.nombre as nombremodelo', 'hcp.hc as hcpaciente', 'venta.descripcion', 'venta.valorimpuesto', 'venta.subtotal', 'venta.deducible', 'venta.coaseguro', 'aseguradoraplan.nombre as nombreaseguradoraplan', 
                'cicloatencion.fecha as fechaaperturaciclo', 'venta.idventaref', 'venta.tiponotacredito', 'venta.descuento', 'venta.cpeemision',
        'venta.cpeanulacion', 'venta.cpecorreo', 'venta.idventareemplazo'
            ];
        }

        /*  La relacion a 'cicloatencion', lo puse unicamente porque Gisela Mac desea saber el numero 
            de pacientes que pagaron tratamiento el dia de consulta, por lo que necesito que fecha de pago de venta(tratamientos) sea igual a la fecha de apertura del ciclo "fechaaperturaciclo". 
            Esto incremento 0.5 el tiempo de consulta, MOTIVO por el que desearia condicionar.
        */
        // \DB::enableQueryLog();  
        $select = \DB::table('venta') 
                ->join('entidad as afiliado', 'venta.idafiliado', '=', 'afiliado.identidad')
                ->join('documentofiscal', 'venta.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal')
                ->join('entidad as cliente', 'venta.idcliente', '=', 'cliente.identidad')
                ->join('sede', 'venta.idsede', '=', 'sede.idsede')
                
                ->leftJoin('cicloatencion', 'venta.idcicloatencion', '=', 'cicloatencion.idcicloatencion')
                ->leftJoin('moneda', 'venta.idmoneda', '=', 'moneda.idmoneda')
                ->leftJoin('citamedica', 'venta.idventa', '=', 'citamedica.idventa')
                ->leftJoin('estadodocumento', 'venta.idestadodocumento', '=', 'estadodocumento.idestadodocumento')
                ->leftJoin('mediopago', 'venta.idmediopago', '=', 'mediopago.idmediopago')
                ->leftJoin('mediopago as mediopagoseg', 'venta.idtarjetaseg', '=', 'mediopagoseg.idmediopago')                
                ->leftJoin('entidad as created', 'venta.id_created_at', '=', 'created.identidad')  
                ->leftJoin('entidad as updated', 'venta.id_updated_at', '=', 'updated.identidad') 
                
                ->leftJoin('modelo', 'venta.idmodelo', '=', 'modelo.idmodelo')
                ->leftJoin('entidad as paciente', 'venta.idpaciente', '=', 'paciente.identidad')  
                
                ->leftJoin('cicloautorizacion', 'venta.idcicloautorizacion', '=', 'cicloautorizacion.idcicloautorizacion')
                ->leftJoin('aseguradoraplan', 'cicloautorizacion.idaseguradoraplan', '=', 'aseguradoraplan.idaseguradoraplan')
                ->leftJoin('historiaclinica', function($join) {
                    $join->on('cliente.identidad', '=', 'historiaclinica.idpaciente')
                         ->on('historiaclinica.idsede', '=', 'venta.idsede');
                })
                ->leftJoin('historiaclinica as hcp', function($join) {
                    $join->on('paciente.identidad', '=', 'hcp.idpaciente')
                         ->on('hcp.idsede', '=', 'venta.idsede');
                })
                ;
        
        if ($ventareemplazo) { 
            array_push($fields, 'ventareemplazo.serie as reemplazoserie', 'ventareemplazo.serienumero as reemplazoserienumero', 'afilreemplazo.acronimo as reemplazoacronimo', 'docreemplazo.nombre as reemplazonombredocventa', 'ventareemplazo.fechaventa as fechaventareemplazo', 'estadoreemplazo.nombre as reemplazoestadodocumento', 'ventareemplazo.movecon as reemplazomovecon', 'ventareemplazo.total as reemplazototal');

            $select->leftJoin('venta as ventareemplazo', 'venta.idventareemplazo', '=', 'ventareemplazo.idventa')
                   ->leftJoin('entidad as afilreemplazo', 'ventareemplazo.idafiliado', '=', 'afilreemplazo.identidad')
                   ->leftJoin('estadodocumento as estadoreemplazo', 'ventareemplazo.idestadodocumento', '=', 'estadoreemplazo.idestadodocumento')
                   ->leftJoin('documentofiscal as docreemplazo', 'ventareemplazo.iddocumentofiscal', '=', 'docreemplazo.iddocumentofiscal');
        }

        if ($ventareferencia) { 
            array_push($fields, 'ventaref.serie as refserie', 'ventaref.serienumero as refserienumero', 'afiliadoref.acronimo as refacronimo', 'docref.nombre as refnombredocventa', 'ventaref.fechaventa as reffechaemision');

            $select->leftJoin('venta as ventaref', 'venta.idventaref', '=', 'ventaref.idventa')
                   ->leftJoin('entidad as afiliadoref', 'ventaref.idafiliado', '=', 'afiliadoref.identidad')
                   ->leftJoin('documentofiscal as docref', 'ventaref.iddocumentofiscal', '=', 'docref.iddocumentofiscal');
        }

        if ($datosfactura) {
            array_push($fields, 'ventafactura.hc as fahc', 'ventafactura.paciente as fapaciente', 'ventafactura.seguroplan as faseguroplan', 'ventafactura.titular as fatitular', 'ventafactura.empresa as faempresa', 'ventafactura.ciclo as faciclo', 'ventafactura.diagnostico as fadiagnostico', 'ventafactura.indicacion as faindicacion', 'ventafactura.autorizacion as faautorizacion', 'ventafactura.programa as faprograma', 'ventafactura.deducible as fadeducible', 'ventafactura.coaseguro as facoaseguro', 'ventafactura.letra as faletra','ventafactura.consulta as faconsulta', 'ventafactura.sesiones as fasesiones', 'ventafactura.totaldetto as fatotaldetto', 'ventafactura.totalaseguradora as fatotalaseguradora', 'ventafactura.pcttotalcoaded as fapcttotalcoaded');

            $select->leftJoin('ventafactura', 'venta.idventa', '=', 'ventafactura.idventa');
        }

        $select->select($fields)
                ->whereNull('venta.deleted')
                ->where($param);        

        if (!empty($betweendate)) {
            $select->whereBetween('venta.fechaventa', $betweendate);
        }

        if (!empty($betweenhora)) {
            $select->whereBetween('venta.horaventa', $betweenhora);
        }

        if (!empty($likename)) {
            $select->where('cliente.entidad', 'like', '%' . $likename . '%');
        }

        if (!empty($likepaciente)) {
            $select->where('paciente.entidad', 'like', '%' . $likepaciente . '%');
        }

        if (!empty($whereIdaperturaIn)) {
            $select->whereIn('venta.idapertura', $whereIdaperturaIn);
        }

        if (!empty($whereIdnotacreditoIn)) {
            $select->whereIn('venta.idnotacredito', $whereIdnotacreditoIn);
        }

        if (!empty($IdsedeIn)) {
            $select->whereIn('venta.idsede', $IdsedeIn);
        } 

         if (!empty($whereIddocumentofiscal)) {
             $select->whereIn('venta.iddocumentofiscal', $whereIddocumentofiscal);
         }

        if ($nullIdcitamedica) {
            $select->whereNull('citamedica.idcitamedica');
        } 
        
        $orderName = !empty($orderName) ? $orderName : 'venta.fechaventa';
        $orderSort = !empty($orderSort) ? $orderSort : 'desc';

        $select->orderBy($orderName, $orderSort)
                ->orderBy('venta.horaventa', 'desc');

        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        }
        // dd(\DB::getQueryLog());

        foreach ($data as $row) {
            
            if(isset($row->fecharegistro))
                $row->fecharegistro = $this->formatFecha($row->fecharegistro);

            if(isset($row->fechaventa))   
                $row->fechaventa = $this->formatFecha($row->fechaventa);

            if(isset($row->fechaventareemplazo))   
                $row->fechaventareemplazo = $this->formatFecha($row->fechaventareemplazo);

            if(isset($row->reffechaemision))   
                $row->reffechaemision = $this->formatFecha($row->reffechaemision);
            
            if(isset($row->serienumero))
                // $row->documentoSerieNumero = '(' . $row->acronimo . ') ' . $row->nombredocventa . ' N° ' . $row->serie . '-' . str_pad($row->serienumero, 6, "0", STR_PAD_LEFT);
                $row->documentoSerieNumero = '(' . $row->acronimo . ') ' . $row->nombredocventa . ' N° ' . $row->serie . '-' . $row->serienumero;
             
            if(isset($row->reemplazoserienumero))
                $row->reemplazoDocumentoSerieNumero = '(' . $row->reemplazoacronimo . ') ' . $row->reemplazonombredocventa . ' N° ' . $row->reemplazoserie . '-' . str_pad($row->reemplazoserienumero, 6, "0", STR_PAD_LEFT);

            if(isset($row->refserienumero))
                $row->refDocumentoSerieNumero = '(' . $row->refacronimo . ') ' . $row->refnombredocventa . ' N° ' . $row->refserie . '-' . str_pad($row->refserienumero, 6, "0", STR_PAD_LEFT);

            if(isset($row->idnotacredito)) 
                $row->documentoNcSerieNumero = '(' . $row->ncacronimo . ') ' . $row->ncnombredocventa . ' N° ' . $row->ncserie . '-' . str_pad($row->ncserienumero, 6, "0", STR_PAD_LEFT);     

            if(isset($row->fechaaperturaciclo))   
                $row->fechaaperturaciclo = $this->formatFecha($row->fechaaperturaciclo);            
            
        }

        return $data;
    }

    public function gridLight($param, $betweendate = '', $likename = '', $items = '', $orderName = '', $orderSort = '',  $fields = []) { 

        // \DB::enableQueryLog();  
        $select = \DB::table('venta') 
                ->join('entidad as afiliado', 'venta.idafiliado', '=', 'afiliado.identidad')
                ->join('documentofiscal', 'venta.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal')
                ->join('entidad as cliente', 'venta.idcliente', '=', 'cliente.identidad')
                ->join('sede', 'venta.idsede', '=', 'sede.idsede');

        $select->select($fields)
                ->whereNull('venta.deleted')
                ->where($param);        

        if (!empty($betweendate)) {
            $select->whereBetween('venta.fechaventa', $betweendate);
        }

        if (!empty($likename)) {
            $select->where('cliente.entidad', 'like', '%' . $likename . '%');
        } 
        
        $orderName = !empty($orderName) ? $orderName : 'venta.fechaventa';
        $orderSort = !empty($orderSort) ? $orderSort : 'desc';

        $select->orderBy($orderName, $orderSort)
                ->orderBy('venta.horaventa', 'desc');

        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        }
        // dd(\DB::getQueryLog());

        foreach ($data as $row) {
            
            if(isset($row->fecharegistro))
                $row->fecharegistro = $this->formatFecha($row->fecharegistro);

            if(isset($row->fechaventa))   
                $row->fechaventa = $this->formatFecha($row->fechaventa);

            if(isset($row->serienumero))
                $row->documentoSerieNumero = '(' . $row->acronimo . ') ' . $row->nombredocventa . ' N° ' . $row->serie . '-' . str_pad($row->serienumero, 6, "0", STR_PAD_LEFT);   
            
        }

        return $data;
    }

    public function venta($id,  $hc = false, $ventareferencia = false, $ventareemplazo = false) {
        
        $campos = ['venta.idventa', 'venta.idsede', 'sede.nombre as nombresede',  'sede.direccion as direccionsede', 'afiliado.acronimo', 'afiliado.entidad as afiliado', 'afiliado.numerodoc as numerodocafiliado',
                            'venta.iddocumentofiscal', 'documentofiscal.nombre as nombredocfiscal', 'venta.serie', 'venta.serienumero',
                            'venta.idcliente',  'cliente.entidad as cliente', 'venta.idpaciente', 'paciente.entidad as paciente', 'venta.fechaventa', 'venta.horaventa', 'venta.idmediopago', 'mediopago.nombre as nombremediopago',
                            'venta.idestadodocumento', 'estadodocumento.nombre as nombreestadodoc','venta.descuento', 'venta.subtotal', 'venta.valorimpuesto', 'venta.total', 'venta.deducible', 'venta.coaseguro',
                            'venta.partetipotarjeta', 'tarjeta.nombre as nombretarjeta', 'venta.parteopetarjeta', 'venta.partemontotarjeta','venta.parteefectivo', 'cliente.iddocumento', 'cliente.numerodoc', 'documento.abreviatura as abrevdocumento', 'venta.idcicloatencion', 'venta.idafiliado', 'venta.idapertura', 
                            'created.entidad as created', 'updated.entidad as updated', 'venta.created_at', 'venta.updated_at', 
                            'responsable.entidad as nombreresponsable', 'venta.control',  'venta.fechactrol', 'revision.entidad as nombreresponsablerev', 'venta.revision', 'venta.revisioncomentario', 'venta.controlcomentario',
                            'mediopagoseg.nombre as mediopagosegnombre', 'venta.tarjetapriope', 'venta.tarjetaprimonto', 'venta.idtarjetaseg', 'venta.tarjetasegope', 'venta.tarjetasegmonto', 'venta.idestadoseguro', 'venta.descripcion', 'venta.movecon', 
                            'cicloatencion.fecha as fechaaperturaciclo', 'documentofiscal.codigosunat', 'venta.tiponotacredito', 'venta.cpeemision', 'venta.cpeanulacion', 'venta.cpecorreo', 'venta.cpeticket', 'venta.idventaref', 'venta.idventareemplazo'];
                            
        if ($hc){
            $campos[] = 'historiaclinica.hc';   
            $campos[] = 'cliente.direccion';
        }        
        //\DB::enableQueryLog();
        $select = \DB::table('venta')
                ->join('sede', 'venta.idsede', '=', 'sede.idsede')
                ->join('entidad as afiliado', 'venta.idafiliado', '=', 'afiliado.identidad') 
                ->join('documentofiscal', 'venta.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal')
                ->join('entidad as cliente', 'venta.idcliente', '=', 'cliente.identidad')
                ->join('documento', 'cliente.iddocumento', '=', 'documento.iddocumento')
                ->leftJoin('cicloatencion', 'venta.idcicloatencion', '=', 'cicloatencion.idcicloatencion')
                ->leftJoin('entidad as paciente', 'venta.idpaciente', '=', 'paciente.identidad')  
                ->leftJoin('entidad as created', 'venta.id_created_at', '=', 'created.identidad')  
                ->leftJoin('entidad as responsable', 'venta.identidadctrol', '=', 'responsable.identidad')  
                ->leftJoin('entidad as revision', 'venta.identidadrevision', '=', 'revision.identidad')  
                ->leftJoin('entidad as updated', 'venta.id_updated_at', '=', 'updated.identidad');

                if($hc){
                    $select->leftJoin('historiaclinica', function($join){
                        $join->on('venta.idpaciente', '=', 'historiaclinica.idpaciente')
                             ->on('venta.idsede', '=', 'historiaclinica.idsede');
                    });
                }

                if ($ventareferencia) {
                    array_push($campos, 'ventaref.serie as refserie', 'ventaref.serienumero as refserienumero', 
                        // 'afiliadoref.acronimo as refacronimo', 
                        'docref.codigosunat as refcodigosunat');

                    $select->leftJoin('venta as ventaref', 'venta.idventaref', '=', 'ventaref.idventa')
                           // ->leftJoin('entidad as afiliadoref', 'ventaref.idafiliado', '=', 'afiliadoref.identidad')
                           ->leftJoin('documentofiscal as docref', 'ventaref.iddocumentofiscal', '=', 'docref.iddocumentofiscal');
                }

                if ($ventareemplazo) { 
                    array_push($campos, 'ventareemplazo.serie as reemplazoserie', 'ventareemplazo.serienumero as reemplazoserienumero', 'afilreemplazo.acronimo as reemplazoacronimo', 'docreemplazo.nombre as reemplazonombredocventa', 'ventareemplazo.fechaventa as fechaventareemplazo', 'ventareemplazo.movecon as reemplazomovecon', 'ventareemplazo.total as reemplazototal');

                    $select->leftJoin('venta as ventareemplazo', 'venta.idventareemplazo', '=', 'ventareemplazo.idventa')
                           ->leftJoin('entidad as afilreemplazo', 'ventareemplazo.idafiliado', '=', 'afilreemplazo.identidad') 
                           ->leftJoin('documentofiscal as docreemplazo', 'ventareemplazo.iddocumentofiscal', '=', 'docreemplazo.iddocumentofiscal');
                }

        $data = $select
                ->leftJoin('mediopago', 'venta.idmediopago', '=', 'mediopago.idmediopago')
                ->leftJoin('estadodocumento', 'venta.idestadodocumento', '=', 'estadodocumento.idestadodocumento')
                ->leftJoin('mediopago as mediopagoseg', 'venta.idtarjetaseg', '=', 'mediopagoseg.idmediopago')                
                ->leftJoin('mediopago as tarjeta', 'venta.partetipotarjeta', '=', 'tarjeta.idmediopago')
                ->select($campos)
                ->whereNull('venta.deleted')
                ->where('venta.idventa', $id)
                ->first();
        
        //dd(\DB::getQueryLog());    
        if($data) { 
            $data->fechaventa = $this->formatFecha($data->fechaventa);
            $data->fechactrol = $this->formatFecha($data->fechactrol); 

            if ($data->fechaaperturaciclo) {
                $data->fechaaperturaciclo = $this->formatFecha($data->fechaaperturaciclo); 
            }

            if(isset($data->reemplazoserienumero))
                $data->reemplazoDocumentoSerieNumero = '(' . $data->reemplazoacronimo . ') ' . $data->reemplazonombredocventa . ' N° ' . $data->reemplazoserie . '-' . str_pad($data->reemplazoserienumero, 6, "0", STR_PAD_LEFT);
        }
        
        return $data;
    }
    
    public function ventadet($id) {
        
        $data = \DB::table('ventadet')
                ->join('producto', 'ventadet.idproducto', '=', 'producto.idproducto')
                ->join('venta', 'ventadet.idventa', '=', 'venta.idventa')
                // ->leftJoin('entidad as cliente', 'cliente.identidad', '=', 'ventadet.idcliente')
                // ->leftJoin('documento', 'cliente.iddocumento', '=', 'documento.iddocumento')
                ->select('ventadet.idventadet', 'ventadet.idproducto', 'ventadet.nombre as nombreproducto', 'producto.codigosunat','ventadet.cantidad', 
                         'ventadet.idcliente', 'ventadet.preciounit',
                         'ventadet.codigocupon', 'ventadet.descuento', 'ventadet.total', 'ventadet.idcitamedica', 'ventadet.descripcion', 'ventadet.idcicloatencion', 'venta.iddocumentofiscal', 'ventadet.valorunit', 'ventadet.valorventa', 'ventadet.montototalimpuestos')
                ->whereNull('ventadet.deleted')
                ->whereNull('venta.deleted')
                ->where('ventadet.idventa', $id)
                ->get()->all();

        return $data;
    } 

    public function ventafactura($id) {
        $campos = ['ventafactura.idventafactura', 'ventafactura.idventa', 'ventafactura.hc', 'ventafactura.paciente', 'ventafactura.seguroplan', 'ventafactura.titular',
         'ventafactura.empresa', 'ventafactura.diagnostico', 'ventafactura.zona', 'ventafactura.indicacion', 'ventafactura.autorizacion', 'ventafactura.programa',
          'ventafactura.deducible', 'ventafactura.coaseguro', 'ventafactura.letra'];

        $row = \DB::table('ventafactura')               
                ->select($campos)
                ->whereNull('ventafactura.deleted')
                ->where('ventafactura.idventa', $id)
                ->first(); 
        
        return $row;
    }
    
}
