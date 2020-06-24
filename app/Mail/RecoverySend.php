<?php

namespace App\Mail;

use App\Models\venta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecoverySend extends Mailable
{
    use Queueable, SerializesModels;

    public $entidad;
    public $imgHeader;
    public $urlPortal;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($entidad, $urlPortal)
    {
        $this->entidad = $entidad;
        $this->imgHeader = "https://sistemas.centromedicoosi.com/apiosi/public/img/osi/logologin.png";
        
        if ($urlPortal === 'reservatuconsulta') {
            $this->urlPortal = 'https://reservatuconsulta.centromedicoosi.com';
        } else {
            $this->urlPortal = 'https://pacientes.centromedicoosi.com';
        }
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('invoice.recovery')
            ->subject('Recuperación de contraseña'); 
    }
}
