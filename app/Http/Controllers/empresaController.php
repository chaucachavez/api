<?php

namespace App\Http\Controllers;

use App\Models\sede;
use App\Models\empresa;
use App\Models\entidad;
use Illuminate\Http\Request;
use Image;

class empresaController extends Controller
{

    // public $pathImg =  'C:\\xampp7.3\\htdocs\\apiosi\\public\\img\\';
    public $pathImg = '/home/centromedico/public_html/apiosi/public/img/';
    // public $pathImg =  '/home/ositest/public_html/apiosi/public/img/';

    public function home($enterprise, Request $request)
    {

        $objenterprise = new empresa();
        $sede = new sede();

        $row = $objenterprise->empresa(['url' => $enterprise]);
        $idempresa = $row->idempresa;

        if ($row) {
            unset($row->idempresa);
            $sedes = [];
            if (isset($request['sedes']) && $request['sedes']) {
                $sedes = $sede->sedes($idempresa, ['sede.idsede', 'sede.nombre', 'sede.principal', 'sede.sedeabrev']);
            }
            return $this->crearRespuesta($row, 200, '', '', $sedes);
        }

        return $this->crearRespuestaError('Empresa no encotrado', 404);
    }

    public function show($enterprise)
    {

        $sede = new sede();

        $empresa = empresa::where('url', '=', $enterprise)->first();

        $idempresa = $empresa->idempresa;
        $listcombox = array(
            'sedes' => $sede->sedes($idempresa),
            'personal' => entidad::select('identidad', 'entidad')->where(['tipopersonal' => '1', 'idempresa' => $idempresa])->whereNull('entidad.deleted')->get()->all(),
            'horasi' => $empresa->horas('00:00:00', '23:45:00', 15, 0),
            'horasf' => $empresa->horas('00:14:00', '23:59:00', 15, 14),
            'diaferiado' => $empresa->diasferiados(['idempresa' => $idempresa]),
            'diaxhora' => $empresa->diasporhoras(['diaxhora.idempresa' => $idempresa]),
            'aseguradoras' => $empresa->aseguradoras($idempresa),
            'seguros' => $empresa->aseguradoras($idempresa),
        );

        $ubigeo = $empresa->idubigeo;
        if (!empty($ubigeo)) {
            $pais = substr($ubigeo, 0, 2);
            $dpto = substr($ubigeo, 2, 3);
            $prov = substr($ubigeo, 5, 2);
            $dist = substr($ubigeo, 7, 2);
            $listcombox['paises'] = $empresa->paises();
            $listcombox['departamentos'] = $empresa->departamentos($pais);
            $listcombox['provincias'] = $empresa->provincias($pais, $dpto);
            $listcombox['distritos'] = $empresa->distritos($pais, $dpto, $prov);
            $empresa->pais = $pais;
            $empresa->dpto = $dpto;
            $empresa->prov = $prov;
            $empresa->dist = $dist;
        } else {
            $listcombox['paises'] = $empresa->paises();
        }
        //dd($listcombox);

        return $this->crearRespuesta($empresa, 200, '', '', $listcombox);
    }

