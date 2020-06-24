<?php
namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\empresa;
use App\Models\ventafactura;
use App\Models\venta;
use App\Models\entidad;
use Codedge\Fpdf\Fpdf\Fpdf as baseFpdf;
use Fpdf;
use Exception;

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

class FPDF_Merge {
    // public $pathImg =  'C:\\xampp7.3\\htdocs\\apiosi\\public\\';
    public $pathImg =  '/home/centromedico/public_html/apiosi/public/';
    // public $pathImg =  '/home/ositest/public_html/apiosi/public/';
    
    const   TYPE_NULL       = 0,
            TYPE_TOKEN      = 1,
            TYPE_REFERENCE  = 2,
            TYPE_REFERENCE_F= 3,
            TYPE_NUMERIC    = 4,
            TYPE_HEX        = 5,
            TYPE_BOOL       = 6,
            TYPE_STRING     = 7,
            TYPE_ARRAY      = 8,
            TYPE_DICTIONARY = 9,
            TYPE_STREAM     = 10;
            
    private $buffer, $compress, $fonts, $objects, $pages, $ref, $n, $xref;
            
    /**************************************************
    /*                    CONSTRUCTOR
    /**************************************************/
    
    public function __construct(){
        $this->buffer   = '';
        $this->fonts    = array();
        $this->objects  = array();
        $this->pages    = array();
        $this->ref      = array();
        $this->xref     = array();
        $this->n        = 0;
        $this->compress = function_exists('gzcompress');
    }
            
    /**************************************************
    /*                      PRIVATE
    /**************************************************/
    
    
    private function error($msg){
        throw new Exception($msg);
        die;
    }
    
    //================================================
    // FONCTIONS D'IMPORT
    //================================================
    
    private function parse($buffer, &$len, &$off){
        if ($len === $off) {
            return null;
        }
        
        if (!preg_match('`\s*(.)`', $buffer, $m, PREG_OFFSET_CAPTURE, $off)) return null;
        $off = $m[1][1];
        
        switch($buffer[$off]){
            case '<':
                if ($buffer[$off+1] === '<'){
                    // dictionnary
                    $v = array();
                    $off+=2;
                    while(1){
                        $key = $this->parse($buffer, $len, $off);
                        if ($key === null) break;
                        if ($key[0] !== self::TYPE_TOKEN) break;
                        $value = $this->parse($buffer, $len, $off);
                        $v[$key[1]] = $value;
                    }
                    $off+=2;
                    return array(self::TYPE_DICTIONARY, $v);
                } else {
                    // hex
                    $p = strpos($buffer, '>', $off);
                    if ($p !== false){
                        $v = substr($buffer, $off+1, $p - $off - 1);
                        $off = $p + 1;
                        return array(self::TYPE_HEX, $v);
                    }
                }
            break;
            case '(':
                // string
                $p = $off;
                while(1){
                    $p++;
                    if ($p === $len) break;
                    if (($buffer[$p] === ')') && ($buffer[$p-1] !== '\\')) break;
                }
                if ($p < $len){
                    $v = substr($buffer, $off+1, $p - $off - 1);
                    $off = $p + 1;
                    return array(self::TYPE_STRING, $v);
                }
            break;
            case '[':
                $v = array();
                $off++; // jump the [
                while(1){
                    $value = $this->parse($buffer, $len, $off);
                    if ($value === null) break;
                    $v[] = $value;
                }
                $off++; // jump the ]
                return array(self::TYPE_ARRAY, $v);
            break;
            case '>': // dictionnary : end
            case ']': // array : end
                return null;
            break;
            case '%': // comments : jump
                $p = strpos($buffer, "\n", $off);
                if ($p !== false){
                    $off = $p + 1;
                    return $this->parse($buffer, $len, $off);
                }
                
            break;
            default:
                if (preg_match('`^\s*([0-9]+) 0 R`', substr($buffer, $off, 32), $m)){
                    $off += strlen($m[0]);
                    return array(self::TYPE_REFERENCE, $m[1]);
                } else {
                    $p = strcspn($buffer, " %[]<>()\r\n\t/", $off+1);
                    $v = substr($buffer, $off, $p+1);
                    $off += $p+1;
                    if ( is_numeric($v) ){
                      $type = self::TYPE_NUMERIC;
                    } else if ( ($v === 'true') || ($v === 'false') ){
                      $type = self::TYPE_BOOL;
                    } else if ( $v === 'null' ){
                      $type = self::TYPE_NULL;
                    } else {
                      $type = self::TYPE_TOKEN;
                    }
                    return array($type, $v);
                }
            break;
        }
        return null;
    }
    
