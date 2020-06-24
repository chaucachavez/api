<?php

namespace App\Models; 

class cicloatencion extends apimodel {

    protected $table = 'cicloatencion';
    protected $primaryKey = 'idcicloatencion';
    public $timestamps = false;
    protected $fillable = [
        'idempresa',
        'idsede',
        'idmedico',
        'idpaciente',
        'idestado',
        'fecha',
        'idcitamedica',
        'idcitamed',
        'idestadofactura',        
        'fechacierre',
        'fechatraslado',
        'identidadtraslado',
        'idsedetraslado', 
        'terminot',
        'primert',
        'ultimot', 
        'idpost',
        'cantidadlla',
        'cantidadllae',
        'ultimallae',
        'idpaquete',
        'idzona',
        'pdfs',
        'logenvios',
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];
    protected $hidden = ['idempresa']; 

    public function grid($param, $likename = '', $betweendate = '', $items = '', $orderName = '', $orderSort = '', $existsPresupuesto = false, $fields = [],  $notultimot = false, 
                        $notexistscicloopen = false, $notexistsultimat = false, $seguimiento = false, $ordenseguimiento = false, $whereInestadopago = [], $deuda = false, $whereInidpaciente = [], $betweenCreatedAt = [], $existePresupuesto = false, $NoexisteReservacita = false, $primertNull = false, $primertNotNull = false, $distrito = false, $NoexisteTarea = false) {
                                    
        if (empty($fields)) {
            $fields = ['cicloatencion.idcicloatencion', 'cicloatencion.fecha', 'cicloatencion.fechacierre', 'cicloatencion.idestado', 'cicloatencion.idsede', 'cicloatencion.primert', 'cicloatencion.ultimot',
                'cliente.identidad as idcliente', 'cliente.entidad as paciente', 'cliente.numerodoc', 'cliente.celular', 'cliente.telefono', 'cliente.sexo', 'cliente.imgperfil', 
                'cicloatencion.id_created_at', 'created.entidad as created', 'cicloatencion.terminot', 'cicloatencion.cantidadlla',  
                \DB::raw('DATEDIFF(CURDATE(), cicloatencion.ultimot) as diasinasistencia'), 'cicloatencion.ultimallae', \DB::raw('DATEDIFF(CURDATE(), cicloatencion.ultimallae) as diasultimallamada'), 
                'medico.entidad as medico', 'estadodocumento.nombre as estadociclo', 'sede.nombre as sedenombre', 'sede.direccion as direccionsede', 'historiaclinica.hc', 
                'presupuesto.idpresupuesto', 'presupuesto.total', 'presupuesto.montopago', 'presupuesto.montoefectuado', 'estadopagopre.nombre as estadopagopre', 'cicloatencion.idestadofactura', 'paquete.nombre as nombrepaquete', 'citamedica.fecha as fechacita', 'zona.nombre as nombrezona', 'cliente.email', 'cliente.sms_acm', 'cliente.sms_ite', 'cliente.sms_ate', 'cliente.sms_sat', 'cliente.fechanacimiento', \DB::raw('TIMESTAMPDIFF(YEAR, cliente.fechanacimiento, cicloatencion.fecha) as edaddiaciclo'), 'cicloatencion.logenvios', 'cicloatencion.pdfs'];
        }
        //\DB::enableQueryLog(); 
        $select = \DB::table('cicloatencion')
                ->join('sede', 'cicloatencion.idsede', '=', 'sede.idsede')
                ->leftJoin('entidad as medico', 'cicloatencion.idmedico', '=', 'medico.identidad')
                ->leftJoin('paquete', 'cicloatencion.idpaquete', '=', 'paquete.idpaquete')
                ->join('entidad as cliente', 'cicloatencion.idpaciente', '=', 'cliente.identidad')
                ->leftJoin('arbol as zona', 'cicloatencion.idzona', '=', 'zona.idarbol')
                ->leftJoin('documento', 'cliente.iddocumento', '=', 'documento.iddocumento')
                ->join('estadodocumento', 'cicloatencion.idestado', '=', 'estadodocumento.idestadodocumento')  
                ->join('entidad as created', 'cicloatencion.id_created_at', '=', 'created.identidad') 
                ->leftJoin('historiaclinica', function($leftJoin) { 
                    $leftJoin->on('cliente.identidad', '=', 'historiaclinica.idpaciente')
                         ->on('historiaclinica.idsede', '=', 'cicloatencion.idsede');
                })
                ->leftJoin('citamedica', 'cicloatencion.idcitamedica', '=', 'citamedica.idcitamedica');

        if ($distrito) {
            array_push($fields, 'ubigeo.nombre as distrito');            
            $select->leftJoin('ubigeo', 'cliente.idubigeo','=', 'ubigeo.idubigeo');
        }


        if ($seguimiento) {
            array_push($fields, 'createdseguim.entidad as createdsegui',  'post.idactividad', 'post.created_at as createdatsegui', 'post.tarea', 'post.mensaje', 'llamada.nombre as nombrellamada');
            
            $select->leftJoin('post', 'cicloatencion.idpost', '=', 'post.idpost');
            $select->leftJoin('entidad as createdseguim', 'post.id_created_at', '=', 'createdseguim.identidad');//join 
            $select->leftJoin('estadodocumento as llamada', 'post.idllamada', '=', 'llamada.idestadodocumento');  

            $select->whereNull('post.deleted'); 
        }

        if (!$existsPresupuesto) {
            $select->leftJoin('presupuesto', 'cicloatencion.idcicloatencion', '=', 'presupuesto.idcicloatencion')
                   ->leftJoin('estadodocumento as estadopagopre', 'presupuesto.idestadopago', '=', 'estadopagopre.idestadodocumento');
        }      

        if ($deuda) { 
            $select->whereRaw('presupuesto.montoefectuado > presupuesto.montopago');
        }

        if ($existsPresupuesto) {
            $select->join('presupuesto', 'cicloatencion.idcicloatencion', '=', 'presupuesto.idcicloatencion')
                   ->join('estadodocumento as estadopagopre', 'presupuesto.idestadopago', '=', 'estadopagopre.idestadodocumento');
        } 

        if (!empty($whereInestadopago)) {
            $select->whereIn('presupuesto.idestadopago', $whereInestadopago);
        }

        if (!empty($whereInidpaciente)) {
            $select->whereIn('cicloatencion.idpaciente', $whereInidpaciente);
        }

        if ($notultimot) {
            $select->whereNull('cicloatencion.ultimot');
        }

        if ($primertNull) {
            $select->whereNull('cicloatencion.primert');
        }

        if ($primertNotNull) {
            $select->whereNotNull('cicloatencion.primert');
        }

        if (!empty($likename)) { 
            $select->where('cliente.entidad', 'like', '%' . $likename . '%');
        }
 
        $select->select($fields)
                ->whereNull('cicloatencion.deleted')
                ->where($param);

        if ($notexistscicloopen) {
            
            $select->whereNotExists(function($query)
                {
                    $query->select(\DB::raw(1))
                        ->from('cicloatencion as t1') 
                        ->whereRaw('t1.idpaciente = cicloatencion.idpaciente and t1.idcicloatencion != cicloatencion.idcicloatencion')
                        ->whereNull('t1.deleted')
                        ->where('t1.idestado', 20); //Aperturado 
                });
        }

        if ($notexistsultimat) {
             
            $select->whereNotExists(function($query)
                {
                    $query->select(\DB::raw(1))
                        ->from('cicloatencion as t1')
                        ->whereRaw('t1.idpaciente = cicloatencion.idpaciente and 
                                    t1.idcicloatencion != cicloatencion.idcicloatencion and 
                                    t1.ultimot > cicloatencion.ultimot')
                        ->whereNull('t1.deleted');
                });
        }
        
        if ($existePresupuesto) {             
            $select->whereExists(function($query)
                {
                    //Falta "presupuestodet" porque puede registro en presupuesto pero no en presupuestodet
                    $query->select(\DB::raw(1)) 
                        ->from('presupuesto as pre')
                        ->join('presupuestodet as predet', 'pre.idpresupuesto', '=', 'predet.idpresupuesto')  
                        ->whereRaw('pre.idcicloatencion = cicloatencion.idcicloatencion and pre.idpresupuesto = predet.idpresupuesto')
                        ->whereNull('pre.deleted');
                });
        }

        if ($NoexisteReservacita) {             
            $select->whereNotExists(function($query)
                {
                    $query->select(\DB::raw(1))
                        ->from('citaterapeutica as ct')
                        ->whereRaw('ct.fecha >= cicloatencion.fecha and ct.idpaciente = cicloatencion.idpaciente')
                        ->whereNull('ct.deleted');
                });
        }

        if ($NoexisteTarea) {             
            $select->whereNotExists(function($query)
                {
                    $query->select(\DB::raw(1))
                        ->from('tarea')
                        ->whereRaw('tarea.idautomatizacion = 4 and tarea.idcicloatencion = cicloatencion.idcicloatencion')
                        ->whereNull('tarea.deleted');
                });
        }
        
        if (!empty($likename)) {
            $select->where('cliente.entidad', 'like', '%' . $likename . '%');
        }

        if (!empty($betweendate)) {
            $select->whereBetween('cicloatencion.fecha', $betweendate);
        }

        if (!empty($betweenCreatedAt)) {
            $select->whereBetween('cicloatencion.created_at', $betweenCreatedAt);
        } 

        $orderName = !empty($orderName) ? $orderName : 'cicloatencion.fecha';
        $orderSort = !empty($orderSort) ? $orderSort : 'desc';

        if($ordenseguimiento){
            $select->orderBy('presupuesto.idestadopago', 'asc')
                    ->orderBy('cicloatencion.cantidadlla', 'asc')
                    ->orderBy('cicloatencion.ultimallae', 'asc')   
                    ->orderBy('cicloatencion.ultimot', 'asc')   
                    ->orderBy('cicloatencion.fecha', 'desc');      
        }else {
            $select->orderBy($orderName, $orderSort)
                ->orderBy('cicloatencion.idcicloatencion', 'desc');
        }         

        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        }
        //dd(\DB::getQueryLog());
        
        foreach ($data as $row) {
            if (isset($row->fecha))
                $row->fecha = $this->formatFecha($row->fecha);
            if (isset($row->fechacierre))
                $row->fechacierre = $this->formatFecha($row->fechacierre);
            if (isset($row->fechacita))
                $row->fechacita = $this->formatFecha($row->fechacita);
            if (isset($row->ultimot))
                $row->ultimot = $this->formatFecha($row->ultimot);
            if (isset($row->primert))
                $row->primert = $this->formatFecha($row->primert);
            if (isset($row->ultimallae))
                $row->ultimallae = $this->formatFecha($row->ultimallae);             
            if (isset($row->createdatsegui)) {
                $row->createdtimeatsegui = substr($row->createdatsegui, 11, 8);
                $row->createdatsegui = $this->formatFecha(substr($row->createdatsegui, 0, 10)); 
            } 
            if (isset($row->fechanacimiento))
                $row->fechanacimiento = $this->formatFecha($row->fechanacimiento);
        } 
        
        return $data;
    }

