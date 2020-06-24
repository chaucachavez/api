<?php

namespace App\Mail;

use App\Models\venta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ConfirmacionSend extends Mailable
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
        $this->imgHeader = "https://sistemas.centromedicoosi.com/apiosi/public/img/osi/logologin.png";
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('invoice.confirmation')
            ->subject('Confirmar registro de paciente');
    }
}
