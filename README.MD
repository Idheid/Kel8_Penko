# Penko - Sistem Pengelolaan Inventaris Alat Tulis Kantor

## Deskripsi Proyek
Penko adalah aplikasi web yang dirancang untuk mengelola inventaris Alat Tulis Kantor (ATK) di lingkungan kerja. Aplikasi ini mendukung dua peran utama: Admin dan Staff. Admin bertanggung jawab mengelola data barang masuk dan keluar, memastikan stok tercatat dengan baik, serta memproses permintaan ATK dari staff. Staff dapat mengajukan permintaan ATK untuk mendukung kebutuhan kerja mereka, yang kemudian disetujui atau ditolak oleh admin berdasarkan ketersediaan dan kebijakan.

Sistem ini bertujuan menciptakan alur pengajuan dan distribusi ATK yang tertib, transparan, dan terdokumentasi dengan baik. Dengan Penko, proses pencatatan keluar-masuk barang menjadi lebih efisien, mudah diawasi, dan meminimalkan kesalahan dalam pendistribusian.

## Fitur Utama
- **Manajemen Inventaris** (Admin): Tambah, edit, dan hapus data alat tulis.
- **Pelacakan Stok** (Admin): Pantau jumlah stok secara real-time.
- **Pengelolaan Permintaan** (Admin & Staff): Staff mengajukan permintaan ATK, admin menyetujui atau menolaknya.
- **Laporan Stok** (Admin):: Hasilkan laporan stok dan riwayat penggunaan.
- **Hak Akses Berbasis Peran**: Admin memiliki akses penuh, sedangkan staff hanya dapat melihat daftar barang dan mengajukan permintaan.
- **Antarmuka Pengguna**: Desain responsif untuk kemudahan penggunaan.

## Teknologi yang Digunakan
- **Frontend**: HTML, CSS
- **Backend**: PHP
- **Database**: MySQL
- **Server**: Apache
- **Lainnya**: Bootstrap (untuk styling), jQuery (untuk interaktivitas)


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

1. **Akses Aplikasi**:

   - Buka browser dan kunjungi `https://localhost`.
   - Login menggunakan kredensial admin (default: username `admin`, password `penko123`) atau akun staff (default: username `staff1` atau `staff2`, password `penko123`).

2. **Sebagai Admin**:

   - **Kelola Inventaris**:
     - Buka menu "Inventaris" untuk menambah barang baru (misalnya, pena, kertas).
     - Edit atau hapus data barang jika diperlukan.
     - Perbarui jumlah stok setelah barang masuk atau keluar.
   - **Proses Permintaan**:
     - Buka menu "Permintaan" untuk melihat daftar permintaan dari staff.
     - Tinjau detail permintaan (nama barang, jumlah, keperluan).
     - Setujui atau tolak permintaan berdasarkan ketersediaan stok.
   - **Lihat Laporan**:
     - Akses menu "Laporan" untuk menghasilkan laporan stok atau riwayat permintaan.
     - Unduh laporan dalam format PDF atau Excel (jika tersedia).

3. **Sebagai Staff**:

   - **Lihat Daftar Barang**:
     - Buka menu "Daftar Barang" untuk melihat ATK yang tersedia.
   - **Ajukan Permintaan**:
     - Buka menu "Buat Permintaan".
     - Pilih barang, masukkan jumlah, dan jelaskan keperluan.
     - Kirim permintaan untuk ditinjau admin.
   - **Cek Status Permintaan**:
     - Buka menu "Status Permintaan" untuk melihat apakah permintaan disetujui, ditolak, atau masih ditinjau.

4. **Tips Penggunaan**:

   - Pastikan login dengan peran yang sesuai (admin/staff).
   - Periksa stok sebelum mengajukan permintaan (untuk staff).
   - Admin disarankan memperbarui stok secara berkala untuk menjaga akurasi data.
   - Gunakan fitur laporan untuk analisis kebutuhan ATK bulanan.

## Kontributor
1. [Adrianne Julian Claresta](https://github.com/Idheid)
2. [Dymaz Satya Putra](https://github.com/DYmazeh)
3. [Sulthon Aris Setiawan](https://github.com/sulthonarissetiawan)
4. [Rizqi Ananda Pratama](https://github.com/rizqianandapratam)
