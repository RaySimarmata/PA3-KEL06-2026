<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodeAkademik extends Model
{
    protected $table = 'periode_akademik';

    protected $fillable = [
        'tahun_ajaran',
        'semester',
        'semester_label',
        'start_date',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
    ];
}
