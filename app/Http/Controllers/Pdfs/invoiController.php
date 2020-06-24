<?php
namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller; 
use App\Models\empresa; 
use Codedge\Fpdf\Fpdf\Fpdf as baseFpdf;
  
class PDF extends baseFpdf 
{        
 
    public $web;
    public $borde = 1;
    public $nombresede;
    public $numerodoc;
    public $serienumero; 
    public $tipodocumento;
    public $logo;
    public $path = 'https://sistemas.centromedicoosi.com/img/';     
    public $razonsocial;    
    public $direccion;    
    public $telefono;   
    // public $pathImg =  'C:\\xampp7.3\\htdocs\\apiosi\\public\\comprobantes\\';
    public $pathImg =  '/home/centromedico/public_html/apiosi/public/comprobantes/';
    // public $pathImg =  '/home/ositest/public_html/apiosi/public/comprobantes/';

    function Footer() 
    {
        // Posición: a 1,5 cm del final
        $this->SetY(-5); 

        // Arial italic 8
        $this->SetFont('Arial','I',8);
        $this->SetTextColor(0, 0, 0);

        // Número de página
        $this->Cell(70, 5, utf8_decode($this->web), 0, 0, 'L');
        // $this->Cell(100, 5, utf8_decode('Fecha y hora de generación: ') . date('d/m/Y H:i:s'), 0, 0, 'L');
        $this->Cell(100, 5, '');
        $this->Cell(0, 5, utf8_decode('Página ').$this->PageNo().'/{nb}', 0, 0, 'R');

        // Linea
        $this->SetDrawColor(0,0,0);  
        $this->SetLineWidth(.5); 
        $this->Line(2, $this->GetY(), 208, $this->GetY()); 
    } 
    
    function Header()
    {
        $b = 1; 
        $this->Image($this->path.$this->logo, 2, 2, 80, 0, 'PNG');  
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0, 93, 169);
        $this->SetFillColor(0, 93, 169); 
        $this->SetDrawColor(0, 93, 169); 

        $documentofiscal = null;
        // dd($this->iddocumentofiscal);
        $heightc = 8;
        $sizetc = 14;
        switch ($this->tipodocumento) { 
            case '01':
                $documentofiscal = 'FACTURA ELECTRÓNICA'; 
                break; 
            case '03':
                $documentofiscal = 'BOLETA DE VENTA';
                $heightc = 6;
                $sizetc = 13;
                break;
            case '07':
                $documentofiscal = 'NOTA DE CRÉDITO';
                $heightc = 6;
                $sizetc = 13;
                break;   
            default:
                # code...
                break;
        }
        
        $this->setX(134);
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(74, $heightc, 'RUC: ' . $this->numerodoc, 'T,L,R', 0, 'C'); 
        $this->Ln();  
 
        $this->setX(134);
        $this->SetFont('Arial', 'B', $sizetc);
        $this->SetTextColor(0, 93, 169);
        $this->Cell(74, $heightc, utf8_decode($documentofiscal), 'L,R', 0, 'C'); 
        $this->Ln(); 

        if ($this->tipodocumento === '03' || $this->tipodocumento === '07') {
            $this->setX(134);
            $this->SetFont('Arial', 'B', $sizetc);
            $this->SetTextColor(0, 93, 169);
            $this->Cell(74, $heightc, utf8_decode('ELECTRÓNICA'), 'L,R', 0, 'C'); 
            $this->Ln();
        }

        $this->setX(134);
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(74, $heightc, $this->serienumero, 'L,B,R', 0, 'C'); 


        $this->setXY(2, 15); 
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0, 93, 169);
        $this->Cell(0, 8, $this->razonsocial, 0, 0, 'L'); 

        $this->Ln(); 
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(0, 0, 0); 
        $this->MultiCell(132, 5, utf8_decode($this->direccion), 0, 'L', false, 2);  
        $this->SetFont('Arial', 'I', 8); 
        $this->MultiCell(132, 5, !empty($this->telefono) ? (utf8_decode('Teléfono: ') . $this->telefono) : '' , 0, 'L', false, 3); 
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

