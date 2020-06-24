<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\empresa;
use App\Models\perfil;
use App\Models\entidad;

class perfilController extends Controller {
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }
    
    public function index(Request $request, $enterprise) {

        $paramsTMP = $request->all();

        $empresa = new empresa();
        $perfil = new perfil();
        $param = array();

        $idempresa = $empresa->idempresa($enterprise);
        $param['perfil.idempresa'] = $idempresa;

        $orderName = !empty($paramsTMP['orderName']) ? $paramsTMP['orderName'] : 'perfil.nombre';
        $orderSort = !empty($paramsTMP['orderSort']) ? $paramsTMP['orderSort'] : 'ASC';
        $pageSize = !empty($paramsTMP['pageSize']) ? $paramsTMP['pageSize'] : 25;

        $like = !empty($paramsTMP['likenombre']) ? trim($paramsTMP['likenombre']) : '';
        $data = $perfil->grid($param, $like, $pageSize, $orderName, $orderSort);

        if ($data) {
            return $this->crearRespuesta($data->items(), 200, $data->total(),'',$this->objTtoken->mysede);
        }

        return $this->crearRespuestaError('Perfil no encontrada', 404);
    }

    public function store(Request $request, $enterprise) {

        $empresa = new empresa();

        $idempresa = $empresa->idempresa($enterprise);
        $request = $request->all();
        $request['activo'] = 1;
        $request['idempresa'] = $idempresa;
        $request['idsuperperfil'] = 2; //Perfil creado por las empresas clientes

        $obj = perfil::create($request);

        return $this->crearRespuesta('El perfil ' . $obj->nombre . ' ha sido creado.', 201);
    }

    public function update(Request $request, $enterprise, $id) {

        $perfil = perfil::find($id);

        if ($perfil) {
            $request = $request->all();

            $perfil->fill($request);
            $perfil->save();

            return $this->crearRespuesta('El perfil ' . $perfil->nombre . ' ha sido editado. ', 200, '', '', $request);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un perfil', 404);
    }

    public function destroy($enterprise, $id) {

        $perfil = perfil::find($id);

        if ($perfil) {
            $perfilmodulo = $perfil->listaPerfilModulo(['idperfil' => $id]);

            if (empty($perfilmodulo)) {
                $entidadperfil = $perfil->listaEntidadPerfil(['idperfil' => $id]);

                if (empty($entidadperfil)) {
                    $perfil->delete();
                    return $this->crearRespuesta('El perfil ' . $perfil->nombre . ' a sido eliminado', 200);
                } else {
                    return $this->crearRespuesta('El perfil "' . $perfil->nombre . '" no puede ser eliminado. Tiene usuarios asociados.', [200, 'info']);
                }
            } else {
                return $this->crearRespuesta('El perfil "' . $perfil->nombre . '" no puede ser eliminado. Tiene módulos asociados.', [200, 'info']);
            }
        }

        return $this->crearRespuestaError('Perfil no encotrado', 404);
    }

    public function show($enterprise, $id) {

        $empresa = new empresa();
        $entidad = new entidad();

        $perfil = perfil::find($id);

        $idempresa = $empresa->idempresa($enterprise);
        $listcombox = array(
            'modulos' => $entidad->ListaModules(['empresa.idempresa' => $idempresa], true),
            'perfilmodulo' => $perfil->listaPerfilModulo(['idperfil' => $id]),
        );

        if ($perfil) {
            return $this->crearRespuesta($perfil, 200, '', '', $listcombox);
        }
        
        return $this->crearRespuestaError('Entidad no encotrado', 404);
    }

    public function update_perfilmodulo(Request $request, $enterprise, $id) {

        $empresa = new empresa();
        $entidad = new entidad();

        $perfil = perfil::find($id);
        $idempresa = $empresa->idempresa($enterprise);


        $perfil = perfil::find($id); 
        $perfil->optinforme = $request['perfil']['optinforme'];
        $perfil->save(); 

        if ($perfil) {
            $modulos = [];
            if (!empty($request['perfilmodulo'])) {
                $data = [];
                $modulos = $entidad->ListaModules(['empresa.idempresa' => $idempresa], true)[$enterprise]['modules'];
                foreach ($modulos as $modulo) {
                    if (!empty($modulo['menus'])) {
                        foreach ($modulo['menus'] as $menu) {

                            //Entidad Modulo del Request                                    
                            foreach ($request['perfilmodulo'] as $row) {
                                if ($row['idmodulo'] == $menu['id']) {
                                    $data[$modulo['id']] = ['idperfil' => $id, 'idmodulo' => $modulo['id']];
                                    $data[$menu['id']] = ['idperfil' => $id, 'idmodulo' => $menu['id']];
                                }
                            }

                            if (!empty($menu['options'])) {
                                foreach ($menu['options'] as $option) {
                                    //Entidad Modulo del Request                                    
                                    foreach ($request['perfilmodulo'] as $row) {
                                        if ($row['idmodulo'] == $option['id']) {
                                            $data[$modulo['id']] = ['idperfil' => $id, 'idmodulo' => $modulo['id']];
                                            $data[$menu['id']] = ['idperfil' => $id, 'idmodulo' => $menu['id']];
                                            $data[$row['idmodulo']] = ['idperfil' => $id, 'idmodulo' => $row['idmodulo']];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $perfil->GrabarTransaccionPerfilModulo($data, $id);
            } else {
                $perfil->GrabarTransaccionPerfilModulo([], $id);
            }

            return $this->crearRespuesta('Las opciones del perfil ' . $perfil->nombres . ' a sido actualizado. ', 200);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a un perfil', 404);
    }

}
