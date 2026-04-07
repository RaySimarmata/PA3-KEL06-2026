@extends('layouts.app')

@section('page-title', 'Pengaturan Jadwal Reminder')

@section('content')
    <div style="padding: 1.5rem;">
        <div class="alert-gkm info" style="margin-bottom: 1.5rem;">
            <h6 style="margin-bottom: 0.5rem; font-weight: 600;"><i class="bi bi-info-circle"></i> Cara Kerja Reminder Agent</h6>
            <p class="mb-0" style="font-size: 0.9rem;">
                Reminder Agent akan otomatis mengirim email reminder ke dosen sesuai dengan jadwal yang Anda atur.
                Sistem akan menggunakan template pesan yang sudah tersedia di halaman masing-masing tipe reminder.
            </p>
        </div>

        <div class="monitoring-card">
            <div class="monitoring-header">
                <i class="bi bi-calendar-plus"></i>
                <h6>Tambah Jadwal Reminder Baru</h6>
                <div style="margin-left: auto;">
                    <a href="{{ route('gkm.reminder-agent.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
            <div style="padding: 1.5rem;">
                @if (session('success'))
                    <div class="alert-gkm success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert-gkm danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                        <form action="{{ route('gkm.reminder-agent.jadwal.store') }}" method="POST">
                            @csrf

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nama_jadwal" class="form-label">Nama Jadwal <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('nama_jadwal') is-invalid @enderror"
                                        id="nama_jadwal" name="nama_jadwal" value="{{ old('nama_jadwal') }}"
                                        placeholder="Contoh: Reminder RPS Mingguan" required>
                                    @error('nama_jadwal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="tipe_reminder" class="form-label">Tipe Reminder <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select @error('tipe_reminder') is-invalid @enderror"
                                        id="tipe_reminder" name="tipe_reminder" required>
                                        <option value="">Pilih Tipe Reminder</option>
                                        <option value="RPS" {{ old('tipe_reminder') == 'RPS' ? 'selected' : '' }}>
                                            Reminder RPS</option>
                                        <option value="Upload Materi"
                                            {{ old('tipe_reminder') == 'Upload Materi' ? 'selected' : '' }}>Reminder Upload
                                            Materi</option>
                                        <option value="Perwalian"
                                            {{ old('tipe_reminder') == 'Perwalian' ? 'selected' : '' }}>Reminder Perwalian
                                        </option>
                                        <option value="Review Soal"
                                            {{ old('tipe_reminder') == 'Review Soal' ? 'selected' : '' }}>Reminder Review
                                            Soal</option>
                                    </select>
                                    @error('tipe_reminder')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tanggal_kirim" class="form-label">
                                        <i class="bi bi-calendar-event"></i> Tanggal Kirim <span
                                            class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control @error('tanggal_kirim') is-invalid @enderror"
                                        id="tanggal_kirim" name="tanggal_kirim" value="{{ old('tanggal_kirim') }}"
                                        required>
                                    <small class="text-muted">Tanggal pengiriman reminder</small>
                                    @error('tanggal_kirim')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="jam_pengiriman" class="form-label">
                                        <i class="bi bi-clock"></i> Jam Pengiriman <span class="text-danger">*</span>
                                    </label>
                                    <input type="time" class="form-control @error('jam_pengiriman') is-invalid @enderror"
                                        id="jam_pengiriman" name="jam_pengiriman"
                                        value="{{ old('jam_pengiriman', '09:00') }}" required>
                                    <small class="text-muted">Waktu pengiriman reminder (Format 24 jam)</small>
                                    @error('jam_pengiriman')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="alert-gkm info" role="alert">
                                <i class="bi bi-calendar-check"></i>
                                <strong>Pengiriman Otomatis:</strong> Email akan terkirim otomatis pada tanggal <strong
                                    id="preview-tanggal">-</strong> pukul <strong id="preview-jam">-</strong>.
                            </div>

                            <div class="alert-gkm success" role="alert">
                                <i class="bi bi-robot"></i>
                                <strong>AI Agent Aktif:</strong> Sistem akan menggunakan AI untuk menghasilkan pesan
                                reminder yang personal,
                                menyebutkan nama dosen, mata kuliah, dan kelas wali secara otomatis.
                            </div>

                            <script>
                                // Preview tanggal dan jam
                                document.getElementById('tanggal_kirim').addEventListener('change', updatePreview);
                                document.getElementById('jam_pengiriman').addEventListener('change', updatePreview);

                                function updatePreview() {
                                    const tanggal = document.getElementById('tanggal_kirim').value;
                                    const jam = document.getElementById('jam_pengiriman').value;

                                    if (tanggal) {
                                        const date = new Date(tanggal);
                                        const options = {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric'
                                        };
                                        document.getElementById('preview-tanggal').textContent = date.toLocaleDateString('id-ID', options);
                                    }

                                    if (jam) {
                                        document.getElementById('preview-jam').textContent = jam;
                                    }
                                }
                            </script>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('gkm.reminder-agent.index') }}" class="btn btn-secondary">
                                    Batal
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Simpan Jadwal
                                </button>
                            </div>
                        </form>
            </div>
        </div>
    </div>
@endsection