class invoiController extends Controller 
{        
    public function reporte($id, $datos, $telefono, $codTipoDocumento, $correoenvio, $idafiliado)
    {      
        $key = key($datos);
        $data = $datos[$key];  

        $pdf = new PDF();  
        $objEmpresa = new empresa();

        $height = 6;        
        $idempresa = 1;
        $empresa = $objEmpresa->empresa(['empresa.idempresa' => $idempresa]);

        $logopdf = null; 
        if (in_array($idafiliado, [87, 239, 240, 244, 245, 256, 259, 261, 262, 263, 4844, 31058])) { 
            $logopdf = "logopdfinvoiceosi.png";
        } 
        if (in_array($idafiliado, [25425, 29508])) { 
            $logopdf = "logopdfinvoiceunion.png";
        }
        
        // Datos decabecera          
        $pdf->web = $empresa->paginaweb;
        $pdf->logo = $empresa->url.'/' .$logopdf;
        $pdf->razonsocial = $data['EMI']['razonSocial'];
        $pdf->direccion = $data['EMI']['direccion'];
        $pdf->telefono = $telefono;
        $pdf->serienumero = $data['IDE']['numeracion'];
        $pdf->numerodoc = $data['EMI']['numeroDocId'];
        $pdf->tipodocumento = $codTipoDocumento;

        switch ($data['REC']['tipoDocId']) { 
            case 1: $docIdentif = 'DNI'; break;
            case 6: $docIdentif = 'RUC'; break;
            case 4: $docIdentif = 'C.EXT.'; break;
            case 7: $docIdentif = 'PASAP.'; break;
            default: $docIdentif = ''; break;
        }

        $nombreFile = $data['EMI']['numeroDocId'] .'-'. 
                      $codTipoDocumento . '-'.
                      $data['IDE']['numeracion'];
        
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
        $pdf->Cell(176, $height, utf8_decode($data['REC']['razonSocial']), 'B', 0, 'L');
        $pdf->Ln(); 
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 93, 169); 
        $pdf->Cell(30, $height, utf8_decode('Dirección:'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0);

        $direccioncliente = ''; 
        if (isset($data['REC']['direccion'])) {
            $direccioncliente = $data['REC']['direccion'];
        }

        if (isset($data['REC']['distrito'])) {
            $direccioncliente .= (!empty($direccioncliente) ? ' ' : '') . $data['REC']['distrito'];
        }

        if (isset($data['REC']['provincia'])) {
            $direccioncliente .= (!empty($direccioncliente) ? '. ' : '') . $data['REC']['provincia'];
        }

        if (isset($data['REC']['departamento'])) {
            $direccioncliente .= (!empty($direccioncliente) ? ' - ' : '') . $data['REC']['departamento'];
        }        

        $pdf->Cell(176, $height, utf8_decode($direccioncliente), 'B', 0, 'L');
        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 93, 169); 
        $pdf->Cell(30, $height, utf8_decode($docIdentif) . ':', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(68, $height, utf8_decode($data['REC']['numeroDocId']), 'B', 0, 'L'); 
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
        $fechaemision = explode('-', $data['IDE']['fechaEmision']);  
        $pdf->Cell(68, $height, $fechaemision[2].'/'.$fechaemision[1].'/'.$fechaemision[0], 'B', 0, 'L'); 
        $pdf->Cell(5, $height); 
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 93, 169); 

        if ($codTipoDocumento === '07') {
            $titulo = 'CPE que modifica:';
            $valor = $data['DRF'][0]['numeroDocRelacionado'];
        } else {
            $titulo = 'Fecha vencimiento:';
            $valor = '';
        }

