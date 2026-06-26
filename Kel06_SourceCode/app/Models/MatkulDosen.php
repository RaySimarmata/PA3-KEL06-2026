<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatkulDosen extends Model
{
    protected $table = 'matkul_dosen';

    protected $fillable = [
        'kode_mk',
        'nama_mk',
        'pegawai_id'
    ];

    public function dosen()
    {
        return $this->belongsTo(Dosenn::class, 'pegawai_id', 'pegawai_id');
    }
}
