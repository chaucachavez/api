<?php
namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\empresa;
use App\Models\ventafactura;
use App\Models\venta;
use App\Models\entidad;

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

class facturaController extends Controller 
{    
    public function __construct(Request $request) 
    {   
        $objEntidad = new entidad();
        $objEmpresa = new empresa();
        
        $this->getToken($request);
        
        $this->pdf = new PDF('P', 'cm', array(22.3, 23)); //new PDF();       
        $this->pdf->SetMargins(0, 0, 0);
        $this->pdf->SetAutoPageBreak(true, 1);
        $this->pdf->AliasNbPages();  
        $this->pdf->SetDrawColor($this->pdf->color[0], $this->pdf->color[1], $this->pdf->color[2]); 
        
        $this->pdf->SetFont('Arial', '', 8);
        
        $this->entidad = $objEntidad->entidad(['entidad.identidad' => 1]);    
        $this->empresa = $objEmpresa->empresa(['empresa.idempresa' => 1]);    
    }
    
    public function reporte(Request $request, $enterprise, $id)
    {   
       
        $request = $request->all();

        $Objventa = new venta();
        $objEntidad = new entidad();
        
        $venta = $Objventa->venta($id);     
        $ventadet = $Objventa->ventadet($id);
        
        $ventafactura = $Objventa->ventafactura($id); 
        $cliente = $objEntidad->entidad(['entidad.identidad' =>$venta->idcliente]); 
        $height = 0.3;

        /*Reporte TD3*/
        $this->pdf->AddPage();                 
        $this->pdf->Cell(0, 1.2, utf8_decode(''), $this->pdf->borde); 
        $this->pdf->Ln(); 
        // $this->pdf->SetTextColor(255, 0, 0);  
        // $this->pdf->Cell(15.3, 1, utf8_decode('xxx'), $this->pdf->borde);  
        // $this->pdf->Cell(5, 1, utf8_decode(''), $this->pdf->borde, 0, 'C'); 
        // $this->pdf->Ln(); 
        $this->pdf->SetTextColor(0, 0, 0);  
        // $this->pdf->Cell(0, 1, utf8_decode(''), $this->pdf->borde); 
        // $this->pdf->Ln(); 
        $this->pdf->Cell(1.5, 0.35, utf8_decode(''), $this->pdf->borde);         
        $this->pdf->Cell(18.8, 0.35, utf8_decode($cliente->entidad), $this->pdf->borde); 
        $this->pdf->Ln();
        $this->pdf->Cell(1.5, 0.35, utf8_decode(''), $this->pdf->borde);         
        $this->pdf->Cell(18.8, 0.35, utf8_decode($cliente->direccion), $this->pdf->borde); 
        $this->pdf->Ln();
        $this->pdf->Cell(1.5, 0.35, utf8_decode(''), $this->pdf->borde);         
        $this->pdf->Cell(11.5, 0.35, utf8_decode($cliente->numerodoc), $this->pdf->borde); 
        $this->pdf->Cell(3, 0.35, utf8_decode(''), $this->pdf->borde);         
        $this->pdf->Cell(4.3, 0.35, utf8_decode($venta->fechaventa), $this->pdf->borde); 
        $this->pdf->Ln(); 
        // $this->pdf->Cell(0, $height, utf8_decode(''), $this->pdf->borde); 
        // $this->pdf->Ln(); 
        $this->pdf->Cell(0, 0.50, utf8_decode(''), $this->pdf->borde); 
        $this->pdf->Ln();  
 
        $modelodet = [];
        if(isset($ventafactura) && $ventafactura->hc)
            $modelodet[] = (object) array('cantidad' => null, 'descripcion' => 'HC', 'descripcion2' => $ventafactura->hc, 'precio' => null, 'total' => null);
        if(isset($ventafactura) && $ventafactura->paciente)
            $modelodet[] = (object) array('cantidad' => null, 'descripcion' => 'Paciente', 'descripcion2' =>$ventafactura->paciente, 'precio' => null, 'total' => null);
        if(isset($ventafactura) && $ventafactura->titular)
            $modelodet[] = (object) array('cantidad' => null, 'descripcion' => 'Parentesco', 'descripcion2' => $ventafactura->titular, 'precio' => null, 'total' => null);
        if(isset($ventafactura) && $ventafactura->empresa) {
            $modelodet[] = (object) array('cantidad' => null, 'descripcion' => 'Empresa', 'descripcion2' =>$ventafactura->empresa, 'precio' => null, 'total' => null);
            // $modelodet[] = (object) array('cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);
        }
        if(isset($ventafactura) && $ventafactura->diagnostico) {

            $cantcaracteres = 45;
            if(isset($ventafactura->zona)) {
                $cantcaracteres = 37;
            }

            $strdx = $ventafactura->diagnostico; 
            if (strlen($ventafactura->diagnostico) > $cantcaracteres) {
                 $strdx = substr($ventafactura->diagnostico, 0, $cantcaracteres) . '..';
            }

            if(isset($ventafactura->zona)) {
                $strdx .= ' (' . $ventafactura->zona . ')';
            }

            $modelodet[] = (object) array('cantidad' => null, 'descripcion' => 'Diagnóstico', 'descripcion2' => $strdx, 'precio' => null, 'total' => null);
            $modelodet[] = (object) array('cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);        
        }
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => 'Indicación', 'descripcion2' => 'MEDICINA FISICA Y REHABILITACION', 'precio' => null, 'total' => null);

        if(isset($ventafactura) && $ventafactura->autorizacion)
            $modelodet[] = (object) array('cantidad' => null, 'descripcion' => 'Autorización', 'descripcion2' => $ventafactura->autorizacion, 'precio' => null, 'total' => null);            
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => 'MEDICINA FISICA Y REHABILITACION', 'precio' => null, 'total' => null);

        
        foreach ($ventadet as $row) {
            $nombreproducto = $row->nombreproducto;
            if (!empty($row->descripcion)) {
                $nombreproducto .= ' ' . $row->descripcion;
            }

            if($row->idproducto === 1) { 
                $row->descripcion = $nombreproducto;
                $row->precio = $row->valorunit;
                $row->total = $row->valorventa;
                $modelodet[] = $row; 
            }
        }
        