    public function cicloatencion($id, $traslado = false) {

        $fields = ['cliente.identidad', 'cliente.numerodoc', 'cliente.entidad', 
                'documento.abreviatura as nombredocumento', 'historiaclinica.hc', 'cliente.celular', 
                'cliente.email', 'cliente.fechanacimiento', 'cliente.idubigeo', 'cicloatencion.idcicloatencion', 
                'cicloatencion.fecha', 'cicloatencion.idsede', 'estadodocumento.nombre as nombrestadociclo', 'cicloatencion.idestado',
                'medico.entidad as medico', 'sede.nombre as nombresede', 'sede.sedeabrev', 'cicloatencion.primert', 'cicloatencion.ultimot', 'cicloatencion.idestadofactura', 'paquete.nombre as nombrepaquete', 'arbol.nombre as nombrecategoria', 'arbol.color', 'citamedica.fecha as fechacita', 'cicloatencion.idcitamed', 'citamed.fecha as citfecha', 'citamed.inicio as cithora', 'citmedico.entidad as citmedico', 'zona.nombre as nombrezona', 'cicloatencion.idzona', 'cicloatencion.idpaquete', 'created.entidad as created', 'cliente.sms_acm', 'cliente.sms_ite', 'cliente.sms_ate', 'cliente.sms_sat', 'cliente.ocupacion', 'cliente.estadocivil', 'cliente.hijos', 'cicloatencion.pdfs', 'cicloatencion.logenvios'];

        //\DB::enableQueryLog();
        $select = \DB::table('cicloatencion')
                ->join('sede', 'cicloatencion.idsede', '=', 'sede.idsede')
                ->leftJoin('entidad as medico', 'cicloatencion.idmedico', '=', 'medico.identidad')
                ->leftJoin('paquete', 'cicloatencion.idpaquete', '=', 'paquete.idpaquete')
                ->leftJoin('arbol', 'paquete.idcategoria', '=', 'arbol.idarbol')
                ->join('entidad as cliente', 'cicloatencion.idpaciente', '=', 'cliente.identidad')
                ->join('estadodocumento', 'cicloatencion.idestado', '=', 'estadodocumento.idestadodocumento')
                ->join('entidad as created', 'cicloatencion.id_created_at', '=', 'created.identidad') 
                ->leftJoin('documento', 'cliente.iddocumento', '=', 'documento.iddocumento')
                ->leftJoin('historiaclinica', function($join) {
                    $join->on('cliente.identidad', '=', 'historiaclinica.idpaciente')
                    ->on('historiaclinica.idsede', '=', 'cicloatencion.idsede');
                }) 
                ->leftJoin('citamedica', 'cicloatencion.idcitamedica', '=', 'citamedica.idcitamedica')
                ->leftJoin('citamedica as citamed', 'cicloatencion.idcitamed', '=', 'citamed.idcitamedica')
                ->leftJoin('entidad as citmedico', 'citamed.idmedico', '=', 'citmedico.identidad')
                ->leftJoin('arbol as zona', 'cicloatencion.idzona', '=', 'zona.idarbol');

        if ($traslado) {

            array_push($fields, "traslado.sedeabrev as sedeorigen");

            $select->leftJoin('sede as traslado', 'cicloatencion.idsedetraslado', '=', 'traslado.idsede');
        }

        $select->select($fields)
                ->where('cicloatencion.idcicloatencion', $id);

        $data = $select->first();

        //dd(\DB::getQueryLog()); 
        $data->fecha = $this->formatFecha($data->fecha);
        
        if($data->primert)
            $data->primert = $this->formatFecha($data->primert);

        if($data->ultimot)
            $data->ultimot = $this->formatFecha($data->ultimot);

        if($data->citfecha)
            $data->citfecha = $this->formatFecha($data->citfecha);

        return $data;
    }

