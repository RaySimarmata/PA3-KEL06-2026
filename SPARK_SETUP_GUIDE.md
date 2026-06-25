# Spark Setup Guide - Dashboard GJM

## Masalah
Ketika klik tombol "Jalankan Analisis Spark" di Dashboard GJM, muncul error:
```
The system cannot find the path specified.
```

## Root Cause
Berdasarkan `php artisan spark:test-config`:
1. ❌ **Spark tidak terinstall** - `spark-submit` tidak ditemukan di PATH
2. ❌ **PySpark tidak terinstall** - Python package PySpark belum ada
3. ✅ Python sudah OK - `C:\Python312\python.exe`

---

## 🚀 Solusi 1: Install Spark & PySpark (RECOMMENDED)

### Step 1: Install Apache Spark

#### **Download Spark**
1. Buka: https://spark.apache.org/downloads.html
2. Pilih:
   - Spark release: **3.5.0** (latest stable)
   - Package type: **Pre-built for Apache Hadoop 3.3**
3. Download file: `spark-3.5.0-bin-hadoop3.tgz`

#### **Extract Spark**
1. Extract file `.tgz` menggunakan 7-Zip atau WinRAR
2. Copy folder hasil extract ke `C:\spark`
3. Struktur folder harus seperti ini:
   ```
   C:\spark\
   ├── bin\
   │   ├── spark-submit.cmd
   │   └── ...
   ├── conf\
   ├── jars\
   └── ...
   ```

#### **Set Environment Variable (OPTIONAL)**
Tambahkan ke System PATH (agar bisa jalankan dari mana saja):
1. Windows Search → "Environment Variables"
2. System Properties → Environment Variables
3. Pada "System variables" → Edit "Path"
4. Tambahkan: `C:\spark\bin`
5. OK dan restart terminal

### Step 2: Install PySpark

Buka terminal/CMD dan jalankan:
```bash
pip install pyspark
```

Atau jika menggunakan specific Python:
```bash
C:\Python312\python.exe -m pip install pyspark
```

### Step 3: Update .env

Edit file `.env` di project:
```env
# Spark Configuration
SPARK_HOME=C:\spark
SPARK_SUBMIT_CMD=spark-submit
PYTHON_EXECUTABLE=C:\Python312\python.exe
PYSPARK_PYTHON=C:\Python312\python.exe
```

### Step 4: Verify Configuration

Jalankan test:
```bash
php artisan spark:test-config
```

Expected output:
```
✓ Python executable found
✓ Spark found
✓ Python script found
✓ PySpark installed
✓ All checks passed!
```

### Step 5: Test Spark Analysis

1. Buka Dashboard GJM
2. Klik "Jalankan Analisis Spark"
3. Tunggu proses selesai (1-5 menit)
4. Heatmap akan ter-update

---

## 🔧 Solusi 2: Gunakan Artisan Command (ALTERNATIF)

Jika tidak ingin install Spark GUI, jalankan script Python langsung:

### Step 1: Install Dependencies

```bash
pip install pyspark pymongo
```

### Step 2: Jalankan Script Manual

```bash
cd "c:\Semester 6\PA3"
python spark\spark_kuesioner.py
```

### Step 3: Clear Cache Dashboard

```bash
php artisan gjm:debug-dashboard --clear-cache
```

### Step 4: Refresh Dashboard

Buka dashboard GJM dan data akan ter-update.

---

## 🐍 Solusi 3: Buat Command Artisan (TANPA SPARK)

Jika tidak mau repot install Spark, kita bisa membuat Artisan command yang melakukan analisis tanpa Spark.

### Create Command

```bash
php artisan make:command AnalisisKuesioneNoSpark
```

### Implementation

File: `app/Console/Commands/AnalisisKuesioneNoSpark.php`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KuesionesMongo;
use App\Models\HasilAnalisisMongo;

class AnalisisKuesioneNoSpark extends Command
{
    protected $signature = 'gjm:analisis-no-spark';
    protected $description = 'Analisis kuesioner tanpa Spark (native PHP)';

