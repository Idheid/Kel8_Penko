# Kel8_Penko - Sistem Pengelolaan Inventaris Alat Tulis Kantor

## Deskripsi Proyek
Kel8_Penko adalah aplikasi web untuk mengelola inventaris alat tulis kantor di sebuah perusahaan. Sistem ini memungkinkan pencatatan, pelacakan, dan pengelolaan stok alat tulis seperti pena, kertas, stapler, dan lainnya. Tujuannya adalah untuk memastikan ketersediaan barang, mencegah kekurangan stok, dan mendukung proses pengadaan yang efisien.

## Fitur Utama
- **Manajemen Inventaris**: Tambah, edit, dan hapus data alat tulis.
- **Pelacakan Stok**: Pantau jumlah stok secara real-time.
- **Laporan Stok**: Hasilkan laporan stok dan riwayat penggunaan.
- **Pengelolaan Pengadaan**: Rencanakan pembelian berdasarkan kebutuhan.
- **Antarmuka Pengguna**: Desain responsif untuk kemudahan penggunaan.

## Teknologi yang Digunakan
- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **Server**: Apache
- **Lainnya**: Bootstrap (untuk styling), jQuery (untuk interaktivitas)

## Struktur Direktori
```
Kel8_Penko/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── script.js
│   └── images/
├── config/
│   └── db_connect.php
├── includes/
│   ├── header.php
│   └── footer.php
├── pages/
│   ├── dashboard.php
│   ├── inventory.php
│   ├── add_item.php
│   ├── edit_item.php
│   └── report.php
├── .gitignore
├── index.php
├── README.md
└── LICENSE
```

## Cara Instalasi
1. **Kloning Repositori**:
   ```bash
   git clone https://github.com/Idheid/Kel8_Penko.git
   ```
2. **Masuk ke Direktori Proyek**:
   ```bash
   cd Kel8_Penko
   ```
3. **Konfigurasi Database**:
   - Buat database MySQL bernama `penko_inventory`.
   - Impor skema database dari file `database.sql` (jika ada).
   - Sesuaikan pengaturan koneksi database di `config/db_connect.php`.
4. **Siapkan Server**:
   - Salin proyek ke direktori server web (misalnya, `htdocs` untuk XAMPP).
   - Pastikan Apache dan MySQL berjalan.
5. **Akses Aplikasi**:
   - Buka browser dan kunjungi `http://localhost/Kel8_Penko`.

## Cara Penggunaan
1. Buka aplikasi melalui browser di `http://localhost/Kel8_Penko`.
2. Login sebagai admin (gunakan kredensial default jika ada, atau buat akun baru).
3. Tambahkan alat tulis baru melalui menu "Tambah Inventaris".
4. Pantau stok melalui halaman "Dashboard".
5. Hasilkan laporan stok melalui menu "Laporan".

## Kontribusi
Kami menyambut kontribusi! Untuk berkontribusi:
1. Fork repositori ini.
2. Buat branch baru (`git checkout -b fitur-baru`).
3. Commit perubahan Anda (`git commit -m 'Menambahkan fitur X'`).
4. Push ke branch Anda (`git push origin fitur-baru`).
5. Buat Pull Request di GitHub.

## Lisensi
Proyek ini dilisensikan di bawah MIT License. Lihat file `LICENSE` untuk detail.

## Kontak
Untuk pertanyaan atau dukungan, buka issue di repositori ini atau hubungi kami melalui email.

---
**Repositori GitHub**: [https://github.com/Idheid/Kel8_Penko](https://github.com/Idheid/Kel8_Penko)