    private function getObject($f, $xref, $index, $includeSubObject = false){
        
        $type = self::TYPE_TOKEN;
        
        if (!isset($xref[$index])){
            $this->error('reference d\'object inconnue');
        }
        
        fseek($f, $xref[$index]);
        
        $data   = '';
        $len    = 0;
        $offset = 0;
        $expLen = 1024;
        do{
            $prev = $len;
            $data .= fread($f, $expLen);
            $len = strlen($data);
            $p = strpos($data, "endobj", $offset);
            if ($p !== false){
                if ( $data[$p-1] !== "\n" ){
                    $offset = $p + 6;
                    $p = false;
                } else {
                    if ($len < $p + 8){
                        $data .= fread($f, 1);
                        $len = strlen($data);
                    }
                    if ($data[$p+6] !== "\n"){
                        $offset = $p + 6; // not the endobj markup, maybe a string content
                        $p = false;
                    }
                }
            }
            $expLen *= 2;
        }while( ($p === false) && ($prev !== $len) );
        
        if ($p === false){
            $this->error('object ['.$index.'] non trouve');
        }
        
        $p--;
        $data = substr($data,0, $p);
        
        if (!preg_match('`^([0-9]+ 0 obj)`', $data, $m, PREG_OFFSET_CAPTURE)){
            $this->error('object ['.$index.'] invalide');
        }
        
        $p = $m[0][1] + strlen($m[1][0]) + 1;
        $data = substr($data, $p);
        
        if (substr($data, 0, 2) === '<<') {
            $type = self::TYPE_DICTIONARY;
            $off = 0;
            $len = strlen($data);
            $dictionary = $this->parse($data, $len, $off);
            $off++;
            $data = substr($data, $off);
            if ($data === false) {
                $data = '';
            } else if (substr($data, 0, 7) === "stream\n"){
                $data = substr($data, 7, strlen($data) -  17);
                $type = self::TYPE_STREAM;
            }
            if ( $includeSubObject ){
                $dictionary = $this->_resolveValues($f, $xref, $dictionary);
            }
        } else {
            $dictionary = null;
        }
        return array($type, $dictionary, $data);
    }
    
    private function _resolveValues($f, $xref, $item){
        switch($item[0]){
            case self::TYPE_REFERENCE:
                $object = $this->getObject($f, $xref, $item[1], true);
                if ($object[0] === self::TYPE_TOKEN){
                    return array(self::TYPE_TOKEN, $object[2]);
                }
                $ref = $this->storeObject($object);
                return array(self::TYPE_REFERENCE_F, $this->_getObjectType($object), $ref);
            break;
            case self::TYPE_ARRAY:
            case self::TYPE_DICTIONARY:
                $r = array();
                foreach($item[1] as $key => $val){
                    if ( ($val[0] == self::TYPE_REFERENCE) || 
                         ($val[0] == self::TYPE_ARRAY) ||
                         ($val[0] == self::TYPE_DICTIONARY) ){
                        $r[$key] = $this->_resolveValues($f, $xref, $val);
                    } else {
                        $r[$key] = $val;
                    }
                }
                return array($item[0], $r);
            break;
            default: 
                return $item;
        }            
    }
    
    private function getResources($f, $xref, $page){
        if ($page[0] !== self::TYPE_DICTIONARY){
            $this->error('getResources necessite un dictionaire');
        }
        if (isset($page[1]['/Resources'])){
            if ($page[1]['/Resources'][0] === self::TYPE_REFERENCE){
                return $this->getObject($f, $xref, $page[1]['/Resources'][1]);
            } else {
                return array($page[1]['/Resources'][1]);
            }
        } else if (isset($page[1]['/Parent'])){
            return $this->getResources($f, $xref, $page[1]['/Parent']);
        }
        return null;
    }
    
