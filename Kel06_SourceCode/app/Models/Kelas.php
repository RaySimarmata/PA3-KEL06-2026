<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;

    protected $table = 'kelas';

    protected $fillable = [
        'prodi_id',
        'kode_kelas',
        'tingkat',
        'program_studi',
        'tahun_angkatan',
        'status',
    ];

    protected $casts = [
        'tingkat' => 'integer',
        'tahun_angkatan' => 'integer',
    ];

    /**
     * Relasi ke Prodi
     */
    public function prodi()
    {
        return $this->belongsTo(Prodi::class, 'prodi_id');
    }

    /**
     * Scope untuk filter berdasarkan prodi
     */
    public function scopeByProdi($query, $prodiId)
    {
        return $query->where('prodi_id', $prodiId);
    }

    /**
     * Scope untuk filter status aktif
     */
    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }
}
