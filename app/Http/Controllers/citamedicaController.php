<?php

namespace App\Http\Controllers;

use Excel;
use Culqi\Culqi;
use App\Models\sede;
use App\Models\calls;
use App\Models\venta;
use App\Models\empresa;
use App\Models\entidad;
use App\Models\grupodx;
use App\Models\paquete;
use App\Models\producto;
use App\Models\citamedica;
use App\Exports\DataExport;
use App\Models\ordencompra;
use App\Models\presupuesto;
use Illuminate\Http\Request;
use App\Models\cicloatencion;
use App\Models\dxtratamiento;
use App\Models\horariomedico;
use App\Models\citamedicaarchivo;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\ventaController;

//getresponse e-mailmarketing
class citamedicaController extends Controller
{

    public function __construct(Request $request)
    {
        $this->getToken($request);
    }

    public function construct(Request $request, $enterprise)
    {
        /* Obtiene solo las sedes a las que tiene acceso el usuario
         * Obtiene dias laborales, hora inicio de labores, hora inicio de refrigerio,
         * tiempo de cita medica, tiempo de terapia.
         */
        $sede = new sede();
        $entidad = new entidad();

        $empresa = empresa::select('idempresa', 'laborinicio', 'laborfin', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo')
            ->where('url', '=', $enterprise)->first();

        $idempresa = $empresa->idempresa;

        $param = [
            'sede.idempresa' => $empresa->idempresa,
            'entidadsede.identidad' => $this->objTtoken->my,
        ];

        $data = array(
            'estadoscita' => $empresa->estadodocumentos(2),
            'sedes' => $sede->autorizadas($param, $this->objTtoken->mysede),
            'sedehorarios' => $empresa->listaSedeshorarios($idempresa),
            'diasferiados' => $empresa->diasferiados(['idempresa' => $idempresa]),
            'empresahorario' => $empresa,
        );

        if (isset($request['getmedicos']) && $request['getmedicos'] === '1') {
            $data['medicos'] = $entidad->entidades(['entidad.tipomedico' => '1', 'acceso' => '1']);
        }

        return $this->crearRespuesta($data, 200);
    }

    public function dashboard(Request $request, $enterprise)
    {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $citamedica = new citamedica();
        $horariomedico = new horariomedico();

        $idempresa = $empresa->idempresa($enterprise);

        $param = [];
        $param['citamedica.idempresa'] = $idempresa;
        $param2 = [];
        $paramCreated = [];
        $paramCreated['citamedica.idempresa'] = $idempresa;

        //Obtencion de rango de tiempo
        if (isset($paramsTMP['year']) && !empty($paramsTMP['year']) && isset($paramsTMP['week']) && !empty($paramsTMP['week'])) {
            $semana = $empresa->semanasAno($paramsTMP['year'], $paramsTMP['week']);
        } else {
            $semana = $empresa->semanasAno(date('Y'), date('W'));
        }

        if (empty($semana)) {
            $semana = $empresa->semanasAno($paramsTMP['year'] + 1, 1);
        }

        $paramsTMP['desde'] = $semana['inicio'];
        $paramsTMP['hasta'] = $semana['fin'];

        $fecha = $this->formatFecha($semana['inicio'], 'yyyy-mm-dd');

        $tiempo = array();
        $tiempo[] = date('d/m/Y', strtotime('+0 day', strtotime($fecha)));
        $tiempo[] = date('d/m/Y', strtotime('+1 day', strtotime($fecha)));
        $tiempo[] = date('d/m/Y', strtotime('+2 day', strtotime($fecha)));
        $tiempo[] = date('d/m/Y', strtotime('+3 day', strtotime($fecha)));
        $tiempo[] = date('d/m/Y', strtotime('+4 day', strtotime($fecha)));
        $tiempo[] = date('d/m/Y', strtotime('+5 day', strtotime($fecha)));
        //

        $between = [];
        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta'])) {
            if (!empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {
                $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd') . ' 00:00:00';
                $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd') . ' 23:59:59';
                $between = [$paramsTMP['desde'], $paramsTMP['hasta']];
            }
        }

        if (isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])) {
            $param['citamedica.idsede'] = $paramsTMP['idsede'];
            $param2['horariomedico.idsede'] = $paramsTMP['idsede'];
            $paramCreated['citamedica.idsede'] = $paramsTMP['idsede'];
        }

        //dd($between);
        //4:pendiente, 5:confirmada, 6:atendida, 7:cancelada
        //public function grid($param, $betweendate='', $likename= '', $items = '', $orderName = '', $orderSort = '', $whereIn = array(), $notExists = false, $whereInMed = array(), $pendiente = false, $pagado = false, $fieldbetween = 'citamedica.fecha') {

        //Interesa solo sede y telefono [18,19]        
        $dataCreated = $citamedica->grid($paramCreated, $between, '', '', '', '', [4, 5, 6], false, [], false, false, 'citamedica.created_at', '', false, false, false, '', '', [], [], [], false, [18, 19]);

        //Interesa solo pagina web
        $paramCreated['citamedica.idatencion'] = 70;
        $dataCreatedPortal = $citamedica->grid($paramCreated, $between, '', '', '', '', [4, 5, 6], false, [], false, false, 'citamedica.created_at');

        $dataCitas = $citamedica->grid($param, $between, '', '', '', '', [4, 5, 6]);

        $matrizDisp = []; //1
        $matrizAdmisionistas = [];
        $matrizPortal = [];
        $matrizSedes = [];
        $matrizReservado = [];
        $matrizAtendido = [];

        //dd($Indicador);
        $total = 0;
        foreach ($dataCreated as $row) {

            $Indicador = $row->createdat; //$this->formatFecha($row->fecha);
            if (!isset($matrizAdmisionistas[$row->id_created_at])) {
                $matrizAdmisionistas[$row->id_created_at]['Indicador'] = $row->created;
                foreach ($tiempo as $time) {
                    $matrizAdmisionistas[$row->id_created_at][$time] = 0;
                }
                $matrizAdmisionistas[$row->id_created_at]['Total'] = 0;
                $matrizAdmisionistas[$row->id_created_at]['Sede'] = 0;
                $matrizAdmisionistas[$row->id_created_at]['Fono'] = 0;
                $matrizAdmisionistas[$row->id_created_at]['Porcentaje'] = 0;
            }

            if (!isset($matrizSedes[$row->idsede])) {
                $matrizSedes[$row->idsede]['Indicador'] = $row->sedenombre;
                foreach ($tiempo as $time) {
                    $matrizSedes[$row->idsede][$time] = 0;
                }
                $matrizSedes[$row->idsede]['Total'] = 0;
                $matrizSedes[$row->idsede]['Sede'] = 0;
                $matrizSedes[$row->idsede]['Fono'] = 0;
            }

            $row->idestado = ($row->idestado === 4 || $row->idestado === 5) ? 5 : $row->idestado;

            if (!isset($matrizReservado[$row->idsede])) {
                $matrizReservado[$row->idsede]['Indicador'] = $row->sedenombre;
                foreach ($tiempo as $time) {
                    $matrizReservado[$row->idsede]['5']['am'][$time] = 0;
                    $matrizReservado[$row->idsede]['5']['pm'][$time] = 0;
                    $matrizReservado[$row->idsede]['6']['am'][$time] = 0;
                    $matrizReservado[$row->idsede]['6']['pm'][$time] = 0;
                }
                $matrizReservado[$row->idsede]['5']['am']['Total'] = 0;
                $matrizReservado[$row->idsede]['5']['pm']['Total'] = 0;
                $matrizReservado[$row->idsede]['6']['am']['Total'] = 0;
                $matrizReservado[$row->idsede]['6']['pm']['Total'] = 0;
            }

            if (isset($matrizAdmisionistas[$row->id_created_at][$Indicador])) {
                $matrizAdmisionistas[$row->id_created_at][$Indicador] = $matrizAdmisionistas[$row->id_created_at][$Indicador] + 1;
            }

            $matrizAdmisionistas[$row->id_created_at]['Total'] = $matrizAdmisionistas[$row->id_created_at]['Total'] + 1;
            $matrizAdmisionistas[$row->id_created_at]['Sede'] = $matrizAdmisionistas[$row->id_created_at]['Sede'] + ($row->idatencion === 18 ? 1 : 0);
            $matrizAdmisionistas[$row->id_created_at]['Fono'] = $matrizAdmisionistas[$row->id_created_at]['Fono'] + ($row->idatencion === 19 ? 1 : 0);

            if (isset($matrizSedes[$row->idsede][$Indicador])) {
                $matrizSedes[$row->idsede][$Indicador] = $matrizSedes[$row->idsede][$Indicador] + 1;
            }

            $matrizSedes[$row->idsede]['Total'] = $matrizSedes[$row->idsede]['Total'] + 1;
            $matrizSedes[$row->idsede]['Sede'] = $matrizSedes[$row->idsede]['Sede'] + ($row->idatencion === 18 ? 1 : 0);
            $matrizSedes[$row->idsede]['Fono'] = $matrizSedes[$row->idsede]['Fono'] + ($row->idatencion === 19 ? 1 : 0);

            $meridiano = (int) substr($row->inicio, 0, 2) < 16 ? 'am' : 'pm';
            if (isset($matrizReservado[$row->idsede][$row->idestado][$meridiano][$Indicador])) {
                $matrizReservado[$row->idsede][$row->idestado][$meridiano][$Indicador] = $matrizReservado[$row->idsede][$row->idestado][$meridiano][$Indicador] + 1;
            }

            $matrizReservado[$row->idsede][$row->idestado][$meridiano]['Total'] = $matrizReservado[$row->idsede][$row->idestado][$meridiano]['Total'] + 1;

            $total = $total + 1;
        }

        //Inicio portal
        foreach ($dataCreatedPortal as $row) {

            $Indicador = $row->createdat;
            if (!isset($matrizPortal[$row->id_created_at])) {
                $matrizPortal[$row->id_created_at]['Indicador'] = $row->created;
                foreach ($tiempo as $time) {
                    $matrizPortal[$row->id_created_at][$time] = 0;
                }
                $matrizPortal[$row->id_created_at]['Total'] = 0;
            }

            if (isset($matrizPortal[$row->id_created_at][$Indicador])) {
                $matrizPortal[$row->id_created_at][$Indicador] = $matrizPortal[$row->id_created_at][$Indicador] + 1;
            }

            $matrizPortal[$row->id_created_at]['Total'] = $matrizPortal[$row->id_created_at]['Total'] + 1;
        }
        //Fin portal

        $matrizDisp['disponibilidad'] = array('Indicador' => 'Disponibilidad', 'Total' => 0, 'Porcentaje' => 0);
        $matrizDisp['agendada'] = array('Indicador' => 'Agendadas', 'Total' => 0, 'Porcentaje' => 0);
        $matrizDisp['atendida'] = array('Indicador' => 'Atendidas', 'Total' => 0, 'Porcentaje' => 0);
        $matrizDisp['libre'] = array('Indicador' => 'Libres', 'Total' => 0, 'Porcentaje' => 0);
        $matrizDisp['pagopresupuesto'] = array('Indicador' => 'Total/Acuenta de presupuesto', 'Total' => 0, 'Porcentaje' => 0);

        foreach ($tiempo as $time) {
            $matrizDisp['disponibilidad'][$time] = 0;
            $matrizDisp['agendada'][$time] = 0;
            $matrizDisp['atendida'][$time] = 0;
            $matrizDisp['libre'][$time] = 0;
            $matrizDisp['pagopresupuesto'][$time] = 0;
        }

        $matrizAgendadas = [];

        foreach ($dataCitas as $row) {

            $row->turno = $this->turno($row->idsede, $row->fecha, $row->inicio, $row->fin);

            if (!isset($matrizAgendadas[$row->idsede])) {
                $matrizAgendadas[$row->idsede]['Indicador'] = $row->sedenombre;
                foreach ($tiempo as $time) {
                    $matrizAgendadas[$row->idsede]['Mañana'][$time] = 0;
                    $matrizAgendadas[$row->idsede]['Tarde'][$time] = 0;
                }
                $matrizAgendadas[$row->idsede]['Mañana']['Total'] = 0;
                $matrizAgendadas[$row->idsede]['Tarde']['Total'] = 0;
            }

            $Indicador = $row->fecha; //$this->formatFecha($row->fecha);

            $matrizAgendadas[$row->idsede][$row->turno][$Indicador] += 1;
            $matrizAgendadas[$row->idsede][$row->turno]['Total'] += +1;

            $row->idestado = ($row->idestado === 4 || $row->idestado === 5) ? 5 : $row->idestado;

            $matrizDisp['agendada'][$Indicador] = $matrizDisp['agendada'][$Indicador] + 1;
            $matrizDisp['agendada']['Total'] = $matrizDisp['agendada']['Total'] + 1;

            if ($row->idestado === 6) {
                $matrizDisp['atendida'][$Indicador] = $matrizDisp['atendida'][$Indicador] + 1;
                $matrizDisp['atendida']['Total'] = $matrizDisp['atendida']['Total'] + 1;

                if (!empty($row->presupuesto)) {
                    $matrizDisp['pagopresupuesto'][$Indicador] += +1;
                    $matrizDisp['pagopresupuesto']['Total'] += +1;
                }
            }
        }

        $Admisionistas = array();
        foreach ($matrizAdmisionistas as $pk => $row) {
            $row['Porcentaje'] = round($row['Total'] / $total * 100, 0);
            $row['idcreatedat'] = $pk;
            $Admisionistas[] = $row;
        }

        $Portal = array();
        foreach ($matrizPortal as $pk => $row) {
            $row['idcreatedat'] = $pk;
            $Portal[] = $row;
        }

        /* TRUE: 30 CONSULTAS en 4 HORAS(Papá de Andres)
         * FALSE: 4 CONSULTAS en 1 HORAS(Default, recomendable)
         */
        $opcion = true;
        //dd($Admisionistas);
        /* HORARIOS DE MEDICO */
        $param2['horariomedico.idempresa'] = $idempresa;
        $param2['perfil.idsuperperfil'] = 3;

        $matrizhorario = $horariomedico->grid($param2, $between);

        foreach ($matrizhorario as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            if ($opcion) {
                /* Obtiene las horas y lo multiplica por el factor
                 * Anado 60 s porque es equivalente a 1 min. y obtengo por ejemplo 4 horas
                 * FACTOR 7.5 = 30 CONSULTAS en 4 HORAS.
                 * 7.5 consultas por hora.
                 */
                $row->disponibles = ceil((($row->end_s + 60 - $row->start_s) / 3600) * 7.5);
                $matrizDisp['disponibilidad'][$row->fecha] = $matrizDisp['disponibilidad'][$row->fecha] + $row->disponibles;
                $matrizDisp['disponibilidad']['Total'] = $matrizDisp['disponibilidad']['Total'] + $row->disponibles;
            }
        }
        //dd($matrizDisp);
        if (!$opcion) {
            // $sedehorario = $empresa->sedehorarios(1);
            // $interconsultas = $this->configurarInterconsultas($matrizhorario, $this->horaaSegundos($sedehorario->tiempoconsultamedica), $this->horaaSegundos($sedehorario->tiempointerconsulta));
            // foreach ($interconsultas as $row) {
            //     $fecha = explode(' ', $row['inicio'])[0];
            //     $matrizDisp['disponibilidad'][$fecha] = $matrizDisp['disponibilidad'][$fecha] + 1;
            //     $matrizDisp['disponibilidad']['Total'] = $matrizDisp['disponibilidad']['Total'] + 1;
            // }
        }
        //dd($matrizDisp);

        $acum = 0;
        foreach ($matrizDisp['libre'] as $key => $row) {
            if ($key !== 'Indicador' && $key !== 'Total') {
                $resta = $matrizDisp['disponibilidad'][$key] - $matrizDisp['agendada'][$key];
                $matrizDisp['libre'][$key] = $resta > 0 ? $resta : 0;
                $acum = $acum + ($resta > 0 ? $resta : 0);
            }
        }
        $matrizDisp['libre']['Total'] = $acum;

        foreach ($matrizDisp as $key => $row) {
            if ($key === 'atendida') {
                if ($matrizDisp['agendada']['Total'] > 0) {
                    $matrizDisp[$key]['Porcentaje'] = round(($matrizDisp[$key]['Total'] / $matrizDisp['agendada']['Total']) * 100, 0);
                }

            } else if ($key === 'pagopresupuesto') {
                if ($matrizDisp['atendida']['Total'] > 0) {
                    $matrizDisp[$key]['Porcentaje'] = round(($matrizDisp[$key]['Total'] / $matrizDisp['atendida']['Total']) * 100, 0);
                }

            } else {
                if ($matrizDisp['disponibilidad']['Total'] > 0) {
                    $matrizDisp[$key]['Porcentaje'] = round(($matrizDisp[$key]['Total'] / $matrizDisp['disponibilidad']['Total']) * 100, 0);
                }

            }
        }

        $matrizDisponibilidad = array();
        foreach ($matrizDisp as $key => $row) {
            $matrizDisponibilidad[] = $row;
        }

        $matrizAgenda = array();
        foreach ($matrizAgendadas as $k => $row) {
            foreach ($row as $key => $row2) {
                if (in_array($key, ['Mañana', 'Tarde'])) {
                    $row2['Indicador'] = $row['Indicador'];
                    $row2['Turno'] = $key;
                    $matrizAgenda[] = $row2;
                }
            }
        }

        $matrizAdmisionistas = $this->ordenarMultidimension($Admisionistas, 'Total', SORT_DESC);
        $matrizAgenda = $this->ordenarMultidimension($matrizAgenda, 'Indicador', SORT_ASC);

