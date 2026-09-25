<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Domain\Contracts;

use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;

/**
 * Penyedia WhatsApp yang dapat mengantar notifikasi operasional ke staf, lewat nomor
 * platform maupun nomor milik organisasi.
 *
 * Kontraknya milik Notifikasi supaya engine notifikasi tidak mengenal adapter mana pun;
 * adapter WhatsApp (domain Pemasaran) yang mengimplementasikannya.
 */
interface DapatMengirimNotifikasiWhatsApp
{
    /**
     * Mengembalikan pengenal pesan di sisi penyedia.
     *
     * @param  string  $nomor  Nomor internasional tanpa tanda plus, mis. 6281234567890.
     */
    public function kirimNotifikasi(string $nomor, string $judul, string $isi): string;

    /**
     * Sama dengan `kirimNotifikasi()`, tetapi memakai kredensial yang diberikan, mis. nomor
     * WhatsApp milik organisasi, bukan kredensial platform (PRD 8.23).
     */
    public function kirimNotifikasiDengan(KredensialPenyedia $kredensial, string $nomor, string $judul, string $isi): string;
}
