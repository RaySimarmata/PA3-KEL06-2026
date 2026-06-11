@extends('layouts.app')

@section('page-title', 'Laporan Kuesioner')

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Filter Section -->
        <div class="filter-card mb-4">
            <form method="GET" action="{{ route('gkm.laporan-kuesioner.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="filter-label">Periode</label>
                        <select name="periode" class="form-select">
                            <option value="">Semua Periode</option>
                            @foreach ($periodes as $p)
                                <option value="{{ $p->periode }}"
                                    {{ request('periode') == $p->periode ? 'selected' : '' }}>
                                    {{ $p->bulan }} {{ $p->tahun }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="filter-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                            <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Sedang
                                Diproses</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai
                            </option>
                            <option value="error" {{ request('status') == 'error' ? 'selected' : '' }}>Error</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100" style="padding: 0.6rem;">
                        <i class="bi bi-search"></i> Cari
                    </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Monitoring Table -->
        <div class="monitoring-card">
            <div class="monitoring-header">
                <h6>Laporan Kuesioner</h6>
                <div style="margin-left: auto; display: flex; gap: 0.5rem;">
                    <a href="{{ route('gkm.laporan-kuesioner.template.index') }}" 
                       class="btn btn-sm btn-success" 
                       style="background-color: #28a745; border-color: #28a745; transition: none;"
                       onmouseover="this.style.backgroundColor='#28a745'; this.style.borderColor='#28a745';"
                       onmouseout="this.style.backgroundColor='#28a745'; this.style.borderColor='#28a745';">
                        <i class="bi bi-file-earmark-text" style="color: white;"></i> Kelola Template
                    </a>
                    <a href="{{ route('gkm.laporan-kuesioner.create') }}" class="btn-reminder">
                        <i class="bi bi-plus-circle"></i>
                        <span>Generate Laporan Baru</span>
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-monitoring">
                    <thead>
                        <tr>
                            <th style="width: 18%;">Periode</th>
                            <th style="width: 12%;" class="text-center">Total Kuesioner</th>
                            <th style="width: 18%;" class="text-center">Index Kepuasan</th>
                            <th style="width: 14%;" class="text-center">Status</th>
                            <th style="width: 15%;">Dibuat</th>
                            <th style="width: 23%;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($laporanList as $laporan)
                            <tr>
                                <td class="code-mk">{{ $laporan->formatted_periode }}</td>
                                <td class="text-center">
                                    <span class="badge-gkm info">{{ $laporan->total_kuesioner }}</span>
                                </td>
                                <td class="text-center">
                                    @if ($laporan->index_kepuasan_rata_rata)
                                        <div>
                                            <span class="badge-gkm success" style="font-size: 0.9rem;">
                                                {{ number_format($laporan->index_kepuasan_rata_rata, 2) }}
                                            </span>
                                            <div class="text-muted" style="font-size: 0.75rem; margin-top: 0.25rem;">
                                                ({{ number_format($laporan->persen_kepuasan_rata_rata, 1) }}%)
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span
                                        class="badge-gkm {{ $laporan->status_badge == 'success' ? 'success' : ($laporan->status_badge == 'warning' ? 'warning' : ($laporan->status_badge == 'danger' ? 'danger' : 'info')) }}">
                                        {{ $laporan->status_label }}
                                    </span>
                                </td>
                                <td class="text-secondary" style="font-size: 0.85rem;">
                                    {{ $laporan->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('gkm.laporan-kuesioner.show', $laporan->id) }}"
                                            class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        @if ($laporan->status == 'completed' && $laporan->file_word)
                                            <a href="{{ route('gkm.laporan-kuesioner.download', [$laporan->id, 'word']) }}"
                                                class="btn btn-sm btn-outline-success" title="Download Word">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        @endif

                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-laporan"
                                            title="Hapus" data-laporan-id="{{ $laporan->id }}"
                                            data-laporan-periode="{{ $laporan->formatted_periode }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <p>Belum ada laporan. <a href="{{ route('gkm.laporan-kuesioner.create') }}"
                                                style="color: #5B9BD5; font-weight: 600;">Generate laporan baru</a></p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if ($laporanList->hasPages())
            <div class="mt-4 d-flex justify-content-center">
                {{ $laporanList->links() }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        // Handle delete laporan with AJAX
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.btn-delete-laporan');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const laporanId = this.getAttribute('data-laporan-id');
                    const laporanPeriode = this.getAttribute('data-laporan-periode');

                    if (confirm(`Yakin ingin menghapus laporan periode ${laporanPeriode}?`)) {
                        // Disable button
                        this.disabled = true;
                        const originalHTML = this.innerHTML;
                        this.innerHTML = '<i class="bi bi-hourglass-split"></i>';

                        // Build URL using route helper
                        const url = '{{ route('gkm.laporan-kuesioner.destroy', ':id') }}'.replace(
                            ':id', laporanId);

                        // Send DELETE request
                        fetch(url, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector(
                                        'meta[name="csrf-token"]').getAttribute('content'),
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Show success message
                                    alert(data.message);
                                    // Reload page to update list
                                    window.location.reload();
                                } else {
                                    alert('Error: ' + data.message);
                                    this.disabled = false;
                                    this.innerHTML = originalHTML;
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Terjadi kesalahan saat menghapus laporan');
                                this.disabled = false;
                                this.innerHTML = originalHTML;
                            });
                    }
                });
            });
        });
    </script>
@endpush
