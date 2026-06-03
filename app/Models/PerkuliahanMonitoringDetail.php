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

        // analytics fields
        'total_minggu',
        'jumlah_upload',
        'jumlah_terlambat',
        'jumlah_belum_upload',

        'persentase_kepatuhan',
        'status_kepatuhan',

        // JSON data minggu
        'detail_weeks',

        // optional trace data
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'detail_weeks' => 'array',
    ];

    public function dosen()
    {
        return $this->belongsTo(
            Dosenn::class,
            'pegawai_id',
            'pegawai_id'
        );
    }
}