<?php

namespace App\Http\Controllers;

use App\Models\sede;
use App\Models\tarea;
use App\Models\venta;
use App\Models\empresa;
use App\Models\entidad;
use App\Models\proceso;
use App\Models\terapia;
use App\Mail\InvoiceSend;
use App\Models\citamedica;
use App\Models\notificacion;
use Illuminate\Http\Request;
use App\Models\cicloatencion;
use App\Models\horariomedico;
use App\Models\citaterapeutica;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Pdfs\invoiController;

//getresponse e-mailmarketing
class cronController extends sendEmail {
    
    var $iduser = 4844; 
    var $sendSMS = false;
    var $sendMAIL = false;

    // public $pathImg =  'C:\\xampp7.3\\htdocs\\apiosi\\public\\comprobantes\\';
    public $pathImg =  '/home/centromedico/public_html/apiosi/public/comprobantes/';
    // public $pathImg =  '/home/ositest/public_html/apiosi/public/comprobantes/';

    public function testeocron() {
        dd('Hola mundo');
    }

    public function receivesms(Request $request) {

        $empresa = new empresa(); 

        $idempresa = $empresa->idempresa('osi'); 

        $requestAll = $request->all();  

        $record = $requestAll['data']['record'][0];  

        $terapia = terapia::where('codigosms', $record['pairedMessageId'])->first(); 

        if ($terapia) { 

            $data = array(
                'numerosms' => $record['sender'], 
                'textosms' => $record['text'] 
            );

            $rspta = substr(trim($record['text']), 0, 1); 
            if (in_array($rspta, ['1', '2', '3', '4', '5'])) {
                $data['puntajesms'] = $record['text'];
            }

            $terapia->fill($data);             
            $terapia->save();
        } else { 
            $terapia = terapia::where('codigosalasms', $record['pairedMessageId'])->first();             
      
            if ($terapia) { 
                $data = array(
                    'numerosalasms' => $record['sender'], 
                    'textosalasms' => $record['text'] 
                );

                $rspta = substr(trim($record['text']), 0, 1); 
                if (in_array($rspta, ['1', '2', '3', '4', '5'])) {
                    $data['puntajesalasms'] = $record['text'];
                }

                $terapia->fill($data);             
                $terapia->save();
            }
        }
    }

    public function automatizacion() {
        // $this->automatizacion1();
        // $this->automatizacion2();
        // $this->automatizacion3();
        // $this->automatizacion4();
    }

    public function automatizacion1 () {

        $INFOBIP = false;

        if ($INFOBIP) {
            $this->Auto1paso1(1);
            $this->Auto1paso2(2);        

            if (date('H:i') === '07:00') 
                $this->Auto1paso3(3);

            if (date('H:i') === '07:00')
                $this->Auto1paso4(4);

            $this->Auto1paso5(5); 
        } else {
            $this->Auto1paso5(5); 
        }        
    }

    public function automatizacion2 () {

        $INFOBIP = false;
        
        if ($INFOBIP) {
            $this->Auto2paso1(6);

            if (date('H:i') === '07:00') 
                $this->Auto2Paso2(7);

            if (date('H:i') === '07:00') 
                $this->Auto2Paso3(8);

            $this->Auto2Paso4(9);
        } else {
            $this->Auto2Paso4(9);
        }
    }

    public function automatizacion3 () {

        $INFOBIP = false;

        if ($INFOBIP) {
            $this->Auto3paso1(10);
            $this->Auto3paso2(11);

            if (date('H:i') === '08:00') 
                $this->Auto3paso3(12);  

            if (date('H:i') === '08:00')
                $this->Auto3paso4(13); 

            $this->Auto3paso5(14);
        } else {
            $this->Auto3paso5(14);
        }
    } 

    public function automatizacion4 () {

        $INFOBIP = false;

        if ($INFOBIP) {
            if (date('H:i') === '07:00')  
            $this->Auto4paso1(15);  

            if (date('H:i') === '07:00') 
                $this->Auto4paso2(16);  

            $this->Auto4paso3(17);
        } else {
            $this->Auto4paso3(17);
        }
    } 

    //Automatizacion 4
    function Auto4paso3($idproceso) {
        $INFOBIP = false;

        $empresa = new empresa();
        $cicloatencion = new cicloatencion();
        $notificacion = new notificacion();

        $idempresa = $empresa->idempresa('osi'); 

        $param = array('cicloatencion.idempresa'=> $idempresa, 'cicloatencion.idestado' => 20, 'terminot' => '0');

        /*** Paso 1 ***/
        if ($INFOBIP) {
            $datacita = $cicloatencion->grid($param, '', '', '', '', '', false, ['cicloatencion.idcicloatencion', 'cicloatencion.fecha', 'cicloatencion.created_at', 'cicloatencion.fecha', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'cicloatencion.idsede'], false, false, false, false, false, [], false, [], [], true, true, false, true, false); 
 
        } else {

            $fa = $this->restarDiaCompleto(date('Y-m-d H:i:s'), '-240 hours');
            
            $inicio =  substr($fa[0], 0, 10);
            $fin = date('Y-m-d');

            $betweenFechaHora = [$inicio, $fin]; 
            // dd($betweenFechaHora);
            $datacita = $cicloatencion->grid($param, '', $betweenFechaHora, '', '', '', false, ['cicloatencion.idcicloatencion', 'cicloatencion.fecha', 'cicloatencion.created_at', 'cicloatencion.fecha', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'cicloatencion.idsede'], false, false, false, false, false, [], false, [], [], true, true, false, true, false, true); 
        }

        if ($INFOBIP) {
            /*** Paso 2 ***/ 
            $idciclos = [];
            foreach ($datacita as $row) {  
                $idciclos[] = $row->idcicloatencion;
            } 

            $datanotificaciones = [];
            if (!empty($idciclos)) {
                $param = array('notificacion.idempresa'=> $idempresa);
                $datanotificaciones = $notificacion->grid($param, '', '', '', '', '', [], $idciclos);
            }
            
            $datacita = $this->unirNotificacionAData($datacita, $datanotificaciones, [$idproceso, 16], 'idcicloatencion');         
        }
        
        /*** Paso 3 ***/
        if ($INFOBIP) {
            foreach ($datacita as $row) {
                $existDieciseis = false;            
                $existActual = false;
                $existDieciseisDia = '';

                foreach ($row->notificaciones as $notificacion) {
                    if ($notificacion->idproceso === 16) { 
                        $existDieciseis = true;
                        $existDieciseisDia = substr($notificacion->created_at, 0, 10);
                    }
                    
                    if ($notificacion->idproceso === $idproceso) {
                        $existActual = true;
                    }
                } 

                if ($existDieciseis && !$existActual && date('d/m/Y') !== $existDieciseisDia) {           
                // if ($existDieciseis && !$existActual) {
                    $insert = array(
                        'idempresa' => $idempresa,
                        'idproceso' => $idproceso,
                        'idcicloatencion' => $row->idcicloatencion,
                        'identidad' =>  $row->idpaciente,
                        'created_at' => date('Y-m-d H:i:s'),                    
                        'sms' => '0',
                        'email' => '0',
                        'sms_numero' => '',
                        'email_correo' => '',
                        'id_created_at' => $this->iduser
                    ); 
                    \DB::table('notificacion')->insert($insert);
                    
                    //Tarea           
                    $insert = array(
                        'idempresa' => $idempresa, 
                        'idcicloatencion' => $row->idcicloatencion,
                        'idestado' => 85, 
                        'idautomatizacion' => 4, 
                        'idsede' => $row->idsede,
                        'identidad' =>  $row->idpaciente,
                        'cantdiasrest' => 5,
                        'created_at' => date('Y-m-d H:i:s'), 
                        'id_created_at' => $this->iduser
                    );
                    \DB::table('tarea')->insert($insert);
                }
            }
        } else {            
            foreach ($datacita as $row) {
                $insert = array(
                    'idempresa' => $idempresa, 
                    'idcicloatencion' => $row->idcicloatencion,
                    'idestado' => 85, 
                    'idautomatizacion' => 4, 
                    'idsede' => $row->idsede,
                    'identidad' =>  $row->idpaciente,
                    'cantdiasrest' => 5,
                    'created_at' => date('Y-m-d H:i:s'), 
                    'id_created_at' => $this->iduser
                );
                \DB::table('tarea')->insert($insert);
            }

            \Log::info(print_r('===== CRON: Auto4paso3 =====', true)); 
            \Log::info(print_r($datacita, true)); 
        } 
        // dd($idproceso, $datacita); 
    }

    function Auto4paso2($idproceso) {
        $empresa = new empresa();
        $cicloatencion = new cicloatencion();
        $notificacion = new notificacion();
        
        $proceso = new proceso();  
        $proceso = $proceso->proceso($idproceso);

        $idempresa = $empresa->idempresa('osi'); 

        $param = array('cicloatencion.idempresa'=> $idempresa, 'cicloatencion.idestado' => 20, 'terminot' => '0'); 
        
        /*** Paso 1 ***/
        $datacita = $cicloatencion->grid($param, '', '', '', '', '', false, ['cicloatencion.idcicloatencion', 'cicloatencion.fecha', 'cicloatencion.fecha', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'cliente.sms_acm', 'cliente.sms_ite', 'cliente.sms_ate', 'cliente.sms_sat'], false, false, false, false, false, [], false, [], [], true, true, false, true); 

        $idciclos = [];
        foreach ($datacita as $row) {  
            $idciclos[] = $row->idcicloatencion;
        } 

        /*** Paso 2 ***/
        $datanotificaciones = [];
        if (!empty($idciclos)) {
            $param = array('notificacion.idempresa'=> $idempresa);
            $datanotificaciones = $notificacion->grid($param, '', '', '', '', '', [], $idciclos);
        }

        $datacita = $this->unirNotificacionAData($datacita, $datanotificaciones, [$idproceso, 15], 'idcicloatencion');         
        
        /*** Paso 3 ***/
        //Envío a email y SMS 
        foreach ($datacita as $i => $row) { 

            $existQuince = false;            
            $existActual = false;
            $existQuinceDia = '';

            foreach ($row->notificaciones as $notificacion) {
                if ($notificacion->idproceso === 15) { 
                    $existQuince = true;
                    $existQuinceDia = substr($notificacion->created_at, 0, 10);
                }

                if ($notificacion->idproceso === $idproceso) {
                    $existActual = true;
                }
            }
             
            if ($existQuince && !$existActual && date('d/m/Y') !== $existQuinceDia) {
            // if ($existQuince && !$existActual) {
                $this->sendNotification($row, $proceso, 'sms_sat');
            }
        } 

        /*** Proceso 1 - Paso 4 ***/ 
        foreach ($datacita as $row) { 
            $existQuince = false;            
            $existActual = false;
            $existQuinceDia = '';

            foreach ($row->notificaciones as $notificacion) {
                if ($notificacion->idproceso === 15) { 
                    $existQuince = true;
                    $existQuinceDia = substr($notificacion->created_at, 0, 10);
                }

                if ($notificacion->idproceso === $idproceso) {
                    $existActual = true;
                }
            }
 
            if ($existQuince && !$existActual && date('d/m/Y') !== $existQuinceDia) {    
            // if ($existQuince && !$existActual) {        
                $insert = array(
                    'idempresa' => $idempresa,
                    'idproceso' => $idproceso,
                    'idcicloatencion' => $row->idcicloatencion,
                    'identidad' =>  $row->idpaciente,
                    'created_at' => date('Y-m-d H:i:s'),
                    'sms' => '0',
                    'email' => '0',
                    'sms_numero' => '',
                    'email_correo' => '',
                    'id_created_at' => $this->iduser
                ); 

                if (!empty($row->celular) && $row->sms_sat === '1' && $proceso->activosms === '1' && !empty($proceso->plantillasms)) { 
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->build->messages)) {
                        $messageId = $row->build->messages[0]->messageId;
                        $messageCount = $row->build->messages[0]->smsCount;
                    }

                    $insert['sms'] = '1';
                    $insert['sms_numero'] = $row->celular;
                    $insert['sms_text'] = $row->mensaje;
                    $insert['sms_codigo'] = $messageId;
                    $insert['sms_count'] = $messageCount;
                }

                if (!empty($row->email) && $proceso->activomail === '1' && !empty($proceso->plantillamail)) {
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->buildMail->messages)) {
                        $messageId = $row->buildMail->messages[0]->messageId;
                        $messageCount = $row->buildMail->messages[0]->messageCount;
                    }

