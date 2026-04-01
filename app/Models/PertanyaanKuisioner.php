<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PertanyaanKuisioner extends Model
{
    use HasFactory;

    protected $table = 'pertanyaan_kuisioner';

    protected $fillable = [
        'kuisioner_id',
        'nomor_urut',
        'teks_pertanyaan',
        'tipe_pertanyaan',
        'opsi_jawaban',
    ];

    protected $casts = [
        'opsi_jawaban' => 'json',
    ];

    public function kuisioner()
    {
        return $this->belongsTo(Kuisioner::class);
    }

    public function jawaban()
    {
        return $this->hasMany(JawabanKuisioner::class);
    }
}
