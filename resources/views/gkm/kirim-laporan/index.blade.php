@extends('layouts.app')

@section('page-title', 'Kirim Laporan')

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
            <h5 class="mb-1">Pengiriman Laporan</h5>
            <p class="text-muted mb-0">Kelola dan kirim pesan pengingat kepada penerima terkait laporan.</p>
        </div>
    </div>

    <form id="laporanForm">
        @csrf
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h6 class="mb-3">Informasi Penerima</h6>
                
                <div class="mb-3">
                    <label class="form-label">Penerima <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="recipients" id="recipients"
                           placeholder="Contoh: dekan@example.com, kaprodi@example.com" required>
                    <small class="text-muted">
                        Masukkan alamat email penerima. Pisahkan dengan koma untuk beberapa penerima.
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label">CC</label>
                    <input type="text" class="form-control" name="cc" id="cc"
                           placeholder="Contoh: spm@example.com">
                    <small class="text-muted">
                        Masukkan alamat email CC. Pisahkan dengan koma untuk beberapa email.
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Subjek email <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="subject" id="subject"
                           placeholder="Contoh: Pengingat Pengumpulan Laporan Bulanan" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Isi Pesan <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="message" id="message" rows="10" 
                              placeholder="Tuliskan pesan Anda di sini..." required></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Lampiran Berkas</label>
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-outline-primary" onclick="showUploadExternal()">
                            <i class="bi bi-upload"></i> Upload File Eksternal
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="showPilihLaporan()">
                            <i class="bi bi-file-earmark-text"></i> Pilih dari Pelaporan
                        </button>
                    </div>
                    
                    <!-- Upload File Eksternal -->
                    <div id="uploadExternalSection" style="display: none;">
                        <div class="border rounded p-3 mb-3" style="background: #f8f9fa;">
                            <input type="file" class="form-control" name="temp_attachments[]" id="tempAttachments" multiple>
                            <small class="text-muted d-block mt-2">
                                <i class="bi bi-info-circle"></i> Anda dapat melampirkan beberapa file sekaligus
                            </small>
                        </div>
                        <div id="pendingFileList" class="mt-2"></div>
                    </div>
                    
                    <!-- Pilih dari Pelaporan -->
                    <div id="pilihLaporanSection" style="display: none;">
                        <div class="border rounded p-3" style="background: #f8f9fa;">
                            <h6 class="mb-3">Laporan Kuesioner Bulanan</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="50">
                                                <input type="checkbox" class="form-check-input" id="selectAllLaporan" onchange="toggleAllLaporan(this)">
                                            </th>
                                            <th>Periode</th>
                                            <th>Tanggal</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($laporanList as $laporan)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input laporan-checkbox" 
                                                       value="{{ $laporan->id }}"
                                                       data-title="{{ $laporan->bulan }} {{ $laporan->tahun }}"
                                                       onchange="showLaporanValidation()">
                                            </td>
                                            <td>{{ $laporan->bulan }} {{ $laporan->tahun }}</td>
                                            <td>{{ $laporan->created_at->format('d M Y') }}</td>
                                            <td>
                                                <span class="badge bg-success">Selesai</span>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-3 text-muted">
                                                Belum ada laporan yang tersedia
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="bi bi-info-circle"></i> Pilih laporan yang akan dilampirkan dalam email
                            </small>
                        </div>
                        <div id="pendingLaporanList" class="mt-2"></div>
                    </div>
                    
                    <!-- Daftar File yang Sudah Divalidasi -->
                    <div id="validatedFilesSection" class="mt-3" style="display: none;">
                        <div class="border rounded p-3" style="background: #e8f5e9;">
                            <h6 class="mb-3 text-success">
                                <i class="bi bi-check-circle"></i> File yang Akan Dikirim
                            </h6>
                            <div id="validatedFilesList"></div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary" onclick="generateMessage()">
                        <i class="bi bi-magic"></i> Generate Pesan
                    </button>
                    <button type="button" class="btn btn-success" onclick="sendLaporan()">
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
                    <strong>Penerima:</strong>
                    <p id="previewRecipients" class="mb-0"></p>
                </div>
                <div class="mb-3">
                    <strong>CC:</strong>
                    <p id="previewCC" class="mb-0"></p>
                </div>
                <div class="mb-3">
                    <strong>Subjek:</strong>
                    <p id="previewSubject" class="mb-0"></p>
                </div>
                <hr>
                <div>
                    <strong>Isi Pesan:</strong>
                    <pre id="previewMessage" class="mt-2" style="white-space: pre-wrap; font-family: inherit;"></pre>
                </div>
                <div class="mt-3" id="previewAttachments"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// Storage untuk file yang sudah divalidasi
