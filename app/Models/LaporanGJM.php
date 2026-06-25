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
        'ppt_creator',
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

        // RAGAS Metrics for VMTS
        'ragas_faithfulness',
        'ragas_answer_relevancy',
        'ragas_context_precision',
        'ragas_context_recall',
        'ragas_context_relevancy',
        'ragas_overall_score',

        // RAG Metadata
        'rag_chunks_count',
        'rag_avg_similarity',
        'rag_contexts',
        'ragas_evaluation_type',
        'ragas_evaluated_at',
    ];

    protected $casts = [
        'periode_mulai' => 'date',
        'periode_akhir' => 'date',
        'tanggal_submit' => 'date',
        'tanggal_review' => 'date',
        'ppt_generated_at' => 'datetime',
        'instruksi_prompt' => 'array',
        'ai_sections' => 'array',
        'ai_file_details' => 'array',
        'ai_preview_created_at' => 'datetime',
        'ai_preview_used_for_generation' => 'boolean',
        'ocr_data' => 'array',
        'has_ocr_data' => 'boolean',

        // RAGAS Casts
        'rag_contexts' => 'array',
        'ragas_evaluated_at' => 'datetime',
    ];

    protected $appends = ['periode_triwulan', 'periode_semester', 'tahun'];

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
    public function getPeriodeTriwulanAttribute()
    {
        if (is_array($this->instruksi_prompt)) {
            return $this->instruksi_prompt['periode_triwulan'] ?? null;
        }
        return null;
    }

    public function getPeriodeSemesterAttribute()
    {
        if (is_array($this->instruksi_prompt)) {
            return $this->instruksi_prompt['periode_semester'] ?? null;
        }
        return null;
    }

    public function getTahunAttribute()
    {
        if (is_array($this->instruksi_prompt)) {
            return $this->instruksi_prompt['tahun'] ?? date('Y');
        }
        return date('Y');
    }

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
