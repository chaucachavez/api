<?php
namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\empresa; 
use App\Models\entidad;
use App\Models\citamedica;
use App\Models\cicloatencion;
use App\Models\cicloautorizacion;
use App\Models\modelo;

use Codedge\Fpdf\Fpdf\Fpdf as baseFpdf;
  
class PDF extends baseFpdf 
{    
    public $color = [0, 0, 0]; 
    public $borde = 0;    

    function Footer() 
    {
        
    } 
    
    function Header()
    {
         
    }
}

class vistapreviafacturaController extends Controller 
{    
    public function __construct(Request $request)
    {
        $objEntidad = new entidad();
        $objEmpresa = new empresa();
        
        $this->getToken($request);
        
        $this->pdf = new PDF('P', 'cm', array(22.3, 23)); //new PDF();       
        $this->pdf->SetMargins(1, 1, 1);
        $this->pdf->SetAutoPageBreak(true, 1);
        $this->pdf->AliasNbPages();  
        $this->pdf->SetDrawColor($this->pdf->color[0], $this->pdf->color[1], $this->pdf->color[2]); 
        
        $this->pdf->SetFont('Arial', '', 9);
        
        $this->entidad = $objEntidad->entidad(['entidad.identidad' => $this->objTtoken->my]);    
        $this->empresa = $objEmpresa->empresa(['empresa.idempresa' => 1]);    
    }
    
