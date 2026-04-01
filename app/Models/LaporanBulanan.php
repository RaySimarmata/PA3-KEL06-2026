<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanBulanan extends Model
{
    use HasFactory;

    protected $table = 'laporan_bulanan';

    protected $fillable = [
        'periode',
        'bulan',
        'tahun',
        'prodi_id',
        'user_id',
        'template_id',
        'total_kuesioner',
        'total_responden',
        'index_kepuasan_rata_rata',
        'persen_kepuasan_rata_rata',
        'hasil_laporan',
        'konten',
        'metadata',
        'generated_by',
        'file_word',
        'file_pdf',
        'status',
        'error_message',
    ];

    protected $casts = [
        'hasil_laporan' => 'array',
        'konten' => 'string',
        'metadata' => 'string',
        'index_kepuasan_rata_rata' => 'decimal:2',
        'persen_kepuasan_rata_rata' => 'decimal:2',
    ];

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Prodi
     */
    public function prodi()
    {
        return $this->belongsTo(Prodi::class);
    }

    /**
     * Relasi ke Template Laporan
     */
    public function template()
    {
        return $this->belongsTo(TemplateLaporan::class, 'template_id');
    }

    /**
     * Scope untuk filter by periode
     */
    public function scopePeriode($query, $periode)
    {
        return $query->where('periode', $periode);
    }

    /**
     * Scope untuk filter by prodi
     */
    public function scopeProdi($query, $prodiId)
    {
        return $query->where('prodi_id', $prodiId);
    }

    /**
     * Scope untuk filter by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'completed' => 'success',
            'processing' => 'warning',
            'error' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'Menunggu',
            'processing' => 'Sedang Diproses',
            'completed' => 'Selesai',
            'error' => 'Error',
            default => 'Unknown',
        };
    }

    /**
     * Get formatted periode
     */
    public function getFormattedPeriodeAttribute()
    {
        return $this->bulan . ' ' . $this->tahun;
    }
}
