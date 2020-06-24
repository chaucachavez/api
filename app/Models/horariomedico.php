<?php

namespace App\Models; 

class horariomedico extends apimodel {

    protected $table = 'horariomedico';
    protected $primaryKey = 'idhorariomedico';
    public $timestamps = false;
    protected $fillable = [
        'idempresa',
        'idsede', 
        'idmedico',
        'fecha', 
        'inicio', 
        'fin',
        'tipo',
        'online'
    ];
    protected $hidden = ['idempresa']; 

    public function grid($param, $betweendate = '', $whereInMed = array(), $validarHora = '', $anomes = []) {
        $select = \DB::table('horariomedico')                
                ->leftJoin('sede', 'horariomedico.idsede', '=', 'sede.idsede')                
                ->join('entidad', 'horariomedico.idmedico', '=', 'entidad.identidad')
                ->join('entidadperfil', 'entidad.identidad', '=', 'entidadperfil.identidad')
                ->join('perfil', 'entidadperfil.idperfil', '=', 'perfil.idperfil') 
                ->select('horariomedico.idhorariomedico',  
                        'horariomedico.fecha', 'horariomedico.inicio', 'horariomedico.fin',  
                        'horariomedico.idmedico', 
                        'entidad.entidad', 'entidad.colorcss', 'entidad.maxcamilla',
                        'sede.idsede', 'sede.nombre as nombresede', 'sede.sedeabrev', 'perfil.idperfil', 'perfil.idsuperperfil', 'horariomedico.online')
                ->where($param);

        if (!empty($betweendate)) {
            $select->whereBetween('horariomedico.fecha', $betweendate);
        } 
            
        if (!empty($anomes)) {
            $select->whereRaw("YEAR(fecha) = ".$anomes[0]." AND month(fecha) = ".$anomes[1]);
        }

        if(!empty($validarHora)) {
            $select->where('inicio', '<=',$validarHora) 
                   ->where('fin', '>=',$validarHora);
        }        
        
        if (!empty($whereInMed)) {
            $select->whereIn('horariomedico.idmedico', $whereInMed);
        }
        
        
                
        $data = $select 
                ->orderBy('horariomedico.idmedico', 'ASC')
                ->orderBy('horariomedico.fecha', 'ASC')
                ->orderBy('horariomedico.inicio', 'ASC')
                ->get()->all();

        foreach ($data as $row) {
            $row->fecha = $this->formatFecha($row->fecha);
        }

        return $data;
    }
    
    public function medicosPorHorario($param, $horaInicio = '', $horaFin = '', $restar = FALSE) {
        //\DB::enableQueryLog(); 
        $select = \DB::table('horariomedico')                 
                ->join('entidad', 'horariomedico.idmedico', '=', 'entidad.identidad')
                ->join('entidadperfil', 'entidad.identidad', '=', 'entidadperfil.identidad')
                ->join('perfil', 'entidadperfil.idperfil', '=', 'perfil.idperfil')
                ->select('horariomedico.idmedico', 'entidad.entidad', 'entidad.maxcamilla', 'entidad.breakinicio', 'entidad.breakfin')
                ->where($param);
        
        if(!empty($horaInicio)){
            $select->where('inicio', '<=',$horaInicio);
        }                
        
        if($restar){
            //Solo para terapistas, los terapistas la ultima media hora no reciben pacientes, solo aplican terapias.
            $select->whereRaw("SUBTIME(fin,'00:30') >= '$horaFin'");
        }else{
            if(!empty($horaInicio)){
                $select->where('fin', '>=',$horaFin);    
            }
        }
        
        //distinct: Para medicos que tienen doble turno en un mismo dia y no se especifica inicio y fin como filtro.
        
        $data = $select
                ->orderBy('entidad.entidad', 'ASC')
                ->distinct()->get()->all();
         
        //dd(\DB::getQueryLog());     
        //dd($data);
        return $data;        
    }
    
    public function eliminarBloqueHorario($param, $ano, $mes, $diasemana, $horainicio, $horafin) {
        
        \DB::table('horariomedico')
                ->where($param) 
                ->whereRaw("YEAR(fecha) = $ano AND month(fecha) = $mes AND dayofweek(fecha) = $diasemana" )
                ->where('inicio', $horainicio)
                ->where('fin', $horafin)
                ->delete(); 
    }
    
    public function listaBloqueHorario($param, $ano, $mes) {
        $data = \DB::table('horariomedico')                
                ->leftJoin('sede', 'horariomedico.idsede', '=', 'sede.idsede')
                ->select('horariomedico.idsede', 'sede.sedeabrev', 'horariomedico.idmedico', 'horariomedico.fecha',
                        'horariomedico.inicio', 'horariomedico.fin')
                ->where($param)
                ->whereRaw("YEAR(fecha) = $ano AND month(fecha) = $mes" )                
                ->get()->all();
        
        return $data;
    }
    
    public function listaBloqueHorarioRango($param, $horaInicio, $horaFin, $tipo = '1') {
        //$tipo:1 "x Dia(dd/mm/Y)" 2: "x diasemana(L,...D) de un Mes"
        
        //\DB::enableQueryLog(); 
        //$param = ['fecha' => '2016-03-02'];
        //$betweendate = ['07:15:00', '08:30:00']; 
        $select = \DB::table('horariomedico')
                  ->join('entidad', 'horariomedico.idmedico', '=', 'entidad.identidad')
                  ->leftJoin('entidadperfil', 'entidad.identidad', '=', 'entidadperfil.identidad')
                  ->leftJoin('perfil', 'entidadperfil.idperfil', '=', 'perfil.idperfil') 
                  ->select('horariomedico.idmedico', 'horariomedico.idsede', 'horariomedico.fecha','horariomedico.inicio','horariomedico.fin', 'perfil.idsuperperfil', 'perfil.nombre', 'entidad.entidad');

            if($tipo === '1'){
                $select->where($param);
            }else{
                $param = explode('|', $param);
                //$select->where('tipo', $param[3]); //YA NO EXISTIRA TIPO, ahora es todo el personal
                $select->whereRaw("YEAR(fecha) = $param[0] AND month(fecha) = $param[1] AND dayofweek(fecha) = $param[2]");
            }
            
            $select->where(function($query) use ($horaInicio, $horaFin) {
                $query->where('inicio', '>=',$horaInicio) 
                      ->where('inicio', '<=',$horaFin)                            
                      ->orWhere(function($query2) use ($horaInicio, $horaFin) {
                            $query2->where('fin', '>=',$horaInicio) 
                                   ->where('fin', '<=',$horaFin);
                      })
                      ->orWhere(function($query3) use ($horaInicio, $horaFin) {
                                $query3->where('inicio', '<',$horaInicio) 
                                       ->where('fin', '>',$horaFin);
                      });
            });
                    
        $data = $select->get()->all();
        //dd(\DB::getQueryLog());    
        return $data; 
    }
}
