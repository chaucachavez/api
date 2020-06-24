<?php

namespace App\Models; 

class empresa extends apimodel {

    public $table = 'empresa';
    public $primaryKey = 'idempresa';
    public $timestamps = true;
    public $fillable = [
        'url',
        'ruc',
        'nombre',
        'razonsocial',
        'direccion',
        'idubigeo',
        'actividadinicio',
        'telefono',
        'celular',
        'email',
        'idtema',
        'imglogologin',
        'imglogosistema',
        'igv',
        'facebook',
        'twitter',
        'paginaweb',
        'whatsapp',
        'lunes',
        'martes',
        'miercoles',
        'jueves',
        'viernes',
        'sabado',
        'domingo',
        'laborinicio',
        'laborfin',
        'alertamontomin',
        'montomin',
        'headercolor', 
        'primarycolor', 
        'navigationcolor', 
        'fondocolor',
        'codeconsultamedica',
        'codecargo',
        'codeordencompra',
        'codeaguja',
        'testeo',
//        'recesoinicio',
//        'recesofin',
//        'tiempoconsultamedica',
//        'tiempoterapia'
        'ctadetraccion',
        'imgcpe',
        'preciounitario',
        'tipocambio',
        'tipocambiovalor',  
        'tipocalculo',  
        'mediopago',
        'recargoconsumo',  
        'recargoconsumovalor',  
        'productoselva', 
        'servicioselva'        
    ];
    
    protected $hidden = ['idempresa', 'created_at', 'updated_at'];
 
    function __construct($enterprise = null) {
        switch ($enterprise) {
            case 'osi':
                $this->idempresa = 1; 
                break;
            case 'nativa':
                $this->idempresa = 2; 
                break; 
       }
    }

    public function automatizaciones($param) {
        $data = \DB::table('automatizacion')
                ->select('automatizacion.*')
                ->where($param)
                ->whereNull('automatizacion.deleted')
                ->get()->all();

        return $data;
    }

    public function respuestas($param) {
        $data = \DB::table('respuesta')
                ->select('respuesta.*')
                ->where($param)
                ->whereNull('respuesta.deleted')
                ->get()->all();

        return $data;
    }

    public function empresa($param, $fields = []) {
        
        if(empty($fields)) {
            $fields = ['empresa.idempresa', 'empresa.razonsocial', 'empresa.url', 
            'empresa.direccion', 'empresa.imglogologin', 'empresa.imglogosistema', 'empresa.imgfondo', 'empresa.paginaweb', 'empresa.telefono',
            'headercolor', 'primarycolor', 'navigationcolor', 'fondocolor', 'codeconsultamedica', 'codecargo', 'codeordencompra'];
        }

        $data = \DB::table('empresa') 
                ->select($fields)
                ->where($param)
                ->first();

        return $data;
    }

    public function paises($where = NULL) {
        $select = \DB::table('ubigeo')
                ->select('pais', 'nombre', 'nacionalidad')
                ->where('dpto', '=', '000')
                ->where('prov', '=', '00')
                ->where('dist', '=', '00');
        if (!empty($where)) {
            $select->where($where);
        }
        $data = $select
                ->orderBy('nombre', 'asc')
                ->get()->all();
        return $data;
    }

    public function departamentos($pais, $where = NULL) {
        $select = \DB::table('ubigeo')
                ->select('dpto', 'nombre')
                ->where('pais', '=', $pais)
                ->where('dpto', '!=', '000')
                ->where('prov', '=', '00')
                ->where('dist', '=', '00');
        if (!empty($where)) {
            $select->where($where);
        }
        $data = $select
                ->orderBy('nombre', 'asc')
                ->get()->all();

        return $data;
    }

    public function provincias($pais, $departamento, $where = NULL) {
        $select = \DB::table('ubigeo')
                ->select('prov', 'nombre')
                ->where('pais', '=', $pais)
                ->where('dpto', '=', $departamento)
                ->where('prov', '!=', '00')
                ->where('dist', '=', '00');
        if (!empty($where)) {
            $select->where($where);
        }
        $data = $select
                ->orderBy('nombre', 'asc')
                ->get()->all();
        return $data;
    }

    public function distritos($pais, $departamento, $provincia, $where = NULL) {
        $select = \DB::table('ubigeo')
                ->select('dist', 'nombre')
                ->where('pais', '=', $pais)
                ->where('dpto', '=', $departamento)
                ->where('prov', '=', $provincia)
                ->where('dist', '!=', '00');
        if (!empty($where)) {
            $select->where($where);
        }
        $data = $select
                ->orderBy('nombre', 'asc')
                ->get()->all();
        return $data;
    }

    public function idempresa($url) {
//        $data = \DB::table('empresa')
//                ->select('empresa.idempresa')
//                ->where('url', '=', $url)
//                ->first();

        $idempresa = null;
        switch ($url) {
            case 'osi':
                $idempresa = 1;
                break;
            case 'nativa':
                $idempresa = 2;
                break; 
        }

        return $idempresa; //$data->idempresa;
    }

