<?php

namespace App\Http\Controllers;

use Excel;
use App\Models\sede;
use \Firebase\JWT\JWT;
use App\Models\empresa;
use App\Models\entidad;
use App\Models\citamedica;
use App\Exports\DataExport;
use Illuminate\Http\Request;
use App\Models\horariomedico;
use App\Models\citaterapeutica;

class horariomedicoController extends Controller {

    public function __construct(Request $request) {
        $this->getToken($request);
    }  

    public function construct(Request $request, $enterprise) {
        /* Obtiene solo las sedes a las que tiene acceso el usuario.
         * Obtiene dias laborales, hora inicio de labores, hora inicio de refrigerio por sede. 
         * Obtiene los dias feriados y lo dias laborables por horas.
         */

        $empresa = empresa::select('idempresa', 'laborinicio', 'laborfin', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo')
                            ->where('url', '=', $enterprise)->first();
        
        $idempresa = $empresa->idempresa($enterprise);

        $data = array(
            'diasferiados' => $empresa->diasferiados(['idempresa' => $idempresa]) 
        );

        unset($empresa->idempresa);

        return $this->crearRespuesta($data, 200, '', '', $empresa);
    }

    public function index(Request $request, $enterprise) {
        /* Obtiene el horario laboral, del medico o fisioterapista, para una sede.
         * Param "tipo" 1:Medico 2:Fisioterapista     
         * Return Array     
         */
        $paramsTMP = $request->all();

        $empresa = new empresa();
        $horariomedico = new horariomedico();

        $param = array();
        $param['horariomedico.idempresa'] = $empresa->idempresa($enterprise);

        if (isset($paramsTMP['idsede'])) {
            $param['horariomedico.idsede'] = $paramsTMP['idsede'];
        }

        if (isset($paramsTMP['idmedico'])) {
            $param['horariomedico.idmedico'] = $paramsTMP['idmedico'];
        }

        $between = array();
        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta'])) {
            if (!empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {
                $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
                $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
                $between = [$paramsTMP['desde'], $paramsTMP['hasta']];
            }
        }
         
        $datahorario = $horariomedico->grid($param, $between);

        if (isset($paramsTMP['formato']) && !empty($paramsTMP['formato'])) {
            if(in_array($paramsTMP['formato'], ['xls', 'xlsx'])){

                $data = [];
                foreach($datahorario as $row){                  
                    $data[] = (array) $row;  
                }

                return Excel::download(new DataExport($data), 'Reporte_' . date('Y-m-d H:i:s') . '.xlsx');
            }
        } else {
            return $this->crearRespuesta($datahorario, 200);
        }
    }

    private function diasHabilesdeSemana($row) {
        /* Retorna una matriz con los dias no laborables. 
         * Los dias no laborables son configurado en tabla empresa.
         */
        $hiddenDays = [];

        if ($row->domingo === '0')
            array_push($hiddenDays, 7);
        if ($row->lunes === '0')
            array_push($hiddenDays, 1);
        if ($row->martes === '0')
            array_push($hiddenDays, 2);
        if ($row->miercoles === '0')
            array_push($hiddenDays, 3);
        if ($row->jueves === '0')
            array_push($hiddenDays, 4);
        if ($row->viernes === '0')
            array_push($hiddenDays, 5);
        if ($row->sabado === '0')
            array_push($hiddenDays, 6);

        return $hiddenDays;
    }

    public function bloquehorarios(Request $request, $enterprise) {

        $horariomedico = new horariomedico();

        $empresa = empresa::select('idempresa', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo')
                    ->where('url', '=', $enterprise)->first();

        $idempresa = $empresa->idempresa($enterprise);
        unset($empresa->idempresa);

        $paramsTMP = $request->all(); 
        $hiddenDays = $this->diasHabilesdeSemana($empresa);

        $param = array(
            'horariomedico.idempresa' => $idempresa,
            'horariomedico.idmedico' => $paramsTMP['idmedico']
        );
        
        $Y = date('Y');  
        if((int)$paramsTMP['mes'] === 1 && $Y === '2019')  {
            $Y = '2020'; 
        } 

        $dataTmp = $horariomedico->listaBloqueHorario($param, $Y, $paramsTMP['mes']);

        $data1 = [];
        foreach ($dataTmp as $row) {
            $fecha = explode('-', $row->fecha);
            $diaSemana = date('N', mktime(0, 0, 0, $fecha[1], $fecha[2], $fecha[0])); // php date('N')(Lu=1,...,Do=7) 

            /* Debe de agregarse solo lo dias habiles de semana configurado para la empresa.                 
             */
            if (!in_array($diaSemana, $hiddenDays)) {
                $dia = date('d', mktime(0, 0, 0, $fecha[1], $fecha[2], $fecha[0])); //01 a 31

                unset($row->fecha);
                $row->dia = $diaSemana;
                $row->mes = $fecha[1];
                $data1[$diaSemana][$dia][] = $row;
            }
        }

        $data2 = [];
        foreach ($data1 as $diaSemana => $row1) { // php date('N')(Lu=1,...,Do=7)     
            $cantDiasSemana = $this->_fechas_de_dia_mensual($paramsTMP['mes'], $Y, $diaSemana);
            $copiaPrimerdiaSem = [];
            //$mensaje = 'El horario a sido editado. (+/-) bloques de horarios.'; 
            $mensaje = 'El horario a sido editado.';
            //El numero de dias semanal debe ser igual al horario del medico
            if (count($row1) === count($cantDiasSemana)) {
                $bool = true;
                $mensaje = '';
                foreach ($row1 as $dia => $row2) { // $dia:  //01 a 31 Ej. [07, 14, 21, 28]                    
                    if ($bool) {
                        $bool = false;
                        $copiaPrimerdiaSem = $row2;
                    } else {
                        if (count($copiaPrimerdiaSem) === count($row2)) {
                            foreach ($row2 as $indice => $row3) {
                                //Calcula la diferencia entre arrays con un chequeo adicional de índices                                
                                $resultado = array_diff_assoc((array) $copiaPrimerdiaSem[$indice], (array) $row3);
                                //dd($resultado);
                                if (count($resultado) > 0) {
                                    $copiaPrimerdiaSem = [];
                                    $mensaje = 'El horario a sido editado.';
                                    break 2;
                                }
                            }
                        } else {
                            //Al tener cantidad de items, quiere decir por ejemplo:
                            //El lunes 14 tiene mas o menos bloques de horarios que el lunes 7.                           
                            $copiaPrimerdiaSem = [];
                            //$mensaje = 'El horario a sido editado. (+/-) bloques de horarios.';
                            $mensaje = 'El horario a sido editado.';
                            break 1;
                        }
                    }
                }
            }
            $data2[$diaSemana]['data'] = $copiaPrimerdiaSem;
            $data2[$diaSemana]['mensaje'] = $mensaje;
        }

        /* Retornamos el objeto empresa, en ella esta los dias habiles de la semana laboral         
         */
        return $this->crearRespuesta($data2, 200, '', '', $empresa);
    }

    public function medicos(Request $request, $enterprise) {

        $empresa = new empresa();
        $entidad = new entidad();
 
        $paramsTMP = $request->all();        

        $tmpempresa = $empresa->empresa(['url' => $enterprise]);
        $idempresa = $tmpempresa->idempresa;
        
        if ($paramsTMP['tipo'] === '1') {
            $param = array(
                'entidad.idempresa' => $idempresa,
                'tipomedico' => '1'
            );
        } else {
            $param = array(
                'entidad.idempresa' => $idempresa,
                'tipopersonal' => '1',
                'idcargoorg' => $tmpempresa->codecargo
            );
        }

        $data = $entidad->entidadesSedes($param); 

        return $this->crearRespuesta($data, 200);
    }

    public function show($enterprise, $id) {

        $entidad = new entidad();
        $sede = new sede();
        $empresa = empresa::where('url', '=', $enterprise)->first();
        $idempresa = $empresa->idempresa;
        $horariomedico = horariomedico::find($id);

        if ($horariomedico) {
            $horariomedico->fecha = $this->formatFecha($horariomedico->fecha);
  
            $mminicioI = (int) explode(':', $empresa->laborinicio)[1];            
            $listcombox = array(          
                // 'horasi' => $empresa->horas($empresa->laborinicio, $empresa->laborfin, 15, $mminicioI, 0),
                // 'horasf' => $empresa->horas($empresa->laborinicio, $empresa->laborfin, 15, $mminicioF, 14),
                'sedes' => $sede->sedes($idempresa, ['sede.idsede', 'sede.nombre']) 
            ); 

            $entidad = entidad::select('idperfil')->find($horariomedico->idmedico);

            if ($entidad->idperfil === 3) { // Médico
                $mminicioF = $mminicioI + 19;
                $listcombox['horasi'] = $empresa->horas($empresa->laborinicio, $empresa->laborfin, 20, $mminicioI, 0);
                $listcombox['horasf'] = $empresa->horas($empresa->laborinicio, $empresa->laborfin, 20, $mminicioF, 19);
            } else { // Teraputas y otros
                $mminicioF = $mminicioI + 14;
                $listcombox['horasi'] = $empresa->horas($empresa->laborinicio, $empresa->laborfin, 15, $mminicioI, 0);
                $listcombox['horasf'] = $empresa->horas($empresa->laborinicio, $empresa->laborfin, 15, $mminicioF, 14);
            }

            return $this->crearRespuesta($horariomedico, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Horario no encotrado', 404);
    }

    public function newhorariomedico(Request $request, $enterprise) {

//        $entidad = new entidad();
        $sede = new sede();
        $entidad = new entidad(); 
        $empresa = empresa::where('url', '=', $enterprise)->first();

        $idempresa = $empresa->idempresa;

        $meses = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Setiembre', 'Octubre', 'Noviembre', 'Diciembre');
        $mes = (int) date('m');
                    
        if ($mes == 12) {
            $selectMeses = array(['id' => 12, 'mes' => $meses[$mes - 1]], ['id' => 1, 'mes' => $meses[0]]);
        } else {
            $selectMeses = array(['id' => $mes, 'mes' => $meses[$mes - 1]], ['id' => $mes + 1, 'mes' => $meses[$mes]]);
        }

        $paramsTMP = $request->all();  
 
        $mminicioI = (int) explode(':', $empresa->laborinicio)[1]; 

        if (isset($paramsTMP['idsuperperfil']) && $paramsTMP['idsuperperfil'] === '3') { // Médico
            $mminicioF = $mminicioI + 19;
            $listcombox = array(        
                'meses' => $selectMeses, 
                'horasi' => $empresa->horas($empresa->laborinicio, $empresa->laborfin, 20, $mminicioI, 0),
                'horasf' => $empresa->horas($empresa->laborinicio, $empresa->laborfin, 20, $mminicioF, 19)
            );
        } else {
            $mminicioF = $mminicioI + 14;
            $listcombox = array(
                'meses' => $selectMeses, 
                'horasi' => $empresa->horas($empresa->laborinicio, $empresa->laborfin, 15, $mminicioI, 0),
                'horasf' => $empresa->horas($empresa->laborinicio, $empresa->laborfin, 15, $mminicioF, 14)
            );
        }

        if ($paramsTMP['tiporegistro'] === 'diario') {
            $listcombox['sedes'] = $sede->sedes($idempresa); 
        }

        if ($paramsTMP['tiporegistro'] === 'mensual') {
            $param = array( 
                'entidad.idempresa' => $idempresa,
                'entidad.tipopersonal' => '1' 
            );

            $param2 = array(
                'camilla.idempresa' => $idempresa 
            );  

            $listcombox['sedes'] = $sede->sedes($idempresa);
            $listcombox['meses'] = $selectMeses;
            $listcombox['personal'] = $entidad->entidades($param, true); 
            $listcombox['camillas'] = $empresa->camillas($param2);  

        }

        return $this->crearRespuesta([], 200, '', '', $listcombox);
    }

    public function store(Request $request, $enterprise) {

        $empresa = empresa::where('url', '=', $enterprise)->first();
        $horariomedico = new horariomedico();
        $objentidad = new entidad();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        $entidad = $objentidad->entidad(['entidad.identidad' => $request['horariomedico']['idmedico']]);

        $fecha = $request['horariomedico']['fecha'];
        $request['horariomedico']['idempresa'] = $idempresa;
        $request['horariomedico']['fecha'] = $this->formatFecha($request['horariomedico']['fecha'], 'yyyy-mm-dd');

        //VALIDACIONES  
        /* 1ra validcion       
         * para que el mes no sea mayor al proximo mes.  
         */ 
                    
        /* 1ra validacion */
        $validation = ['inValid' => false, 'message' => 'ss'];
        $param = array(
            'horariomedico.idempresa' => $idempresa,
            'horariomedico.fecha' => $request['horariomedico']['fecha'],
                //'tipo' => $request['horariomedico']['tipo'] 
        );

        $data = $horariomedico->listaBloqueHorarioRango($param, $request['horariomedico']['inicio'], $request['horariomedico']['fin']);

        foreach ($data as $row) {

            // if ($row->idmedico === $request['horariomedico']['idmedico'] && $row->idsede !== 15) {
            //     // 16.06.2020
            //     // Omite validación para OSI ONLINE
            //     //  && $row->idsede !== 15 

            //     $validation['inValid'] = true;

            //     if ($row->idsede !== $request['horariomedico']['idsede']) {
            //         $sede = sede::find($row->idsede);
            //         $validation['message'] = 'Ya tiene un bloque de horario. En sede ' . $sede->nombre;
            //     } else {
            //         $validation['message'] = 'Ya tiene un bloque de horario.';
            //     }
            //     break;
            // }

            //idsuperperfil 3: Medico
            if (isset($request['horariomedico']['idsede']) && $row->idsede === $request['horariomedico']['idsede'] && $row->idsuperperfil === 3 && $entidad->idsuperperfil === 3) {
                $validation['inValid'] = true;
                $validation['message'] = 'Sede está ocupado, por médico ' . $row->entidad;
                break;
            }
        } 
        

        if ($validation['inValid']) {
            return $this->crearRespuesta($validation['message'], [200, 'info'], '', '', $data);
        }


        /* 328506 */

        \DB::beginTransaction();
        try {
            //Graba en 1 tablaa(horariomedico)            
            $horariomedico = horariomedico::create($request['horariomedico']);
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta('Horario creado.', 201);
    }

    public function storemensual(Request $request, $enterprise) {

        $empresa = empresa::where('url', '=', $enterprise)->first();
        $horariomedico = new horariomedico();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        //VALIDACIONES  

        /* 1ra validacion */
        $validation = ['inValid' => false, 'message' => ''];
        
        $Y = date('Y');   

        
        if((int)$request['mes'] === 1 && $Y === '2019')  {
            //Este mala practica se soluciona si coloco el año en la vista
            $Y = '2020'; 
        }           
        
        $diasemana = $request['dia'] === '7' ? 1 : ((int) $request['dia'] + 1);
        $param = $Y . '|' . $request['mes'] . '|' . $diasemana; //2016|3|6     //(24 Vi de Marzo)
        $data = $horariomedico->listaBloqueHorarioRango($param, $request['inicio'], $request['fin'], '2');

        // 16.06.2019
        // Temporalmente
        // foreach ($data as $row) {
        //     if ($row->idmedico === $request['idmedico']) {
        //         $validation['inValid'] = true;
        //         if (isset($request['idsede']) && $row->idsede !== $request['idsede']) {
        //             $sede = sede::find($row->idsede);
        //             $validation['message'] = 'Ya tiene un bloque de horario. En sede ' . $sede->nombre;
        //         } else {
        //             $validation['message'] = 'Ya tiene un bloque de horario.';
        //         }
        //         break;
        //     }
        // }

        if ($validation['inValid']) {
            return $this->crearRespuesta($validation['message'], [200, 'info'], '', '', $data);
        }
        /**/

        \DB::beginTransaction();
        try {
            //Graba en 1 tablaa(horariomedico)            
            //
            $request['idempresa'] = $idempresa;
            $request['fecha'] = '';
            $diasMensual = $this->_fechas_de_dia_mensual($request['mes'], $Y, $request['dia']);

            foreach ($diasMensual as $row) {
                $request['fecha'] = $row;
                $horariomedico = horariomedico::create($request);
            }
            //$horariomedico = horariomedico::create($request);  
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();
        
        return $this->crearRespuesta('Horario creado.', 201, '', '', $diasMensual);
    }

    public function storemensualmasivo(Request $request, $enterprise, $id) {

        $empresa = empresa::where('url', '=', $enterprise)->first();
        $horariomedico = new horariomedico();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        $param = array(
            'horariomedico.idempresa' => $idempresa, 
            'horariomedico.idmedico' => $id 
        );
 
        $data = $horariomedico->grid($param, '', [], '', [$request['ano'], $request['mes']]);
        // return $this->crearRespuesta('XD', [200, 'info'], '', '', [$param, $request['ano'], $request['mes']]);
        if ($data) 
            return $this->crearRespuesta('Ya tiene horario para el mes destino.', [200, 'info'], '', '', $data);

        
        // return $this->crearRespuesta('XD', [200, 'info'], '', '', $request);
        //VALIDACIONES  
        \DB::beginTransaction();
        try {
            //Graba en 1 tablaa(horariomedico)
            foreach($request['data'] as $row) { 

                $diasMensual = $this->_fechas_de_dia_mensual($row['mes'], $request['ano'], $row['dia']);

                foreach ($diasMensual as $fecha) { 

                    $insert = array(
                        'idempresa' => $idempresa,
                        'idmedico' => $row['idmedico'],
                        'fecha' => $fecha,
                        'inicio' => $row['inicio'],
                        'fin' => $row['fin'],
                    );

                    if (isset($row['idsede'])) {
                        $insert['idsede'] =  $row['idsede'];
                    }

                    $horariomedico = horariomedico::create($insert);
                }
            }
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();
        return $this->crearRespuesta('Horario mensual creado.', 201, '', '', $diasMensual);
    }

    public function update(Request $request, $enterprise, $id) {

        $objentidad = new entidad();
        
        $empresa = empresa::where('url', '=', $enterprise)->first();
        $horariomedico = horariomedico::find($id);

        $idempresa = $empresa->idempresa;

        $entidad = $objentidad->entidad(['entidad.identidad' => $horariomedico->idmedico]);

        if ($horariomedico) {
            $request = $request->all(); 
            $request['horariomedico']['fecha'] = $this->formatFecha($request['horariomedico']['fecha'], 'yyyy-mm-dd');

            //VALIDACIONES  
            /* 1ra validacion
             */
            $validation = ['inValid' => false, 'message' => 'ss'];

            $param = array(
                'horariomedico.idempresa' => $horariomedico->idempresa,
                'horariomedico.fecha' => $request['horariomedico']['fecha'],
                'horariomedico.tipo' => $horariomedico->tipo
            );

            $data = $horariomedico->listaBloqueHorarioRango($param, $request['horariomedico']['inicio'], $request['horariomedico']['fin']);

            foreach ($data as $row) {
             
                $bool = $row->idsede === $horariomedico->idsede &&
                        $row->idmedico === $horariomedico->idmedico &&
                        $row->inicio === $horariomedico->inicio &&
                        $row->fin === $horariomedico->fin; 

                if ($row->idsede === 15) {
                    // 16.06.2020
                    // Omite validación para OSI ONLINE
                    $bool = true;
                }

                // 16.06.2020
                // if (!$bool) {
                //     if ($row->idmedico === $request['horariomedico']['idmedico']) {
                //         $validation['inValid'] = true;
                //         if ($row->idsede !== $request['horariomedico']['idsede']) {
                //             $sede = sede::find($row->idsede);
                //             $validation['message'] = 'Ya tiene un bloque de horario. En sede ' . $sede->nombre;
                //         } else {
                //             $validation['message'] = 'Ya tiene un bloque de horario.';
                //         }
                //         break;
                //     } 
                // }

                if (isset($request['horariomedico']['idsede']) && 
                    $row->idsede === $request['horariomedico']['idsede'] && 
                    $row->idsuperperfil === 3 &&    
                    $entidad->idsuperperfil === 3 && 
                    $row->idmedico !== $horariomedico->idmedico
                    ) {
 
                    $validation['inValid'] = true;
                    $validation['message'] = 'Sede está ocupado, por médico ' . $row->entidad;
                    break;
                }
            }

            if ($validation['inValid']) {
                return $this->crearRespuesta($validation['message'], [200, 'info'], '', '', $data);
            }
            /**/

            \DB::beginTransaction();
            try {
                //Graba en 1 tablaa(horariomedico) 
                $horariomedico->fill($request['horariomedico']);
                $horariomedico->save();

                // 16.06.2020
                // Se cree automáticamente las reservas para OSI ONLINE
                if (isset($request['horariomedico']['online']) && $request['horariomedico']['online'] !== 15) {

                    // Eliminar horario ONLINE
                    $where = array(
                        'idempresa' => $idempresa,
                        'idsede' => 15,
                        'idmedico' => $request['horariomedico']['idmedico'],                        
                        'fecha' => $request['horariomedico']['fecha'],
                        'inicio' => $request['horariomedico']['inicio'],
                        'fin' => $request['horariomedico']['fin']
                    );

                    \DB::table('horariomedico')
                        ->where($where) 
                        ->delete();

                    // Crear horario ONLINE
                    $insert = array(
                        'idempresa' => $idempresa,
                        'idsede' => 15,
                        'idmedico' => $request['horariomedico']['idmedico'],
                        'fecha' => $request['horariomedico']['fecha'],
                        'inicio' => $request['horariomedico']['inicio'],
                        'fin' => $request['horariomedico']['fin']
                    );

                    if ($request['horariomedico']['online'] === '1') {
                        \Log::info(print_r($insert, true));
                        \DB::table('horariomedico')->insert($insert);
                    }
                }
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Horario actualizado', 200, '', '', $data);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un horario', 404);
    }

    public function destroy($enterprise, $id) {

        $empresa = empresa::where('url', '=', $enterprise)->first();

        $citamedica = new citamedica();
        $citaterapeutica = new citaterapeutica();
        $horariomedico = horariomedico::find($id);

        $idempresa = $empresa->idempresa;

        // Validaciones
        // 1.-Validar que no tenga citas médicas.
        $param = [];
        $param['citamedica.idempresa'] = $idempresa;
        $param['citamedica.idsede'] = $horariomedico->idsede;
        $param['citamedica.idmedico'] = $horariomedico->idmedico;
        $param['citamedica.fecha'] = $horariomedico->fecha; 
        $whereIn = [4, 5, 6]; //Pendiente, confirmada y atendida

        $datacita = $citamedica->grid($param, '', '', '', '', '', $whereIn); 

        if(count($datacita) > 0) {
            $fecha = $this->formatFecha($horariomedico->fecha);
            return $this->crearRespuesta('Médico tiene citas agendadas el '. $fecha .' deberá reprogramar citas a otro médico, antes de eliminar horario laboral.', [200, 'info']);
        }
 
        // 2.-Validar que no tenga citas médicas.
        $param = [];
        $param['citaterapeutica.idempresa'] = $idempresa;
        $param['citaterapeutica.idsede'] = $horariomedico->idsede;
        $param['citaterapeutica.idterapista'] = $horariomedico->idmedico;
        $param['citaterapeutica.fecha'] = $horariomedico->fecha; 
        $whereIn = [32, 33, 34]; //Pendiente, confirmada y atendida

        $datacita = $citaterapeutica->grid($param, '', '', '', '', '', $whereIn); 

        if(count($datacita) > 0) {
            // Comento porque no hay una reprogramacion de reservaciones masivas en la agenda. 30/10/2018.
            // $fecha = $this->formatFecha($horariomedico->fecha);
            // return $this->crearRespuesta('Terapeuta tiene citas agendadas el '. $fecha .' deberá reprogramar citas a otro Terapeuta, antes de eliminar horario laboral.', [200, 'info']);
        }

        // return $this->crearRespuesta('Oh!!', [200, 'info']);

        if ($horariomedico) {

            \DB::beginTransaction();
            try {

                //Graba en 1 tablaa(horariomedico)    


                $horariomedico->delete();


            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Horario eliminado.', 200);
        }
        return $this->crearRespuestaError('Horario no encotrados', 404);
    }

    public function destroymensual(Request $request, $enterprise) {

        $citamedica = new citamedica();
        $citaterapeutica = new citaterapeutica();
        $horariomedico = new horariomedico();
        $empresa = new empresa();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();
        
        $Y = date('Y');  
        if((int)$request['mes'] === 1 && $Y === '2019')  {
            $Y = '2020'; 
        }  

        // php date('N')(Lu=1,...,Do=7) 
        // mysql dayofweek(fecha)(Lu=2,...,Do=1)
        $diasemana = $request['dia'] === '7' ? 1 : ((int) $request['dia'] + 1);

        $primerDia = date('Y-m-d', mktime(0, 0, 0, (int)$request['mes'], 1, $Y)); 
        $day = date("d", mktime(0, 0, 0, $request['mes'] + 1, 0, $Y));
        $ultimoDia = date('Y-m-d', mktime(0, 0, 0, $request['mes'], $day, $Y)); 

        // Validaciones
        // 1.-Validar que no tenga citas médicas.        
        $param = [];
        $param['citamedica.idempresa'] = $idempresa;
        $param['citamedica.idsede'] = $request['idsede'];
        $param['citamedica.idmedico'] = $request['idmedico'];
        $whereIn = [4, 5, 6]; //Pendiente, confirmada y atendida
        $between = [$primerDia, $ultimoDia];

        $rawWhere = "YEAR(citamedica.fecha) = ".$Y." AND 
                month(citamedica.fecha) = ".$request['mes']." AND 
                dayofweek(citamedica.fecha) = ".$diasemana;

        $datacita = $citamedica->grid($param, $between, '', '', '', '', $whereIn, false, [], false, false, 'citamedica.fecha', '', false, false, false, '', '', [], [], [], false, [], '', false, $rawWhere); 
         
        if (count($datacita) > 0) {
            $strfechas = []; 
            foreach ($datacita as $row) { 
                if (!in_array($row->fecha, $strfechas)) {
                    $strfechas[] = $row->fecha;  
                }
            } 

            return $this->crearRespuesta('Médico tiene citas agendadas el '. implode(", ", $strfechas) .' deberá reprogramar citas a otro médico, antes de eliminar horario laboral.', [200, 'info']);
        }

        // 2.-Validar que no tenga citas médicas.
        $param = [];
        $param['citaterapeutica.idempresa'] = $idempresa;
        $param['citaterapeutica.idsede'] = $request['idsede'];
        $param['citaterapeutica.idterapista'] = $request['idmedico']; 
        $whereIn = [32, 33, 34]; //Pendiente, confirmada y atendida

        $rawWhere = "YEAR(citaterapeutica.fecha) = ".$Y." AND 
                month(citaterapeutica.fecha) = ".$request['mes']." AND 
                dayofweek(citaterapeutica.fecha) = ".$diasemana;

        $datacita = $citaterapeutica->grid($param, '', '', '', '', '', $whereIn, [], $betweenhora='', [], '', [], [], [], false, [], [], [], $rawWhere); 
 
        if (count($datacita) > 0) {
            $strfechas = []; 
            foreach ($datacita as $row) { 
                if (!in_array($row->fecha, $strfechas)) {
                    $strfechas[] = $row->fecha;  
                }
            } 

            // Comento porque no hay una reprogramacion de reservaciones masivas en la agenda. 30/10/2018.
            // return $this->crearRespuesta('Terapeuta tiene citas agendadas el '. implode(", ", $strfechas) .' deberá reprogramar citas a otro médico, antes de eliminar horario laboral.', [200, 'info']);
        }
        
        if (true) {
            \DB::beginTransaction();
            try {
                //Elimina en 1 tabla(horariomedico   
                $param = array(
                    'idempresa' => $idempresa,
                    'idsede' => $request['idsede'],
                    'idmedico' => $request['idmedico']
                ); 

                $horariomedico->eliminarBloqueHorario($param, $Y, $request['mes'], $diasemana, $request['inicio'], $request['fin']);
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Bloque de horario eliminado', 200, '', '', $request);
        }
        return $this->crearRespuestaError('Horario médico no encotrado', 404);
    } 

    function _fechas_de_dia_mensual($mes, $ano, $diaReferencia) { //1
        //2008-12-14
        /* Primer dia */
        $primerDia = date('d', mktime(0, 0, 0, $mes, 1, $ano));

        $day = date("d", mktime(0, 0, 0, $mes + 1, 0, $ano));
        $ultimoDia = date('d', mktime(0, 0, 0, $mes, $day, $ano));

        $dia1 = date('N', mktime(0, 0, 0, $mes, 1, $ano)); //php date('N')(Lu=1,...,Do=7) //Marzo: martes 2
        $dia2 = date('N', mktime(0, 0, 0, $mes, 2, $ano)); //php date('N')(Lu=1,...,Do=7) //Marzo: miercoles 3
        $dia3 = date('N', mktime(0, 0, 0, $mes, 3, $ano)); //php date('N')(Lu=1,...,Do=7) 
        $dia4 = date('N', mktime(0, 0, 0, $mes, 4, $ano)); //php date('N')(Lu=1,...,Do=7) 
        $dia5 = date('N', mktime(0, 0, 0, $mes, 5, $ano)); //php date('N')(Lu=1,...,Do=7) 
        $dia6 = date('N', mktime(0, 0, 0, $mes, 6, $ano)); //php date('N')(Lu=1,...,Do=7) //Marzo: domingo 7
        $dia7 = date('N', mktime(0, 0, 0, $mes, 7, $ano)); //php date('N')(Lu=1,...,Do=7) //Marzo: lunes 1

        $d = '';
        if ($dia1 == $diaReferencia)
            $d = 1;
        if ($dia2 == $diaReferencia)
            $d = 2;
        if ($dia3 == $diaReferencia)
            $d = 3;
        if ($dia4 == $diaReferencia)
            $d = 4;
        if ($dia5 == $diaReferencia)
            $d = 5;
        if ($dia6 == $diaReferencia)
            $d = 6;
        if ($dia7 == $diaReferencia)
            $d = 7; //Lunes 7 de Marzo

        $diasMensual = array();
        for ($i = $d; $i <= $ultimoDia; $i = $i + 7) {
            $diasMensual[] = date('Y-m-d', mktime(0, 0, 0, $mes, $i, $ano));
        }

        return $diasMensual;
    }

}
