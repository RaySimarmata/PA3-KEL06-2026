@extends('layouts.app')

@section('page-title', 'Kirim Reminder Upload Materi')

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
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Kirim Reminder Upload Materi di CIS</h5>
                    <p class="text-muted mb-0">Pilih dosen yang akan dikirim reminder untuk upload materi perkuliahan di sistem CIS</p>
                </div>
                <a href="{{ route('gkm.monitoring-perkuliahan.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <form id="reminderForm">
        @csrf
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Pilih Dosen</h6>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">
                            Pilih Semua
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">
                            Batal Pilih
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50">
                                    <input type="checkbox" class="form-check-input" id="selectAllCheckbox" onchange="toggleAll(this)">
                                </th>
                                <th>No</th>
                                <th>Nama Dosen</th>
                                <th>Email</th>
                                <th>Mata Kuliah</th>
                                <th>Status Upload</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dosenList as $index => $dosen)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input dosen-checkbox" 
                                           name="dosen_ids[]" value="{{ $dosen->id }}">
                                </td>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $dosen->nama_lengkap }}</td>
                                <td>{{ $dosen->kontak_email }}</td>
                                <td>
                                    @if($dosen->matakuliah && $dosen->matakuliah->count() > 0)
                                        @foreach($dosen->matakuliah->take(2) as $mk)
                                            <span class="badge bg-secondary">{{ $mk->nama_mk }}</span>
                                        @endforeach
                                        @if($dosen->matakuliah->count() > 2)
                                            <span class="badge bg-light text-dark">+{{ $dosen->matakuliah->count() - 2 }}</span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-warning">Perlu Upload</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">Tidak ada data dosen</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="mb-3">Template Pesan Reminder</h6>
                
                <div class="mb-3">
                    <label class="form-label">Subjek Email <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="subject" id="subject"
                           value="Reminder: Upload Materi Perkuliahan di CIS" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Isi Pesan <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="message" id="message" rows="12" required></textarea>
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> 
                        Klik "Generate Pesan AI" untuk membuat pesan otomatis yang sopan dan profesional
                    </small>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-warning" onclick="generateMessage()">
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
    messageTextarea.value = 'Generating pesan dengan AI...';
    messageTextarea.disabled = true;

    fetch('{{ route("gkm.monitoring-perkuliahan.materi.generate") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            dosen_ids: dosenIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageTextarea.value = data.message;
            messageTextarea.disabled = false;
        } else {
            alert('Gagal generate pesan');
            messageTextarea.value = '';
            messageTextarea.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat generate pesan');
        messageTextarea.value = '';
        messageTextarea.disabled = false;
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
@endsection
