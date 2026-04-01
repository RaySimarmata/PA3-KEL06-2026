<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RPS extends Model
{
    use HasFactory;

    protected $table = 'rps';

    protected $fillable = [
        'matakuliah_id',
        'ajaran_id',
        'dosen_id',
        'file_rps',
        'status_upload_rps',
        'tanggal_upload_rps',
        'status_review_rps',
        'feedback_review_rps',
    ];

    protected $casts = [
        'tanggal_upload_rps' => 'datetime',
    ];

    public function matakuliah()
    {
        return $this->belongsTo(Matakuliah::class);
    }

    public function ajaran()
    {
        return $this->belongsTo(Ajaran::class);
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class);
    }

    public function materi()
    {
        return $this->hasMany(Materi::class);
    }

    public function evaluasiArtefak()
    {
        return $this->hasOne(EvaluasiArtefak::class);
    }

    public function monitoring()
    {
        return $this->hasMany(Monitoring::class);
    }
}
