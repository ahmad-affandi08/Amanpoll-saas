<?php

return [
    'header_organisasi' => env('AMANPOLL_HEADER_ORGANISASI', 'X-Organisasi-Id'),
    'audit_aktif' => env('AMANPOLL_AUDIT_AKTIF', true),
    'mata_uang' => env('AMANPOLL_MATA_UANG', 'IDR'),
    'zona_waktu_default' => env('AMANPOLL_ZONA_WAKTU', 'Asia/Jakarta'),
    'retensi_catatan_akses_hari' => env('AMANPOLL_RETENSI_CATATAN_AKSES_HARI', 90),
    'disk_berkas' => env('AMANPOLL_DISK_BERKAS', 'local'),
];
