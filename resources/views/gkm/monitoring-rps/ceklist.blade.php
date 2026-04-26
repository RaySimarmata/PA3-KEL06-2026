@extends('layouts.app')

@section('page-title', 'Kirim Reminder RPS')

@section('content')
    <div style="padding: 1.5rem;">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Header Card -->
        <div class="filter-card mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 d-flex align-items-center gap-2" style="font-weight: 600; color: #333;">
                        <i class="bi bi-send-fill" style="color: #5B9BD5;"></i>
                        Kirim Reminder Upload RPS
                    </h5>
                    <p class="text-muted mb-0" style="font-size: 0.875rem;">Pilih dosen yang akan dikirim reminder</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('gkm.monitoring-rps.history') }}" class="btn btn-outline-primary">
                        <i class="bi bi-clock-history"></i> History Reminder
                    </a>
                    <a href="{{ route('gkm.monitoring-rps.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <form id="reminderForm">
            @csrf

            <!-- Table Card -->
            <div class="monitoring-card mb-4">
                <div class="monitoring-header">
                    <i class="bi bi-people"></i>
                    <h6>Pilih Dosen</h6>
                    <input type="text" id="searchInput" class="form-control w-25"
        placeholder="masukkan kata kunci ....."
        value="{{ request('search') }}">
                    <div style="margin-left: auto; display: flex; gap: 0.5rem;">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">
                            Pilih Semua
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">
                            Batal Pilih
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-monitoring">
                        <thead>
                            <tr>
                                <th style="width: 5%;">
                                    <input type="checkbox" class="form-check-input" id="selectAllCheckbox"
                                        onchange="toggleAll(this)">
                                </th>
                                <th style="width: 5%;">No</th>
                                <th style="width: 25%;">Nama Dosen</th>
                                <th style="width: 25%;">Email</th>
                                <th style="width: 30%;">Mata Kuliah</th>
                                <th style="width: 10%;" class="text-center">Status RPS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dosenList as $index => $dosen)
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input dosen-checkbox" name="dosen_ids[]"
                                            value="{{ $dosen->id }}">
                                    </td>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td class="dosen-name">{{ $dosen->nama_lengkap }}</td>
                                    <td class="text-secondary">{{ $dosen->kontak_email }}</td>
                                    <td>
                                        @if ($dosen->nama_matkul)
                                            <span class="badge-gkm info">{{ $dosen->nama_matkul }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="status-icon warning">
                                            <i class="bi bi-clock"></i>
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="bi bi-inbox"></i>
                                            <p>Tidak ada data dosen</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Template Pesan Card -->
            <div class="monitoring-card">
                <div class="monitoring-header">
                    <i class="bi bi-envelope"></i>
                    <h6>Template Pesan Reminder</h6>
                </div>
                <div style="padding: 1.5rem;">
                    <div class="mb-3">
                        <label class="filter-label">Subjek Email <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="subject" id="subject"
                            value="Reminder: Upload RPS Semester Ini" required>
                    </div>

                    <div class="mb-3">
                        <label class="filter-label">Isi Pesan <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="message" id="message" rows="12" required></textarea>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i>
                            Klik "Generate Pesan AI" untuk membuat pesan otomatis yang sopan dan profesional
                        </small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" onclick="generateMessage()">
                            <i class="bi bi-magic"></i> Generate Pesan AI
                        </button>
                        <button type="button" class="btn btn-success" onclick="sendReminder()">
                            <i class="bi bi-send"></i> Kirim Reminder
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="previewMessage()">
                            <i class="bi bi-eye"></i> Preview
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Modal Preview -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Preview Pesan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>Subjek:</strong>
                        <p id="previewSubject" class="mb-0"></p>
                    </div>
                    <hr>
                    <div>
                        <strong>Isi Pesan:</strong>
                        <pre id="previewMessage" class="mt-2" style="white-space: pre-wrap; font-family: inherit;"></pre>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let timeout = null;
        document.getElementById('searchInput').addEventListener('keyup', function () {
            clearTimeout(timeout);

            timeout = setTimeout(() => {
                const url = new URL(window.location.href);
                url.searchParams.set('search', this.value);
                window.location.href = url.toString();
            }, 400);
        });
        function toggleAll(checkbox) {
            const checkboxes = document.querySelectorAll('.dosen-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }

        function selectAll() {
            const checkboxes = document.querySelectorAll('.dosen-checkbox');
            checkboxes.forEach(cb => cb.checked = true);
            document.getElementById('selectAllCheckbox').checked = true;
        }

        function deselectAll() {
            const checkboxes = document.querySelectorAll('.dosen-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
            document.getElementById('selectAllCheckbox').checked = false;
        }

        function generateMessage() {
            const selectedDosen = document.querySelectorAll('.dosen-checkbox:checked');

            if (selectedDosen.length === 0) {
                alert('Pilih minimal 1 dosen terlebih dahulu');
                return;
            }

            const dosenIds = Array.from(selectedDosen).map(cb => cb.value);

            // Show loading
            const messageTextarea = document.getElementById('message');
            const originalValue = messageTextarea.value;
            messageTextarea.value = 'Generating pesan dengan AI Agent...\nMohon tunggu...';
            messageTextarea.disabled = true;

            // Disable button
            const generateBtn = event.target;
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';

            fetch('{{ route('gkm.monitoring-rps.generate-message') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        dosen_ids: dosenIds
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.message || 'Server error');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        messageTextarea.value = data.message;
                        messageTextarea.disabled = false;
                        generateBtn.disabled = false;
                        generateBtn.innerHTML = '<i class="bi bi-magic"></i> Generate Pesan AI';

                        // Show success notification
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-success alert-dismissible fade show';
                        alert.innerHTML = `
                <i class="bi bi-check-circle"></i> Pesan berhasil di-generate oleh AI Agent!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
                        document.querySelector('.card-body').insertBefore(alert, document.querySelector('.card-body')
                            .firstChild);

                        // Auto dismiss after 3 seconds
                        setTimeout(() => alert.remove(), 3000);
                    } else {
                        throw new Error(data.message || 'Gagal generate pesan');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);

                    // Show error alert
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-danger alert-dismissible fade show';
                    alert.innerHTML = `
            <strong><i class="bi bi-exclamation-triangle"></i> Error!</strong><br>
            ${error.message}<br>
            <small>Pastikan AI Agent aktif dan konfigurasi LLM sudah benar di .env</small>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
                    document.querySelector('.card-body').insertBefore(alert, document.querySelector('.card-body')
                        .firstChild);

                    messageTextarea.value = originalValue;
                    messageTextarea.disabled = false;
                    generateBtn.disabled = false;
                    generateBtn.innerHTML = '<i class="bi bi-magic"></i> Generate Pesan AI';
                });
        }

        function previewMessage() {
            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value;

            if (!subject || !message) {
                alert('Subjek dan pesan harus diisi');
                return;
            }

            document.getElementById('previewSubject').textContent = subject;
            document.getElementById('previewMessage').textContent = message;

            const modal = new bootstrap.Modal(document.getElementById('previewModal'));
            modal.show();
        }

        function sendReminder() {
            const selectedDosen = document.querySelectorAll('.dosen-checkbox:checked');

            if (selectedDosen.length === 0) {
                alert('Pilih minimal 1 dosen terlebih dahulu');
                return;
            }

            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value;

            if (!subject || !message) {
                alert('Subjek dan pesan harus diisi');
                return;
            }

            if (!confirm(`Kirim reminder ke ${selectedDosen.length} dosen?`)) {
                return;
            }

            const dosenIds = Array.from(selectedDosen).map(cb => cb.value);

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('gkm.monitoring-rps.send-reminder') }}';

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);

            dosenIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'dosen_ids[]';
                input.value = id;
                form.appendChild(input);
            });

            const subjectInput = document.createElement('input');
            subjectInput.type = 'hidden';
            subjectInput.name = 'subject';
            subjectInput.value = subject;
            form.appendChild(subjectInput);

            const messageInput = document.createElement('input');
            messageInput.type = 'hidden';
            messageInput.name = 'message';
            messageInput.value = message;
            form.appendChild(messageInput);

            document.body.appendChild(form);
            form.submit();
        }
    </script>
    </div>
@endsection
