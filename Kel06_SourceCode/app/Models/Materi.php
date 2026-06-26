<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Materi extends Model
{
    use HasFactory;

    protected $table = 'materi'; // ✅ TAMBAHKAN INI

    protected $fillable = [
        'rps_id',
        'matakuliah_id',
        'dosen_id',
        'judul_materi',
        'deskripsi_materi',
        'file_materi',
        'jenis_file',
        'status_upload_materi',
        'tanggal_upload_materi',
        'lokasi_upload',
    ];

    protected $casts = [
        'tanggal_upload_materi' => 'datetime',
    ];

    public function rps()
    {
        return $this->belongsTo(RPS::class);
    }

    public function matakuliah()
    {
        return $this->belongsTo(Matakuliah::class);
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class);
    }

    public function evaluasiArtefak()
    {
        return $this->hasOne(EvaluasiArtefak::class, 'materi_id');
    }

    public function monitoring()
    {
        return $this->hasMany(Monitoring::class);
    }
}
