<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dosenn extends Model
{
    protected $table = 'dosenn';

    // primary key dari API
    protected $primaryKey = 'dosen_id';

    // karena bukan auto increment default Laravel
    public $incrementing = false;

    // tipe primary key
    protected $keyType = 'int';

    // mass assign
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

    public function matakuliah()
{
    return $this->belongsToMany(Matakuliah::class, 'dosen_matakuliah', 'dosen_id', 'matakuliah_id');
}
}