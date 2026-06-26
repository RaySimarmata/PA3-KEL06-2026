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
        'user_id',
        'periode_akademik_id',
        'template_id',
        'judul_laporan',
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
        'tipe_laporan',
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
     * Relasi ke Prodi (through user)
     */
    public function prodi()
    {
        return $this->hasOneThrough(Prodi::class, User::class, 'id', 'id', 'user_id', 'prodi_id');
    }

    /**
     * Relasi ke Template Laporan
     */
    public function template()
    {
        return $this->belongsTo(TemplateLaporan::class, 'template_id');
    }

    /**
     * Relasi ke Periode Akademik
     */
    public function periodeAkademik()
    {
        return $this->belongsTo(PeriodeAkademik::class, 'periode_akademik_id');
    }

    /**
     * Scope untuk filter by periode
     */
    public function scopePeriode($query, $periode)
    {
        return $query->where('periode', $periode);
    }

    /**
     * Scope untuk filter by user
     */
    public function scopeUser($query, $userId)
    {
        return $query->where('user_id', $userId);
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
            'error' => 'danger',
            default => 'danger',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'completed' => 'Berhasil',
            default => 'Gagal',
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
