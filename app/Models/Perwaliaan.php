<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Perwaliaan extends Model
{
    use HasFactory;

    protected $fillable = [
        'dosen_pembimbing_id',
        'jumlah_mahasiswa_bimbing',
        'status_perwalian',
        'periode_perwalian',
        'catatan_perwalian',
        'tanggal_update',
    ];

    protected $casts = [
        'tanggal_update' => 'datetime',
    ];

    public function dosenPembimbing()
    {
        return $this->belongsTo(Dosen::class, 'dosen_pembimbing_id');
    }
}
