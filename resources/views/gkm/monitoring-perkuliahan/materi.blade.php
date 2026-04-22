<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reminder Upload Materi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="{{ asset('css/gkm-style.css') }}" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body style="background-color: #f8f9fa; margin: 0; padding: 1rem;">
    <div>
    @if (session('success'))
        <script>
            window.parent.postMessage({
                type: 'success',
                message: '{{ session("success") }}'
            }, '*');
        </script>
    @endif

    @if (session('error'))
        <script>
            window.parent.postMessage({
                type: 'error',
                message: '{{ session("error") }}'
            }, '*');
        </script>
    @endif

    <!-- Header Card -->
    <div class="filter-card mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1" style="font-weight: 600; color: #333;">Reminder Upload Materi di CIS</h5>
                <p class="text-muted mb-0" style="font-size: 0.875rem;">
                    <i class="bi bi-info-circle"></i> 
                    Data dosen yang belum upload materi <strong>Teori dan Praktikum</strong> untuk 
                    <strong>semua semester (Ganjil & Genap)</strong> dan 
                    <strong>semua tingkat (1-4)</strong> berdasarkan hasil monitoring perkuliahan
                </p>
            </div>
        </div>
    </div>

<form id="reminderFormMateri">
    @csrf
    
    <!-- Table Card -->
    <div class="monitoring-card mb-4">
        <div class="monitoring-header">
            <i class="bi bi-file-earmark-text"></i>
            <h6>Pilih Dosen</h6>
            <div style="margin-left: auto; display: flex; gap: 0.5rem;">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllMateri()">
                    Pilih Semua
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllMateri()">
                    Batal Pilih
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-monitoring">
                <thead>
                    <tr>
                        <th style="width: 4%;">
                            <input type="checkbox" class="form-check-input" id="selectAllCheckboxMateri"
                                onchange="toggleAllMateri(this)">
                        </th>
                        <th style="width: 4%;">No</th>
                        <th style="width: 20%;">Nama Dosen</th>
                        <th style="width: 20%;">Email</th>
                        <th style="width: 25%;">Mata Kuliah</th>
                        <th style="width: 12%;" class="text-center">Jenis Materi</th>
                        <th style="width: 8%;" class="text-center">Semester</th>
                        <th style="width: 7%;" class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dosenMateri as $index => $dosen)
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input dosen-checkbox-materi"
                                    name="dosen_ids[]" value="{{ $dosen['pegawai_id'] }}">
                            </td>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="dosen-name">{{ $dosen['nama_lengkap'] }}</td>
                            <td class="text-secondary">{{ $dosen['kontak_email'] }}</td>
                            <td>
                                @if($dosen['nama_matkul'])
                                    <div class="mb-1">
                                        <strong>{{ $dosen['nama_matkul'] }}</strong>
                                    </div>
                                    @if(isset($dosen['kode_mk']))
                                        <small class="text-muted">
                                            <i class="bi bi-tag"></i> {{ $dosen['kode_mk'] }}
                                            @if(isset($dosen['tingkat']))
                                                | Tingkat {{ $dosen['tingkat'] }}
                                            @endif
                                        </small>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(isset($dosen['jenis']))
                                    @if($dosen['jenis'] == 'Materi Teori')
                                        <span class="badge bg-primary">
                                            <i class="bi bi-book"></i> Teori
                                        </span>
                                    @else
                                        <span class="badge bg-info">
                                            <i class="bi bi-laptop"></i> Praktikum
                                        </span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(isset($dosen['semester']))
                                    <span class="badge bg-secondary">
                                        {{ $dosen['semester'] == '1' ? 'Ganjil' : 'Genap' }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="status-icon danger" title="Belum upload materi">
                                    <i class="bi bi-x-lg"></i>
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-check-circle" style="color: #28a745;"></i>
                                    <p>Semua dosen sudah upload materi</p>
                                    <small class="text-muted">Tidak ada dosen yang perlu diingatkan untuk semua semester (Ganjil & Genap) dan semua tingkat (1-4)</small>
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
                <input type="text" class="form-control" name="subject" id="subjectMateri"
                    value="Reminder: Upload Materi Perkuliahan di CIS" required>
            </div>

            <div class="mb-3">
                <label class="filter-label">Isi Pesan <span class="text-danger">*</span></label>
                <textarea class="form-control" name="message" id="messageMateri" rows="12" required></textarea>
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i>
                    Klik "Generate Pesan AI" untuk membuat pesan otomatis yang sopan dan profesional
                </small>
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" onclick="generateMessageMateri()">
                    <i class="bi bi-magic"></i> Generate Pesan AI
                </button>
                <button type="button" class="btn btn-success" onclick="sendReminderMateri()">
                    <i class="bi bi-send"></i> Kirim Reminder
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="previewMessageMateri()">
                    <i class="bi bi-eye"></i> Preview
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Modal Preview -->
<div class="modal fade" id="previewModalMateri" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview Pesan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong>Subjek:</strong>
                    <p id="previewSubjectMateri" class="mb-0"></p>
                </div>
                <hr>
                <div>
                    <strong>Isi Pesan:</strong>
                    <pre id="previewMessageMateri" class="mt-2" style="white-space: pre-wrap; font-family: inherit;"></pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
function toggleAllMateri(checkbox) {
    const checkboxes = document.querySelectorAll('.dosen-checkbox-materi');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

function selectAllMateri() {
    const checkboxes = document.querySelectorAll('.dosen-checkbox-materi');
    checkboxes.forEach(cb => cb.checked = true);
    document.getElementById('selectAllCheckboxMateri').checked = true;
}

function deselectAllMateri() {
    const checkboxes = document.querySelectorAll('.dosen-checkbox-materi');
    checkboxes.forEach(cb => cb.checked = false);
    document.getElementById('selectAllCheckboxMateri').checked = false;
}

function generateMessageMateri() {
    const selectedDosen = document.querySelectorAll('.dosen-checkbox-materi:checked');
    
    if (selectedDosen.length === 0) {
        alert('Pilih minimal 1 dosen terlebih dahulu');
        return;
    }

    const dosenIds = Array.from(selectedDosen).map(cb => cb.value);
    const messageTextarea = document.getElementById('messageMateri');
    messageTextarea.value = 'Generating pesan dengan AI Agent...\nMohon tunggu...';
    messageTextarea.disabled = true;

    fetch('{{ route("gkm.monitoring-perkuliahan.materi.generate") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ dosen_ids: dosenIds })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageTextarea.value = data.message;
        } else {
            alert('Gagal generate pesan');
            messageTextarea.value = '';
        }
        messageTextarea.disabled = false;
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat generate pesan');
        messageTextarea.value = '';
        messageTextarea.disabled = false;
    });
}

