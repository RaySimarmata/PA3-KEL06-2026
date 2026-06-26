<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dosenn extends Model
{
    protected $table = 'dosenn';

    protected $primaryKey = 'dosen_id';

    public $incrementing = false;

    protected $fillable = [
        'dosen_id',
        'pegawai_id',
        'user_id',
        'nip',
        'nidn',
        'nama',
        'inisial_nama',
        'email',
        'prodi_id',
        'prodi',
        'jabatan_akademik',
        'jabatan_akademik_desc',
        'jenjang_pendidikan',
    ];

    protected $casts = [
        'dosen_id' => 'integer',
        'pegawai_id' => 'integer',
        'user_id' => 'integer',
        'prodi_id' => 'integer',
    ];
}
