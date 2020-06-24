<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class cargoorg extends Model {

    protected $table = 'cargoorg';
    protected $primaryKey = 'idcargoorg';
    public $timestamps = false;
    protected $fillable = [
        'idempresa',
        'nombre'
    ];
    protected $hidden = ['idempresa'];

}
