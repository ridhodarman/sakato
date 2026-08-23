# SAKATO V2 - Struktur Terpisah

Struktur:
- index.php       -> login
- login.php       -> proses login/session
- dashboard.php   -> dashboard pimpinan
- databerkas.php  -> database monitoring berkas
- warning.php     -> early warning
- eskalasi.php    -> pusat eskalasi
- pic.php         -> kinerja PIC
- input.php       -> input/update
- audit.php       -> audit trail
- setting.php     -> pengaturan
- auth.php        -> proteksi halaman
- assets/style.css
- assets/app.js

Catatan:
1. Data prototype masih menggunakan localStorage browser seperti file asli.
2. Login memakai PHP session untuk membatasi halaman setelah login.
3. Akun demo: kepala/kepala, php/php, sp/sp, tu/tu, pic/pic.
4. Untuk produksi, username/password sebaiknya dipindahkan ke database dan password di-hash.
5. Letakkan folder ini di htdocs XAMPP, lalu buka index.php melalui Apache.
