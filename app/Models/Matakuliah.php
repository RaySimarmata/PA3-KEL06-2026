<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matakuliah extends Model
{
    use HasFactory;

    // Paksa Laravel pakai tabel "matakuliah"
    protected $table = 'matakuliah';

    protected $fillable = [
        'prodi_id',
        'kode_mk',
        'nama_mk',
        'deskripsi',
        'capaian_pembelajaran',
        'sks',
        'semester',
        'jenis_mk',
        'status',
    ];

    public function prodi()
    {
        return $this->belongsTo(Prodi::class);
    }

    public function dosen()
    {
        return $this->belongsToMany(Dosen::class, 'dosen_matakuliah');
    }

    public function rps()
    {
        return $this->hasMany(RPS::class);
    }

    public function materi()
    {
        return $this->hasMany(Materi::class);
    }
}
