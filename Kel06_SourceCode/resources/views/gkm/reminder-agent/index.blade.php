@extends('layouts.app')

@section('page-title', 'Reminder Agent')

@section('content')
<div style="padding: 1.5rem;">
    @if(session('success'))
        <div class="alert-app success mb-4" data-auto-dismiss>
            <i class="bi bi-check-circle-fill alert-app-icon"></i>
            <div class="alert-app-body">{{ session('success') }}</div>
        </div>
    @endif



    <!-- Jadwal Reminder Table -->
    <div class="monitoring-card">
        <div class="monitoring-header">
            {{-- <i class="bi bi-calendar-event"></i> --}}
            <h6>Daftar Jadwal Reminder</h6>
            <div style="margin-left: auto;">
                <a href="{{ route('gkm.reminder-agent.jadwal') }}" class="btn-reminder">
                    <i class="bi bi-plus-circle"></i>
                    <span>Tambah Jadwal</span>
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-monitoring">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 25%;">Tipe Reminder</th>
                        <th style="width: 20%;">Tanggal & Jam Kirim</th>
                        <th style="width: 20%;">Status Terakhir</th>
                        <th style="width: 15%;" class="text-center">Status</th>
                        <th style="width: 15%;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jadwalList as $index => $jadwal)
                        <tr>
                            <td class="text-center">{{ $jadwalList->firstItem() + $index }}</td>
                            <td class="dosen-name">{{ $jadwal->tipe_reminder }}</td>
                            <td class="text-secondary">
                                @if($jadwal->tanggal_mulai)
                                    <div>
                                        <i class="bi bi-calendar-event"></i> {{ $jadwal->tanggal_mulai->format('d/m/Y') }}
                                    </div>
                                    <div class="mt-1">
                                        <i class="bi bi-clock"></i> {{ date('H:i', strtotime($jadwal->jam_pengiriman)) }}
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-secondary">
                                @if($jadwal->last_sent_at)
                                    <small>{{ \Carbon\Carbon::parse($jadwal->last_sent_at)->format('d/m/Y H:i') }}</small>
                                @else
                                    <span class="text-muted">Belum pernah dikirim</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($jadwal->last_sent_at)
                                    <span class="status-icon success">
                                        <i class="bi bi-check-lg"></i>
                                    </span>
                                @else
                                    <span class="status-icon warning">
                                        <i class="bi bi-clock"></i>
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="{{ route('gkm.reminder-agent.jadwal.edit', $jadwal->id) }}" 
                                       class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('gkm.reminder-agent.jadwal.destroy', $jadwal->id) }}" 
                                          method="POST" class="d-inline"
                                          onsubmit="AppConfirm.delete(this, 'Jadwal reminder ini akan dihapus permanen.'); return false;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-calendar-x"></i>
                                    <p>Belum ada jadwal reminder</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($jadwalList->hasPages())
            <div style="padding: 1rem 1.5rem; border-top: 1px solid #f1f3f5;">
                {{ $jadwalList->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
