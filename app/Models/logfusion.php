<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class logfusion extends apimodel {

    protected $table = 'logfusion';
    protected $primaryKey = 'idlogfusion';
    public $timestamps = false;
    
    protected $fillable = [
        'idlogfusion',
        'idempresa',
        'identidadeliminado',
        'identidadconservado',
        'descripcion', 
        'created_at',
        'updated_at',
        'deleted_at',
        'id_created_at',
        'id_updated_at',
        'id_deleted_at',
        'deleted'
    ];

    protected $hidden = ['idempresa']; 

}
