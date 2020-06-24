<?php

namespace App\Http\Controllers;

use \Firebase\JWT\JWT;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public $objTtoken;

    public function crearRespuesta($datos, $codigo, $total = '', $currentPage = '', $others = []) {   // $success:
        // 1: Success, Procesado y se llevo a cabo con exito la tarea.
        // 2: Suceess, Procesado pero no se llevo la tarea. Ej. Tienes datos relaccionados en otra tabla 
        $success = 'success';
        if (is_array($codigo)) {
            $success = $codigo[1];
            $codigo = $codigo[0];
        }

        return response()->json(['type' => $success, 'data' => $datos, 'total' => $total, 'currentPage' => $currentPage, 'others' => $others], $codigo);
    }

    public function crearRespuestaError($mensaje, $codigo) {
        return response()->json(['message' => $mensaje, 'code' => $codigo], $codigo);
    }

    public function crearRespuestaToken() {
        return response()->json(['message' => 'tokenExpirado', 'code' => 200], 200);
    }
    
    public function ordenarMultidimension($array, $field, $sort, $optional1 = '', $optional2 = '', $optional3 = '', $optional4 = ''){
        /*$optional1 y $optional2, son campos para un ordenamiento por dos columnas*/
        $aux = [];
        $aux2 = [];
        $aux3 = [];
        // dd($field, $sort, $optional1, $optional2);
        foreach($array as $key => $row){
            if(is_object($row)) {
                $aux[$key] = $row->{$field};
            }else{
                $aux[$key] = $row[$field];
            }             

            if(!empty($optional1) &&  !empty($optional2)){
                if(is_object($row)) {
                    $aux2[$key] = $row->{$optional1};
                }else{
                    $aux2[$key] = $row[$optional1];
                }
            }

            if(!empty($optional3) &&  !empty($optional4)){
                if(is_object($row)) {
                    $aux3[$key] = $row->{$optional3};
                }else{
                    $aux3[$key] = $row[$optional3];
                }
            }
        }
        

        if(!empty($optional1) &&  !empty($optional2) && !empty($optional3) &&  !empty($optional4)){ 
            array_multisort($aux, $sort, $aux2, $optional2, $aux3, $optional4, $array);
        }else if(!empty($optional1) &&  !empty($optional2)){ 
            array_multisort($aux, $sort, $aux2, $optional2, $array);
        }else{  
            array_multisort($aux, $sort, $array);
        } 
        
        return $array;
    }
    
    public function getToken($request) {
        /* Autor: chaucachavez@gmail.com 
         * Objeto Global JWT
         * Ejemplo: iss: "http://wwww.lagranescuela.com" my: 1 myenterprise: "cime" myusername: "44120026A"
         * JWT: //"aud" => "http://example.com", //"iat" => time(), //"exp" => (time() + 10), //"nbf" => 1357000000
         */
        $key = "x1TLVtPhZxN64JQB3fN8cHSp69999999";
        if (!empty($request->header('AuthorizationToken'))) {            
            $this->objTtoken = JWT::decode($request->header('AuthorizationToken'), $key, array('HS256'));
        }else{
            if(isset($request->all()['us']))
                $this->objTtoken = JWT::decode($request->all()['us'], $key, array('HS256'));
        }
    }    

    //22.01.2016
    //Generacion de arbol Tree
    function procesarRaiz($data, $OPTION, $idarbol = NULL, $pila = false) {
        //Nota: $idarbol debe ser entero, caso contrario genera ERROR.  

        $i = 1;
        $arbol = [];

        while (count($data) > 0):
            // echo $i++;
            // Extraigo el o los nodos raices.
            foreach ($data as $b => $row) {
                if (empty($row[$OPTION['PARENT']])) {
                    $row[$OPTION['CHILDREN']] = [];
                    $row['nivel'] = 0;
                    $arbol[] = $row;
                    unset($data[$b]);
                }
            }

            // Extraigo los nodos hijos 
            foreach ($data as $b => $row) {
                $row[$OPTION['CHILDREN']] = [];
                $tmp = $this->procesarNiveles($arbol, $row, $OPTION);
                if ($tmp['flat']) {
                    $arbol = $tmp['data'];
                    unset($data[$b]);
                }
            }

        endwhile;

        /* Extrae una determinada raiz.        
         * */
        $bandera = 0; 
        if (!empty($idarbol)) {
            foreach ($arbol as $fila) {
                if ($fila[$OPTION['ID']] === $idarbol) {
                    $arbol = $fila;
                    $bandera = 1;
                    break 1;                    
                }
                foreach ($fila[$OPTION['CHILDREN']] as $fila2) {
                    if ($fila2[$OPTION['ID']] === $idarbol) {
                        $arbol = $fila2;
                        $bandera = 2;
                        break 2;
                    }
                    foreach ($fila2[$OPTION['CHILDREN']] as $fila3) {
                        if ($fila3[$OPTION['ID']] === $idarbol) {
                            $arbol = $fila3;
                            $bandera = 3;
                            break 3;
                        }
                        foreach ($fila3[$OPTION['CHILDREN']] as $fila4) {
                            if ($fila4[$OPTION['ID']] === $idarbol) {
                                $arbol = $fila4;
                                $bandera = 4;
                                break 4;
                            }
                            foreach ($fila4[$OPTION['CHILDREN']] as $fila5) {
                                if ($fila5[$OPTION['ID']] === $idarbol) {
                                    $arbol = $fila5;
                                    $bandera = 5;
                                    break 5;
                                }
                                foreach ($fila5[$OPTION['CHILDREN']] as $fila6) {
                                    if ($fila6[$OPTION['ID']] === $idarbol) {
                                        $arbol = $fila6;
                                        $bandera = 6;
                                        break 6;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
         
        /* Convierte el arbol a un array UNIDIMENSIONAL del tipo PILA. */ 
        if ($pila) { 
            $arbol = $this->procesarPila($arbol, $OPTION);
        } 
        
        return $arbol;
    }

    function procesarNiveles($arbol, $row, $OPTION) {
        $flat = false;

        //Nivel 1
        foreach ($arbol as $i => $fila) {
            if ($fila[$OPTION['ID']] == $row[$OPTION['PARENT']]) {
                $row['nivel'] = 1;
                array_push($arbol[$i][$OPTION['CHILDREN']], $row);
                $flat = true;
                break 1;
            }
            //Nivel 2
            foreach ($fila[$OPTION['CHILDREN']] as $i2 => $fila2) {
                if ($fila2[$OPTION['ID']] == $row[$OPTION['PARENT']]) {
                    $row['nivel'] = 2;
                    array_push($arbol[$i][$OPTION['CHILDREN']][$i2][$OPTION['CHILDREN']], $row);
                    $flat = true;
                    break 2;
                }
                //Nivel 3
                foreach ($fila2[$OPTION['CHILDREN']] as $i3 => $fila3) {
                    if ($fila3[$OPTION['ID']] == $row[$OPTION['PARENT']]) {
                        $row['nivel'] = 3;
                        array_push($arbol[$i][$OPTION['CHILDREN']][$i2][$OPTION['CHILDREN']][$i3][$OPTION['CHILDREN']], $row);
                        $flat = true;
                        break 3;
                    }
                    //Nivel 4 
                    foreach ($fila3[$OPTION['CHILDREN']] as $i4 => $fila4) {
                        if ($fila4[$OPTION['ID']] == $row[$OPTION['PARENT']]) {
                            $row['nivel'] = 4;
                            array_push($arbol[$i][$OPTION['CHILDREN']][$i2][$OPTION['CHILDREN']][$i3][$OPTION['CHILDREN']][$i4][$OPTION['CHILDREN']], $row);
                            $flat = true;
                            break 4;
                        }
                        //Nivel 5
                        foreach ($fila4[$OPTION['CHILDREN']] as $i5 => $fila5) {
                            if ($fila5[$OPTION['ID']] == $row[$OPTION['PARENT']]) {
                                $row['nivel'] = 5;
                                array_push($arbol[$i][$OPTION['CHILDREN']][$i2][$OPTION['CHILDREN']][$i3][$OPTION['CHILDREN']][$i4][$OPTION['CHILDREN']][$i5][$OPTION['CHILDREN']], $row);
                                $flat = true;
                                break 5;
                            }
                        }
                    }
                }
            }
        }
        //dd($arbol);
        return array('data' => $arbol, 'flat' => $flat);
    }

    function procesarPila($arbol, $OPTION) {

        $pila = [];

        //Nivel 0
        $tmp = $arbol;        
        unset($tmp[$OPTION['CHILDREN']]);
        array_push($pila, $tmp);
        //Nivel 1 
        foreach ($arbol[$OPTION['CHILDREN']] as $fila) {
            $tmp = $fila;
            unset($tmp[$OPTION['CHILDREN']]);
            array_push($pila, $tmp);
            //Nivel 2
            foreach ($fila[$OPTION['CHILDREN']] as $i2 => $fila2) {
                $tmp = $fila2;
                unset($tmp[$OPTION['CHILDREN']]);
                array_push($pila, $tmp);
                //Nivel 3
                foreach ($fila2[$OPTION['CHILDREN']] as $i3 => $fila3) {
                    $tmp = $fila3;
                    unset($tmp[$OPTION['CHILDREN']]);
                    array_push($pila, $tmp);
                    //Nivel 4 
                    foreach ($fila3[$OPTION['CHILDREN']] as $i4 => $fila4) {
                        $tmp = $fila4;
                        unset($tmp[$OPTION['CHILDREN']]);
                        array_push($pila, $tmp);
                        //Nivel 5
                        foreach ($fila4[$OPTION['CHILDREN']] as $i5 => $fila5) {
                            $tmp = $fila5;
                            unset($tmp[$OPTION['CHILDREN']]);
                            array_push($pila, $tmp);
                        }
                    }
                }
            }
        }
        //dd($arbol);
        return $pila;
    }
    
    function turno($idsede, $fecha, $inicio, $fin){
        $turno = NULL;
        
        $fechaIF = $this->fechaInicioFin($fecha, $inicio, $fin);
        $start_s = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        
        $ddmmyy = explode( '/', $fecha); 
        $diasem = date('N', mktime(0, 0, 0, (int)$ddmmyy[1], (int)$ddmmyy[0], (int)$ddmmyy[2])); //php date('N')(Lu=1,...,Do=7)                

        switch ($idsede) {
            case 1: //MI
                if($diasem === '6'){
                    $inicio_tm = '07:00:00'; $fin_tm = '15:29:00'; $inicio_tt = '15:30:00'; $fin_tt = '21:59:00';                    
                }else{            
                    $inicio_tm = '07:00:00'; $fin_tm = '14:59:00'; $inicio_tt = '15:00:00'; $fin_tt = '21:59:00';
                }
                break;
            case 2: //CH
                if($diasem === '6'){
                    $inicio_tm = '07:00:00'; $fin_tm = '15:44:00'; $inicio_tt = '15:45:00'; $fin_tt = '21:59:00';
                }else{ 
                    $inicio_tm = '07:00:00'; $fin_tm = '15:14:00'; $inicio_tt = '15:15:00'; $fin_tt = '21:59:00';
                }
                break;
            case 3: //TR
                if($diasem === '6'){                    
                    $inicio_tm = '07:00:00'; $fin_tm = '15:44:00'; $inicio_tt = '15:45:00'; $fin_tt = '21:59:00';
                }else{
                    $inicio_tm = '07:00:00'; $fin_tm = '15:14:00'; $inicio_tt = '15:15:00'; $fin_tt = '21:59:00';
                }
                break;
            case 4: //LO 
                if($diasem === '6'){
                    $inicio_tm = '07:00:00'; $fin_tm = '15:44:00'; $inicio_tt = '15:45:00'; $fin_tt = '21:59:00';
                }else{ 
                    $inicio_tm = '07:00:00'; $fin_tm = '15:14:00'; $inicio_tt = '15:15:00'; $fin_tt = '21:59:00';
                }

            case 5: // NATIVA 
                if($diasem === '6'){
                    $inicio_tm = '07:00:00'; $fin_tm = '15:44:00'; $inicio_tt = '15:45:00'; $fin_tt = '21:59:00';
                }else{ 
                    $inicio_tm = '07:00:00'; $fin_tm = '15:14:00'; $inicio_tt = '15:15:00'; $fin_tt = '21:59:00';
                }

            case 6: // NUEVA SEDE
                if($diasem === '6'){
                    $inicio_tm = '07:00:00'; $fin_tm = '15:44:00'; $inicio_tt = '15:45:00'; $fin_tt = '21:59:00';
                }else{ 
                    $inicio_tm = '07:00:00'; $fin_tm = '15:14:00'; $inicio_tt = '15:15:00'; $fin_tt = '21:59:00';
                }
                break;

            case 7: // NATIVA MIRAFLORES
                if($diasem === '6'){
                    $inicio_tm = '07:00:00'; $fin_tm = '15:44:00'; $inicio_tt = '15:45:00'; $fin_tt = '21:59:00';
                }else{ 
                    $inicio_tm = '07:00:00'; $fin_tm = '15:14:00'; $inicio_tt = '15:15:00'; $fin_tt = '21:59:00';
                }
                break;

            case 8: // OSI CALERA
                if($diasem === '6'){
                    $inicio_tm = '07:00:00'; $fin_tm = '15:44:00'; $inicio_tt = '15:45:00'; $fin_tt = '21:59:00';
                }else{ 
                    $inicio_tm = '07:00:00'; $fin_tm = '15:14:00'; $inicio_tt = '15:15:00'; $fin_tt = '21:59:00';
                }
                break;

            case 9: // OSI MAGDALENA
                if($diasem === '6'){
                    $inicio_tm = '07:00:00'; $fin_tm = '15:44:00'; $inicio_tt = '15:45:00'; $fin_tt = '21:59:00';
                }else{ 
                    $inicio_tm = '07:00:00'; $fin_tm = '15:14:00'; $inicio_tt = '15:15:00'; $fin_tt = '21:59:00';
                }
                break; 

            case 10: // OSI JESUS MARIA
                if($diasem === '6'){
                    $inicio_tm = '07:00:00'; $fin_tm = '15:44:00'; $inicio_tt = '15:45:00'; $fin_tt = '21:59:00';
                }else{ 
                    $inicio_tm = '07:00:00'; $fin_tm = '15:14:00'; $inicio_tt = '15:15:00'; $fin_tt = '21:59:00';
                }
                break;

            case 11: // OSI LA MOLINA
                if($diasem === '6'){
                    $inicio_tm = '07:00:00'; $fin_tm = '15:44:00'; $inicio_tt = '15:45:00'; $fin_tt = '21:59:00';
                }else{ 
                    $inicio_tm = '07:00:00'; $fin_tm = '15:14:00'; $inicio_tt = '15:15:00'; $fin_tt = '21:59:00';
                }
                break;

            case 14: // OSI SAN MIGUEL
                if($diasem === '6'){
                    $inicio_tm = '07:00:00'; $fin_tm = '15:44:00'; $inicio_tt = '15:45:00'; $fin_tt = '21:59:00';
                }else{ 
                    $inicio_tm = '07:00:00'; $fin_tm = '15:14:00'; $inicio_tt = '15:15:00'; $fin_tt = '21:59:00';
                }
                break;

            case 15: // OSI ONLINE
                if($diasem === '6'){
                    $inicio_tm = '07:00:00'; $fin_tm = '15:44:00'; $inicio_tt = '15:45:00'; $fin_tt = '21:59:00';
                }else{ 
                    $inicio_tm = '07:00:00'; $fin_tm = '15:14:00'; $inicio_tt = '15:15:00'; $fin_tt = '21:59:00';
                }
                break;
        }

        $fechaIF = $this->fechaInicioFin($fecha, $inicio_tm, $fin_tm);
        $start_tm = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        $end_tm = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']); 

        $fechaIF = $this->fechaInicioFin($fecha, $inicio_tt, $fin_tt);
        $start_tt = mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']);
        $end_tt = mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']); 

        if ($start_s >= $start_tm && $start_s <= $end_tm) {
            $turno = 'Mañana';
        }

        if ($start_s >= $start_tt && $start_s <= $end_tt) {
            $turno = 'Tarde';
        }  
        
        return $turno;
    }
    
    public function semanasAno($ano, $W = '') {
        //$W = '': NO implementado 
        //date('W', mktime(0, 0, 0, 12, 31, $ano)) //Número de la semana del año ISO-8601                         
        $numerosemana = date('W', mktime(0, 0, 0, 12, 31, $ano));
        if($numerosemana ==  1){
            $numerosemana = date('W', strtotime($ano.'-12-31 -7 day'));
        }
        
        for ($semana = 1; $semana <= $numerosemana; $semana++) {                                
            $fecha_lunes = date('Y-m-d', strtotime($ano . 'W' . str_pad($semana , 2, '0', STR_PAD_LEFT)));
            
            $inicio = $this->formatFecha($fecha_lunes);
            $fin = date('d/m/Y', strtotime($fecha_lunes.' 6 day'));
            
            $data[] = array(
                'year' => $ano,
                'week' =>  $semana,
                'inicio' => $inicio,  
                'fin' => $fin,                
                'start_s' => mktime(0, 0, 0, (int)substr($inicio, 3, 2), (int)substr($inicio, 0, 2), (int)substr($inicio, -4)),
                'end_s' => mktime(0, 0, 0, (int)substr($fin, 3, 2), (int)substr($fin, 0, 2), (int)substr($fin, -4)),
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
    
    public function diasemana($data, $fecha = '', $campo = '') {  
        //$fecha: dd/mm/yyyy;
        $fila = [];
        $tiempoMs = mktime(0, 0, 0, (int)substr($fecha, 3, 2), (int)substr($fecha, 0, 2), (int)substr($fecha, -4));
        foreach($data as $row){
            if($tiempoMs >= $row['start_s'] &&  $tiempoMs <= $row['end_s']){
                $fila = $row;
                break;
            }
        }
        $data = empty($campo) ? $fila : $fila[$campo];       
            
        return $data; 
    }
    
    function fechaInicioFin($fecha, $horainicio, $horafin) {
        //02-07-1986
        //17:25:00
        // dd($fecha, $horainicio, $horafin);
        $d = substr($fecha, 0, 2);
        $m = substr($fecha, 3, 2);
        $y = substr($fecha, 6, 4);

        $Hi = substr($horainicio, 0, 2);
        $Mi = substr($horainicio, 3, 2);

        $Hf = substr($horafin, 0, 2);
        $Mf = substr($horafin, 3, 2);

        return [
            'd' => $d, 'm' => $m, 'y' => $y,
            'Hi' => $Hi, 'Mi' => $Mi,
            'Hf' => $Hf, 'Mf' => $Mf
        ];
    }
    
    public function formatFecha($fecha, $format = 'dd/mm/yyyy') {
        $newFecha = NULL;
        if (!empty($fecha) && strlen($fecha) == 10) {
            if ($format === 'dd/mm/yyyy') {
                //de: yyyy-mm-dd a: dd/mm/yyyy 
                $fecha = explode('-', $fecha);
                $newFecha = $fecha[2] . '/' . $fecha[1] . '/' . $fecha[0];
            }
            if ($format === 'yyyy-mm-dd') {
                //de: dd/mm/yyyy a: yyyy-mm-dd 
                $fecha = explode('/', $fecha);
                $newFecha = $fecha[2] . '-' . $fecha[1] . '-' . $fecha[0];
            }
        }
        return $newFecha;
    }
    
    public function convertArray($n) { 
        $array = [];
        foreach($n as $val){
            $array[] = (array) $val;
        } 
        return $array;
    }

    public function convertDiff($fecha, $inicio, $fin, $formato = '%i min.') { 

        $datetime1 = date_create($fecha.' '.$inicio);
        $datetime2 = date_create($fecha.' '.$fin);
        $interval2 = $datetime1->diff($datetime2); 
        $tiempoterapia = $interval2->format($formato);

        return $tiempoterapia;
    }

    function sumahoras($hora1, $hora2){

        $hora1 = explode(":",$hora1);
        $hora2 = explode(":",$hora2);

        $horas = (int)$hora1[0] + (int)$hora2[0];
        $minutos = (int)$hora1[1] + (int)$hora2[1];
        $segundos = (int)$hora1[2] + (int)$hora2[2];

        $horas += (int)($minutos/60);
        $minutos = (int)($minutos%60) + (int)($segundos/60);
        $segundos = (int)($segundos%60);

        return ($horas < 10 ? '0' . $horas : $horas) . ':' .
               ($minutos < 10 ? '0' . $minutos : $minutos) . ':' .
               ($segundos < 10 ? '0' . $segundos : $segundos);
    }
    
    public function obtenerDia($mes, $dia, $ano, $opt = false) {
        $dias = array('Lun.', 'Mar.', 'Mie.', 'Jue.', 'Vie.', 'Sab.', 'Dom');
        $diascompletos = array('Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo');
        
        $N = date('N', mktime(0, 0, 0, (int)$mes, (int)$dia, (int)$ano));     
        
        if ($opt) {
            $dia = $diascompletos[(int)$N - 1]; 
        } else {
            $dia = $dias[(int)$N - 1]; 
        }
        return $dia; 
    }
    
    public function agruparPorColumna($data, $ano= '', $quiebre, $campoextra = '', $grupos = [], $cantidad = array('cantidad'), 
                                        $calculopresupuesto = false, $calculoterapia = false, $totalizador = false){ 
        
        if(!empty($ano)) {
            $weeks = $this->semanasAno($ano); 
            foreach($data as $row){                
                $row->semana = $this->diasemana($weeks, $row->fecha, 'inicio');
                $row->mes = substr($row->fecha, 3, 2).'/'.substr($row->fecha, -4);
                $row->ano = substr($row->fecha, -4);
            } 
        } 
        
        $indices = [];
        if(!empty($grupos)){
            foreach($grupos[1] as $pk => $val){ 
                if($pk !== '*'){
                    $indices[] = $pk;
                }            
            } 
        }

        $arrayNrodias = [];
        $matriz = array();  
        foreach($data as $row){ 
            $rowTmp = (array)$row;  
            $agrupador = $rowTmp[key($quiebre)]; //$row->idterapista;
            
            // dd(key($quiebre), $quiebre);

            if(!isset($matriz[$agrupador])){ 
                if(empty($grupos)){ 
                    $matriz[$agrupador]['quiebre'] = $agrupador;

                    foreach($cantidad as $celda){
                        $matriz[$agrupador][$celda] = 0;
                    }

                    if($calculopresupuesto){
                        $matriz[$agrupador]['cantclientecosto'] = 0;
                        $matriz[$agrupador]['cantefectivocosto'] = 0;
                    }

                    if($calculoterapia){
                        $matriz[$agrupador]['cantidadcosto'] = 0; 
                    }

                    if(!empty($campoextra)){ 
                        $matriz[$agrupador][$campoextra[key($campoextra)]] = $rowTmp[$campoextra[key($campoextra)]];
                    }
                }else{
                    foreach($grupos[1] as $pk => $val){
                        //if($pk !== '*'){
                            $matriz[$agrupador][$pk]['idquiebre'] = $agrupador;
                            $matriz[$agrupador][$pk]['quiebre'] = $rowTmp[$quiebre[key($quiebre)]];
                            $matriz[$agrupador][$pk]['idgrupo'] = $pk;
                            $matriz[$agrupador][$pk]['grupo'] = $val; 
 
                            foreach($cantidad as $celda){
                                $matriz[$agrupador][$pk][$celda] = 0; 
                            }

                            if($calculopresupuesto){
                                $matriz[$agrupador][$pk]['cantclientecosto'] = 0;
                                $matriz[$agrupador][$pk]['cantefectivocosto'] = 0;
                            }

                            if($calculoterapia){
                                $matriz[$agrupador][$pk]['cantidadcosto'] = 0;
                            }

                            if($totalizador)
                                $matriz[$agrupador]['tmptotal'] = 0;

                        //}
                    }
                    
                    if(!empty($campoextra)){ 
                        foreach($grupos[1] as $pk => $val){ 
                            foreach($campoextra as $val2){
                                $cadena = (array)$row;  
                                $matriz[$agrupador][$pk][$val2] = $cadena[$val2];
                            }
                        }
                    } 
                }
            }
            
            if(empty($grupos)){
                foreach($cantidad as $celda){  
                    $matriz[$agrupador][$celda] += 1;
                }                

                if($calculopresupuesto){
                    $cantclientecosto = ($row->tipotarifa === 1 ? $row->preciounitregular : ($row->tipotarifa === 2 ? $row->preciounittarjeta : $row->preciounitefectivo)) * $row->cantcliente;
                    $cantefectivocosto = ($row->tipotarifa === 1 ? $row->preciounitregular : ($row->tipotarifa === 2 ? $row->preciounittarjeta : $row->preciounitefectivo)) * $row->cantefectivo;

                    $matriz[$agrupador]['cantclientecosto'] += $cantclientecosto;
                    $matriz[$agrupador]['cantefectivocosto'] += $cantefectivocosto;
                } 

                if($calculoterapia){
                    $cantidadcosto = ($row->tipotarifa === 1 ? $row->preciounitregular : ($row->tipotarifa === 2 ? $row->preciounittarjeta : $row->preciounitefectivo)) * $row->cantidad;                    

                    $matriz[$agrupador]['cantidadcosto'] += $cantidadcosto; 
                } 
            }else{ 
                
                $newindices = [];
                foreach($indices as $indice){
                    $tmp = explode(',', $indice);
                    //dd($row[$grupos[0]]);
                    // dd($row->{$grupos[0]}, $grupos[0]);
                    if(in_array($row->{$grupos[0]}, $tmp)){ 
                        $newindices[] = $indice;
                    }  
                } 

                if(empty($newindices)) {
                    $newindices[] = '*';
                } 

                // $indice = '*'; 
                // if(in_array($row->$grupos[0], $indices)){
                //     $indice = $row->$grupos[0];
                // } 
                 
                foreach($newindices as $indice){
                    foreach($cantidad as $celda){  

                        if($celda === "cantcliente" || $celda === "cantefectivo"){
                            $cant = $row->{$celda}; 
                        }else{
                            $cant = 1;  
                            if(isset($row->{$celda})){//Sumara por un campo, de nombre cantidad que biene de la bd
                                $cant = $row->{$celda};   
                            }
                        }

                        $matriz[$agrupador][$indice][$celda] += $cant;     

                        if($totalizador)
                            $matriz[$agrupador]['tmptotal'] += $cant;                         
                    }       

                    if($calculopresupuesto){
                        $cantclientecosto = ($row->tipotarifa === 1 ? $row->preciounitregular : ($row->tipotarifa === 2 ? $row->preciounittarjeta : $row->preciounitefectivo)) * $row->cantcliente;
                        $cantefectivocosto = ($row->tipotarifa === 1 ? $row->preciounitregular : ($row->tipotarifa === 2 ? $row->preciounittarjeta : $row->preciounitefectivo)) * $row->cantefectivo;

                        $matriz[$agrupador][$indice]['cantclientecosto'] += $cantclientecosto;
                        $matriz[$agrupador][$indice]['cantefectivocosto'] += $cantefectivocosto;
                    }

                    if($calculoterapia){
                        $cantidadcosto = ($row->tipotarifa === 1 ? $row->preciounitregular : ($row->tipotarifa === 2 ? $row->preciounittarjeta : $row->preciounitefectivo)) * $row->cantidad;                    

                        $matriz[$agrupador][$indice]['cantidadcosto'] += $cantidadcosto;
                    } 
                }  
            }
        }               
             
        if($totalizador){
            //Nota: Si $matriz los indices son numeros(4,2,7), retorna con indices nuevos numericos(0,1,2), se inventa indices.
            //Nota: Si $matriz los indices son cadena('25/07/2017','20/07/2017','28/07/2017'), retorna con indices nuevos cadena, no se inventa. 
            $matriz = $this->ordenarMultidimension($matriz, 'tmptotal', SORT_DESC); 
            foreach($matriz as $i => $row){ 
                foreach($row as $indice => $row2){ 
                    if($indice === 'tmptotal') {     
                        unset($matriz[$i]['tmptotal']);      
                    }              
                }                   
            }
        }   
        $dato = array();
        
        if(empty($grupos)){
            foreach($matriz as $row){ 
                $dato[] = $row; 
            }
        }else{
            foreach($matriz as $row){ 
                foreach($row as $row2){  
                    $dato[] = $row2; 
                }
            }
        } 
               
        return $dato;
    }

    public function configurarInterconsultas($datahorario, $tiempoconsultamedica, $tiempointerconsulta, $datacitas = [], $idpaciente = '') {
        /* 1800 recomiendo quitar hace que la primera consulta comienze despues de 1/2 'en duro' y para las demas entrecitas 1 hora que es configurado 'en sede'.
         * 
         */
        $interconsultas = [];

        foreach ($datahorario as $row) {
            //$start_s = $row->start_s + 1800; // 30 minutos
            $start_s = $row->start_s;
            $end_s = $row->end_s;

            $turnosvalidos = [0,1,0,0,0,1,0,0,0,1,0,0,0,1,0,0,0,1,0,0,0,1,0,0,1,0,0,1,0,0]; //AMAC 13.06.2018;
            $i = 0;
            while ($start_s < $end_s ) {
                
                // dd($turnosvalidos);
                
                if ($turnosvalidos[$i] === 1) {
                    $interconsultas[] = array(
                        //inicio y fin; no se usa.
                        'inicio' => date('d/m/Y H:i:s', $start_s),
                        'fin' => date('d/m/Y H:i:s', $start_s + $tiempoconsultamedica), //14 minutos
                        'start_s' => $start_s,
                        'end_s' => $start_s + $tiempoconsultamedica, //14 minutos
                        'numCitas' => 0,
                        'idsede' => $row->idsede, //23.09.2016
                        'idhorariomedico' => $row->idhorariomedico, //23.09.2016
                        'zindextemp' => $i
                    );
                }

                $i++; 
                $start_s = $start_s + $tiempointerconsulta; // 1hora                                    
            }
        }

        if (!empty($datacitas)) {
            foreach ($interconsultas as $indice => $row) {
                $numCitas = 0;
                foreach ($datacitas as $cita) {
                    if ($cita->start_s === $row['start_s'] && $cita->end_s === $row['end_s']) {
                        if (!empty($idpaciente)) {
                            if ($idpaciente !== $cita->idpaciente) {
                                $numCitas = $numCitas + 1;
                            }
                        } else {
                            $numCitas = $numCitas + 1;
                        }
                    }
                }
                $interconsultas[$indice]['numCitas'] = $numCitas;
            }
        }

        return $interconsultas;
    }

    public function acumulativo($datacitaagendaatendida, $idgrupo = 'idgrupo', $cantidad = 'cantidad') {
        $tmp = [];
        for($i=0; $i < count($datacitaagendaatendida); $i++){ //acumulativo
            if(!isset($tmp[$datacitaagendaatendida[$i][$idgrupo]]))
                $tmp[$datacitaagendaatendida[$i][$idgrupo]] = 0;    
            $datacitaagendaatendida[$i][$cantidad] += $tmp[$datacitaagendaatendida[$i][$idgrupo]]; 
            $tmp[$datacitaagendaatendida[$i][$idgrupo]] = $datacitaagendaatendida[$i][$cantidad];
        } 

        return $datacitaagendaatendida;
    }

    public function convertMes($mes, $tipo = 0) {   
        $ene = ['Ene.', 'ene'];
        $feb = ['Feb.', 'feb'];
        $mar = ['Mar.', 'mar'];
        $abr = ['Abr.', 'abr'];
        $may = ['May.', 'may'];
        $jun = ['Jun.', 'jun'];
        $jul = ['Jul.', 'jul'];
        $ago = ['Ago.', 'ago'];
        $set = ['Set.', 'seti'];
        $oct = ['Oct.', 'oct'];
        $nov = ['Nov.', 'nov'];
        $dic = ['Dic.', 'dic'];
        switch ((int) $mes) {
            case 1:
                $newmes = $ene[$tipo];
                break;
            case 2:
                $newmes = $feb[$tipo];
                break;
            case 3:
                $newmes = $mar[$tipo];
                break;
            case 4:
                $newmes = $abr[$tipo];
                break;
            case 5:
                $newmes = $may[$tipo];
                break;
            case 6:
                $newmes = $jun[$tipo];
                break;
            case 7:
                $newmes = $jul[$tipo];
                break;
            case 8:
                $newmes = $ago[$tipo];
                break;
            case 9:
                $newmes = $set[$tipo];
                break;
            case 10:
                $newmes = $oct[$tipo];
                break;
            case 11:
                $newmes = $nov[$tipo];
                break;
            case 12:
                $newmes = $dic[$tipo];
                break;
            default:
                $newmes = '';
                break;
        }
        return $newmes;
    }

    public function sumarUnminuto($hora) {
        $newHora = '';
        $horaTmp = explode(':', $hora); //'08:00:00'

        if ($horaTmp[1] === '59') {
            $horaTmp[0] = (int) $horaTmp[0] + 1;
            $horaTmp[0] = strlen($horaTmp[0]) === 1 ? ('0' . $horaTmp[0]) : $horaTmp[0];
            $horaTmp[1] = '00';

            $newHora = $horaTmp[0] . ':' . $horaTmp[1] . ':00';
        } else {
            $horaTmp[1] = (int) $horaTmp[1] + 1;
            $horaTmp[1] = strlen($horaTmp[1]) === 1 ? ('0' . $horaTmp[1]) : $horaTmp[1];
            $newHora = $horaTmp[0] . ':' . $horaTmp[1] . ':00';
        }
        return $newHora;
    }

    //Citamedica y Citaterapetica
    public function restarUnminuto($hora) {
        $newHora = '';
        $horaTmp = explode(':', $hora); //'08:00:00'

        if ($horaTmp[1] === '00') {

            $horaTmp[0] = (int) $horaTmp[0] - 1;
            $horaTmp[0] = strlen($horaTmp[0]) === 1 ? ('0' . $horaTmp[0]) : $horaTmp[0];
            $horaTmp[1] = '59';

            $newHora = $horaTmp[0] . ':' . $horaTmp[1] . ':00';
        } else {
            $horaTmp[1] = (int) $horaTmp[1] - 1;
            $horaTmp[1] = strlen($horaTmp[1]) === 1 ? ('0' . $horaTmp[1]) : $horaTmp[1];

            $newHora = $horaTmp[0] . ':' . $horaTmp[1] . ':00';
        }
        return $newHora;
    } 

    public function horaaSegundos($hora) {
        //$hora = "09:24:38";
        list($horas, $minutos, $segundos) = explode(':', $hora);
        $hora_en_segundos = ($horas * 3600 ) + ($minutos * 60 ) + $segundos;
        return $hora_en_segundos;
    }

    public function toHours($min, $type) {
        //obtener segundos
        $sec = $min * 60;
        //dias es la division de n segs entre 86400 segundos que representa un dia
        $dias = floor($sec/86400);
        //mod_hora es el sobrante, en horas, de la division de días; 
        $mod_hora = $sec%86400;
        //hora es la division entre el sobrante de horas y 3600 segundos que representa una hora;
        $horas = floor($mod_hora/3600); 
        //mod_minuto es el sobrante, en minutos, de la division de horas; 
        $mod_minuto = $mod_hora%3600;
        //minuto es la division entre el sobrante y 60 segundos que representa un minuto;
        $minutos = floor($mod_minuto/60);

        if ($horas<=0){
            $text = $minutos.' min';
        } else if($dias <= 0) {
            //nos apoyamos de la variable type para especificar si se muestra solo las horas
            if($type=='round') {
                $text = $horas.' hrs';
            } else {
                $text = $horas." hrs ".$minutos;
            }
        } else {
            //nos apoyamos de la variable type para especificar si se muestra solo los dias
            if ($type=='round') {
                $text = $dias.' dias';
            } else {
                $text = $dias." dias ".$horas." hrs ".$minutos." min";
            }
        }

        return $text; 
    }

    public function transformHora($str) {

        if(empty($str) || $str === '00:00:00') 
            return '';
        
        $hora = (int) substr($str, 0, 2);            
        $ampm = $hora >= 12 ? 'p.m.' : 'a.m.';
        $h = $hora > 12 ? ($hora - 12) : $hora;

        $formato = $h . substr($str, 2, 3) . ' '. $ampm;

        return $formato;
    }

    public function convertAmPm($hour) {   
        $newDateTime = null;
        if(isset($hour) && !empty($hour)){
            $currentDateTime = date('Y-m-d').' '.$hour;
            $newDateTime = date('h:i A', strtotime($currentDateTime));
        }
        return $newDateTime;
    }

    public function configurarFeriados($feriados, $minTime, $maxTime) {
        $tiempoNohabil = [];

        /* Configurar dias feriados */
        foreach ($feriados['diasFeriados'] as $row) {

            $fechaIF = $this->fechaInicioFin($row->fecha, $minTime, $maxTime);

            $tmp = [ 
                'start_s' => mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']),
                'end_s' => mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y']) 

                // ,'inicio' => date('d/m/Y H:i:s', mktime((int) $fechaIF['Hi'], (int) $fechaIF['Mi'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y'])),
                // 'fin' => date('d/m/Y H:i:s', mktime((int) $fechaIF['Hf'], (int) $fechaIF['Mf'], 0, (int) $fechaIF['m'], (int) $fechaIF['d'], (int) $fechaIF['y'])) 
            ];

            $tiempoNohabil[] = $tmp;
        }
 

        return $tiempoNohabil;
    }

    public function validarFeriado($data, $inicio, $fin, $idpaciente = '') {
        //start_s: Tiempo en segundos
        //end_s: Tiempo en segundos       
        $inValid = false;

        foreach ($data as $row) {

            $row = is_object($row) ? (array) $row : $row;

            //if(($row->start_s >= $inicio && $row->start_s <= $fin) || ($row->end_s >= $inicio && $row->end_s <= $fin) || ($row->start_s < $inicio && $row->end_s > $fin)){

            if (($row['start_s'] >= $inicio && $row['start_s'] <= $fin) || ($row['end_s'] >= $inicio && $row['end_s'] <= $fin) || ($row['start_s'] < $inicio && $row['end_s'] > $fin)) {
                if (!empty($idpaciente)) {
                    //if( $idpaciente !== $row->idpaciente ){
                    if ($idpaciente !== $row['idpaciente']) {
                        $inValid = true;
                        break;
                    }
                } else {
                    $inValid = true;
                    break;
                }
            }
        }

        return $inValid;
    }

    public function resumenIngresos($ventas){
        $notacreditoventas = 0;
        $resumen = array();
        $matriztmp = array();

        foreach ($ventas as $row) {  
            $resumen[$row->idafiliado][$row->iddocumentofiscal][] = $row; 
        }  
        
        foreach($resumen as $row1) { 
            foreach($row1 as $row2){  
                $monto = 0;
                $rowTmp = array(); 
                $emitidasTmp = array();
                foreach($row2 as $row4){   //dd($row4);
                    array_push($emitidasTmp,  $row4->serienumero); 
                    $rowTmp['acronimo'] = $row4->acronimo;
                    $rowTmp['nombredocventa'] = $row4->nombredocventa; 
                    $rowTmp['estadodocumento'] = $row4->estadodocumento;
                    $monto += $row4->total; 
                } 
                
                sort($emitidasTmp);//menor a mayor
                $cantidad = count($emitidasTmp); 
                
                $rowTmp['emitidas'] = $cantidad === 1 ? $emitidasTmp[0] : ($emitidasTmp[0] . ' al '.$emitidasTmp[$cantidad - 1]); 
                $rowTmp['monto'] = $monto;
                array_push($matriztmp, $rowTmp);  
            }  
        }  

        // foreach ($ventas as $row) {  
        //     if((double)$row->valorcredito > 0){ 
        //         $rowTmp = array(
        //             'acronimo' => $row->ncacronimo,
        //             'nombredocventa' => $row->ncnombredocventa, 
        //             'estadodocumento' => NULL,
        //             'emitidas' => 'N° ' . $row->ncserie . '-' . str_pad($row->ncserienumero, 6, "0", STR_PAD_LEFT),
        //             'monto' => (double)$row->valorcredito * -1 
        //         );  
        //         array_push($matriztmp, $rowTmp);
        //     }
        // } 
 

        // dd($matriztmp);
        return $matriztmp;
    }

    public function resumenVentas($ventas) {

        $ventadeposito = 0;
        $ventaculqiexpress = 0;
        $ventaefectivo = 0;
        $ventatarjeta = 0;
        $ventatarjetaVisa = 0;
        $ventatarjetaMastercad = 0;
        $ventanotacredito = 0; //No lo uso, pero esta facil calcularlo.
        
        $visa = [];
        //dd($ventas);
        foreach ($ventas as $row) {
            //idestadodocumento: 26:Pago pendiente 27:Pagado 28:Anulado 
            //idestadodocumento: 1:Efectivo 2:T.Visa 3:T.Mastercad 4:T+E  
            //if ($row->idestadodocumento === 27) {
                switch ($row->idmediopago) {
                    case 1: //Efectivo
                        $ventaefectivo = $ventaefectivo + $row->total;
                        break;
                    case 2: //Visa
                        $ventatarjetaVisa = $ventatarjetaVisa + $row->tarjetaprimonto;                        

                        if($row->tarjetasegmonto > 0 && $row->idtarjetaseg === 3)
                            $ventatarjetaMastercad = $ventatarjetaMastercad + $row->tarjetasegmonto;

                        if($row->tarjetasegmonto > 0 && $row->idtarjetaseg === 2)
                            $ventatarjetaVisa = $ventatarjetaVisa + $row->tarjetasegmonto;

                        $ventatarjeta = $ventatarjeta + $row->total;
                        break;
                    case 3: //Mastercad
                        $ventatarjetaMastercad = $ventatarjetaMastercad + $row->tarjetaprimonto;

                        if($row->tarjetasegmonto > 0 && $row->idtarjetaseg === 2)
                            $ventatarjetaVisa = $ventatarjetaVisa + $row->tarjetasegmonto;

                        if($row->tarjetasegmonto > 0 && $row->idtarjetaseg === 3)
                            $ventatarjetaMastercad = $ventatarjetaMastercad + $row->tarjetasegmonto;

                        $ventatarjeta = $ventatarjeta + $row->total;
                        break;
                    case 4: //Tarjeta + Efectivo
                        $ventaefectivo = $ventaefectivo + $row->parteefectivo;
                        $ventatarjeta = $ventatarjeta + $row->partemontotarjeta;

                        if ($row->partetipotarjeta === 2) {
                            $ventatarjetaVisa = $ventatarjetaVisa + $row->partemontotarjeta;
                        }
                        if ($row->partetipotarjeta === 3) {
                            $ventatarjetaMastercad = $ventatarjetaMastercad + $row->partemontotarjeta;
                        }
                        break;
                    case 7: //Culqi
                        $ventaculqiexpress = $ventaculqiexpress + $row->total;
                        break;
                    case 9: //Deposito
                        $ventadeposito = $ventadeposito + $row->total;
                        break;
                    default:
                        //No debe llegar AQUI, ya que idmediopago es NOTNULL
                        break;
                }
            //}
        }

        // dd($visa);
        return array(
            'ventadeposito' => $ventadeposito,
            'ventaculqiexpress' => $ventaculqiexpress,
            'ventaefectivo' => $ventaefectivo,
            'ventatarjeta' => $ventatarjeta,
            'ventatarjetaVisa' => $ventatarjetaVisa,
            'ventatarjetaMastercad' => $ventatarjetaMastercad,
            'ventanotacredito' => $ventanotacredito,
            );
    } 
    

    protected function buildFailedValidationResponse(Request $request, array $errors) {
        return $this->crearRespuestaError($errors, 422);
    }

    public function num2letras($num, $fem = false, $dec = true) { //$num = 43.52;
        $matuni[2]  = "dos"; 
        $matuni[3]  = "tres"; 
        $matuni[4]  = "cuatro"; 
        $matuni[5]  = "cinco"; 
        $matuni[6]  = "seis"; 
        $matuni[7]  = "siete"; 
        $matuni[8]  = "ocho"; 
        $matuni[9]  = "nueve"; 
        $matuni[10] = "diez"; 
        $matuni[11] = "once"; 
        $matuni[12] = "doce"; 
        $matuni[13] = "trece"; 
        $matuni[14] = "catorce"; 
        $matuni[15] = "quince"; 
        $matuni[16] = "dieciseis"; 
        $matuni[17] = "diecisiete"; 
        $matuni[18] = "dieciocho"; 
        $matuni[19] = "diecinueve"; 
        $matuni[20] = "veinte"; 
        $matunisub[2] = "dos"; 
        $matunisub[3] = "tres"; 
        $matunisub[4] = "cuatro"; 
        $matunisub[5] = "quin"; 
        $matunisub[6] = "seis"; 
        $matunisub[7] = "sete"; 
        $matunisub[8] = "ocho"; 
        $matunisub[9] = "nove"; 

        $matdec[2] = "veint"; 
        $matdec[3] = "treinta"; 
        $matdec[4] = "cuarenta"; 
        $matdec[5] = "cincuenta"; 
        $matdec[6] = "sesenta"; 
        $matdec[7] = "setenta"; 
        $matdec[8] = "ochenta"; 
        $matdec[9] = "noventa"; 
        $matsub[3]  = 'mill'; 
        $matsub[5]  = 'bill'; 
        $matsub[7]  = 'mill'; 
        $matsub[9]  = 'trill'; 
        $matsub[11] = 'mill'; 
        $matsub[13] = 'bill'; 
        $matsub[15] = 'mill'; 
        $matmil[4]  = 'millones'; 
        $matmil[6]  = 'billones'; 
        $matmil[7]  = 'de billones'; 
        $matmil[8]  = 'millones de billones'; 
        $matmil[10] = 'trillones'; 
        $matmil[11] = 'de trillones'; 
        $matmil[12] = 'millones de trillones'; 
        $matmil[13] = 'de trillones'; 
        $matmil[14] = 'billones de trillones'; 
        $matmil[15] = 'de billones de trillones'; 
        $matmil[16] = 'millones de billones de trillones'; 
        // \Log::info(print_r($num, true));
        // \Log::info(print_r(gettype($num), true));
        //Zi hack 
        $float=explode('.',$num);
        $num = $float[0];

        $num = trim((string)@$num); 
        if ($num[0] == '-') { 
            $neg = 'menos '; 
            $num = substr($num, 1); 
        }else 
            $neg = ''; 

        // while ($num[0] == '0') { 
        //     \Log::info(print_r('=>'. $num, true));
        //     $num = substr($num, 1); 
        //     \Log::info(print_r('->'. $num, true));
        // }
            
        if ($num[0] < '1' or $num[0] > 9) {
            $num = '0' . $num; 
        }

        $zeros = true; 
        $punt = false; 
        $ent = ''; 
        $fra = ''; 
        for ($c = 0; $c < strlen($num); $c++) { 
            $n = $num[$c]; 
            if (! (strpos(".,'''", $n) === false)) { 
                if ($punt) break; 
                else{ 
                    $punt = true; 
                    continue; 
                } 

            }elseif (! (strpos('0123456789', $n) === false)) { 
                if ($punt) { 
                    if ($n != '0') $zeros = false; 
                    $fra .= $n; 
                }else 

                    $ent .= $n; 
            }else 

                break; 

        } 
        $ent = '     ' . $ent; 
        if ($dec and $fra and ! $zeros) { 
            $fin = ' coma'; 
            for ($n = 0; $n < strlen($fra); $n++) { 
                if (($s = $fra[$n]) == '0') 
                    $fin .= ' cero'; 
                elseif ($s == '1') 
                    $fin .= $fem ? ' una' : ' un'; 
                else 
                    $fin .= ' ' . $matuni[$s]; 
            } 
        }else 
            $fin = ''; 

        if ((int)$ent === 0) {
            // return 'Cero ' . $fin; 22.12.2019
            $tex = 'Cero';
            $con = '';
            if(isset($float[1])) {
                $con = ' CON '.$float[1].'/100 SOLES';
            } else {
                //10.11.2018
                $con = ' CON 00/100 SOLES';
            }

            $end_num= mb_strtoupper($tex).$con;
            return $end_num; 
        }

        $tex = ''; 
        $sub = 0; 
        $mils = 0; 
        $neutro = false; 
        while ( ($num = substr($ent, -3)) != '   ') { 
            $ent = substr($ent, 0, -3); 
            if (++$sub < 3 and $fem) { 
                $matuni[1] = 'una'; 
                $subcent = 'as'; 
            }else{ 
                $matuni[1] = $neutro ? 'un' : 'uno'; 
                $subcent = 'os'; 
            } 
            $t = ''; 
            $n2 = substr($num, 1); 
            if ($n2 == '00') { 
            }elseif ($n2 < 21) 
                $t = ' ' . $matuni[(int)$n2]; 
            elseif ($n2 < 30) { 
                $n3 = $num[2]; 
                if ($n3 != 0) $t = 'i' . $matuni[$n3]; 
                $n2 = $num[1]; 
                $t = ' ' . $matdec[$n2] . $t; 
            }else{ 
                $n3 = $num[2]; 
                if ($n3 != 0) $t = ' y ' . $matuni[$n3]; 
                $n2 = $num[1]; 
                $t = ' ' . $matdec[$n2] . $t; 
            } 
            $n = $num[0]; 
            if ($n == 1) { 
                $t = ' ciento' . $t; 
            }elseif ($n == 5){ 
                $t = ' ' . $matunisub[$n] . 'ient' . $subcent . $t; 
            }elseif ($n != 0){ 
                $t = ' ' . $matunisub[$n] . 'cient' . $subcent . $t; 
            } 
            if ($sub == 1) { 
            }elseif (! isset($matsub[$sub])) { 
                if ($num == 1) { 
                    $t = ' mil'; 
                }elseif ($num > 1){ 
                    $t .= ' mil'; 
                } 
            }elseif ($num == 1) { 
                $t .= ' ' . $matsub[$sub] . '?n'; 
            }elseif ($num > 1){ 
                $t .= ' ' . $matsub[$sub] . 'ones'; 
            }   
            if ($num == '000') $mils ++; 
            elseif ($mils != 0) { 
                if (isset($matmil[$sub])) $t .= ' ' . $matmil[$sub]; 
                $mils = 0; 
            } 
            $neutro = true; 
            $tex = $t . $tex; 
        } 
        $tex = $neg . substr($tex, 1) . $fin; 
        //Zi hack --> return ucfirst($tex);
        //$end_num= ucfirst($tex).' con '.$float[1].'/100  Soles';
        // dd($float);
        $con = '';
        if(isset($float[1])) {
            $con = ' CON '.$float[1].'/100 SOLES';
        } else {
            //10.11.2018
            $con = ' CON 00/100 SOLES';
        }

        $end_num= mb_strtoupper($tex).$con;
        return $end_num; 
    } 

    //https://abelcabans.com/2014/04/obtener-primer-ultimo-dia-del-mes-php/
    function _data_last_month_day($month="", $year="") { 

      if (empty($month))
        $month = date('m');  

      if (empty($year))
        $year = date('Y');

      $day = date("d", mktime(0,0,0, $month+1, 0, $year));

      return date('Y-m-d', mktime(0,0,0, $month, $day, $year));
    }
     
    /** Actual month first day **/
    function _data_first_month_day($month="", $year="") {
      if (empty($month))
        $month = date('m');  

      if (empty($year))
        $year = date('Y');

      return date('Y-m-d', mktime(0,0,0, $month, 1, $year));
    }

    function _data_dayweek_month_day($fecha="", $str = false) {


      $dias = array('Lun.', 'Mar.', 'Mié.', 'Jue.', 'Vie.', 'Sáb.', 'Dom'); 

      if (empty($fecha))  
        $fecha = date('Y-m-d');  

      $ddmmyy = explode('-', $fecha); 

      $N = date('N', mktime(0, 0, 0, (int)$ddmmyy[1], (int)$ddmmyy[2], (int)$ddmmyy[0])); //php date('N')( Lu=1,...,Do=7) 

      if ($str) {
            $N = $dias[(int)$N - 1]; 
        } 

      return $N;
    }
}