    public function update(Request $request, $enterprise)
    {

        $empresa = empresa::where('url', '=', $enterprise)->first();
        $sede = new sede();

        $idempresa = $empresa->idempresa;
        $request = $request->all();

        $this->pathImg .= $empresa->url . DIRECTORY_SEPARATOR;

        if ($empresa) {
            if (isset($request['empresa']['idubigeo'])) {
                $request['empresa']['idubigeo'] = null;
                if (!empty($request['empresa']['pais'])) {
                    $dpto = empty($request['empresa']['dpto']) ? '00' : $request['empresa']['dpto'];
                    $prov = empty($request['empresa']['prov']) ? '00' : $request['empresa']['prov'];
                    $dist = empty($request['empresa']['dist']) ? '00' : $request['empresa']['dist'];
                    $request['empresa']['idubigeo'] = $request['empresa']['pais'] . $dpto . $prov . $dist;
                }
            }

            $eliminado = false;
            if (isset($request['empresa']['imglogosistema'])) {
                if (empty($request['empresa']['imglogosistema'])) {
                    if (unlink($this->pathImg . $empresa->imglogosistema)) {
                        $eliminado = true;
                    }
                }
            }

            if (isset($request['empresa']['imglogologin'])) {
                if (empty($request['empresa']['imglogologin'])) {
                    if (unlink($this->pathImg . $empresa->imglogologin)) {
                        $eliminado = true;
                    }
                }
            }

            $dataDiaferiado = [];
            if (isset($request['diaferiado'])) {
                foreach ($request['diaferiado'] as $row) {
                    $dataDiaferiado[] = ['idempresa' => $idempresa, 'fecha' => $this->formatFecha($row['fecha'], 'yyyy-mm-dd')];
                }
            }

            $dataDiaxhora = [];
            if (isset($request['diaxhora'])) {
                foreach ($request['diaxhora'] as $row) {
                    $dataDiaxhora[] = [
                        'idempresa' => $idempresa,
                        'idsede' => $row['idsede'],
                        'fecha' => $this->formatFecha($row['fecha'], 'yyyy-mm-dd'),
                        'inicio' => $row['inicio'],
                        'fin' => $row['fin'],
                    ];
                }
            }

            \DB::beginTransaction();
            try {
                if (isset($request['diaferiado'])) {
                    $sede->GrabarDiaferiado($dataDiaferiado, $idempresa);
                }

                if (isset($request['diaxhora'])) {
                    $sede->GrabarDiaxhora($dataDiaxhora, $idempresa);
                }

                if (isset($request['seguro'])) {
                    foreach ($request['seguro'] as $row) {
                        \DB::table('aseguradora')->where('idaseguradora', $row['idaseguradora'])->update(array('nroagenda' => $row['nroagenda']));
                    }
                }

                $empresa->fill($request['empresa']);
                $empresa->save();
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('"' . $empresa->razonsocial . '" ha sido editado.', 200, '', '', $eliminado);
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una empresa.', 404);
    }

    public function upload($enterprise, Request $request)
    {

        $empresa = empresa::where('url', '=', $enterprise)->first();
        $request = $request->all();

        $this->pathImg .= $empresa->url . DIRECTORY_SEPARATOR;

        if ($empresa) {
            if (!empty($_FILES)) {

                $name = $_FILES['file']['name'];
                $name = time() . '_' . $name;
                $tempPath = $_FILES['file']['tmp_name'];
                $Path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $name;
                $uploadPath = $this->pathImg . $name;
                $Path = time();
                if (move_uploaded_file($tempPath, $uploadPath)) {
                    $empresa->fill(array($request['field'] => $name));
                    $empresa->save();

                    return $this->crearRespuesta($name, 200, '', '', $Path);
                }
            }
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una empresa.', 404);
    }

    public function uploadtmp($enterprise, Request $request)
    {

        $empresa = empresa::where('url', '=', $enterprise)->first();

        if (isset($request['directorio']) && !empty($request['directorio'])) {
            // $this->pathImg = 'C:\\xampp7.3\\htdocs\\apiosi\\public\\' . $request['directorio'];
            $this->pathImg = '/home/centromedico/public_html/apiosi/public/' . $request['directorio'];
            // $this->pathImg = '/home/ositest/public_html/apiosi/public/' . $request['directorio'];

            if ($request['directorio'] === 'img_autorizaciones') { 

                if (!in_array($_FILES['file']['type'], ['image/png', 'image/jpeg'])) {
                    // \Log::info(print_r($_FILES['file']['type'], true)); 
                    return $this->crearRespuesta('Tipo de archivo erroneo. Solo suba imagen.', [200, 'info']);
                }
            }
        } else {
            $this->pathImg .= $empresa->url . DIRECTORY_SEPARATOR;
        }

        if ($empresa) {
            if (!empty($_FILES)) {
                $file = $_FILES['file']['name'];
                $name = $_FILES['file']['name'];
                $name = str_replace(' ', '', $name);
                $name = time() . '_' . $name;
                $tempPath = $_FILES['file']['tmp_name'];
                $uploadPath = $this->pathImg . DIRECTORY_SEPARATOR . $name;  

                if (isset($request['directorio']) && !empty($request['directorio'])) {

                    $path = $request->file('file')->storeAs('/' . $request['directorio'], $name, 'siteds'); 

                    $fileName = collect(explode('/', $path))->last();
                    // \Log::info(print_r($fileName, true)); 

                    $image = Image::make(\Storage::disk('siteds')->get($path));

                    $image->resize(1024, null, function ($constraint) {
                      $constraint->aspectRatio();
                      $constraint->upsize();
                    });

                    \Storage::disk('siteds')->put($path, (string) $image->encode()); 

                    if ($path) {
                        return $this->crearRespuesta(array('name' => $name, 'file' => $file), 200);
                    }
                } else {
                    if (move_uploaded_file($tempPath, $uploadPath)) {
                        return $this->crearRespuesta(array('name' => $name, 'file' => $file), 200);
                    }
                }
            }
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una empresa.', 404);
    }

    public function uploadtmpFile($enterprise, Request $request)
    {

        $empresa = empresa::where('url', '=', $enterprise)->first();

        // $this->pathImg = 'C:\\xampp7.3\\htdocs\\apiosi\\public\\' . $request['directorio'];
        $this->pathImg = '/home/centromedico/public_html/apiosi/public/' . $request['directorio'];
        // $this->pathImg = '/home/ositest/public_html/apiosi/public/' . $request['directorio'];

        if ($empresa) {
            if (!empty($_FILES)) {

                $file = $_FILES['file']['name'];
                $name = $_FILES['file']['name'];
                $name = str_replace(' ', '', $name);
                $name = time() . '_' . $name;
                $tempPath = $_FILES['file']['tmp_name'];                
                $uploadPath = $this->pathImg . DIRECTORY_SEPARATOR . $name;
              
                if (move_uploaded_file($tempPath, $uploadPath)) {
                    return $this->crearRespuesta(array('name' => $name, 'file' => $file), 200);
                }                
            }
        }

        return $this->crearRespuestaError('El id especificado no corresponde a una empresa.', 404);
    }
}
