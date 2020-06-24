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
 
    public $web;
    public $borde = 1;
    public $nombresede;
    public $numerodoc;
    public $serienumero; 
    public $iddocumentofiscal;
    public $logo;
    public $path = 'https://sistemas.centromedicoosi.com/img/';     
    public $razonsocial;    
    public $direccion;    
    public $telefono;   
 
    public $pathImg =  '/home/centromedico/public_html/apiosi/public/comprobantes/';
    // public $pathImg =  'C:\\xampp7.3\\htdocs\\apiosi\\public\\comprobantes\\'; 

    function Footer() 
    {
        // Posición: a 1,5 cm del final
        $this->SetY(-5); 

        // Arial italic 8
        $this->SetFont('Arial','I',8);
        $this->SetTextColor(0, 0, 0);

        // Número de página
        $this->Cell(70, 5, utf8_decode($this->web), 0, 0, 'L');
        $this->Cell(100, 5, utf8_decode('Fecha y hora de generación: ') . date('d/m/Y H:i:s'), 0, 0, 'L');
        $this->Cell(0, 5, utf8_decode('Página ').$this->PageNo().'/{nb}', 0, 0, 'R');

        // Linea
        $this->SetDrawColor(0,0,0);  
        $this->SetLineWidth(.5); 
        $this->Line(2, $this->GetY(), 208, $this->GetY()); 
    } 
    
    function Header()
    {
        $b = 1; 
        $this->Image($this->path.$this->logo, 2, 2, 50, 0, 'PNG');  
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0, 93, 169);
        $this->SetFillColor(0, 93, 169); 
        $this->SetDrawColor(0, 93, 169); 
        
        $this->setX(134);
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(74, 8, 'RUC: ' . $this->numerodoc, 'T,L,R', 0, 'C'); 
        $this->Ln();  

        $documentofiscal = null;
        // dd($this->iddocumentofiscal);
        switch ($this->iddocumentofiscal) { 
            case 1:
                $documentofiscal = 'FACTURA';
                break; 
            case 2:
                $documentofiscal = 'BOLETA DE VENTA';
                break;
            case 13:
                $documentofiscal = 'NOTA DE CRÉDITO';
                break;   
            default:
                # code...
                break;
        }

        $this->setX(134);
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(0, 93, 169);
        $this->Cell(74, 8, $documentofiscal, 'L,R', 0, 'C'); 

        $this->Ln(); 
        $this->setX(134);
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(74, 8, $this->serienumero, 'L,B,R', 0, 'C'); 

        $this->setXY(2, 15); 
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0, 93, 169);
        $this->Cell(0, 8, $this->razonsocial, 0, 0, 'L'); 

        $this->Ln(); 
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0); 
        $this->MultiCell(132, 5, utf8_decode($this->direccion), 0, 'L', false, 2); 
        
        if (empty($this->telefono)) {
            $this->telefono = 'TELÉFONO: (511) 739 0888 ANEXO 101';
        }

        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(0, 0, 0);
        $this->MultiCell(132, 5, utf8_decode($this->telefono), 0, 'L', false, 3); 
    }

    function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $maxline=0)
    {
        //Output text with automatic or explicit line breaks, at most $maxline lines
        $cw=&$this->CurrentFont['cw'];
        if($w==0)
            $w=$this->w-$this->rMargin-$this->x;
        $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
        $s=str_replace("\r",'',$txt);
        $nb=strlen($s);
        if($nb>0 && $s[$nb-1]=="\n")
            $nb--;
        $b=0;
        if($border)
        {
            if($border==1)
            {
                $border='LTRB';
                $b='LRT';
                $b2='LR';
            }
            else
            {
                $b2='';
                if(is_int(strpos($border,'L')))
                    $b2.='L';
                if(is_int(strpos($border,'R')))
                    $b2.='R';
                $b=is_int(strpos($border,'T')) ? $b2.'T' : $b2;
            }
        }
        $sep=-1;
        $i=0;
        $j=0;
        $l=0;
        $ns=0;
        $nl=1;
        while($i<$nb)
        {
            //Get next character
            $c=$s[$i];
            if($c=="\n")
            {
                //Explicit line break
                if($this->ws>0)
                {
                    $this->ws=0;
                    $this->_out('0 Tw');
                }
                $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
                $i++;
                $sep=-1;
                $j=$i;
                $l=0;
                $ns=0;
                $nl++;
                if($border && $nl==2)
                    $b=$b2;
                if($maxline && $nl>$maxline)
                    return substr($s,$i);
                continue;
            }
            if($c==' ')
            {
                $sep=$i;
                $ls=$l;
                $ns++;
            }
            $l+=$cw[$c];
            if($l>$wmax)
            {
                //Automatic line break
                if($sep==-1)
                {
                    if($i==$j)
                        $i++;
                    if($this->ws>0)
                    {
                        $this->ws=0;
                        $this->_out('0 Tw');
                    }
                    $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
                }
                else
                {
                    if($align=='J')
                    {
                        $this->ws=($ns>1) ? ($wmax-$ls)/1000*$this->FontSize/($ns-1) : 0;
                        $this->_out(sprintf('%.3F Tw',$this->ws*$this->k));
                    }
                    $this->Cell($w,$h,substr($s,$j,$sep-$j),$b,2,$align,$fill);
                    $i=$sep+1;
                }
                $sep=-1;
                $j=$i;
                $l=0;
                $ns=0;
                $nl++;
                if($border && $nl==2)
                    $b=$b2;
                if($maxline && $nl>$maxline)
                {
                    if($this->ws>0)
                    {
                        $this->ws=0;
                        $this->_out('0 Tw');
                    }
                    return substr($s,$i);
                }
            }
            else
                $i++;
        }
        //Last chunk
        if($this->ws>0)
        {
            $this->ws=0;
            $this->_out('0 Tw');
        }
        if($border && is_int(strpos($border,'B')))
            $b.='B';
        $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
        $this->x=$this->lMargin;
        return '';
    }
}

