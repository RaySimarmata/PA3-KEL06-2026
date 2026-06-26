<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class KirimLaporanHistory extends Model
{
    use HasFactory;

    protected $table = 'kirim_laporan_history';

    protected $fillable = [
        'user_id',
        'recipients',
        'cc',
        'subject',
        'message',
        'laporan_ids',
        'attachment_names',
        'recipient_statuses',
        'sent_count',
        'recipient_count',
        'failed_count',
        'status',
        'error_message',
    ];

    protected $casts = [
        'laporan_ids' => 'array',
        'attachment_names' => 'array',
        'recipient_statuses' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
