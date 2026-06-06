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
                    Kirim Pesan Pengingat
                </h5>
                <p class="text-muted mb-0 small">Pilih jenis reminder dan kirim ke dosen terkait</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('gkm.monitoring-perkuliahan.index') }}" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="monitoring-card mb-4">
        {{-- <div class="monitoring-header">
            <ul class="nav nav-tabs" id="reminderTabs" role="tablist" style="border-bottom: none; margin-bottom: -1px;">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="materi-tab" data-bs-toggle="tab" data-bs-target="#materi"
                        type="button" role="tab" data-tab="materi">
                        <i class="bi bi-file-earmark-text"></i> Reminder Upload Materi
                    </button>
                </li>
                {{-- <li class="nav-item" role="presentation">
                    <button class="nav-link" id="soal-tab" data-bs-toggle="tab" data-bs-target="#soal" type="button" role="tab" data-tab="soal">
                        <i class="bi bi-clipboard-check"></i> Reminder Review Soal
                    </button>
                </li> --}}
            {{-- </ul> --}}
        {{-- </div> --}} 


        <div class="tab-content p-0" id="reminderTabContent">
            <!-- Tab Materi -->
            <div class="tab-pane fade show active" id="materi" role="tabpanel">
                <div id="materiContent" class="p-3">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Memuat data dosen yang belum upload materi...</p>
                    </div>
                </div>

                
            </div>

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

<style>
    .nav-tabs .nav-link {
        transition: all 0.3s ease;
        font-weight: 500;
        color: #6c757d;
        border: none;
        padding: 0.75rem 1.5rem;
    }
    .nav-tabs .nav-link:hover {
        color: #5B9BD5 !important;
        background-color: #f8f9fa;
    }
    .nav-tabs .nav-link.active {
        color: #5B9BD5 !important;
        background-color: transparent;
        border-bottom: 3px solid #5B9BD5 !important;
        font-weight: 600 !important;
    }
    .nav-tabs .nav-link i {
        margin-right: 0.5rem;
    }
    .filter-card {
        background: #fff;
        border-radius: 12px;
        padding: 1rem 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border: 1px solid #e9ecef;
    }
    /* Pastikan konten tidak overflow horizontal */
    .tab-content {
        overflow-x: auto;
    }
    .table-responsive {
        overflow-x: auto;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Load materi tab pertama kali
        loadTabContent('materi', '{{ route("gkm.monitoring-perkuliahan.materi") }}');

        // Pasang event listener untuk tab switch
        const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
        tabButtons.forEach(button => {
            button.addEventListener('shown.bs.tab', function(e) {
                const targetId = e.target.getAttribute('data-bs-target').replace('#', '');
                const url = targetId === 'materi' ? '{{ route("gkm.monitoring-perkuliahan.materi") }}' : '{{ route("gkm.monitoring-perkuliahan.soal") }}';
                loadTabContent(targetId, url);
            });
        });

        function loadTabContent(tabId, url) {
            const container = document.getElementById(`${tabId}Content`);
            // Jika sudah pernah dimuat dan bukan karena refresh paksa, lewati
            if (container && container.hasAttribute('data-loaded')) {
                return;
            }
            if (!container) return;

            // Tampilkan loader jika container masih kosong
            if (!container.querySelector('.spinner-border')) {
                container.innerHTML = `
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Memuat data...</p>
                    </div>
                `;
            }

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => {
                if (!response.ok) throw new Error('Gagal memuat data');
                return response.text();
            })
            .then(html => {
                container.innerHTML = html;
                container.setAttribute('data-loaded', 'true');
                // Menjalankan ulang script yang mungkin ada di dalam partial (misal untuk tombol kirim)
                executeScripts(container);
            })
            .catch(error => {
                container.innerHTML = `<div class="alert alert-danger m-3">Gagal memuat data: ${error.message}</div>`;
            });
        }

        // Fungsi untuk mengeksekusi script yang ada di dalam konten yang dimuat via AJAX
        function executeScripts(container) {
            const scripts = container.querySelectorAll('script');
            scripts.forEach(script => {
                const newScript = document.createElement('script');
                if (script.src) {
                    newScript.src = script.src;
                    newScript.async = false;
                } else {
                    newScript.textContent = script.textContent;
                }
                document.body.appendChild(newScript);
                script.remove();
            });
        }
    });

    // Menangkap pesan dari dalam partial (misal jika ada event postMessage dari dalam form)
    window.addEventListener('message', function(event) {
        // Pastikan event berasal dari halaman yang sama (bukan iframe)
        if (event.data && (event.data.type === 'success' || event.data.type === 'error')) {
            const alertClass = event.data.type === 'success' ? 'alert-success' : 'alert-danger';
            const iconClass = event.data.type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show mb-4" role="alert">
                    <i class="bi ${iconClass} me-2"></i> ${event.data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            const container = document.querySelector('[style*="padding: 1.5rem"]');
            const existingAlert = container.querySelector('.alert');
            if (existingAlert && existingAlert.innerText.includes(event.data.message)) return;
            container.insertAdjacentHTML('afterbegin', alertHtml);
            setTimeout(() => {
                const alert = container.querySelector('.alert');
                if (alert && alert.innerText.includes(event.data.message)) alert.remove();
            }, 5000);
        }
    });
</script>
@endsection