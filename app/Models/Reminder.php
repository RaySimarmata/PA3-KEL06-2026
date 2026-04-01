<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    use HasFactory;

    // 🔥 INI WAJIB
    protected $table = 'reminder';

    protected $fillable = [
        'user_pembuat_id',
        'user_penerima_id',
        'tipe_reminder',
        'subjek_reminder',
        'isi_reminder',
        'tanggal_pengiriman',
        'status_pengiriman',
        'catatan_reminder',
    ];

    protected $casts = [
        'tanggal_pengiriman' => 'datetime',
    ];

    public function userPembuat()
    {
        return $this->belongsTo(User::class, 'user_pembuat_id');
    }

    public function userPenerima()
    {
        return $this->belongsTo(User::class, 'user_penerima_id');
    }

    public function logEmail()
    {
        return $this->hasMany(LogEmail::class);
    }
}
