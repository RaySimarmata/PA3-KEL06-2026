@extends('layouts.app')

@section('page-title', 'Monitoring Perkuliahan')

@section('content')
    <div style="padding: 1.5rem;">
        <div class="row mb-4">
            <div class="col-12">
                <p class="text-muted">Pengaturan Reminder Dosen untuk Perkuliahan</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-envelope" style="font-size: 40px; color: #1e3c72;"></i>
                        <h6 class="mt-3 mb-2">Reminder Dosen untuk Perwalian & Persiapan Perkuliahan</h6>
                        <p class="text-muted small mb-3">Kirimkan email reminder kepada dosen untuk perwalian dan persiapan
                            perkuliahan di periode semester ini</p>
                        <a href="{{ route('gkm.monitoring-perkuliahan.perwalian') }}" class="btn btn-primary btn-sm">Kirim
                            Reminder</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-cloud-upload" style="font-size: 40px; color: #ffc107;"></i>
                        <h6 class="mt-3 mb-2">Reminder Upload Materi di CIS</h6>
                        <p class="text-muted small mb-3">Kirimkan email reminder kepada dosen untuk upload materi di sistem
                            CIS</p>
                        <a href="{{ route('gkm.monitoring-perkuliahan.materi') }}" class="btn btn-warning btn-sm">Kirim
                            Reminder</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-clipboard-check" style="font-size: 40px; color: #28a745;"></i>
                        <h6 class="mt-3 mb-2">Reminder Kaprodi Review Soal</h6>
                        <p class="text-muted small mb-3">Kirimkan email reminder kepada kepala prodi untuk review soal ujian
                        </p>
                        <a href="{{ route('gkm.monitoring-perkuliahan.soal') }}" class="btn btn-success btn-sm">Kirim
                            Reminder</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
