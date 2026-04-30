<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalDosen extends Model
{
    use HasFactory;

    protected $table = 'jadwal_dosen';

    protected $fillable = [
        'pegawai_id',
        'kode_mk',
        'kuliah_id',
        'semester',
        'tahun_ajaran',
    ];

    /**
     * Relasi ke dosen
     */
    public function dosen()
    {
        return $this->belongsTo(Dosenn::class, 'pegawai_id', 'pegawai_id');
    }
}