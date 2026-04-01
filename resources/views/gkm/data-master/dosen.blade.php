@extends('layouts.app')

@section('page-title')
Data Dosen Pengajar {{ $user->prodi ? $user->prodi->kode_prodi : "" }}
@endsection

@section('content')
<div style="padding: 1.5rem;">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Dosen Pengajar {{ $user->prodi ? $user->prodi->kode_prodi : "" }}</h5>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#tambahDosenModal">
                + Tambah Dosen
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Dosen</th>
                            <th>Email</th>
                            <th>NIDN</th>
                            <th>Jabatan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dosenList as $index => $dosen)
                        <tr>
                            <td>{{ $dosenList->firstItem() + $index }}</td>
                            <td>{{ $dosen->nama_lengkap }}</td>
                            <td>{{ $dosen->kontak_email }}</td>
                            <td>{{ $dosen->nidn }}</td>
                            <td>
                                @if($dosen->is_kaprodi)
                                    <span class="badge bg-primary">Kaprodi</span>
                                @endif
                                @if($dosen->is_dosen_wali)
                                    <span class="badge bg-info">Wali {{ $dosen->kelas_wali }}</span>
                                @endif
                                @if(!$dosen->is_kaprodi && !$dosen->is_dosen_wali)
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $dosen->status == 'aktif' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($dosen->status) }}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editDosenModal{{ $dosen->id }}">
                                    Edit
                                </button>
                                <form action="{{ route('gkm.data-master.dosen.destroy', $dosen->id) }}" 
                                      method="POST" 
                                      class="d-inline"
                                      onsubmit="return confirm('Yakin ingin menghapus dosen ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>

                        <!-- Modal Edit Dosen -->
                        <div class="modal fade" id="editDosenModal{{ $dosen->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('gkm.data-master.dosen.update', $dosen->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Dosen</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Nama Lengkap</label>
                                                <input type="text" class="form-control" name="nama_lengkap" 
                                                       value="{{ $dosen->nama_lengkap }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">NIDN</label>
                                                <input type="text" class="form-control" name="nidn" 
                                                       value="{{ $dosen->nidn }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" class="form-control" name="kontak_email" 
                                                       value="{{ $dosen->kontak_email }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Gelar Akademik</label>
                                                <input type="text" class="form-control" name="gelar_akademik" 
                                                       value="{{ $dosen->gelar_akademik }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Jabatan Akademik</label>
                                                <input type="text" class="form-control" name="jabatan_akademik" 
                                                       value="{{ $dosen->jabatan_akademik }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" name="status" required>
                                                    <option value="aktif" {{ $dosen->status == 'aktif' ? 'selected' : '' }}>Aktif</option>
                                                    <option value="tidak_aktif" {{ $dosen->status == 'tidak_aktif' ? 'selected' : '' }}>Tidak Aktif</option>
                                                    <option value="pensiun" {{ $dosen->status == 'pensiun' ? 'selected' : '' }}>Pensiun</option>
                                                </select>
                                            </div>
                                            <hr>
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="is_kaprodi" 
                                                           id="isKaprodi{{ $dosen->id }}" value="1" 
                                                           {{ $dosen->is_kaprodi ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="isKaprodi{{ $dosen->id }}">
                                                        Kaprodi
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="is_dosen_wali" 
                                                           id="isDosenWali{{ $dosen->id }}" value="1" 
                                                           {{ $dosen->is_dosen_wali ? 'checked' : '' }}
                                                           onchange="toggleKelasWali{{ $dosen->id }}(this)">
                                                    <label class="form-check-label" for="isDosenWali{{ $dosen->id }}">
                                                        Dosen Wali
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="mb-3" id="kelasWaliGroup{{ $dosen->id }}" 
                                                 style="display: {{ $dosen->is_dosen_wali ? 'block' : 'none' }};">
                                                <label class="form-label">Kelas Wali</label>
                                                <select class="form-select" name="kelas_wali" id="kelasWaliSelect{{ $dosen->id }}">
                                                    <option value="">Pilih Kelas</option>
                                                    @foreach($kelasList as $kelas)
                                                    <option value="{{ $kelas }}" {{ $dosen->kelas_wali == $kelas ? 'selected' : '' }}>
                                                        {{ $kelas }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <script>
                                                function toggleKelasWali{{ $dosen->id }}(checkbox) {
                                                    const kelasWaliGroup = document.getElementById('kelasWaliGroup{{ $dosen->id }}');
                                                    const kelasWaliSelect = document.getElementById('kelasWaliSelect{{ $dosen->id }}');
                                                    if (checkbox.checked) {
                                                        kelasWaliGroup.style.display = 'block';
                                                    } else {
                                                        kelasWaliGroup.style.display = 'none';
                                                        kelasWaliSelect.value = '';
                                                    }
                                                }
                                            </script>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary">Simpan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">Belum ada data dosen</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($dosenList->hasPages())
            <div class="mt-3">
                {{ $dosenList->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Tambah Dosen -->
<div class="modal fade" id="tambahDosenModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('gkm.data-master.dosen.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Dosen Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('nama_lengkap') is-invalid @enderror" 
                               name="nama_lengkap" value="{{ old('nama_lengkap') }}" required>
                        @error('nama_lengkap')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">NIDN <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('nidn') is-invalid @enderror" 
                               name="nidn" value="{{ old('nidn') }}" required>
                        @error('nidn')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('kontak_email') is-invalid @enderror" 
                               name="kontak_email" value="{{ old('kontak_email') }}" required>
                        @error('kontak_email')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gelar Akademik</label>
                        <input type="text" class="form-control @error('gelar_akademik') is-invalid @enderror" 
                               name="gelar_akademik" value="{{ old('gelar_akademik') }}" 
                               placeholder="Contoh: S.Kom, M.Cs">
                        @error('gelar_akademik')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jabatan Akademik</label>
                        <input type="text" class="form-control @error('jabatan_akademik') is-invalid @enderror" 
                               name="jabatan_akademik" value="{{ old('jabatan_akademik') }}" 
                               placeholder="Contoh: Asisten Ahli, Lektor">
                        @error('jabatan_akademik')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                            <option value="">Pilih Status</option>
                            <option value="aktif" {{ old('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="tidak_aktif" {{ old('status') == 'tidak_aktif' ? 'selected' : '' }}>Tidak Aktif</option>
                            <option value="pensiun" {{ old('status') == 'pensiun' ? 'selected' : '' }}>Pensiun</option>
                        </select>
                        @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <hr>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_kaprodi" 
                                   id="isKaprodiAdd" value="1" {{ old('is_kaprodi') ? 'checked' : '' }}>
                            <label class="form-check-label" for="isKaprodiAdd">
                                Kaprodi
                            </label>
                        </div>
                        <small class="text-muted">Centang jika dosen ini adalah Ketua Program Studi</small>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_dosen_wali" 
                                   id="isDosenWaliAdd" value="1" {{ old('is_dosen_wali') ? 'checked' : '' }}
                                   onchange="toggleKelasWaliAdd(this)">
                            <label class="form-check-label" for="isDosenWaliAdd">
                                Dosen Wali
                            </label>
                        </div>
                        <small class="text-muted">Centang jika dosen ini adalah Dosen Wali kelas</small>
                    </div>
                    <div class="mb-3" id="kelasWaliGroupAdd" style="display: {{ old('is_dosen_wali') ? 'block' : 'none' }};">
                        <label class="form-label">Kelas Wali</label>
                        <select class="form-select @error('kelas_wali') is-invalid @enderror" 
                                name="kelas_wali" id="kelasWaliSelectAdd">
                            <option value="">Pilih Kelas</option>
                            @foreach($kelasList as $kelas)
                            <option value="{{ $kelas }}" {{ old('kelas_wali') == $kelas ? 'selected' : '' }}>
                                {{ $kelas }}
                            </option>
                            @endforeach
                        </select>
                        @error('kelas_wali')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Pilih kelas yang akan dibimbing oleh dosen wali ini</small>
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

@push('scripts')
<script>
function toggleKelasWaliAdd(checkbox) {
    const kelasWaliGroup = document.getElementById('kelasWaliGroupAdd');
    const kelasWaliSelect = document.getElementById('kelasWaliSelectAdd');
    if (checkbox.checked) {
        kelasWaliGroup.style.display = 'block';
    } else {
        kelasWaliGroup.style.display = 'none';
        kelasWaliSelect.value = '';
    }
}
</script>
@endpush
