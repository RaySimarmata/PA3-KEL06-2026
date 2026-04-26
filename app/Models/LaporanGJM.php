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
        'template_id',
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
        'dokumen_hasil_path',
        'ppt_path',
        'ppt_generated_at',
        'status_laporan',
        'tanggal_submit',
        'reviewed_by',
        'tanggal_review',
        'catatan_review',
        'jumlah_prodi_terlibat',
        'jumlah_laporan_gkm_diterima',
        'created_by',
        'instruksi_prompt',
        'ai_preview_draft',
        'ai_sections',
        'ai_file_details',
        'ai_preview_created_at',
        'ai_preview_used_for_generation',
        'ocr_data',
        'has_ocr_data',
    ];

    protected $casts = [
        'periode_mulai' => 'date',
        'periode_akhir' => 'date',
        'tanggal_submit' => 'date',
        'tanggal_review' => 'date',
        'ppt_generated_at' => 'datetime',
        'ai_sections' => 'array',
        'ai_file_details' => 'array',
        'ai_preview_created_at' => 'datetime',
        'ai_preview_used_for_generation' => 'boolean',
        'ocr_data' => 'array',
        'has_ocr_data' => 'boolean',
    ];

    public function ajaran()
    {
        return $this->belongsTo(Ajaran::class);
    }

    public function template()
    {
        return $this->belongsTo(TemplateLaporan::class, 'template_id');
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
            'processing' => 'bg-info',
            'completed' => 'bg-success',
            'menunggu_review' => 'bg-warning',
            'approved' => 'bg-success',
            'revisi' => 'bg-danger',
            'failed' => 'bg-danger',
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