    private function getContent($f, $xref, $page){
        if ($page[0] !== self::TYPE_DICTIONARY){
            $this->error('getContent necessite un dictionaire');
        }
        $stream = '';
        if (isset($page[1]['/Contents'])){
            $stream = $this->_getContent($f, $xref, $page[1]['/Contents']);
        }
        return $stream;
    }
    
    private function _getContent($f, $xref, $content){
        $stream = '';
        if ($content[0] === self::TYPE_REFERENCE){
            $stream .= $this->getStream($f, $xref, $this->getObject($f, $xref, $content[1]));
        } else if ($content[0] === self::TYPE_ARRAY){
            foreach($content[1] as $sub){
                $stream .= $this->_getContent($f, $xref, $sub);
            }
        } else {
            $stream .= $this->getStream($f, $xref, $item);
        }
        return $stream;
    }
    
    private function getCompression($f, $xref, $item){
        if ($item[0] === self::TYPE_TOKEN){
            return array($item[1]);
        } else if ($item[0] === self::TYPE_ARRAY){
            $r = array();
            foreach($item[1] as $sub){
                $r = array_merge($r, $this->getCompression($f, $xref, $sub));
            }
            return $r;
        } else if ($item[0] === self::TYPE_REFERENCE){
            return $this->getCompression($f, $xref, $this->getObject($f, $xref, $item[1]));
        }
        return array();
    }
    
    private function getStream($f, $xref, $item){
        $methods = isset($item[1][1]['/Filter']) ? $this->getCompression($f, $xref, $item[1][1]['/Filter']) : array();
        
        $raw = $item[2];
        foreach($methods as $method){
            switch ($method) {
                case '/FlateDecode':
                    if (function_exists('gzuncompress')) {
                        $raw = !empty($raw) ? @gzuncompress($raw) : '';
                    } else {
                        $this->error('gzuncompress necessaire pour decompresser ce stream');
                    }
                    if ($raw === false) {
                        $this->error('erreur de decompression du stream');
                    }
                break;
                default:
                    $this->error($method . ' necessaire pour decompresser ce stream');
            }
        }
        return $raw;
    }
    
    private function storeObject($item, $type = false){
        $md5 = md5(serialize($item));
        if ($type === '/Font'){
            $array  = & $this->fonts;
            $prefix = '/F';
        } else {
            $array = & $this->objects;
            $prefix = '/Obj';
        }
        if (!isset($array[$md5])){
            $index = count($array) + 1;
            $array[$md5] = array(
                'name'  => $prefix . $index,
                'item'  => $item,
                'type'  => $type,
                'index' => $index
            );
        } else if ($type){
            $array[$md5]['type'] = $type;
        }
        return $array[$md5][$type ? 'name' : 'index'];
    }
    
    //================================================
    // FONCTIONS D'IMPRESSION
    //================================================
    
    private function _out($raw){
        $this->buffer .= $raw . "\n";
    }
    
    private function _strval($value){
        $value+=0;
        if ($value){
            return strval($value);
        }
        return '0';
    }
    
    private function _toStream($item){
        switch($item[0]){
            case self::TYPE_NULL        : return 'null';
            case self::TYPE_TOKEN       : return $item[1];
            case self::TYPE_REFERENCE   : return $this->_strval($item[1]) . ' 0 R';
            case self::TYPE_REFERENCE_F :
                if (!isset($this->ref[ $item[1] ][ $item[2] ])){
                    $this->error('reference vers un object non sauve');
                }
                return $this->_strval($this->ref[ $item[1] ][ $item[2] ]) . ' 0 R';
            
            case self::TYPE_NUMERIC     : return $this->_strval($item[1]);
            case self::TYPE_HEX         : return '<'.strval($item[1]).'>';
            case self::TYPE_BOOL        : return $item[1] ? 'true' : 'false';
            case self::TYPE_STRING      : return '(' . str_replace(array('\\', '(', ')'), array('\\\\', '\\(', '\\)'), strval($item[1])) . ')';
            case self::TYPE_ARRAY       :
                $r = array(); 
                foreach($item[1] as $val){    
                    $r[] = $this->_toStream($val);
                }
                return '[' . implode(' ', $r) . ']';
            case self::TYPE_DICTIONARY  :
                $r = array();
                foreach($item[1] as $key => $val){
                    $val = $this->_toStream($val);
                    $r[] = $key . ' ' . $val;
                }
                return '<<' . implode("\n", $r) . '>>';
            break;
        }
        return '';
    }
    
