<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class sede extends apimodel {

    protected $table = 'sede';
    protected $primaryKey = 'idsede';
    public $timestamps = false;
    protected $fillable = [
        'idsede',
        'idempresa', 
        'nombre',
        'sedeabrev',
        'direccion',
        'telefono',
        'celular',
        'principal',
        'comercial',
        'xmap',
        'ymap',
        'estado',
        'idafiliado',
        'iddocumentofiscal',
        'serie'
    ];
    protected $hidden = ['idempresa'];
    //1 Mi = 155
    //2 Ch = 135
    //3 BE = 133
    //4 Ol = 117
    //----------
    //       540
    public function sedes($id, $fields = []) {
        if(empty($fields)){
            $fields = ['sede.idsede', 'sede.nombre', 'sede.sedeabrev', 'sede.direccion', 'sede.telefono', 'sede.celular', 'sede.principal', 'sede.comercial'];
        }
        $data = \DB::table('sede')
                ->join('empresa', 'sede.idempresa', '=', 'empresa.idempresa') 
                ->select($fields)
                ->where('sede.idempresa', '=', $id)
                // ->where('sede.idsede', '=', 9)
                ->orderBy('sede.nombre', 'asc')
                ->get()->all();

        return $data;
    }
    
    public function autorizadas($param, $sedes = [], $fields = []) {
        $campos = ['sede.idsede', 'sede.nombre'];
        
        if (!empty($fields))
            $campos = $fields;
        
        $select = \DB::table('sede')
                ->join('entidadsede', 'sede.idsede', '=', 'entidadsede.idsede') 
                ->select($campos)
                ->where($param);
        
        if (!empty($sedes))
            $select->whereIn('sede.idsede', $sedes);        
        
        $data = $select->orderBy('sede.nombre', 'asc')
                ->get()->all();

        return $data;
    }

    public function updateSede($data, $where) {
        \DB::table('sede')->where($where)->update($data);
    }

    public function GrabarDiaferiado($data, $idempresa) {
        \DB::table('diaferiado')->where('idempresa', $idempresa)->delete();
        \DB::table('diaferiado')->insert($data);
    }
    
    public function GrabarDiaxhora($data, $idempresa) {
        \DB::table('diaxhora')->where('idempresa', $idempresa)->delete();
        \DB::table('diaxhora')->insert($data);
    } 
    
    public function GrabarIps($data, $idsede) {
        \DB::table('ip')->where('idsede', $idsede)->delete();
        \DB::table('ip')->insert($data);
    }     

    public function GrabarTurnoterapia($data, $idsede) {
        \DB::table('turnoterapia')->where('idsede', $idsede)->delete();
        \DB::table('turnoterapia')->insert($data);
    } 

    public function gridAperturas($param, $items = '', $orderName = '', $orderSort = '', $betweendate = []) { 
        $select = \DB::table('sede')  
                ->join('apertura', 'sede.idsede', '=', 'apertura.idsede')
                ->join('entidad as entidadapertura', 'apertura.identidadapertura', '=', 'entidadapertura.identidad')
                ->leftJoin('entidad as entidadcierre', 'apertura.identidadcierre', '=', 'entidadcierre.identidad')                
                ->leftJoin('moneda', 'apertura.idmoneda', '=', 'moneda.idmoneda')
                ->select('apertura.idapertura', 'apertura.estado', 'apertura.fechaapertura', 'apertura.horaapertura', 
                         'apertura.fechacierre', 'apertura.horacierre', 'apertura.idmoneda', 'moneda.simbolo', 'apertura.saldoinicial', 
                         'apertura.totalefectivo','apertura.totaltarjeta', 'apertura.tcdolar', 'apertura.totalsoles', 
                         'apertura.totaldolares', 'sede.nombre as nombresede', 'apertura.visanetlote', 'apertura.mastercadlote', 'sede.idsede',
                         'apertura.cajafinal', 'apertura.faltantesobrante', 'entidadcierre.entidad as personalcierre', 'entidadapertura.entidad as personalapertura', 'apertura.totalvisa', 'apertura.totalmastercard')
 
                ->whereNull('apertura.deleted')
                ->where($param);

        if (!empty($betweendate)) {
            $select->whereBetween('apertura.fechaapertura', $betweendate);
        }

        $orderName = !empty($orderName) ? $orderName : 'apertura.fechaapertura';
        $orderSort = !empty($orderSort) ? $orderSort : 'desc';

        $select->orderBy($orderName, $orderSort);
        
        if (!empty($items)) {
            $data = $select->paginate($items);
        } else {
            $data = $select->get()->all();
        }
         

        foreach ($data as $row) { 
            if($row->fechaapertura){
                
                $row->fechaapertura = $this->formatFecha($row->fechaapertura);
            } 
            if($row->fechacierre){
                $row->fechacierre = $this->formatFecha($row->fechacierre);
            } 
        }

        return $data;
    }

    public function GrabarCajero($data, $idsede) {
        \DB::table('cajero')->where('idsede', $idsede)->delete();
        \DB::table('cajero')->insert($data);
    }

    public function documentoSeries($param, $whereIn = array(), $orderName = 'documentoserie.iddocumentofiscal', $orderSort = 'asc') {
        $select = \DB::table('documentoserie')                
                ->join('entidad', 'documentoserie.identidad', '=', 'entidad.identidad')
                ->join('documentofiscal', 'documentoserie.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal')
                ->leftJoin('sede', 'sede.idsede', '=', 'documentoserie.idsede') 
                ->select('entidad.entidad', 'entidad.acronimo', 'entidad.numerodoc', 'documentofiscal.nombre as nombredocumento', 'documentoserie.identidad', 
                         'documentoserie.iddocumentofiscal', 'documentoserie.iddocumentoserie', 'documentoserie.serie', 'documentoserie.numero',   
                         'documentoserie.montomax', 'documentoserie.uso', 'documentoserie.seesunat', 'documentoserie.sucursalsunat', 'documentoserie.idsede', 'documentoserie.id_updated_at', 'documentoserie.updated_at', 'documentoserie.deleted', 'documentoserie.numeroeditable', 'documentoserie.iddocumentofiscalref')
                ->orderBy($orderName, $orderSort) 
                ->whereNull('documentoserie.deleted')
                ->where($param);

        if (!empty($whereIn)) {
            $select->whereIn('sede.idsede', $whereIn);
        }

        $data = $select->get()->all();

        return $data;
    }

    public function cajaCajeras($param, $whereIn = array()) {
        $select = \DB::table('sede')
                ->join('cajero', 'sede.idsede', '=', 'cajero.idsede')
                ->join('entidad', 'cajero.identidad', '=', 'entidad.identidad')
                ->select('sede.idsede',  'entidad.identidad', 'entidad.entidad', 'cajero.activo')
                ->orderBy('entidad.entidad', 'asc') 
                ->where($param);

        if (!empty($whereIn)) {
            $select->whereIn('sede.idsede', $whereIn);
        }

        $data = $select->get()->all();

        return $data;
    }

    public function apertura($param) {

        $row = \DB::table('sede') 
                ->join('apertura', 'sede.idsede', '=', 'apertura.idsede')
                ->join('entidad as entidadapertura', 'apertura.identidadapertura', '=', 'entidadapertura.identidad')
                ->leftJoin('entidad as entidadcierre', 'apertura.identidadcierre', '=', 'entidadcierre.identidad')
                ->leftJoin('moneda', 'apertura.idmoneda', '=', 'moneda.idmoneda')
                ->select('sede.idempresa', 'apertura.idapertura', 'apertura.estado', 'apertura.fechaapertura', 
                        'apertura.horaapertura', 'apertura.fechacierre', 'apertura.horacierre', 'apertura.idmoneda', 'moneda.simbolo', 
                        'apertura.saldoinicial', 'apertura.totalefectivo', 'apertura.totaltarjeta',  'sede.idsede', 
                        'sede.nombre as nombresede', 'apertura.tcdolar', 'apertura.visanetlote', 'apertura.mastercadlote', 'apertura.totalsoles', 'apertura.totaldolares',
                        'entidadapertura.entidad as personalapertura', 'entidadcierre.entidad as personalcierre', 'apertura.cajafinal', 'apertura.totalvisa', 'apertura.totalmastercard') 
                ->whereNull('apertura.deleted')
                ->where($param)
                ->first();
         
        if ($row) {
            $row->fechaapertura = $this->formatFecha($row->fechaapertura);
            $row->fechacierre = $this->formatFecha($row->fechacierre);
        }

        return $row;
    }

    public function cajasPorAbrir($param) { 
        $data = \DB::table('sede') 
                ->select('sede.idsede', 'sede.nombre')
                ->where($param) 
                ->get()->all();

        return $data;
    }

    public function cajasAbiertas($param) { 
        $select = \DB::table('apertura')
                ->select('apertura.idapertura', 'apertura.idsede')
                ->whereNull('apertura.deleted')
                ->where('apertura.estado', '1')
                ->where($param); 

        $data = $select->get()->all();

        return $data;
    } 

    public function documentosMontomaximos($param, $whereInIdsede, $whereInIdentidad, $whereInIddocu, $whereInSerie) {
        $data = \DB::table('documentoserie')                 
                ->join('documentofiscal', 'documentoserie.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal') 
                ->select('documentoserie.*') 
                ->where($param) 
                ->whereIn('documentoserie.idsede', $whereInIdsede)
                ->whereIn('documentoserie.identidad', $whereInIdentidad)
                ->whereIn('documentoserie.iddocumentofiscal', $whereInIddocu)
                ->whereIn('documentoserie.serie', $whereInSerie) 
                ->whereNull('documentoserie.deleted') 
                ->get()->all();

        return $data;
    }

    public function documentoserie($id) {
        $data = \DB::table('documentoserie')                
                ->join('entidad', 'documentoserie.identidad', '=', 'entidad.identidad')
                ->join('documentofiscal', 'documentoserie.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal')
                ->select('documentoserie.iddocumentoserie', 'documentoserie.serie', 'documentoserie.numero')
                ->where('documentoserie.iddocumentoserie', $id)
                ->whereNull('documentoserie.deleted') 
                ->first(); 

        return $data;
    }

}
