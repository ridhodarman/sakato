# SAKATO V2 — Prototype Aplikasi

**SAKATO — Sistem Akselerasi Kolaboratif Tunggakan Online**  
Prototype V2 untuk simulasi APKO pada Kantor Pertanahan Kabupaten Agam.

## Fitur Utama
- Login dan role demo: Kepala Kantor, Kepala Seksi PHP, Kepala Seksi SP, Tata Usaha, dan PIC.
- Dashboard pimpinan dengan KPI berkas aktif, waspada, kritis, selesai, dan on-time rate.
- Database monitoring berkas dengan pencarian dan filter.
- Early Warning System berdasarkan rasio umur berkas terhadap SLA.
- Pusat eskalasi untuk dukungan lintas seksi dan keputusan pimpinan.
- Monitoring kinerja dan beban kerja PIC.
- Input berkas dan update cepat progres/kendala/tindak lanjut.
- Audit trail sederhana.
- Grafik tren dan beban PIC tanpa library eksternal.
- Ekspor CSV.
- Pengaturan ambang Waspada dan Kritis.
- Penyimpanan data lokal menggunakan localStorage.

## Akun Demo
- `kepala / kepala` — Kepala Kantor
- `php / php` — Seksi Penetapan Hak & Pendaftaran
- `sp / sp` — Seksi Survei & Pemetaan
- `tu / tu` — Tata Usaha
- `pic / pic` — PIC Pelaksana

## Logika Early Warning
- Aman: umur berkas < 70% SLA.
- Waspada: umur berkas ≥ 70% SLA dan < 100% SLA.
- Kritis: umur berkas ≥ 100% SLA.
- Selesai: progres 100%.

Ambang dapat diubah di menu Pengaturan.

## Menjalankan
Buka `index.html` di Chrome atau Microsoft Edge. Tidak memerlukan server.

## Pengembangan Produksi
Untuk pilot riil, prototype dapat dihubungkan dengan Google Spreadsheet + Google Apps Script. Untuk skala produksi, dapat diarahkan ke API/database internal dengan autentikasi resmi, role-based access, audit log, backup, dan kebijakan keamanan data instansi.

> Seluruh data awal di prototype adalah data demo dan tidak mewakili data layanan riil.
