# Apps Office 354

> **Integrated Office Management Platform**
> **by PT Pandu Palapa Telematika**

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel\&logoColor=white)](https://laravel.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php\&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?logo=mysql\&logoColor=white)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-Proprietary-red)](#lisensi)

---

## 📌 Tentang Apps Office 354

**Apps Office 354** adalah aplikasi berbasis web yang dikembangkan untuk mendukung kebutuhan administrasi, pengelolaan data, dan operasional perkantoran secara terintegrasi.

Aplikasi ini dibangun menggunakan **Laravel 12** dengan database **MySQL/MariaDB** dan dirancang agar dapat digunakan pada lingkungan development maupun production sesuai dengan kebutuhan organisasi.

**Apps Office 354 dikembangkan oleh:**

### PT Pandu Palapa Telematika

---

# ✨ Fitur Utama

Apps Office 354 dikembangkan untuk mendukung berbagai kebutuhan operasional perkantoran, antara lain:

* 🏢 Pengelolaan administrasi perusahaan
* 👥 Pengelolaan pengguna dan data
* 📊 Pengelolaan informasi operasional
* 🗂️ Pengelolaan data administrasi
* 🔐 Sistem autentikasi pengguna
* 📑 Pengelolaan dokumen dan informasi
* 📈 Monitoring dan pelaporan
* 🛠️ Pengelolaan sistem
* 🔄 Integrasi database
* 🌐 Akses melalui web browser

> Fitur yang tersedia dapat berkembang sesuai dengan versi dan pengembangan Apps Office 354.

---

# 🧰 Teknologi

| Teknologi  | Versi  |
| ---------- | ------ |
| Laravel    | 12.x   |
| PHP        | 8.2+   |
| MySQL      | 8.x    |
| Composer   | Latest |
| Node.js    | LTS    |
| NPM        | Latest |
| Git        | Latest |
| phpMyAdmin | Latest |

---

# 💻 Persyaratan Sistem

Sebelum memulai instalasi, pastikan komputer sudah memiliki seluruh kebutuhan berikut.

### Wajib

* PHP 8.2 atau lebih baru
* Composer
* MySQL atau MariaDB
* phpMyAdmin
* Git
* Node.js
* NPM
* Web Browser

### Browser yang Direkomendasikan

* Google Chrome
* Microsoft Edge
* Mozilla Firefox

---

# 🚀 INSTALASI DARI 0 SAMPAI 100

Bagian ini merupakan panduan lengkap untuk menjalankan Apps Office 354 dari kondisi awal hingga aplikasi berhasil digunakan.

Ikuti **setiap langkah secara berurutan**.

---

# STEP 01 — Install Software Pendukung

Sebelum mengambil source code, pastikan software berikut sudah tersedia.

## 1. PHP

Pastikan PHP sudah terinstall.

Cek melalui Terminal / Command Prompt:

```bash
php -v
```

Contoh hasil:

```text
PHP 8.3.x
```

Versi minimal:

```text
PHP 8.2
```

---

## 2. Composer

Cek Composer:

```bash
composer -V
```

Contoh:

```text
Composer version 2.x.x
```

Jika Composer belum tersedia, install Composer terlebih dahulu.

---

## 3. MySQL

Pastikan MySQL atau MariaDB sudah berjalan.

Jika menggunakan XAMPP, buka:

```text
XAMPP Control Panel
```

Kemudian aktifkan:

```text
Apache
MySQL
```

---

## 4. phpMyAdmin

Pastikan phpMyAdmin dapat dibuka.

Buka browser:

```text
http://localhost/phpmyadmin
```

Jika halaman phpMyAdmin muncul, berarti database server sudah dapat digunakan.

---

## 5. Git

Cek Git:

```bash
git --version
```

Contoh:

```text
git version 2.x.x
```

---

## 6. Node.js

Cek Node.js:

```bash
node -v
```

Kemudian cek NPM:

```bash
npm -v
```

Direkomendasikan menggunakan versi **LTS**.

---

# STEP 02 — Ambil Source Code dari GitHub

Buka:

* Command Prompt
* PowerShell
* Terminal Visual Studio Code

Kemudian tentukan lokasi penyimpanan project.

Contoh menggunakan XAMPP:

```bash
cd C:\xampp\htdocs
```

Kemudian clone repository Apps Office 354:

```bash
git clone https://github.com/papatel365/apps-office-354.git
```

> Gunakan repository resmi Apps Office 354 milik PT Pandu Palapa Telematika.

---

# STEP 03 — Masuk ke Folder Project

Setelah proses clone selesai:

```bash
cd apps-office-354
```

Untuk memastikan sudah berada di folder project, jalankan:

```bash
dir
```

atau pada Linux/macOS:

```bash
ls
```

Pastikan terdapat file seperti:

```text
artisan
composer.json
package.json
.env.example
```

---

# STEP 04 — Install Dependency Laravel

Jalankan:

```bash
composer install
```

Tunggu hingga proses selesai.

Composer akan membuat folder:

```text
vendor/
```

Jika proses selesai tanpa error, lanjutkan ke langkah berikutnya.

---

# STEP 05 — Membuat File Environment

Laravel menggunakan file `.env` untuk menyimpan konfigurasi aplikasi.

Jika `.env` belum tersedia, buat dari `.env.example`.

### Windows

```bash
copy .env.example .env
```

### Linux / macOS

```bash
cp .env.example .env
```

Setelah itu pastikan file berikut tersedia:

```text
.env
```

---

# STEP 06 — Membuat Database

Sekarang kita akan membuat database Apps Office 354.

Buka browser:

```text
http://localhost/phpmyadmin
```

Kemudian:

1. Klik **New**.
2. Masukkan nama database.
3. Gunakan nama:

```text
office354
```

4. Klik **Create**.

Database sekarang sudah dibuat.

---

# STEP 07 — Import Database Apps Office 354

Database aplikasi tersedia pada folder:

```text
database/
```

Cari file database dengan format:

```text
.sql
```

Contoh:

```text
database/office354.sql
```

> Nama file dapat berbeda tergantung versi source code.

---

## Cara Import

Buka:

```text
http://localhost/phpmyadmin
```

Kemudian:

1. Pilih database:

```text
office354
```

2. Klik menu:

```text
Import
```

3. Klik:

```text
Choose File
```

4. Pilih file `.sql` dari folder:

```text
database/
```

5. Scroll ke bagian bawah.
6. Klik:

```text
Import
```

7. Tunggu sampai proses selesai.

Jika berhasil, tabel-tabel Apps Office 354 akan muncul pada database.

---

# STEP 08 — Konfigurasi Database Laravel

Buka file:

```text
.env
```

Kemudian cari konfigurasi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=office354
DB_USERNAME=root
DB_PASSWORD=
```

Sesuaikan dengan konfigurasi MySQL pada komputer.

### Contoh XAMPP

Jika MySQL menggunakan user `root` tanpa password:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=office354
DB_USERNAME=root
DB_PASSWORD=
```

Jika MySQL menggunakan password:

```env
DB_USERNAME=root
DB_PASSWORD=password_mysql
```

---

# STEP 09 — Konfigurasi Nama Aplikasi

Pada `.env`, konfigurasi aplikasi dapat dibuat seperti berikut:

```env
APP_NAME="Office354"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
```

Untuk development, `APP_DEBUG=true` dapat digunakan.

> Jangan menggunakan `APP_DEBUG=true` pada server production.

---

# STEP 10 — Generate Application Key

Setelah file .env selesai dikonfigurasi, jalankan:

```bash
php artisan key:generate
```

Jika berhasil, akan muncul pesan bahwa application key telah berhasil dibuat.

Periksa .env:

APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxx

Jangan membagikan APP_KEY kepada publik.

---

# STEP 11 — Bersihkan Cache Laravel

Jalankan:

```bash
php artisan optimize:clear
```

Perintah ini memastikan Laravel menggunakan konfigurasi terbaru dari `.env`.

---

# STEP 12 — Install Frontend Dependency

Jalankan:

```bash
npm install
```

Tunggu hingga proses selesai.

Setelah selesai, folder:

```text
node_modules/
```

akan tersedia.

---

# STEP 13 — Jalankan Vite

Untuk development, jalankan:

```bash
npm run dev
```

Biarkan terminal ini tetap berjalan.

Contoh:

```text
VITE ready
```

> Jangan tutup terminal ini selama proses development apabila aplikasi membutuhkan Vite secara langsung.

---

# STEP 14 — Jalankan Laravel

Buka **terminal baru**.

Pastikan kembali berada pada folder project:

```bash
cd apps-office-354
```

Kemudian jalankan:

```bash
php artisan serve
```

Jika berhasil, akan muncul informasi seperti:

```text
INFO  Server running on [http://127.0.0.1:8000].
```

---

# STEP 15 — Buka Apps Office 354

Buka browser.

Masukkan:

```text
http://127.0.0.1:8000
```

atau:

```text
http://localhost:8000
```

Jika halaman login Apps Office 354 muncul, berarti aplikasi sudah berhasil dijalankan.

---

# 🔐 STEP 16 — Login ke Apps Office 354

Gunakan akun administrator yang telah disediakan.

### Akun Default

| Informasi    | Nilai          |
| ------------ | -------------- |
| **Username** | `admin`        |
| **Password** | `Admin@123456` |

Masukkan:

```text
Username:
admin

Password:
Admin@123456
```

Kemudian klik:

```text
Login
```

---

# ⚠️ PENTING — Keamanan Akun

Akun di atas merupakan **akun default untuk akses awal aplikasi**.

Setelah berhasil login, administrator **sangat disarankan untuk segera mengganti password default** dengan password yang lebih aman.

Jangan menggunakan password default untuk lingkungan production apabila aplikasi dapat diakses oleh publik.

---

# 🎯 STEP 17 — Instalasi Berhasil

Jika Anda sudah sampai pada halaman dashboard setelah login, maka instalasi Apps Office 354 telah berhasil.

Alur lengkapnya:

```text
01. Install PHP
        ↓
02. Install Composer
        ↓
03. Install MySQL
        ↓
04. Install phpMyAdmin
        ↓
05. Install Git
        ↓
06. Install Node.js + NPM
        ↓
07. Clone Repository
        ↓
08. composer install
        ↓
09. Buat .env
        ↓
10. Buat Database
        ↓
11. Import Database
        ↓
12. Konfigurasi .env
        ↓
13. php artisan key:generate
        ↓
14. php artisan optimize:clear
        ↓
15. npm install
        ↓
16. npm run dev
        ↓
17. php artisan serve
        ↓
18. Buka localhost:8000
        ↓
19. Login
        ↓
20. Apps Office 354 siap digunakan
```

---

# 🖥️ Menjalankan Aplikasi Setelah Instalasi

Setelah instalasi pertama selesai, Anda tidak perlu mengulangi seluruh proses.

Setiap kali ingin menjalankan aplikasi:

### Terminal 1

Masuk ke folder project:

```bash
cd apps-office-354
```

Kemudian:

```bash
npm run dev
```

### Terminal 2

Buka terminal baru:

```bash
cd apps-office-354
```

Kemudian:

```bash
php artisan serve
```

Setelah itu buka:

```text
http://127.0.0.1:8000
```

---

# 📂 Struktur Project

Struktur utama Apps Office 354:

```text
apps-office-354/
│
├── app/
│   ├── Console/
│   ├── Http/
│   ├── Models/
│   └── ...
│
├── bootstrap/
│
├── config/
│
├── database/
│   ├── factories/
│   ├── migrations/
│   ├── seeders/
│   └── *.sql
│
├── public/
│
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│
├── routes/
│
├── storage/
│
├── tests/
│
├── .env.example
├── artisan
├── composer.json
├── package.json
└── vite.config.js
```

---

# 🛠️ Perintah Penting

## Menjalankan Laravel

```bash
php artisan serve
```

## Menjalankan Vite

```bash
npm run dev
```

## Install Composer Dependency

```bash
composer install
```

## Install NPM Dependency

```bash
npm install
```

## Membersihkan Cache

```bash
php artisan optimize:clear
```

## Melihat Route

```bash
php artisan route:list
```

## Refresh Autoload Composer

```bash
composer dump-autoload
```

---

# 🗄️ Database Management

Database Apps Office 354 dapat dikelola melalui phpMyAdmin.

Akses:

```text
http://localhost/phpmyadmin
```

Database:

```text
apps_office_354
```

### Backup Database

Sebelum melakukan perubahan besar, selalu lakukan backup.

Langkah:

```text
phpMyAdmin
    ↓
apps_office_354
    ↓
Export
    ↓
Format SQL
    ↓
Export
```

Simpan file backup pada lokasi yang aman.

---

# 🔒 Security Guidelines

Keamanan aplikasi merupakan tanggung jawab penting dalam pengelolaan Apps Office 354.

## Jangan Commit `.env`

Jangan pernah meng-upload `.env` ke repository publik.

Pastikan `.gitignore` memiliki:

```text
.env
```

## Jangan Publikasikan Credential

Jangan menyimpan informasi berikut di source code:

* Password database
* API Key
* Secret Key
* Token
* `APP_KEY`
* Credential layanan pihak ketiga

## Production

Untuk server production:

```env
APP_ENV=production
APP_DEBUG=false
```

Gunakan HTTPS dan konfigurasi server yang aman.

---

# 🐛 Troubleshooting

## 1. `vendor/autoload.php` Not Found

Jika muncul error:

```text
require(vendor/autoload.php): Failed to open stream
```

Jalankan:

```bash
composer install
```

---

## 2. `No application encryption key has been specified`

Jalankan:

```bash
php artisan key:generate
```

Kemudian:

```bash
php artisan optimize:clear
```

---

## 3. `Unknown database`

Periksa apakah database:

```text
apps_office_354
```

sudah dibuat di phpMyAdmin.

Kemudian periksa `.env`:

```env
DB_DATABASE=apps_office_354
```

---

## 4. `Access denied for user`

Periksa:

```env
DB_USERNAME=root
DB_PASSWORD=
```

Pastikan username dan password MySQL benar.

---

## 5. Perubahan `.env` Tidak Terbaca

Jalankan:

```bash
php artisan optimize:clear
```

Kemudian restart:

```bash
php artisan serve
```

---

## 6. CSS atau JavaScript Tidak Muncul

Pastikan NPM dependency sudah terinstall:

```bash
npm install
```

Kemudian:

```bash
npm run dev
```

---

## 7. Port 8000 Sudah Digunakan

Jika port 8000 sudah digunakan aplikasi lain, jalankan:

```bash
php artisan serve --port=8080
```

Kemudian buka:

```text
http://127.0.0.1:8080
```

---

# 🌐 Deployment

Untuk penggunaan production, Apps Office 354 harus melalui proses deployment yang sesuai.

Alur deployment yang direkomendasikan:

```text
Development
     ↓
Testing
     ↓
Staging
     ↓
Code Review
     ↓
Database Backup
     ↓
Production Deployment
     ↓
Testing
     ↓
Monitoring
```

Sebelum deployment production:

* Pastikan source code telah diuji.
* Backup database.
* Konfigurasi `.env` production.
* Gunakan `APP_DEBUG=false`.
* Pastikan dependency terinstall.
* Build asset frontend.
* Pastikan permission server benar.
* Pastikan HTTPS aktif.
* Pastikan database production tersedia.
* Lakukan pengujian setelah deployment.

---

# 📦 Production Build

Untuk melakukan build asset frontend:

```bash
npm run build
```

Pada production, jangan menjalankan development server Vite sebagai mekanisme utama aplikasi.

Gunakan hasil build yang telah dibuat:

```text
public/build/
```

---

# 🤝 Contribution

Pengembangan Apps Office 354 dikelola oleh:

**PT Pandu Palapa Telematika**

Perubahan terhadap source code harus mengikuti prosedur pengembangan internal perusahaan.

Developer disarankan untuk:

1. Membuat branch.
2. Melakukan perubahan.
3. Melakukan testing.
4. Memastikan tidak terdapat error.
5. Membuat commit yang jelas.
6. Melakukan code review.
7. Melakukan merge setelah mendapatkan persetujuan.

---

# 📄 Lisensi

**Apps Office 354** merupakan perangkat lunak milik:

> **PT Pandu Palapa Telematika**

Source code, desain, database, dokumentasi, dan komponen internal aplikasi merupakan aset perusahaan.

Penggunaan, penggandaan, distribusi, modifikasi, atau redistribusi aplikasi di luar pihak yang berwenang harus mendapatkan izin dari PT Pandu Palapa Telematika.

**License: Proprietary**

---

# 🏢 Informasi Aplikasi

| Informasi           | Detail                     |
| ------------------- | -------------------------- |
| **Nama**            | Apps Office 354            |
| **Kategori**        | Office Management Platform |
| **Framework**       | Laravel 12                 |
| **PHP**             | 8.2+                       |
| **Database**        | MySQL / MariaDB            |
| **Frontend Build**  | Vite                       |
| **Version Control** | Git                        |
| **Developer**       | PT Pandu Palapa Telematika |
| **License**         | Proprietary                |

---

# 📞 Support

Untuk kebutuhan dukungan teknis, pengembangan, deployment, atau pemeliharaan Apps Office 354, silakan menghubungi administrator atau tim teknis yang ditunjuk oleh:

**PT Pandu Palapa Telematika**

---

<div align="center">

## Apps Office 354

### by PT Pandu Palapa Telematika

© 2026 PT Pandu Palapa Telematika. All rights reserved.

</div>
