<?php

namespace App\Mail;

use App\Models\venta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class BienvenidoSend extends Mailable
{
    use Queueable, SerializesModels;

    public $entidad;
    public $imgHeader;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($entidad)
    {
        $this->entidad = $entidad;

        // if (in_array($venta->idafiliado, [87, 239, 240, 244, 245, 256, 259, 261, 262, 263, 4844, 31058])) { 
            $this->imgHeader = "https://sistemas.centromedicoosi.com/apiosi/public/img/osi/logologin.png";
        // } 

        // if (in_array($venta->idafiliado, [25425, 29508])) { 
            // $this->imgHeader = "https://sistemas.centromedicoosi.com/img/osi/email/mailheadunion.png";
        // }
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('invoice.bienvenido')
            ->subject('Bienvenido a citas en linea'); 
        // return $this->view('invoice.texto')
        //     ->subject('Mensaje de prueba');
    }
}