        $pdf->Cell(30, $height, utf8_decode($titulo), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(73, $height, utf8_decode($valor), 'B', 0, 'L');
        $pdf->Ln();

        if ($codTipoDocumento === '07') {
            $motivo = '';
            if ($data['DRF'][0]['codigoMotivo'] === '01') {  
                $motivo = 'ANULACIÓN DE LA OPERACIÓN';
            }

            if ($data['DRF'][0]['codigoMotivo'] === '06') {  
                $motivo = 'DEVOLUCIÓN TOTAL';
            }

            if ($data['DRF'][0]['codigoMotivo'] === '07') {  
                $motivo = 'DEVOLUCIÓN PARCIAL';
            } 

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetTextColor(0, 93, 169); 
            $pdf->Cell(30, $height, utf8_decode('Motivo:'), 0, 0, 'L');
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(0, 0, 0); 
            $pdf->Cell(68, $height, utf8_decode($motivo), 'B', 0, 'L'); 
            $pdf->Cell(5, $height);              
            $pdf->Cell(103, $height, utf8_decode($data['DRF'][0]['descripcionMotivo']), 'B', 0, 'L');
            $pdf->Ln();        
        }

        // $pdf->Ln();
        if ($codTipoDocumento === '03') {
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetTextColor(0, 93, 169); 
            $pdf->Cell(30, $height, utf8_decode('Correo envío:'), 0, 0, 'L');
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(0, 0, 0); 
            $pdf->Cell(68, $height, $correoenvio, 'B', 0, 'L'); 
            $pdf->Ln();
        }
        
        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFillColor(0, 93, 169);        
        $pdf->Cell(94, 10, utf8_decode('DESCRIPCIÓN'), 1, 0, 'L', true);
        $pdf->Cell(16, 10, utf8_decode('CANT.'), 1, 0, 'C', true);
        $pdf->Cell(16, 10, utf8_decode('UM'), 1, 0, 'C', true);
        $pdf->Cell(16, 5, utf8_decode('VALOR'), 1, 0, 'C', true);
        $pdf->Cell(16, 5, utf8_decode('PRECIO'), 1, 0, 'C', true);
        $pdf->Cell(16, 5, utf8_decode('VALOR'), 1, 0, 'C', true); 
        $pdf->Cell(16, 10, utf8_decode('IGV'), 1, 0, 'C', true); 
        $pdf->Cell(16, 5, utf8_decode('VENTA'), 1, 0, 'C', true);
        $pdf->Ln();
        $pdf->setX(128);
        $pdf->Cell(16, 5, utf8_decode('UNIT.'), 1, 0, 'C', true);
        $pdf->Cell(16, 5, utf8_decode('UNIT.'), 1, 0, 'C', true);        
        $pdf->Cell(16, 5, utf8_decode('VENTA'), 1, 0, 'C', true);
        $pdf->setX(192); 
        $pdf->Cell(16, 5, utf8_decode('TOTAL'), 1, 0, 'C', true);

        $pdf->Ln(); 
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0); 
        $background = true;
        foreach ($data['DET'] as $row) {

            if ($background) {
                $pdf->SetFillColor(255, 255, 255);
            } else {
                $pdf->SetFillColor(240, 240, 240);
            }

            // $x1 = $pdf->getX();
            $y1 = $pdf->getY();
            $pdf->MultiCell(94, $height, utf8_decode($row['descripcionProducto']), 1, 'L', true, 2); 
            $y2temp = $pdf->getY();
            $hcelda = $y2temp - $y1;
            $pdf->setXY(96, $y1);
            $pdf->Cell(16, $hcelda, utf8_decode($row['cantidadItems']), 1, 0, 'C', true);                  
            $pdf->Cell(16, $hcelda, utf8_decode($row['unidad']), 1, 0, 'C', true);

            $valorUnitario = str_replace(",", "", $row['valorUnitario']);

            $pdf->Cell(16, $hcelda, number_format((float) $valorUnitario, 2, '.', ','), 1, 0, 'R', true);
            $pdf->Cell(16, $hcelda, $row['precioVentaUnitario'], 1, 0, 'R', true);
            $pdf->Cell(16, $hcelda, $row['valorVenta'], 1, 0, 'R', true);
            $pdf->Cell(16, $hcelda, $row['montoTotalImpuestos'], 1, 0, 'R', true);

            $valorVenta = str_replace(",", "", $row['valorVenta']);
            $montoTotalImpuestos = str_replace(",", "", $row['montoTotalImpuestos']);

            $total = (float) $valorVenta +  (float) $montoTotalImpuestos; 

            $pdf->Cell(16, $hcelda, number_format($total, 2, '.', ','), 1, 0, 'R', true);   
            $pdf->Ln();
             
            $background = !$background;
        }

        $dsctoGlobal = 0;
        if (!empty($data['CAB']['cargoDescuento'])) { 
            $dsctoGlobal = str_replace(",", "", $data['CAB']['cargoDescuento'][0]['montoCargoDescuento']);
        }

