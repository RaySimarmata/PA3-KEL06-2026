<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RpsMonitoringSnapshot extends Model
{
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
    ];

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    // Filter berdasarkan dosen
    public function scopeByDosen($query, $pegawaiId)
    {
        return $query->where('pegawai_id', $pegawaiId);
    }

    // Filter periode
    public function scopeByPeriode($query, $semester, $tahunAjaran)
    {
        return $query->where('semester', $semester)
                    ->where('tahun_ajaran', $tahunAjaran);
    }

    // Filter belum upload RPS
    public function scopeBelumUpload($query)
    {
        return $query->where('status_rps', 'BELUM UPLOAD');
    }

    // Filter yang belum dikirim reminder
    public function scopeBelumDireminder($query)
    {
        return $query->where('reminder_sent', false);
    }
    public function matakuliah()
{
    return $this->belongsToMany(Matakuliah::class, 'dosen_matakuliah', 'dosen_id', 'matakuliah_id');
}
public function rps()
{
    return $this->hasMany(RpsMonitoringSnapshot::class, 'pegawai_id', 'pegawai_id');
}

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    // Cek apakah sudah pernah direminder
    public static function alreadyReminded($pegawaiId, $semester, $tahunAjaran)
    {
        return self::where('pegawai_id', $pegawaiId)
            ->where('semester', $semester)
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('reminder_sent', true)
            ->exists();
    }

    // Mark sudah dikirim reminder
    public static function markReminded($pegawaiId, $semester, $tahunAjaran)
    {
        return self::where('pegawai_id', $pegawaiId)
            ->where('semester', $semester)
            ->where('tahun_ajaran', $tahunAjaran)
            ->update([
                'reminder_sent' => true
            ]);
    }
}