    private function _newobj($n = null){
        if ( ($n === null) || ($n === true) ){
            $this->n++;
            $id = $this->n;
        } else {
            $id = $n;
        }
        if ($n !== true){
            $this->xref[ $id ] = strlen($this->buffer);
            $this->_out($id . ' 0 obj');
        }
        return $id;
    }
    
    private function _addObj($dico = null, $buf = null){
        $ref = $this->_newobj();
        $buf = empty($buf) && ($buf !== 0) && ($buf !== '0') ? null : $buf;
        if (is_array($dico)){
            if ($buf !== null){
                if ($this->compress && !isset($dico['/Filter'])) {
                    $buf = gzcompress($buf);
                    $dico['/Filter'] = array(self::TYPE_TOKEN, '/FlateDecode');
                }
                $dico['/Length'] = array(self::TYPE_NUMERIC, strlen($buf));
            }
            $this->_out($this->_toStream(array(self::TYPE_DICTIONARY, $dico)));
        }
        if ($buf !== null){
            $this->_out('stream');
            $this->_out($buf);
            $this->_out('endstream');
        }
        $this->_out('endobj');
        return $ref;
    }
    
    private function _getObjectType($object){
        return isset($object['type']) && !empty($object['type']) ? $object['type'] : 'default';
    }
    
    private function _putObject($object){
        $type = $this->_getObjectType($object);
        if (!isset($this->ref[$type])){
            $this->ref[$type] = array();
        }
        $this->ref[$type][ $object['index'] ] = $this->_addObj($object['item'][1][1], $object['item'][2]);
    }
    
    private function _putObjects(){
        foreach($this->objects as $object){
            if ($object['type']) continue;
            $this->_putObject($object);
        }
        foreach($this->objects as $object){
            if (!$object['type']) continue;
            $this->_putObject($object);
        }
        foreach($this->fonts as $object){
            $this->_putObject($object);
        }
    }
    
    
    private function _putResources(){
        $dico = array(
            '/ProcSet' => array(
                    self::TYPE_ARRAY, 
                    array(
                        array(self::TYPE_TOKEN, '/PDF'),
                        array(self::TYPE_TOKEN, '/Text'),
                        array(self::TYPE_TOKEN, '/ImageB'),
                        array(self::TYPE_TOKEN, '/ImageC'),
                        array(self::TYPE_TOKEN, '/ImageI')
                    )
                )
        );
        
        $xObjects = array();
        foreach($this->objects as $index => $object){
            if ($object['type'] === false){
                continue;
            }
            $value = array(
                self::TYPE_TOKEN,
                $this->_toStream(array(self::TYPE_REFERENCE, $this->ref[ $object['type'] ][ $object['index'] ]))
            );
            if ($object['type'] === '/XObject'){
                $xObjects[$object['name']] = $value;
            }
        }
        if (!empty($xObjects)){
            $dico['/XObject'] = array(self::TYPE_DICTIONARY, $xObjects);
        }
        
        $fonts = array();
        foreach($this->fonts as $index => $object){
            $value = array(
                self::TYPE_TOKEN,
                $this->_toStream(array(self::TYPE_REFERENCE, $this->ref[ '/Font' ][ $object['index'] ]))
            );
            $fonts[$object['name']] = $value;
        }
        if (!empty($fonts)){
            $dico['/Font'] = array(self::TYPE_DICTIONARY, $fonts);
        }
        return $this->_addObj($dico);
    }
    
