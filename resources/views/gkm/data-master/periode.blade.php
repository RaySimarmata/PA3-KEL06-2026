@extends('layouts.app')

@section('page-title')
Data Periode Akademik {{ $user->prodi ? $user->prodi->kode_prodi : "" }}
@endsection

@section('content')
<div style="padding: 1.5rem;">
    @if(session('success'))
    <div class="alert-app success mb-3" data-auto-dismiss>
        <i class="bi bi-check-circle-fill alert-app-icon"></i>
        <div class="alert-app-body">{{ session('success') }}</div>
    </div>
    @endif

    @if(session('error'))
    <div class="alert-app danger mb-3" data-auto-dismiss>
        <i class="bi bi-exclamation-triangle-fill alert-app-icon"></i>
        <div class="alert-app-body">{{ session('error') }}</div>
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
                                      onsubmit="AppConfirm.delete(this, 'Periode ini akan dihapus permanen.'); return false;">
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
                                                <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" name="tanggal_mulai" 
                                                       value="{{ $periode->tanggal_mulai }}" required>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar-event"></i> Tanggal tidak boleh sebelum hari ini
                                                </small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Tanggal Akhir <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" name="tanggal_akhir" 
                                                       value="{{ $periode->tanggal_akhir }}" required>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar-event"></i> Tanggal tidak boleh sebelum hari ini
                                                </small>
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
                               id="tanggal_mulai_tambah"
                               name="tanggal_mulai" value="{{ old('tanggal_mulai') }}" required>
                        <small class="text-muted">
                            <i class="bi bi-calendar-event"></i> Tanggal tidak boleh sebelum hari ini
                        </small>
                        @error('tanggal_mulai')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Akhir <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('tanggal_akhir') is-invalid @enderror" 
                               id="tanggal_akhir_tambah"
                               name="tanggal_akhir" value="{{ old('tanggal_akhir') }}" required>
                        <small class="text-muted">
                            <i class="bi bi-calendar-event"></i> Tanggal tidak boleh sebelum hari ini
                        </small>
                        @error('tanggal_akhir')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="submitTambahPeriode">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set minimum date ke hari ini untuk semua input tanggal
        const today = new Date();
        const todayStr = today.toISOString().split('T')[0];
        
        // ===== VALIDASI MODAL TAMBAH PERIODE =====
        const modalTambah = document.getElementById('tambahPeriodeModal');
        const tanggalMulaiTambah = document.getElementById('tanggal_mulai_tambah');
        const tanggalAkhirTambah = document.getElementById('tanggal_akhir_tambah');
        const formTambah = tanggalMulaiTambah?.closest('form');
        
        if (tanggalMulaiTambah && tanggalAkhirTambah) {
            // Set minimum date
            tanggalMulaiTambah.setAttribute('min', todayStr);
            tanggalAkhirTambah.setAttribute('min', todayStr);
            
            function validateTambahPeriode() {
                const tanggalMulai = tanggalMulaiTambah.value;
                const tanggalAkhir = tanggalAkhirTambah.value;
                
                // Clear previous custom validity
                tanggalMulaiTambah.setCustomValidity('');
                tanggalAkhirTambah.setCustomValidity('');
                
                // Validasi tanggal mulai tidak boleh sebelum hari ini
                if (tanggalMulai && tanggalMulai < todayStr) {
                    tanggalMulaiTambah.setCustomValidity('Tanggal mulai tidak boleh sebelum hari ini.');
                    return false;
                }
                
                // Validasi tanggal akhir tidak boleh sebelum hari ini
                if (tanggalAkhir && tanggalAkhir < todayStr) {
                    tanggalAkhirTambah.setCustomValidity('Tanggal selesai tidak boleh sebelum hari ini.');
                    return false;
                }
                
                // Validasi tanggal akhir tidak boleh sebelum tanggal mulai
                if (tanggalMulai && tanggalAkhir && tanggalAkhir < tanggalMulai) {
                    tanggalAkhirTambah.setCustomValidity('Tanggal selesai tidak boleh sebelum tanggal mulai.');
                    return false;
                }
                
                return true;
            }
            
            tanggalMulaiTambah.addEventListener('change', function() {
                validateTambahPeriode();
                if (this.validity.customError) {
                    this.classList.add('is-invalid');
                    showErrorMessage(this, this.validationMessage);
                } else {
                    this.classList.remove('is-invalid');
                    removeErrorMessage(this);
                }
            });
            
            tanggalAkhirTambah.addEventListener('change', function() {
                validateTambahPeriode();
                if (this.validity.customError) {
                    this.classList.add('is-invalid');
                    showErrorMessage(this, this.validationMessage);
                } else {
                    this.classList.remove('is-invalid');
                    removeErrorMessage(this);
                }
            });
            
            formTambah?.addEventListener('submit', function(e) {
                if (!validateTambahPeriode()) {
                    e.preventDefault();
                    
                    if (tanggalMulaiTambah.validity.customError) {
                        tanggalMulaiTambah.classList.add('is-invalid');
                        showErrorMessage(tanggalMulaiTambah, tanggalMulaiTambah.validationMessage);
                    }
                    
                    if (tanggalAkhirTambah.validity.customError) {
                        tanggalAkhirTambah.classList.add('is-invalid');
                        showErrorMessage(tanggalAkhirTambah, tanggalAkhirTambah.validationMessage);
                    }
                    
                    return false;
                }
            });
        }
        
        // ===== VALIDASI MODAL EDIT PERIODE =====
        document.querySelectorAll('[id^="editPeriodeModal"]').forEach(function(modalEdit) {
            const form = modalEdit.querySelector('form');
            const tanggalMulaiEdit = form?.querySelector('input[name="tanggal_mulai"]');
            const tanggalAkhirEdit = form?.querySelector('input[name="tanggal_akhir"]');
            
            if (tanggalMulaiEdit && tanggalAkhirEdit) {
                // Set minimum date
                tanggalMulaiEdit.setAttribute('min', todayStr);
                tanggalAkhirEdit.setAttribute('min', todayStr);
                
                function validateEditPeriode() {
                    const tanggalMulai = tanggalMulaiEdit.value;
                    const tanggalAkhir = tanggalAkhirEdit.value;
                    
                    // Clear previous custom validity
                    tanggalMulaiEdit.setCustomValidity('');
                    tanggalAkhirEdit.setCustomValidity('');
                    
                    // Validasi tanggal mulai tidak boleh sebelum hari ini
                    if (tanggalMulai && tanggalMulai < todayStr) {
                        tanggalMulaiEdit.setCustomValidity('Tanggal mulai tidak boleh sebelum hari ini.');
                        return false;
                    }
                    
                    // Validasi tanggal akhir tidak boleh sebelum hari ini
                    if (tanggalAkhir && tanggalAkhir < todayStr) {
                        tanggalAkhirEdit.setCustomValidity('Tanggal selesai tidak boleh sebelum hari ini.');
                        return false;
                    }
                    
                    // Validasi tanggal akhir tidak boleh sebelum tanggal mulai
                    if (tanggalMulai && tanggalAkhir && tanggalAkhir < tanggalMulai) {
                        tanggalAkhirEdit.setCustomValidity('Tanggal selesai tidak boleh sebelum tanggal mulai.');
                        return false;
                    }
                    
                    return true;
                }
                
                tanggalMulaiEdit.addEventListener('change', function() {
                    validateEditPeriode();
                    if (this.validity.customError) {
                        this.classList.add('is-invalid');
                        showErrorMessage(this, this.validationMessage);
                    } else {
                        this.classList.remove('is-invalid');
                        removeErrorMessage(this);
                    }
                });
                
                tanggalAkhirEdit.addEventListener('change', function() {
                    validateEditPeriode();
                    if (this.validity.customError) {
                        this.classList.add('is-invalid');
                        showErrorMessage(this, this.validationMessage);
                    } else {
                        this.classList.remove('is-invalid');
                        removeErrorMessage(this);
                    }
                });
                
                form.addEventListener('submit', function(e) {
                    if (!validateEditPeriode()) {
                        e.preventDefault();
                        
                        if (tanggalMulaiEdit.validity.customError) {
                            tanggalMulaiEdit.classList.add('is-invalid');
                            showErrorMessage(tanggalMulaiEdit, tanggalMulaiEdit.validationMessage);
                        }
                        
                        if (tanggalAkhirEdit.validity.customError) {
                            tanggalAkhirEdit.classList.add('is-invalid');
                            showErrorMessage(tanggalAkhirEdit, tanggalAkhirEdit.validationMessage);
                        }
                        
                        return false;
                    }
                });
            }
        });
        
        // Helper functions untuk menampilkan error message
        function showErrorMessage(inputElement, message) {
            removeErrorMessage(inputElement);
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            errorDiv.style.display = 'block';
            errorDiv.textContent = message;
            errorDiv.setAttribute('data-custom-error', 'true');
            
            inputElement.parentNode.appendChild(errorDiv);
        }
        
        function removeErrorMessage(inputElement) {
            const customError = inputElement.parentNode.querySelector('[data-custom-error="true"]');
            if (customError) {
                customError.remove();
            }
        }
    });
</script>

@endsection
