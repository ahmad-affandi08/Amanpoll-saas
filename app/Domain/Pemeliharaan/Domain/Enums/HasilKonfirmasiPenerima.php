<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Domain\Enums;

/** Jawaban penerima atas pekerjaan yang diserahkan teknisi (PRD 8.22). */
enum HasilKonfirmasiPenerima: string
{
    case Diterima = 'Diterima';

    /** Alasan wajib; perintah kerja kembali ke Dikerjakan dan teknisi diberi tahu. */
    case MasihBermasalah = 'MasihBermasalah';
}
