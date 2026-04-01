<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateLaporan extends Model
{
    protected $table = 'template_laporan';

    protected $fillable = [
        'prodi_id',
        'nama_template',
        'nama_file',
        'jenis_file',
        'file_path',
        'ukuran_file',
        'deskripsi',
        'uploaded_by',
        'jenis_template',
        'struktur_template',
        'contoh_konten',
        'is_active',
        'is_indexed',
        'indexed_at',
        'total_chunks',
        'structure_metadata',
    ];

    protected $casts = [
        'struktur_template' => 'array',
        'structure_metadata' => 'array',
        'is_active' => 'boolean',
        'is_indexed' => 'boolean',
        'indexed_at' => 'datetime',
    ];

    public function prodi()
    {
        return $this->belongsTo(Prodi::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Relasi ke Laporan Bulanan
     */
    public function laporanBulanan()
    {
        return $this->hasMany(LaporanBulanan::class, 'template_id');
    }

    /**
     * Scope untuk template aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk filter by jenis template
     */
    public function scopeJenis($query, $jenis)
    {
        return $query->where('jenis_template', $jenis);
    }
}
