# RAGAS Metrics — Penjelasan dan Rumus

Dokumen ini merangkum definisi, rumus (KaTeX), contoh perhitungan, dan catatan pelaporan untuk metrik yang digunakan dalam evaluasi RAGAS.

---

## 1. Faithfulness

Definisi:
- Proporsi pernyataan dalam jawaban yang dapat ditelusuri dan didukung oleh dokumen sumber (trusted evidence).

Rumus:

$$
\text{Faithfulness} = \frac{|\text{Pernyataan Didukung}|}{|\text{Total Pernyataan}|}
$$

Sebagai persen:

$$
\text{Faithfulness(\%)} = 100 \times \frac{|\text{Pernyataan Didukung}|}{|\text{Total Pernyataan}|}
$$

Penjelasan:
- Unit penilaian biasanya adalah klaim/pernyataan faktual dalam jawaban.
- "Didukung" berarti terdapat kutipan, ayat, atau bagian dokumen yang dapat memverifikasi klaim tersebut.
- Klaim yang ambigu bisa diberi label `uncertain` dan diperlakukan sesuai aturan (mis. dihitung sebagai unsupported jika tidak ada bukti jelas).

Contoh singkat:
- Total pernyataan = 10, Pernyataan didukung = 7 → Faithfulness = 7/10 = 0.70 → 70.0%

---

## 2. Hallucination Rate

Definisi:
- Tingkat klaim atau informasi yang dibuat model tanpa dasar pada dokumen sumber.
- Semakin kecil semakin baik.

Rumus (dalam hubungan langsung dengan Faithfulness):

$$
\text{HallucinationRate} = 1 - \text{Faithfulness}
$$

Sebagai persen:

$$
\text{HallucinationRate(\%)} = 100 \times \left(1 - \frac{|\text{Pernyataan Didukung}|}{|\text{Total Pernyataan}|}\right)
$$

Contoh singkat:
- Dari contoh Faithfulness 70.0% → Hallucination Rate = 30.0%

Catatan operasional:
- Putuskan kebijakan untuk klaim yang "separuh didukung" (mis. sebagian bukti) — apakah dihitung sebagai supported atau unsupported.

---

## 3. Context Precision

Definisi:
- Proporsi konteks (dokumen/snippet/paragraf) yang diambil oleh sistem retrieval yang benar-benar relevan untuk menjawab pertanyaan.

Rumus:

$$
\text{ContextPrecision} = \frac{|\text{RelevantRetrieved}|}{|\text{Retrieved}|}
$$

Sebagai persen:

$$
\text{ContextPrecision(\%)} = 100 \times \frac{|\text{RelevantRetrieved}|}{|\text{Retrieved}|}
$$

Penjelasan:
- Unit bisa berupa snippet, paragraf, atau dokumen; konsistensi unit diperlukan saat melaporkan.
- `Retrieved` = semua unit yang sistem bawa ke proses generation.
- `RelevantRetrieved` = subset dari unit tersebut yang berkontribusi pada jawaban (berisi informasi yang relevan).

Contoh:
- Retrieved = 5 snippet, RelevantRetrieved = 4 → Context Precision = 4/5 = 0.8 → 80.0%

---

## 4. Context Recall

Definisi:
- Proporsi dari seluruh konteks relevan yang tersedia di knowledge base (gold set) yang berhasil ditemukan oleh retrieval.

Rumus:

$$
\text{ContextRecall} = \frac{|\text{RelevantRetrieved}|}{|\text{RelevantInKB}|}
$$

Sebagai persen:

$$
\text{ContextRecall(\%)} = 100 \times \frac{|\text{RelevantRetrieved}|}{|\text{RelevantInKB}|}
$$

Penjelasan:
- `RelevantInKB` memerlukan ground-truth: daftar unit relevan untuk tiap pertanyaan.
- Recall mengukur kelengkapan retrieval — apakah sistem melewatkan dokumen penting.

Contoh:
- RelevantRetrieved = 4, RelevantInKB = 8 → Context Recall = 4/8 = 0.5 → 50.0%

---

## 5. F1 Score

Definisi:
- Skor harmonis antara Precision dan Recall (umumnya dihitung untuk konteks/retrieval atau ekstraksi entitas), yang menyeimbangkan ketepatan dan kelengkapan.

Rumus (Precision/Recall dalam proporsi 0..1):

$$
\text{F1} = 2 \times \frac{\text{Precision} \times \text{Recall}}{\text{Precision} + \text{Recall}}
$$

Sebagai persen kalikan dengan 100.

Contoh (menggunakan Context Precision & Context Recall di atas):
- Precision = 0.8, Recall = 0.5

$$
\text{F1} = 2 \times \frac{0.8 \times 0.5}{0.8 + 0.5} = 0.615384... \approx 61.54\%.
$$

---

## Contoh Lengkap - Skenario Tunggal

Diberikan:
- Total pernyataan (jawaban) = 10
- Pernyataan didukung = 7
- Retrieved snippets = 5
- RelevantRetrieved = 4
- RelevantInKB (gold set) = 8

Perhitungan:
- Faithfulness = $7/10 = 0.7 \rightarrow 70.0\%$
- HallucinationRate = $1-0.7 = 0.3 \rightarrow 30.0\%$
- ContextPrecision = $4/5 = 0.8 \rightarrow 80.0\%$
- ContextRecall = $4/8 = 0.5 \rightarrow 50.0\%$
- F1 = $2\times\frac{0.8\times0.5}{0.8+0.5} \approx 61.54\%$

---

## Rekomendasi Pelaporan

- Tunjukkan definisi unit evaluasi (pernyataan/klaim, snippet/paragraf, dll.).
- Sertakan contoh bukti (claim → dokumen yang mendukung) untuk transparansi.
- Laporkan metrik per-skenario dan ringkasan (mean, median, std) serta ukuran sampel.
- Untuk konteks/retrieval, sertakan contoh gold set untuk beberapa skenario sebagai referensi.
- Format angka sebagai persen dengan 1 desimal (mis. 76.3%).

---

## Catatan Implementasi

- Pastikan normalisasi: beberapa metrik mengharapkan proporsi (0..1) sedangkan UI menampilkan persen (0..100).
- Tetapkan kebijakan annotator untuk klaim ambigu dan partial evidence.
- Saat meng-automate evaluasi, gunakan thresholding yang jelas (mis. apakah "partially supported" = supported?).

---

*File ini dibuat otomatis oleh asisten. Jika mau, saya bisa juga membuat versi PDF atau menambahkan tabel contoh hasil seperti pada gambar.*
