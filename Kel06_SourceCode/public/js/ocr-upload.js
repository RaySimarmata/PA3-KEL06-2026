/**
 * OCR Upload & Preview System
 * Enhanced OCR & RAG System for Laporan Generation
 */

class OCRUploadSystem {
    constructor() {
        this.laporanId = null;
        this.uploadedImages = [];
        this.extractedText = '';
        this.previewSections = {};

        this.initializeElements();
        this.bindEvents();
    }

    initializeElements() {
        // OCR Elements
        this.ocrSection = document.getElementById('ocr-section');
        this.imageUploadZone = document.getElementById('image-upload-zone');
        this.imageInput = document.getElementById('ocr-images');
        this.imagePreview = document.getElementById('image-preview');
        this.btnUploadOCR = document.getElementById('btn-upload-ocr');
        this.ocrProgress = document.getElementById('ocr-progress');
        this.ocrResults = document.getElementById('ocr-results');
        this.extractedTextArea = document.getElementById('extracted-text');
        this.btnGeneratePreview = document.getElementById('btn-generate-preview');

        // Preview Elements
        this.previewPanel = document.getElementById('preview-panel');
        this.previewContent = document.getElementById('preview-content');
        this.btnGenerateWord = document.getElementById('btn-generate-word');

        // Stats Elements
        this.aiUsageStats = document.getElementById('ai-usage-stats');
        this.ocrStats = document.getElementById('ocr-stats');
    }

    bindEvents() {
        // Image upload events
        if (this.imageInput) {
            this.imageInput.addEventListener('change', (e) => this.handleImageSelection(e));
        }

        if (this.imageUploadZone) {
            this.imageUploadZone.addEventListener('click', () => this.imageInput?.click());
            this.imageUploadZone.addEventListener('dragover', (e) => this.handleDragOver(e));
            this.imageUploadZone.addEventListener('drop', (e) => this.handleDrop(e));
        }

        // OCR upload button
        if (this.btnUploadOCR) {
            this.btnUploadOCR.addEventListener('click', () => this.uploadAndExtractOCR());
        }

        // Generate preview button
        if (this.btnGeneratePreview) {
            this.btnGeneratePreview.addEventListener('click', () => this.generatePreview());
        }

        // Generate Word button
        if (this.btnGenerateWord) {
            this.btnGenerateWord.addEventListener('click', () => this.generateWordDocument());
        }

        // Load stats on page load
        this.loadUsageStats();
    }

    handleImageSelection(event) {
        const files = Array.from(event.target.files);
        this.displayImagePreviews(files);
        this.updateUploadZone(files.length);
    }

    handleDragOver(event) {
        event.preventDefault();
        this.imageUploadZone.classList.add('drag-over');
    }

    handleDrop(event) {
        event.preventDefault();
        this.imageUploadZone.classList.remove('drag-over');

        const files = Array.from(event.dataTransfer.files).filter(file =>
            file.type.startsWith('image/') || file.type === 'application/pdf'
        );

        if (files.length > 0) {
            this.imageInput.files = this.createFileList(files);
            this.displayImagePreviews(files);
            this.updateUploadZone(files.length);
        }
    }

    createFileList(files) {
        const dt = new DataTransfer();
        files.forEach(file => dt.items.add(file));
        return dt.files;
    }

    displayImagePreviews(files) {
        if (!this.imagePreview) return;

        this.imagePreview.innerHTML = '';

        files.forEach((file, index) => {
            const previewItem = document.createElement('div');
            previewItem.className = 'image-preview-item';

            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.onload = () => URL.revokeObjectURL(img.src);
                previewItem.appendChild(img);
            } else {
                const icon = document.createElement('div');
                icon.className = 'file-icon';
                icon.innerHTML = '<i class="bi bi-file-earmark-pdf"></i>';
                previewItem.appendChild(icon);
            }

            const fileName = document.createElement('div');
            fileName.className = 'file-name';
            fileName.textContent = file.name;
            previewItem.appendChild(fileName);

            const removeBtn = document.createElement('button');
            removeBtn.className = 'btn-remove-image';
            removeBtn.innerHTML = '<i class="bi bi-x"></i>';
            removeBtn.onclick = () => this.removeImage(index);
            previewItem.appendChild(removeBtn);

            this.imagePreview.appendChild(previewItem);
        });

