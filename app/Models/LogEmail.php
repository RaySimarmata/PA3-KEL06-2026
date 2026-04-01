<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogEmail extends Model
{
    use HasFactory;

    protected $table = 'log_email';

    protected $fillable = [
        'reminder_id',
        'prodi_id',
        'penerima_email',
        'subjek',
        'isi_email',
        'status_pengiriman',
        'pesan_error',
        'tanggal_pengiriman',
        'percobaan_kirim',
    ];

    protected $casts = [
        'tanggal_pengiriman' => 'date',
    ];

    public function reminder()
    {
        return $this->belongsTo(Reminder::class);
    }

    public function prodi()
    {
        return $this->belongsTo(Prodi::class);
    }
}
