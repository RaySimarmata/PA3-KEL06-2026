<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KuesioneUpload extends Model
{
    use HasFactory;

    protected $table = 'kuesioner_uploads';

    protected $fillable = [
        'nama_file',
        'file_path',
        'periode',
        'nama_matakuliah',
        'kode_matakuliah',
        'dosen_pengampu',
        'tingkat',
        'user_id',
        'deskripsi',
        'total_responden',
        'hasil_analisis',
        'status',
        'source',              
        'index_kepuasan',      
        'persen_kepuasan',
    ];

    protected $casts = [
        'hasil_analisis' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper method untuk mendapatkan prodi dari user
    public function getProdiAttribute()
    {
        return $this->user->prodi ?? null;
    }
}