    public function documentos() {
        $data = \DB::table('documento')
                ->select('documento.*')
                ->get()->all();
        return $data;
    }

    public function perfiles($id) {
        $data = \DB::table('perfil')
                ->select('perfil.idperfil', 'perfil.nombre')
                ->where('idempresa', '=', $id)
                ->get()->all();
        return $data;
    }

    public function monedas($id) {
        $data = \DB::table('moneda')
                ->select('moneda.idmoneda', 'moneda.nombre', 'simbolo')
                ->where('idempresa', '=', $id)
                ->get()->all();
        return $data;
    }

    public function unidadmedidas($id) {
        $data = \DB::table('unidadmedida')
                ->select('unidadmedida.idunidadmedida', 'unidadmedida.nombre')
                ->where('idempresa', '=', $id)
                ->get()->all();
        return $data;
    }
  

    public function anos($id) {
        $data = \DB::table('ano')
                ->select('ano.ano', 'ano.activo')
                ->where('idempresa', '=', $id)
                ->get()->all();
        return $data;
    }

    public function estadodocumentos($tipo) {
        $data = \DB::table('estadodocumento')
                ->select('estadodocumento.*')
                ->where('estadodocumento.tipo', '=', $tipo)
                ->where('estadodocumento.activo', '=', '1')  
                ->orderBy('estadodocumento.nombre', 'asc')  
                ->get()->all();

        return $data;
    }



    public function serienumero($where) {
        $row = \DB::table('serie')
                ->select('serie.serienumero')
                ->where($where)
                ->first();

        return $row->serienumero;
    }

    public function updateSerieNumero($where) {
        $row = \DB::table('serie')
                ->select('serie.idserie', 'serie.serienumero')
                ->where($where)
                ->first();

        $serienumero = (int) $row->serienumero + 1;
        \DB::table('serie')->where('idserie', $row->idserie)->update(['serienumero' => $serienumero]);
    } 

    public function horas($start, $end, $mmrango, $mminicio, $mmreinicio = '') {
        //$rango: minutos de aumento
        //$mminicio: minuto de inicio

        // \DB::enableQueryLog();  

        $tmp = \DB::table('hora')
                ->select('hora.*')
                ->where('hora.idhora', '>=', $start)
                ->where('hora.idhora', '<=', $end)
                ->get()->all();

        // dd(\DB::getQueryLog());
        // dd($tmp);
        $data = [];
        $m = $mminicio; //minuto inicial: 0 u 14; | 30 o 45
        //19
        foreach ($tmp as $row) {
            
            if ((int) explode(':', $row->idhora)[1] === $m) { // 19, 39, 59  
                $data[] = $row;
                $m = $m + $mmrango;
                // 39 = 19 + 20
                // 59 = 39 + 20
                // 59 = 59 + 20
                if ($m > 59) {
                    if(empty($mmreinicio) && $mmreinicio !== 0) {
                        $m = $mminicio; 
                        // 19
                    }else{ 
                        $m = $mmreinicio;
                    }
                }
            }
        }
        
        return $data;
    }

    public function aseguradoras($idempresa) {

        $data = \DB::table('aseguradora')                
                ->select('idaseguradora', 'nombre', 'nroagenda')
                ->where('aseguradora.idempresa', $idempresa)
                ->orderBy('aseguradora.nombre', 'asc')
                ->get()->all();

        return $data;
    }

    public function referenciasmedicas($idempresa) {
        $data = \DB::table('referenciacita')                
                ->select('idreferenciacita', 'nombre')
                ->where('referenciacita.idempresa', $idempresa)
                ->orderBy('referenciacita.nombre', 'asc')
                ->get()->all();

        return $data;
    }

    public function aseguradorasplanes($idempresa, $cubierto = false, $cm = false) {

        $select = \DB::table('aseguradoraplan')
                ->select('aseguradoraplan.idaseguradoraplan', 'aseguradoraplan.idaseguradora', 'aseguradoraplan.nombre', 'aseguradoraplan.cubierto', 'aseguradoraplan.idcliente', 'cliente.email')
                ->join('aseguradora', 'aseguradoraplan.idaseguradora', '=', 'aseguradora.idaseguradora')
                ->join('entidad as cliente', 'aseguradoraplan.idcliente', '=', 'cliente.identidad')
                ->where('aseguradora.idempresa', $idempresa);

                if ($cubierto) {
                    $select->where('aseguradoraplan.cubierto', '1');
                }

                if ($cm) {
                    $select->where('aseguradoraplan.reservacita', '1');
                }
        
        $data = $select->orderBy('aseguradoraplan.nombre', 'asc')
                ->get()->all();

        return $data;
    }

    public function coaseguros() {
        $data = \DB::table('coaseguro')
                ->select('idcoaseguro', 'nombre', 'valor')
                //->orderBy('coaseguro.nombre', 'asc')
                ->get()->all();

        return $data;
    }

    public function sedehorarios($idsede, $fields = []) {

        if (empty($fields)) {
            $fields = 'sedehorario.*';
        }

        $data = \DB::table('sedehorario')
                ->select($fields)
                ->where('sedehorario.idsede', $idsede)
                ->first();

        return $data;
    }

