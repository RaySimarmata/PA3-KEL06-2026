<!-- Header Card -->
<div class="filter-card mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1" style="font-weight: 600; color: #333;">Reminder Kaprodi Review Soal</h5>
            <p class="text-muted mb-0" style="font-size: 0.875rem;">Kirimkan reminder kepada Kepala Program Studi untuk review soal ujian</p>
        </div>
    </div>
</div>

<form id="reminderFormSoal">
    @csrf
    
    <!-- Table Card -->
    <div class="monitoring-card mb-4">
        <div class="monitoring-header">
            <i class="bi bi-clipboard-check"></i>
            <h6>Pilih Kaprodi</h6>
            <div style="margin-left: auto; display: flex; gap: 0.5rem;">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllSoal()">
                    Pilih Semua
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllSoal()">
                    Batal Pilih
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-monitoring">
                <thead>
                    <tr>
                        <th style="width: 5%;">
                            <input type="checkbox" class="form-check-input" id="selectAllCheckboxSoal"
                                onchange="toggleAllSoal(this)">
                        </th>
                        <th style="width: 5%;">No</th>
                        <th style="width: 30%;">Nama Kaprodi</th>
                        <th style="width: 30%;">Email</th>
                        <th style="width: 20%;">Program Studi</th>
                        <th style="width: 10%;" class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dosenKaprodi as $index => $dosen)
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input dosen-checkbox-soal"
                                    name="dosen_ids[]" value="{{ $dosen->id }}">
                            </td>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="dosen-name">{{ $dosen->nama_lengkap }}</td>
                            <td class="text-secondary">{{ $dosen->kontak_email }}</td>
                            <td>
                                @if($dosen->prodi)
                                    <span class="badge-gkm info">{{ $dosen->prodi->nama_prodi }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="status-icon success">
                                    <i class="bi bi-check-lg"></i>
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <p>Tidak ada data Kaprodi</p>
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
                <input type="text" class="form-control" name="subject" id="subjectSoal"
                    value="Reminder: Review Soal Ujian" required>
            </div>

            <div class="mb-3">
                <label class="filter-label">Isi Pesan <span class="text-danger">*</span></label>
                <textarea class="form-control" name="message" id="messageSoal" rows="12" required></textarea>
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i>
                    Klik "Generate Pesan AI" untuk membuat pesan otomatis yang sopan dan profesional
                </small>
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" onclick="generateMessageSoal()">
                    <i class="bi bi-magic"></i> Generate Pesan AI
                </button>
                <button type="button" class="btn btn-success" onclick="sendReminderSoal()">
                    <i class="bi bi-send"></i> Kirim Reminder
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="previewMessageSoal()">
                    <i class="bi bi-eye"></i> Preview
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Modal Preview -->
<div class="modal fade" id="previewModalSoal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview Pesan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong>Subjek:</strong>
                    <p id="previewSubjectSoal" class="mb-0"></p>
                </div>
                <hr>
                <div>
                    <strong>Isi Pesan:</strong>
                    <pre id="previewMessageSoal" class="mt-2" style="white-space: pre-wrap; font-family: inherit;"></pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAllSoal(checkbox) {
    const checkboxes = document.querySelectorAll('.dosen-checkbox-soal');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

function selectAllSoal() {
    const checkboxes = document.querySelectorAll('.dosen-checkbox-soal');
    checkboxes.forEach(cb => cb.checked = true);
    document.getElementById('selectAllCheckboxSoal').checked = true;
}

function deselectAllSoal() {
    const checkboxes = document.querySelectorAll('.dosen-checkbox-soal');
    checkboxes.forEach(cb => cb.checked = false);
    document.getElementById('selectAllCheckboxSoal').checked = false;
}

function generateMessageSoal() {
    const selectedDosen = document.querySelectorAll('.dosen-checkbox-soal:checked');
    
    if (selectedDosen.length === 0) {
        alert('Pilih minimal 1 kaprodi terlebih dahulu');
        return;
    }

    const dosenIds = Array.from(selectedDosen).map(cb => cb.value);
    const messageTextarea = document.getElementById('messageSoal');
    messageTextarea.value = 'Generating pesan dengan AI Agent...\nMohon tunggu...';
    messageTextarea.disabled = true;

    fetch('{{ route("gkm.monitoring-perkuliahan.soal.generate") }}', {
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

function previewMessageSoal() {
    const subject = document.getElementById('subjectSoal').value;
    const message = document.getElementById('messageSoal').value;
    
    if (!subject || !message) {
        alert('Subjek dan pesan harus diisi');
        return;
    }
    
    document.getElementById('previewSubjectSoal').textContent = subject;
    document.getElementById('previewMessageSoal').textContent = message;
    
    const modal = new bootstrap.Modal(document.getElementById('previewModalSoal'));
    modal.show();
}

function sendReminderSoal() {
    const selectedDosen = document.querySelectorAll('.dosen-checkbox-soal:checked');
    
    if (selectedDosen.length === 0) {
        alert('Pilih minimal 1 kaprodi terlebih dahulu');
        return;
    }
    
    const subject = document.getElementById('subjectSoal').value;
    const message = document.getElementById('messageSoal').value;
    
    if (!subject || !message) {
        alert('Subjek dan pesan harus diisi');
        return;
    }
    
    if (!confirm(`Kirim reminder ke ${selectedDosen.length} kaprodi?`)) {
        return;
    }
    
    const dosenIds = Array.from(selectedDosen).map(cb => cb.value);
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("gkm.monitoring-perkuliahan.soal.send") }}';
    
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
