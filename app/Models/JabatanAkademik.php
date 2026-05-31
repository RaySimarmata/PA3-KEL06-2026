<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JabatanAkademik extends Model
{
    protected $table = 'jabatan_akademik';

    protected $fillable = [
        'kode',
        'nama'
    ];
}