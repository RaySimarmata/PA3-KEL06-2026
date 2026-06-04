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
                    message: '{{ session('success') }}'
                }, '*');
            </script>
        @endif

        @if (session('error'))
            <script>
                window.parent.postMessage({
                    type: 'error',
                    message: '{{ session('error') }}'
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
                        <strong>semua tingkat </strong> berdasarkan hasil monitoring perkuliahan
                    </p>
                </div>
            </div>
        </div>

        <form id="reminderFormMateri">
            @csrf
            <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
                

                <div class="d-flex gap-2 align-items-center">
                    <label class="mb-0 fw-semibold">MONITORING:</label>
                    <select id="reminderMode" class="form-select form-select-sm" style="width: 160px;"
                        onchange="reloadMateri()">
                        <option value="uts" {{ ($mode ?? 'uts') === 'uts' ? 'selected' : '' }}>Periode UTS</option>
                        <option value="uas" {{ ($mode ?? 'uts') === 'uas' ? 'selected' : '' }}>Periode UAS</option>
                        <option value="force" {{ ($mode ?? '') === 'force' ? 'selected' : '' }}>Force</option>
                    </select>
                </div>
            </div>

            {{-- <!-- Table Card -->
            <div class="monitoring-card mb-4">
                <div class="monitoring-header">
                    <h6>Pilih Dosen</h6>
                    
                    <div style="ms-auto d-flex gap-2">
                        <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control"
                        placeholder="Cari nama dosen, mata kuliah, kode MK..."
                        value="{{ old('search', $search ?? '') }}" onkeyup="debouncedReload()">
                </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllMateri()">
                            Pilih Semua
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllMateri()">
                            Batal Pilih
                        </button>
                    </div>
                </div> --}}

                <div class="monitoring-card mb-4">
            <div class="monitoring-header d-flex flex-wrap align-items-center gap-2">
                <h6 class="mb-0">Pilih Dosen</h6>
                <div class="ms-auto d-flex gap-2">
                    <!-- Input group dengan ikon search, lebar 300px -->
                    <div class="input-group input-group-sm" style="width: 300px;">
                        <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                         <input type="text" id="searchInput" class="form-control"
                        placeholder="Cari nama dosen, mata kuliah, kode MK..."
                        value="{{ old('search', $search ?? '') }}" onkeyup="debouncedReload()">
                    </div>
                    <!-- Tombol Pilih Semua: biru solid (btn-primary) -->
                    <button type="button" class="btn btn-sm btn-primary" onclick="selectAllMateri()">
                         Pilih Semua
                    </button>
                    <!-- Tombol Batal Pilih: abu solid (btn-secondary) -->
                    <button type="button" class="btn btn-sm btn-secondary" onclick="deselectAllMateri()">
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
                                    <td class="text-center">
                                        {{ ($dosenMateri->currentPage() - 1) * $dosenMateri->perPage() + $loop->iteration }}
                                    </td>
                                    <td class="dosen-name">{{ $dosen['nama_lengkap'] }}</td>
                                    <td class="text-secondary">{{ $dosen['kontak_email'] }}</td>
                                    <td>
                                        @if ($dosen['nama_matkul'])
                                            <div class="mb-1">
                                                <strong>{{ $dosen['nama_matkul'] }}</strong>
                                            </div>
                                            @if (isset($dosen['kode_mk']))
                                                <small class="text-muted">
                                                    <i class="bi bi-tag"></i> {{ $dosen['kode_mk'] }}
                                                    @if (isset($dosen['tingkat']))
                                                        | Tingkat {{ $dosen['tingkat'] }}
                                                    @endif
                                                </small>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if (isset($dosen['jenis']))
                                            @if ($dosen['jenis'] == 'Materi Teori')
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
                                        @if (isset($dosen['semester']))
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
                                            <small class="text-muted">Tidak ada dosen yang perlu diingatkan untuk semua
                                                semester (Ganjil & Genap) dan semua tingkat (1-4)</small>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 px-3 pb-3">
                    <div class="text-muted small">
                        Menampilkan {{ $dosenMateri->firstItem() ?? 0 }} - {{ $dosenMateri->lastItem() ?? 0 }} dari
                        {{ $dosenMateri->total() ?? 0 }} data
                    </div>
                    <div>
                        {{ $dosenMateri->withQueryString()->links() }}
                    </div>
                </div>
            </div>

            
        </form>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const materiRoute = '{{ route('gkm.monitoring-perkuliahan.materi') }}';
        let searchDebounceTimer = null;

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

        function generateMessageMateri(event) {
            const selectedDosen = document.querySelectorAll('.dosen-checkbox-materi:checked');
            if (selectedDosen.length === 0) {
                alert('Pilih minimal 1 dosen terlebih dahulu');
                return;
            }

            const dosenIds = Array.from(selectedDosen).map(cb => cb.value);
            const messageTextarea = document.getElementById('messageMateri');
            const generateBtn = event.currentTarget || event.target;
            const originalBtnText = generateBtn.innerHTML;

            messageTextarea.value = 'Generating pesan dengan AI Agent...\nMohon tunggu...';
            messageTextarea.disabled = true;
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';

            fetch('{{ route('gkm.monitoring-perkuliahan.materi.generate') }}', {
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
                    } else {
                        throw new Error(data.message || 'Gagal generate pesan');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert(error.message || 'Terjadi kesalahan saat generate pesan');
                    messageTextarea.value = '';
                })
                .finally(() => {
                    messageTextarea.disabled = false;
                    generateBtn.disabled = false;
                    generateBtn.innerHTML = originalBtnText;
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
            form.action = '{{ route('gkm.monitoring-perkuliahan.materi.send') }}';

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

        function getCurrentSearch() {
            return document.getElementById('searchInput')?.value.trim() || '';
        }

        function getCurrentMode() {
            return document.getElementById('reminderMode')?.value || 'uts';
        }

        function buildMateriUrl(page = 1) {
            const params = new URLSearchParams();
            const mode = getCurrentMode();
            const search = getCurrentSearch();

            if (mode) params.set('mode', mode);
            if (search) params.set('search', search);
            if (page && page > 1) params.set('page', page);

            return `${materiRoute}?${params.toString()}`;
        }

        function executeInlineScripts(container) {
            const scripts = Array.from(container.querySelectorAll('script'));
            scripts.forEach(oldScript => {
                const script = document.createElement('script');
                if (oldScript.src) {
                    script.src = oldScript.src;
                    script.async = false;
                } else {
                    script.textContent = oldScript.textContent;
                }
                document.body.appendChild(script);
                oldScript.remove();
            });
        }

        function renderLoading() {
            const tbody = document.querySelector('table.table-monitoring tbody');
            if (!tbody) return;
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5">Memuat data...</td></tr>';
        }

        function reloadMateri(page = 1) {
            renderLoading();
            const url = buildMateriUrl(page);
            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error('Gagal memuat data');
                    return response.text();
                })
                .then(html => {
                    const container = document.getElementById('materiContent');
                    if (!container) return;
                    container.innerHTML = html;
                    executeInlineScripts(container);
                })
                .catch(error => {
                    console.error(error);
                    const tbody = document.querySelector('table.table-monitoring tbody');
                    if (tbody) {
                        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5">Gagal memuat data</td></tr>';
                    }
                });
        }

        function debouncedReload() {
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => reloadMateri(), 400);
        }

        function handlePaginationClicks(event) {
            const link = event.target.closest('.pagination a');
            if (!link) return;
            event.preventDefault();
            const url = new URL(link.href, window.location.origin);
            const page = url.searchParams.get('page') || 1;
            reloadMateri(page);
        }

        if (!window.materiPaginationHandlerAdded) {
            document.addEventListener('click', handlePaginationClicks);
            window.materiPaginationHandlerAdded = true;
        }

        function initMateriPartial() {
            const searchInput = document.getElementById('searchInput');
            const modeSelect = document.getElementById('reminderMode');
            if (searchInput) {
                searchInput.addEventListener('keyup', debouncedReload);
            }
            if (modeSelect) {
                modeSelect.addEventListener('change', () => reloadMateri(1));
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initMateriPartial);
        } else {
            initMateriPartial();
        }
    </script>
</body>

</html>