        $cabeceraosi = true;
        foreach ($ventadet as $row) {            
            if ($row->idproducto !== 1) {   
                if (isset($ventafactura) && $cabeceraosi) {
                    $modelodet[] = (object) array('cantidad' => null, 'descripcion' => $ventafactura->programa, 'precio' => null, 'total' => null);
                    $cabeceraosi = false;
                } 

                $nombreproducto = $row->nombreproducto;
                if (!empty($row->descripcion)) {
                    $nombreproducto .= ' ' . $row->descripcion;
                }

                $row->descripcion = $nombreproducto;
                $row->precio = $row->valorunit;
                $row->total = $row->valorventa;
                $modelodet[] = $row; 
            }
        }  

        //Completar filas con vacio
        $cant = 17 - count($modelodet); 
        if($cant > 0) {
            for ($i=0; $i < $cant; $i++) { 
                $modelodet[] = (object) array('cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);
            }
        }

        $textoletra = $this->num2letras((float) $venta->total);
        $pos = strpos($textoletra, '/');
        if($pos === false) {
            $textoletra .= ' Soles';
        }
        //Completar filas con deducible y coaseguro
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);
        
        if (isset($ventafactura)) {
            $modelodet[] = (object) array('cantidad' => null, 'descripcion' => $ventafactura->deducible ? ('Deducible      '. $ventafactura->deducible) : '', 'precio' => null, 'total' => $venta->deducible);
            $modelodet[] = (object) array('cantidad' => null, 'descripcion' => $ventafactura->coaseguro ? ('Coaseguro      '. $ventafactura->coaseguro.' %') : '', 'precio' => null, 'total' => $venta->coaseguro);
        }

        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => 'Son ' . $textoletra, 'precio' => null, 'total' => null);
        $modelodet[] = (object) array('cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);
        

        foreach ($modelodet as $row) {              
            $this->pdf->Cell(1.5, $height, utf8_decode($row->cantidad), $this->pdf->borde, 0, 'C');  
            if(isset($row->descripcion2)) {
                // $this->pdf->Cell(3, $height, utf8_decode($row->descripcion), $this->pdf->borde, 0, 'L');   
                // $this->pdf->Cell(10.3, $height, utf8_decode($row->descripcion2), $this->pdf->borde, 0, 'L');   
                $this->pdf->Cell(3, $height, utf8_decode($row->descripcion), $this->pdf->borde, 0, 'L');   
                $this->pdf->Cell(9.5, $height, utf8_decode($row->descripcion2), $this->pdf->borde, 0, 'L');   
            }else {
                //$this->pdf->Cell(13.3, $height, utf8_decode($row->descripcion), $this->pdf->borde, 0, 'L');   
                $this->pdf->Cell(12.5, $height, utf8_decode($row->descripcion), $this->pdf->borde, 0, 'L');   
            }            
            $this->pdf->Cell(2, $height, (isset($row->precio) ? $row->precio : null), $this->pdf->borde, 0, 'R');   
            $stotal = null;
           
            if (isset($row->total)) {
                $stotal = number_format($row->total, 2, '.', ',');
            } 
            $this->pdf->Cell(2, $height, $stotal, $this->pdf->borde, 0, 'R');   
            $this->pdf->Ln();
        }

        $height = 0.47;
        $this->pdf->Cell(16, $height, utf8_decode(''), $this->pdf->borde, 0, 'L');        
        $this->pdf->Cell(2, $height, number_format($venta->subtotal, 2, '.', ','), $this->pdf->borde, 0, 'R');  
        $this->pdf->Ln();
        $this->pdf->Cell(16, $height, utf8_decode(''), $this->pdf->borde, 0, 'L');        
        $this->pdf->Cell(2, $height, number_format($venta->valorimpuesto, 2, '.', ','), $this->pdf->borde, 0, 'R');  
        $this->pdf->Ln();
        $this->pdf->Cell(16, $height, utf8_decode(''), $this->pdf->borde, 0, 'L');        
        $this->pdf->Cell(2, $height, number_format($venta->total, 2, '.', ','), $this->pdf->borde, 0, 'R');  
        $this->pdf->Ln();

        /*Salida*/
        $this->pdf->Output();       
    }  

    public function reportehtml(Request $request, $enterprise, $id)
    {   
       
        $request = $request->all();

        $Objventa = new venta();
        $objEntidad = new entidad();
        
        $venta = $Objventa->venta($id);     
        $ventadet = $Objventa->ventadet($id);
        
        $ventafactura = $Objventa->ventafactura($id); 
        $cliente = $objEntidad->entidad(['entidad.identidad' =>$venta->idcliente]); 

        $table = '<table border="0" cellspacing="0" cellpadding="0">';
        // return $this->crearRespuesta($table, [200, 'info'], '', '', $cliente);  
        $height = 0.3;

        $table .= '<tr><td class="alto"></td><td colspan="5">' . $cliente->entidad . '</td></tr>';
        $table .= '<tr><td class="alto"></td><td colspan="5">' . $cliente->direccion . '</td></tr>';
        $table .= '<tr><td class="alto"></td><td colspan="3">' . $cliente->numerodoc . '</td>
                       <td colspan="2" align="center">' . $venta->fechaventa . '</td></tr>';
        $table .= '<tr><td colspan="6" class="alto">&nbsp;</td></tr>'; 

        $modelodet = [];
        if(isset($ventafactura) && $ventafactura->hc)
            $modelodet[] = (object) array('bloque' => 1, 'cantidad' => null, 'descripcion' => 'HC', 'descripcion2' => $ventafactura->hc, 'precio' => null, 'total' => null);
        if(isset($ventafactura) && $ventafactura->paciente)
            $modelodet[] = (object) array('bloque' => 1, 'cantidad' => null, 'descripcion' => 'Paciente', 'descripcion2' =>$ventafactura->paciente, 'precio' => null, 'total' => null);
        if(isset($ventafactura) && $ventafactura->titular)
            $modelodet[] = (object) array('bloque' => 1, 'cantidad' => null, 'descripcion' => 'Parentesco', 'descripcion2' => $ventafactura->titular, 'precio' => null, 'total' => null);
        if(isset($ventafactura) && $ventafactura->empresa) {
            $modelodet[] = (object) array('bloque' => 1, 'cantidad' => null, 'descripcion' => 'Empresa', 'descripcion2' =>$ventafactura->empresa, 'precio' => null, 'total' => null);

            // $modelodet[] = (object) array('bloque' => 0, 'cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);            

        }
 
        // if($ventafactura->diagnostico) {

        //     $cantcaracteres = 45;
        //     if(isset($ventafactura->zona)) {
        //         $cantcaracteres = 37;
        //     }

        //     $strdx = $ventafactura->diagnostico; 
        //     if (strlen($ventafactura->diagnostico) > $cantcaracteres) {
        //          $strdx = substr($ventafactura->diagnostico, 0, $cantcaracteres) . '..';
        //     }

        //     if(isset($ventafactura->zona)) {
        //         $strdx .= ' (' . $ventafactura->zona . ')';
        //     }

        //     $modelodet[] = (object) array('bloque' => 1, 'cantidad' => null, 'descripcion' => 'Diagnóstico', 'descripcion2' => $strdx, 'precio' => null, 'total' => null);
        //     $modelodet[] = (object) array('bloque' => 0, 'cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);        
        // }

        $diagnosticos = [];

        if (isset($ventafactura) && $ventafactura->diagnostico)
            $diagnosticos = explode('|', $ventafactura->diagnostico);

        if (count($diagnosticos) > 0) {

            if (count($diagnosticos) === 1) {            
                $modelodet[] = (object) array('bloque' => 0, 'cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null); 
            }

            $cantcaracteres = 55;
            foreach ($diagnosticos as $index => $strdx) {
                $tmpstr = mb_strtolower($strdx, 'utf-8'); 
                $strdx = ucwords($tmpstr);

                if($index < 3) { 
                    if (strlen($strdx) > $cantcaracteres) 
                         $strdx = substr($strdx, 0, $cantcaracteres) . '..';                    
                    
                    $textodiagnostico = $index === 0 ? 'Diagnóstico' : '';
                    $modelodet[] = (object) array('bloque' => 1, 'cantidad' => null, 'descripcion' => $textodiagnostico, 'descripcion2' => $strdx, 'precio' => null, 'total' => null);
                }
            }

            if (count($diagnosticos) === 1 || count($diagnosticos) === 2) {            
                $modelodet[] = (object) array('bloque' => 0, 'cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null); 
            }      
        }

        if (isset($ventafactura) && $ventafactura->indicacion) 
            $modelodet[] = (object) array('bloque' => 1, 'cantidad' => null, 'descripcion' => 'Indicación', 'descripcion2' => 'MEDICINA FISICA Y REHABILITACION', 'precio' => null, 'total' => null);   

        if (isset($ventafactura) && $ventafactura->autorizacion)
            $modelodet[] = (object) array('bloque' => 1, 'cantidad' => null, 'descripcion' => 'Autorización', 'descripcion2' => $ventafactura->autorizacion, 'precio' => null, 'total' => null);            
        $modelodet[] = (object) array('bloque' => 0, 'cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);

        if (isset($ventafactura) && $ventafactura->indicacion) 
            $modelodet[] = (object) array('bloque' => 2, 'cantidad' => null, 'descripcion' => 'MEDICINA FISICA Y REHABILITACION', 'precio' => null, 'total' => null);

        
        foreach ($ventadet as $row) {
            if($row->idproducto === 1) { 

                $nombreproducto = $row->nombreproducto;
                if (!empty($row->descripcion)) {
                    $nombreproducto .= ' ' . $row->descripcion;
                }

                $row->bloque = 3;
                $row->descripcion = $nombreproducto;
                $row->precio = $row->valorunit;
                $row->total = $row->valorventa;
                $modelodet[] = $row; 
            }
        }
        
        if (isset($ventafactura) && $ventafactura->programa) {
            $cabeceraosi = true;
        } else {
            $cabeceraosi = false;
        }

        foreach ($ventadet as $row) {
            
            if($cabeceraosi) {
                $modelodet[] = (object) array('bloque' => 2, 'cantidad' => null, 'descripcion' => $ventafactura->programa, 'precio' => null, 'total' => null);
                $cabeceraosi = false;
            }

            if($row->idproducto !== 1){    

                $nombreproducto = $row->nombreproducto;
                if (!empty($row->descripcion)) {
                    $nombreproducto .= ' ' . $row->descripcion;
                }
                
                $row->bloque = 3;
                $row->descripcion = $nombreproducto;
                $row->precio = $row->valorunit;
                $row->total = $row->valorventa;
                $modelodet[] = $row; 
            }


        }  
        //Completar filas con vacio
        $cant = 16 - count($modelodet); 
        if($cant > 0) {
            for ($i=0; $i < $cant; $i++) { 
                $modelodet[] = (object) array('bloque' => 0, 'cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);
            }
        }

        $textoletra = $this->num2letras((float) $venta->total);
        $pos = strpos($textoletra, '/');
        if($pos === false) {
            $textoletra .= ' Soles';
        }

        //Completar filas con deducible y coaseguro
        $modelodet[] = (object) array('bloque' => 0, 'cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);

        if (isset($ventafactura)) {
            $modelodet[] = (object) array('bloque' => 4, 'cantidad' => null, 'descripcion' => $ventafactura->deducible ? ('Deducible      '. $ventafactura->deducible) : '', 'precio' => null, 'total' => $venta->deducible);

            $modelodet[] = (object) array('bloque' => 4, 'cantidad' => null, 'descripcion' => $ventafactura->coaseguro ? ('Coaseguro      '. $ventafactura->coaseguro.' %') : '', 'precio' => null, 'total' => $venta->coaseguro);
        } else {
            $modelodet[] = (object) array('bloque' => 0, 'cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);
            
            $modelodet[] = (object) array('bloque' => 0, 'cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);
        }

        $modelodet[] = (object) array('bloque' => 0, 'cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);

        $modelodet[] = (object) array('bloque' => 2, 'cantidad' => null, 'descripcion' => 'Son ' . $textoletra, 'descripcion2' => '', 'precio' => null, 'total' => null);

        $modelodet[] = (object) array('bloque' => 0, 'cantidad' => null, 'descripcion' => null, 'precio' => null, 'total' => null);
         

        foreach ($modelodet as $row) {

            switch ($row->bloque) {
                case 1:  
                    $table .= '<tr>
                               <td width="60"></td>
                               <td width="90">' . $row->descripcion . '</td>
                               <td colspan="2">' . $row->descripcion2 . '</td>
                               <td colspan="2"></td>
                               </tr>';
                    break;

                case 2:   
                    $table .= '<tr>
                               <td></td> 
                               <td colspan="5">' . $row->descripcion . '</td>
                               </tr>'; 
                    break;

                case 3:    
                    $stotal = null;
                    if(isset($row->total)) {
                        $stotal = number_format($row->total, 2, '.', ',');
                    } 
                   
                    $table .= '<tr>
                               <td align="center">' . $row->cantidad . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . '</td>
                               <td colspan="3">' . $row->descripcion . '</td>
                               <td align="right" width="78">' . (isset($row->valorunit) ? ($row->valorunit. "&nbsp;&nbsp;&nbsp;" ) : null) . '</td>
                               <td align="right" width="88">' . $stotal . "&nbsp;&nbsp;". '</td>
                               </tr>'; 
                    break;

                case 4:  
                    $stotal = null;
                    if(isset($row->total)) {
                        $stotal = number_format($row->total, 2, '.', ',');
                    }

                    $table .= '<tr>
                               <td></td>
                               <td colspan="4">' . $row->descripcion . '</td> 
                               <td align="right">' . $stotal . "&nbsp;&nbsp;". '</td>
                               </tr>';  
                    break;      

                default:
                    $table .= '<tr><td colspan="6">&nbsp;</td></tr>';
                    break;
            }
        }
        
        $table .= '<tr><td style="height: 7px"></td></tr>';
        $table .= '<tr><td colspan="5" class="altoFooter"></td><td align="right">' . number_format($venta->subtotal, 2, '.', ',') . "&nbsp;&nbsp;". '</td></tr>';
        $table .= '<tr><td colspan="5" class="altoFooter"></td><td align="right">' . number_format($venta->valorimpuesto, 2, '.', ',') . "&nbsp;&nbsp;". '</td></tr>';
        $table .= '<tr><td colspan="5" class="altoFooter"></td><td align="right">' . number_format($venta->total, 2, '.', ',') . "&nbsp;&nbsp;". '</td></tr>';
 
        $table .= '</table>';

        return $this->crearRespuesta($table, [200, 'info'], '', '', $modelodet);   
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

