<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class KuesionerMongo extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'kuesioner_data';

    protected $primaryKey = 'id';

    protected $fillable = [
        'kuesioner_id',
        'judul_kuesioner',
        'kode_mk',
        'periode',
        'raw_data',
        'updated_at',
        'created_at'
    ];

    protected $guarded = [];

    protected $casts = [
        'raw_data' => 'array',
    ];

    public $timestamps = true;
}