    /**************************************************
    /*                      PUBLIC
    /**************************************************/
    
    public function add( $filename ){
        $f = @fopen($filename, 'rb');
        if (!$f) {
            $this->error('impossible d\'ouvrir le fichier');
        }
        fseek($f, 0, SEEK_END);
        $fileLength = ftell($f);
        
        // Localisation de xref
        //-------------------------------------------------
        
        fseek($f, -128, SEEK_END);
        $data = fread($f, 128);
        if ($data === false) {
            return $this->error('erreur de lecture dans le fichier');
        }
        $p = strripos($data, 'startxref');
        if ($p === false){
            return $this->error('startxref absent');
        }
        $startxref = substr($data, $p+10, strlen($data) - $p - 17);
        $posStartxref = $fileLength - 128 + $p;
        
        // extraction de xref + trailer
        //-------------------------------------------------
        
        fseek($f, $startxref);
        $data = fread($f, $posStartxref - $startxref);
        
        // extraction du trailer
        //-------------------------------------------------
        $p = stripos($data, 'trailer');
        if ($p === false){
            return $this->error('trailer absent');
        }
        $dataTrailer = substr($data, $p + 8);
        $len = strlen($dataTrailer);
        $off = 0;
        $trailer = $this->parse($dataTrailer, $len, $off);
        
        // extraction du xref
        //-------------------------------------------------
        
        $data = explode("\n", trim(substr($data, 0, $p)));
        array_shift($data); // "xref"
        
        $cnt = 0;
        $xref = array();
        
        foreach($data as $line){
            if (!$cnt) {
                if (preg_match('`^([0-9]+) ([0-9]+)$`', $line, $m)){
                    $index = intval($m[1]) - 1;
                    $cnt = intval($m[2]);
                } else {
                    $this->error('erreur dans xref');
                }
            } else {
                $index++;
                $cnt--;
                if (preg_match('`^([0-9]{10}) [0-9]{5} ([n|f])`', $line, $m)){
                    if ($m[2] === 'f') {
                        continue;
                    }
                    $xref[ $index ] = $m[1];
                } else {
                    $this->error('erreur dans xref : ' . $line);
                }
            }
        }
        
        // Lecture des pages
        //-------------------------------------------------

        $root = $this->getObject($f, $xref, $trailer[1]['/Root'][1]);
        $root = $root[1][1];
        
        $pages = $this->getObject($f, $xref, $root['/Pages'][1]);
        $pages = $pages[1][1];
        
        foreach($pages['/Kids'][1] as $kid){
            $kid = $this->getObject($f, $xref, $kid[1]);
            $kid = $kid[1];
            
            $resources = $this->getResources($f, $xref, $kid);
            $resources = $resources[1][1];
            
            $content = $this->getContent($f, $xref, $kid);
            
            // traitement des fonts
            //-------------------------------------------------
            $newFonts = array();
            if (isset($resources['/Font']) && !empty($resources['/Font'])){
                if (preg_match_all("`(/F[0-9]+)\s+-?[0-9\.]+\s+Tf`", $content, $matches, PREG_OFFSET_CAPTURE)){
                    $newContent = '';
                    $offset     = 0;
                    $cnt = count($matches[0]);
                    for($i=0; $i<$cnt; $i++){
                        $position = $matches[0][$i][1];
                        $name     = $matches[1][$i][0];
                        if (!isset($newFonts[$name])){
                            $object = $this->getObject($f, $xref, $resources['/Font'][1][$name][1], true);
                            $newFonts[$name] = $this->storeObject($object, '/Font');
                        }
                        if ($newFonts[$name] !== $name){
                            $newContent .= substr($content, $offset, $position - $offset);
                            $newContent .= $newFonts[$name];
                            $offset = $position + strlen($name);
                        }
                    }
                    $content = $newContent . substr($content, $offset);
                }
            }
            
            // traitement des XObjets
            //-------------------------------------------------
            $newXObjects = array();
            if (isset($resources['/XObject']) && !empty($resources['/XObject'])){
                if (preg_match_all("`(/[^%\[\]<>\(\)\r\n\t/]+) Do`", $content, $matches, PREG_OFFSET_CAPTURE)){
                    $newContent = '';
                    $offset     = 0;
                    foreach($matches[1] as $m){
                        $name = $m[0];
                        $position = $m[1];
                        if (!isset($newXObjects[$name])){
                            $object = $this->getObject($f, $xref, $resources['/XObject'][1][$name][1], true);
                            $newXObjects[$name] = $this->storeObject($object, '/XObject');
                        }
                        if ($newXObjects[$name] !== $name){
                            $newContent .= substr($content, $offset, $position - $offset);
                            $newContent .= $newXObjects[$name];
                            $offset = $position + strlen($name);
                        }
                    }
                    $content = $newContent . substr($content, $offset);
                }
            }

            $mediaBox = isset($kid[1]['/MediaBox']) ? $kid[1]['/MediaBox'] : (isset($pages['/MediaBox']) ? $pages['/MediaBox'] : null);
        
            if ($mediaBox[0] !== self::TYPE_ARRAY){
                $this->error('MediaBox non definie');
            }
            
            
            $this->pages[] = array(
                'content'   => $content,
                '/XObject'  => array_values($newXObjects),
                '/Font'     => array_values($newFonts),
                '/MediaBox' => $mediaBox
            );
        }
        fclose($f);
    }
    
