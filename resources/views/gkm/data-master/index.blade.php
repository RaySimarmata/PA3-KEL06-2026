@extends('layouts.app')

@section('page-title', 'Data Master')

@section('content')
<div style="padding: 1.5rem;">
    <div class="row">
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="content-card h-100">
                <div class="text-center">
                    <i class="bi bi-person-lines-fill" style="font-size: 40px; color: #5B9BD5;"></i>
                    <h6 class="mt-3 mb-2 font-semibold">Penugasan Dosen</h6>
                    <p class="text-secondary small mb-3">Lihat daftar penugasan dosen per mata kuliah</p>
                    <a href="{{ route('gkm.data-master.penugasan-dosen') }}" class="btn-action-primary btn-sm">
                        Lihat Penugasan
                    </a>
                </div>
            </div>
        </div>


        <div class="col-md-6 col-lg-4 mb-3">
            <div class="content-card h-100">
                <div class="text-center">
                    <i class="bi bi-book" style="font-size: 40px; color: #ffc107;"></i>
                    <h6 class="mt-3 mb-2 font-semibold">Data Mata Kuliah Ajaran</h6>
                    <p class="text-secondary small mb-3">Kelola daftar mata kuliah</p>
                    <a href="{{ route('gkm.data-master.matakuliah') }}" class="btn btn-warning btn-sm">Kelola Mata Kuliah</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-3">
            <div class="content-card h-100">
                <div class="text-center">
                    <i class="bi bi-calendar" style="font-size: 40px; color: #6f42c1;"></i>
                    <h6 class="mt-3 mb-2 font-semibold">Data Periode Akademik</h6>
                    <p class="text-secondary small mb-3">Kelola periode semester akademik</p>
                    <a href="{{ route('gkm.data-master.periodeA') }}" class="btn btn-secondary btn-sm">Kelola Periode</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
