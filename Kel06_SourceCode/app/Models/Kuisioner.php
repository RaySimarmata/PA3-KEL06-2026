<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kuisioner extends Model
{
    use HasFactory;

    protected $table = 'kuisioner'; // ⬅️ WAJIB DITAMBAHKAN

    protected $fillable = [
        'judul_kuisioner',
        'deskripsi_kuisioner',
        'tipe_kuisioner',
        'tanggal_mulai',
        'tanggal_selesai',
        'status_kuisioner',
    ];

    protected $casts = [
        'tanggal_mulai' => 'datetime',
        'tanggal_selesai' => 'datetime',
    ];

    public function pertanyaan()
    {
        return $this->hasMany(PertanyaanKuisioner::class);
    }

    public function jawaban()
    {
        return $this->hasMany(JawabanKuisioner::class);
    }
}
