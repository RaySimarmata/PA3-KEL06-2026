@extends('layouts.app')

@section('page-title', 'Buat Laporan Baru')

@section('content')
<div class="container-fluid px-4">
    <!-- Header Section -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                <div class="flex-grow-1">
                    <h3 class="mb-2 fw-bold text-dark">Buat Laporan Baru</h3>
                    <p class="text-muted mb-0 fs-6">Buat laporan monitoring dan evaluasi GJM fakultas</p>
                </div>
                
                <div class="d-flex gap-2">
                    <a href="{{ route('gjm.laporan-gjm.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                        <i class="bi bi-arrow-left"></i>
                        <span>Kembali ke Arsip</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Section -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-4">
                    <h5 class="mb-0 fw-semibold">Informasi Laporan</h5>
                </div>
                
                <div class="card-body p-4">
                    <form action="{{ route('gjm.laporan-gjm.generate') }}" method="POST">
                        @csrf
                        
                        <!-- Jenis Laporan -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="jenis_laporan" class="form-label fw-semibold">Jenis Laporan <span class="text-danger">*</span></label>
                                <select class="form-select" id="jenis_laporan" name="jenis_laporan" required>
                                    <option value="">Pilih Jenis Laporan</option>
                                    <option value="bulanan">Laporan Bulanan</option>
                                    <option value="semester">Laporan Semester</option>
                                    <option value="tahunan">Laporan Tahunan</option>
                                </select>
                                @error('jenis_laporan')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="ajaran_id" class="form-label fw-semibold">Tahun Ajaran <span class="text-danger">*</span></label>
                                <select class="form-select" id="ajaran_id" name="ajaran_id" required>
                                    <option value="">Pilih Tahun Ajaran</option>
                                    @foreach(\App\Models\Ajaran::orderBy('tahun_ajaran', 'desc')->get() as $ajaran)
                                    <option value="{{ $ajaran->id }}">{{ $ajaran->tahun_ajaran }} - {{ ucfirst($ajaran->semester) }}</option>
                                    @endforeach
                                </select>
                                @error('ajaran_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Periode Laporan -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="periode_mulai" class="form-label fw-semibold">Periode Mulai <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="periode_mulai" name="periode_mulai" required>
                                @error('periode_mulai')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="periode_akhir" class="form-label fw-semibold">Periode Akhir <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="periode_akhir" name="periode_akhir" required>
                                @error('periode_akhir')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Ringkasan -->
                        <div class="mb-4">
                            <label for="ringkasan_mutu_institusi" class="form-label fw-semibold">Ringkasan Mutu Institusi</label>
                            <textarea class="form-control" id="ringkasan_mutu_institusi" name="ringkasan_mutu_institusi" rows="4" 
                                      placeholder="Masukkan ringkasan kondisi mutu institusi..."></textarea>
                            @error('ringkasan_mutu_institusi')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Analisis Kepatuhan -->
                        <div class="mb-4">
                            <label for="analisis_kepatuhan" class="form-label fw-semibold">Analisis Kepatuhan</label>
                            <textarea class="form-control" id="analisis_kepatuhan" name="analisis_kepatuhan" rows="4" 
                                      placeholder="Masukkan analisis tingkat kepatuhan..."></textarea>
                            @error('analisis_kepatuhan')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Temuan Utama -->
                        <div class="mb-4">
                            <label for="temuan_utama" class="form-label fw-semibold">Temuan Utama</label>
                            <textarea class="form-control" id="temuan_utama" name="temuan_utama" rows="3" 
                                      placeholder="Masukkan temuan-temuan utama..."></textarea>
                            @error('temuan_utama')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Rekomendasi -->
                        <div class="mb-4">
                            <label for="rekomendasi_perbaikan" class="form-label fw-semibold">Rekomendasi Perbaikan</label>
                            <textarea class="form-control" id="rekomendasi_perbaikan" name="rekomendasi_perbaikan" rows="3" 
                                      placeholder="Masukkan rekomendasi untuk perbaikan..."></textarea>
                            @error('rekomendasi_perbaikan')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Rencana Tindakan -->
                        <div class="mb-4">
                            <label for="rencana_tindakan" class="form-label fw-semibold">Rencana Tindakan</label>
                            <textarea class="form-control" id="rencana_tindakan" name="rencana_tindakan" rows="3" 
                                      placeholder="Masukkan rencana tindakan yang akan dilakukan..."></textarea>
                            @error('rencana_tindakan')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Data Statistik -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="jumlah_prodi_terlibat" class="form-label fw-semibold">Jumlah Prodi Terlibat</label>
                                <input type="number" class="form-control" id="jumlah_prodi_terlibat" name="jumlah_prodi_terlibat" 
                                       min="0" placeholder="0">
                                @error('jumlah_prodi_terlibat')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="jumlah_laporan_gkm_diterima" class="form-label fw-semibold">Jumlah Laporan GKM Diterima</label>
                                <input type="number" class="form-control" id="jumlah_laporan_gkm_diterima" name="jumlah_laporan_gkm_diterima" 
                                       min="0" placeholder="0">
                                @error('jumlah_laporan_gkm_diterima')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-end">
                            <a href="{{ route('gjm.laporan-gjm.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i> Batal
                            </a>
                            <button type="submit" name="action" value="draft" class="btn btn-outline-primary">
                                <i class="bi bi-file-earmark me-1"></i> Simpan sebagai Draft
                            </button>
                            <button type="submit" name="action" value="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> Buat Laporan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Cards -->
    <div class="row mt-5">
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="text-info mb-3">
                        <i class="bi bi-info-circle" style="font-size: 2.5rem;"></i>
                    </div>
                    <h6 class="fw-semibold mb-2">Laporan Bulanan</h6>
                    <p class="text-muted small mb-0">Laporan evaluasi bulanan berdasarkan data monitoring GKM</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="text-success mb-3">
                        <i class="bi bi-calendar-range" style="font-size: 2.5rem;"></i>
                    </div>
                    <h6 class="fw-semibold mb-2">Laporan Semester</h6>
                    <p class="text-muted small mb-0">Laporan komprehensif per semester dengan analisis mendalam</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="text-warning mb-3">
                        <i class="bi bi-calendar-year" style="font-size: 2.5rem;"></i>
                    </div>
                    <h6 class="fw-semibold mb-2">Laporan Tahunan</h6>
                    <p class="text-muted small mb-0">Laporan evaluasi tahunan dengan rekomendasi strategis</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Form styling improvements */
.form-label {
    color: #495057;
    margin-bottom: 0.5rem;
}

.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.btn {
    transition: all 0.2s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
}

@media (max-width: 576px) {
    .container-fluid {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
    
    .card-body {
        padding: 1.5rem !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-set periode akhir based on jenis laporan
    const jenisLaporan = document.getElementById('jenis_laporan');
    const periodeMulai = document.getElementById('periode_mulai');
    const periodeAkhir = document.getElementById('periode_akhir');
    
    function updatePeriodeAkhir() {
        if (periodeMulai.value && jenisLaporan.value) {
            const startDate = new Date(periodeMulai.value);
            let endDate = new Date(startDate);
            
            switch(jenisLaporan.value) {
                case 'bulanan':
                    endDate.setMonth(endDate.getMonth() + 1);
                    endDate.setDate(0); // Last day of month
                    break;
                case 'semester':
                    endDate.setMonth(endDate.getMonth() + 6);
                    break;
                case 'tahunan':
                    endDate.setFullYear(endDate.getFullYear() + 1);
                    break;
            }
            
            periodeAkhir.value = endDate.toISOString().split('T')[0];
        }
    }
    
    jenisLaporan.addEventListener('change', updatePeriodeAkhir);
    periodeMulai.addEventListener('change', updatePeriodeAkhir);
});
</script>
@endsection