@extends('layouts.app')
@section('page-title', 'AI Assistant - Laporan VMTS')

@section('styles')
<style>
.vmts-ai-wrapper { background:#0f172a; min-height:80vh; border-radius:16px; overflow:hidden; border:1px solid #1e293b; display:flex; flex-direction:column; }
.vmts-ai-header { background:linear-gradient(135deg,#1e40af,#7c3aed); padding:1.25rem 1.5rem; display:flex; align-items:center; gap:1rem; }
.vmts-ai-header .ai-avatar { width:44px;height:44px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem; }
.vmts-ai-header h5 { margin:0;color:#fff;font-weight:700;font-size:1.05rem; }
.vmts-ai-header small { color:rgba(255,255,255,.75);font-size:.78rem; }
.vmts-ai-messages { flex:1;overflow-y:auto;padding:1.5rem;display:flex;flex-direction:column;gap:1.25rem;min-height:400px;max-height:550px; }
.vmts-ai-messages::-webkit-scrollbar{width:5px} .vmts-ai-messages::-webkit-scrollbar-track{background:rgba(255,255,255,.05)} .vmts-ai-messages::-webkit-scrollbar-thumb{background:rgba(99,102,241,.4);border-radius:10px}
.msg-user{display:flex;justify-content:flex-end}
.msg-user .bubble{background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border-radius:18px 18px 4px 18px;padding:.8rem 1.1rem;max-width:75%;font-size:.875rem;line-height:1.6;word-break:break-word;white-space:pre-wrap}
.msg-ai{display:flex;align-items:flex-start;gap:.75rem}
.msg-ai .ai-icon{width:34px;height:34px;flex-shrink:0;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.9rem}
.msg-ai .bubble{background:#1e293b;border:1px solid #334155;border-radius:4px 16px 16px 16px;padding:.9rem 1.1rem;max-width:90%;font-size:.875rem;line-height:1.7;color:#e2e8f0}
.msg-ai .bubble h2,.msg-ai .bubble h3,.msg-ai .bubble h4{color:#93c5fd;font-weight:700;margin:.75rem 0 .35rem;font-size:.92rem}
.msg-ai .bubble p{margin:0 0 .5rem} .msg-ai .bubble ul,.msg-ai .bubble ol{padding-left:1.2rem;margin:.25rem 0} .msg-ai .bubble li{margin-bottom:.2rem} .msg-ai .bubble strong{color:#a5f3fc} .msg-ai .bubble hr{border-color:#334155;margin:.75rem 0}
.msg-ai .bubble table{width:100%;border-collapse:collapse;margin:.75rem 0;font-size:.82rem} .msg-ai .bubble th{background:#1e40af;color:#fff;padding:.45rem .65rem;text-align:left} .msg-ai .bubble td{padding:.4rem .65rem;border-bottom:1px solid #334155;color:#cbd5e1} .msg-ai .bubble tr:hover td{background:rgba(255,255,255,.03)}
.typing-dots{display:none;align-items:center;gap:.5rem} .typing-dots.show{display:flex} .typing-dots span{width:7px;height:7px;background:#64748b;border-radius:50%;animation:tb 1.2s infinite} .typing-dots span:nth-child(2){animation-delay:.2s} .typing-dots span:nth-child(3){animation-delay:.4s}
@keyframes tb{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-6px)}}
.vmts-input-area{padding:1.25rem 1.5rem;background:#0f172a;border-top:1px solid #1e293b}
.upload-grid{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:.85rem}
.upload-zone{border:1.5px dashed #334155;border-radius:10px;padding:.85rem 1rem;cursor:pointer;transition:all .2s;background:#1e293b;text-align:center;font-size:.82rem;color:#94a3b8}
.upload-zone:hover{border-color:#6366f1;background:#1e2a3d;color:#a5b4fc}
.upload-zone.has-file{border-color:#10b981;background:#064e3b20;color:#6ee7b7}
.upload-zone .icon{font-size:1.5rem;margin-bottom:.3rem;display:block}
.input-row{display:flex;align-items:flex-end;gap:.5rem}
.vmts-textarea{flex:1;background:#1e293b;border:1.5px solid #334155;border-radius:10px;padding:.65rem .9rem;font-size:.875rem;resize:none;color:#e2e8f0;min-height:52px;max-height:150px;transition:border-color .2s;line-height:1.6}
.vmts-textarea:focus{border-color:#6366f1;outline:none;box-shadow:0 0 0 3px rgba(99,102,241,.15)}
.vmts-textarea::placeholder{color:#64748b}
.btn-send{background:linear-gradient(135deg,#6366f1,#4f46e5);border:none;border-radius:10px;width:44px;height:44px;display:flex;align-items:center;justify-content:center;color:#fff;cursor:pointer;transition:all .2s;flex-shrink:0}
.btn-send:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(99,102,241,.35)}
.btn-send:disabled{opacity:.5;cursor:not-allowed;transform:none}
.file-chips{display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:.5rem}
.fchip{display:inline-flex;align-items:center;gap:.3rem;background:#1e293b;border:1px solid #334155;border-radius:8px;padding:.3rem .6rem;font-size:.75rem;color:#94a3b8}
.fchip .rm{cursor:pointer;color:#f87171;margin-left:.2rem;font-weight:700} .fchip .rm:hover{color:#ef4444}
.btn-dl-word{background:linear-gradient(135deg,#059669,#047857);color:#fff;border:none;border-radius:10px;padding:.6rem 1.1rem;font-size:.85rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:.45rem;transition:all .2s;margin-top:.75rem;width:100%;justify-content:center}
.btn-dl-word:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(5,150,105,.35)}
.btn-dl-word:disabled{opacity:.6;cursor:not-allowed;transform:none}
.info-badge{background:#1e293b;border:1px solid #334155;border-radius:8px;padding:.6rem 1rem;font-size:.8rem;color:#94a3b8;margin-bottom:.75rem}
.info-badge strong{color:#a5b4fc}
</style>
@endsection

@section('content')
<div style="padding:1.5rem">
  <!-- Header -->
  <div class="filter-card mb-4">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h5 class="mb-1 d-flex align-items-center gap-2" style="font-weight:700;color:#333">
          <i class="bi bi-robot" style="color:#6366f1;font-size:2rem"></i>
          <div>
            <div style="font-size:1.4rem">AI Assistant — Laporan VMTS</div>
            <p class="text-muted mb-0" style="font-size:.9rem">Upload Excel data kuesioner + contoh laporan (PDF/Word) → AI langsung analisis & hasilkan laporan</p>
          </div>
        </h5>
      </div>
      <a href="{{ route('gjm.buat-laporan.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>
  </div>

  <div class="row">
    <div class="col-xl-10 col-lg-12 mx-auto">

      <!-- Guide -->
      <div class="monitoring-card mb-4" style="border-left:4px solid #6366f1">
        <div style="padding:1.25rem">
          <div class="d-flex align-items-start">
            <i class="bi bi-info-circle-fill" style="color:#6366f1;font-size:1.6rem;margin-right:1rem;flex-shrink:0"></i>
            <div style="font-size:.875rem;color:#495057;line-height:1.8">
              <strong style="color:#333">Cara Penggunaan:</strong><br>
              1. <strong>Upload file Excel</strong> berisi data kuesioner/hasil survei VMTS<br>
              2. <strong>Upload file PDF/Word</strong> sebagai contoh laporan sebelumnya (opsional tapi direkomendasikan)<br>
              3. Tambahkan instruksi tambahan jika diperlukan, lalu klik <strong>Kirim</strong><br>
              4. AI akan langsung menganalisis dan menghasilkan laporan — klik <strong>Download Word</strong> untuk mengunduh
            </div>
          </div>
        </div>
      </div>

      <!-- AI Chat -->
      <div class="monitoring-card mb-4" style="overflow:hidden">
        <div class="vmts-ai-wrapper">
          <!-- Header -->
          <div class="vmts-ai-header">
            <div class="ai-avatar"><i class="bi bi-stars"></i></div>
            <div>
              <h5>AI Assistant — Laporan VMTS</h5>
              <small>Analisis kuesioner survei → Laporan akademik profesional</small>
            </div>
          </div>

          <!-- Messages -->
          <div class="vmts-ai-messages" id="vmts-messages"></div>

          <!-- Typing -->
          <div style="padding:0 1.5rem .5rem">
            <div class="typing-dots" id="typing-indicator">
              <div class="msg-ai" style="margin:0">
                <div class="ai-icon"><i class="bi bi-stars"></i></div>
                <div class="bubble" style="padding:.6rem .9rem">
                  <span></span><span></span><span></span>
                </div>
              </div>
            </div>
          </div>

          <!-- Input area -->
          <div class="vmts-input-area">
            <!-- Hidden file inputs -->
            <input type="file" id="excel-input" accept=".xlsx,.xls" multiple style="display:none">
            <input type="file" id="ref-input" accept=".docx,.doc,.pdf,.txt" multiple style="display:none">

            <!-- Upload zones -->
            <div class="upload-grid">
              <div class="upload-zone" id="excel-zone" onclick="document.getElementById('excel-input').click()">
                <span class="icon">📊</span>
                <strong>File Excel</strong><br>
                <span id="excel-label">Data kuesioner (.xlsx/.xls)</span>
              </div>
              <div class="upload-zone" id="ref-zone" onclick="document.getElementById('ref-input').click()">
                <span class="icon">📄</span>
                <strong>Contoh Laporan</strong><br>
                <span id="ref-label">Laporan sebelumnya (.docx/.pdf)</span>
              </div>
            </div>

            <!-- File chips -->
            <div class="file-chips" id="file-chips"></div>

            <!-- Text input row -->
            <div class="input-row">
              <textarea class="vmts-textarea" id="vmts-prompt" placeholder="Tambahkan instruksi (opsional): mis. 'Fokus pada prodi D3TK' atau 'Buat lebih singkat'..." rows="1"></textarea>
              <button class="btn-send" id="btn-send" title="Kirim">
                <i class="bi bi-send-fill"></i>
              </button>
            </div>

            <div style="font-size:.72rem;color:#475569;margin-top:.5rem;text-align:center">
              Tekan Enter untuk kirim · Shift+Enter untuk baris baru
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-start gap-2 mb-4">
        <a href="{{ route('gjm.buat-laporan.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Batal</a>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const messagesBox = document.getElementById('vmts-messages');
  const promptInput = document.getElementById('vmts-prompt');
  const btnSend     = document.getElementById('btn-send');
  const typingEl    = document.getElementById('typing-indicator');
  const excelInput  = document.getElementById('excel-input');
  const refInput    = document.getElementById('ref-input');

  let excelFiles = [];
  let refFiles   = [];
  let conversationHistory = [];
  let lastAiResponse = '';

  // ── Welcome message ──────────────────────────────────────────────────────
  appendAI(
    '<strong>👋 Selamat datang di AI Assistant Laporan VMTS!</strong><br><br>' +
    'Saya dapat membantu Anda menganalisis data kuesioner survei VMTS dan menghasilkan laporan analisis profesional.<br><br>' +
    '📊 <strong>Upload file Excel</strong> berisi data kuesioner<br>' +
    '📄 <strong>Upload file contoh laporan</strong> sebagai referensi format (opsional)<br>' +
    '✍️ Tambahkan instruksi tambahan jika perlu<br>' +
    '⬆️ Klik tombol kirim → AI langsung menganalisis!'
  );

  // ── File input handlers ──────────────────────────────────────────────────
  excelInput.addEventListener('change', function () {
    excelFiles = [...excelFiles, ...Array.from(this.files)];
    this.value = '';
    updateZones();
    updateChips();
  });

  refInput.addEventListener('change', function () {
    refFiles = [...refFiles, ...Array.from(this.files)];
    this.value = '';
    updateZones();
    updateChips();
  });

  function updateZones() {
    const ez = document.getElementById('excel-zone');
    const rz = document.getElementById('ref-zone');
    const el = document.getElementById('excel-label');
    const rl = document.getElementById('ref-label');

    if (excelFiles.length > 0) {
      ez.classList.add('has-file');
      el.textContent = excelFiles.map(f => f.name).join(', ');
    } else {
      ez.classList.remove('has-file');
      el.textContent = 'Data kuesioner (.xlsx/.xls)';
    }
    if (refFiles.length > 0) {
      rz.classList.add('has-file');
      rl.textContent = refFiles.map(f => f.name).join(', ');
    } else {
      rz.classList.remove('has-file');
      rl.textContent = 'Laporan sebelumnya (.docx/.pdf)';
    }
  }

  function updateChips() {
    const chips = document.getElementById('file-chips');
    chips.innerHTML = '';
    excelFiles.forEach((f, i) => {
      chips.innerHTML += `<div class="fchip">📊 ${f.name}<span class="rm" onclick="removeExcel(${i})">×</span></div>`;
    });
    refFiles.forEach((f, i) => {
      chips.innerHTML += `<div class="fchip">📄 ${f.name}<span class="rm" onclick="removeRef(${i})">×</span></div>`;
    });
  }

  window.removeExcel = function(i) { excelFiles.splice(i,1); updateZones(); updateChips(); };
  window.removeRef   = function(i) { refFiles.splice(i,1);   updateZones(); updateChips(); };

  // ── Auto-resize textarea ─────────────────────────────────────────────────
  promptInput.addEventListener('input', function () {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 150) + 'px';
  });
  promptInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); btnSend.click(); }
  });

  // ── Send ─────────────────────────────────────────────────────────────────
  btnSend.addEventListener('click', async function () {
    const prompt     = promptInput.value.trim();
    const totalFiles = excelFiles.length + refFiles.length;
    const hasConversation = conversationHistory.length > 0;

    console.log('🔍 VALIDATION CHECK:', {
      prompt: prompt,
      promptLength: prompt.length,
      totalFiles: totalFiles,
      hasConversation: hasConversation,
      excelFiles: excelFiles.length,
      refFiles: refFiles.length
    });

    // VALIDATION 1: Interaksi pertama wajib upload file
    if (!hasConversation && totalFiles === 0) {
      console.warn('❌ VALIDATION FAILED: No files uploaded for first interaction');
      appendAI(`
        <div style="background:#450a0a;border-left:4px solid #dc2626;padding:15px;border-radius:6px;">
          <p style="color:#fca5a5;margin:0;font-weight:700;font-size:.95rem;">
            <i class="bi bi-exclamation-triangle-fill"></i> UPLOAD FILE TERLEBIH DAHULU!
          </p>
          <p style="color:#fca5a5;margin:10px 0 0 0;line-height:1.6;">
            Untuk chat pertama dengan AI, Anda <strong>WAJIB upload file</strong> terlebih dahulu.
          </p>
          <p style="color:#fca5a5;margin:10px 0 0 0;line-height:1.6;">
            <strong>File yang didukung:</strong>
          </p>
          <ul style="color:#fca5a5;margin:8px 0 0 20px;line-height:1.6;">
            <li>📊 Data kuesioner: XLSX, XLS (wajib ada)</li>
            <li>📄 Contoh laporan: PDF, DOCX (direkomendasikan)</li>
          </ul>
          <p style="color:#fca5a5;margin:10px 0 0 0;line-height:1.6;">
            <strong>Cara upload:</strong> Klik zona upload <strong>📊 File Excel</strong> atau <strong>📄 Contoh Laporan</strong> di atas.
          </p>
          <p style="color:#fca5a5;margin:10px 0 0 0;font-style:italic;">
            Setelah upload file, baru Anda bisa chat dengan AI.
          </p>
        </div>
      `);
      return;
    }

    // VALIDATION 2: Ada file tapi tidak ada instruksi
    if (!prompt || prompt.length === 0) {
      if (totalFiles > 0) {
        console.warn('❌ VALIDATION FAILED: Files uploaded without instruction');
        appendAI(`
          <div style="background:#450a0a;border-left:4px solid #dc2626;padding:15px;border-radius:6px;">
            <p style="color:#fca5a5;margin:0;font-weight:700;font-size:.95rem;">
              <i class="bi bi-exclamation-triangle-fill"></i> INSTRUKSI WAJIB DIISI!
            </p>
            <p style="color:#fca5a5;margin:10px 0 0 0;line-height:1.6;">
              Anda telah mengupload <strong>${totalFiles} file</strong>, tetapi belum memberikan instruksi.
            </p>
            <p style="color:#fca5a5;margin:10px 0 0 0;line-height:1.6;">
              <strong>Silakan ketik instruksi Anda</strong>, misalnya:
            </p>
            <ul style="color:#fca5a5;margin:8px 0 0 20px;line-height:1.6;">
              <li>"Analisis data Excel dan buat laporan VMTS lengkap"</li>
              <li>"Buat laporan berdasarkan kuesioner yang diupload"</li>
              <li>"Ekstrak data survei dan analisis per butir pertanyaan"</li>
            </ul>
            <p style="color:#fca5a5;margin:10px 0 0 0;font-style:italic;">
              File tidak akan diproses tanpa instruksi yang jelas.
            </p>
          </div>
        `);
        setTimeout(() => { promptInput.focus(); }, 100);
        return;
      } else {
        console.warn('❌ VALIDATION FAILED: No instruction provided');
        appendAI(`
          <div style="background:#422006;border-left:4px solid #f59e0b;padding:12px;border-radius:6px;">
            <p style="color:#fde68a;margin:0;font-weight:600;">
              <i class="bi bi-info-circle-fill"></i> Silakan masukkan instruksi atau pertanyaan Anda
            </p>
          </div>
        `);
        setTimeout(() => { promptInput.focus(); }, 100);
        return;
      }
    }

    // VALIDATION 3: Instruksi terlalu pendek
    if (prompt.length < 5) {
      console.warn('❌ VALIDATION FAILED: Instruction too short (' + prompt.length + ' chars)');
      appendAI(`
        <div style="background:#450a0a;border-left:4px solid #dc2626;padding:15px;border-radius:6px;">
          <p style="color:#fca5a5;margin:0;font-weight:700;">
            <i class="bi bi-exclamation-triangle-fill"></i> Instruksi terlalu singkat!
          </p>
          <p style="color:#fca5a5;margin:10px 0 0 0;line-height:1.6;">
            Instruksi Anda hanya <strong>${prompt.length} karakter</strong>.<br>
            Silakan berikan instruksi yang lebih jelas dan spesifik (minimal 5 karakter).
          </p>
          <p style="color:#fca5a5;margin:10px 0 0 0;line-height:1.6;">
            <strong>Contoh instruksi yang baik:</strong>
          </p>
          <ul style="color:#fca5a5;margin:8px 0 0 20px;line-height:1.6;">
            <li>"Buat laporan VMTS dari data Excel ini"</li>
            <li>"Analisis hasil survei kuesioner"</li>
            <li>"Buat ringkasan temuan"</li>
          </ul>
        </div>
      `);
      setTimeout(() => { promptInput.focus(); }, 100);
      return;
    }

    // VALIDATION 4: Prompt tidak relevan dengan laporan VMTS
    const isPromptRelevantVMTS = (function(text) {
      const t = text.toLowerCase();

      // Pola sapaan / obrolan umum yang TIDAK relevan
      const offTopicPatterns = [
        /^(hai|halo|hello|hi|hey|hei|holas?)\b/,
        /^(apa kabar|how are you|selamat pagi|selamat siang|selamat malam|selamat sore)\b/,
        /^(siapa kamu|siapa anda|kamu siapa|anda siapa|nama kamu|nama anda)\b/,
        /^(aku|saya|gue|gw)\s+(adalah|aku|bernama|namaku|namanya)\b/,
        /^(test|tes|coba|cobaan|testing|hello world)\b/,
        /^(ok|oke|okay|iya|ya|yep|yup|sip|baik|bagus|mantap|keren)\s*[.!]*$/,
        /^(terima kasih|makasih|thanks|thank you)\s*[.!]*$/,
        /^(tolong bantu|bantu saya|help me|bantuin)\s*$/,
      ];

      // Kata kunci yang RELEVAN dengan laporan VMTS
      const relevantKeywords = [
        'laporan', 'vmts', 'visi', 'misi', 'tujuan', 'sasaran',
        'kuesioner', 'survei', 'survey', 'analisis', 'analisa',
        'excel', 'data', 'hasil', 'buat', 'generate', 'tulis',
        'periode', 'fakultas', 'prodi', 'program studi', 'responden',
        'distribusi', 'persentase', 'statistik', 'mean', 'median',
        'rekomendasi', 'kesimpulan', 'pembahasan', 'pendahuluan',
        'sosialisasi', 'pemahaman', 'perbaiki', 'revisi', 'ubah',
        'tambah', 'lengkapi', 'ringkasan', 'rangkum', 'ekstrak',
        'download', 'word', 'dokumen', 'file', 'upload', 'referensi'
      ];

      // Jika mengandung kata kunci relevan → langsung lolos
      if (relevantKeywords.some(kw => t.includes(kw))) return true;

      // Cek pola off-topic
      for (const pat of offTopicPatterns) {
        if (pat.test(t.trim())) return false;
      }

      // Default: izinkan jika panjang > 20 karakter
      return text.length > 20;
    })(prompt);

    if (!isPromptRelevantVMTS) {
      console.warn('❌ VALIDATION FAILED: Prompt not relevant to VMTS report');
      appendAI(`
        <div style="background:#422006;border-left:4px solid #f59e0b;padding:15px;border-radius:6px;">
          <p style="color:#fde68a;margin:0;font-weight:700;">
            <i class="bi bi-exclamation-triangle-fill"></i> Instruksi tidak relevan dengan Laporan VMTS
          </p>
          <p style="color:#fde68a;margin:10px 0 0 0;line-height:1.6;">
            AI Assistant ini khusus untuk membantu pembuatan <strong>Laporan VMTS (Visi, Misi, Tujuan, dan Sasaran)</strong>.
            Silakan berikan instruksi yang berkaitan dengan laporan.
          </p>
          <p style="color:#fde68a;margin:10px 0 0 0;line-height:1.6;">
            <strong>Contoh instruksi yang tepat:</strong>
          </p>
          <ul style="color:#fde68a;margin:8px 0 0 20px;line-height:1.6;">
            <li>"Buat laporan VMTS berdasarkan data kuesioner yang diupload"</li>
            <li>"Analisis hasil survei dan buat laporan lengkap"</li>
            <li>"Perbaiki bagian kesimpulan agar lebih detail"</li>
            <li>"Ekstrak data dari Excel dan analisis per butir pertanyaan"</li>
          </ul>
        </div>
      `);
      setTimeout(() => { promptInput.focus(); }, 100);
      return;
    }

    console.log('✅ VALIDATION PASSED: Proceeding with request');

    // Show user message
    const allFileNames = [...excelFiles.map(f=>f.name), ...refFiles.map(f=>f.name)];
    appendUser(prompt || '(File dikirim)', allFileNames);

    const capturedExcel = [...excelFiles];
    const capturedRef   = [...refFiles];
    excelFiles = []; refFiles = [];
    updateZones(); updateChips();
    promptInput.value = ''; promptInput.style.height = 'auto';

    showTyping();
    btnSend.disabled = true;
    btnSend.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    try {
      const fd = new FormData();
      fd.append('_token', '{{ csrf_token() }}');
      fd.append('prompt', prompt);
      if (conversationHistory.length > 0) {
        fd.append('conversation_history', JSON.stringify(conversationHistory));
      }
      capturedExcel.forEach(f => fd.append('excel_files[]', f));
      capturedRef.forEach(f   => fd.append('ref_files[]', f));

      const ctrl = new AbortController();
      const tId  = setTimeout(() => ctrl.abort(), 120000);

      let response;
      try {
        response = await fetch('{{ route("gjm.buat-laporan.vmts-ai.chat") }}', {
          method: 'POST', body: fd,
          headers: { 'Accept': 'application/json' },
          signal: ctrl.signal
        });
      } finally { clearTimeout(tId); }

      const data = await response.json();

      if (data.success) {
        lastAiResponse = data.preview;
        conversationHistory.push({ role:'user', content: prompt || '(file dikirim)' });
        conversationHistory.push({ role:'assistant', content: data.preview });

        appendAIWithDownload(data.preview);
      } else {
        appendAI('<strong style="color:#f87171">❌ Error:</strong> ' + (data.message || 'Terjadi kesalahan'));
      }
    } catch (err) {
      if (err.name === 'AbortError') {
        appendAI('<strong style="color:#fbbf24">⏱️ Timeout:</strong> Permintaan melebihi 2 menit. Coba kurangi ukuran file atau coba lagi.');
      } else {
        appendAI('<strong style="color:#f87171">❌ Error:</strong> ' + err.message);
      }
    } finally {
      hideTyping();
      btnSend.disabled = false;
      btnSend.innerHTML = '<i class="bi bi-send-fill"></i>';
    }
  });

  // ── Download Word ────────────────────────────────────────────────────────
  window.downloadWord = async function (btn) {
    if (!lastAiResponse) {
      appendAI('<span style="color:#fbbf24">⚠️ Belum ada laporan untuk diunduh.</span>');
      return;
    }
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';

    try {
      const fd = new FormData();
      fd.append('_token', '{{ csrf_token() }}');
      fd.append('laporan_text', lastAiResponse);
      fd.append('judul', 'Laporan Analisis Data Hasil Survei VMTS');

      const res = await fetch('{{ route("gjm.buat-laporan.vmts-ai.download-word") }}', {
        method:'POST', body:fd
      });

      const ct = res.headers.get('content-type');
      if (ct && (ct.includes('application/vnd.openxmlformats') || ct.includes('application/octet-stream'))) {
        const blob = await res.blob();
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href = url;
        const cd = res.headers.get('content-disposition');
        let fn = 'Laporan_VMTS_' + Date.now() + '.docx';
        if (cd) { const m = cd.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/); if (m) fn = m[1].replace(/['"]/g,''); }
        a.download = fn;
        document.body.appendChild(a); a.click();
        URL.revokeObjectURL(url); document.body.removeChild(a);
        appendAI('<p style="color:#6ee7b7"><i class="bi bi-check-circle-fill"></i> <strong>File Word berhasil diunduh!</strong><br><small>📁 Cek folder Downloads Anda: <strong>' + fn + '</strong></small></p>');
      } else {
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Gagal membuat Word');
      }
    } catch (err) {
      appendAI('<strong style="color:#f87171">❌ Gagal download:</strong> ' + err.message);
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-file-earmark-word-fill"></i> Download Laporan Word';
    }
  };

  // ── Helpers ──────────────────────────────────────────────────────────────
  function appendUser(text, fileNames) {
    const d = document.createElement('div');
    d.className = 'msg-user';
    let chips = '';
    if (fileNames && fileNames.length > 0) {
      chips = '<div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.5rem">';
      fileNames.forEach(n => {
        const icon = n.match(/\.(xlsx|xls)$/i) ? '📊' : '📄';
        chips += `<span style="background:rgba(255,255,255,.15);border-radius:8px;padding:.2rem .55rem;font-size:.75rem">${icon} ${n}</span>`;
      });
      chips += '</div>';
    }
    d.innerHTML = `<div class="bubble">${(text||'').replace(/\n/g,'<br>')}${chips}</div>`;
    messagesBox.appendChild(d);
    messagesBox.scrollTop = messagesBox.scrollHeight;
  }

  function appendAI(html) {
    const d = document.createElement('div');
    d.className = 'msg-ai';
    d.innerHTML = `<div class="ai-icon"><i class="bi bi-stars"></i></div><div class="bubble">${html}</div>`;
    messagesBox.appendChild(d);
    messagesBox.scrollTop = messagesBox.scrollHeight;
  }

  function appendAIWithDownload(markdownText) {
    const d = document.createElement('div');
    d.className = 'msg-ai';
    d.innerHTML = `
      <div class="ai-icon"><i class="bi bi-stars"></i></div>
      <div class="bubble">
        ${renderMd(markdownText)}
        <hr style="border-color:#334155;margin:1rem 0">
        <button class="btn-dl-word" onclick="downloadWord(this)">
          <i class="bi bi-file-earmark-word-fill"></i> Download Laporan Word
        </button>
      </div>`;
    messagesBox.appendChild(d);
    messagesBox.scrollTop = messagesBox.scrollHeight;
  }

  function showTyping() { typingEl.classList.add('show'); }
  function hideTyping()  { typingEl.classList.remove('show'); }

  function renderMd(text) {
    if (!text) return '';
    // Tables: detect lines with | and render as HTML table
    text = renderTables(text);
    let html = text
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
      .replace(/\*\*\*(.+?)\*\*\*/g,'<strong><em>$1</em></strong>')
      .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
      .replace(/\*(.+?)\*/g,'<em>$1</em>')
      .replace(/^#### (.+)$/gm,'<h4>$1</h4>')
      .replace(/^### (.+)$/gm,'<h4>$1</h4>')
      .replace(/^## (.+)$/gm,'<h3>$1</h3>')
      .replace(/^# (.+)$/gm,'<h2>$1</h2>')
      .replace(/^---+$/gm,'<hr>')
      .replace(/^[•\-\*] (.+)$/gm,'<li>$1</li>')
      .replace(/^\d+\. (.+)$/gm,'<li>$1</li>')
      .replace(/\n\n/g,'</p><p>')
      .replace(/\n/g,'<br>');
    html = html.replace(/(<li>[\s\S]+?<\/li>)/g,'<ul>$1</ul>');
    return '<p>' + html + '</p>';
  }

  function renderTables(text) {
    // Replace markdown tables with HTML tables before other markdown processing
    const tableRegex = /((\|.+\|\n?)+)/g;
    return text.replace(tableRegex, function(match) {
      const rows = match.trim().split('\n').filter(r => r.trim());
      const dataRows = rows.filter(r => !/^\|[\s\-:]+\|/.test(r.trim()));
      if (dataRows.length < 1) return match;
      let html = '<div style="overflow-x:auto;margin:.75rem 0"><table>';
      dataRows.forEach((row, ri) => {
        const cells = row.replace(/^\||\|$/g,'').split('|').map(c => c.trim());
        const tag = ri === 0 ? 'th' : 'td';
        html += '<tr>' + cells.map(c => `<${tag}>${c}</${tag}>`).join('') + '</tr>';
      });
      html += '</table></div>';
      return html;
    });
  }
});
</script>
@endsection