    public function motivosexcepciones($idempresa) {
 
        $data = \DB::table('excepcion') 
                ->where('excepcion.idempresa', $idempresa)
                ->orderBy('excepcion.nombre', 'asc')
                ->get()->all();

        return $data;
    }

    public function listaSedeshorarios($idempresa) {
        $data = \DB::table('sedehorario')
                ->select('sedehorario.*')
                ->where('sedehorario.idempresa', $idempresa)
                ->get()->all();

        return $data;
    }

    public function listaGrupotimbrado($idempresa) {
        $data = \DB::table('grupotimbrado')
                ->where('grupotimbrado.idempresa', $idempresa)
                ->orderBy('grupotimbrado.nombre', 'asc')
                ->get()->all();

        return $data;
    }

    public function semanasAno($ano, $W = '') {
        //$W= '': NO implementado 
        
        //date('W', mktime(0, 0, 0, 12, 31, $ano)) //Número de la semana del año ISO-8601
        
        $numerosemana = date('W', mktime(0, 0, 0, 12, 31, $ano));
        if($numerosemana ==  1){
            $numerosemana = date('W', strtotime($ano.'-12-31 -7 day'));
        }
        
        for ($semana = 1; $semana <= $numerosemana; $semana++) {                                
            $fecha_lunes = date('Y-m-d', strtotime($ano . 'W' . str_pad($semana , 2, '0', STR_PAD_LEFT)));
            $data[] = array(
                'year' => $ano,
                'week' =>  $semana,
                'inicio' => $this->formatFecha($fecha_lunes),  
                'fin' => date('d/m/Y', strtotime($fecha_lunes.' 6 day'))
            ); 
        } 
        
        
        if(!empty($W)){ 
            $fila = [];
            foreach($data as $row){
                if($row['week'] === (int)$W){
                    $fila = $row;
                    break;
                }
            }
            $data = $fila;
        }
        return $data;
        
    }

    public function diasferiados($param, $mayor = false) {

        $select = \DB::table('diaferiado')
                ->select('diaferiado.fecha')
                ->where($param);

        if ($mayor) {
            $select->where('diaferiado.fecha', '>=', date('Y-m-d'));
        }

        $data = $select->orderBy('diaferiado.fecha', 'asc')
                       ->get()->all();

        foreach ($data as $row) {
            $row->fecha = $this->formatFecha($row->fecha);
        }
 
        return $data;
    }

    public function documentosfiscales($tipo) {
        $data = \DB::table('documentofiscal')
                ->select('documentofiscal.iddocumentofiscal', 'documentofiscal.nombre')
                ->where('tipo', $tipo)
                ->orderBy('documentofiscal.nombre', 'asc')
                ->get()->all();

        return $data;
    } 

    public function diasporhoras($param) {
        $data = \DB::table('diaxhora')
                ->join('sede', 'diaxhora.idsede', '=', 'sede.idsede') 
                ->select('diaxhora.fecha', 'diaxhora.idsede', 'diaxhora.inicio', 'diaxhora.fin', 'sede.nombre as nombresede')
                ->where($param)
                ->orderBy('diaxhora.fecha', 'asc')
                ->get()->all();

        foreach ($data as $row) {
            $row->fecha = $this->formatFecha($row->fecha);
        }

        return $data;
    }

    public function turnosterapeuticas($param) {

        $data = \DB::table('turnoterapia')
                ->select('turnoterapia.idsede', 'turnoterapia.dia', 'turnoterapia.inicio', 'turnoterapia.fin')
                ->where($param)
                ->orderBy('turnoterapia.dia', 'asc')
                ->orderBy('turnoterapia.inicio', 'asc')
                ->get()->all();

        return $data;
    }

    public function camillas($param) {
        $data = \DB::table('camilla')
                ->select('camilla.idcamilla', 'camilla.nombre', 'camilla.activo', 'camilla.idsede')
                ->whereNull('camilla.deleted')
                ->where($param)
                ->orderBy('camilla.nombre', 'asc')
                ->get()->all();

        return $data;
    } 

    public function ips($param) {
        $data = \DB::table('ip')
                ->select('ip.idip', 'ip.nombre', 'ip.idsede')
                ->where($param)
                ->get()->all();

        return $data;
    }
    
    public function mediopagos() {
        $data = \DB::table('mediopago')
                ->select('idmediopago', 'nombre')
                ->orderBy('mediopago.nombre', 'asc')
                ->get()->all();

        return $data;
    }
    
    public function anexos($param) {
        $data = \DB::table('anexo')
                ->select('idanexo', 'clave', 'nombre', 'activo')  
                ->where($param)
                ->orderBy('anexo.clave', 'asc') 
                ->get()->all();

        return $data;
    }
    
    public function configcallcenter($param) {
        $data = \DB::table('callcenter')
                ->select('idcallcenter', 'inicio', 'fin')            
                ->where($param)
                ->first();

        return $data;
    }
    
    public function callcenter($param) {
 
        $data = \DB::table('callcenter') 
                ->where($param)
                ->first();

        return $data;
    }    
}
