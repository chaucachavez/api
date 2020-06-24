<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class perfil extends Model {

    protected $table = 'perfil';
    protected $primaryKey = 'idperfil';
    public $timestamps = false;
    protected $fillable = [
        'idsuperperfil',
        'idempresa',
        'nombre',
        'descripcion',
        'nuevo',
        'editar',
        'eliminar',
        'optinforme',
        'activo'        
    ];
    
    protected $hidden = ['idempresa'];

    public function grid($param, $likename, $items = 25, $orderName, $orderSort) {
        $select = \DB::table('perfil')
                ->select('idperfil', 'nombre', 'descripcion','nuevo', 'editar', 'eliminar', 'activo')
                ->where($param);
        if (!empty($likename)) {
            $select->where('perfil.nombre', 'like', '%' . $likename . '%');
            //$select->whereRaw('sp_ascii(perfil.nombre) ilike sp_ascii(?) ', ['%' . $likename . '%']); 
        }
        $data = $select
                ->orderBy($orderName, $orderSort)
                ->paginate($items);
        return $data;
    }

    public function listaPerfilModulo($param) {
        $data = \DB::table('perfilmodulo')
                ->join('modulo', 'perfilmodulo.idmodulo', '=', 'modulo.idmodulo')
                ->select('perfilmodulo.idmodulo', 'modulo.nombre')
                ->where($param)
                ->get()->all();

        return $data;
    }

    public function listaEntidadPerfil($param) {
        $data = \DB::table('entidadperfil')
                ->join('entidad', 'entidadperfil.identidad', '=', 'entidad.identidad')
                ->select('entidad.identidad')
                ->where($param)
                ->get()->all();

        return $data;
    }

    public function GrabarTransaccionPerfilModulo($data, $idperfil) {

        \DB::transaction(function () use ($data, $idperfil) {
            \DB::table('perfilmodulo')->where('idperfil', $idperfil)->delete();
            \DB::table('perfilmodulo')->insert($data);
        });
    }

}
