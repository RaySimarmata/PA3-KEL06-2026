<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanGKM extends Model
{
    use HasFactory;

    protected $table = 'laporan_gkm';

    protected $fillable = [
        'prodi_id',
        'ajaran_id',
        'periode_laporan',
        'file_laporan',
        'kepatuhan_rps',
        'kepatuhan_materi',
        'hasil_kuisioner',
        'catatan_laporan',
        'status_laporan',
        'tanggal_buat_laporan',
        'tanggal_validasi',
        'validated_by',
    ];

    protected $casts = [
        'tanggal_buat_laporan' => 'datetime',
        'tanggal_validasi' => 'datetime',
    ];

    public function prodi()
    {
        return $this->belongsTo(Prodi::class);
    }

    public function ajaran()
    {
        return $this->belongsTo(Ajaran::class);
    }

    public function validatedByUser()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
