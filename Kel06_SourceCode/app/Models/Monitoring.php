<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Monitoring extends Model
{
    use HasFactory;

    protected $fillable = [
        'dosen_id',
        'matakuliah_id',
        'ajaran_id',
        'rps_id',
        'materi_id',
        'status_rps',
        'status_materi',
        'status_kuisioner',
        'status_perwalian',
        'persentase_kepatuhan',
        'catatan_monitoring',
        'tanggal_monitoring',
    ];

    protected $casts = [
        'tanggal_monitoring' => 'datetime',
    ];

    public function dosen()
    {
        return $this->belongsTo(Dosen::class);
    }

    public function matakuliah()
    {
        return $this->belongsTo(Matakuliah::class);
    }

    public function ajaran()
    {
        return $this->belongsTo(Ajaran::class);
    }

    public function rps()
    {
        return $this->belongsTo(RPS::class);
    }

    public function materi()
    {
        return $this->belongsTo(Materi::class);
    }
}
