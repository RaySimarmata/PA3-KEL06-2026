@extends('layouts.app')

@section('page-title', 'Pengaturan Jadwal Reminder')

@section('content')
    <div style="padding: 1.5rem;">

        {{-- ALERT --}}
        @if (session('success'))
            <div class="alert-gkm success mb-4">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert-gkm danger mb-4">
                <h6 style="font-weight: 600; margin-bottom: 0.5rem;">
                    <i class="bi bi-exclamation-triangle"></i> Terjadi Kesalahan
                </h6>
                <ul class="mb-0" style="font-size: 0.875rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- HEADER CARD (sama persis dengan style Kirim Reminder RPS) --}}
        <div class="filter-card mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="mb-1 fw-semibold" style="color: #333;">
                        Pengaturan Jadwal Reminder
                    </h5>
                    <p class="text-muted mb-0 small">
                        Atur jadwal pengiriman email reminder secara otomatis.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('gkm.reminder-agent.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        {{-- FORM CARD --}}
        <div class="monitoring-card">
            <div class="monitoring-header">
                <h6 class="mb-0">Tambah Jadwal Reminder Baru</h6>
            </div>

            <div style="padding: 1.5rem;">
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
                            <select class="form-select @error('tipe_reminder') is-invalid @enderror" id="tipe_reminder"
                                name="tipe_reminder" required>
                                <option value="">Pilih Tipe Reminder</option>
                                <option value="RPS" {{ old('tipe_reminder') == 'RPS' ? 'selected' : '' }}>
                                    Reminder RPS
                                </option>
                                <option value="Upload Materi"
                                    {{ old('tipe_reminder') == 'Upload Materi' ? 'selected' : '' }}>
                                    Reminder Upload Materi
                                </option>
                                <option value="Review Soal" {{ old('tipe_reminder') == 'Review Soal' ? 'selected' : '' }}>
                                    Reminder Review Soal
                                </option>
                            </select>
                            @error('tipe_reminder')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tanggal_kirim" class="form-label">
                                Tanggal Kirim <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control @error('tanggal_kirim') is-invalid @enderror"
                                id="tanggal_kirim" name="tanggal_kirim" value="{{ old('tanggal_kirim') }}" required>
                            <small class="text-muted">
                                <i class="bi bi-calendar-event"></i> Tanggal pengiriman reminder
                            </small>
                            @error('tanggal_kirim')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="jam_pengiriman" class="form-label">
                                Jam Pengiriman <span class="text-danger">*</span>
                            </label>
                            <input type="time" class="form-control @error('jam_pengiriman') is-invalid @enderror"
                                id="jam_pengiriman" name="jam_pengiriman" value="{{ old('jam_pengiriman', '09:00') }}"
                                required>
                            <small class="text-muted">
                                <i class="bi bi-clock"></i> Waktu pengiriman reminder (Format 24 jam)
                            </small>
                            @error('jam_pengiriman')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="bi bi-check-circle"></i> Simpan Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tanggalInput = document.getElementById('tanggal_kirim');
            const jamInput = document.getElementById('jam_pengiriman');
            const submitBtn = document.getElementById('submitBtn');
            const form = tanggalInput.closest('form');

            // Set minimum date ke hari ini
            const today = new Date();
            const todayStr = today.toISOString().split('T')[0];
            tanggalInput.setAttribute('min', todayStr);

            function validateDateTime() {
                const selectedDate = tanggalInput.value;
                const selectedTime = jamInput.value;

                if (!selectedDate || !selectedTime) {
                    return true; // Biarkan validasi required HTML handle ini
                }

                const now = new Date();
                const selected = new Date(selectedDate + 'T' + selectedTime);

                // Jika tanggal di masa lalu
                if (selectedDate < todayStr) {
                    tanggalInput.setCustomValidity('Tanggal kirim tidak boleh sebelum hari ini.');
                    return false;
                }

                // Jika tanggal hari ini, cek waktu
                if (selectedDate === todayStr) {
                    if (selected <= now) {
                        jamInput.setCustomValidity('Untuk hari ini, jam pengiriman harus setelah jam sekarang.');
                        return false;
                    }
                }

                // Clear custom validity jika valid
                tanggalInput.setCustomValidity('');
                jamInput.setCustomValidity('');
                return true;
            }

            // Validasi saat input berubah
            tanggalInput.addEventListener('change', function() {
                validateDateTime();
                if (this.validity.customError) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });

            jamInput.addEventListener('change', function() {
                validateDateTime();
                if (this.validity.customError) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });

            // Validasi saat form submit
            form.addEventListener('submit', function(e) {
                if (!validateDateTime()) {
                    e.preventDefault();
                    
                    // Tampilkan pesan error
                    if (tanggalInput.validity.customError) {
                        tanggalInput.classList.add('is-invalid');
                        const feedback = tanggalInput.nextElementSibling;
                        if (feedback && feedback.classList.contains('invalid-feedback')) {
                            feedback.textContent = tanggalInput.validationMessage;
                        } else {
                            const newFeedback = document.createElement('div');
                            newFeedback.className = 'invalid-feedback';
                            newFeedback.style.display = 'block';
                            newFeedback.textContent = tanggalInput.validationMessage;
                            tanggalInput.parentNode.appendChild(newFeedback);
                        }
                    }

                    if (jamInput.validity.customError) {
                        jamInput.classList.add('is-invalid');
                        const feedback = jamInput.nextElementSibling?.nextElementSibling;
                        if (feedback && feedback.classList.contains('invalid-feedback')) {
                            feedback.textContent = jamInput.validationMessage;
                        } else {
                            const newFeedback = document.createElement('div');
                            newFeedback.className = 'invalid-feedback';
                            newFeedback.style.display = 'block';
                            newFeedback.textContent = jamInput.validationMessage;
                            jamInput.parentNode.appendChild(newFeedback);
                        }
                    }

                    return false;
                }
            });
        });
    </script>
@endsection