function previewMessageMateri() {
    const subject = document.getElementById('subjectMateri').value;
    const message = document.getElementById('messageMateri').value;
    
    if (!subject || !message) {
        alert('Subjek dan pesan harus diisi');
        return;
    }
    
    document.getElementById('previewSubjectMateri').textContent = subject;
    document.getElementById('previewMessageMateri').textContent = message;
    
    const modal = new bootstrap.Modal(document.getElementById('previewModalMateri'));
    modal.show();
}

function sendReminderMateri() {
    const selectedDosen = document.querySelectorAll('.dosen-checkbox-materi:checked');
    
    if (selectedDosen.length === 0) {
        alert('Pilih minimal 1 dosen terlebih dahulu');
        return;
    }
    
    const subject = document.getElementById('subjectMateri').value;
    const message = document.getElementById('messageMateri').value;
    
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
    form.action = '{{ route("gkm.monitoring-perkuliahan.materi.send") }}';
    
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
    subjectInput.name = 'subjek';
    subjectInput.value = subject;
    form.appendChild(subjectInput);
    
    const messageInput = document.createElement('input');
    messageInput.type = 'hidden';
    messageInput.name = 'pesan';
    messageInput.value = message;
    form.appendChild(messageInput);
    
    document.body.appendChild(form);
    form.submit();
}
    </script>
</body>
</html>