    public function cicloCitasmedicas($param = array(), $whereInCitaEstado = array(), $whereInCicloEstado = array(), $whereInCiclo = array(), $fields = []) {

        if (empty($fields)) {
            $fields = ['citamedica.idcitamedica', 'citamedica.fecha', 'citamedica.idmedico', 'citamedica.inicio', 'citamedica.idestado',
                    'entidad.entidad as medico', 'estadodocumento.nombre as estadocita', 'cicloatencion.idcicloatencion', 'sede.idsede',
                    'sede.nombre as sedenombre', 'cliente.identidad as idcliente', 'cliente.entidad', 'cliente.numerodoc', 'citamedica.presupuesto',
                    'citamedica.tipocm', 'citamedica.tipocmcomentario', 'citamedica.costocero', 'citamedica.motivo', 'citamedica.antecedente',
                    'citamedica.nota', 'citamedica.idordencompra', 'citamedica.idventa', 'citamedica.idestadopago'];
        }

        $select = \DB::table('cicloatencion')
                ->join('citamedica', 'cicloatencion.idcicloatencion', '=', 'citamedica.idcicloatencion')
                ->join('sede', 'citamedica.idsede', '=', 'sede.idsede')
                ->join('entidad', 'citamedica.idmedico', '=', 'entidad.identidad')
                ->join('entidad as cliente', 'citamedica.idpaciente', '=', 'cliente.identidad')
                ->join('estadodocumento', 'citamedica.idestado', '=', 'estadodocumento.idestadodocumento')
                ->select($fields)
                ->orderBy('citamedica.fecha', 'asc')
                ->whereNull('cicloatencion.deleted')
                ->whereNull('citamedica.deleted');

        if (!empty($param)) {
            $select->where($param);
        }

        if (!empty($whereInCitaEstado)) {
            $select->whereIn('citamedica.idestado', $whereInCitaEstado);
        }

        if (!empty($whereInCicloEstado)) {
            $select->whereIn('cicloatencion.idestado', $whereInCicloEstado);
        }

        if (!empty($whereInCiclo)) {
            $select->whereIn('cicloatencion.idcicloatencion', $whereInCiclo);
        }

        $data = $select->get()->all();

        foreach ($data as $row) {
            $row->fecha = $this->formatFecha($row->fecha);
        }

        return $data;
    }

