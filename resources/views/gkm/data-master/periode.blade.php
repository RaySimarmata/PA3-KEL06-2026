@extends('layouts.app')

@section('page-title')
Data Periode Akademik {{ $user->prodi ? $user->prodi->kode_prodi : "" }}
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
            <h5 class="mb-0">Daftar Periode Semester Akademik</h5>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#tambahPeriodeModal">
                + Tambah Periode
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
                            <th>Tahun Ajaran</th>
                            <th>Semester</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Akhir</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($periodeList as $index => $periode)
                        <tr>
                            <td>{{ $periodeList->firstItem() + $index }}</td>
                            <td>{{ $periode->tahun_ajaran }}/{{ $periode->tahun_ajaran + 1 }}</td>
                            <td>{{ ucfirst($periode->semester) }}</td>
                            <td>{{ \Carbon\Carbon::parse($periode->tanggal_mulai)->format('d M Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($periode->tanggal_akhir)->format('d M Y') }}</td>
                            <td>
                                @if($periode->status == 'aktif')
                                <span class="badge bg-success">Aktif</span>
                                @else
                                <span class="badge bg-secondary">Tidak Aktif</span>
                                @endif
                            </td>
                            <td>
                                @if($periode->status != 'aktif')
                                <form action="{{ route('gkm.data-master.periode.activate', $periode->id) }}" 
                                      method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">Aktifkan</button>
                                </form>
                                @endif
                                
                                <button class="btn btn-sm btn-outline-secondary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editPeriodeModal{{ $periode->id }}">
                                    Edit
                                </button>
                                
                                @if($periode->status != 'aktif')
                                <form action="{{ route('gkm.data-master.periode.destroy', $periode->id) }}" 
                                      method="POST" 
                                      class="d-inline"
                                      onsubmit="return confirm('Yakin ingin menghapus periode ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                                @endif
                            </td>
                        </tr>

                        <!-- Modal Edit Periode -->
                        <div class="modal fade" id="editPeriodeModal{{ $periode->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('gkm.data-master.periode.update', $periode->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Periode Akademik</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Tahun Ajaran</label>
                                                <input type="number" class="form-control" name="tahun_ajaran" 
                                                       value="{{ $periode->tahun_ajaran }}" 
                                                       min="2020" max="2100" required>
                                                <small class="text-muted">Contoh: 2024 untuk tahun ajaran 2024/2025</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Semester</label>
                                                <select class="form-select" name="semester" required>
                                                    <option value="ganjil" {{ $periode->semester == 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                                    <option value="genap" {{ $periode->semester == 'genap' ? 'selected' : '' }}>Genap</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Tanggal Mulai</label>
                                                <input type="date" class="form-control" name="tanggal_mulai" 
                                                       value="{{ $periode->tanggal_mulai }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Tanggal Akhir</label>
                                                <input type="date" class="form-control" name="tanggal_akhir" 
                                                       value="{{ $periode->tanggal_akhir }}" required>
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
                            <td colspan="7" class="text-center">Belum ada data periode akademik</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($periodeList->hasPages())
            <div class="mt-3">
                {{ $periodeList->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Tambah Periode -->
<div class="modal fade" id="tambahPeriodeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('gkm.data-master.periode.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Periode Akademik Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('tahun_ajaran') is-invalid @enderror" 
                               name="tahun_ajaran" value="{{ old('tahun_ajaran', date('Y')) }}" 
                               min="2020" max="2100" required>
                        <small class="text-muted">Contoh: 2024 untuk tahun ajaran 2024/2025</small>
                        @error('tahun_ajaran')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Semester <span class="text-danger">*</span></label>
                        <select class="form-select @error('semester') is-invalid @enderror" name="semester" required>
                            <option value="">Pilih Semester</option>
                            <option value="ganjil" {{ old('semester') == 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                            <option value="genap" {{ old('semester') == 'genap' ? 'selected' : '' }}>Genap</option>
                        </select>
                        @error('semester')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('tanggal_mulai') is-invalid @enderror" 
                               name="tanggal_mulai" value="{{ old('tanggal_mulai') }}" required>
                        @error('tanggal_mulai')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Akhir <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('tanggal_akhir') is-invalid @enderror" 
                               name="tanggal_akhir" value="{{ old('tanggal_akhir') }}" required>
                        @error('tanggal_akhir')
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
