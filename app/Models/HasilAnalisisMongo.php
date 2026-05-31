<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class HasilAnalisisMongo extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'hasil_analisis_lengkap';

    protected $primaryKey = 'id';

    protected $guarded = [];

    public $timestamps = false;
}