let validatedFiles = {
    external: [], // Array of File objects
    laporan: []   // Array of {id, title}
};

function showUploadExternal() {
    document.getElementById('uploadExternalSection').style.display = 'block';
    document.getElementById('pilihLaporanSection').style.display = 'none';
}

function showPilihLaporan() {
    document.getElementById('uploadExternalSection').style.display = 'none';
    document.getElementById('pilihLaporanSection').style.display = 'block';
}

function toggleAllLaporan(checkbox) {
    const checkboxes = document.querySelectorAll('.laporan-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    showLaporanValidation();
}

// Handle file selection untuk eksternal
document.getElementById('tempAttachments').addEventListener('change', function(e) {
    const pendingList = document.getElementById('pendingFileList');
    pendingList.innerHTML = '';
    
    if (this.files.length > 0) {
        const filesDiv = document.createElement('div');
        filesDiv.className = 'border rounded p-3 bg-light';
        filesDiv.innerHTML = '<h6 class="mb-3">File yang Dipilih - Validasi:</h6>';
        
        Array.from(this.files).forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'd-flex align-items-center justify-content-between p-2 mb-2 bg-white rounded border';
            fileItem.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark text-primary"></i>
                    <div>
                        <div class="small fw-bold">${file.name}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">${formatFileSize(file.size)}</div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-success" onclick="validateExternalFile(${index}, true)">
                        <i class="bi bi-check"></i> Ya
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="validateExternalFile(${index}, false)">
                        <i class="bi bi-x"></i> Tidak
                    </button>
                </div>
            `;
            filesDiv.appendChild(fileItem);
        });
        
        pendingList.appendChild(filesDiv);
    }
});

function validateExternalFile(index, isValid) {
    const fileInput = document.getElementById('tempAttachments');
    const file = fileInput.files[index];
    
    if (isValid) {
        // Tambahkan ke validated files
        validatedFiles.external.push(file);
        updateValidatedFilesDisplay();
    }
    
    // Hapus file dari pending list
    const dt = new DataTransfer();
    Array.from(fileInput.files).forEach((f, i) => {
        if (i !== index) dt.items.add(f);
    });
    fileInput.files = dt.files;
    fileInput.dispatchEvent(new Event('change'));
}

function showLaporanValidation() {
    const selectedCheckboxes = document.querySelectorAll('.laporan-checkbox:checked');
    const pendingList = document.getElementById('pendingLaporanList');
    
    if (selectedCheckboxes.length > 0) {
        const laporanDiv = document.createElement('div');
        laporanDiv.className = 'border rounded p-3 bg-light mt-2';
        laporanDiv.innerHTML = '<h6 class="mb-3">Laporan yang Dipilih - Validasi:</h6>';
        
        selectedCheckboxes.forEach(checkbox => {
            const laporanItem = document.createElement('div');
            laporanItem.className = 'd-flex align-items-center justify-content-between p-2 mb-2 bg-white rounded border';
            laporanItem.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-text text-success"></i>
                    <div>
                        <div class="small fw-bold">Laporan ${checkbox.dataset.title}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">Dari database pelaporan</div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-success" onclick="validateLaporan('${checkbox.value}', '${checkbox.dataset.title}', true, this)">
                        <i class="bi bi-check"></i> Ya
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="validateLaporan('${checkbox.value}', '${checkbox.dataset.title}', false, this)">
                        <i class="bi bi-x"></i> Tidak
                    </button>
                </div>
            `;
            laporanDiv.appendChild(laporanItem);
        });
        
        pendingList.innerHTML = '';
        pendingList.appendChild(laporanDiv);
    } else {
        pendingList.innerHTML = '';
    }
}