        $pdf->Ln(3);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(146, $height, utf8_decode('SON: ' . $data['CAB']['leyenda'][0]['descripcion']), 0, 0, 'L');                
        $pdf->Cell(30, $height, utf8_decode('Descuentos Globales'), 'L,T', 0, 'L');
        // $pdf->SetFont('Arial', '', 8); 
        $pdf->Cell(30, $height, number_format($dsctoGlobal, 2, '.', ','), 'T,R', 0, 'R');  
        $pdf->Ln();
        $pdf->Cell(146, $height, utf8_decode(''), 0, 0, 'L');        
        // $pdf->SetFont('Arial', '', 8);
        // $pdf->SetTextColor(0, 0, 0); 
        $pdf->Cell(30, $height, utf8_decode('Total - Op. Gravadas'), 'L', 0, 'L');    
        // $pdf->SetFont('Arial', '', 8); 
        $pdf->Cell(30, $height, $data['CAB']['gravadas']['totalVentas'], 'R', 0, 'R');  
        $pdf->Ln();
        $pdf->Cell(146, $height, utf8_decode(''), 0, 0, 'L');     
        // $pdf->SetFont('Arial', '', 8);
        // $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(30, $height, utf8_decode('I.G.V.'), 'L', 0, 'L');    
        // $pdf->SetFont('Arial', '', 8);    
        $pdf->Cell(30, $height, $data['CAB']['montoTotalImpuestos'], 'R', 0, 'R');  
        $pdf->Ln();
        $pdf->Cell(146, $height, utf8_decode(''), 0, 0, 'L');   
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(30, $height, utf8_decode('Importe total'),'L,B', 0, 'L');          
        $pdf->Cell(30, $height, $data['CAB']['importeTotal'], ',B,R', 0, 'R');  
        
        

        if (isset($data['ADI'])) {

            $pdf->Ln(); 
            $pdf->SetFont('Arial', 'B', 9); 
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->Cell(130, $height, utf8_decode('Información adicional'), 'B', 0, 'L');
            $pdf->SetDrawColor(0, 93, 169);

            foreach($data['ADI'] as $valor) {
                $pdf->Ln();
                $pdf->SetFont('Arial', '', 8); 
                $pdf->Cell(30, $height, utf8_decode($valor['tituloAdicional']), 0, 0, 'L');            
                $pdf->Cell(100, $height, utf8_decode($valor['valorAdicional']), 0, 0, 'L');  
            } 
        }
 
        //Codigo QR
        $pdf->Ln();
        $pdf->setY(-28);
        $pdf->Cell(22, 20, utf8_decode(''), 1, 0, 'L'); 

        if (!file_exists($pdf->pathImg . $nombreFile . '.png')) {
            \Log::info(print_r('Archivo QR no existe. Comunicarse con administrador.', true));    
        } else {  
            $pdf->setXY(0, -33);            
            $pdf->Image($pdf->pathImg . $nombreFile . '.png', $pdf->getX() - 2, $pdf->getY() + 1, 28, 0, 'PNG');
        }

        $pdf->SetFont('Arial', '', 8); 
        $pdf->setY(-28);
        $pdf->setX(24);
        $pdf->Cell(184, 4, utf8_decode(''), 0, 0, 'L');
        $pdf->Ln();
        $pdf->setX(24);
        $pdf->Cell(184, 4, utf8_decode(''), 0, 0, 'L');
        $pdf->Ln();
        $pdf->setX(24);
        $pdf->Cell(184, 4, utf8_decode(''), 0, 0, 'L');
        $pdf->Ln();
        $pdf->setX(24);
        $pdf->Cell(184, 4, utf8_decode('Representación impresa del Comprobante Electronico.'), 0, 0, 'L');
        $pdf->Ln();
        $pdf->setX(24);
        $pdf->Cell(184, 4, utf8_decode('Autorizado mediante resolucion Nro. 0340050010017/SUNAT'), 0, 0, 'L');
        /*Salida*/

        // $pdf->Output();         
        $pdf->Output('F', 'comprobantes/' . $nombreFile . '.pdf');    

        $fileName = NULL;
        if (!file_exists($pdf->pathImg . $nombreFile . '.pdf')) {
            \Log::info(print_r('PDF no se genero.', true));   
        } else {
            $fileName = $nombreFile . '.pdf';
        }

        return $fileName;
    }  
}
