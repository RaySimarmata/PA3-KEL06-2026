<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JawabanKuisioner extends Model
{
    use HasFactory;

    protected $table = 'jawaban_kuisioner';

    protected $fillable = [
        'kuisioner_id',
        'pertanyaan_id',
        'dosen_id',
        'jawaban_teks',
        'jawaban_nilai',
        'tanggal_jawab',
    ];

    protected $casts = [
        'tanggal_jawab' => 'datetime',
    ];

    public function kuisioner()
    {
        return $this->belongsTo(Kuisioner::class);
    }

    public function pertanyaan()
    {
        return $this->belongsTo(PertanyaanKuisioner::class);
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class);
    }
}
