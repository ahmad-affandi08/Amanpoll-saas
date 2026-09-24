<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Domain\Contracts;

/**
 * Penyedia WhatsApp platform yang dapat mengantar notifikasi operasional ke staf.
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
}
