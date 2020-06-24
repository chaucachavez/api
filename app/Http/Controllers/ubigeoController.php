<?php

namespace App\Http\Controllers;

use \Firebase\JWT\JWT;
use Illuminate\Http\Request;

use App\Models\empresa;

class ubigeoController extends Controller {

    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();
        $empresa = new empresa();

        $data = [];
        switch (count($paramsTMP)) {
            case 0://Paises y nacionalidades
                $data = $empresa->paises();
                break;
            case 1://Departamentos
                if (isset($paramsTMP['pais'])) {
                    $data = $empresa->departamentos($paramsTMP['pais']);
                }
                break;
            case 2://Provincias
                if (isset($paramsTMP['pais']) && isset($paramsTMP['dpto'])) {
                    $data = $empresa->provincias($paramsTMP['pais'], $paramsTMP['dpto']);
                }
                break;
            case 3://Distritos          
                if (isset($paramsTMP['pais']) && isset($paramsTMP['dpto']) && isset($paramsTMP['prov'])) {
                    $data = $empresa->distritos($paramsTMP['pais'], $paramsTMP['dpto'], $paramsTMP['prov']);
                }
                break;
            default:
                break;
        }

        if ($data) {
            return $this->crearRespuesta($data, 200);
        }

        return $this->crearRespuestaError('Ubigeo no encontrado', 404);
    }

}
