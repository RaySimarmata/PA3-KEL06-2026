<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerkuliahanMonitoringDetail extends Model
{
    use HasFactory;

    protected $table = 'perkuliahan_monitoring_details';

    protected $fillable = [
        'pegawai_id',
        'nama_dosen',

        'kode_mk',
        'nama_matkul',

        'prodi_kode',
        'prodi_id',

        'semester',
        'tahun_ajaran',

        'tingkat',

        'jenis_materi',

        'minggu',

        'status_upload',

        'is_tepat_waktu',

        'tanggal_upload',

        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'tanggal_upload' => 'datetime',
        'is_tepat_waktu' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATION DOSEN
    |--------------------------------------------------------------------------
    */

    public function dosen()
    {
        return $this->belongsTo(
            Dosenn::class,
            'pegawai_id',
            'pegawai_id'
        );
    }
}