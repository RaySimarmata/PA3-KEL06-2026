<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PencapaianKPI extends Model
{
    use HasFactory;

    protected $table = 'pencapaian_kpi';

    protected $fillable = [
        'prodi_id',
        'nama_kpi',
        'target_kpi',
        'pencapaian_kpi',
        'persentase_pencapaian',
        'periode',
        'catatan_kpi',
    ];

    public function prodi()
    {
        return $this->belongsTo(Prodi::class);
    }
}
