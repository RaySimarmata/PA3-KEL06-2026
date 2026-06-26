<!-- ===== OCR UPLOAD & PREVIEW SECTION ===== -->
<div class="monitoring-card mb-4" id="ocr-section" style="display: none;">
    <div class="monitoring-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-camera" style="color: #16a34a;"></i>
            <h6 class="mb-0">Upload Gambar & OCR</h6>
            <span class="badge bg-success ms-2">NEW</span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" id="btn-toggle-stats" class="btn btn-sm btn-outline-info">
                <i class="bi bi-graph-up"></i> Stats
            </button>
        </div>
    </div>
    
    <div style="padding: 1.5rem;">
        <!-- Info Banner -->
        <div class="alert alert-info" style="border-left: 4px solid #0891b2;">
            <div class="d-flex align-items-start">
                <i class="bi bi-info-circle me-2" style="font-size: 1.2rem; margin-top: 0.1rem;"></i>
                <div>
                    <strong>Fitur OCR + AI:</strong> Upload gambar dokumentasi kegiatan, daftar hadir, kuesioner, atau dokumen lainnya. 
                    Sistem akan mengekstrak teks menggunakan Tesseract OCR dan menggunakan AI untuk membuat draft laporan.
                    <br><small class="text-muted">
                        <strong>Format didukung:</strong> JPG, PNG, PDF | 
                        <strong>Max:</strong> 15 gambar | 
                        <strong>AI:</strong> Groq (Gratis, Unlimited)
                    </small>
                </div>
            </div>
        </div>

        <!-- Step 1: Image Upload -->
        <div class="mb-4">
            <h6 class="mb-3 d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-circle" style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;">1</span>
                Upload Gambar
            </h6>
            
            <!-- Upload Zone -->
            <div class="ocr-upload-zone" id="image-upload-zone">
                <div class="upload-text">
                    <i class="bi bi-cloud-upload"></i>
                    Drag & drop gambar atau klik untuk upload
                    <br><small style="color: #9ca3af;">JPG, PNG, PDF • Max 15 files • Max 10MB per file</small>
                </div>
            </div>
            
            <input type="file" id="ocr-images" accept=".jpg,.jpeg,.png,.pdf" multiple style="display: none;">
            
            <!-- Image Preview -->
            <div id="image-preview" class="image-preview-grid" style="display: none;"></div>
            
            <!-- Upload Button -->
            <div class="mt-3 d-flex justify-content-between align-items-center">
                <button type="button" id="btn-upload-ocr" class="btn-ocr-action" disabled>
                    <i class="bi bi-upload"></i>
                    Upload & Extract OCR
                </button>
                <small class="text-muted">
                    <i class="bi bi-clock"></i> Estimasi: ~2-3 detik per gambar
                </small>
            </div>
        </div>

        <!-- Progress Indicator -->
        <div id="ocr-progress" class="ocr-progress"></div>

        <!-- Step 2: OCR Results -->
        <div id="ocr-results" class="ocr-results">
            <!-- Results will be populated by JavaScript -->
        </div>

        <!-- Extracted Text (Editable) -->
        <textarea id="extracted-text" class="extracted-text-area" placeholder="Teks hasil OCR akan muncul di sini..."></textarea>

        <!-- Step 3: Generate Preview -->
        <div class="mt-4">
            <h6 class="mb-3 d-flex align-items-center gap-2">
                <span class="badge bg-warning rounded-circle" style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;">2</span>
                Generate Preview Draft
            </h6>
            
            <div class="d-flex justify-content-between align-items-center">
                <button type="button" id="btn-generate-preview" class="btn-ocr-action btn-warning" disabled>
                    <i class="bi bi-magic"></i>
                    Generate Preview dengan AI
                </button>
                <small class="text-muted">
                    <i class="bi bi-robot"></i> Menggunakan AI untuk membuat draft laporan
                </small>
            </div>
        </div>

        <!-- Step 4: Preview Panel -->
        <div id="preview-panel" class="preview-panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 d-flex align-items-center gap-2">
                    <span class="badge bg-success rounded-circle" style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;">3</span>
                    Preview Draft Laporan
                </h6>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('preview-panel').style.display='none'">
                    <i class="bi bi-x"></i> Tutup
                </button>
            </div>
            
            <div id="preview-content">
                <!-- Preview content will be populated by JavaScript -->
            </div>
            
            <!-- Generate Word Button -->
            <div class="mt-4 text-center">
                <button type="button" id="btn-generate-word" class="btn-ocr-action btn-success" disabled>
                    <i class="bi bi-file-earmark-word-fill"></i>
                    Generate Laporan Word
                </button>
                <br><small class="text-muted mt-2 d-block">
                    Preview dapat diedit sebelum generate Word document
                </small>
            </div>
        </div>

        <!-- Stats Panel (Collapsible) -->
        <div id="stats-panel" class="stats-container" style="display: none;">
            <div class="stats-card">
                <h6><i class="bi bi-robot"></i> AI Provider Status</h6>
                <div id="ai-usage-stats">
                    <div class="text-center text-muted">
                        <div class="spinner-border spinner-border-sm"></div>
                        <small class="d-block mt-1">Loading...</small>
                    </div>
                </div>
            </div>
            <div class="stats-card">
                <h6><i class="bi bi-camera"></i> OCR Engine Status</h6>
                <div id="ocr-stats">
                    <div class="text-center text-muted">
                        <div class="spinner-border spinner-border-sm"></div>
                        <small class="d-block mt-1">Loading...</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Additional styles specific to this section */
.image-preview-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 1rem;
}

.image-preview-item {
    position: relative;
    width: 120px;
    height: 120px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
    background: #f9fafb;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.image-preview-item img {
    width: 100%;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
}

.image-preview-item .file-icon {
    font-size: 2rem;
    color: #dc2626;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.image-preview-item .file-name {
    font-size: 0.7rem;
    color: #6b7280;
    text-align: center;
    padding: 0.25rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    width: 100%;
}

.btn-remove-image {
    position: absolute;
    top: 4px;
    right: 4px;
    background: rgba(220, 38, 38, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    font-size: 0.7rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.btn-remove-image:hover {
    background: #dc2626;
    transform: scale(1.1);
}
</style>

<script>
// Toggle stats panel
document.addEventListener('DOMContentLoaded', function() {
    const btnToggleStats = document.getElementById('btn-toggle-stats');
    const statsPanel = document.getElementById('stats-panel');
    
    if (btnToggleStats && statsPanel) {
        btnToggleStats.addEventListener('click', function() {
            const isVisible = statsPanel.style.display !== 'none';
            statsPanel.style.display = isVisible ? 'none' : 'grid';
            
            const icon = this.querySelector('i');
            icon.className = isVisible ? 'bi bi-graph-up' : 'bi bi-graph-down';
        });
    }
});
</script>