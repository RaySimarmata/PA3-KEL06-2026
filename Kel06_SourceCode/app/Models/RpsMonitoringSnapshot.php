<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RpsMonitoringSnapshot extends Model
{
    use HasFactory;

    protected $table = 'rps_monitoring_snapshots';

    protected $fillable = [
        'pegawai_id',
        'prodi_kode',
        'prodi_id',
        'semester',
        'tahun_ajaran',
        'kuliah_id',
        'kode_mk',
        'nama_matkul',
        'status_rps',
        'reminder_sent',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'reminder_sent' => 'boolean',
        'pegawai_id' => 'integer',
        'prodi_id' => 'integer',
        'semester' => 'integer',
    ];

    /**
     * Relationship with Dosen model
     */
    public function dosen()
    {
        return $this->belongsTo(Dosenn::class, 'pegawai_id', 'pegawai_id');
    }

    /**
     * Relationship with Prodi model
     */
    public function prodi()
    {
        return $this->belongsTo(Prodi::class, 'prodi_id', 'id');
    }

    /**
     * Scope for filtering by semester and tahun ajaran
     */
    public function scopeByPeriode($query, $semester, $tahunAjaran)
    {
        return $query->where('semester', $semester)
                    ->where('tahun_ajaran', $tahunAjaran);
    }

    /**
     * Scope for filtering by prodi
     */
    public function scopeByProdi($query, $prodiId)
    {
        return $query->where('prodi_id', $prodiId);
    }

    /**
     * Scope for filtering by status RPS
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status_rps', $status);
    }

    /**
     * Scope for filtering by reminder sent status
     */
    public function scopeReminderNotSent($query)
    {
        return $query->where('reminder_sent', false);
    }

    /**
     * Mark reminder as sent
     */
    public function markReminderSent()
    {
        $this->update(['reminder_sent' => true]);
    }
}