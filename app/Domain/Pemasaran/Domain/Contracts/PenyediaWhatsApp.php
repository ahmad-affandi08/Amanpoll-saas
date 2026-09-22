<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Contracts;

use App\Domain\Pemasaran\Domain\ValueObjects\PersetujuanTemplateWa;
use App\Domain\Pemasaran\Domain\ValueObjects\PesanWhatsApp;
use App\Domain\Pemasaran\Domain\ValueObjects\StatusKirimanWhatsApp;

/** Abstraksi penyedia WhatsApp Business (MARKETING.md 16, 28). */
interface PenyediaWhatsApp
{
    public function kode(): string;

    /** Mengembalikan pengenal pesan di sisi penyedia, dipakai untuk menyinkronkan statusnya nanti. */
    public function kirim(PesanWhatsApp $pesan): string;

    /**
     * Status terkini beberapa kiriman sekaligus.
     *
     * @param  list<string>  $idPesan
     * @return list<StatusKirimanWhatsApp>
     */
    public function statusKiriman(array $idPesan): array;

    /** Mengajukan satu template untuk ditinjau penyedia. */
    public function ajukanTemplate(string $kode, string $bahasa, string $kategori, string $isiTeks): PersetujuanTemplateWa;

    /** Menarik keputusan terkini penyedia atas satu template. */
    public function periksaTemplate(string $kode): PersetujuanTemplateWa;
}