    public function reporte(Request $request, $enterprise)
    {   
        
        $request = $request->all();
        
        $objCicloatencion = new cicloatencion();
        $objEntidad = new entidad();
        $objCicloautorizacion = new cicloautorizacion(); 
        $empresa = new empresa();
        $modelo = new modelo();
       
        $idempresa = $empresa->idempresa($enterprise);        
        $cicloautorizacion = $objCicloautorizacion->cicloautorizacion($request['idcicloautorizacion']);
        $id = $cicloautorizacion->idcicloatencion;
        $cicloatencion = $objCicloatencion->cicloatencion($id);
        $cliente = $objEntidad->entidad(['entidad.identidad' => $request['idcliente']]);
        
        $productos = explode(',', $request['productos']);

        $cicloautorizacion->valor = $cicloautorizacion->valor ? $cicloautorizacion->valor : 100;       

        //Citas médicas
        $diagnostico = \DB::table('diagnostico') 
                ->select('diagnostico.iddiagnostico', 'diagnostico.nombre as diagnostico')                
                ->where('diagnostico.iddiagnostico', $request['iddiagnostico'])               
                ->first(); 


        if (isset($request['idzona'])) {
            $zona = \DB::table('zona') 
                ->select('zona.idzona', 'zona.nombre as nombrezona')                
                ->where('zona.idzona', $request['idzona'])               
                ->first(); 
        }
        
       
        //Presupuestodet
        $where = array(
            'cicloatencion.idcicloatencion' => $id,
            'presupuestodet.idproducto' => $cicloautorizacion->idproducto
        );
        $cantefectivo = \DB::table('presupuestodet')                
                ->join('presupuesto', 'presupuestodet.idpresupuesto', '=', 'presupuesto.idpresupuesto')
                ->join('cicloatencion', 'presupuesto.idcicloatencion', '=', 'cicloatencion.idcicloatencion')
                ->select('presupuestodet.cantefectivo')
                ->where($where)  
                ->whereNull('presupuestodet.deleted')
                ->whereNull('presupuesto.deleted')
                ->whereNull('cicloatencion.deleted')
                ->first();  

        //Modelos
        $modelos = [];
        $tmpmodelos = $modelo->grid(['modelo.idempresa' => $idempresa, 'modelo.idmodelo' => $request['idmodelo']]); 
        foreach($tmpmodelos as $row){
            foreach($row->modeloseguro as $row2){
                if($row2->idaseguradoraplan === $cicloautorizacion->idaseguradoraplan) {
                    $modelos[] = $row;
                    break;
                }                
            }
        }  
  
        $this->pdf->request = $request;
        if(isset($request['borde'])) {
            $this->pdf->borde = $request['borde'];
        }
        
        /*Reporte TD3*/
        $this->pdf->AddPage();                 
        $this->pdf->Cell(0, 2.5, utf8_decode(''), $this->pdf->borde); 
        $this->pdf->Ln(); 
        $this->pdf->SetTextColor(255, 0, 0);  
        $this->pdf->Cell(15.3, 1, utf8_decode(''), $this->pdf->borde);  
        $this->pdf->Cell(5, 1, $this->pdf->borde ? $request['serienumero'] : null, $this->pdf->borde, 0, 'C'); 
        $this->pdf->Ln(); 
        $this->pdf->SetTextColor(0, 0, 0);  
        $this->pdf->Cell(0, 1, utf8_decode(''), $this->pdf->borde); 
        $this->pdf->Ln(); 
        $this->pdf->Cell(2, 0.75, utf8_decode(''), $this->pdf->borde);         
        $this->pdf->Cell(18.3, 0.75, utf8_decode($cliente->entidad), $this->pdf->borde); 
        $this->pdf->Ln();
        $this->pdf->Cell(2, 0.75, utf8_decode(''), $this->pdf->borde);         
        $this->pdf->Cell(18.3, 0.75, utf8_decode($cliente->direccion), $this->pdf->borde); 
        $this->pdf->Ln();
        $this->pdf->Cell(2, 0.75, utf8_decode(''), $this->pdf->borde);         
        $this->pdf->Cell(11, 0.75, utf8_decode($cliente->numerodoc), $this->pdf->borde); 
        $this->pdf->Cell(3, 0.75, utf8_decode(''), $this->pdf->borde);         
        $this->pdf->Cell(4.3, 0.75, utf8_decode($request['fecha']), $this->pdf->borde); 
        $this->pdf->Ln();
 
        $this->pdf->Cell(0, 0.5, utf8_decode(''), $this->pdf->borde); 
        $this->pdf->Ln();  

        $cantcaracteres = 45;
        if(isset($zona)) {
            $cantcaracteres = 37;
        }

        $strdx = $diagnostico->diagnostico; 
        if (strlen($diagnostico->diagnostico) > $cantcaracteres) {
             $strdx = substr($diagnostico->diagnostico, 0, $cantcaracteres) . '..';
        }

        if(isset($zona)) {
            $strdx .= ' (' . $zona->nombrezona . ')';
        }

        $modelodet = [];
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => 'HC', 'descripcion2' => $cicloatencion->sedeabrev.' '.$cicloatencion->hc, 'precio' => null, 'total' => null);
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => 'Paciente', 'descripcion2' => $cicloautorizacion->paciente, 'precio' => null, 'total' => null);
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => 'Titular', 'descripcion2' => $cicloautorizacion->parentesco, 'precio' => null, 'total' => null);
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => 'Empresa', 'descripcion2' => $cicloautorizacion->nombrecompania, 'precio' => null, 'total' => null);
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => 'Diagnóstico', 'descripcion2' => $strdx, 'precio' => null, 'total' => null);
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);        
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => 'Indicación', 'descripcion2' => 'MEDICINA FISICA Y REHABILITACION', 'precio' => null, 'total' => null);        
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => 'Autorización', 'descripcion2' => $cicloautorizacion->codigo, 'precio' => null, 'total' => null);
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => 'MEDICINA FISICA Y REHABILITACION', 'precio' => null, 'total' => null);

        $totaltto = 0;
        $subtotal = 0;
        foreach ($modelos[0]->modelodet as $row) {
            if($row->idproducto === 1 && in_array($row->idproducto, $productos)) {
                $row->cantidad = $row->cantidad ? $row->cantidad : $cantefectivo->cantefectivo;
                $row->total = round($row->cantidad * $row->precio, 2); 

                $subtotal += $row->total;
                if($row->idproducto !== 1)//Diferente a consulta medica
                    $totaltto += $row->total;
                
                $modelodet[] = $row; 
            }
        } 
        
        $cabeceraosi = true;
        foreach ($modelos[0]->modelodet as $row) {
            if($row->idproducto !== 1 && in_array($row->idproducto, $productos)) {
                if($cabeceraosi) {
                    $modelodet[] = (object) array('cantidad' => null, 'descripcion' => 'Programa OSI '.$cicloatencion->primert . ' al '. $cicloatencion->ultimot, 'precio' => null, 'total' => null);
                    $cabeceraosi = false;
                }

                $row->cantidad = $row->cantidad ? $row->cantidad : $cantefectivo->cantefectivo;
                $row->total = round($row->cantidad * $row->precio, 2); 

                $subtotal += $row->total;
                if($row->idproducto !== 1)//Diferente a consulta medica
                    $totaltto += $row->total;
                
                $modelodet[] = $row; 
            }
        }

        

        $valordeducible = round($cicloautorizacion->deducible/-1.18, 2);
        $valorcoaseguro = round((1 - ($cicloautorizacion->valor/100)) * - $totaltto, 2);
        $subtotal = round($subtotal + $valordeducible + $valorcoaseguro, 2);
        $valorimpuesto = round($subtotal * 0.18, 2);
        // dd($subtotal, $subtotal * 0.18, round($subtotal * 0.18, 2));
        $total = round($subtotal + $valorimpuesto, 2);
        
        //Completar filas con vacio
        $cant = 17 - count($modelodet); 
        if($cant > 0) {
            for ($i=0; $i < $cant; $i++) { 
                $modelodet[] = (object) array('cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);
            }
        }

        //Completar filas con deducible y coaseguro
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);        
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => 'Deducible      '. $cicloautorizacion->deducible, 'precio' => null, 'total' => $valordeducible);
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => 'Coaseguro      '. $cicloautorizacion->valor.' %', 'precio' => null, 'total' => $valorcoaseguro);
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => 'Son ' .$this->num2letras($total) , 'precio' => null, 'total' => null);
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);
        
        foreach ($modelodet as $row) {  
            $this->pdf->Cell(2, 0.5, utf8_decode($row->cantidad), $this->pdf->borde, 0, 'C');  
            if(isset($row->descripcion2)) {
                $this->pdf->Cell(3, 0.5, utf8_decode($row->descripcion), $this->pdf->borde, 0, 'L');   
                $this->pdf->Cell(10.3, 0.5, utf8_decode($row->descripcion2), $this->pdf->borde, 0, 'L');   
            }else {
                $this->pdf->Cell(13.3, 0.5, utf8_decode($row->descripcion), $this->pdf->borde, 0, 'L');   
            }            
            $this->pdf->Cell(2.5, 0.5, utf8_decode($row->precio), $this->pdf->borde, 0, 'R');   
            $stotal = null;
            if(!is_null($row->total)) {
                $stotal = number_format($row->total, 2, '.', ',');
            } 
            $this->pdf->Cell(2, 0.5, $stotal, $this->pdf->borde, 0, 'R');   
            $this->pdf->Ln();
        }   

        $this->pdf->Cell(17.8, 0.7, utf8_decode(''), $this->pdf->borde, 0, 'L');        
        $this->pdf->Cell(2, 0.7, number_format($subtotal, 2, '.', ','), $this->pdf->borde, 0, 'R');  
        $this->pdf->Ln();
        $this->pdf->Cell(17.8, 0.7, utf8_decode(''), $this->pdf->borde, 0, 'L');        
        $this->pdf->Cell(2, 0.7, number_format($valorimpuesto, 2, '.', ','), $this->pdf->borde, 0, 'R');  
        $this->pdf->Ln();
        $this->pdf->Cell(17.8, 0.7, utf8_decode(''), $this->pdf->borde, 0, 'L');        
        $this->pdf->Cell(2, 0.7, number_format($total, 2, '.', ','), $this->pdf->borde, 0, 'R');  
        $this->pdf->Ln();

        /*Salida*/
        $this->pdf->Output();       
    }  

    /*! 
    @function num2letras () 
    @abstract Dado un n?mero lo devuelve escrito. 
    @param $num number - N?mero a convertir. 
    @param $fem bool - Forma femenina (true) o no (false). 
    @param $dec bool - Con decimales (true) o no (false). 
    @result string - Devuelve el n?mero escrito en letra. 

    */ 
    function num2letras($num, $fem = false, $dec = true) { 
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
        
        //Zi hack
        $float=explode('.',$num);
        $num=$float[0];

        $num = trim((string)@$num); 
        if ($num[0] == '-') { 
            $neg = 'menos '; 
            $num = substr($num, 1); 
        }else 
            $neg = ''; 
        while ($num[0] == '0') $num = substr($num, 1); 
        if ($num[0] < '1' or $num[0] > 9) $num = '0' . $num; 
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
        if ((int)$ent === 0) return 'Cero ' . $fin; 
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

        $con = '';
        if(isset($float[1])) {
            $con = ' con '.$float[1].'/100  Soles';
        }

        $end_num= mb_strtoupper($tex).$con;
        return $end_num; 
    } 
 

}

