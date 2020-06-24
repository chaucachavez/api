<?php

namespace App\Http\Controllers;

use \Firebase\JWT\JWT;
use Illuminate\Http\Request;

use App\Models\empresa;
use App\Models\producto;
use App\Models\arbol;

class culqiController extends Controller {
    
    public function generarCargo(Request $request, $enterprise) {

        $empresa = new empresa();

        $idempresa = $empresa->idempresa($enterprise);

        $request = $request->all();  

        return $this->crearRespuesta('Respondiendo con token: ' . $request['id'], 201);
    }
}
