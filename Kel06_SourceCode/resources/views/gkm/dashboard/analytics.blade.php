@extends('layouts.app')

@section('page-title', 'Dashboard Analytics GKM')

@section('content')

<div style="padding: 1.5rem; background: #f4f6f9; min-height: 100vh;">

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h3 class="fw-bold mb-1">Dashboard Analytics GKM</h3>
                    <p class="text-muted mb-0">
                        Selamat datang, <strong>{{ $user->name }}</strong>
                        • Periode {{ $periode }}
                    </p>
                </div>
                <div class="mt-3 mt-md-0">
                    <a href="{{ route('gkm.dashboard') }}" class="btn btn-outline-secondary shadow-sm">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    @include('gkm.dashboard._analytics-section')

@endsection
