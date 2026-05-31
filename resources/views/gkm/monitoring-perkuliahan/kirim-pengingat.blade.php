@extends('layouts.app')

@section('title', 'Kirim Pesan Pengingat')
@section('page-title', 'Kirim Reminder Perkuliahan')

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Alert Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="monitoring-card">
            <div class="monitoring-header">
                <i class="bi bi-send-fill" style="color: #5B9BD5;"></i>
                <h6>Kirim Pesan Pengingat</h6>
            </div>
            <div style="padding: 1.5rem;">
                <ul class="nav nav-tabs mb-4" id="reminderTabs" role="tablist" style="border-bottom: 2px solid #e9ecef;">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="materi-tab" data-bs-toggle="tab" data-bs-target="#materi"
                            type="button" role="tab"
                            style="font-weight: 500; color: #6c757d; border: none; padding: 0.75rem 1.5rem;">
                            <i class="bi bi-file-earmark-text"></i> Reminder Upload Materi
                        </button>
                    </li>
                    {{-- <li class="nav-item" role="presentation">
                        <button class="nav-link" id="soal-tab" data-bs-toggle="tab" data-bs-target="#soal" type="button"
                            role="tab" style="font-weight: 500; color: #6c757d; border: none; padding: 0.75rem 1.5rem;">
                            <i class="bi bi-clipboard-check"></i> Reminder Review Soal
                        </button>
                    </li> --}}  
                </ul>

                <div class="tab-content" id="reminderTabContent">
                    <!-- Materi Tab -->
                    <div class="tab-pane fade show active" id="materi" role="tabpanel">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Memuat data dosen yang belum upload materi...</p>
                        </div>
                        <iframe id="materiFrame" src="{{ route('gkm.monitoring-perkuliahan.materi') }}" 
                                style="width: 100%; height: 800px; border: none; display: none;"
                                onload="hideLoader('materi')"></iframe>
                    </div>

                    <!-- Soal Tab -->
                    <div class="tab-pane fade" id="soal" role="tabpanel">
                        <div class="text-center py-4" id="soalLoader">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Memuat data kaprodi...</p>
                        </div>
                        <iframe id="soalFrame" src="{{ route('gkm.monitoring-perkuliahan.soal') }}" 
                                style="width: 100%; height: 800px; border: none; display: none;"
                                onload="hideLoader('soal')"></iframe>
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

    <script>
        function hideLoader(tabName) {
            const loader = document.querySelector(`#${tabName} .spinner-border`).closest('.text-center');
            const frame = document.getElementById(`${tabName}Frame`);
            
            if (loader) loader.style.display = 'none';
            if (frame) frame.style.display = 'block';
        }

        // Handle messages from iframe
        window.addEventListener('message', function(event) {
            if (event.data.type === 'success' || event.data.type === 'error') {
                const alertClass = event.data.type === 'success' ? 'alert-success' : 'alert-danger';
                const iconClass = event.data.type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle';
                
                const alertHtml = `
                    <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                        <i class="bi ${iconClass} me-2"></i>
                        ${event.data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                
                // Insert alert at the top of the page
                const container = document.querySelector('[style*="padding: 1.5rem"]');
                container.insertAdjacentHTML('afterbegin', alertHtml);
                
                // Auto-hide after 5 seconds
                setTimeout(() => {
                    const alert = container.querySelector('.alert');
                    if (alert) {
                        alert.remove();
                    }
                }, 5000);
            }
        });

        // Load iframe when tab is clicked
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
            
            tabButtons.forEach(button => {
                button.addEventListener('shown.bs.tab', function(e) {
                    const targetId = e.target.getAttribute('data-bs-target').replace('#', '');
                    const frame = document.getElementById(`${targetId}Frame`);
                    
                    if (frame && !frame.hasAttribute('data-loaded')) {
                        // Mark as loaded to prevent reloading
                        frame.setAttribute('data-loaded', 'true');
                        
                        // Reload iframe content when tab is shown
                        if (targetId === 'materi') {
                            frame.src = frame.src + '?t=' + Date.now();
                        } else if (targetId === 'soal') {
                            frame.src = frame.src + '?t=' + Date.now();
                        }
                    }
                });
            });
        });
    </script>
@endsection
