@extends('layouts.app')

@section('page-title', 'Reminder Agent')

@section('content')
<div style="padding: 1.5rem;">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-calendar-check" style="font-size: 40px; color: #1e3c72;"></i>
                    <h6 class="mt-3 mb-2">Pengaturan Jadwal Reminder</h6>
                    <p class="text-muted small mb-3">Atur jadwal pengiriman email reminder otomatis ke dosen</p>
                    <a href="{{ route('gkm.reminder-agent.jadwal') }}" class="btn btn-primary btn-sm">Atur Jadwal</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-envelope-open" style="font-size: 40px; color: #28a745;"></i>
                    <h6 class="mt-3 mb-2">Log Pengiriman Email</h6>
                    <p class="text-muted small mb-3">Lihat riwayat pengiriman email reminder</p>
                    <a href="{{ route('gkm.reminder-agent.log') }}" class="btn btn-success btn-sm">Lihat Log</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">Daftar Jadwal Reminder</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tipe Reminder</th>
                                    <th>Tanggal & Jam Kirim</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($jadwalList as $index => $jadwal)
                                <tr>
                                    <td>{{ $jadwalList->firstItem() + $index }}</td>
                                    <td>{{ $jadwal->tipe_reminder }}</td>
                                    <td>
                                        @if($jadwal->tanggal_mulai)
                                            <i class="bi bi-calendar-event"></i> {{ $jadwal->tanggal_mulai->format('d/m/Y') }}<br>
                                            <i class="bi bi-clock"></i> {{ date('H:i', strtotime($jadwal->jam_pengiriman)) }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($jadwal->last_sent_at)
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i> Berhasil
                                            </span>
                                            <br>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($jadwal->last_sent_at)->format('d/m/Y H:i') }}</small>
                                        @else
                                            <span class="badge bg-warning text-dark">
                                                <i class="bi bi-clock-history"></i> Menunggu
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('gkm.reminder-agent.jadwal.edit', $jadwal->id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <form action="{{ route('gkm.reminder-agent.jadwal.destroy', $jadwal->id) }}" 
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Yakin ingin menghapus jadwal reminder ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada jadwal reminder</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if($jadwalList->hasPages())
                    <div class="mt-3">
                        {{ $jadwalList->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