function validateLaporan(id, title, isValid, button) {
    const checkbox = document.querySelector(`.laporan-checkbox[value="${id}"]`);
    
    if (isValid) {
        // Tambahkan ke validated laporan
        if (!validatedFiles.laporan.find(l => l.id === id)) {
            validatedFiles.laporan.push({id, title});
            updateValidatedFilesDisplay();
        }
    }
    
    // Uncheck checkbox dan update pending list
    checkbox.checked = false;
    showLaporanValidation();
}

function updateValidatedFilesDisplay() {
    const section = document.getElementById('validatedFilesSection');
    const listDiv = document.getElementById('validatedFilesList');
    
    if (validatedFiles.external.length === 0 && validatedFiles.laporan.length === 0) {
        section.style.display = 'none';
        return;
    }
    
    section.style.display = 'block';
    listDiv.innerHTML = '';
    
    // Display external files
    if (validatedFiles.external.length > 0) {
        const externalDiv = document.createElement('div');
        externalDiv.className = 'mb-3';
        externalDiv.innerHTML = '<strong class="text-success"><i class="bi bi-upload"></i> File Eksternal:</strong>';
        
        const externalList = document.createElement('div');
        externalList.className = 'mt-2';
        
        validatedFiles.external.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'd-flex align-items-center justify-content-between p-2 mb-1 bg-white rounded border';
            fileItem.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark text-primary"></i>
                    <div>
                        <div class="small">${file.name}</div>
                        <div class="text-muted" style="font-size: 0.7rem;">${formatFileSize(file.size)}</div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeValidatedExternal(${index})">
                    <i class="bi bi-trash"></i>
                </button>
            `;
            externalList.appendChild(fileItem);
        });
        
        externalDiv.appendChild(externalList);
        listDiv.appendChild(externalDiv);
    }
    
    // Display laporan files
    if (validatedFiles.laporan.length > 0) {
        const laporanDiv = document.createElement('div');
        laporanDiv.innerHTML = '<strong class="text-success"><i class="bi bi-file-earmark-text"></i> Laporan dari Pelaporan:</strong>';
        
        const laporanList = document.createElement('div');
        laporanList.className = 'mt-2';
        
        validatedFiles.laporan.forEach((laporan, index) => {
            const laporanItem = document.createElement('div');
            laporanItem.className = 'd-flex align-items-center justify-content-between p-2 mb-1 bg-white rounded border';
            laporanItem.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-text text-success"></i>
                    <div class="small">Laporan ${laporan.title}</div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeValidatedLaporan(${index})">
                    <i class="bi bi-trash"></i>
                </button>
            `;
            laporanList.appendChild(laporanItem);
        });
        
        laporanDiv.appendChild(laporanList);
        listDiv.appendChild(laporanDiv);
    }
}

function removeValidatedExternal(index) {
    validatedFiles.external.splice(index, 1);
    updateValidatedFilesDisplay();
}

