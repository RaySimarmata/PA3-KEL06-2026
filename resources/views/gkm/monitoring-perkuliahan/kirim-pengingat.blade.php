@extends('layouts.app')

@section('page-title', 'Kirim Reminder Perkuliahan')

@section('content')
<div style="padding: 1.5rem;">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Header Card -->
    <div class="filter-card mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-1 fw-semibold" style="color: #1e3c72;">
                    Kirim Reminder Upload Materi Perkuliahan
                </h5>
                <p class="text-muted mb-0 small">Pilih dosen yang akan dikirim reminder</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('gkm.monitoring-perkuliahan.index') }}" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <form id="reminderForm">
        @csrf

        <!-- Table Card -->
        <div class="monitoring-card mb-4">
            <div class="monitoring-header d-flex flex-wrap align-items-center gap-2">
                <h6 class="mb-0">Pilih Dosen</h6>
                <div class="ms-auto d-flex gap-2">
                    <!-- Input group dengan ikon search, lebar 300px -->
                    <div class="input-group input-group-sm" style="width: 300px;">
                        <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchInput" class="form-control" 
                               placeholder="Cari nama dosen..." 
                               value="{{ request('search') }}">
                    </div>
                    <!-- Tombol Pilih Semua: biru solid (btn-primary) -->
                    <button type="button" class="btn btn-sm btn-primary" onclick="selectAll()">
                         Pilih Semua
                    </button>
                    <!-- Tombol Batal Pilih: merah solid (btn-danger) -->
                    <button type="button" class="btn btn-sm btn-danger" onclick="deselectAll()">
                        Batal Pilih
                    </button>
                </div>

                
            </div>

