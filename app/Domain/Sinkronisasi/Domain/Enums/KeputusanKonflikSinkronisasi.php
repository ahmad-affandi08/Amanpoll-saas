<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Domain\Enums;

/**
 * Pilihan penyelesaian konflik sinkronisasi (20.06). Tidak ada opsi yang
 * menimpa data server secara diam-diam: pengguna selalu memilih setelah
 * melihat versi server.
 */
enum KeputusanKonflikSinkronisasi: string
{
    /** Buang mutasi offline; versi server dipertahankan. */
    case PakaiServer = 'PakaiServer';

    /** Terapkan ulang mutasi offline di atas versi server terbaru. */
    case TerapkanUlang = 'TerapkanUlang';
}