function removeValidatedLaporan(index) {
    validatedFiles.laporan.splice(index, 1);
    updateValidatedFilesDisplay();
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function generateMessage() {
    const recipients = document.getElementById('recipients').value;
    const subject = document.getElementById('subject').value;
    
    if (!recipients || !subject) {
        alert('Penerima dan subjek harus diisi terlebih dahulu');
        return;
    }

    const messageTextarea = document.getElementById('message');
    const originalValue = messageTextarea.value;
    messageTextarea.value = 'Generating pesan dengan AI Agent...\nMohon tunggu...';
    messageTextarea.disabled = true;

    const generateBtn = event.target;
    const originalBtnHtml = generateBtn.innerHTML;
    generateBtn.disabled = true;
    generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';

    fetch('{{ route("gkm.kirim-laporan.generate") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            recipients: recipients,
            subject: subject
        })
    })
    .then(response => {
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            // Log the actual response for debugging
            return response.text().then(text => {
                console.error('Non-JSON response:', text.substring(0, 500));
                throw new Error('Server mengembalikan response yang tidak valid. Periksa console browser untuk detail.');
            });
        }
        
        if (!response.ok) {
            return response.json().then(err => {
                throw new Error(err.message || 'Server error');
            }).catch(jsonErr => {
                if (jsonErr.message) throw jsonErr;
                throw new Error('Server error: ' + response.status);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            messageTextarea.value = data.message;
            messageTextarea.disabled = false;
            generateBtn.disabled = false;
            generateBtn.innerHTML = originalBtnHtml;
            
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = `
                <i class="bi bi-check-circle"></i> Pesan berhasil di-generate oleh AI Agent!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.card-body').insertBefore(alert, document.querySelector('.card-body').firstChild);
            
            setTimeout(() => alert.remove(), 3000);
        } else {
            throw new Error(data.message || 'Gagal generate pesan');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `
            <strong><i class="bi bi-exclamation-triangle"></i> Error!</strong><br>
            ${error.message}<br>
            <small>Pastikan AI Agent aktif dan konfigurasi LLM sudah benar di .env. Periksa console browser untuk detail.</small>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.card-body').insertBefore(alert, document.querySelector('.card-body').firstChild);
        
        messageTextarea.value = originalValue;
        messageTextarea.disabled = false;
        generateBtn.disabled = false;
        generateBtn.innerHTML = originalBtnHtml;
    });
}

function previewMessage() {
    const recipients = document.getElementById('recipients').value;
    const cc = document.getElementById('cc').value;
    const subject = document.getElementById('subject').value;
    const message = document.getElementById('message').value;
    
    if (!recipients || !subject || !message) {
        alert('Penerima, subjek, dan pesan harus diisi');
        return;
    }
    
    document.getElementById('previewRecipients').textContent = recipients || '-';
    document.getElementById('previewCC').textContent = cc || '-';
    document.getElementById('previewSubject').textContent = subject;
    document.getElementById('previewMessage').textContent = message;
    
    const attachmentsDiv = document.getElementById('previewAttachments');
    let attachmentHtml = '';
    
    if (validatedFiles.external.length > 0) {
        attachmentHtml += '<strong>File Eksternal:</strong><ul class="mt-2">';
        validatedFiles.external.forEach(file => {
            attachmentHtml += `<li>${file.name} (${formatFileSize(file.size)})</li>`;
        });
        attachmentHtml += '</ul>';
    }
    
    if (validatedFiles.laporan.length > 0) {
        attachmentHtml += '<strong>Laporan Terpilih:</strong><ul class="mt-2">';
        validatedFiles.laporan.forEach(laporan => {
            attachmentHtml += `<li>Laporan ${laporan.title}</li>`;
        });
        attachmentHtml += '</ul>';
    }
    
    if (attachmentHtml) {
        attachmentsDiv.innerHTML = attachmentHtml;
    } else {
        attachmentsDiv.innerHTML = '<small class="text-muted">Tidak ada lampiran</small>';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
}

function sendLaporan() {
    const recipients = document.getElementById('recipients').value;
    const subject = document.getElementById('subject').value;
    const message = document.getElementById('message').value;
    
    if (!recipients || !subject || !message) {
        alert('Penerima, subjek, dan pesan harus diisi');
        return;
    }
    
    if (!confirm('Kirim laporan sekarang?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('recipients', recipients);
    formData.append('cc', document.getElementById('cc').value);
    formData.append('subject', subject);
    formData.append('message', message);
    
    // Add validated external files
    validatedFiles.external.forEach(file => {
        formData.append('attachments[]', file);
    });
    
    // Add validated laporan IDs
    validatedFiles.laporan.forEach(laporan => {
        formData.append('laporan_ids[]', laporan.id);
    });
    
    const sendBtn = event.target;
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengirim...';
    
    fetch('{{ route("gkm.kirim-laporan.send") }}', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server mengembalikan response yang tidak valid. Periksa log Laravel untuk detail error.');
        }
        
        if (!response.ok) {
            return response.json().then(err => {
                throw new Error(err.message || 'Server error');
            }).catch(() => {
                throw new Error('Server error: ' + response.status);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Laporan berhasil dikirim!');
            window.location.reload();
        } else {
            throw new Error(data.message || 'Gagal mengirim laporan');
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="bi bi-send"></i> Kirim Reminder';
    });
}
</script>
@endsection