    public function cicloAutorizaciones($param, $whereInCiclo = array(), $fields = []) {

        if (empty($fields)) {
             $fields = ['aseguradora.nombre as nombreaseguradora', 'aseguradoraplan.nombre as nombreaseguradoraplan', 'aseguradoraplan.abreviatura', 'coaseguro.nombre as nombrecoaseguro', 'producto.nombre as nombreproducto',
                    'cicloautorizacion.idcicloautorizacion', 'cicloautorizacion.idsede', 'cicloautorizacion.fecha', 'cicloautorizacion.deducible', 'cicloautorizacion.coaseguro', 'cicloautorizacion.principal',
                    'cicloautorizacion.idaseguradora', 'cicloautorizacion.idaseguradoraplan', 'cicloautorizacion.idcoaseguro', 'cicloautorizacion.idtipo', 'cicloautorizacion.codigo', 'cicloautorizacion.descripcion', 'cicloautorizacion.idpaciente',
                    'cicloatencion.idcicloatencion', 'cicloautorizacion.idproducto', 'titular.entidad as nombretitular',  'cicloautorizacion.parentesco', 'cicloautorizacion.nombrecompania', 'cicloautorizacion.idtitular', 'cicloautorizacion.hora', 'cicloautorizacion.numero', 'cicloautorizacion.idventa'];
        }

        $select = \DB::table('cicloatencion')
                ->join('cicloautorizacion', 'cicloatencion.idcicloatencion', '=', 'cicloautorizacion.idcicloatencion')
                ->join('aseguradora', 'cicloautorizacion.idaseguradora', '=', 'aseguradora.idaseguradora')
                ->leftJoin('aseguradoraplan', 'cicloautorizacion.idaseguradoraplan', '=', 'aseguradoraplan.idaseguradoraplan')
                ->leftJoin('coaseguro', 'cicloautorizacion.idcoaseguro', '=', 'coaseguro.idcoaseguro')
                ->leftJoin('producto', 'cicloautorizacion.idproducto', '=', 'producto.idproducto')
                ->leftJoin('entidad as titular', 'cicloautorizacion.idtitular', '=', 'titular.identidad') 
                ->select($fields)
                ->orderBy('cicloautorizacion.idcicloautorizacion', 'asc')
                ->whereNull('cicloatencion.deleted')
                ->whereNull('cicloautorizacion.deleted')
                ->where($param);

        if (!empty($whereInCiclo)) {
            $select->whereIn('cicloatencion.idcicloatencion', $whereInCiclo);
        }

        $data = $select->get()->all();

        foreach ($data as $row) {
            $row->fecha = $this->formatFecha($row->fecha);
        }

        return $data;
    } 