<<<<<<< Updated upstream
            <div class="table-responsive">
                <table class="table table-monitoring mb-0">
                    <thead>
                        <tr>
                            <th style="width: 5%;">
                                <input type="checkbox" class="form-check-input" id="selectAllCheckbox" onchange="toggleAll(this)">
                            </th>
                            <th style="width: 5%;">No</th>
                            <th style="width: 25%;">Nama Dosen</th>
                            <th style="width: 25%;">Email</th>
                            <th style="width: 30%;">Mata Kuliah</th>
                            <th style="width: 10%;" class="text-center">Status Upload</th>
                        </tr>
                    </thead>
                    <tbody id="dosenTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="empty-state">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2 mb-0">Memuat data dosen...</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination & Info -->
            <div id="paginationContainer" class="d-flex flex-wrap justify-content-between align-items-center mt-3 px-3 pb-3">
                <div class="text-muted small">
                    <span id="paginationInfo">Memuat data...</span>
                </div>
                <div id="paginationLinks">
                </div>
            </div>
        </div>

        <!-- Template Pesan Card -->
        <div class="monitoring-card">
            <div class="monitoring-header">
                <h6 class="mb-0">Template Pesan Reminder</h6>
            </div>
            <div class="p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Subjek Email <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="subject" id="subject"
                           value="Reminder: Upload Materi Perkuliahan di CIS" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Isi Pesan <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="message" id="message" rows="10" required></textarea>
                    <div class="form-text text-muted mt-1">
                        <i class="bi bi-info-circle"></i> Klik "Generate Pesan" untuk membuat pesan otomatis yang sopan dan profesional
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary" onclick="generateMessage()">
                        <i class="bi bi-magic"></i> Generate Pesan
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
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background: linear-gradient(135deg, #1e3c72, #2a5298);">
                <h5 class="modal-title text-white">
                    <i class="bi bi-eye me-2"></i> Preview Pesan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="fw-semibold text-secondary">Subjek:</label>
                    <p id="previewSubject" class="mt-1 p-2 bg-light rounded"></p>
                </div>
                <hr>
                <div>
                    <label class="fw-semibold text-secondary">Isi Pesan:</label>
                    <pre id="previewMessage" class="mt-1 p-2 bg-light rounded" style="white-space: pre-wrap; font-family: inherit;"></pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
=======
>>>>>>> Stashed changes
        </div>
    </div>
    <div class="reminder-template-section mt-4">
                    <div class="monitoring-card">
                        <div class="monitoring-header">
                            <h6 class="mb-0">Template Pesan Reminder</h6>
                        </div>
                        <div class="p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Subjek Email <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="subject" id="subjectMateri"
                                    value="Reminder: Upload Materi Perkuliahan di CIS" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Isi Pesan <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="message" id="messageMateri" rows="12" required></textarea>
                                <div class="form-text text-muted mt-1">
                                    <i class="bi bi-info-circle"></i>
                                    Klik "Generate Pesan AI" untuk membuat pesan otomatis yang sopan dan profesional
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" id="generateBtnMateri" class="btn btn-primary"
                                    onclick="generateMessageMateri(event)">
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
</div>

<script>
    let timeout = null;
    let currentPage = 1;

    document.addEventListener('DOMContentLoaded', function() {
        loadDosenData();
    });

    document.getElementById('searchInput').addEventListener('keyup', function () {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            currentPage = 1;
            loadDosenData();
        }, 400);
    });

    function loadDosenData(page = 1) {
        const search = document.getElementById('searchInput').value;
        const tbody = document.getElementById('dosenTableBody');
        
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="empty-state">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 mb-0">Memuat data dosen...</p>
                    </div>
                </td>
            </tr>
        `;

        const url = new URL('{{ route("gkm.monitoring-perkuliahan.materi") }}');
        url.searchParams.set('search', search);
        url.searchParams.set('page', page);

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.dosenList && data.dosenList.data) {
                displayDosenData(data.dosenList);
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="empty-state">
                                <i class="bi bi-inbox fs-1 text-muted"></i>
                                <p class="mt-2 mb-0">Tidak ada data dosen</p>
                            </div>
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="alert alert-danger m-3">Gagal memuat data: ${error.message}</div>
                    </td>
                </tr>
            `;
        });
    }

    function displayDosenData(dosenList) {
        const tbody = document.getElementById('dosenTableBody');
        const paginationInfo = document.getElementById('paginationInfo');
        const paginationLinks = document.getElementById('paginationLinks');

        if (dosenList.data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="empty-state">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="mt-2 mb-0">Tidak ada data dosen</p>
                        </div>
                    </td>
                </tr>
            `;
            paginationInfo.innerHTML = 'Menampilkan 0 - 0 dari 0 data';
            paginationLinks.innerHTML = '';
            return;
        }

        // Populate table
        let rows = '';
        dosenList.data.forEach((dosen, index) => {
            const no = (dosenList.current_page - 1) * dosenList.per_page + index + 1;
            rows += `
                <tr>
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input dosen-checkbox" name="dosen_ids[]" value="${dosen.id}">
                    </td>
                    <td class="text-center">${no}</td>
                    <td class="dosen-name fw-medium">${dosen.nama || '-'}</td>
                    <td class="text-secondary">${dosen.email || '-'}</td>
                    <td>
                        ${dosen.matkul ? `<span class="badge-gkm info">${dosen.matkul}</span>` : '<span class="text-muted">-</span>'}
                    </td>
                    <td class="text-center">
                        <span class="status-icon danger">
                            <i class="bi bi-x-lg"></i>
                        </span>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = rows;

        // Update pagination info
        paginationInfo.innerHTML = `Menampilkan ${dosenList.from || 0} - ${dosenList.to || 0} dari ${dosenList.total || 0} data`;

        // Build pagination links
        let linksHtml = '<nav><ul class="pagination pagination-sm mb-0">';
        
        // Previous button
        if (dosenList.current_page > 1) {
            linksHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadDosenData(${dosenList.current_page - 1}); return false;">«</a></li>`;
        } else {
            linksHtml += `<li class="page-item disabled"><span class="page-link">«</span></li>`;
        }

        // Page numbers
        for (let i = 1; i <= dosenList.last_page; i++) {
            if (i === dosenList.current_page) {
                linksHtml += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
            } else {
                linksHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadDosenData(${i}); return false;">${i}</a></li>`;
            }
        }

        // Next button
        if (dosenList.current_page < dosenList.last_page) {
            linksHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadDosenData(${dosenList.current_page + 1}); return false;">»</a></li>`;
        } else {
            linksHtml += `<li class="page-item disabled"><span class="page-link">»</span></li>`;
        }

        linksHtml += '</ul></nav>';
        paginationLinks.innerHTML = linksHtml;
    }

    function toggleAll(checkbox) {
        document.querySelectorAll('.dosen-checkbox').forEach(cb => cb.checked = checkbox.checked);
    }

    function selectAll() {
        document.querySelectorAll('.dosen-checkbox').forEach(cb => cb.checked = true);
        document.getElementById('selectAllCheckbox').checked = true;
    }

    function deselectAll() {
        document.querySelectorAll('.dosen-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('selectAllCheckbox').checked = false;
    }

    function generateMessage() {
        const selected = document.querySelectorAll('.dosen-checkbox:checked');
        if (selected.length === 0) {
            alert('Pilih minimal 1 dosen terlebih dahulu');
            return;
        }
        const dosenIds = Array.from(selected).map(cb => cb.value);
        const messageTextarea = document.getElementById('message');
        const originalValue = messageTextarea.value;
        messageTextarea.value = 'Generating pesan dengan AI Agent...\nMohon tunggu...';
        messageTextarea.disabled = true;
        const btn = event.target;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';

        fetch('{{ route("gkm.monitoring-perkuliahan.materi.generate") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ dosen_ids: dosenIds })
        })
        .then(res => res.ok ? res.json() : res.json().then(err => Promise.reject(err)))
        .then(data => {
            if (data.success) {
                messageTextarea.value = data.message;
                showTemporaryAlert('success', 'Pesan berhasil di-generate oleh AI Agent!');
            } else throw new Error(data.message || 'Gagal generate');
        })
        .catch(err => {
            showTemporaryAlert('danger', err.message);
            messageTextarea.value = originalValue;
        })
        .finally(() => {
            messageTextarea.disabled = false;
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-magic"></i> Generate Pesan';
        });
    }

    function showTemporaryAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show mb-3`;
        alertDiv.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}-fill me-2"></i> ${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        const container = document.querySelector('#reminderForm .monitoring-card:last-child .p-4');
        container.prepend(alertDiv);
        setTimeout(() => alertDiv.remove(), 3000);
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
        new bootstrap.Modal(document.getElementById('previewModal')).show();
    }

    function sendReminder() {
        const selected = document.querySelectorAll('.dosen-checkbox:checked');
        if (selected.length === 0) {
            alert('Pilih minimal 1 dosen terlebih dahulu');
            return;
        }
        const subject = document.getElementById('subject').value;
        const message = document.getElementById('message').value;
        if (!subject || !message) {
            alert('Subjek dan pesan harus diisi');
            return;
        }
        if (!confirm(`Kirim reminder ke ${selected.length} dosen?`)) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("gkm.monitoring-perkuliahan.materi.send") }}';
        form.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}">
                          <input type="hidden" name="subject" value="${subject.replace(/"/g, '&quot;')}">
                          <input type="hidden" name="message" value="${message.replace(/"/g, '&quot;')}">`;
        Array.from(selected).forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'dosen_ids[]';
            input.value = cb.value;
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
    }
</script>
@endsection