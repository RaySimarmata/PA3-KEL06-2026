<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluasiArtefak extends Model
{
    use HasFactory;

    protected $table = 'evaluasi_artefak';

    protected $fillable = [
        'evaluator_id',
        'rps_id',
        'jenis_artefak',
        'skor_evaluasi',
        'catatan_evaluasi',
        'status_evaluasi',
        'tanggal_evaluasi',
        'saran_perbaikan',
        'tanggal_revisi_selesai',
        'jumlah_revisi',
    ];

    protected $casts = [
        'tanggal_evaluasi' => 'datetime',
        'tanggal_revisi_selesai' => 'datetime',
    ];

    public function dosen()
    {
        return $this->belongsTo(Dosen::class, 'evaluator_id');
    }

    public function rps()
    {
        return $this->belongsTo(RPS::class);
    }
}
