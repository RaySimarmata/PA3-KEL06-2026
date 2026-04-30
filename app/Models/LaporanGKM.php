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
        'user_id',
        'template_id',
        'ajaran_id',
        'jenis_laporan',
        'periode',
        'bulan',
        'tahun',
        'periode_laporan',
        'file_laporan',
        'file_word',
        'file_pdf',
        'kepatuhan_rps',
        'kepatuhan_materi',
        'hasil_kuisioner',
        'catatan_laporan',
        'status_laporan',
        'status',
        'konten_laporan',
        'total_rps',
        'total_materi',
        'error_message',
        'tanggal_buat_laporan',
        'tanggal_validasi',
        'validated_by',
        'generated_at',
    ];

    protected $casts = [
        'tanggal_buat_laporan' => 'datetime',
        'tanggal_validasi' => 'datetime',
        'generated_at' => 'datetime',
    ];

    // Accessors
    public function getFormattedPeriodeAttribute()
    {
        if ($this->bulan && $this->tahun) {
            return $this->bulan . ' ' . $this->tahun;
        }
        return $this->periode ?? '-';
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'Menunggu',
            'processing' => 'Sedang Diproses',
            'completed' => 'Selesai',
            'error' => 'Error',
            default => 'Unknown'
        };
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'pending' => 'info',
            'processing' => 'warning',
            'completed' => 'success',
            'error' => 'danger',
            default => 'secondary'
        };
    }

    public function prodi()
    {
        return $this->belongsTo(Prodi::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function template()
    {
        return $this->belongsTo(TemplateLaporan::class, 'template_id');
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
