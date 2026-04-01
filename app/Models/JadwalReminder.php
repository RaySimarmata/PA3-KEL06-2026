<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalReminder extends Model
{
    use HasFactory;

    protected $table = 'jadwal_reminder';

    protected $fillable = [
        'prodi_id',
        'nama_jadwal',
        'tipe_reminder',
        'jam_pengiriman',
        'tanggal_mulai',
        'tanggal_selesai',
        'waktu_pengiriman',
        'frekuensi',
        'template_pesan',
        'pesan_template',
        'status',
        'last_sent_at',
        'is_active',
        'dibuat_oleh',
        'catatan',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function dibuatOleh()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * Check if the reminder schedule is currently active based on date
     */
    public function isActiveNow()
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->tanggal_mulai) {
            return false;
        }

        $today = now()->startOfDay();
        $tanggalKirim = $this->tanggal_mulai->startOfDay();

        // Reminder aktif jika tanggal kirim belum lewat
        return $tanggalKirim->gte($today);
    }

    /**
     * Get status text based on date and active status
     */
    public function getStatusText()
    {
        if (!$this->is_active) {
            return 'Nonaktif';
        }

        if (!$this->tanggal_mulai) {
            return 'Belum Dijadwalkan';
        }

        $today = now()->startOfDay();
        $tanggalKirim = $this->tanggal_mulai->startOfDay();

        if ($tanggalKirim->gt($today)) {
            return 'Terjadwal';
        }

        if ($tanggalKirim->eq($today)) {
            return 'Hari Ini';
        }

        return 'Selesai';
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClass()
    {
        $status = $this->getStatusText();

        return match($status) {
            'Hari Ini' => 'bg-success',
            'Terjadwal' => 'bg-info',
            'Selesai' => 'bg-secondary',
            'Nonaktif' => 'bg-warning',
            'Belum Dijadwalkan' => 'bg-danger',
            default => 'bg-secondary',
        };
    }
}
