<?php

namespace App\Mail;

use App\Models\venta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class InvoiceSend extends Mailable
{
    use Queueable, SerializesModels;

    public $venta;
    public $filePDF;
    public $fileXML;
    public $imgHeader;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($venta, $filePDF, $fileXML)
    {
        $this->venta = $venta;
        $this->filePDF = $filePDF;
        $this->fileXML = $fileXML;

        if (in_array($venta->idafiliado, [87, 239, 240, 244, 245, 256, 259, 261, 262, 263, 4844, 31058])) { 
            $this->imgHeader = "https://sistemas.centromedicoosi.com/img/osi/email/mailheadosi.png";
        } 

        if (in_array($venta->idafiliado, [25425, 29508])) { 
            $this->imgHeader = "https://sistemas.centromedicoosi.com/img/osi/email/mailheadunion.png";
        }
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('invoice.email')
            ->subject('Envío de Comprobante Electrónico: ' . $this->venta->serie . '-' . $this->venta->serienumero)
            ->attach($this->filePDF)
            ->attach($this->fileXML); 
        // return $this->view('invoice.texto')
        //     ->subject('Mensaje de prueba');
    }
}
