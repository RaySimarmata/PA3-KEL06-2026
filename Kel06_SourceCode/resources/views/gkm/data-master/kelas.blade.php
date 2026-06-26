@extends('layouts.app')

@section('page-title', 'Data Kelas')

@section('content')
<div style="padding: 1.5rem;">
    <div class="filter-card mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1 font-semibold">Data Kelas</h5>
                <p class="text-secondary mb-0">Kelola daftar kelas untuk perwalian</p>
            </div>
            <button type="button" class="btn-action-primary" data-bs-toggle="modal" data-bs-target="#addKelasModal">
                <i class="bi bi-plus-circle"></i> Tambah Kelas
            </button>
        </div>
    </div>

    @if(session('success'))
    <div class="alert-gkm success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert-gkm danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="monitoring-card">
        <div class="monitoring-header">
            <i class="bi bi-table"></i>
            <h6>Daftar Kelas</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-monitoring">
                <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Kelas</th>
                            <th>Tingkat</th>
                            <th>Program Studi</th>
                            <th>Tahun Angkatan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kelasList as $index => $kelas)
                        <tr>
                            <td class="code-mk">{{ $kelasList->firstItem() + $index }}</td>
                            <td class="code-mk">{{ $kelas->kode_kelas }}</td>
                            <td class="text-secondary">Tingkat {{ $kelas->tingkat }}</td>
                            <td class="dosen-name">{{ $kelas->program_studi }}</td>
                            <td class="text-secondary">{{ $kelas->tahun_angkatan }}</td>
                            <td>
                                @if($kelas->status == 'aktif')
                                    <span class="badge-gkm success">Aktif</span>
                                @else
                                    <span class="badge-gkm" style="background-color: #e9ecef; color: #6c757d;">Tidak Aktif</span>
                                @endif
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        data-bs-toggle="modal" data-bs-target="#editKelasModal{{ $kelas->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('gkm.data-master.kelas.destroy', $kelas->id) }}" 
                                      method="POST" class="d-inline" 
                                      onsubmit="AppConfirm.delete(this, 'Data kelas ini akan dihapus permanen.'); return false;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editKelasModal{{ $kelas->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('gkm.data-master.kelas.update', $kelas->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Kelas</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="kode_kelas{{ $kelas->id }}" class="form-label">Kode Kelas <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="kode_kelas{{ $kelas->id }}" 
                                                       name="kode_kelas" value="{{ $kelas->kode_kelas }}" required
                                                       placeholder="Contoh: 41TRPL1">
                                                <small class="text-muted">Format: [Tingkat][Program Studi][Nomor Kelas]</small>
                                            </div>
                                            <div class="mb-3">
                                                <label for="tingkat{{ $kelas->id }}" class="form-label">Tingkat <span class="text-danger">*</span></label>
                                                <select class="form-select" id="tingkat{{ $kelas->id }}" name="tingkat" required>
                                                    <option value="1" {{ $kelas->tingkat == 1 ? 'selected' : '' }}>1</option>
                                                    <option value="2" {{ $kelas->tingkat == 2 ? 'selected' : '' }}>2</option>
                                                    <option value="3" {{ $kelas->tingkat == 3 ? 'selected' : '' }}>3</option>
                                                    <option value="4" {{ $kelas->tingkat == 4 ? 'selected' : '' }}>4</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="program_studi{{ $kelas->id }}" class="form-label">Program Studi <span class="text-danger">*</span></label>
                                                <select class="form-select" id="program_studi{{ $kelas->id }}" name="program_studi" required>
                                                    <option value="TRPL" {{ $kelas->program_studi == 'TRPL' ? 'selected' : '' }}>TRPL</option>
                                                    <option value="TI" {{ $kelas->program_studi == 'TI' ? 'selected' : '' }}>TI</option>
                                                    <option value="NM" {{ $kelas->program_studi == 'NM' ? 'selected' : '' }}>NM</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="tahun_angkatan{{ $kelas->id }}" class="form-label">Tahun Angkatan <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="tahun_angkatan{{ $kelas->id }}" 
                                                       name="tahun_angkatan" value="{{ $kelas->tahun_angkatan }}" required 
                                                       min="2000" max="{{ date('Y') + 1 }}">
                                            </div>
                                            <div class="mb-3">
                                                <label for="status{{ $kelas->id }}" class="form-label">Status <span class="text-danger">*</span></label>
                                                <select class="form-select" id="status{{ $kelas->id }}" name="status" required>
                                                    <option value="aktif" {{ $kelas->status == 'aktif' ? 'selected' : '' }}>Aktif</option>
                                                    <option value="tidak_aktif" {{ $kelas->status == 'tidak_aktif' ? 'selected' : '' }}>Tidak Aktif</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                    <p class="mt-2 mb-0">Belum ada data kelas</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if($kelasList->hasPages())
        <div class="mt-4 d-flex justify-content-center" style="padding: 0 1.5rem 1.5rem;">
            {{ $kelasList->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addKelasModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('gkm.data-master.kelas.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kelas Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="kode_kelas" class="form-label">Kode Kelas <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('kode_kelas') is-invalid @enderror" 
                               id="kode_kelas" name="kode_kelas" value="{{ old('kode_kelas') }}" required
                               placeholder="Contoh: 41TRPL1">
                        <small class="text-muted">Format: [Tingkat][Program Studi][Nomor Kelas]</small>
                        @error('kode_kelas')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="tingkat" class="form-label">Tingkat <span class="text-danger">*</span></label>
                        <select class="form-select @error('tingkat') is-invalid @enderror" 
                                id="tingkat" name="tingkat" required>
                            <option value="">Pilih Tingkat</option>
                            <option value="1" {{ old('tingkat') == 1 ? 'selected' : '' }}>1</option>
                            <option value="2" {{ old('tingkat') == 2 ? 'selected' : '' }}>2</option>
                            <option value="3" {{ old('tingkat') == 3 ? 'selected' : '' }}>3</option>
                            <option value="4" {{ old('tingkat') == 4 ? 'selected' : '' }}>4</option>
                        </select>
                        @error('tingkat')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="program_studi" class="form-label">Program Studi <span class="text-danger">*</span></label>
                        <select class="form-select @error('program_studi') is-invalid @enderror" 
                                id="program_studi" name="program_studi" required>
                            <option value="">Pilih Program Studi</option>
                            <option value="TRPL" {{ old('program_studi') == 'TRPL' ? 'selected' : '' }}>TRPL</option>
                            <option value="TI" {{ old('program_studi') == 'TI' ? 'selected' : '' }}>TI</option>
                            <option value="NM" {{ old('program_studi') == 'NM' ? 'selected' : '' }}>NM</option>
                        </select>
                        @error('program_studi')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="tahun_angkatan" class="form-label">Tahun Angkatan <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('tahun_angkatan') is-invalid @enderror" 
                               id="tahun_angkatan" name="tahun_angkatan" value="{{ old('tahun_angkatan', date('Y')) }}" 
                               required min="2000" max="{{ date('Y') + 1 }}" placeholder="2026">
                        @error('tahun_angkatan')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" 
                                id="status" name="status" required>
                            <option value="aktif" {{ old('status', 'aktif') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="tidak_aktif" {{ old('status') == 'tidak_aktif' ? 'selected' : '' }}>Tidak Aktif</option>
                        </select>
                        @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
