<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanGJM extends Model
{
    use HasFactory;

    protected $table = 'laporan_gjm';

    protected $fillable = [
        'ajaran_id',
        'periode_mulai',
        'periode_akhir',
        'jenis_laporan',
        'program_studi',
        'ringkasan_mutu_institusi',
        'analisis_kepatuhan',
        'temuan_utama',
        'rekomendasi_perbaikan',
        'rencana_tindakan',
        'file_laporan',
        'dokumen_path',
        'status_laporan',
        'tanggal_submit',
        'reviewed_by',
        'tanggal_review',
        'catatan_review',
        'jumlah_prodi_terlibat',
        'jumlah_laporan_gkm_diterima',
        'created_by',
        'instruksi_prompt',
    ];

    protected $casts = [
        'periode_mulai' => 'date',
        'periode_akhir' => 'date',
        'tanggal_submit' => 'date',
        'tanggal_review' => 'date',
    ];

    public function ajaran()
    {
        return $this->belongsTo(Ajaran::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helper methods
    public function getStatusBadgeClass()
    {
        return match($this->status_laporan) {
            'draft' => 'bg-secondary',
            'menunggu_review' => 'bg-warning',
            'approved' => 'bg-success',
            'revisi' => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    public function getJenisLaporanLabel()
    {
        return match($this->jenis_laporan) {
            'triwulan' => 'Triwulan',
            'semester' => 'Semester',
            'bulanan' => 'Bulanan',
            'tahunan' => 'Tahunan',
            default => ucfirst($this->jenis_laporan)
        };
    }

    public function getPeriodeLabel()
    {
        if ($this->periode_mulai && $this->periode_akhir) {
            return $this->periode_mulai->format('M Y') . ' - ' . $this->periode_akhir->format('M Y');
        }
        return $this->periode_mulai ? $this->periode_mulai->format('M Y') : '-';
    }
}
