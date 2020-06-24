<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\empresa;
use App\Models\perfil;
use App\Models\modulo;
use \Firebase\JWT\JWT;

class moduloController extends Controller {

    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $modulo = new modulo();

        $idempresa = $empresa->idempresa($enterprise);
        $data = $modulo->ListaModules(['empresa.idempresa' => $idempresa]);

        if ($data) {
            return $this->crearRespuesta($data, 200);
        }

        return $this->crearRespuestaError('Modulos no encontrados', 404);
    }

    public function store(Request $request, $enterprise) {

        $empresa = new empresa();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();

        \DB::beginTransaction();
        try {
            $modulo = modulo::create($request);

            $param = array(
                'idmodulo' => $modulo->idmodulo,
                'idempresa' => $idempresa
            );
            $modulo->insertarModuloEmpresa($param);
        } catch (QueryException $e) {
            \DB::rollback();
        }
        \DB::commit();

        $nombre = 'El m&oacute;dulo';
        if ($modulo->nivel === 2) {
            $nombre = 'El men&uacute;';
        }
        if ($modulo->nivel === 3) {
            $nombre = 'La opción';
        }

        return $this->crearRespuesta($nombre . ' "' . $modulo->nombre . '" ha sido creado.', 201);
    }

    public function update(Request $request, $enterprise, $id) {

        $modulo = modulo::find($id);

        if ($modulo) {
            $nombre = 'El m&oacute;dulo';
            if ($modulo->nivel === 2) {
                $nombre = 'El men&uacute;';
            }
            if ($modulo->nivel === 3) {
                $nombre = 'La opción';
            }

            $request = $request->all();
            $modulo->fill($request);
            $modulo->save();

            return $this->crearRespuesta($nombre . ' "' . $modulo->nombre . '" ha sido editado. ', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un m&oacute;dulo', 404);
    }

    public function destroy($enterprise, $id) {

        $empresa = new empresa();
        $perfil = new perfil();
        $modulo = modulo::find($id);

        if ($modulo) {
            $idempresa = $empresa->idempresa($enterprise);

            // VALIDACIONES
            // 1ERA VALIDACION
            $params['idempresa'] = $idempresa;
            $id = (int) $id;
            $data = $modulo->grid($params);
            $rows = [];
            foreach ($data as $row) {
                $rows[] = (array) $row;
            }

            $data = $this->procesarRaiz($rows, ['ID' => 'idmodulo', 'PARENT' => 'parent', 'CHILDREN' => 'children'], $id, true);
            $count = count($data);
            if ($count > 1) {
                $desc = ($modulo->nivel == 1) ? 'menús.' : 'opciones.';
                return $this->crearRespuesta('"' . $modulo->nombre . '" no puede ser eliminado. Tiene ' . $desc, [200, 'info']);
            }
            //FIN 1ERA VALIDACION

            $nombre = 'El m&oacute;dulo';
            if ($modulo->nivel === 2) {
                $nombre = 'El men&uacute;';
            }
            if ($modulo->nivel === 3) {
                $nombre = 'La opción';
            }

            \DB::beginTransaction();
            try {

                $perfilmodulo = $perfil->listaPerfilModulo(['modulo.idmodulo' => $id]);

                if (empty($perfilmodulo)) {
                    $param = array(
                        'idmodulo' => $modulo->idmodulo,
                        'idempresa' => $idempresa
                    );

                    $modulo->eliminarModuloEmpresa($param);
                    $modulo->delete();
                } else {
                    return $this->crearRespuesta($nombre . ' "' . $modulo->nombre . '" no puede ser eliminado. Está asignado a perfiles.', [200, 'info']);
                }
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta($nombre . ' "' . $modulo->nombre . '" a sido eliminado', 200);
        }

        return $this->crearRespuestaError('m&oacute;dulo no encotrado', 404);
    }

}