    public function output($filename = null){
        $this->_out('%PDF-1.6');
        
        $this->_putObjects();
        
        $rsRef = $this->_putResources();
        
        $ptRef = $this->_newobj(true);
        
        $kids = array();
        
        // Ajout des pages 
        $n = count($this->pages);
        for($i=0; $i<$n; $i++){
            $ctRef = $this->_addObj(array(), $this->pages[$i]['content']);
            $dico = array(
                '/Type'     => array(self::TYPE_TOKEN, '/Page'),
                '/Parent'   => array(self::TYPE_REFERENCE, $ptRef),
                '/MediaBox' => $this->pages[$i]['/MediaBox'],
                '/Resources'=> array(self::TYPE_REFERENCE, $rsRef),
                '/Contents' => array(self::TYPE_REFERENCE, $ctRef),
            );
            $kids[] = array(self::TYPE_REFERENCE, $this->_addObj($dico));
        }
        
        // Ajout du page tree
        $ptDico = array(
            self::TYPE_DICTIONARY,
            array(
                '/Type'     => array(self::TYPE_TOKEN, '/Pages'),
                '/Kids'     => array(self::TYPE_ARRAY, $kids),
                '/Count'    => array(self::TYPE_NUMERIC, count($kids))
            )
        );
        
        $this->_newobj($ptRef);
        $this->_out($this->_toStream($ptDico));
        $this->_out('endobj');
        
        // Ajout du catalogue
        $ctDico = array(
            self::TYPE_DICTIONARY,
            array(
                '/Type' => array(self::TYPE_TOKEN, '/Calalog'),
                '/Pages'=> array(self::TYPE_REFERENCE, $ptRef)
                )
        );
        $ctRef = $this->_newobj();
        $this->_out($this->_toStream($ctDico));
        $this->_out('endobj');
        
        // Ajout du xref
        $xrefOffset = strlen($this->buffer);
        $count = count($this->xref);
        $this->_out('xref');
        $this->_out('0 ' . ($count+1));
        $this->_out('0000000000 65535 f ');
        for($i=0; $i<$count; $i++){
            $this->_out(sprintf('%010d 00000 n ',$this->xref[$i+1]));
        }
        
        // Ajout du trailer
        $dico = array(
            '/Size' => array(self::TYPE_NUMERIC, 1+count($this->xref)),
            '/Root' => array(self::TYPE_REFERENCE, $ctRef)
        );
        $this->_out('trailer');
        $this->_out($this->_toStream(array(self::TYPE_DICTIONARY, $dico)));
        
        
        // Ajout du startxref
        $this->_out('startxref');
        $this->_out($xrefOffset);
        $this->_out('%%EOF');
        
        if ($filename === null){
            header('Content-Type: application/pdf');
            header('Content-Length: '.strlen($this->buffer));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            ini_set('zlib.output_compression','0');
            
            echo $this->buffer;
            die;
        } else {
            file_put_contents($filename, $this->buffer);
        }
    }
}