        return $this->crearRespuesta(['admisionistas' => $matrizAdmisionistas, 'disponibilidad' => $matrizDisponibilidad, 'agendadas' => $matrizAgenda,
            'week' => $semana, 'day' => date('d/m/Y'), 'portal' => $Portal], 200);
    }

    public function dashboarddetail(Request $request, $enterprise)
    {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $citamedica = new citamedica();
        $idempresa = $empresa->idempresa($enterprise);

        $param = [];
        $param['citamedica.idempresa'] = $idempresa;

        if (isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])) {
            $param['citamedica.idsede'] = $paramsTMP['idsede'];
        }

        $between = [];
        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta'])) {
            if (!empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {
                $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd') . ' 00:00:00';
                $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd') . ' 23:59:59';
                $between = [$paramsTMP['desde'], $paramsTMP['hasta']];
            }
        }

        if (isset($paramsTMP['idcreatedat']) && !empty($paramsTMP['idcreatedat'])) {
            $param['citamedica.id_created_at'] = $paramsTMP['idcreatedat'];
        }

        $dataCreated = $citamedica->grid($param, $between, '', '', 'citamedica.created_at', 'ASC', [4, 5, 6], false, [], false, false, 'citamedica.created_at', '', true, false, false, '', '', [], [], [], false, [18, 19]);

        return $this->crearRespuesta(['citas' => $dataCreated], 200);
    }

    public function controldiario(Request $request, $enterprise)
    {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $citamedica = new citamedica();
        $horariomedico = new horariomedico();

        $idempresa = $empresa->idempresa($enterprise);

        $param = [];
        $param['citamedica.idempresa'] = $idempresa;
        if (isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])) {
            $param['citamedica.idsede'] = $paramsTMP['idsede'];
        }

        $param2 = [];
        $param2['venta.idempresa'] = $idempresa;
        if (isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])) {
            $param2['citamedica.idsede'] = $paramsTMP['idsede'];
        }

        $between = [];
        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta'])) {
            if (!empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {
                $between = [$this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd') . ' 00:00:00', $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd') . ' 23:59:59'];
                $between2 = [$this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd'), $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd')];
            }
        }

        //Importante el ordenamiento para obtener el rango de fecha a consultar, el horario del medico.
        $data = $citamedica->controldiario($param, $param2, $between, $between2, [4, 5, 6], [18, 19]);
        $data = $this->ordenarMultidimension($data, 'fechacita', SORT_ASC);

        //dd($data);
        if (!empty($data)) {
            $param = [];
            $param['horariomedico.idempresa'] = $idempresa;
            $param['perfil.idsuperperfil'] = 3;
            $between = [$this->formatFecha($data[0]->fechacita, 'yyyy-mm-dd'), $this->formatFecha($data[count($data) - 1]->fechacita, 'yyyy-mm-dd')];

            $matrizhorario = $horariomedico->grid($param, $between);
            foreach ($matrizhorario as $row) {
                $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
                $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
                $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            }

            foreach ($data as $row) {
                $fechaIF = $this->fechaInicioFin($row->fechacita, $row->inicio, $row->fin);
                $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
                $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

                $row->idhorariomedico = '';
                $row->hsedeabrev = '';
                $row->hfecha = '';
                $row->hinicio = '';
                $row->hfin = '';
                foreach ($matrizhorario as $rowh) {
                    if ($rowh->idmedico === $row->idmedico && $rowh->idsede === $row->idsede && $row->start_s >= $rowh->start_s && $row->end_s <= $rowh->end_s) {
                        $row->idhorariomedico = $rowh->idhorariomedico;
                        $row->hsedeabrev = $rowh->sedeabrev;
                        $row->hfecha = $rowh->fecha;
                        $row->hinicio = $rowh->inicio;
                        $row->hfin = $rowh->fin;
                        break;
                    }
                }
                if (empty($row->idhorariomedico)) {

                }
                unset($row->start_s, $row->end_s);
            }
        }

        $matrizTMPMed = [];
        foreach ($data as $row) {

            if (!isset($matrizTMPMed[$row->fecha]) || !isset($matrizTMPMed[$row->fecha][$row->identidad]) || !isset($matrizTMPMed[$row->fecha][$row->identidad][$row->idhorariomedico]) || !isset($matrizTMPMed[$row->fecha][$row->identidad][$row->idhorariomedico][$row->idmedico])) {
                $matrizTMPMed[$row->fecha][$row->identidad][$row->idhorariomedico][$row->idmedico]['fecha'] = $row->fecha;
                //$matrizTMPMed[$row->fecha][$row->identidad][$row->idsede][$row->idmedico]['sedenombre'] = $row->sedenombre;
                $matrizTMPMed[$row->fecha][$row->identidad][$row->idhorariomedico][$row->idmedico]['identidad'] = $row->identidad;
                $matrizTMPMed[$row->fecha][$row->identidad][$row->idhorariomedico][$row->idmedico]['idmedico'] = $row->idmedico;
                $matrizTMPMed[$row->fecha][$row->identidad][$row->idhorariomedico][$row->idmedico]['personal'] = $row->personal;
                $matrizTMPMed[$row->fecha][$row->identidad][$row->idhorariomedico][$row->idmedico]['medico'] = $row->nombremedico;
                $matrizTMPMed[$row->fecha][$row->identidad][$row->idhorariomedico][$row->idmedico]['cantcmpagada'] = 0;
                $matrizTMPMed[$row->fecha][$row->identidad][$row->idhorariomedico][$row->idmedico]['cantprespagada'] = 0;
                $matrizTMPMed[$row->fecha][$row->identidad][$row->idhorariomedico][$row->idmedico]['cantreserva'] = 0;
                $matrizTMPMed[$row->fecha][$row->identidad][$row->idhorariomedico][$row->idmedico]['idhorariomedico'] = $row->idhorariomedico;
                $matrizTMPMed[$row->fecha][$row->identidad][$row->idhorariomedico][$row->idmedico]['hsedeabrev'] = $row->hsedeabrev;
                $matrizTMPMed[$row->fecha][$row->identidad][$row->idhorariomedico][$row->idmedico]['hfecha'] = $row->hfecha;
                $matrizTMPMed[$row->fecha][$row->identidad][$row->idhorariomedico][$row->idmedico]['hinicio'] = $row->hinicio;
                $matrizTMPMed[$row->fecha][$row->identidad][$row->idhorariomedico][$row->idmedico]['hfin'] = $row->hfin;
            }

            if ($row->tipo === 'venta') {
                $matrizTMPMed[$row->fecha][$row->identidad][$row->idhorariomedico][$row->idmedico]['cantcmpagada'] += 1;
                $matrizTMPMed[$row->fecha][$row->identidad][$row->idhorariomedico][$row->idmedico]['cantprespagada'] += !empty($row->presupuesto) ? 1 : 0;
            }

            if ($row->tipo === 'reserva') {
                $matrizTMPMed[$row->fecha][$row->identidad][$row->idhorariomedico][$row->idmedico]['cantreserva'] += 1;
            }
        }

        $data = [];
        foreach ($matrizTMPMed as $fila1) {
            foreach ($fila1 as $fila2) {
                foreach ($fila2 as $fila3) {
                    foreach ($fila3 as $row) {
                        $data[] = $row;
                    }
                }
            }
        }

        $data = $this->ordenarMultidimension($data, 'fecha', SORT_ASC, 'personal', SORT_ASC);
        /* END TEMPORALMED */

        return $this->crearRespuesta($data, 200);
    }

    public function dashboardmedicodetail(Request $request, $enterprise)
    {
        $paramsTMP = $request->all();

        $empresa = new empresa();
        $citamedica = new citamedica();
        $horariomedico = new horariomedico();
        $idempresa = $empresa->idempresa($enterprise);

        //Obtencion de rango de tiempo
        $param = [];
        $param2 = [];
        $param['citamedica.idempresa'] = $idempresa;

        if (isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])) {
            $param['citamedica.idsede'] = $paramsTMP['idsede'];
            $param2['horariomedico.idsede'] = $paramsTMP['idsede'];
        }

        $between = [];
        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta'])) {
            if (!empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {
                $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
                $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
                $between = [$paramsTMP['desde'], $paramsTMP['hasta']];
            }
        }

        $opcion = true;
        $disponibilidad = [];

        $param2['horariomedico.idempresa'] = $idempresa;
        $param2['perfil.idsuperperfil'] = 3;

        $matrizhorario = $horariomedico->grid($param2, $between);

        foreach ($matrizhorario as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            if ($opcion) {
                /* Obtiene las horas y lo multiplica por el factor
                 * Anado 60 s porque es equivalente a 1 min. y obtengo por ejemplo 4 horas
                 * FACTOR 7.5 = 30 CONSULTAS en 4 HORAS.
                 * 7.5 consultas por hora.
                 */
                $row->disponibles = ceil((($row->end_s + 60 - $row->start_s) / 3600) * 7.5);
                $disponibilidad[$row->idhorariomedico]['disponibilidad'] = isset($disponibilidad[$row->idhorariomedico]['disponibilidad']) ? ($disponibilidad[$row->idhorariomedico]['disponibilidad'] + $row->disponibles) : $row->disponibles;
                $disponibilidad[$row->idhorariomedico]['agendada'] = 0;
                $disponibilidad[$row->idhorariomedico]['pagado'] = 0;
                $disponibilidad[$row->idhorariomedico]['atendido'] = 0;
            }
        }

        if (!$opcion) {
            // $sedehorario = $empresa->sedehorarios(1);
            // $interconsultas = $this->configurarInterconsultas($matrizhorario, $this->horaaSegundos($sedehorario->tiempoconsultamedica), $this->horaaSegundos($sedehorario->tiempointerconsulta));
            // foreach ($interconsultas as $row) {
            //     $disponibilidad[$row['idhorariomedico']]['disponibilidad'] = isset($disponibilidad[$row['idhorariomedico']]['disponibilidad']) ? ($disponibilidad[$row['idhorariomedico']]['disponibilidad'] + 1) : 1;
            //     $disponibilidad[$row['idhorariomedico']]['agendada'] = 0;
            //     $disponibilidad[$row['idhorariomedico']]['pagado'] = 0;
            //     $disponibilidad[$row['idhorariomedico']]['atendido'] = 0;
            // }
        }

        $sinHorario = [];
        $dataCita = $citamedica->grid($param, $between, '', '', '', '', [4, 5, 6, 48]);
        foreach ($dataCita as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            $idhorariomedico = '';
            foreach ($matrizhorario as $rowh) {
                if ($rowh->idmedico === $row->idmedico && $rowh->idsede === $row->idsede && $row->start_s >= $rowh->start_s && $row->end_s <= $rowh->end_s) {
                    $idhorariomedico = $rowh->idhorariomedico;
                    break;
                }
            }

            //4:pendiente, 5:confirmada, 6:atendida, 7:cancelada
            $row->idestado = ($row->idestado === 4 || $row->idestado === 5) ? 5 : $row->idestado;

            if (empty($idhorariomedico)) {
                if (!isset($sinHorario[$row->idmedico])) {

                    $sinHorario[$row->idmedico]['identidad'] = $row->idmedico;
                    $sinHorario[$row->idmedico]['entidad'] = $row->medico;
                    $sinHorario[$row->idmedico]['disponibilidad'] = 0;
                    $sinHorario[$row->idmedico]['agendada'] = 0;
                    $sinHorario[$row->idmedico]['atendido'] = 0;
                    $sinHorario[$row->idmedico]['pagado'] = 0;
                }

                $sinHorario[$row->idmedico]['agendada'] = $sinHorario[$row->idmedico]['agendada'] + 1;
                if ($row->idestado === 6) {
                    $sinHorario[$row->idmedico]['atendido'] = $sinHorario[$row->idmedico]['atendido'] + 1;
                }

                if ($row->idestadopago === 71) {
                    $sinHorario[$row->idmedico]['pagado'] = $sinHorario[$row->idmedico]['pagado'] + 1;
                }

            } else {
                $disponibilidad[$idhorariomedico]['agendada'] = $disponibilidad[$idhorariomedico]['agendada'] + 1;
                if ($row->idestado === 6) {
                    $disponibilidad[$idhorariomedico]['atendido'] = $disponibilidad[$idhorariomedico]['atendido'] + 1;
                }

                if ($row->idestadopago === 71) {
                    $disponibilidad[$idhorariomedico]['pagado'] = $disponibilidad[$idhorariomedico]['pagado'] + 1;
                }

            }
        }

        $data = [];
        foreach ($matrizhorario as $row) {
            $temp = array(
                'fecha' => $row->fecha,
                'inicio' => $row->inicio,
                'fin' => $row->fin,
                'identidad' => $row->idmedico,
                'entidad' => $row->entidad,
                'nombresede' => $row->nombresede,
                'disponibilidad' => $disponibilidad[$row->idhorariomedico]['disponibilidad'],
                'agendada' => $disponibilidad[$row->idhorariomedico]['agendada'],
                'atendido' => $disponibilidad[$row->idhorariomedico]['atendido'],
                'pagado' => $disponibilidad[$row->idhorariomedico]['pagado'],
                'idsede' => $row->idsede,
            );
            $data[] = $temp;
        }

        if (!empty($sinHorario)) {
            foreach ($sinHorario as $row) {
                $temp = array(
                    'fecha' => null,
                    'inicio' => null,
                    'fin' => null,
                    'identidad' => $row['identidad'],
                    'entidad' => $row['entidad'],
                    'nombresede' => null,
                    'disponibilidad' => $row['disponibilidad'],
                    'agendada' => $row['agendada'],
                    'atendido' => $row['atendido'],
                    'pagado' => $row['pagado'],
                    'idsede' => null,
                );
                $data[] = $temp;
            }
        }

        foreach ($data as $ind => $row) {
            $resta = $row['disponibilidad'] - $row['agendada'];
            $data[$ind]['libre'] = $resta > 0 ? $resta : 0;
        }

        $data = $this->ordenarMultidimension($data, 'identidad', SORT_DESC);

        if (isset($paramsTMP['formato']) && !empty($paramsTMP['formato'])) {

            if (in_array($paramsTMP['formato'], ['xls', 'xlsx'])) {

                return Excel::download(new DataExport($data), 'Reporte_' . date('Y-m-d H:i:s') . '.xlsx');
            }
        } else {
            return $this->crearRespuesta($data, 200);
        }

    }

    public function index(Request $request, $enterprise)
    {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $citamedica = new citamedica();
        $horariomedico = new horariomedico();
        $idempresa = $empresa->idempresa($enterprise);

        $param = [];
        $param['citamedica.idempresa'] = $idempresa;

        if (isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])) {
            $param['citamedica.idsede'] = $paramsTMP['idsede'];
        }

        $between = [];
        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta']) && !empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {
            $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
            $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
            $between = [$paramsTMP['desde'], $paramsTMP['hasta']];
        }

        $betweenHour = [];
        if (isset($paramsTMP['inicio']) && isset($paramsTMP['fin'])) {
            if (!empty($paramsTMP['inicio']) && !empty($paramsTMP['fin'])) {
                $betweenHour = [$paramsTMP['inicio'], $paramsTMP['fin']];
            }
        }
        
        // Validacion 45 por COVID
        if (isset($paramsTMP['formato']) && 
            !empty($paramsTMP['formato']) &&  
            in_array($paramsTMP['formato'], ['xls', 'xlsx']) &&
            $this->objTtoken->myperfilid !== 1 && 
            $this->objTtoken->my !== 28874 // Maribel
        ) {

            $fechaMaxima = strtotime('-45 day', strtotime(date('Y-m-d')));
            $fechausuario = strtotime($paramsTMP['desde']);

            if ($fechausuario <= $fechaMaxima) {
                return response()->json('ACCESO DENEGADO: Solo puedes descargar ultimo 45 dias.', 200);
            }
        }

        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'citamedica.fecha';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'DESC';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        }

        if (isset($paramsTMP['idestado']) && !empty($paramsTMP['idestado'])) {
            $param['citamedica.idestado'] = $paramsTMP['idestado'];
        }

        if (isset($paramsTMP['idpaciente']) && !empty($paramsTMP['idpaciente'])) {
            $param['citamedica.idpaciente'] = $paramsTMP['idpaciente'];
        }

        if (isset($paramsTMP['idmedico']) && !empty($paramsTMP['idmedico'])) {
            $param['citamedica.idmedico'] = $paramsTMP['idmedico'];
        }

        if (isset($paramsTMP['idcreatedat']) && !empty($paramsTMP['idcreatedat'])) {
            $param['citamedica.id_created_at'] = $paramsTMP['idcreatedat'];
        }

        if (isset($paramsTMP['idllamada']) && !empty($paramsTMP['idllamada'])) {
            $param['post.idllamada'] = $paramsTMP['idllamada'];
        }

        $whereInMed = [];
        if (isset($paramsTMP['inMedico']) && !empty($paramsTMP['inMedico'])) {
            $whereInMed = explode(',', $paramsTMP['inMedico']);
        } else {
            $paramsTMP['inMedico'] = [];
        }

        $whereIn = [];
        if (isset($paramsTMP['inEstado']) && !empty($paramsTMP['inEstado'])) {
            $whereIn = explode(',', $paramsTMP['inEstado']);
        }

        $notExists = false;
        if (isset($paramsTMP['notexists']) && !empty($paramsTMP['notexists'])) {
            $notExists = (boolean) $paramsTMP['notexists'];
        }

        $seguimiento = '';
        if (isset($paramsTMP['seguimiento'])) {
            $seguimiento = $paramsTMP['seguimiento'];
        }

        $datahorario = [];
        $meddisponibles = [];
        if (isset($paramsTMP['idperfil']) && !empty($paramsTMP['idperfil'])) {

            $param2 = array();
            $param2['horariomedico.idempresa'] = $idempresa;
            $param2['horariomedico.idsede'] = $paramsTMP['idsede'];
            $param2['perfil.idsuperperfil'] = $paramsTMP['idperfil'];
            $datahorario = $horariomedico->grid($param2, $between, $whereInMed);
            $meddisponibles = $horariomedico->grid($param2, $between);
        }

        $pendiente = false;
        $pagado = false;
        if (isset($paramsTMP['estadopago'])) {
            if ($paramsTMP['estadopago'] === '0') //Pendiente
            {
                $pendiente = true;
            }

            if ($paramsTMP['estadopago'] === '1') //Pagado
            {
                $pagado = true;
            }

        }

        $cobro = true; //Persona quien cobro consulta
        $presupuesto = false; //Si se pago todo/acuenta el presupuesto de la consulta
        if (isset($paramsTMP['presupuesto']) && $paramsTMP['presupuesto'] === '1') {
            $presupuesto = true;
        }

        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';

        $cmpagada = false;
        $fieldbetween = 'citamedica.fecha';
        if (isset($paramsTMP['tipofecha']) && $paramsTMP['tipofecha'] === 'venta') {
            $fieldbetween = 'venta.fechaventa';
            $cmpagada = true;
            $orderName = 'venta.fechaventa';
            $orderSort = 'ASC';

            $cobro = false; //Sino sale violation: 1066 Not unique table/alias
        }

        $citasdeleted = false;
        if (isset($paramsTMP['citasdeleted']) && $paramsTMP['citasdeleted'] === '1') {
            $citasdeleted = true;
        }

        $solocitasdeleted = false;
        if (isset($paramsTMP['solocitasdeleted']) && $paramsTMP['solocitasdeleted'] === '1') {
            $solocitasdeleted = true;
        }

        $distrito = false;
        if (isset($paramsTMP['distrito']) && $paramsTMP['distrito'] === '1') {
            $distrito = true;
        }

        $datacita = $citamedica->grid($param, $between, $like, $pageSize, $orderName, $orderSort, $whereIn, $notExists, $whereInMed, $pendiente, $pagado, $fieldbetween, $betweenHour, $cobro, $presupuesto, $cmpagada, $seguimiento, '', [], [], [], false, [], '', $citasdeleted, '', $solocitasdeleted, $distrito);

        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $datacita->total();
            $datacita = $datacita->items();
        }

        if (isset($paramsTMP['trata']) && $paramsTMP['trata'] === '1' && !empty($datacita)) {
            $datacita = $this->devolverTratamientos($datacita);
        }

        if (isset($paramsTMP['formato']) && !empty($paramsTMP['formato'])) {

            if (in_array($paramsTMP['formato'], ['xls', 'xlsx'])) {

                $whereEvaIn = [];
                $whereGrupotraIn = [];
                $whereIdcitamedicaIn = [];
                foreach ($datacita as $index => $row) {
                    $whereIdcitamedicaIn[] = $row->idcitamedica;

                    $TF = isset($row->TF) ? ('2:' . $row->TF . ',') : null;
                    $AC = isset($row->AC) ? ('3:' . $row->AC . ',') : null;
                    $QT = isset($row->QT) ? ('4:' . $row->QT . ',') : null;
                    $OCH = isset($row->OCH) ? ('5:' . $row->OCH . ',') : null;
                    $ESP = isset($row->ESP) ? ('6:' . $row->ESP . ',') : null;
                    $BL = isset($row->BL) ? ('11:' . $row->BL . ',') : null;
                    $BMG = isset($row->BMG) ? ('17:' . $row->BMG . ',') : null;

                    $pack = $TF . $AC . $QT . $OCH . $ESP . $BL . $BMG;
                    $pack = $pack ? substr($pack, 0, -1) : null;

                    if (!empty($pack) && !in_array($pack, $whereGrupotraIn)) {
                        $whereGrupotraIn[] = $pack;
                    }

                    if (!in_array($row->eva, $whereEvaIn)) {
                        $whereEvaIn[] = $row->eva;
                    }

                    $datacita[$index]->grupotra = $pack;
                    $datacita[$index]->grupodx = null;
                    $datacita[$index]->nombregrupodx = null;

                }

                //Diagnositicos
                $whereGrupodxIn = [];
                $diagnosticosmedicos = [];
                if (!empty($whereIdcitamedicaIn)) {
                    $datadiagnosticos = $citamedica->diagnosticomedico(['citamedica.idempresa' => $idempresa], '', [], $whereIdcitamedicaIn);
                    foreach ($datadiagnosticos as $row) {

                        $diagnosticosmedicos[$row->idcitamedica]['iddiagnostico'][] = $row->iddiagnostico;
                        $diagnosticosmedicos[$row->idcitamedica]['nombre'][] = $row->nombre;
                    }

                    foreach ($diagnosticosmedicos as $index => $row) {
                        asort($row['iddiagnostico']); //Ordenamiento de menor a mayor
                        $cadenadx = implode(",", $row['iddiagnostico']);
                        $cadenadxnombre = implode(" || ", $row['nombre']);

                        if (!empty($cadenadx) && !in_array($cadenadx, $whereGrupodxIn)) {
                            $whereGrupodxIn[] = $cadenadx;
                        }

                        foreach ($datacita as $idcitamedica => $row2) {
                            if ($index === $row2->idcitamedica) {
                                $datacita[$idcitamedica]->grupodx = $cadenadx;
                                $datacita[$idcitamedica]->nombregrupodx = $cadenadxnombre;
                                break;
                            }
                        }
                    }
                }

                $dxdata = \DB::table('dxtratamiento')
                    ->select('dxtratamiento.iddxtratamiento', 'dxtratamiento.grupodx', 'dxtratamiento.grupotra', 'dxtratamiento.eva', 'dxtratamiento.aprobacion', 'dxtratamiento.fechaaprobacion', 'dxtratamiento.updated_at')
                    ->whereNull('dxtratamiento.deleted')
                    ->whereIn('dxtratamiento.grupodx', $whereGrupodxIn)
                    ->whereIn('dxtratamiento.grupotra', $whereGrupotraIn)
                    ->whereIn('dxtratamiento.eva', $whereEvaIn)
                // ->distinct()
                    ->get()->all();

                //Ventadet
                $dataventadet = \DB::table('ventadet')
                    ->select('ventadet.idcitamedica', 'ventadet.idventa', 'ventadet.total')
                    ->whereNull('ventadet.deleted')
                    ->whereIn('ventadet.idcitamedica', $whereIdcitamedicaIn)
                    ->get()->all();

                //CitaterapeuticaLog
                $datacitamedicalog = \DB::table('citamedicalog')
                    ->select('citamedicalog.idcitamedica', 'citamedicalog.descripcion', 'citamedicalog.created_at')
                    ->whereIn('citamedicalog.idcitamedica', $whereIdcitamedicaIn)
                    ->get()->all();

                $data = array();
                $i = 0;
                foreach ($datacita as $row) {
                    // dd($row);
                    $data[$i] = array(
                        'SEDE' => $row->sedenombre,
                        'FECHA RESERVA' => $row->createdat,
                        'RESERVACION' => $row->created,
                        'MEDICO' => $row->medico,
                        'FECHA CITA' => $row->fecha,
                        'HORA CITA' => $row->inicio,
                        'PACIENTE' => $row->paciente,
                        'N°HC' => $row->hc,
                        'CELULAR' => $row->celular,
                        'TIPO' => $row->nombretipo,
                        'ATENCION' => $row->nombreatencion,
                        'REFERENCIA' => $row->nombrereferencia,
                        'ESTADO PAGO' => $row->idestadopago === 71 ? 'Pagado' : 'Por pagar',
                        'ESTADO CITA' => $row->estadocita,
                        'CICLO' => $row->idcicloatencion,
                        'SEGURO PLAN' => isset($row->nombreaseguradoraplan) ? $row->nombreaseguradoraplan : '',
                        'DEDUCIBLE' => isset($row->deducible) ? $row->deducible : '',
                        'COASEGURO' => isset($row->coaseguro) ? $row->coaseguro : '',
                        'HORA COUNTER' => $row->horaespera,
                        'FECHA PAGO' => $row->fechaventa,
                        'HORA PAGO' => $row->horaventa,
                        'CAJA' => $row->personal,
                        'HORA ATENDIDO' => $row->horaatencion,
                        'DIAGNOSTICO' => $row->diagnostico,
                        'INICIO TRAT.' => (isset($row->primert) && !empty($row->primert)) ? 'Si' : 'No',
                        'EVA' => $row->eva,
                        // 'GRUPOTRA' => $row->grupotra,
                        // 'GRUPODX' => $row->grupodx,
                        'DXDIAGNOSTICOS' => $row->nombregrupodx,
                        'DXAPROBACION' => null,
                        'DXFECHAAPROBACION' => null,
                        'DXREVISION' => null,
                        'DXFECHAREVISION' => null,
                    );

                    $logcantidad = 0;
                    $logcambios = '';
                    foreach ($datacitamedicalog as $row2) {

                        if ($row2->idcitamedica === $row->idcitamedica) {
                            $logcantidad++;
                            $logcambios .= $logcantidad . '.-' . $row2->descripcion . '(' . $row2->created_at . ') ';
                        }
                    }
                    $data[$i]['LOG-CANTIDAD'] = $logcantidad;
                    $data[$i]['LOG-CAMBIOS'] = $logcambios;

                    //Asignacion de COSTO DE CITAMEDICA
                    $data[$i]['COSTOCM'] = null;
                    if ($row->idestadopago === 71) {
                        foreach ($dataventadet as $row2) {
                            /*La comparacion de idventa no es necesario, hasta que se implemente Orden compra */
                            if ($row2->idcitamedica === $row->idcitamedica && $row2->idventa === $row->idventa) {
                                $data[$i]['COSTOCM'] = $row2->total;
                                break;
                            }
                        }
                    }

                    //Asignacion de PACK de tratamientos
                    $TF = isset($row->TF) && !empty($row->TF) ? ($row->TF . ' TF + ') : null;
                    $AC = isset($row->AC) && !empty($row->AC) ? ($row->AC . ' AC + ') : null;
                    $QT = isset($row->QT) && !empty($row->QT) ? ($row->QT . ' QT + ') : null;
                    $OCH = isset($row->OCH) && !empty($row->OCH) ? ($row->OCH . ' OCH + ') : null;
                    $ESP = isset($row->ESP) && !empty($row->ESP) ? ($row->ESP . ' ESP + ') : null;
                    $BL = isset($row->BL) && !empty($row->BL) ? ($row->BL . ' BL + ') : null;
                    $BMG = isset($row->BMG) && !empty($row->BMG) ? ($row->BMG . ' BMG + ') : null;
                    $AGUJA = isset($row->AGUJA) && !empty($row->AGUJA) ? ($row->AGUJA . ' AGUJA + ') : null;
                    $OTROS = isset($row->OTROS) && !empty($row->OTROS) ? ($row->OTROS . ' OTROS + ') : null;

                    $pack = $TF . $AC . $QT . $OCH . $ESP . $BL . $BMG . $AGUJA . $OTROS;
                    $pack = $pack ? substr($pack, 0, -2) : null;

                    $data[$i]['PACK DE TTO.'] = $pack;

                    //Asignacion de columnas de tratamientos
                    if (isset($paramsTMP['trata']) && $paramsTMP['trata'] === '1') {
                        $gruposProducto = ['idproducto', array(2 => 'TF', 3 => 'AC', 4 => 'QT', 5 => 'OCH', 6 => 'ESP', 11 => 'BL', 17 => 'BMG', 23 => 'AGUJA', '*' => 'OTROS')];
                        foreach ($gruposProducto[1] as $val) {
                            $data[$i][$val] = $row->$val;
                        }
                    }

                    //Asignacion de columnas(dxtratamiento) 'aprobacion',  'fechaaprobacion',
                    foreach ($dxdata as $row2) {
                        $fechaaprobacion = null;
                        $updated_at = null;

                        if (isset($row2->fechaaprobacion)) {
                            $fechaaprobacion = $this->formatFecha($row2->fechaaprobacion);
                        }

                        if (!empty($row2->updated_at)) {
                            $updated_at = $this->formatFecha(substr($row2->updated_at, 0, 10));
                        }

                        if ($row2->grupodx === $row->grupodx && $row2->grupotra === $row->grupotra && $row2->eva === $row->eva) {
                            $data[$i]['DXAPROBACION'] = $row2->aprobacion === '1' ? 'Si' : '';
                            $data[$i]['DXFECHAAPROBACION'] = $fechaaprobacion;
                            $data[$i]['DXREVISION'] = !empty($updated_at) ? 'Si' : '';
                            $data[$i]['DXFECHAREVISION'] = $updated_at;
                            break;
                        }
                    }

                    $data[$i]['NACIMIENTO_PACIENTE'] = $row->fechanacimiento;
                    $data[$i]['EDAD_DIA_CITA'] = $row->edaddiacita;
                    $data[$i]['ELIMINADO'] = $row->deleted === '1' ? 'Si' : 'No';

                    if (isset($paramsTMP['solocitasdeleted']) && $paramsTMP['solocitasdeleted'] === '1') {
                        $data[$i]['ELIMINADO_POR'] = $row->personaleliminacion;
                    }

                    if (isset($paramsTMP['distrito']) && $paramsTMP['distrito'] === '1') {
                        $data[$i]['DISTRITO'] = $row->distrito;
                    }

                    $i++;
                }
                
                return Excel::download(new DataExport($data), 'Reporte_' . date('Y-m-d H:i:s') . '.xlsx');
            }
        } else {
            return $this->crearRespuesta(['horarios' => $datahorario, 'citas' => $datacita, 'disponibles' => $meddisponibles], 200, $total);
        }
    }

    public function indexLight(Request $request, $enterprise)
    {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $citamedica = new citamedica();
        $idempresa = $empresa->idempresa($enterprise);

        $param = [];
        $param['citamedica.idempresa'] = $idempresa;

        if (isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])) {
            $param['citamedica.idsede'] = $paramsTMP['idsede'];
        }

        $between = [];
        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta']) && !empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {
            $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
            $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
            $between = [$paramsTMP['desde'], $paramsTMP['hasta']];
        }

        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'citamedica.fecha';
            $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'DESC';
            $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;
        } 
        
        $like = !empty($paramsTMP['likeentidad']) ? trim($paramsTMP['likeentidad']) : '';

        $datacita = $citamedica->gridLight($param, $between, $like, $pageSize, $orderName, $orderSort);

        $total = '';
        if (isset($paramsTMP['pageSize']) && !empty($paramsTMP['pageSize'])) {
            $total = $datacita->total();
            $datacita = $datacita->items();
        } 

        return $this->crearRespuesta($datacita, 200, $total);
    }

    private function devolverTratamientos($datacita)
    {

        $whereIdcitamedicaIn = array();
        $whereIdcicloatencionIn = array();
        foreach ($datacita as $row) {
            $whereIdcitamedicaIn[] = $row->idcitamedica;
            if ($row->idcicloatencion) {
                $whereIdcicloatencionIn[] = $row->idcicloatencion;
            }

        }

        //Autorizacion valida('1') de Fisioterapia(2)
        // , 'cicloautorizacion.principal' => '1'
        $coaseguos = \DB::table('cicloautorizacion')
            ->select('cicloautorizacion.idcicloatencion', 'aseguradora.nombre as nombreaseguradora', 'cicloautorizacion.deducible',
                'cicloautorizacion.coaseguro', 'aseguradoraplan.nombre as nombreaseguradoraplan')
            ->join('aseguradora', 'cicloautorizacion.idaseguradora', '=', 'aseguradora.idaseguradora')
            ->leftJoin('aseguradoraplan', 'cicloautorizacion.idaseguradoraplan', '=', 'aseguradoraplan.idaseguradoraplan')
            ->where(array('cicloautorizacion.idproducto' => 2))
            ->whereIn('cicloautorizacion.idcicloatencion', $whereIdcicloatencionIn)
            ->whereNull('cicloautorizacion.deleted')
            ->get()->all();

        $productos = \DB::table('tratamientomedico')
            ->select('tratamientomedico.idcitamedica', 'tratamientomedico.cantidad', 'tratamientomedico.idproducto', 'tratamientomedico.parentcantidad')
            ->whereIn('tratamientomedico.idcitamedica', $whereIdcitamedicaIn)
            ->whereNull('tratamientomedico.deleted')
            ->get()->all();

        foreach ($productos as $row) {
            if (!empty($row->parentcantidad)) {
                $row->cantidad = $row->cantidad * $row->parentcantidad;
            }

        }

        $gruposProducto = ['idproducto', array(2 => 'TF', 3 => 'AC', 4 => 'QT', 5 => 'OCH', 6 => 'ESP', 11 => 'BL', 17 => 'BMG', 23 => 'AGUJA', '*' => 'OTROS')];
        $quiebre = array('idcitamedica' => 'idcitamedica');
        $datatratxterapista = $this->agruparPorColumna($productos, '', $quiebre, '', $gruposProducto);

        $data = array();
        foreach ($datatratxterapista as $row) {
            if (!isset($data[$row['idquiebre']])) {
                foreach ($gruposProducto[1] as $val) {
                    $data[$row['idquiebre']][$val] = null;
                }
            }
            $data[$row['idquiebre']][$gruposProducto[1][$row['idgrupo']]] = $row['cantidad'] > 0 ? $row['cantidad'] : '';
        }

        foreach ($datacita as $row) {
            foreach ($gruposProducto[1] as $val) {
                $row->$val = null;
                if (isset($data[$row->idcitamedica])) {
                    $row->$val = $data[$row->idcitamedica][$val];
                }
            }

            //Añadir coaseguro de FISIOTERAPIA
            $tmpcoa = null;
            foreach ($coaseguos as $val) {
                if ($val->idcicloatencion === $row->idcicloatencion) {
                    $tmpcoa = $val;
                    break;
                }
            }

            $row->nombreaseguradora = $tmpcoa ? $tmpcoa->nombreaseguradora : null;
            $row->nombreaseguradoraplan = $tmpcoa ? $tmpcoa->nombreaseguradoraplan : null;
            $row->deducible = $tmpcoa ? $tmpcoa->deducible : null;
            $row->coaseguro = $tmpcoa ? $tmpcoa->coaseguro : null;
        }

        return $datacita;
    }

    public function show(Request $request, $enterprise, $id)
    {
        $objCitamedica = new citamedica();
        $horariomedico = new horariomedico();
        $producto = new producto(); 
        $cicloatencion = new cicloatencion();

        $empresa = empresa::where('url', '=', $enterprise)->first();

        $idempresa = $empresa->idempresa;
        $citamedica = $objCitamedica->citamedica($id);
        $paramsTMP = $request->all(); 

        if ($citamedica) {

            if ($citamedica->idestado === 6) { //Cita Atentido

                $listcombox = [];
                if (isset($paramsTMP['others']) && $paramsTMP['others'] === '1') {
                    $listcombox = array(
                        'diagnosticosmedico' => $objCitamedica->diagnosticomedico(['citamedica.idcitamedica' => $id]),
                        'tratamientosmedicos' => $objCitamedica->tratamientomedico(['citamedica.idcitamedica' => $id]), 
                        'existciclocita' => !empty($citamedica->idcicloatencion) ? true : false
                    );
                }

                if (isset($paramsTMP['informes']) && $paramsTMP['informes'] === '1') {
                    $informes = $objCitamedica->informes(['citamedica.idcitamedica' => $id]);
                    $informes = $this->ordenarMultidimension($informes, 'idinforme', SORT_DESC);
                    $listcombox['informes'] = $informes;
                }

            } else {

                $listcombox = null;
                if (isset($paramsTMP['others']) && $paramsTMP['others'] === '1') {
                    /* Medicos disponibles en horario */
                    $param2 = array();
                    $param2['horariomedico.idsede'] = $citamedica->idsede;
                    $param2['horariomedico.fecha'] = $this->formatFecha($citamedica->fecha, 'yyyy-mm-dd');
                    $param2['perfil.idsuperperfil'] = 3; //tipo medico

                    $medicos = $horariomedico->medicosPorHorario($param2, $citamedica->inicio, $citamedica->fin);
                    /* Medicos disponibles en horario */

                    $fieldsProducto = ['producto.idproducto', 'producto.nombre', 'producto.categoria'];
                    $listcombox = array(
                        'estadoscita' => $empresa->estadodocumentos(2),
                        'motivoscancelacion' => $empresa->estadodocumentos(3),
                        'referencias' => $empresa->referenciasmedicas($idempresa),
                        'atenciones' => $empresa->estadodocumentos(5),
                        'tipos' => $empresa->estadodocumentos(13),
                        'medicos' => $medicos,
                        // 'horasf' => $empresa->horas($empresa->laborinicio, $empresa->laborfin, 15, 14),
                        'horasf' => $empresa->horas($empresa->laborinicio, $empresa->laborfin, 20, 19),
                        //'existciclocita' => !empty($ciclocitamedica)?true:false,
                        'existciclocita' => !empty($citamedica->idcicloatencion) ? true : false,
                        'servicios' => $producto->grid(['producto.idempresa' => $idempresa, 'producto.idtipoproducto' => 2, 'producto.tratamientoind' => '1'], '', '', '', '', $fieldsProducto), //22:Acuenta 24:Informe medico 25:ConsultaTera 1: ConsultaM 
                        'aseguradorasplanes' => $empresa->aseguradorasplanes($idempresa, true, true),
                    );
                }
            }

            return $this->crearRespuesta($citamedica, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Cita médica no encotrado', 404);
    }

    public function referencia(Request $request, $enterprise, $id)
    {

        $objCitamedica = new citamedica();
        $empresa = new empresa();

        $idempresa = $empresa->idempresa($enterprise);

        $param = array(
            'idempresa' => $idempresa,
            'idpaciente' => $id,
        );

        $citamedica = $objCitamedica->referencia($param);

        return $this->crearRespuesta($citamedica, 200);
    }

    public function tratamientosmedicos(Request $request, $enterprise)
    {

        $objCitamedica = new citamedica();
        $empresa = new empresa();

        $paramsTMP = $request->all();

        $idempresa = $empresa->idempresa($enterprise);

        $param = array();
        $param['citamedica.idempresa'] = $idempresa;
        $param['citamedica.idestado'] = 6; // 4:pendiente, 5:confirmada, 6:atendida, 7:cancelada

        if (isset($paramsTMP['idsede']) && !empty($paramsTMP['idsede'])) {
            $param['citamedica.idsede'] = $paramsTMP['idsede'];
        }

        $between = [];
        if (isset($paramsTMP['desde']) && isset($paramsTMP['hasta'])) {
            if (!empty($paramsTMP['desde']) && !empty($paramsTMP['hasta'])) {
                $paramsTMP['desde'] = $this->formatFecha($paramsTMP['desde'], 'yyyy-mm-dd');
                $paramsTMP['hasta'] = $this->formatFecha($paramsTMP['hasta'], 'yyyy-mm-dd');
                $between = [$paramsTMP['desde'], $paramsTMP['hasta']];
            }
        }

        $data = $objCitamedica->tratamientomedico($param, false, false, true, $between, true);

        return $this->crearRespuesta($data, 200);
    }

    public function showconsulta(Request $request, $enterprise, $id)
    {

        $objCitamedica = new citamedica();
        $producto = new producto();
        $grupodx = new grupodx();
        $entidad = new entidad();
        $empresa = new empresa();
        $cicloatencion = new cicloatencion();

        $citamedica = $objCitamedica->citamedica($id);
        $tmpempresa = $empresa->empresa(['url' => $enterprise]);
        $idempresa = $tmpempresa->idempresa;

        if ($citamedica) { 

            $tratamientos = $objCitamedica->tratamientomedico(['citamedica.idcitamedica' => $id]);            
            $informes = $objCitamedica->informes(['citamedica.idcitamedica' => $id]);
            $informes = $this->ordenarMultidimension($informes, 'idinforme', SORT_DESC);

            $fieldsProducto = ['producto.idproducto', 'producto.nombre', 'producto.categoria'];
            $servicios = $producto->grid(['producto.idempresa' => $idempresa, 'producto.idtipoproducto' => 2, 'producto.tratamientoind' => '1'], '', '', '', '', $fieldsProducto);

            $listcombox = array(
                // 'diagnosticosmedico' => $diagnosticos,
                // 'tratamientosmedicos' => $tratamientos,
                'servicios' => $servicios, // historiamedico.js
                'productosservicios' => $producto->productoServicios(['producto.idempresa' => $idempresa]), // historiamedico.js
                // 'medicos' => $entidad->entidades($param, true),
                // 'terapistas' => $terapistas,
                'examenescita' => $objCitamedica->examenescita(['citamedica.idcitamedica' => $id]), // historiamedico.js
                'examenescitaobs' => $objCitamedica->examenescitaobs(['citamedica.idcitamedica' => $id]), // historiamedico.js
                // 'especialidadescita' => $objCitamedica->especialidadescita(['citamedica.idcitamedica' => $id]),
                'antecedentemedico' => $objCitamedica->antecedentemedico(array('antecedentemedico.idcitamedica' => $id)), // historiamedico.js
                'examenfisicocita' => $objCitamedica->examenfisicocita(['citamedica.idcitamedica' => $id]), // historiamedico.js
                'informes' => $informes, // historiamedico.js
                // 'adicionalestrat' => $adicionalestrat,
            );

            if (isset($request['others'])) {
                $others = explode(',', $request['others']);

                if (in_array('gruposdx', $others)) { 

                    $diagnosticos = [];
                    if (!empty($citamedica->idcicloatencion)) {
                        $diagnosticos = $objCitamedica->diagnosticomedico(['citamedica.idcicloatencion' => $citamedica->idcicloatencion]);
                    }

                    $gruposDx = $grupodx->grid(['grupodx.idcicloatencion' => $citamedica->idcicloatencion]); //

                    foreach ($gruposDx as $row) {
                        $row->diagnosticos = [];
                        $row->tratamientos = [];

                        foreach ($diagnosticos as $diagnostico) {
                            if ($diagnostico->idgrupodx === $row->idgrupodx) {
                                $row->diagnosticos[] = $diagnostico;
                            }
                        }

                        foreach ($tratamientos as $tratamiento) {
                            if ($tratamiento->idgrupodx === $row->idgrupodx) {
                                $row->tratamientos[] = $tratamiento;
                            }
                        }
                    }

                    $listcombox['gruposdx'] = $gruposDx;
                }

                if (in_array('adjuntos', $others)) {
                    $citamedicaarchivo = new citamedicaarchivo();

                    $listcombox['adjuntos'] = $citamedicaarchivo->grid(['idcitamedica' => $id]);
                }
            }

            return $this->crearRespuesta($citamedica, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Cita médica no encotrado', 404);
    }

    public function newcitamedica(Request $request, $enterprise)
    {

        $horariomedico = new horariomedico();
        $producto = new producto(); 

        $empresa = empresa::where('url', '=', $enterprise)->first();

        $paramsTMP = $request->all();
        $idempresa = $empresa->idempresa;

        $paramsTMP['fecha'] = $this->formatFecha($paramsTMP['fecha'], 'yyyy-mm-dd');

        /* Medicos disponibles en horario */
        $param2 = array();
        $param2['horariomedico.idempresa'] = $idempresa;
        $param2['horariomedico.idsede'] = $paramsTMP['idsede'];
        $param2['horariomedico.fecha'] = $paramsTMP['fecha'];
        $param2['perfil.idsuperperfil'] = $paramsTMP['idperfil']; //tipo medico

        $inicio = '';
        $fin = '';
        if (isset($paramsTMP['inicio'])) {
            $inicio = $paramsTMP['inicio'];
        }

        if (isset($paramsTMP['fin'])) {
            $fin = $paramsTMP['fin'];
        }

        $medicos = $horariomedico->medicosPorHorario($param2, $inicio, $fin);
        /* Medicos disponibles en horario */

        $fieldsProducto = ['producto.idproducto', 'producto.nombre', 'producto.categoria'];
        $listcombox = array(
            'estadoscita' => $empresa->estadodocumentos(2),
            'motivoscancelacion' => $empresa->estadodocumentos(3),
            'referencias' => $empresa->referenciasmedicas($idempresa),
            'atenciones' => $empresa->estadodocumentos(5),
            'tipos' => $empresa->estadodocumentos(13),
            'medicos' => $medicos,            
            // 'horasf' => $empresa->horas($empresa->laborinicio, $empresa->laborfin, 15, 14),
            'horasf' => $empresa->horas($empresa->laborinicio, $empresa->laborfin, 20, 19),
            'servicios' => $producto->grid(['producto.idempresa' => $idempresa, 'producto.idtipoproducto' => 2, 'producto.tratamientoind' => '1'], '', '', '', '', $fieldsProducto), //22:Acuenta 24:Informe medico 25:ConsultaTera 1: ConsultaM 
            'aseguradorasplanes' => $empresa->aseguradorasplanes($idempresa, true, true),
        );

        return $this->crearRespuesta([], 200, '', '', $listcombox);
    }

    public function disponibilidadHora(Request $request, $enterprise)
    {
        /* Horas disponibles en horario.
         */

        $horariomedico = new horariomedico();
        $citamedica = new citamedica();

        $empresa = empresa::where('url', '=', $enterprise)->first();

        $paramsTMP = $request->all();

        $idempresa = $empresa->idempresa;
        $sedehorario = $empresa->sedehorarios($paramsTMP['idsede']);

        $param = [];
        $param['citamedica.idempresa'] = $idempresa;
        // 16.06.2020
        // $param['citamedica.idsede'] = $paramsTMP['idsede'];
        $param['citamedica.idmedico'] = $paramsTMP['idmedico'];
        $param['citamedica.fecha'] = $this->formatFecha($paramsTMP['fecha'], 'yyyy-mm-dd');

        $param2 = [];
        $param2['horariomedico.idempresa'] = $idempresa;
        $param2['horariomedico.idsede'] = $paramsTMP['idsede'];
        $param2['horariomedico.idmedico'] = $paramsTMP['idmedico'];
        $param2['horariomedico.fecha'] = $this->formatFecha($paramsTMP['fecha'], 'yyyy-mm-dd');
        $param2['perfil.idsuperperfil'] = $paramsTMP['idperfil'];

        $datacita = $citamedica->grid($param, '', '', '', '', '', [4, 5, 6]);
        $datahorario = $horariomedico->grid($param2);

        foreach ($datacita as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        }

        foreach ($datahorario as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        }

        $idpaciente = isset($paramsTMP['idpaciente']) && $paramsTMP['idpaciente'] ? (integer) $paramsTMP['idpaciente'] : null;

        // 16.06.2020
        $interconsultas = array();  
        // $interconsultas = $this->configurarInterconsultas($datahorario, $this->horaaSegundos($sedehorario->tiempoconsultamedica), $this->horaaSegundos($sedehorario->tiempointerconsulta), $datacita, $idpaciente);
 
        $obviar = null;
        if (isset($paramsTMP['inicio']) && isset($paramsTMP['fin'])) {
            $fechaIF = $this->fechaInicioFin($paramsTMP['fecha'], $paramsTMP['inicio'], $paramsTMP['fin']);
            $obviar = array(
                'inicio_s' => mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']),
                'fin_s' => mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']),
            );
        }

        $horas = $this->horasdisponibles($datahorario, $this->horaaSegundos($sedehorario->tiempoconsultamedica), $datacita, $interconsultas, $obviar);

        $horasdisp = [];
        foreach ($horas as $id => $row) {
            $horasdisp[] = array(
                'idhora' => $row['inicio'],
            );
        }
        return $this->crearRespuesta($horasdisp, 200);
    }

    public function disponibilidadMedico(Request $request, $enterprise)
    {	
        /* Horas disponibles en horario.
         */
        $horariomedico = new horariomedico();
        $citamedica = new citamedica();

        $empresa = empresa::where('url', '=', $enterprise)->first();

        $paramsTMP = $request->all();

        $idempresa = $empresa->idempresa;
        $sedehorario = $empresa->sedehorarios($paramsTMP['idsede']);

        $paramsTMP['fecha'] = $this->formatFecha($paramsTMP['fecha'], 'yyyy-mm-dd');

        // Feriados 
        $data = array(
        	'diasFeriados' => $empresa->diasferiados(['idempresa' => $idempresa], true),
        ); 

        $tiempoNohabil = $this->configurarFeriados($data, $empresa->laborinicio, $empresa->laborfin);
        
        /* Medicos disponibles en horario */
        $param = array();
        $param['horariomedico.idempresa'] = $idempresa;
        $param['horariomedico.idsede'] = $paramsTMP['idsede'];
        $param['horariomedico.fecha'] = $paramsTMP['fecha'];
        $param['perfil.idsuperperfil'] = $paramsTMP['idperfil']; //tipo medico

        $medicos = $horariomedico->medicosPorHorario($param);
        /* Medicos disponibles en horario */

        $disponibilidad = array();
        foreach ($medicos as $medico) {
            $param = [];
            $param['citamedica.idempresa'] = $idempresa;
            // 16.06.2020
            // $param['citamedica.idsede'] = $paramsTMP['idsede'];
            $param['citamedica.idmedico'] = $medico->idmedico;
            $param['citamedica.fecha'] = $paramsTMP['fecha'];

            $param2 = [];
            $param2['horariomedico.idempresa'] = $idempresa;
            $param2['horariomedico.idsede'] = $paramsTMP['idsede'];
            $param2['horariomedico.idmedico'] = $medico->idmedico;
            $param2['horariomedico.fecha'] = $paramsTMP['fecha'];
            $param2['perfil.idsuperperfil'] = $paramsTMP['idperfil']; //tipo medico

            $datacita = $citamedica->grid($param, '', '', '', '', '', [4, 5, 6]);
            $datahorario = $horariomedico->grid($param2);

            foreach ($datacita as $row) {
                $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
                $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
                $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            }

            foreach ($datahorario as $row) {
                $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
                $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
                $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            }
            

            // 28.04.2020: OSI ONLINE NO PERMITE INTERCONSULTAS - DEHABILITADO TEMPORALMENTE
            $interconsultas = array();
            // $interconsultas = $this->configurarInterconsultas($datahorario, $this->horaaSegundos($sedehorario->tiempoconsultamedica), $this->horaaSegundos($sedehorario->tiempointerconsulta), $datacita);
 			
            $horas = $this->horasdisponibles($datahorario, $this->horaaSegundos($sedehorario->tiempoconsultamedica), $datacita, $interconsultas); 
 
            $horasdisp = [];
            foreach ($horas as $row) {
            	$valido = true; 

				if ($this->validarFeriado($tiempoNohabil, $row['start_s'], $row['end_s'])) {
		            $valido = false;
		        }

            	if ($valido) {
	                $horasdisp[] = array(
	                    'idmedico' => $medico->idmedico,
	                    'nombre' => $medico->entidad,
	                    'inicio' => $row['inicio'],
	                    'fin' => $row['fin'],
	                );
            	}
            }

            $disponibilidad[] = array('idmedico' => $medico->idmedico, 'nombre' => $medico->entidad, 'horas' => $horasdisp);
        }

        return $this->crearRespuesta($disponibilidad, 200);
    }

    public function updatetratamiento(Request $request, $enterprise, $id)
    {

        $citamedica = citamedica::find($id);
        $objPresupuesto = new presupuesto();
        $empresa = new empresa();
        $dxtratamiento = new dxtratamiento();

        $request = $request->all();
        $idempresa = $empresa->idempresa($enterprise);

        //VALIDACIONES

        /* 1.- Validar que ciclo no se encuentre cerrado. Caso ya tenga un idcicloatencion
         */
        $cicloatencion = cicloatencion::find($citamedica->idcicloatencion);

        if (isset($cicloatencion->idestado) && $cicloatencion->idestado === 21) {
            //6.2.2019 Temporal para que pruebe AMAC la nueva cita mèdica del
            return $this->crearRespuesta('Ciclo de atención se encuentra cerrado. No puede editarse.', [200, 'info']);
        }

        /* 2.- Validar que cita se haya generado un comprobante de venta.
         */
        if ($citamedica->idestadopago === 72 && $idempresa === 1) {
            return $this->crearRespuesta('Cita médica no está pagado. No se puede editar.', [200, 'info']);
        }

        if (isset($request['diagnosticomedico'])) {
            $dataDiagnosticomedico = [];
            foreach ($request['diagnosticomedico'] as $row) {

                $dataDiagnosticomedico[] = [
                    'idcitamedica' => $id,
                    'iddiagnostico' => $row['iddiagnostico'],
                    'idgrupodx' => isset($row['idgrupodx']) ? $row['idgrupodx'] : null,
                    'idzona' => isset($row['idzona']) ? $row['idzona'] : null,
                ];

            }
        }

        if (isset($request['especialidadescita'])) {
            $dataEspecialidadescita = [];
            foreach ($request['especialidadescita'] as $row) {
                $dataEspecialidadescita[] = ['idcitamedica' => $id, 'idespecialidad' => $row['idespecialidad']];
            }
        }

        if (isset($request['examenescita'])) {
            $dataExamenescita = [];
            foreach ($request['examenescita'] as $row) {
                $dataExamenescita[] = ['idcitamedica' => $id, 'idexamen' => $row['idexamen'], 'descripcion' => $row['descripcion']];
            }
        }

        if (isset($request['examenescitaobs'])) {
            $dataExamenescitaobs = [];
            foreach ($request['examenescitaobs'] as $row) {
                $dataExamenescitaobs[] = ['idcitamedica' => $id, 'idexamen' => $row['idexamen'], 'descripcion' => $row['descripcion']];
            }
        } 

        if (isset($request['examenfisicocita'])) {
            $dataAnamnesis = [];
            foreach ($request['examenfisicocita'] as $row) {
                $dataAnamnesis[] = [
                    'idcitamedica' => $id,
                    'zona' => $row['zona'],
                    'rom' => $row['rom'],
                    'muscular' => $row['muscular'],
                    'funcional' => $row['funcional'],
                    'eva' => $row['eva'],
                    'idgrupodx' => isset($row['idgrupodx']) ? $row['idgrupodx'] : null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'id_created_at' => $this->objTtoken->my,
                ];
            }
        }

        if (isset($request['antecedentemedico'])) {
            $dataAntecedentemedico = [];
            foreach ($request['antecedentemedico'] as $row) {
                $dataAntecedentemedico[] = ['idcitamedica' => $id, 'descripcion' => $row['descripcion']];
            }
        }

        $requestCitamedica = [];
        $requestCitamedica['motivo'] = $request['citamedica']['motivo'];
        $requestCitamedica['antecedente'] = $request['citamedica']['antecedente'];
        $requestCitamedica['nota'] = $request['citamedica']['nota'];
        $requestCitamedica['notaespecialidad'] = $request['citamedica']['notaespecialidad'];
        $requestCitamedica['notaexamen'] = $request['citamedica']['notaexamen'];
        $requestCitamedica['notamedicamento'] = $request['citamedica']['notamedicamento'];
        $requestCitamedica['idestado'] = 6; //Atendido
        $requestCitamedica['eva'] = $request['citamedica']['eva'];
        $requestCitamedica['adjunto'] = $request['citamedica']['adjunto'];

        $requestCitamedica['notaexamenfis'] = isset($request['citamedica']['notaexamenfis']) ? $request['citamedica']['notaexamenfis'] : '';
        $requestCitamedica['observacion'] = isset($request['citamedica']['observacion']) ? $request['citamedica']['observacion'] : '';
        $requestCitamedica['descansodias'] = isset($request['citamedica']['descansodias']) ? $request['citamedica']['descansodias'] : '';

        if (isset($request['citamedica']['altamedica'])) //Temporal hasta que borren historial
        {
            $requestCitamedica['altamedica'] = $request['citamedica']['altamedica'];
        }

        if (isset($request['citamedica']['sugerencia'])) //Temporal hasta que borren historial
        {
            $requestCitamedica['sugerencia'] = $request['citamedica']['sugerencia'];
        }

        //Borrar estos IF
        if (isset($request['citamedica']['fuerzamuscular'])) //Temporal hasta que borren historial
        {
            $requestCitamedica['fuerzamuscular'] = $request['citamedica']['fuerzamuscular'];
        }

        if (isset($request['citamedica']['pruebafuncional'])) //Temporal hasta que borren historial
        {
            $requestCitamedica['pruebafuncional'] = $request['citamedica']['pruebafuncional'];
        }

        if (isset($request['citamedica']['altamedicacomentario'])) //Temporal hasta que borren historial
        {
            $requestCitamedica['altamedicacomentario'] = $request['citamedica']['altamedicacomentario'];
        }

        if (isset($request['citamedica']['rom'])) //Temporal hasta que borren historial
        {
            $requestCitamedica['rom'] = $request['citamedica']['rom'];
        }

        if (isset($request['citamedica']['romvalor'])) //Temporal hasta que borren historial
        {
            $requestCitamedica['romvalor'] = $request['citamedica']['romvalor'];
        }

        if (isset($request['citamedica']['firmamedico'])) //Temporal hasta que borren historial
        {
            $requestCitamedica['firmamedico'] = $request['citamedica']['firmamedico'];
        }

        if (isset($request['citamedica']['enfermedadtiempo'])) //Temporal hasta que borren historial
        {
            $requestCitamedica['enfermedadtiempo'] = $request['citamedica']['enfermedadtiempo'];
        }

        if (isset($request['citamedica']['fvpc'])) //Temporal hasta que borren historial
        {
            $requestCitamedica['fvpc'] = $request['citamedica']['fvpc'];
        }

        if (isset($request['citamedica']['fvfc'])) //Temporal hasta que borren historial
        {
            $requestCitamedica['fvfc'] = $request['citamedica']['fvfc'];
        }

        if (isset($request['citamedica']['fvpeso'])) //Temporal hasta que borren historial
        {
            $requestCitamedica['fvpeso'] = $request['citamedica']['fvpeso'];
        }

        if (isset($request['citamedica']['fvtalla'])) //Temporal hasta que borren historial
        {
            $requestCitamedica['fvtalla'] = $request['citamedica']['fvtalla'];
        }

        if (isset($request['citamedica']['frecuencia'])) //Temporal hasta que borren historial
        {
            $requestCitamedica['frecuencia'] = $request['citamedica']['frecuencia'];
        }

        if (isset($request['citamedica']['descansodesde'])) { //Temporal hasta que borren historial
            $requestCitamedica['descansodesde'] = $this->formatFecha($request['citamedica']['descansodesde'], 'yyyy-mm-dd');
        }

        if (isset($request['citamedica']['descansohasta'])) { //Temporal hasta que borren historial
            $requestCitamedica['descansohasta'] = $this->formatFecha($request['citamedica']['descansohasta'], 'yyyy-mm-dd');
        }

        if (isset($request['citamedica']['enfermedad'])) {
            $requestCitamedica['enfermedad'] = $this->formatFecha($request['citamedica']['enfermedad'], 'yyyy-mm-dd');
        }

        /* Campos auditores */
        $requestCitamedica['updated_at'] = date('Y-m-d H:i:s');
        $requestCitamedica['id_updated_at'] = $this->objTtoken->my;
        /* Campos auditores */

        if (empty($citamedica->idpersonalatencion)) {
            $requestCitamedica['idpersonalatencion'] = $this->objTtoken->my;
            $requestCitamedica['fechaatencion'] = date('Y-m-d');
            $requestCitamedica['horaatencion'] = date('H:i:s');
        }

        //Grabar 1er diagnostico
        if (isset($dataDiagnosticomedico)) {
            $requestCitamedica['iddiagnostico'] = !empty($dataDiagnosticomedico) ? $dataDiagnosticomedico[0]['iddiagnostico'] : null;
        }

        if (isset($request['tratamientomedico'])) {

            /* Obtener array de 'Insert', 'Update', 'Deleted' con campos auditores para tabla 'tratamientomedico'.
             */
            $tratamientomedicoInsert = [];
            $tratamientomedicoUpdate = [];
            $tratamientomedicoDelete = [];

            $dataTratamiento = $citamedica->tratamientomedico(['citamedica.idcitamedica' => $id]);

            foreach ($request['tratamientomedico'] as $indice => $row) {

                $nuevo = true;
                $update = false;
                foreach ($dataTratamiento as $indice => $row2) {
                    if (isset($row['idtratamientomedico']) && $row['idtratamientomedico'] === $row2->idtratamientomedico) {
                        $nuevo = false;
                        $update = true;
                        unset($dataTratamiento[$indice]);
                        break 1;
                    }
                }

                $tmp = array(
                    'idmedico' => isset($row['idmedico']) ? $row['idmedico'] : null,
                    'parent' => isset($row['parent']) ? $row['parent'] : null,
                    'parentcantidad' => isset($row['parentcantidad']) ? $row['parentcantidad'] : null,
                    'idproducto' => $row['idproducto'],
                    'cantidad' => $row['cantidad'],
                    'idgrupodx' => isset($row['idgrupodx']) ? $row['idgrupodx'] : null,
                );

                if ($nuevo) {
                    $tmp['idcitamedica'] = $id;
                    $tmp['created_at'] = date('Y-m-d H:i:s');
                    $tmp['id_created_at'] = $this->objTtoken->my;

                    $tratamientomedicoInsert[] = $tmp;
                }

                if ($update) {
                    $tmp['updated_at'] = date('Y-m-d H:i:s');
                    $tmp['id_updated_at'] = $this->objTtoken->my;

                    $tratamientomedicoUpdate[] = array(
                        'data' => $tmp,
                        'where' => ['idtratamientomedico' => $row['idtratamientomedico']],
                    );
                }
            }

            if (!empty($dataTratamiento)) {
                $tmp = array();
                $tmp['deleted'] = '1';
                $tmp['deleted_at'] = date('Y-m-d H:i:s');
                $tmp['id_deleted_at'] = $this->objTtoken->my;

                foreach ($dataTratamiento as $row) {
                    $tratamientomedicoDelete[] = array(
                        'data' => $tmp,
                        'where' => array(
                            'idtratamientomedico' => $row->idtratamientomedico,
                        ),
                        'idproducto' => $row->idproducto,
                        'nombreproducto' => $row->nombreproducto,
                    );
                }
            }
        }

        $presupuesto = presupuesto::where('idcicloatencion', '=', $citamedica->idcicloatencion)->first();
        if (isset($request['tratamientomedico']) && $presupuesto && $tratamientomedicoDelete) {

            $inValid = false;
            $nombreproducto = '';
            $presupuestodet = $objPresupuesto->presupuestodet($presupuesto->idpresupuesto);

            foreach ($presupuestodet as $row) {
                foreach ($tratamientomedicoDelete as $fila) {
                    if ($row->cantefectivo > 0 && $row->idproducto === $fila['idproducto'] && $fila['idproducto'] !== 23) {
                        $inValid = true;
                        $nombreproducto = $fila['nombreproducto'];
                        break 2;
                    }
                }
            }

            if ($inValid) {
                return $this->crearRespuesta('No puede eliminarse "' . $nombreproducto . '" porque tiene sesiones efectuadas.', [200, 'info']);
            }
        }

        //return $this->crearRespuesta('No se puede editar.', [200, 'info'], '', '', [$request['tratamientomedico'], $tratamientomedicoInsert, $tratamientomedicoUpdate]);

        // return $this->crearRespuesta('D', [200, 'info'], '', '', $requestCitamedica);
        if ($citamedica) {

            $citamedica->fill($requestCitamedica);

            \DB::beginTransaction();
            try {

                //Graba en Citamedica, Diagnosticomedico.
                if (isset($request['diagnosticomedico'])) {
                    $citamedica->GrabarDiagnosticomedico($dataDiagnosticomedico, $id);
                }

                if (isset($request['especialidadescita'])) {
                    $citamedica->GrabarEspecialidadescita($dataEspecialidadescita, $id);
                }

                if (isset($request['examenescita'])) {
                    $citamedica->GrabarExamenescita($dataExamenescita, $id);
                }

                if (isset($request['examenescitaobs'])) {
                    $citamedica->GrabarExamenescitaobs($dataExamenescitaobs, $id);
                }

                if (isset($request['examenfisicocita'])) {
                    $citamedica->GrabarAnamnesis($dataAnamnesis, $id);
                }

                if (isset($request['antecedentemedico'])) {
                    $citamedica->GrabarAntecedentemedico($dataAntecedentemedico, $id);
                }

                $citamedica->save();

                if (isset($request['tratamientomedico'])) {

                    //$dataProducto = $tratamientomedicoInsert;

                    if (!empty($tratamientomedicoInsert)) {
                        \DB::table('tratamientomedico')->insert($tratamientomedicoInsert);
                    }

                    foreach ($tratamientomedicoUpdate as $fila) {
                        \DB::table('tratamientomedico')->where($fila['where'])->update($fila['data']);
                        //$dataProducto[] = $fila['data'];
                    }

                    foreach ($tratamientomedicoDelete as $fila) {
                        \DB::table('tratamientomedico')->where($fila['where'])->update($fila['data']);
                    }

                    if (!empty($tratamientomedicoInsert) && !empty($requestCitamedica['eva']) && !empty($dataDiagnosticomedico) && empty($tratamientomedicoUpdate) && empty($tratamientomedicoDelete)) {

                        $idproductos = $this->ordenarMultidimension($request['tratamientomedico'], 'idproducto', SORT_ASC);
                        $iddiagnosticos = $this->ordenarMultidimension($dataDiagnosticomedico, 'iddiagnostico', SORT_ASC);

                        $guarda = $dxtratamiento->GrabarDxtratamiento($idproductos, $iddiagnosticos, $idempresa, $requestCitamedica['eva'], $this->objTtoken->my);
                        //return $this->crearRespuesta('Cita ', [200, 'info'], '', '', $guarda);
                    }
                }

                //return $this->crearRespuesta('No.', [200, 'info']);

                /* Actualizar presupuesto
                 * 04.05.2016
                 */
                if ($presupuesto) {
                    //SI Existe presupuesto.

                    if (isset($request['presupuesto']['tipotarifa'])) {
                        $presupuesto->tipotarifa = $request['presupuesto']['tipotarifa'];
                        $paramPresupuesto['tipotarifa'] = $request['presupuesto']['tipotarifa'];
                    }

                    // $unificado = $this->obtenerTratamientoUnificado($citamedica->idcicloatencion, $presupuesto);
                    // return $this->crearRespuesta('UNIFICADO', [200, 'info'], '', '', $unificado);

                    $unificado = $this->compararTratamientoUnificadoVSPresupuesto($citamedica->idcicloatencion, $presupuesto, $request);

                    if (!empty($unificado['tratamientomedicoInsert'])) {
                        \DB::table('presupuestodet')->insert($unificado['tratamientomedicoInsert']);
                    }

                    foreach ($unificado['tratamientomedicoUpdate'] as $fila) {
                        \DB::table('presupuestodet')->where($fila['where'])->update($fila['data']);
                    }
                    foreach ($unificado['tratamientomedicoDelete'] as $fila) {
                        \DB::table('presupuestodet')->where($fila['where'])->update($fila['data']);
                    }

                    /* Campos auditores */
                    $paramPresupuesto['created_at'] = date('Y-m-d H:i:s');
                    $paramPresupuesto['id_created_at'] = $this->objTtoken->my;
                    /* Campos auditores */

                    $paramPresupuesto['regular'] = $unificado['regular'];
                    $paramPresupuesto['tarjeta'] = $unificado['tarjeta'];
                    $paramPresupuesto['efectivo'] = $unificado['efectivo'];
                    $paramPresupuesto['montoefectuado'] = $unificado['montoefectuado'];

                    //Estos tres se actualizan en NUEVA VENTA
                    $total = ($presupuesto->tipotarifa === 1 ? $unificado['regular'] : ($presupuesto->tipotarifa === 2 ? $unificado['tarjeta'] : $unificado['efectivo']));
                    $paramPresupuesto['idestadopago'] = $presupuesto->montopago >= $total && $total > 0 ? 68 : ($presupuesto->montopago > 0 && $presupuesto->montopago < $total ? 67 : 66);

                    $paramPresupuesto['total'] = $total;

                    $presupuesto->fill($paramPresupuesto);
                    $presupuesto->save();
                } else {

                    if ($idempresa === 1) {
                        //NO Existe presupuesto, crearse Nuevo.
                        $presupuesto = new presupuesto();

                        $paramPresupuesto = [];
                        $paramPresupuesto['idempresa'] = $citamedica->idempresa;
                        $paramPresupuesto['idsede'] = $citamedica->idsede;
                        $paramPresupuesto['idcliente'] = $citamedica->idpaciente;
                        $paramPresupuesto['idcicloatencion'] = $citamedica->idcicloatencion;
                        $paramPresupuesto['idestado'] = 29; //29: Aperturado 30: Terminado  31: Cancelado
                        $paramPresupuesto['fecha'] = date('Y-m-d');
                        $paramPresupuesto['tipotarifa'] = 3; //1:Regular 2:Tarjeta 3:Efectivo
                        $paramPresupuesto['regular'] = 0;
                        $paramPresupuesto['tarjeta'] = 0;
                        $paramPresupuesto['efectivo'] = 0;
                        $paramPresupuesto['idestadopago'] = 66;
                        $paramPresupuesto['total'] = 0;
                        /* Campos auditores */
                        $paramPresupuesto['created_at'] = date('Y-m-d H:i:s');
                        $paramPresupuesto['id_created_at'] = $this->objTtoken->my;
                        /* Campos auditores */

                        $presupuesto->fill($paramPresupuesto);
                        $presupuesto->save();

                        $unificado = $this->obtenerTratamientoUnificado($citamedica->idcicloatencion, $presupuesto);
                        $paramPresupuesto = $unificado['paramPresupuesto'];
                        $paramPresupuestodet = $unificado['paramPresupuestodet'];
                        // return $this->crearRespuesta('No efectuadas.', [200, 'info'], '', '', $paramPresupuesto);

                        \DB::table('presupuestodet')->insert($paramPresupuestodet);
                        \DB::table('presupuesto')->where(['idpresupuesto' => $presupuesto->idpresupuesto])->update($paramPresupuesto);
                    }
                }

                if ($idempresa === 1) {
                    //LogPresupuesto
                    $presupuesto->grabarLog($presupuesto->idpresupuesto, $this->objTtoken->my);
                }
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Cita médica ha sido editado. ', 200, '', '', $tratamientomedicoInsert);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una cita médica', 404);
    }

    public function updatediagnostico(Request $request, $enterprise)
    {

        $citamedica = new citamedica();

        $request = $request->all();

        //VALIDACIONES

        // $idcitas = [];
        $dataDiagnosticomedico = [];
        foreach ($request['diagnosticomedico'] as $row) {
            $dataDiagnosticomedico[$row['idcitamedica']][] = ['idcitamedica' => $row['idcitamedica'], 'iddiagnostico' => $row['iddiagnostico'], 'idzona' => $row['idzona']];

            // if(!in_array($row['idcitamedica'], $idcitas)) {
            //     $idcitas[] = $row['idcitamedica'];
            // }
        }

        \DB::beginTransaction();
        try {
            foreach ($dataDiagnosticomedico as $idcita => $val) {
                $citamedica->GrabarDiagnosticomedico($val, $idcita);
            }
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta('Diasnósticos ha sido editado. ', 200, '', '', $dataDiagnosticomedico);

    }

    private function compararTratamientoUnificadoVSPresupuesto($idcicloatencion, $presupuesto, $request = [])
    {

        $unificado = $this->obtenerTratamientoUnificado($idcicloatencion, $presupuesto);

        $paramPresupuestodet = $unificado['paramPresupuestodet'];
        $regular = 0;
        $tarjeta = 0;
        $efectivo = 0;
        $montoefectuado = 0;
        /* Obtener array de 'Insert', 'Update', 'Deleted' con campos auditores para tabla 'tratamientomedico'.
         */
        $tratamientomedicoInsert = [];
        $tratamientomedicoUpdate = [];
        $tratamientomedicoDelete = [];

        $dataPresupuestodet = $presupuesto->presupuestodet($presupuesto->idpresupuesto);

        foreach ($paramPresupuestodet as $indice => $row) { // Tratamiento
            $nuevo = true;
            $update = false;
            foreach ($dataPresupuestodet as $indice => $row2) { // Presupuesto
                if ($row['idproducto'] === $row2->idproducto) {
                    $nuevo = false;
                    $update = true;

                    unset($dataPresupuestodet[$indice]);
                    break 1;
                }
            }

            $tmp = array(
                'idproducto' => $row['idproducto'],
                'cantmedico' => $row['cantmedico'],
                'cantcliente' => $row['cantmedico'],
                'tipoprecio' => $row['tipoprecio'],
            );

            if ($nuevo) {
                $tmp['idpresupuesto'] = $presupuesto->idpresupuesto;
                $tmp['cantpagada'] = null;
                $tmp['cantefectivo'] = null;
                $tmp['preciounitregular'] = $row['preciounitregular'];
                $tmp['totalregular'] = $row['totalregular'];
                $tmp['preciounittarjeta'] = $row['preciounittarjeta'];
                $tmp['totaltarjeta'] = $row['totaltarjeta'];
                $tmp['preciounitefectivo'] = $row['preciounitefectivo'];
                $tmp['totalefectivo'] = $row['totalefectivo'];
                $tmp['observacion'] = $row['observacion'];

                $tmp['created_at'] = date('Y-m-d H:i:s');
                $tmp['id_created_at'] = $this->objTtoken->my;

                $tratamientomedicoInsert[] = $tmp;

                $totalregular = $row['totalregular'];
                $totaltarjeta = $row['totaltarjeta'];
                $totalefectivo = $row['totalefectivo'];
            }

            if ($update) {
                /* Ahora se recibe: cantcliente, idproducto, preciounitregular, preciounittarjeta, preciounitefectivo */
                if (isset($request['presupuestodet'])) {
                    foreach ($request['presupuestodet'] as $fila) {
                        if ($fila['idproducto'] === $row['idproducto']) {
                            // $row2->cantcliente = $fila['cantcliente'];
                            // $row2->cantmedico = $fila['cantcliente'];
                            $row2->preciounitregular = $fila['preciounitregular'];
                            $row2->preciounittarjeta = $fila['preciounittarjeta'];
                            $row2->preciounitefectivo = $fila['preciounitefectivo'];
                            $row2->observacion = $fila['observacion'];
                            break;
                        }
                    }
                }

                $tmp['cantpagada'] = $row2->cantpagada;
                $tmp['cantefectivo'] = $row2->cantefectivo;
                $tmp['preciounitregular'] = $row2->preciounitregular;
                $tmp['totalregular'] = $row2->preciounitregular * $row['cantmedico']; // $row2->totalregular;
                $tmp['preciounittarjeta'] = $row2->preciounittarjeta;
                $tmp['totaltarjeta'] = $row2->preciounittarjeta * $row['cantmedico']; // $row2->totaltarjeta;
                $tmp['preciounitefectivo'] = $row2->preciounitefectivo;
                $tmp['totalefectivo'] = $row2->preciounitefectivo * $row['cantmedico']; //$row2->totalefectivo;
                $tmp['observacion'] = $row2->observacion;
                $tmp['updated_at'] = date('Y-m-d H:i:s');
                $tmp['id_updated_at'] = $this->objTtoken->my;

                $tratamientomedicoUpdate[] = array(
                    'data' => $tmp,
                    'where' => ['idpresupuestodet' => $row2->idpresupuestodet],
                );

                $preciounit = $presupuesto->tipotarifa === 1 ? $row2->preciounitregular : ($presupuesto->tipotarifa === 2 ? $row2->preciounittarjeta : $row2->preciounitefectivo);

                $montoefectuado = $montoefectuado + $preciounit * $row2->cantefectivo;

                $totalregular = $row2->preciounitregular * $row['cantmedico'];
                $totaltarjeta = $row2->preciounittarjeta * $row['cantmedico'];
                $totalefectivo = $row2->preciounitefectivo * $row['cantmedico'];
            }

            $regular = $regular + $totalregular;
            $tarjeta = $tarjeta + $totaltarjeta;
            $efectivo = $efectivo + $totalefectivo;
        }

        if (!empty($dataPresupuestodet)) {
            $tmp = array();
            $tmp['deleted'] = '1';
            $tmp['deleted_at'] = date('Y-m-d H:i:s');
            $tmp['id_deleted_at'] = $this->objTtoken->my;

            foreach ($dataPresupuestodet as $row) {
                $tratamientomedicoDelete[] = array(
                    'data' => $tmp,
                    'where' => array(
                        'idpresupuestodet' => $row->idpresupuestodet,
                    ),
                );
            }
        }
        
        $return = array(
            'tratamientomedicoInsert' => $tratamientomedicoInsert,
            'tratamientomedicoUpdate' => $tratamientomedicoUpdate,
            'tratamientomedicoDelete' => $tratamientomedicoDelete,
            'regular' => $regular,
            'tarjeta' => $tarjeta,
            'efectivo' => $efectivo,
            'montoefectuado' => $montoefectuado,
            'montopago' => 0,
        );

        return $return;
    }

    private function obtenerTratamientoUnificado($idcicloatencion, $presupuesto)
    {
        /* Junta los tratamientos de las N consultas, perteneciente del mismo ciclo de atencion
         * Osea sumas las cantidades indicadas por los medicos. "$citamedica->tratamientomedico()"
         */

        $citamedica = new citamedica();
        $cicloatencion = new cicloatencion();

        $tratamientos = $citamedica->tratamientomedicoAdicionales($idcicloatencion);
        $autorizaciones = $cicloatencion->cicloAutorizaciones(['cicloatencion.idcicloatencion' => $idcicloatencion]);

        $regular = 0;
        $tarjeta = 0;
        $efectivo = 0;
        $paramPresupuestodet = [];
        foreach ($tratamientos as $row) {

            //Precio de tarifario, no tiene autorizacion.
            $array['tipo'] = 3;

            if ($row['idtipoproducto'] === 1) {
                //Producto
                $preciounitregular = $row['valorventa'];
                $totalregular = round($row['valorventa'] * $row['cantidad'], 2);
                $preciounittarjeta = $row['valorventa'];
                $totaltarjeta = round($row['valorventa'] * $row['cantidad'], 2);
                $preciounitefectivo = $row['valorventa'];
                $totalefectivo = round($row['valorventa'] * $row['cantidad'], 2);
            } else {
                //Servicio
                $array = $this->getAutorizacionProducto($autorizaciones, $row['idproducto']);
                switch ($array['tipo']):
            case 1:
                //Precio deducible, de autorizacion 'Valida'.
                $preciounit = 0;
                if (in_array($row['idproducto'], [2])) {
                        //Fisioterapia
                        $preciounit = $array['coaseguro'];
                } else {
                    $preciounit = $array['deducible'];
                }
                $preciounitregular = $preciounit;
                $totalregular = round($preciounit * $row['cantidad'], 2);
                $preciounittarjeta = $preciounit;
                $totaltarjeta = round($preciounit * $row['cantidad'], 2);
                $preciounitefectivo = $preciounit;
                $totalefectivo = round($preciounit * $row['cantidad'], 2);
                break;
            case 2:
                //Caso no se haya definido un precio en el tarifario, entonces punit sera "0".
                //Precio de tarifario, de autorizacion 'No valida' y seguro 'No cubierto'.
                $preciounitregular = !empty($row['sscoref']) ? $row['sscoref'] : 0;
                $totalregular = round($row['sscoref'] * $row['cantidad'], 2);
                $preciounittarjeta = !empty($row['sscocta']) ? $row['sscocta'] : 0;
                $totaltarjeta = round($row['sscocta'] * $row['cantidad'], 2);
                $preciounitefectivo = !empty($row['sscosta']) ? $row['sscosta'] : 0;
                $totalefectivo = round($row['sscosta'] * $row['cantidad'], 2);
                break;
            case 3:
                //Precio de tarifario, no tiene autorizacion.
                $preciounitregular = !empty($row['partref']) ? $row['partref'] : 0;
                $totalregular = round($row['partref'] * $row['cantidad'], 2);
                $preciounittarjeta = !empty($row['partcta']) ? $row['partcta'] : 0;
                $totaltarjeta = round($row['partcta'] * $row['cantidad'], 2);
                $preciounitefectivo = !empty($row['partsta']) ? $row['partsta'] : 0;
                $totalefectivo = round($row['partsta'] * $row['cantidad'], 2);
                break;
                endswitch;
            }

            $paramPresupuestodet[] = array(
                'idpresupuesto' => $presupuesto->idpresupuesto,
                'idproducto' => $row['idproducto'],
                'cantmedico' => $row['cantidad'],
                'cantcliente' => $row['cantidad'],
                'cantpagada' => null,
                'cantefectivo' => null,
                'tipoprecio' => $array['tipo'],
                'preciounitregular' => $preciounitregular,
                'totalregular' => $totalregular,
                'preciounittarjeta' => $preciounittarjeta,
                'totaltarjeta' => $totaltarjeta,
                'preciounitefectivo' => $preciounitefectivo,
                'totalefectivo' => $totalefectivo,
                'observacion' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'id_created_at' => $this->objTtoken->my,
            );

            $regular = $regular + $totalregular;
            $tarjeta = $tarjeta + $totaltarjeta;
            $efectivo = $efectivo + $totalefectivo;
        }

        $total = ($presupuesto->tipotarifa === 1 ? $regular : ($presupuesto->tipotarifa === 2 ? $tarjeta : $efectivo));

        $paramPresupuesto = array(
            'regular' => $regular,
            'tarjeta' => $tarjeta,
            'efectivo' => $efectivo,
            'montoefectuado' => 0,
            'montopago' => 0,
            'montocredito' => 0,
            'total' => $total,
        );

        return array('paramPresupuesto' => $paramPresupuesto, 'paramPresupuestodet' => $paramPresupuestodet);
    }

    private function getAutorizacionProducto($autorizaciones, $idproducto)
    {

        //Precio de tarifario, no tiene autorizacion.
        $return = ['tipo' => 3];

        foreach ($autorizaciones as $row) {
            /* Obtiene el "idcicloautorizacion" y "deducible" de la autorizacion 'Principal' o 'Valida'
             * idproducto: 2: El precio va a ser el deducible de "Fisioterapia"
             */

            // $row->principal === '1' &&
            if ($row->idproducto === $idproducto) {
                //Precio deducible, de autorizacion 'Valida'.
                $return = array(
                    'tipo' => 1,
                    'idcicloatencion' => $row->idcicloatencion,
                    'idcicloautorizacion' => $row->idcicloautorizacion,
                    'deducible' => $row->deducible,
                    'idcoaseguro' => $row->idcoaseguro,
                    'coaseguro' => $row->coaseguro,
                );
                break;
            }

            /* Obtiene el "idcicloautorizacion" de la autorizacion 'No valida' y seguro 'No cubierto'.
             * Al no haber fecha en una autorizacion no valida, entonces toma el ultimo de orden descendente.
             *
             * && $row->principal === '0' : Solo cuando autorizacion no es valida. Si es no cubierto estara vacio.
             */
            if (in_array($row->idaseguradoraplan, [5, 8, 13, 18, 21])) {
                //Precio de tarifario, de autorizacion 'No valida' y seguro 'No cubierto'.
                $return = array(
                    'tipo' => 2,
                    'idcicloatencion' => $row->idcicloatencion,
                    'idcicloautorizacion' => $row->idcicloautorizacion,
                );
            }
        }

        return $return;
    }

    public function updatereagendamasivo(Request $request, $enterprise)
    {

        $empresa = new empresa();
        $citamedica = new citamedica();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        $param = [];
        $param['citamedica.idempresa'] = $idempresa;
        $param['citamedica.idsede'] = $request['idsede'];
        $param['citamedica.idmedico'] = $request['idmedicoorigen'];
        $param['citamedica.fecha'] = $this->formatFecha($request['fecha'], 'yyyy-mm-dd');
        $whereIn = [4, 5];

        $datacita = $citamedica->grid($param, '', '', '', '', '', $whereIn);
        $cantidad = count($datacita);

        if (empty($cantidad)) {
            return $this->crearRespuesta('Personal no tiene citas médicas', [200, 'info']);
        }

        // return $this->crearRespuesta('Reagendas', [200, 'info'], '', '', $datacita);

        $where = array(
            'idsede' => $request['idsede'],
            'idmedico' => $request['idmedicoorigen'],
            'fecha' => $this->formatFecha($request['fecha'], 'yyyy-mm-dd'),
        );

        $update = array(
            'idmedico' => $request['idmedicodestino'],
            'updated_at' => date('Y-m-d H:i:s'),
            'id_updated_at' => $this->objTtoken->my,
        );

        \DB::table('citamedica')
            ->where($where)
            ->whereIn('citamedica.idestado', $whereIn)
            ->update($update);

        return $this->crearRespuesta($cantidad . ' citas han sido reagendadas.', 201, '', '', $update);
    }

    public function storePagarCm(Request $request, $enterprise)
    {   
        \Log::info(print_r('PASO 1: ' . date('Y-m-d H:i:s'), true));

        $empresa = new empresa();
        $cliente = new entidad();
        $citamedica = new citamedica();

        $requestAll = $request->all();

        $entidad = $cliente->entidad(array('entidad.identidad' => $requestAll['citamedica']['idpaciente'])); //entidad::find($requestAll['citamedica']['idpaciente']);
        $sede = sede::find($requestAll['citamedica']['idsede']);
        $idempresa = $empresa->idempresa($enterprise);

        // VALIDACIONES 
        if (!$this->objTtoken) {
            return $this->crearRespuesta('Su sesión a expirado, inicie sesión. No se realizó la compra.', [200, 'info']);
        }

        // 1.- Fecha y hora mayor a la actual
        $a = date('Y-m-j H:i:s');
        $b = $this->formatFecha($requestAll['citamedica']['fecha'], 'yyyy-mm-dd') . ' ' . (isset($requestAll['citamedica']['inicio']) ? $requestAll['citamedica']['inicio'] : date('H:i:s'));

        $fechahoraActual = strtotime($a);
        $fechahoraUsuario = strtotime($b);

        if ($fechahoraUsuario < $fechahoraActual) {
            return $this->crearRespuesta('Hora de cita debe ser mayor a hora actual.  No se realizó la compra.', [200, 'info']);
        }

        // 2.- Exista correlativo configurado
        if (empty($sede->idafiliado) || empty($sede->iddocumentofiscal) || empty($sede->serie)) {
            return $this->crearRespuesta('No existe comprobante de emisión configurado.  No se realizó la compra.', [200, 'info']);
        }

        $param = array(
            'documentoserie.identidad' => $sede->idafiliado,
            'documentoserie.iddocumentofiscal' => $sede->iddocumentofiscal,
            'documentoserie.serie' => $sede->serie
        );

        $documentoserie = \DB::table('documentoserie')
                ->join('documentofiscal', 'documentoserie.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal')
                ->select('documentoserie.*', 'documentofiscal.codigosunat')
                ->whereNull('documentoserie.deleted')
                ->where($param)
                ->first();

        if (empty($documentoserie))
        {
            return $this->crearRespuesta('No existe comprobante de emisión configurado.  No se realizó la compra.', [200, 'info']);
        }

        $docnumero = $documentoserie->numero + 1;

        $orderid = $documentoserie->identidad .'_'. $documentoserie->serie . '-' .$docnumero;

        $nombre = null;
        // CM OSI ONLINE
        if ($requestAll['citamedica']['idsede'] == 15) {
            $nombre = 'Teleconsulta médica orientadora';
        } else {
            $nombre = 'Consulta Evaluación Medicina Alternativa';
        }

        // CULQI
        // $SECRET_KEY = "sk_test_3T3ZESYvMPdseTTj";
        $SECRET_KEY = "sk_live_MfN4PW6PxdzVJm3y"; 
        $culqi = new Culqi(array('api_key' => $SECRET_KEY));

        // Crear Cargo a tarjeta por token
        $dataCulqi = array(
            "amount" => 5000,
            "capture" => true,
            "currency_code" => "PEN",
            "description" => "Servicio: " . $nombre,
            "email" => $requestAll['citamedica']['email'], 
            "source_id" => $requestAll['citamedica']['idtoken'],
            "metadata" => array(
                "order_id" => $orderid
            ),
            "antifraud_details" => array(
                "first_name" => $entidad->nombre,
                "last_name" => $entidad->apellidopat,
                "phone_number" => $entidad->celular,
                "address" => $entidad->direccion,
                "address_city" => $entidad->distrito,
                "country_code" => "PE"
            )
        );

        \Log::info(print_r('PASO 2: ' . date('Y-m-d H:i:s'), true));

        try {
            $charge = $culqi->Charges->create($dataCulqi);  
            \Log::info(print_r('PASO 2[try]: ' . date('Y-m-d H:i:s'), true));
        } catch (\Exception $e) {
            \Log::info(print_r('PASO 2[catch]: ' . date('Y-m-d H:i:s'), true));

            $error = json_decode($e->getMessage());

            if (isset($error->object) && $error->object === 'error') {
                \Log::info(print_r('YESxx', true));
                \Log::info(print_r($error, true)); 
                if (isset($error->user_message)) {
                    $mensaje = $error->user_message;
                } else {
                    $mensaje = $error->merchant_message;
                }
            } else {
                \Log::info(print_r('NO', true));  
                $mensaje = $e->getMessage();
            }

            \Log::info(print_r($error, true));

            return $this->crearRespuesta('Pago en linea: '.$mensaje, [200, 'info']);
        }
        \Log::info(print_r('PASO 3: ' . date('Y-m-d H:i:s'), true)); 

        // $printer = json_encode($charge);
        // \Log::info(print_r($printer, true));

        $idpaciente = $requestAll['citamedica']['idpaciente']; 
        $idsede = $requestAll['citamedica']['idsede'];

        $paramCita = $requestAll['citamedica'];
        $paramCita['idempresa'] = $idempresa;
        $paramCita['fecha'] = $this->formatFecha($requestAll['citamedica']['fecha'], 'yyyy-mm-dd');
        $paramCita['created_at'] = date('Y-m-d H:i:s');
        $paramCita['id_created_at'] = $this->objTtoken->my;
        $paramCita['idtipo'] = $this->definirTipopaciente($idempresa, $idpaciente, $idsede); 

        $total = 50;
        $subtotal = 42.37;
        $valorimpuesto = 7.63;
        

        $paramVenta = array(
            'idempresa' => $idempresa,
            'idsede' => $idsede, 
            'idafiliado' => $documentoserie->identidad,
            'iddocumentofiscal' => $documentoserie->iddocumentofiscal,
            'serie' => $documentoserie->serie,
            'serienumero' => $docnumero, 
            'idcliente' => $idpaciente, 
            'movecon' => '1', 
            'fechaventa' => date('Y-m-d'),   
            'idmoneda' => 1, // Soles
            'idestadodocumento' => 27, // Pagado
            'idmediopago' => 6, // Culqi            
            'subtotal' => $subtotal,
            'igv' => 18,
            'valorimpuesto' => $valorimpuesto,
            'total' => $total,             
            'idpaciente' => $idpaciente,
            'cpecorreo' => $requestAll['citamedica']['email'],
            'culqitkn' => $charge->source->id,
            'culqichr' => $charge->id,
            'created_at' => date('Y-m-d H:i:s'),
            'id_created_at' => $this->objTtoken->my
        );  

        // return $this->crearRespuesta('ID: ' . $requestAll['citamedica']['idtoken'], [200, 'info']);

        \DB::beginTransaction();
        try {
            \Log::info(print_r('PASO 4: ' . date('Y-m-d H:i:s'), true));
            // Tabla: venta
            $venta = venta::create($paramVenta);


            // Tabla: citamedica 
            $paramCita['idestadopago'] = 71;
            $paramCita['idventa'] = $venta->idventa;
            $citamedica = citamedica::create($paramCita);

            // Tabla: ventadet
            $dataVentadet = [array(
                'idventa' => $venta->idventa,
                'idproducto' => 1,
                'cantidad' => 1,
                'preciounit' => 50,                    
                'valorunit' => 42.372,
                'valorventa' => 42.37,
                'montototalimpuestos' => 7.63,
                'total' => 50, 
                'nombre' => $nombre,  
                'idcitamedica' => $citamedica->idcitamedica, //Segun la BD ya no es necesario.
                'created_at' => date('Y-m-d H:i:s'),
                'id_created_at' => $this->objTtoken->my
            )];
            \DB::table('ventadet')->insert($dataVentadet); 

            // Tabla: documentoserie
            \DB::table('documentoserie')
                    ->where('iddocumentoserie', $documentoserie->iddocumentoserie)
                    ->update(array(
                        'numero' => $docnumero,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'id_updated_at' => $this->objTtoken->my
                    ));

            \Log::info(print_r('PASO 5: ' . date('Y-m-d H:i:s'), true));
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        \Log::info(print_r('PASO 6: ' . date('Y-m-d H:i:s'), true));
        $ventaController = new ventaController($request);
        $fileNamePdf = $ventaController->cpeemision($enterprise, $venta->idventa, true);
        \Log::info(print_r('PASO 7: ' . date('Y-m-d H:i:s'), true));

        $respuesta = array(
            'venta' => $venta,
            'citamedica' => $citamedica,
            'filenamepdf' => $fileNamePdf,
            'user_message' => $charge->outcome->user_message
        );

        return $this->crearRespuesta($respuesta, 201);
    }

    public function storePagarTrat(Request $request, $enterprise)
    {   
        \Log::info(print_r('PASO 1: ' . date('Y-m-d H:i:s'), true));

        $empresa = new empresa();
        $cliente = new entidad();
        $citamedica = new citamedica();

        $requestAll = $request->all();
        // var param = {
        //     venta: {
        //         idsede: null,
        //         idpaciente: null,
        //         subtotal: null,
        //         valorimpuesto: null,
        //         total: null,
        //         idcicloatencion: null,
        //         email: null,
        //         amount: null,
        //         description: null,
        //         idtoken: null
        //     },
        //     ventadet: [
        //         {
        //            idproducto: null, 
        //            cantidad: null,
        //            preciounit: null,
        //            valorunit: null,
        //            valorventa: null,
        //            montototalimpuestos: null,
        //            total: null,
        //            nombreproducto: null
        //         }
        //     ]
        // };

        $entidad = $cliente->entidad(array('entidad.identidad' => $requestAll['venta']['idpaciente']));
        $sede = sede::find($requestAll['venta']['idsede']);
        $idempresa = $empresa->idempresa($enterprise);

        // VALIDACIONES 
        if (!$this->objTtoken) {
            return $this->crearRespuesta('Su sesión a expirado, inicie sesión. No se realizó la compra.', [200, 'info']);
        }

        $param = array(
            'documentoserie.identidad' => $sede->idafiliado,
            'documentoserie.iddocumentofiscal' => $sede->iddocumentofiscal,
            'documentoserie.serie' => $sede->serie
        );

        $documentoserie = \DB::table('documentoserie')
                ->join('documentofiscal', 'documentoserie.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal')
                ->select('documentoserie.*', 'documentofiscal.codigosunat')
                ->whereNull('documentoserie.deleted')
                ->where($param)
                ->first();

        if (empty($documentoserie))
        {
            return $this->crearRespuesta('No existe comprobante de emisión configurado.  No se realizó la compra.', [200, 'info']);
        }

        $docnumero = $documentoserie->numero + 1;
        $orderid = $documentoserie->identidad .'_'. $documentoserie->serie . '-' .$docnumero;

        // CULQI
        // $SECRET_KEY = "sk_test_3T3ZESYvMPdseTTj";
        $SECRET_KEY = "sk_live_MfN4PW6PxdzVJm3y"; 
        $culqi = new Culqi(array('api_key' => $SECRET_KEY));

        // Crear Cargo a tarjeta por token 
        $dataCulqi = array(
            "amount" => $requestAll['venta']['amount'],
            "capture" => true,
            "currency_code" => "PEN",
            "description" => "Servicio: " . $requestAll['venta']['description'],
            "email" => $requestAll['venta']['email'], 
            "source_id" => $requestAll['venta']['idtoken'],
            "metadata" => array(
                "order_id" => $orderid
            ),
            "antifraud_details" => array(
                "first_name" => $entidad->nombre,
                "last_name" => $entidad->apellidopat,
                "phone_number" => $entidad->celular,
                "address" => $entidad->direccion,
                "address_city" => $entidad->distrito,
                "country_code" => "PE"
            )
        );

        \Log::info(print_r('PASO 2: ' . date('Y-m-d H:i:s'), true));

        try {
            $charge = $culqi->Charges->create($dataCulqi);  
            \Log::info(print_r('PASO 2[try]: ' . date('Y-m-d H:i:s'), true));
        } catch (\Exception $e) {
            \Log::info(print_r('PASO 2[catch]: ' . date('Y-m-d H:i:s'), true));

            $error = json_decode($e->getMessage());

            if (isset($error->object) && $error->object === 'error') {
                \Log::info(print_r('YES', true));
                $mensaje = $error->user_message;
            } else {
                \Log::info(print_r('NO', true));
                $mensaje = $e->getMessage();
            }

            \Log::info(print_r($error, true));
            return $this->crearRespuesta('Pago en linea: '.$mensaje, [200, 'info']);
        }

        \Log::info(print_r('PASO 3: ' . date('Y-m-d H:i:s'), true)); 
        // \Log::info(print_r($charge, true));

        // $printer = json_encode($charge);
        // \Log::info(print_r($printer, true));

        $paramVenta = array(
            'idempresa' => $idempresa,
            'idsede' => $requestAll['venta']['idsede'],
            'idafiliado' => $documentoserie->identidad,
            'iddocumentofiscal' => $documentoserie->iddocumentofiscal,
            'serie' => $documentoserie->serie,
            'serienumero' => $docnumero,
            'idcliente' => $requestAll['venta']['idpaciente'],
            'movecon' => '1',
            'fechaventa' => date('Y-m-d'),
            'idmoneda' => 1, // Soles
            'idestadodocumento' => 27, // Pagado
            'idmediopago' => 6, // Culqi
            'subtotal' => $requestAll['venta']['subtotal'],
            'igv' => 18,
            'valorimpuesto' => $requestAll['venta']['valorimpuesto'],
            'total' => $requestAll['venta']['total'],            
            'idcicloatencion' => $requestAll['venta']['idcicloatencion'], //Eliminar este campo. Se guarda en ventadet, porque una boleta puede tener el pago de n ciclos. 
            'idpaciente' => $requestAll['venta']['idpaciente'],
            'cpecorreo' => $requestAll['venta']['email'],
            'culqitkn' => $charge->source->id,
            'culqichr' => $charge->id,
            'created_at' => date('Y-m-d H:i:s'),
            'id_created_at' => $this->objTtoken->my
        ); 

        // return $this->crearRespuesta('ID: ' . $requestAll['venta']['idtoken'], [200, 'info']);

        \DB::beginTransaction();
        try {
            \Log::info(print_r('PASO 4: ' . date('Y-m-d H:i:s'), true));
            // Tabla: venta
            $venta = venta::create($paramVenta); 

            // Tabla: ventadet
            $dataVentadet = [];
            foreach ($requestAll['ventadet'] as $value) {
                $dataVentadet[] = array(
                    'idventa' => $venta->idventa,
                    'idproducto' => $value['idproducto'],
                    'cantidad' => $value['cantidad'],
                    'preciounit' => $value['preciounit'],
                    'valorunit' => $value['valorunit'],
                    'valorventa' => $value['valorventa'],                    
                    'montototalimpuestos' => $value['montototalimpuestos'],
                    'total' => $value['total'],
                    'nombre' => $value['nombreproducto'],
                    'idcicloatencion' => $value['idcicloatencion'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'id_created_at' => $this->objTtoken->my
                );
            }

            \DB::table('ventadet')->insert($dataVentadet); 

            // Tabla: Pagar tratamiento
            $presupuesto = presupuesto::where('idcicloatencion', '=', $requestAll['venta']['idcicloatencion'])->first();
            $presupuestodet = $presupuesto->presupuestodet($presupuesto->idpresupuesto);
            $ventatotal = 0; 

            foreach ($requestAll['ventadet'] as $row) {
                $ventatotal += $row['total'];
                // Actualizar presupuestodet 
                foreach ($presupuestodet as $rowpres) {
                    if ($rowpres->idproducto === $row['idproducto']) 
                    {               
                        $cantpagada = $rowpres->cantpagada + $row['cantidad'];  
                        
                        \DB::table('presupuestodet')
                             ->where(['idpresupuestodet' => $rowpres->idpresupuestodet])
                             ->update(['cantpagada' => $cantpagada]);                                
                        break;
                    }
                }
            }

            $montopago = $presupuesto->montopago + $ventatotal;

            if ($montopago >= $presupuesto->total && $presupuesto->total > 0) {
                $idestadopago = 68;
            } else if ($montopago > 0 && $montopago < $presupuesto->total) {
                $idestadopago = 67;
            } else {
                $idestadopago = 66;
            }

            $paramPresupuesto = array(
                'updated_at' => date('Y-m-d H:i:s'),
                'id_updated_at' => $this->objTtoken->my,
                'montopago' => $montopago,
                'montocredito' => $montopago,//Temporal hasta quitar de los JS, se debe usan montopago
                'idestadopago' => $idestadopago 
            ); 

            $presupuesto->fill($paramPresupuesto);
            $presupuesto->save(); 

            // Tabla: documentoserie
            \DB::table('documentoserie')
                    ->where('iddocumentoserie', $documentoserie->iddocumentoserie)
                    ->update(array(
                        'numero' => $docnumero,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'id_updated_at' => $this->objTtoken->my
                    ));

            \Log::info(print_r('PASO 5: ' . date('Y-m-d H:i:s'), true));
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        \Log::info(print_r('PASO 6: ' . date('Y-m-d H:i:s'), true));
        $ventaController = new ventaController($request);
        $fileNamePdf = $ventaController->cpeemision($enterprise, $venta->idventa, true);
        \Log::info(print_r('PASO 7: ' . date('Y-m-d H:i:s'), true));

        $respuesta = array(
            'venta' => $venta,
            'citamedica' => $citamedica,
            'filenamepdf' => $fileNamePdf,
            'user_message' => $charge->outcome->user_message
        );

        return $this->crearRespuesta($respuesta, 201);
    }

    public function store(Request $request, $enterprise)
    {
        $empresa = new empresa();
        $citamedica = new citamedica();
        $horariomedico = new horariomedico();

        $request = $request->all();

        $entidad = entidad::find($request['citamedica']['idpaciente']);
        $sede = sede::find($request['citamedica']['idsede']);
        $idempresa = $empresa->idempresa($enterprise);

        //VALIDACIONES  

        /* 1.- Validar que cita no sea mayor a los 15 dias, no se considera las horas.
         */
        $fechaMaxima = strtotime('+15 day', strtotime(date('Y-m-d')));
        $fechausuario = strtotime($this->formatFecha($request['citamedica']['fecha'], 'yyyy-mm-dd'));
        if ($fechausuario > $fechaMaxima) {
            return $this->crearRespuesta('Fecha de cita m&eacute;dica no debe ser mayor a +15 dias', [200, 'info']);
        }

        /* 2.- Validar que fecha y hora cita sea mayor a fecha y hora actual.
         */
        $a = date('Y-m-j H:i:s');
        $b = $this->formatFecha($request['citamedica']['fecha'], 'yyyy-mm-dd') . ' ' . (isset($request['citamedica']['inicio']) ? $request['citamedica']['inicio'] : date('H:i:s'));

        $fechahoraActual = strtotime($a);
        $fechahoraUsuario = strtotime($b);

        if ($fechahoraUsuario < $fechahoraActual && isset($request['horaactual']) && $request['horaactual'] === 1) {
            return $this->crearRespuesta('Fecha y hora de cita debe ser mayor a fecha y hora actual.', [200, 'info'], '', '', array($fechahoraActual, $fechahoraUsuario, $a, $b));
        }

        if (isset($request['citamedica']['inicio']) && isset($request['citamedica']['fin'])) {
            /* 2.- Validar que cita para el medico este disponible.
             *     Validar que cita no se trate del mismo paciente.
             *     Validar que cita pueda ser una interconsulta.
             *     [4, 5, 6] Pendiente, confirmada y atendida.
             */

            $sedehorario = $empresa->sedehorarios($request['citamedica']['idsede']);

            $param = [];
            $param['citamedica.idempresa'] = $idempresa;
            $param['citamedica.idsede'] = $request['citamedica']['idsede'];
            $param['citamedica.idmedico'] = $request['citamedica']['idmedico'];
            $param['citamedica.fecha'] = $this->formatFecha($request['citamedica']['fecha'], 'yyyy-mm-dd');

            $param2 = [];
            $param2['horariomedico.idempresa'] = $idempresa;
            $param2['horariomedico.idsede'] = $request['citamedica']['idsede'];
            $param2['horariomedico.idmedico'] = $request['citamedica']['idmedico'];
            $param2['horariomedico.fecha'] = $this->formatFecha($request['citamedica']['fecha'], 'yyyy-mm-dd');

            $datacita = $citamedica->grid($param, '', '', '', '', '', [4, 5, 6]);
            $datahorario = $horariomedico->grid($param2);

            unset($param['citamedica.idmedico']);
            $datacitas = $citamedica->grid($param, '', '', '', '', '', [4, 5, 6]);

            foreach ($datacita as $row) {
                $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
                $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
                $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            }

            $opcion = true;
            $disponibilidad = 0;
            foreach ($datahorario as $row) {
                $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
                $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
                $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

                if ($opcion) {
                    /* Obtiene las horas y lo multiplica por el factor
                     * Anado 60 s porque es equivalente a 1 min. y obtengo por ejemplo 4 horas
                     * fACTOR 7.5 = 30 CONSULTAS en 4 HORAS
                     */
                    $disponibilidad = $disponibilidad + ceil((($row->end_s + 60 - $row->start_s) / 3600) * 7.5);
                }
            }

            foreach ($datacitas as $row) {
                $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
                $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
                $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            }

            $fechaIF = $this->fechaInicioFin($request['citamedica']['fecha'], $request['citamedica']['inicio'], $request['citamedica']['fin']);
            $start = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $end = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            $validation = ['inValid' => true, 'message' => 'Cita est&aacute; fuera del horario del m&eacute;dico'];
            foreach ($datahorario as $row) {
                if ($row->start_s <= $start && $row->end_s >= $end) {
                    //Cita esta dentro del horario del medico
                    $validation['inValid'] = false;
                    $validation['message'] = '';
                    break;
                }
            }

            if ($validation['inValid']) {
                return $this->crearRespuesta($validation['message'], [200, 'info']);
            }

            $validation = ['inValid' => false, 'message' => ''];
            foreach ($datacitas as $row) {
                if ($start === $row->start_s && $end === $row->end_s && $request['citamedica']['idpaciente'] === $row->idpaciente) {
                    $validation['inValid'] = true;
                    $validation['message'] = 'Paciente ya tiene cita.';
                    break;
                }
            }

            if ($validation['inValid']) {
                return $this->crearRespuesta($validation['message'], [200, 'info']);
            }

            $validation = ['inValid' => false, 'message' => ''];
            if ($this->validarFeriado($datacita, $start, $end)) {
                $validation['inValid'] = true;
                $validation['message'] = 'Médico ya tiene asignado citas!';

                /* Verificar si puede asignarse como entrecita.
                 */
                // 16.06.2020
                $interconsultas = array();
                // $interconsultas = $this->configurarInterconsultas($datahorario, $this->horaaSegundos($sedehorario->tiempoconsultamedica), $this->horaaSegundos($sedehorario->tiempointerconsulta), $datacita);

                foreach ($interconsultas as $row) {
                    if ($start === $row['start_s'] && $end === $row['end_s']) {
                        if ($row['numCitas'] < 2) {
                            $validation['inValid'] = false;
                        }
                        break;
                    }
                }
            }

            if ($validation['inValid']) {
                return $this->crearRespuesta($validation['message'], [200, 'info'], '', '', $interconsultas);
            }

            // return $this->crearRespuesta('XD', [200, 'info'], '', '', $interconsultas);

            /* 6.- El numero de citas no sea mayor a los disponibles por dia del medico.
             */
            if (count($datacita) >= $disponibilidad && $opcion) {
                return $this->crearRespuesta('La disponbilidad del m&eacute;dico por d&iacute;a es ' . $disponibilidad . ' citas', [200, 'info'], '', '', count($datacita));
            }
        }

        /* 3.- Validar que exista paciente */
        if (!$entidad) {
            return $this->crearRespuesta('No existe persona, registrarlo.', [200, 'info']);
        }

        /* 5.- Validar que el tiempo de cita medica sea el definido en la Sede. */
        if (!empty($sedehorario->tiempoconsultamedica)) {
            $Ini = explode(":", $request['citamedica']['inicio']);
            $Fin = explode(":", $request['citamedica']['fin']); //h:m:s
            $CitaSegundos = mktime($Fin[0], $Fin[1], $Fin[2]) - mktime($Ini[0], $Ini[1], $Ini[2]);

            $FinT = explode(":", $sedehorario->tiempoconsultamedica);
            $H = $Ini[0] + $FinT[0];
            $M = $Ini[1] + $FinT[1];
            $S = $Ini[2] + $FinT[2];
            if ($M > 59) {
                $M = $M - 59;
                $H = $H + 1;
            }

            $tiempoconsultamedica = mktime($H, $M, $S) - mktime($Ini[0], $Ini[1], $Ini[2]);
            if ($tiempoconsultamedica !== $CitaSegundos) {
                return $this->crearRespuesta("Tiempo de cita invalido. Debe ser '" . $sedehorario->tiempoconsultamedica . "' Hrs.", [200, 'info'], '', '', $empresa);
            }
        }

        /* 6.- Validar que paciente no tenga citas pendientes y confirmadas*/
        $param = [];
        $param['citamedica.idempresa'] = $idempresa;
        $param['citamedica.idsede'] = $request['citamedica']['idsede'];
        $param['citamedica.idpaciente'] = $request['citamedica']['idpaciente'];

        $datacita = $citamedica->grid($param, '', '', '', '', '', [4, 5], false, [], false, false, 'citamedica.fecha', '', false, false, false, '', '', [], [], [], false, [], date('Y-m-d H:i:s'));

        if (!empty($datacita)) {
            $cita = $datacita[0];
            return $this->crearRespuesta('Paciente ya tiene cita, Fecha: ' . $cita->fecha . ' Hora: ' . $this->convertAmPm($cita->inicio), [200, 'info'], '', '', $cita->idcitamedica);
        }
        //FIN VALIDACIONES

        if ($request['citamedica']['idestado'] === 5) {
            $request['citamedica']['idconfirmacion'] = $this->objTtoken->my;
            $request['citamedica']['fechaconfirmacion'] = date('Y-m-d H:i:s');
        }

        $request['citamedica']['idempresa'] = $idempresa;
        $request['citamedica']['idestadopago'] = 72;
        $request['citamedica']['fecha'] = $this->formatFecha($request['citamedica']['fecha'], 'yyyy-mm-dd');
        $request['citamedica']['created_at'] = date('Y-m-d H:i:s');
        $request['citamedica']['id_created_at'] = $this->objTtoken->my;
        $request['citamedica']['idtipo'] = $this->definirTipopaciente($idempresa, $request['citamedica']['idpaciente'], $request['citamedica']['idsede']);

        //En interconsultas no se selecciona referencia y atencion
        if (!isset($request['citamedica']['idatencion'])) {
            $request['citamedica']['idatencion'] = 18; //Sede
        }
        if (!isset($request['citamedica']['idreferencia'])) {
            $request['citamedica']['idreferencia'] = 17; //Otros
        }

        \DB::beginTransaction();
        try {
            //Graba en 2 tablas(citamedica, entidad)
            $create = $request['citamedica'];
            if (!isset($request['citamedica']['inicio']) && !isset($request['citamedica']['fin'])) {
                $create['inicio'] = date('H:i:s');
                $create['fin'] = date('H:i:s', strtotime('+14 minute', strtotime(date('Y-m-d H:i:s'))));
            } 

            $citamedica = citamedica::create($create);

            if ($request['citamedica']['idatencion'] === 19) {
                $paramCall = array(
                    'idempresa' => $idempresa,
                    'idcitamedica' => $citamedica->idcitamedica,
                    'fecha' => date('Y-m-d'),
                    'hora' => date('H:i:s'),
                    'cliente' => $entidad->entidad,
                    'motivo' => 'Reservación',
                    'tipo' => 'Reservación - Cita médica',
                    'created_at' => date('Y-m-d H:i:s'),
                    'id_created_at' => $this->objTtoken->my,
                );
                calls::create($paramCall);
            }

            if (isset($request['entidad'])) {
                //no se modifica la entidad por aqui, sino por el maestro
                if (isset($request['entidad']['entidad'])) {
                    unset($request['entidad']['entidad']);
                }

                $request['entidad']['tipocliente'] = '1';
                $entidad->fill($request['entidad']);
                $entidad->save();
            }

            $citamedica->grabarLogv2($citamedica->idcitamedica, $this->objTtoken->my);

        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        return $this->crearRespuesta('Cita médica para "' . $entidad->entidad . '" ha sido creado.', 201, '', '', $citamedica->idcitamedica);
    }

    private function definirTipopaciente($idempresa, $idpaciente, $idsede)
    {
        //43 Nuevo: No tiene citas atendidas y no tiene terapias realizadas
        $param = array(
            'citamedica.idempresa' => $idempresa,
            'citamedica.idsede' => $idsede,
            'citamedica.idpaciente' => $idpaciente,
            'citamedica.idestado' => 6, //atendido
        );
        $datacitamedica = \DB::table('citamedica')
            ->where($param)
            ->whereNull('deleted')
            ->first();

        $param = array(
            'terapia.idempresa' => $idempresa,
            'terapia.idsede' => $idsede,
            'terapia.idpaciente' => $idpaciente,
            'terapia.idestado' => 38, //atendido
        );
        $datacitaterapia = \DB::table('terapia')
            ->where($param)
            ->whereNull('deleted')
            ->first();

        if (empty($datacitamedica) && empty($datacitaterapia)) {
            return 43;
        }

        //42 Continuador: Ultima terapia en los 60 días ultimos

        $param = array(
            'terapia.idempresa' => $idempresa,
            'terapia.idsede' => $idsede,
            'terapia.idpaciente' => $idpaciente,
            'terapia.idestado' => 38, //atendido
        );

        $day60 = date('Y-m-d', strtotime('-60 day', strtotime(date('Y-m-j'))));

        $datacitaterapia60 = \DB::table('terapia')
            ->where($param)
            ->where('fecha', '>=', $day60)
            ->whereNull('deleted')
            ->first();

        if (!empty($datacitaterapia60)) {
            return 42;
        }

        //44 Reingresante: Todos los demas casos
        return 44;
    }

    private function horasdisponibles($datahorario, $tiempoconsultamedica, $datacitas, $datainterconsultas = [], $obviar = '')
    {

    	// $horas = $this->horasdisponibles($datahorario, $this->horaaSegundos($sedehorario->tiempoconsultamedica), $datacita, $interconsultas);

    	// dd($datacitas, $datainterconsultas);

        $horas = []; 

        foreach ($datahorario as $row) {
            $start_s = $row->start_s;
            $end_s = $row->end_s;

            while ($start_s < $end_s) {
                $horas[] = array(
                    'inicio' => date('H:i:s', $start_s),
                    'fin' => date('H:i:s', $start_s + $tiempoconsultamedica), //14 minutos
                    'start_s' => $start_s,
                    'end_s' => $start_s + $tiempoconsultamedica, //14 minutos
                    'numCitas' => 0,
                );

                $start_s = $start_s + ($tiempoconsultamedica + 60); // 14min. + 1min. = 15 min.
            }
        }

        // dd ($horas);
        if (!empty($datacitas)) {
            //dd($obviar);
            foreach ($horas as $indice => $row) {
                if (!empty($obviar) && $obviar['inicio_s'] === $row['start_s'] && $obviar['fin_s'] === $row['end_s']) {
                    //Como se trata de un rango de hora a obviar no aplica que deba ser suprimido
                } else {
                    foreach ($datacitas as $cita) {
                        if ($cita->start_s === $row['start_s'] && $cita->end_s === $row['end_s']) {
                            $borrar = true;
                            foreach ($datainterconsultas as $inter) {
                                if ($inter['start_s'] === $row['start_s'] && $inter['end_s'] === $row['end_s']) {
                                    if ($inter['numCitas'] < 2) {
                                        $borrar = false;
                                    }

                                    break;
                                }
                            }
                            if ($borrar) {
                                unset($horas[$indice]);
                            }

                            break;
                        }
                    }
                }
            }
        }
        //dd($horas);
        return $horas;
    }

    public function update(Request $request, $enterprise, $id)
    {
        $horariomedico = new horariomedico();
        $empresa = empresa::where('url', '=', $enterprise)->first();

        $citamedica = citamedica::find($id);
        $entidad = entidad::find($request['citamedica']['idpaciente']);
        $sedehorario = $empresa->sedehorarios($citamedica->idsede);

        $request = $request->all();
        $idempresa = $empresa->idempresa;

        //VALIDACIONES

        /* 1.- Validar que cita no sea mayor a los 15 dias, no se considera las horas.
         */
        $fechaMaxima = strtotime('+15 day', strtotime(date('Y-m-j')));
        $fechausuario = strtotime($this->formatFecha($request['citamedica']['fecha'], 'yyyy-mm-dd'));
        if ($fechausuario > $fechaMaxima) {
            return $this->crearRespuesta('Fecha de cita m&eacute;dica no debe ser mayor a +15 dias', [200, 'info']);
        }

        /* 2.- Validacion
         * Valida que la fecha y hora de horario a guardar, NO este en 'Dias feriados' o 'Dias laborables por hora' de Sede.
         * Para 'Dias feriados', se tomara lahora de inicio y fin laboral
         * Para 'Dias laborables por hora' se tomara como hora no habil, lo que no a seleccionado el usuario.
         */
        $validation = ['inValid' => false, 'message' => ''];

        $data = array(
            'diasFeriados' => $empresa->diasferiados(['idempresa' => $idempresa]),
        );

        $tiempoNohabil = $this->configurarFeriados($data, $empresa->laborinicio, $empresa->laborfin);

        $fechaIF = $this->fechaInicioFin($request['citamedica']['fecha'], $request['citamedica']['inicio'], $request['citamedica']['fin']);
        $start = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        $end = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

        if ($this->validarFeriado($tiempoNohabil, $start, $end)) {
            $validation['inValid'] = true;
            $validation['message'] = 'Horario no disponible. Feriado!';
        }
        //return $this->crearRespuesta('Fecha : '.$request['citamedica']['fecha'], [200, 'info'], '', '', $validation);
        if ($validation['inValid']) {
            return $this->crearRespuesta($validation['message'], [200, 'info'], '', '', $data);
        }
        /**/

        /* 3.- Validar que cita para el medico este disponible.
         *     Validar que cita no se trate del mismo paciente.
         *     Validar que cita pueda ser una interconsulta.
         */

        $param = [];
        $param['citamedica.idempresa'] = $idempresa;
        $param['citamedica.idsede'] = $citamedica->idsede;
        $param['citamedica.idmedico'] = $request['citamedica']['idmedico'];
        $param['citamedica.fecha'] = $this->formatFecha($request['citamedica']['fecha'], 'yyyy-mm-dd');

        $param2 = [];
        $param2['horariomedico.idempresa'] = $idempresa;
        $param2['horariomedico.idsede'] = $citamedica->idsede;
        $param2['horariomedico.idmedico'] = $request['citamedica']['idmedico'];
        $param2['horariomedico.fecha'] = $this->formatFecha($request['citamedica']['fecha'], 'yyyy-mm-dd');

        $datacita = $citamedica->grid($param, '', '', '', '', '', [4, 5, 6]);
        $datahorario = $horariomedico->grid($param2);

        foreach ($datacita as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        }

        $opcion = true;
        $disponibilidad = 0;
        foreach ($datahorario as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $row->start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
            $row->end_s = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

            if ($opcion) {
                /* Obtiene las horas y lo multiplica por el factor
                 * Anado 60 s porque es equivalente a 1 min. y obtengo por ejemplo 4 horas
                 * FACTOR 7.4 = 30 CONSULTAS en 4 HORAS
                 */
                $disponibilidad = $disponibilidad + ceil((($row->end_s + 60 - $row->start_s) / 3600) * 7.5);
            }
        }

        $fechaIF = $this->fechaInicioFin($request['citamedica']['fecha'], $request['citamedica']['inicio'], $request['citamedica']['fin']);
        $start = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        $end = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

        //return $this->crearRespuesta('doctor: '.$request['citamedica']['idmedico'], [200, 'info'],'','',$datahorario);
        $validation = ['inValid' => false, 'message' => ''];
        if (empty($datahorario)) {
            $validation['inValid'] = true;
            $validation['message'] = 'Cita est&aacute; fuera del horario del m&eacute;dico';
        }
        if ($validation['inValid']) {
            return $this->crearRespuesta($validation['message'], [200, 'info']);
        }

        $validation = ['inValid' => true, 'message' => 'Cita est&aacute; fuera del horario del m&eacute;dico'];
        foreach ($datahorario as $row) {
            if ($row->start_s <= $start && $row->end_s >= $end) {
                //Cita esta dentro del horario del medico
                $validation['inValid'] = false;
                $validation['message'] = '';
                break;
            }
        }
        if ($validation['inValid']) {
            return $this->crearRespuesta($validation['message'], [200, 'info']);
        }

        $validation = ['inValid' => false, 'message' => ''];
        $num = 0;
        foreach ($datacita as $row) {
            if ($request['citamedica']['idcitamedica'] !== $row->idcitamedica && $start === $row->start_s && $end === $row->end_s && $request['citamedica']['idpaciente'] === $row->idpaciente) {
                $validation['inValid'] = true;
                $validation['message'] = 'Paciente ya tiene cita.';
                $num = $num + 1;
                //break;
            }
        }
        if ($validation['inValid']) {
            return $this->crearRespuesta($validation['message'], [200, 'info'], '', '', $num);
        }

        $validation = ['inValid' => false, 'message' => ''];
        if ($this->validarFeriado($datacita, $start, $end, $citamedica->idpaciente)) {

            $validation['inValid'] = true;
            $validation['message'] = 'Médico ya tiene asignado citas!';
            /* Verificar si puede asignarse como entrecita.
             */

            // 16.06.2020
            $interconsultas = array();
            // $interconsultas = $this->configurarInterconsultas($datahorario, $this->horaaSegundos($sedehorario->tiempoconsultamedica), $this->horaaSegundos($sedehorario->tiempointerconsulta), $datacita, $request['citamedica']['idpaciente']);

            foreach ($interconsultas as $row) {
                if ($start === $row['start_s'] && $end === $row['end_s']) {
                    if ($row['numCitas'] < 2) {
                        $validation['inValid'] = false;
                    }
                    break;
                }
            }
        }

        if ($validation['inValid']) {
            return $this->crearRespuesta($validation['message'], [200, 'info'], '', '', $interconsultas);
        }

        /* 4.- Validar que exista paciente */
        if (!$entidad) {
            return $this->crearRespuesta('No existe persona, registrarlo.', [200, 'info']);
        }

        /* 5.- Validar que cita no se encuentre atendido.
         */
        if ($citamedica->idestado === 6) {
            return $this->crearRespuesta('Cita se encuentra atendido. No puede editarse.', [200, 'info']);
        }

        if (isset($request['citamedica']['idestado']) && $request['citamedica']['idestado'] === 7 && $citamedica->idestadopago === 71) {
                return $this->crearRespuesta('Cita médica está pagada, no se puede cancelar.', [200, 'info']);
        } 

        if ($citamedica) {

            if (isset($request['citamedica']['idestado'])) {
                //4:pendiente, 5:confirmada, 6:atendida, 7:cancelada
                if ($request['citamedica']['idestado'] === 4) {
                    $request['citamedica']['idconfirmacion'] = null;
                    $request['citamedica']['fechaconfirmacion'] = null;
                }

                if ($request['citamedica']['idestado'] === 5 && $citamedica->idestado !== 5) {
                    $request['citamedica']['idconfirmacion'] = $this->objTtoken->my;
                    $request['citamedica']['fechaconfirmacion'] = date('Y-m-d H:i:s');
                }
            }

            $request['citamedica']['fecha'] = $this->formatFecha($request['citamedica']['fecha'], 'yyyy-mm-dd');
            /* Campos auditores */
            $request['citamedica']['updated_at'] = date('Y-m-d H:i:s');
            $request['citamedica']['id_updated_at'] = $this->objTtoken->my;
            /* Campos auditores */

            \DB::beginTransaction();

            try {

                if (isset($request['llamadacliente']) && $request['llamadacliente'] === '1') {
                    $paramCall = array(
                        'idempresa' => $idempresa,
                        'idcitamedica' => $citamedica->idcitamedica,
                        'fecha' => date('Y-m-d'),
                        'hora' => date('H:i:s'),
                        'cliente' => $entidad->entidad,
                        'motivo' => 'Reprogramación',
                        'tipo' => 'Reprogramación - Cita médica',
                        'created_at' => date('Y-m-d H:i:s'),
                        'id_created_at' => $this->objTtoken->my,
                    );
                    calls::create($paramCall);
                }

                // 12.02.2020 El gran BUG que no encontraba:
                // Cita medica    2020-05-11 19:39:25 | 2020-05-11 20:29:51
                // Ciclo atencion 2020-05-11 19:54:43
                // Venta          2020-05-11 19:55:38
                // LOG CM         2020-05-11 19:59:31                
                unset($request['citamedica']['idestadopago']);
                unset($request['citamedica']['idventa']);
                unset($request['citamedica']['idcicloatencion']);

                $citamedica->grabarLogv2($id, $this->objTtoken->my, $request['citamedica']);
                $citamedica->fill($request['citamedica']);

                //Graba en 2 tablaa(citamedica, entidad)
                $citamedica->save();
                if (isset($request['entidad'])) {
                    $entidad->fill($request['entidad']);
                    $entidad->save();
                }
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Cita médica para "' . $entidad->entidad . '" ha sido editado. ', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una cita médica', 404);
    }

    public function destroy($enterprise, $id)
    {

        $Objordencompra = new ordencompra();

        $citamedica = citamedica::find($id);

        // if (isset($citamedica->idordencompra)) {
        //     return $this->crearRespuesta('Cita médica está pagado con una orden de compra. No se puede eliminar.', [200, 'info']);
        // }

        /* 2.- Validar que cita no este pagado.
         */
        if ($citamedica->idestadopago === 71) {
            return $this->crearRespuesta('Cita médica está pagado. No se puede eliminar.', [200, 'info']);
        }

        //idestado: 4:pendiente, 5:confirmada, 6:atendida, 7:cancelada
        if ($citamedica->idestado === 6) {
            return $this->crearRespuesta('Cita médica está atendido. No se puede eliminar.', [200, 'info']);
        }

        if ($citamedica->tipocm === 1) {
            return $this->crearRespuesta('Cita médica es "consulta autorizada" en ciclo de atención ' . $citamedica->idcicloatencion . ". No se puede eliminar.", [200, 'info']);
        }
        //VALIDACIONES

        if ($citamedica) {
            \DB::beginTransaction();
            try {
                $auditoria = ['deleted' => '1', 'deleted_at' => date('Y-m-d H:i:s'), 'id_deleted_at' => $this->objTtoken->my];
                $citamedica->fill($auditoria);
                $citamedica->save();
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Cita médica a sido eliminado.', 200);
        }
        return $this->crearRespuestaError('Cita médica no encotrado', 404);
    }

    public function log($enterprise, $id)
    {

        $citamedica = new citamedica();
        $empresa = new empresa();

        $idempresa = $empresa->idempresa($enterprise);

        $param = array(
            'logcitamedica.idempresa' => $idempresa,
            'logcitamedica.idcitamedica' => $id,
        );

        $data = $citamedica->listaLog($param);

        return $this->crearRespuesta($data, 200);
    }

}