                    $insert['email'] = '1';                    
                    $insert['email_correo'] = $row->email;
                    $insert['email_codigo'] = $messageId;
                    $insert['email_count'] = $messageCount;
                }

                \DB::table('notificacion')->insert($insert);
            }
        }
        // dd($idproceso, $datacita); 
    }

    function Auto4paso1($idproceso) {
        $empresa = new empresa();
        $cicloatencion = new cicloatencion();
        $notificacion = new notificacion();
        
        $proceso = new proceso();  
        $proceso = $proceso->proceso($idproceso);

        $idempresa = $empresa->idempresa('osi'); 

        $param = array('cicloatencion.idempresa'=> $idempresa, 'cicloatencion.idestado' => 20, 'terminot' => '0'); 
        
        /*** Proceso 1 - Paso 1 ***/
        $datacita = $cicloatencion->grid($param, '', '', '', '', '', false, ['cicloatencion.idcicloatencion', 'cicloatencion.fecha', 'cicloatencion.fecha', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'cliente.sms_acm', 'cliente.sms_ite', 'cliente.sms_ate', 'cliente.sms_sat'], false, false, false, false, false, [], false, [], [], true, true, false, true);
        $idciclos = [];
        foreach ($datacita as $row) {  
            $idciclos[] = $row->idcicloatencion;
        }
        // dd($datacita);
        /*** Proceso 1 - Paso 2 ***/
        $datanotificaciones = [];
        if (!empty($idciclos)) {
            $param = array('notificacion.idempresa'=> $idempresa);
            $datanotificaciones = $notificacion->grid($param, '', '', '', '', '', [], $idciclos);
        }

        $datacita = $this->unirNotificacionAData($datacita, $datanotificaciones, [$idproceso], 'idcicloatencion');

        // dd($idproceso, $datacita);
        /*** Proceso 1 - Paso 3 ***/
        //Envío a email y SMS 
        foreach ($datacita as $i => $row) { 
            if (count($row->notificaciones) === 0) {
                $this->sendNotification($row, $proceso, 'sms_sat');
            }
        }

        /*** Proceso 1 - Paso 4 ***/ 
        foreach ($datacita as $row) { 
            if (count($row->notificaciones) === 0) {                
                $insert = array(
                    'idempresa' => $idempresa,
                    'idproceso' => $idproceso,
                    'idcicloatencion' => $row->idcicloatencion,
                    'identidad' =>  $row->idpaciente,
                    'created_at' => date('Y-m-d H:i:s'),
                    'sms' => '0',
                    'email' => '0',
                    'sms_numero' => '',
                    'email_correo' => '',
                    'id_created_at' => $this->iduser
                ); 

                if (!empty($row->celular) && $row->sms_sat === '1' && $proceso->activosms === '1' && !empty($proceso->plantillasms)) { 
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->build->messages)) {
                        $messageId = $row->build->messages[0]->messageId;
                        $messageCount = $row->build->messages[0]->smsCount;
                    }

                    $insert['sms'] = '1';
                    $insert['sms_numero'] = $row->celular;
                    $insert['sms_text'] = $row->mensaje;
                    $insert['sms_codigo'] = $messageId;
                    $insert['sms_count'] = $messageCount;
                }

                if (!empty($row->email) && $proceso->activomail === '1' && !empty($proceso->plantillamail)) {
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->buildMail->messages)) {
                        $messageId = $row->buildMail->messages[0]->messageId;
                        $messageCount = $row->buildMail->messages[0]->messageCount;
                    }

                    $insert['email'] = '1';                    
                    $insert['email_correo'] = $row->email;
                    $insert['email_codigo'] = $messageId;
                    $insert['email_count'] = $messageCount;
                }

                \DB::table('notificacion')->insert($insert);
            }
        }
        // dd($idproceso, $datacita);
    } 

    //Automatizacion 3     
    function Auto3paso5($idproceso) {

        $INFOBIP = false;

        $empresa = new empresa();
        $citaterapeutica = new citaterapeutica();
        $notificacion = new notificacion();

        $idempresa = $empresa->idempresa('osi'); 
 
        $param = array('citaterapeutica.idempresa'=> $idempresa); 

        //Citas "Pendiente y confirmadas" del dia de hoy
        $dataCitas = \DB::table('citaterapeutica') 
                ->select('citaterapeutica.idpaciente')
                ->distinct()
                ->where('citaterapeutica.fecha', date('Y-m-d'))
                ->whereIn('citaterapeutica.idestado', [32, 33])
                ->whereNull('citaterapeutica.deleted')
                ->whereNull('citaterapeutica.idcamilla')
                ->get()->all();

        $tmpdataCitas = [];
        foreach ($dataCitas as $value) {
            $tmpdataCitas[] = $value->idpaciente;
        }

        //Terapias "Atendidas" del dia de hoy
        $dataTerapias = \DB::table('terapia') 
                ->select('terapia.idpaciente')
                ->distinct()
                ->where('terapia.fecha', date('Y-m-d'))
                ->whereIn('terapia.idestado', [38])
                ->whereNull('terapia.deleted')
                ->get()->all();

        $tmpdataTerapias = [];
        foreach ($dataTerapias as $value) {
            $tmpdataTerapias[] = $value->idpaciente;
        }

        //PacientesCitas - PacientesTerapias 
        $resultado = array_diff($tmpdataCitas, $tmpdataTerapias);
        // dd($resultado); // 341

        $param['citaterapeutica.fecha'] = date('Y-m-d');
        $whereIn = [32, 33]; //32:pendiente, 33:confirmada        
        $datacitaTmp = []; 
        if (!empty($resultado)) {
            $datacitaTmp = $citaterapeutica->grid($param, '', '', '', '', '', $whereIn, [], '', $resultado, '', ['citaterapeutica.idcitaterapeutica', 'citaterapeutica.fecha', 'citaterapeutica.inicio', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'citaterapeutica.idsede']); 

        }

        // dd($datacita); // 409
        $tmp = [];
        $datacita = []; 
        foreach ($datacitaTmp as $value) {            
            if (!in_array($value->idpaciente, $tmp)) {
                $tmp[] = $value->idpaciente;
                $datacita[] = $value;
            }
        }

        foreach ($datacita as $row) {  
            //Tarea            
            $insert = array(
                'idempresa' => $idempresa, 
                'idcitaterapeutica' => $row->idcitaterapeutica,
                'idestado' => 85, 
                'idautomatizacion' => 3, 
                'idsede' => $row->idsede,
                'identidad' =>  $row->idpaciente,
                'cantdiasrest' => 5,
                'created_at' => date('Y-m-d H:i:s'), 
                'id_created_at' => $this->iduser
            ); 
            \DB::table('tarea')->insert($insert);
        }

        \Log::info(print_r('===== CRON: Auto3paso5 =====', true)); 
        \Log::info(print_r($datacita, true)); 
    }

    function Auto3paso5ELIMINAR($idproceso) {

        $INFOBIP = false;

        $empresa = new empresa();
        $citaterapeutica = new citaterapeutica();
        $notificacion = new notificacion();

        $idempresa = $empresa->idempresa('osi'); 
 
        $param = array('citaterapeutica.idempresa'=> $idempresa);  

        /*** Proceso 1 - Paso 1 ***/
        if ($INFOBIP) {

            $fa = $this->restarDiaCompleto(date('Y-m-d H:i:s'), '-72 hours');
            $inicio =  $fa[0];
            $fin = $fa[1]; 
            $betweenFechaHora = [$inicio, $fin];

            $whereIn = [32, 33]; //32:pendiente, 33:confirmada        
            $datacita = $citaterapeutica->grid($param, '', '', '', '', '', $whereIn, [], '', [], '', ['citaterapeutica.idcitaterapeutica', 'citaterapeutica.fecha', 'citaterapeutica.inicio', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'citaterapeutica.idsede'], [], $betweenFechaHora, true); 
        } else {
            $param['citaterapeutica.fecha'] = date('Y-m-d');
            $whereIn = [32, 33]; //32:pendiente, 33:confirmada        
            $datacita = $citaterapeutica->grid($param, '', '', '', '', '', $whereIn, [], '', [], '', ['citaterapeutica.idcitaterapeutica', 'citaterapeutica.fecha', 'citaterapeutica.inicio', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'citaterapeutica.idsede']); 
        } 

        /*** Proceso 1 - Paso 2 ***/
        if ($INFOBIP) {
            $idcitasterapeuticas = [];
            foreach ($datacita as $row) {  
                $idcitasterapeuticas[] = $row->idcitaterapeutica;
            } 

            $datanotificaciones = [];
            if (!empty($idcitasterapeuticas)) {
                $param = array('notificacion.idempresa'=> $idempresa);
                $datanotificaciones = $notificacion->grid($param, '', '', '', '', '', [], [], $idcitasterapeuticas);
            }

            $datacita = $this->unirNotificacionAData($datacita, $datanotificaciones, [$idproceso, 13], 'idcitaterapeutica'); 
        }
    

        /*** Proceso 1 - Paso 3 ***/ 
        if ($INFOBIP) {
            foreach ($datacita as $row) {  
                
                $existTrece= false;
                $existActual = false;

                foreach ($row->notificaciones as $notificacion) {
                    if ($notificacion->idproceso === 13) {
                        $existTrece = true;
                    }

                    if ($notificacion->idproceso === $idproceso) {
                        $existActual = true;
                    }
                }

                if ($existTrece && !$existActual) {  
                    //Notificacion
                    $insert = array(
                        'idempresa' => $idempresa,
                        'idproceso' => $idproceso,
                        'idcitaterapeutica' => $row->idcitaterapeutica,
                        'identidad' =>  $row->idpaciente,
                        'created_at' => date('Y-m-d H:i:s'),
                        'sms' => '0',
                        'email' => '0',
                        'sms_numero' => '',
                        'email_correo' => '',
                        'id_created_at' => $this->iduser
                    );
                    \DB::table('notificacion')->insert($insert);

                    //Tarea
                    $d = substr($row->fecha, 0, 2);
                    $m = substr($row->fecha, 3, 2);
                    $y = substr($row->fecha, 6, 4);         
                    $fecha = $y. '-' . $m . '-' . $d;            
                    $insert = array(
                        'idempresa' => $idempresa, 
                        'idcitaterapeutica' => $row->idcitaterapeutica,
                        'idestado' => 85, 
                        'idautomatizacion' => 3, 
                        'idsede' => $row->idsede,
                        'identidad' =>  $row->idpaciente,
                        'cantdiasrest' => 5,
                        'created_at' => date('Y-m-d H:i:s'), 
                        'id_created_at' => $this->iduser
                    ); 
                    \DB::table('tarea')->insert($insert);
                }
            }
        } else {
            dd($datacita);
        }

        // dd($idproceso, $datacita);
    }

    function Auto3paso4($idproceso) {
        $empresa = new empresa();
        $citaterapeutica = new citaterapeutica();
        $notificacion = new notificacion();

        $proceso = new proceso();  
        $proceso = $proceso->proceso($idproceso);

        $idempresa = $empresa->idempresa('osi');

        $param = array('citaterapeutica.idempresa'=> $idempresa); 

        /**/
        $fa = $this->restarDiaCompleto(date('Y-m-d H:i:s'), '-48 hours');
        $inicio =  $fa[0];
        $fin = $fa[1]; 

        $betweenFechaHora = [$inicio, $fin]; 
        /**/

        /*** Proceso 1 - Paso 1 ***/  
        $whereIn = [32, 33]; //32:pendiente, 33:confirmada        
        $datacita = $citaterapeutica->grid($param, '', '', '', '', '', $whereIn, [], '', [], '', ['citaterapeutica.idcitaterapeutica', 'citaterapeutica.fecha', 'citaterapeutica.inicio', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'cliente.sms_acm', 'cliente.sms_ite', 'cliente.sms_ate', 'cliente.sms_sat'], [], $betweenFechaHora, true); 

        $idcitasterapeuticas = [];
        foreach ($datacita as $row) {  
            $idcitasterapeuticas[] = $row->idcitaterapeutica;
        } 

        /*** Proceso 1 - Paso 2 ***/
        $datanotificaciones = [];
        if (!empty($idcitasterapeuticas)) {
            $param = array('notificacion.idempresa'=> $idempresa);
            $datanotificaciones = $notificacion->grid($param, '', '', '', '', '', [], [], $idcitasterapeuticas);
        }

        $datacita = $this->unirNotificacionAData($datacita, $datanotificaciones, [$idproceso, 12], 'idcitaterapeutica'); 
        // dd($betweenFechaHora, $datacita);
        
        /*** Proceso 1 - Paso 3 ***/
        //Envío a email y SMS        
        foreach ($datacita as $i => $row) { 

            $existDoce = false;
            $existActual = false;

            foreach ($row->notificaciones as $notificacion) {
                if ($notificacion->idproceso === 12) {
                    $existDoce = true;
                }

                if ($notificacion->idproceso === $idproceso) {
                    $existActual = true;
                }
            }

            if ($existDoce && !$existActual) {
                $this->sendNotification($row, $proceso, 'sms_ate');
            }
        }
        
        // dd($datacita);

        /*** Proceso 1 - Paso 4 ***/ 
        foreach ($datacita as $row) { 

            $existDoce = false;
            $existActual = false;

            foreach ($row->notificaciones as $notificacion) {
                if ($notificacion->idproceso === 12) {
                    $existDoce = true;
                }

                if ($notificacion->idproceso === $idproceso) {
                    $existActual = true;
                }
            }

            if ($existDoce && !$existActual) {             
                $insert = array(
                    'idempresa' => $idempresa,
                    'idproceso' => $idproceso,
                    'idcitaterapeutica' => $row->idcitaterapeutica,
                    'identidad' =>  $row->idpaciente,
                    'created_at' => date('Y-m-d H:i:s'),
                    'sms' => '0',
                    'email' => '0',
                    'sms_numero' => '',
                    'email_correo' => '',
                    'id_created_at' => $this->iduser
                );

                if (!empty($row->celular) && $row->sms_ate === '1' && $proceso->activosms === '1' && !empty($proceso->plantillasms)) { 
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->build->messages)) {
                        $messageId = $row->build->messages[0]->messageId;
                        $messageCount = $row->build->messages[0]->smsCount;
                    }

                    $insert['sms'] = '1';
                    $insert['sms_numero'] = $row->celular;
                    $insert['sms_text'] = $row->mensaje;
                    $insert['sms_codigo'] = $messageId;
                    $insert['sms_count'] = $messageCount;
                }

                if (!empty($row->email) && $proceso->activomail === '1' && !empty($proceso->plantillamail)) {
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->buildMail->messages)) {
                        $messageId = $row->buildMail->messages[0]->messageId;
                        $messageCount = $row->buildMail->messages[0]->messageCount;
                    }

                    $insert['email'] = '1';                    
                    $insert['email_correo'] = $row->email;
                    $insert['email_codigo'] = $messageId;
                    $insert['email_count'] = $messageCount;
                }

                \DB::table('notificacion')->insert($insert);
            }
        }
        // dd($idproceso, $datacita);
    } 

    function Auto3paso3($idproceso) {
        $empresa = new empresa();
        $citaterapeutica = new citaterapeutica();
        $notificacion = new notificacion();
        
        $proceso = new proceso();  
        $proceso = $proceso->proceso($idproceso);

        $idempresa = $empresa->idempresa('osi');  

        $param = array('citaterapeutica.idempresa'=> $idempresa); 

        /**/
        $fa = $this->restarDiaCompleto(date('Y-m-d H:i:s'), '-24 hours');
        $inicio =  $fa[0];
        $fin = $fa[1]; 

        $betweenFechaHora = [$inicio, $fin];  
        // dd($betweenFechaHora);
        /**/ 

        /*** Proceso 1 - Paso 1 ***/
        $whereIn = [32, 33]; //32:pendiente, 33:confirmada        
        $datacita = $citaterapeutica->grid($param, '', '', '', '', '', $whereIn, [], '', [], '', ['citaterapeutica.idcitaterapeutica', 'citaterapeutica.fecha', 'citaterapeutica.inicio', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'cliente.sms_acm', 'cliente.sms_ite', 'cliente.sms_ate', 'cliente.sms_sat'], [], $betweenFechaHora, true); 

        $idcitasterapeuticas = [];
        foreach ($datacita as $row) {  
            $idcitasterapeuticas[] = $row->idcitaterapeutica;
        } 

        /*** Proceso 1 - Paso 2 ***/
        $datanotificaciones = [];
        if (!empty($idcitasterapeuticas)) {
            $param = array('notificacion.idempresa'=> $idempresa);
            $datanotificaciones = $notificacion->grid($param, '', '', '', '', '', [], [], $idcitasterapeuticas);
        }        

        $datacita = $this->unirNotificacionAData($datacita, $datanotificaciones, [$idproceso, 11], 'idcitaterapeutica');

 
        // dd($betweenFechaHora, $datacita);
        // dd($datacita);
        /*** Proceso 1 - Paso 3 ***/
        //Envío a email y SMS
        
        foreach ($datacita as $i => $row) { 
            $existAnterior = false;
            $existActual = false;

            foreach ($row->notificaciones as $notificacion) {
                if ($notificacion->idproceso === 11) {
                    $existAnterior = true;
                }

                if ($notificacion->idproceso === $idproceso) {
                    $existActual = true;
                }
            }

            if ($existAnterior && !$existActual) {
                $this->sendNotification($row, $proceso, 'sms_ate');
            }
        } 
        // dd($datacita);

        /*** Proceso 1 - Paso 4 ***/ 
        foreach ($datacita as $row) { 
            $existAnterior = false;
            $existActual = false;

            foreach ($row->notificaciones as $notificacion) {
                if ($notificacion->idproceso === 11) {
                    $existAnterior = true;
                }

                if ($notificacion->idproceso === $idproceso) {
                    $existActual = true;
                }
            }

            if ($existAnterior && !$existActual) {            
                $insert = array(
                    'idempresa' => $idempresa,
                    'idproceso' => $idproceso,
                    'idcitaterapeutica' => $row->idcitaterapeutica,
                    'identidad' =>  $row->idpaciente,
                    'created_at' => date('Y-m-d H:i:s'),
                    'sms' => '0',
                    'email' => '0',
                    'sms_numero' => '',
                    'email_correo' => '',
                    'id_created_at' => $this->iduser
                );

                if (!empty($row->celular) && $row->sms_ate === '1' && $proceso->activosms === '1' && !empty($proceso->plantillasms)) { 
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->build->messages)) {
                        $messageId = $row->build->messages[0]->messageId;
                        $messageCount = $row->build->messages[0]->smsCount;
                    }

                    $insert['sms'] = '1';
                    $insert['sms_numero'] = $row->celular;
                    $insert['sms_text'] = $row->mensaje;
                    $insert['sms_codigo'] = $messageId;
                    $insert['sms_count'] = $messageCount;
                }

                if (!empty($row->email) && $proceso->activomail === '1' && !empty($proceso->plantillamail)) {
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->buildMail->messages)) {
                        $messageId = $row->buildMail->messages[0]->messageId;
                        $messageCount = $row->buildMail->messages[0]->messageCount;
                    }

                    $insert['email'] = '1';                    
                    $insert['email_correo'] = $row->email;
                    $insert['email_codigo'] = $messageId;
                    $insert['email_count'] = $messageCount;
                }

                \DB::table('notificacion')->insert($insert);
            }
        }

        // dd($idproceso, $datacita);
    }

    function Auto3paso2($idproceso) {
        $empresa = new empresa();
        $citaterapeutica = new citaterapeutica();
        $notificacion = new notificacion();
        
        $proceso = new proceso();  
        $proceso = $proceso->proceso($idproceso);

        $idempresa = $empresa->idempresa('osi'); 


        $param = array('citaterapeutica.idempresa'=> $idempresa); 

        /**/
        $fa = date('Y-m-d H:i:s');
        $inicio = date('Y-m-d H:i:s', strtotime('+180 minute', strtotime($fa)));
        $fin = date('Y-m-d H:i:s', strtotime('+181 minute', strtotime($fa))); //'+224 minute'

        $betweenFechaHora = [$inicio, $fin];   
        /**/ 

        /*** Proceso 1 - Paso 1 ***/
        $whereIn = [32, 33]; //32:pendiente, 33:confirmada 
        $datacita = $citaterapeutica->grid($param, '', '', '', '', '', $whereIn, [], '', [], '', ['citaterapeutica.idcitaterapeutica', 'citaterapeutica.fecha', 'citaterapeutica.inicio', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'cliente.sms_acm', 'cliente.sms_ite', 'cliente.sms_ate', 'cliente.sms_sat'], [], $betweenFechaHora);  

        $idcitasterapeuticas = [];
        foreach ($datacita as $row) {  
            $idcitasterapeuticas[] = $row->idcitaterapeutica;
        }

        // dd($betweenFechaHora, $datacita);

        /*** Proceso 1 - Paso 2 ***/
        $datanotificaciones = [];
        if (!empty($idcitasterapeuticas)) {
            $param = array('notificacion.idempresa'=> $idempresa);
            $datanotificaciones = $notificacion->grid($param, '', '', '', '', '', [], [], $idcitasterapeuticas);
        }        

        $datacita = $this->unirNotificacionAData($datacita, $datanotificaciones, [$idproceso], 'idcitaterapeutica');

        // dd($datacita);
        /*** Proceso 1 - Paso 3 ***/
        //Envío a email y SMS
        
        foreach ($datacita as $i => $row) { 
            if (count($row->notificaciones) === 0) {
                $this->sendNotification($row, $proceso, 'sms_ate');
            }
        }
        
        // dd($datacita);

        /*** Proceso 1 - Paso 4 ***/ 
        foreach ($datacita as $row) { 
            if (count($row->notificaciones) === 0) {
                $insert = array(
                    'idempresa' => $idempresa,
                    'idproceso' => $idproceso,
                    'idcitaterapeutica' => $row->idcitaterapeutica,
                    'identidad' =>  $row->idpaciente,
                    'created_at' => date('Y-m-d H:i:s'),
                    'sms' => '0',
                    'email' => '0',
                    'sms_numero' => '',
                    'email_correo' => '',
                    'id_created_at' => $this->iduser
                );

                if (!empty($row->celular) && $row->sms_ate === '1' && $proceso->activosms === '1' && !empty($proceso->plantillasms)) { 
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->build->messages)) {
                        $messageId = $row->build->messages[0]->messageId;
                        $messageCount = $row->build->messages[0]->smsCount;
                    }

                    $insert['sms'] = '1';
                    $insert['sms_numero'] = $row->celular;
                    $insert['sms_text'] = $row->mensaje;
                    $insert['sms_codigo'] = $messageId;
                    $insert['sms_count'] = $messageCount;
                }

                if (!empty($row->email) && $proceso->activomail === '1' && !empty($proceso->plantillamail)) {
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->buildMail->messages)) {
                        $messageId = $row->buildMail->messages[0]->messageId;
                        $messageCount = $row->buildMail->messages[0]->messageCount;
                    }

                    $insert['email'] = '1';                    
                    $insert['email_correo'] = $row->email;
                    $insert['email_codigo'] = $messageId;
                    $insert['email_count'] = $messageCount;
                }

                \DB::table('notificacion')->insert($insert);
            }
        } 
        // dd($idproceso, $datacita);
    }

    function Auto3paso1($idproceso) {
        $empresa = new empresa();
        $citaterapeutica = new citaterapeutica();
        $notificacion = new notificacion();

        $proceso = new proceso();  
        $proceso = $proceso->proceso($idproceso);

        $idempresa = $empresa->idempresa('osi'); 

        $param = array('citaterapeutica.idempresa'=> $idempresa);

        $fin = date('Y-m-d H:i:s'); 
        $inicio = date('Y-m-d H:i:s', strtotime('-1 minute', strtotime($fin)));     
        $betweenCreatedAt = [$inicio, $fin];  
        
        /*** Proceso 1 - Paso 1 ***/
        $whereIn = [32, 33]; //32:pendiente, 33:confirmada 
        $datacita = $citaterapeutica->grid($param, '', '', '', '', '', $whereIn, [], '', [], '', ['citaterapeutica.idcitaterapeutica', 'citaterapeutica.fecha', 'citaterapeutica.inicio', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'cliente.sms_acm', 'cliente.sms_ite', 'cliente.sms_ate', 'cliente.sms_sat'], $betweenCreatedAt);

        $idcitasterapeuticas = [];
        foreach ($datacita as $row) {  
            $idcitasterapeuticas[] = $row->idcitaterapeutica;
        }

        /*** Proceso 1 - Paso 2 ***/
        $datanotificaciones = [];
        if (!empty($idcitasterapeuticas)) {
            $param = array('notificacion.idempresa'=> $idempresa);
            $datanotificaciones = $notificacion->grid($param, '', '', '', '', '', [], [], $idcitasterapeuticas);
        }        

        $datacita = $this->unirNotificacionAData($datacita, $datanotificaciones, [$idproceso], 'idcitaterapeutica');

        /*** Proceso 1 - Paso 3 ***/
        //Envío a email y SMS 
        foreach ($datacita as $i => $row) { 
            if (count($row->notificaciones) === 0) {
                $this->sendNotification($row, $proceso, 'sms_ate');
            }
        }
        
        // dd($datacita);

        /*** Proceso 1 - Paso 4 ***/ 
        foreach ($datacita as $row) { 
            if (count($row->notificaciones) === 0) {                
                $insert = array(
                    'idempresa' => $idempresa,
                    'idproceso' => $idproceso,
                    'idcitaterapeutica' => $row->idcitaterapeutica,
                    'identidad' =>  $row->idpaciente,
                    'created_at' => date('Y-m-d H:i:s'),
                    'sms' => '0',
                    'email' => '0',
                    'sms_numero' => '',
                    'email_correo' => '',
                    'id_created_at' => $this->iduser
                );

                if (!empty($row->celular) && $row->sms_ate === '1' && $proceso->activosms === '1' && !empty($proceso->plantillasms)) { 
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->build->messages)) {
                        $messageId = $row->build->messages[0]->messageId;
                        $messageCount = $row->build->messages[0]->smsCount;
                    }

                    $insert['sms'] = '1';
                    $insert['sms_numero'] = $row->celular;
                    $insert['sms_text'] = $row->mensaje;
                    $insert['sms_codigo'] = $messageId;
                    $insert['sms_count'] = $messageCount;
                }

                if (!empty($row->email) && $proceso->activomail === '1' && !empty($proceso->plantillamail)) {
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->buildMail->messages)) {
                        $messageId = $row->buildMail->messages[0]->messageId;
                        $messageCount = $row->buildMail->messages[0]->messageCount;
                    }

                    $insert['email'] = '1';                    
                    $insert['email_correo'] = $row->email;
                    $insert['email_codigo'] = $messageId;
                    $insert['email_count'] = $messageCount;
                }

                \DB::table('notificacion')->insert($insert);
            }
        }

        // dd($idproceso, $datacita, $betweenCreatedAt);
    } 

    //Automatizacion 2
    function Auto2paso4($idproceso) {
        $INFOBIP = false;
        $empresa = new empresa();
        $cicloatencion = new cicloatencion();
        $notificacion = new notificacion();

        $idempresa = $empresa->idempresa('osi'); 

        $param = array('cicloatencion.idempresa'=> $idempresa, 'cicloatencion.idestado' => 20);
 
        // dd($betweenCreatedAt);
        /*** Proceso 1 - Paso 1 ***/   
        if ($INFOBIP) {
            $fa = $this->restarDiaCompleto(date('Y-m-d H:i:s'), '-72 hours');
            $inicio =  $fa[0];
            $fin = $fa[1]; 
            $betweenCreatedAt = [$inicio, $fin];

            $datacita = $cicloatencion->grid($param, '', '', '', '', '', false, ['cicloatencion.idcicloatencion', 'cicloatencion.fecha', 'cicloatencion.created_at', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'cicloatencion.idsede'], false, false, false, false, false, [], false, [], $betweenCreatedAt, true, true, true);
        } else {
            $param['cicloatencion.fecha'] = date('Y-m-d');
            
            $datacita = $cicloatencion->grid($param, '', '', '', '', '', false, ['cicloatencion.idcicloatencion', 'cicloatencion.fecha', 'cicloatencion.created_at', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'cicloatencion.idsede'], false, false, false, false, false, [], false, [], [], true, true, true);
        }         

        /*** Proceso 1 - Paso 2 ***/ 
        if ($INFOBIP) { 
            $idciclos = [];
            foreach ($datacita as $row) {  
                $idciclos[] = $row->idcicloatencion;
            }

            $datanotificaciones = [];
            if (!empty($idciclos)) {
                $param = array('notificacion.idempresa'=> $idempresa);
                $datanotificaciones = $notificacion->grid($param, '', '', '', '', '', [], $idciclos);
            }

            $datacita = $this->unirNotificacionAData($datacita, $datanotificaciones, [$idproceso, 8], 'idcicloatencion');         
        }

        /*** Proceso 1 - Paso 4 ***/ 
        if ($INFOBIP) { 
            foreach ($datacita as $row) { 
                $existOcho = false;
                $existActual = false;

                foreach ($row->notificaciones as $notificacion) {
                    if ($notificacion->idproceso === 8) {
                        $existOcho = true;
                    }

                    if ($notificacion->idproceso === $idproceso) {
                        $existActual = true;
                    }
                }

                if ($existOcho && !$existActual) {
                    $insert = array(
                        'idempresa' => $idempresa,
                        'idproceso' => $idproceso,
                        'idcicloatencion' => $row->idcicloatencion,
                        'identidad' =>  $row->idpaciente,
                        'created_at' => date('Y-m-d H:i:s'),
                        'sms' => '0',
                        'email' => '0',
                        'sms_numero' => '',
                        'email_correo' => '',
                        'id_created_at' => $this->iduser
                    ); 
                    \DB::table('notificacion')->insert($insert);
                    // dd($row);
                    //Tarea           
                    $insert = array(
                        'idempresa' => $idempresa, 
                        'idcicloatencion' => $row->idcicloatencion,
                        'idestado' => 85, 
                        'idautomatizacion' => 2, 
                        'idsede' => $row->idsede,       
                        'identidad' =>  $row->idpaciente,      
                        'cantdiasrest' => 5,       
                        'created_at' => date('Y-m-d H:i:s'), 
                        'id_created_at' => $this->iduser
                    );
                    \DB::table('tarea')->insert($insert);
                }
            }
        } else {

            // dd($datacita);
            foreach ($datacita as $row) { 
                //Tarea           
                $insert = array(
                    'idempresa' => $idempresa, 
                    'idcicloatencion' => $row->idcicloatencion,
                    'idestado' => 85, 
                    'idautomatizacion' => 2, 
                    'idsede' => $row->idsede,       
                    'identidad' =>  $row->idpaciente,      
                    'cantdiasrest' => 5,       
                    'created_at' => date('Y-m-d H:i:s'), 
                    'id_created_at' => $this->iduser
                );
                \DB::table('tarea')->insert($insert);
            }
            \Log::info(print_r('===== CRON: Auto2paso4 =====', true)); 
            \Log::info(print_r($datacita, true)); 
        }
        // dd($idproceso, $datacita); 
    }

    function Auto2paso3($idproceso) {
        $empresa = new empresa();
        $cicloatencion = new cicloatencion();
        $notificacion = new notificacion();
        
        $proceso = new proceso();  
        $proceso = $proceso->proceso($idproceso);

        $idempresa = $empresa->idempresa('osi'); 

        $param = array('cicloatencion.idempresa'=> $idempresa, 'cicloatencion.idestado' => 20);

        $fa = $this->restarDiaCompleto(date('Y-m-d H:i:s'), '-48 hours');
        $inicio = $fa[0];
        $fin = $fa[1]; 
        $betweenCreatedAt = [$inicio, $fin];
        
        // dd($betweenCreatedAt);
        /*** Proceso 1 - Paso 1 ***/         
        $datacita = $cicloatencion->grid($param, '', '', '', '', '', false, ['cicloatencion.idcicloatencion', 'cicloatencion.fecha', 'cicloatencion.fecha', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'cliente.sms_acm', 'cliente.sms_ite', 'cliente.sms_ate', 'cliente.sms_sat'], false, false, false, false, false, [], false, [], $betweenCreatedAt, true, true, true);
 
        $idciclos = [];
        foreach ($datacita as $row) {  
            $idciclos[] = $row->idcicloatencion;
        } 

        /*** Proceso 1 - Paso 2 ***/
        $datanotificaciones = [];
        if (!empty($idciclos)) {
            $param = array('notificacion.idempresa'=> $idempresa);
            $datanotificaciones = $notificacion->grid($param, '', '', '', '', '', [], $idciclos);
        }

        $datacita = $this->unirNotificacionAData($datacita, $datanotificaciones, [$idproceso, 7], 'idcicloatencion');         
        // dd($datacita);
        /*** Proceso 1 - Paso 3 ***/
        //Envío a email y SMS 
        foreach ($datacita as $i => $row) { 

            $existSiete = false;
            $existActual = false;

            foreach ($row->notificaciones as $notificacion) {
                if ($notificacion->idproceso === 7) {
                    $existSiete = true;
                }

                if ($notificacion->idproceso === $idproceso) {
                    $existActual = true;
                }
            }

            if ($existSiete && !$existActual) {  
                $this->sendNotification($row, $proceso, 'sms_ite');
            }
        } 

        /*** Proceso 1 - Paso 4 ***/ 
        foreach ($datacita as $row) { 
            $existSiete = false;
            $existActual = false;

            foreach ($row->notificaciones as $notificacion) {
                if ($notificacion->idproceso === 7) {
                    $existSiete = true;
                }

                if ($notificacion->idproceso === $idproceso) {
                    $existActual = true;
                }
            }

            if ($existSiete && !$existActual) {            
                $insert = array(
                    'idempresa' => $idempresa,
                    'idproceso' => $idproceso,
                    'idcicloatencion' => $row->idcicloatencion,
                    'identidad' =>  $row->idpaciente,
                    'created_at' => date('Y-m-d H:i:s'),
                    'sms' => '0',
                    'email' => '0',
                    'sms_numero' => '',
                    'email_correo' => '',
                    'id_created_at' => $this->iduser
                ); 

                if (!empty($row->celular) && $row->sms_ite === '1' && $proceso->activosms === '1' && !empty($proceso->plantillasms)) { 
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->build->messages)) {
                        $messageId = $row->build->messages[0]->messageId;
                        $messageCount = $row->build->messages[0]->smsCount;
                    }

                    $insert['sms'] = '1';
                    $insert['sms_numero'] = $row->celular;
                    $insert['sms_text'] = $row->mensaje;
                    $insert['sms_codigo'] = $messageId;
                    $insert['sms_count'] = $messageCount;
                }

                if (!empty($row->email) && $proceso->activomail === '1' && !empty($proceso->plantillamail)) {
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->buildMail->messages)) {
                        $messageId = $row->buildMail->messages[0]->messageId;
                        $messageCount = $row->buildMail->messages[0]->messageCount;
                    }

                    $insert['email'] = '1';                    
                    $insert['email_correo'] = $row->email;
                    $insert['email_codigo'] = $messageId;
                    $insert['email_count'] = $messageCount;
                }

                \DB::table('notificacion')->insert($insert);
            }
        }
        // dd($idproceso, $datacita); 
    }

    function Auto2paso2($idproceso) {
        $empresa = new empresa();
        $cicloatencion = new cicloatencion();
        $notificacion = new notificacion();
        
        $proceso = new proceso();  
        $proceso = $proceso->proceso($idproceso); 

        $idempresa = $empresa->idempresa('osi'); 

        $param = array('cicloatencion.idempresa'=> $idempresa, 'cicloatencion.idestado' => 20);

        $fa = $this->restarDiaCompleto(date('Y-m-d H:i:s'), '-24 hours');
        $inicio =  $fa[0];
        $fin = $fa[1]; 
        $betweenCreatedAt = [$inicio, $fin];

        
        // dd($betweenCreatedAt);
        /*** Proceso 1 - Paso 1 ***/         
        $datacita = $cicloatencion->grid($param, '', '', '', '', '', false, ['cicloatencion.idcicloatencion', 'cicloatencion.fecha', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'cliente.sms_acm', 'cliente.sms_ite', 'cliente.sms_ate', 'cliente.sms_sat'], false, false, false, false, false, [], false, [], $betweenCreatedAt, true, true, true);
 

        $idciclos = [];
        foreach ($datacita as $row) {  
            $idciclos[] = $row->idcicloatencion;
        } 

        /*** Proceso 1 - Paso 2 ***/
        $datanotificaciones = [];
        if (!empty($idciclos)) {
            $param = array('notificacion.idempresa'=> $idempresa);
            $datanotificaciones = $notificacion->grid($param, '', '', '', '', '', [], $idciclos);
        }

        $datacita = $this->unirNotificacionAData($datacita, $datanotificaciones, [$idproceso, 6], 'idcicloatencion');         
        /*** Proceso 1 - Paso 3 ***/
        //Envío a email y SMS 
        foreach ($datacita as $i => $row) { 
            $existAnterior = false;
            $existActual = false;

            foreach ($row->notificaciones as $notificacion) {
                if ($notificacion->idproceso === 6) {
                    $existAnterior = true;
                }

                if ($notificacion->idproceso === $idproceso) {
                    $existActual = true;
                }
            }

            if ($existAnterior && !$existActual) {  
                $this->sendNotification($row, $proceso, 'sms_ite');
            }
        } 

        /*** Proceso 1 - Paso 4 ***/ 
        foreach ($datacita as $row) { 
            $existAnterior = false;
            $existActual = false;

            foreach ($row->notificaciones as $notificacion) {
                if ($notificacion->idproceso === 6) {
                    $existAnterior = true;
                }

                if ($notificacion->idproceso === $idproceso) {
                    $existActual = true;
                }
            }

            if ($existAnterior && !$existActual) {          
                $insert = array(
                    'idempresa' => $idempresa,
                    'idproceso' => $idproceso,
                    'idcicloatencion' => $row->idcicloatencion,
                    'identidad' =>  $row->idpaciente,
                    'created_at' => date('Y-m-d H:i:s'),
                    'sms' => '0',
                    'email' => '0',
                    'sms_numero' => '',
                    'email_correo' => '',
                    'id_created_at' => $this->iduser
                ); 

                if (!empty($row->celular) && $row->sms_ite === '1' && $proceso->activosms === '1' && !empty($proceso->plantillasms)) { 
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->build->messages)) {
                        $messageId = $row->build->messages[0]->messageId;
                        $messageCount = $row->build->messages[0]->smsCount;
                    }

                    $insert['sms'] = '1';
                    $insert['sms_numero'] = $row->celular;
                    $insert['sms_text'] = $row->mensaje;
                    $insert['sms_codigo'] = $messageId;
                    $insert['sms_count'] = $messageCount;
                }

                if (!empty($row->email) && $proceso->activomail === '1' && !empty($proceso->plantillamail)) {
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->buildMail->messages)) {
                        $messageId = $row->buildMail->messages[0]->messageId;
                        $messageCount = $row->buildMail->messages[0]->messageCount;
                    }

                    $insert['email'] = '1';                    
                    $insert['email_correo'] = $row->email;
                    $insert['email_codigo'] = $messageId;
                    $insert['email_count'] = $messageCount;
                }

                \DB::table('notificacion')->insert($insert);
            }
        }
        // dd($datacita);
        // dd($betweenCreatedAt, $datacita, $this->sendMAIL);
    }

    function Auto2paso1($idproceso) {
        $empresa = new empresa();
        $cicloatencion = new cicloatencion();
        $notificacion = new notificacion();
        
        $proceso = new proceso();  
        $proceso = $proceso->proceso($idproceso); 

        $idempresa = $empresa->idempresa('osi'); 

        $param = array('cicloatencion.idempresa'=> $idempresa, 'cicloatencion.idestado' => 20);

        // $fin = date('Y-m-d H:i:s'); 
        // $inicio = date('Y-m-d H:i:s', strtotime('-1 minute', strtotime($fin)));     
        // $betweenCreatedAt = [$inicio, $fin];  

        $fa = $this->restarDiaCompleto(date('Y-m-d H:i:s'), '-0 hours');
        $inicio = $fa[0];
        $fin = $fa[1];
        $betweenCreatedAt = [$inicio, $fin]; 
        // dd($idproceso, $betweenCreatedAt);

        // dd($betweenCreatedAt);
        /*** Proceso 1 - Paso 1 ***/         
        $datacita = $cicloatencion->grid($param, '', '', '', '', '', false, ['cicloatencion.idcicloatencion', 'cicloatencion.fecha', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'cliente.sms_acm', 'cliente.sms_ite', 'cliente.sms_ate', 'cliente.sms_sat'], false, false, false, false, false, [], false, [], $betweenCreatedAt, true);
        $idciclos = [];
        foreach ($datacita as $row) {  
            $idciclos[] = $row->idcicloatencion;
        }

        /*** Proceso 1 - Paso 2 ***/
        $datanotificaciones = [];
        if (!empty($idciclos)) {
            $param = array('notificacion.idempresa'=> $idempresa);
            $datanotificaciones = $notificacion->grid($param, '', '', '', '', '', [], $idciclos);
        }

        $datacita = $this->unirNotificacionAData($datacita, $datanotificaciones, [$idproceso], 'idcicloatencion');

        // dd($idproceso, $datacita);

        /*** Proceso 1 - Paso 3 ***/
        //Envío a email y SMS 
        foreach ($datacita as $i => $row) { 
            if (count($row->notificaciones) === 0) {  
                $this->sendNotification($row, $proceso, 'sms_ite');
            }
        }
        
        // dd($datacita);

        /*** Proceso 1 - Paso 4 ***/ 
        foreach ($datacita as $row) { 
            if (count($row->notificaciones) === 0) {                
                $insert = array(
                    'idempresa' => $idempresa,
                    'idproceso' => $idproceso,
                    'idcicloatencion' => $row->idcicloatencion,
                    'identidad' =>  $row->idpaciente,
                    'created_at' => date('Y-m-d H:i:s'),
                    'sms' => '0',
                    'email' => '0',
                    'sms_numero' => '',
                    'email_correo' => '',
                    'id_created_at' => $this->iduser
                ); 

                if (!empty($row->email) && $proceso->activomail === '1' && !empty($proceso->plantillamail)) {
                    $messageId = NULL;
                    if (isset($row->buildMail->messages))
                        $messageId = $row->buildMail->messages[0]->messageId;

                    $insert['email'] = '1';
                    $insert['email_codigo'] = $messageId;
                    $insert['email_correo'] = $row->email;
                }

                \DB::table('notificacion')->insert($insert);
            }
        }
        // dd($betweenCreatedAt, $datacita, $this->sendMAIL);
    }

    //Automatizacion 1
    function Auto1paso5($idproceso) {
        $INFOBIP = false;
        $empresa = new empresa();
        $citamedica = new citamedica();
        $notificacion = new notificacion(); 

        $idempresa = $empresa->idempresa('osi'); 


        $param = array('citamedica.idempresa'=> $idempresa);  

        /*** Proceso 1 - Paso 1 ***/
        if ($INFOBIP) {
            $fa = $this->restarDiaCompleto(date('Y-m-d H:i:s'), '-72 hours');
            $inicio =  $fa[0];
            $fin = $fa[1]; 
            $betweenFechaHora = [$inicio, $fin]; //Ej. 2019-10-20 00:00:00, 2019-10-20 23:59:59 

            // Citas pendientes que no tengan un cita recien próxima
            $whereIn = [4, 5]; //4:pendiente, 5:confirmada         
            $datacita = $citamedica->grid($param, '', '', '', '', '', $whereIn, false, '', false, false, 'citamedica.fecha', [], false, false,false, '', '', ['citamedica.idcitamedica', 'citamedica.fecha', 'citamedica.inicio', 'citamedica.fin', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'citamedica.idsede'], [], $betweenFechaHora, true);
        } else {
            $param['citamedica.fecha'] = date('Y-m-d');
            // Citas pendientes que no tengan un cita recien próxima
            $whereIn = [4, 5]; //4:pendiente, 5:confirmada         
            $datacita = $citamedica->grid($param, '', '', '', '', '', $whereIn, false, '', false, false, 'citamedica.fecha', [], false, false,false, '', '', ['citamedica.idcitamedica', 'citamedica.fecha', 'citamedica.inicio', 'citamedica.fin', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'citamedica.idsede'], [], [], true);
        } 
         

        /*** Proceso 1 - Paso 2 ***/
        if ($INFOBIP) {
            $idcitasmedicas = [];
            foreach ($datacita as $row) {  
                $idcitasmedicas[] = $row->idcitamedica;
            }

            $datanotificaciones = [];
            if (!empty($idcitasmedicas)) {
                $param = array('notificacion.idempresa'=> $idempresa);
                $datanotificaciones = $notificacion->grid($param, '', '', '', '', '', $idcitasmedicas);
            } 
            $datacita = $this->unirNotificacionAData($datacita, $datanotificaciones, [$idproceso, 4]);
        } 

        /*** Proceso 1 - Paso 3 ***/ 
        if ($INFOBIP) {
            foreach ($datacita as $row) { 
                
                $existCuatro= false;
                $existActual = false;

                foreach ($row->notificaciones as $notificacion) {
                    if ($notificacion->idproceso === 4) {
                        $existCuatro = true;
                    }

                    if ($notificacion->idproceso === $idproceso) {
                        $existActual = true;
                    }
                }

                if ($existCuatro && !$existActual) {  
                    //Notificacion
                    $insert = array(
                        'idempresa' => $idempresa,
                        'idproceso' => $idproceso,
                        'idcitamedica' => $row->idcitamedica,
                        'identidad' =>  $row->idpaciente,
                        'created_at' => date('Y-m-d H:i:s'),
                        'sms' => '0',
                        'email' => '0',
                        'sms_numero' => '',
                        'email_correo' => '',
                        'id_created_at' => $this->iduser
                    );
                    \DB::table('notificacion')->insert($insert);

                    //Tarea            
                    $insert = array(
                        'idempresa' => $idempresa,
                        'idcitamedica' => $row->idcitamedica,
                        'idestado' => 85,
                        'idautomatizacion' => 1,
                        'idsede' => $row->idsede,
                        'identidad' =>  $row->idpaciente,
                        'cantdiasrest' => 5,
                        'created_at' => date('Y-m-d H:i:s'), 
                        'id_created_at' => $this->iduser
                    ); 
                    \DB::table('tarea')->insert($insert);
                }
            }
        } else {
            // Esta tarea debe ejecutarse una sola vez, caso contrario se insertará doble.
            foreach ($datacita as $row) { 
                //Tarea            
                $insert = array(
                    'idempresa' => $idempresa,
                    'idcitamedica' => $row->idcitamedica,
                    'idestado' => 85,
                    'idautomatizacion' => 1,
                    'idsede' => $row->idsede,
                    'identidad' =>  $row->idpaciente,
                    'cantdiasrest' => 5,
                    'created_at' => date('Y-m-d H:i:s'), 
                    'id_created_at' => $this->iduser
                ); 

                \DB::table('tarea')->insert($insert);
            }

            \Log::info(print_r('===== CRON: Auto1paso5 =====', true)); 
            \Log::info(print_r($datacita, true)); 
        } 
        // dd($datacita);
    }

    function Auto1paso4($idproceso) {
        $empresa = new empresa();
        $citamedica = new citamedica();
        $notificacion = new notificacion();
        
        $proceso = new proceso();  
        $proceso = $proceso->proceso($idproceso);

        $idempresa = $empresa->idempresa('osi'); 


        $param = array('citamedica.idempresa'=> $idempresa); 

        /**/ 
        $fa = $this->restarDiaCompleto(date('Y-m-d H:i:s'), '-48 hours');
        $inicio =  $fa[0];
        $fin = $fa[1]; 
        $betweenFechaHora = [$inicio, $fin];
        /**/
 
        /*** Proceso 1 - Paso 1 ***/
        $whereIn = [4, 5]; //4:pendiente, 5:confirmada         
        $datacita = $citamedica->grid($param, '', '', '', '', '', $whereIn, false, '', false, false, 'citamedica.fecha', [], false, false,false, '', '', ['citamedica.idcitamedica', 'citamedica.fecha', 'citamedica.inicio', 'citamedica.fin', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'cliente.sms_acm', 'cliente.sms_ite', 'cliente.sms_ate', 'cliente.sms_sat'], [], $betweenFechaHora, true);

        $idcitasmedicas = [];
        foreach ($datacita as $row) {  
            $idcitasmedicas[] = $row->idcitamedica;
        }

        /*** Proceso 1 - Paso 2 ***/
        $datanotificaciones = [];
        if (!empty($idcitasmedicas)) {
            $param = array('notificacion.idempresa'=> $idempresa);
            $datanotificaciones = $notificacion->grid($param, '', '', '', '', '', $idcitasmedicas);
        }        

        $datacita = $this->unirNotificacionAData($datacita, $datanotificaciones, [$idproceso, 3]);
        // dd($betweenFechaHora, $datacita);
        
        /*** Proceso 1 - Paso 3 ***/
        //Envío a email y SMS        
        foreach ($datacita as $i => $row) { 

            $existTres = false;
            $existActual = false;

            foreach ($row->notificaciones as $notificacion) {
                if ($notificacion->idproceso === 3) {
                    $existTres = true;
                }

                if ($notificacion->idproceso === $idproceso) {
                    $existActual = true;
                }
            }

            if ($existTres && !$existActual) { 
                $this->sendNotification($row, $proceso, 'sms_acm');
            }
        }
        
        // dd($datacita);

        /*** Proceso 1 - Paso 4 ***/ 
        foreach ($datacita as $row) { 

            $existTres = false;
            $existActual = false;

            foreach ($row->notificaciones as $notificacion) {
                if ($notificacion->idproceso === 3) {
                    $existTres = true;
                }

                if ($notificacion->idproceso === $idproceso) {
                    $existActual = true;
                }
            }

            if ($existTres && !$existActual) {               
                $insert = array(
                    'idempresa' => $idempresa,
                    'idproceso' => $idproceso,
                    'idcitamedica' => $row->idcitamedica,
                    'identidad' =>  $row->idpaciente,
                    'created_at' => date('Y-m-d H:i:s'),
                    'sms' => '0',
                    'email' => '0',
                    'sms_numero' => '',
                    'email_correo' => '',
                    'id_created_at' => $this->iduser
                );

                if (!empty($row->celular) && $row->sms_acm === '1' && $proceso->activosms === '1' && !empty($proceso->plantillasms)) { 
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->build->messages)) {
                        $messageId = $row->build->messages[0]->messageId;
                        $messageCount = $row->build->messages[0]->smsCount;
                    }

                    $insert['sms'] = '1';
                    $insert['sms_numero'] = $row->celular;
                    $insert['sms_text'] = $row->mensaje;
                    $insert['sms_codigo'] = $messageId;
                    $insert['sms_count'] = $messageCount;
                }

                if (!empty($row->email) && $proceso->activomail === '1' && !empty($proceso->plantillamail)) {
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->buildMail->messages)) {
                        $messageId = $row->buildMail->messages[0]->messageId;
                        $messageCount = $row->buildMail->messages[0]->messageCount;
                    }

                    $insert['email'] = '1';                    
                    $insert['email_correo'] = $row->email;
                    $insert['email_codigo'] = $messageId;
                    $insert['email_count'] = $messageCount;
                }

                \DB::table('notificacion')->insert($insert);
            }
        }
    }

    function Auto1paso3($idproceso) {
        $empresa = new empresa();
        $citamedica = new citamedica();
        $notificacion = new notificacion();
        
        $proceso = new proceso();  
        $proceso = $proceso->proceso($idproceso);

        $idempresa = $empresa->idempresa('osi'); 

        $param = array('citamedica.idempresa'=> $idempresa); 

        /**/ 
        $fa = $this->restarDiaCompleto(date('Y-m-d H:i:s'), '-24 hours');
        $inicio =  $fa[0];
        $fin = $fa[1]; 
        $betweenFechaHora = [$inicio, $fin]; 
        /**/

        /*** Proceso 1 - Paso 1 ***/
        $whereIn = [4, 5]; //4:pendiente, 5:confirmada         
        $datacita = $citamedica->grid($param, '', '', '', '', '', $whereIn, false, '', false, false, 'citamedica.fecha', [], false, false,false, '', '', ['citamedica.idcitamedica', 'citamedica.fecha', 'citamedica.inicio', 'citamedica.fin', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'cliente.sms_acm', 'cliente.sms_ite', 'cliente.sms_ate', 'cliente.sms_sat'], [], $betweenFechaHora, true);

        $idcitasmedicas = [];
        foreach ($datacita as $row) {  
            $idcitasmedicas[] = $row->idcitamedica;
        }

        /*** Proceso 1 - Paso 2 ***/
        $datanotificaciones = [];
        if (!empty($idcitasmedicas)) {
            $param = array('notificacion.idempresa'=> $idempresa);
            $datanotificaciones = $notificacion->grid($param, '', '', '', '', '', $idcitasmedicas);
        }        

        $datacita = $this->unirNotificacionAData($datacita, $datanotificaciones, [$idproceso, 2]);
        // dd($betweenFechaHora, $datacita);
        // dd($datacita);
        /*** Proceso 1 - Paso 3 ***/
        //Envío a email y SMS
        
        foreach ($datacita as $i => $row) { 
            $existAnterior = false;
            $existActual = false;

            foreach ($row->notificaciones as $notificacion) {
                if ($notificacion->idproceso === 2) {
                    $existAnterior = true;
                }

                if ($notificacion->idproceso === $idproceso) {
                    $existActual = true;
                }
            }

            if ($existAnterior && !$existActual) {  
                $this->sendNotification($row, $proceso, 'sms_acm');
            }
        }
        
        // dd($datacita);

        /*** Proceso 1 - Paso 4 ***/ 
        foreach ($datacita as $row) { 
            $existAnterior = false;
            $existActual = false;

            foreach ($row->notificaciones as $notificacion) {
                if ($notificacion->idproceso === 2) {
                    $existAnterior = true;
                }

                if ($notificacion->idproceso === $idproceso) {
                    $existActual = true;
                }
            }

            if ($existAnterior && !$existActual) {             
                $insert = array(
                    'idempresa' => $idempresa,
                    'idproceso' => $idproceso,
                    'idcitamedica' => $row->idcitamedica,
                    'identidad' =>  $row->idpaciente,
                    'created_at' => date('Y-m-d H:i:s'),
                    'sms' => '0',
                    'email' => '0',
                    'sms_numero' => '',
                    'email_correo' => '',
                    'id_created_at' => $this->iduser
                );

                if (!empty($row->celular) && $row->sms_acm === '1' && $proceso->activosms === '1' && !empty($proceso->plantillasms)) {
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->build->messages)) {
                        $messageId = $row->build->messages[0]->messageId;
                        $messageCount = $row->build->messages[0]->smsCount;
                    }

                    $insert['sms'] = '1';
                    $insert['sms_numero'] = $row->celular;
                    $insert['sms_text'] = $row->mensaje;
                    $insert['sms_codigo'] = $messageId;
                    $insert['sms_count'] = $messageCount;
                }

                if (!empty($row->email) && $proceso->activomail === '1' && !empty($proceso->plantillamail)) {
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->buildMail->messages)) {
                        $messageId = $row->buildMail->messages[0]->messageId;
                        $messageCount = $row->buildMail->messages[0]->messageCount;
                    }

                    $insert['email'] = '1';                    
                    $insert['email_correo'] = $row->email;
                    $insert['email_codigo'] = $messageId;
                    $insert['email_count'] = $messageCount;
                }

                \DB::table('notificacion')->insert($insert);
            }
        }
    }

    function Auto1paso2($idproceso) {
        $empresa = new empresa();
        $citamedica = new citamedica();
        $notificacion = new notificacion();

        $proceso = new proceso();  
        $proceso = $proceso->proceso($idproceso);

        $idempresa = $empresa->idempresa('osi'); 


        $param = array('citamedica.idempresa'=> $idempresa); 

        /**/
        $fa = date('Y-m-d H:i:s');
        $inicio = date('Y-m-d H:i:s', strtotime('+180 minute', strtotime($fa)));
        $fin = date('Y-m-d H:i:s', strtotime('+181 minute', strtotime($fa))); //'+194 minute'

        $betweenFechaHora = [$inicio, $fin];   
        /**/ 

        /*** Proceso 1 - Paso 1 ***/
        $whereIn = [4, 5]; //4:pendiente, 5:confirmada         
        $datacita = $citamedica->grid($param, '', '', '', '', '', $whereIn, false, '', false, false, 'citamedica.fecha', [], false, false,false, '', '', ['citamedica.idcitamedica', 'citamedica.fecha', 'citamedica.inicio', 'citamedica.fin', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'cliente.sms_acm', 'cliente.sms_ite', 'cliente.sms_ate', 'cliente.sms_sat'], [], $betweenFechaHora);
        // dd($betweenFechaHora, $datacita);
        

        $idcitasmedicas = [];
        foreach ($datacita as $row) {  
            $idcitasmedicas[] = $row->idcitamedica;
        }

        /*** Proceso 1 - Paso 2 ***/
        $datanotificaciones = [];
        if (!empty($idcitasmedicas)) {
            $param = array('notificacion.idempresa'=> $idempresa);
            $datanotificaciones = $notificacion->grid($param, '', '', '', '', '', $idcitasmedicas);
        }        

        $datacita = $this->unirNotificacionAData($datacita, $datanotificaciones, [$idproceso]);

        // dd($datacita);
        /*** Proceso 1 - Paso 3 ***/
        //Envío a email y SMS
        
        foreach ($datacita as $i => $row) { 
            if (count($row->notificaciones) === 0) { 
                $this->sendNotification($row, $proceso, 'sms_acm');
            }
        }
        
        // dd($datacita);

        /*** Proceso 1 - Paso 4 ***/ 
        foreach ($datacita as $row) { 
            if (count($row->notificaciones) === 0) {                
                $insert = array(
                    'idempresa' => $idempresa,
                    'idproceso' => $idproceso,
                    'idcitamedica' => $row->idcitamedica,
                    'identidad' =>  $row->idpaciente,
                    'created_at' => date('Y-m-d H:i:s'),
                    'sms' => '0',
                    'email' => '0',
                    'sms_numero' => '',
                    'email_correo' => '',
                    'id_created_at' => $this->iduser
                );
                
                if (!empty($row->celular) && $row->sms_acm === '1' && $proceso->activosms === '1' && !empty($proceso->plantillasms)) { 
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->build->messages)) {
                        $messageId = $row->build->messages[0]->messageId;
                        $messageCount = $row->build->messages[0]->smsCount;
                    }

                    $insert['sms'] = '1';
                    $insert['sms_numero'] = $row->celular;
                    $insert['sms_text'] = $row->mensaje;
                    $insert['sms_codigo'] = $messageId;
                    $insert['sms_count'] = $messageCount;
                }

                if (!empty($row->email) && $proceso->activomail === '1' && !empty($proceso->plantillamail)) {
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->buildMail->messages)) {
                        $messageId = $row->buildMail->messages[0]->messageId;
                        $messageCount = $row->buildMail->messages[0]->messageCount;
                    }

                    $insert['email'] = '1';                    
                    $insert['email_correo'] = $row->email;
                    $insert['email_codigo'] = $messageId;
                    $insert['email_count'] = $messageCount;
                }

                \DB::table('notificacion')->insert($insert);
            }
        }
    }

    function Auto1paso1($idproceso) {

        $empresa = new empresa();
        $citamedica = new citamedica();
        $notificacion = new notificacion();  

        $proceso = new proceso();  
        $proceso = $proceso->proceso($idproceso);  

        $idempresa = $empresa->idempresa('osi'); 

        $param = array('citamedica.idempresa'=> $idempresa);

        $fin = date('Y-m-d H:i:s'); 
        $inicio = date('Y-m-d H:i:s', strtotime('-1 minute', strtotime($fin)));     
        $betweenCreatedAt = [$inicio, $fin];  
        // dd($betweenCreatedAt);
        /*** Proceso 1 - Paso 1 ***/
        $whereIn = [4, 5]; //4:pendiente, 5:confirmada         
        $datacita = $citamedica->grid($param, '', '', '', '', '', $whereIn, false, '', false, false, 'citamedica.fecha', [], false, false,false, '', '', ['citamedica.idcitamedica', 'citamedica.fecha', 'citamedica.inicio', 'citamedica.fin', 'cliente.identidad as idpaciente', 'cliente.celular', 'cliente.email', 'sede.nombre as sedenombre', 'cliente.entidad as paciente', 'cliente.sms_acm', 'cliente.sms_ite', 'cliente.sms_ate', 'cliente.sms_sat'], $betweenCreatedAt);
        $idcitasmedicas = [];
        foreach ($datacita as $row) {  
            $idcitasmedicas[] = $row->idcitamedica;
        }


        /*** Proceso 1 - Paso 2 ***/
        $datanotificaciones = [];
        if (!empty($idcitasmedicas)) {
            $param = array('notificacion.idempresa'=> $idempresa);
            $datanotificaciones = $notificacion->grid($param, '', '', '', '', '', $idcitasmedicas);
        }        

        $datacita = $this->unirNotificacionAData($datacita, $datanotificaciones, [$idproceso]);

        /*** Proceso 1 - Paso 3 ***/
        //Envío a email y SMS
        
        foreach ($datacita as $i => $row) { 
            if (count($row->notificaciones) === 0) { 
                $this->sendNotification($row, $proceso, 'sms_acm');
            }
        }
        // dd('EXIT');
        // dd($datacita);

        /*** Proceso 1 - Paso 4 ***/ 
        foreach ($datacita as $row) { 
            if (count($row->notificaciones) === 0) {                
                $insert = array(
                    'idempresa' => $idempresa,
                    'idproceso' => $idproceso,
                    'idcitamedica' => $row->idcitamedica,
                    'identidad' =>  $row->idpaciente,
                    'created_at' => date('Y-m-d H:i:s'),
                    'sms' => '0',
                    'email' => '0',
                    'sms_numero' => '',
                    'email_correo' => '', 
                    'id_created_at' => $this->iduser 
                ); 

                if (!empty($row->celular) && $row->sms_acm === '1' && $proceso->activosms === '1' && !empty($proceso->plantillasms)) {
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->build->messages)) {
                        $messageId = $row->build->messages[0]->messageId;
                        $messageCount = $row->build->messages[0]->smsCount;
                    }

                    $insert['sms'] = '1';
                    $insert['sms_numero'] = $row->celular;
                    $insert['sms_text'] = $row->mensaje;
                    $insert['sms_codigo'] = $messageId;
                    $insert['sms_count'] = $messageCount;
                }

                if (!empty($row->email) && $proceso->activomail === '1' && !empty($proceso->plantillamail)) {
                    $messageId = NULL;
                    $messageCount = NULL;
                    if (isset($row->buildMail->messages)) {
                        $messageId = $row->buildMail->messages[0]->messageId;
                        $messageCount = $row->buildMail->messages[0]->messageCount;
                    }

                    $insert['email'] = '1';                    
                    $insert['email_correo'] = $row->email;
                    $insert['email_codigo'] = $messageId;
                    $insert['email_count'] = $messageCount;
                }

                \DB::table('notificacion')->insert($insert);
            }
        }
    }
    

    public function sendNotification(&$row, &$proceso, $sms = '') {
        // 13.03.2020 Suspendido temporalmente por CoronaVirus

        // $sendEmail = new sendEmail();
        // $sendSms = new sendEmail(); 

        // if (!empty($row->celular) && $row->$sms === '1' && $proceso->activosms === '1' && !empty($proceso->plantillasms)) {

        //     $mensaje = $proceso->plantillasms;

        //     if (isset($row->sedenombre))
        //         $mensaje = str_replace("[SEDE]", $row->sedenombre, $mensaje);  

        //     if (isset($row->fecha))
        //         $mensaje = str_replace("[FECHA]", $row->fecha, $mensaje);  

        //     if (isset($row->inicio))
        //         $mensaje = str_replace("[HORA]", $this->transformHora($row->inicio), $mensaje);  

        //     if (isset($row->paciente))
        //         $mensaje = str_replace("[PACIENTE]", $row->paciente, $mensaje);
 
        //     $row->build = json_decode($sendSms->sendSms('51'.$row->celular, $mensaje));  
        //     $row->mensaje = $mensaje;
        // } 

        // if (!empty($row->email) && $proceso->activomail === '1' && !empty($proceso->plantillamail)) {
        //     $mensaje = $proceso->plantillamail;
            
        //     if (isset($row->sedenombre))
        //         $mensaje = str_replace("[SEDE]", $row->sedenombre, $mensaje);  

        //     if (isset($row->fecha))
        //         $mensaje = str_replace("[FECHA]", $row->fecha, $mensaje);  

        //     if (isset($row->inicio))
        //         $mensaje = str_replace("[HORA]", $this->transformHora($row->inicio), $mensaje);  

        //     if (isset($row->paciente))
        //         $mensaje = str_replace("[PACIENTE]", $row->paciente, $mensaje);

        //     $row->buildMail = json_decode($sendEmail->send($row->email, 'Portal web del paciente - Centro Médico OSI', $mensaje)); 
        // }  
    }

    private function restarDiaCompleto($fa, $resta) {

        $dia =  date('Y-m-d', strtotime($resta, strtotime($fa)));
        $inicio = $dia . ' 00:00:00';
        $fin = $dia . ' 23:59:59';

        return array($inicio, $fin);
    }

    function unirNotificacionAData($data, $datatmp, $procesos, $id = 'idcitamedica') {
        foreach($data as $row) {
            $row->notificaciones = [];
 
            foreach($datatmp as $notificacion) { 
                // if ($id === 'idcicloatencion') {
                //     dd( $notificacion, $row);
                // }

                if (in_array($notificacion->idproceso, $procesos) && $notificacion->$id === $row->$id) {
                    $row->notificaciones[] = $notificacion;
                }
            }
        }

        return $data;
    }

    /* Recordatorio de cita cada 2 horas.
    *  En el CRON linux el archivo que lee esta info es sendsms1.js
    */
    public function store() {
        
        $empresa = new empresa();
        $citamedica = new citamedica();

        $idempresa = $empresa->idempresa('osi');        

        $param = [];
        $param['citamedica.idempresa'] = $idempresa;
        $param['citamedica.fecha'] = date('Y-m-d'); 

        $fechactual = date('Y-m-d H:i:s');
        $mas2Horas = date('Y-m-d H:i:s', strtotime('+120 minute', strtotime($fechactual)));
        $suma = strtotime('+14 minute', strtotime($mas2Horas));
        
        $inicio = substr($mas2Horas, 11, 6) . '00';
        $fin = substr(date('Y-m-d H:i:s', $suma), 11, 6) . '00';
        $betweenHour = [$inicio, $fin]; 
 
        $whereIn = [4, 5]; //4:pendiente, 5:confirmada, 6:atendida, 7:cancelada          
        $datacita = $citamedica->grid($param, '', '', '', '', '', $whereIn, false, '', false, false, 'citamedica.fecha', $betweenHour, false, false,false, '', '', ['citamedica.idcitamedica', 'citamedica.fecha', 'citamedica.inicio', 'citamedica.fin', 'cliente.celular', 'sede.nombre as sedenombre']);
        
        /////////////////////// SMS NUEVO PROVEEDOR ///////////////////////////////
        $data = [];
        $horasExcluidas = array('7', '8', '9'); 
        foreach ($datacita as $row) {
            $fechaIF = $this->fechaInicioFin($row->fecha, $row->inicio, $row->fin);
            $start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);           
            $horaG = date('G', $start_s); //0 hasta 23                         
            if (!empty($row->celular) && !in_array($horaG, $horasExcluidas)) {                    
                $data[] = $row;
            }
        }

        return $this->crearRespuesta($data, 200); 
    }
    
    /* Recordatorio de cita para el dia siguiente. 
    *  En el CRON(10:00 PM) linux el archivo que lee esta info es sendsms2.js
    */
    public function storediario() {

        $empresa = new empresa();
        $citamedica = new citamedica();

        $idempresa = $empresa->idempresa('osi');

        $param = [];
        $param['citamedica.idempresa'] = $idempresa;
        $param['citamedica.fecha'] = date('Y-m-d', strtotime('+1 day', strtotime(date('Y-m-d'))));  
 
        $whereIn = [4, 5]; //4:pendiente, 5:confirmada, 6:atendida, 7:cancelada 
        $datacita = $citamedica->grid($param, '', '', '', '', '', $whereIn, false, '', false, false, 'citamedica.fecha', [], false, false,false, '', '', ['citamedica.idcitamedica', 'citamedica.fecha', 'citamedica.inicio', 'citamedica.fin', 'cliente.celular', 'sede.nombre as sedenombre']);
 
        /////////////////////// SMS NUEVO PROVEEDOR ///////////////////////////////
        $data = []; 
        foreach ($datacita as $row) {                     
            if (!empty($row->celular)) {                    
                $data[] = $row;
            }
        }

        return $this->crearRespuesta($data, 200);                               
    }
    
    //public function insistenciaCitamedica(Request $request, $enterprise) {
    public function insistenciaCitamedica(Request $request) {

        $empresa = new empresa();
        $citamedica = new citamedica();
        
        $request = $request->all(); 
        
        $idempresa =  $empresa->idempresa($request['empresa']); //$empresa->idempresa($enterprise);  
        
        $param = [];
        $param['citamedica.idempresa'] = $idempresa;
        $param['citamedica.fecha'] = date('Y-m-d');

        $whereIn = [4, 5]; //4:pendiente, 5:confirmada, 6:atendida, 7:cancelada  
        $datacita = $citamedica->grid($param, '', '', '', '', '', $whereIn);
         
        $whereIn = [];
        foreach ($datacita as $row) { 
            $whereIn[] = $row->idcitamedica;  
        }
        
        if(!empty($whereIn))
            \DB::table('citamedica')->whereIn('citamedica.idcitamedica', $whereIn)->update(array('citamedica.idestado' => 48)); //48:No asistio 
    }

    public function setearSeguroaCitaTerapeutica() {

        $citaterapeutica = new citaterapeutica();

        $idempresa = 1;
        $fecha = date('Y-m-d');

        $datacitadia = $citaterapeutica->grid(['citaterapeutica.idempresa' => $idempresa], '', '', '', '', '', [34], [], '', [], $fecha);
 
        //dd($datacitadia);
        $filtrados = [];
        $particulares = [];
        $whereIn = [];
        foreach ($datacitadia as $row) { 
            if (!$row->idaseguradora) { 
                $param = array(
                    'cicloautorizacion.idempresa' => $idempresa,
                    'cicloautorizacion.idpaciente' => $row->idpaciente,
                    'cicloatencion.idsede' => $row->idsede,
                    'cicloatencion.idestado' => 20 
                ); 
                $seguros = $citaterapeutica->segurosPaciente($param);

                if (!empty($seguros)) {
                    $row->seguros = $seguros;
                    $filtrados[] = $row;    
                } else {
                    $particulares[] = $row;
                }               

            }
        }  

        //dd($filtrados);

        foreach ($filtrados as $row) {
            if (count($row->seguros) === 1) {
                //dd($row->seguros, $row->seguros[0]->idaseguradora);
                \DB::table('citaterapeutica')
                    ->where('idcitaterapeutica', $row->idcitaterapeutica)
                    ->update(['idaseguradora' => $row->seguros[0]->idaseguradora]);

            } else {

                $idaseguradora = null;
                foreach ($row->seguros as $fila) {
                    if ($fila->idaseguradora !== 6) {
                        $idaseguradora = $fila->idaseguradora;
                        break;
                    }
                }

                //dd($idaseguradora, $row->seguros);
                \DB::table('citaterapeutica')
                    ->where('idcitaterapeutica', $row->idcitaterapeutica)
                    ->update(['idaseguradora' => $idaseguradora]);
            }
        }

        //dd($filtrados);
    }

    public function SetearCitaTerapeuticaParaTerapia(Request $request) {
        //['2018-01-01', '2018-04-30']

        // \DB::enableQueryLog();  
        $citasp = \DB::table('terapia') 
                ->select(['terapia.idterapia', 'terapia.idsede', 'terapia.idestado', 'terapia.fecha', 'terapia.idpaciente'])   
                ->where('terapia.idestado', 38)  
                //->whereBetween('terapia.fecha', ['2018-01-01', '2018-04-30'])
                ->where('terapia.fecha', date('Y-m-d'))
                ->whereNull('terapia.idcitaterapeutica')  
                ->whereNull('terapia.deleted')  
                ->orderBy('terapia.fecha', 'desc')       
                ->get()->all();   
        // dd(\DB::getQueryLog()); 

        //dd($citasp);
        $actualizados = [];
        foreach ($citasp as $row) {
            //Actualiza a pendiente citaterapeutica 
            $cita = \DB::table('citaterapeutica')
                        ->select('idcitaterapeutica')
                        ->where('idpaciente', $row->idpaciente)
                        ->where('fecha', $row->fecha)
                        ->where('idsede', $row->idsede)
                        ->whereNull('deleted')  
                        ->whereIn('idestado', [32, 33])
                        ->first();

            if ($cita) {
                //Actualiza terapia
                \DB::table('terapia')
                    ->where(['idterapia' => $row->idterapia])
                    ->update(array('idcitaterapeutica' => $cita->idcitaterapeutica)); 

                \DB::table('citaterapeutica')
                    ->where(['idcitaterapeutica' => $cita->idcitaterapeutica])
                    ->update(array('idestado' => 34)); 

                $actualizados[] = array('idterapia' => $row->idterapia, 'idcitaterapeutica' => $cita->idcitaterapeutica, 'fecha' => $row->fecha);
            }
        } 

        \Log::info(print_r('===== CRON: SetearCitaTerapeuticaParaTerapia =====', true)); 
        \Log::info(print_r($actualizados, true)); 
    }

    /* Resumen de atencion para personal OSI incluido yo.
    *  En el CRON linux el archivo que lee esta info es sendsms3.js
    */
    public function eliminarCitasterapeutas() {

        $hoy = date('Y-m-d');

        $dataTodas = \DB::table('citaterapeutica') 
                ->select('citaterapeutica.idpaciente', 'citaterapeutica.idcitaterapeutica', 'citaterapeutica.idsede')
                ->where('citaterapeutica.fecha', $hoy)
                ->whereIn('citaterapeutica.idestado', [32, 33])
                ->whereNull('citaterapeutica.deleted')
                ->whereNull('citaterapeutica.idcamilla')
                ->orderBy('citaterapeutica.inicio', 'asc')
                ->get()->all();

        //Citas "Pendiente y confirmadas" del dia de hoy
        $dataCitas = \DB::table('citaterapeutica') 
                ->select('citaterapeutica.idpaciente')
                ->distinct()
                ->where('citaterapeutica.fecha', $hoy)
                ->whereIn('citaterapeutica.idestado', [32, 33])
                ->whereNull('citaterapeutica.deleted')
                ->whereNull('citaterapeutica.idcamilla')
                ->get()->all();
        // dd($dataCitas);
        $tmpdataCitas = [];
        foreach ($dataCitas as $value) { 
            $tmpdataCitas[] = $value->idpaciente;
        }

        //Terapias "Atendidas" del dia de hoy
        $dataTerapias = \DB::table('terapia') 
                ->select('terapia.idpaciente')
                ->distinct()
                ->where('terapia.fecha', $hoy)
                ->whereIn('terapia.idestado', [38])
                ->whereNull('terapia.deleted')
                ->get()->all();

        $tmpdataTerapias = [];
        foreach ($dataTerapias as $value) {
            $tmpdataTerapias[] = $value->idpaciente;
        }

        //PacientesCitas - PacientesTerapias
        $resultado = array_diff($tmpdataCitas, $tmpdataTerapias);

        //Citas "Pendiente y confirmadas" con fecha mayor a la dehoy, de pacientes faltantes de hoy
        $dataEliminar = \DB::table('citaterapeutica') 
                ->select('citaterapeutica.idcitaterapeutica' ,'citaterapeutica.idpaciente', 'citaterapeutica.idsede')
                ->where('citaterapeutica.fecha', '>', $hoy) 
                ->whereIn('citaterapeutica.idestado', [32, 33])
                ->whereIn('citaterapeutica.idpaciente', $resultado)
                ->whereNull('citaterapeutica.deleted')
                ->whereNull('citaterapeutica.idcamilla') 
                ->get()->all();
        
        $eliminados = [];
        foreach ($dataEliminar as $value) { 
            $value->idcitareferencia = null; 
            foreach ($dataTodas as $cita) {
                if ($cita->idpaciente === $value->idpaciente && $cita->idsede === $value->idsede) {
                    $value->idcitareferencia = $cita->idcitaterapeutica;
                    break;
                }
            }
            
            $eliminados[] = $value;
            // $eliminados[] = $value->idcitaterapeutica;
        } 
 
        if ($eliminados) { 
            foreach ($eliminados as $value) {
                $iduser = 4844;
                \DB::table('citaterapeutica')
                        ->where('idcitaterapeutica', $value->idcitaterapeutica)
                        ->update([
                            'idestado' => 35, 
                            'idcitareferencia' => $value->idcitareferencia, 
                            'id_updated_at' => $iduser, 
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);

                // \DB::table('citaterapeutica')
                //         ->whereIn('idcitaterapeutica', $eliminados)
                //         ->update(['idestado' => 35, 'id_updated_at' => $iduser, 'updated_at' => date('Y-m-d H:i:s')]);
            }
            
            \Log::info(print_r('===== CRON: eliminarCitasterapeutas =====', true));
            \Log::info(print_r($eliminados, true));
        }
    }

    //NO VA ESTO ESTA LIGADO CON UN NUEVO ESTADO 88: RESERVACON donde se hace programacion de reservas.
    public function eliminarCitasterapeutasV2(Request $request) {

        //Citas "Pendiente y confirmadas" del dia de hoy
        $dataCitas = \DB::table('citaterapeutica') 
                ->select('citaterapeutica.idpaciente')
                ->distinct()
                ->where('citaterapeutica.fecha', date('Y-m-d'))
                ->whereIn('citaterapeutica.idestado', [32, 33])
                ->whereNull('citaterapeutica.deleted')
                ->whereNotNull('citaterapeutica.idcamilla')
                ->get()->all();

        $tmpdataCitas = [];
        foreach ($dataCitas as $value) {
            $tmpdataCitas[] = $value->idpaciente;
        } 

        //Terapias "Atendidas" del dia de hoy
        $dataTerapias = \DB::table('terapia') 
                ->select('terapia.idpaciente')
                ->distinct()
                ->where('terapia.fecha', date('Y-m-d'))
                ->whereIn('terapia.idestado', [38])
                ->whereNull('terapia.deleted')
                ->get()->all();

        $tmpdataTerapias = [];
        foreach ($dataTerapias as $value) {
            $tmpdataTerapias[] = $value->idpaciente;
        }

        //PacientesCitas - PacientesTerapias
        $resultado = array_diff($tmpdataCitas, $tmpdataTerapias);
        // $resultado = [28174]; //Lopez Cervantes, Jose Alejandro
        //Citas "Pendiente y confirmadas" con fecha mayor a la dehoy, de pacientes faltantes de hoy 
        $dataEliminar = \DB::table('citaterapeutica') 
                ->select('citaterapeutica.*')
                ->where('citaterapeutica.fecha', '>', date('Y-m-d')) 
                ->whereIn('citaterapeutica.idestado', [32, 33])
                ->whereIn('citaterapeutica.idpaciente', $resultado)
                ->whereNull('citaterapeutica.deleted')
                ->whereNotNull('citaterapeutica.idcamilla')
                ->get()->all();

        // dd($dataEliminar);
        $eliminados = [];
        $eliminadosInsert = [];
        foreach ($dataEliminar as $value) {
            $eliminados[] = $value->idcitaterapeutica;

            $eliminadosInsert[] = array(
                'idempresa' => $value->idempresa,
                'idsede' => $value->idsede,
                'idterapista' => $value->idterapista,
                'idpaciente' => $value->idpaciente,
                'idestado' => $value->idestado,
                'fecha' => $value->fecha,
                'inicio' => $value->inicio,
                'fin' => $value->fin,
                'idcamilla' => $value->idcamilla,
                'reservaportal' => $value->reservaportal,
                'created_at' => date('Y-m-d H:i:s'),
                'id_created_at' => 4844
            );
        }

        // dd($eliminados); 
        // dd($eliminadosInsert, $eliminados);

        if ($eliminados) {
            $iduser = 4844;

            $param = [
                'idestado' => 88, //88:disponible
                'idpaciente' => null, 
                'reservaportal' => 0, 
                'updated_at' => date('Y-m-d H:i:s'), 
                'id_updated_at' => $iduser 
            ];

            \DB::beginTransaction();
            try {

                \DB::table('citaterapeutica')
                        ->whereIn('idcitaterapeutica', $eliminados)
                        ->update($param);

                \DB::table('citaterapeuticaeliminado')
                        ->insert($eliminadosInsert);

            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            dd($eliminados, $eliminadosInsert);      
        } else {
            dd('vacio NO ELIMINO');
        }

    }

    public function eliminarCitasterapeutasYANOVA(Request $request) {
        
        $idempresa = 1;

        $empresa = new empresa();
        $cicloatencion = new cicloatencion();
        $citaterapeutica = new citaterapeutica();
        $sede = new sede();

        $codigoaleatorio = $this->generarCodigo(10);
        // dd($codigoaleatorio);
        $datasede = $sede->sedes($idempresa);
        $fecha = date('Y-m-d'); //'2018-03-24'; 

        $fechaIF = $this->fechaInicioFin(date('d-m-Y'), date('H:i:s'), date('H:i:s'));
        $fecha_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']); 
        
        $sendEmail = new sendEmail();
 
        $inasistencias = [];
        $eliminados = [];
        $correosreales = [];
        $eliminadosreales = [];
        foreach ($datasede as $sede) {

            //Reservaciones del día de hoy, 32:Pendiente y 33:Confirmada. 
            $datacitadia = $citaterapeutica->grid(['citaterapeutica.idempresa' => $idempresa, 'citaterapeutica.fecha' => $fecha, 'citaterapeutica.idsede' => $sede->idsede], '', '', '', '', '', [32, 33]);  
 
            $whereIn = []; 
            foreach ($datacitadia as $row) {
                if (!in_array($row->idpaciente, $whereIn))  
                    $whereIn[] = $row->idpaciente;
            } 
            
            $fields = ['cicloatencion.idcicloatencion', 'cicloatencion.fecha', 'cliente.identidad as idcliente', 'cliente.entidad as paciente', 'cliente.email', 'sede.nombre as sedenombre'];

            $dataciclo = [];
            if ($whereIn){
                //Ciclo último para cada paciente. Importante el orden DESC            
                $dataciclotmp = $cicloatencion->grid(['cicloatencion.idempresa' => $idempresa, 'cicloatencion.idsede' => $sede->idsede], '', '', '', 'cicloatencion.fecha', 'desc', false, $fields, false, false, false, false, false, [], false, $whereIn); 

                $tmpCliente = [];
                foreach($dataciclotmp as $row) { 
                    if (!in_array($row->idcliente, $tmpCliente)) {
                        $dataciclo[] = $row;
                        $tmpCliente[] = $row->idcliente;
                    }
                }
            }

            
            // Paciente que tienen Reservaciones, pero no tienen ciclo. Reservaciones deben eliminarse.
            $whereInSinCiclo = [];
            foreach($whereIn as $valor) {
                $encontrado = false;
                foreach($dataciclo as $row) {
                    if ($row->idcliente === $valor) {
                        $encontrado = true;
                    }
                } 
                if(!$encontrado) { 
                    $whereInSinCiclo[] = $valor;
                }
            }
 
            if ($whereInSinCiclo) {
                foreach($whereInSinCiclo as $idcliente) {
                    $fechatmp = date('Y-m-d'); 
                    $datacitaTmp = $citaterapeutica->grid(['citaterapeutica.idempresa' => $idempresa, 'citaterapeutica.idsede' => $sede->idsede, 'citaterapeutica.idpaciente' => $idcliente], '', '', '', '', '', [32, 33], [], '', [], $fechatmp);

                    $programados = NULL;
                    $programadoscant = 0;
                    foreach ($datacitaTmp as $cita) {   
                        $programados .= ($programados?', ':'') . $cita->idcitaterapeutica;
                        $eliminados[] = $cita->idcitaterapeutica;
                        $programadoscant++;
                    }

                    $eliminadosreales[] = array(
                        'idempresa' => $idempresa,
                        'coderandom' => $codigoaleatorio,
                        'idcicloatencion' =>  NULL,
                        'paciente' => $idcliente,
                        'sedenombre' => $sede->idsede,
                        'fechaopen' => NULL,
                        'programados' => $programados,
                        'programadoscant' => $programadoscant,
                        'asistencias' => NULL,
                        'inasistencias' => NULL,
                        'inasistenciascant' => NULL,
                        'created_at' => date('Y-m-d H:i:s') 
                    );
                }
            }
        

            //Relacionas las citas al último ciclo
            //Citas de pacientes 32:Pendiente, 33:Confirmada 
            $datacita = [];
            foreach ($dataciclo as $row) { 

                $fechatmp = $this->formatFecha($row->fecha, 'yyyy-mm-dd');  
                $datacitaTmp = $citaterapeutica->grid(['citaterapeutica.idempresa' => $idempresa, 'citaterapeutica.idsede' => $sede->idsede, 'citaterapeutica.idpaciente' => $row->idcliente], '', '', '', '', '', [32, 33, 34], [], '', [], $fechatmp);

                foreach ($datacitaTmp as $cita) {   
                    $fechaIF = $this->fechaInicioFin($cita->fecha, $cita->inicio, $cita->inicio);
                    $cita->fecha_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);

                    $cita->idcicloatencion = $row->idcicloatencion;
                    $cita->fechaopen = $row->fecha;
                    $datacita[] = $cita;
                } 
            } 

            
            foreach($datacita as $cita) {  

                if(!isset($inasistencias[$cita->idcicloatencion])) {
                    $inasistencias[$cita->idcicloatencion] = array(
                        'idcicloatencion' => $cita->idcicloatencion, 
                        'paciente' => $cita->paciente,
                        'sedenombre' => $cita->sedenombre,
                        'fechaopen' => $cita->fechaopen,
                        'programados' => [],
                        'asistencias' => [],
                        'inasistencias' => []
                    );
                }

                if ($cita->idestado === 32 || $cita->idestado === 33) {      
                    if ($cita->fecha_s > $fecha_s) {
                        $inasistencias[$cita->idcicloatencion]['programados'][] = $cita;
                    } else {
                        $inasistencias[$cita->idcicloatencion]['inasistencias'][] = $cita;
                    } 
                }

                if ($cita->idestado === 34) { 
                    $inasistencias[$cita->idcicloatencion]['asistencias'][] = $cita;
                }
            }
        }

             
        
        foreach ($inasistencias as $ciclo) { 

            if (count($ciclo['inasistencias']) > 3 && count($ciclo['programados']) > 0) {

                $programados = NULL; 
                $asistencias = NULL; 
                $inasistencias = NULL; 

                foreach($ciclo['programados'] as $row) {
                    $programados .= ($programados?', ':'') . $row->idcitaterapeutica;
                    $eliminados[] = $row->idcitaterapeutica;
                }

                foreach($ciclo['asistencias'] as $row) {
                    $asistencias .= ($asistencias?', ':'') . $row->idcitaterapeutica;
                }

                foreach($ciclo['inasistencias'] as $row) {
                    $inasistencias .= ($inasistencias?', ':'') . $row->idcitaterapeutica;
                }

                $eliminadosreales[] = array(
                    'idempresa' => $idempresa,
                    'coderandom' => $codigoaleatorio,
                    'idcicloatencion' =>  $ciclo['idcicloatencion'],
                    'paciente' => $ciclo['paciente'],
                    'sedenombre' => $ciclo['sedenombre'],
                    'fechaopen' => $ciclo['fechaopen'],
                    'programados' => $programados,
                    'programadoscant' => count($ciclo['programados']),
                    'asistencias' => $asistencias,
                    'inasistencias' => $inasistencias,
                    'inasistenciascant' => count($ciclo['inasistencias']),
                    'created_at' => date('Y-m-d H:i:s') 
                );
            }

            // if($ciclo['email'] && $ciclo['nroinasistencias'] < 3) {
            //     $correosreales[] = $ciclo;
            // }
        }
  
        if($eliminadosreales){

            $iduser = 4844;

            // \DB::table('citasteliminados')->insert($eliminadosreales);    
            // \DB::table('citaterapeutica')
            //     ->whereIn('idcitaterapeutica', $eliminados)
            //     ->update(['idestado' => 35, 'id_updated_at' => $iduser, 'updated_at' => date('Y-m-d H:i:s')]);

            $dataInsert = [];
            foreach($eliminados as $idcitaterapeutica) {
                $dataInsert[] = array(
                    'idcitaterapeutica' => $idcitaterapeutica,
                    'descripcion' => 'Cambió estado a "Cancelado" por tarea CRON',
                    'created_at' => date('Y-m-d H:i:s'),
                    'id_created_at' => $iduser 
                );
            }

            // \DB::table('citaterapeuticalog')->insert($dataInsert); 
        }
        

        // if($correosreales){         
        //     \DB::table('mailinasistencia')->insert($correosreales);             
        // } 


        // if($correosreales) {              
            // foreach($correosreales as $ciclo) { 
                //$ciclo['email'] = 'chaucachavez@gmail.com';
                //$build = $sendEmail->send($ciclo['email'], 'Portal web del paciente - Centro Médico OSI', $this->htmlemail($ciclo));                     
            // } 
        // } 


        //dd($correosreales, $eliminadosreales);
        dd($eliminadosreales);
    }
    
    private function htmlemailproceso($paciente, $mensaje) {

        return '<img src="https://sistemas.centromedicoosi.com/img/osi/email/emailhead.png" width="100%">
                <div style="padding: 0px 30px 0px 30px; color: #333; font-family: Arial; line-height: 20px;">
                    <h3><strong>Estimado paciente: '.mb_strtoupper($paciente).'.</strong></h3>
                    <div style="line-height: 25px; padding-left: 10px;">
                       ' . $mensaje. '
                    </div> 
                    <p>Que tengas un buen día.</p>
                </div>
                <img src="https://sistemas.centromedicoosi.com/img/osi/email/emailfooter.jpg" width="100%">';
    }

    private function htmlemail($ciclo) {

        //Ciclo
        $strciclo1 = '
        <table align="center" border="1" cellpadding="0" cellspacing="0" width="600" style="border-collapse: collapse;">
            <thead>
              <tr> 
                <th scope="col">Sede</th> 
                <th scope="col">Ciclo de atención</th> 
              </tr>
            </thead>
            <tbody>'; 

        $strciclo2 = '
              <tr>
                <th scope="row"></th>
                <td></td> 
                <td></td> 
              </tr>'; 

        $strciclo3 = '
            </tbody>        
        </table>'; 

        //Faltas
        $strfalta1 = '
        <table align="center" border="1" cellpadding="0" cellspacing="0" width="600" style="border-collapse: collapse;">
            <thead>
              <tr>
                <th scope="col">#</th>
                <th scope="col">Sede</th> 
                <th scope="col">Reservación</th> 
                <th scope="col">Asistencia</th> 
              </tr>
            </thead>
            <tbody>'; 

        $strfalta2 = '
              <tr>
                <th scope="row"></th>
                <td></td> 
                <td></td> 
              </tr>'; 
              
        $strfalta3 = '
            </tbody>        
        </table>';
          
        $ciclos = explode("|", $ciclo['ciclos']); 
        //dd($ciclos);       
        foreach($ciclos as $cadena) {  
            $array = explode("*", $cadena);
            //dd($array, $cadena);
            //OSI BENAVIDES*29/01/2018*18187
            $strciclo2 .= '
              <tr> 
                <td>'.$array[0].'</td> 
                <td>Ciclo atención aperturado el '.$array[1].'</td> 
              </tr>';
        }
        $tablaciclo = $strciclo1 . $strciclo2 . $strciclo3;

        
        $inasistencias = explode("|", $ciclo['citas']);
        $i = count($inasistencias);
        foreach($inasistencias as $cadena) {  
            $array = explode("*", $cadena);
            //"OSI BENAVIDES*24/03/2018*17:15:00*'No asistió'

            $estado = $array[3] === 'No asistió' ? ('<span style="color: #F00">'.$array[3].'</span>') : $array[3];
            $strfalta2 .= '
              <tr>
                <th scope="row">'.($i--).'</th>
                <td align="center">'.$array[0].'</td> 
                <td align="center">'.$array[1]. ' - ' .$array[2].'</td> 
                <td align="center">'.$estado.'</td> 
              </tr>';
        }
        $tablafalta = $strfalta1 . $strfalta2 . $strfalta3;

        return '<img src="https://sistemas.centromedicoosi.com/img/osi/email/emailhead.png" width="100%">
                <div style="padding: 0px 30px 0px 30px; color: #333; font-family: Arial; line-height: 20px;">
                    <h3><strong>Estimado paciente: '.mb_strtoupper($ciclo['paciente']).'.</strong></h3>
                    <p>Usted tiene '.$ciclo['cantciclos'].' ciclo(s) de atención aperturado: </p>
                    
                    <div style="line-height: 25px; padding-left: 10px;">
                        '. $tablaciclo . '                               
                    </div>
                    <br>
                    <p>Usted tiene '.$ciclo['nroinasistencias'].' inasistencias en sus reservaciones a terapias: </p>
                    <div style="line-height: 25px; padding-left: 10px;">
                        '. $tablafalta . '
                    </div>
                    <p><strong>Recordar:</strong> A su <span style="color: #F00">tercera inasistencia</span> a sus terapias, sus reservas programadas serán eliminadas. Sin embargo podrás volverlas a agendar llamando al <strong>739 0888</strong> Centro Médico OSI.</p>
                    <p>Que tengas un buen día.</p>
                </div>
                <img src="https://sistemas.centromedicoosi.com/img/osi/email/emailfooter.jpg" width="100%">';
    }

    function generarCodigo($longitud) {
         $key = '';
         $pattern = '1234567890abcdefghijklmnopqrstuvwxyz';
         $max = strlen($pattern)-1;
         for($i=0;$i < $longitud;$i++) $key .= $pattern{mt_rand(0,$max)};
         return $key;
    }  

    public function automatizacionCantEstado(Request $request) { 
        //85: No efectuado 86: Efectuado 87: Vencido 
        $idempresa = 1;

        //Pasar de No efecuato a efectuado
        $tarea = new tarea();
        $param = array(
            'tarea.idempresa' => $idempresa,
            'tarea.idestado' => 85 
        );

        $data = $tarea->grid($param); 

        foreach($data as $row) {

            $row->agendocita = null;
            $row->agendoasitio = null;

            /* Próxima cita mas cercana */
            $fecha = $this->formatFecha(substr($row->created_at, 0, 10), 'yyyy-mm-dd');
            $inicio = substr($row->created_at, 11, 8);  

            $citarow = null;
            if ($row->idcitamedica) {   // 4:pendiente, 5:confirmada, 6:atendida
                $citarow = \DB::table('citamedica')  
                            ->select('citamedica.fecha', 'citamedica.inicio', 'citamedica.idestado')                            
                            ->whereRaw("CONCAT(fecha,' ',inicio) > '".$fecha." ".$inicio."'") 
                            ->where(['citamedica.idpaciente' => $row->identidad, 
                                     'citamedica.idsede' => $row->idsede,
                                    ]) 
                            ->whereIn('citamedica.idestado', [4, 5, 6])   
                            ->whereNull('citamedica.deleted')
                            ->first();  
            }

            if ($row->idcitaterapeutica || $row->idcicloatencion) { //32:pendiente, 33:confirmada, 34:atendida, 
                
                $citarow = \DB::table('citaterapeutica') 
                            ->select('citaterapeutica.fecha', 'citaterapeutica.inicio', 'citaterapeutica.idestado')
                            ->whereRaw("CONCAT(fecha,' ',inicio) > '".$fecha." ".$inicio."'") 
                            ->where(['citaterapeutica.idpaciente' => $row->identidad, 
                                     'citaterapeutica.idsede' => $row->idsede,
                                    ]) 
                            ->whereIn('citaterapeutica.idestado', [32, 33, 34])   
                            ->whereNull('citaterapeutica.deleted')
                            ->first();  
            }

            if($citarow) {
                $fecha = $this->formatFecha($citarow->fecha); 
                $inicio = $citarow->inicio; 

                $row->agendocita = $fecha . ' ' .$inicio; 
            }
        } 

        $idtareaIn = [];
        foreach($data as $row) {
            if ($row->agendocita)
                $idtareaIn[] = $row->idtarea;
        }

        if (!empty($idtareaIn)) {
            \DB::table('tarea')
            ->whereNull('deleted') 
            ->whereIn('idtarea', $idtareaIn)
            ->update(['idestado' => 86]);
        } 
 
        //Setear a Vencido los No efectuado
        \DB::table('tarea')
            ->whereNull('deleted') 
            ->where(['idempresa' => $idempresa, 'idestado' => 85, 'cantdiasrest' => 1])
            ->update(['idestado' => 87]); 

        // Descontar un dia
        for ($i=1; $i < 6; $i++) {
            \DB::table('tarea')
                ->whereNull('deleted') 
                ->where(['idempresa' => $idempresa, 'cantdiasrest' => $i])
                ->update(['cantdiasrest' => ($i - 1)]);
        }
    }

    public function is_valid_email($str)
    {
      return (false !== filter_var($str, FILTER_VALIDATE_EMAIL));
    }

    public function enviarEmailInvoice(Request $request) { 
        // exit;
        //Opcion 1
        $data = \DB::table('venta')
                ->join('entidad as afiliado', 'venta.idafiliado', '=', 'afiliado.identidad')
                ->join('documentofiscal', 'venta.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal')        
                ->select('afiliado.numerodoc', 'documentofiscal.codigosunat', 'venta.serie', 'venta.serienumero', 'venta.cpecorreo', 'venta.idventa', 'venta.idafiliado', 'documentofiscal.nombre as nombredocfiscal', 'venta.fechaventa', 'afiliado.entidad as afiliado')                
                ->where('venta.fechaventa', date('Y-m-d'))
                // ->whereIn('venta.fechaventa', ['2020-01-07', '2020-01-08'])  
                ->where('venta.cpeemision', '0')
                ->whereNull('venta.deleted') 
                ->whereNull('venta.correolog')
                ->whereNotNull('venta.cpecorreo')    
                ->orderBy('venta.fechaventa', 'asc')            
                ->take(30)
                ->get()
                ->all();  
        //Opcion 2 

        // dd($data);

        try{ 
            // dd($data);
            foreach ($data as $venta) {

                if ($this->is_valid_email($venta->cpecorreo)) {

                    $venta->fechaventa = $this->formatFecha($venta->fechaventa);
                    $filePDF = $this->pathImg . $venta->numerodoc . '-' . $venta->codigosunat . '-' . $venta->serie . '-' . $venta->serienumero . '.pdf';
                    $fileXML = $this->pathImg . $venta->numerodoc . '-' . $venta->codigosunat . '-' . $venta->serie . '-' . $venta->serienumero . '.xml';                   
                    if (file_exists($filePDF) && file_exists($fileXML)) { 

                        $inicio = date('Y-m-d H:i:s');
                        $return = Mail::to($venta->cpecorreo)->send(new InvoiceSend($venta, $filePDF, $fileXML)); 
                        $fin = date('H:i:s');

                        \DB::table('venta')
                            ->where('idventa', $venta->idventa) 
                            ->update(array('correolog' => '1:'.$inicio.'-'.$fin)); 

                    } else {
                        \DB::table('venta')
                            ->where('idventa', $venta->idventa) 
                            ->update(array('correolog' => '0:PDF y XML no existe'));
                            
                        \Log::info(print_r($venta->idventa . ': 0:PDF y XML no existe', true));
                    } 

                }  else {
                    \Log::info(print_r($venta->cpecorreo . ': No es un formato de correo para PHP.', true));
                }        
            } 
        } 
        catch(\Exception $e){            
            \Log::info(print_r($e->getMessage(), true));  
        }
    }

    public function reGenerarPDF(Request $request) { 
        $request = $request->all();


        $ventas = \DB::table('venta')
            ->join('entidad as afiliado', 'venta.idafiliado', '=', 'afiliado.identidad')
            ->join('documentofiscal', 'venta.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal')        
            ->select('afiliado.numerodoc', 'documentofiscal.codigosunat', 'venta.serie', 'venta.serienumero', 'venta.cpecorreo', 'venta.idventa')
            // ->where('venta.fechaventa', '2019-12-06') 
            // ->whereIn('venta.serie', ['B001', 'B002', 'B003', 'B004', 'F001', 'F002', 'F003', 'F004'])
            ->where('venta.idventa', $request['idventa']) 
            ->whereNull('venta.deleted')  
            ->get()
            ->all();  
        
        foreach ($ventas as $venta) {
            $invoice = new invoiController();
            $comprobante = $this->cpeComprobante('osi', $venta->idventa);

            if ($comprobante) {
                $data = $comprobante['comprobante'];
                $telefono = $comprobante['telefono'];
                $cpecorreo = $comprobante['cpecorreo'];
                $idafiliado = $comprobante['idafiliado'];
                $archivoEmision = $this->archivoFe($data);

                \Log::info(print_r('Regenerando ID: ' . $venta->idventa, true));    
                $invoice->reporte($venta->idventa, $data, $telefono, $archivoEmision['tipo'], $cpecorreo, $idafiliado); 
            }
        } 
       
    }

    private function cpeComprobante($enterprise, $id) {
        $empresa = new empresa();
        $Objventa = new venta();  
        $idempresa = $empresa->idempresa($enterprise);
        $venta = $Objventa->venta($id, false, true); 

        $ventadet = $Objventa->ventadet($id);
        $ventafactura = $Objventa->ventafactura($id);

        $documentoserie = \DB::table('documentoserie')
                ->join('documentofiscal', 'documentoserie.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal')
                ->join('sede', 'documentoserie.idsede', '=', 'sede.idsede') 
                ->select('documentoserie.*', 'documentofiscal.codigosunat', 'sede.direccion', 'sede.telefono')
                ->where(array(
                    'documentoserie.identidad' => $venta->idafiliado,
                    'documentoserie.iddocumentofiscal' => $venta->iddocumentofiscal,
                    'documentoserie.serie' => $venta->serie
        ))->first();
         
        if ($documentoserie->seesunat !== '1') { 
            return NULL;
        }
 
        if (empty($documentoserie->sucursalsunat)) { 
            return NULL;
        } 
          
        $objCliente = new entidad();

        $dataAfiliado = \DB::table('entidad') 
                    ->join('documento', 'documento.iddocumento', '=', 'entidad.iddocumento')
                    ->select('entidad.*', 'departamento.nombre as departamento', 'provincia.nombre as provincia', 'ubigeo.nombre as distrito', 'documento.codigosunat') 
                    ->leftJoin('ubigeo as departamento', \DB::raw("CONCAT(SUBSTRING(entidad.idubigeo,1,5), '0000')"),'=', 'departamento.idubigeo')
                    ->leftJoin('ubigeo as provincia', \DB::raw("CONCAT(SUBSTRING(entidad.idubigeo,1,7), '00')"),'=', 'provincia.idubigeo')
                    ->leftJoin('ubigeo', 'entidad.idubigeo','=', 'ubigeo.idubigeo')
                    ->where('entidad.identidad', $venta->idafiliado)
                    ->whereNull('entidad.deleted')
                    ->first(); 

        $dataCliente = \DB::table('entidad') 
                    ->join('documento', 'documento.iddocumento', '=', 'entidad.iddocumento')
                    ->select('entidad.*', 'departamento.nombre as departamento', 'provincia.nombre as provincia', 'ubigeo.nombre as distrito', 'documento.codigosunat') 
                    ->leftJoin('ubigeo as departamento', \DB::raw("CONCAT(SUBSTRING(entidad.idubigeo,1,5), '0000')"),'=', 'departamento.idubigeo')
                    ->leftJoin('ubigeo as provincia', \DB::raw("CONCAT(SUBSTRING(entidad.idubigeo,1,7), '00')"),'=', 'provincia.idubigeo')
                    ->leftJoin('ubigeo', 'entidad.idubigeo','=', 'ubigeo.idubigeo')
                    ->where('entidad.identidad', $venta->idcliente)
                    // ->where('entidad.identidad', 240)
                    ->whereNull('entidad.deleted')
                    ->first();

        $tipocomprobante = '';
        $temporal = '';
        switch ($venta->codigosunat) {
            case '01': //Factura
                $tipocomprobante = 'factura';
                $temporal = strlen($venta->serie) === 3 ? ('F'.$venta->serie) : $venta->serie;
                break;

            case '03': //Boleta
                $tipocomprobante = 'boleta';
                $temporal = strlen($venta->serie) === 3 ? ('B'.$venta->serie) : $venta->serie;
                break;

            case '07': //Nota de crédito
                $tipocomprobante = 'notaCredito'; 

                if ($venta->refcodigosunat === '01') {
                    $temporal = strlen($venta->serie) === 3 ? ('F'.$venta->serie) : $venta->serie;
                }

                if ($venta->refcodigosunat === '03') {
                    $temporal = strlen($venta->serie) === 3 ? ('B'.$venta->serie) : $venta->serie;
                }
                break;
        }

        if (empty($tipocomprobante)) { 
            return NULL;
        }  
               
        $venta->total = (float)$venta->total; 
        $venta->subtotal = (float)$venta->subtotal;          
        $venta->valorimpuesto = (float)$venta->valorimpuesto;  
        
        $comprobante = array(
            $tipocomprobante => array(
                'IDE' => array( 
                    'numeracion' => $venta->serie . '-' .$venta->serienumero,
                    'fechaEmision' => $this->formatFecha($venta->fechaventa, 'yyyy-mm-dd'),                
                    'tipoMoneda' => 'PEN' 
                ),
                'EMI' => array(
                    'tipoDocId' => (string) $dataAfiliado->codigosunat,
                    'numeroDocId' => $dataAfiliado->numerodoc, 
                    'razonSocial' => $dataAfiliado->entidad, 
                    'direccion' => $documentoserie->direccion,
                    'codigoPais' => substr($dataAfiliado->idubigeo, 0, 2),
                    'codigoAsigSUNAT' => $documentoserie->sucursalsunat //150113
                ),
                'REC' => array(
                    'tipoDocId' => (string) $dataCliente->codigosunat,
                    'numeroDocId' => $dataCliente->numerodoc,
                    'razonSocial' => $dataCliente->entidad 
                ),
                'CAB' => array(
                    'gravadas' => array(
                        'codigo' => '1001', //Catálogo 14:Total valor de venta - operaciones gravadas
                        'totalVentas' => number_format($venta->subtotal, 2)
                    ),
                    'totalImpuestos' => array(
                        array(
                            'idImpuesto' => '1000', //Catálogo 05:IGV Impuesto General a las Ventas
                            'montoImpuesto' => number_format($venta->valorimpuesto, 2)
                        )
                    ), 
                    'importeTotal' => number_format($venta->total, 2),
                    'tipoOperacion' => '0101', //Catálogo 51:Venta interna
                    'leyenda' => array(
                        array(
                            'codigo' => '1000', //Catálogo 52:Monto en Letras
                            'descripcion' => $this->num2letras((float)$venta->total),
                        )
                    ),
                    'montoTotalImpuestos' => number_format($venta->valorimpuesto, 2) 
                ),
                'DET' => array() 
            )
        );  

        switch ($venta->codigosunat) {
            case '01': //Factura 
                $comprobante[$tipocomprobante]['IDE']['codTipoDocumento'] = $venta->codigosunat;
                break;
            case '03': //Boleta
                $comprobante[$tipocomprobante]['IDE']['codTipoDocumento'] = $venta->codigosunat;
                break;
            case '07': //Nota de crédito 

                if ($venta->tiponotacredito === '1') {
                    $codigoMotivo = '01';
                }

                if ($venta->tiponotacredito === '2') {
                    $codigoMotivo = '06';
                }

                if ($venta->tiponotacredito === '3') {
                    $codigoMotivo = '07';
                } 
                
                //$venta->refserie siempre va a ser igual a $venta->serie, excepto si se trata de un boleta fisica o emitida por portal
                $comprobante[$tipocomprobante]['DRF'] = array();
                $comprobante[$tipocomprobante]['DRF'][] = array(
                    'tipoDocRelacionado' => $venta->refcodigosunat,
                    'numeroDocRelacionado' => $venta->refserie . '-' . $venta->refserienumero,
                    'codigoMotivo' => $codigoMotivo,
                    'descripcionMotivo' => $venta->descripcion
                );
                //Tb podria añadir Guia de Remisión ejemplo "idventaguiaref"
                break;
        }

        if (!empty($dataCliente->direccion)) {
            $comprobante[$tipocomprobante]['REC']['direccion'] = $dataCliente->direccion; 
        }        

        if (!empty($dataCliente->distrito)) {
            $comprobante[$tipocomprobante]['REC']['distrito'] = $dataCliente->distrito;
        }

        if (!empty($dataCliente->provincia)) {
            $comprobante[$tipocomprobante]['REC']['provincia'] = $dataCliente->provincia;
        }

        if (!empty($dataCliente->departamento)) {
            $comprobante[$tipocomprobante]['REC']['departamento'] = $dataCliente->departamento;
        }

        if (!empty($dataCliente->idubigeo)) {
            $comprobante[$tipocomprobante]['REC']['codigoPais'] = substr($dataCliente->idubigeo, 0, 2);
        }

        if (!empty($dataCliente->telefono)) {
            $comprobante[$tipocomprobante]['REC']['telefono'] = $dataCliente->telefono;
        } 

        if ((float) $venta->descuento > 0) {
            $venta->descuento = (float) $venta->descuento;        
            $montoBaseCargoDescuento = $venta->subtotal + $venta->descuento; 
            $factorCargoDescuento = ($venta->descuento / $montoBaseCargoDescuento); 
            // a/b/100
            $comprobante[$tipocomprobante]['CAB']['cargoDescuento'] = array(
                array(
                    'indicadorCargoDescuento' => 'false', //true: cargo false: descuento
                    'codigoCargoDescuento' => '02', //Catálogo 53:Descuentos globales que afectan la base imponible del IGV/IVAP
                    'factorCargoDescuento' => number_format($factorCargoDescuento, 5),
                    'montoCargoDescuento' => (string) $venta->descuento, 
                    'montoBaseCargoDescuento' => number_format($montoBaseCargoDescuento, 2)
                )
            ); 
        } 

        // Campos adicionales
        switch ($venta->codigosunat) {
            case '01': //Factura 
                if(isset($ventafactura)) {
                    $comprobante[$tipocomprobante]['ADI'] = array();
                }

                if (isset($ventafactura) && $ventafactura->paciente) { 
                    $comprobante[$tipocomprobante]['ADI'][] = array(
                        'tituloAdicional' => 'Paciente', 
                        'valorAdicional' => $ventafactura->paciente 
                    );
                }

                if (isset($ventafactura) && $ventafactura->titular) {
                    $comprobante[$tipocomprobante]['ADI'][] = array(
                        'tituloAdicional' => 'Parentesco', 
                        'valorAdicional' => $ventafactura->titular 
                    );
                }

                if (isset($ventafactura) && $ventafactura->empresa) {
                    $comprobante[$tipocomprobante]['ADI'][] = array(
                        'tituloAdicional' => 'Empresa',
                        'valorAdicional' => $ventafactura->empresa 
                    );
                }

                if (isset($ventafactura) && $ventafactura->diagnostico) {
                    $comprobante[$tipocomprobante]['ADI'][] = array(
                        'tituloAdicional' => 'Diagnóstico', 
                        'valorAdicional' => $ventafactura->diagnostico 
                    );
                }  

                if (isset($ventafactura) && $ventafactura->indicacion) {
                    $comprobante[$tipocomprobante]['ADI'][] = array(
                        'tituloAdicional' => 'Indicación', 
                        'valorAdicional' => $ventafactura->indicacion 
                    );
                } 

                if (isset($ventafactura) && $ventafactura->autorizacion) {
                    $comprobante[$tipocomprobante]['ADI'][] = array(
                        'tituloAdicional' => 'Autorización', 
                        'valorAdicional' => $ventafactura->autorizacion 
                    );
                } 

                if (isset($ventafactura) && $ventafactura->programa) {
                    $comprobante[$tipocomprobante]['ADI'][] = array(
                        'tituloAdicional' => 'Programa', 
                        'valorAdicional' => $ventafactura->programa 
                    );
                } 

                if (isset($ventafactura) && $ventafactura->deducible) {
                    $comprobante[$tipocomprobante]['ADI'][] = array(
                        'tituloAdicional' => 'Deducible ('.$ventafactura->deducible.')', 
                        'valorAdicional' => $venta->deducible 
                    );
                }

                if (isset($ventafactura) && $ventafactura->coaseguro) {
                    $comprobante[$tipocomprobante]['ADI'][] = array(
                        'tituloAdicional' => 'Coaseguro ('.$ventafactura->coaseguro.'%)',
                        'valorAdicional' => $venta->coaseguro 
                    );
                }  

                break;
            case '03': //Boleta
                
                if ($venta->idpaciente) {
                    $comprobante[$tipocomprobante]['ADI'] = array();

                    $comprobante[$tipocomprobante]['ADI'][] = array(
                        'tituloAdicional' => 'Paciente', 
                        'valorAdicional' => $venta->paciente 
                    );
                }

                break;
        }
        // dd($ventadet); 
        
        $i = 1;
        foreach($ventadet as $row) {
            if ((float)$row->total >= 0) {
 
                $row->preciounit = (float)$row->preciounit;
                $row->valorunit = (float)$row->valorunit;
                $row->valorventa = (float)$row->valorventa;
                $row->montototalimpuestos = (float)$row->montototalimpuestos;
 
                $nombreproducto = $row->nombreproducto;

                if(!empty($row->descripcion)) {
                    $nombreproducto .= ' ' . $row->descripcion;
                }

                $comprobante[$tipocomprobante]['DET'][] = array(
                    'numeroItem' => (string) $i,
                    'codProductoSunat' => $row->codigosunat,
                    // 'descripcionProducto' => !empty($row->renombreproducto) ? $row->renombreproducto : $row->nombreproducto,
                    'descripcionProducto' => $nombreproducto,
                    'cantidadItems' => number_format($row->cantidad, 2),
                    'unidad' => 'ZZ',//'NIU', //Catálogo 03:Código de tipo de unidad de medida comercial
                    'valorUnitario' => number_format($row->valorunit, 3),
                    'precioVentaUnitario' => number_format($row->preciounit, 2),
                    'totalImpuestos' => array(
                        array(
                            'idImpuesto' => '1000', //Catálogo 05:IGV Impuesto General a las Ventas
                            'montoImpuesto' => number_format($row->montototalimpuestos, 2),
                            'tipoAfectacion' => '10', //Catálogo 07:Gravado - Operación Onerosa
                            'montoBase' => number_format($row->valorventa, 2),
                            'porcentaje' => number_format(18, 2)
                        )
                    ),
                    'valorVenta' => number_format($row->valorventa, 2),
                    'montoTotalImpuestos' => number_format($row->montototalimpuestos, 2)
                );
                $i++;
            }
        }

        return array(
            'comprobante' => $comprobante,
            'authentication' => array(
                'cpeuser' => $dataAfiliado->cpeuser,
                'cpepassword' => $dataAfiliado->cpepassword
            ),
            'cpeemision' => $venta->cpeemision, 
            'telefono' => $documentoserie->telefono,
            'cpecorreo' => $venta->cpecorreo,
            'idafiliado' => $venta->idafiliado
        );
    }

    private function archivoFe($data) {
        $tipo = '';
        $codTipoDocumento;
        $comprobante;

        if (isset($data['factura'])) {
            $comprobante = 'factura';
            $codTipoDocumento = '-01';
            $tipo = '01';
        }

        if (isset($data['boleta'])) {
            $comprobante = 'boleta';
            $codTipoDocumento = '-03';
            $tipo = '03';
        }

        if (isset($data['notaCredito'])) {
            $comprobante = 'notaCredito';
            $codTipoDocumento = '-07';
            $tipo = '07';
        }

        if (isset($data['comunicacionBaja'])) {
            $comprobante = 'comunicacionBaja';
            $codTipoDocumento = '';
        }

        if (isset($data['resumenComprobantes'])) {
            $comprobante = 'resumenComprobantes';
            $codTipoDocumento = '';
        }

        $numeroDocId = $data[$comprobante]['EMI']['numeroDocId'];
        $filename = $numeroDocId . $codTipoDocumento . '-' . $data[$comprobante]['IDE']['numeracion'];
        $arrayDeCadenas = explode('-', $data[$comprobante]['IDE']['numeracion']);

        return array(
            'nombreArchivo' => $filename,
            'comprobante' => $comprobante,
            'numeroDocId' => $numeroDocId,
            'tipo' => $tipo,
            'serie' => $arrayDeCadenas[0],
            'numero' => $arrayDeCadenas[1]
        );
    }
}
