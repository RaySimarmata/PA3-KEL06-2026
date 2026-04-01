@extends('layouts.app')

@section('page-title')
Data Mata Kuliah Ajaran {{ $user->prodi ? $user->prodi->kode_prodi : "" }}
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
            <h5 class="mb-0">Daftar Mata Kuliah Ajaran 2025/2026</h5>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#tambahMatakuliahModal">
                + Tambah Mata Kuliah
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
                            <th>Kode Mata Kuliah</th>
                            <th>Nama Mata Kuliah</th>
                            <th>SKS</th>
                            <th>Semester</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($matakuliahList as $index => $mk)
                        <tr>
                            <td>{{ $matakuliahList->firstItem() + $index }}</td>
                            <td>{{ $mk->kode_mk }}</td>
                            <td>
                                {{ $mk->nama_mk }}
                                @if($mk->dosen->count() > 0)
                                <br>
                                <small class="text-muted">
                                    Dosen: {{ $mk->dosen->pluck('nama_lengkap')->join(', ') }}
                                </small>
                                @endif
                            </td>
                            <td>{{ $mk->sks }}</td>
                            <td>{{ $mk->semester ?? '-' }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editMatakuliahModal{{ $mk->id }}">
                                    Edit
                                </button>
                                <form action="{{ route('gkm.data-master.matakuliah.destroy', $mk->id) }}" 
                                      method="POST" 
                                      class="d-inline"
                                      onsubmit="return confirm('Yakin ingin menghapus mata kuliah ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>

                        <!-- Modal Edit Mata Kuliah -->
                        <div class="modal fade" id="editMatakuliahModal{{ $mk->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('gkm.data-master.matakuliah.update', $mk->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Mata Kuliah</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Kode Mata Kuliah</label>
                                                <input type="text" class="form-control" name="kode_mk" 
                                                       value="{{ $mk->kode_mk }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Nama Mata Kuliah</label>
                                                <input type="text" class="form-control" name="nama_mk" 
                                                       value="{{ $mk->nama_mk }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Dosen Pengajar</label>
                                                <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                                    @forelse($dosenList as $dosen)
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" 
                                                               name="dosen_ids[]" value="{{ $dosen->id }}" 
                                                               id="edit_dosen_{{ $mk->id }}_{{ $dosen->id }}"
                                                               {{ $mk->dosen->contains($dosen->id) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="edit_dosen_{{ $mk->id }}_{{ $dosen->id }}">
                                                            {{ $dosen->nama_lengkap }}
                                                            @if($dosen->gelar_akademik)
                                                            <small class="text-muted">({{ $dosen->gelar_akademik }})</small>
                                                            @endif
                                                        </label>
                                                    </div>
                                                    @empty
                                                    <p class="text-muted mb-0">Belum ada dosen aktif</p>
                                                    @endforelse
                                                </div>
                                                <small class="text-muted">Pilih satu atau lebih dosen pengajar</small>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">SKS</label>
                                                    <input type="number" class="form-control" name="sks" 
                                                           value="{{ $mk->sks }}" min="1" max="6" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Semester</label>
                                                    <input type="number" class="form-control" name="semester" 
                                                           value="{{ $mk->semester }}" min="1" max="8" required>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Jenis Mata Kuliah</label>
                                                <select class="form-select" name="jenis_mk">
                                                    <option value="">Pilih Jenis</option>
                                                    <option value="Teori" {{ $mk->jenis_mk == 'Teori' ? 'selected' : '' }}>Teori</option>
                                                    <option value="Praktik" {{ $mk->jenis_mk == 'Praktik' ? 'selected' : '' }}>Praktik</option>
                                                    <option value="Teori & Praktik" {{ $mk->jenis_mk == 'Teori & Praktik' ? 'selected' : '' }}>Teori & Praktik</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Deskripsi</label>
                                                <textarea class="form-control" name="deskripsi" rows="3">{{ $mk->deskripsi }}</textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" name="status" required>
                                                    <option value="aktif" {{ $mk->status == 'aktif' ? 'selected' : '' }}>Aktif</option>
                                                    <option value="tidak_aktif" {{ $mk->status == 'tidak_aktif' ? 'selected' : '' }}>Tidak Aktif</option>
                                                </select>
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
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">Belum ada data mata kuliah</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($matakuliahList->hasPages())
            <div class="mt-3">
                {{ $matakuliahList->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Tambah Mata Kuliah -->
<div class="modal fade" id="tambahMatakuliahModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('gkm.data-master.matakuliah.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Mata Kuliah Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kode Mata Kuliah <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('kode_mk') is-invalid @enderror" 
                               name="kode_mk" value="{{ old('kode_mk') }}" 
                               placeholder="Contoh: DPM001" required>
                        @error('kode_mk')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Mata Kuliah <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('nama_mk') is-invalid @enderror" 
                               name="nama_mk" value="{{ old('nama_mk') }}" 
                               placeholder="Contoh: Pemrograman Web" required>
                        @error('nama_mk')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dosen Pengajar</label>
                        <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                            @forelse($dosenList as $dosen)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       name="dosen_ids[]" value="{{ $dosen->id }}" 
                                       id="dosen_{{ $dosen->id }}"
                                       {{ (is_array(old('dosen_ids')) && in_array($dosen->id, old('dosen_ids'))) ? 'checked' : '' }}>
                                <label class="form-check-label" for="dosen_{{ $dosen->id }}">
                                    {{ $dosen->nama_lengkap }}
                                    @if($dosen->gelar_akademik)
                                    <small class="text-muted">({{ $dosen->gelar_akademik }})</small>
                                    @endif
                                </label>
                            </div>
                            @empty
                            <p class="text-muted mb-0">Belum ada dosen aktif</p>
                            @endforelse
                        </div>
                        <small class="text-muted">Pilih satu atau lebih dosen pengajar</small>
                        @error('dosen_ids')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SKS <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('sks') is-invalid @enderror" 
                                   name="sks" value="{{ old('sks') }}" 
                                   min="1" max="6" required>
                            @error('sks')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Semester <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('semester') is-invalid @enderror" 
                                   name="semester" value="{{ old('semester') }}" 
                                   min="1" max="8" required>
                            @error('semester')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis Mata Kuliah</label>
                        <select class="form-select @error('jenis_mk') is-invalid @enderror" name="jenis_mk">
                            <option value="">Pilih Jenis</option>
                            <option value="Teori" {{ old('jenis_mk') == 'Teori' ? 'selected' : '' }}>Teori</option>
                            <option value="Praktik" {{ old('jenis_mk') == 'Praktik' ? 'selected' : '' }}>Praktik</option>
                            <option value="Teori & Praktik" {{ old('jenis_mk') == 'Teori & Praktik' ? 'selected' : '' }}>Teori & Praktik</option>
                        </select>
                        @error('jenis_mk')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control @error('deskripsi') is-invalid @enderror" 
                                  name="deskripsi" rows="3" 
                                  placeholder="Deskripsi singkat mata kuliah">{{ old('deskripsi') }}</textarea>
                        @error('deskripsi')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                            <option value="">Pilih Status</option>
                            <option value="aktif" {{ old('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
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