        this.imagePreview.style.display = files.length > 0 ? 'flex' : 'none';
    }

    updateUploadZone(fileCount) {
        if (!this.imageUploadZone) return;

        const text = this.imageUploadZone.querySelector('.upload-text');
        if (fileCount > 0) {
            text.innerHTML = `<i class="bi bi-check-circle-fill text-success"></i> ${fileCount} gambar dipilih`;
            this.imageUploadZone.classList.add('has-files');
            this.btnUploadOCR.disabled = false;
        } else {
            text.innerHTML = '<i class="bi bi-cloud-upload"></i> Drag & drop gambar atau klik untuk upload';
            this.imageUploadZone.classList.remove('has-files');
            this.btnUploadOCR.disabled = true;
        }
    }

    removeImage(index) {
        const dt = new DataTransfer();
        const files = Array.from(this.imageInput.files);

        files.forEach((file, i) => {
            if (i !== index) dt.items.add(file);
        });

        this.imageInput.files = dt.files;
        this.displayImagePreviews(Array.from(dt.files));
        this.updateUploadZone(dt.files.length);
    }

    async uploadAndExtractOCR() {
        if (!this.laporanId) {
            this.showError('Silakan buat laporan draft terlebih dahulu');
            return;
        }

        const files = this.imageInput.files;
        if (files.length === 0) {
            this.showError('Pilih gambar terlebih dahulu');
            return;
        }

        this.showProgress('Mengupload gambar dan melakukan OCR...');
        this.btnUploadOCR.disabled = true;

        try {
            const formData = new FormData();
            formData.append('laporan_id', this.laporanId);

            Array.from(files).forEach(file => {
                formData.append('images[]', file);
            });

            const response = await fetch('/gjm/buat-laporan/ocr/upload', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const result = await response.json();

            if (result.success) {
                this.extractedText = result.data.extracted_text;
                this.displayOCRResults(result.data);
                this.showSuccess(`OCR berhasil! ${result.data.successful_images} gambar diproses dalam ${result.data.processing_time_ms}ms`);
                this.btnGeneratePreview.disabled = false;
            } else {
                this.showError('OCR gagal: ' + (result.message || 'Unknown error'));
            }

        } catch (error) {
            console.error('OCR upload error:', error);
            this.showError('Terjadi kesalahan saat upload: ' + error.message);
        } finally {
            this.hideProgress();
            this.btnUploadOCR.disabled = false;
        }
    }

    displayOCRResults(data) {
        if (!this.ocrResults) return;

        this.ocrResults.innerHTML = `
            <div class="ocr-summary">
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number">${data.successful_images}</div>
                            <div class="stat-label">Gambar Berhasil</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number">${data.failed_images}</div>
                            <div class="stat-label">Gambar Gagal</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number">${data.text_length}</div>
                            <div class="stat-label">Karakter</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number">${Math.round(data.processing_time_ms)}ms</div>
                            <div class="stat-label">Waktu Proses</div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        if (this.extractedTextArea) {
            this.extractedTextArea.value = data.extracted_text;
            this.extractedTextArea.style.display = 'block';
        }

        this.ocrResults.style.display = 'block';
    }

    async generatePreview() {
        if (!this.extractedText) {
            this.showError('Tidak ada teks hasil OCR');
            return;
        }

        this.showProgress('Generating preview dengan AI...');
        this.btnGeneratePreview.disabled = true;

        try {
            const response = await fetch('/gjm/buat-laporan/ocr/preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    laporan_id: this.laporanId,
                    extracted_text: this.extractedText
                })
            });

            const result = await response.json();

            if (result.success) {
                this.previewSections = result.data.sections;
                this.displayPreview(result.data);
                this.showSuccess('Preview berhasil dibuat!');
                this.btnGenerateWord.disabled = false;
            } else {
                this.showError('Generate preview gagal: ' + (result.message || 'Unknown error'));
            }

        } catch (error) {
            console.error('Preview generation error:', error);
            this.showError('Terjadi kesalahan: ' + error.message);
        } finally {
            this.hideProgress();
            this.btnGeneratePreview.disabled = false;
        }
    }

    displayPreview(data) {
        if (!this.previewContent) return;

        let html = '<div class="preview-sections">';

        Object.entries(data.sections).forEach(([sectionName, content]) => {
            const title = this.formatSectionTitle(sectionName);
            html += `
                <div class="preview-section">
                    <div class="section-header">
                        <h6>${title}</h6>
                        <button type="button" class="btn-edit-section" data-section="${sectionName}">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                    <div class="section-content" data-section="${sectionName}">
                        ${this.formatContent(content)}
                    </div>
                </div>
            `;
        });

        html += '</div>';

        this.previewContent.innerHTML = html;
        this.previewPanel.style.display = 'block';

        // Bind edit buttons
        this.previewContent.querySelectorAll('.btn-edit-section').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const section = e.target.closest('.btn-edit-section').dataset.section;
                this.editSection(section);
            });
        });
    }

    formatSectionTitle(sectionName) {
        const titles = {
            'latar_belakang': 'Latar Belakang',
            'tujuan': 'Tujuan',
            'program_kerja': 'Program Kerja',
            'pelaksanaan': 'Pelaksanaan',
            'hambatan': 'Hambatan',
            'evaluasi': 'Evaluasi',
            'rekomendasi': 'Rekomendasi'
        };
        return titles[sectionName] || sectionName.replace('_', ' ').toUpperCase();
    }

    formatContent(content) {
        return content.replace(/\n/g, '<br>');
    }

    editSection(sectionName) {
        const contentEl = this.previewContent.querySelector(`[data-section="${sectionName}"]`);
        const currentContent = this.previewSections[sectionName];

        const textarea = document.createElement('textarea');
        textarea.className = 'form-control';
        textarea.rows = 8;
        textarea.value = currentContent;

        const saveBtn = document.createElement('button');
        saveBtn.className = 'btn btn-sm btn-primary mt-2';
        saveBtn.innerHTML = '<i class="bi bi-check"></i> Simpan';
        saveBtn.onclick = () => {
            this.previewSections[sectionName] = textarea.value;
            contentEl.innerHTML = this.formatContent(textarea.value);
        };

        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'btn btn-sm btn-secondary mt-2 ms-2';
        cancelBtn.innerHTML = '<i class="bi bi-x"></i> Batal';
        cancelBtn.onclick = () => {
            contentEl.innerHTML = this.formatContent(currentContent);
        };

        contentEl.innerHTML = '';
        contentEl.appendChild(textarea);
        contentEl.appendChild(saveBtn);
        contentEl.appendChild(cancelBtn);

        textarea.focus();
    }

    async generateWordDocument() {
        // Use existing generate word functionality
        // Pass preview sections as ai_preview_data
        const aiPreviewData = document.getElementById('ai_preview_data');
        if (aiPreviewData) {
            aiPreviewData.value = JSON.stringify({
                sections: this.previewSections,
                draft: Object.values(this.previewSections).join('\n\n')
            });
        }

        // Trigger existing generate word process
        if (typeof window.generateWordDocument === 'function') {
            window.generateWordDocument();
        } else {
            this.showError('Generate Word function not found');
        }
    }

    async loadUsageStats() {
        try {
            const [aiStats, ocrStats] = await Promise.all([
                fetch('/gjm/buat-laporan/ocr/usage').then(r => r.json()),
                fetch('/gjm/buat-laporan/ocr/stats').then(r => r.json())
            ]);

            this.displayUsageStats(aiStats.data, ocrStats.data);
        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    }

    displayUsageStats(aiStats, ocrStats) {
        if (this.aiUsageStats) {
            this.aiUsageStats.innerHTML = `
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-label">AI Provider</div>
                        <div class="stat-value">${aiStats.provider || 'N/A'}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Model</div>
                        <div class="stat-value">${aiStats.model || 'N/A'}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Status</div>
                        <div class="stat-value ${aiStats.api_key_configured ? 'text-success' : 'text-danger'}">
                            ${aiStats.api_key_configured ? 'Ready' : 'Not Configured'}
                        </div>
                    </div>
                </div>
            `;
        }

        if (this.ocrStats) {
            this.ocrStats.innerHTML = `
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-label">OCR Engine</div>
                        <div class="stat-value">${ocrStats.tesseract_available ? 'Tesseract' : 'Not Available'}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Languages</div>
                        <div class="stat-value">${ocrStats.languages?.join(', ') || 'N/A'}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Cache</div>
                        <div class="stat-value">${ocrStats.cache_enabled ? 'Enabled' : 'Disabled'}</div>
                    </div>
                </div>
            `;
        }
    }

    setLaporanId(id) {
        this.laporanId = id;
        if (this.ocrSection) {
            this.ocrSection.style.display = id ? 'block' : 'none';
        }
    }

    showProgress(message) {
        if (this.ocrProgress) {
            this.ocrProgress.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm me-2"></div>
                    <span>${message}</span>
                </div>
            `;
            this.ocrProgress.style.display = 'block';
        }
    }

    hideProgress() {
        if (this.ocrProgress) {
            this.ocrProgress.style.display = 'none';
        }
    }

    showSuccess(message) {
        this.showAlert(message, 'success');
    }

    showError(message) {
        this.showAlert(message, 'danger');
    }

    showAlert(message, type) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        const container = document.querySelector('.col-lg-9') || document.body;
        container.insertBefore(alert, container.firstChild);

        setTimeout(() => alert.remove(), 5000);
    }
}

// Initialize OCR system when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    window.ocrSystem = new OCRUploadSystem();
});