class invoicemasivoController extends Controller 
{     
    
    public function reporte(Request $request, $enterprise)
    {    
        // Fpdf::AddPage();
        // Fpdf::SetFont('Courier', 'B', 18);
        // Fpdf::Cell(50, 25, 'Hello World!');
        // Fpdf::Output();
        // exit;
        $merge = new FPDF_Merge();
        $request = $request->all(); 

        if (empty($request['desde']) || empty($request['hasta'])) {
            return $this->crearRespuesta('Debe especificar rando de fechas', [200, 'info']);
        }

        if (empty($request['idafiliado']) ) {
            return $this->crearRespuesta('Debe especificar afiliado', [200, 'info']);
        }

        if (empty($request['iddocumentofiscal'])) {
            return $this->crearRespuesta('Debe especificar comprobante', [200, 'info']);
        }

        if (empty($request['serie'])) {
            return $this->crearRespuesta('Debe especificar serie', [200, 'info']);
        }

        $param = array(
            'venta.idafiliado' => $request['idafiliado'],
            'venta.iddocumentofiscal' => $request['iddocumentofiscal'],
            'venta.serie' => $request['serie'],
        );

        $request['desde'] = $this->formatFecha($request['desde'], 'yyyy-mm-dd');
        $request['hasta'] = $this->formatFecha($request['hasta'], 'yyyy-mm-dd');
        $betweendate = [$request['desde'], $request['hasta']];
        // dd($betweendate, $param);
        $data = \DB::table('venta')
                ->join('entidad as afiliado', 'venta.idafiliado', '=', 'afiliado.identidad')
                ->join('documentofiscal', 'venta.iddocumentofiscal', '=', 'documentofiscal.iddocumentofiscal')
                ->leftJoin('cicloatencion', 'venta.idcicloatencion', '=', 'cicloatencion.idcicloatencion')
                ->select('afiliado.numerodoc', 'documentofiscal.codigosunat', 'venta.serie', 'venta.serienumero', 'venta.idventa', 'venta.idcicloatencion', 'cicloatencion.pdfs')
                ->where($param)
                ->whereBetween('venta.fechaventa', $betweendate)
                ->whereNull('venta.deleted')
                ->orderBy('venta.serienumero', 'asc')
                ->get()->all();

        $noExiste = false; 
        // dd($data);
        $file = '';
        $idventa = '';
        foreach ($data as $value) {  
            $nombreFile = $merge->pathImg . 'comprobantes' . DIRECTORY_SEPARATOR . $value->numerodoc .'-'. $value->codigosunat .'-'. $value->serie .'-'. $value->serienumero .'.pdf';

            $file = $value->numerodoc .'-'. $value->codigosunat .'-'. $value->serie .'-'. $value->serienumero .'.pdf';
            $idventa = $value->idventa;

            if (!file_exists($nombreFile)) {
                $noExiste = true;
                break;
            }  else {
                $merge->add($nombreFile);
            }
            // dd($value->idcicloatencion);
            if (!empty($value->pdfs) && ($request['anadirha'] === '1' || $request['anadirau'] === '1')) {
                $archivos = explode(",", $value->pdfs);
                foreach ($archivos as $archivo) { 
                    // dd( $archivo);
                    $nombreFile = $merge->pathImg . 'atenciones' . DIRECTORY_SEPARATOR . $archivo;
                    // dd($nombreFile);
                    if ($request['anadirha'] === '1' && substr($archivo, 0, 2) === 'HA'){
                        $merge->add($nombreFile);
                    }

                    if ($request['anadirau'] === '1' && substr($archivo, 0, 2) === 'AU'){
                        $merge->add($nombreFile);
                    }
                } 
            }
        }  

        if ($noExiste) {
            return $this->crearRespuesta('Archivo '.$file.' para #'.$idventa.' no existe. Comunicarse con administrador.', [200, 'info']);
        }

        $merge->output();
    }    
}