class invoiceController extends Controller 
{     
    
    public function reporte(Request $request, $enterprise, $id)
    {    

        $pdf = new PDF();

        $request = $request->all();

        $Objventa = new venta();
        $objEntidad = new entidad();
        $objEmpresa = new empresa();

        $height = 6;        
        $idempresa = $objEmpresa->idempresa($enterprise);
        $empresa = $objEmpresa->empresa(['empresa.idempresa' => $idempresa]); 

        $venta = $this->venta($id);
        // $ventafactura = $this->ventafactura($id);
        $ventadet = $Objventa->ventadet($id);  

        // Datos decabecera          
        $pdf->web = $empresa->paginaweb;
        $pdf->logo = $empresa->url.'/logopdf.png';
        $pdf->razonsocial = $venta->afiliado;
        $pdf->direccion = $venta->direccionsede;
        $pdf->telefono = $venta->telefonosede;
        $pdf->serienumero = $venta->serie . '-' . $venta->serienumero;
        $pdf->numerodoc = $venta->numerodocafiliado;
        $pdf->iddocumentofiscal = $venta->iddocumentofiscal;

        switch ($venta->iddocumento) { 
            case 1: $docIdentif = 'DNI'; break;
            case 2: $docIdentif = 'RUC'; break;
            case 3: $docIdentif = 'C.EXT.'; break;
            case 4: $docIdentif = 'PASAP.'; break;
            default:
                $docIdentif = '';
                break;
        }

        // Campos adicionales 
        $adicionales = array();
        switch ($venta->codigosunat) {
            case '01': //Factura   
                 
                if ($venta->pacientefactura) { 
                    $adicionales[] = array(
                        'titulo' => 'Paciente', 
                        'valor' => $venta->pacientefactura 
                    );
                }

                if ($venta->titularfactura) {
                    $adicionales[] = array(
                        'titulo' => 'Parentesco', 
                        'valor' => $venta->titularfactura 
                    );
                }

                if ($venta->empresafactura) {
                    $adicionales[] = array(
                        'titulo' => 'Empresa',
                        'valor' => $venta->empresafactura 
                    );
                }

                if ($venta->diagnosticofactura) {
                    $adicionales[] = array(
                        'titulo' => 'Diagnóstico', 
                        'valor' => $venta->diagnosticofactura 
                    );
                }  

                if ($venta->indicacionfactura) {
                    $adicionales[] = array(
                        'titulo' => 'Indicación', 
                        'valor' => $venta->indicacionfactura 
                    );
                } 

                if ($venta->autorizacionfactura) {
                    $adicionales[] = array(
                        'titulo' => 'Autorización', 
                        'valor' => $venta->autorizacionfactura 
                    );
                } 

                if ($venta->programafactura) {
                    $adicionales[] = array(
                        'titulo' => 'Programa', 
                        'valor' => $venta->programafactura 
                    );
                } 

                if ($venta->deduciblefactura) {
                    $adicionales[] = array(
                        'titulo' => 'Deducible ('.$venta->deduciblefactura.')', 
                        'valor' => $venta->deducible 
                    );
                }

                if ($venta->coasegurofactura) {
                    $adicionales[] = array(
                        'titulo' => 'Coaseguro ('.$venta->coasegurofactura.'%)',
                        'valor' => $venta->coaseguro 
                    );
                }

                break;
            case '03': //Boleta
                
                if ($venta->idpaciente) {
                    $adicionales[] = array(
                        'titulo' => 'Paciente:', 
                        'valor' => $venta->paciente 
                    );
                }
                break;
        }
        
        $pdf->SetMargins(2, 2, 2);
        $pdf->SetAutoPageBreak(true, 4);
        $pdf->AliasNbPages(); 
        $pdf->SetFillColor(0, 93, 169); 
        $pdf->SetDrawColor(0, 93, 169); 
        $pdf->SetLineWidth(0.2); 
        $pdf->AddPage(); 

        // Filiacion  
        $pdf->Ln();  
        $pdf->Ln();  
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 93, 169); 
        $pdf->Cell(30, $height, utf8_decode('Señor(es):'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(176, $height, utf8_decode($venta->cliente), 'B', 0, 'L');
        $pdf->Ln(); 
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 93, 169); 
        $pdf->Cell(30, $height, utf8_decode('Dirección:'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(176, $height, utf8_decode($venta->direccioncliente), 'B', 0, 'L');
        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 93, 169); 
        $pdf->Cell(30, $height, utf8_decode($docIdentif) . ':', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(68, $height, utf8_decode($venta->numerodoc), 'B', 0, 'L'); 
        $pdf->Cell(5, $height); 
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 93, 169); 
        $pdf->Cell(30, $height, utf8_decode('Moneda:'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(73, $height, utf8_decode('SOLES'), 'B', 0, 'L');
        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 93, 169); 
        $pdf->Cell(30, $height, utf8_decode('Fecha emisión:'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(68, $height, utf8_decode($venta->fechaventa), 'B', 0, 'L'); 
        $pdf->Cell(5, $height); 
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 93, 169); 

        if ($venta->iddocumentofiscal === 13) {
            $titulo = 'CPE que modifica:';
            $valor = $venta->refserie . '-' . $venta->refserienumero;            
        } else {
            $titulo = 'Fecha vencimiento:';
            $valor = '';
        }


        $pdf->Cell(30, $height, utf8_decode($titulo), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(73, $height, utf8_decode($valor), 'B', 0, 'L');
        $pdf->Ln();

        if ($venta->iddocumentofiscal === 13) {
            $motivo = '';
            if ($venta->tiponotacredito === '1') {  
                $motivo = 'ANULACIÓN DE LA OPERACIÓN';
            }

            if ($venta->tiponotacredito === '2') {
                $motivo = 'DEVOLUCIÓN TOTAL';
            }

            if ($venta->tiponotacredito === '3') {
                $motivo = 'DEVOLUCIÓN PARCIAL';
            } 

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetTextColor(0, 93, 169); 
            $pdf->Cell(30, $height, utf8_decode('Motivo:'), 0, 0, 'L');
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(0, 0, 0); 
            $pdf->Cell(68, $height, utf8_decode($motivo), 'B', 0, 'L'); 
            $pdf->Cell(5, $height);              
            $pdf->Cell(103, $height, utf8_decode($venta->descripcion), 'B', 0, 'L');
            $pdf->Ln();        
        }

        $modelodet = [];   
        foreach ($ventadet as $row) {  
            $row->nombreproducto .= !empty($row->descripcion) ? (' ' . $row->descripcion) : '';
            $row->descripcion = $row->nombreproducto; 
            $modelodet[] = $row;  
        }  

        $textoletra = $this->num2letras((float) $venta->total);
        $pos = strpos($textoletra, '/');        
        if ($pos === false) {
            $textoletra .= ' SOLES';
        } 
        
        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFillColor(0, 93, 169);
        $pdf->Cell(16, 10, utf8_decode('CANT.'), 1, 0, 'C', true);
        $pdf->Cell(110, 10, utf8_decode('DESCRIPCIÓN'), 1, 0, 'L', true);
        $pdf->Cell(16, 10, utf8_decode('UM'), 1, 0, 'C', true);
        $pdf->Cell(16, 5, utf8_decode('VALOR'), 1, 0, 'C', true);
        $pdf->Cell(16, 5, utf8_decode('PRECIO'), 1, 0, 'C', true);
        $pdf->Cell(16, 5, utf8_decode('VALOR'), 1, 0, 'C', true); 
        $pdf->Cell(16, 5, utf8_decode('VENTA'), 1, 0, 'C', true);
        $pdf->Ln();
        $pdf->setX(144);
        $pdf->Cell(16, 5, utf8_decode('UNIT.'), 1, 0, 'C', true);
        $pdf->Cell(16, 5, utf8_decode('UNIT.'), 1, 0, 'C', true);
        $pdf->Cell(16, 5, utf8_decode('VENTA'), 1, 0, 'C', true); 
        $pdf->Cell(16, 5, utf8_decode('TOTAL'), 1, 0, 'C', true);

        $pdf->Ln(); 
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0); 
        foreach ($modelodet as $row) {    
            $pdf->Cell(16, $height, utf8_decode($row->cantidad), $pdf->borde, 0, 'C');  
            $pdf->Cell(110, $height, utf8_decode($row->descripcion), $pdf->borde, 0, 'L');                 
            $pdf->Cell(16, $height, 'ZZ', $pdf->borde, 0, 'C');   
            $pdf->Cell(16, $height, number_format($row->valorunit, 2, '.', ','), $pdf->borde, 0, 'R');   
            $pdf->Cell(16, $height, $row->preciounit, $pdf->borde, 0, 'R');   
            $pdf->Cell(16, $height, $row->valorventa, $pdf->borde, 0, 'R');   
            $pdf->Cell(16, $height, number_format($row->total, 2, '.', ','), $pdf->borde, 0, 'R');   
            $pdf->Ln();
        }

        $pdf->Ln(3);
        $pdf->Cell(146, $height, utf8_decode('SON: ' . $textoletra), 0, 0, 'L');        
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(30, $height, utf8_decode('DESCUENTOS GLOBALES'), 'L,T', 0, 'L');    
        $pdf->SetFont('Arial', '', 8); 
        $pdf->Cell(30, $height, number_format(0, 2, '.', ','), 'T,R', 0, 'R');  
        $pdf->Ln();
        $pdf->Cell(146, $height, utf8_decode(''), 0, 0, 'L');        
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(30, $height, utf8_decode('GRAVADA'), 'L', 0, 'L');    
        $pdf->SetFont('Arial', '', 8); 
        $pdf->Cell(30, $height, number_format($venta->subtotal, 2, '.', ','), 'R', 0, 'R');  
        $pdf->Ln();
        $pdf->Cell(146, $height, utf8_decode(''), 0, 0, 'L');     
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(30, $height, utf8_decode('IGV 18.00 %'), 'L', 0, 'L');    
        $pdf->SetFont('Arial', '', 8);    
        $pdf->Cell(30, $height, number_format($venta->valorimpuesto, 2, '.', ','), 'R', 0, 'R');  
        $pdf->Ln();
        $pdf->Cell(146, $height, utf8_decode(''), 0, 0, 'L');   
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(30, $height, utf8_decode('TOTAL'),'L,B', 0, 'L');    
        $pdf->SetFont('Arial', '', 8);      
        $pdf->Cell(30, $height, number_format($venta->total, 2, '.', ','), ',B,R', 0, 'R');  
        
        $pdf->Ln(); 
        $pdf->SetFont('Arial', 'B', 9); 
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Cell(130, $height, utf8_decode('Información adicional'), 'B', 0, 'L');
        $pdf->SetDrawColor(0, 93, 169);

        foreach($adicionales as $valor) {
            $pdf->Ln();
            $pdf->SetFont('Arial', '', 8); 
            $pdf->Cell(30, $height, utf8_decode($valor['titulo']), 0, 0, 'L');            
            $pdf->Cell(100, $height, utf8_decode($valor['valor']), 0, 0, 'L');  
        }
 

        // dd($pdf->pathImg);
        // $this->generarCodigoQR($id, $pdf->pathImg);
 
        //Codigo QR
        $pdf->Ln();
        $pdf->setY(-28);
        $pdf->Cell(22, 20, utf8_decode(''), 1, 0, 'L');
         
        $archivo = $id .'.png';

        if (!file_exists($pdf->pathImg . $archivo)) {
            return $this->crearRespuesta('Archivo de firma no existe. Comunicarse con administrador.', [200, 'info']);
        } else {  
            $pdf->setXY(0, -33);            
            $pdf->Image($pdf->pathImg . $archivo, $pdf->getX() - 2, $pdf->getY() + 1, 28, 0, 'PNG');
        }

        $pdf->setY(-28);
        $pdf->setX(24);
        $pdf->Cell(184, 4, utf8_decode(''), 0, 0, 'L');
        $pdf->Ln();
        $pdf->setX(24);
        $pdf->Cell(184, 4, utf8_decode('Representación impresa del Comprobante Electronico.'), 0, 0, 'L');
        $pdf->Ln();
        $pdf->setX(24);
        $pdf->Cell(184, 4, utf8_decode('Autorizado mediante resolucion Nro. 0340050010017/SUNAT'), 0, 0, 'L');
        $pdf->Ln();
        $pdf->setX(24);
        $pdf->Cell(184, 4, utf8_decode('Para consultar el comprobante ingresar a https://escondatagate.page.link/Bj3p.'), 0, 0, 'L');
        $pdf->Ln();
        $pdf->setX(24);
        $pdf->Cell(184, 4, utf8_decode(''), 0, 0, 'L');
        /*Salida*/
        // $pdf->Output();       

        $nombre = 'comprobante_' . date('Y-m-d_H-i-s') .'.pdf';
        $pdf->Output('F', 'comprobantes/' . $nombre); 
    }  
  
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
 

    private function venta($id) {
        
        $campos = ['venta.idventa', 'venta.idsede', 'sede.nombre as nombresede',  'sede.direccion as direccionsede', 'sede.telefono as telefonosede', 'afiliado.numerodoc as numerodocafiliado', 'afiliado.entidad as afiliado', 'venta.iddocumentofiscal', 'documentofiscal.nombre as nombredocfiscal', 'venta.serie', 'venta.serienumero', 'venta.idcliente', 'cliente.entidad as cliente', 'venta.idpaciente', 'paciente.entidad as paciente', 'venta.fechaventa', 'venta.idmediopago', 'venta.idestadodocumento', 'venta.descuento', 'venta.subtotal', 'venta.valorimpuesto', 'venta.total', 'venta.deducible', 'venta.coaseguro', 'venta.partetipotarjeta', 'venta.parteopetarjeta', 'venta.partemontotarjeta','venta.parteefectivo', 'cliente.iddocumento', 'cliente.numerodoc', 'cliente.direccion as direccioncliente', 'venta.idcicloatencion', 'venta.idafiliado', 'venta.idapertura', 'venta.created_at', 'venta.updated_at', 'venta.control',  'venta.fechactrol', 'venta.revisioncomentario', 'venta.controlcomentario', 'venta.tarjetapriope', 'venta.tarjetaprimonto', 'venta.idtarjetaseg', 'venta.tarjetasegope', 'venta.tarjetasegmonto', 'venta.idestadoseguro', 'venta.descripcion', 'venta.movecon', 'documentofiscal.codigosunat', 'venta.tiponotacredito', 'venta.cpeemision', 'venta.cpeanulacion', 'venta.cpecorreo', 'venta.cpeticket', 'venta.idventaref', 'venta.idventareemplazo', 'ventafactura.paciente as pacientefactura', 'ventafactura.titular as titularfactura', 'ventafactura.empresa as empresafactura', 'ventafactura.diagnostico as diagnosticofactura', 'ventafactura.indicacion as indicacionfactura', 'ventafactura.autorizacion as autorizacionfactura', 'ventafactura.programa as programafactura', 'ventafactura.deducible as deduciblefactura', 'ventafactura.coaseguro as coasegurofactura', 'ventaref.serie as refserie', 'ventaref.serienumero as refserienumero']; 

        //\DB::enableQueryLog();
        $data = \DB::table('venta')                
                ->join('entidad as afiliado', 'venta.idafiliado', '=', 'afiliado.identidad')
                ->join('documentofiscal', 'venta.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal')
                ->join('entidad as cliente', 'venta.idcliente', '=', 'cliente.identidad')
                ->join('documentoserie', 'venta.iddocumentofiscal', '=', 'documentoserie.iddocumentofiscal')
                ->join('sede', 'documentoserie.idsede', '=', 'sede.idsede')
                ->leftJoin('entidad as paciente', 'venta.idpaciente', '=', 'paciente.identidad')
                ->leftJoin('ventafactura', 'venta.idventa', '=', 'ventafactura.idventa')
                ->leftJoin('venta as ventaref', 'venta.idventaref', '=', 'ventaref.idventa') //NC referencia
                ->select($campos)
                ->whereNull('venta.deleted')
                ->where('venta.idventa', $id)
                ->whereRaw("documentoserie.identidad = venta.idafiliado AND documentoserie.serie = venta.serie")
                ->first();
        
        //dd(\DB::getQueryLog());    
        if($data) { 
            $data->fechaventa = $this->formatFecha($data->fechaventa);
            $data->fechactrol = $this->formatFecha($data->fechactrol);  

            if(isset($data->reemplazoserienumero))
                $data->reemplazoDocumentoSerieNumero = '(' . $data->reemplazoacronimo . ') ' . $data->reemplazonombredocventa . ' N° ' . $data->reemplazoserie . '-' . str_pad($data->reemplazoserienumero, 6, "0", STR_PAD_LEFT);
        }
        
        return $data;
    }

    
}