    public function handle()
    {
        $this->info('Memulai analisis...');
        
        // Get kuesioner yang belum dianalisis
        $kuesioners = KuesionesMongo::where('is_analyzed', false)->get();
        
        $this->info("Total kuesioner: " . $kuesioners->count());
        
        $bar = $this->output->createProgressBar($kuesioners->count());
        
        foreach ($kuesioners as $kuesioner) {
            // Proses analisis (implementasi logic dari Spark Python)
            // ... (convert logic Python ke PHP)
            
            // Mark as analyzed
            $kuesioner->update([
                'is_analyzed' => true,
                'analyzed_at' => now()
            ]);
            
            $bar->advance();
        }
        
        $bar->finish();
        
        $this->info("\n✓ Analisis selesai!");
        
        // Clear cache
        \Cache::forget('filter_list_tahun');
        \Cache::forget('filter_list_prodi');
        
        return 0;
    }
}
```

### Modify Controller

Update `DashboardController::Analisis()`:

```php
public function Analisis()
{
    try {
        // Run Artisan command instead
        \Artisan::call('gjm:analisis-no-spark');
        
        $output = \Artisan::output();
        
        \Log::info('Analysis output: ' . $output);
        
        // Clear cache
        Cache::forget('filter_list_tahun');
        Cache::forget('filter_list_prodi');
        
        return back()->with(
            'success',
            'Analisis berhasil dijalankan! Cache telah dibersihkan.'
        );
    } catch (\Exception $e) {
        return back()->with(
            'error',
            'Terjadi kesalahan: ' . $e->getMessage()
        );
    }
}
```

---

## 📝 Troubleshooting

### Error: "Spark not found"

**Check:**
```bash
where spark-submit
```

**Fix:**
- Install Spark (Solusi 1)
- Atau set `SPARK_HOME` di .env
- Atau gunakan Solusi 2/3

### Error: "PySpark not installed"

**Fix:**
```bash
pip install pyspark
```

### Error: "Python not found"

**Check:**
```bash
where python
```

**Fix:**
Update `.env`:
```env
PYTHON_EXECUTABLE=C:\Python312\python.exe
```

### Error: "MongoDB connection failed"

**Check:**
- MONGO_URI di `spark/spark_kuesioner.py`
- Koneksi internet
- MongoDB credentials

**Fix:**
Update MONGO_URI di script Python

### Script berjalan lama

**Normal:** Analisis 10,000+ records bisa 1-5 menit

**Check progress:**
```bash
tail -f storage/logs/laravel.log
```

---

## 🎯 Recommended Solution

### **For Development:**
**Solusi 2** - Jalankan Python script langsung
- Tidak perlu install Spark
- Cepat dan simple
- Cocok untuk testing

### **For Production:**
**Solusi 1** - Full Spark setup
- Scalable untuk data besar
- Distributed processing
- Professional setup

### **For Simple Use:**
**Solusi 3** - PHP native (Artisan command)
- No external dependencies
- Easy maintenance
- Good enough untuk < 50k records

---

## ✅ Quick Start (Fastest)

Jika Anda tidak mau repot install Spark:

```bash
# 1. Install Python packages
pip install pyspark pymongo

# 2. Update .env
PYTHON_EXECUTABLE=C:\Python312\python.exe

# 3. Jalankan script langsung
python spark\spark_kuesioner.py

# 4. Clear cache
php artisan gjm:debug-dashboard --clear-cache

# 5. Refresh dashboard
```

Selesai! ✨

---

## 📚 References

- Apache Spark: https://spark.apache.org/
- PySpark Documentation: https://spark.apache.org/docs/latest/api/python/
- MongoDB Spark Connector: https://www.mongodb.com/docs/spark-connector/

---

## 💡 Tips

1. **Gunakan virtual environment** untuk Python packages
2. **Schedule analysis** dengan Laravel Task Scheduler
3. **Monitor logs** di `storage/logs/laravel.log`
4. **Backup data** sebelum running analysis pertama kali
5. **Test di development** dulu sebelum production

---

## 🆘 Need Help?

Run diagnostic command:
```bash
php artisan spark:test-config
```

This will tell you exactly what's missing!
