document.addEventListener('DOMContentLoaded', function () {
    console.log('=== Monitoring Kuesioner Search Initialized ===');

    const searchInput = document.getElementById('search_matkul');
    const searchResults = document.getElementById('search_results');
    const searchLoading = document.getElementById('search_loading');
    const selectedMatkulCard = document.getElementById('selected_matkul_card');
    const selectedMatkulDisplay = document.getElementById('selected_matkul_display');
    const selectedMatkulInput = document.getElementById('selected_matkul');
    const infKuesioneCard = document.getElementById('info_kuesioner_card');
    const uploadFileCard = document.getElementById('upload_file_card');
    const aiInfoCard = document.getElementById('ai_info_card');
    const actionButtons = document.getElementById('action_buttons');
    const ubahMatkulBtn = document.getElementById('btn_ubah_matkul');
    const periodeSelect = document.getElementById('periode');

    let selectedMatkul = null;
    let searchTimeout;

    // Validate elements exist
    if (!searchInput) {
        console.error('Search input element not found');
        return;
    }

    console.log('All required elements found');

    // Event listener untuk input pencarian
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        const searchTerm = this.value.trim();

        console.log('Input changed:', searchTerm);

        if (searchTerm.length < 2) {
            searchResults.style.display = 'none';
            return;
        }

        searchLoading.style.display = 'block';
        searchResults.style.display = 'none';

        searchTimeout = setTimeout(() => {
            performSearch(searchTerm);
        }, 300);
    });

    // Close dropdown ketika klik di luar
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.position-relative')) {
            searchResults.style.display = 'none';
        }
    });

    function performSearch(searchTerm) {
        const periode = periodeSelect.value;

        console.log('Performing search:', { searchTerm, periode });

        if (!periode) {
            searchLoading.style.display = 'none';
            searchResults.innerHTML = '<div style="padding: 1rem; color: #dc3545;"><i class="bi bi-exclamation-circle"></i> Pilih periode terlebih dahulu</div>';
            searchResults.style.display = 'block';
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        fetch('/gkm/monitoring-kuesioner/search-matkul', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            body: JSON.stringify({
                search: searchTerm,
                periode: periode
            })
        })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Search results:', data);
                searchLoading.style.display = 'none';

                if (data.success && data.data && data.data.length > 0) {
                    displaySearchResults(data.data);
                } else {
                    searchResults.innerHTML = '<div style="padding: 1rem; color: #6c757d;"><i class="bi bi-search"></i> Tidak ada matakuliah yang ditemukan</div>';
                    searchResults.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                searchLoading.style.display = 'none';
                searchResults.innerHTML = '<div style="padding: 1rem; color: #dc3545;"><i class="bi bi-exclamation-circle"></i> Terjadi kesalahan: ' + error.message + '</div>';
                searchResults.style.display = 'block';
            });
    }

    function displaySearchResults(results) {
        searchResults.innerHTML = '';

        results.forEach(item => {
            const resultItem = document.createElement('div');
            resultItem.style.cssText = 'padding: 0.75rem 1rem; border-bottom: 1px solid #e0e0e0; cursor: pointer; transition: background-color 0.2s;';
            resultItem.onmouseover = () => resultItem.style.backgroundColor = '#f8f9fa';
            resultItem.onmouseout = () => resultItem.style.backgroundColor = 'transparent';

            resultItem.innerHTML = `
                <div style="font-weight: 600; color: #333; margin-bottom: 0.25rem;">
                    ${item.kode_mk} - ${item.nama_matkul}
                </div>
                <div style="font-size: 0.875rem; color: #6c757d; margin-bottom: 0.25rem;">
                    <i class="bi bi-person"></i> ${item.dosen_pengampu}
                </div>
                <div style="font-size: 0.8rem; color: #999;">
                    <i class="bi bi-layers"></i> Tingkat: ${item.tingkat}
                </div>
            `;

            resultItem.addEventListener('click', () => {
                selectMatkul(item);
            });

            searchResults.appendChild(resultItem);
        });

        searchResults.style.display = 'block';
    }

    function selectMatkul(item) {
        console.log('Selected matkul:', item);

        selectedMatkul = item;
        searchInput.value = `${item.kode_mk} - ${item.nama_matkul}`;
        searchResults.style.display = 'none';

        // Update hidden input dengan format: kuliah_id|kode_mk|nama_matkul|dosen_pengampu|pegawai_ids|tingkat
        selectedMatkulInput.value = item.value;

        // Tampilkan kartu matakuliah terpilih
        displaySelectedMatkul(item);

        // Tampilkan kartu informasi kuesioner, upload file dan info AI
        if (infKuesioneCard) {
            infKuesioneCard.style.display = 'block';
        }
        uploadFileCard.style.display = 'block';
        aiInfoCard.style.display = 'block';
        actionButtons.style.display = 'flex';
    }

    function displaySelectedMatkul(item) {
        selectedMatkulDisplay.innerHTML = `
            <tr>
                <td style="padding: 1rem; border: none;">
                    <div style="margin-bottom: 0.5rem;">
                        <strong style="color: #333;">Kode Matakuliah:</strong>
                        <span style="color: #5B9BD5; font-weight: 600;">${item.kode_mk}</span>
                    </div>
                    <div style="margin-bottom: 0.5rem;">
                        <strong style="color: #333;">Nama Matakuliah:</strong>
                        <span>${item.nama_matkul}</span>
                    </div>
                    <div style="margin-bottom: 0.5rem;">
                        <strong style="color: #333;">Dosen Pengampu:</strong>
                        <span>${item.dosen_pengampu}</span>
                    </div>
                    <div>
                        <strong style="color: #333;">Tingkat:</strong>
                        <span>${item.tingkat}</span>
                    </div>
                </td>
            </tr>
        `;

        selectedMatkulCard.style.display = 'block';
    }

    // Event listener untuk tombol ubah pilihan
    if (ubahMatkulBtn) {
        ubahMatkulBtn.addEventListener('click', function () {
            selectedMatkul = null;
            searchInput.value = '';
            selectedMatkulInput.value = '';
            selectedMatkulCard.style.display = 'none';
            infKuesioneCard.style.display = 'none';
            uploadFileCard.style.display = 'none';
            aiInfoCard.style.display = 'none';
            actionButtons.style.display = 'none';
            searchResults.style.display = 'none';
            searchInput.focus();
        });
    }

    // Validasi periode sebelum submit
    const form = document.getElementById('uploadForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (!selectedMatkulInput.value) {
                e.preventDefault();
                alert('Silakan pilih matakuliah terlebih dahulu');
                searchInput.focus();
                return false;
            }

            if (!periodeSelect.value) {
                e.preventDefault();
                alert('Silakan pilih periode terlebih dahulu');
                periodeSelect.focus();
                return false;
            }

            const fileInput = document.getElementById('file_excel');
            if (!fileInput.value) {
                e.preventDefault();
                alert('Silakan pilih file Excel terlebih dahulu');
                fileInput.focus();
                return false;
            }

            const namaFileInput = document.getElementById('nama_file');
            if (!namaFileInput.value) {
                e.preventDefault();
                alert('Silakan isi nama file kuesioner terlebih dahulu');
                namaFileInput.focus();
                return false;
            }
        });
    }
});
