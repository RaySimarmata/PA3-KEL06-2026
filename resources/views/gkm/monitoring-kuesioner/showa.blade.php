@extends('layouts.app')

@section('page-title', 'Detail Kuesioner')

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Header Card (sama persis dengan style History Reminder) -->
        <div class="filter-card mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="mb-1 fw-semibold" style="color: #333;">
                        Detail Kuesioner
                    </h5>
                    <p class="text-muted mb-0 small">
                        Informasi lengkap kuesioner dan rekapitulasi jawaban
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <!-- Detail Kuesioner Card -->
        <div class="monitoring-card mb-4">
            <div class="monitoring-header">
                <h6 class="mb-0">Informasi Kuesioner</h6>
            </div>
            <div class="card-body" style="padding: 1.25rem;">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold text-muted small">Judul Kuesioner</label>
                        <p class="mb-0">{{ $kuesioner->judul_kuesioner }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold text-muted small">Kode MK</label>
                        <p class="mb-0">{{ $kuesioner->kode_mk }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold text-muted small">Kuesioner ID</label>
                        <p class="mb-0">{{ $kuesioner->kuesioner_id }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold text-muted small">Tahun</label>
                        <p class="mb-0">{{ $kuesioner->periode }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold text-muted small">Semester</label>
                        <p class="mb-0">{{ $kuesioner->semester }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold text-muted small">Jenis Kuesioner</label>
                        <p class="mb-0">{{ $kuesioner->jenis_kuesioner ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pertanyaan Card -->
        <div class="monitoring-card">
            <div class="monitoring-header">
                <h6 class="mb-0">Rekapitulasi Pertanyaan & Jawaban</h6>
            </div>
            <div class="card-body" style="padding: 1.25rem;">
                @php
                    $rekapitulasi = $kuesioner->raw_data['statistik']['rekapitulasi'] ?? [];
                @endphp

                @forelse($rekapitulasi as $i => $item)
                    <div class="border rounded p-3 mb-3" style="background-color: #f8f9fa;">
                        <!-- Pertanyaan: nomor di samping kiri, teks di sampingnya -->
                        <div class="d-flex mb-2">
                            <strong style="color: #333; min-width: 35px;">
                                {{ $i + 1 }}.
                            </strong>
                            <strong style="color: #333; flex: 1;">
                                {!! $item['pertanyaan'] ?? '-' !!}
                            </strong>
                        </div>
                        <hr class="my-2">
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless mb-0">
                                <thead>
                                    <tr class="text-muted small">
                                        <th>Jawaban</th>
                                        <th width="100">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($item['rincian_jawaban'] ?? [] as $jawaban)
                                        <tr>
                                            <td>{{ $jawaban['jawaban'] ?? '-' }}</td>
                                            <td>
                                                <span class="badge-gkm primary">{{ $jawaban['jumlah'] ?? 0 }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-chat-square-text" style="font-size: 3rem;"></i>
                        <p class="mt-2 mb-0">Tidak ada pertanyaan</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
