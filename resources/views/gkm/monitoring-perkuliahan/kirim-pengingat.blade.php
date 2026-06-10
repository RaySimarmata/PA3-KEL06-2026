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
                {{-- <h6 class="mb-0">Pilih Dosen</h6> --}}
                <select id="reminderMode" class="form-select form-select-sm" style="width: 150px;">
    <option value="uts">Reminder UTS</option>
    <option value="uas">Reminder UAS</option>
</select>
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
                            <th style="width: 15%;">Jenis Perkuliahan</th>
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

    document.addEventListener('DOMContentLoaded', function () {

        loadDosenData();

        // Search
        document.getElementById('searchInput').addEventListener('keyup', function () {
            clearTimeout(timeout);

            timeout = setTimeout(() => {
                currentPage = 1;
                loadDosenData();
            }, 400);
        });

        // Filter UTS / UAS
        const reminderMode = document.getElementById('reminderMode');

        if (reminderMode) {
            reminderMode.addEventListener('change', function () {
                currentPage = 1;
                loadDosenData();
            });
        }
    });

    function loadDosenData(page = 1) {

        const search = document.getElementById('searchInput').value;
        const mode = document.getElementById('reminderMode')?.value || 'uts';
        const tbody = document.getElementById('dosenTableBody');

        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5">
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
        url.searchParams.set('mode', mode);

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {

            if (data.dosenList && data.dosenList.data) {
                displayDosenData(data.dosenList);
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-5">
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
                    <td colspan="8" class="text-center py-5">
                        <div class="alert alert-danger m-3">
                            Gagal memuat data: ${error.message}
                        </div>
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
                    <td colspan="8" class="text-center py-5">
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

        let rows = '';

        dosenList.data.forEach((dosen, index) => {

            const no = ((dosenList.current_page - 1) * dosenList.per_page) + index + 1;

            rows += `
                <tr>
                    <td class="text-center">
                        <input
                            type="checkbox"
                            class="form-check-input dosen-checkbox"
                            name="dosen_ids[]"
                            value="${dosen.id}">
                    </td>

                    <td class="text-center">${no}</td>

                    <td class="dosen-name fw-medium">
                        ${dosen.nama || '-'}
                    </td>

                    <td class="text-secondary">
                        ${dosen.email || '-'}
                    </td>

                    <td>
                        ${
                            dosen.matkul
                                ? `<span class="badge-gkm info">${dosen.matkul}</span>`
                                : '<span class="text-muted">-</span>'
                        }
                    </td>

                    <td>
                        ${
                            dosen.jenis
                                ? `<span class="badge bg-info">${dosen.jenis}</span>`
                                : '<span class="text-muted">-</span>'
                        }
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

        paginationInfo.innerHTML =
            `Menampilkan ${dosenList.from || 0} - ${dosenList.to || 0} dari ${dosenList.total || 0} data`;

        let linksHtml = '<nav><ul class="pagination pagination-sm mb-0">';

        if (dosenList.current_page > 1) {
            linksHtml += `
                <li class="page-item">
                    <a class="page-link"
                       href="#"
                       onclick="loadDosenData(${dosenList.current_page - 1}); return false;">
                        «
                    </a>
                </li>
            `;
        } else {
            linksHtml += `
                <li class="page-item disabled">
                    <span class="page-link">«</span>
                </li>
            `;
        }

        for (let i = 1; i <= dosenList.last_page; i++) {

            if (i === dosenList.current_page) {

                linksHtml += `
                    <li class="page-item active">
                        <span class="page-link">${i}</span>
                    </li>
                `;

            } else {

                linksHtml += `
                    <li class="page-item">
                        <a class="page-link"
                           href="#"
                           onclick="loadDosenData(${i}); return false;">
                            ${i}
                        </a>
                    </li>
                `;
            }
        }

        if (dosenList.current_page < dosenList.last_page) {
            linksHtml += `
                <li class="page-item">
                    <a class="page-link"
                       href="#"
                       onclick="loadDosenData(${dosenList.current_page + 1}); return false;">
                        »
                    </a>
                </li>
            `;
        } else {
            linksHtml += `
                <li class="page-item disabled">
                    <span class="page-link">»</span>
                </li>
            `;
        }

        linksHtml += '</ul></nav>';

        paginationLinks.innerHTML = linksHtml;
    }

    function selectAll() {
        document.querySelectorAll('.dosen-checkbox').forEach(checkbox => {
            checkbox.checked = true;
        });
        document.getElementById('selectAllCheckbox').checked = true;
    }

    function deselectAll() {
        document.querySelectorAll('.dosen-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        document.getElementById('selectAllCheckbox').checked = false;
    }

    function toggleAll(checkbox) {
        document.querySelectorAll('.dosen-checkbox').forEach(cb => {
            cb.checked = checkbox.checked;
        });
    }

    function generateMessage() {
        try {
            const dosenCheckboxes = document.querySelectorAll('.dosen-checkbox:checked');
            
            if (dosenCheckboxes.length === 0) {
                alert('Silakan pilih minimal satu dosen');
                return;
            }

            const dosenIds = Array.from(dosenCheckboxes).map(cb => cb.value);

            // Show loading state
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';

            fetch('{{ route("gkm.monitoring-perkuliahan.materi.generate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ dosen_ids: dosenIds })
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Failed to parse JSON:', text);
                        throw new Error('Invalid response format: ' + text.substring(0, 200));
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    document.getElementById('message').value = data.message;
                    // Show success toast
                    showToast('Pesan berhasil di-generate', 'success');
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Gagal generate pesan: ' + error.message);
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });

        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan: ' + error.message);
        }
    }

    function previewMessage() {
        const subject = document.getElementById('subject').value;
        const message = document.getElementById('message').value;

        if (!subject || !message) {
            alert('Subjek dan isi pesan harus diisi');
            return;
        }

        document.getElementById('previewSubject').textContent = subject;
        document.getElementById('previewMessage').textContent = message;

        const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
        previewModal.show();
    }

    function sendReminder() {
        try {
            const dosenCheckboxes = document.querySelectorAll('.dosen-checkbox:checked');
            
            if (dosenCheckboxes.length === 0) {
                alert('Silakan pilih minimal satu dosen');
                return;
            }

            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value;

            if (!subject || !message) {
                alert('Subjek dan isi pesan harus diisi');
                return;
            }

            const dosenIds = Array.from(dosenCheckboxes).map(cb => cb.value);

            // Confirm before sending
            if (!confirm(`Apakah Anda yakin ingin mengirim reminder ke ${dosenIds.length} dosen?`)) {
                return;
            }

            // Show loading state
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengirim...';

            fetch('{{ route("gkm.monitoring-perkuliahan.materi.send") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    dosen_ids: dosenIds,
                    subject: subject,
                    pesan: message
                })
            })
            .then(response => {
                // Log response for debugging
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                // Try to parse as JSON
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Failed to parse JSON:', text);
                        throw new Error('Invalid response format: ' + text.substring(0, 200));
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    alert('Reminder berhasil dikirim ke ' + dosenIds.length + ' dosen');
                    // Clear selection
                    deselectAll();
                    // Reload data
                    currentPage = 1;
                    loadDosenData();
                    // Clear form
                    document.getElementById('message').value = '';
                } else {
                    const errorMsg = data.message || 'Gagal mengirim reminder';
                    alert('Error: ' + errorMsg);
                    if (data.errors && data.errors.length > 0) {
                        console.error('Detailed errors:', data.errors);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Gagal mengirim reminder: ' + error.message);
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });

        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan: ' + error.message);
        }
    }

    function showToast(message, type = 'info') {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-info';
        const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-info-circle-fill';
        
        const toastHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="bi ${icon} me-2"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        const container = document.createElement('div');
        container.innerHTML = toastHtml;
        document.body.appendChild(container.firstElementChild);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            container.firstElementChild?.remove();
        }, 5000);
    }
</script>
@endsection