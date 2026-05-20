# Aplikasi Anak Sholeh - Aplikasi Tracker Kegiatan Anak

## Tentang Aplikasi

Aplikasi ini adalah aplikasi web berbasis PHP untuk membantu orang tua melacak kegiatan harian anak, khususnya yang berkaitan dengan aktivitas islami dan pembelajaran.

## Fitur Utama

1. **📅 Kegiatan Harian** - Tracking 6 kategori kegiatan dengan bintang reward
   - Amalan Baik (kustomisasi di Pengaturan)
   - Sholat 5 Waktu
   - Hafalan Surah Juz 30
   - Hafalan Do'a
   - Hafalan Hadist
   - Bacaan Iqro
   
2. **📝 Catatan Pekanan** - Input catatan 3 sentra per minggu
   - Sentra Diniyah
   - Sentra Bilangan & Literasi
   - Sentra Tematik

3. **⭐ Bintang & Hadiah** - Sistem reward dengan progress bar
   - Total bintang per anak
   - Progress menuju hadiah
   - Riwayat kegiatan

4. **👶 Data Anak** - Kelola data anak (nama, usia)

5. **⚙️ Pengaturan** - Kustomisasi oleh orang tua
   - Edit daftar hadiah & jumlah bintang yang dibutuhkan
   - Edit daftar Do'a
   - Edit daftar Hadist
   - Edit Amalan Baik

## Cara Menjalankan

### Syarat
- PHP 7.4 atau lebih baru
- Web server (Apache/Nginx) atau PHP built-in server

### Instalasi

1. Copy semua file ke folder web server Anda
   ```
   /var/www/html/bintangku/
   ```

2. Buat folder `data/` dengan permission write:
   ```bash
   mkdir data
   chmod 755 data
   ```

3. Jalankan via PHP built-in server (untuk development):
   ```bash
   cd /path/to/bintangku
   php -S localhost:8000
   ```

4. Buka browser ke `http://localhost:8000`

### Struktur File
```
bintangku/
├── index.php          # File utama aplikasi
├── style.css          # Stylesheet
├── data/              # Folder penyimpanan data (JSON)
│   ├── children.json
│   ├── activities.json
│   ├── settings.json
│   └── weekly_notes.json
└── README.md
```

## Konversi ke Laravel (Rencana Pengembangan)

Untuk versi production dengan Laravel, disarankan:

### Database Structure
```sql
-- children table
CREATE TABLE children (
    id UUID PRIMARY KEY,
    nama VARCHAR(255),
    usia INT,
    created_at TIMESTAMP
);

-- activities table  
CREATE TABLE activities (
    id UUID PRIMARY KEY,
    child_id UUID REFERENCES children(id),
    date DATE,
    type VARCHAR(50),
    item VARCHAR(255),
    stars INT DEFAULT 1,
    created_at TIMESTAMP,
    UNIQUE(child_id, date, type, item)
);

-- settings table (per user/family)
CREATE TABLE settings (
    id UUID PRIMARY KEY,
    key VARCHAR(100),
    value JSON,
    created_at TIMESTAMP
);

-- weekly_notes table
CREATE TABLE weekly_notes (
    id UUID PRIMARY KEY,
    child_id UUID REFERENCES children(id),
    week VARCHAR(10), -- format: 2024-W01
    diniyah TEXT,
    bilangan_literasi TEXT,
    tematik TEXT,
    updated_at TIMESTAMP
);
```

### Laravel Improvements
- Authentication (login orang tua)
- Multi-family support
- Push notification pengingat sholat
- Export PDF/Excel laporan mingguan
- Mode anak (tampilan besar, gamifikasi)
- Avatar/foto anak
- Chart progress bulanan

## Saran Pengembangan Lanjutan

1. **Mode Anak** - Tampilan khusus dengan karakter kartun dan animasi celebrasi
2. **Notifikasi** - Pengingat waktu sholat
3. **Laporan PDF** - Export laporan mingguan untuk dicetak
4. **Multi-user** - Login untuk setiap orang tua dengan data terpisah
5. **Foto Anak** - Upload foto profil
6. **Gamifikasi** - Badge achievement, level naik, dll
7. **Share** - Kirim laporan ke nenek/kakek via WhatsApp
