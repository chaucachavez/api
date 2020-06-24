<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tarifario extends Model {

    protected $table = 'tarifario';
    protected $primaryKey = 'idtarifario';
    public $timestamps = false;
    protected $fillable = [
        'idtarifario',
        'idsede',
        'idproducto',
        'partref',
        'partcta',
        'partsta',
        'sscoref',
        'sscocta',
        'sscosta',
        'sccocien',
        'scconoventacinco',
        'scconoventa',
        'sccoochentacinco',
        'sccoochenta',
        'sccosetentacinco',
        'sccosetenta',
        'sccosesentacinco',
        'sccosesenta',
        'sccocincuentacinco',
        'sccocincuenta',
        'sccocuarentacinco',
        'sccocuarenta',
        'sccotreintacinco',
        'sccotreinta',
        'sccoveintecinco',
        'sccoveinte',
        'sccoquince',
        'sccodiez'
    ]; 

}
