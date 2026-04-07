@extends('layouts.app')

@section('title', 'Kirim Pesan Pengingat')
@section('page-title', 'Kirim Reminder Perkuliahan')

@section('content')
    <div style="padding: 1.5rem;">
        <div class="monitoring-card">
            <div class="monitoring-header">
                <i class="bi bi-send-fill" style="color: #5B9BD5;"></i>
                <h6>Kirim Pesan Pengingat</h6>
            </div>
            <div style="padding: 1.5rem;">
                <ul class="nav nav-tabs mb-4" id="reminderTabs" role="tablist" style="border-bottom: 2px solid #e9ecef;">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="perwalian-tab" data-bs-toggle="tab" data-bs-target="#perwalian"
                            type="button" role="tab"
                            style="font-weight: 500; color: #6c757d; border: none; padding: 0.75rem 1.5rem;">
                            <i class="bi bi-people"></i> Reminder Perwalian
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="materi-tab" data-bs-toggle="tab" data-bs-target="#materi"
                            type="button" role="tab"
                            style="font-weight: 500; color: #6c757d; border: none; padding: 0.75rem 1.5rem;">
                            <i class="bi bi-file-earmark-text"></i> Reminder Upload Materi
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="soal-tab" data-bs-toggle="tab" data-bs-target="#soal" type="button"
                            role="tab" style="font-weight: 500; color: #6c757d; border: none; padding: 0.75rem 1.5rem;">
                            <i class="bi bi-clipboard-check"></i> Reminder Review Soal
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="reminderTabContent">
                    <!-- Perwalian Tab -->
                    <div class="tab-pane fade show active" id="perwalian" role="tabpanel">
                        @include('gkm.monitoring-perkuliahan.perwalian', ['dosenWali' => $dosenWali])
                    </div>

                    <!-- Materi Tab -->
                    <div class="tab-pane fade" id="materi" role="tabpanel">
                        @include('gkm.monitoring-perkuliahan.materi', ['dosenMateri' => $dosenMateri])
                    </div>

                    <!-- Soal Tab -->
                    <div class="tab-pane fade" id="soal" role="tabpanel">
                        @include('gkm.monitoring-perkuliahan.soal', ['dosenKaprodi' => $dosenKaprodi])
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Tab styling to match GKM design */
        .nav-tabs .nav-link {
            transition: all 0.3s ease;
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
    </style>
@endsection