    public function cicloTratamientos($param) {
        $select = \DB::table('ciclotratamiento')                
                ->join('producto', 'ciclotratamiento.idproducto', '=', 'producto.idproducto')
                ->join('entidad as personal', 'ciclotratamiento.identidad', '=', 'personal.identidad') 
                ->select('ciclotratamiento.idciclotratamiento', 'producto.nombre as nombreproducto', 'personal.entidad as nombrepersonal', 'ciclotratamiento.created_at as createdat', 'ciclotratamiento.cantidad', 'ciclotratamiento.idcicloatencion', 'ciclotratamiento.idproducto', 'producto.idtipoproducto', 
                    'producto.valorventa', 'ciclotratamiento.idgrupodx'
                )
                ->orderBy('ciclotratamiento.idciclotratamiento', 'asc')
                ->whereNull('ciclotratamiento.deleted_at')
                // ->whereNull('producto.deleted')
                ->whereNull('personal.deleted')
                ->where($param); 

        $data = $select->get()->all();

        foreach ($data as $row) { 
            $row->createdat = $this->formatFecha(substr($row->createdat, 0, 10));
        }

        return $data;
    }

    public function validadorDataRelacionada($id) {

        $data = \DB::table('cicloautorizacion')->select('idcicloatencion')->where('idcicloatencion', $id)->whereNull('deleted')->get()->all();
        if (!empty($data)) {
            return ['validator' => true, 'message' => 'Tiene autorizaciones de seguro. No puede ser eliminado.'];
        } 

        $data = \DB::table('ciclomovimiento')->select('idcicloatencion')->where('idcicloatencion', $id)->whereNull('deleted')->get()->all();
        if (!empty($data)) {
            return ['validator' => true, 'message' => 'Tiene notas de saldo. No puede ser eliminado.'];
        }

        $data = \DB::table('ciclomovimiento')->select('idcicloatencionref')->where('idcicloatencionref', $id)->whereNull('deleted')->get()->all();
        if (!empty($data)) {
            return ['validator' => true, 'message' => 'Tiene notas de saldo. No puede ser eliminado.'];
        } 

        $data = \DB::table('citamedica')->select('idcicloatencion')->where('idcicloatencion', $id)->whereNull('deleted')->get()->all();
        if (!empty($data)) {
            return ['validator' => true, 'message' => 'Tiene citas médicas. No puede ser eliminado.'];
        } 

        $data = \DB::table('presupuesto')->select('idcicloatencion', 'montopago')->where('idcicloatencion', $id)->whereNull('deleted')->get()->all();
        if (!empty($data)) {
            if((float) $data[0]->montopago > 0)
                return ['validator' => true, 'message' => 'Tiene presupuesto pagado. No puede ser eliminado.'];
        }

        $data = \DB::table('venta')->select('idcicloatencion')->where('idcicloatencion', $id)->whereNull('deleted')->get()->all();
        if (!empty($data)) {
            return ['validator' => true, 'message' => 'Tiene ventas. No puede ser eliminado.'];
        }

        $data = \DB::table('terapiatratamiento')->select('idcicloatencion')->where('idcicloatencion', $id)->whereNull('deleted')->get()->all();
        if (!empty($data)) {
            return ['validator' => true, 'message' => 'Tiene terapias realizadas. No puede ser eliminado.'];
        }

        $data = \DB::table('terapia')->select('idcicloatencion')->where('idcicloatencion', $id)->whereNull('deleted')->get()->all();
        if (!empty($data)) {
            return ['validator' => true, 'message' => 'Tiene "terapia". No puede ser eliminado.'];
        }

        return ['validator' => false];
    }
}
