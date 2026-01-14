PANDUAN INTEGRASI WHMCS - VERSI 1.0

Panduan ini mencakup instalasi Modul WHMCS dan update Backend API untuk mencegah 
masalah "Link Menumpuk" (Transaksi ganda setiap kali halaman invoice di-refresh).

Sistem ini terdiri dari 2 Sisi yang harus diupdate:
1. SISI SERVER QIOSLINK (Backend API)
2. SISI WHMCS (Hosting WHMCS)

--------------------------------------------------------------------------------
BAGIAN 1: INSTALASI MODUL DI WHMCS
--------------------------------------------------------------------------------
Gunakan file modul yang sudah dibersihkan (`backend_whmcs_module.txt` dan callbacknya).

LANGKAH A: UPLOAD FILE GATEWAY
1. Ambil kode dari `backend_whmcs_module.txt`.
2. Simpan/Rename menjadi `qiosgateway.php`.
3. Upload ke hosting WHMCS di folder:
   `/modules/gateways/`

LANGKAH B: UPLOAD FILE CALLBACK
1. Ambil kode dari `backend_whmcs_callback.txt`.
2. Simpan/Rename menjadi `qiosgateway.php` (Namanya HARUS SAMA dengan langkah A).
3. Upload ke hosting WHMCS di folder:
   `/modules/gateways/callback/`

Struktur File Akhir di WHMCS:
/public_html/whmcs/modules/gateways/qiosgateway.php
/public_html/whmcs/modules/gateways/callback/qiosgateway.php

--------------------------------------------------------------------------------
BAGIAN 2: AKTIVASI & KONFIGURASI
--------------------------------------------------------------------------------
1. Login ke Admin Area WHMCS.
2. Pergi ke: System Settings -> Payment Gateways.
3. Klik tab "All Payment Gateways".
4. Cari modul bernama "QiosGateway QRIS (Nobu)".
5. Klik nama modul untuk mengaktifkan (warna jadi hijau).
6. Isi Konfigurasi:
   - Show on Order Form: Centang.
   - Display Name: "QRIS (All E-Wallet & Mobile Banking)".
   - API URL: `https://domain-qioslink-anda.com/api/create_payment.php`
   - Merchant ID: (Lihat di Dashboard QiosLink -> Settings).
   - Secret Key: (Lihat di Dashboard QiosLink -> Settings).
7. Klik "Save Changes".

--------------------------------------------------------------------------------
BAGIAN 3: PENGUJIAN (TESTING)
--------------------------------------------------------------------------------
1. Buat Invoice baru di WHMCS (sebagai Admin atau Client).
2. Pilih metode pembayaran "QRIS".
3. Lihat Invoice tersebut. QR Code akan muncul.
4. Buka Tab Baru, login ke Dashboard QiosLink -> Menu Transactions.
   - Pastikan transaksi baru muncul (Status: Pending).
5. Kembali ke Invoice WHMCS, lalu REFRESH halaman berkali-kali (F5).
6. Cek lagi Dashboard QiosLink.
   - HASIL BENAR: Tidak ada transaksi baru yang bertambah. QR Code tetap sama.
   - HASIL SALAH: Transaksi bertambah banyak. (Cek ulang Bagian 1).

--------------------------------------------------------------------------------
CATATAN PENTING
--------------------------------------------------------------------------------
- Jika modul tidak muncul di list WHMCS, jalankan script `backend_force_activate.txt`.
- Jika QR Code tidak muncul (gambar pecah/blank), pastikan API URL benar dan bisa diakses.
- Pastikan hosting QiosLink dan WHMCS sama-sama menggunakan SSL (https).
