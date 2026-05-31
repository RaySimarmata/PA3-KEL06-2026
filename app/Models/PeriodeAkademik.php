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
        'end_date', // 🔥 tambah
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | 🔥 HELPER
    |--------------------------------------------------------------------------
    */

    // Ambil periode aktif
    public static function getActive()
    {
        return self::where('is_active', true)->first();
    }

    /*
    |--------------------------------------------------------------------------
    | 🔥 QUERY SCOPE
    |--------------------------------------------------------------------------
    */

    // Scope aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /*
    |--------------------------------------------------------------------------
    | 🔥 MUTATOR (AUTO LABEL)
    |--------------------------------------------------------------------------
    */

    public function setSemesterAttribute($value)
    {
        $this->attributes['semester'] = $value;

        // otomatis set label
        $this->attributes['semester_label'] = $value == 1 ? 'Ganjil' : 'Genap';
    }
}