@extends('layouts.app')

@section('page-title', 'Data Master - Penugasan Dosen')

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Header -->
        <div class="filter-card mb-4 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 font-semibold" style="text-transform: uppercase; letter-spacing: 0.5px;">
                Data Master: Penugasan Dosen
            </h5>
            <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        <!-- Filter Section -->
        <div class="filter-card mb-4">
            <form method="GET" id="filterForm">
                <input type="hidden" name="submitted" value="1">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="filter-label">Pilih Dosen:</label>
                        <div class="position-relative">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control" name="search" value="{{ request('search') }}"
                                    placeholder="Cari Nama Dosen..." id="searchDosen" autocomplete="off">
                            </div>
                            <!-- Dropdown suggestions -->
                            <div id="searchSuggestions" class="position-absolute bg-white border rounded shadow-sm"
                                style="top: 100%; left: 0; right: 0; z-index: 9999; max-height: 300px; overflow-y: auto; display: none; margin-top: 2px;">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="filter-label">Semester:</label>
                        <select class="form-select" name="sem_ta">
                            <option value="1" {{ request('sem_ta', $defaultSemTa ?? 1) == 1 ? 'selected' : '' }}>Ganjil
                            </option>
                            <option value="2" {{ request('sem_ta', $defaultSemTa ?? 1) == 2 ? 'selected' : '' }}>Genap
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="filter-label">Tahun Ajaran:</label>
                        <select class="form-select" name="ta">
                            @if (isset($tahunAjaranList) && count($tahunAjaranList) > 0)
                                @foreach ($tahunAjaranList as $tahun)
                                    <option value="{{ $tahun['id_thn_ajaran'] }}"
                                        {{ request('ta', $defaultTa ?? date('Y')) == $tahun['id_thn_ajaran'] ? 'selected' : '' }}>
                                        {{ $tahun['nm_thn_ajaran'] }}
                                    </option>
                                @endforeach
                            @else
                                <option value="{{ date('Y') }}" selected>{{ date('Y') }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="filter-label" style="opacity: 0;">Action</label>
                        <button class="btn btn-primary w-100" type="button" id="btnSearch">
                            <i class="bi bi-search me-1"></i> Cari
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Dosen Cards -->
        @if (!request('submitted') && !request('search'))
            <div class="monitoring-card">
                <div class="text-center py-5">
                    <i class="bi bi-info-circle" style="font-size: 4rem; color: #5B9BD5;"></i>
                    <h6 class="mt-3 text-secondary">Silakan Cari Nama Dosen</h6>
                    <p class="text-muted">Masukkan nama dosen pada kolom pencarian dan klik "Tampilkan data"</p>
                </div>
            </div>
        @else
            @forelse($dosenList as $dosen)
                <div class="monitoring-card mb-4">
                    <div class="monitoring-header" style="background: white; border-bottom: 2px solid #e9ecef;">
                        <div class="d-flex align-items-center gap-2">
                            <div
                                style="width: 40px; height: 40px; background: #5B9BD5; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                @php
                                    $nama = is_array($dosen)
                                        ? $dosen['nama'] ?? ''
                                        : $dosen->nama_lengkap ?? ($dosen->nama ?? '');
                                @endphp
                                {{ strtoupper(substr($nama, 0, 1)) }}
                            </div>
                            <div>
                                <h6 class="mb-0 font-semibold">{{ $nama }}</h6>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    @php
                                        $jabatan = is_array($dosen)
                                            ? $dosen['jabatan_akademik_desc'] ?? 'Dosen'
                                            : $dosen->jabatan_akademik_desc ?? 'Dosen';
                                        $nidn = is_array($dosen) ? $dosen['nidn'] ?? '-' : $dosen->nidn ?? '-';
                                        $email = is_array($dosen) ? $dosen['email'] ?? '' : $dosen->email ?? '';
                                    @endphp
                                    <span class="badge-gkm info" style="font-size: 0.75rem;">
                                        <i class="bi bi-person-badge"></i> {{ $jabatan }}
                                    </span>
                                    <span class="text-secondary" style="font-size: 0.85rem;">
                                        <i class="bi bi-hash"></i> NIDN: {{ $nidn }}
                                    </span>
                                    @if ($email)
                                        <span class="text-secondary">
                                            <i class="bi bi-envelope"></i> {{ $email }}
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('gkm.data-master.dosen.update.email') }}"
                                            style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="nidn" value="{{ $nidn }}">

                                            <input type="email" name="email" placeholder="Isi email..."
                                                style="font-size: 0.75rem; padding: 2px 6px; width: 150px;" required>

                                            <button type="submit" class="btn btn-sm btn-primary">
                                                Save
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-monitoring">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">Kode MK</th>
                                    <th style="width: 35%;">Nama Mata Kuliah</th>
                                    <th style="width: 10%;">SKS</th>
                                    <th style="width: 15%;">Semester</th>
                                    <th style="width: 15%;">Tahun Ajaran</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $matakuliah = is_array($dosen)
                                        ? $dosen['matakuliah'] ?? []
                                        : $dosen->matakuliah ?? [];
                                @endphp
                                @forelse($matakuliah as $mk)
                                    <tr>
                                        @php
                                            $kodeMk = is_array($mk) ? $mk['kode_mk'] ?? '-' : $mk->kode_mk ?? '-';
                                            $namaMk = is_array($mk) ? $mk['nama_mk'] ?? '-' : $mk->nama_mk ?? '-';
                                            $sks = is_array($mk) ? $mk['sks'] ?? '-' : $mk->sks ?? '-';
                                            $semester = is_array($mk) ? $mk['semester'] ?? '-' : $mk->semester ?? '-';
                                            $tahunAjaran = is_array($mk)
                                                ? $mk['tahun_ajaran'] ?? '-'
                                                : $mk->tahun_ajaran ?? '-';
                                        @endphp
                                        <td class="code-mk">{{ $kodeMk }}</td>
                                        <td class="nama-mk" style="text-transform: uppercase;">{{ $namaMk }}</td>
                                        <td class="text-secondary">{{ $sks }} SKS</td>
                                        <td class="text-secondary">Semester {{ $semester }}</td>
                                        <td class="text-secondary">{{ $tahunAjaran }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                                <p class="mt-2 mb-0">Belum ada mata kuliah yang ditugaskan</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="monitoring-card">
                    <div class="text-center py-5">
                        <i class="bi bi-search" style="font-size: 4rem; color: #dee2e6;"></i>
                        <h6 class="mt-3 text-secondary">Tidak ada data dosen ditemukan</h6>
                        <p class="text-muted">Coba ubah filter pencarian Anda</p>
                    </div>
                </div>
            @endforelse
        @endif

        <!-- Pagination -->
        @if ($dosenList->hasPages())
            <div class="mt-4 d-flex justify-content-center">
                {{ $dosenList->links() }}
            </div>
        @endif
    </div>

    <style>
        /* Autocomplete dropdown styling */
        #searchSuggestions {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        #searchSuggestions .suggestion-item {
            transition: background-color 0.2s ease;
        }

        #searchSuggestions .suggestion-item:hover {
            background-color: #f8f9fa !important;
        }

        #searchSuggestions .suggestion-item:last-child {
            border-bottom: none !important;
        }

        /* Ensure parent doesn't clip dropdown */
        .filter-card {
            overflow: visible !important;
        }
    </style>

    <script>
        const searchInput = document.getElementById('searchDosen');
        const searchSuggestions = document.getElementById('searchSuggestions');
        const filterForm = document.getElementById('filterForm');
        let searchTimeout;
        let dosenList = [];

        // Fetch dosen list for autocomplete
        async function fetchDosenList() {
            try {
                const response = await fetch('{{ route('gkm.data-master.penugasan-dosen') }}?get_dosen_list=1', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();
                dosenList = data.dosen || [];
            } catch (error) {
                console.error('Error fetching dosen list:', error);
            }
        }

        // Show suggestions
        function showSuggestions(query) {
            if (!query || query.length < 1) {
                searchSuggestions.style.display = 'none';
                return;
            }

            const filtered = dosenList.filter(dosen =>
                dosen.nama.toLowerCase().includes(query.toLowerCase())
            ).slice(0, 10); // Limit to 10 suggestions

            if (filtered.length === 0) {
                searchSuggestions.style.display = 'none';
                return;
            }

            // Function to highlight matching text
            function highlightMatch(text, query) {
                const regex = new RegExp(`(${query})`, 'gi');
                return text.replace(regex, '<strong style="color: #5B9BD5; font-weight: 600;">$1</strong>');
            }

            const html = filtered.map(dosen => {
                const highlightedName = highlightMatch(dosen.nama, query);
                return `
                <div class="suggestion-item p-2 border-bottom" style="cursor: pointer;"
                     onmouseover="this.style.backgroundColor='#f8f9fa'"
                     onmouseout="this.style.backgroundColor='white'"
                     onclick="selectDosen('${dosen.nama.replace(/'/g, "\\'")}')">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width: 30px; height: 30px; background: #5B9BD5; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.8rem;">
                            ${dosen.nama.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <div style="font-weight: 500;">${highlightedName}</div>
                            <small class="text-muted">${dosen.email || ''}</small>
                        </div>
                    </div>
                </div>
            `;
            }).join('');

            searchSuggestions.innerHTML = html;
            searchSuggestions.style.display = 'block';
        }

        // Select dosen from suggestions
        function selectDosen(nama) {
            searchInput.value = nama;
            searchSuggestions.style.display = 'none';
            filterForm.submit();
        }

        // Handle input changes
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();

            clearTimeout(searchTimeout);

            // If empty, redirect to initial state
            if (query === '') {
                searchSuggestions.style.display = 'none';
                // Redirect to page without search parameter
                window.location.href = '{{ route('gkm.data-master.penugasan-dosen') }}';
                return;
            }

            searchTimeout = setTimeout(() => {
                showSuggestions(query);
            }, 300);
        });

        // Close suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
                searchSuggestions.style.display = 'none';
            }
        });

        // Fetch dosen list on page load
        fetchDosenList();
    </script>
@endsection
