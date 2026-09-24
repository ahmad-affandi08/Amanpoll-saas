<?php

return [
    'header_organisasi' => env('AMANPOLL_HEADER_ORGANISASI', 'X-Organisasi-Id'),
    'audit_aktif' => env('AMANPOLL_AUDIT_AKTIF', true),
    'mata_uang' => env('AMANPOLL_MATA_UANG', 'IDR'),
    'zona_waktu_default' => env('AMANPOLL_ZONA_WAKTU', 'Asia/Jakarta'),
    'retensi_catatan_akses_hari' => env('AMANPOLL_RETENSI_CATATAN_AKSES_HARI', 90),
    'disk_berkas' => env('AMANPOLL_DISK_BERKAS', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Mesin kompresi berkas
    |--------------------------------------------------------------------------
    |
    | PRD 11.1. Setiap berkas yang disimpan sistem melewati PemampatBerkas.
    | Ambang dan kualitas diatur di sini, bukan di kode. Kegagalan kompresi
    | tidak pernah menggagalkan penyimpanan: berkasnya disimpan apa adanya.
    |
    */
    'kompresi' => [
        'aktif' => env('AMANPOLL_KOMPRESI_AKTIF', true),

        'gambar' => [
            // Sisi terpanjang gambar tersimpan, dalam piksel.
            'sisi_maks' => env('AMANPOLL_KOMPRESI_GAMBAR_SISI_MAKS', 2560),
            'kualitas_webp' => env('AMANPOLL_KOMPRESI_GAMBAR_KUALITAS', 80),
            'sisi_thumbnail' => env('AMANPOLL_KOMPRESI_THUMBNAIL_SISI', 480),
            'kualitas_thumbnail' => env('AMANPOLL_KOMPRESI_THUMBNAIL_KUALITAS', 75),
            // Gambar di atas jumlah piksel ini tidak didekode (batas memori shared hosting); disimpan apa adanya.
            'piksel_maks' => env('AMANPOLL_KOMPRESI_GAMBAR_PIKSEL_MAKS', 40_000_000),
        ],

        'gzip' => [
            'level' => env('AMANPOLL_KOMPRESI_GZIP_LEVEL', 9),
            // Jenis pada `mime_gzip_bila_hemat` baru di-gzip bila hematnya minimal sekian persen.
            'hemat_minimal_persen' => env('AMANPOLL_KOMPRESI_GZIP_HEMAT_MINIMAL', 10),
        ],

        // Selain `text/*`: selalu di-gzip (bila hasilnya lebih kecil).
        'mime_teks' => [
            'application/json',
            'application/xml',
            'application/csv',
            'application/x-ndjson',
        ],

        // Di-gzip hanya bila hemat minimal `gzip.hemat_minimal_persen`.
        'mime_gzip_bila_hemat' => [
            'application/pdf',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data contoh
    |--------------------------------------------------------------------------
    |
    | Dipakai DemoAwalSeeder, yang menolak berjalan di produksi. Kata sandinya
    | tetap dapat ditebak dengan sengaja supaya lingkungan pengembangan mudah
    | dipakai; yang menjaga produksi adalah penjaga lingkungan di seeder itu,
    | bukan kerahasiaan nilai ini.
    |
    */
    /*
    |--------------------------------------------------------------------------
    | Konfirmasi penerima (PRD 8.22)
    |--------------------------------------------------------------------------
    |
    | QR yang ditampilkan teknisi berisi tautan bertanda tangan server untuk
    | satu perintah kerja. Masa berlakunya singkat supaya foto layar QR tidak
    | bisa dipakai belakangan; teknisi tinggal memperbaruinya.
    |
    */
    'konfirmasi_penerima' => [
        'menit_berlaku_qr' => (int) env('AMANPOLL_KONFIRMASI_QR_MENIT', 10),
    ],

    'demo' => [
        'kata_sandi' => env('AMANPOLL_DEMO_KATA_SANDI', 'password'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Host
    |--------------------------------------------------------------------------
    |
    | Satu instalasi melayani beberapa host (PRD 5.4, MARKETING.md 1). Host tidak
    | pernah ditulis di source maupun di frontend; seluruhnya dibaca dari sini.
    |
    | `publik` boleh null, dan itulah keadaan sebelum situs pemasaran ada: grup
    | rute publik tidak didaftarkan sama sekali, sehingga root tetap milik
    | dashboard dan tidak ada rute yang bertabrakan.
    |
    */
    'domain' => [
        'publik' => env('AMANPOLL_DOMAIN_PUBLIK'),
        'dashboard' => env('AMANPOLL_DOMAIN_DASHBOARD', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost'),
        'partner' => env('AMANPOLL_DOMAIN_PARTNER'),

        // Bentuk kanonik host publik.
        'kanonik_pakai_www' => (bool) env('AMANPOLL_DOMAIN_KANONIK_WWW', false),
        'skema_kanonik' => env('AMANPOLL_DOMAIN_SKEMA', 'https'),

        // Domain induk untuk cookie yang harus bertahan saat pengunjung berpindah dari host publik ke host.
        'cookie_induk' => env('SESSION_DOMAIN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pemasaran
    |--------------------------------------------------------------------------
    |
    | CAPTCHA formulir publik (MARKETING.md 10). Dibuat agnostik penyedia:
    | Turnstile, hCaptcha, dan reCAPTCHA sama-sama memeriksa satu token lewat
    | satu endpoint dan menjawab `success`, sehingga tidak ada alasan mengikat
    | aplikasi ini pada salah satunya.
    |
    | Rahasianya tetap di environment. Tanpa `rahasia`, formulir yang menyalakan
    | CAPTCHA akan menolak seluruh pengiriman — gagal tertutup, bukan diam-diam
    | melewati pemeriksaan yang dikira menyala.
    |
    */
    'pemasaran' => [
        'penyedia_email' => env('AMANPOLL_PEMASARAN_PENYEDIA_EMAIL', 'Laravel'),
        'penyedia_whatsapp' => env('AMANPOLL_PEMASARAN_PENYEDIA_WHATSAPP', 'Log'),
        'penyedia_sosial' => env('AMANPOLL_PEMASARAN_PENYEDIA_SOSIAL', 'Log'),

        // Rahasia dari environment, bukan dari konfigurasi langkah yang tersimpan terbaca di konsol.
        'webhook_rahasia' => env('AMANPOLL_PEMASARAN_WEBHOOK_RAHASIA', ''),

        'captcha' => [
            'endpoint' => env(
                'AMANPOLL_CAPTCHA_ENDPOINT',
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            ),
            'rahasia' => env('AMANPOLL_CAPTCHA_RAHASIA'),
            'nama_field' => env('AMANPOLL_CAPTCHA_FIELD', 'cf-turnstile-response'),

            // Kunci situs ikut terkirim ke browser — memang itu gunanya, dan ia bukan rahasia.
            'kunci_situs' => env('AMANPOLL_CAPTCHA_KUNCI_SITUS'),
            'skrip' => env(
                'AMANPOLL_CAPTCHA_SKRIP',
                'https://challenges.cloudflare.com/turnstile/v0/api.js',
            ),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pengirim email (PRD 8.23)
    |--------------------------------------------------------------------------
    |
    | Mailer `amanpoll` meneruskan email ke penyedia email aktif di konsol
    | platform. Selama belum ada yang aktif, email diteruskan ke mailer
    | cadangan ini (bawaan `log`: tersimpan di log, tidak terkirim).
    |
    */
    'email' => [
        'mailer_cadangan' => env('AMANPOLL_EMAIL_MAILER_CADANGAN', 'log'),
    ],

    'langganan' => [
        // Hari setelah tanggal berakhir yang masih memberi akses tulis penuh.
        'hari_tenggang' => env('AMANPOLL_LANGGANAN_HARI_TENGGANG', 7),
        'hari_uji_coba' => env('AMANPOLL_LANGGANAN_HARI_UJI_COBA', 14),
        'hari_jatuh_tempo' => env('AMANPOLL_LANGGANAN_HARI_JATUH_TEMPO', 14),
        'pajak_persen' => env('AMANPOLL_LANGGANAN_PAJAK_PERSEN', 0),

        'penyedia_pembayaran' => env('AMANPOLL_LANGGANAN_PENYEDIA', 'TransferManual'),
        // Tanpa rahasia ini, endpoint webhook pembayaran menolak seluruh permintaan.
        'rahasia_webhook' => env('AMANPOLL_LANGGANAN_RAHASIA_WEBHOOK', ''),
        'bank_nama' => env('AMANPOLL_LANGGANAN_BANK_NAMA', 'Bank Mandiri'),
        'bank_rekening' => env('AMANPOLL_LANGGANAN_BANK_REKENING', '000-000-0000'),
        'bank_atas_nama' => env('AMANPOLL_LANGGANAN_BANK_ATAS_NAMA', 'PT Amanpoll Indonesia'),
    ],

    'cadangan' => [
        // Disk kerja; harus berdriver local. Dump dan arsip dibuat di sini lebih dulu.
        'disk' => env('AMANPOLL_CADANGAN_DISK', 'local'),
        'folder' => env('AMANPOLL_CADANGAN_FOLDER', 'cadangan'),

        /*
         * Disk salinan luar server (FASE 45), mis. `cadangan_luar` di
         * config/filesystems.php (S3 atau Cloudflare R2). Setiap cadangan lokal
         * diunggah ke sini lalu diverifikasi ukuran dan SHA-256-nya. Kosong
         * berarti cadangan hanya tinggal di server yang sama dengan datanya;
         * setiap jalan `cadangan:jalankan` lalu mencatat peringatan.
         */
        'disk_luar' => env('AMANPOLL_CADANGAN_DISK_LUAR'),
        'folder_luar' => env('AMANPOLL_CADANGAN_FOLDER_LUAR', 'cadangan'),

        // Membaca ulang salinan luar untuk mencocokkan SHA-256. Egress R2 gratis; di S3 ini berbayar.
        'verifikasi_checksum' => (bool) env('AMANPOLL_CADANGAN_VERIFIKASI_CHECKSUM', true),

        /*
         * Jalur biner mysqldump dan mysql. Shared hosting kadang tidak memasang
         * keduanya di PATH; bila salah satu tidak ada, pencadangan berhenti
         * dengan pesan jelas alih-alih menulis berkas kosong yang baru ketahuan
         * tidak berguna saat dibutuhkan.
         */
        'mysqldump' => env('AMANPOLL_CADANGAN_MYSQLDUMP', 'mysqldump'),
        'mysql' => env('AMANPOLL_CADANGAN_MYSQL', 'mysql'),

        /*
         * Retensi dalam hari. `retensi_hari` berlaku untuk salinan lokal bila
         * disk luar tidak diatur, dan sekaligus batas atas salinan lokal yang
         * belum berhasil disalin ke luar. Dengan disk luar, salinan lokal yang
         * sudah utuh di luar dipangkas setelah `retensi_lokal_hari`. Cadangan
         * basis data terbaru dan rantai arsip berkas terbaru tidak pernah
         * dipangkas.
         */
        'retensi_hari' => env('AMANPOLL_CADANGAN_RETENSI_HARI', 14),
        'retensi_lokal_hari' => env('AMANPOLL_CADANGAN_RETENSI_LOKAL_HARI', 2),
        'retensi_luar_hari' => env('AMANPOLL_CADANGAN_RETENSI_LUAR_HARI', 30),

        // Folder di bawah storage/app yang ikut dicadangkan; berkas unggahan tenant ada di sini.
        'folder_berkas' => ['private', 'public'],

        /*
         * Arsip berkas penuh dibuat bila arsip penuh terakhir berumur sekian
         * hari; di antaranya hanya arsip selisih (berkas yang berubah sejak arsip
         * penuh itu). 1 berarti arsip penuh setiap hari.
         */
        'berkas_penuh_tiap_hari' => env('AMANPOLL_CADANGAN_BERKAS_PENUH_TIAP_HARI', 7),

        // Batas waktu satu proses dump, dalam detik.
        'batas_detik' => env('AMANPOLL_CADANGAN_BATAS_DETIK', 600),
    ],

    'http_keluar' => [
        /*
         * Panggilan keluar ke URL isian pengguna (webhook, integrasi eksternal,
         * server WhatsApp) dijaga PenjagaUrlKeluar. Skema http polos hanya untuk
         * pengembangan; di luar local/testing hanya https yang diterima.
         */
        'izinkan_http' => (bool) env('AMANPOLL_HTTP_KELUAR_IZINKAN_HTTP', in_array(env('APP_ENV', 'production'), ['local', 'testing'], true)),

        // Port selain 80/443 yang boleh dituju, dipisah koma (mis. "3000" untuk WAHA).
        'port_tambahan' => array_values(array_filter(array_map(
            'intval',
            explode(',', (string) env('AMANPOLL_HTTP_KELUAR_PORT_TAMBAHAN', '')),
        ))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pelaporan (FASE 45)
    |--------------------------------------------------------------------------
    |
    | Dasbor dan laporan yang dibuka di layar dihitung sinkron, jadi rentangnya
    | dibatasi; rentang lebih panjang hanya lewat ekspor yang berjalan di antrean.
    | Hasil KPI disimpan sebentar di cache bawaan (file/database) per organisasi,
    | lingkup akses, dan filter -- bukan per halaman, sehingga dua dasbor yang
    | memuat KPI sama berbagi hitungannya. 0 mematikan cache.
    |
    */
    'pelaporan' => [
        'rentang_maks_hari_interaktif' => (int) env('AMANPOLL_PELAPORAN_RENTANG_MAKS_HARI_INTERAKTIF', 365),
        // Kira-kira lima tahun.
        'rentang_maks_hari_ekspor' => (int) env('AMANPOLL_PELAPORAN_RENTANG_MAKS_HARI_EKSPOR', 1827),
        'kpi_maks_ekspor' => (int) env('AMANPOLL_PELAPORAN_KPI_MAKS_EKSPOR', 30),
        // Batas KPI bila rentangnya melebihi batas interaktif.
        'kpi_maks_ekspor_rentang_panjang' => (int) env('AMANPOLL_PELAPORAN_KPI_MAKS_EKSPOR_RENTANG_PANJANG', 15),
        'cache_kpi_detik' => (int) env('AMANPOLL_PELAPORAN_CACHE_KPI_DETIK', 45),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retensi tabel operasional (FASE 45)
    |--------------------------------------------------------------------------
    |
    | Batas basis data hosting 3 GB. `retensi:pangkas` menghapus baris berstatus
    | akhir yang lewat masa simpannya, per potongan dengan jeda kecil dan batas
    | waktu total supaya aman di shared hosting. Nilai null mematikan aturannya.
    | Data bisnis tidak pernah disentuh. CatatanAudit sengaja append-only: bawaan
    | tidak dihapus sama sekali; bila `catatan_audit.arsip_setelah_hari` diisi,
    | baris yang lebih tua diarsipkan ke berkas gzip lebih dulu, baru dihapus.
    |
    */
    'retensi' => [
        'potongan' => (int) env('AMANPOLL_RETENSI_POTONGAN', 5000),
        'jeda_milidetik' => (int) env('AMANPOLL_RETENSI_JEDA_MILIDETIK', 100),
        // Batas waktu total satu jalan perintah; sisanya diteruskan jalan berikutnya.
        'batas_detik' => (int) env('AMANPOLL_RETENSI_BATAS_DETIK', 240),

        // Notifikasi Terkirim/Gagal: yang sudah dibaca atau bukan in-app.
        'notifikasi_hari' => env('AMANPOLL_RETENSI_NOTIFIKASI_HARI', 90),
        // Notifikasi in-app terkirim yang tidak pernah dibaca.
        'notifikasi_belum_dibaca_hari' => env('AMANPOLL_RETENSI_NOTIFIKASI_BELUM_DIBACA_HARI', 180),
        'kotak_keluar_selesai_hari' => env('AMANPOLL_RETENSI_KOTAK_KELUAR_SELESAI_HARI', 30),
        'kotak_keluar_gagal_hari' => env('AMANPOLL_RETENSI_KOTAK_KELUAR_GAGAL_HARI', 90),
        'panggilan_balik_berhasil_hari' => env('AMANPOLL_RETENSI_PANGGILAN_BALIK_BERHASIL_HARI', 30),
        // Hanya GagalPermanen; `Gagal` masih akan dicoba ulang.
        'panggilan_balik_gagal_hari' => env('AMANPOLL_RETENSI_PANGGILAN_BALIK_GAGAL_HARI', 90),
        // Selesai dan Dibatalkan; Gagal/Konflik menunggu keputusan manusia.
        'sinkronisasi_selesai_hari' => env('AMANPOLL_RETENSI_SINKRONISASI_SELESAI_HARI', 30),
        // Tayangan halaman pengunjung anonim yang tidak pernah menjadi prospek (~14 bulan, cukup untuk banding tahunan).
        'event_pemasaran_anonim_hari' => env('AMANPOLL_RETENSI_EVENT_PEMASARAN_ANONIM_HARI', 425),

        'catatan_audit' => [
            'arsip_setelah_hari' => env('AMANPOLL_RETENSI_CATATAN_AUDIT_ARSIP_SETELAH_HARI'),
            'disk' => env('AMANPOLL_RETENSI_CATATAN_AUDIT_DISK', 'local'),
            'folder' => env('AMANPOLL_RETENSI_CATATAN_AUDIT_FOLDER', 'arsip/catatan-audit'),
        ],

        'pemantau' => [
            'batas_byte' => (int) env('AMANPOLL_RETENSI_BATAS_BYTE_DB', 3 * 1024 * 1024 * 1024),
            'ambang_peringatan_persen' => (int) env('AMANPOLL_RETENSI_AMBANG_PERINGATAN_PERSEN', 70),
            'ambang_kritis_persen' => (int) env('AMANPOLL_RETENSI_AMBANG_KRITIS_PERSEN', 85),
            // Email ke admin platform aktif saat ambang terlewati; log selalu ditulis.
            'kirim_email_admin' => (bool) env('AMANPOLL_RETENSI_KIRIM_EMAIL_ADMIN', true),
        ],
    ],
];
