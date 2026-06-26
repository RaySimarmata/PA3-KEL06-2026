<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dosen extends Model
{
    use HasFactory;

    // Paksa Laravel pakai tabel "dosen"
    protected $table = 'dosen';

    protected $fillable = [
        'user_id',
        'prodi_id',
        'nama_lengkap',
        'nidn',
        'gelar_akademik',
        'jabatan_akademik',
        'kontak_email',
        'bio',
        'foto_profil',
        'status',
        'is_kaprodi',
        'is_dosen_wali',
        'kelas_wali',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function prodi()
    {
        return $this->belongsTo(Prodi::class);
    }

    public function matakuliah()
    {
        return $this->belongsToMany(Matakuliah::class, 'dosen_matakuliah');
    }

    public function rps()
    {
        return $this->hasMany(RPS::class);
    }

    public function materi()
    {
        return $this->hasMany(Materi::class);
    }

    public function monitoring()
    {
        return $this->hasMany(Monitoring::class);
    }

    public function perwalian()
    {
        return $this->hasMany(Perwaliaan::class, 'dosen_pembimbing_id');
    }

    public function kuisionerResponses()
    {
        return $this->hasMany(JawabanKuisioner::class);
    }

    public function laporanGKM()
    {
        return $this->hasMany(LaporanGKM::class, 'prodi_id', 'prodi_id');
    }

    public function evaluasiArtefak()
    {
        return $this->hasMany(EvaluasiArtefak::class, 'dosen_id');
    }

    /**
     * Scope untuk filter dosen yang merupakan Kaprodi
     */
    public function scopeKaprodi($query)
    {
        return $query->where('is_kaprodi', true);
    }

    /**
     * Scope untuk filter dosen yang merupakan Dosen Wali
     */
    public function scopeDosenWali($query)
    {
        return $query->where('is_dosen_wali', true);
    }

    /**
     * Get daftar kelas yang tersedia berdasarkan prodi
     */
    public static function getKelasListByProdi($prodiId)
    {
        // Jika tidak ada prodi_id, ambil semua kelas aktif
        if (!$prodiId) {
            return Kelas::where('status', 'aktif')
                ->orderBy('kode_kelas')
                ->pluck('kode_kelas')
                ->toArray();
        }

        // Ambil kelas berdasarkan prodi_id dari tabel kelas
        return Kelas::where('prodi_id', $prodiId)
            ->where('status', 'aktif')
            ->orderBy('kode_kelas')
            ->pluck('kode_kelas')
            ->toArray();
    }
}
